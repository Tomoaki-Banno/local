drop table if exists use_plan;
create table use_plan
(
    /* use_plan に記録されるレコードは子品目使用予約と受注引当の2種類があり、*/
    /* 前者の場合は order_header_id/detail_id が、後者の場合は received_detail_id が登録される */
    order_header_id int,                /* 製造指示・外製指示の子品目使用予約の場合に記録。製造指示書発行時 */
    order_detail_id int,                /* 製造指示・外製指示の子品目使用予約の場合に記録。*/
    received_detail_id int,             /* 受注引当の場合に記録。受注製番とフリー在庫のヒモつけ。受注登録時 */
    received_detail_id_for_dummy int,   /* (15i)受注したダミー品目の子品目使用予約の場合に記録。受注登録時 */

    item_id int,                        /* 品目id（REFERENCESは高速化のため削除） */
    use_date date,                      /* 日付 */
    quantity numeric,                   /* 数量 */

    completed_adjust_delivery_id int,   /* 納品登録時、完納扱いにしたときに引当数調整のために入れたレコードの場合、ここに納品IDが入る（削除時引当数復活のため） */

    record_creator text,
    record_create_date timestamp,
    record_create_func text,
    record_updater text,
    record_update_date timestamp,
    record_update_func text
);


/* テーブルの値を他からコピーしたりした場合、下記を実行してserialの値を再設定する必要がある。 */
/* select setval('use_plan_use_plan_id_seq',(select max(use_plan_id) from use_plan)); */

/* Logic_Order::entryOrderDetail の USE_PLAN登録 2.3ms ⇒ 0.2ms */
create index use_plan_index1 on use_plan (order_header_id);
create index use_plan_index2 on use_plan (received_detail_id);
create index use_plan_index3 on use_plan (item_id);