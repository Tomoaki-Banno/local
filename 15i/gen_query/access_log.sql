drop table if exists access_log;
create table access_log
(
    url text not null,                  /* アクセスURL */
    action text not null,               /* action名 */
    user_name text not null,            /* アクセスユーザー名 */
    ip text not null,                   /* ユーザーIP */
    access_time timestamp not null,     /* アクセス日時 */
    duration int,                       /* 実行時間（ms） */
    remarks text,                       /* 備考 */

    record_creator text,
    record_create_date timestamp,
    record_create_func text,
    record_updater text,
    record_update_date timestamp,
    record_update_func text
);