<?php

class JsonableException extends Exception {

    public function responseJson()
    {
        header('Content-Type: application/json');

        echo json_encode(array(
            'status' => false,
            'error'  => $this->getMessage(),
        ));
    }

}

class VersionNotMatchException extends JsonableException{}

class DownloadErrorException extends JsonableException{}

class UnzipErrorException extends JsonableException{}