<?php

class Partner_Order_AjaxItemPrice extends Base_AjaxBase
{

    function _execute(&$form)
    {
        global $gen_db;

        $ouid = $form['orderUserId'];
        if (!Gen_String::isNumeric($ouid))
            return;

        $ids = $form['ids'];
        $idArr = explode(',', $ids);
        if (count($idArr) == 0)
            return;
        foreach ($idArr as $id) {
            if (!Gen_String::isNumeric($id))
                return;
        }

        $keyCurrency = $gen_db->queryOneValue("select key_currency from company_master");

        $query = "
        select
            item_master.item_id
            ,case when t_partner.item_id is not null then t_partner.default_order_price else t_default.default_order_price end as default_order_price
            ,case when t_partner.item_id is not null then t_partner.default_order_price_2 else t_default.default_order_price_2 end as default_order_price_2
            ,case when t_partner.item_id is not null then t_partner.default_order_price_3 else t_default.default_order_price_3 end as default_order_price_3
            ,case when t_partner.item_id is not null then t_partner.order_price_limit_qty_1 else t_default.order_price_limit_qty_1 end as order_price_limit_qty_1
            ,case when t_partner.item_id is not null then t_partner.order_price_limit_qty_2 else t_default.order_price_limit_qty_2 end as order_price_limit_qty_2
            ,case when currency_name is null then '{$keyCurrency}' else currency_name end as currency_name

        from
            item_master
            left join item_order_master as t_default on item_master.item_id = t_default.item_id and t_default.line_number=0
            left join item_order_master as t_partner on item_master.item_id = t_partner.item_id
                and t_partner.order_user_id = '{$ouid}'
            left join customer_master on t_partner.order_user_id = customer_master.customer_id
            left join currency_master on customer_master.currency_id = currency_master.currency_id
        where
            item_master.item_id in ({$ids})
        ";

        $res = $gen_db->getArray($query);
        if ($res == false)
            return;

        $resArr = array();
        foreach ($res as $row) {
            $resArr[$row['item_id']]['default_order_price'] = $row['default_order_price'];
            $resArr[$row['item_id']]['default_order_price_2'] = $row['default_order_price_2'];
            $resArr[$row['item_id']]['default_order_price_3'] = $row['default_order_price_3'];
            $resArr[$row['item_id']]['order_price_limit_qty_1'] = $row['order_price_limit_qty_1'];
            $resArr[$row['item_id']]['order_price_limit_qty_2'] = $row['order_price_limit_qty_2'];
            $resArr[$row['item_id']]['currency_name'] = $row['currency_name'];
        }

        return $resArr;
    }

}