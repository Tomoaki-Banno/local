<?php

require_once("Model.class.php");

class Config_Schedule_Entry extends Base_EntryBase
{

    function setParam(&$form)
    {
        // 基本パラメータ
        $this->listAction = "Config_Schedule_List";
        $this->errorAction = "Config_Schedule_Edit";
        $this->modelName = "Config_Schedule_Model";
        $this->newRecordNotKeepField = array(
            "end_date", // 「繰り返し」有効時の「1970-1-1」問題対策
        );

        if ($form['begin_time_hour'] == "" && $form['begin_time_minute'] == "") {
            $form['begin_time'] = "";
        } else {
            $form['begin_time'] = $form['begin_time_hour'] . ":" . $form['begin_time_minute'];
        }
        if ($form['end_time_hour'] == "" && $form['end_time_minute'] == "") {
            $form['end_time'] = "";
        } else {
            $form['end_time'] = $form['end_time_hour'] . ":" . $form['end_time_minute'];
        }

        // gen_app ユーザーリスト
        if (isset($_SESSION['gen_app']) && $_SESSION['gen_app'] === true) {
            global $gen_db;
            
            $userArr = array();
            foreach ($form as $key => $value) {
                if (strlen($key) > 5 && substr($key,0,5) == "user_") {
                    $userId = substr($key,5);
                    if (Gen_String::isNumeric($userId) && $value == "true") {
                        $userArr[] = $userId;
                    }
                }
            }
            $form['users'] = join(",", $userArr);
        }
    }

    function setLogParam($form)
    {
        global $gen_db;
        if (isset($form['non_disclosure']) && $form['non_disclosure'] == "true") {
            $text = "(" . _g("非公開") . ")";
        } else {
            $text = h(mb_substr($form['schedule_text'], 0, 20));
        }
        $this->log1 = _g("スケジュール");
        $this->log2 = "[" . _g("日付") . "] " . $form['begin_date'] . " [" . _g("スケジュール") . "] " . $text;
        $this->afterEntryMessage = _g("スケジュールを登録しました。");
    }

}
