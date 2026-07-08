<?php

if (!defined('ABSPATH')) {
    exit;
}

class VeryDRM_Loader
{
    public static function run()
    {
        require_once VERYDRM_PATH . 'admin/class-admin.php';

        new VeryDRM_Admin();
    }
}
