drop table if exists chat_star;
create table chat_star
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
create index chat_star_index1 on chat_star (chat_detail_id);