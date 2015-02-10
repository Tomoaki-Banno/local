<?php

class Partner_Order_AjaxPrintedCheck extends Base_AjaxBase
{

    function _execute(&$form)
    {
        global $gen_db;

        $isDetailMode = isset($form['detail']) && $form['detail'] == "true";

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

        // 明細モードのときはorder_detail_idが指定されているので、order_header_idに変換しておく
        if ($isDetailMode) {
            $query = "select order_header_id from order_detail
                where order_detail_id in (" . join(",", $idArr) . ")
                group by order_header_id";
            $idArr2 = $gen_db->getArray($query);
            $idArr = array();
            foreach ($idArr2 as $row) {
                $idArr[] = $row['order_header_id'];
            }
        }

        $idCsv = join(',', $idArr);
        $query = "select count(order_header_id) from order_header
                    where order_header_id in ({$idCsv}) and order_printed_flag = 'true'";
        $count = Gen_String::nz($gen_db->queryOneValue($query));

        return
            array(
                "status" => ($count > 0 ? "success" : "")
            );
    }

}