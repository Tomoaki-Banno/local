<?php

class Stock_Move_AjaxStockParam extends Base_AjaxBase
{

    function _execute(&$form)
    {
        global $gen_db;

        // idは 「品目id_製番_ロケid_ロットid」
        if (!isset($form['item_seiban_location_lot']))
            return;
        if ($form['item_seiban_location_lot'] == "")
            return;

        $arr = explode("_", $form['item_seiban_location_lot']);
        if (count($arr) != 4)
            return;
        $itemId = $arr[0];
        $seiban = $arr[1];
        $locationId = $arr[2];
        $lotId = $arr[3];
        if (!is_numeric($itemId))
            return;
        if (!is_numeric($locationId))
            return;
        if (!is_numeric($lotId))
            return;

        $query = "select item_code, item_name from item_master where item_id = '{$itemId}'";
        $item = $gen_db->queryOneRowObject($query);
        $locationName = $gen_db->queryOneValue("select location_name from location_master where location_id = '{$locationId}'");
        $lotNo = $gen_db->queryOneValue("select lot_no from lot_master where lot_id = '{$lotId}'");

        $logicalStockQty = Logic_Stock::getLogicalStock($itemId, $seiban, $locationId, $lotId);

        return
            array(
                "item_code" => $item->item_code,
                "item_name" => $item->item_name,
                "seiban" => $seiban,
                "location_name" => $locationName,
                "lot_no" => $lotNo,
                "qty" => $logicalStockQty,
            );
    }

}