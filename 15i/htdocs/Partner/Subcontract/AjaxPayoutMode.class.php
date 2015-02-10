<?php

class Partner_Subcontract_AjaxPayoutMode extends Base_AjaxBase
{

    function _execute(&$form)
    {
        global $gen_db;

        if (!isset($form['itemId']) || !is_numeric($form['itemId'])
                || !isset($form['customerId']) || !is_numeric($form['customerId']))
            return;

        // 支給の状態を返す
        // 0: 子品目がないため、支給なし
        // 1: 子品目はあるが、品目マスタに取引先が「標準手配先」もしくは「代替手配先」として登録されていないため、支給なし
        // 2: 子品目はあるが、手配先の手配区分が「外製（支給あり）」ではないため支給なし
        //      ちなみに外製工程の場合、手配区分が「内製」なので支給なしとなる。
        //      かつては、その取引先を手配先としても指定しておき、手配区分を「外製（支給あり）」にすれば支給されていたが、
        //      いまは外製工程は常に支給なし
        // 3: 支給あり。サプライヤーロケあり
        // 4: 支給あり。サプライヤーロケなし。発注時引落
        // 5: 支給あり。サプライヤーロケなし。受入時引落

        $result = 0;
        $locationName = '';

        if (!is_numeric(@$form['orderHeaderId'])) {
            // 新規モード時
            //  外製指示登録ロジック（Logic_Order）にあわせて判定

            $query = "select * from bom_master where item_id = '{$form['itemId']}'";
            if ($gen_db->existRecord($query)) {
                $query = "
                select
                    -- 手配区分 0:発注、1:外注(支給無)、2:外注(支給有)、3：内製
                    item_order_master.partner_class
                    -- サプライヤーロケの有無
                    ,location_master.location_name
                from
                    item_order_master
                    left join location_master on item_order_master.order_user_id = location_master.customer_id
                where
                    item_order_master.item_id = '{$form['itemId']}'
                    and item_order_master.order_user_id = '{$form['customerId']}'
                ";
                $resObj = $gen_db->queryOneRowObject($query);

                if (!$resObj || $resObj == null) {
                    $result = 1;
                } else if ($resObj->partner_class != '2') {
                    $result = 2;
                } else {
                    if ($resObj->location_name != null) {
                        $result = 3;
                        $locationName = $resObj->location_name;
                    } else {
                        // 自社情報 外製支給のタイミング。0:発注時、1:受入時
                        $query = "select payout_timing from company_master";
                        if ($gen_db->queryOneValue($query) == '0') {
                            $result = 4;
                        } else {
                            $result = 5;
                        }
                    }
                }
            }
        } else {
            // 編集モード時
            //  外製受入登録ロジック（Logic_Accepted::entryAccepted()）にあわせて判定。
            //　オーダー後にマスタが変更されてもオーダー時点の情報で処理されるようになっている
            //　ため、ここでもそのように判定する。
            //　詳細は上記ロジック「4. 支給子品目の処理」のコメントを参照。
            //

            $query = "
            select
                -- オーダー時の支給モードを判断するための情報
                t_supplier_payout.supplier_location_id
                ,use_plan.quantity as use_plan_quantity
                ,item_in_out.item_in_out_id

                -- 手配区分 0:発注、1:外注(支給無)、2:外注(支給有)、3：内製
                ,item_order_master.partner_class
                -- サプロケ名
                ,location_master.location_name
            from
                order_detail
                inner join order_header on order_detail.order_header_id = order_header.order_header_id
                left join item_order_master on order_detail.item_id = item_order_master.item_id
                    and item_order_master.order_user_id = '{$form['customerId']}'
                -- サプライヤーロケへの入庫
                left join (
                    select
                        item_in_out.order_detail_id, max(location_id) as supplier_location_id
                    from
                        item_in_out
                        inner join order_detail on item_in_out.order_detail_id = order_detail.order_detail_id
                    where
                        order_detail.order_header_id = '{$form['orderHeaderId']}' and payout_item_in_out_id is not null
                    group by
                        item_in_out.order_detail_id
                    ) as t_supplier_payout
                    on order_detail.order_detail_id = t_supplier_payout.order_detail_id
                left join location_master on t_supplier_payout.supplier_location_id = location_master.location_id
                -- 子品目使用予定
                left join order_child_item on order_detail.order_detail_id = order_child_item.order_detail_id
                left join use_plan
                    on order_detail.order_detail_id = use_plan.order_detail_id
                    and order_child_item.child_item_id = use_plan.item_id
                -- 発注時引落
                left join item_in_out
                     on order_detail.order_detail_id = item_in_out.order_detail_id
                     and item_in_out.classification = 'payout'
                     and order_child_item.child_item_id = item_in_out.item_id
            where
                order_detail.order_header_id = '{$form['orderHeaderId']}'
                and order_detail.item_id = '{$form['itemId']}'
            ";
            $resObj = $gen_db->queryOneRowObject($query);

            if (!$resObj || $resObj == null) {
                // ありえない
            } else if (is_numeric($resObj->use_plan_quantity)) {
                // 子品目使用予定があるとき
                //　 （＝発注時点で子品目があって「支給あり」、サプライヤーロケがなく、かつ支給タイミングが「受入時」のとき）
                $result = 5;
            } else if (is_numeric($resObj->supplier_location_id)) {
                // 子品目使用予定がなく、サプライヤーロケへの入庫があるとき
                //　 （＝発注時点で子品目があって「支給あり」、サプライヤーロケがあるとき）：
                $result = 3;
                $locationName = $resObj->location_name;
            } else if (is_numeric($resObj->item_in_out_id)) {
                // 発注時引落があるとき
                //　 （＝発注時点で子品目があって「支給あり」、サプライヤーロケがなく、かつ支給タイミングが「発注時」のとき））：
                $result = 4;
            } else {
                // 子品目使用予定もサプライヤーロケへの入庫もないとき
                // 　（＝発注時点で子品目がないとき [0]、もしくは「支給なし」か外製工程のとき [1,2]）
                //    ※上記 0,1,2 の区別は、オーダー発行時ではなく現時点のマスタに基づいて行う。
                //      オーダー時に保存された情報だけでは判別できないため。
                //      いずれにせよ支給なしであることにはかわりないので大きな問題はないだろう
                if (!$resObj->partner_class) {
                    $result = 1;
                } else {
                    $result = 2;
                }
            }
        }

        return
            array(
                'result' => $result,
                'location_name' => $locationName,
            );
    }

}