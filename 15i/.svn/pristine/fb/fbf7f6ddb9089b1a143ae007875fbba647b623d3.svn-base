drop table if exists bom_master;
create table bom_master
(
    item_id int not null,           /* 親品目id */
    child_item_id int not null,     /* 子品目id */
    quantity numeric not null,      /* 員数 */
    seq int,                        /* 順番 */

    record_creator text,
    record_create_date timestamp,
    record_create_func text,
    record_updater text,
    record_update_date timestamp,
    record_update_func text,

    primary key (item_id, child_item_id)
);
create index bom_master_index1 on bom_master(item_id);
create index bom_master_index2 on bom_master(child_item_id);