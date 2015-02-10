<?php

class Config_AdminAccessLog_List extends Base_ListBase
{

    function setSearchCondition(&$form)
    {
        if (isset($form['error_time']) && Gen_String::isDateTimeString($form['error_time'])) {
            $timestamp = strtotime($form['error_time']);
            $defaultFrom = date("Y-m-d H:i:s", mktime(date('H', $timestamp), date('i', $timestamp), date('s', $timestamp) - 15, date('m', $timestamp), date('d', $timestamp), date('Y', $timestamp)));
            $defaultTo = date("Y-m-d H:i:s", mktime(date('H', $timestamp), date('i', $timestamp),  date('s', $timestamp) + 15, date('m', $timestamp), date('d', $timestamp), date('Y', $timestamp)));
        } else {
            $defaultFrom = date('Y-m-d 00:00:00');
            $defaultTo = date('Y-m-d 23:59:00');
        }

        $form['gen_searchControlArray'] = array(
            array(
                'label' => _g('日時'),
                'type' => 'dateTimeFromTo',
                'field' => 'access_time',
                'size' => '130',
                'defaultFrom' => $defaultFrom,
                'defaultTo' => $defaultTo,
                'rowSpan' => 2,
            ),
            array(
                'label' => _g('ユーザー名'),
                'field' => 'user_name',
            ),
            array(
                'label' => _g('IPアドレス'),
                'field' => 'ip',
                'hide' => true,
            ),
            array(
                'label' => ADMIN_NAME,
                'type' => 'select',
                'field' => 'is_admin',
                'options' => Gen_Option::getTrueOrFalse('search-include'),
                'nosql' => true,
                'default' => 'false',
            ),
            array(
                'label' => _g('url'),
                'field' => 'url',
            ),
            array(
                'label' => _g('action'),
                'field' => 'action',
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
    }

    function setQueryParam(&$form)
    {
        $obj = new Logic_Menu();
        $menuArr = $obj->getMenuArray(true);
        $classSql = "";
        foreach ($menuArr as $menuGroup) {
            foreach ($menuGroup as $menu) {
                if ($menu[0] == "Stock_Inout") {
                    $menu1 = str_replace("List", "", $menu[1]);
                    $classSql .= " when url like '%{$menu[0]}%' and url like '%{$menu1}%' then '{$menu[2]}'";
                } else {
                    $classSql .= " when url like '%{$menu[0]}%' then '{$menu[2]}'";
                }
            }
        }
        $classSql .= " when url like '%Login%' then '" . _g("ログイン") . "'";

        $this->selectQuery = "
            select
                *
                ,case date_part('dow',access_time)
                    when 0 then '" . _g("日") . "'
                    when 1 then '" . _g("月") . "'
                    when 2 then '" . _g("火") . "'
                    when 3 then '" . _g("水") . "'
                    when 4 then '" . _g("木") . "'
                    when 5 then '" . _g("金") . "'
                    else '" . _g("土") . "' end as dow
                ,case {$classSql} else '' end as class_name_1
                ,case
                    when url like '%Ajax%' then 'Ajax'
                    when url like '%Delete%' then '削除'
                    when url like '%List%' or url like '%StandardCostList%' or url like '%StandardCostTotal%' or url like '%Mrp_Analyze%'
                        or url like '%Stocklist_Expand%' or url like '%Stocklist_History%' or url like '%Stocklist_Flow%' then 'リスト'
                    when url like '%Edit%' or (url like '%index.php' and action like '%Edit') then '編集'
                    when url like '%Entry%' or url like '%BatchOrder%' or url like '%BulkInspection%' or url like '%Bom_Excel%'
                        or url like '%AlertMail_Regist%' or url like '%Import%' then '登録'
                    when url like '%Report%' then 'レポート'
                    when url like '%Mrp_Mrp%' then '所要量計算'
                    when url like '%ImageUpload%' then '画像'
                    when url like '%Menu%' then 'メニュー'
                    when url like '%Dropdown%' then 'ドロップダウン'
                    when url like '%Lock%' or url like '%Process_Monthly' then 'ロック'
                    when url like '%Restore%' then 'リストア'
                    when url like '%Backup%' then 'バックアップ'
                    when url like '%Login%' then 'ログイン'
                    when url like '%Logout%' then 'ログアウト'
                    when url like '%GenesissError%' then 'エラー'
                    when action like '%Login%' then 'ログイン'
                    else '' end as class_name_2
            from
                access_log
            [Where]
                " . ($form['gen_search_is_admin'] == 'false' ? "and user_name <> '" . ADMIN_NAME . "'" : '') . "
            [Orderby]
        ";
        $this->orderbyDefault = 'access_time desc';
    }

    function setViewParam(&$form)
    {
        $form['gen_pageTitle'] = _g("アクセスログ（admin専用）");
        $form['gen_menuAction'] = "Menu_Admin";
        $form['gen_listAction'] = "Config_AdminAccessLog_List";
        $form['gen_excel'] = "true";

        $form['gen_goLinkArray'] = array(
            array(
                'onClick' => "javascript:location.href='index.php?action=Config_AdminAccessLog_TotalList'",
                'value' => _g('アクセス集計リスト'),
            ),
        );

        $form['gen_fixColumnArray'] = array(
            array(
                'label' => _g('日時'),
                'field' => 'access_time',
                'width' => '130',
                'align' => 'center',
                'helpText_noEscape' => _g('日本時間で表示されます。'),
            ),
            array(
                'label' => _g('曜日'),
                'field' => 'dow',
                'width' => '40',
                'align' => 'center',
            ),
        );
        $form['gen_columnArray'] = array(
            array(
                'label' => _g('IPアドレス'),
                'field' => 'ip',
                'width' => '100',
            ),
            array(
                'label' => _g('ユーザー名'),
                'field' => 'user_name',
                'width' => '100',
            ),
            array(
                'label' => _g('区分') . '1',
                'field' => 'class_name_1',
                'width' => '110',
            ),
            array(
                'label' => _g('区分') . '2',
                'field' => 'class_name_2',
                'width' => '100',
            ),
            array(
                'label' => _g('url'),
                'field' => 'url',
                'width' => '400',
            ),
            array(
                'label' => _g('action'),
                'field' => 'action',
                'width' => '300',
            ),
        );
    }

}