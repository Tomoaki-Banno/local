drop table if exists chat_user;
create table chat_user
(
    chat_header_id int not null,
    user_id int not null,
    readed_chat_detail_id int,

    record_creator text,
    record_create_date timestamp,
    record_create_func text,
    record_updater text,
    record_update_date timestamp,
    record_update_func text,

    PRIMARY KEY (chat_header_id, user_id)
);
create index chat_user_index1 on chat_user (user_id);