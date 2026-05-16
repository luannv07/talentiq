<?php if (!defined('ABSPATH')) exit; ?>

<?php
if (!function_exists('sr_sort_url')) {
    function sr_sort_url($col, $orderby, $order_raw, $base) {
        if ($orderby === $col) {
            $p = $order_raw === 'asc'
                ? array_merge($base, ['orderby' => $col, 'order' => 'desc'])
                : array_filter($base, fn($k) => !in_array($k, ['orderby', 'order']), ARRAY_FILTER_USE_KEY);
        } else {
            $p = array_merge($base, ['orderby' => $col, 'order' => 'asc']);
        }
        return 'admin.php?' . http_build_query($p);
    }
    function sr_sort_icon($col, $orderby, $order_raw) {
        if ($orderby !== $col) return '<i class="fas fa-sort text-muted" style="font-size:10px"></i>';
        return $order_raw === 'asc'
            ? '<i class="fas fa-sort-up text-primary" style="font-size:10px"></i>'
            : '<i class="fas fa-sort-down text-primary" style="font-size:10px"></i>';
    }
}

$base_params = array_filter([
    'page'          => 'sr-applications',
    'filter_name'   => $filter_name,
    'filter_email'  => $filter_email,
    'filter_exp'    => $filter_exp,
    'filter_job'    => $filter_job  ?: null,
    'filter_status' => $filter_status,
    'score_min'     => $filter_score_min !== '' ? $filter_score_min : null,
    'score_max'     => $filter_score_max !== '' ? $filter_score_max : null,
], fn($v) => $v !== null && $v !== '');

$export_url = admin_url('admin.php?' . http_build_query(array_merge($base_params, [
    'action' => 'export_csv',
    'nonce'  => wp_create_nonce('sr_export_csv'),
])));

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
            <a href="<?php echo admin_url('admin.php?page=smart-recruitment'); ?>">
                <i class="fas fa-chart-pie"></i>Dashboard
            </a>
            <a href="<?php echo admin_url('admin.php?page=sr-jobs'); ?>">
                <i class="fas fa-briefcase"></i>Vị trí
            </a>
            <a href="<?php echo admin_url('admin.php?page=sr-applications'); ?>" class="active">
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
            <h4>
                <span class="sr-page-icon"><i class="fas fa-users"></i></span>
                Danh sách ứng viên
            </h4>
            <a href="<?php echo esc_url($export_url); ?>" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-file-csv me-1"></i>Xuất CSV
            </a>
        </div>

        <!-- Filter Card -->
        <div class="sr-filter-card">
            <div class="sr-filter-toggle" data-bs-toggle="collapse" data-bs-target="#srFilterBody">
                <h6><i class="fas fa-filter"></i>Bộ lọc tìm kiếm</h6>
                <i class="fas fa-chevron-down text-muted small"></i>
            </div>
            <div class="collapse show" id="srFilterBody">
                <div class="sr-filter-body">
                    <form method="GET" action="">
                        <input type="hidden" name="page" value="sr-applications">
                        <div class="row g-2 mb-2">
                            <div class="col-12 col-sm-6 col-md-2">
                                <input type="text" name="filter_name" class="form-control form-control-sm"
                                    value="<?php echo esc_attr($filter_name); ?>" placeholder="Tên ứng viên...">
                            </div>
                            <div class="col-12 col-sm-6 col-md-2">
                                <input type="text" name="filter_email" class="form-control form-control-sm"
                                    value="<?php echo esc_attr($filter_email); ?>" placeholder="Email...">
                            </div>
                            <div class="col-12 col-sm-6 col-md-2">
                                <input type="text" name="filter_exp" class="form-control form-control-sm"
                                    value="<?php echo esc_attr($filter_exp); ?>" placeholder="Kinh nghiệm...">
                            </div>
                            <div class="col-12 col-sm-6 col-md-2">
                                <select name="filter_job" class="form-select form-select-sm">
                                    <option value="">Tất cả vị trí</option>
                                    <?php foreach ($jobs as $j) : ?>
                                        <option value="<?php echo $j->id; ?>" <?php selected($filter_job, $j->id); ?>>
                                            <?php echo esc_html($j->title); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12 col-sm-6 col-md-2">
                                <select name="filter_status" class="form-select form-select-sm">
                                    <option value="">Tất cả trạng thái</option>
                                    <option value="submitted"   <?php selected($filter_status, 'submitted'); ?>>Đã nộp</option>
                                    <option value="viewed"      <?php selected($filter_status, 'viewed'); ?>>Đã xem</option>
                                    <option value="suitable"    <?php selected($filter_status, 'suitable'); ?>>Phù hợp</option>
                                    <option value="considering" <?php selected($filter_status, 'considering'); ?>>Cần xem xét</option>
                                    <option value="unsuitable"  <?php selected($filter_status, 'unsuitable'); ?>>Chưa phù hợp</option>
                                </select>
                            </div>
                            <div class="col-12 col-sm-6 col-md-2">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">AI%</span>
                                    <input type="number" name="score_min" class="form-control" min="0" max="100"
                                        value="<?php echo esc_attr($filter_score_min); ?>" placeholder="Min">
                                    <input type="number" name="score_max" class="form-control" min="0" max="100"
                                        value="<?php echo esc_attr($filter_score_max); ?>" placeholder="Max">
                                </div>
                            </div>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="fas fa-magnifying-glass me-1"></i>Tìm kiếm
                            </button>
                            <a href="<?php echo admin_url('admin.php?page=sr-applications'); ?>" class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-rotate-left me-1"></i>Đặt lại
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Applicant Table -->
        <div class="sr-panel">
            <div class="table-responsive">
                <table class="table table-hover table-bordered mb-0 sr-table">
                    <thead>
                        <tr>
                            <th>
                                <a href="<?php echo esc_url(sr_sort_url('full_name', $orderby, $order_raw, $base_params)); ?>" class="sr-sort-link">
                                    Ứng viên <?php echo sr_sort_icon('full_name', $orderby, $order_raw); ?>
                                </a>
                            </th>
                            <th>
                                <a href="<?php echo esc_url(sr_sort_url('job_title', $orderby, $order_raw, $base_params)); ?>" class="sr-sort-link">
                                    Vị trí <?php echo sr_sort_icon('job_title', $orderby, $order_raw); ?>
                                </a>
                            </th>
                            <th class="sr-col-hide-sm">
                                <a href="<?php echo esc_url(sr_sort_url('experience', $orderby, $order_raw, $base_params)); ?>" class="sr-sort-link">
                                    Kinh nghiệm <?php echo sr_sort_icon('experience', $orderby, $order_raw); ?>
                                </a>
                            </th>
                            <th>
                                <a href="<?php echo esc_url(sr_sort_url('status', $orderby, $order_raw, $base_params)); ?>" class="sr-sort-link">
                                    Trạng thái <?php echo sr_sort_icon('status', $orderby, $order_raw); ?>
                                </a>
                            </th>
                            <th>
                                <a href="<?php echo esc_url(sr_sort_url('score', $orderby, $order_raw, $base_params)); ?>" class="sr-sort-link">
                                    Phù hợp <?php echo sr_sort_icon('score', $orderby, $order_raw); ?>
                                </a>
                            </th>
                            <th class="sr-col-hide-md">Nhận xét AI</th>
                            <th class="sr-col-hide-md">Thư giới thiệu</th>
                            <th class="sr-col-hide-sm">
                                <a href="<?php echo esc_url(sr_sort_url('created_at', $orderby, $order_raw, $base_params)); ?>" class="sr-sort-link">
                                    Ngày nộp <?php echo sr_sort_icon('created_at', $orderby, $order_raw); ?>
                                </a>
                            </th>
                            <th>Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($applications)) : ?>
                            <tr><td colspan="9" class="text-center text-muted py-4">
                                <i class="fas fa-inbox fa-2x d-block mb-2"></i>Chưa có ứng viên nào
                            </td></tr>
                        <?php else : ?>
                            <?php foreach ($applications as $app) :
                                $locked = !empty($app->email_sent) && $app->email_sent == 1;
                            ?>
                                <tr id="row-<?php echo $app->id; ?>" data-email-sent="<?php echo $locked ? 1 : 0; ?>">
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="sr-avatar"><?php echo strtoupper(substr($app->full_name, 0, 2)); ?></div>
                                            <div>
                                                <div class="fw-semibold"><?php echo esc_html($app->full_name); ?></div>
                                                <div class="text-muted" style="font-size:12px"><?php echo esc_html($app->email); ?></div>
                                                <?php if ($app->phone) : ?>
                                                    <div class="text-muted" style="font-size:12px">
                                                        <i class="fas fa-phone me-1"></i><?php echo esc_html($app->phone); ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo esc_html($app->job_title); ?></td>
                                    <td class="sr-col-hide-sm text-muted small"><?php echo esc_html($app->experience ?: '—'); ?></td>
                                    <td>
                                        <span class="badge <?php echo $status_badge[$app->status] ?? 'sr-badge-submitted'; ?>"
                                              id="status-<?php echo $app->id; ?>">
                                            <?php echo $status_labels[$app->status] ?? esc_html($app->status); ?>
                                        </span>
                                        <?php if ($locked) : ?>
                                            <i class="fas fa-lock text-muted ms-1 small" title="Đã gửi email"></i>
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
                                            <span class="text-muted small" id="score-wrap-<?php echo $app->id; ?>">Chưa phân tích</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="sr-col-hide-md">
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
                                                <?php echo esc_html(mb_substr($app->recommendation, 0, 55)); ?><i class="fas fa-ellipsis ms-1"></i>
                                            </a>
                                        <?php else : ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="sr-col-hide-md">
                                        <?php if ($app->cover_letter) : ?>
                                            <a href="#" class="sr-review-link"
                                                data-bs-toggle="modal" data-bs-target="#srCoverLetterModal"
                                                data-content="<?php echo esc_attr($app->cover_letter); ?>"
                                                data-name="<?php echo esc_attr($app->full_name); ?>"
                                                onclick="srShowCoverLetter(this); return false;">
                                                <?php echo esc_html(mb_substr($app->cover_letter, 0, 40)); ?><i class="fas fa-ellipsis ms-1"></i>
                                            </a>
                                        <?php else : ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="sr-col-hide-sm text-muted small"><?php echo date('d/m/Y H:i', strtotime($app->created_at)); ?></td>
                                    <td>
                                        <div class="sr-action-group" id="actions-<?php echo $app->id; ?>" data-email-sent="<?php echo $locked ? 1 : 0; ?>">
                                            <button class="btn btn-sm btn-ai" onclick="srAnalyzeCV(<?php echo $app->id; ?>)" title="Phân tích AI">
                                                <i class="fas fa-robot"></i>
                                            </button>
                                            <?php if ($app->cv_path) : ?>
                                                <a href="<?php echo esc_url(str_replace(ABSPATH, site_url('/'), $app->cv_path)); ?>"
                                                    target="_blank" class="btn btn-sm btn-outline-danger" title="Xem CV">
                                                    <i class="fas fa-file-pdf"></i>
                                                </a>
                                            <?php endif; ?>
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button"
                                                    data-bs-toggle="dropdown" <?php echo $locked ? 'disabled' : ''; ?>>
                                                    <i class="fas fa-user-check me-1"></i>Đánh giá
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

<!-- Cover Letter Modal -->
<div class="modal fade" id="srCoverLetterModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title fw-bold"><i class="fas fa-envelope-open-text me-2 text-primary"></i>Thư giới thiệu — <span id="srCLName"></span></h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p id="srCLContent" style="white-space:pre-wrap;font-size:14px;line-height:1.7"></p>
            </div>
        </div>
    </div>
</div>

<script>
function srShowCoverLetter(el) {
    document.getElementById('srCLName').textContent    = el.dataset.name    || '';
    document.getElementById('srCLContent').textContent = el.dataset.content || '';
}
</script>

<?php include SR_PLUGIN_DIR . 'admin/views/ai-result.php'; ?>
