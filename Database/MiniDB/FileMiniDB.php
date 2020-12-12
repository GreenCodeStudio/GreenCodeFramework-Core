<?php

namespace Core\Database\MiniDB;
/**
 * Class FileMiniDB
 * Fallback in case of redis not available
 * @package Core\Database\MiniDB
 */
class FileMiniDB
{
    public function setEx($name, $content)
    {
        $this->set($name, $content);
    }

    public function set($name, $content)
    {
        $path = $this->path($name);
        return file_put_contents($path, $content);
    }

    private function path($name)
    {
        if (!is_dir(__DIR__."/../../../../tmp/miniDB"))
            mkdir(__DIR__."/../../../../tmp/miniDB", 0777, true);
        return __DIR__."/../../../../tmp/miniDB/".$this->safeName($name);
    }

    private function safeName($name)
    {
        $name = str_replace('/', '_', $name);
        $name = str_replace('\\', '_', $name);
        $name = str_replace('.', '_', $name);
        return $name;
    }

    public function del($name)
    {
        $path = $this->path($name);
        if (is_file($path))
            unlink($path);
    }

    public function get($name)
    {
        $path = $this->path($name);
        if (file_exists($path)) {
            return file_get_contents($path);
        } else {
            return null;
        }
    }

    public function expire()
    {

    }
}