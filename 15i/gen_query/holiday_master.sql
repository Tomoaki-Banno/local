drop table if exists holiday_master;
create table holiday_master
(
    holiday date primary key,   /* 休日 */

    record_creator text,
    record_create_date timestamp,
    record_create_func text,
    record_updater text,
    record_update_date timestamp,
    record_update_func text
);