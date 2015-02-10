drop table if exists chat_header;
create table chat_header
(
    chat_header_id serial primary key,

    title text not null,
    author_id int not null,
    create_time timestamp not null,
    is_pin bool,        /* 未使用。settingに移行 */
    is_ecom bool,
    is_system bool,
    chat_group_id int,

    /* 以下はレコードとリンクしたスレッドのみ */
    action_group text,  
    record_id int,
    temp_user_id int,   /* 仮作成状態のとき */

    record_creator text,
    record_create_date timestamp,
    record_create_func text,
    record_updater text,
    record_update_date timestamp,
    record_update_func text
);
