<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class SR_Activator {

    public static function activate() {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();

        // Bảng vị trí tuyển dụng
        $sql1 = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}sr_jobs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            description LONGTEXT,
            requirements LONGTEXT,
            salary VARCHAR(100),
            location VARCHAR(100),
            type VARCHAR(50) DEFAULT 'full-time',
            status VARCHAR(20) DEFAULT 'active',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) $charset;";

        // Bảng ứng viên
        $sql2 = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}sr_applications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            job_id INT NOT NULL,
            full_name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL,
            phone VARCHAR(20),
            experience VARCHAR(50),
            cover_letter TEXT,
            cv_path VARCHAR(500),
            status VARCHAR(20) DEFAULT 'submitted',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) $charset;";

        // Bảng kết quả AI
        $sql3 = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}sr_ai_results (
            id INT AUTO_INCREMENT PRIMARY KEY,
            application_id INT NOT NULL,
            score INT DEFAULT 0,
            strengths TEXT,
            weaknesses TEXT,
            recommendation TEXT,
            analyzed_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) $charset;";

        // Bảng cài đặt
        $sql4 = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}sr_settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(100) UNIQUE,
            setting_value TEXT
        ) $charset;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql1 );
        dbDelta( $sql2 );
        dbDelta( $sql3 );
        dbDelta( $sql4 );
    }

    public static function migrate() {
        global $wpdb;
        $app_cols = $wpdb->get_col( "DESCRIBE {$wpdb->prefix}sr_applications" );
        if ( ! in_array( 'email_sent', $app_cols ) ) {
            $wpdb->query( "ALTER TABLE {$wpdb->prefix}sr_applications ADD COLUMN email_sent TINYINT(1) DEFAULT 0" );
        }
        $job_cols = $wpdb->get_col( "DESCRIBE {$wpdb->prefix}sr_jobs" );
        if ( ! in_array( 'quantity', $job_cols ) ) {
            $wpdb->query( "ALTER TABLE {$wpdb->prefix}sr_jobs ADD COLUMN quantity INT DEFAULT 1" );
        }
        if ( ! in_array( 'skills', $job_cols ) ) {
            $wpdb->query( "ALTER TABLE {$wpdb->prefix}sr_jobs ADD COLUMN skills TEXT" );
        }
    }
}