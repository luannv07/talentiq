<?php if (!defined('ABSPATH')) exit; ?>

<div class="sr-page">
    <div class="sr-topbar">
        <span class="sr-topbar-brand"><i class="fas fa-brain"></i> TalentIQ <span class="sr-brand-dot"></span></span>
        <nav class="sr-topbar-nav">
            <a href="<?php echo admin_url('admin.php?page=smart-recruitment'); ?>">
                <i class="fas fa-chart-pie"></i>Dashboard
            </a>
            <a href="<?php echo admin_url('admin.php?page=sr-jobs'); ?>" class="active">
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
            <h4>
                <span class="sr-page-icon"><i class="fas fa-briefcase"></i></span>
                Vị trí tuyển dụng
            </h4>
            <button class="btn btn-primary btn-sm" onclick="srOpenJobModal(null)">
                <i class="fas fa-plus me-1"></i>Thêm vị trí mới
            </button>
        </div>

        <!-- Job Table -->
        <div class="sr-panel">
            <div class="table-responsive">
                <table class="table table-hover table-bordered mb-0 sr-table">
                    <thead>
                        <tr>
                            <th>Vị trí</th>
                            <th class="sr-col-hide-sm">Kỹ năng</th>
                            <th class="sr-col-hide-sm">Địa điểm</th>
                            <th class="sr-col-hide-sm">Loại</th>
                            <th class="sr-col-hide-sm">Lương</th>
                            <th class="sr-col-hide-sm">SL</th>
                            <th>Trạng thái</th>
                            <th class="sr-col-hide-sm">Ngày tạo</th>
                            <th>Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($jobs)) : ?>
                            <tr><td colspan="9" class="text-center text-muted py-4">
                                <i class="fas fa-briefcase fa-2x d-block mb-2"></i>Chưa có vị trí nào.
                                <a href="#" onclick="srOpenJobModal(null); return false;" class="d-block mt-2">
                                    <i class="fas fa-plus me-1"></i>Thêm vị trí đầu tiên
                                </a>
                            </td></tr>
                        <?php else : ?>
                            <?php
                            $type_labels = [
                                'full-time' => 'Full-time',
                                'part-time' => 'Part-time',
                                'remote'    => 'Remote',
                                'intern'    => 'Intern',
                            ];
                            $type_class = [
                                'full-time' => 'sr-type-full',
                                'part-time' => 'sr-type-part',
                                'remote'    => 'sr-type-remote',
                                'intern'    => 'sr-type-intern',
                            ];
                            foreach ($jobs as $job) : ?>
                                <tr>
                                    <td>
                                        <div class="fw-semibold"><?php echo esc_html($job->title); ?></div>
                                        <?php if ($job->description) : ?>
                                            <div class="text-muted" style="font-size:12px">
                                                <?php echo esc_html(mb_substr(strip_tags($job->description), 0, 65)); ?>…
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="sr-col-hide-sm">
                                        <?php if ($job->skills) :
                                            foreach (array_slice(explode(',', $job->skills), 0, 3) as $sk) :
                                                $sk = trim($sk);
                                                if ($sk) : ?>
                                                    <span class="sr-skill-tag"><?php echo esc_html($sk); ?></span>
                                                <?php endif;
                                            endforeach;
                                        else : ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="sr-col-hide-sm small text-muted"><?php echo esc_html($job->location ?: '—'); ?></td>
                                    <td class="sr-col-hide-sm">
                                        <span class="<?php echo $type_class[$job->type] ?? 'sr-type-full'; ?>">
                                            <?php echo esc_html($type_labels[$job->type] ?? $job->type); ?>
                                        </span>
                                    </td>
                                    <td class="sr-col-hide-sm small fw-semibold" style="color:#059669"><?php echo esc_html($job->salary ?: '—'); ?></td>
                                    <td class="sr-col-hide-sm text-center fw-semibold"><?php echo intval($job->quantity ?? 1); ?></td>
                                    <td>
                                        <?php if ($job->status === 'active') : ?>
                                            <span class="badge sr-badge-suitable">Đang tuyển</span>
                                        <?php else : ?>
                                            <span class="badge sr-badge-submitted">Dừng tuyển</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="sr-col-hide-sm text-muted small"><?php echo date('d/m/Y', strtotime($job->created_at)); ?></td>
                                    <td>
                                        <div class="d-flex gap-1 flex-wrap">
                                            <?php
                                            $job_data = json_encode([
                                                'id'           => $job->id,
                                                'title'        => $job->title,
                                                'salary'       => $job->salary,
                                                'location'     => $job->location,
                                                'quantity'     => intval($job->quantity ?? 1),
                                                'type'         => $job->type,
                                                'skills'       => $job->skills,
                                                'description'  => $job->description,
                                                'requirements' => $job->requirements,
                                            ]);
                                            ?>
                                            <button class="btn btn-sm btn-outline-primary"
                                                onclick='srOpenJobModal(<?php echo esc_attr($job_data); ?>)'
                                                title="Chỉnh sửa">
                                                <i class="fas fa-pen"></i>
                                            </button>
                                            <button class="btn btn-sm <?php echo $job->status === 'active' ? 'btn-outline-success' : 'btn-outline-secondary'; ?>"
                                                onclick="srToggleJobStatus(<?php echo $job->id; ?>, this)"
                                                title="Bật/tắt tuyển dụng">
                                                <i class="fas <?php echo $job->status === 'active' ? 'fa-toggle-on' : 'fa-toggle-off'; ?> me-1"></i>
                                                <?php echo $job->status === 'active' ? 'Đang tuyển' : 'Dừng'; ?>
                                            </button>
                                            <a href="<?php echo admin_url('admin.php?page=sr-applications&filter_job=' . $job->id); ?>"
                                                class="btn btn-sm btn-outline-info" title="Xem ứng viên">
                                                <i class="fas fa-users"></i>
                                            </a>
                                            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="display:inline"
                                                onsubmit="return confirm('Xóa vị trí này? Thao tác không thể hoàn tác.')">
                                                <?php wp_nonce_field('sr_delete_job'); ?>
                                                <input type="hidden" name="action" value="sr_delete_job">
                                                <input type="hidden" name="job_id" value="<?php echo intval($job->id); ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Xóa">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
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

<?php include SR_PLUGIN_DIR . 'admin/views/job-modal.php'; ?>

<div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index:99999">
    <div id="srToast" class="toast align-items-center border-0 bg-dark text-white" role="alert">
        <div class="d-flex">
            <div class="toast-body" id="srToastBody"></div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>
