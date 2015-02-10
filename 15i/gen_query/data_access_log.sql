drop table if exists data_access_log;
create table data_access_log
(
    table_name text not null,           /* データ */
    classification text not null,       /* 種別 */
    user_name text not null,            /* ユーザー名 */
    access_time timestamp not null,     /* 更新日時 */
    remarks text,                       /* 備考 */

    record_creator text,
    record_create_date timestamp,
    record_create_func text,
    record_updater text,
    record_update_date timestamp,
    record_update_func text
);