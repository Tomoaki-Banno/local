<?php

class Delivery_Delivery_AjaxReceivedDetail extends Base_AjaxBase
{

    // received_header_id（必須）を受け取り、received_detail_id と seiban、remarks_header を返す。

    function _execute(&$form)
    {
        global $gen_db;

        // 数字
        if (!isset($form['received_header_id']) || !is_numeric($form['received_header_id'])) {
            return
                array(
                    "status" => "failure"
                );
        }

        // データ取得
        $keyCurrency = $gen_db->queryOneValue("select key_currency from company_master");
        $query = "
        select
            remarks_header
            ,remarks_header_2
            ,remarks_header_3
            ,received_detail_id
            ,seiban
            ,case when t_bill_currency.currency_name is null then '{$keyCurrency}' else t_bill_currency.currency_name end as currency_name_show
            ,received_detail.foreign_currency_id as received_currency_id
            ,t_bill_customer.currency_id as bill_currency_id
        from
            received_header
            inner join received_detail on received_header.received_header_id = received_detail.received_header_id
            inner join customer_master on received_header.customer_id = customer_master.customer_id
            left join customer_master as t_bill_customer on coalesce(customer_master.bill_customer_id,customer_master.customer_id) = t_bill_customer.customer_id
            left join currency_master on received_detail.foreign_currency_id = currency_master.currency_id
            left join currency_master as t_bill_currency on t_bill_customer.currency_id = t_bill_currency.currency_id

        where
            received_header.received_header_id = '{$form['received_header_id']}'
            -- 納品済みの受注は取得しない。納品登録の受注コピー機能で、登録時にvalidエラーになるのを防ぐため。
            and (delivery_completed = false or delivery_completed is null)
        order by
            line_no
        ";

        $res = $gen_db->getArray($query);
        $obj = null;
        if (is_array($res)) {
            $line = 1;
            $obj['remarks_header'] = $res[0]['remarks_header'];
            $obj['remarks_header_2'] = $res[0]['remarks_header_2'];
            $obj['remarks_header_3'] = $res[0]['remarks_header_3'];
            $obj['currency_name'] = $res[0]['currency_name_show'];
            foreach ($res as $row) {
                $obj[$line++] = $row['received_detail_id'] . ":" . $row['seiban'];
            }
            $obj['status'] = "success";
            $obj['currency_flag'] = ($res[0]['received_currency_id'] == $res[0]['bill_currency_id'] ? "0" : "1");
        }

        return $obj;
    }

}