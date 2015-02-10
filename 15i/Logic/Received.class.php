<?php

class Logic_Received
{

    //************************************************
    // 親データ（received_header）登録
    //************************************************
    // 引数：
    //      $receivedHeaderId         新規のときはnullを渡す。更新のときのみ指定する。
    //      $receivedNumber           受注番号。省略すると自動採番
    //      $customerReceivedNumber   客先受注番号
    //      $customerId               取引先ID
    //      $deliveryCustomerId       発送先ID
    //      $receivedDate             受注日
    //      $workerId                 担当者ID
    //      $sectionId                部門ID
    //      $guaranteeGrade           確定度
    //      $estimateHeaderId         見積番号。省略可能
    //      $remarksHeader            備考1
    //      $remarksHeader2           備考2
    //      $remarksHeader3           備考3
    //
    // 戻り値：
    //      $receivedHeaderId    引数で指定したならそのままの値、nullを渡したなら自動採番された値が返る

    static function entryReceivedHeader($receivedHeaderId, $receivedNumber, $customerReceivedNumber, $customerId, $deliveryCustomerId,
            $receivedDate, $workerId, $sectionId, $guaranteeGrade, $estimateHeaderId, $remarksHeader, $remarksHeader2, $remarksHeader3)
    {
        global $gen_db;

        // 受注番号の自動取得
        //     受注番号をユーザーが指定しなかったとき、自動取得を行う。
        //  複数セッションの同時実行による不具合を回避するため、採番の方式を変更。
        //  くわしくは Logic_Received::getReceivedNumber() のコメントを参照。
        $receivedNumber = self::getReceivedNumber($receivedNumber);

        // 親テーブル登録
        if (isset($receivedHeaderId) && is_numeric($receivedHeaderId)) {
            $key = array("received_header_id" => $receivedHeaderId);
        } else {
            $key = null;
        }
        $data = array(
            'received_number' => $receivedNumber,
            'customer_received_number' => $customerReceivedNumber,
            'customer_id' => $customerId,
            'delivery_customer_id' => $deliveryCustomerId,
            'received_date' => $receivedDate,
            'worker_id' => $workerId,
            'section_id' => $sectionId,
            'guarantee_grade' => $guaranteeGrade,
            'estimate_header_id' => $estimateHeaderId,
            'remarks_header' => $remarksHeader,
            'remarks_header_2' => $remarksHeader2,
            'remarks_header_3' => $remarksHeader3,
        );
        $gen_db->updateOrInsert('received_header', $key, $data);
        if (!isset($receivedHeaderId) || $receivedHeaderId == null) {
            $receivedHeaderId = $gen_db->getSequence("received_header_received_header_id_seq");
        }

        return $receivedHeaderId;
    }

    //************************************************
    // 明細データ（received_detail）登録
    //************************************************
    // 引数：
    //      $receivedHeaderId
    //      $receivedDetailId         新規のときはnullを渡す。更新のときのみ指定する。
    //      $lineNo                   行番号
    //      $itemId                   品目ID
    //      $receivedQuantity         受注数
    //      $productPrice             単価
    //      $salesBaseCost            販売原価
    //      $reserveQuantity          受注数
    //      $deadLine                 受注納期
    //      $remarks                  受注明細備考1
    //      $remarks2                 受注明細備考2
    //
    // 戻り値：
    //      $receivedDetailId         引数で指定したならそのままの値、nullを渡したなら自動採番された値が返る

    static function entryReceivedDetail($receivedHeaderId, $receivedDetailId, $lineNo, $itemId, $receivedQuantity, $productPrice, $salesBaseCost, $reserveQuantity, $deadLine, $remarks, $remarks2)
    {
        global $gen_db;

        // 製番の取得
        //      新規・コピーでは自動採番。
        //      修正では既存データの製番を読取。
        $seiban = "";
        if (isset($receivedDetailId) && is_numeric($receivedDetailId)) {
            $query = "select seiban from received_detail where received_detail_id = '{$receivedDetailId}'";
            $seiban = $gen_db->queryOneValue($query);
        }
        if ($seiban == "") {
            // 2010iまでは製番は単純なシリアル値だったが、12iからは「受注番号」+「-」+「行番号」とした。
            // そのほうがわかりやすく扱いやすいという意見が多かったため。
            // ただし計画での製番はこれまでどおり。（「Logic_Seiban::getSeiban」でプロジェクト検索）

            // 15i rev20140618で、枝番は最低2桁とした。製番でソートしたときに不自然な順序になるのを避けるため。
            // ag.cgi?page=ProjectDocView&pid=1574&did=222078
            $recNumber = self::getReceivedNumberByTranId($receivedHeaderId);
            $seiban = $recNumber . '-' . ($lineNo < 10 ? "0" : "") . $lineNo;

            // 製番が既存であれば、空いている製番を探す。
            // （採番時に行番号を枝番としているため、修正モードで行挿入した場合などに既存製番とバッティングする）
            $searchLine = $lineNo;
            while (true) {
                $query = "select * from received_detail where seiban = '{$seiban}'";
                if (!$gen_db->existRecord($query)) {
                    break;
                }
                ++$searchLine;
                $seiban = $recNumber . '-' . ($searchLine < 10 ? "0" : "") . $searchLine;
            }
        }

        // 取引通貨とレートを取得（請求先の取引通貨で計算する）
        $obj = self::_getTranDataById($receivedHeaderId, false);
        $receivedDate = $obj->received_date;
        $customerId = $obj->customer_id;
        $query = "
        select
            t_bill_customer.currency_id
            ,coalesce(t_bill_customer.rounding,'round') as rounding
            ,coalesce(rate,1) as rate
        from
            customer_master as t_bill_customer
            inner join customer_master on t_bill_customer.customer_id = coalesce(customer_master.bill_customer_id, customer_master.customer_id)
            left join currency_master on t_bill_customer.currency_id = currency_master.currency_id
            /* 受注日時点のレートを取得 */
            left join (select currency_id, max(rate_date) as rate_date from rate_master
                where rate_date <= '{$receivedDate}'::date
                group by currency_id) as t_rate_date
                on currency_master.currency_id = t_rate_date.currency_id
            left join rate_master on t_rate_date.currency_id = rate_master.currency_id and t_rate_date.rate_date = rate_master.rate_date
        where
            customer_master.customer_id = '{$customerId}'
        ";
        $currency = $gen_db->queryOneRowObject($query);

        // 取引通貨処理（基軸通貨以外のとき）
        if ($currency != null && $currency->currency_id !== null) {
            // 入力された単価・販売原価・経費は外貨として登録する
            $foreignCurrencyPrice = $productPrice;
            $foreignCurrencyBaseCost = $salesBaseCost;

            // 基軸通貨に換算
            // 小数点以下桁数は、単価は GEN_FOREIGN_CURRENCY_PRECISION、金額は取引先マスタの値
            $productPrice = Gen_Math::round(Gen_Math::mul($productPrice, $currency->rate), $currency->rounding, GEN_FOREIGN_CURRENCY_PRECISION);
            $salesBaseCost = Gen_Math::round(Gen_Math::mul($salesBaseCost, $currency->rate), $currency->rounding, GEN_FOREIGN_CURRENCY_PRECISION);
        }

        // キーの準備
        if (isset($receivedDetailId) && is_numeric($receivedDetailId)) {
            $key = array("received_detail_id" => $receivedDetailId);
        } else {
            // header_id と line_no で detail_id を再調査する
            // （編集時に品目書き換えで明細idが失われている可能性がある）
            $query = "select received_detail_id from received_detail where received_header_id = '{$receivedHeaderId}' and line_no = '{$lineNo}'";
            $receivedDetailId = $gen_db->queryOneValue($query);
            if (isset($receivedDetailId) && is_numeric($receivedDetailId)) {
                $key = array("received_detail_id" => $receivedDetailId);
            } else {
                $receivedDetailId = null;   // リセット
                $key = null;
            }
        }

        $data = array(
            'line_no' => $lineNo,
            'seiban' => $seiban,
            'received_header_id' => $receivedHeaderId,
            'item_id' => $itemId,
            'received_quantity' => $receivedQuantity,
            'product_price' => $productPrice,
            'sales_base_cost' => $salesBaseCost,
            'dead_line' => $deadLine,
            'remarks' => $remarks,
            'remarks_2' => $remarks2,
        );
        if ($currency != null && $currency->currency_id !== null) {
            // 取引通貨処理（基軸通貨以外のとき）
            $data['foreign_currency_id'] = $currency->currency_id;
            $data['foreign_currency_rate'] = $currency->rate;
            $data['foreign_currency_product_price'] = $foreignCurrencyPrice;
            $data['foreign_currency_sales_base_cost'] = $foreignCurrencyBaseCost;
        } else {
            // 取引通貨処理（基軸通貨のとき）
            $data['foreign_currency_id'] = null;
            $data['foreign_currency_rate'] = null;
            $data['foreign_currency_product_price'] = null;
            $data['foreign_currency_sales_base_cost'] = null;
        }
        $gen_db->updateOrInsert('received_detail', $key, $data);

        if (!isset($receivedDetailId) || $receivedDetailId == null) {
            $receivedDetailId = $gen_db->getSequence("received_detail_received_detail_id_seq");
        }

        // 引当数の登録/更新
        if (!Gen_String::isNumeric($reserveQuantity)) {
            $reserveQuantity = 0;
        }
        // 製番/ロット品目は引当数を0にする
        $query = "select order_class from item_master where item_id = '{$itemId}'";
        $orderClass = $gen_db->queryOneValue($query);
        if ($orderClass == '0' || $orderClass == '2') {
            $reserveQuantity = 0;
        }
        Logic_Reserve::updateReserveQuantity($receivedDetailId, $itemId, $deadLine, $reserveQuantity);

        // 受注品目がダミー品目だった場合、子品目使用予約を登録
        self::entryUsePlanForDummy($receivedDetailId);

        return $receivedDetailId;
    }

    //************************************************
    // 受注番号の自動採番。
    //************************************************

    static function getReceivedNumber($receivedNumber)
    {
        // 2009で 「A」+「年月4桁」+「連番5桁」に変更。
        //
        // Acc版では「年月 4桁」+「得意先ID 3桁」+「連番 3桁」だったが、連番3桁だと
        // 同一月・得意先からの受注が1000件を超えると同一番号が発行されてしまう。
        // Web版の規模からしてそれでは危険だが、かといって受注番号の桁数を増やすと
        // なにかと面倒なため、得意先IDを入れるのをやめた。
        //
        // 受注番号は文字型で、ユーザーによる登録も可能なので自動採番が難しい。
        // Access SQLならisNumericが使えるので、数字のみのmaxをとるのが簡単だったが
        // pgsqlをはじめ一般のRDBMSでは、SQLで数字のみの抽出をするのは困難だ。
        // （pgsqlなら正規表現を使えばできなくはないが）
        //
        // そこで下記のような、やや苦しい方法を使った。
        //
        // 2009で 「A」+「年月4桁」+「連番5桁」に変更。番号の頭が0だとExcelで切れてしまい不便なため。
        //  また正規表現で数字判断するようにし、最大値が非数値だったときの不自然な採番を回避した。
        //
        //  以前はselect MAX(received_number) from received_detail として採番していたが、
        //  この方法だと複数のトランザクションが同時実行されたときに同じ番号が採番されてしまう可能性がある。
        //  received_detailテーブルを LOCK TABLE table IN ACCESS EXCLUSIVE MODE すればよいが、
        //  パフォーマンス低下やデッドロックが心配。
        //  一方、シーケンスを使用すれば簡単だが、番号を手動で指定された場合の処理が問題。
        //  複数トランザクションが同時にsetvalした場合に競合回避するのが難しい。
        //  また手動指定だと、シーケンスの範囲を超える非常に大きい値が指定されたときの対処が難しい。
        //  そこで、ここでは採番テーブル方式を使用した。

        return Logic_NumberTable::getMonthlyAutoNumber(GEN_PREFIX_RECEIVED_NUMBER, 'received_number', 5, $receivedNumber);
    }

    //************************************************
    // 受注データの削除
    //************************************************
    // トランザクションは呼び出し側で
    //
    // 受注データ（received_header, received_detail）の削除。
    static function deleteReceivedHeader($receivedHeaderId)
    {
        global $gen_db;

        // 明細行（received_detail）ごとに削除。
        // ヘッダ（received_header）の削除もこの中でおこなわれる。
        $query = "select received_detail_id from received_detail where received_header_id = '{$receivedHeaderId}'";
        $arr = $gen_db->getArray($query);
        foreach ($arr as $row) {
            self::deleteReceivedDetail($row['received_detail_id']);
        }
    }

    // 受注明細（received_detail）の削除。
    //  削除したデータがその受注の最後の1行だった場合、ヘッダ(received_header)も削除する。
    static function deleteReceivedDetail($receivedDetailId)
    {
        global $gen_db;

        $query = "select received_header_id from received_detail where received_detail_id = '{$receivedDetailId}'";
        $receivedHeaderId = $gen_db->queryOneValue($query);

        // 関連する納品データの削除（注：deleteReceivedDeliveryのロジックの都合で、受注削除や引当削除より先に行う必要あり）
        Logic_Delivery::deleteReceivedDelivery($receivedDetailId);

        // 引当の解除（注：deleteReserveのロジックの都合で、受注削除より先に行う必要あり）
        Logic_Reserve::deleteReserve($receivedDetailId);

        // ダミー品目の子品目使用予定と支給の削除
        //　　くわしくは entryUsePlanForDummy() のコメントを参照
        $query = "delete from use_plan where received_detail_id_for_dummy = '{$receivedDetailId}'";
        $gen_db->query($query);
        $query = "delete from received_dummy_child_item where received_detail_id_for_dummy = '{$receivedDetailId}'";
        $gen_db->query($query);

        // 受注明細の削除
        $query = "delete from received_detail where received_detail_id = '{$receivedDetailId}'";
        $gen_db->query($query);

        // 削除したデータがその受注の最後の1行だった場合、ヘッダも削除する。
        if (!$gen_db->existRecord("select * from received_detail where received_header_id = '{$receivedHeaderId}'")) {
            // ヘッダ削除
            $gen_db->query("delete from received_header where received_header_id = '{$receivedHeaderId}'");
            return $receivedHeaderId;
        }

        return null;
    }

    //************************************************
    // 完了フラグの更新
    //************************************************

    static function updateCompletedFlag($receivedDetailId, $completed)
    {
        global $gen_db;

        // 完了フラグ
        //   引数 completed がオンのときはフラグを立てる。
        //   そうでなくても、受注数 <= 納品数 のときは強制的にフラグを立てる。
        $completed_flag = "false";

        if ($completed == "true") {
            $completed_flag = "true";
        } else {
            $query = "
            select
                max(received_quantity) as rec_qty
                ,count(delivery_detail.delivery_detail_id) as del_cnt
                ,case when max(received_quantity) <=
                    coalesce(sum(seiban_stock_quantity),0) + coalesce(sum(free_stock_quantity),0)
                    then 'completed' else '' end as comp
            from
                received_detail
                left join delivery_detail on received_detail.received_detail_id = delivery_detail.received_detail_id
            where
                received_detail.received_detail_id = '{$receivedDetailId}'
            group by
                received_detail.received_detail_id
            ";
            $obj = $gen_db->queryOneRowObject($query);

            if ($obj->rec_qty == "0") {
                // 受注数0のときは、納品ありの場合のみ完了とする。
                // （納品なしのときは未完了とする）
                // 通常のロジック（受注数 <= 納品数 のときに完了）では、
                // 受注0で登録 -> 納品を登録（受注は完了） -> 納品を削除 としたときに、
                // 受注が完了のままになってしまい納品登録できなくなるため。
                if ($obj->del_cnt != "0") {
                    $completed_flag = "true";
                }
            } else if ($obj->comp == 'completed') {
                $completed_flag = "true";
            }
        }

        // 登録
        $data = array(
            "delivery_completed" => $completed_flag,
        );
        $where = "received_detail_id = '{$receivedDetailId}'";
        $gen_db->update("received_detail", $data, $where);

        return $completed_flag;
    }

    //************************************************
    // 印刷済みフラグのセット（出荷指示書）
    //************************************************

    static function setReceivedPrintedFlag($idArr, $isSet)
    {
        global $gen_db;

        $idWhere = join(",", $idArr);
        if ($idWhere == "")
            return;

        $query = "
        update
            received_detail
        set
            received_printed_flag = " . ($isSet ? 'true' : 'false') . "
            ,record_updater = '" . $_SESSION['user_name'] . "'
            ,record_update_date = '" . date('Y-m-d H:i:s') . "'
            ,record_update_func = '" . __CLASS__ . "::" . __FUNCTION__ . "'
        where
            received_detail_id in ({$idWhere})
        ";

        $gen_db->query($query);
    }

    //************************************************
    // 印刷済みフラグのセット（発注書）
    //************************************************

    static function setCustomerReceivedPrintedFlag($idArr, $isSet)
    {
        global $gen_db;

        $idWhere = join(",", $idArr);
        if ($idWhere == "")
            return;

        $query = "
        update
            received_detail
        set
            customer_received_printed_flag = " . ($isSet ? 'true' : 'false') . "
            ,record_updater = '" . $_SESSION['user_name'] . "'
            ,record_update_date = '" . date('Y-m-d H:i:s') . "'
            ,record_update_func = '" . __CLASS__ . "::" . __FUNCTION__ . "'
        where
            received_header_id in ({$idWhere})
        ";

        $gen_db->query($query);
    }

    //************************************************
    // 指定された受注の受注番号を返す
    //************************************************

    static function getReceivedNumberByTranId($receivedHeaderId)
    {
        $obj = self::_getTranDataById($receivedHeaderId, false);
        return $obj->received_number;
    }

    static function getReceivedNumberByDetailId($receivedDetailId)
    {
        $obj = self::_getTranDataById($receivedDetailId, true);
        return $obj->received_number;
    }

    //************************************************
    // 指定された受注の受注日を返す
    //************************************************

    static function getReceivedDateByTranId($receivedHeaderId)
    {
        $obj = self::_getTranDataById($receivedHeaderId, false);
        return $obj->received_date;
    }

    static function getReceivedDateByDetailId($receivedDetailId)
    {
        $obj = self::_getTranDataById($receivedDetailId, true);
        return $obj->received_date;
    }

    //************************************************
    // 指定された受注の得意先idを返す
    //************************************************

    static function getCustomerIdByTranId($receivedHeaderId)
    {
        $obj = self::_getTranDataById($receivedHeaderId, false);
        return $obj->customer_id;
    }

    static function getCustomerIdByDetailId($receivedDetailId)
    {
        $obj = self::_getTranDataById($receivedDetailId, true);
        return $obj->customer_id;
    }

    //************************************************
    // id(received_header_id or received_detail_id) をキーに received_headerのデータを取得
    //************************************************

    static function _getTranDataById($id, $isDetailId)
    {
        global $gen_db;

        $query = "select received_header.* from received_header";
        if ($isDetailId) {
            $query .= "
                inner join received_detail on received_detail.received_header_id = received_header.received_header_id
                where received_detail_id = '{$id}'
            ";
        } else {
            $query .= "
                where received_header_id = '{$id}'
            ";
        }
        return $gen_db->queryOneRowObject($query);
    }

    //************************************************
    // 指定された品目の標準販売単価を取得する
    //************************************************
    // 優先順位
    // (1) 得意先販売価格マスタ
    // (2) 取引先マスタ「掛率」×　標準販売単価1-3
    // (3) 取引先マスタ「掛率グループ」×　標準販売単価1-3
    // (4) 品目マスタ「標準販売単価1-3」
    //
    // 品目は必須。
    // 得意先は省略可能。省略した場合、得意先販売単価や掛率は考慮されない。
    // 販売数量は省略可能。省略した場合、標準販売単価2,3は適用されない。

    static function getSellingPrice($itemId, $customerId = null, $qty = null)
    {
        global $gen_db;

        $query = "
        select
            " . (is_numeric($customerId) ? "case when customer_price_master.selling_price is not null then customer_price_master.selling_price
                    else case when customer_master.price_percent is not null then gen_round_precision(t_price.selling_price * (customer_master.price_percent / 100.000000),customer_master.rounding,customer_master.precision)
                    else case when customer_master.price_percent_group_id is not null then gen_round_precision(t_price.selling_price * (price_percent_group_master.price_percent / 100.000000),customer_master.rounding,customer_master.precision)
                    else t_price.selling_price end end end" : "t_price.selling_price") . "
            as product_price

        from
            item_master
            inner join (
                select
                    item_id
                    ," . (is_numeric($qty) ? "case when selling_price_limit_qty_2 is not null and selling_price_limit_qty_2 < {$qty} then default_selling_price_3
                    else case when selling_price_limit_qty_1 is not null and selling_price_limit_qty_1 < {$qty} then default_selling_price_2
                    else default_selling_price
                    end end" : "default_selling_price") . "
                    as selling_price
                from
                    item_master
                where
                    item_id = '{$itemId}'
                ) as t_price on item_master.item_id= t_price.item_id
            " . (is_numeric($customerId) ? " left join customer_price_master on customer_price_master.customer_id = '{$customerId}' and item_master.item_id = customer_price_master.item_id
                    left join customer_master on customer_master.customer_id = '{$customerId}'
                    left join price_percent_group_master on customer_master.price_percent_group_id = price_percent_group_master.price_percent_group_id" : "") . "
        where
            item_master.item_id = '{$itemId}'
        ";
        $price = $gen_db->queryOneValue($query);

        return $price;
    }

    //************************************************
    // 取引先の受注が存在するかどうかを返す
    //************************************************
    static function existReceived($customerId)
    {
        global $gen_db;

        $query = "select customer_id from received_header where customer_id = {$customerId}";
        return $gen_db->existRecord($query);
    }

    //**************************************
    // ダミー品目の子品目使用予定数を登録/更新
    //**************************************
    // 受注品目がダミー品目だった場合、use_planに子品目使用予約を登録する。
    //　　一般品目の場合、製造指示書/外製指示書を登録した時点で子品目の使用予約も登録され、子品目の有効在庫が減る。
    //  　しかしダミー品目は製造指示書/外製指示書が発行されない（できない）ため、納品登録の時点まで子品目の有効在庫が変化しない。
    //  　そのため13iまでは、納品時になって在庫不足が発覚するという可能性があった。（ag.cgi?page=ProjectDocView&pPID=1516&pBID=178129）
    //　　15iではその問題を解決するため、ダミー品目を受注登録した時点で子品目の使用予約を登録するようにした。
    //　　use_plan.received_detail_id_for_dummy が登録されているレコードがこの用途のもの。
    //　　その使用予約は納品登録した時点で削除される。
    //　　一般品目とダミー品目では有効在庫が変化するタイミングが異なることに注意。

    static function entryUsePlanForDummy($receivedDetailId)
    {
        global $gen_db;

        // 既存データをいったん削除
        $query = "delete from use_plan where received_detail_id_for_dummy = '{$receivedDetailId}'";
        $gen_db->query($query);
        $query = "delete from received_dummy_child_item where received_detail_id_for_dummy = '{$receivedDetailId}'";
        $gen_db->query($query);

        // 受注品目がダミー品目以外の場合はここで終了
        $query = "
            select
                received_detail.item_id
                ,item_master.dummy_item
                ,delivery_completed
            from
                received_detail
                inner join item_master on received_detail.item_id = item_master.item_id
            where
                received_detail_id = '{$receivedDetailId}'
        ";
        $obj = $gen_db->queryOneRowObject($query);
        if (!$obj || $obj->dummy_item != 't') {
            return;
        }
        $itemId = $obj->item_id;
        $isCompleted = ($obj->delivery_completed == "t");

        // temp_real_child_item テーブル（ダミー品目をスキップした構成表）を作成
        Logic_Bom::createTempRealChildItemTable($itemId);

        // 構成を保存しておく。
        // 　構成表マスタ変更による不整合を避けるため、納品時の使用予約解除はその時点の構成表ではなく、
        // 　ここで保存した構成にもとづいて行う
        $query = "
        insert into received_dummy_child_item (
            received_detail_id_for_dummy
            ,child_item_id
            ,quantity
            ,record_creator
            ,record_create_date
            ,record_create_func
        )
        select
            '{$receivedDetailId}'
            ,child_item_id
            ,quantity
            ,'" . $_SESSION['user_name'] . "'
            ,'" . date('Y-m-d H:i:s') . "'
            ,'" . __CLASS__ . "::" . __FUNCTION__ . "'
        from
            temp_real_child_item
        ";
        $gen_db->query($query);

        // use_plan登録
        //  納品が完了している場合は、子品目引落済みなのでuse_planを登録してはいけない。
        //  また、一部納品が行われている場合は未納品に相当する分だけの使用予約を出す必要がある。
        if (!$isCompleted) {
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
                ,(received_quantity - coalesce(delivery_quantity,0)) * temp_real_child_item.quantity
                ,'" . $_SESSION['user_name'] . "'
                ,'" . date('Y-m-d H:i:s') . "'
                ,'" . __CLASS__ . "::" . __FUNCTION__ . "'
            from
                received_detail
                inner join temp_real_child_item on received_detail.item_id = temp_real_child_item.item_id
                left join (select received_detail_id, sum(delivery_quantity) as delivery_quantity from delivery_detail group by received_detail_id) as t_delivery
                    on received_detail.received_detail_id = t_delivery.received_detail_id
            where
                received_detail.received_detail_id = '{$receivedDetailId}'
            ";
            $gen_db->query($query);
        }
    }

    //************************************************
    // 受注時の構成と現在の構成表が一致しているかどうかを調べる（ダミー品目用）
    //************************************************
    // 受注品目がダミー品目であった場合、子品目の使用予約を登録した上で、受注時の構成が received_dummy_child_item に登録される。
    //　 同テーブルの内容と現時点の構成表が一致しているかどうかを調べる。

    static function isModifiedBomForDummy($receivedHeaderId)
    {
        global $gen_db;

        // ダミー品目受注だけをリストアップ
        $query = "
            select
                received_detail_id
            from
                received_detail
                inner join item_master on received_detail.item_id = item_master.item_id
            where
                received_header_id = '{$receivedHeaderId}'
                and coalesce(item_master.dummy_item,false)
        ";
        $arr = $gen_db->getArray($query);

        if (!$arr) {
            return;
        }

        foreach($arr as $row) {
            $receivedDetailId = $row['received_detail_id'];

            // temp_real_child_item テーブル（ダミー品目をスキップした構成表）を作成
            Logic_Bom::createTempRealChildItemTable($gen_db->queryOneValue("select item_id from received_detail where received_detail_id = '{$receivedDetailId}'"));

            // 不一致レコードを抽出
            $query = "
            select
                received_dummy_child_item.child_item_id
            from
                received_dummy_child_item
                inner join received_detail on received_dummy_child_item.received_detail_id_for_dummy = received_detail.received_detail_id
                left join temp_real_child_item on received_dummy_child_item.child_item_id = temp_real_child_item.child_item_id
                    and received_detail.item_id = temp_real_child_item.item_id
            where
                received_detail.received_detail_id = '{$receivedDetailId}'
                and received_dummy_child_item.quantity <> coalesce(temp_real_child_item.quantity,0)

            UNION

            /* 上のSQLでは「temp_real_child_itemにはあるがreceived_dummy_child_itemにはない」パターンが拾えないのでこちらで取得 */
            select
                received_dummy_child_item.child_item_id
            from
                temp_real_child_item
                left join received_dummy_child_item on temp_real_child_item.child_item_id = received_dummy_child_item.child_item_id
                    and received_dummy_child_item.received_detail_id_for_dummy in (select received_detail_id from received_detail where received_detail_id = '{$receivedDetailId}')
            where
                temp_real_child_item.item_id in (select item_id from received_detail where received_detail_id = '{$receivedDetailId}')
                and received_dummy_child_item.received_detail_id_for_dummy is null
            group by
                received_dummy_child_item.child_item_id
            ";
            if ($gen_db->existRecord($query)) {
                return true;
            }
        }
        return false;
    }

}