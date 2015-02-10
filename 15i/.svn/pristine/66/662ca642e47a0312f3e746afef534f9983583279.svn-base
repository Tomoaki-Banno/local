<?php

// 抽象クラス（abstract）。インスタンスを生成できない。
//   PHP4のときは「abstract」を外すこと。

abstract class Base_BulkDeleteBase
{

    var $listAction;
    var $deleteAfterAction;
    var $isDetailMode;
    var $deleteIdArray = array();
    var $recordIdArray = array();
    var $numberArray = array();
    var $numberDetailArray = array();
    var $log1;
    var $log2;
    var $afterDeleteMessage;

    //************************************************
    // 抽象メソッド
    //************************************************
    // 子クラスで必ず実装しなければならない。
    //   PHP4の時はコメントアウトすること

    abstract function setParam(&$form);

    abstract function _validate($validator, &$form);

    abstract function setLogParam($form);

    abstract function _delete(&$form);

    //************************************************
    // メイン
    //************************************************
    // リロード(ボタン2重押し)チェック　兼　CSRF対策
    // validator の前に呼ばれる(リロード時にvalidatorエラーになるのを防ぐため)
    // リロードエラー時は、リダイレクト先Action名を返すようにする。
    // setParam内でformを書き換えることがあるので、引数には&をつける。
    function reloadCheck(&$form)
    {
        $this->setParam($form);        // listActionを取得するためここで処理

        if (!isset($this->errorAction))
            $this->errorAction = $this->listAction;

        // 以前は、$form['gen_page_request_id'] がセットされない画面はノーチェックだった。
        // （一括登録画面など。リロード対策のことしか意識していなかったので、一括登録はノーチェックでもよいと考えていた）、
        // しかし、このチェックがCSRF対策にもなっていることを踏まえて、必ずチェックすることにした。
        // List画面では、list.tpl で上記パラメータがセットされてPOSTされる。
        //
        if (!Gen_Reload::reloadCheck($form['gen_page_request_id'])) {
            return "action:" . $this->errorAction;
        }
        return "";
    }

    function validate($validator, &$form)
    {
        // アクセス権チェック
        if ($form['gen_readonly'] == 'true') {
            $validator->raiseError(_g("アクセス権がありません。"));
            return $this->errorAction;
        }

        // validate
        $this->_validate($validator, $form);

        // if error
        $form['gen_restore_search_condition'] = 'true';
        return "action:" . $this->errorAction;
    }

    // 削除処理
    function execute(&$form)
    {
        global $gen_db;

        //------------------------------------------------------
        //  トランザクション開始
        //------------------------------------------------------
        $gen_db->begin();

        //------------------------------------------------------
        //　ログ用パラメータ
        //------------------------------------------------------
        // 削除処理の前段階で情報を取得しておく
        $this->setLogParam($form);

        //------------------------------------------------------
        //　一括削除処理
        //------------------------------------------------------
        // 削除idが存在する場合のみ削除処理実行
        if (count($this->deleteIdArray) > 0)
            $this->_delete($form);

        //------------------------------------------------------
        //　添付ファイルとトークボードの削除
        //------------------------------------------------------
        if (isset($form['gen_action_group']) && count($this->deleteIdArray) > 0) {
            // 明細リスト削除時に添付ファイルidが指定される
            if (isset($this->recordIdArray) && count($this->recordIdArray) > 0) {
                $idCsv = join(',', $this->recordIdArray);
            } else {
                $idCsv = join(',', $this->deleteIdArray);
            }

            // 添付ファイル
            $query = "select file_name from upload_file_info where action_group = '{$form['gen_action_group']}' and record_id in ({$idCsv})";
            $fileNameArr = $gen_db->getArray($query);
            if ($fileNameArr) {
                $storage = new Gen_Storage("Files");
                foreach($fileNameArr as $fileName) {
                    $storage->delete($fileName['file_name']);
                }
            }
            $query = "delete from upload_file_info where action_group = '{$form['gen_action_group']}' and record_id in ({$idCsv})";
            $gen_db->query($query);

            // トークボード
            $query = "select chat_header_id from chat_header where action_group = '{$form['gen_action_group']}' and record_id in ({$idCsv})";
            $headerIdArr = $gen_db->getArray($query);
            if ($headerIdArr) {
                foreach($headerIdArr as $row) {
                    Logic_Chat::deleteChat($row["chat_header_id"]);
                }
            }
        }

        //------------------------------------------------------
        //　一括削除後メッセージとログ
        //------------------------------------------------------
        $form['gen_afterEntryMessage'] = $this->afterDeleteMessage == "" ? _g("削除しました。") : $this->afterDeleteMessage;
        Gen_Log::dataAccessLog($this->log1, _g("一括削除"), $this->log2);

        //------------------------------------------------------
        //  コミット
        //------------------------------------------------------
        $gen_db->commit();

        //------------------------------------------------------
        //  削除後の遷移
        //------------------------------------------------------
        $form['gen_restore_search_condition'] = 'true';
        return "action:" . $this->deleteAfterAction;
    }

    function _checkData($validator, $msg, $code, $query)
    {
        global $gen_db;

        if ($gen_db->existRecord($query)) {
            $validator->raiseError(sprintf($msg, htmlspecialchars($code, ENT_QUOTES)));
            return false;
        }
        return true;
    }

    function _makeNumberCsvForMsg($numberCsv)
    {
        if (strlen($numberCsv) > 50) {
            // 50文字以上は省略
            $msg = substr($numberCsv, 0, 50) . '...';
        } else {
            $msg = $numberCsv;
        }
        return $msg;
    }

}