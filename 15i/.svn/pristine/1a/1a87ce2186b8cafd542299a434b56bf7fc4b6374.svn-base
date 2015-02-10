drop table if exists item_master;
create table item_master
(
    item_id serial primary key,                 /* 品目id */

    item_code text not null,                    /* 品目コード */
    item_name text not null,                    /* 品目名 */
    order_class int not null,                   /* 管理区分  0:製番, 1:MRP, 2:ロット */
    end_item bool,                              /* 非表示 */

    /* 詳細項目 */
    item_group_id int,                          /* 品目グループ1 */
    item_group_id_2 int,                        /* 品目グループ2 */
    item_group_id_3 int,                        /* 品目グループ3 */
    default_selling_price numeric not null,     /* 標準販売単価1（販売対象品目のみ意味を持つ） */
    default_selling_price_2 numeric,            /* 標準販売単価2 */
    default_selling_price_3 numeric,            /* 標準販売単価3 */
    selling_price_limit_qty_1 numeric,          /* 標準販売単価1適用数 */
    selling_price_limit_qty_2 numeric,          /* 標準販売単価2適用数 */
    stock_price numeric not null,               /* 在庫評価単価 */
    payout_price numeric,                       /* 支給単価 */
    tax_rate numeric,                           /* 税率 */
    tax_class int,                              /* 課税区分  0 or Null:課税, 1:非課税 */
    measure text,                               /* 管理単位（kg,m,個など） */
    received_object int not null,               /* 受注対象  0:受注対象, 1:非対象 */
    maker_name text not null,                   /* メーカー */
    spec text not null,                         /* 仕様 */
    rack_no text,                               /* 棚番 */
    quantity_per_carton numeric default 1,      /* 入数 12i rev.20120625 */
    default_location_id int,                    /* 標準ロケ（受入） */
    default_location_id_2 int,                  /* 標準ロケ（使用） */
    default_location_id_3 int,                  /* 標準ロケ（完成） */
    dummy_item bool,                            /* ダミー品目 */
    use_by_days int,                            /* (15i)消費期限日数 */
    lot_header text,                            /* (15i)ロット頭文字 */
    comment text not null,                      /* 備考1 */
    comment_2 text not null,                    /* 備考2 */
    comment_3 text not null,                    /* 備考3 */
    comment_4 text not null,                    /* 備考4 */
    comment_5 text not null,                    /* 備考5 */

    /* 所要量計算 */
    without_mrp int not null,                   /* 所要量計算に含める  0:含める, 1除外 */
    safety_stock numeric not null,              /* 安全在庫数 */
    lead_time numeric,                          /* リードタイム。2010iからnull許可 */
    safety_lead_time numeric not null,          /* 安全リードタイム */

    /* 画像 */
    image_file_oid oid,                         /* 画像ファイルoid(15iでは未使用) */
    image_file_name text,                       /* 画像ファイル名 */
    original_image_file_name text,              /* 元画像ファイル名。15i */
    drawing_file_oid oid,                       /* 添付ファイルoid */
    drawing_file_name text,                     /* 添付ファイル名 */

    /* 内部で使用 */
    llc int default 0,                          /* LLC。構成表マスタ登録時に計算されるが、未登録の場合は0である必要がある */
    dropdown_flag bool,                         /* ドロップダウンから登録された項目はtrue */

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

create index item_master_index1 on item_master (item_code);
create index item_master_index2 on item_master (item_group_id);