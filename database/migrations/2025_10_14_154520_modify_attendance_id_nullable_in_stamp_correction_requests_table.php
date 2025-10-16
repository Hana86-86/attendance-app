<?php

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
        Schema::table('stamp_correction_requests', function (Blueprint $table) {
            $table->dropForeign(['attendance_id']);
        });

        Schema::table('stamp_correction_requests', function (Blueprint $table) {
            $table->unsignedBigInteger('attendance_id')->nullable()->change();
        });

        Schema::table('stamp_correction_requests', function (Blueprint $table) {
            $table->foreign('attendance_id')
                    ->references('id')->on('attendances')
                    ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('stamp_correction_requests', function (Blueprint $table) {
            $table->dropForeign(['attendance_id']);
        });

        Schema::table('stamp_correction_requests', function (Blueprint $table) {
            $table->unsignedBigInteger('attendance_id')->nullable(false)->change();
        });

        Schema::table('stamp_correction_requests', function (Blueprint $table) {
            $table->foreign('attendance_id')
                    ->references('id')->on('attendances')
                    ->cascadeOnDelete();
        });
    }
};