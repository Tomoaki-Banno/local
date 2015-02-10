drop table if exists order_detail;
create table order_detail
(
    order_detail_id serial primary key,     /* オーダー明細id */
    order_header_id int not null,           /* オーダーヘッダーid */

    line_no int,                            /* 明細行番号 */

    order_no text unique not null,          /* オーダー番号（明細行ごとに1つ） */
    seiban text not null,                   /* 製番 */
    item_id int not null,                   /* 品目id（REFERENCESは高速化のため削除） */
    item_code text not null,                /* 品目コード */
    item_name text not null,                /* 品目名 */
    item_price numeric not null,            /* 単価「内製(在庫評価単価)/注文・外製(発注単価)」 */
    order_amount numeric,                   /* 発注金額（取引先マスタ [端数処理]に従い整数丸め） */
    order_tax numeric,                      /* 消費税（取引先マスタ [端数処理]に従い整数丸め） */
    item_sub_code text not null,            /* メーカー型番 */
    order_detail_quantity numeric not null, /* 発注数 */
    order_detail_dead_line date not null,   /* オーダー納期 */
    accepted_quantity numeric,              /* 受入数 */
    order_detail_completed bool,            /* 完納フラグ */
    accepted_flag bool,                     /* （未使用） */
    alarm_flag bool,                        /* 手配が間に合わないため納期が自動調整された場合はtrue */
    payout_location_id int,                 /* 子品目支給元ロケ（0は規定ロケを意味する） */
    payout_lot_id int default 0,
    order_measure text,                     /* 手配単位（kg,mなど） */
    multiple_of_order_measure numeric,      /* 手配単位数 ÷ 管理単位数  */
    tax_class int,                          /* 課税区分「0 or null(課税)/1(非課税)」 */

    foreign_currency_id int,                /* 外貨id */
    foreign_currency_rate numeric,          /* 為替レート */
    foreign_currency_item_price numeric,    /* 外貨単価 */
    foreign_currency_order_amount numeric,  /* 外貨発注金額（取引先マスタ [端数処理]に従い整数丸め） */

    remarks text,                           /* 明細備考 */

    /* 計画によるオーダーの場合のみ登録される項目 */
    plan_date date,                         /* 計画日 */
    plan_qty numeric,                       /* 計画数 */
    hand_qty numeric,                       /* 所要量計算の手修正により自動作成された計画によるオーダーの場合のみ登録される。計画数 */

    /* 製造指示書の外製工程から発行された外製指示書のみ登録される項目 */
    subcontract_order_process_no text,      /* 親製造指示書工程（order_process）とのリンク */
    subcontract_parent_order_no text,       /* 親製造指示書のオーダー番号 */
    subcontract_process_name text,          /* 外製工程名 */
    subcontract_process_remarks_1 text,     /* 外製工程メモ1 */
    subcontract_process_remarks_2 text,     /* 外製工程メモ2 */
    subcontract_process_remarks_3 text,     /* 外製工程メモ3 */
    subcontract_ship_to text,               /* 外製工程発送先 */

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

/* テーブルの値を他からコピーしたりした場合、下記を実行してserialの値を再設定する必要がある。 */
/* select setval('order_detail_order_detail_id_seq',(select max(order_detail_id) from order_detail)); */

/* Logic_Order::entryUsePlan　子品目使用予定（オーダー発行で使用）  50ms ⇒ 3ms */
create index order_detail_index1 on order_detail (item_id);
/* Logic_Order::entryOrderDetail の USE_PLAN登録 3.5ms ⇒ 0.5ms */
create index order_detail_index2 on order_detail (order_header_id);