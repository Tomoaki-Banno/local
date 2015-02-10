<?php

class Manufacturing_Mrp_AjaxResetHandAdjust extends Base_AjaxBase
{

    function _execute(&$form)
    {
        global $gen_db;

        // planテーブルの「計算」レコードを削除
        $query = "delete from plan where classification = 3";
        $gen_db->query($query);

        // mrpテーブルの更新
        $data = array(
            'arrangement_quantity' => 'noquote:arrangement_quantity-coalesce(hand_qty,0)',
            'hand_qty' => null
        );
        $where = "";
        $gen_db->update("mrp", $data, $where);

        // order_detailテーブルの更新
        $data = array(
            'hand_qty' => null
        );
        $where = "hand_qty<>0";
        $gen_db->update("order_detail", $data, $where);

        // データアクセスログ
        Gen_Log::dataAccessLog(_g("所要量計算結果"), _g("リセット"), '');

        $gen_db->commit();

        return 
            array(
                "success" => "true"
            );
    }

}