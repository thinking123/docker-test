<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class AddIcon extends Command
{
    /**
     * 命令行的名称及用法
     *
     * @var string
     */
    protected $signature = 'icon:add
                            {name : the name of the lib}
                            {location : the directory where these icon files are stored}';

    /**
     * 命令行的概述
     *
     * @var string
     */
    protected $description = 'Add a new free icons lib.';

    /**
     * 运行命令
     *
     * @return mixed
     */
    public function handle()
    {
        $name = $this->argument('name');
        $location = $this->argument('location');

        $dir = opendir($location);
        if (false == $dir) {
            $this->error('Cannot open the directory: ' . $location);
        }
    }
}
