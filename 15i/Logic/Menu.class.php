<?php

class Logic_Menu
{
    // メニューデータ。
    // メニュー関連の箇所（全画面のメニューバー表示、およびユーザーマスタの権限設定画面）で共通に使用される。
    // 画面の増減時やメニュー構成の変更時は、ここだけを書き換えればよい。（2008iまでは多数の箇所を書き換える必要があった）

    //  画面を追加した場合、既存クラスグループ（Manufacturing_Receivedなど）の中であればここに書く必要はない。
    //  新規クラスグループの場合は（メニューバーに出さないとしても）ここに書いておく必要がある。
    //  ※メニューに表示しないものも含め、すべてのクラスグループをここに書いておく必要がある。
    //    ここに書いておかないとCSSが適用されず、メニュー表示が乱れる。
    //    最後の引数をfalseにしておけばメニューには表示されない。

    function getMenuArray($exceptAdminMenu = false, $restricted = false)
    {
        // 機能限定ユーザー
        if ($restricted) {
            return array(
                array(
                    array('Menu_Restricted', '', _g('メニュー'), 2, true),

                    array('Menu_Home', '', _g('パティオ'), 2, true),
                    array('Menu_Chat', '', _g('トークボード'), 2, true),
                    array('Config_Schedule', 'List', _g('スケジュール'), 3, true),
                    array('Config_PasswordChange', 'Edit', _g('パスワード変更'), 3, true, true),     // ダイアログで開く（第6引数）
                ),
                array(
                    array('Mobile_Home', '', _g('モバイル'), 2, false),

                    array('Mobile_Chat', 'List', _g('トークボード'), 2, false),
                ),
            );
        }

        $salesOnly = (GEN_GRADE == 'Si');

        $userId = Gen_Auth::getCurrentUserId();
        $adminMenu = ($userId == -1 && !$exceptAdminMenu);

        //  各メニューグループの最初の行がメインメニュー（メニューバーの１段目）、2行目以降がサブメニュー（メニューバー２段目）となる。
        //  各行のフォーマットは
        //      クラスグループ, デフォルトアクション, ラベル（メニューバーおよび権限設定画面に表示）, 権限種別(アクセス権○×なら2、○△×なら3）,メニューバーに出すか（falseなら権限設定のみ）

        $arr = array();

        // ●ホーム
        $arr[] =
        array(
            array('Menu_Home', '', _g('パティオ'), 2, false),
        );

        // ●コンパス
        $arr[] =
        array(
            array('Menu_Home2', '', _g('コンパス'), 2, false),
        );

        // ●マップ
        $arr[] =
        array(
            array('Menu_Map', '', _g('マップ'), 2, false),
        );

        // ●トークボード
        $arr[] =
        array(
            array('Menu_Chat', '', _g('トークボード'), 2, false),
        );

        // ●スケジュール
        $arr[] =
        array(
            array('Config_Schedule', 'List', _g('スケジュール'), 3, false),
        );

        // ●販売管理
        $arr[] =
        array(
            array('Menu_Delivery',                  '',     _g('販売管理'), 2, true),

            array('Manufacturing_Estimate',         'List', _g('見積登録'), 3, true),
            array('Manufacturing_Received',         'List', _g('受注登録'), 3, true),
            array('Delivery_Delivery',              'List', _g('納品登録'), 3, true),
            array('Monthly_Bill',                   'List', _g('請求書発行（締め）'), 3, true),
            array('Monthly_Bill',                   'BillList', _g('請求書リスト'), 3, true),
            array('Delivery_PayingIn',              'List', _g('入金登録'), 3, true),
            array('Delivery_ReceivableList',        'List', _g('売掛残高表'), 2, true),
            array('Delivery_ReceivableCalendar',    'List', _g('回収予定表'), 2, true),
            array('Manufacturing_BaseCost',         'List', _g('原価リスト'), 2, true),
            array('Delivery_DataLock',              'Lock', _g('販売データロック'), 2, true),
            // メニュー非表示
            array('Manufacturing_SeibanExpand',     'Edit', _g('製番展開'), 3, false),
        );

        // ●生産管理
        if (!$salesOnly) {
            $arr[] =
            array(
                array('Menu_Manufacturing',         '',     _g('生産管理'), 2, true),

                array('Manufacturing_Plan',         'List', _g('計画登録'), 3, true),
                array('Manufacturing_Mrp',          'List', _g('所要量計算'), 3, true),
                array('Manufacturing_Order',        'List', _g('製造指示登録'), 3, true),
                array('Manufacturing_Achievement',  'List', _g('実績登録'), 3, true),
                array('Progress_SeibanProgress',    'List', _g('受注別進捗'), 2, true),
                array('Progress_OrderProgress',     'List', _g('オーダー別進捗'), 3, true),// 納期変更があるため、2ではなく3
                array('Progress_ProcessLoad',       'List', _g('工程別負荷'), 2, true),
                array('Manufacturing_WorkerManage', 'List', _g('従業員設備管理'), 2, true),
            );
        }

        // ●購買管理
        if ($salesOnly) {
            // Si（外製なし）
            $arr[] =
            array(
                array('Menu_Partner',               '',     _g('購買管理'), 2, true),

                array('Partner_Order',              'List', _g('注文登録'), 3, true),
                array('Partner_Accepted',           'List', _g('注文受入登録'), 3, true),
                array('Partner_Payment',            'List', _g('支払登録'), 3, true),
                array('Partner_PaymentList',        'List', _g('買掛残高表'), 2, true),
                array('Partner_PaymentCalendar',    'List', _g('支払予定表'), 2, true),
                array('Partner_BuyList',            'List', _g('買掛リスト'), 2, true),
                array('Partner_DataLock',           'Lock', _g('購買データロック'), 2, true),
            );
        } else {
            // フル機能
            $arr[] =
            array(
                array('Menu_Partner',               '',     _g('購買管理'), 2, true),

                array('Partner_Order',              'List', _g('注文登録'), 3, true),
                array('Partner_Accepted',           'List', _g('注文受入登録'), 3, true),
                array('Partner_Subcontract',        'List', _g('外製指示登録'), 3, true),
                array('Stock_Inout',                'List&classification=payout', _g('支給登録'), 3, true),
                array('Partner_SubcontractAccepted','List', _g('外製受入登録'), 3, true),
                array('Partner_Payment',            'List', _g('支払登録'), 3, true),
                array('Partner_PaymentList',        'List', _g('買掛残高表'), 2, true),
                array('Partner_PaymentCalendar',    'List', _g('支払予定表'), 2, true),
                array('Partner_BuyList',            'List', _g('買掛リスト'), 2, true),
                array('Partner_DataLock',           'Lock', _g('購買データロック'), 2, true),
            );
        }

        // ●資材管理
        $arr[] =
        array(
            array('Menu_Stock',                     '', _g('資材管理'), 2, true),

            array('Stock_Stocklist',                'List', _g('在庫リスト'), 2, true),
            array('Stock_StockHistory',             'List', _g('受払履歴'), 2, true),
            array('Stock_StockFlow',                'List', _g('在庫推移リスト'), 2, true),
            array('Monthly_StockInput',             'List', _g('棚卸登録'), 3, true),
            // Stock_Inoutだけは、index.php側でactionにclassificationを付加している。
            // またユーザーマスタ編集画面（Master_User_Edit）では特殊処理をし「入出庫登録」としている。
            array('Stock_Inout',                    'List&classification=in', _g('入庫登録'), 3, true),
            array('Stock_Inout',                    'List&classification=out', _g('出庫登録'), 3, true),
            array('Stock_Inout',                    'List&classification=use', _g('使用数リスト'), 3, true),
            array('Stock_Move',                     'List', _g('ロケーション間移動登録'), 3, true),
            array('Stock_SeibanChange',             'List', _g('製番引当登録'), 3, true),
            // メニュー非表示
            array('Stock_Assessment',               'List', _g('在庫評価単価の更新'), 2, false),
            array('Stock_StockProcess',             'List', _g('工程仕掛リスト'), 2, false),
        );

        // ●レポート
        if ($salesOnly) {
            // Si（製造実績レポートなし）
            $arr[] = array(
                array('Menu_Report',                '', _g('レポート'), 2, true),

                array('Report_Received',            'List', _g('受注レポート'), 2, true),
                array('Report_Delivery',            'List', _g('販売レポート'), 2, true),
                array('Report_Accepted',            'List', _g('購買レポート'), 2, true),
                array('Report_Stock',               'List', _g('在庫レポート'), 2, true),
            );
        } else {
            // フル機能
            $arr[] = array(
                array('Menu_Report',                '', _g('レポート'), 2, true),

                array('Report_Received',            'List', _g('受注レポート'), 2, true),
                array('Report_Delivery',            'List', _g('販売レポート'), 2, true),
                array('Report_Achievement',         'List', _g('製造実績レポート'), 2, true),
                array('Report_Accepted',            'List', _g('購買レポート'), 2, true),
                array('Report_Stock',               'List', _g('在庫レポート'), 2, true),
            );
        }

        // ●マスタ
        $masterArr =
        array(
            array('Menu_Master',                    '', _g('マスタ'), 2, true),

            array('Master_Item',                    'List', _g('品目マスタ'), 3, true),
            array('Master_Customer',                'List', _g('取引先マスタ'), 3, true),
            array('Master_Bom',                     'List', _g('構成表マスタ'), 3, true),
            array('Master_ItemGroup',               'List', _g('品目グループマスタ'), 3, true),
            array('Master_CustomerGroup',           'List', _g('取引先グループマスタ'), 3, true),
            array('Master_Location',                'List', _g('ロケーションマスタ'), 3, true),
            array('Master_Section',                 'List', _g('部門マスタ'), 3, true),
            array('Master_Worker',                  'List', _g('従業員マスタ'), 3, true),
            array('Master_TaxRate',                 'List', _g('消費税率マスタ'), 3, true),
            array('Master_Holiday',                 'List', _g('カレンダーマスタ'), 3, true),
            array('Master_CustomerPrice',           'List', _g('得意先販売価格マスタ'), 3, true),
            array('Master_PricePercentGroup',       'List', _g('掛率グループマスタ'), 3, true),
            array('Master_Currency',                'List', _g('通貨マスタ'), 3, true),
            array('Master_Rate',                    'List', _g('為替レートマスタ'), 3, true),
        );
        if (!$salesOnly) {
            $masterArr = array_merge($masterArr ,array(
            array('Master_Process',                 'List', _g('工程マスタ'), 3, true),
            array('Master_Equip',                   'List', _g('設備マスタ'), 3, true),
            array('Master_Waster',                  'List', _g('不適合理由マスタ'), 3, true),
            ));
        }
        $arr[] = $masterArr;

        // ●メンテナンス
        $arr[] =
        array(
            array('Menu_Maintenance',               '', _g('メンテナンス'), 2, true),

            array('Master_Company',                 'Edit', _g('自社情報'), 3, true, true),     // ダイアログで開く（第6引数）
            array('Master_User',                    'List', _g('ユーザー管理'), 3, true),
            array('Config_Background',              'Edit', _g('パティオ画像設定'), 2, true),
            array('Config_PasswordChange',          'Edit', _g('パスワード変更'), 3, true, true),     // ダイアログで開く（第6引数）
            array('Config_DataAccessLog',           'List', _g('データ更新ログ'), 2, true),
            array('Master_AlertMail',               'List', _g('通知メールの設定'), 3, true),
            array('Config_UploadFile',              'List', _g('ファイルポケット一覧'), 2, true),
            array('Config_WordConvert',             'Edit', _g('ネーム・スイッチャー設定'), 2, true),
            array('Config_Personalize',             'List', _g('パーソナライズ'), 2, true),
            array('Config_Storage',                 'Usage', _g('ストレージ使用量'), 2, true),
            array('Monthly_Process',                'Monthly', _g('データロック'), 2, true),
            array('Config_Backup',                  'Backup', _g('バックアップ'), 2, true),
            array('Config_Restore',                 'Restore', _g('バックアップ読み込み'), 2, true),
            array('Config_DataDelete',              'List', _g('過去データ削除'), 2, true),

            // メニュー非表示（アクセス権設定のみ）
            array('Config_Report',                  'Setting', _g('帳票設定'), 3, false),
            array('Config_CustomColumn',            'Edit', _g('フィールド・クリエイター設定'), 2, false),  // ag.cgi?page=ProjectDocView&pPID=1574&pbid=233466
        );

        // ■admin専用
        if ($adminMenu) {   // adminユーザーのメニューバーのみ（adminでもユーザーマスタの権限設定等には表示しない）
            // ●EDI（adminおよび取引先ユーザーのみ表示。取引先ユーザーの場合の表示は getCustomerMenuArray() で行なっている）
            $arr[] =
            array(
                array('Menu_CustomerUser',          '', _g('EDI'), 2, true),

                array('Manufacturing_CustomerEdi',  'List', _g('発注登録'), 3, true),
                array('Partner_PartnerEdi',         'List', _g('注文受信'), 3, true),
            );

            // ●admin専用
            $arr[] =
            array(
                array('Menu_Admin',                 '', _g('admin専用'), 2, true),
           );
        }

        // ●モバイル（メニュー非表示。アクセス権のみ）
        $arr[] =
        array(
            array('Mobile_Home',                    '', _g('モバイル'), 2, false),

            array('Mobile_Chat',                    'List', _g('トークボード'), 2, false),
            array('Mobile_Stock',                   'List', _g('在庫'), 2, false),
            //array('Mobile_StockInput',            'List', _g('棚卸登録'), 3, false),
            array('Mobile_Received',                'List', _g('受注'), 2, false),
            array('Mobile_Mrp',                     'List', _g('所要量計算'), 2, false),
            array('Mobile_PartnerOrder',            'List', _g('注文書'), 2, false),
            array('Mobile_ItemMaster',              'List', _g('品目マスタ'), 2, false),
            array('Mobile_CustomerMaster',          'List', _g('取引先マスタ'), 2, false),
        );

        return $arr;
    }

    function getCustomerMenuArray($customerId)
    {
        global $gen_db;

        $arr = array();

        $query = "select classification from customer_master where customer_id = '{$customerId}'";
        $classification = $gen_db->queryOneValue($query);
        if ($classification == "0") {
            // ●得意先メニュー
            $arr[] =
            array(
                array('Menu_CustomerUser', '', _g('発注登録'), 2, true),
                array('Manufacturing_CustomerEdi', 'List', _g('発注登録'), 3, true),
            );
        } else {
            // ●サプライヤーメニュー
            $arr[] =
            array(
                array('Menu_PartnerUser', '',  _g('注文受信'),  2, true),
                array('Partner_PartnerEdi', 'List', _g('注文受信'), 3, true),
            );
        }

        // ●メンテナンス
        $arr[] =
        array(
            // メニュー非表示（アクセス権設定のみ）
            array('Config_Report',                  'Setting', _g('帳票設定'),   3, false),
            array('Config_PasswordChange',          'Edit', _g('パスワード変更'),3, false),
        );

        // ●ログアウト
        $arr[] =
        array(
            array('Logout',                         '',     _g('ログアウト'),    2, true),
        );

        return $arr;
    }

    function getMenuBarArray()
    {
        // メニューデータ配列の取得
        if (isset($_SESSION["user_customer_id"]) && is_numeric($_SESSION["user_customer_id"]) && $_SESSION["user_customer_id"] != "-1") {
            // 取引先ユーザー
            $menuArr = self::getCustomerMenuArray($_SESSION["user_customer_id"]);
        } else {
            // 一般ユーザー/機能制限ユーザー。
            // 両者の区別はログイン時にセットしたセッション変数から読み出す。
            // セッション変数を不正操作されると機能制限ユーザーに一般メニューが表示されてしまう可能性があるが、各画面へのアクセス権はないので問題ないはず。
            $menuArr = self::getMenuArray(false, $_SESSION["restricted_user"]);
        }
        return $menuArr;
    }

    function actionGroupToName($actionGroup)
    {
        if (substr($actionGroup, -1) == "_") {
            $actionGroup = substr($actionGroup, 0, strlen($actionGroup)-1);
        }
        $menuArr = self::getMenuBarArray();
        foreach($menuArr as $menuGroup) {
            foreach($menuGroup as $menu) {
                if ($menu[0] == $actionGroup) {
                    return $menu[2];
                }
            }
        }
        return "";
    }
}
