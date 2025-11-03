# HƯỚNG DẪN KỸ THUẬT (huongdankythuat.md / massagenow.md)

Tài liệu này tóm tắt kiến trúc, database, routing, phân quyền, và các luồng chính của hệ thống **massagenow.vn** để dev hoặc admin sau này đọc tiếp tục làm mà không cần lịch sử chat.

---

## 1. Mục tiêu sản phẩm

Massagenow là nền tảng đặt lịch massage tại nhà theo thành phố, đa ngôn ngữ.

Khách truy cập:
- Chọn ngôn ngữ (vi, en, ru, ja, ko, th, zh ...).
- Chọn thành phố.
- Xem nhân viên và dịch vụ ở thành phố đó.
- Gửi form đặt lịch (booking).

Admin nội bộ:
- Quản lý thành phố, dịch vụ, nhân viên, đơn đặt lịch (booking), user admin.
- Dashboard xem nhanh số liệu + biểu đồ.
- Có thể đổi trạng thái đơn hàng (new, confirmed, in_progress, done, canceled).

SEO:
- URL dạng `/vi/ha-noi`, `/ru/nha-trang`.
- Title/Description và nội dung trang city theo ngôn ngữ.
- Sitemap và robots.txt.

---

## 2. Cấu trúc thư mục quan trọng

```text
/public_html/
  index.php            ← router chính (frontend khách)
  /.htaccess           ← rewrite rule cho OpenLiteSpeed/Apache
  /api/booking.php     ← API tạo đơn hàng (booking)
  /admin/
    index.php          ← dashboard (AdminLTE)
    orders.php         ← danh sách booking
    order-view.php     ← chi tiết 1 booking
    cities.php         ← CRUD Thành phố
    ...                ← các file admin khác (services.php, staff.php,...)

