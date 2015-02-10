<?php

class Partner_Accepted_BulkInspection
{

    function validate(&$validator, &$form)
    {
        if ($form['inspection_date'] != "") {
            $validator->buyLockDateOrLater('inspection_date', _g("検収日"));
        }

        $form['gen_restore_search_condition'] = 'true';
        return 'action:Partner_Accepted_List';        // if error
    }

    function execute(&$form)
    {
        global $gen_db;

        // 対象データを配列に列挙する
        $idArr = array();
        foreach ($form as $name => $value) {
            if (substr($name, 0, 6) == "check_") {
                $idArr[] = substr($name, 6, strlen($name) - 6);
            }
        }

        // 日付取得
        $inspectionDate = $form['inspection_date'];

        // アクセス権チェック
        if ($form['gen_readonly'] == 'true') {
            return "action:Partner_Accepted_List";
        }

        // 検収日の更新
        Logic_Accepted::updateInspectionDate($idArr, $inspectionDate);

        $form['gen_afterEntryMessage'] = _g("一括検収登録を実行しました。");

        // データアクセスログ
        Gen_Log::dataAccessLog(_g("受入"), _g("一括検収"), _g("[検収日] ") . ($inspectionDate != "" ? date('Y-m-d', strtotime($inspectionDate)) : _g("削除")));

        $form['gen_restore_search_condition'] = 'true';
        return 'action:Partner_Accepted_List';
    }

}
