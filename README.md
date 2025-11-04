# 勤怠管理アプリ

Attendance App（Laravel 12 + Sail + Fortify）

---

## 環境構築

Docker(Laravel Sail)を使用しています。

### 必要環境

-   Docker Desktop
-   Git
-   Composer

---

## 0. 事前準備

Docker Desktop を起動してから以下を実行してください。

---

## 1. リポジトリのクローンと移動

````bash
git clone git@github.com:Hana86-86/attendance-app.git
cd attendance-app
---

2. .env ファイル作成

```bash
cp .env.example .env

・  DB の設定を以下に変更してください
```env
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=attendance
DB_USERNAME=sail
DB_PASSWORD=password

---

3. 依存関係のインストール
   (開発環境に必要な依存関係を含めるため、--no-dev オプションはつけないでください。)
```bash
composer install


4. Laravel Sail のインストールと実行ファイルの生成

（ `./vendor/bin/sail` コマンドを確実に生成するために必須の手順です）
```bash
composer require laravel/sail --dev
php artisan sail:install


5. Sail の起動

```bash
./vendor/bin/sail up -d

(Sail 環境では、docker compose pull は実行しないでください。内部レジストリのエラーにより、環境構築に失敗する原因となります。)

6. アプリキー生成

```bash
./vendor/bin/sail artisan key:generate

7. マイグレーション & シーディング（どちらか 1 つを実行）

- 既存 DB を保ったまま初期データを投入したい場合

```bash
./vendor/bin/sail artisan migrate --seed


- 完全初期化して入れ直したい場合

```bash
./vendor/bin/sail artisan migrate:fresh --seed

- 初期データ内容

-   AdminUserSeeder … 管理者アカウントを作成（email: admin@example.com / password: password）
-   StaffUsersSeeder … テスト用のスタッフユーザー 10 名を作成
-   AttendanceMonthSeeder … テスト用の勤怠データを作成

 8. phpMyAdmin へのアクセス
   URL: http://localhost:8080

 9. ブラウザで以下の URL にアクセスしてください:
   http://localhost

---

[ER 図]　 er-diagram.drawio

---

-   認証メール送信設定（Mailtrap 利用）

-   開発環境でのメール送信テストに[Mailtrap](https://mailtrap.io)を使用しています。

設定方法

 1.  DB の設定を以下に変更してください
```env
MAIL_MAILER=smtp
MAIL_HOST=sandbox.smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=MailtrapのUsername
MAIL_PASSWORD=MailtrapのPassword
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS=attendance@example.com（アドレスはダミーでOKです）
MAIL_FROM_NAME="Attendance App"

---

-MAIL_USERNAME と MAIL_PASSWORD は各自の Mailtrap アカウントで発行された値を使用してください。
このリポジトリには認証情報は含めていません。

---

 2. Mailtrap のダッシュボードにログインし、受信したテストメールを確認できます。
   URL: https://mailtrap.io

---

・休憩時間の仕様

-   休憩は、1 秒でも発生した場合は 1 分として計上します。
-   集計上は 1 分単位で処理されますが、詳細画面には実際の打刻時間が表示されます。

    例）
    • 09:00〜09:00 の場合 → 休憩時間として 0:01（1 分）を集計
    • 09:00〜09:05 の場合 → 0:05（5 分）を集計

---

- マイグレーションについて

-   アプリケーションは Laravel Sail 環境で開発しており、
    `php artisan schema:dump --prune` を使用してマイグレーションを統合しています。

クリーン環境では以下のコマンドで再構築可能です：

```bash
./vendor/bin/sail up -d
./vendor/bin/sail artisan migrate --seed

---
````
