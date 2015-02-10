drop table if exists dropdown_info;
create table dropdown_info
(
    user_id integer not null,           /* ユーザーid */
    category text not null,             /* カテゴリ */
    orderby_field text not null,        /* ソート列 */
    orderby_desc integer default 0,     /* ソート順「0(昇順)/1(降順)」 */

    record_creator text,
    record_create_date timestamp without time zone,
    record_create_func text,
    record_updater text,
    record_update_date timestamp without time zone,
    record_update_func text
);
create unique index dropdown_info_index1 on dropdown_info (user_id, category);