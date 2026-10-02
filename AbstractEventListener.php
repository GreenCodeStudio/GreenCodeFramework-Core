<?php
namespace Core;
use DateTime;

abstract class AbstractEventListener
{
    public ?object $user;
    public DateTime $created;
}
