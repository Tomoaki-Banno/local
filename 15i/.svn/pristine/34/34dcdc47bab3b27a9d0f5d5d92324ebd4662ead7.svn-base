<?php

class Delivery_Delivery_AjaxDeliveryParam extends Base_AjaxBase
{

    // received_detail_id と delivery_header_id を受け取り納品合計数を返す。

    function _execute(&$form)
    {
        global $gen_db;

        $obj = 
            array(
                "status" => "failure"
            );

        // 数字
        if (!isset($form['delivery_header_id']) || !is_numeric($form['delivery_header_id']))
            return $obj;
        // データ
        if (!isset($form['delivery_data']) || $form['delivery_data'] == '')
            return $obj;

        // データ分割（行毎にデータを分割）
        $lineArr = explode(";", $form['delivery_data']);
        if (!isset($lineArr) || !is_array($lineArr))
            return $obj;

        $msg = "";
        $check = "";
        foreach ($lineArr as $value) {
            // データ分割（項目毎にデータを分割）
            $data = explode(":", $value);
            if (!isset($data) || !is_array($data))
                continue;
            $line_no = @$data[0];
            $received_detail_id = @$data[1];
            $delivery_quantity = @$data[2];
            $delivery_completed = @$data[3];

            // 完了チェックがオンの時
            if ($delivery_completed == "1") {
                // データ取得
                $query = "
                select
                    received_detail.received_detail_id
                    ,max(received_quantity) as received_quantity
                    ,coalesce(sum(delivery_quantity),0) as delivery_total
                from
                    received_detail
                    left join (select * from delivery_detail where delivery_header_id <> '{$form['delivery_header_id']}'
                        ) as t_delivery on received_detail.received_detail_id = t_delivery.received_detail_id
                where
                    received_detail.received_detail_id = '{$received_detail_id}'
                group by
                    received_detail.received_detail_id
                ";
                $res = $gen_db->queryOneRowObject($query);

                if ($res != false && $res != null) {
                    if (((float) $res->delivery_total + (float) $delivery_quantity) < (float) $res->received_quantity) {
                        $check = "error";
                        if ($msg != "")
                            $msg .= ", ";
                        $msg .= sprintf(_g("%d行目"), $line_no);
                    }
                }
            }
        }

        return
            array(
                'status' => "success",
                'check' => $check,
                'message' => $msg,
            );
    }

}