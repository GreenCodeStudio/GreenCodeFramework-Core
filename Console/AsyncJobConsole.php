<?php

namespace Core\Console;

use MKrawczyk\FunQuery\FunQuery;
use PipelineInput;

class AsyncJobConsole extends \Core\AbstractController
{
    public function run(#[PipelineInput] $job)
    {
        dump($job);
        if (is_object($job)) {
            $job = $job->name;
        }
        [$controller, $action] = explode('::', $job);
        \Core\Routing\Router::routeAsyncJob($controller, $action, []);
    }

    public function get()
    {

        $jobs = (new \Core\Routing\AsyncJobRouter())->findScheduleJobs($argv[1] ?? null);

        return FunQuery::create($jobs)->map(fn($job) => (object)['name' => $job->controller . '::' . $job->method])->toArray();
    }
}
