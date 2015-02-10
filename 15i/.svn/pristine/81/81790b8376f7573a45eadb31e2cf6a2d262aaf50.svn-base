drop table if exists user_template_info;
create table user_template_info
(
    user_id int not null,           /* ユーザーid */
    category text not null,         /* カテゴリ */
    template_name text not null,    /* テンプレート名 */

    record_creator text,
    record_create_date timestamp,
    record_create_func text,
    record_updater text,
    record_update_date timestamp,
    record_update_func text
);
create unique index user_template_info_index1 on user_template_info (user_id, category);