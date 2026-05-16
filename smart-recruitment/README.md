# Thông tin sinh viên

- Họ và tên: Nguyễn Văn Luận
- Mã sinh viên: 23810310279
- Lớp: D18CNPM4

# Smart Recruitment — Plugin Tuyển Dụng Thông Minh

Plugin WordPress giúp tự động hóa quy trình tuyển dụng bằng phân tích CV/ứng viên bằng AI (Google Gemini), quản lý tin tuyển dụng và hồ sơ ứng viên trực tiếp trong khu vực quản trị WordPress.

## Tính năng chính

- Phân tích CV bằng Google Gemini và trả về điểm `score`, điểm mạnh, điểm yếu và khuyến nghị.
- Lưu trữ hồ sơ ứng viên, trạng thái ứng tuyển, và lịch sử gửi email.
- Tạo và quản lý tin tuyển dụng (title, description, skills, salary, location, type).
- Shortcode hiển thị danh sách công việc và form ứng tuyển công khai.
- Gửi email thông báo (SMTP) khi cập nhật trạng thái `suitable`/`unsuitable`.

## Yêu cầu

- PHP 8.x
- WordPress 6.x
- Composer (để cài `smalot/pdfparser` đã được vendorized nhưng vẫn khuyến nghị dùng composer khi thay đổi)

## Cài đặt

1. Sao chép thư mục `smart-recruitment` vào `wp-content/plugins/`.
2. (Nếu cần) Chạy `composer install` trong thư mục plugin.
3. Kích hoạt plugin từ trang Plugins trong WordPress admin.
4. Vào `Smart Recruitment` trong menu admin để cấu hình API và SMTP.

## Cấu hình

- `sr_gemini_api_key`: API key cho Google Gemini (lưu trong `get_option`).
- `sr_gemini_model`: Model Gemini (mặc định `gemini-2.5-flash`).
- `sr_smtp_host`, `sr_smtp_port`, `sr_smtp_username`, `sr_smtp_password`: cấu hình SMTP cho gửi email.

## Sử dụng

- Admin: Truy cập trang `Smart Recruitment` để xem dashboard, quản lý jobs, applications và chạy phân tích AI cho từng CV.
- Public: Dùng shortcode `[sr_job_listing]` để hiển thị danh sách tuyển dụng công khai; form nộp hồ sơ có AJAX gửi tới plugin.

## Bảng dữ liệu (prefix = `{$wpdb->prefix}`)

- `sr_jobs` — Danh sách công việc
- `sr_applications` — Hồ sơ ứng viên
- `sr_ai_results` — Kết quả phân tích AI

## AJAX Actions

Tất cả yêu cầu AJAX admin cần nonce `sr_nonce`.

- `sr_analyze_cv` — Phân tích CV (Admin)
- `sr_update_status` — Cập nhật trạng thái ứng viên (Admin)
- `sr_toggle_job_status` — Bật/tắt tin tuyển dụng (Admin)
- `sr_generate_jd` — Sinh mô tả công việc bằng AI (Admin)
- `sr_submit_application` — Nộp hồ sơ (Public)
- `sr_extract_cv` — Trích xuất CV để autofill (Public)
