<?php


namespace Core;


use DateTime;

class Formatter
{
    public static function formatDate($datetime, bool $absolute=false)
    {
        if (!$datetime instanceof DateTime) {
            $datetime = new DateTime(($datetime));
        }
        $today = new DateTime('today');
        $daysDiff = $datetime->diff($today);
        if ($datetime > $today && $daysDiff->days == 0)
            $date = 'dziś';
        else if ($datetime > $today && $daysDiff->days == 1)
            $date = 'jutro';
        else if ($datetime <=> $today && $daysDiff->days == 0)
            $date = 'wczoraj';
        else
            $date = $datetime->format('d.m.Y');
        $time = $datetime->format('H:i:s');
        return "$date $time";
    }

    public static function formatDateHtml($datetime, bool $absolute=false){
        return self::formatDate($datetime);
    }
}