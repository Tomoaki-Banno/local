<?php

class Partner_Subcontract_AjaxItemParam extends Base_AjaxBase
{

    // item_idを受け取り、標準仕入単価を返す

    function _execute(&$form)
    {
        global $gen_db;

        if (!isset($form['itemId']) || !is_numeric(@$form['itemId']))
            return;

        $keyCurrency = $gen_db->queryOneValue("select key_currency from company_master");
        if (isset($form['orderUserId']) && is_numeric($form['orderUserId'])) {
            $query = "
            select
                customer_master.currency_id
                ,case when currency_name is null then '{$keyCurrency}' else currency_name end as currency_name
            from
                customer_master
                left join currency_master on customer_master.currency_id = currency_master.currency_id
            where
                customer_master.customer_id ='{$form['orderUserId']}'
            ";
            $currency = $gen_db->queryOneRowObject($query);
            $currencyId = $currency->currency_id;
            $currencyName = $currency->currency_name;
        } else {
            $currencyId = -1;
            $currencyName = $keyCurrency;
        }

        $query = "
        select
            measure
            ,item_name
            ,order_class
            ,coalesce(lead_time,0) + coalesce(safety_lead_time,0) as total_lead_time
            ," . ($currencyId == -1 ? "'-'" : "case when tax_class=1 then '" . _g("非課税") . "' else '" . _g("課税") . "' end") . " as tax_class
            ,case when t_partner.item_id is not null then t_partner.default_order_price else t_default.default_order_price end as default_order_price
            ,case when t_partner.item_id is not null then t_partner.default_order_price_2 else t_default.default_order_price_2 end as default_order_price_2
            ,case when t_partner.item_id is not null then t_partner.default_order_price_3 else t_default.default_order_price_3 end as default_order_price_3
            ,case when t_partner.item_id is not null then t_partner.order_price_limit_qty_1 else t_default.order_price_limit_qty_1 end as order_price_limit_qty_1
            ,case when t_partner.item_id is not null then t_partner.order_price_limit_qty_2 else t_default.order_price_limit_qty_2 end as order_price_limit_qty_2
            ,case when t_partner.item_id is not null then t_partner.multiple_of_order_measure else t_default.multiple_of_order_measure end as multiple_of_order_measure
            ,case when t_partner.item_id is not null then t_partner.default_lot_unit else t_default.default_lot_unit end as default_lot_unit
            ,case when t_partner.item_id is not null then t_partner.default_lot_unit_2 else t_default.default_lot_unit_2 end as default_lot_unit_2
            ,coalesce(case when t_partner.item_id is not null then t_partner.order_measure else t_default.order_measure end,'') as order_measure
            ,case when dummy_item then 1 else 0 end as dummy_item
        from
            item_master
            left join item_order_master as t_default on item_master.item_id = t_default.item_id and t_default.line_number=0
            left join item_order_master as t_partner on item_master.item_id = t_partner.item_id
                and " . (isset($form['orderUserId']) && is_numeric($form['orderUserId']) ? " t_partner.order_user_id = '{$form['orderUserId']}'" : " 1=0 ") . "
        where
            item_master.item_id = '{$form['itemId']}'
        ";
        $res = $gen_db->queryOneRowObject($query);

        if (!$res || $res == null)
            return;

        // デフォルト外注納期を計算する。
        // 本日から (LT + 安全LT)日後、ただし休日を考慮（カレンダーマスタは自社の休業日なので購買納期計算に使うのは微妙な気もするが、所要量計算でもそうしてるし・・）
        $defaultDeadLine = date('Y-m-d', Gen_Date::getDeadLine(time(), $res->total_lead_time));

        // temp_stock に本日時点の有効在庫数（現在庫リストにおける本日付の有効在庫数と一致）を取得
        //  ・製番品目については、フリー製番在庫のみ。
        //  ・全ロケ・ロット合計。Pロケは排除。
        //  ・引当分は将来分も含めて差し引く。
        // ダミー品目は在庫管理しない。
        if ($res->dummy_item == "1") {
            $stock = 0;
        } else {
            Logic_Stock::createTempStockTable(date('Y-m-d'), $form['itemId'], '', "sum", "sum", true, false, true);
            $stock = $gen_db->queryOneValue("select COALESCE(available_stock_quantity,0) from temp_stock");
        }

        return
            array(
                'default_order_price' => $res->default_order_price,
                'default_order_price_2' => $res->default_order_price_2,
                'default_order_price_3' => $res->default_order_price_3,
                'order_price_limit_qty_1' => $res->order_price_limit_qty_1,
                'order_price_limit_qty_2' => $res->order_price_limit_qty_2,
                'measure' => $res->measure,
                'multiple_of_order_measure' => $res->multiple_of_order_measure,
                'default_lot_unit' => $res->default_lot_unit,
                'default_lot_unit_2' => $res->default_lot_unit_2,
                'order_measure' => $res->order_measure,
                'item_name' => $res->item_name,
                'total_lead_time' => $res->total_lead_time,
                'default_dead_line' => $defaultDeadLine,
                'available_stock_quantity' => $stock,
                'tax_class' => $res->tax_class,
                'order_class' => $res->order_class,
                'currency_name' => $currencyName,
            );
    }

}