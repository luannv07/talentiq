<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;background:#fff;border-radius:16px;overflow:hidden;border:1px solid #eee">
    <div style="background:linear-gradient(135deg,#7B61FF,#5B3FDF);padding:32px;text-align:center">
        <h1 style="color:#fff;margin:0;font-size:24px">🎉 Chúc mừng!</h1>
        <p style="color:rgba(255,255,255,0.8);margin:8px 0 0">Hồ sơ của bạn đã được chấp nhận</p>
    </div>
    <div style="padding:32px">
        <p style="font-size:16px">Xin chào <strong><?php echo esc_html($to_name); ?></strong>,</p>
        <p style="color:#444;line-height:1.8">
            Chúng tôi rất vui được thông báo rằng hồ sơ của bạn cho vị trí
            <strong style="color:#7B61FF"><?php echo esc_html($job_title); ?></strong>
            đã được HR xem xét và <strong style="color:#00C48C">chấp nhận</strong>.
        </p>
        <?php if ( $score ) : ?>
        <div style="background:#f0fdf4;border:1px solid #86efac;border-radius:12px;padding:16px;margin:20px 0;text-align:center">
            <div style="font-size:32px;font-weight:800;color:#00C48C"><?php echo intval($score); ?>%</div>
            <div style="color:#666;font-size:13px">AI Compatibility Score</div>
        </div>
        <?php endif; ?>
        <p style="color:#444;line-height:1.8">
            Chúng tôi sẽ liên hệ với bạn trong vòng <strong>2 ngày làm việc</strong>
            để sắp xếp buổi phỏng vấn. Vui lòng kiểm tra email và điện thoại thường xuyên.
        </p>
        <p style="color:#444;line-height:1.8">Trân trọng,<br>
        <strong>Team Tuyển dụng TalentIQ</strong></p>
    </div>
    <div style="background:#f9f9f9;padding:16px;text-align:center;color:#999;font-size:12px">
        Email này được gửi tự động từ hệ thống TalentIQ
    </div>
</div>