<?php

namespace DealNews\ObjectStorage\Backends;

use Aws\S3\S3Client;
use DealNews\ObjectStorage\Data\StorageObject;
use DealNews\GetConfig\GetConfig;
use GuzzleHttp\Client;

/**
 * S3 storage interface
 *
 * @author      Brian Moon <brianm@dealnews.com>
 * @copyright   1997-Present DealNews.com, Inc
 * @package     \DealNews\ObjectStorage
 */
class S3 implements StorageInterface {

    /**
     * Default ACL for uploads. Defaults to bucket default.
     * Possible values: private, public-read, public-read-write,
     *                  authenticated-read, aws-exec-read, bucket-owner-read,
     *                  bucket-owner-full-control
     */
    protected const DEFAULT_ACL = null;

    /**
     * Bucket name
     *
     * @var string
     */
    protected string $bucket;

    /**
     * S3 Client
     *
     * @var S3Client
     */
    protected S3Client $client;

    /**
     * Maximum file size that can be returned in the object_data
     *
     * @var int
     */
    protected int $max_file_size = 0;

    protected Client $guzzle;

    /**
     * Creates an instance using GetConfig
     *
     * @param      array          $options  S3 Config options
     * @param      ?S3Client      $client   Optional client for testing
     *
     * @return     StorageInterface
     */
    public static function init(array $options = [], ?int $memory_limit = null, ?S3Client $client = null, ?Client $guzzle = null): StorageInterface {
        if (isset($options['profile'])) {
            $config = GetConfig::init();
            $bucket = $config->get("s3.{$options['profile']}.bucket") ?? null;
            $region = $config->get("s3.{$options['profile']}.region") ?? null;
            $key    = $config->get("s3.{$options['profile']}.key") ?? null;
            $secret = $config->get("s3.{$options['profile']}.secret") ?? null;
        }

        $bucket ??= $options['bucket'] ?? null;
        $region ??= $options['region'] ?? null;
        $key    ??= $options['key'] ?? null;
        $secret ??= $options['secret'] ?? null;

        return new self(
            $bucket,
            $region,
            $key,
            $secret,
            $memory_limit,
            $client,
            $guzzle
        );
    }

    /**
     * Constructs a new instance.
     *
     * @param      string  $bucket  The S3 bucket
     * @param      string  $region  The AWS region
     * @param      string  $key     The AWS key
     * @param      string  $secret  The AWS secret
     */
    public function __construct(string $bucket, string $region, string $key, string $secret, ?int $memory_limit = null, ?S3Client $client = null, ?Client $guzzle = null) {
        $this->bucket = $bucket;

        // Get these credentials from somewhere else in production
        $this->client = $client ?? new S3Client([
            'version'     => 'latest',
            'region'      => $region,
            'credentials' => [
                'key'    => $key,
                'secret' => $secret,
            ],
        ]);

        $this->guzzle = $guzzle ?? new Client([]);

        $memory_limit ??= strtoupper(ini_get('memory_limit'));

        if (preg_match('/^(\d+)([KMG])$/', $memory_limit, $match)) {
            switch ($match[2]) {
                case 'K':
                    $memory_limit = $match[1] * 1024;
                    break;
                case 'M':
                    $memory_limit = $match[1] * 1024 * 1024;
                    break;
                case 'G':
                    $memory_limit = $match[1] * 1024 * 1024 * 1024;
                    break;
            }
        }

        $this->max_file_size = ((int)$memory_limit) / 2;
    }

    /**
     * Gets the specified url path.
     *
     * @param      string  $url_path  The url path
     * @param      ?string $filename  File name
     *
     * @return     StorageObject
     */
    public function get(string $url_path, ?string $filename = null): StorageObject {
        return $this->realGet($url_path, true, $filename);
    }

    /**
     * Gets the object meta data only
     *
     * @param      string  $url_path  The url path
     *
     * @return     StorageObject
     */
    public function head(string $url_path): StorageObject {
        return $this->realGet($url_path, false);
    }

    /**
     * Returns an array of object keys
     *
     * @param      string  $prefix  Prefix of objects to match
     *
     * @return     array
     */
    public function list(string $prefix): array {
        $keys = [];

        $prefix = ltrim($prefix, '/');

        $result = $this->client->listObjectsV2([
            'Bucket' => $this->bucket,
            'Prefix' => $prefix,
        ]);

        $contents = $result->get('Contents') ?? [];

        if (!empty($contents)) {
            foreach ($contents as $obj) {
                $keys[] = $obj['Key'];
            }
        }

        return $keys;
    }

    /**
     * Stores an object in S3
     *
     * @param      string      $url_path      The url path
     * @param      string      $object_data   The object data
     * @param      string      $content_type  The content type
     * @param      array       $meta_data     The meta data
     * @param      ?string     $acl           Optional ACL
     *
     * @return     StorageObject|null
     */
    public function put(string $url_path, string $object_data, string $content_type, array $meta_data = [], ?string $acl = null): ?StorageObject {
        $url_path = $this->parseUrl($url_path);

        $object = [
            'Bucket'      => $this->bucket,
            'Key'         => $url_path,
            'Body'        => $object_data,
            'ContentType' => $content_type,
        ];

        if (empty($acl) && !empty($this::DEFAULT_ACL)) {
            $acl = $this::DEFAULT_ACL;
        }

        if (!empty($acl)) {
            $object['ACL'] = $acl;
        }

        if (!empty($meta_data)) {
            $meta_data          = $this->normalizeMetaData($meta_data);
            $object['Metadata'] = $meta_data;
        }

        $result = $this->client->putObject($object);
        if (!empty($result)) {
            // @phan-suppress-next-line PhanTypeArraySuspiciousNullable
            $result = $this->head($result->get('@metadata')['effectiveUri']);
        } else {
            $result = null;
        }

        return $result;
    }

    /**
     * Deletes the given url path.
     *
     * @param      string  $url_path  The url path
     *
     * @return     bool
     */
    public function delete(string $url_path): bool {
        $url_path = $this->parseUrl($url_path);

        $object = [
            'Bucket' => $this->bucket,
            'Key'    => $url_path,
        ];

        try {
            $this->client->deleteObject($object);
            $data = true;
        } catch (\Aws\S3\Exception\S3Exception $e) { // @phan-suppress-current-line PhanUnusedVariableCaughtException
            $data = false;
        }

        return $data;
    }

    /**
     * Gets the object meta data and optionaly the data
     *
     * @param      string  $url_path  The url path
     * @param      bool    $get_data  If true the object body is returned
     * @param      ?string $filename  File name to save data to
     *
     * @return     StorageObject
     */
    protected function realGet(string $url_path, bool $get_data, ?string $filename = null): StorageObject {

        $url_path = $this->parseUrl($url_path);

        try {
            $options = [
                'Bucket' => $this->bucket,
                'Key'    => $url_path,
            ];

            // Download the contents of the object.
            $result = $this->client->headObject($options);

            $object = $this->buildResponse($result, $get_data);

            if ($get_data) {
                if (empty($filename) && $object->size > $this->max_file_size) {
                    throw new \RuntimeException('File is too large. Provide a filename for saving the object data');
                }

                $cmd          = $this->client->getCommand('GetObject', $options);
                $request      = $this->client->createPresignedRequest($cmd, '+15 minutes');
                $presignedUrl = (string)$request->getUri();

                $tmp_file = sys_get_temp_dir() . '/' . sha1(random_bytes(128));

                $this->guzzle->request('GET', $presignedUrl, ['sink' => $tmp_file]);

                if (!empty($filename)) {
                    rename($tmp_file, $filename);
                    $object->local_filename = $filename;
                } else {
                    $object->object_data = file_get_contents($tmp_file);
                }
            }
        } catch (\Aws\S3\Exception\S3Exception $e) {
            throw $e;
        }

        return $object;
    }

    /**
     * Builds a response array from an S3 result
     *
     * @param      \Aws\Result  $result  The result
     *
     * @return     StorageObject        The response.
     */
    protected function buildResponse(\Aws\Result $result, bool $get_data = false): StorageObject {
        $meta_data     = [];
        $amz_meta_data = $result->get('@metadata') ?? [];
        if (!empty($amz_meta_data['headers']) && is_array($amz_meta_data['headers'])) {
            foreach ($amz_meta_data['headers'] as $key => $value) {
                if (stripos($key, 'x-amz-meta-') === 0) {
                    $key                = preg_replace('/^x-amz-meta-/i', '', $key);
                    $meta_data[$key]    = $value;
                }
            }
        }

        $meta_data = $this->normalizeMetaData($meta_data);

        $object = new StorageObject();

        $object->service           = 'S3';
        $object->object_storage_id = $amz_meta_data['headers']['x-amz-id-2']; // @phan-suppress-current-line PhanTypePossiblyInvalidDimOffset
        $object->container         = $this->bucket;
        $object->key               = $this->parseUrl((string)$amz_meta_data['effectiveUri']);
        $object->url_path          = parse_url((string)$amz_meta_data['effectiveUri'], PHP_URL_PATH);
        $object->storage_url       = "s3://{$this->bucket}/{$object->key}";
        $object->content_type      = $amz_meta_data['headers']['content-type'] ?? null;
        $object->meta_data         = $meta_data;
        $object->last_modified     = $amz_meta_data['headers']['last-modified'] ?? $amz_meta_data['headers']['date']; // @phan-suppress-current-line PhanTypePossiblyInvalidDimOffset
        $object->size              = (int)$amz_meta_data['headers']['content-length']; // @phan-suppress-current-line PhanTypePossiblyInvalidDimOffset

        if ($get_data) {
            $object->object_data = $result->get('Body');
        }

        return $object;
    }

    /**
     * Parses the URL to remove common prefixes
     *
     * @param      string  $url_path  The url path
     *
     * @return     string
     */
    protected function parseUrl(string $url_path): string {
        $url_path = rawurldecode(str_replace(
            [
                "https://{$this->bucket}.s3.amazonaws.com/",
                "https://s3.amazonaws.com/{$this->bucket}/",
                "s3://{$this->bucket}/",
            ],
            '/',
            $url_path
        ));

        // remove any leading slash
        $url_path = ltrim($url_path, '/');

        return $url_path;
    }

    /**
     * Normalize the case of the meta data fields
     *
     * @param      array  $meta_data  The meta data
     *
     * @return     array
     */
    protected function normalizeMetaData(array $meta_data): array {
        foreach ($meta_data as $name => $value) {
            $new_name = preg_replace('/[^a-z0-9A-Z]/', '-', $name);
            $new_name = preg_replace('/-+/', '-', $new_name);
            $new_name = str_replace(' ', '-', ucwords(str_replace('-', ' ', strtolower($new_name))));
            if (substr($new_name, 0, 2) === 'X-') {
                $new_name = substr($new_name, 2);
            }
            unset($meta_data[$name]);
            $meta_data[$new_name] = $value;
        }

        ksort($meta_data);

        return $meta_data;
    }
}
