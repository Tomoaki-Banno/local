drop table if exists item_order_master ;
create table item_order_master
(
    item_id int,                            /* 品目id */
    line_number int,                        /* 手配先番号「0(標準発注先)/1(代替発注先1)/2(代替発注先2)～」 */

    order_user_id int not null,             /* 手配先id。0は内製（customer_master参照）。ただし09i以降では、内製かどうかの判断はpartner_classで行うのが基本 */
    default_order_price numeric,            /* 購入単価1 */
    default_order_price_2 numeric,          /* 購入単価2 */
    default_order_price_3 numeric,          /* 購入単価3 */
    order_price_limit_qty_1 numeric,        /* 購入単価1適用数 */
    order_price_limit_qty_2 numeric,        /* 購入単価2適用数 */
    item_sub_code text,                     /* メーカー型番 */
    partner_class int,                      /* 手配区分「0(発注)/1(外注-支給無)/2(外注-支給有)/3(内製)」 */

    order_measure text,                     /* 手配単位（kg,m,個など） */
    multiple_of_order_measure numeric,      /* 手配倍数（手配単位は管理単位の何倍か） */

    default_lot_unit numeric,              /* 最低ロット数 */
    default_lot_unit_2 numeric,            /* 手配ロット数 */
    default_lot_unit_limit numeric,        /* default_lot_unit と同じ値を登録（もともとはdefault_lot_unitがロット数1、default_lot_unit_2がロット数2、default_lot_unit_limitが両者の境界値だった） */

    record_creator text,
    record_create_date timestamp,
    record_create_func text,
    record_updater text,
    record_update_date timestamp,
    record_update_func text,

    PRIMARY KEY (item_id, line_number)
);

create index item_order_master_index1 on item_order_master (item_id);