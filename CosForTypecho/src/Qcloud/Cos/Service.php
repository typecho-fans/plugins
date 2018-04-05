<?php

namespace Qcloud\Cos;

// http://guzzle3.readthedocs.io/webservice-client/guzzle-service-descriptions.html
class Service {
    public static function getService() {
        return array(
                'name' => 'Cos Service',
                'apiVersion' => 'V5',
                'description' => 'Cos V5 API Service',

                'operations' => array(
                    'AbortMultipartUpload' => array(
                        'httpMethod' => 'DELETE',
                        'uri' => '/{Bucket}{/Key*}',
                        'class' => 'Qcloud\\Cos\\Command',
                        'responseClass' => 'AbortMultipartUploadOutput',
                        'responseType' => 'model',
                        'parameters' => array(
                            'Bucket' => array(
                                'required' => true,
                                'type' => 'string',
                                'location' => 'uri'),
                            'Key' => array(
                                'required' => true,
                                'type' => 'string',
                                'location' => 'uri',
                                'minLength' => 1),
                            'UploadId' => array(
                                'required' => true,
                                'type' => 'string',
                                'location' => 'query',
                                'sentAs' => 'uploadId')),
                        'errorResponses' => array(
                                array(
                                    'reason' => 'The specified multipart upload does not exist.',
                                    'class' => 'NoSuchUploadException'))),
                    'CreateBucket' => array(
                        'httpMethod' => 'PUT',
                        'uri' => '/{Bucket}',
                        'class' => 'Qcloud\\Cos\\Command',
                        'responseClass' => 'CreateBucketOutput',
                        'responseType' => 'model',
                        'data' => array(
                            'xmlRoot' => array(
                                'name' => 'CreateBucketConfiguration')),
                        'parameters' => array(
                            'ACL' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'x-cos-acl'),
                            'Bucket' => array(
                                'required' => true,
                                'type' => 'string',
                                'location' => 'uri')),
                        'errorResponses' => array(
                            array(
                                'reason' => 'The requested bucket name is not available. The bucket namespace is shared by all users of the system. Please select a different name and try again.',
                                'class' => 'BucketAlreadyExistsException'))),
                    'CompleteMultipartUpload' => array(
                        'httpMethod' => 'POST',
                        'uri' => '/{Bucket}{/Key*}',
                        'class' => 'Qcloud\\Cos\\Command',
                        'responseClass' => 'CompleteMultipartUploadOutput',
                        'responseType' => 'model',
                        'data' => array(
                            'xmlRoot' => array(
                                'name' => 'CompleteMultipartUpload')),
                        'parameters' => array(
                            'Bucket' => array(
                                'required' => true,
                                'type' => 'string',
                                'location' => 'uri'),
                            'Key' => array(
                                'required' => true,
                                'type' => 'string',
                                'location' => 'uri',
                                'minLength' => 1),
                            'Parts' => array(
                                'type' => 'array',
                                'location' => 'xml',
                                'data' => array(
                                    'xmlFlattened' => true),
                                'items' => array(
                                    'name' => 'CompletedPart',
                                    'type' => 'object',
                                    'sentAs' => 'Part',
                                    'properties' => array(
                                        'ETag' => array(
                                            'type' => 'string'),
                                        'PartNumber' => array(
                                            'type' => 'numeric')))),
                            'UploadId' => array(
                                'required' => true,
                                'type' => 'string',
                                'location' => 'query',
                                'sentAs' => 'uploadId'),
                            'command.expects' => array(
                                'static' => true,
                                'default' => 'application/xml'))),
                    'CreateMultipartUpload' => array(
                        'httpMethod' => 'POST',
                        'uri' => '/{Bucket}{/Key*}?uploads',
                        'class' => 'Qcloud\\Cos\\Command',
                        'responseClass' => 'CreateMultipartUploadOutput',
                        'responseType' => 'model',
                        'data' => array(
                            'xmlRoot' => array(
                                'name' => 'CreateMultipartUploadRequest')),
                        'parameters' => array(
                            'ACL' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'x-cos-acl',
                            ),
                            'Bucket' => array(
                                'required' => true,
                                'type' => 'string',
                                'location' => 'uri',
                            ),
                            'CacheControl' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'Cache-Control',
                            ),
                            'ContentDisposition' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'Content-Disposition',
                            ),
                            'ContentEncoding' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'Content-Encoding',
                            ),
                            'ContentLanguage' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'Content-Language',
                            ),
                            'ContentType' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'Content-Type',
                            ),
                            'Expires' => array(
                                'type' => array(
                                    'object',
                                    'string',
                                    'integer',
                                ),
                                'format' => 'date-time-http',
                                'location' => 'header',
                            ),
                            'GrantFullControl' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'x-cos-grant-full-control',
                            ),
                            'GrantRead' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'x-cos-grant-read',
                            ),
                            'GrantReadACP' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'x-cos-grant-read-acp',
                            ),
                            'GrantWriteACP' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'x-cos-grant-write-acp',
                            ),
                            'Key' => array(
                                'required' => true,
                                'type' => 'string',
                                'location' => 'uri',
                                'minLength' => 1,
                            ),
                            'Metadata' => array(
                                'type' => 'object',
                                'location' => 'header',
                                'sentAs' => 'x-cos-meta-',
                                'additionalProperties' => array(
                                    'type' => 'string',
                                ),
                            ),
                            'ServerSideEncryption' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'x-cos-server-side-encryption',
                            ),
                            'StorageClass' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'x-cos-storage-class',
                            ),
                            'WebsiteRedirectLocation' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'x-cos-website-redirect-location',
                            ),
                            'SSECustomerAlgorithm' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'x-cos-server-side-encryption-customer-algorithm',
                            ),
                            'SSECustomerKey' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'x-cos-server-side-encryption-customer-key',
                            ),
                            'SSECustomerKeyMD5' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'x-cos-server-side-encryption-customer-key-MD5',
                            ),
                            'SSEKMSKeyId' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'x-cos-server-side-encryption-aws-kms-key-id',
                            ),
                            'RequestPayer' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'x-cos-request-payer',
                            ),
                            'ACP' => array(
                                'type' => 'object',
                                'additionalProperties' => true,
                            ),
                            'command.expects' => array(
                                'static' => true,
                                'default' => 'application/xml',
                            ))),
                    'CopyObject' => array(
                        'httpMethod' => 'PUT',
                        'uri' => '/{Bucket}{/Key*}',
                        'class' => 'Qcloud\\Cos\\Command',
                        'responseClass' => 'CopyObjectOutput',
                        'responseType' => 'model',
                        'data' => array(
                            'xmlRoot' => array(
                                'name' => 'CopyObjectRequest',
                            ),
                        ),
                        'parameters' => array(
                            'ACL' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'x-cos-acl',
                            ),
                            'Bucket' => array(
                                'required' => true,
                                'type' => 'string',
                                'location' => 'uri',
                            ),
                            'CacheControl' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'Cache-Control',
                            ),
                            'ContentDisposition' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'Content-Disposition',
                            ),
                            'ContentEncoding' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'Content-Encoding',
                            ),
                            'ContentLanguage' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'Content-Language',
                            ),
                            'ContentType' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'Content-Type',
                            ),
                            'CopySource' => array(
                                'required' => true,
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'x-cos-copy-source',
                            ),
                            'CopySourceIfMatch' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'x-cos-copy-source-if-match',
                            ),
                            'CopySourceIfModifiedSince' => array(
                                'type' => array(
                                    'object',
                                    'string',
                                    'integer',
                                ),
                                'format' => 'date-time-http',
                                'location' => 'header',
                                'sentAs' => 'x-cos-copy-source-if-modified-since',
                            ),
                            'CopySourceIfNoneMatch' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'x-cos-copy-source-if-none-match',
                            ),
                            'CopySourceIfUnmodifiedSince' => array(
                                'type' => array(
                                    'object',
                                    'string',
                                    'integer',
                                ),
                                'format' => 'date-time-http',
                                'location' => 'header',
                                'sentAs' => 'x-cos-copy-source-if-unmodified-since',
                            ),
                            'Expires' => array(
                                'type' => array(
                                    'object',
                                    'string',
                                    'integer',
                                ),
                                'format' => 'date-time-http',
                                'location' => 'header',
                            ),
                            'GrantFullControl' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'x-cos-grant-full-control',
                            ),
                            'GrantRead' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'x-cos-grant-read',
                            ),
                            'GrantReadACP' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'x-cos-grant-read-acp',
                            ),
                            'GrantWriteACP' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'x-cos-grant-write-acp',
                            ),
                            'Key' => array(
                                'required' => true,
                                'type' => 'string',
                                'location' => 'uri',
                                'minLength' => 1,
                            ),
                            'Metadata' => array(
                                'type' => 'object',
                                'location' => 'header',
                                'sentAs' => 'x-cos-meta-',
                                'additionalProperties' => array(
                                    'type' => 'string',
                                ),
                            ),
                            'MetadataDirective' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'x-cos-metadata-directive',
                            ),
                            'ServerSideEncryption' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'x-cos-server-side-encryption',
                            ),
                            'StorageClass' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'x-cos-storage-class',
                            ),
                            'WebsiteRedirectLocation' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'x-cos-website-redirect-location',
                            ),
                            'SSECustomerAlgorithm' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'x-cos-server-side-encryption-customer-algorithm',
                            ),
                            'SSECustomerKey' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'x-cos-server-side-encryption-customer-key',
                            ),
                            'CopySourceSSECustomerAlgorithm' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'x-cos-copy-source-server-side-encryption-customer-algorithm',
                            ),
                            'CopySourceSSECustomerKey' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'x-cos-copy-source-server-side-encryption-customer-key',
                            ),
                            'CopySourceSSECustomerKeyMD5' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'x-cos-copy-source-server-side-encryption-customer-key-MD5',
                            ),
                            'RequestPayer' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'x-cos-request-payer',
                            ),
                            'ACP' => array(
                                'type' => 'object',
                                'additionalProperties' => true,
                            ),
                            'command.expects' => array(
                                'static' => true,
                                'default' => 'application/xml',
                            ),
                        ),
                        'errorResponses' => array(
                            array(
                                'reason' => 'The source object of the COPY operation is not in the active tier and is only stored in Amazon Glacier.',
                                'class' => 'ObjectNotInActiveTierErrorException',
                            ),
                        ),
                    ),
                    'DeleteBucket' => array(
                        'httpMethod' => 'DELETE',
                        'uri' => '/{Bucket}',
                        'class' => 'Qcloud\\Cos\\Command',
                        'responseClass' => 'DeleteBucketOutput',
                        'responseType' => 'model',
                        'parameters' => array(
                            'Bucket' => array(
                                'required' => true,
                                'type' => 'string',
                                'location' => 'uri'))),
                    'DeleteBucketCors' => array(
                        'httpMethod' => 'DELETE',
                        'uri' => '/{Bucket}?cors',
                        'class' => 'Qcloud\\Cos\\Command',
                        'responseClass' => 'DeleteBucketCorsOutput',
                        'responseType' => 'model',
                        'parameters' => array(
                            'Bucket' => array(
                                'required' => true,
                                'type' => 'string',
                                'location' => 'uri',
                            ),
                        ),
                    ),
                    'DeleteObject' => array(
                        'httpMethod' => 'DELETE',
                        'uri' => '/{Bucket}{/Key*}',
                        'class' => 'Qcloud\\Cos\\Command',
                        'responseClass' => 'DeleteObjectOutput',
                        'responseType' => 'model',
                        'parameters' => array(
                            'Bucket' => array(
                                'required' => true,
                                'type' => 'string',
                                'location' => 'uri'),
                            'Key' => array(
                                'required' => true,
                                'type' => 'string',
                                'location' => 'uri',
                                'minLength' => 1),
                            'MFA' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'x-cos-mfa',
                            ),
                            'VersionId' => array(
                                'type' => 'string',
                                'location' => 'query',
                                'sentAs' => 'versionId',
                            ),
                            'RequestPayer' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'x-cos-request-payer',
                            ),)),
                    'DeleteObjects' => array(
                        'httpMethod' => 'POST',
                        'uri' => '/{Bucket}?delete',
                        'class' => 'Qcloud\\Cos\\Command',
                        'responseClass' => 'DeleteObjectsOutput',
                        'responseType' => 'model',
                        'data' => array(
                            'xmlRoot' => array(
                                'name' => 'Delete',
                            ),
                            'contentMd5' => true,
                        ),
                        'parameters' => array(
                            'Bucket' => array(
                                'required' => true,
                                'type' => 'string',
                                'location' => 'uri',
                            ),
                            'Objects' => array(
                                'required' => true,
                                'type' => 'array',
                                'location' => 'xml',
                                'data' => array(
                                    'xmlFlattened' => true,
                                ),
                                'items' => array(
                                    'name' => 'ObjectIdentifier',
                                    'type' => 'object',
                                    'sentAs' => 'Object',
                                    'properties' => array(
                                        'Key' => array(
                                            'required' => true,
                                            'type' => 'string',
                                            'minLength' => 1,
                                        ),
                                        'VersionId' => array(
                                            'type' => 'string',
                                        ),
                                    ),
                                ),
                            ),
                            'Quiet' => array(
                                'type' => 'boolean',
                                'format' => 'boolean-string',
                                'location' => 'xml',
                            ),
                            'MFA' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'x-cos-mfa',
                            ),
                            'RequestPayer' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'x-cos-request-payer',
                            ),
                            'command.expects' => array(
                                'static' => true,
                                'default' => 'application/xml',
                            ),
                        ),
                    ),
                    'DeleteBucketLifecycle' => array(
                        'httpMethod' => 'DELETE',
                        'uri' => '/{Bucket}?lifecycle',
                        'class' => 'Qcloud\\Cos\\Command',
                        'responseClass' => 'DeleteBucketLifecycleOutput',
                        'responseType' => 'model',
                        'parameters' => array(
                            'Bucket' => array(
                                'required' => true,
                                'type' => 'string',
                                'location' => 'uri',
                            ),
                        ),
                    ),
                    'DeleteBucketReplication' => array(
                        'httpMethod' => 'DELETE',
                        'uri' => '/{Bucket}?replication',
                        'class' => 'Qcloud\\Cos\\Command',
                        'responseClass' => 'DeleteBucketReplicationOutput',
                        'responseType' => 'model',
                        'parameters' => array(
                            'Bucket' => array(
                                'required' => true,
                                'type' => 'string',
                                'location' => 'uri',
                            ),
                        ),
                    ),
                    'GetObject' => array(
                        'httpMethod' => 'GET',
                        'uri' => '/{Bucket}{/Key*}',
                        'class' => 'Qcloud\\Cos\\Command',
                        'responseClass' => 'GetObjectOutput',
                        'responseType' => 'model',
                        'parameters' => array(
                            'Bucket' => array(
                                'required' => true,
                                'type' => 'string',
                                'location' => 'uri'),
                            'IfMatch' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'If-Match'),
                            'IfModifiedSince' => array(
                                'type' => array(
                                    'object',
                                    'string',
                                    'integer'),
                                'format' => 'date-time-http',
                                'location' => 'header',
                                'sentAs' => 'If-Modified-Since'),
                            'IfNoneMatch' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'If-None-Match'),
                            'IfUnmodifiedSince' => array(
                                'type' => array(
                                    'object',
                                    'string',
                                    'integer'),
                                'format' => 'date-time-http',
                                'location' => 'header',
                                'sentAs' => 'If-Unmodified-Since'),
                            'Key' => array(
                                'required' => true,
                                'type' => 'string',
                                'location' => 'uri',
                                'minLength' => 1),
                            'Range' => array(
                                'type' => 'string',
                                'location' => 'header'),
                            'ResponseCacheControl' => array(
                                'type' => 'string',
                                'location' => 'query',
                                'sentAs' => 'response-cache-control'),
                            'ResponseContentDisposition' => array(
                                'type' => 'string',
                                'location' => 'query',
                                'sentAs' => 'response-content-disposition'),
                            'ResponseContentEncoding' => array(
                                'type' => 'string',
                                'location' => 'query',
                                'sentAs' => 'response-content-encoding'),
                            'ResponseContentLanguage' => array(
                                'type' => 'string',
                                'location' => 'query',
                                'sentAs' => 'response-content-language'),
                            'ResponseContentType' => array(
                                'type' => 'string',
                                'location' => 'query',
                                'sentAs' => 'response-content-type'),
                            'ResponseExpires' => array(
                                'type' => array(
                                    'object',
                                    'string',
                                    'integer'),
                                'format' => 'date-time-http',
                                'location' => 'query',
                                'sentAs' => 'response-expires'),
                            'VersionId' => array(
                                'type' => 'string',
                                'location' => 'query',
                                'sentAs' => 'versionId',
                            ),
                            'SaveAs' => array(
                                'location' => 'response_body')),
                        'errorResponses' => array(
                            array(
                                'reason' => 'The specified key does not exist.',
                                'class' => 'NoSuchKeyException'))),
                    'GetObjectAcl' => array(
                        'httpMethod' => 'GET',
                        'uri' => '/{Bucket}{/Key*}?acl',
                        'class' => 'Qcloud\\Cos\\Command',
                        'responseClass' => 'GetObjectAclOutput',
                        'responseType' => 'model',
                        'parameters' => array(
                            'Bucket' => array(
                                'required' => true,
                                'type' => 'string',
                                'location' => 'uri',
                            ),
                            'Key' => array(
                                'required' => true,
                                'type' => 'string',
                                'location' => 'uri',
                                'minLength' => 1,
                            ),
                            'VersionId' => array(
                                'type' => 'string',
                                'location' => 'query',
                                'sentAs' => 'versionId',
                            ),
                            'RequestPayer' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'x-cos-request-payer',
                            ),
                            'command.expects' => array(
                                'static' => true,
                                'default' => 'application/xml',
                            ),
                        ),
                        'errorResponses' => array(
                            array(
                                'reason' => 'The specified key does not exist.',
                                'class' => 'NoSuchKeyException',
                            ),
                        ),
                    ),
                    'GetBucketAcl' => array(
                        'httpMethod' => 'GET',
                        'uri' => '/{Bucket}?acl',
                        'class' => 'Qcloud\\Cos\\Command',
                        'responseClass' => 'GetBucketAclOutput',
                        'responseType' => 'model',
                        'parameters' => array(
                            'Bucket' => array(
                                'required' => true,
                                'type' => 'string',
                                'location' => 'uri'),
                            'command.expects' => array(
                                'static' => true,
                                'default' => 'application/xml'))),
                    'GetBucketCors' => array(
                        'httpMethod' => 'GET',
                        'uri' => '/{Bucket}?cors',
                        'class' => 'Qcloud\\Cos\\Command',
                        'responseClass' => 'GetBucketCorsOutput',
                        'responseType' => 'model',
                        'parameters' => array(
                            'Bucket' => array(
                                'required' => true,
                                'type' => 'string',
                                'location' => 'uri',
                            ),
                            'command.expects' => array(
                                'static' => true,
                                'default' => 'application/xml',
                            ),
                        ),
                    ),
                    'GetBucketLifecycle' => array(
                        'httpMethod' => 'GET',
                        'uri' => '/{Bucket}?lifecycle',
                        'class' => 'Qcloud\\Cos\\Command',
                        'responseClass' => 'GetBucketLifecycleOutput',
                        'responseType' => 'model',
                        'parameters' => array(
                            'Bucket' => array(
                                'required' => true,
                                'type' => 'string',
                                'location' => 'uri',
                            ),
                            'command.expects' => array(
                                'static' => true,
                                'default' => 'application/xml',
                            ),
                        ),
                    ),
                    'GetBucketVersioning' => array(
                        'httpMethod' => 'GET',
                        'uri' => '/{Bucket}?versioning',
                        'class' => 'Qcloud\\Cos\\Command',
                        'responseClass' => 'GetBucketVersioningOutput',
                        'responseType' => 'model',
                        'parameters' => array(
                            'Bucket' => array(
                                'required' => true,
                                'type' => 'string',
                                'location' => 'uri',
                            ),
                            'command.expects' => array(
                                'static' => true,
                                'default' => 'application/xml',
                            ),
                        ),
                    ),
                    'GetBucketReplication' => array(
                        'httpMethod' => 'GET',
                        'uri' => '/{Bucket}?replication',
                        'class' => 'Qcloud\\Cos\\Command',
                        'responseClass' => 'GetBucketReplicationOutput',
                        'responseType' => 'model',
                        'parameters' => array(
                            'Bucket' => array(
                                'required' => true,
                                'type' => 'string',
                                'location' => 'uri',
                            ),
                            'command.expects' => array(
                                'static' => true,
                                'default' => 'application/xml',
                            ),
                        ),
                    ),
                    'GetBucketLocation' => array(
                        'httpMethod' => 'GET',
                        'uri' => '/{Bucket}?location',
                        'class' => 'Qcloud\\Cos\\Command',
                        'responseClass' => 'GetBucketLocationOutput',
                        'responseType' => 'model',
                        'parameters' => array(
                            'Bucket' => array(
                                'required' => true,
                                'type' => 'string',
                                'location' => 'uri',
                            ),
                        ),
                    ),
                    'UploadPart' => array(
                        'httpMethod' => 'PUT',
                        'uri' => '/{Bucket}{/Key*}',
                        'class' => 'Qcloud\\Cos\\Command',
                        'responseClass' => 'UploadPartOutput',
                        'responseType' => 'model',
                        'data' => array(
                            'xmlRoot' => array(
                                'name' => 'UploadPartRequest')),
                        'parameters' => array(
                            'Body' => array(
                                'type' => array(
                                    'string',
                                    'object'),
                                'location' => 'body'),
                            'Bucket' => array(
                                'required' => true,
                                'type' => 'string',
                                'location' => 'uri'),
                            'ContentLength' => array(
                                'type' => 'numeric',
                                'location' => 'header',
                                'sentAs' => 'Content-Length'),
                            'ContentMD5' => array(
                                'type' => array(
                                    'string',
                                    'boolean'),
                                'location' => 'header',
                                'sentAs' => 'Content-MD5'),
                            'Key' => array(
                                'required' => true,
                                'type' => 'string',
                                'location' => 'uri',
                                'minLength' => 1),
                            'PartNumber' => array(
                                'required' => true,
                                'type' => 'numeric',
                                'location' => 'query',
                                'sentAs' => 'partNumber'),
                            'UploadId' => array(
                                'required' => true,
                                'type' => 'string',
                                'location' => 'query',
                                'sentAs' => 'uploadId'),
                            'ServerSideEncryption' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'x-cos-server-side-encryption',
                            ),
                            'SSECustomerAlgorithm' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'x-cos-server-side-encryption-customer-algorithm',
                            ),
                            'SSECustomerKey' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'x-cos-server-side-encryption-customer-key',
                            ),
                            'SSECustomerKeyMD5' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'x-cos-server-side-encryption-customer-key-MD5',
                            ),
                            'RequestPayer' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'x-cos-request-payer',
                            ))),
                    'PutObject' => array(
                        'httpMethod' => 'PUT',
                        'uri' => '/{Bucket}{/Key*}',
                        'class' => 'Qcloud\\Cos\\Command',
                        'responseClass' => 'PutObjectOutput',
                        'responseType' => 'model',
                        'data' => array(
                            'xmlRoot' => array(
                                'name' => 'PutObjectRequest')),
                        'parameters' => array(
                            'ACL' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'x-cos-acl'),
                            'Body' => array(
                                'type' => array(
                                    'string',
                                    'object'),
                                'location' => 'body'),
                            'Bucket' => array(
                                'required' => true,
                                'type' => 'string',
                                'location' => 'uri'),
                            'CacheControl' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'Cache-Control'),
                            'ContentDisposition' => array(
                                    'type' => 'string',
                                    'location' => 'header',
                                    'sentAs' => 'Content-Disposition'),
                            'ContentEncoding' => array(
                                    'type' => 'string',
                                    'location' => 'header',
                                    'sentAs' => 'Content-Encoding'),
                            'ContentLanguage' => array(
                                    'type' => 'string',
                                    'location' => 'header',
                                    'sentAs' => 'Content-Language'),
                            'ContentLength' => array(
                                    'type' => 'numeric',
                                    'location' => 'header',
                                    'sentAs' => 'Content-Length'),
                            'ContentMD5' => array(
                                    'type' => array(
                                        'string',
                                        'boolean'),
                                    'location' => 'header',
                                    'sentAs' => 'Content-MD5'),
                            'ContentType' => array(
                                    'type' => 'string',
                                    'location' => 'header',
                                    'sentAs' => 'Content-Type'),
                            'Key' => array(
                                    'required' => true,
                                    'type' => 'string',
                                    'location' => 'uri',
                                    'minLength' => 1),
                            'Metadata' => array(
                                    'type' => 'object',
                                    'location' => 'header',
                                    'sentAs' => 'x-cos-meta-',
                                    'additionalProperties' => array(
                                        'type' => 'string')
                            ),
                            'ServerSideEncryption' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'x-cos-server-side-encryption',
                            ),
                            'StorageClass' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'x-cos-storage-class',
                            ),
                            'WebsiteRedirectLocation' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'x-cos-website-redirect-location',
                            ),
                            'SSECustomerAlgorithm' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'x-cos-server-side-encryption-customer-algorithm',
                            ),
                            'SSECustomerKey' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'x-cos-server-side-encryption-customer-key',
                            ),
                            'SSECustomerKeyMD5' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'x-cos-server-side-encryption-customer-key-MD5',
                            ),
                            'SSEKMSKeyId' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'x-cos-server-side-encryption-aws-kms-key-id',
                            ),
                            'RequestPayer' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'x-cos-request-payer',
                            ),
                            'ACP' => array(
                                'type' => 'object',
                                'additionalProperties' => true,
                            ))),
                    'PutObjectAcl' => array(
                        'httpMethod' => 'PUT',
                        'uri' => '/{Bucket}{/Key*}?acl',
                        'class' => 'Qcloud\\Cos\\Command',
                        'responseClass' => 'PutObjectAclOutput',
                        'responseType' => 'model',
                        'data' => array(
                            'xmlRoot' => array(
                                'name' => 'AccessControlPolicy',
                            ),
                        ),
                        'parameters' => array(
                            'ACL' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'x-cos-acl',
                            ),
                            'Grants' => array(
                                'type' => 'array',
                                'location' => 'xml',
                                'sentAs' => 'AccessControlList',
                                'items' => array(
                                    'name' => 'Grant',
                                    'type' => 'object',
                                    'properties' => array(
                                        'Grantee' => array(
                                            'type' => 'object',
                                            'properties' => array(
                                                'DisplayName' => array(
                                                    'type' => 'string'),
                                                'ID' => array(
                                                    'type' => 'string'),
                                                'Type' => array(
                                                    'type' => 'string',
                                                    'sentAs' => 'xsi:type',
                                                    'data' => array(
                                                        'xmlAttribute' => true,
                                                        'xmlNamespace' => 'http://www.w3.org/2001/XMLSchema-instance')),
                                                'URI' => array(
                                                    'type' => 'string') )),
                                        'Permission' => array(
                                            'type' => 'string',
                                        ),
                                    ),
                                ),
                            ),
                            'Owner' => array(
                                'type' => 'object',
                                'location' => 'xml',
                                'properties' => array(
                                    'DisplayName' => array(
                                        'type' => 'string',
                                    ),
                                    'ID' => array(
                                        'type' => 'string',
                                    ),
                                ),
                            ),
                            'Bucket' => array(
                                'required' => true,
                                'type' => 'string',
                                'location' => 'uri',
                            ),
                            'GrantFullControl' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'x-cos-grant-full-control',
                            ),
                            'GrantRead' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'x-cos-grant-read',
                            ),
                            'GrantReadACP' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'x-cos-grant-read-acp',
                            ),
                            'GrantWrite' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'x-cos-grant-write',
                            ),
                            'GrantWriteACP' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'x-cos-grant-write-acp',
                            ),
                            'Key' => array(
                                'required' => true,
                                'type' => 'string',
                                'location' => 'uri',
                                'minLength' => 1,
                            ),
                            'RequestPayer' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'x-cos-request-payer',
                            ),
                            'ACP' => array(
                                'type' => 'object',
                                'additionalProperties' => true,
                            ),
                        ),
                        'errorResponses' => array(
                            array(
                                'reason' => 'The specified key does not exist.',
                                'class' => 'NoSuchKeyException',
                            ),
                        ),
                    ),
                    'PutBucketAcl' => array(
                        'httpMethod' => 'PUT',
                        'uri' => '/{Bucket}?acl',
                        'class' => 'Qcloud\\Cos\\Command',
                        'responseClass' => 'PutBucketAclOutput',
                        'responseType' => 'model',
                        'data' => array(
                            'xmlRoot' => array(
                                'name' => 'AccessControlPolicy',
                            ),
                        ),
                        'parameters' => array(
                            'ACL' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'x-cos-acl',
                            ),
                            'Grants' => array(
                                'type' => 'array',
                                'location' => 'xml',
                                'sentAs' => 'AccessControlList',
                                'items' => array(
                                    'name' => 'Grant',
                                    'type' => 'object',
                                    'properties' => array(
                                        'Grantee' => array(
                                            'type' => 'object',
                                            'properties' => array(
                                                'DisplayName' => array(
                                                    'type' => 'string',
                                                ),
                                                'EmailAddress' => array(
                                                    'type' => 'string',
                                                ),
                                                'ID' => array(
                                                    'type' => 'string',
                                                ),
                                                'Type' => array(
                                                    'required' => true,
                                                    'type' => 'string',
                                                    'sentAs' => 'xsi:type',
                                                    'data' => array(
                                                        'xmlAttribute' => true,
                                                        'xmlNamespace' => 'http://www.w3.org/2001/XMLSchema-instance',
                                                    ),
                                                ),
                                                'URI' => array(
                                                    'type' => 'string',
                                                ),
                                            ),
                                        ),
                                        'Permission' => array(
                                            'type' => 'string',
                                        ),
                                    ),
                                ),
                            ),
                            'Owner' => array(
                                'type' => 'object',
                                'location' => 'xml',
                                'properties' => array(
                                    'DisplayName' => array(
                                        'type' => 'string',
                                    ),
                                    'ID' => array(
                                        'type' => 'string',
                                    ),
                                ),
                            ),
                            'Bucket' => array(
                                'required' => true,
                                'type' => 'string',
                                'location' => 'uri',
                            ),
                            'GrantFullControl' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'x-cos-grant-full-control',
                            ),
                            'GrantRead' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'x-cos-grant-read',
                            ),
                            'GrantReadACP' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'x-cos-grant-read-acp',
                            ),
                            'GrantWrite' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'x-cos-grant-write',
                            ),
                            'GrantWriteACP' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'x-cos-grant-write-acp',
                            ),
                            'ACP' => array(
                                'type' => 'object',
                                'additionalProperties' => true,
                            ),
                        ),
                    ),
                    'PutBucketCors' => array(
                        'httpMethod' => 'PUT',
                        'uri' => '/{Bucket}?cors',
                        'class' => 'Qcloud\\Cos\\Command',
                        'responseClass' => 'PutBucketCorsOutput',
                        'responseType' => 'model',
                        'data' => array(
                            'xmlRoot' => array(
                                'name' => 'CORSConfiguration',
                            ),
                            'contentMd5' => true,
                        ),
                        'parameters' => array(
                            'Bucket' => array(
                                'required' => true,
                                'type' => 'string',
                                'location' => 'uri',
                            ),
                            'CORSRules' => array(
                                'required' => true,
                                'type' => 'array',
                                'location' => 'xml',
                                'data' => array(
                                    'xmlFlattened' => true,
                                ),
                                'items' => array(
                                    'name' => 'CORSRule',
                                    'type' => 'object',
                                    'sentAs' => 'CORSRule',
                                    'properties' => array(
                                        'ID' => array(
                                            'type' => 'string',
                                        ),
                                        'AllowedHeaders' => array(
                                            'type' => 'array',
                                            'data' => array(
                                                'xmlFlattened' => true,
                                            ),
                                            'items' => array(
                                                'name' => 'AllowedHeader',
                                                'type' => 'string',
                                                'sentAs' => 'AllowedHeader',
                                            ),
                                        ),
                                        'AllowedMethods' => array(
                                            'required' => true,
                                            'type' => 'array',
                                            'data' => array(
                                                'xmlFlattened' => true,
                                            ),
                                            'items' => array(
                                                'name' => 'AllowedMethod',
                                                'type' => 'string',
                                                'sentAs' => 'AllowedMethod',
                                            ),
                                        ),
                                        'AllowedOrigins' => array(
                                            'required' => true,
                                            'type' => 'array',
                                            'data' => array(
                                                'xmlFlattened' => true,
                                            ),
                                            'items' => array(
                                                'name' => 'AllowedOrigin',
                                                'type' => 'string',
                                                'sentAs' => 'AllowedOrigin',
                                            ),
                                        ),
                                        'ExposeHeaders' => array(
                                            'type' => 'array',
                                            'data' => array(
                                                'xmlFlattened' => true,
                                            ),
                                            'items' => array(
                                                'name' => 'ExposeHeader',
                                                'type' => 'string',
                                                'sentAs' => 'ExposeHeader',
                                            ),
                                        ),
                                        'MaxAgeSeconds' => array(
                                            'type' => 'numeric',
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                    'PutBucketLifecycle' => array(
                        'httpMethod' => 'PUT',
                        'uri' => '/{Bucket}?lifecycle',
                        'class' => 'Qcloud\\Cos\\Command',
                        'responseClass' => 'PutBucketLifecycleOutput',
                        'responseType' => 'model',
                        'data' => array(
                            'xmlRoot' => array(
                                'name' => 'LifecycleConfiguration',
                            ),
                            'contentMd5' => true,
                        ),
                        'parameters' => array(
                            'Bucket' => array(
                                'required' => true,
                                'type' => 'string',
                                'location' => 'uri',
                            ),
                            'Rules' => array(
                                'required' => true,
                                'type' => 'array',
                                'location' => 'xml',
                                'data' => array(
                                    'xmlFlattened' => true,
                                ),
                                'items' => array(
                                    'name' => 'Rule',
                                    'type' => 'object',
                                    'sentAs' => 'Rule',
                                    'properties' => array(
                                        'Expiration' => array(
                                            'type' => 'object',
                                            'properties' => array(
                                                'Date' => array(
                                                    'type' => array(
                                                        'object',
                                                        'string',
                                                        'integer',
                                                    ),
                                                    'format' => 'date-time',
                                                ),
                                                'Days' => array(
                                                    'type' => 'numeric',
                                                ),
                                            ),
                                        ),
                                        'ID' => array(
                                            'type' => 'string',
                                        ),
                                        'Filter' => array(
                                            'type' => 'object',
                                            'require' => true,
                                            'properties' => array(
                                                'Prefix' => array(
                                                    'type' => 'string',
                                                    'require' => true,
                                                ),
                                            ),
                                        ),
                                        'Status' => array(
                                            'required' => true,
                                            'type' => 'string',
                                        ),
                                        'Transitions' => array(
                                            'required' => true,
                                            'type' => 'array',
                                            'location' => 'xml',
                                            'data' => array(
                                                'xmlFlattened' => true,
                                            ),
                                            'items' => array(
                                                'name' => 'Transition',
                                                'type' => 'object',
                                                'sentAs' => 'Transition',
                                                'properties' => array(
                                                    'Date' => array(
                                                        'type' => array(
                                                            'object',
                                                            'string',
                                                            'integer',
                                                        ),
                                                        'format' => 'date-time',
                                                    ),
                                                    'Days' => array(
                                                        'type' => 'numeric',
                                                    ),
                                                    'StorageClass' => array(
                                                        'type' => 'string',
                                                    )))),
                                        'NoncurrentVersionTransition' => array(
                                            'type' => 'object',
                                            'properties' => array(
                                                'NoncurrentDays' => array(
                                                    'type' => 'numeric',
                                                ),
                                                'StorageClass' => array(
                                                    'type' => 'string',
                                                ),
                                            ),
                                        ),
                                        'NoncurrentVersionExpiration' => array(
                                            'type' => 'object',
                                            'properties' => array(
                                                'NoncurrentDays' => array(
                                                    'type' => 'numeric',
                                                ),
                                            ),
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                    'PutBucketVersioning' => array(
                        'httpMethod' => 'PUT',
                        'uri' => '/{Bucket}?versioning',
                        'class' => 'Qcloud\\Cos\\Command',
                        'responseClass' => 'PutBucketVersioningOutput',
                        'responseType' => 'model',
                        'data' => array(
                            'xmlRoot' => array(
                                'name' => 'VersioningConfiguration',
                            ),
                        ),
                        'parameters' => array(
                            'Bucket' => array(
                                'required' => true,
                                'type' => 'string',
                                'location' => 'uri',
                            ),
                            'MFA' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'x-cos-mfa',
                            ),
                            'MFADelete' => array(
                                'type' => 'string',
                                'location' => 'xml',
                                'sentAs' => 'MfaDelete',
                            ),
                            'Status' => array(
                                'type' => 'string',
                                'location' => 'xml',
                            ),
                        ),
                    ),
                    'PutBucketReplication' => array(
                        'httpMethod' => 'PUT',
                        'uri' => '/{Bucket}?replication',
                        'class' => 'Qcloud\\Cos\\Command',
                        'responseClass' => 'PutBucketReplicationOutput',
                        'responseType' => 'model',
                        'data' => array(
                            'xmlRoot' => array(
                                'name' => 'ReplicationConfiguration',
                            ),
                            'contentMd5' => true,
                        ),
                        'parameters' => array(
                            'Bucket' => array(
                                'required' => true,
                                'type' => 'string',
                                'location' => 'uri',
                            ),
                            'Role' => array(
                                'required' => true,
                                'type' => 'string',
                                'location' => 'xml',
                            ),
                            'Rules' => array(
                                'required' => true,
                                'type' => 'array',
                                'location' => 'xml',
                                'data' => array(
                                    'xmlFlattened' => true,
                                ),
                                'items' => array(
                                    'name' => 'ReplicationRule',
                                    'type' => 'object',
                                    'sentAs' => 'Rule',
                                    'properties' => array(
                                        'ID' => array(
                                            'type' => 'string',
                                        ),
                                        'Prefix' => array(
                                            'required' => true,
                                            'type' => 'string',
                                        ),
                                        'Status' => array(
                                            'required' => true,
                                            'type' => 'string',
                                        ),
                                        'Destination' => array(
                                            'required' => true,
                                            'type' => 'object',
                                            'properties' => array(
                                                'Bucket' => array(
                                                    'required' => true,
                                                    'type' => 'string',
                                                ),
                                                'StorageClass' => array(
                                                    'type' => 'string',
                                                ),
                                            ),
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                    'RestoreObject' => array(
                        'httpMethod' => 'POST',
                        'uri' => '/{Bucket}{/Key*}?restore',
                        'class' => 'Qcloud\\Cos\\Command',
                        'responseClass' => 'RestoreObjectOutput',
                        'responseType' => 'model',
                        'data' => array(
                            'xmlRoot' => array(
                                'name' => 'RestoreRequest',
                            ),
                        ),
                        'parameters' => array(
                            'Bucket' => array(
                                'required' => true,
                                'type' => 'string',
                                'location' => 'uri',
                            ),
                            'Key' => array(
                                'required' => true,
                                'type' => 'string',
                                'location' => 'uri',
                                'minLength' => 1,
                            ),
                            'VersionId' => array(
                                'type' => 'string',
                                'location' => 'query',
                                'sentAs' => 'versionId',
                            ),
                            'Days' => array(
                                'required' => true,
                                'type' => 'numeric',
                                'location' => 'xml',
                            ),
                            'CASJobParameters' => array(
                                'type' => 'object',
                                'location' => 'xml',
                                'properties' => array(
                                    'Tier' => array(
                                        'type' => 'string',
                                        'required' => true,
                                    ),
                                ),
                            ),
                            'RequestPayer' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'x-cos-request-payer',
                            ),
                        ),
                        'errorResponses' => array(
                            array(
                                'reason' => 'This operation is not allowed against this storage tier',
                                'class' => 'ObjectAlreadyInActiveTierErrorException',
                            ),
                        ),
                    ),
                    'ListParts' => array(
                        'httpMethod' => 'GET',
                        'uri' => '/{Bucket}{/Key*}',
                        'class' => 'Qcloud\\Cos\\Command',
                        'responseClass' => 'ListPartsOutput',
                        'responseType' => 'model',
                        'parameters' => array(
                            'Bucket' => array(
                                'required' => true,
                                'type' => 'string',
                                'location' => 'uri'),
                            'Key' => array(
                                'required' => true,
                                'type' => 'string',
                                'location' => 'uri',
                                'minLength' => 1),
                            'MaxParts' => array(
                                'type' => 'numeric',
                                'location' => 'query',
                                'sentAs' => 'max-parts'),
                            'PartNumberMarker' => array(
                                'type' => 'numeric',
                                'location' => 'query',
                                'sentAs' => 'part-number-marker'),
                            'UploadId' => array(
                                'required' => true,
                                'type' => 'string',
                                'location' => 'query',
                                'sentAs' => 'uploadId'),
                            'command.expects' => array(
                                'static' => true,
                                'default' => 'application/xml'))),
                    'ListObjects' => array(
                        'httpMethod' => 'GET',
                        'uri' => '/{Bucket}',
                        'class' => 'Qcloud\\Cos\\Command',
                        'responseClass' => 'ListObjectsOutput',
                        'responseType' => 'model',
                        'parameters' => array(
                            'Bucket' => array(
                                'required' => true,
                                'type' => 'string',
                                'location' => 'uri'),
                            'Delimiter' => array(
                                'type' => 'string',
                                'location' => 'query',
                                'sentAs' => 'delimiter'),
                            'EncodingType' => array(
                                'type' => 'string',
                                'location' => 'query',
                                'sentAs' => 'encoding-type'),
                            'Marker' => array(
                                'type' => 'string',
                                'location' => 'query',
                                'sentAs' => 'marker'),
                            'MaxKeys' => array(
                                    'type' => 'numeric',
                                    'location' => 'query',
                                    'sentAs' => 'max-keys'),
                            'Prefix' => array(
                                    'type' => 'string',
                                    'location' => 'query',
                                    'sentAs' => 'prefix'),
                            'command.expects' => array(
                                    'static' => true,
                                    'default' => 'application/xml')),
                        'errorResponses' => array(
                            array(
                                'reason' => 'The specified bucket does not exist.',
                                'class' => 'NoSuchBucketException'))),
                    'ListBuckets' => array(
                        'httpMethod' => 'GET',
                        'uri' => '/',
                        'class' => 'Qcloud\\Cos\\Command',
                        'responseClass' => 'ListBucketsOutput',
                        'responseType' => 'model',
                        'parameters' => array(
                            'command.expects' => array(
                                'static' => true,
                                'default' => 'application/xml',
                            ),
                        ),
                    ),
                    'ListObjectVersions' => array(
                        'httpMethod' => 'GET',
                        'uri' => '/{Bucket}?versions',
                        'class' => 'Qcloud\\Cos\\Command',
                        'responseClass' => 'ListObjectVersionsOutput',
                        'responseType' => 'model',
                        'parameters' => array(
                            'Bucket' => array(
                                'required' => true,
                                'type' => 'string',
                                'location' => 'uri',
                            ),
                            'Delimiter' => array(
                                'type' => 'string',
                                'location' => 'query',
                                'sentAs' => 'delimiter',
                            ),
                            'EncodingType' => array(
                                'type' => 'string',
                                'location' => 'query',
                                'sentAs' => 'encoding-type',
                            ),
                            'KeyMarker' => array(
                                'type' => 'string',
                                'location' => 'query',
                                'sentAs' => 'key-marker',
                            ),
                            'MaxKeys' => array(
                                'type' => 'numeric',
                                'location' => 'query',
                                'sentAs' => 'max-keys',
                            ),
                            'Prefix' => array(
                                'type' => 'string',
                                'location' => 'query',
                                'sentAs' => 'prefix',
                            ),
                            'VersionIdMarker' => array(
                                'type' => 'string',
                                'location' => 'query',
                                'sentAs' => 'version-id-marker',
                            ),
                            'command.expects' => array(
                                'static' => true,
                                'default' => 'application/xml',
                            ),
                        ),
                    ),
                    'HeadObject' => array(
                        'httpMethod' => 'HEAD',
                        'uri' => '/{Bucket}{/Key*}',
                        'class' => 'Qcloud\\Cos\\Command',
                        'responseClass' => 'HeadObjectOutput',
                        'responseType' => 'model',
                        'parameters' => array(
                            'Bucket' => array(
                                'required' => true,
                                'type' => 'string',
                                'location' => 'uri',
                            ),
                            'IfMatch' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'If-Match',
                            ),
                            'IfModifiedSince' => array(
                                'type' => array(
                                    'object',
                                    'string',
                                    'integer',
                                ),
                                'format' => 'date-time-http',
                                'location' => 'header',
                                'sentAs' => 'If-Modified-Since',
                            ),
                            'IfNoneMatch' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'If-None-Match',
                            ),
                            'IfUnmodifiedSince' => array(
                                'type' => array(
                                    'object',
                                    'string',
                                    'integer',
                                ),
                                'format' => 'date-time-http',
                                'location' => 'header',
                                'sentAs' => 'If-Unmodified-Since',
                            ),
                            'Key' => array(
                                'required' => true,
                                'type' => 'string',
                                'location' => 'uri',
                                'minLength' => 1,
                            ),
                            'Range' => array(
                                'type' => 'string',
                                'location' => 'header',
                            ),
                            'VersionId' => array(
                                'type' => 'string',
                                'location' => 'query',
                                'sentAs' => 'versionId',
                            ),
                            'SSECustomerAlgorithm' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'x-cos-server-side-encryption-customer-algorithm',
                            ),
                            'SSECustomerKey' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'x-cos-server-side-encryption-customer-key',
                            ),
                            'SSECustomerKeyMD5' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'x-cos-server-side-encryption-customer-key-MD5',
                            ),
                            'RequestPayer' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'x-cos-request-payer',
                            ),
                        ),
                        'errorResponses' => array(
                            array(
                                'reason' => 'The specified key does not exist.',
                                'class' => 'NoSuchKeyException',
                            ),
                        ),
                    ),
                    'HeadBucket' => array(
                        'httpMethod' => 'HEAD',
                        'uri' => '/{Bucket}',
                        'class' => 'Qcloud\\Cos\\Command',
                        'responseClass' => 'HeadBucketOutput',
                        'responseType' => 'model',
                        'parameters' => array(
                            'Bucket' => array(
                                'required' => true,
                                'type' => 'string',
                                'location' => 'uri',
                            ),
                        ),
                        'errorResponses' => array(
                            array(
                                'reason' => 'The specified bucket does not exist.',
                                'class' => 'NoSuchBucketException',
                            ),
                        ),
                    ),
                    'UploadPartCopy' => array(
            'httpMethod' => 'PUT',
            'uri' => '/{Bucket}{/Key*}',
            'class' => 'Qcloud\\Cos\\Command',
            'responseClass' => 'UploadPartCopyOutput',
            'responseType' => 'model',
            'data' => array(
                'xmlRoot' => array(
                    'name' => 'UploadPartCopyRequest',
                ),
            ),
            'parameters' => array(
                'Bucket' => array(
                    'required' => true,
                    'type' => 'string',
                    'location' => 'uri',
                ),
                'CopySource' => array(
                    'required' => true,
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'x-cos-copy-source',
                ),
                'CopySourceIfMatch' => array(
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'x-cos-copy-source-if-match',
                ),
                'CopySourceIfModifiedSince' => array(
                    'type' => array(
                        'object',
                        'string',
                        'integer',
                    ),
                    'format' => 'date-time-http',
                    'location' => 'header',
                    'sentAs' => 'x-cos-copy-source-if-modified-since',
                ),
                'CopySourceIfNoneMatch' => array(
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'x-cos-copy-source-if-none-match',
                ),
                'CopySourceIfUnmodifiedSince' => array(
                    'type' => array(
                        'object',
                        'string',
                        'integer',
                    ),
                    'format' => 'date-time-http',
                    'location' => 'header',
                    'sentAs' => 'x-cos-copy-source-if-unmodified-since',
                ),
                'CopySourceRange' => array(
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'x-cos-copy-source-range',
                ),
                'Key' => array(
                    'required' => true,
                    'type' => 'string',
                    'location' => 'uri',
                    'minLength' => 1,
                ),
                'PartNumber' => array(
                    'required' => true,
                    'type' => 'numeric',
                    'location' => 'query',
                    'sentAs' => 'partNumber',
                ),
                'UploadId' => array(
                    'required' => true,
                    'type' => 'string',
                    'location' => 'query',
                    'sentAs' => 'uploadId',
                ),
                'SSECustomerAlgorithm' => array(
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'x-cos-server-side-encryption-customer-algorithm',
                ),
                'SSECustomerKey' => array(
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'x-cos-server-side-encryption-customer-key',
                ),
                'SSECustomerKeyMD5' => array(
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'x-cos-server-side-encryption-customer-key-MD5',
                ),
                'CopySourceSSECustomerAlgorithm' => array(
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'x-cos-copy-source-server-side-encryption-customer-algorithm',
                ),
                'CopySourceSSECustomerKey' => array(
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'x-cos-copy-source-server-side-encryption-customer-key',
                ),
                'CopySourceSSECustomerKeyMD5' => array(
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'x-cos-copy-source-server-side-encryption-customer-key-MD5',
                ),
                'RequestPayer' => array(
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'x-cos-request-payer',
                ),
                'command.expects' => array(
                    'static' => true,
                    'default' => 'application/xml',
                ),
            ),
        ),),
                'models' => array(
                    'AbortMultipartUploadOutput' => array(
                        'type' => 'object',
                        'additionalProperties' => true,
                        'properties' => array(
                            'RequestId' => array(
                                'location' => 'header',
                                'sentAs' => 'x-cos-request-id'))),
                    'CreateBucketOutput' => array(
                        'type' => 'object',
                        'additionalProperties' => true,
                        'properties' => array(
                            'Location' => array(
                                'type' => 'string',
                                'location' => 'header'),
                            'RequestId' => array(
                                'location' => 'header',
                                'sentAs' => 'x-cos-request-id'))),
                    'CompleteMultipartUploadOutput' => array(
                        'type' => 'object',
                        'additionalProperties' => true,
                        'properties' => array(
                            'Location' => array(
                                'type' => 'string',
                                'location' => 'xml',
                            ),
                            'Bucket' => array(
                                'type' => 'string',
                                'location' => 'xml',
                            ),
                            'Key' => array(
                                'type' => 'string',
                                'location' => 'xml',
                            ),
                            'Expiration' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'x-cos-expiration',
                            ),
                            'ETag' => array(
                                'type' => 'string',
                                'location' => 'xml',
                            ),
                            'ServerSideEncryption' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'x-cos-server-side-encryption',
                            ),
                            'VersionId' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'x-cos-version-id',
                            ),
                            'SSEKMSKeyId' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'x-cos-server-side-encryption-aws-kms-key-id',
                            ),
                            'RequestCharged' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'x-cos-request-charged',
                            ),
                            'RequestId' => array(
                                'location' => 'header',
                                'sentAs' => 'x-cos-request-id',
                            ),
                        ),
                    ),
                    'CreateMultipartUploadOutput' => array(
                        'type' => 'object',
                        'additionalProperties' => true,
                        'properties' => array(
                            'Bucket' => array(
                                'type' => 'string',
                                'location' => 'xml',
                                'sentAs' => 'Bucket'),
                            'Key' => array(
                                'type' => 'string',
                                'location' => 'xml'),
                            'UploadId' => array(
                                'type' => 'string',
                                'location' => 'xml'),
                            'ServerSideEncryption' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'x-cos-server-side-encryption',
                            ),
                            'SSECustomerAlgorithm' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'x-cos-server-side-encryption-customer-algorithm',
                            ),
                            'SSECustomerKeyMD5' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'x-cos-server-side-encryption-customer-key-MD5',
                            ),
                            'SSEKMSKeyId' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'x-cos-server-side-encryption-aws-kms-key-id',
                            ),
                            'RequestCharged' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'x-cos-request-charged',
                            ),
                            'RequestId' => array(
                                'location' => 'header',
                                'sentAs' => 'x-cos-request-id',
                            ))),
                    'CopyObjectOutput' => array(
                        'type' => 'object',
                        'additionalProperties' => true,
                        'properties' => array(
                            'ETag' => array(
                                'type' => 'string',
                                'location' => 'xml',
                            ),
                            'LastModified' => array(
                                'type' => 'string',
                                'location' => 'xml',
                            ),
                            'Expiration' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'x-cos-expiration',
                            ),
                            'CopySourceVersionId' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'x-cos-copy-source-version-id',
                            ),
                            'VersionId' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'x-cos-version-id',
                            ),
                            'ServerSideEncryption' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'x-cos-server-side-encryption',
                            ),
                            'SSECustomerAlgorithm' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'x-cos-server-side-encryption-customer-algorithm',
                            ),
                            'RequestCharged' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'x-cos-request-charged',
                            ),
                            'RequestId' => array(
                                'location' => 'header',
                                'sentAs' => 'x-cos-request-id',
                            ),
                        ),
                    ),
                    'DeleteBucketOutput' => array(
                        'type' => 'object',
                        'additionalProperties' => true,
                        'properties' => array(
                            'RequestId' => array(
                                'location' => 'header',
                                'sentAs' => 'x-cos-request-id'))),
                    'DeleteBucketCorsOutput' => array(
                        'type' => 'object',
                        'additionalProperties' => true,
                        'properties' => array(
                            'RequestId' => array(
                                'location' => 'header',
                                'sentAs' => 'x-cos-request-id',
                            ),
                        ),
                    ),
                    'DeleteObjectOutput' => array(
                        'type' => 'object',
                        'additionalProperties' => true,
                        'properties' => array(
                            'DeleteMarker' => array(
                                'type' => 'boolean',
                                'location' => 'header',
                                'sentAs' => 'x-cos-delete-marker',
                            ),
                            'VersionId' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'x-cos-version-id',
                            ),
                            'RequestCharged' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'x-cos-request-charged',
                            ),
                            'RequestId' => array(
                                'location' => 'header',
                                'sentAs' => 'x-cos-request-id',
                            ),
                        ),
                    ),
                    'DeleteObjectsOutput' => array(
                        'type' => 'object',
                        'additionalProperties' => true,
                        'properties' => array(
                            'Deleted' => array(
                                'type' => 'array',
                                'location' => 'xml',
                                'data' => array(
                                    'xmlFlattened' => true,
                                ),
                                'items' => array(
                                    'name' => 'DeletedObject',
                                    'type' => 'object',
                                    'properties' => array(
                                        'Key' => array(
                                            'type' => 'string',
                                        ),
                                        'VersionId' => array(
                                            'type' => 'string',
                                        ),
                                        'DeleteMarker' => array(
                                            'type' => 'boolean',
                                        ),
                                        'DeleteMarkerVersionId' => array(
                                            'type' => 'string',
                                        ),
                                    ),
                                ),
                            ),
                            'RequestCharged' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'x-cos-request-charged',
                            ),
                            'Errors' => array(
                                'type' => 'array',
                                'location' => 'xml',
                                'sentAs' => 'Error',
                                'data' => array(
                                    'xmlFlattened' => true,
                                ),
                                'items' => array(
                                    'name' => 'Error',
                                    'type' => 'object',
                                    'sentAs' => 'Error',
                                    'properties' => array(
                                        'Key' => array(
                                            'type' => 'string',
                                        ),
                                        'VersionId' => array(
                                            'type' => 'string',
                                        ),
                                        'Code' => array(
                                            'type' => 'string',
                                        ),
                                        'Message' => array(
                                            'type' => 'string',
                                        ),
                                    ),
                                ),
                            ),
                            'RequestId' => array(
                                'location' => 'header',
                                'sentAs' => 'x-cos-request-id',
                            ),
                        ),
                    ),
                    'DeleteBucketLifecycleOutput' => array(
                        'type' => 'object',
                        'additionalProperties' => true,
                        'properties' => array(
                            'RequestId' => array(
                                'location' => 'header',
                                'sentAs' => 'x-cos-request-id',
                            ),
                        ),
                    ),
                    'DeleteBucketReplicationOutput' => array(
                        'type' => 'object',
                        'additionalProperties' => true,
                        'properties' => array(
                            'RequestId' => array(
                                'location' => 'header',
                                'sentAs' => 'x-cos-request-id',
                            ),
                        ),
                    ),
                    'GetObjectOutput' => array(
                        'type' => 'object',
                        'additionalProperties' => true,
                        'properties' => array(
                            'Body' => array(
                                'type' => 'string',
                                'instanceOf' => 'Guzzle\\Http\\EntityBody',
                                'location' => 'body',
                            ),
                            'DeleteMarker' => array(
                                'type' => 'boolean',
                                'location' => 'header',
                                'sentAs' => 'x-cos-delete-marker',
                            ),
                            'AcceptRanges' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'accept-ranges',
                            ),
                            'Expiration' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'x-cos-expiration',
                            ),
                            'Restore' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'x-cos-restore',
                            ),
                            'LastModified' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'Last-Modified',
                            ),
                            'ContentLength' => array(
                                'type' => 'numeric',
                                'location' => 'header',
                                'sentAs' => 'Content-Length',
                            ),
                            'ETag' => array(
                                'type' => 'string',
                                'location' => 'header',
                            ),
                            'MissingMeta' => array(
                                'type' => 'numeric',
                                'location' => 'header',
                                'sentAs' => 'x-cos-missing-meta',
                            ),
                            'VersionId' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'x-cos-version-id',
                            ),
                            'CacheControl' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'Cache-Control',
                            ),
                            'ContentDisposition' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'Content-Disposition',
                            ),
                            'ContentEncoding' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'Content-Encoding',
                            ),
                            'ContentLanguage' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'Content-Language',
                            ),
                            'ContentRange' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'Content-Range',
                            ),
                            'ContentType' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'Content-Type',
                            ),
                            'Expires' => array(
                                'type' => 'string',
                                'location' => 'header',
                            ),
                            'WebsiteRedirectLocation' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'x-cos-website-redirect-location',
                            ),
                            'ServerSideEncryption' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'x-cos-server-side-encryption',
                            ),
                            'Metadata' => array(
                                'type' => 'object',
                                'location' => 'header',
                                'sentAs' => 'x-cos-meta-',
                                'additionalProperties' => array(
                                    'type' => 'string',
                                ),
                            ),
                            'SSECustomerAlgorithm' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'x-cos-server-side-encryption-customer-algorithm',
                            ),
                            'SSECustomerKeyMD5' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'x-cos-server-side-encryption-customer-key-MD5',
                            ),
                            'SSEKMSKeyId' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'x-cos-server-side-encryption-aws-kms-key-id',
                            ),
                            'StorageClass' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'x-cos-storage-class',
                            ),
                            'RequestCharged' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'x-cos-request-charged',
                            ),
                            'ReplicationStatus' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'x-cos-replication-status',
                            ),
                            'RequestId' => array(
                                'location' => 'header',
                                'sentAs' => 'x-cos-request-id',
                            ),
                        ),
                    ),
                    'GetObjectAclOutput' => array(
                        'type' => 'object',
                        'additionalProperties' => true,
                        'properties' => array(
                            'Owner' => array(
                                'type' => 'object',
                                'location' => 'xml',
                                'properties' => array(
                                    'DisplayName' => array(
                                        'type' => 'string',
                                    ),
                                    'ID' => array(
                                        'type' => 'string',
                                    ),
                                ),
                            ),
                            'Grants' => array(
                                'type' => 'array',
                                'location' => 'xml',
                                'sentAs' => 'AccessControlList',
                                'items' => array(
                                    'name' => 'Grant',
                                    'type' => 'object',
                                    'sentAs' => 'Grant',
                                    'properties' => array(
                                        'Grantee' => array(
                                            'type' => 'object',
                                            'properties' => array(
                                                'DisplayName' => array(
                                                    'type' => 'string'),
                                                /*
                                                'EmailAddress' => array(
                                                    'type' => 'string'),
                                                */
                                                'ID' => array(
                                                    'type' => 'string'),
                                                /*
                                                'Type' => array(
                                                    'type' => 'string',
                                                    'sentAs' => 'xsi:type',
                                                    'data' => array(
                                                        'xmlAttribute' => true,
                                                        'xmlNamespace' => 'http://www.w3.org/2001/XMLSchema-instance')),
                                                */
                                                /*'URI' => array(
                                                    'type' => 'string') */)),
                                        'Permission' => array(
                                            'type' => 'string',
                                        ),
                                    ),
                                ),
                            ),
                            'RequestCharged' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'x-cos-request-charged',
                            ),
                            'RequestId' => array(
                                'location' => 'header',
                                'sentAs' => 'x-cos-request-id',
                            ),
                        ),
                    ),
                    'GetBucketAclOutput' => array(
                        'type' => 'object',
                        'additionalProperties' => true,
                        'properties' => array(
                            'Owner' => array(
                                'type' => 'object',
                                'location' => 'xml',
                                'properties' => array(
                                    'DisplayName' => array(
                                        'type' => 'string'),
                                    'ID' => array(
                                        'type' => 'string'))),
                            'Grants' => array(
                                'type' => 'array',
                                'location' => 'xml',
                                'sentAs' => 'AccessControlList',
                                'items' => array(
                                    'name' => 'Grant',
                                    'type' => 'object',
                                    'sentAs' => 'Grant',
                                    'properties' => array(
                                        'Grantee' => array(
                                            'type' => 'object',
                                            'properties' => array(
                                                'DisplayName' => array(
                                                    'type' => 'string'),
                                                /*
                                                'EmailAddress' => array(
                                                    'type' => 'string'),
                                                */
                                                'ID' => array(
                                                    'type' => 'string'),
                                                /*
                                                'Type' => array(
                                                    'type' => 'string',
                                                    'sentAs' => 'xsi:type',
                                                    'data' => array(
                                                        'xmlAttribute' => true,
                                                        'xmlNamespace' => 'http://www.w3.org/2001/XMLSchema-instance')),
                                                */
                                                /*'URI' => array(
                                                    'type' => 'string') */)),
                                        'Permission' => array(
                                            'type' => 'string')))),
                            'RequestId' => array(
                                'location' => 'header',
                                'sentAs' => 'x-cos-request-id'))),
                    'GetBucketCorsOutput' => array(
                        'type' => 'object',
                        'additionalProperties' => true,
                        'properties' => array(
                            'CORSRules' => array(
                                'type' => 'array',
                                'location' => 'xml',
                                'sentAs' => 'CORSRule',
                                'data' => array(
                                    'xmlFlattened' => true,
                                ),
                                'items' => array(
                                    'name' => 'CORSRule',
                                    'type' => 'object',
                                    'sentAs' => 'CORSRule',
                                    'properties' => array(
                                        'ID' => array(
                                            'type' => 'string'),
                                        'AllowedHeaders' => array(
                                            'type' => 'array',
                                            'sentAs' => 'AllowedHeader',
                                            'data' => array(
                                                'xmlFlattened' => true,
                                            ),
                                            'items' => array(
                                                'name' => 'AllowedHeader',
                                                'type' => 'string',
                                                'sentAs' => 'AllowedHeader',
                                            ),
                                        ),
                                        'AllowedMethods' => array(
                                            'type' => 'array',
                                            'sentAs' => 'AllowedMethod',
                                            'data' => array(
                                                'xmlFlattened' => true,
                                            ),
                                            'items' => array(
                                                'name' => 'AllowedMethod',
                                                'type' => 'string',
                                                'sentAs' => 'AllowedMethod',
                                            ),
                                        ),
                                        'AllowedOrigins' => array(
                                            'type' => 'array',
                                            'sentAs' => 'AllowedOrigin',
                                            'data' => array(
                                                'xmlFlattened' => true,
                                            ),
                                            'items' => array(
                                                'name' => 'AllowedOrigin',
                                                'type' => 'string',
                                                'sentAs' => 'AllowedOrigin',
                                            ),
                                        ),
                                        'ExposeHeaders' => array(
                                            'type' => 'array',
                                            'sentAs' => 'ExposeHeader',
                                            'data' => array(
                                                'xmlFlattened' => true,
                                            ),
                                            'items' => array(
                                                'name' => 'ExposeHeader',
                                                'type' => 'string',
                                                'sentAs' => 'ExposeHeader',
                                            ),
                                        ),
                                        'MaxAgeSeconds' => array(
                                            'type' => 'numeric',
                                        ),
                                    ),
                                ),
                            ),
                            'RequestId' => array(
                                'location' => 'header',
                                'sentAs' => 'x-cos-request-id',
                            ),
                        ),
                    ),
                    'GetBucketLifecycleOutput' => array(
                        'type' => 'object',
                        'additionalProperties' => true,
                        'properties' => array(
                            'Rules' => array(
                                'type' => 'array',
                                'location' => 'xml',
                                'sentAs' => 'Rule',
                                'data' => array(
                                    'xmlFlattened' => true,
                                ),
                                'items' => array(
                                    'name' => 'Rule',
                                    'type' => 'object',
                                    'sentAs' => 'Rule',
                                    'properties' => array(
                                        'Expiration' => array(
                                            'type' => 'object',
                                            'properties' => array(
                                                'Date' => array(
                                                    'type' => 'string',
                                                ),
                                                'Days' => array(
                                                    'type' => 'numeric',
                                                ),
                                            ),
                                        ),
                                        'ID' => array(
                                            'type' => 'string',
                                        ),
                                        'Prefix' => array(
                                            'type' => 'string',
                                        ),
                                        'Status' => array(
                                            'type' => 'string',
                                        ),
                                        'Transition' => array(
                                            'type' => 'object',
                                            'properties' => array(
                                                'Date' => array(
                                                    'type' => 'string',
                                                ),
                                                'Days' => array(
                                                    'type' => 'numeric',
                                                ),
                                                'StorageClass' => array(
                                                    'type' => 'string',
                                                ),
                                            ),
                                        ),
                                        'NoncurrentVersionTransition' => array(
                                            'type' => 'object',
                                            'properties' => array(
                                                'NoncurrentDays' => array(
                                                    'type' => 'numeric',
                                                ),
                                                'StorageClass' => array(
                                                    'type' => 'string',
                                                ),
                                            ),
                                        ),
                                        'NoncurrentVersionExpiration' => array(
                                            'type' => 'object',
                                            'properties' => array(
                                                'NoncurrentDays' => array(
                                                    'type' => 'numeric',
                                                ),
                                            ),
                                        ),
                                    ),
                                ),
                            ),
                            'RequestId' => array(
                                'location' => 'header',
                                'sentAs' => 'x-cos-request-id',
                            ),
                        ),
                    ),
                    'GetBucketVersioningOutput' => array(
                        'type' => 'object',
                        'additionalProperties' => true,
                        'properties' => array(
                            'Status' => array(
                                'type' => 'string',
                                'location' => 'xml',
                            ),
                            'MFADelete' => array(
                                'type' => 'string',
                                'location' => 'xml',
                                'sentAs' => 'MfaDelete',
                            ),
                            'RequestId' => array(
                                'location' => 'header',
                                'sentAs' => 'x-cos-request-id',
                            ),
                        ),
                    ),
                    'GetBucketReplicationOutput' => array(
                        'type' => 'object',
                        'additionalProperties' => true,
                        'properties' => array(
                            'Role' => array(
                                'type' => 'string',
                                'location' => 'xml',
                            ),
                            'Rules' => array(
                                'type' => 'array',
                                'location' => 'xml',
                                'sentAs' => 'Rule',
                                'data' => array(
                                    'xmlFlattened' => true,
                                ),
                                'items' => array(
                                    'name' => 'ReplicationRule',
                                    'type' => 'object',
                                    'sentAs' => 'Rule',
                                    'properties' => array(
                                        'ID' => array(
                                            'type' => 'string',
                                        ),
                                        'Prefix' => array(
                                            'type' => 'string',
                                        ),
                                        'Status' => array(
                                            'type' => 'string',
                                        ),
                                        'Destination' => array(
                                            'type' => 'object',
                                            'properties' => array(
                                                'Bucket' => array(
                                                    'type' => 'string',
                                                ),
                                                'StorageClass' => array(
                                                    'type' => 'string',
                                                ),
                                            ),
                                        ),
                                    ),
                                ),
                            ),
                            'RequestId' => array(
                                'location' => 'header',
                                'sentAs' => 'x-cos-request-id',
                            ),
                        ),
                    ),
                    'GetBucketLocationOutput' => array(
                        'type' => 'object',
                        'additionalProperties' => true,
                        'properties' => array(
                            'Location' => array(
                                'type' => 'string',
                                'location' => 'body',
                                'filters' => array(
                                    'strval',
                                    'strip_tags',
                                    'trim',
                                ),
                            ),
                        ),
                    ),
                    'UploadPartOutput' => array(
                        'type' => 'object',
                        'additionalProperties' => true,
                        'properties' => array(
                            'ServerSideEncryption' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'x-cos-server-side-encryption',
                            ),
                            'ETag' => array(
                                'type' => 'string',
                                'location' => 'header',
                            ),
                            'SSECustomerAlgorithm' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'x-cos-server-side-encryption-customer-algorithm',
                            ),
                            'SSECustomerKeyMD5' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'x-cos-server-side-encryption-customer-key-MD5',
                            ),
                            'SSEKMSKeyId' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'x-cos-server-side-encryption-aws-kms-key-id',
                            ),
                            'RequestCharged' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'x-cos-request-charged',
                            ),
                            'RequestId' => array(
                                'location' => 'header',
                                'sentAs' => 'x-cos-request-id',
                            ),
                        ),
                    ),
                    'UploadPartCopyOutput' => array(
                        'type' => 'object',
                        'additionalProperties' => true,
                        'properties' => array(
                            'CopySourceVersionId' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'x-cos-copy-source-version-id',
                            ),
                            'ETag' => array(
                                'type' => 'string',
                                'location' => 'xml',
                            ),
                            'LastModified' => array(
                                'type' => 'string',
                                'location' => 'xml',
                            ),
                            'ServerSideEncryption' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'x-cos-server-side-encryption',
                            ),
                            'SSECustomerAlgorithm' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'x-cos-server-side-encryption-customer-algorithm',
                            ),
                            'SSECustomerKeyMD5' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'x-cos-server-side-encryption-customer-key-MD5',
                            ),
                            'SSEKMSKeyId' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'x-cos-server-side-encryption-aws-kms-key-id',
                            ),
                            'RequestCharged' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'x-cos-request-charged',
                            ),
                            'RequestId' => array(
                                'location' => 'header',
                                'sentAs' => 'x-cos-request-id',
                            ),
                        ),
                    ),
                    'PutBucketAclOutput' => array(
                        'type' => 'object',
                        'additionalProperties' => true,
                        'properties' => array(
                            'RequestId' => array(
                                'location' => 'header',
                                'sentAs' => 'x-cos-request-id'))),
                    'PutObjectOutput' => array(
                        'type' => 'object',
                        'additionalProperties' => true,
                        'properties' => array(
                            'Expiration' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'x-cos-expiration',
                            ),
                            'ETag' => array(
                                'type' => 'string',
                                'location' => 'header',
                            ),
                            'ServerSideEncryption' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'x-cos-server-side-encryption',
                            ),
                            'VersionId' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'x-cos-version-id',
                            ),
                            'SSECustomerAlgorithm' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'x-cos-server-side-encryption-customer-algorithm',
                            ),
                            'SSECustomerKeyMD5' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'x-cos-server-side-encryption-customer-key-MD5',
                            ),
                            'SSEKMSKeyId' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'x-cos-server-side-encryption-aws-kms-key-id',
                            ),
                            'RequestCharged' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'x-cos-request-charged',
                            ),
                            'RequestId' => array(
                                'location' => 'header',
                                'sentAs' => 'x-cos-request-id',
                            ),
                        ),
                    ),
                    'PutObjectAclOutput' => array(
                        'type' => 'object',
                        'additionalProperties' => true,
                        'properties' => array(
                            'RequestCharged' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'x-cos-request-charged',
                            ),
                            'RequestId' => array(
                                'location' => 'header',
                                'sentAs' => 'x-cos-request-id',
                            ),
                        ),
                    ),
                    'PutBucketCorsOutput' => array(
                        'type' => 'object',
                        'additionalProperties' => true,
                        'properties' => array(
                            'RequestId' => array(
                                'location' => 'header',
                                'sentAs' => 'x-cos-request-id',
                            ),
                        ),
                    ),
                    'PutBucketLifecycleOutput' => array(
                        'type' => 'object',
                        'additionalProperties' => true,
                        'properties' => array(
                            'RequestId' => array(
                                'location' => 'header',
                                'sentAs' => 'x-cos-request-id',
                            ),
                        ),
                    ),
                    'PutBucketVersioningOutput' => array(
                        'type' => 'object',
                        'additionalProperties' => true,
                        'properties' => array(
                            'RequestId' => array(
                                'location' => 'header',
                                'sentAs' => 'x-cos-request-id',
                            ),
                        ),
                    ),
                    'PutBucketReplicationOutput' => array(
                        'type' => 'object',
                        'additionalProperties' => true,
                        'properties' => array(
                            'RequestId' => array(
                                'location' => 'header',
                                'sentAs' => 'x-cos-request-id',
                            ),
                        ),
                    ),
                    'RestoreObjectOutput' => array(
                        'type' => 'object',
                        'additionalProperties' => true,
                        'properties' => array(
                            'RequestCharged' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'x-cos-request-charged',
                            ),
                            'RequestId' => array(
                                'location' => 'header',
                                'sentAs' => 'x-cos-request-id',
                            ),
                        ),
                    ),
                    'ListPartsOutput' => array(
                        'type' => 'object',
                        'additionalProperties' => true,
                        'properties' => array(
                            'Bucket' => array(
                                'type' => 'string',
                                'location' => 'xml'),
                            'Key' => array(
                                'type' => 'string',
                                'location' => 'xml'),
                            'UploadId' => array(
                                'type' => 'string',
                                'location' => 'xml'),
                            'PartNumberMarker' => array(
                                'type' => 'numeric',
                                'location' => 'xml'),
                            'NextPartNumberMarker' => array(
                                'type' => 'numeric',
                                'location' => 'xml'),
                            'MaxParts' => array(
                                'type' => 'numeric',
                                'location' => 'xml'),
                            'IsTruncated' => array(
                                'type' => 'boolean',
                                'location' => 'xml'),
                            'Parts' => array(
                                'type' => 'array',
                                'location' => 'xml',
                                'sentAs' => 'Part',
                                'data' => array(
                                    'xmlFlattened' => true),
                                'items' => array(
                                    'name' => 'Part',
                                    'type' => 'object',
                                    'sentAs' => 'Part',
                                    'properties' => array(
                                        'PartNumber' => array(
                                            'type' => 'numeric'),
                                        'LastModified' => array(
                                            'type' => 'string'),
                                        'ETag' => array(
                                            'type' => 'string'),
                                        'Size' => array(
                                            'type' => 'numeric')))),
                            'Initiator' => array(
                                'type' => 'object',
                                'location' => 'xml',
                                'properties' => array(
                                    'ID' => array(
                                        'type' => 'string'),
                                    'DisplayName' => array(
                                        'type' => 'string'))),
                            'Owner' => array(
                                'type' => 'object',
                                'location' => 'xml',
                                'properties' => array(
                                    'DisplayName' => array(
                                        'type' => 'string'),
                                    'ID' => array(
                                        'type' => 'string'))),
                            'StorageClass' => array(
                                'type' => 'string',
                                'location' => 'xml'),
                            'RequestId' => array(
                                'location' => 'header',
                                'sentAs' => 'x-cos-request-id'))),
                    'ListObjectsOutput' => array(
                        'type' => 'object',
                        'additionalProperties' => true,
                        'properties' => array(
                            'IsTruncated' => array(
                                'type' => 'boolean',
                                'location' => 'xml'),
                            'Marker' => array(
                                'type' => 'string',
                                'location' => 'xml'),
                            'NextMarker' => array(
                                'type' => 'string',
                                'location' => 'xml'),
                            'Contents' => array(
                                'type' => 'array',
                                'location' => 'xml',
                                'data' => array(
                                    'xmlFlattened' => true),
                                'items' => array(
                                    'name' => 'Object',
                                    'type' => 'object',
                                    'properties' => array(
                                        'Key' => array(
                                            'type' => 'string'),
                                        'LastModified' => array(
                                            'type' => 'string'),
                                        'ETag' => array(
                                            'type' => 'string'),
                                        'Size' => array(
                                            'type' => 'numeric'),
                                        'StorageClass' => array(
                                            'type' => 'string'),
                                        'Owner' => array(
                                            'type' => 'object',
                                            'properties' => array(
                                                'DisplayName' => array(
                                                    'type' => 'string'),
                                                'ID' => array(
                                                    'type' => 'string')))))),
                            'Name' => array(
                                'type' => 'string',
                                'location' => 'xml'),
                            'Prefix' => array(
                                'type' => 'string',
                                'location' => 'xml'),
                            'Delimiter' => array(
                                'type' => 'string',
                                'location' => 'xml'),
                            'MaxKeys' => array(
                                'type' => 'numeric',
                                'location' => 'xml'),
                            'CommonPrefixes' => array(
                                'type' => 'array',
                                'location' => 'xml',
                                'data' => array(
                                    'xmlFlattened' => true),
                                'items' => array(
                                    'name' => 'CommonPrefix',
                                    'type' => 'object',
                                    'properties' => array(
                                        'Prefix' => array(
                                            'type' => 'string')))),
                            'EncodingType' => array(
                                    'type' => 'string',
                                    'location' => 'xml'),
                            'RequestId' => array(
                                    'location' => 'header',
                                    'sentAs' => 'x-cos-request-id'))),
                    'ListBucketsOutput' => array(
                        'type' => 'object',
                        'additionalProperties' => true,
                        'properties' => array(
                            'Buckets' => array(
                                'type' => 'array',
                                'location' => 'xml',
                                'items' => array(
                                    'name' => 'Bucket',
                                    'type' => 'object',
                                    'sentAs' => 'Bucket',
                                    'properties' => array(
                                        'Name' => array(
                                            'type' => 'string',
                                        ),
                                        'CreationDate' => array(
                                            'type' => 'string',
                                        ),
                                    ),
                                ),
                            ),
                            'Owner' => array(
                                'type' => 'object',
                                'location' => 'xml',
                                'properties' => array(
                                    'DisplayName' => array(
                                        'type' => 'string',
                                    ),
                                    'ID' => array(
                                        'type' => 'string',
                                    ),
                                ),
                            ),
                            'RequestId' => array(
                                'location' => 'header',
                                'sentAs' => 'x-cos-request-id',
                            ),
                        ),
                    ),
                    'ListObjectVersionsOutput' => array(
                        'type' => 'object',
                        'additionalProperties' => true,
                        'properties' => array(
                            'IsTruncated' => array(
                                'type' => 'boolean',
                                'location' => 'xml',
                            ),
                            'KeyMarker' => array(
                                'type' => 'string',
                                'location' => 'xml',
                            ),
                            'VersionIdMarker' => array(
                                'type' => 'string',
                                'location' => 'xml',
                            ),
                            'NextKeyMarker' => array(
                                'type' => 'string',
                                'location' => 'xml',
                            ),
                            'NextVersionIdMarker' => array(
                                'type' => 'string',
                                'location' => 'xml',
                            ),
                            'Versions' => array(
                                'type' => 'array',
                                'location' => 'xml',
                                'sentAs' => 'Version',
                                'data' => array(
                                    'xmlFlattened' => true,
                                ),
                                'items' => array(
                                    'name' => 'ObjectVersion',
                                    'type' => 'object',
                                    'sentAs' => 'Version',
                                    'properties' => array(
                                        'ETag' => array(
                                            'type' => 'string',
                                        ),
                                        'Size' => array(
                                            'type' => 'numeric',
                                        ),
                                        'StorageClass' => array(
                                            'type' => 'string',
                                        ),
                                        'Key' => array(
                                            'type' => 'string',
                                        ),
                                        'VersionId' => array(
                                            'type' => 'string',
                                        ),
                                        'IsLatest' => array(
                                            'type' => 'boolean',
                                        ),
                                        'LastModified' => array(
                                            'type' => 'string',
                                        ),
                                        'Owner' => array(
                                            'type' => 'object',
                                            'properties' => array(
                                                'DisplayName' => array(
                                                    'type' => 'string',
                                                ),
                                                'ID' => array(
                                                    'type' => 'string',
                                                ),
                                            ),
                                        ),
                                    ),
                                ),
                            ),
                            'DeleteMarkers' => array(
                                'type' => 'array',
                                'location' => 'xml',
                                'sentAs' => 'DeleteMarker',
                                'data' => array(
                                    'xmlFlattened' => true,
                                ),
                                'items' => array(
                                    'name' => 'DeleteMarkerEntry',
                                    'type' => 'object',
                                    'sentAs' => 'DeleteMarker',
                                    'properties' => array(
                                        'Owner' => array(
                                            'type' => 'object',
                                            'properties' => array(
                                                'DisplayName' => array(
                                                    'type' => 'string',
                                                ),
                                                'ID' => array(
                                                    'type' => 'string',
                                                ),
                                            ),
                                        ),
                                        'Key' => array(
                                            'type' => 'string',
                                        ),
                                        'VersionId' => array(
                                            'type' => 'string',
                                        ),
                                        'IsLatest' => array(
                                            'type' => 'boolean',
                                        ),
                                        'LastModified' => array(
                                            'type' => 'string',
                                        ),
                                    ),
                                ),
                            ),
                            'Name' => array(
                                'type' => 'string',
                                'location' => 'xml',
                            ),
                            'Prefix' => array(
                                'type' => 'string',
                                'location' => 'xml',
                            ),
                            'Delimiter' => array(
                                'type' => 'string',
                                'location' => 'xml',
                            ),
                            'MaxKeys' => array(
                                'type' => 'numeric',
                                'location' => 'xml',
                            ),
                            'CommonPrefixes' => array(
                                'type' => 'array',
                                'location' => 'xml',
                                'data' => array(
                                    'xmlFlattened' => true,
                                ),
                                'items' => array(
                                    'name' => 'CommonPrefix',
                                    'type' => 'object',
                                    'properties' => array(
                                        'Prefix' => array(
                                            'type' => 'string',
                                        ),
                                    ),
                                ),
                            ),
                            'EncodingType' => array(
                                'type' => 'string',
                                'location' => 'xml',
                            ),
                            'RequestId' => array(
                                'location' => 'header',
                                'sentAs' => 'x-cos-request-id',
                            ),
                        ),
                    ),
                    'HeadObjectOutput' => array(
                        'type' => 'object',
                        'additionalProperties' => true,
                        'properties' => array(
                            'DeleteMarker' => array(
                                'type' => 'boolean',
                                'location' => 'header',
                                'sentAs' => 'x-cos-delete-marker',
                            ),
                            'AcceptRanges' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'accept-ranges',
                            ),
                            'Expiration' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'x-cos-expiration',
                            ),
                            'Restore' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'x-cos-restore',
                            ),
                            'LastModified' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'Last-Modified',
                            ),
                            'ContentLength' => array(
                                'type' => 'numeric',
                                'location' => 'header',
                                'sentAs' => 'Content-Length',
                            ),
                            'ETag' => array(
                                'type' => 'string',
                                'location' => 'header',
                            ),
                            'MissingMeta' => array(
                                'type' => 'numeric',
                                'location' => 'header',
                                'sentAs' => 'x-cos-missing-meta',
                            ),
                            'VersionId' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'x-cos-version-id',
                            ),
                            'CacheControl' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'Cache-Control',
                            ),
                            'ContentDisposition' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'Content-Disposition',
                            ),
                            'ContentEncoding' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'Content-Encoding',
                            ),
                            'ContentLanguage' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'Content-Language',
                            ),
                            'ContentType' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'Content-Type',
                            ),
                            'Expires' => array(
                                'type' => 'string',
                                'location' => 'header',
                            ),
                            'WebsiteRedirectLocation' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'x-cos-website-redirect-location',
                            ),
                            'ServerSideEncryption' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'x-cos-server-side-encryption',
                            ),
                            'Metadata' => array(
                                'type' => 'object',
                                'location' => 'header',
                                'sentAs' => 'x-cos-meta-',
                                'additionalProperties' => array(
                                    'type' => 'string',
                                ),
                            ),
                            'SSECustomerAlgorithm' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'x-cos-server-side-encryption-customer-algorithm',
                            ),
                            'SSECustomerKeyMD5' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'x-cos-server-side-encryption-customer-key-MD5',
                            ),
                            'SSEKMSKeyId' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'x-cos-server-side-encryption-aws-kms-key-id',
                            ),
                            'StorageClass' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'x-cos-storage-class',
                            ),
                            'RequestCharged' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'x-cos-request-charged',
                            ),
                            'ReplicationStatus' => array(
                                'type' => 'string',
                                'location' => 'header',
                                'sentAs' => 'x-cos-replication-status',
                            ),
                            'RequestId' => array(
                                'location' => 'header',
                                'sentAs' => 'x-cos-request-id',
                            ))),
                    'HeadBucketOutput' => array(
                        'type' => 'object',
                        'additionalProperties' => true,
                        'properties' => array(
                            'RequestId' => array(
                                'location' => 'header',
                                'sentAs' => 'x-cos-request-id',
                            ),
                        ),
                    ),));
    }
}
