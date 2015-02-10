<?php

class Config_Setting_AjaxHomeSchedule extends Base_AjaxBase
{

    function _execute(&$form)
    {
        global $gen_db;

        $perm = Gen_Auth::sessionCheck("config_schedule");
        if ($perm == 1 || $perm == 2) {
            // スタイル
            if (isset($form['homeScheduleStyle']) && Gen_String::isNumeric($form['homeScheduleStyle'])) {
                $_SESSION['gen_setting_user']->homeScheduleStyle = $form['homeScheduleStyle'];
                Gen_Setting::saveSetting();
            } else {
                if (isset($_SESSION['gen_setting_user']->homeScheduleStyle)) {
                    $form['homeScheduleStyle'] = $_SESSION['gen_setting_user']->homeScheduleStyle;
                } else {
                    $form['homeScheduleStyle'] = 2;     // 1週間
                }
            }
            
            if (isset($form['begin']) && Gen_String::isDateString($form['begin'])) {
                $fromSrc = $form['begin'];
            } else {
                $fromSrc = date('Y-m-d');
            }
            switch($form['homeScheduleStyle']) {
                case "1":   // 1日
                    $from = $fromSrc;
                    $to = $from;
                    $prev = date('Y-m-d', strtotime($from . " -1 day"));
                    $next = date('Y-m-d', strtotime($from . " +1 day"));
                    break;
                case "3":   // 2週
                    // 当日を含む週の日曜から
                    $firstDayOfWeek = date('w', strtotime($fromSrc));
                    // 月曜始まりにする場合は以下のコメントアウトを外す
                    //if ($firstDayOfWeek == 0) {
                    //    $firstDayOfWeek = 7;
                    //}
                    //$firstDayOfWeek--;       // これで、0（月曜）、1（火曜）... 6（日曜）となる  
                    $from = date('Y-m-d', strtotime($fromSrc . " -{$firstDayOfWeek} day"));
                    
                    $to = date('Y-m-d', strtotime($from . " +13 day"));
                    $prev = date('Y-m-d', strtotime($from . " -7 day"));
                    $next = date('Y-m-d', strtotime($from . " +7 day"));
                    break;
                case "4":   // 月
                    // 月初日を含む週の日曜から
                    //  ⇒以前は月曜始まりだったが変更した。https://gw.genesiss.jp/15i_e-commode/index.php?action=Menu_Chat&chat_detail_id=3009
                    $firstDayOfWeek = date('w', strtotime(date('Y-m-1', strtotime($fromSrc))));
                    // 月曜始まりにする場合は以下のコメントアウトを外す
                    //if ($firstDayOfWeek == 0) {
                    //    $firstDayOfWeek = 7;
                    //}
                    //$firstDayOfWeek--;       // これで、0（月曜）、1（火曜）... 6（日曜）となる  
                    $from = date('Y-m-d', strtotime(date('Y-m-1', strtotime($fromSrc)) . " -{$firstDayOfWeek} day"));
                    
                    // 月末日を含む週の土曜まで
                    $lastDayOfWeek = date('w', strtotime(date('Y-m-t', strtotime($fromSrc))));
                    // 月曜始まりにする場合は以下のコメントアウトを外す
                    //if ($lastDayOfWeek == 0) {
                    //    $lastDayOfWeek = 7;
                    //}
                    //$lastDayOfWeek = 7 - $lastDayOfWeek;    // これで、0（日曜）、1（土曜）... 6（月曜）となる   
                    $lastDayOfWeek = 6 - $lastDayOfWeek;    // これで、0（土曜）、1（金曜）... 6（日曜）となる   
                    $to = date('Y-m-d', strtotime(date('Y-m-t', strtotime($fromSrc)) . " +{$lastDayOfWeek} day"));

                    $prev = date('Y-m-d', strtotime(date('Y-m-1', strtotime($fromSrc)) . " -1 month"));
                    $next = date('Y-m-d', strtotime(date('Y-m-1', strtotime($fromSrc)) . " +1 month"));
                    break;
                case "2":   // 1週
                default:
                    // 当日を含む週の日曜から
                    $firstDayOfWeek = date('w', strtotime($fromSrc));
                    // 月曜始まりにする場合は以下のコメントアウトを外す
                    //if ($firstDayOfWeek == 0) {
                    //    $firstDayOfWeek = 7;
                    //}
                    //$firstDayOfWeek--;       // これで、0（月曜）、1（火曜）... 6（日曜）となる  
                    $from = date('Y-m-d', strtotime($fromSrc . " -{$firstDayOfWeek} day"));
                    
                    $to = date('Y-m-d', strtotime($from . " +6 day"));
                    $prev = date('Y-m-d', strtotime($from . " -7 day"));
                    $next = date('Y-m-d', strtotime($from . " +7 day"));
                    break;
            }
            
            Logic_Schedule::createTempScheduleTable(
                $from, 
                $to, 
                Gen_Auth::getCurrentUserId(), 
                null,     // search
                true,    // isShowNewButton
                true,    // isLinkEnable
                false     // isCrossCount
            );
            $scd = $gen_db->queryOneRowObject("select * from temp_schedule");
            
            // 休業日
            $query = "select holiday from holiday_master where holiday between '{$from}' and '{$to}'";
            $res = $gen_db->getArray($query);
            $holidayArr = array();
            if ($res) {
                foreach ($res as $row) {
                    $holidayArr[] = strtotime($row['holiday']);
                }
            }
            
            // スケジュール展開
            $scdArr = array();
            $today =  strtotime(date('Y-m-d'));
            $thisMonth = date('m', strtotime($fromSrc));
            for($day = strtotime($from); $day <= strtotime($to); $day += 86400) {
                $prop = "day" . date('Ymd', $day);
                $scdArr[] = array(
                    'date' => date('m-d', $day) . "(" . Gen_String::weekdayStr(date('Y-m-d', $day)) . ")",
                    'schedule_text' => $scd ? Logic_Schedule::replaceTagToHTML(h($scd->$prop), true) : "",
                    'is_holiday' => in_array($day, $holidayArr) ? 't' : '',
                    'is_today' => $day == $today && $form['homeScheduleStyle'] != "1" ? 't' : '',
                    'is_thismonth' => date('m', $day) == $thisMonth ? 't' : '',
                );
            }
        } else {
            $scdArr = false;
            $prev = false;
            $next = false;
        }

        return 
            array(
                "data" => $scdArr,
                "prev" => $prev,
                "next" => $next,
            );
    }

}