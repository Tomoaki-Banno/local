drop table if exists stickynote_info;
create table stickynote_info
(
    stickynote_id serial primary key,   /* 付箋id */

    user_id int not null,               /* 作成ユーザーid */
    show_all_user bool not null,        /* 閲覧権限 */
    allow_edit_all_user bool not null,  /* 編集権限 */
    show_all_action bool not null,      /* 画面権限 */
    action text,                        /* アクション */
    x_pos int not null,                 /* x座標 */
    y_pos int not null,                 /* y座標 */
    width int not null,                 /* 幅 */
    height int not null,                /* 高さ */
    content text not null,              /* 内容 */
    color text not null,                /* 色 */
    system_note_no int,                 /* システムが作成したメモパッドの番号（Welcome Message は 1など） */

    record_creator text,
    record_create_date timestamp,
    record_create_func text,
    record_updater text,
    record_update_date timestamp,
    record_update_func text
);
