drop table if exists upload_file_info;
create table upload_file_info (
    action_group text not null,
    record_id int not null,
    file_name text not null,
    original_file_name text not null,
    temp_upload_user_id int,            /* 仮登録状態のときのみ。登録ユーザーID */
    file_size numeric,

    record_creator text,
    record_create_date timestamp without time zone,
    record_create_func text,
    record_updater text,
    record_update_date timestamp without time zone,
    record_update_func text
);
create index upload_file_info_index1 on upload_file_info (action_group, record_id);