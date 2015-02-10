<?php

class Partner_Accepted_AjaxCurrencyRateParam extends Base_AjaxBase
{

    function _execute(&$form)
    {
        global $gen_db;

        // 受入日基準か検収日基準か
        $query = "select payment_report_timing from company_master";
        $timing = $gen_db->queryOneValue($query);

        // 注文書取引通貨取得
        if (!isset($form['order_detail_id']) || !is_numeric($form['order_detail_id'])) {
            return;
        }
        $query = "select foreign_currency_id from order_detail where order_detail_id = '{$form['order_detail_id']}'";
        $currencyId = $gen_db->queryOneValue($query);

        if ($currencyId == '' || $currencyId == null) {
            // 外貨非対応時
            $obj = array(
                'status' => "null",
            );
        } else {
            // 外貨対応時
            // 受入日レート
            if (isset($form['accepted_date']) && Gen_String::isDateString($form['accepted_date'])) {
                $query = "
                select
                    rate
                from
                    rate_master
                    inner join (select currency_id, max(rate_date) as max_date from rate_master where currency_id = '{$currencyId}'and rate_date <= '{$form['accepted_date']}'::date
                        group by currency_id) as t_date on rate_master.rate_date = t_date.max_date and rate_master.currency_id = t_date.currency_id
                ";
                $rate = $gen_db->queryOneValue($query);
            }
            if (!isset($rate) || !is_numeric($rate)) {
                $rate = 1;
            }

            // 検収日レート
            if (isset($form['inspection_date']) && Gen_String::isDateString($form['inspection_date']) && $timing == "1") {
                $query = "
                select
                    rate
                from
                    rate_master
                    inner join (select currency_id, max(rate_date) as max_date from rate_master where currency_id = '{$currencyId}' and rate_date <= '{$form['inspection_date']}'::date
                        group by currency_id) as t_date on rate_master.rate_date = t_date.max_date and rate_master.currency_id = t_date.currency_id
                ";
                $insRate = $gen_db->queryOneValue($query);
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