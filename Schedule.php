<?php
include_once __DIR__.'/MainInit.php';
$argv = $_SERVER['argv'];

$jobs = (new \Core\Routing\AsyncJobRouter())->findScheduleJobs($argv[1]??null);
dump($jobs);
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
        echo($returned);
        var_dump($returnedObj);
    }
}
