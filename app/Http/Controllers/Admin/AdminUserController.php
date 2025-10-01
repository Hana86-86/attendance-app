<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;

class AdminUserController extends Controller
{
    // 検索なし・全件表示
    public function index()
    {
        $users = User::where('role', 'user')  // 管理者は除外
            ->orderBy('id')
            ->get(['id','name','email']);

        $month = now()->format('Y-m');        // 月次リンク用（当月）

        return view('admin.users.index', compact('users','month'));
    }

}