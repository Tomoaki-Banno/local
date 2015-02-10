<?php

class Delivery_Delivery_AjaxCreditLineCheck extends Base_AjaxBase
{

    function _execute(&$form)
    {
        global $gen_db;

        if (!isset($form['type']) || ($form['type'] != "bulk" && $form['type'] != "barcode"))
            return;

        // 検索条件設定
        switch ($form['type']) {
            case "bulk":
                $length = 3;
                $keyword = "id_";
                $matching = "received_detail_id";
                break;

            case "barcode":
                $length = 7;
                $keyword = "seiban_";
                $matching = "seiban";
                break;

            default:
                $length = 0;
                break;
        }

        // 納品登録データを配列に列挙する
        $arr = array();
        foreach ($form as $name => $value) {
            // 一括 ：received_detail_id
            // バーコード ：seiban
            if (substr($name, 0, $length) == $keyword) {
                // 数量の入力確認
                if (!isset($value) || !is_numeric($value))
                    continue;
                // 一括 ：明細idを取得
                // バーコード ：受注製番を取得
                $key = substr($name, $length, strlen($name) - $length);
                $query = "
                select
                    customer_id
                    ,case when foreign_currency_id is null then product_price else foreign_currency_product_price end as product_price
                from
                    received_detail
                    inner join received_header on received_header.received_header_id = received_detail.received_header_id
                where
                    {$matching} = '{$key}'
                ";
                $res = $gen_db->queryOneRowObject($query);

                // 受注単価から納品金額を算出
                if (isset($res->customer_id) && is_numeric($res->customer_id) && isset($res->product_price) && is_numeric($res->product_price)) {
                    if (array_key_exists($res->customer_id, $arr)) {
                        $arr[$res->customer_id] = Gen_Math::add($arr[$res->customer_id], Gen_Math::mul($res->product_price, $value));
                    } else {
                        $arr[$res->customer_id] = Gen_Math::mul($res->product_price, $value);
                    }
                }
            }
        }

        return
            array(
                'status' => (Logic_Delivery::checkDeliveryCreditLine($arr) ? 'warning' : 'safe'),
            );
    }
}