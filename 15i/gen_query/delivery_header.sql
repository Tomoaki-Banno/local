drop table if exists delivery_header;
CREATE TABLE delivery_header
(
    delivery_header_id serial primary key,
    delivery_no text unique,                            /* (15i)納品書番号。13i以前は delivery_header_id を納品書番号として使用していた */

    delivery_date date,                                 /* 納品日 */
    inspection_date date,                               /* 検収日 */
    person_in_charge text,                              /* 納品書 担当者名 */
    delivery_printed_flag bool,                         /* 印刷フラグ（帳票を印刷したらtrue） */
    remarks_header text not null,                       /* 備考1 */
    remarks_header_2 text,                              /* 備考2 */
    remarks_header_3 text,                              /* 備考3 */

    receivable_report_timing int,                       /* 売上計上基準  0:納品日, 1:検収日 */
    customer_id int,                                    /* 得意先id */
    delivery_customer_id int,                           /* 発送先id */
    bill_customer_id int,                               /* 請求先id */
    rounding text,                                      /* 請求先 端数処理  空欄:なし, round:四捨五入, floor:切捨, ceil:切上 */
    precision int not null default 0,                   /* 請求先 小数点以下の桁数 */
    tax_category int,                                   /* 請求先 税計算単位  0:請求書単位, 1:納品書単位, 2:納品明細単位 */
    bill_pattern int,                                   /* 請求先 請求パターン  0:締め-残高表示なし, 1:締め-残高表示あり, 2:都度 */
    foreign_currency_id int,                            /* 請求先 取引通貨 (外貨id) */
    foreign_currency_rate numeric,                      /* 為替レート */
    bill_header_id int,                                 /* 請求書id */

    delivery_note_amount numeric not null default 0,    /* 納品書金額 */
    delivery_note_tax numeric not null default 0,       /* 消費税額 */

    foreign_currency_delivery_note_amount numeric,      /* 納品書金額(外貨) */
    foreign_currency_delivery_note_tax numeric,         /* 消費税額(外貨) */

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
) ;
