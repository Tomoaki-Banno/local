drop table if exists customer_master;
drop sequence if exists customer_master_customer_id_seq;
/* customer_id = 0 は、内製をあらわす特殊IDとして予約する。 */
/* そのため MINVALUE 1 を指定している。 */
create sequence customer_master_customer_id_seq MINVALUE 1;
create table customer_master (
    /* 上記のとおりシーケンスにMINVALUEを指定する必要があるため、serial型は使えない。*/
    /* 動作としては serialと同じ。 */
    customer_id INT4 not null DEFAULT nextval('customer_master_customer_id_seq') primary key,
    customer_no text not null,          /* 取引先コード */
    customer_name text not null,        /* 取引先名 */
    classification int not null,        /* 区分  0:得意先, 1:サプライヤー, 2:発送先 */
    end_customer bool,                  /* 非表示 */
    zip text not null,                  /* 郵便番号 */
    address1 text not null,             /* 住所1 */
    address2 text not null,             /* 住所2 */
    tel text not null,                  /* TEL */
    fax text not null,                  /* FAX */
    e_mail text not null,               /* メールアドレス */
    person_in_charge text not null,     /* 担当者 */
    remarks text,                       /* 備考1 */
    remarks_2 text,                     /* 備考2 */
    remarks_3 text,                     /* 備考3 */
    remarks_4 text,                     /* 備考4 */
    remarks_5 text,                     /* 備考5 */

    rounding text,                      /* 端数処理  空欄:なし, round:四捨五入, floor:切捨, ceil:切上 */
    precision int not null default 0,   /* 小数点以下の桁数 */
    inspection_lead_time int,           /* 検収リードタイム */
    currency_id int,                    /* 取引通貨 */
    report_language int,                /* 帳票言語区分  0orNull:日本語, 1:英語 */
    dropdown_flag bool,                 /* ドロップダウンから登録された項目はtrue */

    customer_group_id_1 int,            /* 取引先グループ1 */
    customer_group_id_2 int,            /* 取引先グループ2 */
    customer_group_id_3 int,            /* 取引先グループ3 */

    /* 得意先用 */
    bill_pattern int,                   /* 請求パターン  0:締め-残高表示なし, 1:締め-残高表示あり, 2:都度 */
    bill_customer_id int,               /* 請求先 */
    monthly_limit_date int not null,    /* 締日 */
    tax_category int,                   /* 税計算単位  0:請求書単位, 1:納品書単位, 2:納品明細単位 */
    price_percent numeric,              /* 掛率 */
    price_percent_group_id int,         /* 掛率グループ */
    opening_balance numeric,            /* 売掛残高初期値 */
    opening_date date,                  /* 売掛残高初期日 */
    credit_line numeric,                /* 与信限度額 */
    receivable_cycle1 int,              /* 回収サイクル1（x日後） */
    receivable_cycle2_month int,        /* 回収サイクル2（xヶ月後） */
    receivable_cycle2_day int,          /* 回収サイクル2（x日） */
    template_delivery text,             /* 帳票テンプレート（納品書）*/
    template_bill text,                 /* 帳票テンプレート（請求書）*/

    /* サプライヤー用 */
    default_lead_time numeric,          /* 標準リードタイム */
    delivery_port text not null,        /* 納入場所 */
    payment_opening_balance numeric,    /* 買掛残高初期値 */
    payment_opening_date date,          /* 買掛残高初期日 */
    payment_cycle1 int,                 /* 支払サイクル1（x日後） */
    payment_cycle2_month int,           /* 支払サイクル2（xヶ月後） */
    payment_cycle2_day int,             /* 支払サイクル2（x日） */
    template_partner_order text,        /* 帳票テンプレート（注文書）*/
    template_subcontract text,          /* 帳票テンプレート（外製指示書）*/

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