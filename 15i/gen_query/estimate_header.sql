drop table if exists estimate_header;
create table estimate_header
(
    estimate_header_id serial primary key,  /* 見積ヘッダーid */

    estimate_number text unique,            /* 見積番号 */
    customer_id int,                        /* 得意先id（登録必須ではなく「得意先選択」を行った場合のみ保存される） */
    customer_name text not null,            /* 得意先名 */
    estimate_date date,                     /* 発行日 */
    subject text,                           /* 件名 */
    customer_zip text,                      /* 郵便番号 */
    customer_address1 text,                 /* 得意先住所1 */
    customer_address2 text,                 /* 得意先住所2 */
    customer_tel text,                      /* 得意先TEL */
    customer_fax text,                      /* 得意先FAX */
    person_in_charge text,                  /* 客先担当者名 */
    delivery_date text,                     /* 受渡期日 */
    delivery_place text,                    /* 受渡場所 */
    mode_of_dealing text,                   /* お支払条件 */
    expire_date text,                       /* 有効期限 */
    worker_id int,                          /* 自社担当者id */
    section_id int,                         /* 自社部門id */
    estimate_rank int default 0,            /* ランク 12i rev.20120918 */
    remarks text,                           /* 備考 */

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