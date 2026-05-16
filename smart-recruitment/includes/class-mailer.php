<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class SR_Mailer {

    public function __construct() {
        add_action( 'phpmailer_init', array( $this, 'setup_smtp' ) );
    }

    public function setup_smtp( $phpmailer ) {
        $host     = get_option( 'sr_smtp_host', '' );
        $port     = get_option( 'sr_smtp_port', 587 );
        $username = get_option( 'sr_smtp_username', '' );
        $password = get_option( 'sr_smtp_password', '' );

        if ( empty( $host ) || empty( $username ) ) return;

        $phpmailer->isSMTP();
        $phpmailer->Host       = $host;
        $phpmailer->SMTPAuth   = true;
        $phpmailer->Username   = $username;
        $phpmailer->Password   = $password;
        $phpmailer->SMTPSecure = 'tls';
        $phpmailer->Port       = $port;
        $phpmailer->CharSet    = 'UTF-8';
    }

    public function send_accepted( $to_email, $to_name, $job_title, $score ) {
        $subject = '[TalentIQ] Chúc mừng! Hồ sơ của bạn đã được chấp nhận';

        ob_start();
        include SR_PLUGIN_DIR . 'templates/email-accepted.php';
        $body = ob_get_clean();

        return $this->send( $to_email, $subject, $body );
    }

    public function send_rejected( $to_email, $to_name, $job_title ) {
        $subject = '[TalentIQ] Cảm ơn bạn đã ứng tuyển tại TalentIQ';

        ob_start();
        include SR_PLUGIN_DIR . 'templates/email-rejected.php';
        $body = ob_get_clean();

        return $this->send( $to_email, $subject, $body );
    }

    public function send_confirmation( $to_email, $to_name, $job_title ) {
        $subject = '[TalentIQ] Xác nhận nhận hồ sơ ứng tuyển';
        $body = "
        <div style='font-family:Arial,sans-serif;max-width:600px;margin:0 auto;padding:24px;'>
            <h2 style='color:#7B61FF'>Xin chào {$to_name}!</h2>
            <p>Chúng tôi đã nhận được hồ sơ ứng tuyển của bạn cho vị trí <strong>{$job_title}</strong>.</p>
            <p>AI đang phân tích CV của bạn. HR sẽ phản hồi trong vòng <strong>3-5 ngày làm việc</strong>.</p>
            <br>
            <p>Trân trọng,<br><strong>Team TalentIQ</strong></p>
        </div>";

        return $this->send( $to_email, $subject, $body );
    }

    private function send( $to, $subject, $body ) {
        $from_email = get_option( 'sr_smtp_username', get_option( 'admin_email' ) );
        $from_name  = get_option( 'blogname', 'TalentIQ' );

        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            "From: {$from_name} <{$from_email}>",
        );

        return wp_mail( $to, $subject, $body, $headers );
    }
}