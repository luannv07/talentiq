/**
 * Admin JS — quản lý trang admin cho Smart Recruitment
 * Mô tả: Xử lý tương tác AI, modal, bảng ứng viên, và thao tác CRUD cho job
 * Author: luannv
 */
var srCurrentAppId = null;
var srBgMode = false;
var srTimeoutTimer = null;
var srAnalysisDone = false;

document.addEventListener("DOMContentLoaded", function () {
  // Khôi phục kết quả AI tạm lưu trong sessionStorage (nếu có)
  for (var key in sessionStorage) {
    if (key.indexOf("sr_ai_") === 0) {
      try {
        var stored = JSON.parse(sessionStorage.getItem(key));
        var appId = stored.appId;
        sessionStorage.removeItem(key);
        window["__sr_result_" + appId] = stored.data;
        srUpdateTableRow(appId, stored.data);
        (function (id) {
          setTimeout(function () {
            srShowToast(
              '<i class="fas fa-robot me-2"></i>Phân tích hoàn tất! ' +
                '<a href="#" onclick="srShowStoredResult(' +
                id +
                '); return false;" ' +
                'class="text-warning fw-semibold">Nhấn để xem kết quả</a>',
              true,
            );
          }, 600);
        })(appId);
      } catch (e) {}
    }
  }

  // Khởi tạo biểu đồ trên dashboard nếu có dữ liệu
  if (
    typeof SR !== "undefined" &&
    SR.chart_status &&
    typeof Chart !== "undefined"
  ) {
    srInitCharts();
  }
});

function srInitCharts() {
  var statusCanvas = document.getElementById("srChartStatus");
  if (
    statusCanvas &&
    SR.chart_status &&
    Object.keys(SR.chart_status).length > 0
  ) {
    var statusLabelMap = {
      submitted: "Đã nộp",
      viewed: "Đã xem",
      suitable: "Phù hợp",
      considering: "Cần xem xét",
      unsuitable: "Chưa phù hợp",
    };
    var colorMap = {
      submitted: "#6b7280",
      viewed: "#2563EB",
      suitable: "#00C48C",
      considering: "#FFB830",
      unsuitable: "#DC2626",
    };
    var keys = Object.keys(SR.chart_status);
    new Chart(statusCanvas, {
      type: "doughnut",
      data: {
        labels: keys.map(function (k) {
          return statusLabelMap[k] || k;
        }),
        datasets: [
          {
            data: keys.map(function (k) {
              return SR.chart_status[k];
            }),
            backgroundColor: keys.map(function (k) {
              return colorMap[k] || "#999";
            }),
            borderWidth: 2,
            borderColor: "#fff",
          },
        ],
      },
      options: {
        responsive: true,
        plugins: {
          legend: { position: "bottom", labels: { font: { size: 12 } } },
        },
      },
    });
  }

  var jobsCanvas = document.getElementById("srChartJobs");
  if (jobsCanvas && SR.chart_jobs && Object.keys(SR.chart_jobs).length > 0) {
    new Chart(jobsCanvas, {
      type: "bar",
      data: {
        labels: Object.keys(SR.chart_jobs),
        datasets: [
          {
            label: "Số ứng viên",
            data: Object.values(SR.chart_jobs),
            backgroundColor: "rgba(123,97,255,0.7)",
            borderRadius: 4,
          },
        ],
      },
      options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } },
      },
    });
  }
}

function srAnalyzeCV(appId) {
  srCurrentAppId = appId;
  srBgMode = false;
  srAnalysisDone = false;

  var loadingEl = document.getElementById("srAILoading");
  var resultEl = document.getElementById("srAIResult");
  var errorEl = document.getElementById("srAIError");
  var timeoutEl = document.getElementById("srTimeoutMsg");

  loadingEl.style.display = "block";
  resultEl.style.display = "none";
  errorEl.classList.add("d-none");
  timeoutEl.classList.add("d-none");
  document.getElementById("srModalTitle").innerHTML =
    '<i class="fas fa-robot me-2 text-primary"></i>Kết quả phân tích AI — Gemini';

  var modalEl = document.getElementById("srAIModal");
  var modal = bootstrap.Modal.getOrCreateInstance(modalEl, {
    backdrop: "static",
    keyboard: false,
  });

  document.getElementById("srAIModalClose").onclick = function () {
    if (srAnalysisDone) {
      bootstrap.Modal.getInstance(modalEl).hide();
    } else {
      srRunBackground();
    }
  };

  modal.show();
  srShowLoadingSteps();

  srTimeoutTimer = setTimeout(function () {
    if (!srAnalysisDone) {
      timeoutEl.classList.remove("d-none");
    }
  }, 30000);

  jQuery.ajax({
    url: SR.ajax_url,
    type: "POST",
    data: { action: "sr_analyze_cv", application_id: appId, nonce: SR.nonce },
    timeout: 35000,
    success: function (res) {
      clearTimeout(srTimeoutTimer);
      srCompleteLoadingSteps();
      srAnalysisDone = true;

      if (srBgMode) {
        if (res.success) {
          srUpdateTableRow(appId, res.data);
          try {
            sessionStorage.setItem(
              "sr_ai_" + appId,
              JSON.stringify({ data: res.data, appId: appId }),
            );
          } catch (e) {}
          srShowToast(
            '<i class="fas fa-robot me-2"></i>Phân tích hoàn tất! ' +
              '<a href="#" onclick="srShowStoredResult(' +
              appId +
              '); return false;" ' +
              'class="text-warning fw-semibold">Nhấn để xem kết quả</a>',
            true,
          );
        } else {
          srShowToast(
            '<i class="fas fa-triangle-exclamation me-2 text-warning"></i>Phân tích thất bại: ' +
              (typeof res.data === "string" ? res.data : "Lỗi không xác định"),
            true,
          );
        }
        return;
      }

      setTimeout(function () {
        srHideLoading();
        if (!res.success) {
          srShowError(res.data || "Không phân tích được");
          return;
        }
        document.getElementById("srAIModalClose").onclick = function () {
          bootstrap.Modal.getInstance(modalEl).hide();
        };
        srDisplayAIResult(res.data, appId);
      }, 400);
    },
    error: function (xhr, status) {
      clearTimeout(srTimeoutTimer);
      srAnalysisDone = true;
      if (srBgMode) {
        srShowToast(
          '<i class="fas fa-triangle-exclamation me-2 text-warning"></i>Phân tích thất bại: lỗi kết nối',
          true,
        );
        return;
      }
      srHideLoading();
      if (status === "timeout") {
        srShowError("Phân tích mất quá nhiều thời gian. Vui lòng thử lại.");
      } else {
        srShowError("Lỗi kết nối: " + (xhr.statusText || "unknown"));
      }
    },
  });

  srWireModalButtons(appId);
}

// Helpers hiển thị/ẩn loading
function srHideLoading() {
  var loadingEl = document.getElementById("srAILoading");
  if (loadingEl) loadingEl.style.display = "none";
}

function srShowError(msg) {
  var loadingEl = document.getElementById("srAILoading");
  var errorEl = document.getElementById("srAIError");
  var stepsEl = document.getElementById("srStepList");
  if (loadingEl) loadingEl.style.display = "block";
  if (stepsEl) stepsEl.style.display = "none";
  if (errorEl) {
    errorEl.innerHTML =
      '<i class="fas fa-triangle-exclamation me-2"></i>' +
      msg +
      '<br><button class="btn btn-sm btn-outline-danger mt-2" onclick="srAnalyzeCV(' +
      srCurrentAppId +
      ')">' +
      '<i class="fas fa-rotate-right me-1"></i>Thử lại</button>';
    errorEl.classList.remove("d-none");
  }
}

function srWireModalButtons(appId) {
  ["suitable", "considering", "unsuitable"].forEach(function (status) {
    var btn = document.getElementById("srModalStatus_" + status);
    if (btn) {
      btn.onclick = function () {
        var modalEl = document.getElementById("srAIModal");
        bootstrap.Modal.getInstance(modalEl).hide();
        srUpdateStatus(appId, status);
      };
    }
  });
}

function srRunBackground() {
  clearTimeout(srTimeoutTimer);
  srBgMode = true;
  var m = bootstrap.Modal.getInstance(document.getElementById("srAIModal"));
  if (m) m.hide();
  srShowToast(
    '<i class="fas fa-robot me-2"></i>Đang phân tích CV trong nền<i class="fas fa-ellipsis ms-1"></i>',
    true,
  );
}

function srDismissTimeout() {
  document.getElementById("srTimeoutMsg").classList.add("d-none");
}

function srViewAIResult(el) {
  var appId = el.dataset.appId;
  srCurrentAppId = appId;
  srBgMode = false;
  srAnalysisDone = true;

  document.getElementById("srAILoading").style.display = "none";
  document.getElementById("srAIResult").style.display = "none";
  document.getElementById("srAIError").classList.add("d-none");
  document.getElementById("srTimeoutMsg").classList.add("d-none");

  var name = el.dataset.name || "";
  var job = el.dataset.job || "";
  document.getElementById("srModalTitle").textContent =
    name + (job ? " — " + job : "");

  var data = {
    score: parseInt(el.dataset.score) || 0,
    strengths: [],
    weaknesses: [],
    recommendation: el.dataset.recommendation || "",
  };
  try {
    data.strengths = JSON.parse(el.dataset.strengths || "[]");
  } catch (e) {}
  try {
    data.weaknesses = JSON.parse(el.dataset.weaknesses || "[]");
  } catch (e) {}

  var modalEl = document.getElementById("srAIModal");
  document.getElementById("srAIModalClose").onclick = function () {
    bootstrap.Modal.getInstance(modalEl).hide();
  };
  bootstrap.Modal.getOrCreateInstance(modalEl).show();

  setTimeout(function () {
    srDisplayAIResult(data, appId);
  }, 100);
  srWireModalButtons(appId);
}

function srShowStoredResult(appId) {
  var data = window["__sr_result_" + appId];
  if (!data) return;
  srCurrentAppId = appId;
  srBgMode = false;
  srAnalysisDone = true;

  document.getElementById("srAILoading").style.display = "none";
  document.getElementById("srAIResult").style.display = "none";
  document.getElementById("srAIError").classList.add("d-none");
  document.getElementById("srTimeoutMsg").classList.add("d-none");
  document.getElementById("srModalTitle").innerHTML =
    '<i class="fas fa-robot me-2 text-primary"></i>Kết quả phân tích AI — Gemini';

  var modalEl = document.getElementById("srAIModal");
  document.getElementById("srAIModalClose").onclick = function () {
    bootstrap.Modal.getInstance(modalEl).hide();
  };
  bootstrap.Modal.getOrCreateInstance(modalEl).show();

  setTimeout(function () {
    srDisplayAIResult(data, appId);
  }, 100);
  srWireModalButtons(appId);
}

function srParseMarkdown(text) {
  if (!text) return "";

  text = String(text).trim();

  return (
    text
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")

      // bold: **text**
      .replace(/\*\*([^*]+)\*\*/g, "<strong>$1</strong>")

      // italic: *text*
      .replace(/(^|[^*])\*([^*]+)\*([^*]|$)/g, "$1<em>$2</em>$3")

      // line breaks
      .replace(/\n/g, "<br>")
  );
}

function srDisplayAIResult(data, appId) {
  var score = parseInt(data.score) || 0;
  var circle = document.getElementById("srScoreCircle");
  var label = document.getElementById("srScoreLabel");

  circle.textContent = score + "%";
  circle.className = "sr-score-circle mx-auto mb-2";

  if (score >= 70) {
    circle.classList.add("sr-score-high");
    label.textContent = "Phù hợp tốt với vị trí";
  } else if (score >= 40) {
    circle.classList.add("sr-score-mid");
    label.textContent = "Cần xem xét thêm";
  } else {
    circle.classList.add("sr-score-low");
    label.textContent = "Chưa phù hợp với vị trí";
  }

  /* Strengths */
  var strengths = data.strengths;
  if (!Array.isArray(strengths)) {
    try {
      strengths = JSON.parse(strengths || "[]");
    } catch (e) {
      strengths = [];
    }
  }
  document.getElementById("srStrengths").innerHTML = strengths.length
    ? strengths
        .map(function (s) {
          return '<span class="sr-tag-pos">' + srParseMarkdown(s) + "</span>";
        })
        .join("")
    : '<span class="text-muted small">Không có thông tin</span>';

  /* Weaknesses */
  var weaknesses = data.weaknesses;
  if (!Array.isArray(weaknesses)) {
    try {
      weaknesses = JSON.parse(weaknesses || "[]");
    } catch (e) {
      weaknesses = [];
    }
  }
  document.getElementById("srWeaknesses").innerHTML = weaknesses.length
    ? weaknesses
        .map(function (w) {
          return '<span class="sr-tag-neg">' + srParseMarkdown(w) + "</span>";
        })
        .join("")
    : '<span class="text-muted small">Không có thông tin</span>';

  document.getElementById("srRecommendation").innerHTML = srParseMarkdown(
    data.recommendation || "(Không có nhận xét)",
  );

  /* Auto-select suggestion radio based on score */
  var suggestionVal =
    score >= 70 ? "suitable" : score >= 40 ? "considering" : "unsuitable";
  var radio = document.querySelector(
    'input[name="sr_suggestion"][value="' + suggestionVal + '"]',
  );
  if (radio) radio.checked = true;

  document.getElementById("srAIResult").style.display = "block";
  srUpdateTableRow(appId, data);
}

function srUpdateTableRow(appId, data) {
  var score = parseInt(data.score) || 0;
  var scoreWrap = document.getElementById("score-wrap-" + appId);
  if (scoreWrap) {
    var tier = score >= 70 ? "high" : score >= 40 ? "mid" : "low";
    scoreWrap.outerHTML =
      '<div class="sr-score-wrap" id="score-wrap-' +
      appId +
      '">' +
      '<div class="sr-score-bar"><div class="sr-score-fill score-' +
      tier +
      '" style="width:' +
      score +
      '%"></div></div>' +
      '<span class="sr-score-badge score-' +
      tier +
      '">' +
      score +
      "%</span>" +
      "</div>";
  }
  window["__sr_result_" + appId] = data;
}

function srShowLoadingSteps() {
  var list = document.getElementById("srStepList");
  list.innerHTML = "";
  list.style.display = "";
  var steps = [
    { delay: 0, text: "Đang kết nối Gemini AI..." },
    { delay: 600, text: "Đang đọc nội dung CV..." },
    { delay: 1800, text: "Đang phân tích và chấm điểm..." },
  ];
  steps.forEach(function (s, i) {
    setTimeout(function () {
      var l = document.getElementById("srStepList");
      if (!l) return;
      if (i > 0) {
        var prev = document.getElementById("srStep" + (i - 1));
        if (prev) {
          prev.className = "sr-step sr-step-done";
          prev.querySelector("i").className =
            "fas fa-circle-check sr-step-icon sr-step-check";
        }
      }
      var div = document.createElement("div");
      div.id = "srStep" + i;
      div.className = "sr-step sr-step-active";
      div.innerHTML =
        '<i class="fas fa-spinner fa-spin sr-step-icon"></i> ' + s.text;
      l.appendChild(div);
    }, s.delay);
  });
}

function srCompleteLoadingSteps() {
  var last = document.getElementById("srStep2");
  if (last) {
    last.className = "sr-step sr-step-done";
    last.querySelector("i").className =
      "fas fa-circle-check sr-step-icon sr-step-check";
  }
  var list = document.getElementById("srStepList");
  if (list) {
    var div = document.createElement("div");
    div.className = "sr-step sr-step-done";
    div.innerHTML =
      '<i class="fas fa-circle-check sr-step-icon sr-step-check"></i> Hoàn thành!';
    list.appendChild(div);
  }
}

function srChangeStatus(appId, status) {
  var row = document.getElementById("row-" + appId);
  if (row && row.dataset.emailSent == "1") {
    srShowToast(
      '<i class="fas fa-lock me-2 text-warning"></i>Không thể thay đổi sau khi đã gửi email.',
      true,
    );
    return;
  }
  srCurrentAppId = appId;
  srUpdateStatus(appId, status);
}

function srUpdateStatus(appId, status) {
  if (status === "suitable" || status === "unsuitable") {
    srShowEmailModal(appId, status);
  } else {
    // considering: chỉ cập nhật trạng thái, không gửi email
    srDoUpdateStatus(appId, status, "none", null);
  }
}

function srDoUpdateStatus(appId, status, emailOption, customMsg) {
  var statusLabels = {
    submitted: "Đã nộp",
    viewed: "Đã xem",
    suitable: "Phù hợp",
    considering: "Cần xem xét",
    unsuitable: "Chưa phù hợp",
  };
  var statusBadge = {
    submitted: "sr-badge-submitted",
    viewed: "sr-badge-viewed",
    suitable: "sr-badge-suitable",
    considering: "sr-badge-considering",
    unsuitable: "sr-badge-unsuitable",
  };

  jQuery.post(
    SR.ajax_url,
    {
      action: "sr_update_status",
      application_id: appId,
      status: status,
      email_option: emailOption || "none",
      custom_message: customMsg || "",
      nonce: SR.nonce,
    },
    function (res) {
      if (!res.success) {
        srShowToast(
          '<i class="fas fa-triangle-exclamation me-2 text-warning"></i>Lỗi: ' +
            (res.data || ""),
          true,
        );
        return;
      }
      var el = document.getElementById("status-" + appId);
      if (el) {
        el.textContent = statusLabels[status] || status;
        el.className = "badge " + (statusBadge[status] || "bg-secondary");
      }

      /* Lock row if email sent */
      if (res.data && res.data.email_sent == 1) {
        var row = document.getElementById("row-" + appId);
        if (row) {
          row.dataset.emailSent = "1";
          var actionGroup = document.getElementById("actions-" + appId);
          if (actionGroup) {
            actionGroup
              .querySelectorAll("button:not(.btn-ai)")
              .forEach(function (b) {
                b.disabled = true;
              });
            /* Add lock indicator */
            var lock = row.querySelector(".sr-lock-icon");
            if (!lock) {
              var lockSpan = document.createElement("span");
              lockSpan.className = "sr-lock-icon ms-1 text-muted";
              lockSpan.title = "Đã gửi email";
              lockSpan.innerHTML = '<i class="fas fa-lock"></i>';
              var statusCell = document.getElementById("status-" + appId);
              if (statusCell) statusCell.parentNode.appendChild(lockSpan);
            }
          }
        }
      }

      var icon =
        status === "suitable"
          ? "fa-circle-check text-success"
          : status === "unsuitable"
            ? "fa-circle-xmark text-danger"
            : status === "considering"
              ? "fa-circle-question text-warning"
              : "fa-check text-success";
      srShowToast(
        '<i class="fas ' +
          icon +
          ' me-2"></i>Đã cập nhật: <strong>' +
          (statusLabels[status] || status) +
          "</strong>",
        true,
      );
    },
  );
}

function srShowEmailModal(appId, status) {
  var label = status === "suitable" ? "phù hợp" : "chưa phù hợp";
  document.getElementById("srEmailModalMsg").textContent =
    "Bạn có muốn gửi email thông báo cho ứng viên rằng họ " + label + "?";
  document.getElementById("srCustomEmailWrap").style.display = "none";
  document.getElementById("srCustomEmailContent").value = "";

  var modalEl = document.getElementById("srEmailModal");
  var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
  modal.show();

  document.getElementById("srEmailOpt1").onclick = function () {
    modal.hide();
    srDoUpdateStatus(appId, status, "default", null);
  };
  document.getElementById("srEmailOpt2").onclick = function () {
    document.getElementById("srCustomEmailWrap").style.display = "block";
  };
  document.getElementById("srSendCustomEmail").onclick = function () {
    var msg = document.getElementById("srCustomEmailContent").value.trim();
    if (!msg) {
      alert("Vui lòng nhập nội dung email");
      return;
    }
    modal.hide();
    srDoUpdateStatus(appId, status, "custom", msg);
  };
  document.getElementById("srEmailOpt3").onclick = function () {
    modal.hide();
    srDoUpdateStatus(appId, status, "none", null);
  };
}

function srShowToast(msg, isHtml) {
  var toastEl = document.getElementById("srToast");
  var bodyEl = document.getElementById("srToastBody");
  if (isHtml) {
    bodyEl.innerHTML = msg;
  } else {
    bodyEl.textContent = msg;
  }
  bootstrap.Toast.getOrCreateInstance(toastEl, { delay: 6000 }).show();
}

function srOpenJobModal(jobData) {
  var modal = document.getElementById("srJobModal");
  if (!modal) return;

  /* Reset form */
  modal.querySelector("#srJobModalTitle").textContent = jobData
    ? "Chỉnh sửa vị trí"
    : "Thêm vị trí mới";
  modal.querySelector("#srJobId").value = jobData ? jobData.id : "";
  modal.querySelector("#srJobTitle").value = jobData ? jobData.title : "";
  modal.querySelector("#srJobSalary").value = jobData ? jobData.salary : "";
  modal.querySelector("#srJobLocation").value = jobData ? jobData.location : "";
  modal.querySelector("#srJobQuantity").value = jobData ? jobData.quantity : 1;
  modal.querySelector("#srJobSkills").value = jobData ? jobData.skills : "";
  modal.querySelector("#srJobDescription").value = jobData
    ? jobData.description
    : "";
  modal.querySelector("#srJobRequirements").value = jobData
    ? jobData.requirements
    : "";
  var typeSelect = modal.querySelector("#srJobType");
  if (typeSelect && jobData) typeSelect.value = jobData.type || "full-time";

  bootstrap.Modal.getOrCreateInstance(modal).show();
}

function srSaveJobModal() {
  var modal = document.getElementById("srJobModal");
  var saveBtn = document.getElementById("srJobSaveBtn");
  var title = modal.querySelector("#srJobTitle").value.trim();
  if (!title) {
    alert("Vui lòng nhập tên vị trí");
    return;
  }

  saveBtn.disabled = true;
  saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Đang lưu...';

  jQuery.post(
    SR.ajax_url,
    {
      action: "sr_save_job_modal",
      nonce: SR.nonce,
      job_id: modal.querySelector("#srJobId").value,
      title: title,
      salary: modal.querySelector("#srJobSalary").value,
      location: modal.querySelector("#srJobLocation").value,
      quantity: modal.querySelector("#srJobQuantity").value,
      type: modal.querySelector("#srJobType").value,
      skills: modal.querySelector("#srJobSkills").value,
      description: modal.querySelector("#srJobDescription").value,
      requirements: modal.querySelector("#srJobRequirements").value,
    },
    function (res) {
      saveBtn.disabled = false;
      saveBtn.innerHTML = '<i class="fas fa-floppy-disk me-1"></i>Lưu';
      if (!res.success) {
        srShowToast(
          '<i class="fas fa-triangle-exclamation me-2 text-warning"></i>' +
            (res.data || "Lỗi lưu"),
          true,
        );
        return;
      }
      bootstrap.Modal.getInstance(modal).hide();
      srShowToast(
        '<i class="fas fa-circle-check me-2 text-success"></i>Đã lưu vị trí thành công!',
        true,
      );
      setTimeout(function () {
        location.reload();
      }, 1000);
    },
  );
}

function srGenerateJD() {
  var modal = document.getElementById("srJobModal");
  var btn = document.getElementById("srGenJDBtn");
  var title = modal.querySelector("#srJobTitle").value.trim();

  if (!title) {
    alert("Vui lòng nhập tên vị trí trước");
    return;
  }

  btn.disabled = true;
  btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Đang tạo...';

  jQuery.post(
    SR.ajax_url,
    {
      action: "sr_generate_jd",
      nonce: SR.nonce,
      title: title,
      skills: modal.querySelector("#srJobSkills").value,
      location: modal.querySelector("#srJobLocation").value,
      type: modal.querySelector("#srJobType").value,
      salary: modal.querySelector("#srJobSalary").value,
    },
    function (res) {
      btn.disabled = false;
      btn.innerHTML =
        '<i class="fas fa-wand-magic-sparkles me-1"></i>Tạo JD bằng AI';
      if (!res.success) {
        srShowToast(
          '<i class="fas fa-triangle-exclamation me-2 text-warning"></i>' +
            (res.data || "Lỗi tạo JD"),
          true,
        );
        return;
      }
      if (res.data.description) {
        modal.querySelector("#srJobDescription").value = res.data.description;
      }
      if (res.data.requirements) {
        modal.querySelector("#srJobRequirements").value = res.data.requirements;
      }
      srShowToast(
        '<i class="fas fa-circle-check me-2 text-success"></i>Đã tạo JD thành công!',
        true,
      );
    },
  );
}

function srToggleJobStatus(jobId, btn) {
  jQuery.post(
    SR.ajax_url,
    {
      action: "sr_toggle_job_status",
      nonce: SR.nonce,
      job_id: jobId,
    },
    function (res) {
      if (!res.success) {
        srShowToast(
          '<i class="fas fa-triangle-exclamation me-2 text-warning"></i>' +
            (res.data || "Lỗi"),
          true,
        );
        return;
      }
      var isActive = res.data.status === "active";
      var row = btn.closest("tr");
      var badgeEl = row ? row.querySelector(".sr-job-status-badge") : null;
      if (badgeEl) {
        badgeEl.textContent = isActive ? "Đang tuyển" : "Dừng tuyển";
        badgeEl.className =
          "badge sr-job-status-badge " +
          (isActive ? "bg-success" : "bg-secondary");
      }
      btn.innerHTML = isActive
        ? '<i class="fas fa-toggle-on me-1"></i>Đang tuyển'
        : '<i class="fas fa-toggle-off me-1"></i>Dừng tuyển';
      btn.className = isActive
        ? "btn btn-sm btn-outline-success"
        : "btn btn-sm btn-outline-secondary";
    },
  );
}
