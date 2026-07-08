<?php
/**
 * Plugin Name: VeryDRM PDF Protection for WordPress
 * Plugin URI: https://verydrm.com/
 * Description: Protect PDF files in WordPress using VeryDRM.
 * Version: 0.1.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: VeryDRM
 * Author URI: https://verydrm.com/
 * License: GPL-2.0-or-later
 * Text Domain: verydrm-pdf-protection
 */

if (!defined('ABSPATH')) {
    exit;
}

define('VERYDRM_VERSION', '0.1.0');
define('VERYDRM_PATH', plugin_dir_path(__FILE__));
define('VERYDRM_URL', plugin_dir_url(__FILE__));

require_once VERYDRM_PATH . 'includes/class-loader.php';

VeryDRM_Loader::run();
