/**
 * Public JS — xử lý form nộp hồ sơ và trích xuất CV
 * Mô tả: Client-side validation, upload CV, và auto-fill dữ liệu từ CV
 * Author: luannv
 */

function srSubmitForm() {
  var btn = document.getElementById("srSubmitBtn");
  var error = document.getElementById("srError");

  var fullName = document.getElementById("sr_full_name").value.trim();
  var email = document.getElementById("sr_email").value.trim();
  var jobId = document.getElementById("sr_job_id").value;

  error.style.display = "none";

  if (!fullName) {
    srShowError("Vui lòng nhập họ và tên");
    return;
  }
  if (!email || !email.includes("@")) {
    srShowError("Vui lòng nhập email hợp lệ");
    return;
  }

  btn.disabled = true;
  btn.innerHTML =
    '<i class="fas fa-spinner fa-spin me-2"></i>Đang nộp hồ sơ...';

  var formData = new FormData();
  formData.append("action", "sr_submit_application");
  formData.append("nonce", SR.nonce);
  formData.append("job_id", jobId);
  formData.append("full_name", fullName);
  formData.append("email", email);
  formData.append("phone", document.getElementById("sr_phone").value.trim());
  formData.append(
    "experience",
    document.getElementById("sr_experience").value.trim(),
  );
  formData.append(
    "cover_letter",
    document.getElementById("sr_cover_letter").value.trim(),
  );

  var cvFile = document.getElementById("sr_cv_file").files[0];
  if (cvFile) formData.append("cv_file", cvFile);

  jQuery.ajax({
    url: SR.ajax_url,
    type: "POST",
    data: formData,
    processData: false,
    contentType: false,
    success: function (res) {
      if (res.success) {
        document.getElementById("srApplyForm").style.display = "none";
        document.getElementById("srSuccess").style.display = "block";
      } else {
        srShowError(res.data || "Có lỗi xảy ra, vui lòng thử lại");
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-paper-plane me-2"></i>Nộp hồ sơ ngay';
      }
    },
    error: function () {
      srShowError("Lỗi kết nối, vui lòng thử lại");
      btn.disabled = false;
      btn.textContent = "Nộp hồ sơ ngay ✦";
    },
  });
}

function srShowError(msg) {
  var error = document.getElementById("srError");
  error.innerHTML = '<i class="fas fa-triangle-exclamation me-2"></i>' + msg;
  error.style.display = "block";
  error.scrollIntoView({ behavior: "smooth", block: "nearest" });
}

// Kiểm tra file và auto-fill từ CV
document.addEventListener("DOMContentLoaded", function () {
  var zone = document.getElementById("srUploadZone");
  var input = document.getElementById("sr_cv_file");
  if (!zone || !input) return;

  input.addEventListener("change", function () {
    var file = this.files[0];
    if (!file) return;

    // Kiểm tra loại file (chỉ PDF)
    var isPDF =
      file.type === "application/pdf" ||
      file.name.toLowerCase().endsWith(".pdf");
    if (!isPDF) {
      srShowError("Only PDF files are accepted.");
      this.value = "";
      return;
    }

    // Kiểm tra dung lượng file (tối đa 10 MB)
    if (file.size > 10 * 1024 * 1024) {
      srShowError("File size must not exceed 10MB.");
      this.value = "";
      return;
    }

    // Hiển thị tên file đã chọn
    zone.classList.add("has-file");
    var fn = document.getElementById("srFileName");
    fn.innerHTML = '<i class="fas fa-check text-success me-1"></i>' + file.name;
    fn.style.display = "block";

    // Tự động điền form bằng dữ liệu trích xuất từ CV
    srExtractCV(file);
  });
});

// ── CV Extraction & Auto-fill ─────────────────────────────────────────────────

function srExtractCV(file) {
  var loading = document.getElementById("srCVLoading");
  var error = document.getElementById("srError");
  error.style.display = "none";
  if (loading) loading.style.display = "block";

  var formData = new FormData();
  formData.append("action", "sr_extract_cv");
  formData.append("nonce", SR.nonce);
  formData.append("cv_file", file);

  jQuery.ajax({
    url: SR.ajax_url,
    type: "POST",
    data: formData,
    processData: false,
    contentType: false,
    success: function (res) {
      if (loading) loading.style.display = "none";
      if (!res.success || !res.data) return;

      var data = res.data;
      var filled = [];

      if (data.full_name) {
        if (srFillField("sr_full_name", data.full_name))
          filled.push("full_name");
      }
      if (data.email) {
        if (srFillField("sr_email", data.email)) filled.push("email");
      }
      if (data.phone) {
        if (srFillField("sr_phone", data.phone)) filled.push("phone");
      }
      if (data.experience) {
        if (srFillField("sr_experience", data.experience))
          filled.push("experience");
      }

      if (filled.length > 0) srShowAutoFillNotice();
    },
    error: function () {
      if (loading) loading.style.display = "none";
    },
  });
}

// Fill a field only if it is currently empty; return true if filled
function srFillField(fieldId, value) {
  var el = document.getElementById(fieldId);
  if (!el || el.value.trim() !== "") return false;
  el.value = value;
  srHighlightField(el);
  return true;
}

// Briefly highlight auto-filled field with yellow border
function srHighlightField(el) {
  el.style.borderColor = "#FEF08A";
  el.style.boxShadow = "0 0 0 3px rgba(254, 240, 138, 0.4)";
  setTimeout(function () {
    el.style.borderColor = "";
    el.style.boxShadow = "";
  }, 2000);
}

function srShowAutoFillNotice() {
  var notice = document.getElementById("srAutoFillNotice");
  if (!notice) {
    notice = document.createElement("div");
    notice.id = "srAutoFillNotice";
    notice.className = "alert alert-success small mb-3";
    notice.innerHTML =
      '<i class="fas fa-wand-magic-sparkles me-2"></i>Thông tin đã được điền tự động từ CV của bạn. Vui lòng kiểm tra lại trước khi nộp.';
    var form = document.getElementById("srApplyForm");
    if (form)
      form.insertBefore(notice, form.querySelector(".row, .sr-form-row"));
  }
  notice.style.display = "block";
}
