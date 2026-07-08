<?php

if (!defined('ABSPATH')) {
    exit;
}

class VeryDRM_Admin
{
    public function __construct()
    {
        add_action('admin_menu', [$this, 'menu']);
    }

    public function menu()
    {
        add_menu_page(
            'VeryDRM',
            'VeryDRM',
            'manage_options',
            'verydrm',
            [$this, 'dashboard'],
            'dashicons-lock'
        );
    }

    public function dashboard()
    {
        include VERYDRM_PATH . 'admin/views/dashboard.php';
    }
}
