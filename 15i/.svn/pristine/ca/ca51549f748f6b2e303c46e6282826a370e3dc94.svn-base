drop table if exists received_dummy_child_item;
create table received_dummy_child_item
(
    received_detail_id_for_dummy int not null,
    child_item_id int not null,               
    quantity numeric not null,                

    record_creator text,
    record_create_date timestamp,
    record_create_func text,
    record_updater text,
    record_update_date timestamp,
    record_update_func text,

    primary key (received_detail_id_for_dummy, child_item_id)
);
create index received_dummy_child_item_index1 on received_dummy_child_item(received_detail_id_for_dummy);