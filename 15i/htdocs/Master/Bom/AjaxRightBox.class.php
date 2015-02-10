<?php

class Master_Bom_AjaxRightBox extends Base_AjaxBase
{

    // 右ボックス（登録できる品目）データを取得
    //  取得データは、親品目（parentItemId）自身ではないもの。親がMRPならMRP品目のみ。
    //  品目グループ指定とオフセット指定を考慮。子品目は表示時に除外するのでここでは考慮しない（変更中の可能性があるので）
    //
    // 結果文字列はプレーンテキストで、項目ごとにカンマで区切られる。
    // [品目データ1],[品目データ2]・・・
    // ※ 品目データ
    //      品目ID;表示名（「品目コード:品目名」という形）

    function _execute(&$form)
    {
        global $gen_db;

        // 引数
        //  parentItemId      親品目id。
        //  itemGroupId
        //  searchText
        //  offset

        // データ取得
        if (isset($form['parentItemId']) && is_numeric($form['parentItemId'])) {
            $pItemId = $form['parentItemId'];
        } else {
            // 品目ID未指定
            return;
        }

        $itemGroupId = @$form['itemGroupId'];
        $searchText = trim(@$form['searchText']);
        $offset = @$form['offset'];
        $omitIdList = @$form['omitIdList'];

        $query = "select order_class from item_master where item_id = '{$pItemId}'";
        $orderClass = $gen_db->queryOneValue($query);

        // エスケープ
        //    like では「_」「%」がワイルドカードとして扱われる
        $searchText = urldecode($searchText);
        $searchText = str_replace('%', '\\\\%', str_replace('_', '\\\\_', $searchText));

        $query = "
        select
            item_id
            ,item_code
            ,item_name
        from
            item_master
        where
            item_id <> '{$pItemId}'
            " . (is_numeric($itemGroupId) ? " and (item_group_id = '{$itemGroupId}' or item_group_id_2 = '{$itemGroupId}' or item_group_id_3 = '{$itemGroupId}')" : "") . "
            " . ($searchText != "" ? " and (item_code ilike '%{$searchText}%' or item_name ilike '%{$searchText}%')" : "") . "
            " . ($omitIdList != "" ? " and (item_id not in ({$omitIdList}))" : "") . "
            " . ($orderClass == "1" ? " and order_class='1' " : "") . "
            -- ロット品目は他の品目の子品目とはなれない。
            -- ロット管理は単階層である。つまり実績で使用ロットを指定して在庫を引き落とすということができないので、部材や中間品のロット管理はできない。
            -- 実際に出荷する品目（最終製品）だけをロット品目とする必要がある。
            and order_class <> '2'
        order by
            item_code
            " . (is_numeric($offset) ? " offset {$offset} " : "") . "
        limit " . (GEN_DROPDOWN_PER_PAGE + 1);    // 次ページ存在判定を行うため1レコード余分に取る

        $data = $gen_db->getArray($query);

        // 結果文字列の準備
        $arr = "";
        if ($data != '') {
            foreach ($data as $row) {
                $arr[] = array(
                    $row['item_id']
                    ,$row['item_code']
                    ,$row['item_name']
                );
            }
        }

        return $arr;
    }

}