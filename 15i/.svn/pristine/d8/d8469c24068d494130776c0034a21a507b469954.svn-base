<?php

require_once(COMPONENTS_DIR . "String.class.php");

class Delivery_Delivery_AjaxTaxRateParam extends Base_AjaxBase
{

    function _execute(&$form)
    {
        global $gen_db;

        // 受注明細id
        if (!isset($form['received_detail_id']) || !is_numeric($form['received_detail_id']))
            return 'ajax.tpl';

        // 納品日
        if (!isset($form['delivery_date']) || !Gen_String::isDateString($form['delivery_date']))
            return 'ajax.tpl';

        // 納品日基準か検収日基準か
        $query = "select receivable_report_timing from company_master";
        $timing = $gen_db->queryOneValue($query);

        // 品目マスタから税率取得
        $query = "
        select
            tax_class
            ,tax_rate
            ,t_bill_customer.currency_id
        from
            item_master
            inner join received_detail on item_master.item_id = received_detail.item_id
            inner join received_header on received_detail.received_header_id = received_header.received_header_id
            inner join customer_master on received_header.customer_id = customer_master.customer_id
            inner join customer_master as t_bill_customer on coalesce(customer_master.bill_customer_id,customer_master.customer_id) = t_bill_customer.customer_id
        where
            received_detail_id = {$form['received_detail_id']}
        ";
        $res = $gen_db->queryOneRowObject($query);

        $taxRate = $res->tax_rate;

        // 品目課税区分
        if ($res->tax_class == 1)
            $taxRate = 0;

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
            // 納品日レート
            } else {
                $date = $form['delivery_date'];
            }

            $query = "select tax_rate from tax_rate_master
                inner join (select max(apply_date) as max_date from tax_rate_master where apply_date <= '{$date}'::date) as t_date on tax_rate_master.apply_date = t_date.max_date
            ";
            $taxRate = $gen_db->queryOneValue($query);

            if (!isset($taxRate) || !is_numeric($taxRate))
                $taxRate = 0;
        }
        
        return
            array(
                'status' => "success",
                'tax_rate' => $taxRate,
                'is_currency' => $isCurrency,
            );
    }

}