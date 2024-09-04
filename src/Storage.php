<?php

namespace DealNews\ObjectStorage;

use DealNews\ObjectStorage\Data\StorageObject;
use DealNews\ObjectStorage\Backends\StorageInterface;

/**
 * Class which moves files to and from long term storage
 *
 * @author      Brian Moon <brianm@dealnews.com>
 * @copyright   1997-Present DealNews.com, Inc
 * @package     \DealNews\ObjectStorage
 */
class Storage {

    /**
     * Storage service
     *
     * @var StorageInterface
     */
    protected StorageInterface $service;

    /**
     * Constructs a new instance.
     *
     * @param      StorageInterface  $service  The service
     */
    public function __construct(StorageInterface $service) {
        $this->service = $service;
    }

    /**
     * Gets the meta dat about the specified file from storage
     *
     * @param      string $path Storage URI
     *
     * @return     StorageObject
     */
    public function head(string $path): StorageObject {
        $result = $this->service->head($path);

        return $result;
    }

    /**
     * Gets the specified file from storage
     *
     * @param      string $path Storage URI
     *
     * @return     StorageObject
     */
    public function get(string $path): StorageObject {
        $result = $this->service->get($path);

        return $result;
    }

    /**
     * Saves the specified file from storage to the given filename.
     * The returned StorageObject will not have object_data. It will have
     * the filename in the local_filename property.
     *
     * @param      string $path     Storage URI
     * @param      string $filename File name
     *
     * @return     StorageObject
     */
    public function download(string $path, string $filename): StorageObject {
        $result = $this->service->get($path, $filename);

        return $result;
    }

    /**
     * Deletes the specified file from storage
     *
     * @param      string $path Storage URI
     *
     * @return     bool
     */
    public function delete(string $path): bool {
        $result = $this->service->delete($path);

        return $result;
    }

    /**
     * Returns an array of object keys
     *
     * @param      string  $prefix  Prefix of objects to match
     *
     * @return     array
     */
    public function list(string $prefix): array {
        return $this->service->list($prefix);
    }

    /**
     * Stores a file in a storage service
     *
     * @param      string             $filename    The filename
     * @param      string             $path        The storage URI
     * @param      array              $meta_data   Meta data to store with the file
     * @param      bool               $is_encoded  Indicates if the file is already gzip encoded
     *
     * @throws     \RuntimeException  Thrown when storage fails
     *
     * @return     StorageObject
     */
    public function store(string $filename, string $path, array $meta_data = [], bool $is_encoded = false): StorageObject {
        if (!$is_encoded) {
            $object_data = gzencode(file_get_contents($filename));
        } else {
            $object_data = file_get_contents($filename);
        }

        $result = $this->service->put(
            $path,
            $object_data,
            'application/gzip',
            $meta_data
        );

        if (!$result) {
            throw new \RuntimeException("Failed to store $filename");
        }

        return $result;
    }
}
