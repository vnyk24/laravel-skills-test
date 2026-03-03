<?php

namespace Database\Seeders;

use App\Models\ProductSubmission;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ProductSubmissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $samples = [
            ['Coffee Beans', 12, 8.50, Carbon::now()->subMinutes(25)],
            ['Tea Bags', 30, 4.25, Carbon::now()->subMinutes(10)],
        ];

        $jsonEntries = [];

        foreach ($samples as [$name, $qty, $price, $submittedAt]) {
            $uuid = (string) Str::uuid();

            ProductSubmission::query()->create([
                'external_id' => $uuid,
                'product_name' => $name,
                'quantity_in_stock' => $qty,
                'price_per_item' => $price,
                'submitted_at' => $submittedAt,
            ]);

            $jsonEntries[] = [
                'id' => $uuid,
                'product_name' => $name,
                'quantity_in_stock' => $qty,
                'price_per_item' => $price,
                'submitted_at' => $submittedAt->toIso8601String(),
            ];
        }

        File::put(
            storage_path('app/product_submissions.json'),
            json_encode($jsonEntries, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
    }
}
