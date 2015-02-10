drop table if exists order_header;
create table order_header
(
    order_header_id serial primary key, /* オーダーヘッダーid */

    order_id_for_user text unique,   /* 注文書番号（注文書ごとに1つ。製造指示書ではnull。13iまではnumericだった）*/
    order_date date not null,           /* オーダー日 */
    partner_id int,                     /* 手配先ID（内製は0） */
    classification int not null,        /* 区分「0(製造指示書)/1(注文書)/2(外製)」 */
    mrp_flag bool,                      /* 所要量計算結果からの取り込みのときtrue */
    order_printed_flag bool,            /* 帳票を印刷したらtrue */
    partner_order_printed_flag bool,    /* 納品書を印刷したらtrue（注文受信） */
    worker_id int,                      /* 担当者id */
    section_id int,                     /* 部門id */
    delivery_partner_id int,            /* 発送先id */
    remarks_header text not null,       /* 備考 */

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