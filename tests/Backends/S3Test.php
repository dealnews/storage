<?php

namespace DealNews\ObjectStorage\Tests\Backends;

use Aws\Result;
use Aws\S3\S3Client;
use DealNews\ObjectStorage\Backends\S3;
use DealNews\TestHelpers\Guzzle;

class S3Test extends \PHPUnit\Framework\TestCase {

    use Guzzle;

    public function testPut() {
        $s3 = $this->getTestObject(
            [
                'putObject' => [
                    '@metadata' => [
                        'effectiveUri' => 'https://s3.amazonaws.com/test_bucket/foo/bar',
                    ],
                ],
                'headObject' => [
                    '@metadata' => [
                        'effectiveUri' => 'https://s3.amazonaws.com/test_bucket/foo/bar',
                        'headers'      => [
                            'x-amz-id-2'     => 'bar',
                            'x-amz-meta-foo' => 'meta1',
                            'content-type'   => 'text/plain',
                            'last-modified'  => '2020-01-15T12:00:00',
                            'content-length' => '6',
                        ],
                    ],
                ],
            ]
        );

        $result = $s3->put('/foo/bar', 'foo', 'text/plain', ['x-foo' => 'meta1'], 'private');

        $this->assertEquals(
            [
                'service'           => 'S3',
                'object_storage_id' => 'bar',
                'container'         => 'test_bucket',
                'key'               => 'foo/bar',
                'url_path'          => '/test_bucket/foo/bar',
                'storage_url'       => 's3://test_bucket/foo/bar',
                'content_type'      => 'text/plain',
                'meta_data'         => [
                    'Foo' => 'meta1',
                ],
                'last_modified'     => '2020-01-15T12:00:00',
                'object_data'       => null,
                'local_filename'    => null,
                'size'              => 6,
            ],
            (array)$result
        );
    }

    public function testDelete() {
        $s3 = $this->getTestObject(['deleteObject' => true]);

        $this->assertTrue($s3->delete('/foo/bar'));
    }

    public function testHead() {
        $s3 = $this->getTestObject(
            [
                'headObject' => [
                    '@metadata' => [
                        'effectiveUri' => 'https://s3.amazonaws.com/test_bucket/foo/bar',
                        'headers'      => [
                            'x-amz-id-2'     => 'bar',
                            'x-amz-meta-foo' => 'meta1',
                            'content-type'   => 'text/plain',
                            'last-modified'  => '2020-01-15T12:00:00',
                            'content-length' => '6',
                        ],
                    ],
                ],
            ]
        );

        $result = $s3->head('/foo/bar');

        $this->assertEquals(
            [
                'service'           => 'S3',
                'object_storage_id' => 'bar',
                'container'         => 'test_bucket',
                'key'               => 'foo/bar',
                'url_path'          => '/test_bucket/foo/bar',
                'storage_url'       => 's3://test_bucket/foo/bar',
                'content_type'      => 'text/plain',
                'meta_data'         => [
                    'Foo' => 'meta1',
                ],
                'last_modified'     => '2020-01-15T12:00:00',
                'object_data'       => null,
                'local_filename'    => null,
                'size'              => 6,
            ],
            (array)$result
        );
    }

    public function testGet() {
        $guzzle_container = [];

        $s3 = $this->getTestObject(
            [
                'headObject' => [
                    '@metadata' => [
                        'effectiveUri' => 'https://s3.amazonaws.com/test_bucket/foo/bar',
                        'headers'      => [
                            'x-amz-id-2'     => 'bar',
                            'x-amz-meta-foo' => 'meta1',
                            'content-type'   => 'text/plain',
                            'last-modified'  => '2020-01-15T12:00:00',
                            'content-length' => '6',
                        ],
                    ],
                ],
            ],
            [
                'codes'     => [200],
                'fixtures'  => [['foo']],
                'container' => &$guzzle_container,
            ]
        );

        $result = $s3->get('/foo/bar');

        $this->assertEquals(
            [
                'service'           => 'S3',
                'object_storage_id' => 'bar',
                'container'         => 'test_bucket',
                'key'               => 'foo/bar',
                'url_path'          => '/test_bucket/foo/bar',
                'storage_url'       => 's3://test_bucket/foo/bar',
                'content_type'      => 'text/plain',
                'meta_data'         => [
                    'Foo' => 'meta1',
                ],
                'last_modified'     => '2020-01-15T12:00:00',
                'object_data'       => '["foo"]',
                'local_filename'    => null,
                'size'              => 6,
            ],
            (array)$result
        );
    }

    public function testGetLargeFile() {
        $guzzle_container = [];

        $file_contents = str_repeat('X', 1024);

        $s3 = $this->getTestObject(
            [
                'headObject' => [
                    '@metadata' => [
                        'effectiveUri' => 'https://s3.amazonaws.com/test_bucket/foo/bar',
                        'headers'      => [
                            'x-amz-id-2'     => 'bar',
                            'x-amz-meta-foo' => 'meta1',
                            'content-type'   => 'text/plain',
                            'last-modified'  => '2020-01-15T12:00:00',
                            'content-length' => '1024',
                        ],
                    ],
                ],
            ],
            [
                'codes'     => [200],
                'fixtures'  => [[$file_contents]],
                'container' => &$guzzle_container,
            ],
            500
        );

        $tmp_file = sys_get_temp_dir() . '/storage_s3_test_file_' . sha1(random_bytes(128));

        if (file_exists($tmp_file)) {
            unlink($tmp_file);
        }

        $result = $s3->get('/foo/bar', $tmp_file);

        $this->assertEquals(
            [
                'service'           => 'S3',
                'object_storage_id' => 'bar',
                'container'         => 'test_bucket',
                'key'               => 'foo/bar',
                'url_path'          => '/test_bucket/foo/bar',
                'storage_url'       => 's3://test_bucket/foo/bar',
                'content_type'      => 'text/plain',
                'meta_data'         => [
                    'Foo' => 'meta1',
                ],
                'last_modified'     => '2020-01-15T12:00:00',
                'object_data'       => null,
                'local_filename'    => $tmp_file,
                'size'              => 1024,
            ],
            (array)$result
        );

        $this->assertTrue(file_exists($tmp_file));

        $this->assertEquals('["' . $file_contents . '"]', file_get_contents($tmp_file));

        unlink($tmp_file);
    }

    public function testList() {
        $s3 = $this->getTestObject(
            [
                'listObjectsV2' => [
                    'Contents' => [
                        [
                            'ETag'         => '"70ee1738b6b21e2c8a43f3a5ab0eee71"',
                            'Key'          => 'happyface.jpg',
                            'LastModified' => null,
                            'Size'         => 11,
                            'StorageClass' => 'STANDARD',
                        ],
                        [
                            'ETag'         => '"becf17f89c30367a9a44495d62ed521a-1"',
                            'Key'          => 'test.jpg',
                            'LastModified' => null,
                            'Size'         => 4192256,
                            'StorageClass' => 'STANDARD',
                        ],
                    ],
                    'IsTruncated'           => 1,
                    'KeyCount'              => 2,
                    'MaxKeys'               => 2,
                    'Name'                  => 'examplebucket',
                    'NextContinuationToken' => '1w41l63U0xa8q7smH50vCxyTQqdxo69O3EmK28Bi5PcROI4wI/EyIJg==',
                    'Prefix'                => '',
                ],
            ]
        );

        $result = $s3->list('');

        $this->assertEquals(
            [
                'happyface.jpg',
                'test.jpg',
            ],
            $result
        );
    }

    public function getTestObject($mock_responses = [], $guzzle_mock = [], $memory_limit = null) {
        $client = new class extends S3Client {

            public array $mock_responses = [];

            public function __construct() {
                // noop
            }

            public function listObjectsV2(array $array) {
                return $this->mockBuildResult($this->mock_responses[__FUNCTION__] ?? []);
            }

            public function putObject(array $array) {
                return $this->mockBuildResult($this->mock_responses[__FUNCTION__] ?? []);
            }

            public function deleteObject(array $array) {
                return $this->mock_responses[__FUNCTION__] ?? false;
            }

            public function getObject(array $array) {
                return $this->mockBuildResult($this->mock_responses[__FUNCTION__] ?? []);
            }

            public function headObject(array $array) {
                return $this->mockBuildResult($this->mock_responses[__FUNCTION__] ?? []);
            }

            public function getCommand($name, array $args = []) {
                return new \Aws\Command($name);
            }

            public function createPresignedRequest(\Aws\CommandInterface $command, $expires, array $options = []) {
                return new \GuzzleHttp\Psr7\Request(
                    'GET',
                    'http://www.exmaple.com/foo'
                );
            }

            protected function mockBuildResult(array $data): Result {
                return new class($data) extends Result {
                    public array $mock_data = [];

                    public function __construct(array $data) {
                        $this->mock_data = $data;
                    }

                    public function get($key) {
                        return $this->mock_data[$key] ?? null;
                    }
                };
            }
        };

        $client->mock_responses = $mock_responses;

        $guzzle = null;

        if (!empty($guzzle_mock)) {
            $guzzle = $this->makeGuzzleMock($guzzle_mock['codes'], $guzzle_mock['fixtures'], $guzzle_mock['container']);
        }

        putenv('S3_MOCK_BUCKET=test_bucket');
        putenv('S3_MOCK_REGION=');
        putenv('S3_MOCK_KEY=');
        putenv('S3_MOCK_SECRET=');

        $s3 = S3::init(
            [
                'profile' => 'mock',
            ],
            $memory_limit,
            $client,
            $guzzle
        );

        return $s3;
    }
}
