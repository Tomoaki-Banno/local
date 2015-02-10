<?php

class Master_Bom_AjaxCopy extends Base_AjaxBase
{

    // 結果文字列はプレーンテキストで、品目ごとにカンマで区切られる。
    // 1品目のデータは、登録済みか未登録か(R or L)、品目ID、品目グループID、
    // 表示名（「品目コード:品目名(員数)」という形）がセミコロンでつながれる。

    function _execute(&$form)
    {
        global $gen_db;

        // 引数
        //    parentItemId    親品目id。この品目は除外する。
        //    copyItemId        近似品目id。左ボックス（登録済み品目）の内容はこの品目の子品目とする。

        if (!isset($form['parentItemId'])) {
            $form['parentItemId'] = '';
        }
        if (!isset($form['copyItemId'])) {
            $form['copyItemId'] = '';
        }

        // データ取得
        if (is_numeric($form['parentItemId']) && is_numeric($form['copyItemId'])) {
            $query = "
            select
                child_item_id
                ,item_code
                ,item_name
                ,quantity as inzu
            from
                bom_master
                inner join item_master on bom_master.child_item_id = item_master.item_id
            where
                bom_master.item_id = '{$form['copyItemId']}'
                and bom_master.child_item_id <> '{$form['parentItemId']}'
            order by
                seq
            ";
        } else {
            // 品目ID未指定
            return;
        }

        $res = $gen_db->getArray($query);

        // 結果文字列の準備
        $arr = array();        
        if (is_array($res)) {
            foreach ($res as $row) {
                $arr[] = array(
                    $row['child_item_id']
                    , $row['inzu']
                    , $row['item_code']
                    , $row['item_name']
                );
            }
        }

        return $arr;
    }

}