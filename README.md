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
   プロジェクトルートで以下のコマンドを実行し、Docker コンテナを起動します
   起動時にはイメージのダウンロードに時間がかかる場合があります。

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

7. phpMyAdmin へのアクセス
   URL: http://localhost:8080

---
[ER 図]　er-diagram.drawio

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

