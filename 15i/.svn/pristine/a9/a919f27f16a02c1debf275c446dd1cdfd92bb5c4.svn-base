<?php

class Manufacturing_Received_AjaxEstimateDetail extends Base_AjaxBase
{

    function _execute(&$form)
    {
        global $gen_db;

        $obj =
            array(
                "status" => "failure"
            );

        // 数字
        if (!isset($form['estimate_header_id']) || !is_numeric($form['estimate_header_id'])) {
            return $obj;
        }

        // データ取得
        $query = "
        select
            estimate_detail_id
            ,estimate_header.customer_id
            ,customer_master.customer_no
            ,customer_master.customer_name
            ,estimate_header.subject
            ,estimate_header.delivery_place
            ,estimate_header.remarks as remarks_header

            ,estimate_detail.item_id
            ,item_master.item_code
            ,item_master.item_name
            ,estimate_detail.quantity
            ,case when estimate_detail.foreign_currency_id is null then estimate_detail.sale_price else estimate_detail.foreign_currency_sale_price end as sale_price
            ,case when estimate_detail.foreign_currency_id is null then estimate_detail.base_cost else estimate_detail.foreign_currency_base_cost end as base_cost
            ,estimate_detail.remarks

        from
            estimate_header
            inner join estimate_detail on estimate_header.estimate_header_id = estimate_detail.estimate_header_id
            left join customer_master on estimate_header.customer_id = customer_master.customer_id
            left join item_master on estimate_detail.item_id = item_master.item_id

        where
            estimate_header.estimate_header_id = '{$form['estimate_header_id']}'
            and estimate_detail.item_id is not null
        order by
            estimate_detail.line_no
        ";

        $res = $gen_db->getArray($query);
        if (is_array($res)) {
            $line = 1;
            $obj['customer_id'] = $res[0]['customer_id'];
            $obj['customer_no'] = $res[0]['customer_no'];
            $obj['customer_name'] = $res[0]['customer_name'];
            $obj['remarks_header'] = $res[0]['remarks_header'];
            $obj['remarks_header_2'] = $res[0]['subject'];
            $obj['remarks_header_3'] = $res[0]['delivery_place'];
            foreach ($res as $row) {
                $i = $line++;
                $obj[$i] = $i;
                $obj["item_id_{$i}"] = $row['item_id'];
                $obj["item_code_{$i}"] = $row['item_code'];
                $obj["item_name_{$i}"] = $row['item_name'];
                $obj["quantity_{$i}"] = $row['quantity'];
                $obj["sale_price_{$i}"] = $row['sale_price'];
                $obj["base_cost_{$i}"] = $row['base_cost'];
                $obj["remarks_{$i}"] = $row['remarks'];
            }
            $obj['status'] = "success";
        }

        return $obj;
    }

}