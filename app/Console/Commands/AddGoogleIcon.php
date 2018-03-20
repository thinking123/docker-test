<?php

namespace App\Console\Commands;

use App\Models\Icon;
use App\Models\IconLib;
use App\Services\Utils\Log as LogUtil;
use Illuminate\Console\Command;
use Google\Cloud\Storage\StorageClient;

class AddGoogleIcon extends Command
{
    use LogUtil;

    /**
     * 命令行的名称及用法
     *
     * @var string
     */
    protected $signature = 'icon:addGoogle
                            {name : the name of the lib}
                            {location : the directory where these icon files are stored}';

    /**
     * 命令行的概述
     *
     * @var string
     */
    protected $description = 'Add a new free google material design icons lib.';

    /**
     * 运行命令
     *
     * @return mixed
     */
    public function handle()
    {
        $name = $this->argument('name');
        $location = $this->argument('location');

        $libId = $this->createIconLib($name);

        $files = $this->getAllSVGFiles($location);

        $bar = $this->output->createProgressBar(count($files));

        foreach ($files as $file) {
            $this->upload($libId, $name, $file, $bar);
        }

        $bar->finish();

        $this->info(" Done.");
    }

    /**
     * 获取目录中全部的 SVG 文件
     *
     * @param $location
     * @return array
     */
    private function getAllSVGFiles($location)
    {
        $dir = opendir($location);
        if (false == $dir) {
            $this->error('Cannot open the directory: ' . $location);
        }

        $files = [];
        while (false !== ($entry = readdir($dir))) {
            if ($entry == '.' || $entry == '..') {
                continue;
            }

            $fullPath = $location . DIRECTORY_SEPARATOR . $entry;

            if (is_dir($fullPath)) {
                $files = array_merge($files, $this->getAllSVGFiles($fullPath));
            }

            $pieces = pathinfo($fullPath);

            if (!isset($pieces['extension']) || strtolower($pieces['extension']) !== 'svg') {
                $this->info('Skip: ' . $fullPath . ' is not a valid svg file.');
                continue;
            }

            $files[] = $fullPath;
        }

        closedir($dir);

        return $files;
    }

    /**
     * 上传 svg 文件
     *
     * @param int $libId
     * @param string $name
     * @param string $path
     * @param mixed $bar
     * @return string
     */
    private function upload($libId, $name, $path, $bar)
    {
        $config = [
            'keyFilePath' => config('app.google_cloud_storage_credential_file')
        ];

        $storageClient = new StorageClient($config);
        $bucket = $storageClient->bucket(config('app.google_cloud_storage_bucket_name'));

        $basename = preg_replace('/_\d+px/', '', substr(basename($path), 3));

        $options = [
            'name'     => implode(DIRECTORY_SEPARATOR, ['icons', str_replace(' ', '-', $name), $basename]),
            'metadata' => [
                'contentType' => 'image/svg+xml'
            ]
        ];

        $bar->advance();

        try {
            $object = $bucket->upload(file_get_contents($path), $options);
            $info = $object->info();

            if (!isset($info['name'])) {
                throw new \Exception('上传文件出错');
            }

            $url = 'https://storage.googleapis.com/' . $info['bucket'] . '/' . $info['name'];
            $this->info(' Upload file success: ' . $url);

            $this->saveIcon($libId, $path, $url);
        } catch (\Exception $e) {
            static::log($e);
            $this->error(' Upload file failed: ' . $path);
        }
    }

    /**
     * 创建 icon lib
     *
     * @param string $name
     * @return int $id
     */
    private function createIconLib($name)
    {
        $lib = IconLib::where('name', $name)->where('accountId', 0)->where('accountType',
            IconLib::ACCOUNT_TYPE_PERSONAL)->where('status', IconLib::ACCOUNT_TYPE_PERSONAL)->first();

        if (is_null($lib)) {
            $lib = new IconLib();

            $lib->name = $name;
            $lib->accountId = 0;
            $lib->accountType = IconLib::ACCOUNT_TYPE_PERSONAL;
            $lib->status = IconLib::STATUS_NORMAL;
            $lib->createdBy = 0;
            $lib->createdAt = $lib->updatedAt = date('Y-m-d H:i:s');

            $lib->save();
        }

        return $lib->id;
    }

    /**
     * 保存 icon
     *
     * @param $libId
     * @param $path
     * @param $url
     */
    public function saveIcon($libId, $path, $url)
    {
        $name = pathinfo($path)['filename'];
        $name = preg_replace('/_\d+px/', '', substr($name, 3));

        $icon = Icon::where('iconLibId', $libId)->where('status', Icon::STATUS_NORMAL)->where('name', $name)->first();

        if (is_null($icon)) {
            $icon = new Icon();

            $icon->name = $name;
            $icon->tags = '[]';
            $icon->path = $url;
            $icon->iconLibId = $libId;
            $icon->status = Icon::STATUS_NORMAL;
            $icon->createdBy = 0;
            $icon->createdAt = $icon->updatedAt = date('Y-m-d H:i:s');

            $icon->save();
        }
    }
}
