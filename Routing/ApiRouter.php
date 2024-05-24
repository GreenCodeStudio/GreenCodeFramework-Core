<?php


namespace Core\Routing;


use Authorization\Exceptions\UnauthorizedException;
use Core\Exceptions\NotFoundException;
use Core\Repository\IdempodencyKeyRepostory;
use ExternalApplication\Repository\ExternalApplicationRepository;

class ApiRouter extends Router
{
    public function __construct()
    {

        if (!class_exists(ExternalApplicationRepository::class))
            return throw new \Exception('ExternalApplicationRepository not installed');
    }

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
                            $args = [];
                            if (count($template) == count($urlArray)) {
                                $isMatch = true;
                                for ($i = 0; $i < count($template); $i++) {
                                    if (str_starts_with($template[$i], '{') && str_ends_with($template[$i], '}')) {
                                        $args[substr($template[$i], 1, strlen($template[$i]) - 2)] = $urlArray[$i];
                                    } else if ($template[$i] != ($urlArray[$i] ?? '')) {
                                        $isMatch = false;
                                        break;
                                    }
                                }
                                if ($isMatch) {
                                    if (!empty($annotation->requestBody)) {
                                        $body = file_get_contents('php://input');
                                        $body = json_decode($body, false);
                                        if (empty($body))
                                            throw new \Exception('Invalid request body');
                                        $args = [$body, ...$args];
                                    }
                                    $this->controllerName = $controller->name;
                                    $this->methodName = $method->name;
                                    $this->args = $args;
                                    $this->allowNotLogged = $annotation->allowNotLogged;
                                    return;
                                }
                            }
                        }
                    }
                }
            }
        }
        throw new NotFoundException();
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

    protected function runMethod()
    {
        if (!$this->allowNotLogged) {
            $application = $this->getApplication();
            if (empty($application))
                throw new UnauthorizedException();
        }
        parent::runMethod();
    }

    private function getApplication()
    {
        if (!class_exists(ExternalApplicationRepository::class))
            return throw new \Exception('ExternalApplicationRepository not installed');
        return (new ExternalApplicationRepository())->getByToken($this->getBearerToken());
    }

    private function getBearerToken()
    {
        return str_replace('Bearer ', '', $_SERVER['HTTP_AUTHORIZATION']);
    }
}
