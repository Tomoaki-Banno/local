<?php

class Monthly_StockInput_AjaxParamBarcode extends Base_AjaxBase
{

    function _execute(&$form)
    {
        global $gen_db;
        
        if (!isset($form['item_code']) || !isset($form['seiban']) || !isset($form['location_id'])) {
            return;
        }
        
        if (!isset($form['inventory_date']) || !Gen_String::isDateString($form['inventory_date'])) {
            $form['inventory_date'] = date('Y-m-d');
        }
        
        $query = "select item_id, item_name from item_master where item_code = '{$form['item_code']}' and not dummy_item";
        $obj = $gen_db->queryOneRowObject($query);
        if (!$obj) {
            return;
        }
            
        Logic_Stock::createTempStockTable(
            $form['inventory_date']
            , $obj->item_id
            , $form['seiban']
            , $form['location_id']
            , null      // lot_id（実質未使用）
            , false     // 有効在庫を取得しない
            , true      // サプライヤーロケを含める
            , false     // use_planを将来分まで差し引かない（有効在庫を取得しないので無関係）
            , true      // stockDate当日の棚卸を計算から除外する。つまり棚卸がある場合、棚卸前の数値を取得する
        );

        $query = "
            select 
                logical_stock_quantity
                ,inventory_quantity
                ,use_by
                ,lot_no
            from 
                temp_stock
                inner join item_master on temp_stock.item_id = item_master.item_id
                left join inventory on 
                    temp_stock.item_id = inventory.item_id
                    and temp_stock.seiban = inventory.seiban
                    and temp_stock.location_id = inventory.location_id
                    and temp_stock.lot_id = inventory.lot_id
                    and inventory.inventory_date = '{$form['inventory_date']}'
                /* ロット番号/消費期限が表示されるのはロット品目のみ。
                    この制限がないと、製番品目の受入/実績でロット番号/消費期限を登録した場合に、同じ製番のオーダーすべてに同じロット番号/消費期限が
                    表示されてしまうことになる。 */
                left join (select stock_seiban, use_by, lot_no from achievement where stock_seiban <> ''
                     union select stock_seiban, use_by, lot_no from accepted where stock_seiban <> ''
                     ) as t_ach_acc on temp_stock.seiban = t_ach_acc.stock_seiban and temp_stock.seiban <>'' and item_master.order_class = 2
        ";
        $obj2 = $gen_db->queryOneRowObject($query);
        if ($obj2) {
            $logQuantity = $obj2->logical_stock_quantity;
            $invQuantity = $obj2->inventory_quantity;
            $useBy = $obj2->use_by;
            $lotNo = $obj2->lot_no;
        } else {
            $logQuantity = "";
            $invQuantity = "";
            $useBy = "";
            $lotNo = "";
        }

        return
            array(
                'item_name' => $obj->item_name,
                'log_quantity' => $logQuantity,
                'inv_quantity' => $invQuantity,
                'use_by' => $useBy,
                'lot_no' => $lotNo,
            );
    }

}