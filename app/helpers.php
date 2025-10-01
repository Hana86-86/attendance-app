<?php

if (!function_exists('m2hm')) {
    /**
     * 分を「H:ii」形式に変換する関数
     * 例: 125 → "2:05"
     *
     * @param int|null $m 分（nullや0は空文字を返す）
     * @return string
     */
    function m2hm($m): string {
        if (empty($m) && $m !== 0) return ''; // nullや空文字なら空を返す
        $m = (int)$m; //強制的に数値にキャストする
        $h = intdiv($m, 60); // 60で割って「時間」を計算
        $i = $m % 60;        // 余りを「分」として計算
        return sprintf('%d:%02d', $h, $i); // 例: "2:05"
    }
}