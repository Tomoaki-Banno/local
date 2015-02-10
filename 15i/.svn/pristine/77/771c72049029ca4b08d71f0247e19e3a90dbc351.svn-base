drop table if exists permission_master;
create table permission_master
(
    user_id int,                /* ユーザーid */
    class_name text,            /* リンク画面 */
    permission int not null,    /* 権限「1(読み取りのみ)/2(読み書き可能)」 */

    record_creator text,
    record_create_date timestamp,
    record_create_func text,
    record_updater text,
    record_update_date timestamp,
    record_update_func text,

    primary key (user_id, class_name)
);