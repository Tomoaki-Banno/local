<?php

class Manufacturing_Achievement_AjaxLocationStockAlarm extends Base_AjaxBase
{

    // 実績ID、オーダー番号、出庫ロケーション、親品目製造数量 を受け取り、
    // 子品目使用により在庫がマイナスになる品目がないかどうかを調べる。
    // 警告文を返す（マイナスになる品目がなければ'success:'のみを返す）

    function _execute(&$form)
    {
        global $gen_db;

        // 実績番号（更新のときのみ。新規のときは指定されていない）
        $issetAchId = (isset($form['achievement_id']) && is_numeric($form['achievement_id']));

        // オーダー番号（新規でも更新でも必須）
        if (!isset($form['order_detail_id']) || !is_numeric(@$form['order_detail_id']))
            return;

        // 工程が中間工程（最終工程以外）である場合、実績数を0とみなすようにする。
        // ※中間工程の場合は製造による子品目の引落は行われないが、不適合数による引落は行われる。
        $isFinalProcess = Logic_Achievement::isFinalProcess($form['order_detail_id'], $form['process_id']);
        if (!$isFinalProcess) {
            $form['quantity'] = 0;
        }

        // 不適合数
        $form['quantity'] += $form['waster'];
        
        // 実績数 + 不適合数が0の場合、チェックは行わない
        //  13iまでは上記の状況でもチェックが行われており、もとの理論在庫数がマイナスの場合は
        //  警告が出ていた。
        if ($form['quantity'] == 0) {
            return array(
                'msg' => "",
            );
        }

        // 出庫ロケーション
        if (!is_numeric(@$form['location_id'])) {
            $form['location_id'] = 0;        // ロケ無し
        }

        // 親品目製造数量
        if (!is_numeric($form['quantity'])) {
            return;
        }

        // 在庫製番の決定
        $query = "select seiban from order_detail where order_detail.order_detail_id = '{$form['order_detail_id']}'";

        $orderSeiban = $gen_db->queryOneValue($query);
        $stockSeiban = Logic_Seiban::getStockSeiban($orderSeiban);

        // データの取得
        // 比較用の在庫数（stock_qty）は、単に現在庫ではないことに注意。
        // この実績の既存データがあり、それが今回登録と同一ロケの場合は、
        // その使用数量を現在庫数に足す必要がある（今回の登録で既存分は上書きされるので）
        Logic_Stock::createTempStockTable(null, null, null, ($form['location_id'] == -1 ? null : $form['location_id']), "0", false, true, true);

        $query = "
        select
            order_child_item.child_item_id
            ,item_master.item_code
            ,item_master.item_name
            ,order_child_item.quantity * {$form['quantity']} as use_qty
            ,coalesce(logical_stock_quantity,0) + " . ($issetAchId ? "coalesce(t_use.use_qty, " : "") . "coalesce((order_child_item.quantity * achievement_quantity),0)" . ($issetAchId ? ")" : "") . " as stock_qty
            ,coalesce(logical_stock_quantity,0) as logical_stock_quantity
            ,case when item_master.order_class='0' then '{$stockSeiban}' else '(" . _g("なし") . ")' end as child_seiban
            ,default_location_id_2
            ,temp_stock.location_id
            ,coalesce(location_name, '" . _g(GEN_DEFAULT_LOCATION_NAME) . "') as location_name
        from
            order_detail
            inner join order_child_item on order_detail.order_detail_id = order_child_item.order_detail_id
            left join item_master on order_child_item.child_item_id = item_master.item_id
            left join temp_stock on order_child_item.child_item_id = temp_stock.item_id
                and temp_stock.location_id = " . ($form['location_id'] == -1 ? "default_location_id_2" : "'" . $form['location_id'] . "'") . "
                and temp_stock.seiban = (case when item_master.order_class='0' then '{$stockSeiban}' else '' end)
                and temp_stock.lot_id = 0
            left join achievement on achievement_id = " . ($issetAchId ? "'{$form['achievement_id']}'" : "null") . " and achievement.child_location_id = '{$form['location_id']}'
            left join location_master on location_master.location_id = " . ($form['location_id'] == -1 ? "default_location_id_2" : "'" . $form['location_id'] . "'") . "
            " . ($issetAchId ? 
                " left join (select item_id, sum(item_in_out_quantity) as use_qty from item_in_out 
                    where item_in_out.achievement_id = " . ($issetAchId ? "'{$form['achievement_id']}'" : "null") . " and item_in_out.classification = 'use'
                    group by item_in_out.item_id
                    ) as t_use
                    on order_child_item.child_item_id = t_use.item_id"
            : "") . "
        where
            order_detail.order_detail_id = '{$form['order_detail_id']}'
        ";
        $res = $gen_db->getArray($query);

        $msg = "";
        if ($stockSeiban == '')
            $stockSeiban = _g("(なし)");
        if (is_array($res)) {
            foreach ($res as $row) {
                $useQty = $row['use_qty'];
                // 実績登録画面で子品目使用数量が指定されていた場合はそちらを優先
                if (isset($form['use_' . $row['child_item_id']]) && Gen_String::isNumeric($form['use_' . $row['child_item_id']])) {
                    $useQty = $form['use_' . $row['child_item_id']];
                }
                if ($useQty > $row['stock_qty']) {
                    $msg .= _g("品目") . _g("：") . $row['item_code'] . "(" . $row['item_name'] . ")" .
                            " " . _g("ロケ") . _g("：") . $row['location_name'] .
                            " " . _g("在庫製番") . _g("：") . $row['child_seiban'] .
                            " " . _g("現在庫数") . _g("：") . $row['stock_qty'] .
                            " " . _g("今回使用数") . _g("：") . $useQty . " \n";
                }
            }
        }

        return
            $obj = array(
                'msg' => $msg,
            );
    }

}