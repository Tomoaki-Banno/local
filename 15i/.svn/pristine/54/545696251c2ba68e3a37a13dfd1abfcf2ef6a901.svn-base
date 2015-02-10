drop table if exists bill_detail;
create table bill_detail
(
    bill_detail_id serial primary key,              /* 請求明細id */
    bill_header_id int not null,                    /* 請求ヘッダーid */

    received_customer_id int,                       /* 得意先id */
    delivery_customer_id int,                       /* 納品先id */
    delivery_date date not null,                    /* 納品日 */
    inspection_date date,                           /* 検収日 */
    delivery_detail_id int,                         /* 納品明細id (15i) */
    delivery_no text,                               /* 納品書番号 */
    line_no int,                                    /* 納品書行番号 */
    received_number text,                           /* 受注番号 */
    customer_received_number text,                  /* 客先注番 */
    received_line_no int,                           /* 受注行番号 */
    received_seiban text,                           /* 製番 */
    item_id int,                                    /* 品目id(15i) */
    item_code text not null,                        /* 品目コード */
    item_name text not null,                        /* 品目名 */
    measure text,                                   /* 単位 */
    quantity numeric not null,                      /* 納品数 */
    price numeric not null,                         /* 単価 */
    amount numeric not null,                        /* 納品金額 */
    tax numeric,                                    /* 消費税額。bill_header.tax_category が 2(納品明細単位)のときのみ */
    tax_rate numeric,                               /* 税率 */
    tax_class int,                                  /* 課税区分  0orNull:課税, 1:非課税 */
    delivery_note_amount numeric not null,          /* 納品書金額 */
    delivery_note_tax numeric,                      /* 納品書消費税額。bill_header.tax_category が 1(納品書単位)もしくは 2(納品明細単位)のときのみ */
    remarks text,                                   /* 備考 */
    lot_no text null,                               /* ロット番号（納品） */

    foreign_currency_rate numeric,                  /* 外貨レート */
    foreign_currency_price numeric,                 /* 単価（外貨） */
    foreign_currency_amount numeric,                /* 金額（外貨） */
    foreign_currency_tax numeric,                   /* 消費税額（外貨） */
    foreign_currency_delivery_note_amount numeric,  /* 納品書金額（外貨） */

    record_creator text,
    record_create_date timestamp,
    record_create_func text,
    record_updater text,
    record_update_date timestamp,
    record_update_func text
);
