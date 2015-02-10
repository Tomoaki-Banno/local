drop table if exists lot_master;
drop sequence if exists lot_master_lot_id_seq;
/* lot_id = 0 は、ロケーション無しをあらわす特殊IDとして使用する。 */
/* そのため MINVALUE 1 を指定している。 */
create sequence lot_master_lot_id_seq MINVALUE 1;
create table lot_master
(
    /* 上記のとおりシーケンスにMINVALUEを指定する必要があるため、serial型は使えない。*/
    /* 動作としては serialと同じ。 */
    lot_id INT4 not null DEFAULT nextval('lot_master_lot_id_seq') primary key,

    lot_no text not null,           /* ロット番号 */
    item_id int not null,           /* 品目id */
    quantity numeric not null,      /* 数量 */
    expires date,                   /* 使用期限 */
    remarks text,                   /* 備考 */

    record_creator text,
    record_create_date timestamp,
    record_create_func text,
    record_updater text,
    record_update_date timestamp,
    record_update_func text
);