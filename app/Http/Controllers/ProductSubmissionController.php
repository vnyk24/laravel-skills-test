<?php

namespace App\Http\Controllers;

use App\Models\ProductSubmission;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ProductSubmissionController extends Controller
{
    public function index()
    {
        return view('products.index', $this->buildResponsePayload());
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_name' => ['required', 'string', 'max:255'],
            'quantity_in_stock' => ['required', 'integer', 'min:0'],
            'price_per_item' => ['required', 'numeric', 'min:0'],
        ]);

        $entry = $this->normalizeEntry([
            'id' => (string) Str::uuid(),
            'product_name' => $validated['product_name'],
            'quantity_in_stock' => (int) $validated['quantity_in_stock'],
            'price_per_item' => (float) $validated['price_per_item'],
            'submitted_at' => Carbon::now()->toIso8601String(),
        ]);

        $entries = $this->readEntries();
        $entries[] = $entry;
        $entries = $this->sortBySubmittedAt($entries);
        $this->writeEntries($entries);
        $this->upsertDatabaseRecord($entry);

        return response()->json($this->buildApiPayload($entries), 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'product_name' => ['required', 'string', 'max:255'],
            'quantity_in_stock' => ['required', 'integer', 'min:0'],
            'price_per_item' => ['required', 'numeric', 'min:0'],
        ]);

        $entries = $this->readEntries();
        $updated = false;

        foreach ($entries as &$entry) {
            if (($entry['id'] ?? null) !== $id) {
                continue;
            }

            $entry['product_name'] = $validated['product_name'];
            $entry['quantity_in_stock'] = (int) $validated['quantity_in_stock'];
            $entry['price_per_item'] = (float) $validated['price_per_item'];
            $entry = $this->normalizeEntry($entry);
            $updated = true;
            break;
        }
        unset($entry);

        if (! $updated) {
            return response()->json(['message' => 'Entry not found.'], 404);
        }

        $entries = $this->sortBySubmittedAt($entries);
        $this->writeEntries($entries);
        $matched = collect($entries)->firstWhere('id', $id);

        if (is_array($matched)) {
            $this->upsertDatabaseRecord($matched);
        }

        return response()->json($this->buildApiPayload($entries));
    }

    private function buildResponsePayload(): array
    {
        $entries = $this->sortBySubmittedAt($this->readEntries());

        return [
            'entries' => $entries,
            'grandTotal' => $this->calculateGrandTotal($entries),
        ];
    }

    private function buildApiPayload(array $entries): array
    {
        return [
            'entries' => $entries,
            'grand_total' => $this->calculateGrandTotal($entries),
        ];
    }

    private function jsonFilePath(): string
    {
        return storage_path('app/product_submissions.json');
    }

    private function readEntries(): array
    {
        $path = $this->jsonFilePath();

        if (! File::exists($path)) {
            return [];
        }

        $decoded = json_decode((string) File::get($path), true);

        if (! is_array($decoded)) {
            return [];
        }

        return array_values(array_map(fn ($entry) => $this->normalizeEntry($entry), $decoded));
    }

    private function writeEntries(array $entries): void
    {
        $path = $this->jsonFilePath();
        $directory = dirname($path);

        if (! File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        File::put($path, json_encode(array_values($entries), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    private function normalizeEntry(array $entry): array
    {
        $submittedAt = isset($entry['submitted_at'])
            ? Carbon::parse((string) $entry['submitted_at'])
            : Carbon::now();

        $quantity = max(0, (int) ($entry['quantity_in_stock'] ?? 0));
        $price = round(max(0, (float) ($entry['price_per_item'] ?? 0)), 2);

        return [
            'id' => (string) ($entry['id'] ?? Str::uuid()),
            'product_name' => (string) ($entry['product_name'] ?? ''),
            'quantity_in_stock' => $quantity,
            'price_per_item' => $price,
            'submitted_at' => $submittedAt->toIso8601String(),
            'submitted_at_display' => $submittedAt->format('Y-m-d H:i:s'),
            'total_value_number' => round($quantity * $price, 2),
        ];
    }

    private function sortBySubmittedAt(array $entries): array
    {
        usort($entries, function (array $a, array $b) {
            return Carbon::parse($a['submitted_at'])->getTimestamp() <=> Carbon::parse($b['submitted_at'])->getTimestamp();
        });

        return $entries;
    }

    private function calculateGrandTotal(array $entries): float
    {
        return round(array_sum(array_map(fn (array $entry) => (float) ($entry['total_value_number'] ?? 0), $entries)), 2);
    }

    private function upsertDatabaseRecord(array $entry): void
    {
        if (! class_exists(ProductSubmission::class)) {
            return;
        }

        try {
            ProductSubmission::query()->updateOrCreate(
                ['external_id' => $entry['id']],
                [
                    'product_name' => $entry['product_name'],
                    'quantity_in_stock' => $entry['quantity_in_stock'],
                    'price_per_item' => $entry['price_per_item'],
                    'submitted_at' => Carbon::parse($entry['submitted_at']),
                ]
            );
        } catch (\Throwable) {
            // JSON file persistence is the required source of truth for this task.
        }
    }
}
