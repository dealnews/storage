<?php

namespace DealNews\ObjectStorage\Data;

/**
 * Value object for storage objects
 *
 * @author      Brian Moon <brianm@dealnews.com>
 * @copyright   1997-Present DealNews.com, Inc
 * @package     DealNews\ObjectStorage
 *
 */
class StorageObject {

    /**
     * Service where the object is stored
     * @suppress PhanWriteOnlyPublicProperty, PhanUnreferencedPublicProperty
     */
    public string  $service;

    /**
     * Unique Id for the object within the service
     * @suppress PhanWriteOnlyPublicProperty, PhanUnreferencedPublicProperty
     */
    public string  $object_storage_id;

    /**
     * Container (aka bucket) where the object is stored
     * @suppress PhanWriteOnlyPublicProperty, PhanUnreferencedPublicProperty
     */
    public string  $container;

    /**
     * Unique human friendly key name for the object
     * @suppress PhanWriteOnlyPublicProperty, PhanUnreferencedPublicProperty
     */
    public string  $key;

    /**
     * URL path for the object
     * @suppress PhanWriteOnlyPublicProperty, PhanUnreferencedPublicProperty
     */
    public string  $url_path;

    /**
     * Public HTTP(s) URL for the object
     * @suppress PhanWriteOnlyPublicProperty, PhanUnreferencedPublicProperty
     */
    public string  $public_url;

    /**
     * Storage service specific URL for the object
     * @suppress PhanWriteOnlyPublicProperty, PhanUnreferencedPublicProperty
     */
    public string  $storage_url;

    /**
     * Content type of the object
     * @suppress PhanWriteOnlyPublicProperty, PhanUnreferencedPublicProperty
     */
    public ?string $content_type = null;

    /**
     * Array of key/value pair meta data
     * @suppress PhanWriteOnlyPublicProperty, PhanUnreferencedPublicProperty
     */
    public array   $meta_data = [];

    /**
     * Date string of the last modified datetime
     * @suppress PhanWriteOnlyPublicProperty, PhanUnreferencedPublicProperty
     */
    public ?string $last_modified = null;

    /**
     * The object data (aka body or contents)
     * Only one of local_filename or object_data should be filled.
     * @suppress PhanWriteOnlyPublicProperty, PhanUnreferencedPublicProperty
     */
    public ?string $object_data = null;

    /**
     * Local file name where object data is stored.
     * Only one of local_filename or object_data should be filled.
     * @suppress PhanWriteOnlyPublicProperty, PhanUnreferencedPublicProperty
     */
    public ?string $local_filename = null;

    /**
     * The size in bytes of the object data
     * @suppress PhanWriteOnlyPublicProperty, PhanUnreferencedPublicProperty
     */
    public int $size = 0;
}
