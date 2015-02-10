<?php

class Manufacturing_Order_AjaxItemParam extends Base_AjaxBase
{

    function _execute(&$form)
    {
        global $gen_db;

        if (!isset($form['itemId']) || !is_numeric(@$form['itemId']))
            return;

        $query = "select order_class from item_master where item_master.item_id = '{$form['itemId']}'";
        $res = $gen_db->queryOneRowObject($query);

        if (!$res)
            return;

        return
            array(
                'order_class' => $res->order_class,
            );
    }

}