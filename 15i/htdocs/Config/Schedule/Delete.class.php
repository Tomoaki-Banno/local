<?php

class Config_Schedule_Delete extends Base_DeleteBase
{

    function dataExistCheck(&$form)
    {
        global $gen_db;

        // 対象データ存在チェック（リロード対策）
        $this->listAction = "Config_Schedule_Edit";

        if (!is_numeric($form['schedule_id']))
            return false;
        $query = "select schedule_id from staff_schedule where schedule_id = '{$form['schedule_id']}'";
        return $gen_db->existRecord($query);
    }

    function validate($validator, &$form)
    {
        $form['gen_restore_search_condition'] = 'true';
        return 'action:Config_Schedule_List';        // if error
    }

    function setParam(&$form)
    {
        global $gen_db;
        
        // メッセージとログ
        $query = "select user_name from staff_schedule_user left join user_master on staff_schedule_user.user_id = user_master.user_id where schedule_id = '{$form['schedule_id']}'";
        $userName = $gen_db->queryOneValue($query);

        $this->afterEntryMessage = _g("スケジュールを削除しました。");
        $this->logTitle = _g("スケジュール");
        $this->logMsg = "[" . _g("ユーザー名") . "] " . $userName;
    }

    function deleteExecute(&$form)
    {
        global $gen_db;

        $this->deleteId = $form['schedule_id'];

        // 削除処理
        $query = "
            delete from staff_schedule where schedule_id = '{$form['schedule_id']}';
            delete from staff_schedule_user where schedule_id = '{$form['schedule_id']}';
        ";
        $gen_db->query($query);
        unset($form['schedule_id']);
    }

}