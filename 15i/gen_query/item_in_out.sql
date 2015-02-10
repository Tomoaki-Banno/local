drop table if exists item_in_out;
create table item_in_out
(
    item_in_out_id serial primary key,          /* 入出庫id */

    item_in_out_date date not null,             /* 日付 */
    item_id int not null,                       /* 品目id */
    seiban text not null,                       /* 製番 */
    location_id int not null,                   /* ロケーションid（0は規定ロケを意味する） */
    lot_id int default 0,                       /* ロットid（0はロットなしを意味する）未使用 */
    lot_no text,                                /* ロット番号（2010i rev.20101109以降、受入・実績・納品登録時に記録される。利用はしていない。） */

    item_in_out_quantity numeric not null,      /* 数量 */
    classification text not null,               /* in(入)/out(出)/payout(払)/use(実)/manufacturing(製)/delivery(納)/move_in(ロケ間移動入庫)/move_out(ロケ間移動出庫)/seiban_change_in(製番引当入庫)/seiban_change_out(製番引当出庫) */
    remarks text not null,                      /* 備考 */

    parent_item_id int,                         /* classification = use のときのみ。親品目id */
    partner_id int,                             /* classification = payout のときのみ。支給先id */
    item_price numeric not null,                /* classification = payout のときのみ。支給単価 */
    order_detail_id int,                        /* classification = payout,in で注文登録時のときのみ。支給出庫とサプライヤーロケへの入庫 */
    payout_item_in_out_id int,                  /* classification = in で支給登録によるサプライヤー在庫への入庫のときのみ */
    delivery_id int,                            /* classification = delivery のときのみ。納品先id */
    accepted_id int,                            /* classification = in のときのみ。受入id */
    achievement_id int,                         /* classification = manufacturing, use のときのみ。実績id */
    move_id int,                                /* classification = move_in, move_out のときのみ。ロケ間移動id */
    seiban_change_id int,                       /* classification = seiban_change_in, seiban_change_out のときのみ。製番引当id */
    stock_amount numeric,                        /* classification = out で製番が記録されているときのみ。出庫金額（原価計算用）*/

    without_stock int not null,                 /* 1なら現在庫計算に反映させない。主に支給の未引落品用 */

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
create index item_in_out_index1 on item_in_out (item_in_out_date);
create index item_in_out_index2 on item_in_out (item_id);
create index item_in_out_index3 on item_in_out (item_id, location_id, seiban, lot_id);
create index item_in_out_index4 on item_in_out (classification);