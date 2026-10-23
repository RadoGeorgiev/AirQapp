<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('air_quality_measurements', function (Blueprint $table): void {
            $table->id();
            $table->string('city', 32);
            $table->dateTime('recorded_at');
            $table->decimal('pm2_5', 8, 3);
            $table->unique(['city', 'recorded_at']);
            $table->index(['recorded_at', 'city', 'pm2_5']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('air_quality_measurements');
    }
};
