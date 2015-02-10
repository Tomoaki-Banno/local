<?php

class Gen_Converter
{

    var $errorList;
    var $form;

    function Gen_Converter(&$form)
    {
        $this->form = &$form;
    }

    // 引数がnullなら、指定された値に変換する。(空文字のときは変換しない)
    function nullToValue($param, $convertVal)
    {
        $val = @$this->form[$param];

        // 「$val === null」は、nullではtrueだが 空文字や0はfalse。
        if ($val === null) {
            $this->form[$param] = $convertVal;
        }
    }

    function nullToValue_JS($convertVal)
    {
        return "if(val=='')val='{$convertVal}'";
    }

    // 引数がnullあるいは空文字なら、指定された値に変換する。
    function nullBlankToValue($param, $convertVal)
    {
        $val = @$this->form[$param];
        if ($val === null || $val === "") {
            $this->form[$param] = $convertVal;
        }
    }

    function nullBlankToValue_JS($convertVal)
    {
        return "if(val=='')val='{$convertVal}'";
    }

    // 引数が空文字なら、nullに変換する。
    function blankToNull($param)
    {
        $val = @$this->form[$param];
        if ($val === "") {
            $this->form[$param] = null;
        }
    }

    function blankToNull_JS()
    {
        // 「||val==='null'」はIE対策
        return "if(val===''||val==='null')val=null";
    }

    // 引数が数字でなければ、指定された値に変換する。
    function notNumToValue($param, $convertVal)
    {
        $val = @$this->form[$param];

        if (!Gen_String::isNumeric($val)) {
            $this->form[$param] = $convertVal;
        }
    }

    function notNumToValue_JS($convertVal)
    {
        return "if(!gen.util.isNumeric(val))val='{$convertVal}'";
    }

    // 引数が数字でなければ、nullに変換する。
    function notNumToNull($param)
    {
        $val = @$this->form[$param];

        if (!Gen_String::isNumeric($val)) {
            $this->form[$param] = null;
        }
    }

    function notNumToNull_JS()
    {
        return "if(!gen.util.isNumeric(val))val=null";
    }

    // 引数が指定された文字列なら、nullに変換する。
    function strToNull($param, $str)
    {
        $val = @$this->form[$param];

        if ($val === $str) {
            $this->form[$param] = null;
        }
    }

    function strToNull_JS($str)
    {
        return "if(val==='{$str}')val=null";
    }

    // 引数をなるべく数値として解釈できるよう、各種の処理を行う（trim、桁区切りカンマはずし、全角を半角に変換）
    // 数値解釈できなければそのまま返す
    function strToNum($param)
    {
        $val = @$this->form[$param];
        $val = Gen_String::trimEx($val);
        $val = str_replace(',', '', $val);
        $val = mb_convert_kana($val, 'n');

        if (Gen_String::isNumeric($val)) {
            $this->form[$param] = $val;
        }
    }

    function strToNum_JS()
    {
        return "val=gen.util.trimEx(gen.util.delFigure(gen.util.fullNumToHalfNum(val)))";
    }

    // 引数が第2引数で指定された値のうちいずれかであれば、第3引数の値に変換する。
    function selectStrToValue($param, $stringArray, $convertVal)
    {
        $val = @$this->form[$param];

        if (in_array($val, $stringArray))
            $this->form[$param] = $convertVal;
    }

    function selectStrToValue_JS($stringArray, $convertVal)
    {
        $res = "";
        foreach ($stringArray as $str) {
            if ($res !== "")
                $res .= "||";
            $res .= "val=='{$str}'";
        }
        return "if({$res})val='{$convertVal}'";
    }

    // 引数が第2引数で指定された値のうちいずれかでなければ、第3引数の値に変換する。
    function notSelectStrToValue($param, $stringArray, $convertVal)
    {
        $val = @$this->form[$param];

        if (!in_array($val, $stringArray))
            $this->form[$param] = $convertVal;
    }

    function notSelectStrToValue_JS($stringArray, $convertVal)
    {
        $res = "";
        foreach ($stringArray as $str) {
            if ($res !== "")
                $res .= "&&";
            $res .= "val!='{$str}'";
        }
        return "if({$res})val='{$convertVal}'";
    }

    // 引数が日付（y-m-d もしくは y/m/d）として不正なら、指定された値に変換する。
    // 第３引数がtrueもしくは未指定なら、空欄は変換しない。
    function notDateStrToValue($param, $convertVal, $blankNoChange = true)
    {
        $val = @$this->form[$param];

        if (!Gen_String::isDateString($val)) {
            if ($blankNoChange && $val == null) {
                return;
            }
            $this->form[$param] = $convertVal;
        }
    }

    function notDateStrToValue_JS($convertVal, $blankNoChange = true)
    {
        return "if(" . ($blankNoChange ? "val!=''&&" : "") . "!gen.date.isDate(val))val='{$convertVal}'";
    }

    // 引数が日付（y-m-d もしくは y/m/d）として不正なら、今月１日に変更する。
    // 第2引数がtrueもしくは未指定なら、空欄は変換しない。
    function notDateStrToThisMonthFirstDay($param, $blankNoChange = true)
    {
        $val = @$this->form[$param];

        if (!Gen_String::isDateString($val)) {
            if ($blankNoChange && $val == null) {
                return;
            }
            $this->form[$param] = date('Y-m-01');
        }
    }

    function notDateStrToThisMonthFirstDay_JS($blankNoChange = true)
    {
        return "if(" . ($blankNoChange ? "val!=''&&" : "") . "!gen.date.isDate(val))val='" . date('Y-m-01') . "'";
    }

    // 引数が日付（y-m-d もしくは y/m/d）として不正なら、今月末日に変更する。
    // 第2引数がtrueもしくは未指定なら、空欄は変換しない。
    function notDateStrToThisMonthLastDay($param, $blankNoChange = true)
    {
        $val = @$this->form[$param];

        if (!Gen_String::isDateString($val)) {
            if ($blankNoChange && $val == null) {
                return;
            }
            $this->form[$param] = Gen_String::getThisMonthLastDateString();
        }
    }

    function notDateStrToThisMonthLastDay_JS($blankNoChange = true)
    {
        return "if(" . ($blankNoChange ? "val!=''&&" : "") . "!gen.date.isDate(val))val='" . Gen_String::getThisMonthLastDateString() . "'";
    }

    // 2つの日付を比較し、date1 > date2 なら、date1とdate2を交換する
    // （date1のほうが前の日付になるようにする）
    function dateSort($param1, $param2)
    {
        $date1 = @$this->form[$param1];
        $date2 = @$this->form[$param2];

        if (!Gen_String::isDateString($date1) || !Gen_String::isDateString($date2))
            return;

        if (strtotime($date1) > strtotime($date2)) {
            $this->form[$param1] = $date2;
            $this->form[$param2] = $date1;
        }
    }

    // 2つの日付の間隔が指定された日数以上に開いていれば、
    // 後のほうの日付を調整して指定された日数になるようにする
    function dateSpan($param1, $param2, $days)
    {
        $date1 = @$this->form[$param1];
        $date2 = @$this->form[$param2];

        if (!Gen_String::isDateString($date1) || !Gen_String::isDateString($date2))
            return;


        if (strtotime($date2) - strtotime($date1) > (86400 * $days)) {
            $convertVal = date('Y-m-d', strtotime($date1) + (86400 * $days));
            $this->form[$param2] = $convertVal;
        }
    }

    // trimする（全角スペースも対象）
    function trimEx($param)
    {
        $val = @$this->form[$param];
        $convertVal = Gen_String::trimEx($val);
        $this->form[$param] = $convertVal;
    }

    function trimEx_JS()
    {
        return "val=gen.util.trim(val)";
    }

}