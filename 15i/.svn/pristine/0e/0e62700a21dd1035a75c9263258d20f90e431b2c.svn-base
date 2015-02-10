<?php

class Config_Schedule_List extends Base_ListBase
{

    function setSearchCondition(&$form)
    {
        global $gen_db;

        $query = "select section_id, section_name from section_master order by section_code";
        $option_section = $gen_db->getHtmlOptionArray($query, false, array(null => _g("(すべて)")));

       $form['gen_searchControlArray'] = array(
            array(
                'label' => _g("表示期間(最大50日)"),
                'type' => 'dateFromTo',
                'field' => 'begin_date',
                'nosql' => 'true',
                'defaultFrom' => date('Y-m-d'),
                'defaultTo' => date('Y-m-d', strtotime("+6 day")),
            ),
            array(
                'label' => _g('スケジュール'),
                'field' => 'schedule_text',
                'ime' => 'on',
                'nosql' =>  true,
            ),
            array(
                'label'=>_g('部門'),
                'field'=>'section_id',
                'type'=>'select',
                'options'=>$option_section,
            ),
        );
    }

    function convertSearchCondition($converter, &$form)
    {
        // from も to も空欄だった場合
        if (!Gen_String::isDateString($form['gen_search_begin_date_from']) && !Gen_String::isDateString($form['gen_search_begin_date_to'])) {
            $form['gen_search_begin_date_from'] = date("Y-m-d");
        }
        
        // from か to が空欄だった場合
        if (!Gen_String::isDateString($form['gen_search_begin_date_from']) && Gen_String::isDateString($form['gen_search_begin_date_to'])) {
            $form['gen_search_begin_date_from'] = date("Y-m-d", strtotime($form['gen_search_begin_date_to'] . " -50 days"));
        } else if (Gen_String::isDateString($form['gen_search_begin_date_from']) && !Gen_String::isDateString($form['gen_search_begin_date_to'])) {
            $form['gen_search_begin_date_to'] = date("Y-m-d", strtotime($form['gen_search_begin_date_from'] . " +50 days"));
        }
        
        $converter->dateSort('gen_search_begin_date_from', 'gen_search_begin_date_to');
        $converter->dateSpan('gen_search_begin_date_from', 'gen_search_begin_date_to', 50);
    }

    function beforeLogic(&$form)
    {
    }

    function setQueryParam(&$form)
    {
        global $gen_db;
        
        // クロス集計でデータの数をcountしたとき
        $isCrossCount = (isset($form['gen_search_gen_crossTableValue']) && substr($form['gen_search_gen_crossTableValue'], 0, 3) == "day");
        $isExcel = isset($form['gen_excelMode']);
        
        // ハイライトIDが指定されていたとき
        $hilightId = false;
        if (isset($form['hilight_schedule_id']) && Gen_String::isNumeric($form['hilight_schedule_id'])) {
            $hilightId = $form['hilight_schedule_id'];
            $query = "select begin_date from staff_schedule where schedule_id = '$hilightId'";
            $begin = $gen_db->queryOneValue($query);
            if ($begin) {
                $span = strtotime($form['gen_search_begin_date_to']) - strtotime($form['gen_search_begin_date_from']);
                $form['gen_search_begin_date_from'] = $begin;
                $form['gen_search_begin_date_to'] = date('Y-m-d', strtotime($begin) + $span);
            } else {
                $hilightId = false;
            }
        }
        
        // データ取得
        Logic_Schedule::createTempScheduleTable(
                $form['gen_search_begin_date_from'], 
                $form['gen_search_begin_date_to'], 
                null,   // userId
                isset($form['gen_search_schedule_text']) ? $form['gen_search_schedule_text'] : null,
                !$isCrossCount && !$isExcel,    // isShowNewButton
                !$isCrossCount && !$isExcel,    // isLinkEnable
                $isCrossCount,                  // isCrossCount
                $hilightId
            );
        
        $this->selectQuery = "select * from temp_schedule [Where] [Orderby]";
        $this->orderbyDefault = 'for_order';
    }

    function setViewParam(&$form)
    {
        global $gen_db;
        
        $form['gen_pageTitle'] = _g("スケジュール");
        $form['gen_listAction'] = "Config_Schedule_List";
        $form['gen_editAction'] = "Config_Schedule_Edit";
        $form['gen_idField'] = 'schedule_id';
        $form['gen_excel'] = "true";
        $form['gen_onLoad_noEscape'] = "onLoad()";

        //  dataRowHeightに0以下を指定した場合は、行の高さが固定されない。
        //  ただしその場合は固定列（fixColumn）が使えない。固定列とスクロール列の高さがそろわないので。
        $form['gen_dataRowHeight'] = 0;        // データ部の1行の高さ

        $url = "index.php?action=Config_Schedule_Edit";
        $form['gen_javascript_noEscape'] = "
            var ctrl = false;
            function onLoad() {
                window.addEventListener('keydown', function(e){
                    if (e.ctrlKey) {
                        ctrl = true;
                    }   
                    if ($('#gen_editFrame').length != 0) {
                        ctrl = false;
                    }
               });
               window.addEventListener('keyup', function(e){
                    if (e.key == 'Control' || e.keyIdentifier == 'Control' ) {
                        ctrl = false;
                    }   
               });
               window.addEventListener('blur', function(e){
                    ctrl = false;
               });
            }
            function scheduleGoEdit(id) {
                url = '{$url}' + '&schedule_id=' + id;
                if (ctrl) {
                    url += '&gen_record_copy';
                }
                gen.modal.open(url);
            }
            function scheduleNewEdit(userId, date) {
                url = '{$url}' + '&user_id=' + userId + '&begin_date=' + date;
                gen.modal.open(url);
            }
        ";

        // 日付移動リンク
        $commonUrl = "index.php?action=Config_Schedule_List";
        if (isset($form['gen_search_schedule_text']) && $form['gen_search_schedule_text'] != "") {
            $commonUrl .= "&gen_search_schedule_text=" . h($form['gen_search_schedule_text']);
        }
        $commonUrl .= "&gen_search_begin_date_from=";

        $before7from = date('Y-m-d', strtotime($form['gen_search_begin_date_from'] . " -7 day"));
        $before7to = date('Y-m-d', strtotime($form['gen_search_begin_date_from'] . " -1 day"));
        $before1from = date('Y-m-d', strtotime($form['gen_search_begin_date_from'] . " -1 day"));
        $before1to = date('Y-m-d', strtotime($form['gen_search_begin_date_from'] . " +5 day"));
        $todayfrom = date('Y-m-d');
        $todayto = date('Y-m-d', strtotime(" +6 day"));
        $after1from = date('Y-m-d', strtotime($form['gen_search_begin_date_from'] . " +1 day"));
        $after1to = date('Y-m-d', strtotime($form['gen_search_begin_date_from'] . " +7 day"));
        $after7from = date('Y-m-d', strtotime($form['gen_search_begin_date_from'] . " +7 day"));
        $after7to = date('Y-m-d', strtotime($form['gen_search_begin_date_from'] . " +13 day"));

        $form['gen_message_noEscape'] =
                "<a href='" . $commonUrl . $before7from . "&gen_search_begin_date_to=" . $before7to . "&gen_restore_search_condition=true'> <<< " . _g("前の７日間") . "</a>" .
                "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;" .
                "<a href='" . $commonUrl . $before1from . "&gen_search_begin_date_to=" . $before1to . "&gen_restore_search_condition=true'> < " . _g("前の日") . "</a>" .
                "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;" .
                "<a href='" . $commonUrl . $todayfrom . "&gen_search_begin_date_to=" . $todayto . "&gen_restore_search_condition=true'> " . _g("今日") . "</a>" .
                "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;" .
                "<a href='" . $commonUrl . $after1from . "&gen_search_begin_date_to=" . $after1to . "&gen_restore_search_condition=true'> " . _g("次の日") . " > </a>" .
                "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;" .
                "<a href='" . $commonUrl . $after7from . "&gen_search_begin_date_to=" . $after7to . "&gen_restore_search_condition=true'> " . _g("次の７日間") . " >>> </a>" .
                "";

        $form['gen_colorSample'] = array(
            "ffffcc" => array(_g("イエロー"), _g("今日")),
            "ffcccc" => array(_g("レッド"), _g("休業日（カレンダーマスタ）")),
        );

        // fix列は使えないことに注意。上の gen_dataRowHeightのコメントを参照
        $form['gen_columnArray'] = array(
            array(
                'label' => _g('ユーザー名'),
                'field' => 'user_name',
                'align' => 'center',
            ),
        );
        
        $from = strtotime($form['gen_search_begin_date_from']);
        $to = strtotime($form['gen_search_begin_date_to']);
        
        // 休業日
        $query = "select holiday from holiday_master where holiday between '" . date('Y-m-d', $from) . "' and '" . date('Y-m-d', $to) . "'";
        $res = $gen_db->getArray($query);
        $holidayArr = array();
        if ($res) {
            foreach ($res as $row) {
                $holidayArr[] = strtotime($row['holiday']);
            }
        }

        for ($date = $from; $date <= $to; $date += 86400) {        // 86400sec = 1day
            $fieldName = 'day' . date('Ymd', $date);
            $weekday = date('w', $date);
            $form['gen_columnArray'][] = array(
                'label' => date('m-d', $date) . "(" . Gen_String::weekdayStr(date('Y-m-d', $date)) . ")",
                'field' => $fieldName,
                'type' => 'schedule',
                'width' => '150',
                'wrapOn' => true, // 折り返して全体を表示
                'denyMove' => true, // 日付列は列順序固定。日付範囲を変更したときの表示乱れを防ぐため
                'valign' => 'top',
                'colorCondition' => array(
                    // 今日
                    "#ffffcc" => "'" . (date('Y-m-d', $date) == date('Y-m-d')) . "'",
                    // 休業日
                    "#ffcccc" => "'" . (in_array($date, $holidayArr)) . "'",
                    // 日曜日
                    //"#ffcccc" => ($weekday == 0 ? "true" : "false"),
                ),
            );
        }
    }

}
