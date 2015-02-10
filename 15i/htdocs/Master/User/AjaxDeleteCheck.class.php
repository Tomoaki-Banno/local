<?php

class Master_User_AjaxDeleteCheck extends Base_AjaxBase
{

    function _execute(&$form)
    {
        global $gen_db;

        $ids = explode(',', $form['ids']);
        if (count($ids) == 0)
            return;

        // id配列を取得
        $idArr = array();
        foreach ($ids as $id) {
            $id = str_replace("delete_", "", $id);
            if (is_numeric($id))
                $idArr[] = $id;
        }
        if (count($idArr) == 0)
            return;

        $idCsv = join(',', $idArr);
        $query = "select count(user_id) from stickynote_info where user_id in ({$idCsv})";
        $count = Gen_String::nz($gen_db->queryOneValue($query));

        $obj['status'] = ($count > 0 ? "success" : "");

        return $obj;
    }

}