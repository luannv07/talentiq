<?php
/**
 * Smart Recruitment — Plugin tuyển dụng thông minh
 * Mô tả: Hệ thống tích hợp AI (Google Gemini) để phân tích CV, quản lý tin tuyển dụng và hồ sơ ứng viên.
 * Version: 3.0.0
 * Author: luannv
 * License: GPL2
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'SR_VERSION', '3.0.0' );
define( 'SR_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SR_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

if ( file_exists( SR_PLUGIN_DIR . 'vendor/autoload.php' ) ) {
    require_once SR_PLUGIN_DIR . 'vendor/autoload.php';
}

require_once SR_PLUGIN_DIR . 'includes/class-activator.php';
require_once SR_PLUGIN_DIR . 'includes/class-uploader.php';
require_once SR_PLUGIN_DIR . 'includes/class-gemini.php';
require_once SR_PLUGIN_DIR . 'includes/class-mailer.php';
require_once SR_PLUGIN_DIR . 'admin/class-admin.php';
require_once SR_PLUGIN_DIR . 'public/class-public.php';

register_activation_hook( __FILE__, array( 'SR_Activator', 'activate' ) );

add_action( 'plugins_loaded', function() {
    SR_Activator::migrate();
    new SR_Admin();
    new SR_Public();
});
