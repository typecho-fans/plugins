<?php

namespace qcloudcos;

require_once(__DIR__ . DIRECTORY_SEPARATOR . 'conf.php');
require_once(__DIR__ . DIRECTORY_SEPARATOR . 'error_code.php');
require_once(__DIR__ . DIRECTORY_SEPARATOR . 'http_client.php');
require_once(__DIR__ . DIRECTORY_SEPARATOR . 'libcurl_helper.php');
require_once(__DIR__ . DIRECTORY_SEPARATOR . 'libcurl_wrapper.php');

/**
 * Uploading file to cos slice by slice.
 */
class SliceUploading {
    // default task number for concurrently uploading slices.
    const DEFAULT_CONCURRENT_TASK_NUMBER = 3;

    private $timeoutMs;            // int: timeout in milliseconds for each http request.
    private $maxRetryCount;        // int: max retry count on failure.

    private $errorCode;            // int: last error code.
    private $errorMessage;         // string: last error message.
    private $requestId;            // string: request id for last http request.
    private $signature;            // string: signature for auth.
    private $srcFpath;             // string: source file path for uploading.
    private $url;                  // string: destination url for uploading.
    private $fileSize;             // int: source file size.
    private $sliceSize;            // int: slice size for each upload.
    private $session;              // string: session for each upload transaction.
    private $concurrentTaskNumber; // int: concurrent uploading task number.

    private $offset;               // int: current uploading offset.
    private $libcurlWrapper;       // LibcurlWrapper: curl wrapper for sending multi http request concurrently.

    private $accessUrl;            // string: access url.
    private $resourcePath;         // string: resource path.
    private $sourceUrl;            // string: source url.

    /**
     * timeoutMs: max timeout in milliseconds for each http request.
     * maxRetryCount: max retry count for uploading each slice on error.
     */
    public function __construct($timeoutMs, $maxRetryCount) {
        $this->timeoutMs = $timeoutMs;
        $this->maxRetryCount = $maxRetryCount;
        $this->errorCode = COSAPI_SUCCESS;
        $this->errorMessage = '';
        $this->concurrentTaskNumber = self::DEFAULT_CONCURRENT_TASK_NUMBER;

        $this->offset = 0;

        $this->libcurlWrapper = new LibcurlWrapper();
    }

    public function __destruct() {
    }

    public function getLastErrorCode() {
        return $this->errorCode;
    }

    public function getLastErrorMessage() {
        return $this->errorMessage;
    }

    public function getRequestId() {
        return $this->requestId;
    }

    public function getAccessUrl() {
        return $this->accessUrl;
    }

    public function getResourcePath() {
        return $this->resourcePath;
    }

    public function getSourceUrl() {
        return $this->sourceUrl;
    }

    /**
     * Return true on success and return false on failure.
     */
    public function initUploading(
            $signature, $srcFpath, $url, $fileSize, $sliceSize, $bizAttr, $insertOnly) {
        $this->signature = $signature;
        $this->srcFpath = $srcFpath;
        $this->url = $url;
        $this->fileSize = $fileSize;
        $this->sliceSize = $sliceSize;

        // Clear error so caller can successfully retry.
        $this->clearError();

        $request = array(
            'url' => $url,
            'method' => 'post',
            'timeout' => $this->timeoutMs / 1000,
            'data' => array(
                'op' => 'upload_slice_init',
                'filesize' => $fileSize,
                'slice_size' => $sliceSize,
                'insertOnly' => $insertOnly,
            ),
            'header' => array(
                'Authorization: ' . $signature,
            ),
        );

        if (isset($bizAttr) && strlen($bizAttr)) {
            $request['data']['biz_attr'] = $bizAttr;
        }

        $response = $this->sendRequest($request);
        if ($response === false) {
            return false;
        }
        $this->session = $response['data']['session'];

        if (isset($response['data']['slice_size'])) {
            $this->sliceSize = $response['data']['slice_size'];
        }

        if (isset($response['data']['serial_upload']) && $response['data']['serial_upload'] == 1) {
            $this->concurrentTaskNumber = 1;
        }

        return true;
    }

    /**
     * Return true on success and return false on failure.
     */
    public function performUploading() {
        for ($i = 0; $i < $this->concurrentTaskNumber; ++$i) {
            if ($this->offset >= $this->fileSize) {
                break;
            }

            $sliceContent = file_get_contents($this->srcFpath, false, null, $this->offset, $this->sliceSize);
            if ($sliceContent === false) {
                $this->setError(COSAPI_PARAMS_ERROR, 'read file ' . $this->srcFpath . ' error');
                return false;
            }

            $request = new HttpRequest();
            $request->timeoutMs = $this->timeoutMs;
            $request->url = $this->url;
            $request->method = 'POST';
            $request->customHeaders = array(
                        'Authorization: ' . $this->signature,
                    );
            $request->dataToPost = array(
                        'op' => 'upload_slice_data',
                        'session' => $this->session,
                        'offset' => $this->offset,
                        'filecontent' => $sliceContent,
                        'datamd5' => md5($sliceContent),
                    );
            $request->userData = array(
                        'retryCount' => 0,
                    );

            $this->libcurlWrapper->startSendingRequest($request, array($this, 'uploadCallback'));

            $this->offset += $this->sliceSize;
        }

        $this->libcurlWrapper->performSendingRequest();

        if ($this->errorCode !== COSAPI_SUCCESS) {
            return false;
        }

        return true;
    }

    /**
     * Return true on success and return false on failure.
     */
    public function finishUploading() {
        $request = array(
            'url' => $this->url,
            'method' => 'post',
            'timeout' => $this->timeoutMs / 1000,
            'data' => array(
                'op' => 'upload_slice_finish',
                'session' => $this->session,
                'filesize' => $this->fileSize,
            ),
            'header' => array(
                'Authorization: ' . $this->signature,
            ),
        );

        $response = $this->sendRequest($request);
        if ($response === false) {
            return false;
        }

        $this->accessUrl = $response['data']['access_url'];
        $this->resourcePath = $response['data']['resource_path'];
        $this->sourceUrl = $response['data']['source_url'];

        return true;
    }

    private function sendRequest($request) {
        $response = HttpClient::sendRequest($request);
        if ($response === false) {
            $this->setError(COSAPI_NETWORK_ERROR, 'network error');
            return false;
        }

        $responseJson = json_decode($response, true);
        if ($responseJson === NULL) {
            $this->setError(COSAPI_NETWORK_ERROR, 'network error');
            return false;
        }

        $this->requestId = $responseJson['request_id'];
        if ($responseJson['code'] != 0) {
            $this->setError($responseJson['code'], $responseJson['message']);
            return false;
        }

        return $responseJson;
    }

    private function clearError() {
        $this->errorCode = COSAPI_SUCCESS;
        $this->errorMessage = 'success';
    }

    private function setError($errorCode, $errorMessage) {
        $this->errorCode = $errorCode;
        $this->errorMessage = $errorMessage;
    }

    public function uploadCallback($request, $response) {
        if ($this->errorCode !== COSAPI_SUCCESS) {
            return;
        }

        $requestErrorCode = COSAPI_SUCCESS;
        $requestErrorMessage = 'success';
        $retryCount = $request->userData['retryCount'];

        $responseJson = json_decode($response->body, true);
        if ($responseJson === NULL) {
            $requestErrorCode = COSAPI_NETWORK_ERROR;
            $requestErrorMessage = 'network error';
        }

        if ($response->curlErrorCode !== CURLE_OK) {
            $requestErrorCode = COSAPI_NETWORK_ERROR;
            $requestErrorMessage = 'network error: curl errno ' . $response->curlErrorCode;
        }

        $this->requestId = $responseJson['request_id'];
        if ($responseJson['code'] != 0) {
            $requestErrorCode = $responseJson['code'];
            $requestErrorMessage = $responseJson['message'];
        }

        if (isset($responseJson['data']['datamd5']) &&
                $responseJson['data']['datamd5'] !== $request->dataToPost['datamd5']) {
            $requestErrorCode = COSAPI_INTEGRITY_ERROR;
            $requestErrorMessage = 'cosapi integrity error';
        }

        if ($requestErrorCode !== COSAPI_SUCCESS) {
            if ($retryCount >= $this->maxRetryCount) {
                $this->setError($requestErrorCode, $requestErrorMessage);
            } else {
                $request->userData['retryCount'] += 1;
                $this->libcurlWrapper->startSendingRequest($request, array($this, 'uploadCallback'));
            }
            return;
        }

        if ($this->offset >= $this->fileSize) {
            return;
        }

        // Send next slice.
        $nextSliceContent = file_get_contents($this->srcFpath, false, null, $this->offset, $this->sliceSize);
        if ($nextSliceContent === false) {
            $this->setError(COSAPI_PARAMS_ERROR, 'read file ' . $this->srcFpath . ' error');
            return;
        }

        $nextSliceRequest = new HttpRequest();
        $nextSliceRequest->timeoutMs = $this->timeoutMs;
        $nextSliceRequest->url = $this->url;
        $nextSliceRequest->method = 'POST';
        $nextSliceRequest->customHeaders = array(
                    'Authorization: ' . $this->signature,
                );
        $nextSliceRequest->dataToPost = array(
                    'op' => 'upload_slice_data',
                    'session' => $this->session,
                    'offset' => $this->offset,
                    'filecontent' => $nextSliceContent,
                    'datamd5' => md5($nextSliceContent),
                );
        $nextSliceRequest->userData = array(
                    'retryCount' => 0,
                );

        $this->libcurlWrapper->startSendingRequest($nextSliceRequest, array($this, 'uploadCallback'));

        $this->offset += $this->sliceSize;
    }
}
