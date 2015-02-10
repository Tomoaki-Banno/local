drop table if exists received_detail;
create table received_detail
(
    received_detail_id serial primary key,      /* 受注明細id */
    received_header_id int,                     /* 受注ヘッダーid */

    line_no int,                                /* 明細行番号 */

    seiban text,                                /* 製番 */
    item_id int,                                /* 品目id */
    received_quantity numeric,                  /* 受注数 */
    product_price numeric,                      /* 受注単価 */
    dead_line date,                             /* 受注納期 */
    sales_base_cost numeric,                    /* 販売原単価 */

    foreign_currency_id int,                    /* 取引通貨id */
    foreign_currency_rate numeric,              /* 適用レート */
    foreign_currency_product_price numeric,     /* 単価(外貨) */
    foreign_currency_sales_base_cost numeric,   /* 販売原単価(外貨) */

    remarks text,                               /* 受注明細備考1 */
    remarks_2 text,                             /* (15i)受注明細備考2 */

    received_printed_flag bool,                 /* 出荷指示書印刷フラグ（帳票を印刷したらtrue） */
    customer_received_printed_flag bool,        /* 発注書印刷フラグ（帳票を印刷したらtrue） */
    delivery_completed bool,                    /* 完了フラグ */

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
create unique index received_unique_index0 on received_detail(seiban);