<?php

class Logic_Estimate
{

    //************************************************
    // 親データ（estimate_header）登録
    //************************************************
    // 引数：
    //      $estimateId          新規のときはnullを渡す。更新のときのみ指定する。
    //      $estimateNumber      見積番号。省略すると自動採番
    //      $customerId          得意先ID。任意
    //      $customerName        得意先名
    //      $estimateDate        日付
    //      $customerZip         郵便番号
    //      $customerAddress1    得意先住所1
    //      $customerAddress2    得意先住所2
    //      $customerTel         得意先tel
    //      $customerFax         得意先fax
    //      $personInCharge      担当者名
    //      $subject             件名
    //      $deliveryDate text,  受渡期日
    //      $deliveryPlace text, 受渡場所
    //      $modeOfDealing text, お支払条件
    //      $expireDate text,    有効期限
    //      $workerId            自社担当者ID
    //      $sectionId           自社部門ID
    //      $estimateRank        ランク
    //      $remarks             備考
    //
    // 戻り値：
    //      $estimateId          引数で指定したならそのままの値、nullを渡したなら自動採番された値が返る

    static function entryEstimate($estimateId, $estimateNumber, $customerId, $customerName, $estimateDate, $customerZip, $customerAddress1, $customerAddress2, $customerTel, $customerFax, $personInCharge, $subject, $deliveryDate, $deliveryPlace, $modeOfDealing, $expireDate, $workerId, $sectionId, $estimateRank, $remarks)
    {
        global $gen_db;

        //-----------------------------------------------------------
        // 見積番号が指定されていなければ自動採番
        //-----------------------------------------------------------

        $estimateNumber = Logic_NumberTable::getMonthlyAutoNumber(GEN_PREFIX_ESTIMATE_NUMBER, 'estimate_number', 5, $estimateNumber);

        //-----------------------------------------------------------
        // 登録処理
        //-----------------------------------------------------------

        if (is_numeric($estimateId)) {
            $key = array('estimate_header_id' => $estimateId);    // 指定されていればUpdate、未指定ならInsert（idは自動採番）
        } else {
            $key = null;
        }
        $data = array(
            'estimate_number' => $estimateNumber,
            'customer_id' => $customerId,
            'customer_name' => $customerName,
            'estimate_date' => $estimateDate,
            'customer_zip' => $customerZip,
            'customer_address1' => $customerAddress1,
            'customer_address2' => $customerAddress2,
            'customer_tel' => $customerTel,
            'customer_fax' => $customerFax,
            'person_in_charge' => $personInCharge,
            'subject' => $subject,
            'delivery_date' => $deliveryDate,
            'delivery_place' => $deliveryPlace,
            'mode_of_dealing' => $modeOfDealing,
            'expire_date' => $expireDate,
            'worker_id' => $workerId,
            'section_id' => $sectionId,
            'estimate_rank' => $estimateRank,
            'remarks' => $remarks,
        );
        $gen_db->updateOrInsert('estimate_header', $key, $data);

        //-----------------------------------------------------------
        // idを返す
        //-----------------------------------------------------------

        if ($key === null) {
            $estimateId = $gen_db->getSequence("estimate_header_estimate_header_id_seq");
        }

        return $estimateId;
    }

    //************************************************
    // 子データ（estimate_detail）登録
    //************************************************
    // 引数：
    //      $estimateDetailId    必ず指定する
    //      $estimateId          必ず指定する。親テーブルとのリンク。
    //      $itemId              必ず指定する
    //      $itemCode            nullなら品目マスタから取得される
    //      $itemName            nullなら品目マスタから取得される
    //      $quantity            数量。必ず指定する
    //      $measure             単位
    //      $baseCost            原価。必ず指定する
    //      $salePrice           見積単価。nullなら品目マスタから取得される
    //      $grossMargin         粗利。必ず指定する
    //      $taxClass            課税区分
    //      $remarks             明細備考
    //      $remarks2            明細備考2
    //
    // 戻り値：
    //      なし

    static function entryEstimateDetail($estimateDetailId, $estimateId, $lineNo, $itemId, $itemCode, $itemName, $quantity, $measure, $baseCost, $salePrice, $grossMargin, $taxClass, $remarks, $remarks2)
    {
        global $gen_db;

        // 税計算・外貨まわりは、納品登録のロジックにだいたいあわせてある

        //-----------------------------------------------------------
        //  ヘッダ情報
        //-----------------------------------------------------------

        $query = "select estimate_date, customer_id from estimate_header where estimate_header_id = '{$estimateId}'";
        $obj = $gen_db->queryOneRowObject($query);
        $estimateDate = $obj->estimate_date;
        $customerId = $obj->customer_id;

        //-----------------------------------------------------------
        //  税率取得
        //-----------------------------------------------------------

        if ($taxClass == 1) {
            // 非課税
            $taxRate = 0;
        } else {
            // 課税
            $taxRate = null;
            if (Gen_String::isNumeric($itemId)) {
                $query = "select item_master.tax_rate from item_master where item_id = '{$itemId}'";
                $taxRate = $gen_db->queryOneValue($query);
            }
            if (!is_numeric($taxRate)) {
                // 消費税率マスタの税率を取得（見積日の税率を取得）
                $taxRate = Logic_Tax::getTaxRate($estimateDate);
            }
        }

        //------------------------------------------------
        //  金額計算・引当計算・外貨計算
        //------------------------------------------------

        // 請求先およびその取引通貨を取得
        if (is_numeric($customerId)) {
            $query = "
            select
                t_bill_customer.customer_id as bill_customer_id
                ,t_bill_customer.currency_id
                ,coalesce(t_bill_customer.rounding,'round') as rounding
                ,coalesce(t_bill_customer.precision,0) as precision
                ,coalesce(rate,1) as rate
            from
                customer_master as t_bill_customer
                inner join customer_master on t_bill_customer.customer_id = coalesce(customer_master.bill_customer_id, customer_master.customer_id)
                left join currency_master on t_bill_customer.currency_id = currency_master.currency_id
                /* 見積日時点のレートを取得 */
                left join (select currency_id, max(rate_date) as rate_date from rate_master
                    where rate_date <= '{$estimateDate}'::date
                    group by currency_id) as t_rate_date
                    on currency_master.currency_id = t_rate_date.currency_id
                left join rate_master on t_rate_date.currency_id = rate_master.currency_id and t_rate_date.rate_date = rate_master.rate_date
            where
                customer_master.customer_id = '{$customerId}'
            ";
            $obj = $gen_db->queryOneRowObject($query);
            $billCustomerId = $obj->bill_customer_id;
            $currencyId = $obj->currency_id;
            $rounding = $obj->rounding;
            $precision = $obj->precision;
            $currencyRate = $obj->rate;
        } else {
            $currencyId = null;
            $rounding = 'round';
            $precision = 0;
        }

        // 金額・税計算（明細行毎に請求先の設定で丸められる）
        if ($currencyId == null) {
            // 基軸通貨の場合
            $amount = Gen_Math::round(Gen_Math::mul($quantity, $salePrice), $rounding, $precision);
            $baseCostTotal = Gen_Math::round(Gen_Math::mul($quantity, $baseCost), $rounding, $precision);
            $tax = Gen_Math::round(Gen_Math::mul($amount, Gen_Math::div($taxRate, 100)), $rounding, $precision);
        } else {
            // 外貨の場合
            $taxRate = 0;   // ※ 外貨の時は税計算の対象から除外する

            // 入力された単価は外貨単価として登録する
            $foreignCurrencyPrice = $salePrice;         // 入力された単価（外貨単価）は丸めない
            $foreignCurrencyAmount = Logic_Customer::round(Gen_Math::mul($quantity, $salePrice), $billCustomerId);
            $foreignCurrencyBaseCost = $baseCost;
            $foreignCurrencyBaseCostTotal = Logic_Customer::round(Gen_Math::mul($quantity, $baseCost), $billCustomerId);
            $foreignCurrencyEstimateTax = 0;    // 外貨の税額は常に0なのでこのカラムは無意味

            // 基軸通貨に換算（請求先の設定で計算）
            // 小数点以下桁数は、単価は GEN_FOREIGN_CURRENCY_PRECISION、金額は取引先マスタの値
            $salePrice = Gen_Math::round(Gen_Math::mul($salePrice, $currencyRate), $rounding, GEN_FOREIGN_CURRENCY_PRECISION);
            $amount = Logic_Customer::round(Gen_Math::mul($quantity, $salePrice), $billCustomerId);
            $baseCost = Gen_Math::round(Gen_Math::mul($baseCost, $currencyRate), $rounding, GEN_FOREIGN_CURRENCY_PRECISION);
            $baseCostTotal = Logic_Customer::round(Gen_Math::mul($quantity, $baseCost), $billCustomerId);
            // 税計算（納品書明細単位のみ）
            $tax = 0;
        }

        //-----------------------------------------------------------
        // 登録処理
        //-----------------------------------------------------------

        if (is_numeric($estimateDetailId)) {
            $key = array('estimate_detail_id' => $estimateDetailId);    // 指定されていればUpdate、未指定ならInsert（idは自動採番）
        } else {
            $key = null;
        }
        $data = array(
            'estimate_header_id' => $estimateId,
            'item_id' => $itemId,
            'line_no' => $lineNo,
            'item_code' => $itemCode,
            'item_name' => $itemName,
            'quantity' => $quantity,
            'measure' => $measure,
            'tax_class' => $taxClass,
            'sale_price' => $salePrice,
            'estimate_amount' => $amount,
            'estimate_tax' => $tax,
            'base_cost' => $baseCost,
            'base_cost_total' => $baseCostTotal,
            'gross_margin' => $grossMargin,
            'remarks' => $remarks,
            'remarks_2' => $remarks2,
        );
        if ($currencyId == null) {
            // 取引通貨処理（基軸通貨のとき）
            $data['foreign_currency_id'] = null;
            $data['foreign_currency_rate'] = null;
            $data['foreign_currency_sale_price'] = null;
            $data['foreign_currency_estimate_amount'] = null;
            $data['foreign_currency_estimate_tax'] = null;
            $data['foreign_currency_base_cost'] = null;
            $data['foreign_currency_base_cost_total'] = null;
        } else {
            // 取引通貨処理（基軸通貨以外のとき）
            $data['foreign_currency_id'] = $currencyId;
            $data['foreign_currency_rate'] = $currencyRate;
            $data['foreign_currency_sale_price'] = $foreignCurrencyPrice;
            $data['foreign_currency_estimate_amount'] = $foreignCurrencyAmount;
            $data['foreign_currency_estimate_tax'] = $foreignCurrencyEstimateTax;
            $data['foreign_currency_base_cost'] = $foreignCurrencyBaseCost;
            $data['foreign_currency_base_cost_total'] = $foreignCurrencyBaseCostTotal;
        }
        $gen_db->updateOrInsert('estimate_detail', $key, $data);

        // いま登録した見積IDを確認
        if (!isset($estimateDetailId) || !is_numeric($estimateDetailId)) {
            $estimateDetailId = $gen_db->getSequence("estimate_detail_estimate_detail_id_seq");
        }

        return $estimateDetailId;
    }

    //************************************************
    // 見積データの削除
    //************************************************
    // トランザクションは呼び出し側で
    //
    // 見積データ（estimate_header, estimate_detail）の削除。
    static function deleteEstimateHeader($estimateTranId)
    {
        global $gen_db;

        // 明細行（estimate_detail）ごとに削除。
        // ヘッダ（estimate_header）の削除もこの中でおこなわれる。
        $query = "select estimate_detail_id from estimate_detail where estimate_header_id = '{$estimateTranId}'";
        $arr = $gen_db->getArray($query);
        foreach ($arr as $row) {
            self::deleteEstimateDetail($row['estimate_detail_id']);
        }
    }

    // 見積明細（estimate_detail）の削除。
    //  削除したデータがその見積の最後の1行だった場合、ヘッダ(estimate_tran)も削除する。
    static function deleteEstimateDetail($estimateDetailId)
    {
        global $gen_db;

        $query = "select estimate_header_id from estimate_detail where estimate_detail_id = '{$estimateDetailId}'";
        $estimateId = $gen_db->queryOneValue($query);

        $query = "delete from estimate_detail where estimate_detail_id = '{$estimateDetailId}'";
        $gen_db->query($query);

        // 削除したデータがその見積の最後の1行だった場合、ヘッダも削除する。
        if (!$gen_db->existRecord("select * from estimate_detail where estimate_header_id = '{$estimateId}'")) {
            // ヘッダ削除
            $gen_db->query("delete from estimate_header where estimate_header_id = '{$estimateId}'");
            return $estimateId;
        }

        return null;
    }

    //************************************************
    // 指定された見積の見積日を返す
    //************************************************

    static function getEstimateDateById($estimateId)
    {
        global $gen_db;

        $query = "select estimate_date from estimate_header where estimate_header_id = '{$estimateId}'";
        return $gen_db->queryOneValue($query);
    }

    static function getEstimateDateByDetailId($estimateDetailId)
    {
        global $gen_db;

        $query = "
            select estimate_date from estimate_header
            inner join estimate_detail on estimate_detail.estimate_header_id = estimate_header.estimate_header_id
            where estimate_detail_id = '{$estimateDetailId}'";

        return $gen_db->queryOneValue($query);
    }

    //************************************************
    // 見積を受注に転記
    //************************************************

    static function estimateToReceived($estimateId, $receivedDate, $deadLine)
    {
        global $gen_db;

        $query = "
            select
                estimate_header.estimate_header_id
                ,estimate_detail.estimate_detail_id
                ,estimate_header.customer_id
                ,estimate_header.estimate_date
                ,estimate_header.worker_id
                ,estimate_header.section_id
                ,estimate_header.subject
                ,estimate_header.delivery_place
                ,estimate_header.remarks as remarks_header

                ,estimate_detail.item_id
                ,estimate_detail.quantity
                ,case when foreign_currency_id is null then sale_price else foreign_currency_sale_price end as sale_price
                ,case when foreign_currency_id is null then base_cost else foreign_currency_base_cost end as base_cost
                ,estimate_detail.remarks as remarks_detail
                ,estimate_detail.remarks_2 as remarks_detail_2
            from
                estimate_header
                inner join estimate_detail
                    on estimate_header.estimate_header_id = estimate_detail.estimate_header_id
            where
                estimate_header.estimate_header_id = '{$estimateId}'
                -- 得意先IDが登録されていない見積、および品目IDが登録されていない行はスキップ
                and customer_id is not null
                and item_id is not null
            order by
                line_no
        ";
        $res = $gen_db->getArray($query);
        if ($res == false)
            return;

        // 受注日と受注納期
        //  受注日：　空欄のときは見積日と同じ
        //  受注納期：空欄のときは受注日と同じ
        //  ※上記を変更するときは、Manufacturing_Estimate_List の JS内のメッセージを変更すること
        if (!Gen_String::isDateString($receivedDate))
            $receivedDate = $res[0]['estimate_date'];
        if (!Gen_String::isDateString($deadLine))
            $deadLine = $receivedDate;

        // 受注ヘッダ
        $headerId = Logic_Received::entryReceivedHeader(
            null                            // received_header_id
            , null                          // received_number
            , null                          // customer_received_number
            , $res[0]['customer_id']
            , $res[0]['customer_id']        // delivery_customer_id
            , $receivedDate
            , $res[0]['worker_id']
            , $res[0]['section_id']
            , '0'                           // guarantee_grade (0:fix)
            , $res[0]['estimate_header_id']
            , $res[0]['remarks_header']     // remarks_header
            , $res[0]['subject']            // remarks_header_2
            , $res[0]['delivery_place']     // remarks_header_3
        );

        // 受注明細
        $lineNo = 1;
        foreach ($res as $row) {
            Logic_Received::entryReceivedDetail(
                $headerId
                , null                  // received_detail_id
                , $lineNo++
                , $row['item_id']
                , $row['quantity']
                , $row['sale_price']
                , $row['base_cost']
                , 0                     // 引当数
                , $deadLine
                , $row['remarks_detail']
                , $row['remarks_detail_2']
            );
        }

        return $headerId;
    }

}