<?php if (!defined('ABSPATH')) exit; ?>
<?php
/**
 * View: Job Listing
 * Mô tả: Hiển thị danh sách công việc công khai và thanh tìm kiếm/bộ lọc.
 * Author: luannv
 */
?>

<div class="sr-wrap">
    <div class="sr-hero py-5 text-center">
        <div class="sr-hero-label mb-2">
            <i class="fas fa-star me-1"></i> Smart Recruitment
        </div>
        <h1 class="sr-hero-title mb-3">Cơ hội việc làm <span>dành cho bạn</span></h1>
        <p class="sr-hero-sub">Nộp hồ sơ ngay — CV của bạn sẽ được AI phân tích tự động</p>
    </div>

    <form method="GET" action="" class="sr-filter-bar mb-4">
        <?php
        foreach ( $_GET as $k => $v ) {
            if ( ! in_array( $k, ['sr_search', 'sr_type', 'sr_location'], true ) ) {
                echo '<input type="hidden" name="' . esc_attr($k) . '" value="' . esc_attr($v) . '">';
            }
        }
        ?>
        <div class="row g-2">
            <div class="col-md-5">
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0">
                        <i class="fas fa-magnifying-glass text-muted"></i>
                    </span>
                    <input type="text" name="sr_search" class="form-control border-start-0"
                        value="<?php echo esc_attr($search); ?>" placeholder="Tìm kiếm vị trí công việc...">
                </div>
            </div>
            <div class="col-md-3">
                <select name="sr_type" class="form-select">
                    <option value="">Tất cả loại hình</option>
                    <?php foreach (['full-time' => 'Full-time', 'part-time' => 'Part-time', 'remote' => 'Remote', 'intern' => 'Intern'] as $v => $l) : ?>
                        <option value="<?php echo $v; ?>" <?php selected($f_type, $v); ?>><?php echo $l; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <select name="sr_location" class="form-select">
                    <option value="">Tất cả địa điểm</option>
                    <?php foreach ($locations as $loc) : ?>
                        <option value="<?php echo esc_attr($loc); ?>" <?php selected($f_loc, $loc); ?>><?php echo esc_html($loc); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-1">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-search"></i>
                </button>
            </div>
        </div>
        <?php if ($search || $f_type || $f_loc) : ?>
            <div class="mt-2">
                <a href="<?php echo esc_url(get_permalink()); ?>" class="small text-muted">
                    <i class="fas fa-rotate-left me-1"></i>Xóa bộ lọc
                </a>
            </div>
        <?php endif; ?>
    </form>

    <?php if (empty($jobs)) : ?>
        <div class="sr-empty">
            <i class="fas fa-briefcase fa-2x mb-3 d-block text-muted"></i>
            <?php echo ($search || $f_type || $f_loc) ? 'Không tìm thấy vị trí nào phù hợp.' : 'Hiện chưa có vị trí tuyển dụng nào.'; ?>
        </div>
    <?php else : ?>
        <?php if ($search || $f_type || $f_loc) : ?>
            <p class="text-muted small mb-3">
                <i class="fas fa-circle-info me-1"></i>Tìm thấy <strong><?php echo count($jobs); ?></strong> vị trí
            </p>
        <?php endif; ?>
        <div class="row row-cols-1 row-cols-md-3 g-4">
            <?php foreach ($jobs as $job) :
                $detail_url = add_query_arg('job_id', $job->id, get_permalink());
            ?>
                <div class="col">
                    <div class="card h-100 sr-job-card border-0 shadow-sm">
                        <div class="card-body d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div class="sr-company-avatar">
                                    <?php echo strtoupper(substr($job->title, 0, 1)); ?>
                                </div>
                                <span class="badge bg-success-subtle text-success border border-success-subtle">
                                    <i class="fas fa-circle me-1" style="font-size:8px"></i>Đang tuyển
                                </span>
                            </div>

                            <h5 class="sr-job-title fw-bold mb-2"><?php echo esc_html($job->title); ?></h5>

                            <div class="sr-job-meta d-flex flex-wrap gap-2 mb-2">
                                <?php if ($job->location) : ?>
                                    <span class="small text-muted">
                                        <i class="fas fa-location-dot me-1"></i><?php echo esc_html($job->location); ?>
                                    </span>
                                <?php endif; ?>
                                <?php if ($job->type) : ?>
                                    <span class="small text-muted">
                                        <i class="fas fa-briefcase me-1"></i><?php echo esc_html($job->type); ?>
                                    </span>
                                <?php endif; ?>
                                <?php if (!empty($job->quantity) && $job->quantity > 1) : ?>
                                    <span class="small text-muted">
                                        <i class="fas fa-users me-1"></i><?php echo intval($job->quantity); ?> vị trí
                                    </span>
                                <?php endif; ?>
                            </div>

                            <?php if ($job->skills) : ?>
                                <div class="d-flex flex-wrap gap-1 mb-2">
                                    <?php foreach (array_slice(explode(',', $job->skills), 0, 4) as $sk) :
                                        $sk = trim($sk);
                                        if ($sk) :
                                    ?>
                                        <span class="badge bg-light text-dark border" style="font-size:11px"><?php echo esc_html($sk); ?></span>
                                    <?php endif; endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <p class="sr-job-desc text-muted small flex-grow-1">
                                <?php echo wp_trim_words($job->description, 20, '<i class="fas fa-ellipsis ms-1"></i>'); ?>
                            </p>

                            <div class="card-footer bg-transparent border-top d-flex justify-content-between align-items-center mt-2 px-0 pb-0">
                                <?php if ($job->salary) : ?>
                                    <span class="sr-salary fw-semibold text-success small">
                                        <i class="fas fa-money-bill-wave me-1"></i><?php echo esc_html($job->salary); ?>
                                    </span>
                                <?php else : ?>
                                    <span></span>
                                <?php endif; ?>
                                <div class="d-flex gap-2">
                                    <a href="<?php echo esc_url($detail_url); ?>" class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-circle-info me-1"></i>Chi tiết
                                    </a>
                                    <a href="<?php echo esc_url(add_query_arg('apply_job', $job->id, get_permalink())); ?>"
                                        class="btn btn-primary btn-sm">
                                        Ứng tuyển <i class="fas fa-arrow-right ms-1"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
