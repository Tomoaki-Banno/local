<?php

class Manufacturing_Mrp_AjaxOrderCheck extends Base_AjaxBase
{

    function _execute(&$form)
    {
        global $gen_db;

        // 受注製番削除による再計算実施対策
        $query = "select mrp_id from mrp where order_class = 0 and (arrangement_quantity > 0 or arrangement_quantity < 0) and seiban not in (select seiban from received_detail)";
        $notOrder = false;
        if ($gen_db->existRecord($query))
            $notOrder = true;

        return 
            array(
                "status" => ($notOrder ? "success" : "")
            );
    }

}