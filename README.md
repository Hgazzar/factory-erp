# Factory ERP

نظام ERP (Laravel) للمحاسبة والمخزون والإنتاج والمبيعات ونقطة البيع.

## الاختبارات وCI

[![PHPUnit (ERP core)](https://github.com/Hgazzar/factory-erp/actions/workflows/phpunit.yml/badge.svg)](https://github.com/Hgazzar/factory-erp/actions/workflows/phpunit.yml)

```bash
composer install
./vendor/bin/phpunit tests/Unit tests/Feature/Production tests/Feature/Delivery
```

- **قاعدة الفريق:** أي إصلاح مالي/مخزني يبدأ باختبار ثم الكود — التفاصيل في [CONTRIBUTING.md](CONTRIBUTING.md).
- **CI:** يعمل تلقائياً على PR إلى `main` / `master` / `develop` (انظر `.github/workflows/phpunit.yml`).

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework. You can also check out [Laravel Learn](https://laravel.com/learn), where you will be guided through building a modern Laravel application.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

راجع [CONTRIBUTING.md](CONTRIBUTING.md) لقواعد المساهمة في Factory ERP (اختبارات، واجهة، CI).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## Standard UI Components

- `x-info` is a required UI helper for labels and important table headers across modules.
- Always place `<x-info field="...">` next to the label/header text for key business fields.
- Hints must be sourced from `config/hints.php` (grouped by domain such as `finance`, `sales`, `hr`).
- If a new business term is introduced, add it to `config/hints.php` before using it in views.
