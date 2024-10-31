<?php

namespace Core;

use PHPMailer\PHPMailer\PHPMailer;

class MailSender extends PHPMailer
{
    public function __construct()
    {
        parent::__construct();
        $this->IsSMTP();
        $this->Mailer = "smtp";
        $this->Encoding = 'base64';
        $this->CharSet = 'UTF-8';
        $this->SMTPAuth = TRUE;
        $this->SMTPSecure = $_ENV['smtp_secure'];
        $this->Port = $_ENV['smtp_port'];
        $this->Host = $_ENV['smtp_host'];
        $this->Username = $_ENV['smtp_username'];
        $this->Password =$_ENV['smtp_password'];
        $this->SetFrom($_ENV['smtp_from'], "Kontakt");
    }
}
