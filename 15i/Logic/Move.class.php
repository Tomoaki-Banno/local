<?php

class Logic_Move
{

    //************************************************
    // 移動データの登録処理
    //************************************************
    // 第一引数$moveIdは、更新時再登録の場合は指定されているが、新規の場合はnull

    static function entryMove($moveId, $moveDate, $itemId, $seiban, $sourceLocationId, $distLocationId, $lot_id, $quantity, $orderDetailId, $remarks)
    {
        global $gen_db;

        // トランザクションは呼び出し元で
        //
        //------------------------------------------------------
        //  1. 移動（move）登録
        //------------------------------------------------------
        //
        // 再登録の場合、呼び出し元でデータはすでに削除されている。だから常にInsertでよい。
        // ただし$moveIdは、更新時再登録の場合は指定されているが、新規の場合はnull。

        $data = array(
            "move_date" => $moveDate,
            "item_id" => $itemId,
            "seiban" => $seiban,
            "source_location_id" => $sourceLocationId,
            "dist_location_id" => $distLocationId,
            "quantity" => $quantity,
            "order_detail_id" => (isset($orderDetailId) && is_numeric($orderDetailId) ? $orderDetailId : null),
            "remarks" => $remarks,
        );
        if (isset($moveId)) {
            $data['move_id'] = $moveId;
        }

        $gen_db->insert("location_move", $data);


        //------------------------------------------------------
        //  2. 入出庫（inout）登録
        //------------------------------------------------------
        //
        // いま登録したIDを確認
        if (!isset($moveId)) {
            $moveId = $gen_db->getSequence("location_move_move_id_seq");
        }

        // 品目の在庫評価単価を取得
        $itemPrice = $gen_db->queryOneValue("select stock_price from item_master where item_id = '{$itemId}'");

        // 登録
        //   第3引数は製番。いまのところ、常に製番なしの在庫が対象（製番在庫は移動できない）
        //
        // 移動元
        Logic_Inout::entryInout(
                $moveDate
                , $itemId
                , $seiban
                , $sourceLocationId
                , ''  // lot_no
                , $quantity
                , $itemPrice
                , "move_out"
                , "move_id"
                , $moveId
        );

        // 移動先
        Logic_Inout::entryInout(
                $moveDate
                , $itemId
                , $seiban
                , $distLocationId
                , ''  // lot_no
                , $quantity
                , $itemPrice
                , "move_in"
                , "move_id"
                , $moveId
        );

        return $moveId;
    }

    //************************************************
    // 移動データの削除処理
    //************************************************

    static function deleteMove($moveId)
    {
        global $gen_db;

        // トランザクション開始
        $gen_db->begin();

        //------------------------------------------------------
        //  1. 受入（move）削除
        //------------------------------------------------------

        $query = "delete from location_move where move_id = '{$moveId}'";
        $gen_db->query($query);

        //------------------------------------------------------
        //  2. 入出庫（inout）削除
        //------------------------------------------------------

        Logic_Inout::deleteMoveInout($moveId);

        // コミット
        $gen_db->commit();
    }

    //************************************************
    // 印刷済みフラグのセット
    //************************************************

    static function setMovePrintedFlag($idArr, $isSet)
    {
        global $gen_db;

        $idWhere = join(",", $idArr);
        if ($idWhere == "")
            return;

        $query = "
        update
            location_move
        set
            move_printed_flag = " . ($isSet ? 'true' : 'false') . "
            ,record_updater = '" . $_SESSION['user_name'] . "'
            ,record_update_date = '" . date('Y-m-d H:i:s') . "'
            ,record_update_func = '" . __CLASS__ . "::" . __FUNCTION__ . "'
        where
            move_id in ({$idWhere})
        ";

        $gen_db->query($query);
    }

}