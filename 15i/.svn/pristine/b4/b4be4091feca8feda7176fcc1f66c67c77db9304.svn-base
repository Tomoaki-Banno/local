<?php

class Logic_SeibanChange
{

    //************************************************
    // 製番引当データの登録処理
    //************************************************
    // $stockId の在庫を「同じ品目・同じロケ・指定された製番」の在庫に移動する
    // 第一引数$changeIdは、更新時再登録の場合は指定されているが、新規の場合はnull

    static function entrySeibanChange($changeId, $changeDate, $itemId, $sourceSeiban, $distSeiban, $locationId, $lotId, $quantity, $remarks)
    {
        global $gen_db;

        // トランザクションは呼び出し元で
        //
        //------------------------------------------------------
        //  1. 製番引当（change）登録
        //------------------------------------------------------
        //
        // 再登録の場合、呼び出し元でデータはすでに削除されている。だから常にInsertでよい。
        // ただし$changeIdは、更新時再登録の場合は指定されているが、新規の場合はnull。

        $data = array(
            "change_date" => $changeDate,
            "item_id" => $itemId,
            "location_id" => $locationId,
            "source_seiban" => $sourceSeiban,
            "dist_seiban" => $distSeiban,
            "quantity" => $quantity,
            "remarks" => $remarks,
        );
        if (isset($changeId)) {
            $data['change_id'] = $changeId;
        }

        $gen_db->insert("seiban_change", $data);

        //------------------------------------------------------
        //  2. 入出庫（inout）登録
        //------------------------------------------------------
        //
        // いま登録したIDを確認
        if (!isset($changeId)) {
            $changeId = $gen_db->getSequence("seiban_change_change_id_seq");
        }

        // 品目の在庫評価単価を取得
        $itemPrice = $gen_db->queryOneValue("select stock_price from item_master where item_id = '{$itemId}'");

        // 製番引当元
        Logic_Inout::entryInout(
                $changeDate
                , $itemId
                , $sourceSeiban
                , $locationId
                , ''  // lot_no
                , $quantity
                , $itemPrice
                , "seiban_change_out"
                , "seiban_change_id"
                , $changeId
        );

        // 製番引当先
        Logic_Inout::entryInout(
                $changeDate
                , $itemId
                , $distSeiban
                , $locationId
                , ''  // lot_no
                , $quantity
                , $itemPrice
                , "seiban_change_in"
                , "seiban_change_id"
                , $changeId
        );
    }

    //************************************************
    // 製番引当データの削除処理
    //************************************************

    static function deleteSeibanChange($changeId)
    {
        global $gen_db;

        // トランザクション開始
        $gen_db->begin();

        //------------------------------------------------------
        //  2. 受入（change）削除
        //------------------------------------------------------

        $query = "delete from seiban_change where change_id = '{$changeId}'";
        $gen_db->query($query);

        //------------------------------------------------------
        //  3. 入出庫（inout）削除
        //------------------------------------------------------

        Logic_Inout::deleteSeibanChangeInout($changeId);

        // コミット
        $gen_db->commit();
    }

    //************************************************
    // MRP結果の取り込み（mrp ⇒ seiban_change）
    //************************************************
    // 戻り値：
    //      取り込んだデータのorder_header_idの配列。エラーならfalse

    static function mrpToSeibanChange($fixDate = false)
    {
        global $gen_db;

        //-----------------------------------------------------------
        // トランザクション開始
        //-----------------------------------------------------------

        $gen_db->begin();

        //-----------------------------------------------------------
        // mrpテーブルをロックする。
        //-----------------------------------------------------------
        //  最初のselectを行った後に、他トランからレコードが追加された場合、
        //  後半の「UPDATE mrp SET order_flag = 1」のときに、データ取り込み
        //  されていない追加レコードまでフラグが立ってしまう可能性がある。
        //  分離レベルをSERIALIZABLEにすれば防げるのだが、それだと
        //  競合があった場合にエラー&ROLLBACKになってしまい、その対処が面倒である。
        //  そこでテーブル自体をロックすることで問題を回避している。
        //  データ量が多くなり、取り込みに時間がかかるようになったときの
        //  パフォーマンス（他トランの待ち時間）がやや心配だが・・。

        $query = "LOCK TABLE mrp IN SHARE MODE;";   // SHAREは、INSERT/UPDATEは防ぐがselectは防がないロックモード
        $gen_db->query($query);

        //-----------------------------------------------------------
        // データの読み出し（From mrp）
        //-----------------------------------------------------------

        $query = "
        select
            mrp.item_id
            ,location_id
            ,seiban
            ,arrangement_finish_date
            ,arrangement_quantity
        from
            mrp
            inner join item_master on mrp.item_id = item_master.item_id
        where
            mrp.order_class = '99'   /* means SeibanChange Order */
            -- ダミー品目はオーダーを発行しない
            and not coalesce(item_master.dummy_item, false)
            and coalesce(mrp.order_flag,0) = 0
            and arrangement_quantity <> 0
            " . (!$fixDate ? "" : "and mrp.arrangement_start_date <= '{$fixDate}'::date") . "
        ";

        if (!$arr = $gen_db->getArray($query)) {
            //  このfuncでbeginしたトランザクションはここで終わらせておかないと、
            //  指示書・注文書一括発行において全体がコミットされなくなってしまう
            //  rollbackではダメなことに注意（これ以前の処理も廃棄されてしまうため）
            $gen_db->commit();

            return false;       // データなし
        }

        //-----------------------------------------------------------
        // データの登録
        //-----------------------------------------------------------

        set_time_limit(600);

        foreach ($arr as $mrpData) {
            Logic_SeibanChange::entrySeibanChange(
                    null
                    , $mrpData['arrangement_finish_date']
                    , $mrpData['item_id']
                    , ""
                    , $mrpData['seiban']
                    , $mrpData['location_id']
                    , 0
                    , $mrpData['arrangement_quantity']
                    , "所要量計算による自動製番引当"
            );

            // 戻り値(取り込んだchange_idのリスト)を準備
            $res[] = $gen_db->getSequence("seiban_change_change_id_seq");
        }

        //-----------------------------------------------------------
        // mrpテーブルの取込済フラグを立てる
        //-----------------------------------------------------------

        $query = "
        update
            mrp
        set
            order_flag = 1
        where
            order_class = '99'
            and arrangement_quantity <> 0
            " . (!$fixDate ? "" : "and mrp.arrangement_start_date <= '{$fixDate}'::date") . "
        ";
        $gen_db->query($query);

        //-----------------------------------------------------------
        // コミット
        //-----------------------------------------------------------
        $gen_db->commit();

        return $res;
    }

}