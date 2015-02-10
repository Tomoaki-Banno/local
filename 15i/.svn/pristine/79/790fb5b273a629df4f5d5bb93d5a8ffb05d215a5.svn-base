drop table if exists page_info;
create table page_info
(
    user_id int not null,               /* ユーザーid */
    action text not null,               /* action名 */

    pin_info text,                      /* JSONデータ */
    orderby  text,                      /* ソート */

    subsum_criteria text,               /* 小計基準。15i */
    subsum_criteria_datetype int,       /* 小計基準のデータ型。15i */

    saved_search_condition_info text,   /* 保存された表示条件。JSONデータ。15i */

    last_show_time timestamp,           /* 最終表示日。15i */

    record_creator text,
    record_create_date timestamp,
    record_create_func text,
    record_updater text,
    record_update_date timestamp,
    record_update_func text
);
