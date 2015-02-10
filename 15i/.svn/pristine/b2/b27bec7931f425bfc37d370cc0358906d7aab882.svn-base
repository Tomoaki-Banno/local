drop table if exists mail_address_master;
create table mail_address_master
(
    mail_address_id serial primary key, /* メールアドレスid */
    mail_address text not null,         /* メールアドレス */
    regist_flag bool not null,          /* 本登録済フラグ */
    regist_password text,               /* 本登録用ワンタイムパスワード */
    regist_limit timestamp,             /* ワンタイムパスワード有効期間 */

    record_creator text,
    record_create_date timestamp,
    record_create_func text,
    record_updater text,
    record_update_date timestamp,
    record_update_func text
);
