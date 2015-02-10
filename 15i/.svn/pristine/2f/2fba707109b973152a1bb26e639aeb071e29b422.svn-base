<?php

class Logic_Inout
{

    // Classification を 見出しに変換
    static function classificationToTitle($classification, $isPast = false, $isShort = false)
    {
        if (!$isPast) {
            if ($isShort) {
                switch ($classification) {
                    case 'in' : return _g("入庫");
                    case 'out' : return _g("出庫");
                    case 'use' : return _g("使用数リスト");
                    case 'payout' : return _g("支給");
                }
            } else {
                switch ($classification) {
                    case 'in' : return _g("入庫登録");
                    case 'out' : return _g("出庫登録");
                    case 'use' : return _g("使用数リスト");
                    case 'payout' : return _g("支給登録");
                }
            }
        } else {
            if ($isShort) {
                switch ($classification) {
                    case 'in' : return _g("入庫履歴");
                    case 'out' : return _g("出庫履歴");
                    case 'use' : return _g("使用数履歴");
                    case 'payout' : return _g("支給履歴");
                }
            } else {
                switch ($classification) {
                    case 'in' : return _g("入庫履歴登録");
                    case 'out' : return _g("出庫履歴登録");
                    case 'use' : return _g("使用数履歴登録");
                    case 'payout' : return _g("支給履歴登録");
                }
            }
        }
    }

    //************************************************
    // 登録関連
    //************************************************
    //
    // 入出庫の登録
    // Inoutテーブルへの登録はすべてこの関数を通すこと
    // （ただしStock_Inout_Entryでの登録はここを通していないが・・）
    //    戻り値：item_in_out_id
    static function entryInout($inoutDate, $itemId, $seiban, $locationId, $lotNo, $quantity, $itemPrice, $classification, $classIdName, $classId)
    {
        global $gen_db;

        $arr = array(
            'item_in_out_date' => $inoutDate,
            'item_id' => $itemId,
            'seiban' => $seiban,
            'location_id' => $locationId,
            'lot_id' => 0,
            'lot_no' => $lotNo,
            'item_in_out_quantity' => $quantity,
            'item_price' => $itemPrice,
            'classification' => $classification,
            'remarks' => '',
            'without_stock' => 0
        );

        // accepted_id とか achievement_id とか
        if ($classIdName != "") {
            $arr[$classIdName] = $classId;
        }

        $gen_db->insert('item_in_out', $arr);

        $inOutSeq = $gen_db->getSequence("item_in_out_item_in_out_id_seq");

        return $inOutSeq;
    }

    // 子品目使用実績の登録（Logic_Achievement用）
    static function entryAchievementChildItemInout($orderDetailId, $achievementId, $inoutDate, $seiban, $locationId, $lotNo, $quantity, $parentItemId, $childItemUsageArr)
    {
        global $gen_db;

        $query = "
        select
            child_item_id
            ,quantity
            ,order_class
            ,default_location_id_2
        from
            order_child_item
            left join item_master on order_child_item.child_item_id = item_master.item_id
        where
            order_detail_id = '{$orderDetailId}'
        ";

        $res = $gen_db->getArray($query);
        if (is_array($res)) {
            foreach ($res as $row) {
                // location_id = -1 のとき、品目マスタの標準ロケ（使用）IDに変換
                $location_id_entry = $locationId;
                if ($locationId == -1) {
                    $location_id_entry = $row['default_location_id_2'];
                    if (!is_numeric($location_id_entry))
                        $location_id_entry = 0;
                }
                // 使用数量。
                // $childItemUsageArr（実績登録画面で指定された使用数量）に該当品目があるときはそちらを優先。
                // 使用数量が指定されなかったときは製造数量 × 員数で計算。
                if (isset($childItemUsageArr[$row['child_item_id']]) && Gen_String::isNumeric($childItemUsageArr[$row['child_item_id']])) {
                    $itemInOutQuantity = $childItemUsageArr[$row['child_item_id']];
                } else {
                    $itemInOutQuantity = ($row['quantity'] * $quantity);
                }
                
                $data = array(
                    'item_in_out_date' => $inoutDate,
                    'item_id' => $row['child_item_id'],
                    //  ハイブリッドMRPの導入に伴い、子品目がMRPだったときは子品目在庫製番をクリアする処理を追加
                    //  ※ロットが子品目ということはありえないはずだが一応
                    'seiban' => ($row['order_class'] == '1' || $row['order_class'] == '2' ? "" : $seiban),
                    'location_id' => $location_id_entry,
                    'lot_id' => 0,
                    'lot_no' => $lotNo,
                    'item_in_out_quantity' => $itemInOutQuantity,
                    'item_price' => 0,
                    'parent_item_id' => $parentItemId,
                    'classification' => 'use',
                    'remarks' => '',
                    'achievement_id' => $achievementId,
                    'without_stock' => 0
                );
                $gen_db->insert('item_in_out', $data);
            }
        }
    }

    //************************************************
    // 削除関連
    //************************************************
    // 共通func
    static function _inOutDelete($where)
    {
        global $gen_db;

        // 削除処理
        $query = "delete from item_in_out where {$where}";
        $gen_db->query($query);
    }

    // 入出庫(納品)の削除
    //     Logic_Deliveryで使用
    static function deleteDeliveryInout($deliveryId)
    {
        self::_inOutDelete("classification = 'delivery' and delivery_id = {$deliveryId}");
    }

    // 入出庫(受入)の削除
    //     Logic_Acceptedで使用
    static function deleteAcceptedInout($acceptedId)
    {
        self::_inOutDelete("accepted_id = {$acceptedId}");
    }

    // 入出庫(実績)の削除
    //     Logic_Achievementで使用
    static function deleteAchievementInout($achievementId)
    {
        // 親品目（classification = manufacturing）、子品目（classification = use）
        // の両方が削除される。
        self::_inOutDelete("achievement_id = {$achievementId}");
    }

    // 入出庫(移動)の削除
    //     Logic_Moveで使用
    static function deleteMoveInout($moveId)
    {
        self::_inOutDelete("move_id = {$moveId}");
    }

    // 入出庫(製番引当)の削除
    //     Logic_SeibanChangeで使用
    static function deleteSeibanChangeInout($seibanChangeId)
    {
        self::_inOutDelete("seiban_change_id = {$seibanChangeId}");
    }

    // 入出庫(注文による支給出庫とサプライヤーロケ入庫)の削除
    //     Logic_Orderで使用
    static function deletePayoutInout($orderDetailId)
    {
        self::_inOutDelete("order_detail_id = {$orderDetailId}");
    }

    // 入出庫(支給)の削除
    //     Inout/Entryで使用
    static function deletePayoutInoutById($payoutItemInOutId)
    {
        self::_inOutDelete("payout_item_in_out_id = {$payoutItemInOutId}");
    }

}
