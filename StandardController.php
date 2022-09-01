<?php

namespace Core;

use DOMDocument;
use MKrawczyk\Mpts\Environment;
use MKrawczyk\Mpts\Parser\XMLParser;

abstract class StandardController extends AbstractController
{

    private $views = [];
    private $breadcrumb;
    protected $headLinks = [];

    public function __construct()
    {
        $this->breadcrumb = [['title' => t('Core.mainPage'), 'url' => '/']];
    }

    public function postAction()
    {
        require __DIR__ . '/../Common/Views/template.php';
    }

    public function getViews()
    {
        return $this->views;
    }

    public function getBreadcrumb()
    {
        return $this->breadcrumb;
    }

    public function getTitle()
    {
        $breadcrumb = array_map(function ($x) {
            return $x['title'];
        }, $this->breadcrumb);
        $breadcrumb[0] = $this->getPageTitle();

        return implode(' - ', array_reverse($breadcrumb));
    }

    abstract public function getPageTitle(): string;

    protected function addViewString(string $html, string $group = 'main')
    {
        $this->views[$group][] = $html;
    }

    protected function addView(string $module, string $name, $data = null, string $group = 'main')
    {
        if (file_exists(__DIR__ . '/../' . $module . '/Views/' . $name . '.php')) {
            $debugType = getDumpDebugType();
            ob_start();
            setDumpDebugType('html', true);
            try {
                require __DIR__ . '/../' . $module . '/Views/' . $name . '.php';
                $this->views[$group][] = ob_get_contents();
            } finally {
                setDumpDebugType($debugType, false);
            }
            ob_end_clean();
        } else if (file_exists(__DIR__ . '/../' . $module . '/Views/' . $name . '.mpts')) {
            $template = XMLParser::Parse(file_get_contents(__DIR__ . '/../' . $module . '/Views/' . $name . '.mpts'));
            $env = new Environment();
            $env->document = new DOMDocument();
            $env->variables = (array)$data;
            $env->variables['dump']= function (...$args) {
                ob_start();
                var_dump(...$args);
                $ret=ob_get_contents();
                ob_end_clean();
                return $ret;
            };
            $env->variables['t']=fn(...$args)=>t(...$args);
            $result = $template->execute($env);
            $this->views[$group][] = $env->document->saveHTML($result);
        } else {
            throw new \Exception("Cannot find $name.php or $name.mpts in $module/Views/");
        }
    }
    protected function insertView(string $module, string $name, $data = null)
    {
        if (file_exists(__DIR__ . '/../' . $module . '/Views/' . $name . '.php')) {
            require __DIR__ . '/../' . $module . '/Views/' . $name . '.php';
        } else if (file_exists(__DIR__ . '/../' . $module . '/Views/' . $name . '.mpts')) {
            $template = XMLParser::Parse(file_get_contents(__DIR__ . '/../' . $module . '/Views/' . $name . '.mpts'));
            $env = new Environment();
            $env->document = new DOMDocument();
            $env->variables = (array)$data;
            $result = $template->execute($env);
            echo $env->document->saveHTML($result);
        } else {
            throw new \Exception("Cannot find $name.php or $name.mpts in $module/Views/");
        }
    }

    protected function showViews(string $group)
    {
        if ($group == 'main') {
            global $debugArray;
            if (!empty($debugArray)) {
                echo '<div class="debugOutput">';
                dump_render_html();
                echo '</div>';
            }
        }
        if ($group == 'main')
            echo '<div class="page">';
        foreach ($this->views[$group] ?? [] as $html) {
            echo $html;
        }
        if ($group == 'main')
            echo '</div>';
    }

    protected function showBreadcrumb()
    {
        echo '<ul>';
        foreach ($this->breadcrumb as $crumb) {
            if (!empty($crumb['url']))
                echo '<li><a href="' . htmlspecialchars($crumb['url']) . '">' . htmlspecialchars($crumb['title']) . '</a></li>';
            else
                echo '<li><span>' . htmlspecialchars($crumb['title']) . '</span></li>';
        }
        echo '</ul>';
    }

    protected function pushBreadcrumb($crumb)
    {
        $this->breadcrumb[] = $crumb;
    }
}
