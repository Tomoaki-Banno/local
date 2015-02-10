drop table if exists waster_detail;
create table waster_detail
(
    achievement_id integer not null,                /* 実績id */
    line_number int not null,                       /* 不適合番号 */

    waster_id integer not null,                     /* 不適合理由id */
    waster_quantity numeric not null default 0,     /* 数量 */

    record_creator text,
    record_create_date timestamp,
    record_create_func text,
    record_updater text,
    record_update_date timestamp,
    record_update_func text,

    unique(achievement_id, line_number)
);