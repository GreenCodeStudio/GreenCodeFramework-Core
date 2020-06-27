<?php


namespace Core\Routing;


use Authorization\Authorization;
use Authorization\Exceptions\NoPermissionException;
use Authorization\Exceptions\UnauthorizedException;
use Core\Exceptions\NotFoundException;

class StandardRouter extends Router
{
    protected function findController()
    {
        $this->parseUrl();
        $this->prepareController();
        $this->prepareMethod();
    }

    public function parseUrl()
    {
        $exploded = explode('/', explode('?', $this->url)[0]);
        $controllerName = empty($exploded[1]) ? 'Start' : $exploded[1];
        $methodName = empty($exploded[2]) ? 'index' : $exploded[2];
        $this->args = array_slice($exploded, 3);
        $this->controllerName = preg_replace('/[^a-zA-Z0-9_]/', '', $controllerName);
        $this->methodName = preg_replace('/[^a-zA-Z0-9_]/', '', $methodName);
    }

    private function prepareController()
    {
        if ($_ENV['cached_code']) {
            // $controllerClassName = static::findControllerCached($controllerName, $type);
        } else {
            $this->controllerClassName = $this->findControllerClass('Controllers');
        }
        $this->controller = new $this->controllerClassName();
        if (!$this->controller->hasPermission($this->methodName)) {
            if (Authorization::isLogged()) {
                throw new NoPermissionException();
            } else {
                throw new UnauthorizedException();
            }
        }
        $this->controller->preAction();
    }

    private function prepareMethod()
    {
        if (!method_exists($this->controller, $this->methodName)) {
            throw new NotFoundException();
        }

        $this->controller->initInfo->controllerName = $this->controllerName;
        $this->controller->initInfo->methodName = $this->methodName;
        $this->controller->initInfo->methodArguments = $this->args;
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

        $this->prepareErrorController($ex,$responseCode);
        echo $this->htmlResult;
    }

    protected function prepareErrorController($ex,$responseCode)
    {
        $this->controllerName = 'Error';
        $this->methodName = 'index';
        $this->prepareController();
        $this->prepareMethod();
        $this->controller->initInfo->error = static::exceptionToArray($ex);
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