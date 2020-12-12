<?php

namespace Core;

abstract class StandardController extends AbstractController
{

    private $views = [];
    private $breadcrumb;

    public function __construct()
    {
        $this->breadcrumb = [['title' => t('Core.mainPage'), 'url' => '/']];
    }

    public function postAction()
    {
        require __DIR__.'/../Common/Views/template.php';
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
        $debugType=getDumpDebugType();
        setDumpDebugType('html', true);
        ob_start();
        try {
            require __DIR__.'/../'.$module.'/Views/'.$name.'.php';
            $this->views[$group][] = ob_get_contents();
        } finally {
            setDumpDebugType($debugType, false);
        }
        ob_end_clean();
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
                echo '<li><a href="'.htmlspecialchars($crumb['url']).'">'.htmlspecialchars($crumb['title']).'</a></li>';
            else
                echo '<li><span>'.htmlspecialchars($crumb['title']).'</span></li>';
        }
        echo '</ul>';
    }

    protected function pushBreadcrumb($crumb)
    {
        $this->breadcrumb[] = $crumb;
    }
}
