drop table if exists accepted;
create table accepted
(
    accepted_id serial primary key,             /* 受入id */
    order_detail_id int4 not null,              /* 発注明細id */
    order_no text not null,                     /* オーダー番号 */
    accepted_date date not null,                /* 受入日 */
    inspection_date date,                       /* 検収日 */
    payment_date date,                          /* 支払予定日 */
    lot_no text,                                /* 購買ロット番号 */
    payment_report_timing int,                  /* 仕入計上基準 0:受入日、1:検収日 */
    tax_rate numeric,                           /* 税率 */
    tax_class int,                              /* 課税区分  0orNull:課税, 1:非課税 */
    rounding text,                              /* 端数処理  空欄:なし, round:四捨五入, floor:切捨, ceil:切上 */
    precision int not null default 0,           /* 小数点以下の桁数（請求先） */
    accepted_quantity numeric not null,         /* 受入数 */
    accepted_price numeric,                     /* 受入単価 */
    accepted_tax numeric,                       /* 消費税（品目マスタに基づく） */
    accepted_amount numeric,                    /* 受入金額 */
    remarks text,                               /* 備考 */

    order_seiban text not null,                 /* 製番（オーダー） */
    stock_seiban text not null,                 /* 製番（在庫） */
    location_id int not null,                   /* ロケーションid（0は規定ロケを意味する） */

    foreign_currency_rate numeric,              /* 為替レート */
    foreign_currency_accepted_price numeric,    /* 外貨受入単価 */
    foreign_currency_accepted_amount numeric,   /* 外貨受入金額 */

    subcontract_inout_achievement_id int,       /* 外製工程受入時、in_outレコードに付与したachievement_id */

    use_by date,                                /* (15i) 消費期限 */

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
create index accepted_index1 on accepted (order_detail_id);