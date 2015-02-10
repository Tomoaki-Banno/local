drop table if exists customer_group_master;
create table customer_group_master
(
    customer_group_id serial primary key,   /* 取引先グループid */
    customer_group_code text not null,      /* 取引先グループコード */
    customer_group_name text not null,      /* 取引先グループ名 */

    record_creator text,
    record_create_date timestamp,
    record_create_func text,
    record_updater text,
    record_update_date timestamp,
    record_update_func text
);