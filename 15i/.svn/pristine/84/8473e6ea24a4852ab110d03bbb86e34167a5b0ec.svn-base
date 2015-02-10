<?php

class Delivery_Delivery_AjaxReceivedParamBarcode extends Base_AjaxBase
{

    // seiban（必須）を受け取り、バーコード納品登録画面用の各種情報を返す

    function _execute(&$form)
    {
        global $gen_db;

        $obj =
            array(
                "status" => "failure"
            );
        
        if (!isset($form['seiban']) || $form['seiban'] == "") {
            return $obj;
        }

        // データ取得
        $query = "
        select
            item_master.item_id
            ,item_code
            ,item_name
            ,case when delivery_completed then 0 else
                (coalesce(received_quantity,0) - coalesce(delivery_quantity,0)) end as remained_quantity
            ,item_master.image_file_name
        from
            item_master
            inner join received_detail on item_master.item_id=received_detail.item_id
            inner join received_header on received_detail.received_header_id=received_header.received_header_id
            left join (
                select
                    received_detail_id,
                    SUM(delivery_quantity) as delivery_quantity
                from
                    delivery_detail
                group by
                    received_detail_id
                ) as T2 on received_detail.received_detail_id=T2.received_detail_id
        -- 予約レコードのデータは返さないようにした
        where
            seiban = '{$form['seiban']}'
            and received_header.guarantee_grade=0
        ";
        $res = $gen_db->queryOneRowObject($query);

        if (!$res || $res == null)
            return $obj;

        return
            array(
                'status' => "success",
                'item_code' => $res->item_code,
                'item_name' => $res->item_name,
                'rem_qty' => $res->remained_quantity,
                'image_file_name' => $res->image_file_name,
            );
    }

}