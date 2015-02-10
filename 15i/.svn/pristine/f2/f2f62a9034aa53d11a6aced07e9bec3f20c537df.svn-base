drop table if exists achievement;
create table achievement
(
    achievement_id serial primary key,      /* 実績id */
    achievement_date date not null,         /* 製造日 */
    begin_time time,                        /* 製造開始時刻 */
    end_time time,                          /* 製造終了時刻 */

    order_header_id int4 not null,          /* 製造指示ヘッダーid */
    order_detail_id int4 not null,          /* 製造指示明細id */
    item_id int not null,                   /* 品目id */
    achievement_quantity numeric not null,  /* 製造数量 */
    product_price numeric not null,         /* 単価 */
    remarks text,                           /* 備考 */

    order_seiban text not null,             /* 製番（オーダー） */
    stock_seiban text not null,             /* 製番（在庫） */
    work_minute numeric not null,           /* 製造時間（分） */
    break_minute numeric,                   /* 休憩時間（分） */
    location_id int not null,               /* 完成品目入庫ロケーションID（0は規定ロケを意味する） */
    child_location_id int not null,         /* 使用部材出庫ロケーションID（0は規定ロケを意味する）2009以降は、-1は子品目ごとの「標準ロケ（使用）」から払い出すことを意味する */
    lot_no text,                            /* 製造ロット番号 */
    use_lot_no text,                        /* 使用子品目(製造/受入)ロット */
    middle_process bool,                    /* 中間工程 「true(中間工程)/null(最終工程)」 */

    process_id int,                         /* 工程実績のときに工程idを登録 */
    section_id int,                         /* 工程実績のときに部門idを登録 */
    equip_id int,                           /* 工程実績のときに設備idを登録 */
    worker_id int,                          /* 工程実績のときに作業者idを登録 */

    cost_1 numeric,                         /* (15i) 製造経費1 */
    cost_2 numeric,                         /* (15i) 製造経費2 */
    cost_3 numeric,                         /* (15i) 製造経費3 */

    use_by date,                            /* (15i) 消費期限 */

    custom_text_1 text,                     /* (15i) カスタム項目 */
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
create index achievement_index1 on achievement (order_detail_id);