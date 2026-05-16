<?php
/**
 * TalentIQ Child Theme — Functions
 * Kế thừa styles/scripts từ plugin smart-recruitment, không tự viết lại CSS/JS
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Tải is_plugin_active() — hàm này mặc định chỉ có trong admin context
if ( ! function_exists( 'is_plugin_active' ) ) {
    include_once ABSPATH . 'wp-admin/includes/plugin.php';
}

/**
 * Enqueue styles và scripts
 * Theme chỉ kế thừa assets từ plugin, không định nghĩa lại
 */
add_action( 'wp_enqueue_scripts', function() {
    // Style của parent theme twentytwentyfive
    wp_enqueue_style(
        'parent-style',
        get_template_directory_uri() . '/style.css'
    );

    if ( ! is_plugin_active( 'smart-recruitment/smart-recruitment.php' ) ) {
        return;
    }

    // Lấy version từ plugin để cache-busting chính xác
    $plugin_file = WP_PLUGIN_DIR . '/smart-recruitment/smart-recruitment.php';
    $plugin_data = get_plugin_data( $plugin_file, false, false );
    $plugin_ver  = $plugin_data['Version'] ?? '1.0.0';

    // Kế thừa CSS public của plugin — không enqueue trùng, dependency rõ ràng
    wp_enqueue_style(
        'sr-public',
        plugins_url( 'smart-recruitment/public/assets/public.css' ),
        array( 'parent-style' ),
        $plugin_ver
    );

    // Kế thừa JS public của plugin — load cuối trang, phụ thuộc jQuery
    wp_enqueue_script(
        'sr-public',
        plugins_url( 'smart-recruitment/public/assets/public.js' ),
        array( 'jquery' ),
        $plugin_ver,
        true
    );
} );

/**
 * Hiện admin notice nếu plugin chưa được kích hoạt
 */
add_action( 'admin_notices', function() {
    if ( ! is_plugin_active( 'smart-recruitment/smart-recruitment.php' ) ) {
        echo '<div class="notice notice-warning is-dismissible"><p>'
            . '<strong>TalentIQ:</strong> TalentIQ plugin chưa được kích hoạt. '
            . 'Vui lòng kích hoạt plugin <em>Smart Recruitment</em> để sử dụng đầy đủ tính năng.'
            . '</p></div>';
    }
} );

/**
 * Đăng ký custom page template "Trang tuyển dụng TalentIQ"
 * Ánh xạ tới file templates/recruitment.html (Gutenberg FSE block template)
 */
add_filter( 'theme_page_templates', function( $templates ) {
    $templates['templates/recruitment.html'] = 'Trang tuyển dụng TalentIQ';
    return $templates;
} );

/**
 * Thêm body class tiq-recruitment-page khi trang dùng template tuyển dụng
 */
add_filter( 'body_class', function( $classes ) {
    if ( is_singular( 'page' ) ) {
        $template = get_post_meta( get_the_ID(), '_wp_page_template', true );
        if ( 'templates/recruitment.html' === $template ) {
            $classes[] = 'tiq-recruitment-page';
        }
    }
    return $classes;
} );
