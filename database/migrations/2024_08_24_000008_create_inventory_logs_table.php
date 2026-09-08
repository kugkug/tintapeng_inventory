<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('location_id')->constrained('locations')->onDelete('cascade');
            $table->enum('change_type', ['sold', 'adjusted', 'received', 'transferred'])->default('adjusted');
            $table->integer('quantity_change');
            $table->string('reference_id')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('product_id');
            $table->index('location_id');
            $table->index('created_at');
            $table->index('change_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_logs');
    }
};
