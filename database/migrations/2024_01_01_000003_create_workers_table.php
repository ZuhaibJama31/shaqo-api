<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->text('bio')->nullable();
            $table->decimal('hourly_rate', 8, 2)->default(0);
            $table->integer('experience_years')->nullable();
            $table->boolean('is_available')->default(true);
            $table->decimal('rating', 3, 2)->default(0);
            $table->integer('total_jobs')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workers');
    }
};
