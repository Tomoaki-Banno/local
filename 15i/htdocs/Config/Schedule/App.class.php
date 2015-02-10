<?php

class Config_Schedule_App
{

    function execute(&$form)
    {
        global $gen_db;
        
        if (isset($form['from']) && Gen_String::isDateString($form['from'])) {
            $from = $form['from'];
        } else {
            $from = date('Y-m-d');
        }
        if (isset($form['days']) && Gen_String::isNumeric($form['days']) && $form['days'] > 0 && $form['days'] < 300) {
            $days = $form['days'];
        } else {
            $days = 5;
        }
        $to = date('Y-m-d', strtotime($from . " +" . ($days - 1) . "day"));
        $userId = null;
        if (isset($form['userId']) && Gen_String::isNumeric($form['userId'])) {
            $userId = $form['userId'];
        } else if (isset($form['mine'])) {
            $userId = Gen_Auth::getCurrentUserId();
        }
        Logic_Schedule::createTempScheduleTable(
                $from, 
                $to,                
                $userId,       // userId
                null,       // search text
                false,      // isShowNewButton
                false,      // isLinkEnable
                false,      // isCrossCount
                null,       // hilightId
                true        // appMode
            );
        $query = "select * from temp_schedule";
        $dataArr = $gen_db->getArray($query);
        if ($dataArr) {
            $query = "select holiday from holiday_master where holiday between '{$from}' and '{$to}'";
            $holidayArr = $gen_db->getArray($query);
            if ($holidayArr) {
                foreach($holidayArr as $key => $row) {
                    $holidayArr[$key] = date("Ymd", strtotime($row['holiday']));
                }
            }

            $resArr = array(
                "data" => $dataArr,
                "holiday" => $holidayArr,
                );
            $form['response_noEscape'] = json_encode($resArr);
        }

        // JSON直接ブラウジングによるXSS回避のため、Content-typeを正しく設定する。
        header("Content-Type: application/json; charset=UTF-8");
        
        return 'simple.tpl';

    }
}
