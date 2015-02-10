drop table if exists column_info;
create table column_info
(
    user_id int not null,   /* ユーザーid */
    action text not null,   /* action名 */
    column_key text,        /* カラム */

    column_number int,      /* 表示順 */
    column_width int,       /* 列幅 */
    column_hide bool,       /* 列の非表示「true(非表示)/false or null(表示)」 */
    column_keta int,        /* 小数点以下表示桁数。-1は自然丸め */
    column_kanma int,       /* 桁区切りの有無「0(なし)/1(あり)」 */
    column_align int,       /* 表示位置「0(左寄せ)/1(中央)/2(右寄せ)」 */
    column_bgcolor text,    /* 背景色 */
    column_wrapon int,      /* 15i。折り返して全体を表示「0(しない)/1(する)」 */
    column_filter text,

    record_creator text,
    record_create_date timestamp,
    record_create_func text,
    record_updater text,
    record_update_date timestamp,
    record_update_func text
);
create unique index column_info_index1 on column_info (user_id, action, column_key);
create index column_info_index2 on column_info (column_number);