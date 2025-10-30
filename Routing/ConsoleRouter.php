<?php


namespace Core\Routing;


class ConsoleRouter extends Router
{

    var $controllerType = 'Console';

    protected function sendBackSuccess()
    {
        global $debugArray;
        echo json_encode(['data' => $this->returned, 'error' => null, 'debug' => $debugArray], JSON_PARTIAL_OUTPUT_ON_ERROR);
        $debugArray = [];
    }

    protected function sendBackException($ex)
    {
        global $debugArray;
        echo json_encode(['error' => $this->exceptionToArray($ex, true), 'debug' => $debugArray], JSON_PARTIAL_OUTPUT_ON_ERROR);
        $debugArray = [];
    }

    protected function findController()
    {
        $this->prepareController();
        $this->prepareMethod();
    }
}
