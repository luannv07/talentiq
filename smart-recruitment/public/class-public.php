<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class SR_Public {

    public function __construct() {
        add_shortcode( 'sr_job_listing', array( $this, 'shortcode_job_listing' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        add_action( 'wp_ajax_sr_submit_application',        array( $this, 'ajax_submit_application' ) );
        add_action( 'wp_ajax_nopriv_sr_submit_application', array( $this, 'ajax_submit_application' ) );
        add_action( 'wp_ajax_sr_extract_cv',        array( $this, 'ajax_extract_cv' ) );
        add_action( 'wp_ajax_nopriv_sr_extract_cv', array( $this, 'ajax_extract_cv' ) );
    }

    public function enqueue_assets() {
        wp_enqueue_style( 'sr-bootstrap', SR_PLUGIN_URL . 'assets/bootstrap/css/bootstrap.min.css', array(), '5.3' );
        wp_enqueue_style( 'sr-fa',        SR_PLUGIN_URL . 'assets/fontawesome/css/all.min.css',     array(), '6.5' );
        wp_enqueue_style( 'sr-public', SR_PLUGIN_URL . 'public/assets/public.css', array( 'sr-bootstrap', 'sr-fa' ), SR_VERSION );
        wp_enqueue_script( 'sr-bootstrap', SR_PLUGIN_URL . 'assets/bootstrap/js/bootstrap.bundle.min.js', array( 'jquery' ), '5.3', true );
        wp_enqueue_script( 'sr-public', SR_PLUGIN_URL . 'public/assets/public.js', array( 'jquery', 'sr-bootstrap' ), SR_VERSION, true );
        wp_localize_script( 'sr-public', 'SR', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'sr_nonce' ),
        ));
    }

    public function shortcode_job_listing( $atts ) {
        global $wpdb;
        $atts = shortcode_atts( array( 'limit' => 50 ), $atts );

        // Apply form view
        $selected_job = null;
        if ( isset( $_GET['apply_job'] ) ) {
            $selected_job = $wpdb->get_row( $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}sr_jobs WHERE id = %d AND status = 'active'",
                intval( $_GET['apply_job'] )
            ));
        }

        // Job detail view
        $view_job = null;
        if ( ! $selected_job && isset( $_GET['job_id'] ) ) {
            $view_job = $wpdb->get_row( $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}sr_jobs WHERE id = %d AND status = 'active'",
                intval( $_GET['job_id'] )
            ));
        }

        // Build job listing with filters
        $search   = isset( $_GET['sr_search'] )   ? sanitize_text_field( $_GET['sr_search'] )   : '';
        $f_type   = isset( $_GET['sr_type'] )     ? sanitize_text_field( $_GET['sr_type'] )     : '';
        $f_loc    = isset( $_GET['sr_location'] ) ? sanitize_text_field( $_GET['sr_location'] ) : '';

        $where_parts = [ "status = 'active'" ];
        $params      = [];

        if ( $search !== '' ) {
            $where_parts[] = '(title LIKE %s OR description LIKE %s OR skills LIKE %s)';
            $like          = '%' . $wpdb->esc_like( $search ) . '%';
            $params[]      = $like;
            $params[]      = $like;
            $params[]      = $like;
        }
        if ( $f_type !== '' ) {
            $where_parts[] = 'type = %s';
            $params[]      = $f_type;
        }
        if ( $f_loc !== '' ) {
            $where_parts[] = 'location = %s';
            $params[]      = $f_loc;
        }

        $where = implode( ' AND ', $where_parts );
        $sql   = "SELECT * FROM {$wpdb->prefix}sr_jobs WHERE {$where} ORDER BY created_at DESC LIMIT %d";
        $params[] = intval( $atts['limit'] );

        $jobs = $wpdb->get_results( $wpdb->prepare( $sql, $params ) );

        // Distinct locations for filter dropdown
        $locations = $wpdb->get_col(
            "SELECT DISTINCT location FROM {$wpdb->prefix}sr_jobs WHERE status = 'active' AND location != '' ORDER BY location"
        );

        ob_start();
        if ( $selected_job ) {
            include SR_PLUGIN_DIR . 'public/views/apply-form.php';
        } elseif ( $view_job ) {
            include SR_PLUGIN_DIR . 'public/views/job-detail.php';
        } else {
            include SR_PLUGIN_DIR . 'public/views/job-listing.php';
        }
        return ob_get_clean();
    }

    // ── CV Auto-Fill: Extract text from PDF ──────────────────────────────────

    public function ajax_extract_cv() {
        check_ajax_referer( 'sr_nonce', 'nonce' );

        if ( empty( $_FILES['cv_file'] ) || $_FILES['cv_file']['error'] !== UPLOAD_ERR_OK ) {
            wp_send_json_error( 'Lỗi upload file' );
        }

        $file = $_FILES['cv_file'];

        // Validate type
        $file_type = wp_check_filetype( $file['name'] );
        if ( $file_type['ext'] !== 'pdf' ) {
            wp_send_json_error( 'Only PDF files are accepted.' );
        }

        // Validate size (10 MB)
        if ( $file['size'] > 10 * 1024 * 1024 ) {
            wp_send_json_error( 'File size must not exceed 10MB.' );
        }

        // Parse with Smalot PDF Parser
        if ( ! class_exists( '\Smalot\PdfParser\Parser' ) ) {
            wp_send_json_error( 'PDF parser not available. Run composer install.' );
        }

        try {
            $parser = new \Smalot\PdfParser\Parser();
            $pdf    = $parser->parseFile( $file['tmp_name'] );
            $text   = $pdf->getText();
        } catch ( \Exception $e ) {
            wp_send_json_error( 'Không thể đọc file PDF' );
        }

        // Temp file is managed by PHP — no manual delete needed

        // Extract fields via regex
        $result = array(
            'full_name'  => '',
            'email'      => '',
            'phone'      => '',
            'experience' => '',
        );

        // Email
        if ( preg_match( '/[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}/', $text, $m ) ) {
            $result['email'] = $m[0];
        }

        // Phone — Vietnamese patterns: 0xxxxxxxxx or +84xxxxxxxxx
        if ( preg_match( '/(?:\+84|0)[0-9]{9}/', $text, $m ) ) {
            $result['phone'] = $m[0];
        }

        // Name — line after keyword, or first non-empty line
        $lines = array_values( array_filter( array_map( 'trim', explode( "\n", $text ) ) ) );
        foreach ( $lines as $line ) {
            if ( preg_match( '/(?:Name|Họ tên|Họ và tên|Full Name)\s*[:：]\s*(.+)/iu', $line, $m ) ) {
                $result['full_name'] = trim( $m[1] );
                break;
            }
        }
        if ( empty( $result['full_name'] ) && ! empty( $lines ) ) {
            $result['full_name'] = $lines[0];
        }

        // Experience — "X year(s)" or "X năm kinh nghiệm"
        if ( preg_match( '/(\d+)\s*(?:year[s]?\s*(?:of\s*)?experience|năm\s*kinh\s*nghiệm)/iu', $text, $m ) ) {
            $result['experience'] = $m[1] . ' năm';
        }

        wp_send_json_success( $result );
    }

    // ── Submit Application ───────────────────────────────────────────────────

    public function ajax_submit_application() {
        check_ajax_referer( 'sr_nonce', 'nonce' );
        global $wpdb;

        // Validate required fields
        $required = array( 'full_name', 'email', 'job_id' );
        foreach ( $required as $field ) {
            if ( empty( $_POST[ $field ] ) ) {
                wp_send_json_error( 'Vui lòng điền đầy đủ thông tin bắt buộc' );
            }
        }

        if ( ! is_email( $_POST['email'] ) ) {
            wp_send_json_error( 'Email không hợp lệ' );
        }

        // Server-side file validation before upload
        if ( ! empty( $_FILES['cv_file']['name'] ) ) {
            $file_type = wp_check_filetype( $_FILES['cv_file']['name'] );
            if ( $file_type['ext'] !== 'pdf' ) {
                wp_send_json_error( 'Only PDF files are accepted.' );
            }
            if ( $_FILES['cv_file']['size'] > 10 * 1024 * 1024 ) {
                wp_send_json_error( 'File size must not exceed 10MB.' );
            }
        }

        // Upload CV
        $cv_path = '';
        $cv_url  = '';
        if ( ! empty( $_FILES['cv_file']['name'] ) ) {
            $uploader = new SR_Uploader();
            $upload   = $uploader->upload_cv( $_FILES['cv_file'] );
            if ( isset( $upload['error'] ) ) {
                wp_send_json_error( $upload['error'] );
            }
            $cv_path = $upload['path'];
            $cv_url  = $upload['url'];
        }

        // Insert into DB with new default status 'submitted'
        $inserted = $wpdb->insert( "{$wpdb->prefix}sr_applications", array(
            'job_id'       => intval( $_POST['job_id'] ),
            'full_name'    => sanitize_text_field( $_POST['full_name'] ),
            'email'        => sanitize_email( $_POST['email'] ),
            'phone'        => sanitize_text_field( $_POST['phone'] ?? '' ),
            'experience'   => sanitize_text_field( $_POST['experience'] ?? '' ),
            'cover_letter' => sanitize_textarea_field( $_POST['cover_letter'] ?? '' ),
            'cv_path'      => $cv_path,
            'status'       => 'submitted',
        ));

        if ( ! $inserted ) {
            wp_send_json_error( 'Có lỗi xảy ra, vui lòng thử lại' );
        }

        // Send confirmation email
        $job = $wpdb->get_row( $wpdb->prepare(
            "SELECT title FROM {$wpdb->prefix}sr_jobs WHERE id = %d",
            intval( $_POST['job_id'] )
        ));
        $mailer = new SR_Mailer();
        $mailer->send_confirmation(
            sanitize_email( $_POST['email'] ),
            sanitize_text_field( $_POST['full_name'] ),
            $job->title
        );

        wp_send_json_success( array( 'message' => 'Nộp hồ sơ thành công!' ) );
    }
}
