<?php

namespace Sprint\Migration;


use Sprint\Migration\Exceptions\RestartException;

abstract class abstractexchange
{
    protected $service;
    protected $params = [];

    use OutTrait {
        out as protected;
        outIf as protected;
        outProgress as protected;
        outNotice as protected;
        outNoticeIf as protected;
        outInfo as protected;
        outInfoIf as protected;
        outSuccess as protected;
        outSuccessIf as protected;
        outWarning as protected;
        outWarningIf as protected;
        outError as protected;
        outErrorIf as protected;
        outDiff as protected;
        outDiffIf as protected;
    }

    public function __construct(RestartableService $service)
    {
        $this->service = $service;
        $this->params = $service->getRestartParams();
    }

    /**
     * @throws RestartException
     */
    protected function restart()
    {
        $this->service->setRestartParams($this->params);
        $this->service->restart();
    }

    /**
     * @return HelperManager
     */
    protected function getHelperManager()
    {
        return HelperManager::getInstance();
    }
}