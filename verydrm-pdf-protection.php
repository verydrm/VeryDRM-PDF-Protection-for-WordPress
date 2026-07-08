<?php
/**
 * Plugin Name: VeryDRM PDF Protection for WordPress
 * Plugin URI: https://verydrm.com/
 * Description: Protect PDF files in WordPress using VeryDRM DRM technology.
 * Version: 0.1.0
 * Author: VeryDRM
 * Author URI: https://verydrm.com/
 * License: GPL-2.0-or-later
 * Text Domain: verydrm-pdf-protection
 */

if (!defined('ABSPATH')) {
    exit;
}

define('VERYDRM_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('VERYDRM_PLUGIN_URL', plugin_dir_url(__FILE__));

require_once VERYDRM_PLUGIN_PATH . 'includes/class-api-demo.php';

add_action('admin_menu', function () {

    add_menu_page(
        'VeryDRM',
        'VeryDRM',
        'manage_options',
        'verydrm',
        'verydrm_admin_page',
        'dashicons-lock'
    );

});

function verydrm_admin_page()
{
?>
<div class="wrap">

<h1>VeryDRM PDF Protection for WordPress</h1>

<p>Welcome to VeryDRM.</p>

<p>This plugin is currently under development.</p>

<p>
Future versions will support:

<ul>

<li>PDF DRM Protection</li>

<li>Dynamic Watermark</li>

<li>WooCommerce</li>

<li>Paid Memberships Pro</li>

<li>LearnDash</li>

</ul>

</p>

</div>

<?php
}
