<?php


namespace Core\Internationalization;


use MKrawczyk\FunQuery\FunQuery;

class LanguagesHierarchy
{
    public $langs = [];

    public function __construct(array $langs = [])
    {
        $this->langs = $langs;
    }

    public static function ReadFromUser(): self
    {
        $httpHeader = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? 'en';
        $splitted = FunQuery::create(explode(',', $httpHeader))->map(fn($x) => explode(';', $x)[0])->toArray();
        if (!empty($_COOKIE['lang'])) {
            $splitted = [$_COOKIE['lang'], ...$splitted];
        }
        if (!empty($_GET['lang'])) {
            $splitted = [$_GET['lang'], ...$splitted];
            setcookie('lang', $_GET['lang'], [
                'samesite' => 'None',
                'secure' => true,
                'expires' => time() + 1000000000]);
        }

        return new LanguagesHierarchy($splitted);
    }

    public function pickBest(array $available)
    {
        foreach ($this->langs as $lang) {
            if (empty($lang)) {
                continue;
            }
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