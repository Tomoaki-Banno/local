drop table if exists received_header;
create table received_header
(
    received_header_id serial primary key,      /* 受注ヘッダーid */

    received_number text unique not null,       /* 受注番号 */
    customer_received_number text,              /* 客先注番 */
    customer_id int not null,                   /* 得意先id */
    delivery_customer_id int,                   /* 発送先id */
    received_date date not null,                /* 受注日 */
    worker_id int,                              /* 担当者(自社) */
    section_id int,                             /* 部門(自社) */
    guarantee_grade int,                        /* 確定度  0:確定-fix, 1:予約-reserve */
    estimate_header_id int,                     /* 見積ヘッダーid（見積から受注へ転記した場合に記録される） */
    remarks_header text not null,               /* 備考1 */
    remarks_header_2 text,                      /* 備考2 */
    remarks_header_3 text,                      /* 備考3 */

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