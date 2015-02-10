drop table if exists alert_mail_master;
create table alert_mail_master
(
    alert_mail_id serial primary key,   /* アラートメールid */
    mail_address_id int not null,       /* メールアドレスid */
    alert_id text not null,             /* アラートid */

    record_creator text,
    record_create_date timestamp,
    record_create_func text,
    record_updater text,
    record_update_date timestamp,
    record_update_func text
);
