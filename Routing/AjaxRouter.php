<?php


namespace Core\Routing;


class AjaxRouter extends Router
{
    var $controllerType = 'Ajax';

    protected function findController()
    {
        $this->parseUrl();
        $this->parseParams();
        $this->prepareController();
        $this->prepareMethod();
    }

    public function parseUrl()
    {
        $exploded = explode('/', explode('?', $this->url)[0]);
        $controllerName = empty($exploded[2]) ? 'Start' : $exploded[2];
        $methodName = empty($exploded[3]) ? 'index' : $exploded[3];
        $this->controllerName = preg_replace('/[^a-zA-Z0-9_]/', '', $controllerName);
        $this->methodName = preg_replace('/[^a-zA-Z0-9_]/', '', $methodName);
    }

    protected function parseParams()
    {
        $args = [];
        foreach ($_POST['args'] ?? [] as $key => $arg) {
            $args[$key] = json_decode($arg, false);
        }
        foreach (self::getFileArgs() as $key => $arg) {
            $args[$key] = $arg;
        }
        ksort($args);
        $this->args = $args;
    }

    protected static function getFileArgs()
    {
        if (empty($_FILES['args']))
            return [];
        $ret = [];
        $keys = array_keys($_FILES['args']['tmp_name']);
        foreach ($keys as $key) {
            $ret[$key] = [
                'type' => $_FILES['args']['type'][$key],
                'tmp_name' => $_FILES['args']['tmp_name'][$key],
                'size' => $_FILES['args']['size'][$key],
                'name' => $_FILES['args']['name'][$key],
                'error' => $_FILES['args']['error'][$key]
            ];
        }
        return $ret;
    }

    protected function sendBackSuccess()
    {
        header('Content-Type: application/json');
        global $debugArray;
        $debugEnabled = $_ENV['debug'] == 'true';
        echo json_encode(['data' => $this->returned, 'error' => null, 'debug' => $debugEnabled ? $debugArray : [], 'output' => $debugEnabled ? ($controller->debugOutput ?? '') : ''], JSON_PARTIAL_OUTPUT_ON_ERROR);
        $debugArray = [];
    }

    protected function sendBackException($ex)
    {
        header('Content-Type: application/json');
        $responseCode = $this->getHttpCode($ex);
        http_response_code($responseCode);
        $this->logExceptionIfNeeded($ex);
        dump($ex);

        global $debugArray;
        $debugEnabled = $_ENV['debug'] == 'true';
        echo json_encode(['error' => $this->exceptionToArray($ex), 'debug' => $debugEnabled ? $debugArray : [], 'output' => $debugEnabled ? ($controller->debugOutput ?? '') : ''], JSON_PARTIAL_OUTPUT_ON_ERROR);
        $debugArray = [];
    }
}
