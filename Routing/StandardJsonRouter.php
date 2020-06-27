<?php


namespace Core\Routing;


class StandardJsonRouter extends StandardRouter
{
    protected function sendBackSuccess()
    {
        echo json_encode([
            'views' => $this->controller->getViews(),
            'breadcrumb' => $this->controller->getBreadcrumb(),
            'title' => $this->controller->getTitle(),
            'debug' => $this->controller->debugOutput,
            'data' => $this->controller->initInfo,
            'error' => null
        ], JSON_PARTIAL_OUTPUT_ON_ERROR);

    }

    protected function sendBackException(\Throwable $ex)
    {
        $responseCode = $this->getHttpCode($ex);
        http_response_code($responseCode);
        $this->logExceptionIfNeeded($ex);
        dump($ex);

        $this->prepareErrorController($ex,$responseCode);
        echo json_encode(['views' => $this->controller->getViews(), 'debug' => '', 'error' => static::exceptionToArray($ex)], JSON_PARTIAL_OUTPUT_ON_ERROR);
    }
    protected function invoke()
    {
        ob_start();
        $this->runMethod();
        $debug = ob_get_contents();
        ob_get_clean();
        if (!empty($debug))
            dump($debug);
    }
}