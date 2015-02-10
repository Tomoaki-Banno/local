drop table if exists user_master;
create table user_master
(
    user_id serial primary key,             /* ユーザーid */
    login_user_id text unique,              /* 15i: ログインユーザーID */
    user_name text unique,                  /* ユーザー名 */
    password text not null,                 /* パスワード */
    company_id int not null,                /* 自社id */
    last_login_date timestamp,              /* ログイン日時 */
    last_logout_date timestamp,             /* ログアウト日時 */
    last_password_change_date timestamp,    /* 最終パスワード変更日時 */
    language text,                          /* 使用する言語。ja,en,vi等。空欄は自動判別 */
    setting text,
    start_action text,                      /* ログイン後の表示画面 */
    account_lockout bool,                   /* アカウントロックアウト */
    password_miss_count int,                /* 連続ログイン失敗（パスワード間違い）回数 */
    password_miss_time timestamp,           /* 最終ログイン失敗時刻 */
    customer_id int,                        /* 取引先id（簡易EDI機能） */
    section_id int,                         /* 15i: 部門ID */
    restricted_user bool,                   /* 15i: スケジュール・トークボード限定ユーザー */

    image_file_name text,                   /* 15i: 画像ファイル名 */
    original_image_file_name text,          /* 15i: 元画像ファイル名 */

    background_mode int default 0,          /* 15i: パティオ画像セレクトモード */
    background_image text,                  /* 15i: 個別セレクト（パティオ画像リスト） */

    show_chat_dialog bool,                  /* 15i: チャットダイアログ表示フラグ */
    chat_dialog_x int,                      /* 15i: チャットダイアログの位置 */
    chat_dialog_y int,                      /* 15i: チャットダイアログの位置 */
    chat_dialog_width int,                  /* 15i: チャットダイアログのサイズ */
    chat_dialog_height int,                 /* 15i: チャットダイアログのサイズ */
    last_chat_header_id int,                /* 15i: 最終表示チャット */

    show_first_intro bool,                  /* 15i: intro初回自動表示済 */

    record_creator text,
    record_create_date timestamp,
    record_create_func text,
    record_updater text,
    record_update_date timestamp,
    record_update_func text
);
