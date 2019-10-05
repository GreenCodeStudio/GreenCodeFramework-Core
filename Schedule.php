<?php
error_reporting(E_ALL);
ini_set("log_errors", 1);
ini_set("error_log", __dir__."/../../tmp/php-error.log");
spl_autoload_register(function ($class_name) {
    $path = __DIR__.'/../'.str_replace("\\", "/", $class_name).'.php';
    if (file_exists($path))
        include_once $path;
});
include_once __DIR__.'/../../vendor/autoload.php';
include_once __DIR__.'/Debug.php';
$jobs = \Core\Router::findScheduleJobs();
foreach ($jobs as $job) {
    $descriptorspec = array(
        0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
        1 => array("pipe", "w"),  // stdout is a pipe that the child will write to
        2 => array("file", "./tmp/error-output.txt", "a") // stderr is a file to write to
    );
    $process = proc_open ("php ./modules/Core/AsyncJob.php", $descriptorspec, $pipes);

    if (is_resource($process)) {
        fwrite($pipes[0], json_encode(['controller'=>$job->controller, 'action'=>$job->method, 'args'=>[]]));
        fclose($pipes[0]);
        $returned= stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        $return_value = proc_close($process);
        $returnedObj=json_decode($returned);
        var_dump($returned);
        var_dump($returnedObj);
    }
}