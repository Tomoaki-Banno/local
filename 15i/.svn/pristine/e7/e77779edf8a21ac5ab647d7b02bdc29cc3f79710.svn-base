drop table if exists item_process_master;
create table item_process_master
(
    item_id int not null,                       /* 品目id */
    process_id int not null,                    /* 工程id */
    machining_sequence int not null,            /* 工順番号 0はじまり */
    default_work_minute numeric not null,       /* 標準加工時間 */
    pcs_per_day numeric not null,               /* 製造能力（1日あたり） */
    charge_price numeric not null,              /* チャージ料（1分あたり） */
    process_lt int,                             /* 工程LT */
    overhead_cost numeric,                      /* 固定経費 */
    subcontract_partner_id int,                 /* 外製工程用 */
    subcontract_unit_price numeric,             /* 外製単価 */
    process_remarks_1 text,                     /* 備考1 */
    process_remarks_2 text,                     /* 備考2 */
    process_remarks_3 text,                     /* 備考3 */

    record_creator text,
    record_create_date timestamp,
    record_create_func text,
    record_updater text,
    record_update_date timestamp,
    record_update_func text
);

create index item_process_master_index1 on item_process_master (item_id);