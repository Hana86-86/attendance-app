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
        $table->dropForeign(['reviewed_by']);
        $table->dropColumn('reviewed_by');
        $table->dropColumn('reviewed_at');
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stamp_correction_requests', function (Blueprint $table) {
        $table->foreignId('reviewed_by')->nullable()->constrained('users');
        $table->timestamp('reviewed_at')->nullable();
    });
    }
};
