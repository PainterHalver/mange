# Backend API

Chạy app:

```bash
# 1. Tạo file .env theo mẫu .env.example

# 2. Cài đặt các package
composer install

# 3. Tạo db và seed dữ liệu
php artisan migrate:install
php artisan migrate:fresh --seed

# 4. Chạy app
php artisan serve
```

Chạy test, cần cài xdebug dll (windows) hoặc so (linux) để chạy coverage:

```bash
# Tạo file database/database.testing.sqlite

# Chạy laravel test và coverage trong console
php artisan test --coverage

# Html coverage, mở thư mục reports/index.html để xem
php artisan test --coverage-html=reports
```
