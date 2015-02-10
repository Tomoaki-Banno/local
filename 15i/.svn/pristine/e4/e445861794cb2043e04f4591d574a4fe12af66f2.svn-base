drop table if exists process_master;
drop sequence if exists process_master_process_id_seq;
/* process_id = 0 は、標準工程をあらわす特殊IDとして使用する。 */
/* そのため MINVALUE 1 を指定している。 */
create sequence process_master_process_id_seq MINVALUE 1;
create table process_master
(
    /* 上記のとおりシーケンスにMINVALUEを指定する必要があるため、serial型は使えない。*/
    /* 動作としては serialと同じ。 */
    process_id INT4 not null DEFAULT nextval('process_master_process_id_seq') primary key,
    process_code text not null,     /* 工程コード */
    process_name text not null,     /* 工程名 */
    equipment_name text not null,   /* 設備名 */
    default_lead_time numeric,      /* 標準LT */

    custom_text_1 text,	/* (15i) カスタム項目 */
    custom_text_2 text,
    custom_text_3 text,
    custom_text_4 text,
    custom_text_5 text,
    custom_text_6 text,
    custom_text_7 text,
    custom_text_8 text,
    custom_text_9 text,
    custom_text_10 text,
    custom_date_1 date,
    custom_date_2 date,
    custom_date_3 date,
    custom_date_4 date,
    custom_date_5 date,
    custom_date_6 date,
    custom_date_7 date,
    custom_date_8 date,
    custom_date_9 date,
    custom_date_10 date,
    custom_numeric_1 numeric,
    custom_numeric_2 numeric,
    custom_numeric_3 numeric,
    custom_numeric_4 numeric,
    custom_numeric_5 numeric,
    custom_numeric_6 numeric,
    custom_numeric_7 numeric,
    custom_numeric_8 numeric,
    custom_numeric_9 numeric,
    custom_numeric_10 numeric,

    record_creator text,
    record_create_date timestamp,
    record_create_func text,
    record_updater text,
    record_update_date timestamp,
    record_update_func text
);