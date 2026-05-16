# TalentIQ — Hệ Thống Tuyển Dụng Thông Minh Tích Hợp AI

> Sinh viên: Nguyễn Văn Luận · MSSV: 23810310279 · Lớp: D18CNPM4

## Giới thiệu

TalentIQ là hệ thống tuyển dụng thông minh xây dựng trên nền WordPress, tích hợp AI (Google Gemini) để tự động phân tích CV ứng viên, quản lý tin tuyển dụng và hồ sơ ứng tuyển. Dự án gồm hai thành phần phối hợp chặt chẽ: **plugin** xử lý toàn bộ logic nghiệp vụ, **child theme** đảm nhận lớp trình bày giao diện.

## Cấu trúc repo

```
talentiq/
├── smart-recruitment/          ← WordPress plugin (slug: smart-recruitment)
│   ├── admin/                  ← Giao diện quản trị (dashboard, jobs, applications)
│   ├── assets/                 ← Bootstrap 5, Font Awesome
│   ├── includes/               ← Core classes (Activator, Gemini, Mailer, Uploader)
│   ├── public/                 ← Frontend assets & views
│   │   ├── assets/
│   │   │   ├── public.css      ← Design system, component styles
│   │   │   └── public.js       ← Form handling, CV upload, autofill
│   │   └── views/              ← Template partials (job-listing, job-detail, apply-form)
│   ├── templates/              ← Page templates dùng shortcode
│   ├── vendor/                 ← Composer dependencies (smalot/pdfparser)
│   └── smart-recruitment.php   ← Plugin entry point
├── theme/                      ← Child theme Twenty Twenty-Five
│   ├── parts/
│   │   └── recruitment-hero.html   ← Hero section (Gutenberg FSE)
│   ├── templates/
│   │   └── recruitment.html        ← Page template tuyển dụng full-width
│   ├── functions.php           ← Enqueue từ plugin, đăng ký template
│   └── style.css               ← Theme header + CSS variables thương hiệu
└── README.md
```

## Yêu cầu hệ thống

| Thành phần | Phiên bản |
|---|---|
| WordPress | 6.x trở lên |
| PHP | 8.x trở lên |
| Parent theme | Twenty Twenty-Five |
| Trình duyệt | Chrome / Firefox / Edge (modern) |

## Cài đặt

### 1. Plugin Smart Recruitment

```bash
# Sao chép thư mục plugin vào WordPress
cp -r smart-recruitment/ /path/to/wp-content/plugins/

# (Tùy chọn) Cài Composer dependencies nếu cần cập nhật
cd /path/to/wp-content/plugins/smart-recruitment
composer install
```

Sau đó vào **WordPress Admin → Plugins → Kích hoạt** plugin **Smart Recruitment**.

Cấu hình tại **Smart Recruitment → Cài đặt**:
- `sr_gemini_api_key` — API key Google Gemini
- `sr_gemini_model` — Model AI (mặc định `gemini-2.5-flash`)
- `sr_smtp_*` — Thông số SMTP để gửi email thông báo

### 2. Child Theme TalentIQ

```bash
# Sao chép thư mục theme vào WordPress
cp -r theme/ /path/to/wp-content/themes/twentytwentyfive-child/
```

Vào **WordPress Admin → Giao diện → Themes → Kích hoạt** theme **TalentIQ Child**.

> **Lưu ý:** Plugin phải được kích hoạt trước khi kích hoạt theme. Nếu không, một admin notice sẽ nhắc nhở.

## Mối quan hệ Plugin ↔ Theme

| Vai trò | Plugin `smart-recruitment` | Theme `twentytwentyfive-child` |
|---|---|---|
| Logic nghiệp vụ | Toàn bộ (CRUD jobs, AI, email, AJAX) | Không |
| CSS / JS | Định nghĩa design system (`public/assets/`) | Chỉ enqueue lại, không viết mới |
| Render frontend | Cung cấp shortcode | Override giao diện qua block template |
| Shortcode | `[sr_job_listing]` | Nhúng trong `templates/recruitment.html` |

**Nguyên tắc chính:**
- Theme là lớp **presentation** thuần túy — không chứa logic.
- Theme **không tự viết lại** CSS/JS đã có trong plugin, chỉ enqueue lại với dependency rõ ràng.
- Plugin cung cấp shortcode `[sr_job_listing]`; theme quyết định **nơi và cách** hiển thị.

## Shortcodes

| Shortcode | Mô tả |
|---|---|
| `[sr_job_listing]` | Hiển thị danh sách tin tuyển dụng công khai kèm form nộp hồ sơ |

**Ví dụ sử dụng trong trang WordPress:**
```
[sr_job_listing]
```

Hoặc thông qua **Trang tuyển dụng TalentIQ** — page template có sẵn trong theme.

## Tính năng chính

- **Đăng tin tuyển dụng (JD)** — Tạo/sửa/xóa job với đầy đủ thông tin (title, mô tả, kỹ năng, lương, địa điểm, loại hình). Hỗ trợ sinh mô tả tự động bằng AI.
- **Phân tích CV bằng AI** — Upload CV (PDF), hệ thống trích xuất nội dung và gửi cho Google Gemini để chấm điểm (`score`), phân tích điểm mạnh/yếu và đưa ra khuyến nghị.
- **Quản lý ứng viên** — Xem danh sách ứng viên theo từng job, cập nhật trạng thái (`suitable` / `unsuitable`), gửi email thông báo tự động.
- **Dashboard admin** — Thống kê tổng quan: số job, số đơn, điểm AI cao nhất.

## Cơ sở dữ liệu

Các bảng được tạo tự động khi kích hoạt plugin (prefix theo `$wpdb->prefix`):

| Bảng | Nội dung |
|---|---|
| `sr_jobs` | Danh sách tin tuyển dụng |
| `sr_applications` | Hồ sơ ứng viên |
| `sr_ai_results` | Kết quả phân tích AI |

## AJAX Actions

Tất cả request admin yêu cầu nonce `sr_nonce`.

| Action | Phạm vi | Mô tả |
|---|---|---|
| `sr_analyze_cv` | Admin | Phân tích CV bằng Gemini |
| `sr_update_status` | Admin | Cập nhật trạng thái ứng viên |
| `sr_toggle_job_status` | Admin | Bật/tắt tin tuyển dụng |
| `sr_generate_jd` | Admin | Sinh mô tả JD bằng AI |
| `sr_submit_application` | Public | Nộp hồ sơ ứng tuyển |
| `sr_extract_cv` | Public | Trích xuất CV để autofill form |
