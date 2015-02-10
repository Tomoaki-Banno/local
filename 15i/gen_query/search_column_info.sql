drop table if exists search_column_info;
create table search_column_info
(
    user_id int not null,   /* ユーザーid */
    action text not null,   /* action名 */
    column_key text,        /* カラム */

    column_number int,      /* 表示順 */
    column_hide bool,       /* 列の非表示「true(非表示)/false or null(表示)」 */

    record_creator text,
    record_create_date timestamp,
    record_create_func text,
    record_updater text,
    record_update_date timestamp,
    record_update_func text
);
create unique index search_column_info_index1 on search_column_info (user_id, action, column_key);
create index search_column_info_index2 on search_column_info (column_number);
