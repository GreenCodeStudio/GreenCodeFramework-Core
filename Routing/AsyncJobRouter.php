<?php


namespace Core\Routing;


class AsyncJobRouter extends Router
{
    var $controllerType = 'AsyncJobs';

    public function findScheduleJobs()
    {
        $controllers = $this->listControllers();
        $ret = [];
        foreach ($controllers as $controller) {
            foreach ($controller->methods as $method) {
                foreach ($method->annotations as $annotation) {
                    if ($annotation instanceof \ScheduleJobAnnotation)
                        $ret[] = (object)['controller' => $controller->name, 'method' => $method->name];
                }
            }
        }
        return $ret;
    }

    protected function findController()
    {
        $this->prepareController();
        $this->prepareMethod();
    }

    protected function sendBackException(\Throwable $ex)
    {
        error_log($ex);
        Log::Exception($ex);
        global $debugArray;
        echo json_encode(['data' => null, 'error' => $this->exceptionToArray($ex), 'debug' => $debugArray], JSON_PARTIAL_OUTPUT_ON_ERROR);
    }

    protected function sendBackSuccess()
    {
        global $debugArray;
        echo json_encode(['data' => $this->returned, 'error' => null, 'debug' => $debugArray], JSON_PARTIAL_OUTPUT_ON_ERROR);
    }
}