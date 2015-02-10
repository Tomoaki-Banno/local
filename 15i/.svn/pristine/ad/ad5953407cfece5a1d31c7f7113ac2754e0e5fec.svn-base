<?php

class Manufacturing_Estimate_AjaxItemParam extends Base_AjaxBase
{

    function _execute(&$form)
    {
        global $gen_db;

        if (!isset($form['itemId']) || !is_numeric(@$form['itemId']))
            return;

        $customerId = @$form['customerId'];
        if (!Gen_String::isNumeric($customerId)) {
            $customerId = null;
        }

        $qty = @$form['qty'];
        if (!Gen_String::isNumeric($qty)) {
            $qty = null;
        }

        // パラメータ取得
        $query = "select item_code, item_name, default_selling_price, measure, tax_class from item_master where item_id = '{$form['itemId']}'";
        $res = $gen_db->queryOneRowObject($query);

        // デフォルト単価
        $sale_price = Logic_Received::getSellingPrice($form['itemId'], $customerId, $qty);

        // 原価
        $base_cost = Logic_BaseCost::calcStandardBaseCost($form['itemId'], 1);
        
        // 外貨取引先（請求先）の場合は原価をレート換算する ag.cgi?page=ProjectDocView&pid=1516&did=200642
        if ($customerId) {
            $today = date('Y-m-d');
            // 取引先（請求先がある場合は請求先）の取引通貨とレート、丸め方法を取得する
            $query = "
            select
                t_bill_customer.currency_id
                ,coalesce(t_bill_customer.rounding,'round') as rounding
                ,coalesce(t_bill_customer.precision,0) as precision
                ,coalesce(rate,1) as rate
            from
                customer_master as t_bill_customer
                inner join customer_master on t_bill_customer.customer_id = coalesce(customer_master.bill_customer_id, customer_master.customer_id)
                left join currency_master on t_bill_customer.currency_id = currency_master.currency_id
                /* 本日時点のレートを取得 */
                left join (select currency_id, max(rate_date) as rate_date from rate_master
                    where rate_date <= '{$today}'::date
                    group by currency_id) as t_rate_date
                    on currency_master.currency_id = t_rate_date.currency_id
                left join rate_master on t_rate_date.currency_id = rate_master.currency_id and t_rate_date.rate_date = rate_master.rate_date
            where
                customer_master.customer_id = '{$customerId}'
            ";
            $obj = $gen_db->queryOneRowObject($query);
            $currencyId = $obj->currency_id;
            $rounding = $obj->rounding;
            $precision = $obj->precision;
            $currencyRate = $obj->rate;
            if ($currencyId !== null) {
                // 外貨の場合
                // 基軸通貨に換算（請求先の設定で計算）
                // 小数点以下桁数は、単価は GEN_FOREIGN_CURRENCY_PRECISION、金額は取引先マスタの値
                if ($currencyRate === null || $currencyRate === 0) {
                    $currencyRate = 1;
                }
                $base_cost = Gen_Math::round(Gen_Math::div($base_cost, $currencyRate), $rounding, GEN_FOREIGN_CURRENCY_PRECISION);    // $precisionを使うべきかも？
            }
        }

        // temp_stock に本日時点の有効在庫数（現在庫リストにおける本日付の有効在庫数と一致）を取得
        //  ・製番品目については、フリー製番在庫のみ。
        //  ・全ロケ・ロット合計。Pロケは排除。
        //  ・引当分は将来分も含めて差し引く。
        Logic_Stock::createTempStockTable(date('Y-m-d'), $form['itemId'], '', "sum", "sum", true, false, true);
        $stock = $gen_db->queryOneValue("select COALESCE(available_stock_quantity,0) from temp_stock");

        return
            array(
                'status' => "success",
                'item_code' => $res->item_code,
                'item_name' => $res->item_name,
                'measure' => $res->measure,
                'sale_price' => $sale_price,
                'base_cost' => $base_cost,
                'stock' => (isset($stock) && is_numeric($stock) ? $stock : ''),
                'tax_class' => $res->tax_class,
            );
    }

}