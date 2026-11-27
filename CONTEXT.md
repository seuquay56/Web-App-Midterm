# CONTEXT.md – Tổng quan dự án & Tiến độ hiện tại

> **Môn học:** Web Programming & Applications (503073) – HK1 2026–2027  
> **Đề tài:** Đề tài 4 – Containerization (Docker)  
> **Nhánh Git hiện tại:** `backend-khiem` (nhánh `main` hiện mới chỉ có `README.md`)

---

## 1. Dự án đang làm về gì?

Dự án xây dựng một **ứng dụng Web quản lý ghi chú (Notes API)** sử dụng **Laravel 13 (PHP 8.3+)** kết hợp **Laravel Sanctum** để minh họa kiến trúc **multi-service containerization với Docker** (Web App + Database + Cache).

### Cấu trúc thư mục hiện có
- `README.md`: Mô tả ngắn tên môn học và đề tài.
- `Backend/`: Ứng dụng Laravel 13 (được tái sử dụng và chỉnh sửa từ đồ án cuối kỳ).
  - `app/Http/Controllers/AuthController.php`: Xử lý đăng ký tài khoản (`POST /api/register`) và cấp Sanctum token.
  - `app/Http/Controllers/NoteController.php`: CRUD ghi chú (`index`, `store`, `update`, `destroy`) gắn với `user_id`.
  - `app/Models/Note.php`, `app/Models/User.php`: Eloquent models.
  - `database/migrations/`: Migration cho `users`, `cache`, `jobs`, `personal_access_tokens`, `notes`.
  - `database/seeders/DatabaseSeeder.php`: Tạo 1 user mẫu (`test@example.com`).
  - `Database/init.sql`: File SQL test tạo bảng `tasks` và insert 2 dòng mẫu.
  - `routes/api.php`: Định nghĩa các endpoint `/health`, `/register`, `/notes`, `/user`.
  - `Dockerfile` & `.dockerignore`: Cấu hình Docker bước đầu cho Backend.
- `docs/API-Collections/`: Bộ tài liệu & request test API định dạng Bruno / OpenCollection (`Health`, `Register`, `Create`, `Read`, `Update`, `Delete`).

### Điểm nhấn phục vụ đề tài Docker đã có trong code
- Endpoint `GET /api/health` kiểm tra kết nối DB (`DB::connection()->getPdo()`) và trả về `container: gethostname()` cùng HTTP `200` hoặc `503` — dùng để nhận diện container ID và kiểm tra trạng thái service khi chạy Docker / mô phỏng crash.
- `Backend/Dockerfile` đã cài `pdo_mysql` và `pecl install redis` sẵn sàng cho kiến trúc MySQL + Redis.

---

## 2. Đã làm được đến đâu? (So với Requirement)

### Đã hoàn thành / Có nền tảng
- [x] Khởi tạo repo Git, có nhánh `main` và nhánh làm việc `backend-khiem`.
- [x] Có Backend Laravel 13 với xác thực Sanctum (`POST /api/register`) và CRUD `notes` (`GET`, `POST`, `PUT`, `DELETE /api/notes`).
- [x] Có endpoint `/api/health` kiểm tra DB và trả về hostname của container.
- [x] Có **1 Dockerfile chuẩn** (`Backend/Dockerfile`, single-stage từ `php:8.5-cli`).
- [x] Có tài liệu API collection trong `docs/API-Collections/`.

### Các lỗi / điểm chưa khớp cần sửa ngay trong code hiện tại
1. **`Backend/routes/api.php` (dòng 49):** Có lỗi chính tả `'up   ate'` thay vì `'update'`, đồng thời đang bị lặp (duplicate) các route `/notes` ở nhiều block khác nhau.
2. **`Backend/Database/init.sql` vs Migrations:** `init.sql` đang tạo bảng `tasks`, trong khi Laravel migration và `NoteController` dùng bảng `notes` và `users`. Cần đồng bộ lại.
3. **`Backend/.env.example`:** Đang cấu hình mặc định `DB_CONNECTION=sqlite` và `CACHE_STORE=database`, chưa trỏ sang MySQL và Redis trong Docker network.
4. **`Backend/.dockerignore`:** Đang ghi `/.vendor`, `/.node_modules`, `/.storage/logs/*` (dư dấu `/` và `.` ở `vendor`, `node_modules`), dẫn đến `COPY . .` có thể copy nhầm thư mục lớn vào image.
5. **Thiếu `login` endpoint:** Hiện chỉ có `/register`, chưa có `/login` để đăng nhập lại bằng tài khoản test/admin có sẵn.

---

## 3. Những việc còn thiếu cần triển khai tiếp

### A. Kỹ thuật & Ứng dụng Demo
- [ ] **`docker-compose.yml` ở thư mục gốc:** Kết nối đủ ít nhất 3 service bắt buộc:
  1. **Web App** (Laravel Backend + Frontend/UI)
  2. **Database** (MySQL/MariaDB, mount volume + script init/seed)
  3. **Cache** (Redis)
  - Cấu hình đầy đủ `healthcheck`, `restart` policy, `depends_on` (kèm `condition: service_healthy`), `networks`, `volumes`.
- [ ] **Dockerfile thứ 2 (Multi-stage Build):** Tạo thêm `Dockerfile.multistage` (hoặc target multi-stage) để chạy thực nghiệm so sánh với `Backend/Dockerfile` hiện tại.
- [ ] **Tích hợp Redis Cache thực tế vào app:** Dùng Redis để cache danh sách notes / session / hoặc tạo endpoint/chức năng demo cache hit/miss rõ ràng.
- [ ] **Giao diện Demo (UI/UX – 0.5đ):** Hiện chưa có Frontend. Cần làm giao diện web (Blade/HTML+JS hoặc SPA) có nút thao tác trực quan để demo CRUD, xem container ID, trạng thái DB/Redis, test cache hit/miss, và test recovery khi crash service.
- [ ] **Seed dữ liệu & Tài khoản test/admin:** Cập nhật `DatabaseSeeder.php` (hoặc `init.sql`) để có sẵn tài khoản test/admin + dữ liệu mẫu khi khởi chạy.

### B. Thí nghiệm bắt buộc (Đo đạc thực tế & phân tích Root Cause)
- [ ] **Thí nghiệm 1 – Dockerfile chuẩn vs Multi-stage Build:**
  - Đo `docker images` (final image size).
  - Đo `docker build` time (build lần đầu `--no-cache` và build có cache).
- [ ] **Thí nghiệm 2 – Phân tích Container Logs:**
  - Thu thập `docker logs` khi hệ thống chạy bình thường (HTTP 200/201) và khi gặp lỗi (DB down, 500/503, validation 422).
- [ ] **Thí nghiệm 3 – Mô phỏng Crash & Recovery:**
  - Dùng `docker stop` / `docker kill` một service (ví dụ DB hoặc Web App hoặc Redis).
  - Quan sát thời gian phục hồi, hành vi `restart` policy, `healthcheck`, ảnh hưởng tới các service còn lại và `/api/health`.
- [ ] **Tổng hợp số liệu:** Lập bảng, vẽ biểu đồ, chụp screenshot CLI (`docker images`, `docker stats`, `docker logs`, `docker ps`) và viết phân tích **root cause**.

### C. Sản phẩm nộp & Quy trình nhóm
- [ ] **Git Workflow:** Merge các nhánh vào `main` hợp lý; đảm bảo **tất cả thành viên đều có commit đều đặn**, tránh dồn 1 commit sát deadline.
- [ ] **`README.md` hoàn chỉnh:** Hướng dẫn cài đặt, chạy `docker compose up`, seed DB, thông tin tài khoản test/admin, danh sách endpoint.
- [ ] **Báo cáo kỹ thuật (PDF – Tiếng Anh):** Theo đúng template của Khoa, tự vẽ sơ đồ kiến trúc, đầy đủ trích dẫn chính thống và **AI Usage Log** trong Appendix (tối thiểu 2–3 ví dụ prompt + cách nhóm kiểm chứng/sửa lỗi).
- [ ] **Video Walkthrough (10–15 phút, 1080p+):** Tất cả thành viên xuất hiện trên camera và trình bày phần mình làm, demo app + terminal + logs + benchmark trực tiếp.
- [ ] **Chuẩn bị Live Defense (cá nhân, không dùng AI):** Ôn kỹ kiến trúc, data flow, và luyện thao tác sửa `Dockerfile` / `docker-compose.yml` / debug lỗi kết nối service trong 3–5 phút.
