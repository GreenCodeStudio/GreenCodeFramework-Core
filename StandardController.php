<?php


namespace Core;

class StandardController extends AbstractController {
private $views=[];    

    protected function addView(string $module, string $name, $data=null,string $group='main'){
        ob_start();
        require __DIR__.'/../'.$module.'/Views/'.$name.'.php';
        $this->views[$group][]= ob_get_contents();
        ob_end_clean();
    }
    protected function showViews(string $group){
       foreach($this->views[$group]??[] as $html){
           echo $html;
       } 
    }
    public function postAction(){
        require __DIR__.'/../Common/Views/template.php';
    }
}
