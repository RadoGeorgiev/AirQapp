<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('air_quality_syncs', function (Blueprint $table): void {
            $table->id();
            $table->string('status', 16)->index();
            $table->date('requested_from');
            $table->date('requested_to');
            $table->unsignedInteger('rows_synced')->default(0);
            $table->text('error')->nullable();
            $table->dateTime('started_at')->index();
            $table->dateTime('finished_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('air_quality_syncs');
    }
};
