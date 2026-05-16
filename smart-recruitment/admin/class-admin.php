<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class SR_Admin {

    public function __construct() {
        add_action( 'admin_menu',             array( $this, 'add_menu' ) );
        add_action( 'admin_enqueue_scripts',  array( $this, 'enqueue_assets' ) );
        add_action( 'admin_init',             array( $this, 'maybe_export_csv' ) );
        add_action( 'wp_ajax_sr_analyze_cv',        array( $this, 'ajax_analyze_cv' ) );
        add_action( 'wp_ajax_sr_update_status',     array( $this, 'ajax_update_status' ) );
        add_action( 'wp_ajax_sr_toggle_job_status', array( $this, 'ajax_toggle_job_status' ) );
        add_action( 'wp_ajax_sr_generate_jd',       array( $this, 'ajax_generate_jd' ) );
        add_action( 'admin_post_sr_save_settings',  array( $this, 'save_settings' ) );
        add_action( 'admin_post_sr_save_job',       array( $this, 'save_job' ) );
        add_action( 'admin_post_sr_delete_job',     array( $this, 'delete_job' ) );
        add_action( 'wp_ajax_sr_save_job_modal',    array( $this, 'ajax_save_job_modal' ) );
    }

    public function add_menu() {
        add_menu_page(
            'Smart Recruitment',
            'Tuyển Dụng',
            'manage_options',
            'smart-recruitment',
            array( $this, 'page_dashboard' ),
            'dashicons-id-alt',
            30
        );
        add_submenu_page( 'smart-recruitment', 'Dashboard',          'Dashboard',          'manage_options', 'smart-recruitment', array( $this, 'page_dashboard' ) );
        add_submenu_page( 'smart-recruitment', 'Vị trí tuyển dụng', 'Vị trí tuyển dụng', 'manage_options', 'sr-jobs',           array( $this, 'page_jobs' ) );
        add_submenu_page( 'smart-recruitment', 'Ứng viên',          'Ứng viên',           'manage_options', 'sr-applications',   array( $this, 'page_applications' ) );
        add_submenu_page( 'smart-recruitment', 'Cài đặt',           'Cài đặt',            'manage_options', 'sr-settings',       array( $this, 'page_settings' ) );
    }

    public function enqueue_assets( $hook ) {
        if ( strpos( $hook, 'smart-recruitment' ) === false && strpos( $hook, 'sr-' ) === false ) return;

        wp_enqueue_style( 'sr-bootstrap', SR_PLUGIN_URL . 'assets/bootstrap/css/bootstrap.min.css', array(), '5.3' );
        wp_enqueue_style( 'sr-fa', SR_PLUGIN_URL . 'assets/fontawesome/css/all.min.css', array(), '6.5' );
        wp_enqueue_style( 'sr-admin', SR_PLUGIN_URL . 'admin/assets/admin.css', array( 'sr-bootstrap', 'sr-fa' ), SR_VERSION );

        wp_enqueue_script( 'sr-bootstrap', SR_PLUGIN_URL . 'assets/bootstrap/js/bootstrap.bundle.min.js', array( 'jquery' ), '5.3', true );
        wp_enqueue_script( 'sr-chartjs',   SR_PLUGIN_URL . 'admin/assets/chart.umd.min.js', array(), '4.4', true );
        wp_enqueue_script( 'sr-admin', SR_PLUGIN_URL . 'admin/assets/admin.js', array( 'jquery', 'sr-bootstrap', 'sr-chartjs' ), SR_VERSION, true );

        // Chart data — only on dashboard page
        $chart_status_data = array();
        $chart_jobs_data   = array();
        if ( strpos( $hook, 'smart-recruitment' ) !== false ) {
            global $wpdb;
            $statuses = $wpdb->get_results( "SELECT status, COUNT(*) as cnt FROM {$wpdb->prefix}sr_applications GROUP BY status" );
            foreach ( $statuses as $s ) {
                $chart_status_data[ $s->status ] = intval( $s->cnt );
            }
            $jobs_apps = $wpdb->get_results( "
                SELECT j.title, COUNT(a.id) as cnt
                FROM {$wpdb->prefix}sr_jobs j
                LEFT JOIN {$wpdb->prefix}sr_applications a ON j.id = a.job_id
                WHERE j.status = 'active'
                GROUP BY j.id ORDER BY cnt DESC LIMIT 10
            " );
            foreach ( $jobs_apps as $jd ) {
                $chart_jobs_data[ $jd->title ] = intval( $jd->cnt );
            }
        }

        wp_localize_script( 'sr-admin', 'SR', array(
            'ajax_url'     => admin_url( 'admin-ajax.php' ),
            'nonce'        => wp_create_nonce( 'sr_nonce' ),
            'chart_status' => $chart_status_data,
            'chart_jobs'   => $chart_jobs_data,
        ));
    }

    public function page_dashboard() {
        global $wpdb;
        $total   = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}sr_applications" );
        $pending = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}sr_applications WHERE status = 'submitted'" );
        $suitable = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}sr_applications WHERE status = 'suitable'" );
        $jobs    = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}sr_jobs WHERE status = 'active'" );
        $recent  = $wpdb->get_results( "
            SELECT a.*, j.title as job_title, r.score, r.strengths, r.weaknesses, r.recommendation
            FROM {$wpdb->prefix}sr_applications a
            LEFT JOIN {$wpdb->prefix}sr_jobs j ON a.job_id = j.id
            LEFT JOIN {$wpdb->prefix}sr_ai_results r ON a.id = r.application_id
            ORDER BY a.created_at DESC LIMIT 10
        " );
        include SR_PLUGIN_DIR . 'admin/views/dashboard.php';
    }

    public function page_jobs() {
        global $wpdb;
        $jobs = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}sr_jobs ORDER BY created_at DESC" );
        include SR_PLUGIN_DIR . 'admin/views/jobs.php';
    }

    public function page_applications() {
        global $wpdb;

        // Filter params
        $filter_name      = isset( $_GET['filter_name'] )   ? sanitize_text_field( $_GET['filter_name'] )   : '';
        $filter_email     = isset( $_GET['filter_email'] )  ? sanitize_text_field( $_GET['filter_email'] )  : '';
        $filter_exp       = isset( $_GET['filter_exp'] )    ? sanitize_text_field( $_GET['filter_exp'] )    : '';
        $filter_job       = isset( $_GET['filter_job'] )    ? intval( $_GET['filter_job'] )                 : 0;
        $filter_status    = isset( $_GET['filter_status'] ) ? sanitize_text_field( $_GET['filter_status'] ) : '';
        $filter_score_min = ( isset( $_GET['score_min'] ) && $_GET['score_min'] !== '' ) ? intval( $_GET['score_min'] ) : '';
        $filter_score_max = ( isset( $_GET['score_max'] ) && $_GET['score_max'] !== '' ) ? intval( $_GET['score_max'] ) : '';

        if ( ! $filter_job && isset( $_GET['job_id'] ) ) {
            $filter_job = intval( $_GET['job_id'] );
        }
        $job_id = $filter_job;

        // Sort params
        $valid_cols = array( 'full_name', 'job_title', 'experience', 'status', 'score', 'created_at' );
        $orderby    = ( isset( $_GET['orderby'] ) && in_array( $_GET['orderby'], $valid_cols, true ) ) ? $_GET['orderby'] : '';
        $order_raw  = ( isset( $_GET['order'] ) && $_GET['order'] === 'asc' ) ? 'asc' : 'desc';
        $order      = strtoupper( $order_raw );

        // Build WHERE
        $where_parts = array();
        $params      = array();

        if ( $filter_name !== '' ) {
            $where_parts[] = 'a.full_name LIKE %s';
            $params[]      = '%' . $wpdb->esc_like( $filter_name ) . '%';
        }
        if ( $filter_email !== '' ) {
            $where_parts[] = 'a.email LIKE %s';
            $params[]      = '%' . $wpdb->esc_like( $filter_email ) . '%';
        }
        if ( $filter_exp !== '' ) {
            $where_parts[] = 'a.experience LIKE %s';
            $params[]      = '%' . $wpdb->esc_like( $filter_exp ) . '%';
        }
        if ( $filter_job > 0 ) {
            $where_parts[] = 'a.job_id = %d';
            $params[]      = $filter_job;
        }
        if ( $filter_status !== '' ) {
            $where_parts[] = 'a.status = %s';
            $params[]      = $filter_status;
        }
        if ( $filter_score_min !== '' ) {
            $where_parts[] = 'r.score >= %d';
            $params[]      = $filter_score_min;
        }
        if ( $filter_score_max !== '' ) {
            $where_parts[] = 'r.score <= %d';
            $params[]      = $filter_score_max;
        }

        $where = ! empty( $where_parts ) ? 'WHERE ' . implode( ' AND ', $where_parts ) : '';

        $col_map = array(
            'full_name'  => 'a.full_name',
            'job_title'  => 'j.title',
            'experience' => 'a.experience',
            'status'     => 'a.status',
            'score'      => 'r.score',
            'created_at' => 'a.created_at',
        );
        $order_clause = $orderby ? "ORDER BY {$col_map[$orderby]} {$order}" : 'ORDER BY a.created_at DESC';

        $sql = "
            SELECT a.*, j.title as job_title, j.requirements, r.score, r.strengths, r.weaknesses, r.recommendation
            FROM {$wpdb->prefix}sr_applications a
            LEFT JOIN {$wpdb->prefix}sr_jobs j ON a.job_id = j.id
            LEFT JOIN {$wpdb->prefix}sr_ai_results r ON a.id = r.application_id
            {$where}
            {$order_clause}
        ";

        $applications = ! empty( $params )
            ? $wpdb->get_results( $wpdb->prepare( $sql, $params ) )
            : $wpdb->get_results( $sql );

        $jobs = $wpdb->get_results( "SELECT id, title FROM {$wpdb->prefix}sr_jobs ORDER BY title" );

        include SR_PLUGIN_DIR . 'admin/views/applications.php';
    }

    public function page_settings() {
        include SR_PLUGIN_DIR . 'admin/views/settings.php';
    }

    // ── CSV Export ──────────────────────────────────────────────────────────────

    public function maybe_export_csv() {
        if (
            ! is_admin() ||
            ! current_user_can( 'manage_options' ) ||
            empty( $_GET['action'] ) || $_GET['action'] !== 'export_csv' ||
            empty( $_GET['page'] )   || $_GET['page']   !== 'sr-applications' ||
            empty( $_GET['nonce'] )  || ! wp_verify_nonce( $_GET['nonce'], 'sr_export_csv' )
        ) return;

        $this->export_csv();
    }

    public function export_csv() {
        global $wpdb;

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Không có quyền truy cập' );
        }

        $filter_name      = isset( $_GET['filter_name'] )   ? sanitize_text_field( $_GET['filter_name'] )   : '';
        $filter_email     = isset( $_GET['filter_email'] )  ? sanitize_text_field( $_GET['filter_email'] )  : '';
        $filter_exp       = isset( $_GET['filter_exp'] )    ? sanitize_text_field( $_GET['filter_exp'] )    : '';
        $filter_job       = isset( $_GET['filter_job'] )    ? intval( $_GET['filter_job'] )                 : 0;
        $filter_status    = isset( $_GET['filter_status'] ) ? sanitize_text_field( $_GET['filter_status'] ) : '';
        $filter_score_min = ( isset( $_GET['score_min'] ) && $_GET['score_min'] !== '' ) ? intval( $_GET['score_min'] ) : '';
        $filter_score_max = ( isset( $_GET['score_max'] ) && $_GET['score_max'] !== '' ) ? intval( $_GET['score_max'] ) : '';

        $where_parts = array();
        $params      = array();

        if ( $filter_name !== '' ) {
            $where_parts[] = 'a.full_name LIKE %s';
            $params[]      = '%' . $wpdb->esc_like( $filter_name ) . '%';
        }
        if ( $filter_email !== '' ) {
            $where_parts[] = 'a.email LIKE %s';
            $params[]      = '%' . $wpdb->esc_like( $filter_email ) . '%';
        }
        if ( $filter_exp !== '' ) {
            $where_parts[] = 'a.experience LIKE %s';
            $params[]      = '%' . $wpdb->esc_like( $filter_exp ) . '%';
        }
        if ( $filter_job > 0 ) {
            $where_parts[] = 'a.job_id = %d';
            $params[]      = $filter_job;
        }
        if ( $filter_status !== '' ) {
            $where_parts[] = 'a.status = %s';
            $params[]      = $filter_status;
        }
        if ( $filter_score_min !== '' ) {
            $where_parts[] = 'r.score >= %d';
            $params[]      = $filter_score_min;
        }
        if ( $filter_score_max !== '' ) {
            $where_parts[] = 'r.score <= %d';
            $params[]      = $filter_score_max;
        }

        $where = ! empty( $where_parts ) ? 'WHERE ' . implode( ' AND ', $where_parts ) : '';

        $sql = "
            SELECT a.*, j.title as job_title, r.score, r.recommendation
            FROM {$wpdb->prefix}sr_applications a
            LEFT JOIN {$wpdb->prefix}sr_jobs j ON a.job_id = j.id
            LEFT JOIN {$wpdb->prefix}sr_ai_results r ON a.id = r.application_id
            {$where}
            ORDER BY a.created_at DESC
        ";

        $rows = ! empty( $params )
            ? $wpdb->get_results( $wpdb->prepare( $sql, $params ) )
            : $wpdb->get_results( $sql );

        $upload_dir = wp_upload_dir();
        $export_dir = $upload_dir['basedir'] . '/smart-recruitment/exports/applications/';
        $export_url = $upload_dir['baseurl'] . '/smart-recruitment/exports/applications/';

        if ( ! file_exists( $export_dir ) ) {
            wp_mkdir_p( $export_dir );
            file_put_contents( $export_dir . '.htaccess', 'Options -Indexes' );
        }

        $filename = 'applicants-' . date( 'Y-m-d-His' ) . '.csv';
        $filepath = $export_dir . $filename;
        $fileurl  = $export_url . $filename;

        $status_labels = array(
            'submitted'   => 'Đã nộp',
            'viewed'      => 'Đã xem',
            'suitable'    => 'Phù hợp',
            'considering' => 'Cần xem xét',
            'unsuitable'  => 'Chưa phù hợp',
        );

        $fp = fopen( $filepath, 'w' );
        fputs( $fp, "\xEF\xBB\xBF" );

        fputcsv( $fp, array( '#', 'Họ tên', 'Email', 'Số điện thoại', 'Vị trí', 'Kinh nghiệm', 'Trạng thái', 'Điểm AI', 'Nhận xét AI', 'Ngày nộp' ) );

        $i = 1;
        foreach ( $rows as $row ) {
            fputcsv( $fp, array(
                $i++,
                $row->full_name,
                $row->email,
                $row->phone,
                $row->job_title,
                $row->experience,
                $status_labels[ $row->status ] ?? $row->status,
                $row->score !== null ? $row->score . '%' : '',
                $row->recommendation ? mb_substr( strip_tags( $row->recommendation ), 0, 255 ) : '',
                date( 'd/m/Y H:i', strtotime( $row->created_at ) ),
            ) );
        }

        fclose( $fp );

        foreach ( glob( $export_dir . 'applicants-*.csv' ) as $old_file ) {
            if ( filemtime( $old_file ) < time() - 86400 ) {
                unlink( $old_file );
            }
        }

        wp_redirect( $fileurl );
        exit;
    }

    // ── Settings / Jobs ─────────────────────────────────────────────────────────

    public function save_settings() {
        check_admin_referer( 'sr_save_settings' );
        update_option( 'sr_gemini_api_key', sanitize_text_field( $_POST['gemini_api_key'] ) );
        update_option( 'sr_gemini_model',   sanitize_text_field( $_POST['gemini_model'] ?? 'gemini-2.5-flash' ) );
        update_option( 'sr_smtp_host',      sanitize_text_field( $_POST['smtp_host'] ) );
        update_option( 'sr_smtp_port',      intval( $_POST['smtp_port'] ) );
        update_option( 'sr_smtp_username',  sanitize_email( $_POST['smtp_username'] ) );
        update_option( 'sr_smtp_password',  sanitize_text_field( $_POST['smtp_password'] ) );
        wp_redirect( admin_url( 'admin.php?page=sr-settings&saved=1' ) );
        exit;
    }

    public function save_job() {
        check_admin_referer( 'sr_save_job' );
        global $wpdb;
        $data = array(
            'title'        => sanitize_text_field( $_POST['title'] ),
            'description'  => wp_kses_post( $_POST['description'] ),
            'requirements' => sanitize_textarea_field( $_POST['requirements'] ?? '' ),
            'skills'       => sanitize_text_field( $_POST['skills'] ?? '' ),
            'salary'       => sanitize_text_field( $_POST['salary'] ),
            'location'     => sanitize_text_field( $_POST['location'] ),
            'type'         => sanitize_text_field( $_POST['type'] ),
            'quantity'     => max( 1, intval( $_POST['quantity'] ?? 1 ) ),
            'status'       => 'active',
        );
        if ( ! empty( $_POST['job_id'] ) ) {
            $wpdb->update( "{$wpdb->prefix}sr_jobs", $data, array( 'id' => intval( $_POST['job_id'] ) ) );
        } else {
            $wpdb->insert( "{$wpdb->prefix}sr_jobs", $data );
        }
        wp_redirect( admin_url( 'admin.php?page=sr-jobs&saved=1' ) );
        exit;
    }

    public function delete_job() {
        check_admin_referer( 'sr_delete_job' );
        global $wpdb;
        $wpdb->delete( "{$wpdb->prefix}sr_jobs", array( 'id' => intval( $_POST['job_id'] ) ) );
        wp_redirect( admin_url( 'admin.php?page=sr-jobs&deleted=1' ) );
        exit;
    }

    // ── AJAX: Save Job (Modal) ──────────────────────────────────────────────────

    public function ajax_save_job_modal() {
        check_ajax_referer( 'sr_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Không có quyền' );
        global $wpdb;

        $data = array(
            'title'        => sanitize_text_field( $_POST['title'] ?? '' ),
            'description'  => wp_kses_post( $_POST['description'] ?? '' ),
            'requirements' => sanitize_textarea_field( $_POST['requirements'] ?? '' ),
            'skills'       => sanitize_text_field( $_POST['skills'] ?? '' ),
            'salary'       => sanitize_text_field( $_POST['salary'] ?? '' ),
            'location'     => sanitize_text_field( $_POST['location'] ?? '' ),
            'type'         => sanitize_text_field( $_POST['type'] ?? 'full-time' ),
            'quantity'     => max( 1, intval( $_POST['quantity'] ?? 1 ) ),
            'status'       => 'active',
        );

        if ( empty( $data['title'] ) ) wp_send_json_error( 'Tên vị trí không được để trống' );

        $job_id = intval( $_POST['job_id'] ?? 0 );
        if ( $job_id > 0 ) {
            $wpdb->update( "{$wpdb->prefix}sr_jobs", $data, array( 'id' => $job_id ) );
            wp_send_json_success( array( 'job_id' => $job_id, 'action' => 'updated' ) );
        } else {
            $wpdb->insert( "{$wpdb->prefix}sr_jobs", $data );
            wp_send_json_success( array( 'job_id' => $wpdb->insert_id, 'action' => 'created' ) );
        }
    }

    // ── AJAX: Toggle Job Status ──────────────────────────────────────────────────

    public function ajax_toggle_job_status() {
        check_ajax_referer( 'sr_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Không có quyền' );
        global $wpdb;

        $job_id = intval( $_POST['job_id'] );
        $job    = $wpdb->get_row( $wpdb->prepare( "SELECT id, status FROM {$wpdb->prefix}sr_jobs WHERE id = %d", $job_id ) );
        if ( ! $job ) wp_send_json_error( 'Không tìm thấy vị trí' );

        $new_status = $job->status === 'active' ? 'inactive' : 'active';
        $wpdb->update( "{$wpdb->prefix}sr_jobs", array( 'status' => $new_status ), array( 'id' => $job_id ) );

        wp_send_json_success( array( 'status' => $new_status ) );
    }

    // ── AJAX: Generate JD ────────────────────────────────────────────────────────

    public function ajax_generate_jd() {
        check_ajax_referer( 'sr_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Không có quyền' );

        $title    = sanitize_text_field( $_POST['title']    ?? '' );
        $skills   = sanitize_text_field( $_POST['skills']   ?? '' );
        $location = sanitize_text_field( $_POST['location'] ?? '' );
        $type     = sanitize_text_field( $_POST['type']     ?? '' );
        $salary   = sanitize_text_field( $_POST['salary']   ?? '' );

        if ( empty( $title ) ) wp_send_json_error( 'Vui lòng nhập tên vị trí' );

        $gemini = new SR_Gemini();
        $result = $gemini->generate_jd( $title, $skills, $location, $type, $salary );

        if ( isset( $result['error'] ) ) wp_send_json_error( $result['error'] );

        wp_send_json_success( $result );
    }

    // ── AJAX: Analyze CV ────────────────────────────────────────────────────────

    public function ajax_analyze_cv() {
        check_ajax_referer( 'sr_nonce', 'nonce' );
        global $wpdb;

        $app_id = intval( $_POST['application_id'] );
        $app    = $wpdb->get_row( $wpdb->prepare( "
            SELECT a.*, j.title as job_title, j.requirements, j.skills
            FROM {$wpdb->prefix}sr_applications a
            LEFT JOIN {$wpdb->prefix}sr_jobs j ON a.job_id = j.id
            WHERE a.id = %d
        ", $app_id ) );

        if ( ! $app ) wp_send_json_error( 'Không tìm thấy ứng viên' );

        // Auto-set status to 'viewed' when HR triggers analysis on a 'submitted' application
        if ( $app->status === 'submitted' ) {
            $wpdb->update( "{$wpdb->prefix}sr_applications", array( 'status' => 'viewed' ), array( 'id' => $app_id ) );
        }

        // Check AI cache first
        $existing = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}sr_ai_results WHERE application_id = %d", $app_id
        ));
        if ( $existing && $existing->score > 0 ) {
            wp_send_json_success( array(
                'score'          => $existing->score,
                'strengths'      => json_decode( $existing->strengths, true ) ?: array(),
                'weaknesses'     => json_decode( $existing->weaknesses, true ) ?: array(),
                'recommendation' => $existing->recommendation,
                'cached'         => true,
            ));
        }

        // Use skills+requirements for analysis
        $job_requirements = trim( ( $app->requirements ?? '' ) . "\n" . ( $app->skills ?? '' ) );

        $gemini = new SR_Gemini();
        $result = $gemini->analyze_cv( $app->cv_path, $app->job_title, $job_requirements );

        if ( isset( $result['error'] ) ) wp_send_json_error( $result['error'] );

        $data = array(
            'application_id' => $app_id,
            'score'          => intval( $result['score'] ),
            'strengths'      => json_encode( $result['strengths'] ),
            'weaknesses'     => json_encode( $result['weaknesses'] ),
            'recommendation' => sanitize_textarea_field( $result['recommendation'] ),
            'analyzed_at'    => current_time( 'mysql' ),
        );

        if ( $existing ) {
            $wpdb->update( "{$wpdb->prefix}sr_ai_results", $data, array( 'application_id' => $app_id ) );
        } else {
            $wpdb->insert( "{$wpdb->prefix}sr_ai_results", $data );
        }

        wp_send_json_success( $result );
    }

    // ── AJAX: Update Status ─────────────────────────────────────────────────────

    public function ajax_update_status() {
        check_ajax_referer( 'sr_nonce', 'nonce' );
        global $wpdb;

        $app_id       = intval( $_POST['application_id'] );
        $status       = sanitize_text_field( $_POST['status'] );
        $email_option = sanitize_text_field( $_POST['email_option'] ?? 'none' );
        $custom_msg   = sanitize_textarea_field( $_POST['custom_message'] ?? '' );

        // Only allow HR-settable statuses
        $allowed = array( 'suitable', 'considering', 'unsuitable' );
        if ( ! in_array( $status, $allowed, true ) ) {
            wp_send_json_error( 'Trạng thái không hợp lệ' );
        }

        $app = $wpdb->get_row( $wpdb->prepare(
            "SELECT a.*, j.title as job_title FROM {$wpdb->prefix}sr_applications a
             LEFT JOIN {$wpdb->prefix}sr_jobs j ON a.job_id = j.id
             WHERE a.id = %d", $app_id
        ) );

        if ( ! $app ) wp_send_json_error( 'Không tìm thấy ứng viên' );

        // Reject if email already sent
        if ( $app->email_sent == 1 ) {
            wp_send_json_error( 'Không thể thay đổi trạng thái sau khi đã gửi email.' );
        }

        $wpdb->update( "{$wpdb->prefix}sr_applications", array( 'status' => $status ), array( 'id' => $app_id ) );

        $email_sent = false;

        // Send email only for suitable/unsuitable
        if ( in_array( $status, array( 'suitable', 'unsuitable' ), true ) ) {
            if ( $email_option === 'default' ) {
                $mailer = new SR_Mailer();
                if ( $status === 'suitable' ) {
                    $ai    = $wpdb->get_row( $wpdb->prepare(
                        "SELECT score FROM {$wpdb->prefix}sr_ai_results WHERE application_id = %d", $app_id
                    ));
                    $score = $ai ? $ai->score : 0;
                    $mailer->send_accepted( $app->email, $app->full_name, $app->job_title, $score );
                } else {
                    $mailer->send_rejected( $app->email, $app->full_name, $app->job_title );
                }
                $email_sent = true;
            } elseif ( $email_option === 'custom' && ! empty( $custom_msg ) ) {
                $from_email = get_option( 'sr_smtp_username', get_option( 'admin_email' ) );
                $from_name  = get_option( 'blogname', 'TalentIQ' );
                $headers    = array(
                    'Content-Type: text/html; charset=UTF-8',
                    "From: {$from_name} <{$from_email}>",
                );
                $subject = '[TalentIQ] Thông báo từ HR';
                $body    = '<div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;padding:24px;">'
                         . '<p>' . nl2br( esc_html( $custom_msg ) ) . '</p>'
                         . '</div>';
                wp_mail( $app->email, $subject, $body, $headers );
                $email_sent = true;
            }

            // Lock row after email sent
            if ( $email_sent ) {
                $wpdb->update( "{$wpdb->prefix}sr_applications", array( 'email_sent' => 1 ), array( 'id' => $app_id ) );
            }
        }

        wp_send_json_success( array( 'status' => $status, 'email_sent' => $email_sent ? 1 : 0 ) );
    }
}
