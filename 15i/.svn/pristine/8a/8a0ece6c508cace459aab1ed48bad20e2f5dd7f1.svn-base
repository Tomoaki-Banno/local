<?php

class Manufacturing_Achievement_AjaxOrderParamBarcode extends Base_AjaxBase
{

    function _execute(&$form)
    {
        if (!isset($form['no'])) {
            return;
        }

        $data = Logic_Achievement::getOrderProcessInfo($form['no']);
        if ($data == null) {
            return;
        }
        
        // 前工程の製造数を取得。ag.cgi?page=ProjectDocView&pid=1574&did=208745
        //  前工程とは、完了した工程のうち、製造日/登録日時が最後のもの。
        //  必ずしも品目マスタの工程の登録順ではない。
        $beforeQty = Logic_Achievement::getBeforeProcessAchievementQuantity($data->order_detail_id, false);
        if (!$beforeQty) {
            $beforeQty = $data->order_detail_quantity;
        }

        return
            array(
                'item_code' => $data->item_code,
                'item_name' => $data->item_name,
                'quantity' => ($data->completed == "t" ? 0 : ($beforeQty - $data->achievement_quantity)),
                'classification' => $data->classification,
                'process_name' => $data->process_name,
                'is_subcontract_process' => $data->is_subcontract_process,
                'remarks' => $data->remarks_header,
            );
    }

}