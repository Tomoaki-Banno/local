drop table if exists seiban_master;
create table seiban_master
(
    current_number int primary key,     /* 製番 */

    record_creator text,
    record_create_date timestamp,
    record_create_func text,
    record_updater text,
    record_update_date timestamp,
    record_update_func text
);