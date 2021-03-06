<?php

namespace OSS\Tests;


use OSS\Result\ListObjectsResult;
use OSS\Http\ResponseCore;

class ListObjectsResultTest extends \PHPUnit_Framework_TestCase
{

    private $validXml1 = <<<BBBB
<?xml version="1.0" encoding="UTF-8"?>
<ListBucketResult>
  <Name>testbucket-hf</Name>
  <Prefix></Prefix>
  <Marker></Marker>
  <MaxKeys>1000</MaxKeys>
  <Delimiter>/</Delimiter>
  <IsTruncated>false</IsTruncated>
  <CommonPrefixes>
    <Prefix>oss-qiniu-test/</Prefix>
  </CommonPrefixes>
  <CommonPrefixes>
    <Prefix>test/</Prefix>
  </CommonPrefixes>
</ListBucketResult>
BBBB;

    private $validXml2 = <<<BBBB
<?xml version="1.0" encoding="UTF-8"?>
<ListBucketResult>
  <Name>testbucket-hf</Name>
  <Prefix>oss-qiniu-test/</Prefix>
  <Marker>xx</Marker>
  <MaxKeys>1000</MaxKeys>
  <Delimiter>/</Delimiter>
  <IsTruncated>false</IsTruncated>
  <Contents>
    <Key>oss-qiniu-test/upload-test-object-name.txt</Key>
    <LastModified>2015-11-18T03:36:00.000Z</LastModified>
    <ETag>"89B9E567E7EB8815F2F7D41851F9A2CD"</ETag>
    <Type>Normal</Type>
    <Size>13115</Size>
    <StorageClass>Standard</StorageClass>
    <Owner>
      <ID>cname_user</ID>
      <DisplayName>cname_user</DisplayName>
    </Owner>
  </Contents>
</ListBucketResult>
BBBB;

    private $validXmlWithEncodedKey = <<<BBBB
<?xml version="1.0" encoding="UTF-8"?>
<ListBucketResult>
  <Name>testbucket-hf</Name>
  <EncodingType>url</EncodingType>
  <Prefix>php%2Fprefix</Prefix>
  <Marker>php%2Fmarker</Marker>
  <NextMarker>php%2Fnext-marker</NextMarker>
  <MaxKeys>1000</MaxKeys>
  <Delimiter>%2F</Delimiter>
  <IsTruncated>true</IsTruncated>
  <Contents>
    <Key>php/a%2Bb</Key>
    <LastModified>2015-11-18T03:36:00.000Z</LastModified>
    <ETag>"89B9E567E7EB8815F2F7D41851F9A2CD"</ETag>
    <Type>Normal</Type>
    <Size>13115</Size>
    <StorageClass>Standard</StorageClass>
    <Owner>
      <ID>cname_user</ID>
      <DisplayName>cname_user</DisplayName>
    </Owner>
  </Contents>
</ListBucketResult>
BBBB;

    public function testParseValidXml1()
    {
        $response = new ResponseCore(array(), $this->validXml1, 200);
        $result = new ListObjectsResult($response);
        $this->assertTrue($result->isOK());
        $this->assertNotNull($result->getData());
        $this->assertNotNull($result->getRawResponse());
        $objectListInfo = $result->getData();
        $this->assertEquals(2, count($objectListInfo->getPrefixList()));
        $this->assertEquals(0, count($objectListInfo->getObjectList()));
        $this->assertEquals('testbucket-hf', $objectListInfo->getBucketName());
        $this->assertEquals('', $objectListInfo->getPrefix());
        $this->assertEquals('', $objectListInfo->getMarker());
        $this->assertEquals(1000, $objectListInfo->getMaxKeys());
        $this->assertEquals('/', $objectListInfo->getDelimiter());
        $this->assertEquals('false', $objectListInfo->getIsTruncated());
        $this->assertEquals('oss-qiniu-test/', $objectListInfo->getPrefixList()[0]->getPrefix());
        $this->assertEquals('test/', $objectListInfo->getPrefixList()[1]->getPrefix());
    }

    public function testParseValidXml2()
    {
        $response = new ResponseCore(array(), $this->validXml2, 200);
        $result = new ListObjectsResult($response);
        $this->assertTrue($result->isOK());
        $this->assertNotNull($result->getData());
        $this->assertNotNull($result->getRawResponse());
        $objectListInfo = $result->getData();
        $this->assertEquals(0, count($objectListInfo->getPrefixList()));
        $this->assertEquals(1, count($objectListInfo->getObjectList()));
        $this->assertEquals('testbucket-hf', $objectListInfo->getBucketName());
        $this->assertEquals('oss-qiniu-test/', $objectListInfo->getPrefix());
        $this->assertEquals('xx', $objectListInfo->getMarker());
        $this->assertEquals(1000, $objectListInfo->getMaxKeys());
        $this->assertEquals('/', $objectListInfo->getDelimiter());
        $this->assertEquals('false', $objectListInfo->getIsTruncated());
        $this->assertEquals('oss-qiniu-test/upload-test-object-name.txt', $objectListInfo->getObjectList()[0]->getKey());
        $this->assertEquals('2015-11-18T03:36:00.000Z', $objectListInfo->getObjectList()[0]->getLastModified());
        $this->assertEquals('"89B9E567E7EB8815F2F7D41851F9A2CD"', $objectListInfo->getObjectList()[0]->getETag());
        $this->assertEquals('Normal', $objectListInfo->getObjectList()[0]->getType());
        $this->assertEquals(13115, $objectListInfo->getObjectList()[0]->getSize());
        $this->assertEquals('Standard', $objectListInfo->getObjectList()[0]->getStorageClass());
    }

    public function testParseValidXmlWithEncodedKey()
    {
        $response = new ResponseCore(array(), $this->validXmlWithEncodedKey, 200);
        $result = new ListObjectsResult($response);
        $this->assertTrue($result->isOK());
        $this->assertNotNull($result->getData());
        $this->assertNotNull($result->getRawResponse());
        $objectListInfo = $result->getData();
        $this->assertEquals(0, count($objectListInfo->getPrefixList()));
        $this->assertEquals(1, count($objectListInfo->getObjectList()));
        $this->assertEquals('testbucket-hf', $objectListInfo->getBucketName());
        $this->assertEquals('php/prefix', $objectListInfo->getPrefix());
        $this->assertEquals('php/marker', $objectListInfo->getMarker());
        $this->assertEquals('php/next-marker', $objectListInfo->getNextMarker());
        $this->assertEquals(1000, $objectListInfo->getMaxKeys());
        $this->assertEquals('/', $objectListInfo->getDelimiter());
        $this->assertEquals('true', $objectListInfo->getIsTruncated());
        $this->assertEquals('php/a+b', $objectListInfo->getObjectList()[0]->getKey());
        $this->assertEquals('2015-11-18T03:36:00.000Z', $objectListInfo->getObjectList()[0]->getLastModified());
        $this->assertEquals('"89B9E567E7EB8815F2F7D41851F9A2CD"', $objectListInfo->getObjectList()[0]->getETag());
        $this->assertEquals('Normal', $objectListInfo->getObjectList()[0]->getType());
        $this->assertEquals(13115, $objectListInfo->getObjectList()[0]->getSize());
        $this->assertEquals('Standard', $objectListInfo->getObjectList()[0]->getStorageClass());
    }
}
