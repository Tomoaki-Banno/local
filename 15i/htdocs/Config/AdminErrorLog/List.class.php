<?php

class Config_AdminErrorLog_List extends Base_ListBase
{

    function setSearchCondition(&$form)
    {
        $form['gen_searchControlArray'] = array(
            array(
                'label' => _g('発生日時'),
                'type' => 'dateTimeFromTo',
                'field' => 'error_time',
                'defaultFrom' => date('Y-m-d 00:00'),
                'defaultTo' => date('Y-m-d 23:59'),
                'size' => '120',
                'rowSpan' => 2,
            ),
            array(
                'label' => _g('ユーザー名'),
                'field' => 'user_name',
            ),
            array(
                'label' => _g('IPアドレス'),
                'field' => 'ip',
            ),
            array(
                'label' => _g('実行クラス'),
                'field' => 'function_name',
            ),
        );
    }

    function convertSearchCondition($converter, &$form)
    {
        // 検索条件（日付）に不正な値が指定されたとき、正しい値に変換しておく。
        // converterには時刻まで判定する関数がないので、自前で判定している。
        // 最後の一文字をチェックしているのは、strtotimeでは「2009-11-01 0:00a」がOKになってしまうため。
        if (isset($form['gen_search_error_time_from']) && $form['gen_search_error_time_from'] != '' && (!Gen_String::isDateTimeString($form['gen_search_error_time_from']) || !is_numeric(substr($form['gen_search_error_time_from'], -1))))
            $form['gen_search_error_time_from'] = date('Y-m-d 00:00');
        if (isset($form['gen_search_error_time_to']) && $form['gen_search_error_time_to'] != '' && (!Gen_String::isDateTimeString($form['gen_search_error_time_to']) || !is_numeric(substr($form['gen_search_error_time_to'], -1))))
            $form['gen_search_error_time_to'] = date('Y-m-d 23:59');
    }

    function beforeLogic(&$form)
    {
    }

    function setQueryParam(&$form)
    {
        $this->selectQuery = "
            select
                *
                /* 改行があるとリスト表示が崩れるので削除 */
                ,replace(error_query,'[br]','') as error_query_show
                ,case date_part('dow',error_time)
                    when 0 then '" . _g("日") . "'
                    when 1 then '" . _g("月") . "'
                    when 2 then '" . _g("火") . "'
                    when 3 then '" . _g("水") . "'
                    when 4 then '" . _g("木") . "'
                    when 5 then '" . _g("金") . "'
                    else '" . _g("土") . "' end as dow
            from
                error_log
            [Where]
            [Orderby]
        ";
        $this->orderbyDefault = 'error_time';
    }

    function setViewParam(&$form)
    {
        $form['gen_pageTitle'] = _g("エラーログ（admin専用）");
        $form['gen_menuAction'] = "Menu_Admin";
        $form['gen_listAction'] = "Config_AdminErrorLog_List";
        $form['gen_excel'] = "true";

        $form['gen_fixColumnArray'] = array(
            array(
                'label' => _g('発生日時'),
                'field' => 'error_time',
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
                'label' => _g('ユーザー名'),
                'field' => 'user_name',
                'width' => '100',
            ),
            array(
                'label' => _g('IPアドレス'),
                'field' => 'ip',
                'width' => '100',
            ),
            array(
                'label' => _g('実行クラス'),
                'field' => 'function_name',
            ),
            array(
                'label' => _g('コールスタック'),
                'field' => 'call_stack',
            ),
            array(
                'label' => _g('エラー番号'),
                'field' => 'error_no',
                'hide' => true,
            ),
            array(
                'label' => _g('エラー内容'),
                'field' => 'error_comment',
                'width' => '350',
            ),
            array(
                'label' => _g('実行クエリ'),
                'field' => 'error_query_show',
                'width' => '350',
            ),
        );
    }

}