<?php

class Config_AdminAccessLog_TotalList extends Base_ListBase
{

    function setSearchCondition(&$form)
    {
        $timestamp = strtotime(date('Y-m-d'));
        $form['gen_searchControlArray'] = array(
            array(
                'label' => _g('日時'),
                'type' => 'dateTimeFromTo',
                'field' => 'access_time',
                'size' => '130',
                'defaultFrom' => date("Y-m-d H:i:s", mktime(date('H', $timestamp), date('i', $timestamp), date('s', $timestamp), date('m', $timestamp) - 3, date('d', $timestamp) + 1, date('Y', $timestamp))),
                'defaultTo' => date("Y-m-d 23:59:59"),
                'nosql' => true,
                'rowSpan' => 2,
            ),
            array(
                'label' => ADMIN_NAME,
                'type' => 'select',
                'field' => 'is_admin',
                'options' => Gen_Option::getTrueOrFalse('search-include'),
                'nosql' => true,
                'default' => 'false',
            ),
        );
    }

    function convertSearchCondition($converter, &$form)
    {
        // 検索条件（日付）に不正な値が指定されたとき、正しい値に変換しておく。
        // converterには時刻まで判定する関数がないので、自前で判定している。
        // 最後の一文字をチェックしているのは、strtotimeでは「2009-11-01 0:00a」がOKになってしまうため。
        if (isset($form['gen_search_access_time_from']) && $form['gen_search_access_time_from'] != '' && (!Gen_String::isDateTimeString($form['gen_search_access_time_from']) || !is_numeric(substr($form['gen_search_access_time_from'], -1))))
            $form['gen_search_access_time_from'] = date('Y-m-d 00:00');
        if (isset($form['gen_search_access_time_to']) && $form['gen_search_access_time_to'] != '' && (!Gen_String::isDateTimeString($form['gen_search_access_time_to']) || !is_numeric(substr($form['gen_search_access_time_to'], -1))))
            $form['gen_search_access_time_to'] = date('Y-m-d 23:59');
    }

    function beforeLogic(&$form)
    {
        global $gen_db;

        // 1セッション中で同じテーブルを複数回作成する可能性があるときは、CREATE TEMP TABLE文ではなくこのメソッドを使う
        $gen_db->createTempTable("temp_access_class", "(access_class_code text)", false);

        $menu = new Logic_Menu();
        $menuArr = $menu->getMenuArray(true);
        $classSql = "";
        $insertSql = "";
        foreach ($menuArr as $menuGroup) {
            foreach ($menuGroup as $menu) {
                $column = $menu[0] . ($menu[1] == "" ? "" : "_" . $menu[1]);
                if ($menu[0] == "Stock_Inout") {
                    $menu1 = str_replace("List", "", $menu[1]);
                    $classSql .= " when url like '%{$menu[0]}%' and url like '%{$menu1}%' then '{$column}'";
                } else {
                    $classSql .= " when url like '%{$menu[0]}%' then '{$column}'";
                }
                $insertSql .= "insert into temp_access_class (access_class_code) values ('{$column}');";
            }
        }
        $classSql .= " when url like '%Login%' then 'Login'";
        $insertSql .= "insert into temp_access_class (access_class_code) values ('Login');";
        $insertSql .= "insert into temp_access_class (access_class_code) values ('Etc');";

        // 区分データ作成
        $gen_db->query($insertSql);

        // 指定日（$stockDate）以前の最終棚卸日テーブルを作成
        $query = "
        select
            case {$classSql} else 'Etc' end as class_code_1
            ,case
                when url like '%Ajax%' then 'Ajax'
                when url like '%Delete%' then 'Delete'
                when url like '%List%' or url like '%StandardCostList%' or url like '%StandardCostTotal%' or url like '%Mrp_Analyze%'
                    or url like '%Stocklist_Expand%' or url like '%Stocklist_History%' or url like '%Stocklist_Flow%' then 'List'
                when url like '%Edit%' or (url like '%index.php' and action like '%Edit') then 'Edit'
                when url like '%Entry%' or url like '%BatchOrder%' or url like '%BulkInspection%' or url like '%Bom_Excel%'
                    or url like '%AlertMail_Regist%' or url like '%Import%' then 'Entry'
                when url like '%Report%' then 'Report'
                when url like '%Mrp_Mrp%' then 'Mrp'
                when url like '%ImageUpload%' then 'ImageUpload'
                when url like '%Menu%' then 'Menu'
                when url like '%Dropdown%' then 'Dropdown'
                when url like '%Lock%' or url like '%Process_Monthly' then 'Lock'
                when url like '%Restore%' then 'Restore'
                when url like '%Backup%' then 'Backup'
                when url like '%Login%' then 'Login'
                when url like '%Logout%' then 'Logout'
                when url like '%GenesissError%' then 'GenesissError'
                when action like '%Login%' then 'Login'
                else '' end as class_code_2
        from
            access_log
        where 1=1
            " . ($form['gen_search_is_admin'] == 'false' ? "and user_name <> '" . ADMIN_NAME . "'" : '') . "
            " . (isset($form['gen_search_access_time_from']) && Gen_String::isDateTimeString($form['gen_search_access_time_from']) ? "and access_time >= '{$form['gen_search_access_time_from']}'::date" : '') . "
            " . (isset($form['gen_search_access_time_to']) && Gen_String::isDateTimeString($form['gen_search_access_time_to']) ? "and access_time <= '{$form['gen_search_access_time_to']}'::date" : '') . "
        ";

        // 1セッション中で同じテーブルを複数回作成する可能性があるときは、CREATE TEMP TABLE文ではなくこのメソッドを使う
        $gen_db->createTempTable("temp_access_log", $query, true);
    }

    function setQueryParam(&$form)
    {
        $menu = new Logic_Menu();
        $menuArr = $menu->getMenuArray(true);
        $classSql = "";
        foreach ($menuArr as $menuGroup) {
            foreach ($menuGroup as $menu) {
                $column = $menu[0] . ($menu[1] == "" ? "" : "_" . $menu[1]);
                $classSql .= " when '{$column}' then '{$menu[2]}'";
            }
        }
        $classSql .= " when 'Login' then '" . _g("ログイン") . "'";
        $classSql .= " when 'Etc' then '" . _g("その他") . "'";

        $classArr = array(
            'Ajax' => 'class_ajax',
            'Delete' => 'class_delete',
            'List' => 'class_list',
            'Edit' => 'class_edit',
            'Entry' => 'class_entry',
            'Report' => 'class_report',
            'Mrp' => 'class_mrp',
            'ImageUpload' => 'class_image',
            'Menu' => 'class_menu',
            'Dropdown' => 'class_dropdown',
            'Lock' => 'class_lock',
            'Restore' => 'class_restore',
            'Backup' => 'class_backup',
            'Login' => 'class_login',
            'Logout' => 'class_logout',
            'GenesissError' => 'class_error',
            '' => 'class_etc',
        );
        $sumSql = "";
        $coalesceSql = "";
        foreach ($classArr as $key => $value) {
            $sumSql .= ",sum(case when class_code_2 = '{$key}' then 1 end) as {$value}";
            $coalesceSql .= ",coalesce({$value}, 0) as {$value}";
            $coalesceSql .= ",RANK() OVER(ORDER BY(coalesce({$value}, 0)) desc) as rank_{$value}";
        }

        $this->selectQuery = "
        select
            access_class_code
            ,case access_class_code {$classSql} else '' end as class_name
            ,coalesce(class_total, 0) as class_total
            {$coalesceSql}
        from
            temp_access_class
            left join (
                select
                    class_code_1
                    ,count(class_code_1) as class_total
                    {$sumSql}
                from
                    temp_access_log
                group by
                    class_code_1
            ) as t_log on temp_access_class.access_class_code = t_log.class_code_1
        [Orderby]
        ";
        $this->orderbyDefault = 'class_total desc';
    }

    function setViewParam(&$form)
    {
        $form['gen_pageTitle'] = _g("アクセス集計（admin専用）");
        $form['gen_menuAction'] = "Menu_Admin";
        $form['gen_listAction'] = "Config_AdminAccessLog_TotalList";
        $form['gen_excel'] = "true";

        $form['gen_returnUrl'] = "index.php?action=Config_AdminAccessLog_List&gen_restore_search_condition=true";
        $form['gen_returnCaption'] = _g('アクセスログへ戻る');

        $form['gen_fixColumnArray'] = array(
            array(
                'label' => _g('区分名'),
                'field' => 'class_name',
                'width' => '150',
            ),
            array(
                'label' => _g('区分'),
                'field' => 'access_class_code',
                'width' => '220',
                'hide' => true,
            ),
            array(
                'label' => _g('合計'),
                'field' => 'class_total',
                'type' => 'numeric',
                'width' => '100',
                'colorCondition' => array("#ffcc99" => "true"), // 色付け条件。常にtrueになるようにしている
            ),
        );
        $form['gen_columnArray'] = array(
            array(
                'label' => _g('リスト'),
                'field' => 'class_list',
                'type' => 'numeric',
                'width' => '80',
                'colorCondition' => array("#ccffff" => "'[rank_class_list]'=='1'&&'[class_list]'!='0'"),
            ),
            array(
                'label' => _g('編集'),
                'field' => 'class_edit',
                'type' => 'numeric',
                'width' => '80',
                'colorCondition' => array("#ccffff" => "'[rank_class_edit]'=='1'&&'[class_edit]'!='0'"),
            ),
            array(
                'label' => _g('登録'),
                'field' => 'class_entry',
                'type' => 'numeric',
                'width' => '80',
                'colorCondition' => array("#ccffff" => "'[rank_class_entry]'=='1'&&'[class_entry]'!='0'"),
            ),
            array(
                'label' => _g('削除'),
                'field' => 'class_delete',
                'type' => 'numeric',
                'width' => '80',
                'colorCondition' => array("#ccffff" => "'[rank_class_delete]'=='1'&&'[class_delete]'!='0'"),
            ),
            array(
                'label' => _g('レポート'),
                'field' => 'class_report',
                'type' => 'numeric',
                'width' => '80',
                'colorCondition' => array("#ccffff" => "'[rank_class_report]'=='1'&&'[class_report]'!='0'"),
            ),
            array(
                'label' => _g('MRP'),
                'field' => 'class_mrp',
                'type' => 'numeric',
                'width' => '80',
                'colorCondition' => array("#ccffff" => "'[rank_class_mrp]'=='1'&&'[class_mrp]'!='0'"),
            ),
            array(
                'label' => _g('画像'),
                'field' => 'class_image',
                'type' => 'numeric',
                'width' => '80',
                'colorCondition' => array("#ccffff" => "'[rank_class_image]'=='1'&&'[class_image]'!='0'"),
            ),
            array(
                'label' => _g('メニュー'),
                'field' => 'class_menu',
                'type' => 'numeric',
                'width' => '80',
                'colorCondition' => array("#ccffff" => "'[rank_class_menu]'=='1'&&'[class_menu]'!='0'"),
            ),
            array(
                'label' => _g('ロック'),
                'field' => 'class_lock',
                'type' => 'numeric',
                'width' => '80',
                'colorCondition' => array("#ccffff" => "'[rank_class_lock]'=='1'&&'[class_lock]'!='0'"),
            ),
            array(
                'label' => _g('リストア'),
                'field' => 'class_restore',
                'type' => 'numeric',
                'width' => '80',
                'colorCondition' => array("#ccffff" => "'[rank_class_restore]'=='1'&&'[class_restore]'!='0'"),
            ),
            array(
                'label' => _g('バックアップ'),
                'field' => 'class_backup',
                'type' => 'numeric',
                'width' => '80',
                'colorCondition' => array("#ccffff" => "'[rank_class_backup]'=='1'&&'[class_backup]'!='0'"),
            ),
            array(
                'label' => _g('ログイン'),
                'field' => 'class_login',
                'type' => 'numeric',
                'width' => '80',
                'colorCondition' => array("#ccffff" => "'[rank_class_login]'=='1'&&'[class_login]'!='0'"),
            ),
            array(
                'label' => _g('ログアウト'),
                'field' => 'class_logout',
                'type' => 'numeric',
                'width' => '80',
                'colorCondition' => array("#ccffff" => "'[rank_class_logout]'=='1'&&'[class_logout]'!='0'"),
            ),
            array(
                'label' => _g('DD'),
                'field' => 'class_dropdown',
                'type' => 'numeric',
                'width' => '80',
                'colorCondition' => array("#ccffff" => "'[rank_class_dropdown]'=='1'&&'[class_dropdown]'!='0'"),
            ),
            array(
                'label' => _g('Ajax'),
                'field' => 'class_ajax',
                'type' => 'numeric',
                'width' => '80',
                'colorCondition' => array("#ccffff" => "'[rank_class_ajax]'=='1'&&'[class_ajax]'!='0'"),
            ),
            array(
                'label' => _g('エラー'),
                'field' => 'class_error',
                'type' => 'numeric',
                'width' => '80',
                'colorCondition' => array("#ccffff" => "'[rank_class_error]'=='1'&&'[class_error]'!='0'"),
            ),
            array(
                'label' => _g('その他'),
                'field' => 'class_etc',
                'type' => 'numeric',
                'width' => '80',
                'colorCondition' => array("#ccffff" => "'[rank_class_etc]'=='1'&&'[class_etc]'!='0'"),
            ),
        );
    }

}
