<?php if (!defined('ABSPATH')) exit; ?>

<div class="sr-wrap py-4">
    <a href="?" class="btn btn-sm btn-outline-secondary mb-3">
        <i class="fas fa-arrow-left me-1"></i>Quay lại danh sách
    </a>

    <div class="card shadow-sm border-0 mx-auto" style="max-width:640px">
        <div class="card-body p-4">
            <div class="mb-4">
                <div class="d-inline-flex align-items-center gap-2 bg-light border rounded px-3 py-2 mb-3 small text-muted">
                    <div class="sr-job-ref-avatar"><?php echo strtoupper(substr($selected_job->title, 0, 1)); ?></div>
                    <?php echo esc_html($selected_job->title); ?>
                </div>
                <h4 class="fw-bold mb-1">Nộp hồ sơ ứng tuyển</h4>
                <p class="text-muted small mb-0">Điền đầy đủ thông tin bên dưới. CV của bạn sẽ được AI phân tích tự động.</p>
            </div>

            <div id="srApplyForm">
                <input type="hidden" id="sr_job_id" value="<?php echo intval($selected_job->id); ?>">

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small">Họ và tên <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="sr_full_name" placeholder="Nguyễn Văn A">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small">Email <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="sr_email" placeholder="example@gmail.com">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small">Số điện thoại</label>
                        <input type="tel" class="form-control" id="sr_phone" placeholder="0912 345 678">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small">Số năm kinh nghiệm</label>
                        <input type="text" class="form-control" id="sr_experience" placeholder="2 năm">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold small">Upload CV (PDF) <span class="text-danger">*</span></label>
                    <div class="alert alert-info py-2 small mb-2">
                        <i class="fas fa-circle-info me-1"></i>
                        Chỉ nhận file PDF, tối đa 10MB. CV sẽ được tự động đọc để điền thông tin.
                    </div>
                    <div class="sr-upload-zone" id="srUploadZone">
                        <input type="file" id="sr_cv_file" accept=".pdf">
                        <div class="sr-upload-icon mb-2">
                            <i class="fas fa-file-pdf fa-2x text-danger"></i>
                        </div>
                        <div class="sr-upload-text small">
                            <strong>Kéo thả hoặc click để upload CV</strong><br>
                            Chỉ nhận file PDF, tối đa 10MB
                        </div>
                        <div class="sr-file-name" id="srFileName"></div>
                        <div class="sr-cv-loading" id="srCVLoading" style="display:none">
                            <i class="fas fa-spinner fa-spin me-1"></i>Đang đọc CV của bạn<i class="fas fa-ellipsis ms-1"></i>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold small">Thư giới thiệu <span class="text-muted">(tùy chọn)</span></label>
                    <textarea class="form-control" id="sr_cover_letter" rows="4"
                        placeholder="Giới thiệu ngắn về bản thân và lý do muốn ứng tuyển..."></textarea>
                </div>

                <div class="alert alert-danger" id="srError" style="display:none"></div>

                <button class="btn btn-primary btn-lg w-100" id="srSubmitBtn" onclick="srSubmitForm()">
                    <i class="fas fa-paper-plane me-2"></i>Nộp hồ sơ ngay
                </button>
            </div>

            <div class="alert alert-success text-center py-4" id="srSuccess" style="display:none">
                <i class="fas fa-circle-check fa-3x text-success mb-3 d-block"></i>
                <h5 class="fw-bold">Nộp hồ sơ thành công!</h5>
                <p class="text-muted mb-3">Chúng tôi đã nhận được hồ sơ của bạn.<br>
                Email xác nhận đã được gửi. HR sẽ phản hồi trong vòng 3-5 ngày làm việc.</p>
                <a href="?" class="btn btn-outline-primary btn-sm">
                    <i class="fas fa-arrow-left me-1"></i>Xem thêm vị trí khác
                </a>
            </div>
        </div>
    </div>
</div>
