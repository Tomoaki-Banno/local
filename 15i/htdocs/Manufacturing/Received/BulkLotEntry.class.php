<?php

class Manufacturing_Received_BulkLotEntry
{
    function convert($converter, &$form)
    {
    }

    function validate($validator, &$form)
    {
        $validator->salesLockDateOrLater("change_date", _g("在庫引当日"));
        $validator->dateString("use_by_limit", _g("対象消費期限が正しくありません。"));
        $validator->numeric("location_id", _g("出庫ロケーションが正しくありません。"));
        
        $form['gen_restore_search_condition'] = 'true';
        return 'action:Manufacturing_Received_BulkLotEdit';        // if error
    }

    function execute(&$form)
    {
        global $gen_db;

        $changeDate = $form['change_date'];
        $useByLimit = $form['use_by_limit'];
        $locationId = $form['location_id'];

        // トランザクション開始
        $gen_db->begin();

        // 指定ロケの品目・製番別在庫データを取得
        Logic_Stock::createTempStockTable(
            $changeDate,
            null,   // item_id
            null,   // seiban
            $locationId,
            "sum",  // lot
            false,  // 有効在庫を取得するか
            false,  // サプライヤー在庫を含めるかどうか
            false   //  use_plan の全期間分差し引くかどうか。有効在庫を取得しないので無関係
            );

       // 引当処理
        foreach ($form as $name=>$value) {
            if (substr($name, 0, 9) == "sc_check_") {
                $id = substr($name, 9, strlen($name)-9);
                if (!Gen_String::isNumeric($id)) continue;

                // 受注情報取得
                $query = "
                    select
                        item_id
                        ,seiban
                        ,coalesce(received_quantity,0) - coalesce(t_sc.qty,0) as remained_qty
                    from
                        received_detail
                        left join
                            (select dist_seiban, sum(quantity) as qty from seiban_change group by dist_seiban) as t_sc
                            on received_detail.seiban = t_sc.dist_seiban
                    where
                        received_detail_id = '$id'
                ";
                if (!($obj = $gen_db->queryOneRowObject($query))) continue;

                // 先入先出（消費期限順）で各ロットからの引当数を決定
                $query = "
                    select
                        seiban
                        ,logical_stock_quantity
                        -- 基本的には消費期限順だが、消費期限なし（製番フリー在庫）を最優先する
                        ,coalesce(use_by,'1970-1-1') as forOrder

                    from
                        temp_stock
                        left join
                            (select stock_seiban, lot_no, use_by, achievement_date as ac_date from achievement
                             union select stock_seiban, lot_no, use_by,  accepted_date as ac_date from accepted) as t_ach_acc
                             on temp_stock.seiban = t_ach_acc.stock_seiban
                     where
                        temp_stock.item_id = '{$obj->item_id}'
                        and logical_stock_quantity > 0
                        /* ロット番号のある（実績とひもついた）製番在庫か、製番フリー在庫を出す。
                           逆に言えば、実績とひもつかない製番在庫（受注製番・計画製番）は出さない。 */
                        and (lot_no is not null or temp_stock.seiban = '')
                        /* 消費期限が対象消費期限より古い品目は引き当てない */
                        and (use_by is null or use_by >= '{$useByLimit}')

                     -- 先入先出（消費期限順）
                     order by
                        forOrder, ac_date
                ";
                if (!($arr = $gen_db->getArray($query))) {
                    continue;
                }

                $left = $obj->remained_qty;     // 未引当数
                foreach($arr as $row) {
                    // このロットからの引当数を決定
                    $qty = $row['logical_stock_quantity'];
                    if ($left < $qty) $qty =  $left;

                    // 製番引当の登録
                    Logic_SeibanChange::entrySeibanChange(
                        null,
                        $changeDate,
                        $obj->item_id,
                        $row['seiban'],
                        $obj->seiban,
                        $locationId,
                        0,  // lot
                        $qty,
                        '自動引当登録');

                    $query = "update temp_stock set logical_stock_quantity = logical_stock_quantity - $qty
                        where item_id = '{$obj->item_id}' and seiban = '{$row['seiban']}'";
                    $gen_db->query($query);

                    $left -= $qty;
                    if ($left <= 0) break;
                }
            }
        }

        $gen_db->commit();

        $form['gen_restore_search_condition'] = 'true';
        return 'action:Manufacturing_Received_List';
    }
}