drop table if exists chat_detail;
create table chat_detail
(
    chat_detail_id serial primary key,
    chat_header_id int not null,

    user_id int not null,
    chat_time timestamp not null,
    content text not null,
    file_name text,
    original_file_name text,
    file_size numeric,
    image_width int,
    image_height int,
    ecom_chat_key int,

    record_creator text,
    record_create_date timestamp,
    record_create_func text,
    record_updater text,
    record_update_date timestamp,
    record_update_func text
);
create index chat_detail_index1 on chat_detail (user_id);