<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('benchmark_items', function (Blueprint $table) {
            $table->id();
            $table->string('title', 100)->index();
            $table->string('category', 50)->index();
            $table->text('content');
            $table->bigInteger('view_count')->default(0);
            $table->json('extra_data')->nullable();
            $table->smallInteger('status')->default(1);
            $table->bigInteger('data_version')->default(1);
            $table->timestamps();
        });

        Schema::create('benchmark_logs', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('item_id')->index();
            $table->string('action', 50);
            $table->string('ip', 45)->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('benchmark_logs');
        Schema::dropIfExists('benchmark_items');
    }
};
