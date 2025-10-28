<?php

namespace App\Models;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable;
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'email_verified_at',
    ];
    protected $attributes = [
        'role' => 'user', // デフォルトはスタッフ
    ];
    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
    // メール認証用のメールを送信
    public function sendEmailVerificationNotification(): void
{
    $this->notify(new class extends VerifyEmail {
        public function toMail($notifiable): MailMessage
        {
            return (new MailMessage)
                ->subject('【' . config('app.name') . '】メールアドレスの確認')
                ->line('以下のボタンをクリックして、メールアドレスの確認を完了してください。')
                ->action('メールアドレスを確認する', $this->verificationUrl($notifiable))
                ->line('このメールに心当たりがない場合は破棄してください。');
        }
    });
}
    public function attendances()
    {
        return $this->hasMany(\App\Models\Attendance::class);
    }

    public function getIsAdminAttribute(): bool
{
    return $this->role === 'admin';
}

public function isAdmin(): bool
{
    return $this->role === 'admin';
}

public function isStaff(): bool
{
    return $this->role === 'user';
}

}