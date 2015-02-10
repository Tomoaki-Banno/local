drop table if exists delivery_detail;
create table delivery_detail
(
    delivery_detail_id serial primary key,          /* 納品明細id */
    delivery_header_id int,                         /* 納品ヘッダーid */

    line_no int not null,                           /* 行番号 */

    received_detail_id int not null,                /* 受注明細id */
    delivery_quantity numeric not null,             /* seiban_stock + free_stock */
    seiban_stock_quantity numeric not null,         /* 製番在庫からの納品済数 */
    free_stock_quantity numeric not null,           /* フリー在庫からの納品済数 */
    tax_rate numeric,                               /* 税率 */
    tax_class int,                                  /* 課税区分「0 or null(課税)/1(非課税)」 */

    delivery_price numeric not null,                /* 納品単価 */
    delivery_amount numeric,                        /* 納品金額 delivery_quantity * delivery_price を customer_master.roundingにしたがって丸めた金額 */
    delivery_tax numeric,                           /* 消費税額（納品明細行レベル） */
    sales_base_cost numeric,                        /* 販売原単価 */
    sales_base_cost_total numeric,                  /* 販売原価 */

    foreign_currency_delivery_price numeric,        /* 納品単価（外貨） */
    foreign_currency_delivery_amount numeric,       /* 納品金額（外貨） */
    foreign_currency_delivery_tax numeric,          /* 消費税額（外貨） */
    foreign_currency_sales_base_cost numeric,       /* 販売原単価（外貨） */
    foreign_currency_sales_base_cost_total numeric, /* 販売原価（外貨） */

    location_id int not null,                       /* 出庫ロケーションID（0は規定ロケを意味する） */
    use_lot_no text,                                /* 製造/購買ロット */
    remarks text,                                   /* 明細備考 */

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
create index delivery_detail_index1 on delivery_detail (received_detail_id);
create index delivery_detail_index2 on delivery_detail (delivery_header_id);