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
        if (empty($m) && $m !== 0) return '';
        $m = (int)$m;
        $h = intdiv($m, 60);
        $i = $m % 60;
        return sprintf('%d:%02d', $h, $i); 
    }
}