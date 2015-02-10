drop table if exists staff_schedule_user;
create table staff_schedule_user
(
    schedule_id int not null,   /* スケジュールid */
    user_id int not null,       /* ユーザーid */

    record_creator text,
    record_create_date timestamp,
    record_create_func text,
    record_updater text,
    record_update_date timestamp,
    record_update_func text
);
create unique index staff_schedule_user_index1 on staff_schedule_user (schedule_id, user_id);