drop table if exists chat_like;
create table chat_like
(
    chat_detail_id int not null,
    user_id int not null,

    record_creator text,
    record_create_date timestamp,
    record_create_func text,
    record_updater text,
    record_update_date timestamp,
    record_update_func text,

    PRIMARY KEY (chat_detail_id, user_id)
);
create index chat_like_index1 on chat_like (chat_detail_id);