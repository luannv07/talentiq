<?php if (!defined('ABSPATH')) exit; ?>

<div class="sr-page">
    <div class="sr-topbar">
        <span class="sr-topbar-brand"><i class="fas fa-brain"></i> TalentIQ <span class="sr-brand-dot"></span></span>
        <nav class="sr-topbar-nav">
            <a href="<?php echo admin_url('admin.php?page=smart-recruitment'); ?>">
                <i class="fas fa-chart-pie"></i>Dashboard
            </a>
            <a href="<?php echo admin_url('admin.php?page=sr-jobs'); ?>">
                <i class="fas fa-briefcase"></i>Vị trí
            </a>
            <a href="<?php echo admin_url('admin.php?page=sr-applications'); ?>">
                <i class="fas fa-users"></i>Ứng viên
            </a>
            <a href="<?php echo admin_url('admin.php?page=sr-settings'); ?>" class="active">
                <i class="fas fa-gear"></i>Cài đặt
            </a>
        </nav>
        <div class="sr-topbar-user">
            <i class="fas fa-user-circle"></i>
            <span><?php echo esc_html(wp_get_current_user()->display_name); ?></span>
            <a href="<?php echo wp_logout_url(admin_url()); ?>" class="btn btn-sm btn-outline-light ms-2">
                <i class="fas fa-right-from-bracket me-1"></i>Đăng xuất
            </a>
        </div>
    </div>

    <div class="sr-content">
        <div class="sr-page-header">
            <div>
                <h4><span class="sr-page-icon"><i class="fas fa-gear"></i></span>Cài đặt</h4>
                <div class="sr-page-subtitle">Cấu hình AI và email cho hệ thống</div>
            </div>
        </div>

        <div class="sr-settings-wrap">

        <?php if (isset($_GET['saved'])) : ?>
            <div class="alert alert-success alert-dismissible fade show mb-4">
                <i class="fas fa-circle-check me-2"></i>Đã lưu cài đặt!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
            <?php wp_nonce_field('sr_save_settings'); ?>
            <input type="hidden" name="action" value="sr_save_settings">

            <ul class="nav sr-settings-tabs" id="srSettingsTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-ai" type="button">
                        <i class="fas fa-robot me-2"></i>Gemini AI
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-smtp" type="button">
                        <i class="fas fa-envelope me-2"></i>Email SMTP
                    </button>
                </li>
            </ul>

            <div class="tab-content">
                <div class="tab-pane fade show active" id="tab-ai" role="tabpanel">
                    <div class="sr-settings-card">
                        <div class="row g-3">
                                <div class="col-12 col-md-8">
                                    <label class="form-label fw-semibold small">Gemini API Key</label>
                                    <div class="input-group">
                                        <input type="password" name="gemini_api_key" id="srGeminiApiKey"
                                            class="form-control" autocomplete="new-password"
                                            value="<?php echo esc_attr(get_option('sr_gemini_api_key', '')); ?>"
                                            placeholder="AIza...">
                                        <button type="button" class="btn btn-outline-secondary"
                                            onclick="srToggleApiKey()" id="srApiKeyToggle" title="Hiện/ẩn API key">
                                            <i class="fas fa-eye" id="srApiKeyIcon"></i>
                                        </button>
                                    </div>
                                    <div class="form-text">
                                        <i class="fas fa-circle-info me-1"></i>
                                        Lấy API key tại <a href="https://aistudio.google.com" target="_blank">Google AI Studio</a>
                                    </div>
                                </div>
                                <div class="col-12 col-md-4">
                                    <label class="form-label fw-semibold small">Gemini Model</label>
                                    <select name="gemini_model" class="form-select">
                                        <?php
                                        $models = [
                                            'gemini-2.5-flash'         => 'Gemini 2.5 Flash (Nhanh, khuyến nghị)',
                                            'gemini-2.5-pro'           => 'Gemini 2.5 Pro (Mạnh nhất)',
                                            'gemini-2.5-flash-lite'    => 'Gemini 2.5 Flash Lite (Nhẹ nhất)',
                                            'gemini-2.0-flash'         => 'Gemini 2.0 Flash',
                                            'gemini-2.0-flash-lite'    => 'Gemini 2.0 Flash Lite',
                                            'gemini-2.0-flash-exp'     => 'Gemini 2.0 Flash Exp',
                                            'gemini-2.5-pro-exp-03-25' => 'Gemini 2.5 Pro Exp',
                                            'gemini-1.5-pro'           => 'Gemini 1.5 Pro',
                                            'gemini-1.5-flash'         => 'Gemini 1.5 Flash',
                                            'gemma-3-4b-it'            => 'Gemma 3 4B',
                                            'gemma-3-12b-it'           => 'Gemma 3 12B',
                                            'gemma-3-27b-it'           => 'Gemma 3 27B',
                                        ];
                                        $cur = get_option('sr_gemini_model', 'gemini-2.5-flash');
                                        foreach ($models as $val => $label) : ?>
                                            <option value="<?php echo esc_attr($val); ?>" <?php selected($cur, $val); ?>>
                                                <?php echo esc_html($label); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                        </div>
                    </div>
                </div><!-- /tab-ai -->

                <div class="tab-pane fade" id="tab-smtp" role="tabpanel">
                    <div class="sr-settings-card">
                        <div class="row g-3">
                                <div class="col-12 col-md-8">
                                    <label class="form-label fw-semibold small">SMTP Host</label>
                                    <input type="text" name="smtp_host" class="form-control"
                                        value="<?php echo esc_attr(get_option('sr_smtp_host', 'smtp.gmail.com')); ?>"
                                        placeholder="smtp.gmail.com">
                                </div>
                                <div class="col-12 col-md-4">
                                    <label class="form-label fw-semibold small">SMTP Port</label>
                                    <input type="number" name="smtp_port" class="form-control"
                                        value="<?php echo esc_attr(get_option('sr_smtp_port', 587)); ?>"
                                        placeholder="587">
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label fw-semibold small">Gmail Address</label>
                                    <input type="email" name="smtp_username" class="form-control"
                                        value="<?php echo esc_attr(get_option('sr_smtp_username', '')); ?>"
                                        placeholder="yourname@gmail.com">
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label fw-semibold small">App Password</label>
                                    <input type="password" name="smtp_password" class="form-control"
                                        value="<?php echo esc_attr(get_option('sr_smtp_password', '')); ?>"
                                        placeholder="xxxx xxxx xxxx xxxx">
                                    <div class="form-text">
                                        Tạo tại <a href="https://myaccount.google.com/apppasswords" target="_blank">Google Account</a>
                                    </div>
                                </div>
                            </div>
                    </div>
                </div><!-- /tab-smtp -->
            </div><!-- /tab-content -->

            <div class="mt-4">
                <button type="submit" class="btn btn-primary" style="padding:10px 32px;font-size:1rem">
                    <i class="fas fa-floppy-disk me-1"></i>Lưu cài đặt
                </button>
            </div>
        </form>

        </div><!-- /.sr-settings-wrap -->
    </div>
</div>

<script>
function srToggleApiKey() {
    var input = document.getElementById('srGeminiApiKey');
    var icon  = document.getElementById('srApiKeyIcon');
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'fas fa-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'fas fa-eye';
    }
}
</script>
