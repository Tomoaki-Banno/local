<?php

class Gen_Validator
{

    var $errorList;
    var $errorParamList;    // エラーが発生したパラメータ（フィールド）名のリスト
    var $form;

    function Gen_Validator(&$form)
    {
        $this->errorList = array();
        $this->errorParamList = array();
        $this->form = $form;
    }

    function _regError($param, $msg)
    {
        $this->errorList[] = $msg;
        $this->errorParamList[] = $param;
    }

    function hasError()
    {
        return count($this->errorList) > 0;
    }

    // 無条件でエラーを発生させる
    function raiseError($msg)
    {
        $this->_regError(null, $msg);
    }

    // パラメータが存在するかどうか
    //   全角スペースのみ・半角スペースのみはアウト
    function required($param, $msg)
    {
        $val = @$this->form[$param];
        $val = trim($val);                      // 半角スペースのみは空文字とみなす
        $val = str_replace("　", "", $val);     // 全角スペースのみは空文字とみなす
        if (is_array($val)) {
            foreach ($val as $v) {
                if (strlen($val) == 0) {
                    $this->_regError($param, $msg);
                }
            }
            return;
        } else if (strlen($val) > 0 && $val != null) {
            return;
        } else {
            $this->_regError($param, $msg);
        }
    }

    function required_JS()
    {
        return "val=gen.util.trim(val).replace('　','');res=(val.length>0&&val!=null)";
    }

    // パラメータが数字かどうか(空白はValidError)
    function numeric($param, $msg)
    {
        $val = @$this->form[$param];

        if (!Gen_String::isNumeric($val)) {    // PHPのis_numericだと指数（例 1e6）や16進数（例 0x1A, &H1A）も数値と判断されてしまう
            $this->_regError($param, $msg);
        }
    }

    function numeric_JS()
    {
        return "res=gen.util.isNumeric(val)";
    }

    // パラメータが空白もしくは数字かどうか
    function blankOrNumeric($param, $msg)
    {
        $val = @$this->form[$param];

        if (!Gen_String::isNumeric($val)           // PHPのis_numericだと指数（例 1e6）や16進数（例 0x1A, &H1A）も数値と判断されてしまう
                && $val != '' && $val != null) {
            $this->_regError($param, $msg);
        }
    }

    function blankOrNumeric_JS()
    {
        return "res=(val==''||val===null||gen.util.isNumeric(val))";
    }

    // パラメータが指定範囲内の数字かどうか
    function range($param, $msg, $min, $max)
    {
        $val = @$this->form[$param];

        if (!Gen_String::isNumeric($val)) {    // PHPのis_numericだと指数（例 1e6）や16進数（例 0x1A, &H1A）も数値と判断されてしまう
            $this->_regError($param, $msg);
        } else if ($val < $min || $val > $max) {
            $this->_regError($param, $msg);
        }
    }

    function range_JS($min, $max)
    {
        return "res=(gen.util.isNumeric(val)?val>={$min}&&val<={$max}:false)";
    }

    // パラメータが指定の値以下の数字かどうか
    function maxNum($param, $msg, $max)
    {
        $val = @$this->form[$param];

        if (!Gen_String::isNumeric($val)) {    // PHPのis_numericだと指数（例 1e6）や16進数（例 0x1A, &H1A）も数値と判断されてしまう
            $this->_regError($param, $msg);
        } else if ($val > $max) {
            $this->_regError($param, $msg);
        }
    }

    function maxNum_JS($max)
    {
        return "res=(gen.util.isNumeric(val)?val<={$max}:false)";
    }

    // パラメータが整数かどうか
    function integer($param, $msg)
    {
        $val = @$this->form[$param];

        if (!Gen_String::isNumeric($val)) {    // PHPのis_numericだと指数（例 1e6）や16進数（例 0x1A, &H1A）も数値と判断されてしまう
            $this->_regError($param, $msg);
        }
        if (!(strpos($val, ".") === false)) {
            $this->_regError($param, $msg);
        }
    }

    function integer_JS()
    {
        return "res=(gen.util.isNumeric(val)?parseInt(val,10).toString()==val:false)";
    }

    // パラメータが指定の値以上の数字かどうか
    function minNum($param, $msg, $min)
    {
        $val = @$this->form[$param];

        if (!Gen_String::isNumeric($val)) {    // PHPのis_numericだと指数（例 1e6）や16進数（例 0x1A, &H1A）も数値と判断されてしまう
            $this->_regError($param, $msg);
        } else if ($val < $min) {
            $this->_regError($param, $msg);
        }
    }

    function minNum_JS($min)
    {
        return "res=(gen.util.isNumeric(val)?val>={$min}:false)";
    }
    
    // パラメータがカンマ区切りの数字かどうか(空白はValidError)
    function csvNum($param, $msg)
    {
        $val = @$this->form[$param];
        if ($val == "") {
            $this->_regError($param, $msg);
            return;
        }
        $arr = explode(",", $val);
        foreach($arr as $one) {
            if (!Gen_String::isNumeric($one)) {
                $this->_regError($param, $msg);
                return;
            }
        }
    }

    function csvNum_JS()
    {
        return "res=(val!=''&&val.indexOf(',,')==-1&&gen.util.isNumeric(val.replace(/,/g,'')))";
    }

    // パラメータが指定の文字数以上かどうか
    function minLength($param, $msg, $minLen)
    {
        $val = @$this->form[$param];

        if (strlen($val) < $minLen) {
            $this->_regError($param, $msg);
        }
    }

    function minLength_JS($minLen)
    {
        return "res=(val.length>={$minLen})";
    }

    // パラメータが指定の文字数以下かどうか
    function maxLength($param, $msg, $maxLen)
    {
        $val = @$this->form[$param];

        if (strlen($val) > $maxLen) {
            $this->_regError($param, $msg);
        }
    }

    function maxLength_JS($maxLen)
    {
        return "res=(val.length<={$maxLen})";
    }

    // パラメータの文字数が正しいか
    function length($param, $msg, $minLen, $maxLen)
    {
        $val = @$this->form[$param];

        if (strlen($val) < $minLen || strlen($val) > $maxLen) {
            $this->_regError($param, $msg);
        }
    }

    function length_JS($minLen, $maxLen)
    {
        return "res=(val.length>={$minLen}&&val.length<={$maxLen})";
    }

    // パラメータが指定された値のいずれかかどうか
    function selectString($param, $msg, $stringArray)
    {
        $val = @$this->form[$param];
        foreach ($stringArray as $str) {
            if ((string) $str === (string) $val) {
                return;
            }
        }
        $this->_regError($param, $msg);
    }

    function selectString_JS($stringArray)
    {
        $res = "";
        foreach ($stringArray as $str) {
            if ($res != "")
                $res .= "||";
            $res .= "val=='{$str}'";
        }
        return "res=({$res})";
    }

    // パラメータが指定された値ではないかどうか
    function notEqualString($param, $msg, $string)
    {
        $val = @$this->form[$param];

        if ((string) $val === $string) {
            $this->_regError($param, $msg);
        }
    }

    function notEqualString_JS($str)
    {
        return "res=(val!=='{$str}')";
    }

    // パラメータが全角アルファベット・全角数字を含まないかどうか
    // アルファベット・数字以外の全角文字はOKであることに注意。
    function notContainTwoByteAlphaNum($param, $msg)
    {
        $val = @$this->form[$param];

        mb_regex_encoding("UTF-8");
        if (mb_ereg("[ａ-ｚＡ-Ｚ０-９]", $val)) {
            $this->_regError($param, $msg);
        }
    }

    function notContainTwoByteAlphaNum_JS()
    {
        return "res=!val.match(/[ａ-ｚＡ-Ｚ０-９]/);";
    }

    // パラメータが日付（y-m-d もしくは y/m/d）として妥当かどうか
    function dateString($param, $msg)
    {
        $val = @$this->form[$param];

        if (Gen_String::isDateString($val))
            return;

        $this->_regError($param, $msg);
    }

    function dateString_JS()
    {
        return "res=gen.date.isDate(val)";
    }

    // パラメータが時刻として妥当かどうか
    function timeString($param, $msg, $isBlankOk = false)
    {
        $val = @$this->form[$param];

        if ($val == "" && $isBlankOk)
            return;
        if ($val == "" && !$isBlankOk) {
            $this->_regError($param, $msg);
            return;
        }

        if (Gen_String::isTimeString($val))
            return;

        $this->_regError($param, $msg);
    }

    function timeString_JS()
    {
        return "res=gen.date.isTime(val)";
    }

    // パラメータが日付もしくは空白かどうか
    function blankOrDateString($param, $msg)
    {
        $val = @$this->form[$param];

        if (Gen_String::isDateString($val))
            return;

        if ($val != '' && $val != null)
            $this->_regError($param, $msg);
    }

    function blankOrDateString_JS()
    {
        return "res=(gen.date.isDate(val)?true:val==''||val==null)";
    }

    // パラメータが日付で、しかも指定日以降であるかどうか
    function dateLater($param, $msg, $date)
    {
        $val = @$this->form[$param];

        if (!Gen_String::isDateString($val)) {
            // 文字列が日付解釈できなかった
            return;
        } else {
            if (strtotime($val) < strtotime($date)) {
                $this->_regError($param, $msg);
            }
        }
    }

    function dateLater_JS($date)
    {
        return "res=(gen.date.isDate(val)?gen.date.parseDateStr(val)>=gen.date.parseDateStr('{$date}'):false)";
    }

    // パラメータが日付で、しかもロック月以降であるかどうか
    //     このバリデータは特殊で、第二引数はエラーメッセージではなく、日付の名称（「入力日」とか）を
    //    受け取る。エラーメッセージはこのメソッドの中で組み立てる。
    //    現在処理月エラーのメッセージでは有効な日付範囲を表示する必要があり、ここでやるほうが
    //    効率がいいため。
    function systemDateOrLater($param, $dateName)
    {
        $val = @$this->form[$param];

        $errorMsg = sprintf(_g("%sに指定された日付はデータがロックされています。"), $dateName);
        $errorMsg .= _g("%s以降の日付を指定してください。");    // 「%s」は下記func内で埋め込む
        $this->_notLockDateCommon(0, $param, $val, $dateName, $errorMsg);
    }

    function systemDateOrLater_JS($dateName)
    {
        // このバリデータはModelBaseでスクリプトを作成している
    }

    // 上のfunc（systemDateOrLater）のDelete用
    //  上との違いはエラー時のメッセージと、引数を直接指定すること
    function notLockDateForDelete($val, $dateName)
    {
        $errorMsg = sprintf(_g("データがロックされているため（%1\$sが%2\$sより前）、削除できません。"), $dateName, "%s");
        $this->_notLockDateCommon(0, null, $val, $dateName, $errorMsg);
    }

    function notLockDateForDelete_JS($dateName)
    {
        // Dummy
    }

    // パラメータが日付で、しかも販売ロック月および全体ロック月以降であるかどうか
    function salesLockDateOrLater($param, $dateName)
    {
        $val = @$this->form[$param];

        $errorMsg = sprintf(_g("%sに指定された日付はデータがロックされています。"), $dateName);
        $errorMsg .= _g("%s以降の日付を指定してください。");    // 「%s」は下記func内で埋め込む
        $this->_notLockDateCommon(1, $param, $val, $dateName, $errorMsg);
    }

    function salesLockDateOrLater_JS($dateName)
    {
        // このバリデータはModelBaseでスクリプトを作成している
    }

    // 上のfunc（salesLockDateOrLater）のDelete用
    //  上との違いはエラー時のメッセージと、引数を直接指定すること
    function notSalesLockDateForDelete($val, $dateName)
    {
        $errorMsg = sprintf(_g("データがロックされているため（%1\$sが%2\$sより前）、削除できません。"), $dateName, "%s");
        $this->_notLockDateCommon(1, null, $val, $dateName, $errorMsg);
    }

    function notSalesLockDateForDelete_JS($dateName)
    {
        // Dummy
    }

    // 上のfunc（salesLockDateOrLater）の登録用
    //  上との違いはエラー時のメッセージと、引数を直接指定すること
    function notSalesLockDateForRegist($val, $dateName)
    {
        $errorMsg = sprintf(_g("選択されたデータがロックされているため（%1\$sが%2\$sより前）、登録できません。"), $dateName, "%s");
        $this->_notLockDateCommon(1, null, $val, $dateName, $errorMsg);
    }

    function notSalesLockDateForRegist_JS($dateName)
    {
        // Dummy
    }

    // パラメータが日付で、しかも購買ロック月および全体ロック月以降であるかどうか
    function buyLockDateOrLater($param, $dateName)
    {
        $val = @$this->form[$param];

        $errorMsg = sprintf(_g("%sに指定された日付はデータがロックされています。"), $dateName);
        $errorMsg .= _g("%s以降の日付を指定してください。");    // 「%s」は下記func内で埋め込む
        $this->_notLockDateCommon(2, $param, $val, $dateName, $errorMsg);
    }

    function buyLockDateOrLater_JS($dateName)
    {
        // このバリデータはModelBaseでスクリプトを作成している
    }

    // 上のfunc（buyLockDateOrLater）のDelete用
    //  上との違いはエラー時のメッセージと、引数を直接指定すること
    function notBuyLockDateForDelete($val, $dateName)
    {
        $errorMsg = sprintf(_g("データがロックされているため（%1\$sが%2\$sより前）、削除できません。"), $dateName, "%s");
        $this->_notLockDateCommon(2, null, $val, $dateName, $errorMsg);
    }

    function notBuyLockDateForDelete_JS($dateName)
    {
        // Dummy
        // このバリデータはModelBaseでスクリプトを作成している
    }

    // ロック系バリデータの共通部分
    private function _notLockDateCommon($cat, $param, $val, $dateName, $errorMsg)
    {
        if (!Gen_String::isDateString($val)) {
            // 文字列が日付解釈できなかった
            $this->_regError($param, sprintf(_g("%sが正しくありません。"), $dateName));
        } else {
            $lock_date = Logic_SystemDate::getStartDate();
            if ($cat == 1) {    // 販売ロック
                $sales_lock_date = Logic_SystemDate::getSalesLockDate();
                if ($lock_date < $sales_lock_date)
                    $lock_date = $sales_lock_date;
            }
            if ($cat == 2) {    // 購買ロック
                $buy_lock_date = Logic_SystemDate::getBuyLockDate();
                if ($lock_date < $buy_lock_date)
                    $lock_date = $buy_lock_date;
            }

            if (strtotime($val) < $lock_date) {
                $this->_regError($param, sprintf($errorMsg, date('Y-m-d', $lock_date)));
            }
        }

        return;
    }

    // SQLを実行して、レコードが存在するかどうか（存在しなければエラー）
    //   $queryはSQLのselect文。$paramを埋め込む位置を「$1」というプレースホルダにしておく
    //   また[[..]]という形でform値を埋め込める。
    //   $paramの中身がnullや空文字の場合はvalidエラーとなる。
    //     ただし第4引数にtrueを指定すると、nullや空文字の場合はチェックを行わなわずパスとする
    //    （$paramが空欄であることを許す）
    function existRecord($param, $msg, $query, $isBlankOk = false)
    {
        global $gen_db;

        $val = @$this->form[$param];

        if ($val === '' || $val === null) {        // 「 == null」だと0でもtrueになってしまう
            if ($isBlankOk) {
                return;
            } else {
                $this->_regError($param, $msg);
                return;
            }
        }

        $query = str_replace('$1', "'$val'", $query);
        $query = Gen_String::bracketToForm($query, $this->form);

        if (!$gen_db->existRecord($query)) {
            $this->_regError($param, $msg);
        }
    }

    // SQLを実行して、レコードが存在しないかどうか（存在すればエラー）
    // つまり existRecord の逆
    function notExistRecord($param, $msg, $query, $isBlankOk = false)
    {
        global $gen_db;

        $val = @$this->form[$param];

        if ($val === "" || $val === null) {
            if ($isBlankOk) {
                return;
            } else {
                $this->_regError($param, $msg);
                return;
            }
        }

        $query = str_replace('$1', "'$val'", $query);
        $query = Gen_String::bracketToForm($query, $this->form);

        if ($gen_db->existRecord($query)) {
            $this->_regError($param, $msg);
        }
    }

}