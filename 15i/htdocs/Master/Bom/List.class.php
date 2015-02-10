<?php

class Master_Bom_List
{

    function execute(&$form)
    {
        global $gen_db;
        
        // CSVインポートモード
        if (isset($form['gen_csvMode']) && $form['gen_csvMode'] == "Import") {
            // アップロードされたファイルのセキュリティチェックとリロードチェック
            $key = array_search($form['gen_csv_page_request_id'], $_SESSION['gen_csv_page_request_id']);
            if ($form['gen_readonly'] != "true"
                    && is_uploaded_file(@$_FILES['uploadFile']['tmp_name']) && @$_FILES['uploadFile']['size'] > 0
                    && $key !== false) {
                
                // ファイル名チェック
                $fileNameError = Gen_String::checkSjisDependencyChar($_FILES['uploadFile']['name']);
                if ($fileNameError != -1) {
                    $form['msg'] = _g("ファイル名に機種依存文字が含まれているため登録できません。");
                    if ($fileNameError >= 0) 
                        $form['msg'] .= sprintf(_g("（%s文字目）"),$fileNameError);
                    $obj = array(
                       'msg' => $form['msg'],
                        'success' => false,
                        'reqId' => $_SESSION['gen_csv_page_request_id'],
                    );
                    $form['response_noEscape'] = json_encode($obj);
                    return 'simple.tpl';
                }

                // インポート処理実行
                $gen_db->begin();
                $csvBeginTime = date('Y-m-d H:i:s');                        
                $form['msg'] = Logic_BomCsv::CsvImport($_FILES['uploadFile']['tmp_name'], @$form['allowUpdate']);
                $csvEndTime = date('Y-m-d H:i:s');      

                // ループチェックとLLC計算
                if (is_array($form['msg'])) {
                    $form['success'] = false;
                } else {
                    if (Logic_Bom::calcLLC()) {
                        $form['success'] = true;
                        $gen_db->commit();

                        // データアクセスログ
                        // basename()は使用しない。日本語の文字化け問題があるため。
                        $fileName = $_FILES['uploadFile']['name'];
                        $fileName = Gen_String::cutSjisDependencyChar($fileName);
                        $fileName = $gen_db->quoteParam($fileName);
                        Gen_Log::dataAccessLog(_g("構成表マスタCSVインポート"), "", "[" . _g("ファイル名") . "] $fileName");

                    } else {
                        $form['msg'] = _g("構成ループが発生しました。構成の内容を確認してください。");
                        $form['success'] = false;
                        $gen_db->rollback();
                    }
                }
            } else {
                $form['msg'] = _g("システムエラーです。"); 
                $form['success'] = false;
            }
            
            if ($form['success']) {
                // インポート後に表示する親品目を決める
                $query = "
                    select
                        item_code
                    from
                        bom_master
                        inner join item_master on bom_master.item_id = item_master.item_id
                    where
                        bom_master.record_create_date >= '{$csvBeginTime}'
                        and bom_master.record_create_date <= '{$csvEndTime}'
                        and bom_master.record_creator = '".$_SESSION['user_name']."'
                    order by
                        bom_master.record_create_date
                    limit 1
                ";
                $showItemCode = $gen_db->queryOneValue($query);
            }

            global $_SESSION;
            $reqId = sha1(uniqid(rand(), true));
            $_SESSION['gen_csv_page_request_id'][] = $reqId;

            $obj = array(
                'msg' => $form['msg'],
                'success' => $form['success'],
                'reqId' => $reqId,
                'notShowOnlyCheck' => true,    // 「いまインポートしたデータのみ」は表示しない
                'isBOM' => true,
                'showItemCode' => $form['success'] ? $showItemCode : "",
            );
            $form['response_noEscape'] = json_encode($obj);
            
            return 'simple.tpl';
        }
        
        // タブ、マイメニュー用
        $form['gen_pageTitle'] = _g("構成表マスタ");
        $form['gen_pageHelp'] = _g("構成表マスタ");

        $query = "select item_group_id, substr(item_group_name,0,20) as item_group_name from item_group_master order by item_group_code";
        $form['option_item_group'] = $gen_db->getHtmlOptionArray($query, true);

        // for listbox style
        $form['gen_dropdown_perpage'] = GEN_DROPDOWN_PER_PAGE;

        // for Export
        $form['gen_totalCount'] = $gen_db->queryOneValue("select count(*) from bom_master");

        // 品目マスタ登録画面の[この品目の構成を登録する]リンクからの遷移に対応
        if (isset($form['is_item_master']) && $form['is_item_master'] == "true") {
            $query = "select item_code from item_master where item_id = '{$form['parent_item_id']}'";
            $form['parent_item_code'] = $gen_db->queryOneValue($query);
        }

        // ピン
        $genPins = array();
        $userId = Gen_Auth::getCurrentUserId();
        $action = $form['action'];
        $colInfoJson = $gen_db->queryOneValue("select pin_info from page_info where user_id = '{$userId}' and action = '{$action}'");
        // 登録の際に「\」が「￥」に自動変換されているので、ここで元に戻す必要がある。
        if (($colInfoObj = json_decode(str_replace("￥", "\\", $colInfoJson))) != null) {
            foreach ($colInfoObj as $key => $val) {
                if (!isset($form[$key])) {    // 表示条件がユーザーにより指定された場合は読み出ししない
                    $form[$key] = $val;
                }
                $genPins[] = $key;
            }
        }
        $form['gen_pin_html_itemgroup'] = Gen_String::makePinControl($genPins, $action, "item_group_id");
        $form['gen_pin_html_searchtext'] = Gen_String::makePinControl($genPins, $action, "searchText");
        
        // CSVインポート関連
        global $_SESSION;
        $reqId = sha1(uniqid(rand(), true));
        $_SESSION['gen_csv_page_request_id'][] = $reqId;
        $form['gen_csv_page_request_id'] = $reqId;

        //  上書きインポート機能は員数の書き換えしかできず、そのことが混乱を招いていた。
        //  08iでは画面での員数書き換えが簡単になったこともあり、不要と判断し廃止した。
        $form['gen_allowUpdateCheck'] = false;

        $form['gen_importMsg_noEscape'] =
                _g("※データは新規登録されます。（既存データの上書きはできません）") . "<br><br>" .
                _g("※現時点で未完了の製造/外製指示がある場合、実績/外製受入登録時" .
                        "子品目の引き落としは製造/外製指示登録時の構成に基づいて行われます（今回の構成変更は反映" .
                        "されないことに注意してください）。今回の構成変更を反映させたい場合は、製造指示登録画面で再登録する必要があります。");
        $form['gen_importMax'] = GEN_CSV_IMPORT_MAX_COUNT;
        $form['gen_importFromEncoding'] = GEN_CSV_IMPORT_FROM_ENCODING;

        return 'master_bom_list.tpl';
    }

}
