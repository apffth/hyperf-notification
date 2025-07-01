<?php

declare(strict_types=1);

namespace Apffth\Hyperf\Notification\Messages;

class MailMessage
{
    public $subject;

    public $greeting;

    public $introLines = [];

    public $outroLines = [];

    public $actionText;

    public $actionUrl;

    public $level = 'info';

    public $salutation;

    public function subject($subject)
    {
        $this->subject = $subject;
        return $this;
    }

    public function greeting($greeting)
    {
        $this->greeting = $greeting;
        return $this;
    }

    public function line($line)
    {
        $this->introLines[] = $line;
        return $this;
    }

    public function action($text, $url)
    {
        $this->actionText = $text;
        $this->actionUrl  = $url;
        return $this;
    }

    public function level($level)
    {
        $this->level = $level;
        return $this;
    }

    public function salutation($salutation)
    {
        $this->salutation = $salutation;
        return $this;
    }

    public function error()
    {
        return $this->level('error');
    }

    public function success()
    {
        return $this->level('success');
    }

    public function warning()
    {
        return $this->level('warning');
    }

    public function info()
    {
        return $this->level('info');
    }
}
