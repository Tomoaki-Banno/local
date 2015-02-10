drop table if exists price_percent_group_master;
create table price_percent_group_master
(
    price_percent_group_id serial primary key,      /* 掛率グループid */
    price_percent_group_code text unique not null,  /* 掛率グループコード */
    price_percent_group_name text not null,         /* 掛率グループ名 */
    price_percent numeric not null,                 /* 掛率 */
    remarks text,                                   /* 備考 */

    record_creator text,
    record_create_date timestamp,
    record_create_func text,
    record_updater text,
    record_update_date timestamp,
    record_update_func text
);
