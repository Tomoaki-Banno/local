drop table if exists mrp_process;
create table mrp_process
(
    mrp_process_id serial,              /* 工程テーブルid */

    item_id int not null,               /* 品目id */
    seiban text not null,               /* 製番 */
    process_id int not null,            /* 工程id */
    machining_sequence int not null,    /* 工順番号（マスタより）0はじまり*/
    process_dead_line date,             /* 工程納期（マスタLTから計算） */
    arrangement_quantity numeric,       /* 数量 */

    order_class int,                    /* 99 は製番在庫引当オーダー */
    llc int,                            /* LLC */
    order_flag int,                     /* オーダー発行済みフラグ「0(false)/1(true)」 */
    alarm_flag int,                     /* アラーム「0(false)/1(true)」 */
    process_lt int,                     /* 工程LT */
    pcs_per_day numeric,                /* 1日あたり製造能力 */

    record_creator text,
    record_create_date timestamp,
    record_create_func text,
    record_updater text,
    record_update_date timestamp,
    record_update_func text
);