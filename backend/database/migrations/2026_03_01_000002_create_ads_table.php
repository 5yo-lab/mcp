<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->constrained()->cascadeOnDelete();
            $table->string('headline');
            $table->text('body_text');
            $table->string('niche', 64);
            $table->string('format', 16);
            $table->string('status', 16);
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->unsignedInteger('impression_rank')->nullable();
            $table->json('countries');
            $table->timestamp('discovered_at');
            $table->timestamps();

            $table->index(['status', 'start_date']);
            $table->index(['niche', 'format', 'status']);
            $table->index('brand_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ads');
    }
};
