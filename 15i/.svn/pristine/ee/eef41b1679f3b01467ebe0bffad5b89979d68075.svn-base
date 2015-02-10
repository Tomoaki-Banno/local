<?php

class Delivery_Delivery_AjaxCompletedFlagRegist extends Base_AjaxBase
{

    function _execute(&$form)
    {
        global $gen_db;
        
        
        $status = "failure";
        if (!$form['gen_readonly']) {
            $gen_db->begin();
            foreach ($form as $deliveryDetailId => $cmp) {
                if (Gen_String::isNumeric($deliveryDetailId) && ($cmp == "0" || $cmp == "1")) {
                    Logic_Delivery::completedOperation($deliveryDetailId, $cmp == "0" ? "false" : "true");
                }
            }
            $gen_db->commit();
            $status = "success";

            // データアクセスログ
            Gen_Log::dataAccessLog(_g("納品登録"), _g("完了フラグ更新"),"");
        }

        return
            array(
                'status' => $status,
            );
    }
}