<?php if (!defined('ABSPATH')) exit; ?>

<div class="sr-wrap py-4">
    <a href="<?php echo esc_url(get_permalink()); ?>" class="sr-back-btn mb-3">
        <i class="fas fa-arrow-left me-1"></i>Quay lại danh sách
    </a>

    <div class="row g-4">
        <div class="col-md-8">
            <!-- Job Header -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <div class="d-flex align-items-start gap-3 mb-3">
                        <div class="sr-company-avatar" style="width:52px;height:52px;font-size:22px">
                            <?php echo strtoupper(substr($view_job->title, 0, 1)); ?>
                        </div>
                        <div>
                            <h2 class="fw-bold mb-1" style="font-size:22px"><?php echo esc_html($view_job->title); ?></h2>
                            <div class="d-flex flex-wrap gap-3 text-muted small">
                                <?php if ($view_job->location) : ?>
                                    <span><i class="fas fa-location-dot me-1"></i><?php echo esc_html($view_job->location); ?></span>
                                <?php endif; ?>
                                <?php if ($view_job->type) : ?>
                                    <span><i class="fas fa-briefcase me-1"></i><?php echo esc_html($view_job->type); ?></span>
                                <?php endif; ?>
                                <?php if (!empty($view_job->quantity)) : ?>
                                    <span><i class="fas fa-users me-1"></i><?php echo intval($view_job->quantity); ?> vị trí</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <?php if ($view_job->salary) : ?>
                        <div class="d-inline-flex align-items-center gap-2 bg-success-subtle text-success border border-success-subtle rounded px-3 py-2 mb-3">
                            <i class="fas fa-money-bill-wave"></i>
                            <strong><?php echo esc_html($view_job->salary); ?></strong>
                        </div>
                    <?php endif; ?>

                    <?php if ($view_job->skills) : ?>
                        <div class="d-flex flex-wrap gap-2 mb-3">
                            <?php foreach (explode(',', $view_job->skills) as $sk) :
                                $sk = trim($sk);
                                if ($sk) : ?>
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle"><?php echo esc_html($sk); ?></span>
                                <?php endif;
                            endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <a href="<?php echo esc_url(add_query_arg('apply_job', $view_job->id, get_permalink())); ?>"
                        class="btn btn-primary">
                        <i class="fas fa-paper-plane me-2"></i>Ứng tuyển ngay
                    </a>
                </div>
            </div>

            <!-- Description -->
            <?php if ($view_job->description) : ?>
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-4">
                        <h5 class="fw-bold mb-3"><i class="fas fa-file-lines me-2 text-primary"></i>Mô tả công việc</h5>
                        <div style="font-size:15px;line-height:1.8;white-space:pre-wrap"><?php echo esc_html($view_job->description); ?></div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Requirements -->
            <?php if ($view_job->requirements) : ?>
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-4">
                        <h5 class="fw-bold mb-3"><i class="fas fa-list-check me-2 text-primary"></i>Yêu cầu ứng viên</h5>
                        <div style="font-size:15px;line-height:1.8;white-space:pre-wrap"><?php echo esc_html($view_job->requirements); ?></div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Benefits -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-3"><i class="fas fa-gift me-2 text-primary"></i>Quyền lợi</h5>
                    <ul class="list-unstyled mb-0" style="font-size:15px;line-height:2">
                        <li><i class="fas fa-circle-check text-success me-2"></i>Lương cạnh tranh, xét tăng lương định kỳ</li>
                        <li><i class="fas fa-circle-check text-success me-2"></i>Môi trường làm việc trẻ trung, năng động</li>
                        <li><i class="fas fa-circle-check text-success me-2"></i>Bảo hiểm sức khỏe, BHXH đầy đủ theo quy định</li>
                        <li><i class="fas fa-circle-check text-success me-2"></i>Thưởng lễ, Tết, sinh nhật</li>
                        <li><i class="fas fa-circle-check text-success me-2"></i>Cơ hội đào tạo và phát triển nghề nghiệp</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-md-4">
            <div class="card border-0 shadow-sm sticky-top" style="top:20px">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3">Ứng tuyển vị trí này</h6>
                    <p class="text-muted small mb-3">CV của bạn sẽ được AI phân tích và đánh giá tự động.</p>
                    <a href="<?php echo esc_url(add_query_arg('apply_job', $view_job->id, get_permalink())); ?>"
                        class="btn btn-primary w-100 mb-2">
                        <i class="fas fa-paper-plane me-2"></i>Ứng tuyển ngay
                    </a>
                    <a href="<?php echo esc_url(get_permalink()); ?>" class="btn btn-outline-secondary w-100 btn-sm">
                        <i class="fas fa-arrow-left me-1"></i>Xem vị trí khác
                    </a>

                    <hr>
                    <div class="small text-muted">
                        <div class="mb-1"><i class="fas fa-location-dot me-2 text-primary"></i><?php echo esc_html($view_job->location ?: '—'); ?></div>
                        <div class="mb-1"><i class="fas fa-briefcase me-2 text-primary"></i><?php echo esc_html($view_job->type ?: '—'); ?></div>
                        <?php if ($view_job->salary) : ?>
                            <div class="mb-1"><i class="fas fa-money-bill me-2 text-success"></i><?php echo esc_html($view_job->salary); ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
