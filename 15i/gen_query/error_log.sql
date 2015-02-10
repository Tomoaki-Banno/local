drop table if exists error_log;
create table error_log
(
    error_time timestamp not null,      /* エラー発生時刻 */
    user_name text,                     /* アクセスユーザー名 */
    ip text,                            /* ユーザーIP */
    function_name text,                 /* 実行ファンクション */
    call_stack text,                    /* コールスタック */
    error_no text,                      /* エラー番号 */
    error_comment text,                 /* エラー内容 */
    error_query text,                   /* 実行クエリ */
    remarks text                        /* 備考 */
);

