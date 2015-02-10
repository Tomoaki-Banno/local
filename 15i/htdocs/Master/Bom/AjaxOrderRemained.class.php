<?php

class Master_Bom_AjaxOrderRemained extends Base_AjaxBase
{

    function _execute(&$form)
    {
        global $gen_db;

        // 引数
        //  itemId      品目id。この品目の残オーダー番号が返される。

        if (!isset($form['itemId'])) {
            $form['itemId'] = '';
        }

        if (is_numeric($form['itemId'])) {
            // データ取得
            // 注文書の場合はオーダー残に含めない。子品目がないので、警告の必要がないため。
            $query = "
            select
                order_no as data
            from
                order_detail
                inner join order_header on order_detail.order_header_id=order_header.order_header_id
            where
                order_detail_quantity > coalesce(accepted_quantity,0)
                and item_id  = '{$form['itemId']}'
                and classification <> '1'
                and (not order_detail_completed or order_detail_completed is null)
            order by
                order_no
            ";
            $data = $gen_db->getArray($query);
        } else {
            // 品目ID未指定
            return;
        }

        // 結果文字列の準備
        $arr = "";
        if ($data != '') {
            foreach ($data as $row) {
                $arr[] = $row['data'];
            }
        }

        return $arr;
    }

}