drop table if exists mrp;
create table mrp
(
    mrp_id serial unique,                   /* 所要量計算id */

    item_id int not null,                   /* 品目id（高速化のためreferenceは外す） */
    seiban text not null,                   /* 製番 */
    calc_date date not null,                /* 日付 */
    location_id int,                        /* ロケーションid */
    order_class int not null,               /* 99 は製番在庫引当オーダー */

    before_useable_quantity numeric,        /* 前在庫数 */
    independent_demand numeric,             /* 受注数 */
    depend_demand numeric,                  /* 従属需要数 */
    order_remained numeric,                 /* 発注製造残 */
    due_in numeric,                         /* 入出庫数 */
    use_plan numeric,                       /* 使用予定数 */
    arrangement_quantity numeric,           /* オーダー数 */
    arrangement_start_date date,            /* オーダー日 */
    arrangement_finish_date date,           /* オーダー納期 */
    useable_quantity numeric,               /* 有効在庫数 */
    safety_stock numeric,                   /* 安全在庫数 */
    stock_quantity numeric,                 /* 在庫数 */

    llc int,                                /* LLC */
    order_flag int,                         /* オーダー発行済みフラグ「0(false)/1(true)」 */
    alarm_flag int,                         /* アラーム「0(false)/1(true)」 */

    plan_qty numeric,                       /* 計画によるオーダーの場合のみ登録される。計画数 */
    hand_qty numeric,                       /* 所要量計算結果の手修正による計画数 */

    record_creator text,
    record_create_date timestamp,
    record_create_func text,
    record_updater text,
    record_update_date timestamp,
    record_update_func text
);

/* テーブルの値を他からコピーしたりした場合、下記を実行してserialの値を再設定する必要がある。 */
/* select setval('mrp_mrp_id_seq',(select max(mrp_id) from mrp)); */

/* Logic_MRP::製番展開　展開終了判断SQL  100ms ⇒ 0.06ms */
create index mrp_index1 on mrp (llc);
/* Logic_MRP::MRP コアSQL（日付×階層 回実行される）で 最高200ms 程度の効果 */
create index mrp_index2 on mrp (calc_date);
/* Logic_MRPの mrp ⇒ mrp_result 処理  280ms ⇒ 180ms */
create index mrp_index3 on mrp (arrangement_quantity);
/* Logic_OrderのmrpToOrderの最後のUPDATE用。75ms⇒0ms。数千レコードの取り込みのとき大きい */
create index mrp_index4 on mrp (arrangement_start_date,arrangement_finish_date,item_id,seiban);