<?php

namespace Apffth\Hyperf\Notification\Messages;

class DatabaseMessage
{
    public $data = [];

    public function data($data)
    {
        $this->data = array_merge($this->data, $data);
        return $this;
    }

    public function toArray()
    {
        return $this->data;
    }
}
