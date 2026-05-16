<?php if (!defined('ABSPATH')) exit; ?>

<?php
$status_labels = [
    'submitted'   => 'Đã nộp',
    'viewed'      => 'Đã xem',
    'suitable'    => 'Phù hợp',
    'considering' => 'Cần xem xét',
    'unsuitable'  => 'Chưa phù hợp',
];
$status_badge = [
    'submitted'   => 'sr-badge-submitted',
    'viewed'      => 'sr-badge-viewed',
    'suitable'    => 'sr-badge-suitable',
    'considering' => 'sr-badge-considering',
    'unsuitable'  => 'sr-badge-unsuitable',
];
?>

<div class="sr-page">
    <div class="sr-topbar">
        <span class="sr-topbar-brand"><i class="fas fa-brain"></i> TalentIQ <span class="sr-brand-dot"></span></span>
        <nav class="sr-topbar-nav">
            <a href="<?php echo admin_url('admin.php?page=smart-recruitment'); ?>" class="active">
                <i class="fas fa-chart-pie"></i>Dashboard
            </a>
            <a href="<?php echo admin_url('admin.php?page=sr-jobs'); ?>">
                <i class="fas fa-briefcase"></i>Vị trí
            </a>
            <a href="<?php echo admin_url('admin.php?page=sr-applications'); ?>">
                <i class="fas fa-users"></i>Ứng viên
            </a>
            <a href="<?php echo admin_url('admin.php?page=sr-settings'); ?>">
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
                <h4><span class="sr-page-icon"><i class="fas fa-chart-pie"></i></span>Dashboard</h4>
                <div class="sr-page-subtitle">Tổng quan hệ thống tuyển dụng TalentIQ</div>
            </div>
            <button class="btn btn-primary" onclick="srOpenJobModal(null)">
                <i class="fas fa-plus me-1"></i>Thêm vị trí mới
            </button>
        </div>

        <!-- Stat Cards -->
        <div class="sr-stat-grid">
            <div class="sr-stat-card sr-stat-purple">
                <div class="sr-stat-body">
                    <div class="sr-stat-info">
                        <span class="sr-stat-label">Tổng ứng viên</span>
                        <div class="sr-stat-value"><?php echo intval($total); ?></div>
                    </div>
                    <div class="sr-stat-icon-bg"><i class="fas fa-users"></i></div>
                </div>
            </div>
            <div class="sr-stat-card sr-stat-yellow">
                <div class="sr-stat-body">
                    <div class="sr-stat-info">
                        <span class="sr-stat-label">Chờ xử lý</span>
                        <div class="sr-stat-value"><?php echo intval($pending); ?></div>
                    </div>
                    <div class="sr-stat-icon-bg"><i class="fas fa-hourglass-half"></i></div>
                </div>
            </div>
            <div class="sr-stat-card sr-stat-green">
                <div class="sr-stat-body">
                    <div class="sr-stat-info">
                        <span class="sr-stat-label">Phù hợp</span>
                        <div class="sr-stat-value"><?php echo intval($suitable); ?></div>
                    </div>
                    <div class="sr-stat-icon-bg"><i class="fas fa-circle-check"></i></div>
                </div>
            </div>
            <div class="sr-stat-card sr-stat-blue">
                <div class="sr-stat-body">
                    <div class="sr-stat-info">
                        <span class="sr-stat-label">Vị trí đang tuyển</span>
                        <div class="sr-stat-value"><?php echo intval($jobs); ?></div>
                    </div>
                    <div class="sr-stat-icon-bg"><i class="fas fa-briefcase"></i></div>
                </div>
            </div>
        </div>

        <!-- Charts -->
        <div class="sr-chart-section">
            <div class="sr-chart-card">
                <div class="sr-chart-header"><i class="fas fa-chart-bar"></i>Ứng viên theo vị trí</div>
                <canvas id="srChartJobs" height="220"></canvas>
            </div>
            <div class="sr-chart-card">
                <div class="sr-chart-header"><i class="fas fa-chart-pie"></i>Phân bổ trạng thái</div>
                <canvas id="srChartStatus" height="220"></canvas>
            </div>
        </div>

        <!-- Recent Applications -->
        <div class="sr-panel">
            <div class="sr-panel-header">
                <h6><i class="fas fa-clock-rotate-left"></i>Ứng viên gần đây</h6>
                <a href="<?php echo admin_url('admin.php?page=sr-applications'); ?>" class="btn btn-sm btn-outline-primary">
                    Xem tất cả <i class="fas fa-arrow-right ms-1"></i>
                </a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover table-bordered mb-0 sr-table">
                    <thead>
                        <tr>
                            <th>Ứng viên</th>
                            <th>Vị trí</th>
                            <th>Trạng thái</th>
                            <th>Phù hợp</th>
                            <th class="sr-col-hide-sm">Nhận xét AI</th>
                            <th class="sr-col-hide-sm">Ngày nộp</th>
                            <th>Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recent)) : ?>
                            <tr><td colspan="7">
                                <div class="sr-empty-state">
                                    <i class="fas fa-inbox"></i>Chưa có ứng viên nào
                                </div>
                            </td></tr>
                        <?php else : ?>
                            <?php foreach ($recent as $app) :
                                $locked = !empty($app->email_sent) && $app->email_sent == 1;
                            ?>
                                <tr id="row-<?php echo $app->id; ?>" data-email-sent="<?php echo $locked ? 1 : 0; ?>">
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="sr-avatar"><?php echo strtoupper(substr($app->full_name, 0, 2)); ?></div>
                                            <div>
                                                <div class="fw-semibold" style="font-size:0.9rem"><?php echo esc_html($app->full_name); ?></div>
                                                <div class="text-muted" style="font-size:0.75rem"><?php echo esc_html($app->email); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td style="font-size:0.875rem"><?php echo esc_html($app->job_title); ?></td>
                                    <td>
                                        <span class="badge <?php echo $status_badge[$app->status] ?? 'sr-badge-submitted'; ?>"
                                              id="status-<?php echo $app->id; ?>">
                                            <?php echo $status_labels[$app->status] ?? esc_html($app->status); ?>
                                        </span>
                                        <?php if ($locked) : ?>
                                            <i class="fas fa-lock text-muted ms-1" style="font-size:11px" title="Đã gửi email"></i>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($app->score !== null && $app->score !== '') :
                                            $s = intval($app->score);
                                            $tier = $s >= 70 ? 'high' : ($s >= 40 ? 'mid' : 'low');
                                        ?>
                                            <div class="sr-score-wrap" id="score-wrap-<?php echo $app->id; ?>">
                                                <div class="sr-score-bar">
                                                    <div class="sr-score-fill score-<?php echo $tier; ?>" style="width:<?php echo $s; ?>%"></div>
                                                </div>
                                                <span class="sr-score-badge score-<?php echo $tier; ?>"><?php echo $s; ?>%</span>
                                            </div>
                                        <?php else : ?>
                                            <span class="text-muted" style="font-size:0.8rem" id="score-wrap-<?php echo $app->id; ?>">Chưa phân tích</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="sr-col-hide-sm">
                                        <?php if ($app->recommendation) : ?>
                                            <a href="#" class="sr-review-link"
                                                data-app-id="<?php echo $app->id; ?>"
                                                data-score="<?php echo intval($app->score); ?>"
                                                data-name="<?php echo esc_attr($app->full_name); ?>"
                                                data-job="<?php echo esc_attr($app->job_title); ?>"
                                                data-strengths="<?php echo esc_attr($app->strengths ?: '[]'); ?>"
                                                data-weaknesses="<?php echo esc_attr($app->weaknesses ?: '[]'); ?>"
                                                data-recommendation="<?php echo esc_attr($app->recommendation); ?>"
                                                onclick="srViewAIResult(this); return false;">
                                                <?php echo esc_html(mb_substr($app->recommendation, 0, 60)); ?><i class="fas fa-ellipsis ms-1"></i>
                                            </a>
                                        <?php else : ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="sr-col-hide-sm text-muted" style="font-size:0.8rem"><?php echo date('d/m/Y', strtotime($app->created_at)); ?></td>
                                    <td>
                                        <div class="sr-action-group" id="actions-<?php echo $app->id; ?>" data-email-sent="<?php echo $locked ? 1 : 0; ?>">
                                            <button class="btn btn-sm btn-ai" onclick="srAnalyzeCV(<?php echo $app->id; ?>)" title="Phân tích AI">
                                                <i class="fas fa-robot"></i>
                                            </button>
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button"
                                                    data-bs-toggle="dropdown" <?php echo $locked ? 'disabled' : ''; ?>>
                                                    Đánh giá
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li><button class="dropdown-item" onclick="srChangeStatus(<?php echo $app->id; ?>, 'suitable')">
                                                        <i class="fas fa-circle-check me-2 text-success"></i>Phù hợp
                                                    </button></li>
                                                    <li><button class="dropdown-item" onclick="srChangeStatus(<?php echo $app->id; ?>, 'considering')">
                                                        <i class="fas fa-circle-question me-2 text-warning"></i>Cần xem xét
                                                    </button></li>
                                                    <li><button class="dropdown-item" onclick="srChangeStatus(<?php echo $app->id; ?>, 'unsuitable')">
                                                        <i class="fas fa-circle-xmark me-2 text-danger"></i>Chưa phù hợp
                                                    </button></li>
                                                </ul>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include SR_PLUGIN_DIR . 'admin/views/ai-result.php'; ?>
<?php include SR_PLUGIN_DIR . 'admin/views/job-modal.php'; ?>
