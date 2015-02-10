drop table if exists control_info;
create table control_info
(
    user_id int not null,   /* ユーザーid */
    action text not null,   /* action名 */
    control_key text,       /* コントロール */

    control_number int,     /* 表示順 */
    control_hide bool,      /* コントロールの非表示「true(非表示)/false or null(表示)」 */

    record_creator text,
    record_create_date timestamp,
    record_create_func text,
    record_updater text,
    record_update_date timestamp,
    record_update_func text
);
create unique index control_info_index1 on control_info (user_id, action, control_key);