drop table if exists currency_master;
drop sequence if exists currency_master_currency_id_seq;
create table currency_master
(
    currency_id serial,                     /* 取引通貨id */

    currency_name text not null unique,     /* 取引通貨 */

    record_creator text,
    record_create_date timestamp,
    record_create_func text,
    record_updater text,
    record_update_date timestamp,
    record_update_func text
);
