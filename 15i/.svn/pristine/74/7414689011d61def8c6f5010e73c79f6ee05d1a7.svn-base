drop table if exists bill_header;
create table bill_header
(
    bill_header_id serial primary key,      /* 請求書ヘッダーid */

    bill_number text unique,                /* 請求書番号 */
    customer_id int not null,               /* 得意先id */
    begin_date date not null,               /* 日付 */
    close_date date not null,               /* 締日 */
    close_date_show text,                   /* 締日（表示用） */
    receivable_date date,                   /* 回収予定日 */
    rounding text,                          /* 端数処理  空欄:なし, round:四捨五入, floor:切捨, ceil:切上 */
    precision int not null default 0,       /* 小数点以下の桁数 */
    tax_category int,                       /* 税計算単位  0:請求書単位, 1:納品書単位, 2:納品明細単位 */
    bill_pattern int,                       /* 請求パターン  0:締め-残高表示なし, 1:締め-残高表示あり, 2:都度 */
    delivery_header_id int default 0,       /* 納品id（都度請求用） */
    detail_display int default 0,           /* 明細表示  0:全表示, 1:数量０を非表示 */
    bill_printed_flag boolean,              /* 帳票を印刷したらtrue */

    before_amount numeric not null,         /* 前回ご請求額 */
    paying_in numeric not null,             /* ご入金額 */
    sales_amount numeric not null,          /* 今回お買い上げ額 */
    tax_amount numeric not null,            /* 消費税額 */
    bill_amount numeric not null,           /* 今回ご請求額 */

    foreign_currency_id int,                /* 外貨id */
    foreign_currency_before_amount numeric, /* 前回ご請求額(外貨) */
    foreign_currency_paying_in numeric,     /* ご入金額(外貨) */
    foreign_currency_sales_amount numeric,  /* 今回お買い上げ額(外貨) */
    foreign_currency_tax_amount numeric,    /* 消費税(外貨) */
    foreign_currency_bill_amount numeric,   /* 今回ご請求額(外貨) */

    record_creator text,
    record_create_date timestamp,
    record_create_func text,
    record_updater text,
    record_update_date timestamp,
    record_update_func text
);
/* 13i 都度請求にて同日の請求書が発行されるため削除 */
/* create unique index bill_header_index1 on bill_header (customer_id, close_date, coalesce(foreign_currency_id,-9999999)); */