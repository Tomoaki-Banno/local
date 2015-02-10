drop table if exists estimate_detail;
create table estimate_detail
(
    estimate_detail_id serial primary key,      /* 見積明細id */
    estimate_header_id int,                     /* 見積ヘッダーid */

    line_no int,                                /* 行番号 */

    item_id int,                                /* 品目id（品目名手入力の場合はnull） */
    item_code text not null,                    /* 品目コード */
    item_name text not null,                    /* 品目名 */
    quantity numeric not null,                  /* 数量 */
    measure text,                               /* 単位 */
    tax_class int,                              /* 課税区分「0 or null(課税)/1(非課税)」 */

    sale_price numeric not null,                /* 見積単価 */
    estimate_amount numeric,                    /* 見積金額 (15i) quantity * sale_price を customer_master.roundingにしたがって丸めた金額 */
    estimate_tax numeric,                       /* 消費税額 */
    base_cost numeric not null,                 /* 販売原単価 */
    base_cost_total numeric,                    /* 販売原価 (15i) */
    gross_margin numeric not null,              /* 粗利 */

    remarks text,                               /* 見積明細備考 */
    remarks_2 text,                             /* 見積明細備考2 (15i)  */

    /* 外貨得意先が指定されたときのみ */
    foreign_currency_id int,                    /* 取引通貨id */
    foreign_currency_rate numeric,              /* 適用レート */
    foreign_currency_sale_price numeric,        /* 単価(外貨) */
    foreign_currency_estimate_amount numeric,   /* 納品金額（外貨） (15i) */
    foreign_currency_estimate_tax numeric,      /* 税額(外貨) */
    foreign_currency_base_cost numeric,         /* 販売原単価(外貨) */
    foreign_currency_base_cost_total numeric,   /* 販売原価（外貨） (15i) */

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