勤怠管理アプリ
Attendance App（Laravel 12 + Sail + Fortify）

環境構築
Docker(Laravel Sail)を使用しています

必要環境

-   Docker Desktop
-   Git
-   Composer

---

1.リポジトリのクローン

```bash
git clone git@github.com:Hana86-86/attendance-app.git
cd attendance-app
```

2. .env ファイル作成
   cp .env.example .env

-   DB の設定を以下に変更
    DB_CONNECTION=mysql
    DB_HOST=mysql
    DB_PORT=3306
    DB_DATABASE=attendance
    DB_USERNAME=sail
    DB_PASSWORD=password

3. Sail の起動
   プロジェクトルートで以下のコマンドを実行し、Docker コンテナを起動します。

```bash
./vendor/bin/sail up -d
```

4.依存関係のインストール
Composer と npm の依存関係をコンテナ内でインストールします。

```bash
./vendor/bin/sail composer install
./vendor/bin/sail npm install
./vendor/bin/sail npm run dev
```

5. アプリキー生成

```bash
./vendor/bin/sail artisan key:generate
```

6. マイグレーション実行

```bash
./vendor/bin/sail artisan migrate
```

7. 初期データ投入（Seeder 実行）

```bash
./vendor/bin/sail artisan migrate:fresh --seed
```

• AdminUserSeeder … 管理者アカウントを作成（email: admin@example.com / password: password）
• StaffUsersSeeder … テスト用のスタッフユーザー 10 名を作成
• AttendanceMonthSeeder … テスト用の勤怠データを作成

8. phpMyAdmin へのアクセス
   URL: http://localhost:8080

9. ブラウザで以下の URL にアクセスしてください:
   http://localhost

---

[ER 図]　 er-diagram.drawio

---

認証メール送信設定（Mailtrap 利用）

開発環境でのメール送信テストに[Mailtrap](https://mailtrap.io)を使用しています。

設定方法

1. .env ファイルに以下を追記してください。

```env
MAIL_MAILER=smtp
MAIL_HOST=sandbox.smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=MailtrapのUsername
MAIL_PASSWORD=MailtrapのPassword
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS=attendance@example.com（アドレスはダミーでOKです）
MAIL_FROM_NAME="Attendance App"

-MAIL_USERNAME と MAIL_PASSWORD は各自の Mailtrap アカウントで発行された値を使用してください。
このリポジトリには認証情報は含めていません。

2. Mailtrapのダッシュボードにログインし、受信したテストメールを確認できます。
URL: https://mailtrap.io

```

・休憩時間の仕様

-   休憩は、1 秒でも発生した場合は 1 分として計上します。
-   集計上は 1 分単位で処理されますが、詳細画面には実際の打刻時間が表示されます。

    例）
    • 09:00〜09:00 の場合 → 休憩時間として 0:01（1 分）を集計
    • 09:00〜09:05 の場合 → 0:05（5 分）を集計

---
