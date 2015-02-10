<?php

class Master_Bom_AjaxSelecter extends Base_AjaxBase
{

    // 左ボックス（登録済み子品目）データを取得
    // 結果文字列はプレーンテキストで、項目ごとにカンマで区切られる。
    // [品目データ1],[品目データ2]・・・
    // ※ 品目データ
    //      品目ID;表示名（「品目コード:品目名(員数)」という形）

    function _execute(&$form)
    {
        global $gen_db;

        // 引数
        //  itemId      親品目id。この品目の子品目が返される。

        if (!isset($form['itemId'])) {
            $form['itemId'] = '';
        }

        if (is_numeric($form['itemId'])) {

            // データ取得
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
                bom_master.item_id = '{$form['itemId']}'
            order by
                seq
                ,item_code
            ";
            $childData = $gen_db->getArray($query);
        } else {
            // 品目ID未指定
            return;
        }

        // 結果文字列の準備
        $arr = array();        
        if ($childData != '') {
            foreach ($childData as $row) {
                $arr[] = array(
                    $row['child_item_id']
                    // 取数モードを実装したが、使用中止になった（tplでセレクタをコメントアウト）。理由はtplのセレクタの箇所を参照
                    , (@$form['inzu_mode'] == "tori" ? round(1 / $row['inzu'], 4) : round($row['inzu'], 4))
                    , $row['item_code']
                    , $row['item_name']
                );
            }
        }

        return $arr;
    }

}