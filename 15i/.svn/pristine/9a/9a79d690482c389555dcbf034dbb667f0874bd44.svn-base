<?php
class Mobile_StockInput_Edit
{
    function execute(&$form)
    {
        global $gen_db;

        // StockInput_Listからのパラメータ受け取り
        if (isset($form['param'])) {
            $arr = explode("___", $form['param']);
            $form['inventory_date'] = $arr[0];
            $form['item_code'] = $arr[1];
            $form['location_id'] = $arr[2];
            $form['seiban'] = $arr[3];

            $query = "select item_id from item_master where item_code = '".$gen_db->quoteParam($arr[1])."'";
            $itemId  = $gen_db->queryOneValue($query);

            if (Gen_String::isDateString($arr[0])
                    && is_numeric($itemId)
                    && Gen_String::isNumeric($arr[2])) {

                $query = "
                select
                    inventory_quantity
                    ,remarks
                from
                    inventory
                where
                    inventory_date = '{$arr[0]}'
                    and item_id = '{$itemId}'
                    and location_id = '{$arr[2]}'
                    and seiban = '" . $gen_db->quoteParam($arr[3]) . "'
                ";
                $obj = $gen_db->queryOneRowObject($query);

                $form['inventory_quantity'] = $obj->inventory_quantity;
                $form['remarks'] = $obj->remarks;

                // temp_stockテーブルにデータを取得
                Logic_Stock::createTempStockTable(
                    $arr[0],                        // date
                    $itemId,
                    $gen_db->quoteParam($arr[3]),   // seiban
                    $arr[2],                        // location_id
                    "sum",                          // lot
                    false,                          // 有効在庫も取得
                    false,  // サプライヤー在庫を含めるかどうか
                    // use_plan の全期間分差し引くかどうか。差し引くならtrue。指定日分までの引当・予約しか含めないならfalse。
                    //  これをtrueにするかfalseにするかは難しい。有効在庫の値をFlowおよびHistoryとあわせるにはfalseに
                    //  する必要があるが、受注管理画面「引当可能数」と合わせるにはtrueにする必要がある。
                    false);

                $query = "select logical_stock_quantity from temp_stock";
                $form['logical_stock_quantity']  = $gen_db->queryOneValue($query);
            }
        }

        $form['gen_pageTitle'] = _g("棚卸登録");

        $form['gen_headerLeftButtonURL'] = "index.php?action=Mobile_StockInput_List";
        $form['gen_headerLeftButtonIcon'] = "delete";
        $form['gen_headerLeftButtonText'] = _g("閉じる");

        // ページリクエストIDの発行処理
        global $_SESSION;
        $reqId = sha1(uniqid(rand(), true));
        $_SESSION['gen_page_request_id'][] = $reqId;
        $form['gen_page_request_id'] = $reqId;

        // ロケ選択肢
        $query = "select location_id, location_name from location_master order by location_code";
        $form['locationOptions'] = $gen_db->getHtmlOptionArray($query, false, array("0"=>GEN_DEFAULT_LOCATION_NAME));

        // 日付指定が不正のときは、前月末とする
        if (!Gen_String::isDateString(@$form['inventory_date'])) {
            $form["inventory_date"] = Gen_String::getLastMonthLastDateString();
        }

        return 'mobile_stockinput_edit.tpl';
    }
}