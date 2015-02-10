/* このテーブルには最低1行のレコードが必要。*/
/* rev.20110501以降、ログイン時にレコードの有無がチェックされ、レコードがないときは自動作成されるようになった。*/
/* （Login.class.php）*/

create table company_master (
    company_id serial primary key,
    company_name text not null,                     /* 自社名 */
    zip text,                                       /* 郵便番号 */
    address1 text,                                  /* 自社住所1 */
    address2 text,                                  /* 自社住所2 */
    tel text,                                       /* TEL */
    fax text,                                       /* FAX */
    main_bank text,                                 /* 取引銀行 */
    bank_account text,                              /* 口座番号 */

    company_name_en text,                           /* 自社名（英語表記）*/
    address1_en text,                               /* 自社住所1（英語表記）*/
    address2_en text,                               /* 自社住所2（英語表記）*/

    starting_month_of_accounting_period int not null default 1,         /* 年度開始月 */
    key_currency text default '￥',                 /* 基軸通貨 */

    password_minimum_length int,                    /* パスワードの最低桁数 */
    password_valid_until int,                       /* パスワードの有効期限 */
    account_lockout_threshold int default 10,       /* ログイン失敗回数の上限 */
    account_lockout_reset_minute int default 120,   /* ログイン失敗回数のリセット時間（分） */

    stock_price_assessment text,                    /* 0:最終仕入原価法、1:総平均法、2:標準原価法 */
    last_assessment_date date,                      /* 15iでは未使用。13i以前は最終在庫評価単価更新日だった */
    assessment_rounding text default 'round',       /* 評価単価端数処理区分「round(四捨五入)/floor(切捨)/ceil(切上)」 12i rev.20120403  */
    assessment_precision int not null default 2,    /* 評価単価の小数点以下桁数 12i rev.20120403 */

    payout_timing int,                              /* 外製支給のタイミング 0:発注時、1:受入時 */
    receivable_report_timing int,                   /* 売上計上基準 0:納品日、1:検収日 */
    payment_report_timing int,                      /* 仕入計上基準 0:受入日、1:検収日 */

    excel_cell_join bool not null default true,     /* Excel出力でセル結合を行うかどうか */
    excel_color bool not null default true,         /* Excel出力でセルに色をつけるかどうか */
    excel_date_type int not null default 1,         /* Excel出力の日付フォーマット「0(2012-11-12)/1(2012/11/12)」 12i rev.20120723 */

    remarks text,                                   /* 備考 */

    /* 以下、2010iでは未使用 */

    delivery_report_type int,
    bill_report_type int,
    order_report_type int,
    manufacturing_report_type int,
    subcontract_report_type int,

    /* 以下、システム内部で使用する項目 */

    startup_date_year date,                         /* 年度開始日 */
    monthly_dealing_date date,                      /* 月次開始日（月度の初日）データロック基準日として使用 */
    logical_inventory_date date,                    /* 棚卸実行日（月度の最終日） */
    last_dealing_date date,                         /* 月次処理実行日 */
    last_mrp_date timestamp,                        /* 所要量計算実行日 */
    last_mrp_user text,                             /* 所要量計算実行ユーザー */
    sales_lock_date date,                           /* 販売データロック基準日 */
    buy_lock_date date,                             /* 購買データロック基準日 */
    unlock_object_1 integer default 0,              /* データロック対象外1（受注登録） */
    unlock_object_2 integer default 0,              /* データロック対象外2（製造指示登録） */
    unlock_object_3 integer default 0,              /* データロック対象外3（注文登録） */
    unlock_object_4 integer default 0,              /* データロック対象外4（外製指示登録） */

    schema_version int,

    setting text,
    company_setting_update_time timestamp,          /* 15i */
    admin_password text not null,
    admin_setting text,
    admin_last_login timestamp,

    image_file_oid oid,                             /* 15iでは未使用だが13iからの移行のために必要 */
    image_file_name text,
    original_image_file_name text,                  /* 15i */

    ecom_chat_version int,                          /* 15i: チャット関連 */
    show_chat_dialog bool,                          /* 15i: adminチャットダイアログ表示フラグ */
    chat_dialog_x int,                              
    chat_dialog_y int,                              
    chat_dialog_width int,                          
    chat_dialog_height int,   
    last_chat_header_id int,

    custom_text_1 text,	/* (15i) カスタム項目 */
    custom_text_2 text,
    custom_text_3 text,
    custom_text_4 text,
    custom_text_5 text,
    custom_text_6 text,
    custom_text_7 text,
    custom_text_8 text,
    custom_text_9 text,
    custom_text_10 text,
    custom_date_1 date,
    custom_date_2 date,
    custom_date_3 date,
    custom_date_4 date,
    custom_date_5 date,
    custom_date_6 date,
    custom_date_7 date,
    custom_date_8 date,
    custom_date_9 date,
    custom_date_10 date,
    custom_numeric_1 numeric,
    custom_numeric_2 numeric,
    custom_numeric_3 numeric,
    custom_numeric_4 numeric,
    custom_numeric_5 numeric,
    custom_numeric_6 numeric,
    custom_numeric_7 numeric,
    custom_numeric_8 numeric,
    custom_numeric_9 numeric,
    custom_numeric_10 numeric,

    record_creator text,
    record_create_date timestamp,
    record_create_func text,
    record_updater text,
    record_update_date timestamp,
    record_update_func text
);