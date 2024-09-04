<?php

namespace DealNews\ObjectStorage\Tests\Backends;

use DealNews\ObjectStorage\Backends\S3;

class S3FunctionalTest extends \PHPUnit\Framework\TestCase {
    protected S3 $s3;

    protected string $tmpfile = '';

    public function testFunctionality() {
        $data = microtime();

        $result = $this->s3->put('/php-storage-unit-test', $data, 'text/plain', ['foo' => 'bar']);

        $this->assertNotEmpty($result->object_storage_id);

        $this->assertEquals(
            'text/plain',
            $result->content_type
        );

        $this->assertEquals(
            'bar',
            $result->meta_data['Foo']
        );

        $result = $this->s3->head('/php-storage-unit-test');

        $this->assertNull($result->object_data);

        $result = $this->s3->get('/php-storage-unit-test');

        $this->assertEquals($data, $result->object_data);

        $tmp_file = sys_get_temp_dir() . '/php-storage-unit-test';

        if (file_exists($tmp_file)) {
            unlink($tmp_file);
        }

        $result = $this->s3->get('/php-storage-unit-test', $tmp_file);

        $this->assertNull($result->object_data);

        $this->assertTrue(file_exists($tmp_file));

        $this->assertEquals($data, file_get_contents($tmp_file));

        unlink($tmp_file);

        $result = $this->s3->list('');

        $this->assertTrue(in_array('php-storage-unit-test', $result));

        $this->assertTrue($this->s3->delete('/php-storage-unit-test'));
    }

    public function setUp(): void {
        if (!file_exists("{$_SERVER['HOME']}/.aws/credentials")) {
            $this->markTestSkipped('AWS Credentials file not found');
        }

        $config = parse_ini_file("{$_SERVER['HOME']}/.aws/credentials", true);

        if (empty($config['default']['aws_access_key_id']) || empty($config['default']['aws_secret_access_key'])) {
            $this->markTestSkipped('AWS Credentials file does not contain defualt key and secret');
        }

        $this->s3 = S3::init(
            [
                'bucket' => 'dealnews_test',
                'region' => 'us-east-1',
                'key'    => $config['default']['aws_access_key_id'],
                'secret' => $config['default']['aws_secret_access_key'],
            ]
        );
    }
}
