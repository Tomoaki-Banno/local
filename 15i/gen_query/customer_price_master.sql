drop table if exists customer_price_master;
create table customer_price_master
(
    customer_price_id serial,           /* 得意先販売価格id */

    customer_id int not null,           /* 得意先id */
    item_id int not null,               /* 品目id */
    selling_price numeric not null,     /* 販売価格 */

    record_creator text,
    record_create_date timestamp,
    record_create_func text,
    record_updater text,
    record_update_date timestamp,
    record_update_func text
);
create unique index customer_price_master_index1 on customer_price_master (customer_id, item_id);
