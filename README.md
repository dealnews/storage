# Object Storage Library

This library provides and interface to store and retrieve objects from
services such as S3.

```php
use DealNews\ObjectStorage\Storage;
use DealNews\ObjectStorage\Backends\S3;

// the options array for S3 can contain a profile name which will use
// GetConfig to find the other values in dealnews.ini or it can contain
// bucket, region, key, and secret.
$storage = new Storage(S3::init(['profile' => 'get_config_name']));

$object = $storage->store(
    $filename,
    'object/path/name',
    'content/type',
    [
        'meta' => 'data'
    ],
    'public' // ACL setting defaults to bucket default
);

echo json_encode($object, JSON_PRETTY_PRINT);
```

A `DealNews\ObjectStorage\Data\StorageObject` object is returned from the
`get`, `head`, and `store` methods. Both the `store` and `head` methods will
not return the object's body in the `object_data` property. The `get` method
will fill the `object_data` property with the object's body. The `delete`
method returns a boolean.

```json
{
    "object_storage_id": "QpFqF6v0BO7dhvNPVZO46t5by5lCRrlciL7GH8Quvc0vADZt/UU5zKCu2dHfibdIC33jb2p+fVs=",
    "container": "bucket_name",
    "key": "object/path/name",
    "url_path": "/bucket_name/object/path/name",
    "storage_url": "s3://bucket_name/object/path/name",
    "content_type": "text/plain",
    "meta_data": {
        "Meta": "data"
    },
    "last_modified": "Fri, 13 Aug 2021 21:27:47 GMT",
    "object_data": null
}
```
