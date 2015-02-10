<?php

class Manufacturing_Received_AjaxItemParam extends Base_AjaxBase
{

    // item_id、received_detail_id（null可） を受け取り、標準販売単価・単位・引当可能数・課税区分を返す
    function _execute(&$form)
    {
        global $gen_db;

        if (!isset($form['itemId']) || !is_numeric(@$form['itemId']))
            return;

        $existCustomerId = false;
        $customerId = null;
        if (isset($form['customerId']) && is_numeric($form['customerId'])) {
            $existCustomerId = true;
            $customerId = $form['customerId'];
        }

        $qty = @$form['qty'];
        if (!Gen_String::isNumeric($qty))
            $qty = null;

        $query = "
        select
            item_name
            ,order_class
            ,default_selling_price  -- 標準販売単価1
            ,measure
            ,case tax_class when 1 then '" . _g("非課税") . "' else '" . _g("課税") . "' end as tax_class
            ," . ($existCustomerId ? "customer_master.currency_id" : "null") . " as currency_id

        from
            item_master
            " . ($existCustomerId ? " left join customer_master on customer_master.customer_id = '{$customerId}'" : "") . "
        where
            item_master.item_id = '{$form['itemId']}'
        ";
        $res = $gen_db->queryOneRowObject($query);

        // デフォルト受注単価
        $productPrice = Logic_Received::getSellingPrice($form['itemId'], $customerId, $qty);

        // 在庫引当可能数
        $reservable = Logic_Reserve::calcReservableQuantity($form['itemId'], @$form['received_detail_id'], "");

        // 標準原価
        $baseCost = floatval(Logic_BaseCost::calcStandardBaseCost($form['itemId'], 1));
        if ($res->currency_id !== null) {
            $query = "
            select
                coalesce(rate,1) as rate
            from
                rate_master
                inner join (
                    select
                        currency_id
                        ,max(rate_date) as rate_date
                    from
                        rate_master
                    where
                        rate_date <= '" . date('Y-m-d') . "'
                        and currency_id = '{$res->currency_id}'
                    group by
                        currency_id
                    ) as t_rate_date
                    on rate_master.currency_id = t_rate_date.currency_id
                    and rate_master.rate_date = t_rate_date.rate_date
            ";
            $rate = $gen_db->queryOneValue($query);
            if ($rate == null)
                $rate = 1;

            // 標準原価を外貨に換算
            if ($existCustomerId) {
                $baseCost = Logic_Customer::round(Gen_Math::div($baseCost, $rate), $customerId);
            } else {
                $baseCost = round(Gen_Math::div($baseCost, $rate), 0);
            }
        }

        return
            array(
                'item_name' => $res->item_name,
                'order_class' => $res->order_class,
                'default_selling_price' => $res->default_selling_price, // 標準販売単価1
                'product_price' => $productPrice, // デフォルト受注単価
                'reservable_quantity' => $reservable,
                'sales_base_cost' => $baseCost,
                'measure' => $res->measure,
                'tax_class' => $res->tax_class,
            );
    }

}