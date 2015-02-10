<?php

class Manufacturing_Estimate_CopyToReceived
{

    function validate(&$validator, &$form)
    {
        // リロードチェック兼CSRF対策
        if (!Gen_Reload::reloadCheck($form['gen_page_request_id'])) {
            $validator->raiseError("");
        }
        
        if ($form['received_date'] != "") {
            $validator->salesLockDateOrLater('received_date', _g("受注日"));
        }
        if ($form['dead_line'] != "") {
            $validator->salesLockDateOrLater('dead_line', _g("納品日"));
        }

        $form['gen_restore_search_condition'] = 'true';
        return 'action:Manufacturing_Estimate_List';        // if error
    }

    function execute(&$form)
    {
        global $gen_db;

        // アクセス権チェック
        if ($form['gen_readonly'] == 'true') {
            return "action:Manufacturing_Estimate_List";
        }

        // パラメータ取得
        $isDetail = isset($form['detail']) && $form['detail'] == 'true';
        $receivedDate = $form['received_date'];
        $deadLine = $form['dead_line'];

        // id取得
        $idArr = array();
        foreach ($form as $name => $value) {
            if (substr($name, 0, 8) == "chk_rec_") {
                $idArr[] = substr($name, 8, strlen($name) - 8);
            }
        }

        // トランザクション開始
        $gen_db->begin();

        // メイン
        $logIdArr = array();
        if ($isDetail) {
            // detail_idが送られてきているとき。header_idに変換して処理
            $query = "select distinct estimate_header_id from estimate_detail where estimate_detail_id in (" . join($idArr, ',') . ")";
            $arr = $gen_db->getArray($query);
            if ($arr) {
                foreach ($arr as $row) {
                    $receiveHeaderId = Logic_Estimate::estimateToReceived($row['estimate_header_id'], $receivedDate, $deadLine);
                    $estimateNumber = $gen_db->queryOneValue("select estimate_number from estimate_header where estimate_header_id = '{$row['estimate_header_id']}'");
                    $receivedNumber = $gen_db->queryOneValue("select received_number from received_header where received_header_id = '{$receiveHeaderId}'");
                    $logIdArr[$estimateNumber] = $receivedNumber;
                }
            }
        } else {
            // header_idが送られてきているとき
            foreach ($idArr as $id) {
                $receiveHeaderId = Logic_Estimate::estimateToReceived($id, $receivedDate, $deadLine);
                $estimateNumber = $gen_db->queryOneValue("select estimate_number from estimate_header where estimate_header_id = '{$id}'");
                $receivedNumber = $gen_db->queryOneValue("select received_number from received_header where received_header_id = '{$receiveHeaderId}'");
                $logIdArr[$estimateNumber] = $receivedNumber;
            }
        }

        //
        $form['gen_afterEntryMessage'] = _g("受注への転記を実行しました。");

        // データアクセスログ
        $msg = "";
        foreach ($logIdArr as $key => $value) {
            if ($msg != "")
                $msg .= ", ";
            $msg .= "[" . _g("見積番号") . "/" . _g("受注番号") . "] {$key} / {$value}";
        }
        Gen_Log::dataAccessLog(_g("見積"), _g("受注へ転記"), $msg);

        // コミット
        $gen_db->commit();

        $form['gen_restore_search_condition'] = 'true';
        return 'action:Manufacturing_Estimate_List';
    }

}
