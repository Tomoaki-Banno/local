<?php

class Logic_Delivery
{

    // デッドロック防止のため、各リソースにアクセスする順を統一する
    //   1. delivery 2. in_out 3. use_plan 4. received_detail(完了フラグ)

    //************************************************
    // 納品ヘッダの登録
    //************************************************
    //  更新の際は、すでに旧データが削除済みであることが前提
    static function entryDeliveryHeader($deliveryHeaderId, $deliveryNo, $deliveryDate, $inspectionDate, $receivedDetailId
            , $deliveryCustomerId, $currencyRate, $personInCharge, $remarksHeader, $remarksHeader2, $remarksHeader3)
    {
        global $gen_db;

        // トランザクション開始
        $gen_db->begin();

        // 請求先情報取得
        $query = "
        select
            received_header.customer_id
            ,coalesce(customer_master.bill_customer_id,customer_master.customer_id) as bill_customer_id
            ,received_header.delivery_customer_id
            ,t_bill_customer.currency_id as currency_id
            ,t_bill_customer.rounding as rounding
            ,t_bill_customer.precision as precision
            ,t_bill_customer.tax_category as tax_category
            ,t_bill_customer.bill_pattern as bill_pattern
            ,receivable_report_timing
        from
            received_detail
            inner join received_header on received_header.received_header_id = received_detail.received_header_id
            inner join customer_master on received_header.customer_id = customer_master.customer_id
            inner join customer_master as t_bill_customer on coalesce(customer_master.bill_customer_id,customer_master.customer_id) = t_bill_customer.customer_id
            left join company_master on 1=1
        where
            received_detail.received_detail_id = '{$receivedDetailId}'
        ";
        $res = $gen_db->queryOneRowObject($query);

        //  納品書番号の取得
        $deliveryNo = Logic_NumberTable::getMonthlyAutoNumber(GEN_PREFIX_DELIVERY_NUMBER, "delivery_no", 5, $deliveryNo);

        // 取引通貨レート
        $isCurrency = false;    // 基軸通貨の場合
        if (isset($res->currency_id) && is_numeric($res->currency_id)) {
            // 外貨の場合（取引通貨レートの計算）
            $isCurrency = true;
            // 取引通貨レートが指定されていない場合はマスタ参照
            if (!isset($currencyRate) || !is_numeric($currencyRate)) {
                // 納品日レート
                $currencyRate = self::getCurrencyRate($res->currency_id, $deliveryDate);
                if (!isset($currencyRate) || !is_numeric($currencyRate))
                    $currencyRate = 1;

                // 検収日レート
                if (isset($inspectionDate) && Gen_String::isDateString($inspectionDate) && $res->receivable_report_timing == "1") {
                    $insRate = self::getCurrencyRate($res->currency_id, $inspectionDate);
                    if (isset($insRate) && is_numeric($insRate)) {
                        $currencyRate = $insRate;
                    } else {
                        $currencyRate = 1;
                    }
                }
            }
        }

        // 納品ヘッダ（delivery_header）を登録
        // idは、更新時再登録の場合は指定されているが、新規の場合はnull
        $data = array(
            'delivery_no' => $deliveryNo,
            'delivery_date' => $deliveryDate,
            'inspection_date' => ($inspectionDate == '' ? null : $inspectionDate),
            'person_in_charge' => $personInCharge,
            'remarks_header' => $remarksHeader,
            'remarks_header_2' => $remarksHeader2,
            'remarks_header_3' => $remarksHeader3,
            'receivable_report_timing' => $res->receivable_report_timing,
            'customer_id' => $res->customer_id,
            'delivery_customer_id' => (isset($deliveryCustomerId) && is_numeric($deliveryCustomerId) ? $deliveryCustomerId : $res->delivery_customer_id),
            'bill_customer_id' => $res->bill_customer_id,
            'rounding' => $res->rounding,
            'precision' => $res->precision,
            'tax_category' => $res->tax_category,
            'bill_pattern' => $res->bill_pattern,
            'foreign_currency_id' => ($isCurrency ? $res->currency_id : null),
            'foreign_currency_rate' => ($isCurrency ? $currencyRate : null),
        );
        if (isset($deliveryHeaderId) && is_numeric($deliveryHeaderId)) {
            $data['delivery_header_id'] = $deliveryHeaderId;
        }

        $gen_db->insert("delivery_header", $data);

        // いま登録した納品IDを確認
        if (!isset($deliveryHeaderId) || !is_numeric($deliveryHeaderId)) {
            $deliveryHeaderId = $gen_db->getSequence("delivery_header_delivery_header_id_seq");
        }

        // コミット
        $gen_db->commit();

        return $deliveryHeaderId;
    }

    //************************************************
    // 納品明細データの登録
    //************************************************
    //  更新の際は、すでに旧データが削除済みであることが前提
    //  フリー分と製番分はこのfuncの中で振り分ける。
    //  また、フリー分の納品引当処理もこのfuncの中で行うようになった。
    static function entryDeliveryDetail($deliveryDetailId, $deliveryHeaderId, $lineNo, $receivedDetailId
            , $deliveryQuantity, $deliveryPrice, $taxRate, $salesBaseCost, $remarks, $location_id, $useLotNo, $completed)
    {
        global $gen_db;

        // トランザクション開始
        $gen_db->begin();

        //    製番品目は製番在庫から、MRP品目はフリー在庫から納品される動作に戻した。
        //    ※ 以前とは異なり在庫数不足でも納品登録できるので、納品前に製番引当しておく必要はない
        //    詳細は下記を参照。
        //
        //    ---------------------------
        //    ag.cgi?page=ProjectDocView&pid=676&did=55288
        //
        //    現在の08iの仕様では、製番品目の納品時に製番在庫が不足すると自動的にフリー在庫（製番「なし」の在庫）
        //    からの納品が行われます。フリー在庫も不足する場合は、フリー在庫がマイナスになります。
        //    フリー在庫から直接納品が行われた場合、在庫推移履歴上の納品には製番が表示されません。
        //    （納品登録時に item_in_out.seiban が記録されない）
        //
        //    若干不自然にも思える動作ですが、こうなったのには歴史的な経緯があります。
        //
        //    昔のアクセス版や07iでは、納品登録時に製番在庫とフリー在庫のそれぞれから何個ずつ納品するかを指定する
        //    ようになっていました。
        //
        //    これが面倒だったため、08iになったときに仕様を変更し、製番品目は製番在庫から、MRP品目はフリー在庫から
        //    自動的に納品されるようになりました。
        //    この変更により納品登録の手間は減りましたが、その一方で07iのときに可能だった「製番品目をフリー在庫か
        //    ら直接納品する」という登録が行えなくなりました。つまり、製番在庫が足りなければまず製番引当登録を
        //    行ってから納品登録する必要があったわけです。
        //
        //    これが不便だという指摘があったため、途中で仕様を変更し、先ほど書いたように「製番在庫が足りなければ
        //    自動的にフリー在庫から納品する」という動作になって今に至っています。
        //
        //    現在の仕様では納品登録時に製番在庫とフリー在庫を登録者が指定する必要がなく、なおかつ製番引当せずに
        //    納品を行うことが可能であり便利です。しかしその一方で、今回指摘があったような現象、つまり「納品登録
        //    時に製番在庫が足りずフリー在庫から納品が行われた場合、製番別の在庫数や推移が不自然になる」という
        //    状況が発生します。
        //
        //    この点について今回再検討を行い、製品版 rev.20081003 で仕様を再変更することにしました。
        //    今回バージョンから、製番品目は製番在庫から、MRP品目はフリー在庫から納品されるようになります。
        //    製番在庫が不足の場合、これまでのようにフリー在庫から納品するのではなく、製番在庫がマイナスになります。
        //    これにより、製番在庫数や製番品目の在庫推移が正確に表示されるようになります。
        //    その一方でこれまでにはなかった「納品登録により製番在庫がマイナスになってしまう」という状況が発生する
        //    可能性があるわけですが、製番在庫はたいていの場合には最終的につじつまがあうもの（もしくは製番引当に
        //    よりつじつまを合わせなくてはならない。それにより原価計算が正確になるという利点もある）なので問題は
        //    小さいと判断しました。
        //
        //    基本的には08i初期版の動作に戻ったわけですが、当時と違うのは納品登録時に製番在庫が不足していた場合
        //    でも製番引当せずに納品できるという点です。
        //
        //    ---------------------------
        //    15iではロット管理が導入された。
        //    ロット品目は製番在庫から出庫する。

        //------------------------------------------------
        //  受注情報取得
        //------------------------------------------------
        $query = "
        select
            received_detail.seiban
            ,received_detail.item_id
            ,case when foreign_currency_id is null then product_price else foreign_currency_product_price end as product_price
            ,item_master.order_class
            ,coalesce(item_master.tax_class,0) as tax_class
            ,item_master.tax_rate
        from
            received_detail
            inner join received_header on received_header.received_header_id = received_detail.received_header_id
            left join item_master on received_detail.item_id = item_master.item_id
            left join (select customer_id as cid, currency_id, rounding from customer_master) as t_customer on received_header.customer_id = t_customer.cid
        where
            received_detail.received_detail_id = '{$receivedDetailId}'
        ";
        $res = $gen_db->queryOneRowObject($query);
        $seiban = $res->seiban;
        $itemId = $res->item_id;
        $productPrice = $res->product_price;
        $taxClass = $res->tax_class;
        $itemTaxRate = $res->tax_rate;
        if ($res->order_class == "0" || $res->order_class == "2") {
            // 製番・ロット品目
            $seibanStockQuantity = $deliveryQuantity;
            $freeStockQuantity = 0;
        } else {
            // MRP品目
            $seibanStockQuantity = 0;
            $freeStockQuantity = $deliveryQuantity;
        }

        // 品目毎の税率の取得（税率非指定時）
        if (!isset($taxRate) || !is_numeric($taxRate)) {
            if ($taxClass == "1") {
                $taxRate = 0;     // 非課税
            } else {
                $taxRate = $itemTaxRate;
            }
        }

        //------------------------------------------------
        //  納品ヘッダー情報取得
        //------------------------------------------------
        // 納品ヘッダーに登録されている情報を取得
        $query = "
        select
            delivery_date
            ,inspection_date
            ,receivable_report_timing
            ,bill_customer_id
            ,coalesce(rounding,'round') as rounding
            ,precision
            ,tax_category
            ,bill_pattern
            ,foreign_currency_id
            ,foreign_currency_rate
        from
            delivery_header
        where
            delivery_header_id = '{$deliveryHeaderId}'
        ";
        $res = $gen_db->queryOneRowObject($query);
        $deliveryDate = $res->delivery_date;
        $inspectionDate = $res->inspection_date;
        $timing = $res->receivable_report_timing;
        $billCustomerId = $res->bill_customer_id;
        $rounding = $res->rounding;
        $taxCategory = $res->tax_category;
        $currencyId = $res->foreign_currency_id;
        $currencyRate = $res->foreign_currency_rate;
        // 納品書・請求書単位の税計算は各明細の税率でグループ化して計算される
        if (!isset($taxRate) || !is_numeric($taxRate)) {
            // 消費税率マスタの税率を取得（売上基準日の税率を取得）
            $taxRate = Logic_Tax::getTaxRate(isset($inspectionDate) && Gen_String::isDateString($inspectionDate) && $timing == "1" ? $inspectionDate : $deliveryDate);
        }

        //------------------------------------------------
        //  在庫自動引当処理
        //------------------------------------------------
        // 完了フラグがオフになっていたときの対応
        $doNotCompletedAdjust = false;
        if ($completed != "true")
            $doNotCompletedAdjust = true;

        // フリー在庫納品分の自動引当。これまでの納品数と今回納品数にもとづいて受注引当を行う。
        Logic_Reserve::reserveByDeliveryQuantity($receivedDetailId, $freeStockQuantity, $deliveryDate, $doNotCompletedAdjust);

        //------------------------------------------------
        //  金額計算・引当計算・外貨計算
        //------------------------------------------------
        // 金額計算（明細行毎に請求先の設定で丸められる）
        $price = (is_numeric($deliveryPrice) ? $deliveryPrice : $productPrice);
        if ($currencyId == null) {
            // 基軸通貨の場合
            $amount = Logic_Customer::round(Gen_Math::mul(Gen_Math::add($freeStockQuantity, $seibanStockQuantity), $price), $billCustomerId);
            $salesBaseCostTotal = Logic_Customer::round(Gen_Math::mul(Gen_Math::add($freeStockQuantity, $seibanStockQuantity), $salesBaseCost), $billCustomerId);
            // 税計算（納品書明細単位のみ）
            if ($taxCategory == 2) {
                $tax = Logic_Customer::round(Gen_Math::mul($amount, Gen_Math::div($taxRate, 100)), $billCustomerId);
            }
        } else {
            // 外貨の場合（取引通貨レートの計算）
            $taxRate = 0;   // ※ 外貨の時は税計算の対象から除外する

            // 入力された単価は外貨単価として登録する
            $foreignCurrencyPrice = $price;         // 入力された単価（外貨単価）は丸めない
            $foreignCurrencyAmount = Logic_Customer::round(Gen_Math::mul(Gen_Math::add($freeStockQuantity, $seibanStockQuantity), $price), $billCustomerId);
            $foreignCurrencyBaseCost = $salesBaseCost;
            $foreignCurrencyBaseCostTotal = Logic_Customer::round(Gen_Math::mul(Gen_Math::add($freeStockQuantity, $seibanStockQuantity), $salesBaseCost), $billCustomerId);
            // 税計算（納品書明細単位のみ）
            if ($taxCategory == 2) {
                $foreignCurrencyTax = 0; // 上で $taxRate を0にしているので常に0
                //$foreignCurrencyTax = Logic_Customer::round(Gen_Math::mul($foreignCurrencyAmount, Gen_Math::div($taxRate, 100)), $billCustomerId);
            }

            // 基軸通貨に換算（請求先の設定で計算）
            // 小数点以下桁数は、単価は GEN_FOREIGN_CURRENCY_PRECISION、金額は取引先マスタの値
            $price = Gen_Math::round(Gen_Math::mul($price, $currencyRate), $rounding, GEN_FOREIGN_CURRENCY_PRECISION);
            $amount = Logic_Customer::round(Gen_Math::mul(Gen_Math::add($freeStockQuantity, $seibanStockQuantity), $price), $billCustomerId);
            $salesBaseCost = Gen_Math::round(Gen_Math::mul($salesBaseCost, $currencyRate), $rounding, GEN_FOREIGN_CURRENCY_PRECISION);
            $salesBaseCostTotal = Logic_Customer::round(Gen_Math::mul(Gen_Math::add($freeStockQuantity, $seibanStockQuantity), $salesBaseCost), $billCustomerId);
            // 税計算（納品書明細単位のみ）
            if ($taxCategory == 2) {
                $tax = 0; // 上で $taxRate を0にしているので常に0
                //$tax = Logic_Customer::round(Gen_Math::mul($amount, Gen_Math::div($taxRate, 100)), $billCustomerId);
            }
        }

        // location_id = -1 のとき、品目マスタの標準ロケ（受入）IDに変換する。
        if ($location_id == -1) {
            $query = "select default_location_id_3 from item_master
                        where item_id = '{$itemId}'";
            $location_id = $gen_db->queryOneValue($query);
            if (!is_numeric($location_id))
                $location_id = 0;
        }

        //------------------------------------------------------
        // 1. 納品データ（delivery_detail）を登録
        //------------------------------------------------------
        // idは、更新時再登録の場合は指定されているが、新規の場合はnull
        $data = array(
            'received_detail_id' => $receivedDetailId,
            'delivery_header_id' => $deliveryHeaderId,
            'line_no' => $lineNo,
            'delivery_quantity' => Gen_Math::add($freeStockQuantity, $seibanStockQuantity),
            'seiban_stock_quantity' => $seibanStockQuantity,
            'free_stock_quantity' => $freeStockQuantity,
            'delivery_price' => $price,
            'sales_base_cost' => $salesBaseCost,
            'sales_base_cost_total' => $salesBaseCostTotal,
            'location_id' => $location_id,
            'use_lot_no' => $useLotNo,
            'remarks' => $remarks,
            'delivery_amount' => $amount,
            'delivery_tax' => (isset($tax) && is_numeric($tax) ? $tax : null),
            'tax_rate' => $taxRate,
            'tax_class' => $taxClass
        );
        if (isset($deliveryDetailId) && is_numeric($deliveryDetailId)) {
            $data['delivery_detail_id'] = $deliveryDetailId;
        }
        if ($currencyId != null) {
            // 取引通貨処理（基軸通貨以外のとき）
            $data['foreign_currency_delivery_price'] = $foreignCurrencyPrice;
            $data['foreign_currency_delivery_amount'] = $foreignCurrencyAmount;
            $data['foreign_currency_delivery_tax'] = (isset($foreignCurrencyTax) && is_numeric($foreignCurrencyTax) ? $foreignCurrencyTax : null);
            $data['foreign_currency_sales_base_cost'] = $foreignCurrencyBaseCost;
            $data['foreign_currency_sales_base_cost_total'] = $foreignCurrencyBaseCostTotal;
        } else {
            // 取引通貨処理（基軸通貨のとき）
            $data['foreign_currency_delivery_price'] = null;
            $data['foreign_currency_delivery_amount'] = null;
            $data['foreign_currency_delivery_tax'] = null;
            $data['foreign_currency_sales_base_cost'] = null;
            $data['foreign_currency_sales_base_cost_total'] = null;
        }

        $gen_db->insert("delivery_detail", $data);

        //------------------------------------------------
        //  2. 入出庫データ（in_out）を登録
        //------------------------------------------------
        // いま登録した納品IDを確認
        if (!isset($deliveryDetailId) || !is_numeric($deliveryDetailId)) {
            $deliveryDetailId = $gen_db->getSequence("delivery_detail_delivery_detail_id_seq");
        }

        // ダミー品目かどうかを判断
        $query = "select case when dummy_item then 1 else 0 end from item_master where item_id = '{$itemId}'";
        $isDummyItem = ($gen_db->queryOneValue($query) == '1');

        // 出庫登録
        if ($isDummyItem) {
            // ダミー品目。
            // その品目自体は引き落とさず、その子品目を引き落とす。
            // 13iまでは納品時点の構成表にもとづいて処理していたが、15iでは受注時点でダミー品目の子品目使用予約を登録する
            // ようになったことに伴い、受注時点の構成（received_dummy_child_item）にもとづくように変更した。
            $query = "select child_item_id, quantity, order_class from received_dummy_child_item
                    left join item_master on received_dummy_child_item.child_item_id = item_master.item_id
                    where received_detail_id_for_dummy = '{$receivedDetailId}'";
            $arr = $gen_db->getArray($query);
            if (!$arr) {
                // 13iで受注登録されたデータ（ダミー品目なのにreceived_dummy_child_itemが登録されていない）

                // temp_real_child_item テーブル（ダミー品目をスキップした構成表）を作成
                Logic_Bom::createTempRealChildItemTable($itemId);
                $query = "select child_item_id, quantity, order_class from temp_real_child_item
                        left join item_master on temp_real_child_item.child_item_id = item_master.item_id";
                $arr = $gen_db->getArray($query);
            }
            if ($arr) {
                foreach ($arr as $row) {
                    Logic_Inout::entryInout(
                        $deliveryDate
                        , $row['child_item_id']
                        , ($row['order_class'] == "1" ? "" : $seiban)
                        , $location_id
                        , $useLotNo
                        , Gen_Math::mul($deliveryQuantity, $row['quantity'])
                        , 0
                        , "delivery"
                        , "delivery_id"
                        , $deliveryDetailId
                    );
                }
            }
        } else {
            // 通常品目
            // 製番在庫からの納品
            if ($seibanStockQuantity <> 0) {
                Logic_Inout::entryInout(
                    $deliveryDate
                    , $itemId
                    , $seiban
                    , $location_id
                    , $useLotNo
                    , $seibanStockQuantity
                    , 0
                    , "delivery"
                    , "delivery_id"
                    , $deliveryDetailId
                );
            }

            // フリー在庫からの納品
            if ($freeStockQuantity <> 0) {
                Logic_Inout::entryInout(
                    $deliveryDate
                    , $itemId
                    , ""
                    , $location_id
                    , $useLotNo
                    , $freeStockQuantity
                    , 0
                    , "delivery"
                    , "delivery_id"
                    , $deliveryDetailId
                );
            }
        }

        //------------------------------------------------
        //  3. 引当（use_plan）の解除（フリー在庫のみ）
        //------------------------------------------------
        //  納品されたら在庫数が減るので、そのぶん引当数も減らす必要がある。
        //  引当データは複数レコードが存在する可能性があるため、UPDATEではなく、マイナスINSERTする。
        //
        //  フリー在庫引当（製番フィールドが登録されている引当）が存在する場合のみ処理を行う。
        //  引当（use_plan）が関係するのはフリー在庫のみであるため。製番在庫に引当はない。
        //
        //  差し引きレコードの日付を納品日から納期($useDate)に変更した。
        //  引当レコードの日付が納期であるため。差し引きレコードと引当レコードの日付がズレていると、
        //  その間は引当数が不正となってしまう。

        $query = "select use_date from use_plan where received_detail_id = '{$receivedDetailId}' and item_id = '{$itemId}' ";
        $useDate = $gen_db->queryOneValue($query);

        if ($useDate != "") {
            $data = array(
                'received_detail_id' => $receivedDetailId,
                'item_id' => $itemId,
                'use_date' => $useDate,
                'quantity' => (-$freeStockQuantity),
            );
            $gen_db->insert("use_plan", $data);
        }

        //------------------------------------------------
        //  4. 受注完了フラグ関連の処理
        //------------------------------------------------
        self::completedOperation($deliveryDetailId, $completed);

        //------------------------------------------------
        //  5. ダミー品目の子品目使用予定を再計算
        //------------------------------------------------
        self::calcUsePlanForDummy($receivedDetailId);

        // コミット
        $gen_db->commit();

        return $deliveryDetailId;
    }

    // 納品登録時の受注完了フラグ関連の処理
    //  AjaxCompletedFlagRegist からも呼ばれる
    static function completedOperation($deliveryDetailId, $completed)
    {
        global $gen_db;

        $query = "
            select
                delivery_detail.received_detail_id
                ,item_id
            from
                delivery_detail
            inner
                join received_detail on delivery_detail.received_detail_id = received_detail.received_detail_id
            where
                delivery_detail_id = '{$deliveryDetailId}'
        ";
        $obj = $gen_db->queryOneRowObject($query);
        $receivedDetailId = $obj->received_detail_id;
        $itemId = $obj->item_id;

        $query = "select use_date from use_plan where received_detail_id = '{$receivedDetailId}' and item_id = '{$itemId}' ";
        $useDate = $gen_db->queryOneValue($query);

        //------------------------------------------------
        //  完了時の引当解除
        //------------------------------------------------
        // 完了扱いのときは、引当を完全に解除する（引当数が0になるように調整レコードを入れる）。
        // ※ $completedがオンになっているのは、登録画面上でチェックがオンになっていた
        //    とき。チェックオフなのに実績登録時に計画数量達成のために強制的に完了状態
        //    になったときは、ここは実行されない。しかしその場合は完了調整は不要なので問題ない。
        if ($completed == "true" && $useDate != "") {
            Logic_Reserve::entryCompletedAdjust($receivedDetailId, $itemId, $deliveryDetailId, date('Y-m-d', strtotime($useDate)));
        }

        //------------------------------------------------
        //  受注完了フラグの更新
        //------------------------------------------------
        $afterCompleted = Logic_Received::updateCompletedFlag($receivedDetailId, $completed);

        //------------------------------------------------
        //  受注完了フラグ更新後の引当（use_plan）の解除
        //------------------------------------------------
        if ($completed != "true" && $afterCompleted == "true") {
            Logic_Reserve::entryCompletedAdjust($receivedDetailId, $itemId, $deliveryDetailId, date('Y-m-d', strtotime($useDate)));
        }
    }

    //************************************************
    //  納品書データの更新処理
    //************************************************

    static function updateDeliveryNote($deliveryHeaderId)
    {
        global $gen_db;

        // 納品書の合計金額を記録する
        $query = "
        update
            delivery_header
        set
            delivery_note_amount = t_delivery_note.delivery_note_amount
            ,delivery_note_tax = t_delivery_note.delivery_note_tax
            ,foreign_currency_delivery_note_amount = t_delivery_note.foreign_currency_delivery_note_amount
            ,foreign_currency_delivery_note_tax = t_delivery_note.foreign_currency_delivery_note_tax

            ,record_updater = '" . $_SESSION['user_name'] . "'
            ,record_update_date = '" . date('Y-m-d H:i:s') . "'
            ,record_update_func = '" . __CLASS__ . "::" . __FUNCTION__ . "'
        from
            (select
                delivery_header.delivery_header_id
                ,sum(delivery_amount) as delivery_note_amount
                ,gen_round_precision(
                    sum(
                        case delivery_header.tax_category
                            when 1 then     /* 1: 納品書単位 */
                                delivery_detail.delivery_amount * coalesce(delivery_detail.tax_rate,0) / 100.00
                            when 2 then     /* 2: 納品明細単位 */
                                delivery_detail.delivery_tax
                            else            /* 0: 請求書単位 */
                                0
                        end
                    ),
                    max(delivery_header.rounding), max(delivery_header.precision)) as delivery_note_tax
                ,sum(delivery_detail.foreign_currency_delivery_amount) as foreign_currency_delivery_note_amount
                ,gen_round_precision(
                    sum(
                        case delivery_header.tax_category
                            when 1 then     /* 1: 納品書単位 */
                                delivery_detail.foreign_currency_delivery_amount * coalesce(delivery_detail.tax_rate,0) / 100.00
                            when 2 then     /* 2: 納品明細単位 */
                                delivery_detail.foreign_currency_delivery_tax
                            else            /* 0: 請求書単位 */
                                0
                        end
                    ),
                    max(delivery_header.rounding), max(delivery_header.precision)) as foreign_currency_delivery_note_tax
            from
                delivery_detail
                inner join delivery_header on delivery_detail.delivery_header_id = delivery_header.delivery_header_id
                inner join received_detail on delivery_detail.received_detail_id = received_detail.received_detail_id
                inner join received_header on received_header.received_header_id = received_detail.received_header_id
                inner join customer_master on received_header.customer_id = customer_master.customer_id
            where
                delivery_header.delivery_header_id = '{$deliveryHeaderId}'
            group by
                delivery_header.delivery_header_id
            ) as t_delivery_note
        where
            delivery_header.delivery_header_id = t_delivery_note.delivery_header_id
            and delivery_header.delivery_header_id = '{$deliveryHeaderId}'
        ";
        $gen_db->query($query);
    }

    //************************************************
    // 検収日の更新処理
    //************************************************

    static function updateInspectionDate($where, $inspectionDate)
    {
        global $gen_db;

        // トランザクション開始
        $gen_db->begin();

        // 各種情報
        $query = "
        select
            delivery_header.delivery_header_id
            ,delivery_header.receivable_report_timing
            ,delivery_header.bill_customer_id
            ,delivery_header.foreign_currency_id
            ,coalesce(delivery_header.rounding,'round') as rounding
            ,delivery_header.tax_category
            ,delivery_detail.delivery_detail_id
            ,delivery_header.delivery_date
            ,delivery_detail.seiban_stock_quantity
            ,delivery_detail.free_stock_quantity
            ,delivery_detail.tax_rate
            ,delivery_detail.foreign_currency_delivery_price
            ,delivery_detail.foreign_currency_sales_base_cost
        from
            delivery_header
            inner join delivery_detail on delivery_header.delivery_header_id = delivery_detail.delivery_header_id
            inner join received_detail on delivery_detail.received_detail_id = received_detail.received_detail_id
            inner join received_header on received_detail.received_header_id = received_header.received_header_id
        where
            {$where}
        ";
        $arr = $gen_db->getArray($query);

        if (is_array($arr)) {
            foreach ($arr as $row) {
                // 検収日
                $headerData = array(
                    "inspection_date" => ($inspectionDate == '' ? null : $inspectionDate),
                );

                // 金額計算（外貨の場合のみ実行）
                $detailData = array();
                if ($row['foreign_currency_id'] != null) {
                    // 納品日レート
                    $currencyRate = self::getCurrencyRate($row['foreign_currency_id'], $row['delivery_date']);
                    if (!isset($currencyRate) || !is_numeric($currencyRate))
                        $currencyRate = 1;

                    // 検収日レート
                    if (isset($inspectionDate) && Gen_String::isDateString($inspectionDate) && $row['receivable_report_timing'] == "1") {
                        $insRate = self::getCurrencyRate($row['foreign_currency_id'], $inspectionDate);
                        if (isset($insRate) && is_numeric($insRate)) {
                            $currencyRate = $insRate;
                        } else {
                            $currencyRate = 1;
                        }
                    }

                    // 基軸通貨に換算（請求先の設定で計算）
                    $price = Gen_Math::round(Gen_Math::mul($row['foreign_currency_delivery_price'], $currencyRate), $row['rounding'], GEN_FOREIGN_CURRENCY_PRECISION);
                    $amount = Logic_Customer::round(Gen_Math::mul(Gen_Math::add($row['seiban_stock_quantity'], $row['free_stock_quantity']), $price), $row['bill_customer_id']);
                    $salesBaseCost = Gen_Math::round(Gen_Math::mul($row['foreign_currency_sales_base_cost'], $currencyRate), $row['rounding'], GEN_FOREIGN_CURRENCY_PRECISION);
                    $salesBaseCostTotal = Logic_Customer::round(Gen_Math::mul(Gen_Math::add($row['seiban_stock_quantity'], $row['free_stock_quantity']), $salesBaseCost), $row['bill_customer_id']);
                    // 税計算（納品書明細単位のみ）
                    if ($row['tax_category'] == 2) {
                        $tax = Logic_Customer::round(Gen_Math::mul($amount, Gen_Math::div($row['tax_rate'], 100)), $row['bill_customer_id']);
                    }

                    // 更新情報
                    $headerData['foreign_currency_rate'] = $currencyRate;   // ヘッダーデータ
                    $detailData['delivery_price'] = $price;
                    $detailData['delivery_amount'] = $amount;
                    $detailData['delivery_tax'] = (isset($tax) && is_numeric($tax) ? $tax : null);
                    $detailData['sales_base_cost'] = $salesBaseCost;
                    $detailData['sales_base_cost_total'] = $salesBaseCostTotal;
                }

                // headerデータ更新実行
                $where = "delivery_header_id = '{$row['delivery_header_id']}'";
                $gen_db->update("delivery_header", $headerData, $where);

                // detailデータ更新実行
                if (count($detailData) > 0) {
                    $where = "delivery_detail_id = '{$row['delivery_detail_id']}'";
                    $gen_db->update("delivery_detail", $detailData, $where);
                }
            }
        }

        // コミット
        $gen_db->commit();
    }

    //************************************************
    //  納品データ（deilvery_tran, delivery_detail）の削除
    //************************************************

    static function deleteDelivery($deliveryHeaderId)
    {
        global $gen_db;

        // トランザクション開始
        $gen_db->begin();

        // 明細行（delivery_detail）ごとに削除。
        // ヘッダ（delivery_header）の削除もこの中でおこなわれる。
        $res = $gen_db->getArray("select delivery_detail_id from delivery_detail where delivery_header_id = '{$deliveryHeaderId}'");
        if (is_array($res)) {
            foreach ($res as $row) {
                self::deleteDeliveryDetail($row['delivery_detail_id']);
            }
        }

        // コミット
        $gen_db->commit();
    }

    //************************************************
    //  納品明細（delivery_detail）の削除
    //************************************************
    // 削除したデータがその納品の最後の1行だった場合、ヘッダ(delivery_header)も削除する。
    static function deleteDeliveryDetail($deliveryDetailId)
    {
        global $gen_db;

        // トランザクション開始
        $gen_db->begin();

        // 1,3,5番の処理のために、納品データ削除前にとっておく
        // （関連する受注データがまだ削除されていないことが前提。seibanを取る必要があるので）
        $query = "
        select
            delivery_detail.delivery_header_id
            ,seiban
            ,item_id
            ,delivery_date
            ,free_stock_quantity
            ,delivery_detail.received_detail_id
            ,dead_line
            ,delivery_quantity
        from
            delivery_detail
            inner join delivery_header on delivery_detail.delivery_header_id = delivery_header.delivery_header_id
            inner join received_detail on delivery_detail.received_detail_id = received_detail.received_detail_id
        where
            delivery_detail.delivery_detail_id = '{$deliveryDetailId}'
        ";
        $deliveryData = $gen_db->queryOneRowObject($query);


        //------------------------------------------------------
        // 1. 納品データ（delivery_detail, delivery_header）の削除
        //------------------------------------------------------
        $query = "delete from delivery_detail where delivery_detail_id = '{$deliveryDetailId}'";
        $gen_db->query($query);

        // 削除したデータがその納品の最後の1行だった場合、ヘッダも削除する。
        $returnId = null;
        if (!$gen_db->existRecord("select * from delivery_detail where delivery_header_id = '{$deliveryData->delivery_header_id}'")) {
            // delivery_header削除
            $gen_db->query("delete from delivery_header where delivery_header_id = '{$deliveryData->delivery_header_id}'");
            $returnId = $deliveryData->delivery_header_id;
        }

        //------------------------------------------------------
        // 2. 入出庫データ（in_out）の削除
        //------------------------------------------------------
        Logic_Inout::deleteDeliveryInout($deliveryDetailId);

        //------------------------------------------------------
        // 3. 引当（use_plan）の復元（フリー在庫）
        //------------------------------------------------------
        //  納品時の自動引当の導入に伴い、納品レコードを削除したときに引当数を復元するのをやめた。
        //  自動引当後に納品を削除した場合、ユーザーが意識していない数の引当ができてしまうため。
        //  たとえば受注数100、引当数0の受注があったとして、 100の納品を行ったあとで納品を削除した場合、
        //  ここのセクションが有効の状態だと、納品時に内部的に行われた自動引当の数量が復元されて、
        //  引当数が100になってしまう。この引当100はユーザーが明示的に行ったものではないので、戸惑う
        //  ことになる。
        //  復元を行わないので、ユーザーが受注登録画面で明示的に行った引当も、納品削除時に解除されて
        //  しまうことになる。これは問題といえば問題だが、ユーザーが明示的に指定しない引当が残ることの
        //  ほうが問題が大きいと考え、ここはコメントアウトとした。
        //
        //   納品登録時に引当数を減らしているので、納品削除のときは逆に引当数を増やす。
        //   引当データは複数レコードある可能性があるため、UPDATEではなくINSERTする。
        //   引当（use_plan）が関係するのはフリー在庫のみ。製番在庫に引当はない。
        //        $data = array(
        //            'seiban' => $deliveryData->seiban,
        //            'item_id' => $deliveryData->item_id,
        //            // 2007-05-01 Changed
        //            'use_date'=> $deliveryData->dead_line,
        //            //'use_date'=> $deliveryData->delivery_date,
        //            'quantity' => $deliveryData->free_stock_quantity,
        //        );
        //
        //        $gen_db->insert("use_plan", $data);
        //
        //        // 引当（use_plan）に「完了扱い調整」レコードが入っていたときはそれを削除しておく必要が
        //        // ある。（完了扱い調整については登録のコードを参照）
        //
        //        $query = "DELETE from use_plan where completed_adjust_delivery_id = $deliveryDetailId";
        //        $gen_db->query($query);
        //
        // 納品削除後、受注で再引当を行うとマイナスの引当データができてしまうため、
        // 全ての納品完了データ削除時に引当情報を解放する。
        // （引当データを削除するのではなく、id値を解放する。）
        $data = array("completed_adjust_delivery_id" => null);
        $where = "completed_adjust_delivery_id = {$deliveryDetailId}";
        $gen_db->update("use_plan", $data, $where);

        //------------------------------------------------------
        // 4. 受注データ完了フラグの更新
        //------------------------------------------------------
        $completed = Logic_Received::updateCompletedFlag($deliveryData->received_detail_id, 'false');

        //------------------------------------------------------
        // 5. 引当（use_plan）の解除
        //------------------------------------------------------
        // 納品完了時
        if ($completed == "true") {
            // 最終の完了納品レコードと納品日を取得
            $query = "
            select
                delivery_detail_id,
                delivery_date
            from
                delivery_detail
                inner join delivery_header on delivery_detail.delivery_header_id = delivery_header.delivery_header_id
            where
                received_detail_id = '{$deliveryData->received_detail_id}'
            order by
                delivery_date desc,
                delivery_detail_id desc
            ";
            $adjustData = $gen_db->queryOneRowObject($query);

            // 完了調整レコードの再登録
            Logic_Reserve::entryCompletedAdjust(
                    $deliveryData->received_detail_id
                    , $deliveryData->item_id
                    , $adjustData->delivery_detail_id
                    , $adjustData->delivery_date
            );
        }

        //------------------------------------------------------
        // 6. 納品ヘッダーデータの更新
        //------------------------------------------------------
        self::updateDeliveryNote($deliveryData->delivery_header_id);

        //------------------------------------------------
        //  7. ダミー品目の子品目使用予定を再計算
        //------------------------------------------------
        self::calcUsePlanForDummy($deliveryData->received_detail_id);

        // コミット
        $gen_db->commit();

        return $returnId;
    }

    // 特定の受注に関連した納品データを削除
    // 受注登録で使用
    static function deleteReceivedDelivery($receivedDetailId)
    {
        global $gen_db;

        $query = "select delivery_detail_id from delivery_detail where received_detail_id = '{$receivedDetailId}'";

        if ($arr = $gen_db->getArray($query)) {
            foreach ($arr as $row) {
                Logic_Delivery::deleteDeliveryDetail($row['delivery_detail_id']);
            }
        }
    }

    //************************************************
    //  取引通貨レートを返す
    //************************************************
    // 指定された取引通貨の最新レートを返す。
    static function getCurrencyRate($currencyId, $date)
    {
        global $gen_db;

        $query = "
        select
            rate
        from
            rate_master
            inner join (
                select
                    currency_id
                    ,max(rate_date) as max_date
                from
                    rate_master
                where
                    currency_id = '{$currencyId}'
                    and rate_date <= '{$date}'::date
                group by
                    currency_id
                ) as t_date on rate_master.rate_date = t_date.max_date
                and rate_master.currency_id = t_date.currency_id
        ";
        $rate = $gen_db->queryOneValue($query);

        return $rate;
    }

    //************************************************
    // 特定の受注について、フリー在庫から納品済みの数量を返す
    //************************************************
    //  受注登録で使用（登録時に、フリー在庫引当数が変更された際のチェック用）
    //  Delivery_Delivery_ReserveEntryの値チェックでも使用
    static function getFreeDeliveryQtyByReceivedDetailId($receivedDetailId)
    {
        global $gen_db;

        $query = "select SUM(free_stock_quantity) from delivery_detail where received_detail_id = '{$receivedDetailId}'";
        return Gen_String::nz($gen_db->queryOneValue($query));
    }

    //************************************************
    //  特定の受注について、納品があるかどうかを返す
    //************************************************
    // header_id: 一部でも納品があれば、納品アリとみなされる。
    static function hasDeliveryByReceivedHeaderId($receivedHeaderId)
    {
        global $gen_db;

        if (!isset($receivedHeaderId) || !is_numeric($receivedHeaderId)) {
            return false;
        }

        $query = "
        select
            delivery_detail_id
        from
            delivery_detail
            inner join received_detail on delivery_detail.received_detail_id = received_detail.received_detail_id
        where
            received_header_id = '{$receivedHeaderId}'
            and (delivery_quantity <> 0 or (delivery_quantity = 0 and delivery_completed))
        ";
        return $gen_db->existRecord($query);
    }

    static function hasDeliveryByReceivedDetailId($receivedDetailId)
    {
        global $gen_db;

        if (!isset($receivedDetailId) || !is_numeric($receivedDetailId)) {
            return false;
        }

        $query = "
        select
            delivery_detail_id
        from
            delivery_detail
        where
            received_detail_id = '{$receivedDetailId}'
            and delivery_quantity <> 0
        ";
        return $gen_db->existRecord($query);
    }

    //************************************************
    //  特定の受注について、ロック済の納品があるかどうかを返す
    //************************************************

    static function hasCannotChangeDeliveryByReceivedDetailId($receivedDetailId)
    {
        global $gen_db;

        $start_date = Logic_SystemDate::getStartDateString($gen_db);

        $query = "
        select
            received_detail_id
        from
            delivery_detail
            inner join delivery_header on delivery_detail.delivery_header_id = delivery_header.delivery_header_id
        where
            received_detail_id = {$receivedDetailId}
            and delivery_date < '{$start_date}'::date
            and delivery_quantity <> 0
        ";
        return $gen_db->existRecord($query);
    }

    //************************************************
    //  特定の納品について、請求があるかどうかを返す
    //************************************************

    static function hasBillByDeliveryHeaderId($deliveryHeaderId)
    {
        global $gen_db;

        $query = "select bill_detail_id from bill_detail where delivery_no = (select delivery_no from delivery_header where delivery_header_id = '{$deliveryHeaderId}')";
        return $gen_db->existRecord($query);
    }

    //************************************************
    //  指定された納品の納品日を返す
    //************************************************

    static function getDeliveryDateByTranId($deliveryHeaderId)
    {
        $res = self::getDeliveryDataById($deliveryHeaderId, false);
        return $res[0]['delivery_date'];
    }

    static function getDeliveryDateByDetailId($deliveryDetailId)
    {
        $res = self::getDeliveryDataById($deliveryDetailId, true);
        return $res[0]['delivery_date'];
    }

    //************************************************
    //  指定された納品の得意先idを返す
    //************************************************

    static function getCustomerIdByTranId($deliveryHeaderId)
    {
        $res = self::getDeliveryDataById($deliveryHeaderId, false);
        $recId = $res[0]['received_detail_id'];
        return Logic_Received::getCustomerIdByDetailId($recId);
    }

    static function getCustomerIdByDetailId($deliveryDetailId)
    {
        $res = self::getDeliveryDataById($deliveryDetailId, true);
        $recId = $res[0]['received_detail_id'];
        return Logic_Received::getCustomerIdByDetailId($recId);
    }

    //************************************************
    //  idをキーに delivery_header, detail のデータを配列で取得
    //************************************************

    static function getDeliveryDataById($id, $isDetailId)
    {
        global $gen_db;

        $query = "
        select * from delivery_header
        inner join delivery_detail on delivery_header.delivery_header_id = delivery_detail.delivery_header_id
        where
        ";
        if ($isDetailId) {
            $query .= "delivery_detail_id = '{$id}'";
        } else {
            $query .= "delivery_header.delivery_header_id = '{$id}'";
        }
        return $gen_db->getArray($query);
    }

    //************************************************
    //  納品画面において、受注が選択されたときに各項目に表示する値を取得する
    //************************************************
    // Delivery_Delivery_AjaxReceivedParam クラスで使用。
    // 第一引数は受注ID。 その受注に引き当てられた在庫が表示対象になる。
    // 第二引数は納品ID。 その納品における納品数が表示される。 0なら納品数は表示しない。
    static function getDeliveryData($receivedDetailId, $deliveryDetailId, $locationId)
    {
        global $gen_db;

        //------------------------------------------------------
        //  受注情報
        //------------------------------------------------------
        $query = "
        select
            received_number
            ,received_detail.line_no
            ,received_detail.item_id
            ,item_code
            ,item_name
            ,measure
            ,received_quantity
            ,case when foreign_currency_id is null then product_price else foreign_currency_product_price end as product_price
            ,seiban
            ,order_class
            ,default_location_id_3
            ,customer_master.customer_id
            ,customer_master.customer_no
            ,received_header.delivery_customer_id
            ,t_delivery_customer.customer_no as delivery_customer_no
            ,t_delivery_customer.customer_name as delivery_customer_name
            ,received_detail.sales_base_cost
            ,received_detail.foreign_currency_id
            ,received_detail.foreign_currency_sales_base_cost
            ,received_detail.remarks
            ,case when dummy_item then 1 else 0 end as is_dummy
            ,received_detail.dead_line
        from
            received_detail
            left join item_master on received_detail.item_id = item_master.item_id
            left join received_header on received_detail.received_header_id = received_header.received_header_id
            left join customer_master on received_header.customer_id = customer_master.customer_id
            left join customer_master as t_delivery_customer on received_header.delivery_customer_id = t_delivery_customer.customer_id
            left join customer_master as t_bill_customer on coalesce(customer_master.bill_customer_id, customer_master.customer_id) = t_bill_customer.customer_id
        where
            received_detail_id = '{$receivedDetailId}'
        ";
        $rData = $gen_db->queryOneRowObject($query);

        $res['received_number'] = $rData->received_number;
        $res['line_no'] = $rData->line_no;
        $res['item_id'] = $rData->item_id;
        $res['item_code'] = $rData->item_code;
        $res['item_name'] = $rData->item_name;
        $res['measure'] = $rData->measure;
        $res['received_quantity'] = Gen_String::nz($rData->received_quantity);
        $res['product_price'] = Gen_String::nz($rData->product_price);
        $res['seiban'] = $rData->seiban;
        $res['default_location_id_3'] = $rData->default_location_id_3;
        $res['customer_id'] = $rData->customer_id;
        $res['customer_no'] = $rData->customer_no;
        $res['delivery_customer_id'] = $rData->delivery_customer_id;
        $res['delivery_customer_no'] = $rData->delivery_customer_no;
        $res['delivery_customer_name'] = $rData->delivery_customer_name;
        $res['sales_base_cost'] = $rData->sales_base_cost;
        $res['currency_id'] = $rData->foreign_currency_id;
        $res['foreign_currency_sales_base_cost'] = $rData->foreign_currency_sales_base_cost;
        $res['remarks'] = $rData->remarks;
        $res['is_dummy'] = $rData->is_dummy;
        $res['dead_line'] = $rData->dead_line;

        //------------------------------------------------------
        //  受注残
        //------------------------------------------------------
        $query = "
        select
            SUM(delivery_quantity)
        from
            delivery_detail
        where
            received_detail_id = '{$receivedDetailId}'
        ";

        $deliveryDoneQty = Gen_String::nz($gen_db->queryOneValue($query));
        $res['remained_quantity'] = Gen_Math::sub($res['received_quantity'], $deliveryDoneQty);

        //------------------------------------------------------
        //  現在庫数
        //------------------------------------------------------
        // 理論在庫を取得（有効在庫にしたいところだがロケ別では有効在庫を取得できない）
        // ロケ未指定のときは標準ロケの在庫を取得する
        if (!is_numeric($locationId) && is_numeric($res['default_location_id_3']))
            $locationId = $res['default_location_id_3'];

        // 製番在庫
        $seiban = $gen_db->queryOneValue("select seiban from received_detail where received_detail_id = '{$receivedDetailId}'");
        $stockQty = Logic_Stock::getLogicalStock($res['item_id'], $seiban, $locationId, 0);

        // フリー在庫
        $stockQty += Logic_Stock::getLogicalStock($res['item_id'], "", $locationId, 0);

        // 現在編集中の納品による納品数
        $currentDeliveryQty = 0;
        if (is_numeric($deliveryDetailId)) {
            $query = "
            select
                coalesce(seiban_stock_quantity,0) + coalesce(free_stock_quantity,0) as dQty
            from
                delivery_detail
            where
                delivery_detail_id = '{$deliveryDetailId}'
            ";
            $currentDeliveryQty = Gen_String::nz($gen_db->queryOneValue($query));
        }

        $res['stock_quantity'] = $stockQty + $currentDeliveryQty;

        return $res;
    }

    //************************************************
    //  一括納品登録画面、バーコード納品登録画面において
    //  与信限度額を超えていないかチェックする。
    //************************************************
    //  引数 $arr : 取引先id -> 納品額
    static function checkDeliveryCreditLine($arr)
    {
        global $gen_db;

        // 売掛残高データの取得（temp_receivable。納品ベース、取引通貨別）
        // customer_id の限定を行ってはいけない。請求先があるかもしれないので
        // 最終残高を求める。（2038年以降は日付と認識されない）
        $day = date('2037-12-31');
        Logic_Receivable::createTempReceivableTable($day, $day, 0, false);

        $limitOver = false;
        foreach ($arr as $key => $value) {
            $query = "
            select
                coalesce(receivable_balance,0) as receivable_balance
                ,t_bill_customer.credit_line
                ,customer_master.customer_no
            from
                customer_master
                left join customer_master as t_bill_customer on coalesce(customer_master.bill_customer_id, customer_master.customer_id) = t_bill_customer.customer_id
                left join temp_receivable on t_bill_customer.customer_id = temp_receivable.customer_id
            where
                customer_master.customer_id = '{$key}'
            ";
            $res = $gen_db->queryOneRowObject($query);

            // 与信限度額が設定されている得意先のみチェック
            if (isset($res->credit_line) && is_numeric($res->credit_line)) {
                if (Gen_Math::add($res->receivable_balance, $value) > $res->credit_line)
                    $limitOver = true;  // 与信限度額をオーバー
            }
        }

        return $limitOver;
    }

    //************************************************
    //  印刷済みフラグのセット
    //************************************************

    static function setDeliveryPrintedFlag($idArr, $isSet)
    {
        global $gen_db;

        $idWhere = join(",", $idArr);
        if ($idWhere == "")
            return;

        $query = "
        update
            delivery_header
        set
            delivery_printed_flag = " . ($isSet ? 'true' : 'false') . "
            ,record_updater = '" . $_SESSION['user_name'] . "'
            ,record_update_date = '" . date('Y-m-d H:i:s') . "'
            ,record_update_func = '" . __CLASS__ . "::" . __FUNCTION__ . "'
        where
            delivery_header_id in ({$idWhere})
        ";
        $gen_db->query($query);
    }

    //************************************************
    // ダミー品目の子品目使用予約を再計算
    //************************************************
    // 納品登録/削除時に、ダミー品目の子品目使用予約（受注品目がダミー品目だった場合のみ、子品目の使用予約が登録される）
    // を調整するのに使用。
    // 　ダミー品目の子品目使用予定については、Logic_Received::entryUsePlanForDummy() のコメントを参照。
    // 　常に「親品目の受注残数 × 員数」が使用予約数として残るようにする。

    static function calcUsePlanForDummy($receivedDetailId)
    {
        global $gen_db;

        // 受注品目が（受注登録時点で）ダミー品目ではない場合、なにもしない
        $query = "select * from received_dummy_child_item where received_detail_id_for_dummy = '{$receivedDetailId}'";
        if (!$gen_db->existRecord($query)) {
            return;
        }

        // use_planは複数レコードに分かれていることがあるので、updateではなくdelete/insertする。
        // いったん削除
        $query = "delete from use_plan where received_detail_id_for_dummy = '{$receivedDetailId}'";
        $gen_db->query($query);

        // 納品完了の場合はuse_plan登録不要
        $query = "select delivery_completed from received_detail where received_detail_id = '{$receivedDetailId}'";
        if ($gen_db->queryOneValue($query) == "t") {
            return;
        }

        // 再登録
        $query = "
        insert into use_plan (
            received_detail_id_for_dummy
            ,item_id
            ,use_date
            ,quantity
            ,record_creator
            ,record_create_date
            ,record_create_func
        )
        select
            '{$receivedDetailId}'
            ,child_item_id
            ,dead_line
            ,(received_quantity - coalesce(delivery_quantity,0)) * received_dummy_child_item.quantity
            ,'" . $_SESSION['user_name'] . "'
            ,'" . date('Y-m-d H:i:s') . "'
            ,'" . __CLASS__ . "::" . __FUNCTION__ . "'
        from
            received_detail
            inner join received_dummy_child_item on received_detail.received_detail_id = received_dummy_child_item.received_detail_id_for_dummy
            left join (select received_detail_id, sum(delivery_quantity) as delivery_quantity from delivery_detail group by received_detail_id) as t_delivery
                on received_detail.received_detail_id = t_delivery.received_detail_id
        where
            received_detail.received_detail_id = '{$receivedDetailId}'
        ";
        $gen_db->query($query);
    }

}