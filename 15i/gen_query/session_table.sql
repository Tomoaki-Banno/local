drop table if exists session_table;
create table session_table
(
    session_id text primary key,                /* セッションid */
    user_id int not null,                       /* ユーザーid */
    login_date timestamp not null,              /* ログイン日時 */
    company_id int not null,
    temp_flag int,                              /* 1なら仮ログイン状態（パスワード変更画面のみアクセス許可）*/
    excel_flag bool not null default false,     /* エクセルセッションかどうか */

    record_creator text,
    record_create_date timestamp,
    record_create_func text,
    record_updater text,
    record_update_date timestamp,
    record_update_func text
);