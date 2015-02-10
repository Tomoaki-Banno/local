drop table if exists csv_format;
create table csv_format
(
    action text not null,       /* action名 */
    format_name text not null,  /* フォーマット名 */
    format_data text not null,  /* フォーマット(JSON) */
    description text,           /* 説明 */

    record_creator text,
    record_create_date timestamp,
    record_create_func text,
    record_updater text,
    record_update_date timestamp,
    record_update_func text
);
