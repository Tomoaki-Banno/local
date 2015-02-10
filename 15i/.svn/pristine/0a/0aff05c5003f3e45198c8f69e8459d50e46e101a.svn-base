drop table if exists order_child_item;
create table order_child_item
(
    order_detail_id int not null,   /* オーダー明細id */
    child_item_id int not null,     /* 子品目id */
    quantity numeric not null,      /* 数量 */

    record_creator text,
    record_create_date timestamp,
    record_create_func text,
    record_updater text,
    record_update_date timestamp,
    record_update_func text,

    primary key (order_detail_id, child_item_id)
);
create index order_child_item_index1 on order_child_item(order_detail_id);