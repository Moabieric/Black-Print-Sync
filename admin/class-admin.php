<?php

namespace BlackPrint\Commerce;

defined('ABSPATH') || exit;

class Admin
{
    public function __construct()
    {
        new Menu();
        new Dashboard();
    }
}