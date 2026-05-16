<?php if (!defined('ABSPATH')) exit; ?>

<!-- AI Result Modal -->
<div class="modal fade" id="srAIModal" tabindex="-1" aria-labelledby="srModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title" id="srModalTitle">
                    <i class="fas fa-robot me-2 text-primary"></i>Kết quả phân tích AI — Gemini
                </h5>
                <button type="button" class="btn-close" id="srAIModalClose" aria-label="Đóng"></button>
            </div>
            <div class="modal-body">
                <!-- Loading steps -->
                <div id="srAILoading">
                    <div id="srStepList" class="sr-step-list"></div>

                    <!-- Timeout warning -->
                    <div id="srTimeoutMsg" class="alert alert-warning mt-3 d-none">
                        <p class="mb-2 small">Phân tích mất nhiều thời gian hơn dự kiến. Bạn có thể đóng và tiếp tục làm việc —
                        kết quả sẽ tự động hiện khi hoàn tất.</p>
                        <div class="d-flex gap-2">
                            <button class="btn btn-sm btn-outline-warning" onclick="srDismissTimeout()">
                                <i class="fas fa-clock me-1"></i>Tiếp tục chờ
                            </button>
                            <button class="btn btn-sm btn-warning text-white" onclick="srRunBackground()">
                                <i class="fas fa-arrow-right me-1"></i>Chạy nền
                            </button>
                        </div>
                    </div>

                    <!-- Error -->
                    <div id="srAIError" class="alert alert-danger mt-3 d-none"></div>
                </div>

                <!-- Result -->
                <div class="sr-ai-result" id="srAIResult">
                    <div class="text-center mb-4">
                        <div class="sr-score-circle mx-auto mb-2" id="srScoreCircle">—</div>
                        <div class="text-muted small fw-semibold" id="srScoreLabel">Đang phân tích...</div>
                    </div>

                    <div class="sr-ai-section strengths">
                        <div class="sr-ai-section-label">
                            <i class="fas fa-circle-check"></i>Điểm mạnh
                        </div>
                        <div class="sr-ai-tags" id="srStrengths"></div>
                    </div>

                    <div class="sr-ai-section weaknesses">
                        <div class="sr-ai-section-label">
                            <i class="fas fa-triangle-exclamation"></i>Điểm cần lưu ý
                        </div>
                        <div class="sr-ai-tags" id="srWeaknesses"></div>
                    </div>

                    <div class="sr-ai-section recommendation mb-4">
                        <div class="sr-ai-section-label">
                            <i class="fas fa-lightbulb"></i>Khuyến nghị
                        </div>
                        <div class="sr-ai-recommend" id="srRecommendation"></div>
                    </div>

                    <!-- AI Suggestion Section -->
                    <div class="sr-ai-suggestion mb-3">
                        <div class="sr-ai-section-title">
                            <i class="fas fa-lightbulb me-2 text-warning"></i>Gợi ý từ AI — Độ phù hợp
                        </div>
                        <div class="sr-suggestion-options">
                            <label class="sr-suggestion-option suitable">
                                <input type="radio" name="sr_suggestion" value="suitable">
                                <i class="fas fa-circle-check"></i> Phù hợp
                            </label>
                            <label class="sr-suggestion-option considering">
                                <input type="radio" name="sr_suggestion" value="considering">
                                <i class="fas fa-circle-question"></i> Cần xem xét
                            </label>
                            <label class="sr-suggestion-option unsuitable">
                                <input type="radio" name="sr_suggestion" value="unsuitable">
                                <i class="fas fa-circle-xmark"></i> Chưa phù hợp
                            </label>
                        </div>
                        <p class="sr-suggestion-note">HR có thể chọn lại trước khi xác nhận trạng thái bên dưới.</p>
                    </div>

                    <!-- Status action buttons -->
                    <div class="d-flex gap-2 flex-wrap mt-3" id="srModalActions">
                        <button class="btn btn-sm btn-suitable flex-fill" id="srModalStatus_suitable">
                            <i class="fas fa-circle-check me-1"></i>Phù hợp
                        </button>
                        <button class="btn btn-sm btn-considering flex-fill" id="srModalStatus_considering">
                            <i class="fas fa-circle-question me-1"></i>Cần xem xét
                        </button>
                        <button class="btn btn-sm btn-unsuitable flex-fill" id="srModalStatus_unsuitable">
                            <i class="fas fa-circle-xmark me-1"></i>Chưa phù hợp
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Email Confirmation Modal -->
<div class="modal fade" id="srEmailModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-envelope me-2 text-primary"></i>Gửi email thông báo
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p id="srEmailModalMsg" class="text-muted mb-3 small"></p>
                <div class="d-grid gap-2">
                    <button class="btn btn-primary" id="srEmailOpt1">
                        <i class="fas fa-paper-plane me-2"></i>Gửi theo mẫu
                    </button>
                    <button class="btn btn-outline-secondary" id="srEmailOpt2">
                        <i class="fas fa-pen me-2"></i>Soạn lại
                    </button>
                    <button class="btn btn-outline-danger" id="srEmailOpt3">
                        <i class="fas fa-ban me-2"></i>Không gửi
                    </button>
                </div>
                <div id="srCustomEmailWrap" class="mt-3" style="display:none">
                    <label class="form-label fw-semibold small">Nội dung email tùy chỉnh</label>
                    <textarea class="form-control" id="srCustomEmailContent" rows="5"
                        placeholder="Nhập nội dung email..."></textarea>
                    <button class="btn btn-primary mt-2 w-100" id="srSendCustomEmail">
                        <i class="fas fa-paper-plane me-2"></i>Gửi email tùy chỉnh
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap Toast Container -->
<div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index:99999">
    <div id="srToast" class="toast align-items-center border-0 bg-dark text-white" role="alert">
        <div class="d-flex">
            <div class="toast-body" id="srToastBody"></div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>
