drop table if exists tax_rate_master;
create table tax_rate_master
(
    tax_rate_id serial primary key,     /* 税率id */

    tax_rate numeric not null,          /* 税率 */
    apply_date date,                    /* 適用開始日 */

    record_creator text,
    record_create_date timestamp,
    record_create_func text,
    record_updater text,
    record_update_date timestamp,
    record_update_func text
);
create unique index tax_rate_master_index1 on tax_rate_master (apply_date);