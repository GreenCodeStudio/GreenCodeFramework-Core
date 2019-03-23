<?php

namespace Core;

class StandardController extends AbstractController
{

    private $views = [];
    private $breadcrumb = [['title' => 'Strona główna', 'url' => '/']];


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
        $breadcrumb = $this->breadcrumb;
        return end($breadcrumb)['title'];
    }
    protected function addViewString(string $html, string $group = 'main'){
        $this->views[$group][]=$html;
    }
    protected function addView(string $module, string $name, $data = null, string $group = 'main')
    {
        ob_start();
        require __DIR__.'/../'.$module.'/Views/'.$name.'.php';
        $this->views[$group][] = ob_get_contents();
        ob_end_clean();
    }

    protected function showViews(string $group)
    {
        if ($group == 'main' && !empty($this->debugOutput))
            echo '<div class="debugOutput">'.$this->debugOutput.'</div>';
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
            echo '<li><a href="'.htmlspecialchars($crumb['url']).'">'.htmlspecialchars($crumb['title']).'</a></li>';
        }
        echo '</ul>';
    }

    protected function pushBreadcrumb($crumb)
    {
        $this->breadcrumb[] = $crumb;
    }

}
