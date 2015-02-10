drop table if exists inventory;
create table inventory
(
    inventory_id serial primary key,        /* 棚卸id */

    /* 以下の5つはユニーク */
    item_id int not null,                   /* 品目id */
    seiban text not null,                   /* 製番 */
    location_id int not null,               /* ロケーションid */
    lot_id int not null,                    /* ロットid */
    inventory_date date not null,           /* 棚卸日 */

    inventory_quantity numeric not null,    /* 棚卸数 */
    remarks text,                           /* 備考 */

    record_creator text,
    record_create_date timestamp,
    record_create_func text,
    record_updater text,
    record_update_date timestamp,
    record_update_func text
);
create unique index inventory_index1 on inventory (item_id, seiban, location_id, lot_id, inventory_date);