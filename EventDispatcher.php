<?php

namespace Core;

use Core\AbstractEventListener;
use ReflectionMethod;

class EventDispatcher
{
    public static function dispatch(string $module, string $eventName, ...$args)
    {
        $listeners = self::listListeners($module);

        foreach ($listeners as $listenerClassName) {
            try {
                $listener = new $listenerClassName();
                if ($listener instanceof AbstractEventListener) {
                    $listener->created = new \DateTime();
                    if (class_exists("Authorization\Authorization")) {
                        $listener->user = \Authorization\Authorization::getUserInfo();
                    }
                }
                if (method_exists($listener, $eventName)) {
                    $reflectionMethodData = new ReflectionMethod($listenerClassName, $eventName);
                    $reflectionMethodData->invoke($listener, ...$args);
                }
            } catch (\Throwable $exception) {
                dump($exception);
            }
        }
    }

    private static function listListeners(string $sourceModule)
    {
        $ret = [];
        $modules = scandir(__DIR__.'/../');
        foreach ($modules as $targetModule) {
            if ($targetModule == '.' || $targetModule == '..') {
                continue;
            }
            $path = __DIR__.'/../'.$targetModule.'/EventListeners/'.$sourceModule.'EventListener.php';
            if (is_file($path)) {
                $ret[] = "\\$targetModule\\EventListeners\\{$sourceModule}EventListener";
            }
        }
        return $ret;
    }
}
