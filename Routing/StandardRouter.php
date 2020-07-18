<?php


namespace Core\Routing;


use Authorization\Exceptions\UnauthorizedException;

class StandardRouter extends Router
{
    var $controllerType = 'Controllers';

    protected function findController()
    {
        $this->parseUrl();
        $this->prepareController();
        $this->prepareMethod();
    }

    protected function sendBackSuccess()
    {
        echo $this->htmlResult;
    }

    protected function sendBackException(\Throwable $ex)
    {
        $responseCode = $this->getHttpCode($ex);
        http_response_code($responseCode);
        $this->logExceptionIfNeeded($ex);
        dump($ex);

        $this->prepareErrorController($ex, $responseCode);
        echo $this->htmlResult;
    }

    protected function prepareErrorController($ex, $responseCode)
    {
        if($ex instanceof UnauthorizedException){
            $this->controllerName = 'Authorization';
            $this->methodName = 'index';
        }else {
            $this->controllerName = 'Error';
            $this->methodName = 'index';
        }
        $this->prepareController();
        $this->prepareMethod();
        $this->controller->initInfo->error = $this->exceptionToArray($ex);
        $this->controller->initInfo->code = $responseCode;
        $this->controller->initInfo->methodArguments = [$responseCode];
        $this->invoke();
    }

    protected function invoke()
    {
        ob_start();
        $this->runMethod();
        $debug = ob_get_contents();
        ob_get_clean();
        if (!empty($debug))
            dump($debug);

        ob_start();
        $this->controller->postAction();
        $this->htmlResult = ob_get_contents();
        ob_get_clean();
    }
}