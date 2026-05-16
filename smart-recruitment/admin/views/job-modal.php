<?php if (!defined('ABSPATH')) exit; ?>

<!-- Job Add/Edit Modal -->
<div class="modal fade" id="srJobModal" tabindex="-1" aria-labelledby="srJobModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="srJobModalTitle">
                    <i class="fas fa-briefcase me-2 text-primary"></i>Thêm vị trí mới
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="srJobId">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small">Tên vị trí <span class="text-danger">*</span></label>
                        <input type="text" id="srJobTitle" class="form-control" placeholder="Frontend Developer">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold small">Số lượng tuyển</label>
                        <input type="number" id="srJobQuantity" class="form-control" min="1" value="1">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold small">Loại công việc</label>
                        <select id="srJobType" class="form-select">
                            <option value="full-time">Full-time</option>
                            <option value="part-time">Part-time</option>
                            <option value="remote">Remote</option>
                            <option value="intern">Intern</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small">Mức lương</label>
                        <input type="text" id="srJobSalary" class="form-control" placeholder="15 - 25 triệu">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small">Địa điểm</label>
                        <input type="text" id="srJobLocation" class="form-control" placeholder="Hà Nội">
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold small">Kỹ năng yêu cầu</label>
                        <input type="text" id="srJobSkills" class="form-control"
                            placeholder="React, TypeScript, Git (phân cách bằng dấu phẩy)">
                        <div class="form-text"><i class="fas fa-circle-info me-1"></i>AI sẽ dùng danh sách này để chấm điểm CV</div>
                    </div>
                    <div class="col-12">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label fw-semibold small mb-0">Mô tả công việc</label>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="srGenJDBtn" onclick="srGenerateJD()">
                                <i class="fas fa-wand-magic-sparkles me-1"></i>Tạo JD bằng AI
                            </button>
                        </div>
                        <textarea id="srJobDescription" class="form-control" rows="6"
                            placeholder="Mô tả chi tiết công việc..."></textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold small">Yêu cầu kỹ năng chi tiết <span class="text-muted fw-normal">(tự động từ AI hoặc nhập tay)</span></label>
                        <textarea id="srJobRequirements" class="form-control" rows="4"
                            placeholder="AI sẽ điền khi nhấn Tạo JD bằng AI..."></textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold small">Quyền lợi <span class="text-muted fw-normal">(cố định)</span></label>
                        <textarea class="form-control text-muted" rows="5" readonly style="background:#f8f9fa;font-size:13px">- Lương cạnh tranh, xét tăng lương định kỳ
- Môi trường làm việc trẻ trung, năng động
- Bảo hiểm sức khỏe, BHXH đầy đủ theo quy định
- Thưởng lễ, Tết, sinh nhật
- Cơ hội đào tạo và phát triển nghề nghiệp</textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-xmark me-1"></i>Hủy
                </button>
                <button type="button" class="btn btn-primary" id="srJobSaveBtn" onclick="srSaveJobModal()">
                    <i class="fas fa-floppy-disk me-1"></i>Lưu
                </button>
            </div>
        </div>
    </div>
</div>
