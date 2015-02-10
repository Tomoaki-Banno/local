<?php

class Manufacturing_SeibanExpand_BatchOrder
{

    function convert($converter, &$form)
    {
    }

    function validate($validator, &$form)
    {
    }

    function execute(&$form)
    {
        global $gen_db;

        // テンポラリテーブルの作成処理
        $query = "
        create temp table temp_expand_order_date (
            partner_class int,
            item_id int,
            seiban text,
            order_user_id int,
            quantity numeric,
            price numeric,
            order_date date,
            dead_line date,
            remarks text
        )
        ";
        $gen_db->query($query);

        //-----------------------------------------------------------
        // 対象データを展開
        //-----------------------------------------------------------
        // 手配日を本日で確定
        if (@$form["isToday"] == "true") {
            $isToday = true;
            $today = date('Y-m-d');
        } else {
            $isToday = false;
            $today = null;
        }

        foreach ($form as $name => $value) {
            if (substr($name, 0, 10) == "expand_id_") {
                // データ取得
                $idSerial = substr($name, 10, strlen($name) - 10);
                $idArr = preg_split('[_]', $idSerial);
                $detailId = $idArr[0];
                $itemId = $idArr[1];

                $query = "select seiban from received_detail where received_detail_id = '{$detailId}'";
                $seiban = $gen_db->queryOneValue($query);

                $partnerClass = @$form["partner_class_{$idSerial}"];
                if (!is_numeric($partnerClass)) {
                    // ag.cgi?page=ProjectDocView&pid=1574&did=215244
                    $form['gen_restore_search_condition'] = 'true';
                    return 'action:Manufacturing_SeibanExpand_Edit';
                }
                $quantity = @$form["order_quantity_{$idSerial}"];
                if ($isToday) {
                    $orderDate = $today;
                } else {
                    $orderDate = @$form["order_date_{$idSerial}"];
                }
                $deadLine = @$form["dead_line_{$idSerial}"];
                $remarks = @$form["remarks_{$idSerial}"];

                // データチェック
                if (!in_array($partnerClass, array(0, 1, 2, 3)))
                    continue;
                if (!is_numeric($quantity))
                    continue;
                if (!Gen_String::isDateString($deadLine))
                    continue;

                // 注文、外製指示
                if ($partnerClass != "3") {
                    // データ取得
                    $orderUserId = @$form["order_user_id_{$idSerial}"];
                    $price = @$form["order_price_{$idSerial}"];

                    // データチェック
                    if (!is_numeric($orderUserId))
                        continue;
                    if (!is_numeric($price))
                        continue;
                } else {
                    $orderUserId = 0;
                    $price = 0;
                }

                // テンポラリテーブルに登録
                $query = "
                insert into temp_expand_order_date (
                    partner_class,
                    item_id,
                    seiban,
                    order_user_id,
                    quantity,
                    price,
                    order_date,
                    dead_line,
                    remarks
                 )
                 values (
                    {$partnerClass},
                    {$itemId},
                    '{$seiban}',
                    {$orderUserId},
                    {$quantity},
                    {$price},
                    '{$orderDate}'::date,
                    '{$deadLine}'::date,
                    '{$remarks}'
                 );
                ";
                $gen_db->query($query);
            }
        }

        // トランザクション開始
        $gen_db->begin();

        $query = "
        select
            partner_class,
            item_id,
            seiban,
            order_user_id,
            quantity,
            price,
            order_date,
            dead_line,
            remarks
        from
            temp_expand_order_date as t01
            inner join (select item_id as imid02, item_code from item_master) as t02 on t01.item_id = t02.imid02
        order by
            partner_class,
            order_user_id,
            dead_line,
            seiban,
            item_code
        ";

        if (!$arr = $gen_db->getArray($query)) {
            $gen_db->commit();
            $form['gen_restore_search_condition'] = 'true';
            return 'action:Manufacturing_SeibanExpand_Edit';
        }

        //-----------------------------------------------------------
        // データの登録（To order_header/detail）
        //-----------------------------------------------------------
        //  発注（発注書）の場合は、発注先と発行日付が一致している場合、一枚の発注書にまとめる。
        //  内製（製造指示書）の場合は、すべてバラ発行。

        $partnerClassCache = "";
        $orderUserIdCache = "";
        $orderDateCache = "";
        $res = array();
        $cnt0 = 0;
        $cnt1 = 0;
        $cnt2 = 0;

        set_time_limit(600);

        $lineNo = 1;
        foreach ($arr as $row) {

            // 登録処理（注文書以外は親テーブルを作成する）
            if ($row['partner_class'] != "0" || $orderUserIdCache != $row['order_user_id'] || $orderDateCache != $row['order_date']) {

                // オーダー区分番号取得
                // 0:製造指示書、1:注文書、2:外製指示書
                $classification = ($row['partner_class'] == "3" ? 0 : ($row['partner_class'] == "0" ? 1 : 2));

                // 親テーブル登録
                $orderHeaderId = Logic_Order::entryOrderHeader(
                    $classification
                    , null   // order_header_id
                    , null   // order_id_for_user
                    , $row['order_date']
                    , $row['order_user_id']
                    , $row['remarks']
                    , null   // worker_id
                    , null   // section_id
                    , null   // delivery_partner_id
                    , false  // mrp_flag
                );

                // 戻り値(取り込んだorder_header_idのリスト)を準備
                $res[] = $orderHeaderId;

                // 次へ
                $lineNo = 1;
                $partnerClassCache = $row['partner_class'];
                $orderUserIdCache = $row['order_user_id'];
                $orderDateCache = $row['order_date'];
            }

            // 子テーブル登録
            // （同時に子品目の使用予約・支給処理も行う）
            Logic_Order::entryOrderDetail(
                null
                , $orderHeaderId
                , $lineNo++
                , null      // order_no
                , $row['seiban']
                , $row['item_id']
                , null      // item_code
                , null      // item_name
                , $row['price']
                , null      // item_sub_code
                , $row['quantity']
                , $row['dead_line']
                , false     // alarm_flag
                , (isset($row['order_user_id']) && is_numeric($row['order_user_id']) ? $row['order_user_id'] : 0)
                , -1        // payout_location_id
                , 0         // source_lot_id
                , null      // planDate
                , null      // planQty
                , null      // handQty
                , ""        // order_measure
                , 1         // multiple_of_order_measure
                , null      // remarks
                , false     // processFlag
            );

            if ($classification == "0")
                $cnt0++;
            if ($classification == "1")
                $cnt1++;
            if ($classification == "2")
                $cnt2++;
        }

        Gen_Log::dataAccessLog(_g("製番展開"), _g("一括確定"), sprintf(_g("[%1\$s]　%2\$s：%3\$s件, %4\$s：%5\$s件, %6\$s：%7\$s件"), _g("確定オーダー"), _g("製造指示書"), $cnt0, _g("注文書"), $cnt1, _g("外製指示書"), $cnt2));

        // コミット
        $gen_db->commit();

        if (isset($form['windowOpen'])) {
            return 'windowclose.tpl';
        } else {
            $form['gen_restore_search_condition'] = 'true';
            return 'action:Manufacturing_SeibanExpand_Edit';
        }
    }

}