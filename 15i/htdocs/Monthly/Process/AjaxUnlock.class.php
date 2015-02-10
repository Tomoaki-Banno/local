<?php

class Monthly_Process_AjaxUnlock extends Base_AjaxBase
{

    function _execute(&$form)
    {
        $object1 = 0;   // 受注登録
        $object2 = 0;   // 製造指示登録
        $object3 = 0;   // 注文登録
        $object4 = 0;   // 外製指示登録
        $object = "";
        if (isset($form['unlock_object_1']) && $form['unlock_object_1'] == "true") {
            $object1 = 1;
            $object .= _g("受注登録");
        }
        if (isset($form['unlock_object_2']) && $form['unlock_object_2'] == "true") {
            $object2 = 1;
            $object .= ($object == "" ? "" : ",") . _g("製造指示登録");
        }
        if (isset($form['unlock_object_3']) && $form['unlock_object_3'] == "true") {
            $object3 = 1;
            $object .= ($object == "" ? "" : ",") . _g("注文登録");
        }
        if (isset($form['unlock_object_4']) && $form['unlock_object_4'] == "true") {
            $object4 = 1;
            $object .= ($object == "" ? "" : ",") . _g("外製指示登録");
        }
        if ($object == "") {
            $object = _g("なし");
        }

        // ロック処理実行
        if (Logic_DataLock::dataUnlock($object1, $object2, $object3, $object4)) {
            $status = "success";
        } else {
            $status = "failure";
        }

        // データアクセスログ
        $msg = "[" . _g("ロック対象外") . "]" . " " . $object;
        Gen_Log::dataAccessLog(_g("データロック対象外"), _g("更新"), $msg);

        return
            array(
                "status" => $status
            );
    }

}