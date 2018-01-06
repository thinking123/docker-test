<?php

namespace App\Jobs;

use Redis;
use App\Models\Layer;
use App\Models\Component;

class TransformJob extends Job
{
    /**
     * @var int
     */
    private $layerId;

    /**
     * @var int
     */
    private $jobId;

    /**
     * @var int
     */
    private $sponsor;


    /**
     * layer 与 component 转换 job
     *
     * @param int $layerId
     * @param int $jobId
     * @param int $sponsor
     * @return void
     */
    public function __construct($layerId, $jobId, $sponsor)
    {
        $this->layerId = $layerId;
        $this->jobId = $jobId;
        $this->sponsor = $sponsor;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Redis::set('job:' . $this->jobId, 'PENDING', 'EX', 3600);

        $layer = Layer::where('id', $this->layerId)->where('status', Layer::STATUS_NORMAL)->first();

        if (is_null($layer)) {
            Redis::set('job:' . $this->jobId, 'FAILED', 'EX', 3600);
            return;
        }

        if ($layer->type === Layer::getTypeIdByName('slot')) {
            $this->componentToLayer($layer);
        } else {
            $this->layerToComponent($layer);
        }
    }

    /**
     * 组件转换为普通 Layer
     *
     * @param Object $layer
     */
    public function componentToLayer($layer)
    {
        if (Layer::slotToGeneral($layer, $layer->fileId)) {
            Redis::set('job:' . $this->jobId, 'SUCCESS', 'EX', 3600);
            return;
        }

        Redis::set('job:' . $this->jobId, 'FAILED', 'EX', 3600);
    }

    /**
     * 普通 Layer 转换为组件
     *
     * @param Object $layer
     */
    public function layerToComponent($layer)
    {
        $name = 'Untitled';

        try {
            $component = Component::createComponent($name, $this->sponsor, null, Component::ACCESS_PRIVATE);

            if (is_null($component)) {
                throw new \Exception('Transform slot layer ' . $this->layerId . ' failed');
            }
        } catch (\Exception $e) {
            Redis::set('job:' . $this->jobId, 'FAILED', 'EX', 3600);
            return;
        }

        if (Layer::layerToComponent($layer, $component)) {
            Redis::set('job:' . $this->jobId, 'SUCCESS', 'EX', 3600);
        }

        Redis::set('job:' . $this->jobId, 'FAILED', 'EX', 3600);
    }
}
