<?php

class Logic_Order
{

    //************************************************
    // 製造指示書/注文書　親データ（order_header）登録
    //************************************************
    // 引数：
    //      $classification     0:製造指示書、1:注文書、2:外製指示書
    //      $orderHeaderId      新規のときはnullを渡す。更新のときのみ指定する。
    //      $orderIdForUser     注文書番号。製造指示書のときは指定しても無視される（強制的にnullになる）。
    //                          注文書・製造指示書兼注文書のときは、nullなら自動採番される。
    //      $orderDate          オーダー日。必ず指定する。
    //      $partnerId          手配先ID。製造指示書のときは指定しても無視される（強制的に「0」になる）。
    //      $remarksHeader      備考。空文字でもいいがnullはだめ。
    //      $workerId           担当者ID
    //      $sectionId          部門ID
    //      $mrpFlag            MRP取り込みフラグ。trueかfalse。変更しないならnullにする
    //      $deliveryPartnerId	発送先ID
    //
    // 戻り値：
    //      $orderHeaderId      引数で指定したならそのままの値、nullを渡したなら自動採番された値が返る

    static function entryOrderHeader($classification, $orderHeaderId, $orderIdForUser, $orderDate,
            $partnerId, $remarksHeader, $workerId, $sectionId, $deliveryPartnerId, $mrpFlag)
    {
        global $gen_db;

        //-----------------------------------------------------------
        // 値の設定
        //-----------------------------------------------------------

        if ($classification == 0 || !is_numeric($classification)) {
            // 製造指示書（内製）のとき。注文書番号はnull、手配先は内製にする
            $orderIdForUser = null;
            $classification = 0;
            $partnerId = 0;
        } else if ($classification == 2) {
            // 外製指示書のとき。
            // 13iまでは注文書と同じく注文書番号を発行/登録していたが、外製指示書を発行すると
            // 注文書の注文書番号がスキップしてしまうという問題があった。
            $orderIdForUser = null;
        } else {
            // 注文書のとき。
            // 注文書番号（order_id_for_user）が未指定なら採番する。
            //  以前はgetOrderIdForUser()にて select MAX(order_id_for_user) from order_header として採番していたが、
            //  この方法だと複数のトランザクションが同時実行されたときに同じ番号が採番されてしまう可能性がある。
            //  order_headerテーブルを LOCK TABLE table IN ACCESS EXCLUSIVE MODE すればよいが、
            //  パフォーマンス低下やデッドロックが心配。
            //  一方、シーケンスを使用すれば簡単だが、番号を手動で指定された場合の処理が問題。
            //  複数トランザクションが同時にsetvalした場合に競合回避するのが難しい。
            //  また手動指定だと、シーケンスの範囲を超える非常に大きい値が指定されたときの対処が難しい。
            //  そこで、ここでは採番テーブル方式を使用した。
            // 13iまでは数値のみだったが、15iからはオーダー番号等と同じ（「アルファベット1文字」+「年月4桁」+「連番」）
            // に変更した。
            //  $orderIdForUser = Logic_NumberTable::nextval("order_id_for_user", $orderIdForUser);
            $orderIdForUser = Logic_NumberTable::getMonthlyAutoNumber(GEN_PREFIX_PARTNER_ORDER_NUMBER, "order_id_for_user", 5, $orderIdForUser);
        }

        //-----------------------------------------------------------
        // 登録処理
        //-----------------------------------------------------------

        if (is_numeric($orderHeaderId)) {
            $key = array('order_header_id' => $orderHeaderId);    // 指定されていればUpdate、未指定ならInsert（idは自動採番）
        } else {
            $key = null;
        }
        $data = array(
            'order_id_for_user' => $orderIdForUser,
            'order_date' => $orderDate,
            'partner_id' => $partnerId,
            'remarks_header' => $remarksHeader,
            'classification' => $classification,                                        // 0:製造指示書、1:注文書、2:製造指示書兼注文書
            'worker_id' => ($workerId == "" || $workerId === null ? null : $workerId),  // 拡張DDなので、「なし」は"null"ではなく空文字で来る
            'section_id' => ($sectionId == "" || $sectionId === null ? null : $sectionId),
            'delivery_partner_id' => ($deliveryPartnerId == "" || $deliveryPartnerId === null ? null : $deliveryPartnerId),
        );
        if ($mrpFlag != null) {
            $data['mrp_flag'] = ($mrpFlag ? "true" : "false");
        }
        $gen_db->updateOrInsert('order_header', $key, $data);

        //-----------------------------------------------------------
        // order_header_idを返す
        //-----------------------------------------------------------

        if ($orderHeaderId === null) {
            $orderHeaderId = $gen_db->getSequence("order_header_order_header_id_seq");
        }

        return $orderHeaderId;
    }

    //************************************************
    // 製造指示書/注文書　子データ（order_detail）登録
    //************************************************
    // 同時に子品目の使用予約・支給処理も行う
    //
    // 引数：
    //      $orderDetailId      新規のときはnullを渡す。更新のときのみ指定する。
    //      $orderHeaderId      必ず指定する。親テーブルとのリンク。
    //      $lineNo             行番号。必ず指定する。
    //      $orderNo            nullなら自動採番される。指定する場合、既存チェックはあらかじめやっておくこと
    //      $seiban             必ず指定する（空文字でもいい）
    //      $itemId             必ず指定する
    //      $itemCode           nullなら品目マスタから取得される
    //      $itemName           nullなら品目マスタから取得される
    //      $itemPrice          nullなら品目マスタから取得される
    //      $itemSubCode        メーカー型番。nullなら手配先マスタから取得される
    //      $quantity           数量。必ず指定する
    //      $deadline           オーダー納期。必ず指定する
    //      $alarmFlag          オーダー納期自動調整されたときのアラーム。MRP取り込み用。
    //      $orderUserId        手配先となる得意先ID。内製のときは0にする
    //      $sourceLocationId   子品目支給元ロケID。-1のときは各子品目ごとの標準ロケ（使用）
    //      $sourceLotId        （ロット機能）
    //      $planDate           計画によるオーダーの場合の計画日（「需要計画を生産計画に変更」）
    //      $planQty            計画によるオーダーの場合の計画オーダー数（「需要計画を生産計画に変更」）
    //      $handQty            所要量計算の手修正により自動作成された計画によるオーダーの場合の計画オーダー数（「需要計画を生産計画に変更」）
    //      $orderMeasure       発注単位
    //      $multipleOfOrderMeasure     単位倍数
    //      $remarks            備考
    //      $processFlag        外製工程
    //
    // 戻り値：
    //      なし

    static function entryOrderDetail($orderDetailId, $orderHeaderId, $lineNo, $orderNo,
            $seiban, $itemId, $itemCode, $itemName, $itemPrice, $itemSubCode, $quantity, $deadline,
            $alarmFlag, $orderUserId, $sourceLocationId, $sourceLotId, $planDate, $planQty,
            $handQty, $orderMeasure, $multipleOfOrderMeasure, $remarks, $processFlag)
    {
        global $gen_db;

        //-----------------------------------------------------------
        // オーダー番号が未指定なら採番
        //-----------------------------------------------------------
        //
        //  以前はgetOrderNo()にて select MAX(order_no) from order_detail として採番していたが、
        //  この方法だと複数のトランザクションが同時実行されたときに同じ番号が採番されてしまう可能性がある。
        //  order_detailテーブルを LOCK TABLE table IN ACCESS EXCLUSIVE MODE すればよいが、
        //  パフォーマンス低下やデッドロックが心配。
        //  一方、シーケンスを使用すれば簡単だが、オーダー番号を手動で指定された場合の処理が問題。
        //  複数トランザクションが同時にsetvalした場合に競合回避するのが難しい。
        //  また手動指定だと、シーケンスの範囲を超える非常に大きい値が指定されたときの対処が難しい。
        //  そこで、ここでは採番テーブル方式を使用した。
        // $orderNo = Logic_NumberTable::nextval("order_no", $orderNo);
        //
        // 12i では「prefix(製造指示書(B)・注文書(C)・外製指示書(D))」+「年月4桁」+「連番5桁」に変更。
        if (is_numeric($orderHeaderId)) {
            $query = "select classification from order_header where order_header_id = '{$orderHeaderId}'";
            $class = $gen_db->queryOneValue($query);
        } else {
            // order_header_id が指定されていない場合（ありえないはずだが）は製造指示書と仮定
            $class = '0';
        }
        $prefix = '';
        $cat = '';

        switch ($class) {
            case '0':
                $prefix = GEN_PREFIX_ORDER_NO_MANUFACTURING;
                $cat = 'order_no_manufacturing';
                break;
            case '1':
                $prefix = GEN_PREFIX_ORDER_NO_PARTNER;
                $cat = 'order_no_partner';
                break;
            default:
                $prefix = GEN_PREFIX_ORDER_NO_SUBCONTRACT;
                $cat = 'order_no_subcontract';
        }
        
        while (true) {
            // オーダー番号が未指定なら自動採番。指定されているなら採番テーブルを更新。
            $newOrderNo = Logic_NumberTable::getMonthlyAutoNumber($prefix, $cat, 5, $orderNo);
            
            if ($orderNo == "" && substr($newOrderNo, -5) != "99999") {
                // 自動採番の場合、既存のオーダー番号との重複をチェックする。
                //  自動採番ロジックでは同じカテゴリ内での番号重複は起こらない。しかし製造指示書・注文書・外製指示書のオーダー番号
                //  については、採番上は別カテゴリでありながらも同じカラムを使用しているため、重複が問題となる。
                //  例えば、外製指示書に対して注文書の形式のオーダー番号を手入力すると、採番テーブル上は別カテゴリなので注文書の採番
                //  テーブルの更新は行われない。それで、後ほど注文書を自動採番したときに同じ番号を採ってしまう可能性がある。
                //  外製と注文のオーダー番号は別物なので仕様的な意味では重複してもいいのだが、実際は同じカラムを使用しているため、
                //  番号が重複するとエラーになってしまう。
                //  実際にこれが問題となった例：　ag.cgi?page=ProjectDocView&pid=1574&did=216330
                //  そこでオーダー番号に関してのみ、自動採番結果の既存チェックを実施する。
                //  番号が既存であれば採番をやりなおす。
                $query = "select order_no from order_detail where order_no = '{$newOrderNo}'";
                if (!$gen_db->existRecord($query)) {
                    break;
                }
            } else {
                break;
            }
        }
        $orderNo = $newOrderNo;
        
        //-----------------------------------------------------------
        // 品目属性が未指定ならマスタから取得
        //-----------------------------------------------------------

        $query = "
        select
            item_code
            ,item_name
            ,item_order_master.default_order_price
            ,safety_lead_time
            ,stock_price
            ,default_order_price_2
            ,default_order_price_3
            ,order_price_limit_qty_1
            ,order_price_limit_qty_2
            ,tax_class
        from
            item_master
            left join item_order_master on item_master.item_id = item_order_master.item_id and line_number = 0
        where
            item_master.item_id = '{$itemId}'
        ";
        $res = $gen_db->getArray($query);

        if ($itemCode === null || $itemCode == '')
            $itemCode = $res[0]['item_code'];
        if ($itemName === null || $itemName == '')
            $itemName = $res[0]['item_name'];

        if ($itemSubCode === null) {
            $query = "select item_sub_code from item_order_master where item_id = '{$itemId}' and order_user_id = '{$orderUserId}'";
            $itemSubCode = $gen_db->queryOneValue($query);
        }

        //-----------------------------------------------------------
        // 金額関連
        //-----------------------------------------------------------
        //
        // 発注単価（未指定の場合）
        if ($itemPrice === null || $itemPrice == '') {
            if ($orderUserId == "0") {
                // 製造品は在庫評価単価を記録するようにした（コンパスで使用）。
                //  在庫評価単価は品目マスタベース。（在庫リストの評価単価欄のように）履歴を参照することまではしていない。
                //  履歴を反映したい場合は、Logic_Stock::getStockPrice(日付, $itemId) を使用する。
                $itemPrice = $res[0]['stock_price'];
                if (!is_numeric($itemPrice))
                    $itemPrice = 0;
            } else {
                $price1 = $res[0]['default_order_price'];
                if (!is_numeric($price1))
                    $price1 = 0;
                $price2 = $res[0]['default_order_price_2'];
                if (!is_numeric($price2))
                    $price2 = 0;
                $price3 = $res[0]['default_order_price_3'];
                if (!is_numeric($price3))
                    $price3 = 0;
                $limit1 = $res[0]['order_price_limit_qty_1'];
                if (!is_numeric($limit1))
                    $limit1 = 0;
                $limit2 = $res[0]['order_price_limit_qty_2'];
                if (!is_numeric($limit2))
                    $limit2 = 0;

                if ($quantity <= $limit1 || $res[0]['order_price_limit_qty_1'] == "") {
                    $itemPrice = $price1;
                } else if ($quantity <= $limit2 || $res[0]['order_price_limit_qty_2'] == "") {
                    $itemPrice = $price2;
                } else {
                    $itemPrice = $price3;
                }
            }
        }

        // 課税区分
        // 　常に品目マスタから取得。
        // 　※ このあとのセクションで、取引通貨が￥以外の場合は非課税にしていることに注意
        $taxClass = $res[0]['tax_class'];
        if (!is_numeric($taxClass))
            $taxClass = 0;  // 課税

        // 発注金額と消費税
        // 　以前のバージョンでは発注金額をDB保存せず、必要時にそのつど単価と数量から計算していた。
        // 　そのため発注金額の端数処理については、リスト画面では丸めなし、帳票ではエクセルテンプレート依存など、
        // 　表示箇所によって異なっていた。
        // 　一方、受入金額は取引先マスタの端数処理にしたがって整数丸めしているため、発注金額と受入金額が異なる場合があった。
        // 　それで 12iより、発注金額についても、取引先マスタの端数処理によって整数丸めした金額をDB保存するようにした。
        if ($orderUserId == "0") {
            $orderAmount = 0;
            $orderTax = 0;
        } else {
            $orderAmount = Logic_Customer::round(Gen_Math::mul($quantity, $itemPrice), $orderUserId);

            if ($taxClass == '0') {
                if (is_numeric($orderHeaderId)) {
                    $query = "select order_date from order_header where order_header_id = '{$orderHeaderId}'";
                    $orderDate = $gen_db->queryOneValue($query);
                } else {
                    $orderDate = date('Y-m-d');
                }
                $taxRate = Logic_Tax::getTaxRate($orderDate);
                $orderTax = Logic_Customer::round(Gen_Math::mul(Gen_Math::mul($quantity, $itemPrice), Gen_Math::div($taxRate, 100)), $orderUserId);
            } else {
                $orderTax = 0;
            }
        }

        //-----------------------------------------------------------
        // 取引通貨とレートを取得
        //-----------------------------------------------------------

        $query = "
        select
            customer_master.currency_id
            ,coalesce(rounding,'round') as rounding
            ,coalesce(rate,1) as rate
        from
            customer_master
            left join currency_master on customer_master.currency_id = currency_master.currency_id
            -- 注文日時点のレートを取得
            left join (select currency_id, max(rate_date) as rate_date from rate_master
                where rate_date <= (select order_date from order_header where order_header_id = '{$orderHeaderId}')
                group by currency_id) as t_rate_date
                on currency_master.currency_id = t_rate_date.currency_id
            left join rate_master on t_rate_date.currency_id = rate_master.currency_id and t_rate_date.rate_date = rate_master.rate_date
        where
            customer_master.customer_id = '{$orderUserId}'
        ";
        $currency = $gen_db->queryOneRowObject($query);

        // 取引通貨処理（基軸通貨以外のとき）
        if ($currency != null && $currency->currency_id !== null) {
            // 基軸通貨以外のときは非課税にする
            $taxClass = 1;  // 非課税
            $orderTax = 0;

            // 入力された単価・金額は外貨として登録する
            $foreignCurrencyItemPrice = $itemPrice;
            $foreignCurrencyOrderAmount = $orderAmount;

            if ($orderUserId == "0") {
                // 内製
                // $itemPrice は在庫評価単価（レポートで使用）
                $itemPrice = Gen_Math::round(Gen_Math::mul($itemPrice, $currency->rate), 'round', 2);
                $orderAmount = Gen_Math::round(Gen_Math::mul($quantity, $itemPrice), 'round');
            } else {
                // 単価を基軸通貨に換算（単価を取引先マスタの基準に従って丸めない）
                // 小数点以下桁数は、単価は GEN_FOREIGN_CURRENCY_PRECISION、金額は取引先マスタの値
                $itemPrice = Gen_Math::round(Gen_Math::mul($itemPrice, $currency->rate), $currency->rounding, GEN_FOREIGN_CURRENCY_PRECISION);
                $orderAmount = Logic_Customer::round(Gen_Math::mul($quantity, $itemPrice), $orderUserId);
            }
        }

        //-----------------------------------------------------------
        // 登録処理
        //-----------------------------------------------------------

        if (is_numeric($orderDetailId)) {
            $key = array('order_detail_id' => $orderDetailId);    // 指定されていればUpdate、未指定ならInsert（idは自動採番）
        } else {
            $key = null;
        }
        $data = array(
            'order_header_id' => $orderHeaderId,
            'line_no' => $lineNo,
            'order_no' => $orderNo,
            'seiban' => $seiban,
            'item_id' => $itemId,
            'item_code' => $itemCode,
            'item_name' => $itemName,
            'item_price' => $itemPrice,
            'order_amount' => $orderAmount,
            'order_tax' => $orderTax,
            'item_sub_code' => $itemSubCode,
            'order_detail_quantity' => $quantity,
            'order_detail_dead_line' => $deadline,
            'alarm_flag' => ($alarmFlag ? 'true' : 'false'),
            'payout_location_id' => $sourceLocationId,
            'payout_lot_id' => $sourceLotId,
            'order_measure' => $orderMeasure,
            'multiple_of_order_measure' => $multipleOfOrderMeasure,
            'tax_class' => $taxClass,
            'remarks' => $remarks,
        );

        if (Gen_String::isDateString($planDate) && is_numeric($planQty)) {
            $data['plan_date'] = $planDate;
            $data['plan_qty'] = $planQty;
        }
        if (Gen_String::isDateString($planDate) && is_numeric($handQty)) {
            $data['plan_date'] = $planDate;
            $data['hand_qty'] = $handQty;
        }
        // 取引通貨処理
        if ($currency != null && $currency->currency_id !== null) {
            // 基軸通貨以外のとき
            $data['foreign_currency_id'] = $currency->currency_id;
            $data['foreign_currency_rate'] = $currency->rate;
            $data['foreign_currency_item_price'] = $foreignCurrencyItemPrice;
            $data['foreign_currency_order_amount'] = $foreignCurrencyOrderAmount;
        } else {
            // 基軸通貨のとき
            $data['foreign_currency_id'] = null;
            $data['foreign_currency_rate'] = null;
            $data['foreign_currency_item_price'] = null;
            $data['foreign_currency_order_amount'] = null;
        }

        $gen_db->updateOrInsert('order_detail', $key, $data);

        //-----------------------------------------------------------
        // 子品目関連の処理
        //-----------------------------------------------------------

        if (!is_numeric($orderDetailId)) {
            $orderDetailId = $gen_db->getSequence("order_detail_order_detail_id_seq");
        }

        // 外製工程以外の時は子品目の処理を行なう
        if (!$processFlag) {
            self::orderChildItemUpdate($orderHeaderId, $orderDetailId, $orderUserId, $sourceLocationId, $sourceLotId);
        }

        //-----------------------------------------------------------
        // 内製（製造指示書）の場合、order_processテーブルに工程を登録
        //-----------------------------------------------------------
        // また、外製工程がある場合は外製指示書を登録

        $deleteArr = array();   // 初期化

        if ($orderUserId == "0") {
            // 外製工程とマスタ工程の差分チェック
            // 不要となった工程は削除する
            // 既存の外製工程をチェック（既存の order_process を削除する前に確認）
            $query = "select order_process_no, machining_sequence from order_process
                        where subcontract_partner_id is not null and order_detail_id = '{$orderDetailId}'";
            $res = $gen_db->getArray($query);
            if (is_array($res)) {
                foreach ($res as $row) {
                    // 品目マスタの該当工程情報を取得
                    $query = "select subcontract_partner_id from item_process_master
                                where item_id = '{$itemId}' and machining_sequence = {$row['machining_sequence']}";
                    $subcontractPartnerId = $gen_db->queryOneValue($query);
                    // 外製先が存在しなければ外製指示書のidを削除用にキャッシュ
                    if (!isset($subcontractPartnerId) || !is_numeric($subcontractPartnerId) || $subcontractPartnerId == "0") {
                        $query = "select order_detail_id from order_detail where subcontract_order_process_no = '{$row['order_process_no']}'";
                        $deleteArr[] = $gen_db->queryOneValue($query);
                    }
                }
            }

            // 既存の order_process を削除
            $query = "delete from order_process where order_detail_id = '{$orderDetailId}'";
            $gen_db->query($query);

            // 必要な情報を読み出す
            $query = "
            select
                order_detail.order_no || '-' || cast((item_process_master.machining_sequence+1) as text) as order_process_no
                ,order_detail.order_detail_id
                ,order_detail.order_no
                ,item_process_master.process_id
                ,item_process_master.machining_sequence
                ,item_process_master.subcontract_partner_id
                ,item_process_master.default_work_minute
                ,item_process_master.pcs_per_day
                ,item_process_master.charge_price
                ,coalesce(item_process_master.overhead_cost,0) as overhead_cost
                ,coalesce(item_process_master.subcontract_unit_price,0) as subcontract_unit_price
                -- LT未指定のときは、工程LTからLTを計算する。工程LTが空欄なら「(オーダー数÷製造能力)-1」。Logic_Mrpの着手日計算と同じ
                -- 「-1」しているのは、製造が1日以内で終わるときはLT=0、足掛け2日かかる場合はLT=1とする必要があるため
                ,coalesce(item_process_master.process_lt,trunc(order_detail_quantity
                    / coalesce(case when item_process_master.pcs_per_day=0 then 1 else item_process_master.pcs_per_day end,1) + 0.9999999999)-1) as process_lt
                ,process_master.process_name
                ,t_next_customer.customer_name as next_partner_name
                ,item_process_master.process_remarks_1
                ,item_process_master.process_remarks_2
                ,item_process_master.process_remarks_3
            from
                order_detail
                inner join item_process_master on order_detail.item_id = item_process_master.item_id
                left join process_master on item_process_master.process_id = process_master.process_id
                left join item_process_master as t_next_item_process on order_detail.item_id = t_next_item_process.item_id
                    and t_next_item_process.machining_sequence = item_process_master.machining_sequence + 1
                left join customer_master as t_next_customer on t_next_item_process.subcontract_partner_id = t_next_customer.customer_id
            where
                order_detail_id = '{$orderDetailId}'
            order by
                machining_sequence desc
            ";
            $res = $gen_db->getArray($query);

            // この指示書の着手日を確認
            $query = "select order_date from order_header where order_header_id = '{$orderHeaderId}'";
            $orderDate = strtotime($gen_db->queryOneValue($query));

            // 最終工程の工程納期を決める
            $deadline = strtotime($deadline);
            if ($deadline < $orderDate) {
                $deadline = $orderDate;
            }

            // 休日データを取得
            $holidayArr = Gen_Date::getHolidayArray($orderDate, $deadline);

            if (is_array($res)) {
                // 開始日・工程納期を決定する
                // 最終工程から前へ向かって処理。
                foreach ($res as $key => $row) {
                    // 工程の着手日を決める
                    // MRPと同じく、LTと休日を考慮しながら計算。オーダー全体の着手日（order_date）より前にはならないようにする。
                    $alarm = '0';    // dummy
                    $startDate = Gen_Date::getOrderDate($deadline, $row['process_lt'], $holidayArr, $orderDate, $alarm);

                    $res[$key]['start_date'] = date('Y-m-d', $startDate);
                    $res[$key]['dead_line'] = date('Y-m-d', $deadline);

                    // 一つ前の工程の納期を決める
                    $deadline = $startDate;
                }

                // 登録処理
                // こんどは最初の工程から後ろに向かって処理（オーダー番号を順につけるため）
                $rev_res = array_reverse($res);
                foreach ($rev_res as $key => $row) {
                    // order_process登録
                    $data = array(
                        'order_process_no' => $row['order_process_no'],
                        'order_detail_id' => $row['order_detail_id'],
                        'process_id' => $row['process_id'],
                        'machining_sequence' => $row['machining_sequence'],
                        'default_work_minute' => $row['default_work_minute'],
                        'pcs_per_day' => $row['pcs_per_day'],
                        'charge_price' => $row['charge_price'],
                        'process_start_date' => $row['start_date'],
                        'process_dead_line' => $row['dead_line'],
                        'overhead_cost' => $row['overhead_cost'],
                        'subcontract_unit_price' => $row['subcontract_unit_price'],
                        'subcontract_partner_id' => $row['subcontract_partner_id'],
                        'process_remarks_1' => $row['process_remarks_1'],
                        'process_remarks_2' => $row['process_remarks_2'],
                        'process_remarks_3' => $row['process_remarks_3'],
                    );

                    $gen_db->insert('order_process', $data);

                    if ($row['subcontract_partner_id'] !== null && $row['subcontract_partner_id'] != "0") {
                        // 外製工程がある場合は外製指示書を発行する。
                        $query = "select seiban from order_detail where subcontract_order_process_no = '{$row['order_process_no']}'";
                        $subSeiban = $gen_db->queryOneValue($query);
                        if ($subSeiban !== false) {
                            // 既存の外製指示書がある場合は発行しない。
                            //   10iでは削除して再発行していたが、それだと外製指示書の手配先等を変更したあとで、
                            //   製造指示書を再登録すると変更点が元に戻ってしまう。
                            // ただし親オーダーの製番が変更された場合は、既存外製指示の製番も変更しておく。ag.cgi?page=ProjectDocView&pid=1574&did=219716
                            if ($seiban != $subSeiban) {
                                $query = "update order_detail set seiban = '{$seiban}' where subcontract_order_process_no = '{$row['order_process_no']}'";
                                $gen_db->query($query);
                            }
                        } else {
                            // 外製指示書を発行
                            $headerId = self::entryOrderHeader(
                                2       // classification
                                , null  // order_header_id
                                , null  // order_id_for_user
                                , $row['start_date']
                                , $row['subcontract_partner_id']
                                , ''    // remarks_header
                                , null  // worker_id
                                , null  // section_id
                                , null  // delivery_partner_id
                                , null
                            );

                            $detailId = self::entryOrderDetail(
                                null    // order_detail_id
                                , $headerId
                                , 1     // line_no
                                , null  // order_no
                                , $seiban
                                , $itemId
                                , null  // item_code
                                , null  // item_name
                                , $row['subcontract_unit_price']
                                , ''    // item_sub_code
                                , $quantity
                                , $row['dead_line']
                                , false // alarm_flag
                                , $row['subcontract_partner_id']
                                , null  // payout_location_id
                                , null  // payout_lot_id
                                , null  // planDate
                                , null  // planQty
                                , null  // handQty
                                , null  // order_measure
                                , null  // multiple_of_order_measure
                                , null  // remarks
                                , true  // processFlag
                            );

                            // 発送先等をいま登録した外製指示書に書きこむ。
                            $data = array(
                                "subcontract_order_process_no" => $row['order_process_no'],
                                "subcontract_parent_order_no" => $row['order_no'],
                                "subcontract_process_name" => $row['process_name'],
                                "subcontract_process_remarks_1" => $row['process_remarks_1'],
                                "subcontract_process_remarks_2" => $row['process_remarks_2'],
                                "subcontract_process_remarks_3" => $row['process_remarks_3'],
                                // 発送先は次工程の外製先。次工程が内製、もしくはこの工程が最終工程なら自社名。
                                "subcontract_ship_to" => ($row['next_partner_name'] === null ? $_SESSION["company_name"] : $row['next_partner_name']),
                            );
                            $where = "order_detail_id = '{$detailId}'";
                            $gen_db->update("order_detail", $data, $where);
                        }
                    }
                }
            }
        }

        // 不要になった外製指示書を削除
        if (count($deleteArr) > 0) {
            foreach ($deleteArr as $id) {
                if (isset($id) && is_numeric($id))
                    self::deleteOrderDetail($id);
            }
        }

        return $orderDetailId;
    }

    //************************************************
    // 子品目（使用予定および支給）の更新
    //************************************************
    // 内製の場合は子品目使用予定を登録する。
    // 外製の場合は、サプライヤーロケがなく、かつ支給タイミングが受入時の場合は子品目使用予定を登録する。
    //  サプライヤーロケがあるか、支給タイミングが発注時の場合は入出庫（in_out）に支給出庫とサプライヤーロケ入庫を登録する。

    static function orderChildItemUpdate($orderHeaderId, $orderDetailId, $orderUserId, $sourceLocationId, $sourceLotId)
    {
        global $gen_db;

        //-----------------------------------------------------------
        // 子品目の処理（使用予定および支給）
        //-----------------------------------------------------------

        if ($orderUserId == "0") {
            // 内製の場合：
            //  子品目使用予定の登録
            self::entryUsePlan($orderDetailId);
        } else {
            // 外製工程の場合：
            //  外製工程の場合は子品目があっても支給しない
            //    （構成表上で子品目は工程ごとに指定されているわけではないので、どの工程で使用される子品目か特定できない）
            //  このあとの order_child_item の処理も行う必要が無いので、ここでreturn
            // ⇒（15i追記）この時点ではまだ subcontract_order_process_no が登録されていないのでこの処理は無意味。
            //              外製工程であってもすべてスルーしてしまう。
            //              その結果、外製工程は本来は一切支給が行われないはずなのに、品目マスタでその取引先を代替手配先としても
            //              指定しておき、手配区分を「外製（支給あり）」にすれば支給される、という動きになっている。
            //              ag.cgi?page=ProjectDocView&pid=1516&did=176104
            $query = "select order_detail_id from order_detail where order_detail_id = '{$orderDetailId}' and coalesce(subcontract_order_process_no,'') <> ''";
            if ($gen_db->existRecord($query))
                return;

            // 外製の場合：
            //  子品目の支給処理。
            //  サプライヤーロケの有無と支給タイミングの設定（自社情報）によって処理
            self::childItemPayout($orderDetailId, $sourceLocationId);
        }

        //-----------------------------------------------------------
        // オーダー発行時の構成を記録（order_child_item）
        //-----------------------------------------------------------
        // オーダー発行後に構成表を変更された場合に、使用予定の消しこみや子品目の引き落としで不具合が出るのを
        // 避けるため、テーブル（order_child_item）にオーダー発行時の構成を記録する
        //
        // 既存の order_child_item を削除
        $query = "delete from order_child_item where order_detail_id = '{$orderDetailId}'";
        $gen_db->query($query);

        // temp_real_child_item テーブル（ダミー品目をスキップした構成表）を作成
        Logic_Bom::createTempRealChildItemTable($gen_db->queryOneValue("select item_id from order_detail where order_detail_id = '{$orderDetailId}'"));

        // order_child_item を登録
        $query = "
        insert into order_child_item (
            order_detail_id
            ,child_item_id
            ,quantity
            ,record_creator
            ,record_create_date
            ,record_create_func
        )
        select
            order_detail_id
            ,child_item_id
            ,sum(quantity) as quantity
            ,max('" . $_SESSION['user_name'] . "')
            ,max('" . date('Y-m-d H:i:s') . "'::timestamp)
            ,max('" . __CLASS__ . "::" . __FUNCTION__ . "')
        from
            order_detail
            inner join temp_real_child_item on order_detail.item_id = temp_real_child_item.item_id
        where
            order_detail_id = '{$orderDetailId}'
        group by
            order_detail_id
            ,child_item_id
        ";
        $gen_db->query($query);
    }

    //**************************************
    // 子品目使用予定数を登録（内製用）
    //**************************************
    // 製造指示書/外製指示書データをもとに、子品目の使用予定数を登録する。
    //  ・09iまでは、この処理を行うのは製造指示書だけだった。10iでは、外製指示書で
    //   子品目引落タイミングが「受入時」になっている場合もこの処理を行うようになった。
    //  ・ここで登録された使用予定数は有効在庫から差し引かれ（MRPで考慮される）、
    //   実績/外製受入登録で消しこまれる。
    //  ・製番品であっても製番は登録しなくていい。
    //   （オーダー番号が分かっているので、実績登録時にちゃんと製番在庫が引き落とされる）
    //  ・更新時はいったん削除して再登録する。
    //  ・トランザクションは呼び出し元ですでに開始されていることが前提。

    static function entryUsePlan($orderDetailId)
    {
        global $gen_db;

        // 既存データをいったん削除
        $query = "delete from use_plan where order_detail_id = '{$orderDetailId}'";
        $gen_db->query($query);

        // temp_real_child_item テーブル（ダミー品目をスキップした構成表）を作成
        Logic_Bom::createTempRealChildItemTable($gen_db->queryOneValue("select item_id from order_detail where order_detail_id = '{$orderDetailId}'"));

        // 登録
        $query = "
        insert into use_plan (
            order_header_id
            ,order_detail_id
            ,item_id
            ,use_date
            ,quantity
            ,record_creator
            ,record_create_date
            ,record_create_func
        )
        select
            order_header.order_header_id
            ,order_detail.order_detail_id
            ,child_item_id
            ,order_date
            ,order_detail_quantity * quantity
            ,'" . $_SESSION['user_name'] . "'
            ,'" . date('Y-m-d H:i:s') . "'
            ,'" . __CLASS__ . "::" . __FUNCTION__ . "'
        from
            order_header
            inner join order_detail on order_header.order_header_id = order_detail.order_header_id
            inner join temp_real_child_item on order_detail.item_id = temp_real_child_item.item_id
        where
            order_detail.order_detail_id = '{$orderDetailId}'
        ";
        $gen_db->query($query);
    }

    //**************************************
    // 子品目支給処理（外製用）
    //**************************************
    // サプライヤーロケがなく、かつ支給タイミング（自社情報）が「受入時」の場合は、子品目使用予定を登録する。
    // 上記以外（サプライヤーロケがあるか、支給タイミングが「発注時」の場合）は入出庫（in_out）に支給出庫と
    // サプライヤーロケ入庫（ロケがあれば）を登録する。
    //  ・明細行単位。
    //  ・トランザクションは呼び出し元ですでに開始されていることが前提。

    static function childItemPayout($orderDetailId, $sourceLocationId)
    {
        global $gen_db;

        // 既存支給データ・使用予定データをいったん削除。
        // 支給区分や支給タイミング等にかかわらず処理しておく必要がある。
        // それらを変更して再登録した場合に不要な支給データが残ってしまわないようにするため。
        $query = "delete from use_plan where order_detail_id = '{$orderDetailId}'";
        $gen_db->query($query);
        Logic_Inout::deletePayoutInout($orderDetailId);

        // データの有無と支給区分（支給の有無）、サプライヤーロケの確認。
        // item_order_masterに、同じ品目に対して同じサプライヤーが2回以上登録されている場合
        // （品目マスタでそのような登録は禁止されているが）、このクエリの結果は複数行になる。
        // それぞれの支給区分が違う場合、結果は不定となる。
        //
        // 外製工程の場合、また品目マスタにその取引先が登録されていない場合は支給なしとなる。
        // （ただし外製工程については、その取引先が「外製（支給あり）」の代替手配先としても登録されている場合は支給される）
        // この仕様の理由等については、Tipsの外製FAQを参照のこと。
        //
        // 支給関係の動作を変更する場合は、外製指示登録画面の支給メッセージも変更すること。
        // （Partner_Subcontract_Edit、Partner_Subcontract_AjaxPayoutMode）
        // 外製登録画面のページヒントも変更すること。
        $query = "
        select
            item_order_master.partner_class
            ,location_id
        from
            order_detail
            inner join order_header on order_header.order_header_id = order_detail.order_header_id
            left join item_order_master
                on order_detail.item_id = item_order_master.item_id
                and order_header.partner_id = item_order_master.order_user_id
            left join location_master on order_header.partner_id = location_master.customer_id
        where
            order_detail_id = '{$orderDetailId}'
        ";

        // オーダーデータ無しの場合や「支給無し」の場合、処理しない
        $res = $gen_db->queryOneRowObject($query);
        if ($res === false || $res->partner_class != "2")
            return;

        // サプライヤーロケ
        $supplierLocationId = $res->location_id;

        // 外製支給タイミング
        $query = "select payout_timing from company_master";
        $payoutTiming = $gen_db->queryOneValue($query);

        // サプライヤーロケがなく、かつ支給タイミングが「受入時」の場合、支給はせず子品目の使用予定を登録する。
        if (!is_numeric($supplierLocationId) && $payoutTiming == "1") {
            self::entryUsePlan($orderDetailId);
            return;
        }

        // ***** 以下、支給の処理
        //
        // temp_real_child_item テーブル（ダミー品目をスキップした構成表）を作成
        Logic_Bom::createTempRealChildItemTable($gen_db->queryOneValue("select item_id from order_detail where order_detail_id = '{$orderDetailId}'"));

        // 登録データの取得
        $query = "
        select
            order_date
            ,partner_id
            ,child_item_id
            ,seiban
            ,order_detail_quantity * temp_real_child_item.quantity as qty
            ,coalesce(payout_price,0) as payout_price
            ,order_class
        from
            order_header
            inner join order_detail on order_header.order_header_id = order_detail.order_header_id
            inner join temp_real_child_item on order_detail.item_id = temp_real_child_item.item_id
            inner join item_master on temp_real_child_item.child_item_id = item_master.item_id
        where
            order_detail_id = '{$orderDetailId}'
        ";
        $res = $gen_db->getArray($query);

        $sourceLocationId = (is_numeric($sourceLocationId) ? $sourceLocationId : 0);
        $autoLocMode = ($sourceLocationId == -1);

        if (is_array($res)) {
            foreach ($res as $row) {
                // 支給在庫製番の決定
                $stockSeiban = Logic_Seiban::getStockSeiban($row['seiban']);

                //  ハイブリッドMRPの導入に伴い、子品目がMRPだったときは子品目在庫製番をクリアする処理を追加
                //  ----- lot ver ----- change 1 line ロットが子品目ということはありえないはずだが一応
                if ($row['order_class'] == '1' || $row['order_class'] == '2') {
                    $stockSeiban = "";
                }

                //  支給ロケが-1の場合、子品目ごとの標準ロケ（使用）を支給ロケとする
                if ($autoLocMode) {
                    $query = "select default_location_id_2 from item_master where item_id= '" . $gen_db->quoteParam($row['child_item_id']) . "'";
                    $sourceLocationId = $gen_db->queryOneValue($query);

                    if (!is_numeric($sourceLocationId))
                        $sourceLocationId = 0;
                }

                // 登録（支給出庫）
                $itemInOutId = Logic_Inout::entryInout(
                    $row['order_date']
                    , $row['child_item_id']
                    , $stockSeiban
                    , $sourceLocationId
                    , ''     // lot_no
                    , $row['qty']
                    , $row['payout_price']
                    , "payout"
                    , "order_detail_id"
                    , $orderDetailId
                );

                $data = array("partner_id" => $row['partner_id']);
                $where = "item_in_out_id = $itemInOutId";
                $gen_db->update("item_in_out", $data, $where);

                // 登録（サプライヤーロケ入庫）
                //   サプライヤーロケがあるときのみ
                if (is_numeric($supplierLocationId)) {
                    $ItemInOutId_payout = Logic_Inout::entryInout(
                                    $row['order_date']
                                    , $row['child_item_id']
                                    , $stockSeiban
                                    , $supplierLocationId
                                    , ''     // lot_no
                                    , $row['qty']
                                    , $row['payout_price']
                                    , "in"
                                    , "order_detail_id"
                                    , $orderDetailId
                    );

                    $data = array("payout_item_in_out_id" => $itemInOutId);
                    $where = "item_in_out_id = {$ItemInOutId_payout}";
                    $gen_db->update("item_in_out", $data, $where);
                }
            }
        }
    }

    //************************************************
    // MRP結果の取り込み（mrp ⇒ order_header/detail）
    //************************************************
    // 戻り値：
    //    取り込んだデータのorder_header_idの配列。エラーならfalse

    static function mrpToOrder($class, $fixDate = false)
    {
        global $gen_db;

        //-----------------------------------------------------------
        // トランザクション開始
        //-----------------------------------------------------------

        $gen_db->begin();

        //-----------------------------------------------------------
        // mrpテーブルをロックする。
        //-----------------------------------------------------------
        //    最初のselectを行った後に、他トランからレコードが追加された場合、
        //    後半の「UPDATE mrp SET order_flag = 1」のときに、データ取り込み
        //    されていない追加レコードまでフラグが立ってしまう可能性がある。
        //    分離レベルをSERIALIZABLEにすれば防げるのだが、それだと
        //    競合があった場合にエラー&ROLLBACKになってしまい、その対処が面倒である。
        //    そこでテーブル自体をロックすることで問題を回避している。
        //    データ量が多くなり、取り込みに時間がかかるようになったときの
        //    パフォーマンス（他トランの待ち時間）がやや心配だが・・。

        $query = "LOCK TABLE mrp IN SHARE MODE;";    // SHAREは、INSERT/UPDATEは防ぐがselectは防がないロックモード
        $gen_db->query($query);

        //-----------------------------------------------------------
        // データの読み出し（From mrp）
        //-----------------------------------------------------------
        // 同様の内容のSQLが内示書発行（Manufacturing_Mrp_UnofficialReport）にもある。
        // ここを変えるときはそちらも修正する必要がある可能性が高いのでチェックすること。

        $query = "
        select
            item_order_master.order_user_id
            ,MAX(customer_id) as customer_id
            ,MAX(customer_name) as customer_name
            ,mrp.item_id
            ,arrangement_start_date
            ,arrangement_finish_date
            ,SUM(arrangement_quantity) as qty
            ,seiban
            ,calc_date
            ,SUM(plan_qty) as plan_qty
            ,SUM(hand_qty) as hand_qty
            ,MAX(order_measure) as order_measure
            ,coalesce(MAX(item_order_master.multiple_of_order_measure),1) as multiple_of_order_measure
            ,MAX(alarm_flag) as alarm_flag
            ,MAX(safety_lead_time) as slt
        from
            mrp
            inner join item_master on mrp.item_id = item_master.item_id
            -- ここでの手配先・発注単価は、手配先マスタの標準手配先(line_number=0)のものをセットする
            inner join item_order_master on mrp.item_id = item_order_master.item_id and line_number=0
            left join customer_master on item_order_master.order_user_id = customer_master.customer_id

        -- このwhere条件を変更するときは、後のMRPテーブル取り込済フラグ（update mrp ...）のSQLも変更すること
        where
            coalesce(order_flag,0) = 0
            -- ダミー品目はオーダーを発行しない
            and not coalesce(item_master.dummy_item, false)
            and partner_class " . ($class == 0 ? "=0" : ($class == 1 ? "=3" : " in (1,2)")) . "
            -- この絞込みはHAVINGでも行っているが、ムダではない。
            -- mrpテーブルに多数のレコードがあるが、その中でオーダー発行対象になるレコードがわずかのときに、これがないとかなり遅くなる。
            and arrangement_quantity <> 0
            and mrp.order_class <> '99'  -- 製番引当オーダーの排除
            " . (!$fixDate ? "" : "and mrp.arrangement_start_date <= '{$fixDate}'::date") . "
        group by
            item_order_master.order_user_id
            ,mrp.item_id
            ,arrangement_start_date
            ,arrangement_finish_date
            ,seiban
            ,calc_date
        having
            SUM(arrangement_quantity) > 0
        -- 後のデータ登録の際に、発注先と発行日が一致しているデータを一枚の発注書にまとめる。
        -- そのため、ここでのOrder By指定が重要であることに注意。
        order by
            item_order_master.order_user_id
            ,arrangement_start_date
            ,arrangement_finish_date
            ,max(item_master.item_code)
            ,seiban
            ,calc_date
        ";

        if (!$arr = $gen_db->getArray($query)) {
            //  このfuncでbeginしたトランザクションはここで終わらせておかないと、
            //  指示書・注文書一括発行において全体がコミットされなくなってしまう
            //  rollbackではダメなことに注意（これ以前の処理も廃棄されてしまうため）
            $gen_db->commit();

            return false;        // データなし
        }

        //-----------------------------------------------------------
        // データの登録（To order_header/detail）
        //-----------------------------------------------------------
        //  発注（発注書）の場合は、発注先と発行日が一致している場合、一枚の発注書にまとめる。
        //  内製（製造指示書）の場合は、すべてバラ発行。

        $orderUserIdCache = "";
        $orderDateCache = "";
        $res = array();

        set_time_limit(600);

        $lineNo = 1;
        foreach ($arr as $row) {

            // 登録処理
            if ($class != 0 || $orderUserIdCache != $row['order_user_id'] || $orderDateCache != $row['arrangement_start_date']) {
                //-----------------------------------------------------------
                // オーダー区分番号（製造指示書 or 注文書 or 製造指示書兼注文書）を取得
                //-----------------------------------------------------------
                // 製造指示書および製造指示書兼注文書の場合、ヘッダと明細が1対1であることが
                // 前提（製造指示書か製造指示書兼注文書かは品目IDにより判断するので）

                $classification = self::getOrderClass(($class == 0), $row['item_id'], $row['order_user_id']);

                //-----------------------------------------------------------
                // 親テーブル登録
                //-----------------------------------------------------------

                $orderHeaderId = self::entryOrderHeader(
                                $classification
                                , null
                                , null
                                , ($classification == 1 ? date('Y-m-d') : $row['arrangement_start_date'])
                                , $row['customer_id']
                                , ""
                                , null
                                , null
                                , null
                                , true
                );

                //-----------------------------------------------------------
                // 戻り値(取り込んだorder_header_idのリスト)を準備
                //-----------------------------------------------------------

                $res[] = $orderHeaderId;

                //-----------------------------------------------------------
                // 次へ
                //-----------------------------------------------------------

                $lineNo = 1;
                $orderUserIdCache = $row['order_user_id'];
                $orderDateCache = $row['arrangement_start_date'];
            }

            //-----------------------------------------------------------
            // 子テーブル登録
            //-----------------------------------------------------------
            // 同時に子品目の使用予約・支給処理も行う

            self::entryOrderDetail(
                null
                , $orderHeaderId
                , $lineNo++
                , null
                , $row['seiban']
                , $row['item_id']
                , null
                , null
                , null
                , null
                , $row['qty']
                , $row['arrangement_finish_date']
                , ($row['alarm_flag'] == 1)
                , (is_numeric($row['order_user_id']) ? $row['order_user_id'] : 0)
                , -1    // 支給元ロケ。品目ごとの標準ロケ（使用）
                , 0
                , $row['calc_date']
                , $row['plan_qty']
                , $row['hand_qty']
                , $row['order_measure']
                , $row['multiple_of_order_measure']
                , null
                , false
            );

            //-----------------------------------------------------------
            // planテーブルに計画オーダー発行済み数を登録（「需要計画を生産計画に変更」）
            //-----------------------------------------------------------

            $year = (int) date('Y', strtotime($row['calc_date']));
            $month = (int) date('m', strtotime($row['calc_date']));
            $day = (int) date('d', strtotime($row['calc_date']));

            if (is_numeric($row['plan_qty'])) {
                $data = array("order{$day}" => "noquote:coalesce(order{$day},0)+coalesce({$row['plan_qty']},0)");

                // 計画テーブルは「年月/品目/クラス」でユニーク
                $where = "plan_year='{$year}' and plan_month='{$month}' and item_id='{$row['item_id']}' and classification=0";
                $gen_db->update("plan", $data, $where);
            }
            if (is_numeric($row['hand_qty'])) {
                // 手動調整分については、安全LTを足した日の計画数を更新する。
                // 手動調整の日付は実際にはcalc_date（需要日）ではなくarrangement_finsh_date（工程納期。calc_dateから
                // 安全LT分さかのぼった日）であるため。
                $planDate = strtotime($row['calc_date'] . ' +' . Gen_String::nz($row['slt']) . ' days');
                $planYear = date('Y', $planDate);
                $planMonth = (int) date('m', $planDate);
                $planDay = (int) date('d', $planDate);

                $data = array("order{$planDay}" => "noquote:coalesce(order{$planDay},0)+coalesce({$row['hand_qty']},0)");

                // 計画テーブルは「年月/品目/クラス」でユニーク
                $where = "plan_year='{$planYear}' and plan_month='{$planMonth}' and item_id='{$row['item_id']}' and classification=3";
                $gen_db->update("plan", $data, $where);
            }
        }

        //-----------------------------------------------------------
        // mrpテーブルの取込済フラグを立てる
        //-----------------------------------------------------------

        if (!is_numeric($row['order_user_id'])) {
            $row['order_user_id'] = 'null';
        }

        // このクエリを書き換えるとインデックス（mrp_index4）が使用されなくなり、
        // レコード数が多いときにとても遅くなる可能性があるため注意すること。
        // WHERE条件は、上記selectと合わせること。
        $query = "
        update
            mrp
        set
            order_flag = 1
        from
            item_order_master
        where
            mrp.item_id = item_order_master.item_id and line_number=0
            and item_order_master.partner_class " . ($class == 0 ? "=0" : ($class == 1 ? "=3" : " in (1,2)")) . "
            and mrp.arrangement_quantity <> 0
            and mrp.order_class <> '99'  /* 製番引当オーダーの排除 */
            " . (!$fixDate ? "" : "and mrp.arrangement_start_date <= '{$fixDate}'::date") . "
        ";
        $gen_db->query($query);

        //-----------------------------------------------------------
        // コミット
        //-----------------------------------------------------------
        $gen_db->commit();

        return $res;
    }

    //************************************************
    // 受入数の更新
    //************************************************
    // 実績登録、受入登録で使用

    static function calcAccepted($orderDetailId, $quantity, $completed)
    {
        global $gen_db;

        // 完了フラグ
        //   引数 completed がオンのときはフラグを立てる。
        //   そうでなくても、計画数 <= 受入数 のときは強制的にフラグを立てる。
        // 計画数がマイナスだったときに対応。（注文等はマイナスオーダーを出せるので）
        //   計画数が0以上なら 計画数 <= 受入数 で完了、
        //   計画数がマイナスなら 計画数 >= 受入数 で完了  とする。
        $query = "select order_detail_quantity, accepted_quantity from order_detail where order_detail_id = '{$orderDetailId}'";
        $data = $gen_db->queryOneRowObject($query);
        if ($completed == "true"
                || ($data->order_detail_quantity >= 0 && $data->order_detail_quantity <= ($data->accepted_quantity + $quantity))
                || ($data->order_detail_quantity < 0 && $data->order_detail_quantity >= ($data->accepted_quantity + $quantity))
        ) {
            $completed_flag = "true";
        } else {
            $completed_flag = "false";
        }

        // 登録
        $data = array(
            "accepted_quantity" => "noquote:COALESCE(accepted_quantity,0) + {$quantity}",
            "order_detail_completed" => $completed_flag,
        );
        $where = "order_detail_id = '{$orderDetailId}'";
        $gen_db->update("order_detail", $data, $where);

        return ($completed_flag == "true");
    }

    //************************************************
    // オーダー分割
    //************************************************
    // オーダー（明細行）を複数のオーダーに分割する。
    // 分割後の各オーダーについて、発注先と数量のみ指定可能。それ以外の内容は分割前ものをコピーする。
    //
    // いったんオーダー削除して再登録するため、order_header_id、order_detail_id が変更になる。
    // order_header_id、order_detail_idをフィールドとして持つテーブルの扱いに注意しなければならない。
    //    ・実績受入（Achievement、Accepted）
    //        登録済の場合は変更不可とする（呼び出し元でチェックすること）。
    //        （連動削除できれば理想的だが、付随データの削除や現在処理月の確認などいろいろ面倒）
    //    ・入出庫（Inout）
    //        order_detailが記録されるのは支給関連のみ。オーダー削除時に同時削除、再登録時に同時登録
    //        されるため問題なし。
    //    ・子品目使用予約（use_plan）
    //        オーダー削除時に同時削除、再登録時に同時登録されるため問題なし。
    //
    // 引数：
    //      $orderDetailId  分割元のid
    //      $orderArray        分割内容が入った二次元配列。(0=>発注先id, 1=>数量, 2=>単価) という形
    //
    // 戻り値：
    //      なし

    static function divideOrder($orderDetailId, $orderArray)
    {
        global $gen_db;

        // 分割前の内容をキャッシュ
        $query = "
        select
            order_header.order_header_id,
            ,order_id_for_user
            ,order_date
            ,partner_id
            ,worker_id
            ,section_id
            ,delivery_partner_id
            ,remarks_header
            ,classification
            ,seiban
            ,item_id
            ,order_detail_dead_line
            ,alarm_flag
            ,plan_date
            ,plan_qty
            ,hand_qty
            ,payout_lot_id
            ,payout_location_id
            ,remarks
        from
            order_header
            inner join order_detail on order_header.order_header_id = order_detail.order_header_id
        where
            order_detail_id = '{$orderDetailId}'
        ";
        $res = $gen_db->queryOneRowObject($query);

        // 分割前のオーダーを削除
        self::deleteOrderDetail($orderDetailId);

        // 計画ベースオーダー数を準備
        $planQty = (is_numeric($res->plan_qty) ? $res->plan_qty : 0);
        $handQty = (is_numeric($res->hand_qty) ? $res->hand_qty : 0);

        // 分割後のオーダーを登録
        foreach ($orderArray as $order) {
            // $order[x]
            // x:0    手配先ID
            // x:1    数量
            // x:2    単価

            if ($order[1] != "0") {    // 数量0のときは登録不要
                // ヘッダを登録
                //    ヘッダが既存のとき（「分割元オーダーで、分割行以外にも明細行があるとき」しか
                //    ありえない）は登録不要
                $query = "
                select
                    order_header.order_header_id
                from
                    order_header
                    inner join order_detail on order_header.order_header_id = order_detail.order_header_id
                where
                    order_detail_id = {$orderDetailId} and partner_id = {$order[0]}
                ";

                if ($gen_db->existRecord($query)) {
                    $orderHeaderId = $res->order_header_id;
                } else {
                    $classification = self::getOrderClass(false, $res->item_id, $order[0]);

                    $orderHeaderId = self::entryOrderHeader(
                        $classification
                        , null
                        , null
                        , $res->order_date
                        , $order[0]
                        , $res->remarks_header
                        , $res->worker_id
                        , $res->section_id
                        , $res->delivery_partner_id
                        , null
                    );
                }

                // 計画ベースオーダー数を計算
                $thisPlanQty = ($planQty > $order[1] ? $order[1] : $planQty);
                $planDate = null;
                if ($thisPlanQty > 0) {
                    $planDate = $res->plan_date;
                    $planQty -= $thisPlanQty;
                }
                if ($planQty < 0)
                    $planQty = 0;

                $thisHandQty = ($handQty > $order[1] ? $order[1] : $handQty);
                if ($thisHandQty > 0) {
                    $planDate = $res->plan_date;
                    $handQty -= $thisHandQty;
                }
                if ($handQty < 0)
                    $handQty = 0;

                // 手配単位・単位倍数を取得
                $query = "
                select
                    order_measure
                    ,multiple_of_order_measure
                from
                    item_order_master
                where
                    order_user_id = '{$order[0]}'
                    and item_id = '{$res->item_id}'
                ";
                $mesureObj = $gen_db->queryOneRowObject($query);

                if (!is_numeric($mesureObj->multiple_of_order_measure)) {
                    $mesureObj->multiple_of_order_measure = 1;
                }

                // 明細を登録
                self::entryOrderDetail(
                        null
                        , $orderHeaderId
                        , 1  // line_no
                        , null
                        , $res->seiban
                        , $res->item_id
                        , null
                        , null
                        , $order[2]
                        , null
                        , $order[1]
                        , $res->order_detail_dead_line
                        , ($res->alarm_flag == "t")
                        , $order[0]
                        , $res->payout_location_id
                        , $res->payout_lot_id
                        , $planDate
                        , $thisPlanQty
                        , $thisHandQty
                        , $mesureObj->order_measure
                        , $mesureObj->multiple_of_order_measure
                        , $res->remarks
                        , false
                );
            }
        }
    }

    //************************************************
    // オーダーの削除
    //************************************************
    // トランザクションは呼び出し元で開始しておくこと。
    //
    // オーダー（order_header, detail）の削除
    static function deleteOrder($orderHeaderId)
    {
        global $gen_db;

        // 明細行ごとに削除。
        // ヘッダの削除もこの中でおこなわれる。
        $query = "select order_detail_id from order_detail where order_header_id = '{$orderHeaderId}'";
        $arr = $gen_db->getArray($query);
        foreach ($arr as $row) {
            // オーダー明細（order_detail）の削除。
            self::deleteOrderDetail($row['order_detail_id']);
        }
    }

    // オーダー明細（order_detail）の削除。
    // オーダー内の最後の１行だった場合、ヘッダも削除する。
    // ホントはPhantomの可能性を考えてIsoLevelをSerialize
    //     にすべきだが、そこまでやってない
    static function deleteOrderDetail($orderDetailId)
    {
        global $gen_db;

        // 削除前にorder_header_idを調べておく
        $query = " select order_header_id from order_detail where order_detail_id = '{$orderDetailId}'";
        $orderHeaderId = $gen_db->queryOneValue($query);

        // 計画テーブルのオーダー済み数を更新
        // planのオーダー済み数は、計画画面の表示だけでなくMRPでも使用されているため、この処理は重要。
        // オーダー削除前に実行する。
        self::updatePlanOrderedQty($orderDetailId);

        // 子品目使用予定と支給の削除
        $query = "delete from use_plan where order_header_id = '{$orderHeaderId}'";
        $gen_db->query($query);
        Logic_Inout::deletePayoutInout($orderDetailId);

        // 構成（order_child_item）の削除
        $query = "delete from order_child_item where order_detail_id = '{$orderDetailId}'";
        $gen_db->query($query);

        // 外製工程の外製指示書の削除
        $query = "select order_detail_id from order_detail
            where subcontract_order_process_no in (select order_process_no from order_process where order_detail_id = '{$orderDetailId}')";
        $pArr = $gen_db->getArray($query);
        if (is_array($pArr)) {
            foreach ($pArr as $pRow) {
                self::deleteOrderDetail($pRow['order_detail_id']);
            }
        }

        // 工程（order_process）の削除
        $query = "delete from order_process where order_detail_id = '{$orderDetailId}'";
        $gen_db->query($query);

        // オーダー明細データの削除
        $query = "delete from order_detail where order_detail_id = '{$orderDetailId}'";
        $gen_db->query($query);

        // 削除したデータがその受注の最後の1行だった場合、ヘッダも削除する。
        if (!$gen_db->existRecord("select * from order_detail where order_header_id = '{$orderHeaderId}'")) {
            // ヘッダ削除
            $gen_db->query("delete from order_header where order_header_id = '{$orderHeaderId}'");
        }

        return $orderHeaderId;
    }

    //************************************************
    // オーダー区分番号の取得
    //************************************************
    // オーダー区分（製造指示書/注文書/製造指示書兼注文書。classification）を判断し、
    // order_header.classification に対応した区分番号を返す。
    //
    // 第一引数をtrueの場合、他の引数に関わらず常に「注文書」と判断する。
    // 第一引数がfalseの場合、他の引数により判断する。
    //   この強制判断の仕組みが必要な理由は、注文書のヘッダ発行時点ではitem_idがセットできないため。
    //
    // 引数：
    //      $isOrder        強制的に注文書と判断させる場合はtrue
    //      $itemId            品目ID
    //      $orderUserId    手配先ID
    //
    // 戻り値：（order_header.classification に対応）
    //      0:製造指示書、1:注文書、2:製造指示書兼注文書

    static function getOrderClass($isOrder, $itemId, $orderUserId)
    {
        global $gen_db;

        // 強制的に注文書(1)と判断させる場合
        if ($isOrder)
            return 1;

        // 製造指示書(0)
        if ($orderUserId == "0")
            return 0;

        // 注文書(1)か製造指示書兼注文書(2)
        $query = "select partner_class from item_order_master where item_id = '{$itemId}' and order_user_id = '{$orderUserId}'";

        return ($gen_db->queryOneValue($query) == 0 ? 1 : 2);
    }

    //************************************************
    // オーダー削除時に計画テーブルのオーダー済み数を更新
    //************************************************
    // 製造指示書・注文書共通。オーダー削除の直「前」に実行する。
    // planのオーダー済み数は、計画画面の表示だけでなくMRPでも使用されているため、この処理は重要。

    static function updatePlanOrderedQty($orderDetailId)
    {
        global $gen_db;

        $query = "select plan_date, plan_qty, hand_qty, item_id from order_detail where order_detail_id = '{$orderDetailId}'";
        $obj = $gen_db->queryOneRowObject($query);

        if (Gen_String::isDateString($obj->plan_date)) {
            $date = strtotime($obj->plan_date);
            $year = (int) date('Y', $date);
            $month = (int) date('m', $date);
            $day = (int) date('d', $date);
            $itemId = $obj->item_id;
            if (is_numeric($obj->plan_qty)) {
                $qty = $obj->plan_qty;

                $data = array("order{$day}" => "noquote:case when order{$day} >= {$qty} then order{$day} - {$qty} else 0 end ");
                $where = "plan_year = '{$year}' and plan_month = '{$month}' and item_id = '{$itemId}' and classification = 0";
                $gen_db->update("plan", $data, $where);
            }
            if (is_numeric($obj->hand_qty)) {
                $qty = $obj->hand_qty;

                $data = array("order{$day}" => "noquote:case when order{$day} >= {$qty} then order{$day} - {$qty} else 0 end ");
                $where = "plan_year = '{$year}' and plan_month = '{$month}' and item_id = '{$itemId}' and classification = 3";
                $gen_db->update("plan", $data, $where);
            }
        }
    }

    //************************************************
    // 指定オーダーのオーダー構成リストと構成表が一致しているかどうかを調べる
    //************************************************
    // 2008では、実績登録時の子品目引き落とし等の処理は、処理の時点ではなくオーダー発行時点の構成に
    // もとづいて行うようになった（構成が変わったときの使用予約などの矛盾を避けるため）。
    // オーダー発行時点の構成を保存してあるのが order_child_itemテーブル。
    // そのテーブルの内容と現在の構成表の内容が一致しているかどうか（オーダー発行後に構成表の
    // 変更があったかどうか）を調べ、変更ありなら true、一致なら false を返す。
    //
    // ヘッダに対する明細が1行のみであること（つまり製造指示書、外製）が前提。

    static function isModifiedBom($orderHeaderId)
    {
        global $gen_db;

        // 外製工程の場合、子品目の支給が行われないのでチェックしない
        $query = "select 1 from order_detail where order_header_id = '{$orderHeaderId}' and subcontract_order_process_no is not null";
        if ($gen_db->existRecord($query)) {
            return false;
        }

        // temp_real_child_item テーブル（ダミー品目をスキップした構成表）を作成
        Logic_Bom::createTempRealChildItemTable($gen_db->queryOneValue("select item_id from order_detail where order_header_id = '{$orderHeaderId}'"));

        // 不一致レコードを抽出
        $query = "
        select
            order_child_item.child_item_id
        from
            order_child_item
            inner join order_detail on order_child_item.order_detail_id = order_detail.order_detail_id
            left join temp_real_child_item on order_child_item.child_item_id = temp_real_child_item.child_item_id
                and order_detail.item_id = temp_real_child_item.item_id
        where
            order_detail.order_header_id = '{$orderHeaderId}'
            and order_child_item.quantity <> coalesce(temp_real_child_item.quantity,0)

        UNION

        /* 上のSQLでは「temp_real_child_itemにはあるがorder_child_itemにはない」パターンが拾えないのでこちらで取得 */
        select
            order_child_item.child_item_id
        from
            temp_real_child_item
            left join order_child_item on temp_real_child_item.child_item_id = order_child_item.child_item_id
            	and order_child_item.order_detail_id in (select order_detail_id from order_detail where order_header_id = '{$orderHeaderId}')
        where
            temp_real_child_item.item_id in (select item_id from order_detail where order_header_id = '{$orderHeaderId}')
            and order_child_item.order_detail_id is null
        ";
        return $gen_db->existRecord($query);
    }

    //************************************************
    // 印刷済みフラグのセット
    //************************************************

    static function setOrderPrintedFlag($idArr, $isSet)
    {
        global $gen_db;

        $idWhere = join(",", $idArr);
        if ($idWhere == "")
            return;

        $query = "
        update
            order_header
        set
            order_printed_flag = " . ($isSet ? 'true' : 'false') . "
            ,record_updater = '" . $_SESSION['user_name'] . "'
            ,record_update_date = '" . date('Y-m-d H:i:s') . "'
            ,record_update_func = '" . __CLASS__ . "::" . __FUNCTION__ . "'
        where
            order_header_id in ({$idWhere})
        ";
        $gen_db->query($query);
    }

    //************************************************
    // 印刷済みフラグのセット (注文受信)
    //************************************************

    static function setPartnerOrderPrintedFlag($idArr, $isSet)
    {
        global $gen_db;

        $idWhere = join(",", $idArr);
        if ($idWhere == "")
            return;

        $query = "
        update
            order_header
        set
            partner_order_printed_flag = " . ($isSet ? 'true' : 'false') . "
            ,record_updater = '" . $_SESSION['user_name'] . "'
            ,record_update_date = '" . date('Y-m-d H:i:s') . "'
            ,record_update_func = '" . __CLASS__ . "::" . __FUNCTION__ . "'
        where
            order_header_id in ({$idWhere})
        ";
        $gen_db->query($query);
    }

    //************************************************
    // 指定されたオーダーのオーダー日を返す
    //************************************************

    static function getOrderDateByTranId($orderHeaderId)
    {
        $arr = self::getOrderData($orderHeaderId, false);
        return $arr[0]['order_date'];
    }

    static function getOrderDateByDetailId($orderDetailId)
    {
        $arr = self::getOrderData($orderDetailId, true);
        return $arr[0]['order_date'];
    }

    //************************************************
    // 指定されたオーダーの注文書番号(order_id_for_user)を返す
    //************************************************

    static function getOrderIdForUserByTranId($orderHeaderId)
    {
        $arr = self::getOrderData($orderHeaderId, false);
        return $arr[0]['order_id_for_user'];
    }

    static function getOrderIdForUserByDetailId($orderDetailId)
    {
        $arr = self::getOrderData($orderDetailId, true);
        return $arr[0]['order_id_for_user'];
    }

    //************************************************
    // 指定されたオーダーNoのID(order_detail_id)を返す
    //************************************************

    static function getDetailIdByOrderNo($orderNo, $whereAdd = "")
    {
        global $gen_db;

        if ($orderNo == "")
            return null;

        $query = "
        select
            order_detail_id
        from
            order_header
            inner join order_detail on order_header.order_header_id = order_detail.order_header_id
        where
            order_no = '{$orderNo}'" . ($whereAdd == "" ? "" : " and {$whereAdd}") . "
        ";
        return $gen_db->queryOneValue($query);
    }

    //************************************************
    // 指定されたオーダーのデータを配列で返す
    //************************************************

    static function getOrderData($id, $isDetailId)
    {
        global $gen_db;

        $query = "
        select
            *
        from
            order_header
            inner join order_detail on order_detail.order_header_id = order_header.order_header_id
        where
        ";
        if ($isDetailId) {
            $query .= "order_detail_id = '{$id}'";
        } else {
            $query .= "order_header.order_header_id = '{$id}'";
        }
        return $gen_db->getArray($query);
    }

    //************************************************
    // 取引先のオーダーが存在するかどうかを返す
    //************************************************

    static function existOrder($partnerId)
    {
        global $gen_db;

        $query = "select partner_id from order_header where partner_id = {$partnerId}";
        return $gen_db->existRecord($query);
    }

}