<?php
/**
 * Created by PhpStorm.
 * User: matri
 * Date: 30.07.2018
 * Time: 20:02
 */

/**
 * @usage('method'=>true)
 */
class OfflineDataOnlyAnnotation extends \mindplay\annotations\Annotation
{
}

/**
 * @usage('method'=>true)
 */
class OfflineConstantAnnotation extends \mindplay\annotations\Annotation
{
}

/**
 * @usage('method'=>true)
 */
class NoAjaxLoaderAnnotation extends \mindplay\annotations\Annotation
{
}

/**
 * @usage('method'=>true)
 */
class ScheduleJobAnnotation extends \mindplay\annotations\Annotation
{
    public $interval;
}

/**
 * @usage('method'=>true)
 */
class ApiEndpointAnnotation extends \mindplay\annotations\Annotation
{
    public $type;
    public $url;
    public $description = "";
    public $responses = [];
    public $tags = [];
    public $parameters = [];
    public $allowNotLogged = false;
}

/**
 * @usage('method'=>true)
 */
class CanSafeRepeatAnnotation extends \mindplay\annotations\Annotation
{
}