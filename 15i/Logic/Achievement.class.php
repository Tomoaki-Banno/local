<?php

class Logic_Achievement
{

    // デッドロック防止のため、各リソースにアクセスする順を統一する
    //   1. achievement 2. order_detail 3. in_out 4. use_plan

    //************************************************
    // 実績データの登録
    //************************************************
    //   更新の際は、すでに旧データが削除済みであることが前提
    static function entryAchievement($achievementId, $achievementDate, $begin_time, $end_time, $orderDetailId, $quantity, $remarks, $workMinute, $breakMinute, 
            $location_id, $lot_no, $use_lot_no, $child_location_id, $completed, $process_id, $section_id, $worker_id, $equip_id, $wasterQty, 
            $childItemUsageArr, $useBy, $cost1, $cost2, $cost3, $stockSeiban = "")
    {
        global $gen_db;

        // トランザクション開始
        $gen_db->begin();

        // order_header_id, item_idを調べる
        $query = "select order_header_id, item_id from order_detail where order_detail_id = '{$orderDetailId}'";
        $arr = $gen_db->queryOneRowObject($query);
        $orderHeaderId = $arr->order_header_id;
        $itemId = $arr->item_id;

        // オーダー製番の取得
        $orderSeiban = Logic_Seiban::getOrderSeiban($orderDetailId);

        // 在庫製番の決定
        $orderClass = $gen_db->queryOneValue("select order_class from item_master where item_id=(select item_id from order_detail where order_detail_id = '$orderDetailId')");
        if ($orderClass == 2 ) {
            // ロット品目
            //    オーダー製番を引き継がず、あらたに製番を採番する。
            if ($stockSeiban === null) {    // 更新の場合はそれまでの製番を維持する
                $stockSeiban = Logic_Seiban::getSeiban();
            }

            // 消費期限が空欄の場合、製造日 + 品目マスタ「消費期限日数」を設定する
            //   品目マスタ「消費期限日数」が未設定の場合、消費期限を設定しない
            if ($useBy === null || $useBy === '') {
                $useByDays = $gen_db->queryOneValue("select use_by_days from item_master where item_id=(select item_id from order_detail where order_detail_id = '$orderDetailId')");
                if (is_numeric($useByDays)) {
                    $useBy = date('Y-m-d',strtotime($achievementDate) + (($useByDays) * 86400));
                }
            }
            
            //　ロット番号が空欄の場合、品目マスタの「ロット頭文字」+通番 を設定する
            if ($lot_no === null) {
                $lot_no = "";
            }
            $lotHeader = $gen_db->queryOneValue("select coalesce(item_master.lot_header,'') from item_master where item_id=(select item_id from order_detail where order_detail_id = '$orderDetailId')");
            if (!$lotHeader) {
                $lotHeader = "";
            }
            $lot_no = Logic_NumberTable::getMonthlyAutoNumber($lotHeader, 'lot_no', 5, $lot_no);
            
        } else {
            // 製番/MRP品目
            //    従来と同じ処理
            $stockSeiban = Logic_Seiban::getStockSeiban($orderSeiban);
        }

        // 工程が最終工程かどうかを確認
        $isFinalProcess = self::isFinalProcess($orderDetailId, $process_id);

        // 最終工程以外は入庫ロケを「なし」にする。
        if (!$isFinalProcess)
            $location_id = 0;

        // 最終工程以外はロット・使用ロットを「なし」にする。
        if (!$isFinalProcess) {
            $lot_no = '';
            $use_lot_no = '';
        }

        // 最終工程以外は消費期限を空欄にする
        // 　以前は在庫製番も空文字にしていたが、それだと不適合による子品目引落が製番フリーから落ちるという
        // 　問題があるため、15i rev.20140523 より在庫製番はそのままとした。
        // 　中間工程は、不適合以外は在庫に影響しないので、在庫製番を残しても問題ないはず。
        if (!$isFinalProcess) {
            $useBy = '';
        }
        
        // バーコード/一括/クイック登録用： location_id = -1 のとき、品目マスタの標準ロケ（完成）IDに変換する。
        //      ちなみにchild_location_id は-1であっても変換せず、そのままachievementテーブルに登録し、
        //      in_outテーブル登録時に子品目ごとのロケに変換する。
        if ($location_id == -1) {
            $query = "select default_location_id_3 from item_master where item_id = '{$itemId}'";
            $location_id = $gen_db->queryOneValue($query);
            if (!is_numeric($location_id))
                $location_id = 0;
        }

        //------------------------------------------------------
        // 1. 実績データ（achievement）を登録
        //------------------------------------------------------
        // $achievementIdは、更新時再登録の場合は指定されているが、新規の場合はnull
        $data = array(
            "achievement_date" => $achievementDate,
            "begin_time" => ($begin_time == "" || $begin_time == null ? null : $begin_time),
            "end_time" => ($end_time == "" || $end_time == null ? null : $end_time),
            "order_header_id" => $orderHeaderId,
            "order_detail_id" => $orderDetailId,
            "item_id" => $itemId,
            "achievement_quantity" => ($begin_time != "" && $end_time == "" ? 0 : $quantity),
            "product_price" => 0,
            "remarks" => $remarks,
            "order_seiban" => $orderSeiban,
            "stock_seiban" => $stockSeiban,
            "work_minute" => $workMinute,
            "break_minute" => $breakMinute,
            "location_id" => $location_id,
            "child_location_id" => $child_location_id, // -1は各子品目の標準ロケ（使用）を意味する
            "lot_no" => $lot_no,
            "use_lot_no" => $use_lot_no,
            "process_id" => $process_id,
            "middle_process" => ($isFinalProcess ? null : "true"),
            "section_id" => $section_id,
            "worker_id" => $worker_id,
            "equip_id" => $equip_id,
            "cost_1" => $cost1,
            "cost_2" => $cost2,
            "cost_3" => $cost3,
            "use_by" => ($useBy=='' ? null : $useBy),
        );
        if (isset($achievementId)) {
            $data['achievement_id'] = $achievementId;
        }

        $gen_db->insert("achievement", $data);

        // いま登録した実績IDを確認
        if (!isset($achievementId)) {
            $achievementId = $gen_db->getSequence("achievement_achievement_id_seq");
        }

        //------------------------------------------------------
        // 2. 在庫関連の処理。 最終工程のときのみ実行
        //------------------------------------------------------

        if ($isFinalProcess) {
            // 完成、かつ最終工程のときのみ実行
            self::finalProcessOperation(
                    $orderHeaderId
                    , $orderDetailId
                    , $achievementId
                    , $achievementDate
                    , $itemId
                    , $stockSeiban
                    , $location_id
                    , $lot_no
                    , $quantity
                    , $child_location_id
                    , $use_lot_no
                    , $completed
                    , $childItemUsageArr
            );
        }

        // この処理は、中間工程であっても実行する
        //------------------------------------------------------
        // 3. 不適合品の処理（子品目の出庫登録）
        //------------------------------------------------------
        // 不適合品は、親品目の入庫は行わないが、子品目の引き落とし処理のみ行う必要がある。
        // ここでその処理を行う。
        // 不適合の場合は子品目使用予定（use_plan）の消しこみは行わないことに注意。
        // もともと予定していない使用であるため。歩留まり機能が導入されたら再考の必要がありそうだが。
        // 子品目（classification = use）
        if ($wasterQty != 0) {
            // 子品目引落数が指定されている場合は不適合分の引き落としを行わない（引落数を0にする）
            $childItemUsageArrForWaster = array();
            if ($childItemUsageArr) {
                foreach($childItemUsageArr as $key =>$val) {
                    $childItemUsageArrForWaster[$key] = ($val == "" ? "" : 0);
                }
            }
            
            Logic_Inout::entryAchievementChildItemInout(
                    $orderDetailId
                    , $achievementId
                    , $achievementDate
                    , $stockSeiban
                    , $child_location_id
                    , ''
                    , $wasterQty
                    , $itemId
                    , $childItemUsageArrForWaster
            );
        }
        
        //------------------------------------------------------
        // 4. 工程完了の処理
        //------------------------------------------------------
        // 15iで追加。ag.cgi?page=ProjectDocView&pid=1574&did=208745
        // 工程完了しても在庫数や使用予定数への影響はない。単に、次回このオーダーの実績を登録する時のデフォルト表示工程を決めるためのもの
        if (!$isFinalProcess) {
            // 工程の製造数が計画数（前工程の製造数）を上回っていれば自動的にオンとする
            $planQty = Logic_Achievement::getBeforeProcessAchievementQuantity($orderDetailId, $process_id);
            if (!$planQty) {
                $query = "select order_detail_quantity from order_detail where order_detail_id = '{$orderDetailId}'";
                $planQty = $gen_db->queryOneValue($query);
            }
            $query = "select sum(achievement_quantity) from achievement where order_detail_id = '{$orderDetailId}' and process_id = {$process_id}";
            $achQty = $gen_db->queryOneValue($query);
            if ($completed == "true" || ($planQty <= $achQty)) {
                $completedFlag = "true";
            } else {
                $completedFlag = "false";
            }

            $data = array("process_completed" => $completedFlag);
            $where = "order_detail_id = '{$orderDetailId}' and process_id = '{$process_id}'";
            $gen_db->update("order_process", $data, $where);
        }

        //------------------------------------------------------
        // コミット
        //------------------------------------------------------
        // 以前はここで完了扱いレコードの挿入を行っていた。
        // しかし削除時のロジックを変更したことにより不要になった。
        // コミット
        $gen_db->commit();

        return $achievementId;
    }
    
    // 前工程の製造数を取得。
    //  前工程とは、完了した工程のうち、製造日/登録日時が最後のもの。
    //  必ずしも品目マスタの工程の登録順ではない。
    static function getBeforeProcessAchievementQuantity($orderDetailId, $processId)
    {
        global $gen_db;
        
        $query = "
        select
            coalesce(qty_1, qty_2)
        from
            order_process
            /* 工程実績 */
            left join (
                select
                    order_detail_id
                    ,process_id
                    ,sum(achievement_quantity) as qty_1
                    ,max(achievement_date) as max_date_1
                    ,max(record_create_date) as max_create_date_1
                from
                    achievement
                where
                    order_detail_id = '{$orderDetailId}'
                    " . (is_numeric($processId) ? "and process_id <> '{$processId}'" : "") . "
                group by
                    order_detail_id
                    ,process_id
                ) as t21
                on order_process.order_detail_id = t21.order_detail_id
                and order_process.process_id = t21.process_id
            /* 外製工程受入（完了分のみ） */
            left join (
                select
                    subcontract_order_process_no
                    ,sum(accepted.accepted_quantity) as qty_2
                    ,max(accepted.accepted_date) as max_date_2
                    ,max(accepted.record_create_date) as max_create_date_2
                from
                    order_detail
                inner join
                    accepted 
                    on order_detail.order_detail_id = accepted.order_detail_id
                where
                    coalesce(order_detail_completed, false)
                group by
                    subcontract_order_process_no
                ) as t22
                on order_process.order_process_no = t22.subcontract_order_process_no
        where 
            order_process.order_detail_id = '{$orderDetailId}'
            and (coalesce(order_process.process_completed, false) or t22.subcontract_order_process_no is not null)
        order by
            coalesce(max_date_1, max_date_2) desc
            ,coalesce(max_create_date_1, max_create_date_2) desc
        limit 1
        ";
        return $gen_db->queryOneValue($query);
    }

    // 最終工程の処理。
    // 外製指示書の受入登録（外製工程の場合。Logic_Accepted）からも呼ばれることに注意。
    static function finalProcessOperation($orderHeaderId, $orderDetailId, $achievementId, $achievementDate, $itemId, $stockSeiban, $locationId, $lotNo, $quantity, $childLocationId, $useLotNo, $completed, $childItemUsageArr)
    {
        global $gen_db;

        //------------------------------------------------------
        // 製造指示（order_detail）の受入数調整と完了フラグ
        //------------------------------------------------------
        // 受入数・受入残数の調整と完了フラグ
        Logic_Order::calcAccepted($orderDetailId, $quantity, $completed);

        //------------------------------------------------------
        // 親品目・子品目の入出庫（inout）を登録
        //------------------------------------------------------
        // 親品目（classification = manufacturing）
        Logic_Inout::entryInout(
                $achievementDate
                , $itemId
                , $stockSeiban
                , $locationId
                , $lotNo
                , $quantity
                , 0
                , "manufacturing"
                , "achievement_id"
                , $achievementId
        );

        // 子品目（classification = use）
        Logic_Inout::entryAchievementChildItemInout(
                $orderDetailId
                , $achievementId
                , $achievementDate
                , $stockSeiban
                , $childLocationId
                , $useLotNo
                , $quantity
                , $itemId
                , $childItemUsageArr
        );

        //-----------------------------------------------
        // 子品目使用予定テーブル（use_plan）を調整
        //-----------------------------------------------
        
        self::calcUsePlan($orderDetailId);
    }

    //************************************************
    // 実績データの削除
    //************************************************

    static function deleteAchievement($achievementId)
    {
        global $gen_db;

        // 各属性を調べる
        $query = "
        select
            order_detail_id
            ,achievement_quantity
            ,order_header_id
            ,item_id
            ,process_id
        from
            achievement
        where
            achievement_id = '{$achievementId}'
        ";
        $arr = $gen_db->queryOneRowObject($query);
        if (!$arr || $arr == null)
            return;
        $orderDetailId = $arr->order_detail_id;
        $quantity = $arr->achievement_quantity;
        $processId = $arr->process_id;

        // トランザクション開始
        $gen_db->begin();

        //------------------------------------------------------
        // 1. 実績データ（achievement）を削除
        //------------------------------------------------------

        $query = "delete from achievement where achievement_id = '{$achievementId}'";
        $gen_db->query($query);

        // 工程が最終工程かどうかを確認。
        $isFinalProcess = self::isFinalProcess($orderDetailId, $processId);

        //------------------------------------------------------
        // 2. 製造指示と子品目使用予定テーブルの調整
        //------------------------------------------------------
        // 完成かつ最終工程のときのみ
        if ($isFinalProcess) {
            self::deleteFinalProcessOperation($orderDetailId, $quantity);
        }

        //------------------------------------------------------
        // 3. 入出庫（inout）を削除
        //------------------------------------------------------
        // このステップだけは中間工程でも行う必要がある。
        //  不適合品の子品目の出庫登録が存在する可能性があるため。
        //  実績数の親子のInoutも削除されてしまうが問題ない（中間工程なので実績数のInoutは存在しないはず。）
        // 親品目（classification = manufacturing）、子品目（classification = use）
        // の両方が削除される。
        Logic_Inout::deleteAchievementInout($achievementId);

        //------------------------------------------------------
        // 4. 不適合数テーブル（waster_detail）の削除
        //------------------------------------------------------
        $query = "delete from waster_detail where achievement_id = '{$achievementId}'";
        $gen_db->query($query);

        //------------------------------------------------------
        // 5. 工程完了フラグのクリア
        //------------------------------------------------------
        // その工程の実績がすべてなくなるときのみクリアする
        $query = "select achievement_id from achievement where order_detail_id = '{$orderDetailId}' and process_id = '{$processId}'";
        if (!$gen_db->existRecord($query)) {
            $data = array("process_completed" => "false");
            $where = "order_detail_id = '{$orderDetailId}' and process_id = '{$processId}'";
            $gen_db->update("order_process", $data, $where);
        }
        
        
        // コミット
        $gen_db->commit();
    }

    // 最終工程の削除処理。
    // 外製指示書の受入削除登録（外製工程の場合。Logic_Accepted）からも呼ばれることに注意。
    static function deleteFinalProcessOperation($orderDetailId, $quantity)
    {
        global $gen_db;

        //------------------------------------------------------
        // 製造指示（order_detail）の受入数を調整
        //------------------------------------------------------
        // 受入数・受入残数の調整
        Logic_Order::calcAccepted($orderDetailId, Gen_Math::mul($quantity, (-1)), 'false');

        //  オーダー数が0で、なおかつ今回削除する実績データが最後の1件だったときは未完了扱いにする。
        //  上で呼び出しているcalcAccepted()では、このようなパターンのときに完了状態になってしまう（「オーダー数 == 受入数」なので）。
        //  しかしオーダー数0であっても登録直後は受入登録できるわけで、それなのにいったん受入して削除したら受入登録できなくなる
        //  のは不自然。
        $query = "select order_detail_quantity from order_detail where order_detail_id = '{$orderDetailId}'";
        if (($gen_db->queryOneValue($query)) == 0) {
            if (!$gen_db->existRecord("select * from achievement where order_detail_id = '{$orderDetailId}'")) {
                $data = array(
                    "order_detail_completed" => "false",
                );
                $where = "order_detail_id = '{$orderDetailId}'";
                $gen_db->update("order_detail", $data, $where);
            }
        }

        //-----------------------------------------------
        // 子品目使用予定（use_plan）の消しこみを戻す（再計算）
        //-----------------------------------------------
        
        self::calcUsePlan($orderDetailId);
    }

    //************************************************
    // 子品目使用予定（use_plan）を再計算
    //************************************************
    // 実績登録/削除時に、子品目の使用予定を調整するのに使用。
    // 「親品目の製造残数 × 員数」を使用予約数とする。
    // 
    // 実績登録時：
    // 　13iまでは 製造数に応じて use_plan を減らしていた。
    // 　しかし15iで子品目の使用数を個別に指定できるようになったため、この function を使うように変更した。
    // 　各実績登録で子品目をどれだけ使ったかにかかわらず、常に「親品目の製造残数 × 員数」が使用予約数として残る。
    // 　（つまり子品目を余分に使ったとしても、その分が製造残数分の使用予約数を先食いしてしまうことはなく、
    // 　　余分に使用した段階で有効在庫が減る。
    // 　　実際の使用数にもとづいて使用予約数を減らしてしまうと、実際に必要な数より使用予約数が少なくなることになる。）
    // 実績削除時：
    // 　以前は 削除した実績数に応じて use_plan を回復していた。
    // 　しかしその以前の方式だと、製造指示数を上回る数の実績を登録（過製造）し、それを削除した場合に、不正な使用予約数が
    // 　残る不具合があった。（過実績の場合は、実績数に応じて使用予約を減らすのではなく、使用予約数をゼロにしているので
    // 　そのような現象が発生する。）
    // 　そのため現在の方式に変更した。再計算するため、完了扱い調整レコードは必要なくなった。
    
    static function calcUsePlan($orderDetailId)
    {
        global $gen_db;
        
        // 製造指示書なので、order_header_id と item_id でユニークになることに注意。
        // 1つのorderにつき1品目に限定されているため。
        $query = "
        update
            use_plan
        set
            quantity =
                (select
                    -- 以前は製造残数を order_detail_quantity - achievement.achievement_quantity で計算していたが、
                    -- 外製工程機能の導入に伴い、order_detail_quantity - order_detail.accepted_quantity に変更した。
                    case when max(case when order_detail_completed then 0 else 1 end)=0 then 0 else coalesce(max(order_detail_quantity),0)-coalesce(sum(accepted_quantity),0) end
                from
                    order_detail
                where
                    order_detail.order_detail_id = '{$orderDetailId}')
                * (select
                    quantity
                from
                    order_child_item
                where
                    order_detail_id = '{$orderDetailId}' and order_child_item.child_item_id = use_plan.item_id)
        where
            order_detail_id = '{$orderDetailId}'
        ";
        $gen_db->query($query);
    }

    //************************************************
    // 製造指示に対してすでに実績が存在するかどうかを返す
    //************************************************

    static function existAchievement($orderHeaderId)
    {
        global $gen_db;

        $query = "select achievement_id from achievement where order_header_id = {$orderHeaderId}";
        return $gen_db->existRecord($query);
    }

    //************************************************
    // 指定された実績の受入日を返す
    //************************************************
    // Deleteクラスで使用

    static function getAchievementDate($achievementId)
    {
        global $gen_db;

        $query = "select achievement_date from achievement where achievement_id = '{$achievementId}'";
        return $gen_db->queryOneValue($query);
    }

    //************************************************
    // 指定された工程がそのオーダーの最終工程かどうかを判断
    //************************************************

    static function isFinalProcess($orderDetailId, $processId)
    {
        global $gen_db;

        $query = "
        select
            process_id
        from
            order_process
        where
            machining_sequence = (select max(machining_sequence) from order_process where order_detail_id = '{$orderDetailId}')
            and order_detail_id = '{$orderDetailId}'
        ";
        return ($processId == $gen_db->queryOneValue($query));
    }

    //************************************************
    // 指定された実績登録コード（order_process_no）に関するオーダー情報を取得
    //************************************************

    static function getOrderProcessInfo($orderProcessNo)
    {
        global $gen_db;

        $query = "
        select
            order_detail.order_detail_id
            ,order_process.process_id
            ,item_code
            ,item_name
            ,order_detail_quantity
            ,achievement_quantity
            ,classification
            ,process_name
            ,case when order_detail_completed then 'completed' else '' end as completed
            ,coalesce(default_work_minute,0) as default_work_minute
            ,order_header.remarks_header
            ,order_detail.remarks
            ,case when order_process.subcontract_partner_id is null then 0 else 1 end as is_subcontract_process
        from
            order_header
            inner join order_detail on order_header.order_header_id = order_detail.order_header_id
            inner join order_process on order_detail.order_detail_id = order_process.order_detail_id
            left join process_master on order_process.process_id = process_master.process_id
            left join (
                select
                    order_detail_id
                    ,process_id
                    ,SUM(achievement_quantity) as achievement_quantity
                from
                    achievement
                group by
                    order_detail_id, process_id
                ) as T1 on order_process.order_detail_id = T1.order_detail_id
                    and order_process.process_id = T1.process_id
        where
            order_process_no = '{$orderProcessNo}'
            and classification = '0'
        ";
        return $gen_db->queryOneRowObject($query);
    }

    //************************************************
    // 実績登録済みチェック
    //************************************************
    // order_header_id 版
    static function hasAchievementByOrderHeaderId($orderHeaderId)
    {
        global $gen_db;

        $query = "
        select
            achievement_id
        from
            achievement
        where
            order_detail_id in (select order_detail_id from order_detail where order_header_id = '{$orderHeaderId}')
        ";
        return $gen_db->existRecord($query);
    }

    // order_detail_id 版
    static function hasAchievementByOrderDetailId($orderDetailId)
    {
        global $gen_db;

        $query = "select achievement_id from achievement where order_detail_id = '{$orderDetailId}'";
        return $gen_db->existRecord($query);
    }

}