<?php

namespace Kuberdock\classes\models;

class Version
{
    public function get()
    {
        $version = shell_exec('rpm -q kuberdock-plugin');
        return preg_replace('/kuberdock-plugin-/i', '', $version);
    }
}