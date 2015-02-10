<?php

class Stock_Assessment_ImportInitData
{
    function execute(&$form)
    {
        global $gen_db;
        
        // トークンの確認（CSRF対策）
        //　　Ajax用のものを流用。トークンについての詳細はAjaxBaseのコメントを参照。
        if (!isset($form['gen_ajax_token']) || $_SESSION['gen_ajax_token'] != $form['gen_ajax_token']) {
            $form['response_noEscape'] = json_encode(array("status" => "tokenError", "success" => false, "msg" => ""));
            return 'simple.tpl';
        }
        
        $errMsg = "";
        
        if (!isset($form['initDate']) || !Gen_String::isDateString($form['initDate'])) {
            $errMsg = _g("基準日が正しくありません。");
        } else {
            $query = "select max(assessment_date) from stock_price_history";
            $assDate = $gen_db->queryOneValue($query);
            if ($assDate) {
                if (strtotime($assDate) >= strtotime($form['initDate'])) {
                    $errMsg = _g("基準日には、前回更新時の基準日よりも後の日付を指定してください。");
                }
            }
        }
        
        if ($errMsg == "") {
            if (is_uploaded_file(@$_FILES['uploadFile']['tmp_name']) && @$_FILES['uploadFile']['size'] > 0) {
                $gen_db->begin();
                
                $query = "        
                    PREPARE prepare1 (int, numeric, numeric) as
                    insert into stock_price_history (
                        assessment_date
                        ,item_id
                        ,stock_price
                        ,stock_quantity
                    ) values (
                        '{$form['initDate']}'
                        ,$1
                        ,$2
                        ,$3
                    );
                ";
                $gen_db->query($query);
                
                $fp = fopen($_FILES['uploadFile']['tmp_name'], 'r');
                $line = 1;
                $errArr = array();
                while ($data = fgets($fp)) {
                    // 文字コードをUTFに変換
                    $data = mb_convert_encoding($data, "UTF-8", GEN_CSV_IMPORT_FROM_ENCODING);

                    // 行末の改行コードをカット
                    if (substr($data, -1) == "\n") {
                        $data = substr($data, 0, strlen($data) - 1);
                    }
                    if (substr($data, -1) == "\r") {
                        $data = substr($data, 0, strlen($data) - 1);
                    }

                    // 文字列をカンマで分割して配列に格納
                    $dataArray = Gen_Csv::splitExt($data);
                    
                    $isErr = false;

                    $query = "select item_id from item_master where item_code = '" . ($gen_db->quoteParam($dataArray[0])) . "'";
                    $itemId = $gen_db->queryOneValue($query);
                    if (!$itemId) {
                        $errArr[] = array($line, _g("品目コードが正しくありません。"));
                        $isErr = true;
                    }
                    if (!Gen_String::isNumeric($dataArray[1])) {
                        $errArr[] = array($line, _g("在庫評価単価が正しくありません。"));
                        $isErr = true;
                    }
                    if (!Gen_String::isNumeric($dataArray[2])) {
                        $errArr[] = array($line, _g("在庫数が正しくありません。"));
                        $isErr = true;
                    }
                    
                    if (!$isErr) {
                        $gen_db->query("EXECUTE prepare1 ({$itemId},$dataArray[1],$dataArray[2])");
                    }
                    $line++;
                }

                fclose($fp);
                
                if (count($errArr) == 0) {
                    // データアクセスログ
                    Gen_Log::dataAccessLog(_g("在庫評価単価"), _g("初期値インポート"), _g("基準日") . _g("：") . $form['initDate']);

                    // 通知メール
                    $title = ("在庫評価単価の更新");
                    $body = _g("在庫評価単価更新の総平均法の初期値がインポートされました。") . "\n\n"
                            . "[" . _g("更新日時") . "] " . date('Y-m-d H:i:s') . "\n"
                            . "[" . _g("更新者") . "] " . $_SESSION['user_name'] . "\n\n"
                            . "[" . _g("基準日") . "] " . $form['initDate'] . "\n"
                            . "";
                    Gen_Mail::sendAlertMail('stock_assessment_update', $title, $body);

                    // commit
                    $gen_db->commit();
                } else {
                    $errMsg = $errArr;
                }

            } else {
                // ファイルサイズ不正か、ファイルがアップロードされていないとき
                $errMsg = _g("登録に失敗しました。ファイルサイズが大きすぎます。");
            }
        }

        $obj = array(
            'msg' => $errMsg,
            'success' => (!is_array($errMsg) && $errMsg == ""),
        );
        $form['response_noEscape'] = json_encode($obj);
        return 'simple.tpl';
    }
}