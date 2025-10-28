<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stamp_correction_requests', function (Blueprint $table) {
            $table->json('requested_break')->nullable()->after('requested_clock_out');
        });

        // 既存データを移行
        DB::table('stamp_correction_requests')
            ->select('id','requested_break_start','requested_break_end')
            ->orderBy('id')
            ->chunkById(500, function ($rows) {
                foreach ($rows as $r) {
                    $start = $r->requested_break_start ? (string)$r->requested_break_start : null;
                    $end   = $r->requested_break_end   ? (string)$r->requested_break_end   : null;

                    $json = null;
                    if ($start || $end) {
                        $json = json_encode([['start' => $start, 'end' => $end]]);
                    }

                    DB::table('stamp_correction_requests')
                        ->where('id', $r->id)
                        ->update(['requested_break' => $json]);
                }
            });

        // 古い2列を削除
        Schema::table('stamp_correction_requests', function (Blueprint $table) {
            if (Schema::hasColumn('stamp_correction_requests', 'requested_break_start')) {
                $table->dropColumn('requested_break_start');
            }
            if (Schema::hasColumn('stamp_correction_requests', 'requested_break_end')) {
                $table->dropColumn('requested_break_end');
            }
        });
    }

    public function down(): void
    {
        // 巻き戻し：古い2列を復活  JSON の先頭要素だけ戻す
        Schema::table('stamp_correction_requests', function (Blueprint $table) {
            $table->dateTime('requested_break_start')->nullable()->after('requested_clock_out');
            $table->dateTime('requested_break_end')->nullable()->after('requested_break_start');
        });

        DB::table('stamp_correction_requests')
            ->select('id','requested_break')
            ->orderBy('id')
            ->chunkById(500, function ($rows) {
                foreach ($rows as $r) {
                    $start = $end = null;
                    if ($r->requested_break) {
                        $arr = json_decode($r->requested_break, true) ?: [];
                        $first = $arr[0] ?? [];
                        $start = $first['start'] ?? null;
                        $end   = $first['end'] ?? null;
                    }
                    DB::table('stamp_correction_requests')
                        ->where('id', $r->id)
                        ->update([
                            'requested_break_start' => $start,
                            'requested_break_end'   => $end,
                        ]);
                }
            });

        Schema::table('stamp_correction_requests', function (Blueprint $table) {
            $table->dropColumn('requested_break');
        });
    }
};