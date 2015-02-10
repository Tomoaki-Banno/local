drop table if exists order_process;
create table order_process
(
    order_process_no text primary key,      /* オーダー工程番号 */
    order_detail_id int not null,           /* オーダー明細id */
    process_id int not null,                /* 工程id */
    machining_sequence int not null,        /* 工順番号（マスタより）0はじまり */
    default_work_minute numeric not null,   /* 標準加工時間（マスタより） */
    pcs_per_day numeric not null,           /* 製造能力（マスタより）1日あたり */
    charge_price numeric not null,          /* チャージ料（マスタより）1分あたり */
    process_start_date date,                /* 工程開始日（マスタLTから計算） */
    process_dead_line date,                 /* 工程納期（マスタLTから計算） */
    overhead_cost numeric,                  /* 固定経費 */
    subcontract_partner_id int,             /* 外製先。これが not null の場合はこの工程が外製工程であると判断できる。外製先そのものについては、外製オーダー（order_process_noでリンク）のオーダー先を参照したほうが正確 */
    subcontract_unit_price numeric,         /* 外製単価。実際には外製オーダー（order_process_noでリンク）の単価を参照したほうが正確 */
    process_remarks_1 text,                 /* 工程メモ1 */
    process_remarks_2 text,                 /* 工程メモ2 */
    process_remarks_3 text,                 /* 工程メモ3 */
    process_completed bool,                 /* (15i)工程完了フラグ */

    record_creator text,
    record_create_date timestamp,
    record_create_func text,
    record_updater text,
    record_update_date timestamp,
    record_update_func text,

    unique(order_detail_id, machining_sequence)
);