<?php

class Stock_Inout_AjaxPayoutPrice extends Base_AjaxBase
{

    // item_id を受け取り、支給単価と標準支給ロケを返す

    function _execute(&$form)
    {
        global $gen_db;

        if (!isset($form['itemId']) || !is_numeric(@$form['itemId']))
            return;

        $query = "select payout_price, default_location_id_2 from item_master where item_id = '{$form['itemId']}'";
        $res = $gen_db->queryOneRowObject($query);

        return
            array(
                'item_price' => $res->payout_price,
                'default_location_id' => $res->default_location_id_2,
            );
    }

}