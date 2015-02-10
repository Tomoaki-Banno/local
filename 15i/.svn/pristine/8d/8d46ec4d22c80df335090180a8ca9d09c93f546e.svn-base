<?php

class Logic_Accepted
{

    // デッドロック防止のため、各リソースにアクセスする順を統一する
    //   1. order_detail 2. accepted 3. in_out
    //  （登録の際、受入IDを知るために、入出庫より先に受入登録する必要がある。）

    //************************************************
    // 受入データの登録処理
    //************************************************
    // 第一引数$acceptedIdは、更新時再登録の場合は指定されているが、新規の場合はnull

    static function entryAccepted($acceptedId, $orderDetailId, $orderNo, $acceptedDate, $inspectionDate,
            $quantity, $price, $taxRate, $rate, $remarks, $location_id, $lotNo, $paymentDate, $completed, $useBy, $stockSeiban = "")
    {
        global $gen_db;

        // トランザクション開始
        $gen_db->begin();

        // 各種情報
        $query = "
        select
            order_detail.item_id
            ,order_detail.seiban
            ,customer_id
            ,coalesce(customer_master.rounding,'round') as rounding
            ,coalesce(customer_master.precision,0) as precision
            ,foreign_currency_id
            ,foreign_currency_rate
            ,subcontract_order_process_no
            ,order_detail.order_header_id
            ,coalesce(item_master.tax_class,0) as tax_class
            ,item_master.tax_rate
            ,item_master.stock_price
        from
            order_detail
            inner join order_header on order_detail.order_header_id = order_header.order_header_id
            left join customer_master on order_header.partner_id = customer_master.customer_id
            left join item_master on order_detail.item_id = item_master.item_id
        where
            order_detail_id = '{$orderDetailId}'
        ";
        $res = $gen_db->queryOneRowObject($query);
        $itemId = $res->item_id;
        $orderSeiban = $res->seiban;
        $customerId = $res->customer_id;
        $rounding = $res->rounding;
        $precision = $res->precision;
        $currencyId = $res->foreign_currency_id;
        $subcontractOrderProcessNo = ($res->subcontract_order_process_no === null ? '' : $res->subcontract_order_process_no);
        $taxClass = $res->tax_class;
        $itemTaxRate = $res->tax_rate;
        $stockPrice = $res->stock_price;
        
        // 受入日基準か検収日基準か
        $query = "select payment_report_timing from company_master";
        $timing = $gen_db->queryOneValue($query);

        // 在庫製番の決定
        $orderClass = $gen_db->queryOneValue("select order_class from item_master where item_id=(select item_id from order_detail where order_detail_id = '$orderDetailId')");
        if ($orderClass == 2 ) {
            // ロット品目
            //    オーダー製番を引き継がず、あらたに製番を採番する。更新の場合はそれまでの製番を維持する
            if ($stockSeiban === null || $stockSeiban == "") {  // このfunctionの最後の引数が未指定の場合は空文字になる
                $stockSeiban = Logic_Seiban::getSeiban();
            }

            // 消費期限が空欄の場合、製造日 + 品目マスタ「消費期限日数」を設定する
            //   品目マスタ「消費期限日数」が未設定の場合、消費期限を設定しない
            if ($useBy === null || $useBy === '') {
                $useByDays = $gen_db->queryOneValue("select use_by_days from item_master where item_id=(select item_id from order_detail where order_detail_id = '$orderDetailId')");
                if (is_numeric($useByDays)) {
                    $useBy = date('Y-m-d',strtotime($acceptedDate) + (($useByDays) * 86400));
                }
            }
            
            //　ロット番号が空欄の場合、品目マスタの「ロット頭文字」+通番 を設定する
            if ($lotNo === null) {
                $lotNo = "";
            }
            $lotHeader = $gen_db->queryOneValue("select coalesce(item_master.lot_header,'') from item_master where item_id=(select item_id from order_detail where order_detail_id = '$orderDetailId')");
            if (!$lotHeader) {
                $lotHeader = "";
            }
            $lotNo = Logic_NumberTable::getMonthlyAutoNumber($lotHeader, 'lot_no', 5, $lotNo);
        } else {
            // 製番/MRP品目
            //    従来と同じ処理
            $stockSeiban = Logic_Seiban::getStockSeiban($orderSeiban);
        }

        // location_id = -1 のとき、品目マスタの標準ロケ（受入）IDに変換する。
        if ($location_id == -1) {
            $query = "select default_location_id from item_master
                        where item_id = (select item_id from order_detail where order_detail_id = '{$orderDetailId}')";
            $location_id = $gen_db->queryOneValue($query);
            if (!is_numeric($location_id))
                $location_id = 0;
        }
        
        // 品目毎の税率の取得（税率非指定時）
        if (!isset($taxRate) || !is_numeric($taxRate)) {
            if ($taxClass == "1") {
                $taxRate = 0;     // 非課税
            } else {
                $taxRate = $itemTaxRate;
            }
        }
        
        // 品目マスタの税率が未設定の時
        if (!isset($taxRate) || !is_numeric($taxRate)) {
            // 消費税率マスタの税率を取得（仕入基準日の税率を取得）
            $taxRate = Logic_Tax::getTaxRate(isset($inspectionDate) && Gen_String::isDateString($inspectionDate) && $timing == "1" ? $inspectionDate : $acceptedDate);
        }

        //------------------------------------------------------
        //  1. 発注（order_detail）の受入数調整と完了フラグ
        //------------------------------------------------------
        // 受入数・受入残数の調整と完了フラグ
        $isCompleted = Logic_Order::calcAccepted($orderDetailId, $quantity, $completed);

        //------------------------------------------------------
        //  2. 受入（accepted）登録
        //------------------------------------------------------
        // 単価が指定されていない場合、発注単価を使用
        if (!is_numeric($price)) {
            $query = "select case when foreign_currency_id is null then item_price else foreign_currency_item_price end as item_price
                        from order_detail where order_detail_id = '{$orderDetailId}'";
            $price = $gen_db->queryOneValue($query);
        }

        // 金額計算
        //  2010からは、受入金額にもまるめを適用するようになった
        if ($currencyId == null) {
            // 基軸通貨の場合
            $amount = Logic_Customer::round(Gen_Math::mul($quantity, $price), $customerId);
            // 税計算
            $tax = Logic_Customer::round(Gen_Math::mul($amount, Gen_Math::div($taxRate, 100)), $customerId);
        } else {
            // 外貨の場合
            $taxRate = 0;   // ※ 外貨の時は税計算の対象から除外する

            // 取引通貨レートの計算
            if (!isset($rate) || !is_numeric($rate)) {
                // 受入日レート
                $query = "
                select
                    rate
                from
                    rate_master
                    inner join (select currency_id, max(rate_date) as max_date from rate_master where currency_id = '{$currencyId}' and rate_date <= '{$acceptedDate}'::date
                        group by currency_id) as t_date on rate_master.rate_date = t_date.max_date and rate_master.currency_id = t_date.currency_id
                ";
                $rate = $gen_db->queryOneValue($query);
                if (!isset($rate) || !is_numeric($rate)) $rate = 1;

                // 検収日レート
                if (isset($inspectionDate) && Gen_String::isDateString($inspectionDate) && $timing == "1") {
                    $query = "
                    select
                        rate
                    from
                        rate_master
                        inner join (select currency_id, max(rate_date) as max_date from rate_master where currency_id = '{$currencyId}' and rate_date <= '{$inspectionDate}'::date
                            group by currency_id) as t_date on rate_master.rate_date = t_date.max_date and rate_master.currency_id = t_date.currency_id
                    ";
                    $insRate = $gen_db->queryOneValue($query);
                    if (isset($insRate) && is_numeric($insRate)) {
                        $rate = $insRate;
                    } else {
                        $rate = 1;
                    }
                }
            }

            // 外貨のときは常に非課税扱い
            $taxClass = 1;
            $tax = 0;

            // 入力された単価は外貨単価として登録する
            $foreignCurrencyPrice = $price;
            $foreignCurrencyAmount = Logic_Customer::round(Gen_Math::mul($quantity, $price), $customerId);

            // 基軸通貨に換算（単価は取引先マスタの基準に従って丸めない）
            // 小数点以下桁数は、単価は GEN_FOREIGN_CURRENCY_PRECISION、金額は取引先マスタの値
            $price = Gen_Math::round(Gen_Math::mul($rate, $price), $rounding, GEN_FOREIGN_CURRENCY_PRECISION);
            $amount = Logic_Customer::round(Gen_Math::mul($quantity, $price), $customerId);
        }

        // 支払予定日が指定されていない場合は、ここで設定
        if ($paymentDate == '') {
            $paymentDate = self::getPaymentDate($customerId, $acceptedDate, $inspectionDate);
        }

        // 再登録の場合、呼び出し元でデータはすでに削除されている。だから常にInsertでよい。
        // ただし$acceptedIdは、更新時再登録の場合は指定されているが、新規の場合はnull。
        $data = array(
            "order_detail_id" => $orderDetailId,
            "order_no" => $orderNo,
            "lot_no" => $lotNo,
            "payment_report_timing" => $timing,
            "tax_rate" => $taxRate,
            "tax_class" => $taxClass,
            "rounding" => $rounding,
            "precision" => $precision,
            "accepted_date" => $acceptedDate,
            "inspection_date" => ($inspectionDate == '' ? null : $inspectionDate),
            "accepted_quantity" => $quantity,
            "accepted_price" => $price,
            "accepted_amount" => $amount,
            "accepted_tax" => $tax,
            "remarks" => $remarks,
            "order_seiban" => $orderSeiban,
            "stock_seiban" => $stockSeiban,
            "location_id" => $location_id,
            "payment_date" => $paymentDate,
            "use_by" => ($useBy=='' ? null : $useBy),
        );
        if (isset($acceptedId)) {
            $data['accepted_id'] = $acceptedId;
        }
        // 取引通貨処理（基軸通貨以外のとき）
        if ($currencyId != null) {
            $data['foreign_currency_rate'] = $rate;
            $data['foreign_currency_accepted_price'] = $foreignCurrencyPrice;
            $data['foreign_currency_accepted_amount'] = $foreignCurrencyAmount;
        // 取引通貨処理（基軸通貨のとき）
        } else {
            $data['foreign_currency_rate'] = null;
            $data['foreign_currency_accepted_price'] = null;
            $data['foreign_currency_accepted_amount'] = null;
        }

        $gen_db->insert("accepted", $data);

        //------------------------------------------------------
        //  3. 入出庫（inout）登録
        //------------------------------------------------------
        // いま登録した受入IDを確認
        if (!isset($acceptedId)) {
            $acceptedId = $gen_db->getSequence("accepted_accepted_id_seq");
        }

        // 登録
        //   外製工程の場合は入庫登録しない（最終工程のみ、この後で入庫処理を行う）
        if ($subcontractOrderProcessNo == '') {
            Logic_Inout::entryInout(
                    $acceptedDate
                    , $itemId
                    , $stockSeiban
                    , $location_id
                    , ''     // lot_no
                    , $quantity
                    , $stockPrice
                    , "in"
                    , "accepted_id"
                    , $acceptedId
            );
        }

        //------------------------------------------------------
        //  外製工程の場合の完了処理
        //------------------------------------------------------

        if ($subcontractOrderProcessNo != '') {
            $query = "
            select
                order_detail.order_header_id
                ,order_process.order_detail_id
                ,order_process.process_id
            from
                order_process
                left join order_detail on order_process.order_detail_id = order_detail.order_detail_id
            where
                order_process_no = '{$subcontractOrderProcessNo}'
            ";
            $obj = $gen_db->queryOneRowObject($query);
            
            // ----- 工程の完了処理 -----
            
            $data = array("process_completed" => ($isCompleted ? "true" : "false"));
            $where = "order_detail_id = '{$obj->order_detail_id}' and process_id = '{$obj->process_id}'";
            $gen_db->update("order_process", $data, $where);
            
            // ----- 親製造指示の完了処理（最終工程の場合） -----
                    
            // 工程が最終工程かどうかを確認
            $isFinalProcess = Logic_Achievement::isFinalProcess($obj->order_detail_id, $obj->process_id);

            // 親オーダーが同じ未完の外製指示登録が存在しないかを確認
            // 存在が確認されたら、製造指示の完了処理は行わない
            $query = "
            select
                order_detail_id
            from
                order_header
                inner join order_detail on order_header.order_header_id = order_detail.order_header_id
            where
                subcontract_order_process_no = '{$subcontractOrderProcessNo}'
                and classification = 2
                and coalesce(order_detail_completed, 'false') = false
            ";
            if ($gen_db->existRecord($query)) {
                $isCompleted = false;
            }

            if ($isFinalProcess) {
                // 受入完了かつ最終工程の場合、製造指示の完了処理を行う。
                // 実績は登録しないが、入出庫（in_out）レコードに achievement_id を記録しておく必要があるため、
                // ここで新規の achievement_id を取得する。
                $achievementId = $gen_db->queryOneValue("select nextval('achievement_achievement_id_seq')");

                // 受入削除時に入出庫レコードを削除するためのキーとして、いま取得した achievement_id を accepted に記録しておく
                $data = array("subcontract_inout_achievement_id" => $achievementId);
                $where = "accepted_id = '{$acceptedId}'";
                $gen_db->update("accepted", $data, $where);
 
                // 製造指示の完了処理
                Logic_Achievement::finalProcessOperation(
                        $obj->order_header_id
                        , $obj->order_detail_id
                        , $achievementId
                        , $acceptedDate
                        , $itemId
                        , $stockSeiban
                        , $location_id
                        , ''                // lot_no
                        , $quantity
                        , -1                // child_location_id    受入画面では指定できないので、各子品目の標準使用ロケを指定。
                        , ''                // use_lot_no
                        , $isCompleted      // completed　　同じ親オーダーの外製指示登録の未完チェックを反映する。
                        , null
                );
            }
        }

        //------------------------------------------------------
        //  4. 支給子品目の処理
        //------------------------------------------------------
        // 仕様としては・・（すべて発注時点のマスタに基づく）
        //  サプライヤーロケがあるとき：　支給子品目の使用数量をサプライヤー在庫から出庫（use）。
        //  サプライヤーロケがないとき：　支給タイミングが「発注時」ならなにもしない（すでに支給済み）。
        //                              支給タイミングが「受入時」なら使用予定を消し込んで、支給元ロケから出庫（use）。
        // ロジック的には次のように処理している。
        // ※ []内は 外製登録画面の支給方法表示（Partner_Subcontract_AjaxPayoutMode） での番号。
        // 　　このロジックは上記クラスとあわせておく必要がある。
        // ・このオーダーの子品目使用予定があるとき
        //　 （＝発注時点で子品目があって「支給あり」、サプライヤーロケがなく、かつ支給タイミングが「受入時」のとき [5]）：
        //      使用予定を消し込んで、支給元ロケから出庫（use）。
        // ・子品目使用予定がなく、サプライヤーロケへの入庫があるとき：
        //　 （＝発注時点で子品目があって「支給あり」、サプライヤーロケがあるとき [3]）：
        //      サプライヤー在庫から出庫（use）。
        // ・子品目使用予定もサプライヤーロケへの入庫もないとき
        // 　（＝発注時点で子品目がないとき [0]、もしくは「支給なし」のとき [1,2]、
        //      もしくは発注時点で子品目があって「支給あり」、サプライヤーロケがなく、かつ支給タイミングが「発注時」のとき [4]）：
        //      なにもしない。
        // このようなロジックにしたのは、外製指示登録時と受入時のマスタ情報が異なっていた場合の動作矛盾を避けるため。
        // 受入時点のマスタ情報ではなく、発行時点の情報を元に動作するようにしている。
        // 　・支給タイミングは、自社情報ではなく使用予定の有無で判断している。
        // 　・構成は、構成表ではなく発注時点の構成（order_child_item）で判断している。
        // 　・サプライヤーロケの有無は、ロケマスタではなくサプライヤーロケへのPayoutの有無で判断している。
        // 2010i rev.20110812より前は、支給タイミングと構成の変更には対応していたが、サプライヤーロケの変更に対応していなかった。
        // 発注時と受入時のサプライヤーロケの有無が異なっていた場合、矛盾が発生した。
        // 2010i rev.20110812以降では上記の通り、受入時点のロケマスタではなく、サプライヤーロケへの入庫で判断することによりこの問題をクリアした。
        // また、2010i rev.20110812より前は受入時に手配区分が「外製(支給あり)」の品目しか処理しないようになっていたため、
        // 発注後に手配区分を変更した場合に問題が生じることがあったが、2010i rev.20110812以降では手配区分を参照しないようにすることで問題を回避した。
        // さらに標準手配先が変更された場合も問題が発生しないようにした。

        if ($subcontractOrderProcessNo == '') {    // 外製工程の場合は支給の処理をしない。Logic_Order::orderChildItemUpdate() 参照
            $query = "
            select
                order_detail.payout_location_id
                ,order_header.partner_id
                ,order_child_item.child_item_id as item_id
                ,order_child_item.quantity * {$quantity} as quantity
                ,coalesce(payout_price,0) as payout_price
                ,item_master.order_class
                ,t_supplier_payout.supplier_location_id
                ,use_plan.quantity as use_plan_quantity
                ,coalesce(red_payout_price,coalesce(payout_price,0)) as red_payout_price
            from
                order_detail
                inner join order_header on order_detail.order_header_id = order_header.order_header_id
                inner join order_child_item on order_detail.order_detail_id = order_child_item.order_detail_id
                inner join item_master on order_child_item.child_item_id = item_master.item_id
                -- サプライヤーロケへの入庫
                left join (
                    select
                        order_detail_id
                        ,max(location_id) as supplier_location_id
                    from
                        item_in_out
                    where
                        order_detail_id = '{$orderDetailId}'
                        and payout_item_in_out_id is not null
                    group by
                        order_detail_id
                ) as t_supplier_payout on order_detail.order_detail_id = t_supplier_payout.order_detail_id
                -- 子品目使用予定
                left join use_plan
                    on order_detail.order_detail_id = use_plan.order_detail_id
                    and order_child_item.child_item_id = use_plan.item_id
                -- 赤伝処理時の支給単価取得
                left join (
                    select
                        item_id
                        ,item_price as red_payout_price
                    from
                        item_in_out
                    where
                        classification = 'payout'
                        and order_detail_id = '{$orderDetailId}'
                    order by
                        item_in_out_date desc
                        ,item_in_out_id desc
                    offset 0 limit 1
                ) as t_payout on order_child_item.child_item_id = t_payout.item_id
            where
                order_detail.order_detail_id = '{$orderDetailId}'
            ";

            $arr = $gen_db->getArray($query);

            if (is_array($arr)) {
                foreach ($arr as $row) {
                    // 使用予定もサプライヤーロケもないときは処理しない。くわしくはこのfunc冒頭のコメントを参照。
                    if (!is_numeric($row['use_plan_quantity']) && !is_numeric($row['supplier_location_id']))
                        continue;

                    // 使用予定があるなら（支給タイミングが受入時の場合）、使用予定を消し込む。
                    if (is_numeric($row['use_plan_quantity'])) {
                        $data = array("quantity" => ($completed == "true" ? "0" : "noquote:case when quantity < {$row['quantity']} then 0 else COALESCE(quantity,0) - {$row['quantity']} end"));
                        $where = "item_id = '{$row['item_id']}' AND order_detail_id = {$orderDetailId}";

                        $gen_db->update("use_plan", $data, $where);
                    }

                    // 出庫ロケーションの決定
                    // 使用予定があるときは支給元ロケから、ないときはサプライヤーロケから。くわしくはこのfunc冒頭のコメントを参照。
                    $sourceLocationId = (is_numeric($row['use_plan_quantity']) ? $row['payout_location_id'] : $row['supplier_location_id']);

                    //  支給ロケが-1の場合、子品目ごとの標準ロケ（使用）を支給ロケとする
                    if ($sourceLocationId == -1) {
                        $query = "select default_location_id_2 from item_master where item_id= '" . $gen_db->quoteParam($row['item_id']) . "'";
                        $sourceLocationId = $gen_db->queryOneValue($query);
                    }
                    // 支給ロケをコンバート
                    if (!is_numeric($sourceLocationId))
                        $sourceLocationId = 0;

                    // 子品目の引落（出庫登録）。
                    $inoutId = Logic_Inout::entryInout(
                        $acceptedDate
                        , $row['item_id']
                        , ($row['order_class'] == '0' ? $stockSeiban : "")
                        //, ($row['order_class'] == '1' ? "" : $stockSeiban)
                        , $sourceLocationId
                        , '' // lot_no。いまのところ払出ロットの指定はできない
                        , $row['quantity']
                        // 赤伝処理時は支給時の単価を復元する。
                        , ($row['quantity'] < 0 ? $row['red_payout_price'] : $row['payout_price'])
                        // 2010i rev.20110201より前は use だった（そのため、受入時支給の際に違和感があった）
                        // 2010i rev.20110201以降は payout だった（そのため、サプライヤーロケからの引落の際に違和感があった）
                        // 2010i rev.20110805以降は、受入時支給なら payout、サプライヤーロケ引落なら use とした。
                        , ($row['supplier_location_id'] ? "use" : "payout")
                        // アトリビュートには、受入削除時のためのキーとして受入IDを登録しておく。
                        , "accepted_id"
                        , $acceptedId
                    );

                    // 支給画面での支給先名とオーダー番号表示のため、partner_id と order_detail_idを記録しておく
                    $query = "update item_in_out set partner_id = '{$row['partner_id']}', order_detail_id = '{$orderDetailId}'
                                where item_in_out_id = '{$inoutId}'";
                    $gen_db->query($query);
                }
            }
        }

        // コミット
        $gen_db->commit();
    }

    //************************************************
    // 検収日の更新処理
    //************************************************

    static function updateInspectionDate($idArr, $inspectionDate)
    {
        global $gen_db;

        // トランザクション開始
        $gen_db->begin();

        // 各種情報
        $query = "
        select
            accepted.accepted_id
            ,accepted.accepted_date
            ,accepted.accepted_quantity
            ,accepted.foreign_currency_accepted_price
            ,accepted.payment_date
            ,customer_master.customer_id
            ,coalesce(customer_master.rounding,'round') as rounding
            ,order_detail.foreign_currency_id
        from
            accepted
            inner join order_detail on accepted.order_detail_id = order_detail.order_detail_id
            inner join order_header on order_detail.order_header_id = order_header.order_header_id
            left join customer_master on order_header.partner_id = customer_master.customer_id
        where
            accepted_id in (" . join(",", $idArr) . ")
        ";
        $arr = $gen_db->getArray($query);

        if (is_array($arr)) {
            foreach ($arr as $row) {
                // 検収日
                $data = array(
                    "inspection_date" => ($inspectionDate == '' ? null : $inspectionDate),
                );

                // 金額計算（外貨の場合のみ実行）
                if ($row['foreign_currency_id'] != null) {
                    // 受入日基準か検収日基準か
                    $query = "select payment_report_timing from company_master";
                    $timing = $gen_db->queryOneValue($query);

                    // 受入日レート
                    $query = "
                    select
                        rate
                    from
                        rate_master
                        inner join (select currency_id, max(rate_date) as max_date from rate_master where currency_id = '{$row['foreign_currency_id']}' and rate_date <= '{$row['accepted_date']}'::date
                            group by currency_id) as t_date on rate_master.rate_date = t_date.max_date and rate_master.currency_id = t_date.currency_id
                    ";
                    $rate = $gen_db->queryOneValue($query);
                    if (!isset($rate) || !is_numeric($rate)) $rate = 1;

                    // 検収日レート
                    if (isset($inspectionDate) && Gen_String::isDateString($inspectionDate) && $timing == "1") {
                        $query = "
                        select
                            rate
                        from
                            rate_master
                            inner join (select currency_id, max(rate_date) as max_date from rate_master where currency_id = '{$row['foreign_currency_id']}' and rate_date <= '{$inspectionDate}'::date
                                group by currency_id) as t_date on rate_master.rate_date = t_date.max_date and rate_master.currency_id = t_date.currency_id
                        ";
                        $insRate = $gen_db->queryOneValue($query);
                        if (isset($insRate) && is_numeric($insRate)) {
                            $rate = $insRate;
                        } else {
                            $rate = 1;
                        }
                    }

                    // 基軸通貨に換算（単価は取引先マスタの基準に従って丸めない）
                    $price = Gen_Math::round(Gen_Math::mul($rate, $row['foreign_currency_accepted_price']), $row['rounding'], GEN_FOREIGN_CURRENCY_PRECISION);
                    $amount = Logic_Customer::round(Gen_Math::mul($row['accepted_quantity'], $price), $row['customer_id']);

                    // 更新情報
                    $data['foreign_currency_rate'] = $rate;
                    $data['accepted_price'] = $price;
                    $data['accepted_amount'] = $amount;
                }

                // 支払予定日が指定されていない場合は、ここで設定
                if ($row['payment_date'] == '') {
                    $data['payment_date'] = self::getPaymentDate($row['customer_id'], $row['accepted_date'], $inspectionDate);
                }

                // データ更新実行
                $where = "accepted_id = '{$row['accepted_id']}'";
                $gen_db->update("accepted", $data, $where);
            }
        }

        // コミット
        $gen_db->commit();
    }

    //************************************************
    // 受入データの削除処理
    //************************************************

    static function deleteAccepted($acceptedId)
    {
        global $gen_db;

        // トランザクション開始
        $gen_db->begin();

        //------------------------------------------------------
        //  1. 発注（order_detail） の受入数を調整
        //------------------------------------------------------
        // 削除数量を調べる
        $query = "
        select
            order_detail_id
            ,accepted_quantity
            ,subcontract_inout_achievement_id
        from
            accepted
        where
            accepted_id = '{$acceptedId}'
        ";
        $arr = $gen_db->queryOneRowObject($query);
        $orderDetailId = $arr->order_detail_id;
        $quantity = $arr->accepted_quantity;
        $subcontractInoutAchievementId = $arr->subcontract_inout_achievement_id;

        // 受入数・受入残数の調整
        $isCompleted = Logic_Order::calcAccepted($orderDetailId, Gen_Math::mul($quantity, (-1)), 'false');

        //------------------------------------------------------
        //  2. 受入（accepted）削除
        //------------------------------------------------------

        $query = "delete from accepted where accepted_id = '{$acceptedId}'";
        $gen_db->query($query);

        //  オーダー数が0で、なおかつ今回削除する受入データが最後の1件だったときは未完了扱いにする。
        //  上で呼び出しているcalcAccepted()では、このようなパターンのときに完了状態になってしまう（「オーダー数 == 受入数」なので）。
        //  しかしオーダー数0であっても登録直後は受入登録できるわけで、それなのにいったん受入して削除したら受入登録できなくなる
        //  のは不自然。
        $query = "select order_detail_quantity from order_detail where order_detail_id = '{$orderDetailId}'";
        if (($gen_db->queryOneValue($query)) == 0) {
            if (!$gen_db->existRecord("select * from accepted where order_detail_id = '{$orderDetailId}'")) {
                $data = array(
                    "order_detail_completed" => "false",
                );
                $where = "order_detail_id = '{$orderDetailId}'";
                $gen_db->update("order_detail", $data, $where);
            }
        }

        //------------------------------------------------------
        //  3. 入出庫（inout）削除
        //------------------------------------------------------
        // サプライヤーロケの子品目使用分引落があれば、それも同時に削除される

        $query = "select subcontract_order_process_no from order_detail where order_detail_id = '{$orderDetailId}'";
        $subcontractOrderProcessNo = $gen_db->queryOneValue($query);
        $subcontractOrderProcessNo = ($subcontractOrderProcessNo === null ? '' : $subcontractOrderProcessNo);

        // 入出庫削除。
        // 　外製工程受入の場合は入庫登録されていないので削除処理しない
        if ($subcontractOrderProcessNo == '') {
            Logic_Inout::deleteAcceptedInout($acceptedId);
        }

        //------------------------------------------------------
        //  削除する外製指示書が外製工程だった場合の処理
        //------------------------------------------------------
        // 次の条件に合致するときは、製造指示の完了処理の取り消しを行っておく必要がある。
        // ・削除する外製指示書が最終工程である
        // ・この削除によって外製オーダーが未完了になる
        // ・親である製造指示が完了している

        if ($subcontractOrderProcessNo != '') {
            
            $query = "
            select
                order_detail.order_header_id
                ,order_process.order_detail_id
                ,order_process.process_id
            from
                order_process
                left join order_detail on order_process.order_detail_id = order_detail.order_detail_id
            where
                order_process_no = '{$subcontractOrderProcessNo}'
            ";
            $obj = $gen_db->queryOneRowObject($query);
            
            // ----- 工程の完了処理 -----
            
            $data = array("process_completed" => ($isCompleted ? "true" : "false"));
            $where = "order_detail_id = '{$obj->order_detail_id}' and process_id = '{$obj->process_id}'";
            $gen_db->update("order_process", $data, $where);
            
            // ----- 親製造指示の完了処理（最終工程の場合） -----

            // 工程が最終工程かどうかを確認
            $isFinalProcess = Logic_Achievement::isFinalProcess($obj->order_detail_id, $obj->process_id);

            // 受入データ登録内の「受入した外製指示書が最終工程だった場合の処理」の仕様変更に伴い削除処理の仕様も変更する。
            // 受入データ登録時に親製造指示の実績も登録しているため、親製造指示の完了取消や実績削除も行う。
            // （製造指示の完了チェックを削除）
            if ($isFinalProcess) {
                Logic_Achievement::deleteFinalProcessOperation($obj->order_detail_id, $quantity);
                // 上の関数では入出庫の削除が行われないため、ここで行う。
                Logic_Inout::deleteAchievementInout($subcontractInoutAchievementId);
            }
        }

        //-----------------------------------------------
        // 4. 外製子品目使用予定テーブル（use_plan）の消しこみを戻す（再計算）
        //-----------------------------------------------

        if ($subcontractOrderProcessNo == '') {    // 外製工程の場合は支給の処理をしない。Logic_Order::orderChildItemUpdate() 参照
            $query = "
            update
                use_plan
            set
                quantity =
                    (select case when max(case when order_detail_completed then 0 else 1 end)=0 then 0
                            else coalesce(max(order_detail_quantity),0)-coalesce(sum(accepted.accepted_quantity),0) end
                    from order_detail left join accepted on order_detail.order_detail_id = accepted.order_detail_id where order_detail.order_detail_id = '{$orderDetailId}')
                        * (select quantity from order_child_item where order_detail_id = '{$orderDetailId}' and order_child_item.child_item_id = use_plan.item_id)
            where
                order_detail_id = '{$orderDetailId}'
            ";
            $gen_db->query($query);
        }

        // コミット
        $gen_db->commit();
    }

    //************************************************
    // 指定された受入の受入日を返す
    //************************************************
    // Deleteクラスで使用

    static function getAcceptedDate($acceptedId)
    {
        global $gen_db;

        $query = "select accepted_date from accepted where accepted_id = '{$acceptedId}'";
        return $gen_db->queryOneValue($query);
    }

    //************************************************
    // 発注に対してすでに受入が存在するかどうかを返す
    //************************************************
    // order_header_id 版
    static function hasAcceptedByOrderHeaderId($orderHeaderId)
    {
        global $gen_db;

        $query = "
        select
            accepted_id
        from
            accepted
            inner join order_detail on accepted.order_detail_id = order_detail.order_detail_id
        where
            order_header_id = '{$orderHeaderId}'
        ";
        return $gen_db->existRecord($query);
    }

    // order_detail_id 版
    static function hasAcceptedByOrderDetailId($orderDetailId)
    {
        global $gen_db;

        $query = "select accepted_id from accepted where order_detail_id = '{$orderDetailId}'";
        return $gen_db->existRecord($query);
    }

    // 支払予定日の自動取得
    static function getPaymentDate($customerId, $acceptedDate, $inspectionDate)
    {
        global $gen_db;

        // 受入日基準か検収日基準か
        $query = "select payment_report_timing from company_master";
        $timing = $gen_db->queryOneValue($query);
        $date = ($timing == '1' ? $inspectionDate : $acceptedDate);

        if ($date == '') {
            // 検収日基準で、かつまだ検収されていない場合は、支払日はnullとする
            return null;
        } else {
            Logic_Customer::makeCycleDateWithMonthlyLimitDateTable($date, false, $customerId);
            $query = "select cycle_date from temp_cycle_date";
            return $gen_db->queryOneValue($query);
        }
    }

}