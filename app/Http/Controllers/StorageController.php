<?php

namespace App\Http\Controllers;

use Output;
use Log;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Google\Cloud\Storage\StorageClient;

class StorageController extends Controller
{
    /**
     * @var StorageClient
     */
    private $storageClient;

    /**
     * StorageController constructor.
     *
     */
    public function __construct()
    {
        parent::__construct();

        $config = [
            'keyFilePath' => config('app.google_cloud_storage_credential_file')
        ];

        $this->storageClient = new StorageClient($config);
    }

    /**
     * Upload a file to Google Cloud Storage
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function write(Request $request)
    {
        $bucket = $this->storageClient->bucket(config('app.google_cloud_storage_bucket_name'));

        $data = file_get_contents('php://input');

        $mineType = $_SERVER['HTTP_CONTENT_TYPE'] ?? 'application/octet-stream';

        $options = [
            'name'     => static::getDestination(),
            'metadata' => [
                'contentType' => $mineType
            ]
        ];

        try {
            $object = $bucket->upload($data, $options);
            $info = $object->info();

            if (!isset($info['name'])) {
                throw new \Exception('上传文件出错');
            }

            $url = 'https://storage.googleapis.com/' . $info['bucket'] . '/' . $info['name'];
        } catch (\Exception $e) {
            static::log($e);
            return Output::error(trans('common.server_is_busy'), 10900, [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return Output::ok([
            'url' => $url
        ]);
    }

    /**
     * 返回存储路径
     *
     * @return string
     */
    public static function getDestination()
    {
        $id = md5(uniqid());
        $sum = sha1(rand(1, 999999999) . $id);

        return implode('/', [
            substr($sum, 0, 8),
            substr($sum, -8),
            $id
        ]);
    }
}
