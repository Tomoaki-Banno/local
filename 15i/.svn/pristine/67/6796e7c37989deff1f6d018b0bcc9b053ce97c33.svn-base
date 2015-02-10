<?php

class Delivery_Delivery_AjaxPrintedCheck extends Base_AjaxBase
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

        // 明細モードのときはdelivery_detail_idが指定されているので、delivery_header_idに変換しておく
        if ($isDetailMode) {
            $query = "select delivery_header_id from delivery_detail
                where delivery_detail_id in (" . join(",", $idArr) . ")
                group by delivery_header_id";
            $idArr2 = $gen_db->getArray($query);
            $idArr = array();
            foreach ($idArr2 as $row) {
                $idArr[] = $row['delivery_header_id'];
            }
            if (count($idArr) == 0)
                return;
        }

        $idCsv = join(',', $idArr);
        if (!isset($idCsv) || $idCsv == "")
            return;
        $query = "select count(delivery_header_id) from delivery_header
                    where delivery_header_id in ({$idCsv}) and delivery_printed_flag = 'true'";
        $count = Gen_String::nz($gen_db->queryOneValue($query));

        return
            array(
                "status" => ($count > 0 ? "success" : "")
            );
    }

}