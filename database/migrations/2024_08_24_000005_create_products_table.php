<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('category_id')->nullable()->constrained('categories')->onDelete('set null');
            $table->string('barcode')->nullable()->unique();
            $table->string('barcode_format')->default('CODE128');
            $table->string('barcode_image_path')->nullable();
            $table->string('name');
            $table->string('sku', 100)->nullable();
            $table->string('unit')->nullable();
            $table->decimal('cost_per_unit', 12, 2);
            $table->decimal('selling_price', 12, 2);
            $table->integer('min_stock')->default(10);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('tenant_id');
            $table->index('category_id');
            $table->index('barcode');
            $table->index('name');
            $table->unique(['tenant_id', 'barcode']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};