drop table if exists rate_master;
create table rate_master
(
    rate_id serial,             /* 為替レートid */

    currency_id serial,         /* 取引通貨id */
    rate_date date not null,    /* 適用開始日 */
    rate numeric,               /* 為替レート */
    remarks text,               /* 備考 */

    record_creator text,
    record_create_date timestamp,
    record_create_func text,
    record_updater text,
    record_update_date timestamp,
    record_update_func text
);
create unique index rate_master_index1 on rate_master (currency_id, rate_date);
