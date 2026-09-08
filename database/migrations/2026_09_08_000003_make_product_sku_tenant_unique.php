<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('products')
            ->select('id', 'tenant_id', 'sku')
            ->whereNull('sku')
            ->orderBy('id')
            ->get()
            ->each(function (object $product): void {
                DB::table('products')
                    ->where('id', $product->id)
                    ->update(['sku' => 'SKU-'.$product->tenant_id.'-'.$product->id]);
            });

        DB::table('products')
            ->select('tenant_id', 'sku')
            ->whereNotNull('sku')
            ->groupBy('tenant_id', 'sku')
            ->havingRaw('COUNT(*) > 1')
            ->get()
            ->each(function (object $duplicate): void {
                $duplicateIds = DB::table('products')
                    ->where('tenant_id', $duplicate->tenant_id)
                    ->where('sku', $duplicate->sku)
                    ->orderBy('id')
                    ->pluck('id');

                foreach ($duplicateIds->skip(1) as $productId) {
                    DB::table('products')
                        ->where('id', $productId)
                        ->update(['sku' => 'SKU-'.$duplicate->tenant_id.'-'.$productId]);
                }
            });

        Schema::table('products', function (Blueprint $table): void {
            $table->dropUnique('products_barcode_unique');
            $table->unique(['tenant_id', 'sku'], 'products_tenant_sku_unique');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->dropUnique('products_tenant_sku_unique');
            $table->unique('barcode', 'products_barcode_unique');
        });
    }
};