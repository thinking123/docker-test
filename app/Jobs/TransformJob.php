<?php

namespace App\Jobs;

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
     * layer 与 component 转换 job
     *
     * @param int $layerId
     * @param int $jobId
     * @return void
     */
    public function __construct($layerId, $jobId)
    {
        $this->layerId = $layerId;
        $this->jobId = $jobId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {

    }
}
