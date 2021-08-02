<?php


namespace Core\Routing;


class ApiRouter extends Router
{

    var $controllerType = 'Api';

    protected function findController()
    {
        $this->parseUrl();
        $this->prepareController();
        $this->prepareMethod();
    }

    public function parseUrl()
    {
        $list = $this->listControllers();
        $url = substr($this->url, 5);
        foreach ($list as $controller) {
            foreach ($controller->methods as $method) {
                foreach ($method->annotations as $annotation) {
                    if ($annotation instanceof \ApiEndpointAnnotation) {
                        if (strtoupper($annotation->type) === $_SERVER['REQUEST_METHOD']) {
                            $template = explode('/', trim($annotation->url, ' /'));
                            $urlArray = explode('/', trim($url, ' /'));
                            if (count($template) == count($urlArray)) {
                                $isMatch = true;
                                for ($i = 0; $i < count($template); $i++) {
                                    if ($template[$i] != ($urlArray[$i] ?? '')) {
                                        $isMatch = false;
                                        break;
                                    }
                                }
                                if ($isMatch) {
                                    $this->controllerName = $controller->name;
                                    $this->methodName = $method->name;
                                    $this->args = [];
                                    return;
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    protected function sendBackSuccess()
    {
        header('Content-Type: application/json');
        echo json_encode($this->returned, JSON_PARTIAL_OUTPUT_ON_ERROR);
    }

    protected function sendBackException($ex)
    {
        header('Content-Type: application/json');
        $responseCode = $this->getHttpCode($ex);
        http_response_code($responseCode);
        $this->logExceptionIfNeeded($ex);
        echo json_encode($this->exceptionToArray($ex), JSON_PARTIAL_OUTPUT_ON_ERROR);
    }
}