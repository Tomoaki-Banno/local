drop table if exists app_device_token;
create table app_device_token
(
    device_token text primary key,
    endpoint_arn text not null,
    user_id int,

    record_creator text,
    record_create_date timestamp,
    record_create_func text,
    record_updater text,
    record_update_date timestamp,
    record_update_func text
);
