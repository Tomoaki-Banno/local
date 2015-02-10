<?php
class Config_Setting_AjaxListEntry extends Base_AjaxBase
{
    function _execute(&$form)
    {
        global $gen_db;
        
        if (!isset($form['editaction'])) 
            return;
        if (!isset($form['id']) || !is_numeric($form['id'])) 
            return;
        if (!isset($form['field'])) 
            return;
        if (!isset($form['val'])) 
            return;
        if (!isset($form['reqid'])) 
            return;
        
        // 登録処理
        
        // CSRF対策
        if (!Gen_Reload::reloadCheck($form['reqid'])) {
            return
                array(
                    "status" => _g("セッションエラーです。")
                );
        }
        $reqId = sha1(uniqid(rand(), true));
        $_SESSION['gen_page_request_id'][] = $reqId;
        $obj = array(
           'reqid' => $reqId,
        );

        // パーミッションチェック
        //  クライアントサイドでもチェックされている
        $editAction = $form['editaction'];
        $actionNameSep = explode("_", $editAction);
        if (count($actionNameSep) >= 2) {
            $classGroup = $actionNameSep[0] . "_" . $actionNameSep[1];
        } else {
            throw new Exception('edit actionが正しくありません。');
        }
        $sessionRes = Gen_Auth::sessionCheck(strtolower($classGroup));
        if ($sessionRes != 2) { // 2: 読み書き可能
            $obj['status'] = _g("この画面の編集が許可されていません。");
            return $obj;
        }
        $form['gen_readonly'] = "false";
        
        // Editクラスからパラメータを得る
        require_once(Gen_File::safetyPathForAction($editAction));
        $edit = new $editAction;
        $edit->setQueryParam($form);
        $keyColumn = $edit->keyColumn;
        $selectQuery = $edit->selectQuery;
        $edit->setViewParam($form);
        $modelName = $edit->modelName;
        $entryAction = $form['gen_entryAction'];
        
        if ($keyColumn == "" || $selectQuery == "") {
            throw new Exception("keyColumn もしくは selectQuery が指定されていません。キー指定がない画面（例：barcodeEdit）は扱うことができません。");
        }
        
        // カスタム項目
        $customColumnArr = Logic_CustomColumn::getCustomColumnParamByClassGroup($classGroup);
        
        if ($customColumnArr) {
            // このパラメータは ModelBase の regist() 内で global $form して使用されている
            $form['gen_customColumnTable'] = $customColumnArr[0];
            $form['gen_customColumnArray'] = $customColumnArr[1];
            
            // EditBaseにあるコードとだいたい同じ
            if (isset($customColumnArr[1])) {
                $table = $form['gen_customColumnTable'];
                $selectPos = stripos($selectQuery, 'select') + 6;
                $originalQuery = $selectQuery;
                $selectQuery = substr($originalQuery, 0, $selectPos) . " ";
                $isDetailArr = array();
                if (isset($_SESSION['gen_setting_company']->customcolumnisdetail)) {
                    $isDetailArr = $_SESSION['gen_setting_company']->customcolumnisdetail;
                    if (is_object($isDetailArr)) {
                        $isDetailArr = get_object_vars($isDetailArr);
                    }
                }
                foreach($customColumnArr[1] as $customCol => $customArr) {
                    if (!isset($isDetailArr[$customArr[2]]) || !$isDetailArr[$customArr[2]]) {
                        $selectQuery .= "{$table}.{$customCol} as gen_custom_{$customCol},";
                    }
                }
                $selectQuery .= substr($originalQuery, $selectPos + 1);
            }
        }
    
        // 既存データの取得
        $query = str_replace("[Where]", "where " .$keyColumn ." = '" . $form['id'] . "'", $selectQuery);
        if (!($arr = $gen_db->getArray($query))) {
            $obj['status'] = _g("指定されたレコードが存在しません。他のユーザーによって削除された可能性があります。");
            return $obj;
        }
        $param = $arr[0];

        // 更新するデータ
        $param[$form['field']] = $form['val'];

        // model
        $model = new $modelName;
        $model->csvUpdateMode = true;
        // clickEditでsetDefault()するといろいろ不具合。今回設定値以外は既存データを読みだすので不要なはず
        // $model->setDefault($param, "csv");
        if (method_exists($model, "beforeLogic")) {
            $model->beforeLogic($param);
        }

        // convert
        $converter = new Gen_Converter($param);
        $model->convert($converter);

        // validate
        $validator = new Gen_Validator($param);
        $model->validate($validator, $param);

        if ($validator->hasError()) {
            $msg = "";
            foreach ($validator->errorList as $errNum=>$errMsg) {
                $msg .= $errMsg;
            }
            $obj['status'] = $errMsg;
        } else {
            // regist
            $model->regist($param);
            $obj['status'] = "success";

            // ログのためのパラメータ取得
            $actionFile = str_replace('_', '/', $entryAction);
            require_once(APP_DIR . $actionFile .'.class.php');
            $entry = new $entryAction;
            $entry->setLogParam($param);

            $colName = "";
            foreach($form['gen_editControlArray'] as $col) {
                if (isset($col['name']) && isset($col['label'])) {
                    if ($col['name']==$form['field']) $colName = $col['label'];
                }
            }
            if ($colName == "") {
                $field = str_replace("gen_custom_", "", $form['field']);
                foreach($form['gen_customColumnArray'] as $custField => $custArr) {
                    if ($custField == $field) {
                        $colName = $custArr[1];
                        break;
                    }
                }
            }
            $entry->log2 .= " ["._g("変更内容")."] {$colName} '" . @$form['orgtext'] . "' ⇒ '" . @$form['showtext'] . "'";
            
            // ログ
            Gen_Log::dataAccessLog($entry->log1, _g("リスト更新"), $entry->log2);
        }

        return $obj;
    }
}