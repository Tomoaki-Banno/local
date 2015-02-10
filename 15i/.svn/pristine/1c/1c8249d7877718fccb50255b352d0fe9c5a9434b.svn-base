drop table if exists staff_schedule;
create table staff_schedule
(
    schedule_id serial,         /* スケジュールid */

    begin_date date not null,   /* 開始日 */
    end_date date,              /* 終了日 */
    begin_time time,            /* 開始時間 */
    end_time time,              /* 終了時間 */
    schedule_text text,         /* スケジュール */
    schedule_memo text,         /* メモ */
    background_color text,      /* 背景色 */
    non_disclosure bool not null default false,  /* 非公開 */

    repeat_pattern int,         /* null:なし、0:毎日、1:休業日以外、2:毎週、3:毎月第1、4:毎月第2、5:毎月第3、6:毎月第4、7:毎月最終、8:毎月 */
    repeat_weekday int,         /* repeat_pattern 2-7用。 0:日曜 - 6:土曜 */
    repeat_day int,             /* repeat_pattern 8用。 1-31、32:月末 */

    record_creator text,
    record_create_date timestamp,
    record_create_func text,
    record_updater text,
    record_update_date timestamp,
    record_update_func text
);
