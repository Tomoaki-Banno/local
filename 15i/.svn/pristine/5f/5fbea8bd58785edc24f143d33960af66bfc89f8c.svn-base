<?php

class Manufacturing_CustomerEdi_AjaxItemParam extends Base_AjaxBase
{

    // item_id, customer_id, quantity を受け取り、得意先販売価格・単位・課税区分を返す
    function _execute(&$form)
    {
        global $gen_db;

        if (!isset($form['itemId']) || !is_numeric(@$form['itemId']))
            return;

        $qty = @$form['qty'];
        if (!Gen_String::isNumeric($qty))
            $qty = null;

        $query = "
        select
            item_name
            ,order_class
            ,selling_price
            ,measure
            ,case tax_class when 1 then '" . _g("非課税") . "' else '" . _g("課税") . "' end as tax_class
            ,customer_master.currency_id as currency_id
        from
            item_master
            inner join customer_price_master on item_master.item_id = customer_price_master.item_id
            inner join customer_master on customer_master.customer_id = customer_price_master.customer_id
        where
            item_master.item_id = '{$form['itemId']}'
            and customer_price_master.customer_id = '{$_SESSION["user_customer_id"]}'
        ";
        $res = $gen_db->queryOneRowObject($query);

        // res
        if (isset($res) && $res != "") {
            $obj = array(
                'status' => "success",
                'item_name' => $res->item_name,
                'order_class' => $res->order_class,
                'selling_price' => $res->selling_price, // 得意先販売価格
                'measure' => $res->measure,
                'tax_class' => $res->tax_class,
            );
        } else {
            $obj['status'] = "error";
        }

        return $obj;
    }

}