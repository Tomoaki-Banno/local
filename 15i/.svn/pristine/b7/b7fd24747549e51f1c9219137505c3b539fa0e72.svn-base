drop table if exists paying_in;
drop sequence if exists paying_in_paying_in_id_seq;
create table paying_in
(
    paying_in_id serial primary key,    /* 入金id */
    paying_in_date date not null,       /* 日付 */
    customer_id int not null,           /* 得意先id */
    way_of_payment int not null,        /* 種別「0(登録無し)/1(現金)/2(振込み)/3(小切手)/4(手形)/5(相殺)/6(値引き)/7(振込手数料)/8(その他)/9(先振込)/10(代引) */
    amount numeric not null,            /* 金額 */
    remarks text,                       /* 備考 */

    foreign_currency_id int,            /* 外貨id */
    foreign_currency_rate numeric,      /* 為替レート */
    foreign_currency_amount numeric,    /* 金額（外貨） */

    bill_header_id int,                 /* 都度請求の入金消込 */

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