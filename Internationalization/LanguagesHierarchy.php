<?php


namespace Core\Internationalization;


class LanguagesHierarchy
{
    public $langs = [];

    public function pickBest(array $available)
    {
        foreach ($this->langs as $lang) {
            foreach ($available as $x) {
                if ($lang == $x) return $x;
            }
            foreach ($available as $x) {
                if (strpos($x, $lang) === 0) return $x;
            }
        }
        return $available[0];
    }
}