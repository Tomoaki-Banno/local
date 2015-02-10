<?php

class Delivery_Delivery_AjaxCurrencyRateParam extends Base_AjaxBase
{

    function _execute(&$form)
    {
        global $gen_db;

        // 納品日基準か検収日基準か
        $query = "select receivable_report_timing from company_master";
        $timing = $gen_db->queryOneValue($query);

        // 請求先取引通貨取得
        if (!isset($form['customer_id']) || !is_numeric($form['customer_id']))
            return;
        // 得意先から取引通貨idを取得していたが、納品データを請求先の設定で作成するため、
        // 請求先の取引通貨idを取得するよう変更した。
        //// 当初は受注ヘッダーidを元に取引通貨idを取得していたが、得意先から取引通貨idを取得するよう変更した。
        //// 納品は編集時に受注参照のidが消えるため、受注データから取引通貨idが取得できなくなる。
        //// 受注明細idから取引通貨idを取得できるが、明細行削除等の動作に対応しなければならないため、
        //// 取引先マスタから取引通貨を取得することにした。
        //// 同一納品書番号内で異なる得意先の受注明細が選択されることはない。
        $query = "
        select
            t_bill_customer.currency_id
        from
            customer_master as t_bill_customer
            inner join customer_master on t_bill_customer.customer_id = coalesce(customer_master.bill_customer_id,customer_master.customer_id)
        where
            customer_master.customer_id = '{$form['customer_id']}'
        ";
        $currencyId = $gen_db->queryOneValue($query);

        if ($currencyId == '' || $currencyId == null) {
            // 外貨非対応時
            $obj = array(
                'status' => "null",
            );
        } else {
            // 外貨対応時
            // 納品日レート
            if (isset($form['delivery_date']) && Gen_String::isDateString($form['delivery_date'])) {
                $rate = Logic_Delivery::getCurrencyRate($currencyId, $form['delivery_date']);
            }
            if (!isset($rate) || !is_numeric($rate))
                $rate = 1;

            // 検収日レート
            if (isset($form['inspection_date']) && Gen_String::isDateString($form['inspection_date']) && $timing == "1") {
                $insRate = Logic_Delivery::getCurrencyRate($currencyId, $form['inspection_date']);
                if (isset($insRate) && is_numeric($insRate)) {
                    $rate = $insRate;
                } else {
                    $rate = 1;
                }
            }

            $obj = array(
                'status' => "success",
                'foreign_currency_rate' => $rate,
            );
        }

        return $obj;
    }

}