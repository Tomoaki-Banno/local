<?php

class Config_DataAccessLog_List extends Base_ListBase
{

    function setSearchCondition(&$form)
    {
        $form['gen_searchControlArray'] = array(
            array(
                'label' => _g('データ'),
                'field' => 'table_name',
            ),
            array(
                'label' => _g('種別'),
                'field' => 'classification',
            ),
            array(
                'label' => _g('ユーザー名'),
                'field' => 'user_name',
            ),
            array(
                'label' => _g('更新日時'),
                'type' => 'dateTimeFromTo',
                'field' => 'access_time',
                'defaultFrom' => date('Y-m-d 00:00'),
                'defaultTo' => date('Y-m-d 23:59'),
                'size' => '120',
            ),
            array(
                'label' => _g('データ備考'),
                'field' => 'remarks',
            ),
        );
        
        // プリセット表示条件パターン
        $form['gen_savedSearchConditionPreset'] =
            array(
                _g("画面別登録数ランキング（今年）") => self::_getPreset("7", "gen_all", "table_name", "table_name", "count", "order by field1 desc"),
                _g("ユーザー登録数ランキング（今年）") => self::_getPreset("7", "gen_all", "user_name", "user_name", "count", "order by field1 desc"),
                _g("ユーザー - 画面（今年）") => self::_getPreset("7", "user_name", "table_name", "user_name", "count", ""),
                _g("月次登録数推移") => self::_getPreset("0", "gen_all", "access_time_month", "table_name", "count", "order by field0 desc", _g("すべて")),
                _g("月次登録数 前年対比") => self::_getPreset("0", "access_time_month", "access_time_year", "table_name", "count", ""),
            );
    }
    
    function _getPreset($datePattern, $horiz, $vert, $value, $method, $orderby, $chart = "")
    {
        return
            array(
                "data" => array(
                    array("f" => "table_name", "v" => ""),
                    array("f" => "classification", "v" => ""),
                    array("f" => "user_name", "v" => ""),
                    array("f" => "access_time", "dp" => $datePattern),
                    array("f" => "remarks", "v" => ""),
                    array("f" => "gen_crossTableHorizontal", "v" => $horiz),
                    array("f" => "gen_crossTableVertical", "v" => $vert),
                    array("f" => "gen_crossTableValue", "v" => $value),
                    array("f" => "gen_crossTableMethod", "v" => $method),
                    array("f" => "gen_crossTableChart", "v" => ($chart == ""  ? _g("なし") : $chart)),
                ),
                "orderby" => $orderby,
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
        $this->selectQuery = "
            select
                table_name
                ,user_name
                ,access_time
                ,case date_part('dow',access_time)
                    when 0 then '" . _g("日") . "'
                    when 1 then '" . _g("月") . "'
                    when 2 then '" . _g("火") . "'
                    when 3 then '" . _g("水") . "'
                    when 4 then '" . _g("木") . "'
                    when 5 then '" . _g("金") . "'
                    else '" . _g("土") . "' end as dow
                ,classification
                ,remarks
            from
                data_access_log
            [Where]
                " . ($_SESSION['user_id'] == "-1" ? "" : " and user_name <> '" . ADMIN_NAME . "' and not table_name like '%" . _g("モバイル") . "%'") . "
            [Orderby]
        ";
        $this->orderbyDefault = 'access_time desc';
    }

    function setViewParam(&$form)
    {
        $form['gen_pageTitle'] = _g("データ更新ログ");
        $form['gen_listAction'] = "Config_DataAccessLog_List";
        $form['gen_excel'] = "true";

        $form['gen_fixColumnArray'] = array(
            array(
                'label' => _g('データ'),
                'field' => 'table_name',
            ),
            array(
                'label' => _g('種別'),
                'field' => 'classification',
            ),
        );
        $form['gen_columnArray'] = array(
            array(
                'label' => _g('ユーザー名'),
                'field' => 'user_name',
            ),
            array(
                'label' => _g('更新日時'),
                'field' => 'access_time',
                'type' => 'datetime',
                'width' => '140',
                'align' => 'center',
                'helpText_noEscape' => _g('日本時間で表示されます。'),
            ),
            array(
                'label' => _g('曜日'),
                'field' => 'dow',
                'width' => '40',
                'align' => 'center',
            ),
            array(
                'label' => _g('データ備考'),
                'field' => 'remarks',
                'width' => '550',
            ),
        );
    }

}