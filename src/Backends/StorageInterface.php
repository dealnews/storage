<?php

namespace DealNews\ObjectStorage\Backends;

use DealNews\ObjectStorage\Data\StorageObject;

/**
 * Interface for storage classes
 *
 * @author      Brian Moon <brianm@dealnews.com>
 * @copyright   1997-Present DealNews.com, Inc
 * @package     \DealNews\ObjectStorage
 */
interface StorageInterface {
    /**
     * Gets the specified url path.
     *
     * @param      string  $url_path  The url path
     * @param      ?string $filename  File name
     *
     * @return     StorageObject
     */
    public function get(string $url_path, ?string $filename = null): StorageObject;

    /**
     * Gets the object attributes and meta data only
     *
     * @param      string  $url_path  The url path
     *
     * @return     StorageObject
     */
    public function head(string $url_path): StorageObject;

    /**
     * Returns an array of object keys
     *
     * @param      string  $prefix  Prefix of objects to match
     *
     * @return     array
     */
    public function list(string $prefix): array;

    /**
     * Stores an object
     *
     * @param      string      $url_path      The url path
     * @param      string      $object_data   The object data
     * @param      string      $content_type  The content type
     * @param      array       $meta_data     The meta data
     * @param      ?string     $acl           Optional ACL
     *
     * @return     StorageObject|null
     */
    public function put(string $url_path, string $object_data, string $content_type, array $meta_data = [], ?string $acl = null): ?StorageObject;

    /**
     * Deletes the given url path.
     *
     * @param      string  $url_path  The url path
     *
     * @return     bool
     */
    public function delete(string $url_path): bool;

    /**
     * Creates an instance with the given options
     *
     * @param      array                  $options  Config options for the service
     *
     * @return     StorageInterface
     */
    public static function init(array $options = []): StorageInterface;
}
