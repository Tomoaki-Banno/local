drop table if exists location_move;
create table location_move
(
    move_id serial primary key,         /* 移動id */
    item_id int not null,               /* 品目id */
    seiban text,                        /* 製番 */
    lot_id int default 0,               /* ロットid */
    move_date date not null,            /* 日付 */
    source_location_id int not null,    /* 移動元ロケーションID（0は規定ロケを意味する） */
    dist_location_id int not null,      /* 移動先ロケーションID（0は規定ロケを意味する） */
    quantity numeric not null,          /* 数量 */
    order_detail_id int,                /* オーダーid */
    move_printed_flag bool,             /* 帳票を印刷したらtrue */
    remarks text,                       /* 備考 */

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