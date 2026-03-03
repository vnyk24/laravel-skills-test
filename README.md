# PHP Skills Test - Laravel Solution

This project implements the requested Laravel webpage with:

- Form fields: `Product name`, `Quantity in stock`, `Price per item`
- AJAX submit and AJAX edit (extra credit)
- JSON file persistence in `storage/app/product_submissions.json`
- Display table ordered by datetime submitted
- Computed `Total value number = quantity * price`
- Final sum row for all total values
- Laravel `Routes`, `Controller`, `Migration`, `Model`, and `Seeder`
- Twitter Bootstrap UI

## Main Files

- `routes/web.php`
- `app/Http/Controllers/ProductSubmissionController.php`
- `app/Models/ProductSubmission.php`
- `resources/views/products/index.blade.php`
- `database/migrations/2026_03_03_000000_create_product_submissions_table.php`
- `database/seeders/ProductSubmissionSeeder.php`
- `storage/app/product_submissions.json`

## Setup

1. Install dependencies:

   ```bash
   composer install
   ```

2. Prepare environment:

   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

3. Run migrations and seeders:

   ```bash
   php artisan migrate
   php artisan db:seed
   ```

4. Start server:

   ```bash
   php artisan serve
   ```

5. Open:

   `http://127.0.0.1:8000`

## Notes

- JSON persistence is the primary required format for this task.
- Database writes are also attempted (for Laravel migration/seeding coverage).
