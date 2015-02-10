<?php

// 抽象クラス（abstract）。インスタンスを生成できない。

abstract class Base_EntryBase
{

    var $model;
    var $entryMode;
    var $listAction;
    var $modelName;
    var $columns;
    var $isNew;
    var $log1;
    var $log2;
    var $logCategory;
    var $newRecordNextAction;
    var $newRecordNotKeepField;
    var $afterEntryMessage;
    var $afterEntryMessage_noEscape;
    // 明細がある場合（EditList/Bulk/Barcode）のみ使用
    var $headerArray;
    var $detailArray;
    var $originalDetailArray;
    var $detailIdArray;
    var $detailFields;
    var $lineCount;
    var $isListMode;

    //************************************************
    // 抽象メソッド
    //************************************************
    // 子クラスで必ず実装しなければならない。
    //   PHP4の時はコメントアウトすること

    abstract function setParam(&$form);

    abstract function setLogParam($form);

    //************************************************
    // メイン
    //************************************************
    // リロード(ボタン2重押し)チェック　兼　CSRF対策
    // convertor / validator の前に呼ばれる(リロード時にvalidatorエラーに
    // なるのを防ぐため)
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
        // Edit画面では、edit/editmodal.tpl で上記パラメータがセットされてPOSTされる。
        // BulkEdit画面では、listtable.tpl にhiddenとして出力された上記パラメータを、gen.postSubmit.submit() で読み取ってPOSTしている。
        if ($_SESSION['gen_app']) {
            if (!isset($form['gen_ajax_token']) || $_SESSION['gen_ajax_token'] != $form['gen_ajax_token']) {
                $form['response_noEscape'] = "tokenError";
                return 'simple.tpl';
            }
        } else { 
            if (!Gen_Reload::reloadCheck($form['gen_page_request_id'])) {
                return "action:" . $this->errorAction;
            }
        }
        return "";
    }

    function convert($converter, &$form)
    {
        // 各種初期設定
        $this->model = new $this->modelName();
        $this->isNew = $this->model->isNew($form);

        // カスタム項目
        if (isset($form['gen_customColumnArray']) && isset($this->headerArray)) {
            if (isset($this->detailArray) && isset($_SESSION['gen_setting_company']->customcolumnisdetail)) {
                $isDetailArr = $_SESSION['gen_setting_company']->customcolumnisdetail;
                if (is_object($isDetailArr)) {
                    $isDetailArr = get_object_vars($isDetailArr);
                }
            }
            foreach($form['gen_customColumnArray'] as $customCol => $customArr) {
                if (isset($isDetailArr[$customArr[2]]) && $isDetailArr[$customArr[2]]) {
                    $this->detailArray[] = "gen_custom_{$customCol}";
                } else {
                    $this->headerArray[] = "gen_custom_{$customCol}";
                }
            }
        }        
        
        if (isset($form['gen_multiEditKey'])) {
            global $gen_db;

            $form['gen_multi_edit'] = true;

            // 一括編集の場合、1レコードごとにこのクラスが呼ばれる（再帰呼び出し）。
            // 複数のレコードのうち、今回登録するレコードを決め、そのキーを取得する。
            if (!isset($form['gen_multiEditKeyNum'])) {
                $form['gen_multiEditKeyNum'] = 0;
                // 一括編集全体にわたるトランザクション
                $gen_db->begin();
            }
            $multiEditKeyArray = explode(",", $form['gen_multiEditKey']);
            $key = $multiEditKeyArray[$form['gen_multiEditKeyNum']];

            // [multi]を既存データに置換する
            require_once(Gen_File::safetyPathForAction($form['gen_multiEditAction']));
            $edit = new $form['gen_multiEditAction'];
            $edit->setQueryParam($form);
            $query = str_replace("[Where]", "where {$edit->keyColumn} = '{$key}'", $edit->selectQuery);
            if (isset($form['gen_customColumnArray'])) {
                $table = $form['gen_customColumnTable'];
                $selectPos = stripos($query, 'select') + 6;
                $originalQuery = $query;
                $query = substr($originalQuery, 0, $selectPos) . " ";
                $isDetailArr = array();
                if (isset($_SESSION['gen_setting_company']->customcolumnisdetail)) {
                    $isDetailArr = $_SESSION['gen_setting_company']->customcolumnisdetail;
                    if (is_object($isDetailArr)) {
                        $isDetailArr = get_object_vars($isDetailArr);
                    }
                }
                foreach($form['gen_customColumnArray'] as $customCol => $customArr) {
                    if (!isset($isDetailArr[$customArr[2]]) || !$isDetailArr[$customArr[2]]) {
                        // select listでワイルドカード指定されていたときのため、エイリアスをつけておく
                        $query .= "{$table}.{$customCol} as gen_custom_{$customCol},";
                    }
                }
                $query .= substr($originalQuery, $selectPos + 1);
            }

            $obj = $gen_db->queryOneRowObject($query);

            $form[$edit->keyColumn] = $key;
            $form['gen_multiEditKeyColumn'] = $edit->keyColumn;
            if ($obj) {
                $this->multiColumnArray = array();
                foreach($obj as $name=>$val) {
                    if (isset($form[$name]) && $form[$name]=="[multi]") {
                        if ($val === null) $val = "";   // 既存値がnullの場合、通常の編集モードではフォーム上で空白値になるので、登録時も空白値となる
                        $form[$name] = $val;
                        $this->multiColumnArray[] = $name;
                    }
                }
            }

            // キーの取得
            $form['gen_multiEditKeyArray'] = explode(',', $form[$edit->keyColumn]);

            $cols = "";
            foreach($form['gen_multiEditKeyArray'] as $row) {
                if ($cols != "") $cols .= ",";
                $cols .= "'{$row}'";
            }
            $form['gen_multiEditKeyString'] = $cols;

            // EditList
            $edit->setViewParam($form);
            foreach($form['gen_editControlArray'] as $ctl) {
                if ($ctl['type']=="list") {
                    $arr = $gen_db->getArray($ctl['query']);
                    if ($arr) {
                        $lineNo = 1;
                        foreach($arr as $row) {
                            foreach($row as $name=>$val) {
                                if (isset($form[$name."_".$lineNo]) && $form[$name."_".$lineNo]=="[multi]") {
                                    if ($val === null) $val = "";   // 既存値がnullの場合、通常の編集モードではフォーム上で空白値になるので、登録時も空白値となる
                                    $form[$name."_".$lineNo] = $val;
                                    $this->multiColumnArray[] = $name."_".$lineNo;
                                }
                            }
                            $lineNo++;
                        }
                    }
                }
            }
        }

        // 明細項目（detailArray）がある場合（EditList,Bulk,Barcode）、明細項目の行IDの配列を作成する。
        self::_setDetailIdArray($form);

        // setDefault（登録モードごとのデフォルト値設定）
        if (!is_array($this->detailIdArray)) {
            $this->model->setDefault($form, $this->entryMode);
            if (method_exists($this->model, "beforeLogic")) {
                $this->model->beforeLogic($form);
            }
        } else {
            $headerParam = array();
            foreach ($this->headerArray as $col) {
                if (isset($form[$col]))
                    $headerParam[$col] = $form[$col];
            }
            $this->originalDetailArray = $this->detailArray;
            foreach ($this->detailIdArray as $id) {
                $param = $headerParam;
                foreach ($this->originalDetailArray as $col) {
                    if (isset($form[$col . "_" . $id]))
                        $param[$col] = $form[$col . "_" . $id];
                }
                $this->model->setDefault($param, $this->entryMode);
                if (method_exists($this->model, "beforeLogic")) {
                    $this->model->beforeLogic($param);
                }
                foreach ($param as $key => $val) {
                    if (!in_array($key, $this->detailArray))
                        $this->detailArray[] = $key;   // setDefaultで新たな項目が作成されたときのための処理
                    $form[$key . "_" . $id] = $val;
                }
            }
        }

        // convert
        if (!is_array($this->detailIdArray)) {
            $this->model->convert($converter);
        } else {
            foreach ($this->detailIdArray as $id) {
                $this->model->convert($converter, "_" . $id);
            }
        }
    }

    function validate($validator, &$form)
    {
        // アクセス権チェック
        if ($form['gen_readonly'] == 'true') {
            $validator->raiseError(_g("アクセス権がありません。"));
            return $this->errorAction;
        }

        // validate
        if (!is_array($this->detailIdArray)) {
            $this->model->validate($validator, $form);
        } else {
            $line = 1;
            if (count($this->detailIdArray) == 0) {
                $validator->raiseError(_g("登録するデータがありません。"));
            } else {
                foreach ($this->detailIdArray as $id) {
                    $this->model->validate($validator, $form, "_" . $id);
                    // 明細項目のエラーの場合、エラーメッセージにエラー行数を付加する
                    if ($validator->hasError()) {
                        $i = 0;
                        foreach ($validator->errorList as $i => &$err) {
                            // エラーが発生したのが明細項目なら
                            $errParam = $validator->errorParamList[$i];
                            if (substr($errParam, -1 * strlen($line) - 1) == '_' . $line)
                                $errParam = substr($errParam, 0, strlen($errParam) - 1 * strlen($line) - 1);
                            if (in_array($errParam, $this->originalDetailArray)) {
                                $err = sprintf(_g("%s行目"), $line) . " : " . $err;
                            }
                            $i++;
                        }
                        //break;
                    }
                    $line++;
                }
            }
        }
        
        if (isset($form['gen_multiEditKey']) && $validator->hasError()) {
            // multiをもどす
            foreach($this->multiColumnArray as $col) {
                $form[$col] = "[multi]";
            }
            // keyを戻す
            $form[$form['gen_multiEditKeyColumn']] = $form['gen_multiEditKey'];
        }
        
        // gen_app
        if ($_SESSION['gen_app']) {
            if ($validator->hasError()) {
                $form['response_noEscape'] = join("[br]", $validator->errorList);
                return 'simple.tpl';
            }
        }

        return "action:" . $this->errorAction;        // if error
    }

    // 登録処理
    function execute(&$form)
    {
        global $gen_db;

        //------------------------------------------------------
        //  トランザクション開始
        //------------------------------------------------------
        $gen_db->begin();

        //------------------------------------------------------
        //  登録前ロジックメソッドの実行（子クラスで実装）
        //------------------------------------------------------
        // 第二引数は、新規登録ならtrue、更新ならfalse
        if (method_exists($this, 'beforeLogic')) {
            $this->beforeLogic($form, $this->isNew);
        }

        //------------------------------------------------------
        // 登録
        //------------------------------------------------------
        $this->_regist($form);

        // 表示する行番号（バーコード登録用）
        @$form['show_line_number'] += $this->lineCount;

        //------------------------------------------------------
        //  登録後ロジックメソッドの実行（子クラスで実装）
        //------------------------------------------------------
        // 第二引数は、新規登録ならtrue、更新ならfalse
        if (method_exists($this, 'afterLogic')) {
            $this->afterLogic($form, $this->isNew);
        }

        //------------------------------------------------------
        // 登録後メッセージとログ
        //------------------------------------------------------
        $this->setLogParam($form);

        if (!isset($form['gen_multiEditKey'])) {
            // 一括登録モードにおいては登録メッセージを表示しない（いずれかのレコードでvalid error が発生したときのため）
            $form['gen_afterEntryMessage'] = $this->afterEntryMessage == "" ? _g("登録しました。") : $this->afterEntryMessage;
            $form['gen_afterEntryMessage_noEscape'] = $this->afterEntryMessage_noEscape;
        }        
        Gen_Log::dataAccessLog($this->log1, ($this->logCategory === null ? ($this->isNew ? _g("新規") : _g("更新")) : $this->logCategory), $this->log2);

        //------------------------------------------------------
        // 更新フラグ
        //------------------------------------------------------
        // データ更新が行われたことを示すフラグを立てる。遷移先クラスやtplにおいて、List画面に更新メッセージを表示するか
        // どうかの判断に使われる。
        // $form['gen_hilight_id']でもよさそうだが、そちらは設定されない場合がある（detailなど）
        $form['gen_updated'] = "true";
       
        //------------------------------------------------------
        //  帳票印刷指定されていたときの処理
        //------------------------------------------------------
        if (isset($form['gen_reportAction'])) {
            // 帳票印刷指定。
            //  新規のときはeditmodal.tplのonload経由gen_script.js(gen.edit.init)で、
            //  修正のときはmodalclose.tpl経由gen_modal.jsで印刷処理される。
            $id = "";
            if (isset($form[$form['gen_reportKeyColumn']])) {
                $id = $form[$form['gen_reportKeyColumn']];
            }
            if ($id == "") {
                $id = $gen_db->getSequence($form['gen_reportSeq']);
            }
            $form['gen_nextPageReport_noEscape'] = $form['gen_reportAction'] . "&" . str_replace('[id]', $id, $form['gen_reportParam']);
        }

        //------------------------------------------------------
        //  コミット
        //------------------------------------------------------
        $gen_db->commit();

        //------------------------------------------------------
        // modal frameの多重OPEN時の、登録後遷移
        //------------------------------------------------------
        if (@$form['gen_overlapFrame'] == "true") {
            $form['gen_overlapCode'] = $form[$form['gen_overlapCodeCol']];
            return "overlapmodalclose.tpl";                        // モーダルウィンドウ(子)を閉じる処理だけをするページ
        }

        //------------------------------------------------------
        // 登録後の遷移（gen_app）
        //------------------------------------------------------
        if ($_SESSION['gen_app']) {
            $form['response_noEscape'] = "success";
            return 'simple.tpl';
        }
        
        //------------------------------------------------------
        // 登録後の遷移（新規登録モード）
        //------------------------------------------------------

        // 一括編集モードでは、キーの数だけアクションリダイレクトでループする
        if (isset($form['gen_multiEditKey'])) {
            $multiEditKeyArray = explode(",", $form['gen_multiEditKey']);
            $form['gen_multiEditKeyNum']++;
            if (count($multiEditKeyArray) > $form['gen_multiEditKeyNum']) {
                // multiをもどす
                foreach($this->multiColumnArray as $col) {
                    $form[$col] = "[multi]";
                }
                $_SESSION['gen_page_request_id'][] = $form['gen_page_request_id'];    // リロード対策回避

                return "action:" . $form['action'];
            } else {
                $this->isNew = false;
                // 一括編集全体にわたるトランザクション
                $gen_db->commit();
            }
        }

        if ($this->isNew && !isset($form['gen_redMode'])) { // 赤伝モードは修正扱い（Listへ戻る）

            // 新規登録後はList画面ではなく$this->newRecordNextActionで指定された画面に移る。
            // newRecordNextActionが未指定のときはEdit画面（現在のaction からedit action名を決定）へ戻る
            if ($this->newRecordNextAction == "") {
                $actionSep = explode("_", $form['action']);
                $this->newRecordNextAction = $actionSep[0] . "_" . $actionSep[1] . "_Edit";
            }

            // キーフィールドの消去
            if (isset($this->idField) && isset($form[$this->idField])) {
                unset($form[$this->idField]);
            }

            // detailフィールドは消去
            if (isset($this->detailFields)) {
                foreach ($this->detailFields as $f) {
                    unset($form[$f]);
                }
            }

            // 指定されたフィールドを消去
            if (is_array($this->newRecordNotKeepField)) {
                foreach ($this->newRecordNotKeepField as $field) {
                    unset($form[$field]);
                }
            }
            return "action:" . $this->newRecordNextAction;
        }

        //------------------------------------------------------
        //  登録後の遷移（編集モード）
        //------------------------------------------------------

        $form['gen_listAction'] = $this->listAction;    // モーダルウィンドウ内でなかったときの遷移先
        return "modalclose.tpl";                        // モーダルウィンドウを閉じる処理だけをするページ
    }

    // 明細リスト（detailArray）の行ID配列を作成する
    private function _setDetailIdArray($form)
    {
        if (!isset($this->detailArray))
            return false;

        $this->detailIdArray = array();

        // キー項目（detailArrayの最初の項目）の名称
        $keyName = $this->detailArray[0] . "_";
        $keyNameLen = strlen($keyName);
        foreach ($form as $name => $value) {
            // キー項目に値がセットされていれば
            if (substr($name, 0, $keyNameLen) == $keyName && $form[$name] != "") {
                // キー項目名からidを取得
                $id = substr($name, $keyNameLen, strlen($name) - $keyNameLen);

                // キー項目が拡張DDだったとき、idのうしろに「_show」がつくことへの対策。
                if (substr($id, -5) != "_show") {
                    $this->detailIdArray[] = $id;
                }
            }
        }
    }

    // 登録処理
    private function _regist($form)
    {
        if (!isset($this->detailArray)) {
            // 明細リストなし
            $this->model->regist($form);
        } else {
            // 明細リストあり
            $isFirst = true;
            sort($this->detailIdArray);
            $lineNo = 1;
            foreach ($this->detailIdArray as $id) {
                $param = array();
                $entArray = array_merge($this->headerArray, $this->detailArray);
                foreach ($entArray as $col) {
                    // 値をセット
                    $param[$col] = @$form[$col . "_" . $id];
                    // 行番号をセット
                    //  行番号は行id順に振られるが、行idがそのまま行番号になるわけではないことに注意。
                    //  行idに欠番があった場合、行番号はつめて採番される。（空行があっても行番号がとばないように）
                    $param['gen_line_no'] = $lineNo;
                    // 後のフィールド消去処理のために、フィールド名を記録しておく
                    $this->detailFields[] = $col . "_" . $id;
                }
                // regist
                $this->model->regist($param, $isFirst);
                $isFirst = false;
                if ($this->entryMode != 'bulk')
                    $lineNo++;
            }

            // 削除処理
            // 削除フラグが立っているレコードを削除する
            foreach ($form as $name => $value) {
                if (substr($name, 0, 16) == "gen_delete_flag_" && $form[$name] != "") {
                    $this->model->detailDelete($form[$name]);
                }
            }
            // 行削除されたレコードを削除する
            if (isset($this->isListMode) && $this->isListMode == true) {
                $this->model->lineDelete($lineNo);
            }
        }
    }

}