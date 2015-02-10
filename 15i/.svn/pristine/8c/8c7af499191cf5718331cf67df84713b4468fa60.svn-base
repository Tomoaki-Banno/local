<?php

class Logic_Reserve
{

    // ※ 引当テーブルの用途について
    // 用途1:    受注引当
    //       受注(製番)とフリー在庫のヒモつけ。
    // 用途2:    子品目使用予約
    //       製造指示書発行時の子品目引当（使用予約）。この場合は製番のかわりにorder_idが入る。
    //       ※15iでは 受注したダミー品目の子品目の使用予約も入るようになった。received_detail_id_for_dummy が登録される
    //
    //    上記のとおり､use_plan に用途がことなる2種類のデータが混在している｡
    //    それぞれ別テーブルにしたほうがわかりやすかったかもしれないが､1テーブルに
    //    まとめたことでコーディングが楽になった面もある｡
    //    というのは、上記2つの用途は、いずれも「他の用途に使用されないように確保して
    //    おく」という意味では同じだからである。たとえばMRPにおいては、両者の区別をす
    //    ることなく､use_planに登録されている数量は引当済みとして利用可能数から差
    //    し引いている｡受注画面等の引当可能数の計算においても同じ｡
    //
    // ※ 2005と2006での引当データの違いについて
    //       2005での引当は､受注と在庫のヒモつけだった｡2006では似ているようで実はだいぶ意味合いが変わっている｡
    //       (1) 前述のように､受注と在庫のヒモつけだけでなく、子品目の使用予約の意味でも使用される｡
    //       (2) 2006では在庫が製番在庫とフリー在庫に分かれるが､引当テーブルにはフリー在庫のほうしか登録されない｡
    //           なので引当に受注と在庫の関係がすべて登録されるわけではなくなった｡
    //       (3) 2005での引当は出庫があっても削除する必要がなかったが､2006では削除する必要がある。
    //
    //       上記(3)について。
    //       2005の納品引当(T_T_DELIVERY_ALLOCATED)の場合は参照側で出庫済み数を意識しているため、出庫があっても
    //       レコードを削除する必要がなかった｡たとえば受注フォームや納品フォームのフリー在庫計算では､引当数から
    //       出庫数を差し引いている｡
    //       しかし2006の引当(T_use_plan)は、該当品目の出庫があった場合はその分の引当データを削除する必要がある｡
    //       なぜなら､T_use_planを参照する各所では､その引当が出庫済みかどうかなど意識していないので､レコード
    //       削除しないとその分の在庫がずっと使われないままになってしまうから｡
    //       それで use_planの引当数は、正確には「引当数のうち未納品の分の数」もしくは「今後の納品に使用できる数」で、
    //       本来の引当数とは異なる。本来の引当数（ユーザーが指定した引当数）は、use_plan のフリー在庫引当
    //       （use_plan.QUANTITY）と 納品テーブルのフリー在庫納品済数（T_DELIVERY.FREE_STOCK_QUANTITY）を合計した
    //       値である｡

    //************************************************
    // 受注引当の登録
    //************************************************
    // 指定された受注（製番で指定）のフリー在庫引当数を更新する。
    // 受注登録・納品引当数変更画面で使用。
    //
    // いったん削除してから、あらためて登録する（複数レコードがあるためUPDATEはできない）。
    // 削除の際、納品データがあった場合に納品による引当差し引き分も消えてしまうため、
    // あらためて登録する必要がある。

    static function updateReserveQuantity($receivedDetailId, $itemId, $useDate, $quantity, $doNotCompletedAdjust = false)
    {
        global $gen_db;

        // 完了調整レコードのdelivery_idを取得しておく
        // 複数レコードが存在することは基本的にないはず。例外ケースでも、最初のレコードだけでいい
        $query = "
        select
            completed_adjust_delivery_id
        from
            use_plan
        where
            received_detail_id = '{$receivedDetailId}'
            and item_id = '{$itemId}'
            and not(completed_adjust_delivery_id is null)
        ";
        $adjustDeliveryId = $gen_db->queryOneValue($query);

        // いったん削除
        $query = "delete from use_plan where received_detail_id = '{$receivedDetailId}'";
        $gen_db->query($query);

        // 再登録
        $data = array(
            'received_detail_id' => $receivedDetailId,
            'item_id' => $itemId,
            'use_date' => date('Y-m-d', strtotime($useDate)),
            'quantity' => $quantity,
        );
        $gen_db->insert('use_plan', $data);

        // フリー在庫納品済み分の差し引きレコードも再登録
        // （この登録が必要な理由については、このクラス冒頭のコメントを参照）
        $query = "
        insert into use_plan (
            received_detail_id
            ,item_id
            ,use_date
            ,quantity
            ,record_creator
            ,record_create_date
            ,record_create_func
        )
        select
            '{$receivedDetailId}'
            ,{$itemId}
            ,'" . date('Y-m-d', strtotime($useDate)) . "'
            ,-free_stock_quantity
            ,'" . $_SESSION['user_name'] . "'
            ,'" . date('Y-m-d H:i:s') . "'
            ,'" . __CLASS__ . "::" . __FUNCTION__ . "'
        from
            (select
                delivery_date
                ,free_stock_quantity
            from
                delivery_detail
                inner join delivery_header on delivery_detail.delivery_header_id = delivery_header.delivery_header_id
                inner join received_detail on delivery_detail.received_detail_id = received_detail.received_detail_id
            where
                delivery_detail.received_detail_id = '{$receivedDetailId}'
        ) as T1
        ";
        $gen_db->query($query);

        // 完了調整レコードの再登録
        if (is_numeric($adjustDeliveryId) && !$doNotCompletedAdjust) {
            Logic_Reserve::entryCompletedAdjust($receivedDetailId, $itemId, $adjustDeliveryId, date('Y-m-d', strtotime($useDate)));
        }
    }

    //************************************************
    // 完了調整レコードの登録
    //************************************************
    // 完了扱いのときに、引当解除を行うための調整レコードを入れる。
    //
    // 完了扱いのときは引当を解除しておく必要があるが、引当レコードを削除してしまうと
    // 納品データを削除したときに引当を復活させることができないので、差分のマイナス
    // レコードを入れる形にする。このレコードを削除すること引当復活できるようにするために、
    // このレコードが完了調整であることを示すようにしておく
    // （「完了扱い調整」フィールドに納品IDを記録しておく。）
    //
    // このクラスのupdateReserveQuantityや、Logic_Deliveryで使用。

    static function entryCompletedAdjust($receivedDetailId, $itemId, $deliveryDetailId, $useDate)
    {
        global $gen_db;

        $query = "
        insert into use_plan (
            received_detail_id
            ,item_id
            ,use_date
            ,quantity
            ,completed_adjust_delivery_id
            ,record_creator
            ,record_create_date
            ,record_create_func
        )
        select
            '{$receivedDetailId}'
            ,{$itemId}
            ,'{$useDate}'::date
            ,-1 * SUM(quantity)
            ,{$deliveryDetailId}
            ,'" . $_SESSION['user_name'] . "'
            ,'" . date('Y-m-d H:i:s') . "'
            ,'" . __CLASS__ . "::" . __FUNCTION__ . "'
        from
            use_plan
        where
            received_detail_id = '{$receivedDetailId}'
            and item_id = '{$itemId}'
        ";
        $gen_db->query($query);
    }

    //************************************************
    // 受注引当の削除
    //************************************************

    static function deleteReserve($receivedDetailId)
    {
        global $gen_db;

        $query = "delete from use_plan where received_detail_id = '{$receivedDetailId}'";
        $gen_db->query($query);
    }

    //************************************************
    // 指定された受注の引当数を返す。
    //************************************************
    ////  引当数 = use_plan.quantity（フリー在庫引当数。完了調整分は除く） + フリー在庫納品済数
    ////  ※ 完了調整分：use_planの中で、completed_adjust_delivery_id が記録されているレコード。
    ////      これは納品登録時に「完了」指定された場合に、「未納品だが完了とみなす」分の
    ////      引当を解除する（差し引く）ために登録されたレコード。これはユーザーが指定した
    ////      引当数ではないので、ここで計算に含めてはいけない。
    //
    //  引当数 = use_plan.quantity（フリー在庫引当数） + フリー在庫納品済数
    //  ※ 完了調整分は納品が削除されてもuse_planに残り、過剰な使用予定を生み出すケースが存在した。
    //     そのため、納品削除時にuse_planの完了調整分を解放することにした。
    //     この変更以前より、受注で明示的に引当られた情報は正確に復元することが不可能であった。
    //     もとより、納品完了したのであれば、引当られた数量は納品数と合致するはずである。
    //     よって、引当数に完了調整分も含めることとした。
    //     （各リストの表示でも完了調整分を含めるよう変更した。）
    //
    //  Logic_Delivery::getDeliveryDataと、Manufacturing_Received_Editで使用

    static function getReserveQuantity($receivedDetailId)
    {
        global $gen_db;

        $query = "
        select
            COALESCE(SUM(use_plan.quantity),0) + COALESCE(MAX(T1.delivery_qty),0) as reserve
        from
            use_plan
            left join (
                select
                    received_detail_id
                    ,SUM(free_stock_quantity) as delivery_qty
                from
                    delivery_detail
                group by
                    received_detail_id
                ) as T1 ON use_plan.received_detail_id = T1.received_detail_id
        where
            ((use_plan.received_detail_id is not null
            and use_plan.quantity <> 0)
            or use_plan.quantity is null)
            and use_plan.received_detail_id = '{$receivedDetailId}'
        group by
            use_plan.received_detail_id
        ";

        return Gen_String::nz($gen_db->queryOneValue($query));
    }

    //************************************************
    // 引当可能数を返す
    //************************************************
    // 有効在庫数（現在のフリー在庫数 - 今回製番以外での引当済数） + 今回製番でのフリー納品済数
    //
    //   引当可能数は、フリー在庫（製番なしの在庫）数から、他の製番で引き当てられて
    //   いる数（子品目使用予約も含めて）を引いた数（有効在庫数）。
    //   ただし今回製番ですでに納品がある場合、納品数は在庫数から差し引かれてしまっ
    //   ているため、その分は加算しておく必要がある。
    //   たとえば今回の受注数が50であり、在庫0の状態から50個製造し、その50個を納品
    //   したとする。このとき在庫数は0だが、受注画面でその受注を表示したとき、
    //   引当可能数には0ではなく50と表示しなければならない。
    //
    //   2008で在庫仕様変更により全面的に書き換え。
    //      重要な変更点として、現在庫（最終入出庫時点の在庫）ではなく、本日時点の在庫によって
    //      引当可能数を計算するようにした。つまり、未来の入出庫を反映しないようにした。
    //      （引当横取り問題への対処。Logic_ExecMrp冒頭コメントの「理由3」参照）
    //   また第3引数を追加。指定されているときは、納品済み数はその納品分だけを考慮。空文字のときは全納品を考慮。
    //
    //   2009で製番品目は引当可能数を常に0とするようにした。
    //      従来は、製番品目の受注に対して、フリー製番在庫から受注引当できる仕様だった。
    //      しかしそのような処理を行なった場合、同時に手動での製番引当も行わねばならない。
    //          （製番品目はフリー製番在庫からの納品は行えないので、上記のような処理を行った場合、
    //            どこかで製番引当（フリー製番在庫を製番在庫に振り替え）を行わなければならないのだが、
    //            所要量計算では受注引当済の需要に対する製番引当オーダーは出てこない。）
    //      そのため製番在庫の受注引当はわかりにくい処理となっていた。
    //      そもそも製番品目は在庫を持たないことが前提。それに、フリー在庫があったとしても、
    //      受注引当ではなく製番引当を行うべきである。
    //      したがって、製番品目に対する受注引当は行えないようにした。

    static function calcReservableQuantity($itemId, $receivedDetailId, $deliveryDetailId)
    {
        global $gen_db;

        // ロット品目も製番品目と同じく、受注引当を行わないこととする
        $query = "select order_class from item_master where item_id = '{$itemId}'";
        $orderClass = $gen_db->queryOneValue($query);
        if ($orderClass == '0' || $orderClass == '2') {
            return 0;
        }

        // temp_stock に本日時点の有効在庫数（現在庫リストにおける本日付の有効在庫数と一致）を取得
        //  ・製番品目については、フリー製番在庫のみ。
        //  ・全ロケ・ロット合計。Pロケは排除。
        //  ・引当分は将来分も含めて差し引く。
        Logic_Stock::createTempStockTable(date('Y-m-d'), $itemId, '', "sum", "sum", true, false, true);

        $query = "
        select
             COALESCE(case when available_stock_quantity >= 0 then available_stock_quantity else 0 end, 0)
                + COALESCE(case when use_plan_qty >= 0 then use_plan_qty else 0 end, 0)
                + COALESCE(case when delivery_qty >= 0 then delivery_qty else 0 end, 0)
        from
            temp_stock

             /* 今回受注による引当済数 */
            left join (
                select
                    item_id,
                    SUM(quantity) as use_plan_qty
                from
                    use_plan
                where
                   " . (!is_numeric($receivedDetailId) ? "1=0" : "received_detail_id = '{$receivedDetailId}'") . "
                group by
                    item_id) as t_use_plan
                on temp_stock.item_id = t_use_plan.item_id

            /* 納品済み数 */
            left join (
                select
                    item_id,
                    SUM(free_stock_quantity) as delivery_qty
                from
                    delivery_detail
                    inner join received_detail on delivery_detail.received_detail_id = received_detail.received_detail_id
                where
                    " . (!is_numeric($receivedDetailId) ? "1=0" : "received_detail.received_detail_id = '{$receivedDetailId}'") . "
                    /* deliveryDetailIdが指定されているときはその納品による納品数のみ考慮 */
                    " . (is_numeric($deliveryDetailId) ? "and delivery_detail_id = '$deliveryDetailId'" : "") . "
                group by
                    item_id
                ) as t_delivery
                on temp_stock.item_id = t_delivery.item_id
        where
            temp_stock.seiban = '' or temp_stock.seiban is null
        ";

        return Gen_String::nz($gen_db->queryOneValue($query));
    }

    //************************************************
    // 納品数にもとづく自動受注引当
    //************************************************
    //  自動受注引当（納品登録用）。
    //  フリー在庫の受注引当数を調整する。引当数は常にフリー在庫納品数とイコールになるようにする。
    //  　　※引当概念自体を廃止してもいいのだが所要量計算等の変更が面倒なので・・
    //  Delivery_Entry と Delivery_BulkEntry で使用。
    //  第2引数（$freeStockQuantity）は、今回納品数。

    static function reserveByDeliveryQuantity($receivedDetailId, $deliveryQuantity, $deliveryDate, $doNotCompletedAdjust)
    {
        global $gen_db;

        // 納品済数
        $freeDeliveryDoneQty = Logic_Delivery::getFreeDeliveryQtyByReceivedDetailId($receivedDetailId);
        // 引当済数
        $reserveDoneQty = Logic_Reserve::getReserveQuantity($receivedDetailId);
        // 合計納品数 = 納品済数 + 今回納品数
        $totalFreeDeliveryDoneQty = $freeDeliveryDoneQty + $deliveryQuantity;

        // 引当済数 < 合計納品数 なら、引当不足分を自動引当する
        if ($reserveDoneQty < $totalFreeDeliveryDoneQty) {
            $query = "select received_detail_id, item_id from received_detail where received_detail_id = '{$receivedDetailId}'";
            $obj = $gen_db->queryOneRowObject($query);
            Logic_Reserve::updateReserveQuantity($obj->received_detail_id, $obj->item_id, $deliveryDate, $totalFreeDeliveryDoneQty, $doNotCompletedAdjust);
        }
    }

}