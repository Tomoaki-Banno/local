<?php

class Stock_Assessment_AjaxDeleteAssessmentPrice extends Base_AjaxBase
{

    function _execute(&$form)
    {
        global $gen_db;

        $gen_db->begin();
        
        // 最終とその前の在庫評価単価の更新日を取得
        $lastDate = "";
        $lastDate2 = "";
        $query = "select assessment_date from stock_price_history group by assessment_date order by assessment_date desc limit 2";
        $assArr = $gen_db->getArray($query);
        if ($assArr) {
            $lastDate = $assArr[0]["assessment_date"];
            $lastDate2 = isset($assArr[1]["assessment_date"]) ? $assArr[1]["assessment_date"] : "";
        }
        
        // 削除ができるのは、評価単価更新が2回以上行われている場合のみ。
        // （削除機能は「前回の更新の直前の状態に戻す機能」ではなく「前々回の更新の直後の状態に戻す機能」なので）
        // ローカルでもチェックしているが、ここでもチェックする。
        if ($lastDate2 == "") {
            return;
        }
        
        // 2回前の更新直後の時点の在庫評価単価をテンポラリテーブルに取得する。
        //  削除後、この値が在庫評価単価となる。
        //  単純に stock_price_history から2回前の更新履歴データを持ってくるだけではダメ。
        //  前回更新が部分更新（発注品のみ or 内製品のみ）だった場合、さらにそれ以前のデータを取得する必要がある。
        Logic_Stock::createTempStockPriceTable($lastDate2);
        
        // データの更新
        $query = "
            /* 履歴テーブルから最終の在庫評価単価更新を削除する */
            delete from stock_price_history where assessment_date = '{$lastDate}';
                
            /* item_masterの在庫評価単価を更新する */
            update
                item_master
            set
                stock_price = temp_stock_price.stock_price
            from
                temp_stock_price
            where
                item_master.item_id = temp_stock_price.item_id;
        ";
        $gen_db->query($query);

        // データアクセスログ
        $log = _g("基準日") . _g("：") . $lastDate;
        Gen_Log::dataAccessLog(_g("在庫評価単価"), _g("削除"), $log);

        // 通知メール
        $title = ("在庫評価単価の更新");
        $body = _g("最新の在庫評価単価が削除されました。") . "\n\n"
                . "[" . _g("削除日時") . "] " . date('Y-m-d H:i:s') . "\n"
                . "[" . _g("削除者") . "] " . $_SESSION['user_name'] . "\n\n"
                . "";
        Gen_Mail::sendAlertMail('stock_assessment_update', $title, $body);

        $gen_db->commit();

        return
            array(
                "result" => "success"
            );
    }

}