<?php

class Partner_SubcontractAccepted_AjaxTaxRateParam extends Base_AjaxBase
{

    function _execute(&$form)
    {
        global $gen_db;

        // オーダー明細id
        if (!isset($form['order_detail_id']) || !is_numeric(@$form['order_detail_id'])) {
            return 'ajax.tpl';
        }

        $keyCurrency = $gen_db->queryOneValue("select key_currency from company_master");

        // 受入日基準か検収日基準か
        $query = "select payment_report_timing from company_master";
        $timing = $gen_db->queryOneValue($query);

        // 品目マスタから税率取得
        $query = "
        select
            item_master.tax_class
            ,item_master.tax_rate
            ,customer_master.currency_id
        from
            item_master
            inner join order_detail on item_master.item_id = order_detail.item_id
            inner join order_header on order_detail.order_header_id = order_header.order_header_id
            inner join customer_master on order_header.partner_id = customer_master.customer_id
        where
            order_detail_id = '{$form['order_detail_id']}'
        ";
        $res = $gen_db->queryOneRowObject($query);

        $taxRate = $res->tax_rate;

        // 品目課税区分
        if ($res->tax_class == 1) {
            $taxRate = 0;
        }

        // 取引先取引通貨設定（取引通貨が設定されている時は税率を指定させない）
        $isCurrency = false;
        if (isset($res->currency_id) && is_numeric($res->currency_id)) {
            $isCurrency = true;
            $taxRate = 0;
        }

        // 消費税率マスタから税率取得
        if (!isset($taxRate) || !is_numeric($taxRate)) {
            // 検収日レート
            if (isset($form['inspection_date']) && Gen_String::isDateString($form['inspection_date']) && $timing == "1") {
                $date = $form['inspection_date'];
            // 受入日レート
            } else {
                $date = $form['accepted_date'];
            }

            $query = "select tax_rate from tax_rate_master
                inner join (select max(apply_date) as max_date from tax_rate_master where apply_date <= '{$date}'::date) as t_date on tax_rate_master.apply_date = t_date.max_date
            ";
            $taxRate = $gen_db->queryOneValue($query);

            if (!isset($taxRate) || !is_numeric($taxRate)) {
                $taxRate = 0;
            }
        }

        return 
            array(
                'status' => "success",
                'tax_rate' => $taxRate,
                'is_currency' => $isCurrency,
            );
    }

}