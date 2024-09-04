<?php

namespace DealNews\ObjectStorage\Tests;

use DealNews\ObjectStorage\Backends\StorageInterface;
use DealNews\ObjectStorage\Data\StorageObject;

class MockBackend implements StorageInterface {
    protected $mock_domain = 'foo://domain';

    public function get(string $url_path, ?string $filename = null): StorageObject {
        $object              = new StorageObject();
        $object->storage_url = "{$this->mock_domain}{$url_path}";

        if (!empty($filename)) {
            file_put_contents($filename, 'foo');
            $object->local_filename = $filename;
        } else {
            $object->object_data = 'foo';
        }

        return $object;
    }

    public function head(string $url_path): StorageObject {
        $object              = new StorageObject();
        $object->storage_url = "{$this->mock_domain}{$url_path}";

        return $object;
    }

    public function list(string $prefix): array {
        $keys = [
            "{$prefix}/foo",
            "{$prefix}/bar",
        ];

        return $keys;
    }

    public function put(string $url_path, string $object_data, string $content_type, array $meta_data = [], string $acl = null): ?StorageObject {
        if ($url_path === '/bad/data') {
            return null;
        }
        $object              = new StorageObject();
        $object->storage_url = "{$this->mock_domain}{$url_path}";

        return $object;
    }

    public function delete(string $url_path): bool {
        return true;
    }

    public static function init(array $options = []): StorageInterface {
        return new self();
    }
}
