<?php

namespace Qcloud\Cos\Tests;

use Qcloud\Cos\Client;
use Qcloud\Cos\Exception\CosException;

class ObjectTest extends \PHPUnit_Framework_TestCase {
    private $cosClient;

    protected function setUp() {
        TestHelper::nuke('testbucket-1252448703');

        $this->cosClient = new Client(array('region' => getenv('COS_REGION'),
                'credentials'=> array(
#                    'appId' => getenv('COS_APPID'),
                'secretId'    => getenv('COS_KEY'),
                'secretKey' => getenv('COS_SECRET'))));
    }



    protected function tearDown() {
        TestHelper::nuke('testbucket-1252448703');
        sleep(2);
    }

    public function testPutObject() {
        try {
            $this->cosClient->createBucket(array('Bucket' => 'testbucket-1252448703'));
            sleep(2);
            $this->cosClient->putObject(array(
                'Bucket' => 'testbucket-1252448703',
                'Key' => 'hello.txt',
                'Body' => 'Hello World',
                'ServerSideEncryption' => 'AES256'));
        } catch (\Exception $e) {
            $this->assertFalse(true, $e);
        }
    }

    public function testPutObjectIntoNonexistedBucket() {
        try {
            $this->cosClient->putObject(array(
                        'Bucket' => 'testbucket-1252448703', 'Key' => 'hello.txt', 'Body' => 'Hello World'));
        } catch (CosException $e) {
            $this->assertTrue($e->getExceptionCode() === 'NoSuchBucket');
            $this->assertTrue($e->getStatusCode() === 404);
        }
    }

    public function testUploadSmallObject() {
        try {
            $result = $this->cosClient->createBucket(array('Bucket' => 'testbucket-1252448703'));
            sleep(2);
            $this->cosClient->upload('testbucket-1252448703', '你好.txt', 'Hello World');
        } catch (\Exception $e) {
            $this->assertFalse(true, $e);
        }
    }

    public function testUploadComplexObject() {
        try {
            $result = $this->cosClient->createBucket(array('Bucket' => 'testbucket-1252448703'));
            sleep(2);
            $this->cosClient->upload('testbucket-1252448703', '→↓←→↖↗↙↘! \"#$%&\'()*+,-./0123456789:;<=>@ABCDEFGHIJKLMNOPQRSTUVWXYZ[\\]^_`abcdefghijklmnopqrstuvwxyz{|}~', 'Hello World');
        } catch (\Exception $e) {
            $this->assertFalse(true, $e);
        }
    }

    public function testUploadLargeObject() {
        try {
            $this->cosClient->createBucket(array('Bucket' => 'testbucket-1252448703'));
            sleep(5);
            $this->cosClient->upload(
                $bucket='testbucket-1252448703',
                $key = '111.txt',
                $body = str_repeat('a', 5* 1024 * 1024),
                $options = array(
                    "ACL"=>'private',
                    'CacheControl' => 'private',
                    'ServerSideEncryption' => 'AES256'));
        } catch (\Exception $e) {
            $this->assertFalse(true, $e);
        }
    }

    public function testGetObject() {
        try {
            $this->cosClient->createBucket(array('Bucket' => 'testbucket-1252448703'));
            sleep(5);
            $this->cosClient->upload('testbucket-1252448703', '你好.txt', 'Hello World');
            $this->cosClient->getObject(array(
                                    'Bucket' => 'testbucket-1252448703',
                                    'Key' => '你好.txt',));
        } catch (\Exception $e) {
            $this->assertFalse(true, $e);
        }
    }

    public function testGetObjectUrl() {
        try{
            $this->cosClient->createBucket(array('Bucket' => 'testbucket-1252448703'));
            $this->cosClient->getObjectUrl('testbucket-1252448703', 'hello.txt', '+10 minutes');
        } catch (\Exception $e) {
            $this->assertFalse(true, $e);
        }
    }

    public function testPutObjectACL() {
        try {
            $this->cosClient->createBucket(array('Bucket' => 'testbucket-1252448703'));
            sleep(5);
            $this->cosClient->upload('testbucket-1252448703', '11', 'hello.txt');
            $this->cosClient->PutObjectAcl(array(
                'Bucket' => 'testbucket-1252448703',
                'Key' => '11',
                'Grants' => array(
                    array(
                        'Grantee' => array(
                            'DisplayName' => 'qcs::cam::uin/327874225:uin/327874225',
                            'ID' => 'qcs::cam::uin/327874225:uin/327874225',
                            'Type' => 'CanonicalUser',
                        ),
                        'Permission' => 'FULL_CONTROL',
                    ),
                    // ... repeated
                ),
                'Owner' => array(
                    'DisplayName' => 'qcs::cam::uin/3210232098:uin/3210232098',
                    'ID' => 'qcs::cam::uin/3210232098:uin/3210232098',
                ),));
        } catch (\Exception $e) {
            $this->assertFalse(true, $e);
        }

    }
    public function testGetObjectACL()
    {
        try {
            $this->cosClient->createBucket(array('Bucket' => 'testbucket-1252448703'));
            sleep(5);
            $this->cosClient->upload('testbucket-1252448703', '11', 'hello.txt');
            $this->cosClient->PutObjectAcl(array(
                'Bucket' => 'testbucket-1252448703',
                'Key' => '11',
                'Grants' => array(
                    array(
                        'Grantee' => array(
                            'DisplayName' => 'qcs::cam::uin/327874225:uin/327874225',
                            'ID' => 'qcs::cam::uin/327874225:uin/327874225',
                            'Type' => 'CanonicalUser',
                        ),
                        'Permission' => 'FULL_CONTROL',
                    ),
                    // ... repeated
                ),
                'Owner' => array(
                    'DisplayName' => 'qcs::cam::uin/3210232098:uin/3210232098',
                    'ID' => 'qcs::cam::uin/3210232098:uin/3210232098',
                ),));

        } catch (\Exception $e) {
            $this->assertFalse(true, $e);
        }
    }
}
