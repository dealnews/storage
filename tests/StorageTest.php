<?php

namespace DealNews\ObjectStorage\Tests;

use DealNews\ObjectStorage\Storage;

class StorageTest extends \PHPUnit\Framework\TestCase {
    protected Storage $storage;

    public function setUp(): void {
        $backend       = new MockBackend();
        $this->storage = new Storage($backend);
    }

    public function testDownload() {
        $tmp_file = sys_get_temp_dir() . '/storage_test_file_' . sha1(random_bytes(128));

        $result = $this->storage->download('/test/path', $tmp_file);

        $this->assertEquals(
            'foo://domain/test/path',
            $result->storage_url
        );

        $this->assertNull($result->object_data);

        $this->assertEquals($tmp_file, $result->local_filename);

        unlink($tmp_file);
    }

    public function testStore() {
        $result = $this->storage->store(__FILE__, '/test/path', [], true);

        $this->assertEquals(
            'foo://domain/test/path',
            $result->storage_url
        );

        $this->assertNull($result->object_data);

        $result = $this->storage->store(__FILE__, '/test/path', [], false);

        $this->assertEquals(
            'foo://domain/test/path',
            $result->storage_url
        );

        $this->expectException(\RuntimeException::class);
        $result = $this->storage->store(__FILE__, '/bad/data', [], false);
    }

    public function testList() {
        $result = $this->storage->list('test');

        $this->assertEquals(
            [
                'test/foo',
                'test/bar',
            ],
            $result
        );
    }

    public function testHead() {
        $result = $this->storage->head('/test/path');

        $this->assertEquals(
            'foo://domain/test/path',
            $result->storage_url
        );

        $this->assertNull($result->object_data);
    }

    public function testGet() {
        $result = $this->storage->get('/test/path');

        $this->assertEquals(
            'foo://domain/test/path',
            $result->storage_url
        );

        $this->assertNotNull($result->object_data);
    }

    public function testDelete() {
        $result = $this->storage->delete('/test/path');

        $this->assertTrue($result);
    }
}
