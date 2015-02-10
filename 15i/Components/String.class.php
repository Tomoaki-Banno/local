<?php

/**
 * Gen_String
 *
 * @author S.Ito
 * @copyright 2008 e-commode
 */

/**
 * 文字処理系のユーティリティ関数を集めたクラス
 *
 * @author S.Ito
 */
class Gen_String
{

    /**
     * 数字限定の is_numeric
     *
     * PHP標準の is_numeric は 指数（例 1e6）や16進数（例 0x1A, &H1A）も数値と判断されるが、
     * この関数では数字だけ（小数点・マイナス符号はOK）を数値と判断する。
     *
     * @access  static
     * @param   mixed   $val    検査する文字列
     * @param   numeric $from   最小値
     * @param   numeric $to     最大値
     * @return  bool            指定された範囲内の数字と解釈できる場合はtrue
     */
    static function isNumeric($val)
    {
        return preg_match("/^[-]?[0-9]+(\.[0-9]+)?$/", $val);
    }

    /**
     * 数値範囲チェックつきの is_numeric
     *
     * 与えられた値が、指定された範囲内の数字として解釈できるかどうかを調べる
     *
     * @access  static
     * @param   mixed   $val    検査する文字列
     * @param   numeric $from   最小値
     * @param   numeric $to     最大値
     * @return  bool            指定された範囲内の数字と解釈できる場合はtrue
     */
    static function isNumericEx($val, $from, $to)
    {
        if (!is_numeric($val)) {
            return false;
        }
        return (($val >= $from && $val <= $to) ? true : false);
    }

    /**
     * ランダムな文字列を生成する
     *
     * @access  static
     * @param   int     $length 生成する文字列の文字数
     * @return  string          ランダムな文字列
     */
    static function makeRandomString($length)
    {
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
        // mt_srand((double)microtime() * 974353);  PHP4.2以後は不要
        $rMax = strlen($chars) - 1;
        $randStr = "";
        for ($i = 0; $i < $length; $i++) {
            $randStr .= substr($chars, mt_rand(0, $rMax), 1);
        }
        return $randStr;
    }

    /**
     * 数値をHTML表示用にフォーマットする
     *
     * 数字をHTML表示用にフォーマットする（桁区切りを入れ、小数点以下の部分の文字を小さくし、色を薄くする）
     *
     * @access  static
     * @param   mixed   $val    数値
     * @return  string          フォーマット済みの文字列（HTMLタグを含む）
     */
    static function decimalFormat($val)
    {
        $val = number_format($val);
        return preg_replace("/\.([0-9]+)$/", ".<font color=\"#666666\" size=\"-2\">\\1</font>", $val);
    }

    /**
     * 非数字をゼロに変換する（数字ならそのまま返す）
     *
     * @access  static
     * @param   mixed   $val    変換対象の文字列
     * @return  string          変換後の文字列（数値）
     */
    static function nz($val)
    {
        if (is_numeric($val)) {
            return $val;
        }
        return 0;
    }

    /**
     * 文字列を整数に変換する
     *
     * 与えられた値が数字解釈できるなら整数丸めして数字として返し、数字以外なら指定された値を返す。
     *
     * @access  static
     * @param   mixed   $val    変換対象の文字列
     * @return  int/string      変換後の整数値、もしくは文字列
     */
    static function numToInt($val, $notNumStr = "")
    {
        if (is_numeric($val)) {
            return (int) $val;
        }
        return $notNumStr;
    }

    /**
     * 数値の末尾の0を削除する
     *
     * 与えられた値の小数点以下の不要な0を削除した値を返す。
     *
     * @access  static
     * @param   mixed   $val    対象の数値
     * @return  numeric         変換後の数値
     */
    static function naturalFormat($val)
    {
        if (!is_numeric($val)) {
            return $val;
        }

        $numArr = explode('.', (string) $val);
        return rtrim($numArr[0] . (isset($numArr[1]) ? '.' . rtrim($numArr[1], '0') : ''), '.');
    }

    /**
     * 与えられた値が日付解釈できるかどうか調べる
     *
     * 与えられた値が日付解釈（y-m-d もしくは y/m/d）できるかどうか調べる。
     * 時刻まで含むときは isDateTimeString() を使用する。
     * MRPから呼ばれるため、static宣言していることに注意。
     * （宣言されていないとPHPのバージョン・設定によりエラーになることがある）
     *
     * @access  static(static)
     * @param   string      $val    検査対象の文字列
     * @return  bool                日付ならtrue
     */
    static function isDateString($val)
    {
        $m = "";
        $check = preg_match("/^([0-9][0-9][0-9][0-9])([-\/])([0-9]?[0-9])([-\/])([0-9]?[0-9])$/", $val, $m);
        if ($check) {
            if (checkdate($m[3], $m[5], $m[1]) && $m[2] == $m[4]) {
                if ($m[1] < 2038 && $m[1] > 1970) {        // 2038年問題対応
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * 与えられた値が日時として解釈できるかどうか調べる
     *
     * @access  static
     * @param   string  $val    検査する文字列
     * @return  bool            日時として解釈できる文字列ならtrue
     */
    static function isDateTimeString($val)
    {
        $m = "";
        $check = preg_match("/^([0-9][0-9][0-9][0-9])([-\/])([0-9]?[0-9])([-\/])([0-9]?[0-9]) +([0-9]+)[:]([0-9]+)[:]?([0-9]*)$/", $val, $m);
        if (!$check) {
            // 時刻なし
            return self::isDateString($val);
        } else {
            if (checkdate($m[3], $m[5], $m[1]) && $m[2] == $m[4]) {
                if ($m[1] < 2038 && $m[1] > 1970) {        // 2038年問題対応
                    $checkStr = $m[6] . ':' . $m[7];
                    if ($m[8] != "")
                        $checkStr .= ':' . $m[8];
                    if (strtotime($checkStr)) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    /**
     * 与えられた値が時刻として解釈できるかどうか調べる
     *
     * @access  static
     * @param   string  $val    検査する文字列
     * @return  bool            時刻として解釈できる文字列ならtrue
     */
    static function isTimeString($val)
    {
        // isDateTimeStringは「2008-10-01 a」のような文字列もOKと判断してしまうので、
        // 後半の条件を入れている。
        return (Gen_String::isDateTimeString(date('Y-m-d ') . $val) && !preg_match('/[^0-9:]/', $val));
    }

    /**
     * 「今月末日」をあらわす文字列(Y-m-d)を返す
     *
     * @access  static
     * @param   void
     * @return  string     「今月末日」をあらわす文字列(Y-m-d)
     */
    static function getThisMonthLastDateString()
    {
        return date('Y-m-t');
    }

    /**
     * 「先月末日」をあらわす文字列(Y-m-d)を返す
     *
     * @access  static
     * @param   void
     * @return  string     「今月末日」をあらわす文字列(Y-m-d)
     */
    static function getLastMonthLastDateString()
    {
        // date('Y-m-t', strtotime('-1 month')) は危険。3/31の-1 monthは 2/28 ではなく 3/3になる
        return date('Y-m-d', mktime(0, 0, 0, date('m'), 0, date('Y')));
    }

    /**
     * 与えられた日付の曜日を文字として返す
     *
     * @access  static
     * @param   string  $dateStr    日付文字列
     * @return  string              '日','月','火','水','木','金','土'　のいずれか
     */
    static function weekdayStr($dateStr)
    {
        $youbi = array(_g('日'), _g('月'), _g('火'), _g('水'), _g('木'), _g('金'), _g('土'));
        return $youbi[date('w', strtotime($dateStr))];
    }

    /**
     * 年月日セレクタHTMLを返す
     *
     * @access  static
     * @param   string  $prefix     各セレクタは [$prefix]_year、[$prefix]_month、[$prefix]_day という名前になる
     * @param   time    $default    デフォルト年月日。UNIXタイムスタンプで指定する。
     * @param   int     $yearFrom   選択肢の開始年
     * @param   int     $yearTo     選択肢の終了年
     * @return  string              セレクタHTMLタグ
     */
    static function makeYMDSelectHTML($prefix, $default, $yearFrom, $yearTo)
    {
        $msg = "<select name=\"{$prefix}_year\" id=\"{$prefix}_year\">\n";
        for ($year = $yearFrom; $year <= $yearTo; $year++) {
            $msg .="<option value=\"{$year}\"";
            if ($year == date('Y', $default)) {
                $msg .= " selected";
            }
            $msg .=">{$year}</option>\n";
        }
        $msg .= "</select> - ";

        $msg .= "<select name=\"{$prefix}_month\" id=\"{$prefix}_month\">\n";
        for ($month = 1; $month <= 12; $month++) {
            $msg .="<option value=\"{$month}\"";
            if ($month == date('m', $default)) {
                $msg .= " selected";
            }
            $msg .=">{$month}</option>\n";
        }
        $msg .= "</select> - ";

        $msg .= "<select name=\"{$prefix}_day\" id=\"{$prefix}_day\">\n";
        for ($day = 1; $day <= 31; $day++) {
            $msg .="<option value=\"{$day}\"";
            if ($day == date('d', $default)) {
                $msg .= " selected";
            }
            $msg .=">{$day}</option>\n";
        }
        $msg .= "</select>\n";

        return $msg;
    }

    /**
     * 年月セレクタHTMLを返す
     *
     * @access  static
     * @param   string  $prefix     各セレクタは [$prefix]_year、[$prefix]_month、[$prefix]_day という名前になる
     * @param   time    $default    デフォルト年月日。UNIXタイムスタンプで指定する。
     * @param   int     $yearFrom   選択肢の開始年
     * @param   int     $yearTo     選択肢の終了年
     * @return  string              セレクタHTMLタグ
     */
    static function makeYMSelectHTML($prefix, $default, $yearFrom, $yearTo)
    {
        $msg = "<select name=\"{$prefix}_year\" id=\"{$prefix}_year\">\n";
        for ($year = $yearFrom; $year <= $yearTo; $year++) {
            $msg .="<option value=\"{$year}\"";
            if ($year == date('Y', $default)) {
                $msg .= " selected";
            }
            $msg .=">{$year}</option>\n";
        }
        $msg .= "</select>年\n";

        $msg .= "<select name=\"{$prefix}_month\" id=\"{$prefix}_month\">\n";
        for ($month = 1; $month <= 12; $month++) {
            $msg .="<option value=\"{$month}\"";
            if ($month == date('m', $default)) {
                $msg .= " selected";
            }
            $msg .=">{$month}</option>\n";
        }
        $msg .= "</select>月\n";

        return $msg;
    }

    /**
     * セレクタHTMLを作成して返す
     *
     * @access  static
     * @param   string  $id             セレクタのid/name
     * @param   array   $optArr         セレクタ選択肢（array）。array(value=>表示値)
     * @param   string  $selectedVal    selectedの値
     * @param   string  $onChange       onChangeの値
     * @param   string  $pinForm        ピン機能（デフォルト：非表示）設置するフォーム名を指定する
     * @param   array   $genPins        ピン状態
     * @return  string                  セレクタHTMLタグ
     */
    static function makeSelectHtml($id, $optArr, $selectedVal = "", $onChange = "", $pinForm = "", $genPins = array())
    {
        $id_escape = h($id);
        $res = "<select id=\"{$id_escape}\" name=\"{$id_escape}\"" . ($onChange == "" ? "" : " onChange=\"" . h($onChange) . "\"") . ">";
        foreach ($optArr as $key => $val) {
            $key_escape = h($key);
            $val_escape = h($val);
            $selected = ($key_escape == $selectedVal ? " selected" : "");
            $res .= "<option value=\"{$key_escape}\"{$selected}>{$val_escape}</option>";
        }
        $res .= "</select>";
        if (isset($pinForm) && $pinForm != "") {
            $res .= self::makePinControl($genPins, $pinForm, $id);
        }
        return $res;
    }

    /**
     * 拡張ドロップダウンHTMLを作成して返す
     *
     * @access  static
     * @param   array   $ctl            コントロール
     *                   [label] 項目名, [name] 項目id, [size] ボックス幅(コード), [subSize] ボックス幅(名), [value] 値,
     *                   [require] 必須入力, [readonly] 読取専用, [onChange][onClick] スクリプト, [tabindex] tabindex設定,
     *                   [dropdownCategory][dropdownParam][dropdownShowCondition_noEscape] dd設定,
     *                   [pinForm] ピン機能（デフォルト：非表示）設置するフォーム名を指定する,
     *                   [genPins] ピン状態
     * @return  string                  拡張ドロップダウンHTMLタグ
     */
    static function makeDropdownHtml($ctl)
    {
        $_html_result = "";

        $name = $ctl['name'];
        $name_escape = h($name);
        $value = @$ctl['value'];
        $value_escape = h($value);
        $onChange_escape = h(@$ctl['onChange_noEscape']);
        $isRequire = (isset($ctl['require']) && $ctl['require'] == "true" ? true : false);
        $isReadonly = (isset($ctl['readonly']) && $ctl['readonly'] == "true" ? true : false);
        $style_escape = "border:1px solid #7b9ebd; background-color: " . ($isReadonly ? "#cccccc;" : ($isRequire ? "#e4f0fd;" : "#ffffff;"));

        $dropdownCategory_escape = h($ctl['dropdownCategory']);
        $dropdownParam_escape = str_replace("'", "[gen_quot]", (isset($ctl['dropdownParam']) ? h($ctl['dropdownParam']) : ""));
        $dropdownShowCondition_escape = str_replace("'", "[gen_quot]", (isset($ctl['dropdownShowCondition_noEscape']) ? $ctl['dropdownShowCondition_noEscape'] : ""));
        $dropdownShowConditionAlert_escape = str_replace("'", "[gen_quot]", (isset($ctl['dropdownShowConditionAlert']) ? h($ctl['dropdownShowConditionAlert']) : ""));

        $ddRes = Logic_Dropdown::getDropdownText($dropdownCategory_escape, $value);

        // ラベル
        $_html_result .= "<span name='{$name_escape}_label' id='{$name_escape}_label'>" . h($ctl['label']) . "</span>\n";
        // テキストボックス（_show）
        $width = $ctl['size'] * 10;
        $_html_result .= "<script>function {$name_escape}_show_onchange() {{$onChange_escape}}</script>";
        $_html_result .= "<input type='text' id='{$name_escape}_show' name='{$name_escape}_show' style='{$style_escape} width: {$width}px'";
        $_html_result .= " value='" . h($ddRes['showtext']) . "'";
        $_html_result .= " onchange=\"gen.dropdown.onTextChange('{$dropdownCategory_escape}','{$name_escape}_show','{$name_escape}','{$name_escape}_sub','{$onChange_escape}');\"";
        $_html_result .= ($isReadonly ? " readonly tabindex='-1'" : " onFocus=\"gen.ui.onFocus(this);\" onBlur=\"gen.ui.onBlur(this," . (isset($ctl['require']) ? "true" : "false") . ");\"");
        $_html_result .= " onKeyDown=\"if (event.keyCode == 40) $('#{$name_escape}_dropdown').click();\"";
        $_html_result .= ">";
        // ドロップダウンボタン
        $_html_result .= "<input type='button' value='▼' id='{$name_escape}_dropdown' class='mini_button'";
        $_html_result .= " onClick=\"" . @$ctl['onClick'] . ";gen.dropdown.show('{$name_escape}_show','{$dropdownCategory_escape}','{$dropdownParam_escape}','{$dropdownShowCondition_escape}','{$dropdownShowConditionAlert_escape}')\"";
        $_html_result .= ($isReadonly ? " disabled" : "") . " tabindex=-1 style='margin-left: 2px;'>";
        // サブテキスト（テキストボックスの下に表示される項目。_sub）
        $width = (isset($ctl['subSize']) && is_numeric($ctl['subSize']) ? $ctl['subSize'] * 10 : $width);
        if ($ddRes['hasSubtext']) {
            if (isset($ctl['isNewline']) && $ctl['isNewline'] == "true")
                $_html_result .= "<br>";
            $_html_result .= "<input type='text' id='{$name_escape}_sub' name='{$name_escape}_sub' style='margin-left: 2px; width: {$width}px; background-color: #cccccc';";
            $_html_result .= " value='" . h($ddRes['subtext']) . "' readonly tabindex=-1>";
        }
        // hidden（value）
        $_html_result .= "<input type='hidden' id='{$name_escape}' name='{$name_escape}' value='{$value_escape}'>";
        // ピン
        if (isset($ctl['pinForm']) && $ctl['pinForm'] != "") {
            $_html_result .= self::makePinControl($ctl['genPins'], $ctl['pinForm'], $name);
        }

        return $_html_result;
    }

    /**
     * カレンダーテキストボックスHTMLを作成して返す
     *
     * @access  static
     * @param   array   $ctl            コントロール
     *                   [label] 項目名, [name] 項目id, [size] ボックス幅, [value] 値,
     *                   [require] 必須入力, [readonly] 読取専用, [onChange] スクリプト, [tabindex] tabindex設定
     *                   [pinForm] ピン機能（デフォルト：非表示）設置するフォーム名を指定する,
     *                   [genPins] ピン状態
     * @return  string                  カレンダーテキストボックスHTMLタグ
     */
    static function makeCalendarHtml($ctl)
    {
        $_html_result = "";

        $name = $ctl['name'];
        $name_escape = h($name);
        $value = @$ctl['value'];
        $value_escape = h($value);
        $onChange_escape = h(@$ctl['onChange_noEscape']);
        $style_escape = "border:1px solid #7b9ebd;";
        $isRequire = (isset($ctl['require']) && $ctl['require'] == "true" ? true : false);
        $isReadonly = (isset($ctl['readonly']) && $ctl['readonly'] == "true" ? true : false);
        $isSubButton = (isset($ctl['nonSubButton']) && $ctl['nonSubButton'] == "true" ? false : true);

        // ラベル
        $_html_result .= "<span name='{$name_escape}_label' id='{$name_escape}_label'>{$ctl['label']}</span>\n";
        // テキストボックス
        $_html_result .= "<input type='text' name='{$name_escape}' id='{$name_escape}' value='{$value_escape}' ";
        $_html_result .= " onChange=\"gen.dateBox.dateFormat('{$name_escape}'); {$onChange_escape}\" ";
        if ($isReadonly) {
            $_html_result .= " readonly tabindex=-1 ";
            $style_escape .= "background-color:#cccccc;";
        } else {
            if (isset($ctl['tabindex']) && is_numeric($ctl['tabindex']))
                $_html_result .= " tabindex='" . h($ctl['tabindex']) . "' ";
            if ($isRequire)
                $style_escape .= "background-color:#e4f0fd;";
            $_html_result .= " onKeyDown=\"return gen.dateBox.onKeyDown('{$name_escape}');\" ";
            $_html_result .= " onKeyUp=\"return gen.dateBox.onKeyUp('{$name_escape}');\" ";
            $_html_result .= " onFocus='gen.ui.onFocus(this);' onBlur=\"gen.ui.onBlur(this," . ($isRequire ? "true" : "false") . ");\" ";
        }
        $_html_result .= " style=\"ime-mode:off; width:" . h($ctl['size']) . "px; height:15px; vertical-align:top; text-align:center; {$style_escape}\">";
        // ボタン
        $_html_result .= "<input type='button' name='{$name_escape}_calendar_button_1' id='{$name_escape}_calendar_button_1' class='mini_button' value='▼' onClick=\"gen.calendar.init('{$name_escape}_calendar', '{$name_escape}');\"" . ($isReadonly ? " disabled" : "") . " tabindex=-1 style='margin-left: 2px;'>";
        if ($isSubButton) {
            $_html_result .= "&nbsp;&nbsp;<input type='button' name='{$name_escape}_calendar_button_2' id='{$name_escape}_calendar_button_2' class='mini_button' value='＜' onClick=\"gen.dateBox.dayChange('{$name_escape}',true)\"" . ($isReadonly ? " disabled" : "") . " tabindex=-1>";
            $_html_result .= "<input type='button' name='{$name_escape}_calendar_button_3' id='{$name_escape}_calendar_button_3' class='mini_button' value=' T ' onClick=\"gen.dateBox.setToday('{$name_escape}')\"" . ($isReadonly ? " disabled" : "") . " tabindex=-1>";
            $_html_result .= "<input type='button' name='{$name_escape}_calendar_button_4' id='{$name_escape}_calendar_button_4' class='mini_button' value='＞' onClick=\"gen.dateBox.dayChange('{$name_escape}',false)\"" . ($isReadonly ? " disabled" : "") . " tabindex=-1>";
        }
        // ピン
        if (isset($ctl['pinForm']) && $ctl['pinForm'] != "") {
            $_html_result .= self::makePinControl($ctl['genPins'], $ctl['pinForm'], $name);
        }
        if (isset($ctl['subText_noEscape']) && $ctl['subText_noEscape'] != "") {
            $_html_result .= $ctl['subText_noEscape'];
        }
        $_html_result .= "<div id='{$name_escape}_calendar'></div>";

        return $_html_result;
    }

    /**
     * ピン要素HTMLを作成して返す
     *
     * @access  static
     * @param   array   $genPins        ピン状態配列
     * @param   string  $pinForm        ピン機能を設置する画面名を指定する
     * @param   string  $name           ピン機能を付ける項目名を指定する
     * @param   array   $name2          項目名2（matchModeなど）
     * @return  string                  ピン要素HTMLタグ
     */
    static function makePinControl($genPins, $pinForm, $name, $name2 = "")
    {
        $_html_result = "";

        $name_escape = h($name);
        $name2_escape = h($name2);
        $isDisplay = (isset($genPins) && in_array($name, $genPins) ? true : false);
        $_html_result .= "&nbsp;<img src='img/pin02.png' id='gen_pin_off_{$name_escape}' style='vertical-align: text-top; cursor:pointer;" . ($isDisplay ? "display:none;" : "") . "' onclick=\"gen.pin.turnOn('{$pinForm}', '{$name_escape}', '{$name2_escape}');\">";
        $_html_result .= "&nbsp;<img src='img/pin01.png' id='gen_pin_on_{$name_escape}' style='vertical-align: text-top; cursor:pointer;" . (!$isDisplay ? "display:none;" : "") . "' onclick=\"gen.pin.turnOff('{$pinForm}', '{$name_escape}', '{$name2_escape}');\">";

        return $_html_result;
    }

    /**
     * コメント行・空白のみの行を削除。各行頭行末のブランク（全角半角スペースとTAB）も削除。
     * （各BaseでのJavaScriptソース加工に使用）
     *
     * @access  static
     * @param   string  $str     加工前文字列
     * @return  string           加工済み文字列
     */
    static function cutCommentAndBlankLine($str)
    {
        // コメント削除
        //  「//」から後ろの部分をコメントとみなしている
        $str = preg_replace("/\/\/.+/", "", $str);
        // 行頭行末のブランク（全角半角スペースとTAB）を削除。
        $str = preg_replace("/\n[ 　\t]+/m", "", $str);
        $str = preg_replace("/[ 　\t]+\n/m", "", $str);
        // 空行削除。
        //  ヒアドキュメント内では「^」での行頭マッチができないためこのような書き方をしている
        //  ただ、なぜかうまくマッチしない行もあるようだ
        $str = preg_replace("/\n[ 　\t]+\n/m", "", $str);

        return $str;
    }

    /**
     * 機種依存文字をカット（EUC版）
     * 与えられたEUC文字列が機種依存文字を含むなら、その文字を削除した文字列を返す。
     * Ajax通信でEUC⇒UTF変換を行う際の文字化けやエラーを回避するのに用いられる。
     *
     * @access  static
     * @param   string     $str    加工前文字列
     * @return  string             加工済み文字列
     */
    // 機種依存文字とは・・（コードはEUCのコード）
    // ・13区の特殊文字（0xADA1潤ｵ0xADFE）
    // ・NEC選定IBM拡張文字（0xF9A1潤ｵ0xF9FE,0xFAA1潤ｵ0xFAFE,0xFBA1潤ｵ0xFBFE,0xFCA1潤ｵ0xFCFE)
    // ・IBM拡張文字 ⇒　eucJP-msにはあるが CP51932（Windows-31JのEUC-JP互換表現）にはない
    // ・外字 ⇒　eucJP-msにはあるが CP51932（Windows-31JのEUC-JP互換表現）にはない
    //
    // ※半角カナ ⇒ \x8E[\xA0-\xDF] だが、存在しても問題なさそうなのでチェックしなかった
    // ※EUCでは
    //        1バイト目が0x00 潤ｵ 0x7F だったら：　それをシングルバイト文字とみなす。
    //　　    1バイト目が0x8E だったら：　そのあとに続く文字を半角カナとして 表示する
    //      1バイト目が 0xA1 潤ｵ 0xFE だったら：　それは漢字の 1バイト目とみなし、 次の 1バイトと合わせて漢字を表示する。
    //
    // 参考
    // http://ja.wikipedia.org/wiki/EUC-JP
    // http://www.unixuser.org/~euske/doc/kanjicode/index.html
    //
    // ソースUTF化にともない、使用しなくなった
//    function cutEucDependencyChar($str) {
//        $pattern = "/" .
//                "[\xAD][\xA1-\xFE]" .
//                "|[\xF9-\xFC][\xA1-\xFE]" .
//                "/";
//
//        // EUCは1バイト文字と2バイト文字が混在するので、$strにたいしていっぺんに preg_replace するのはうまくいかない。1文字ずつ
//        $res = "";
//        for ($i=0;$i<mb_strlen($str, "EUC-JP");$i++) {
//            $char = mb_substr($str,$i, 1, "EUC-JP");
//            if (!preg_match($pattern, $char)) {
//                $res .= $char;
//            }
//        }
//        return $res;
//    }

    /**
     * 機種依存文字の検出（SJIS版）
     * 与えられたSJIS文字列が機種依存文字を含むかどうかを調べ、その文字位置を返す。
     * CSVインポートでの機種依存文字のチェックに用いられる。
     *
     * @access  static
     * @param   string     $str    加工前文字列
     * @return  string             加工済み文字列
     */
    // 機種依存文字とは・・（コードはSJISのコード）
    // ・13区の特殊文字（0x8740 - 0x879F 83文字）
    // ・NEC選定IBM拡張文字（0xED40 - 0xEEFC　374文字）
    // ・IBM拡張文字（0xFA40 - 0xFC4B　388文字）
    // ・外字（0xF040 - 0xF9FC）
    //
    // ※半角カナは、存在しても問題なさそうなのでチェックしなかった
    //
    // なおSJISには 下位2バイトが00-3F,FD-FFの文字はない。
    // SJISコード表  http://hp.vector.co.jp/authors/VA039433/shift_jis-table.html
    //
    // あとはMacの機種依存文字（0x8540 - 0x889E、0xEAA5 - 0xFCFC）もあるがチェックしていない
    //
    // 機種依存文字が含まれていても、クライアントのOSが統一されており、文字変換が
    // 発生しない状況であれば問題はない。
    // （たとえばWindowsから機種依存文字をEUCでPOST ⇒ EUCのままLinuxサーバー上で登録 ⇒
    // 読み出して Windowsクライアントで表示、など。）
    //
    // 問題になるのは「クライアントのOSが混在している場合」と、「サーバーのOSがクライアントと
    // 異なり、しかもサーバー上で文字変換が発生する場合」。
    // このようなケースで機種依存文字を使用すると問題が発生する。
    //
    // Genの場合はクライアントOSはWindowsを想定しており、扱う文字コードはEUC(2009以降はUTF-8)に統一して
    // いる。サーバーがLinuxであっても、通常の画面からの機種依存文字の登録や読み出しは
    // 問題ない（文字コード変換が発生しないので）。
    // しかしCSVを扱う際にはサーバー上でSJIS⇒EUC(2009以降はUTF-8)変換が発生するため、機種依存文字で問題が
    // 発生する。
    //
    // ただしこのチェックでは、Sjisに含まれない機種依存文字は検出できない。
    // そのため、CSVインポートやエクセル登録において、「?」に文字化けしたまま登録されてしまうケースがある。
    //  ag.cgi?page=ProjectDocView&pPID=1574&pbid=233435
    //      まず、CSVでの機種依存文字の問題は次の2パターンがあります。
    //      (1) Shift-JISの機種依存文字を使用した場合
    //      (2) Shift-JISにない機種依存文字を使用した場合
    //
    //      (1)のケースについては、登録時にGen側で機種依存文字チェックを行っていますので、問題はありません。
    //
    //      しかし(2)の場合（今回のケース）は、Gen側での対処が難しいといえます。
    //      エクセル等でCSVを作成する段階、つまりGenにデータが登録される前の段階で問題が発生するためです。
    //
    //      エクセルはファイルをCSVとして保存するときに、文字コードをShift-JISに変換します。
    //      その際、Shift-JISにない文字がデータ内に含まれていると、それらはすべて「?」に変換して保存されるようになっています。
    //      （エクセル以外のCSV編集ソフトを使ったとしても、この点はたぶん同じ。ソフトによっては機種依存文字を入力した時点で警告してくれるかもしれないが）
    //
    //      そのファイルをGenにアップロードすると、Genとしてはその文字がもともと「?」なのか、特殊文字が変換された結果としての「?」なのかを判別することができません。
    //      それで、そのまま「?」として登録されてしまうわけです。
    //
    //      結論として、CSVおよびエクセル登録においては、(2)のケースの文字化け問題を防ぐことができません。
    //      ※エクセル登録も文字列をSjisで送信しているため、上記と同じ現象が起こる

    static function checkSjisDependencyChar($str)
    {
        $pattern = "/" .
                "[\x87][\x40-\x90]" .   // x8791を含めると、なぜか「金」で引っかかってしまう
                "|[\x87][\x92-\x9F]" .
                "|[\xED][\x40-\xFF]" .
                "|[\xEE][\x01-\xFC]" .
                "|[\xFA][\x40-\xFF]" .
                "|[\xFB][\x01-\xFF]" .
                "|[\xFC][\x01-\x4B]" .
                "|[\xF0-\xF9][\x40-\xFC]" .
                "/";

        // SJISは1バイト文字と2バイト文字が混在するので、$strにたいしていっぺんに preg_match するのはうまくいかない。1文字ずつ
        for ($i = 0; $i < mb_strlen($str, "SJIS"); $i++) {
            // ヒットした文字そのものを返したほうがいいように思えるが、そうしたとしても結局SJISなので画面表示できない。
            // （EUC変換しても機種依存文字なので文字化けする）
            // なので、何文字目でヒットしたかの情報を返したほうがいい。
            if (preg_match($pattern, mb_substr($str, $i, 1, "SJIS"))) {
                return $i;
            }
        }

        // 上記forループで検知できないケース
        if (preg_match($pattern, $str))
            return -99;

        return -1;
    }

    /**
     * 機種依存文字をカット（SJIS版）
     * 与えられたSJIS文字列が機種依存文字を含むなら、その文字を削除した文字列を返す。
     * 詳細は checkSjisDependencyChar() のコメントを参照。
     *
     * @access  static
     * @param   string     $str    加工前文字列
     * @return  string             加工済み文字列
     */
    static function cutSjisDependencyChar($str)
    {
        $pattern = "/" .
                "[\x87][\x40-\x9F]" .
                "|[\xED][\x40-\xFF]" .
                "|[\xEE][\x01-\xFC]" .
                "|[\xFA][\x40-\xFF]" .
                "|[\xFB][\x01-\xFF]" .
                "|[\xFC][\x01-\x4B]" .
                "|[\xF0-\xF9][\x40-\xFC]" .
                "/";

        // Sjisは1バイト文字と2バイト文字が混在するので、$strにたいしていっぺんに preg_replace するのはうまくいかない。1文字ずつ
        $res = "";
        for ($i = 0; $i < mb_strlen($str, "SJIS"); $i++) {
            $char = mb_substr($str, $i, 1, "SJIS");
            if (!preg_match($pattern, $char)) {
                $res .= $char;
            }
        }
        return $res;
    }

    /**
     * バイト数カウント
     * 全角は2、半角は1と数える
     *
     * @access  static
     * @param   string     $text    加工前文字列
     * @return  string              バイト数
     */
    static function strlenEx($text)
    {
        return mb_strwidth($text, "utf-8");
    }

    /**
     * 全角スペースも含めたtrim
     * trimとの違いは、全角スペースもtrimする点。
     *
     * @access  static
     * @param   string     $text    加工前文字列
     * @return  string              加工済み文字列
     */
    static function trimEx($text)
    {
        return mb_ereg_replace("(^( |　)+|( |　)+$)", "", $text);
    }

    /**
     * [[...]] を 配列（$form）の値に置き換える
     *
     * @access  static
     * @param   string     $text    加工前文字列
     * @return  string              加工済み文字列
     */
    static function bracketToForm($str, $form, $suffix = "")
    {
        $matches = "";
        $res = $str;
        if (preg_match_all("(\[\[[^\]]*\]\])", $res, $matches) > 0) {
            foreach ($matches[0] as $match) {
                $matchStr = $match;
                $matchStr = str_replace('[[', '', $matchStr);
                $matchStr = str_replace(']]', '', $matchStr);
                $val = @$form[$matchStr . $suffix];
                if ($val === null) {
                    $val = "null";
                } else {
                    $val = "'$val'";
                }
                $res = str_replace($match, $val, $res);
            }
        }
        return $res;
    }

    /**
     * SQL Where 文字列に SQL Injection が行われていないかどうかチェック
     *
     * @access  static
     * @param   string  $where  チェックする文字列
     * @return  bool            trueなら SQL Injectionなし
     */
    static function checkSQLInjection($where)
    {
        // セミコロンチェック
        if (strpos($where, ";") != FALSE) {
            return false;
        }
        // unionチェック
        if (preg_match("/(\n|\r| |(\*\/))union(\n|\r| |(\/\*))/i", $where, $dummy)) {
            return false;
        }
        return true;
    }

    /**
     * 文字列内の危険なHTMLタグと属性を無害化する。危険でないタグは残す。
     * ユーザーが入力した文字列を HTMLとして（htmlspecialcharsせずに）表示する場合に
     * 使用する。（メモパッド機能など）
     *
     * @access  static
     * @param   string  $str    元の文字列
     * @return  string          変換後の文字列
     */
    static function escapeDangerTags($str)
    {
        // strip_tags を使えば簡単なように思えるが、タグでない文字列も削除してしまう場合があるし（「><」など）、
        // onclick, onmouseover, href などの属性が残ってしまうので危険。（属性にjavascriptが設定できる）
        //$str = htmlspecialchars($str, ENT_QUOTES);

        $allowTags = array(
            'b',
            'br',
            'center',
            'dl',
            'dt',
            'em',
            'font',
            'h1',
            'h2',
            'h3',
            'h4',
            'h5',
            'h6',
            'hr',
            'i',
            'li',
            'ol',
            'p',
            's',
            'strike',
            'strong',
            'sub',
            'sup',
            'table',
            'tbody',
            'tr',
            'td',
            'u',
            'ul',
        );
        $allowAttribs = array(
            'align',
            'border',
            'color',
            'colspan',
            'rowspan',
            'size',
                //'style', これは許可してもいいかもしれないが、念のため・・
        );

        // HTMLタグを取り出す
        $matches = "";
        // $matches[0] は各タグが配列で入る。
        // $matches[1]（第1キャプチャ）は<の次のスラッシュ。（閉じタグ用）
        // $matches[2]（第2キャプチャ）はタグ名。
        // $matches[3]（第3キャプチャ）は属性。複数属性が含まれる場合もある。（コーテーション囲み内の「>」は無視していることに注意）
        // $matches[4]（第4キャプチャ）はコーテーションの中身　（不要）
        if (preg_match_all("/<(\/|)([a-zA-Z0-9]*)((\"[^\"]*\"|\'[^\']*\'|[^\'\">])*)>/", $str, $matches) > 0) {
            foreach ($matches[2] as $num => $match) {
                if (!in_array(strtolower($match), $allowTags)) {
                    // 許可タグリストに含まれていないタグはエスケープする
                    //  ENT_NOQUOTES にしていることに注意。GenのAjaxではサーバー側でのエスケープを行わないことが前提であり
                    //  （セキュアコーディングガイド参照）、ここでコーテーションをエスケープしてしまうと処理がうまくいかない
                    //  ことがある。例えば、チャットで「<"">」という文字列を登録するとJSエラーになる。
                    $str = str_replace($matches[0][$num], htmlspecialchars($matches[0][$num], ENT_NOQUOTES), $str);
                } else {
                    // 許可属性リストに含まれていない属性は削除する
                    if ($matches[3][$num] != '') {
                        $attArr = explode(' ', $matches[3][$num]);
                        foreach ($attArr as $att) {
                            $att = trim($att);
                            if ($att != '') {
                                $match2 = "";
                                if (preg_match("/^[a-zA-Z0-9]*/", $att, $match2) > 0) {
                                    if (!in_array(strtolower($match2[0]), $allowAttribs)) {
                                        $str = str_replace($att, '', $str);
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        return $str;
    }

    /**
     * getText イニシャライズ
     * フロントコントローラ および ExecMrp から呼ばれる
     *
     * @access  static
     * @param   なし
     * @return  なし
     */
    static function initGetText()
    {
        global $gen_db;

        $supportLangArr = self::getSupportLangList();

        // 使用する言語の決定。sessionにキャッシュする（sessionはログオン時にクリアされる）
        if (isset($_SESSION["gen_language"])) {
            $language = $_SESSION["gen_language"];
        } else {
            if (isset($_SESSION["session_id"])) {
                $sid = $_SESSION["session_id"];
            } else {
                $sid = -1;
            }

            // sessionに言語がキャッシュされていない場合（ログイン後の最初のページアクセス）は、ここで使用言語を決定する。
            // ユーザーマスタで使用言語が指定されていればその言語を使用し、
            // 指定されていない（自動判別）場合はブラウザの言語指定（$_SERVER['HTTP_ACCEPT_LANGUAGE']）を使用する。
            $query = "
            select
                language
            from
                session_table
                inner join user_master on session_table.user_id = user_master.user_id
            where
                session_id = '{$sid}'
            ";
            // index.phpのブラウザキャッシュ処理から呼び出された場合は、まだ $gen_db が作成されていない
            if (isset($gen_db)) {
                $lang = $gen_db->queryOneValue($query);
            } else {
                $gen_db2 = new Gen_Db();
                $gen_db2->connect();
                $lang = $gen_db2->queryOneValue($query);
                $gen_db2->close();
                unset($gen_db2);
            }
            if ($lang == "") {
                $lang = @$_SERVER['HTTP_ACCEPT_LANGUAGE'];
            }
            
            $language = "ja_JP";                                            // デフォルト日本語（ja-XX） ⇒翻訳ファイルなし
            $langArr = explode(",", $lang);
            // $supportLangArr は index.php で設定されているサポート言語リスト。[0]が短縮形(en等)、[1]が正式コード(en_US等)
            foreach ($supportLangArr as $supportLang) {
                if (preg_match('/' . $supportLang[0] . '/', $langArr[0])) {     // $langArr[0] に 'en'等の文字が含まれていたら
                    $language = $supportLang[1];                            // 'en_US' とする
                    break;
                }
            }

            $_SESSION["gen_language"] = $language;
        }

        // 環境変数の設定
        putenv("LANG={$language}");
        // ja_JP の影響を受ける範囲を LC_ALL(文字列、数値、日時などすべて)に設定
        setlocale(LC_ALL, $language);

        // メッセージファイルの名前と場所
        $domain = 'messages';
        bindtextdomain($domain, ROOT_DIR . "messages");
        textdomain($domain);
        // 以下の1文を入れないと環境により文字化けする（UTF-8で出力されないことがある）。
        // どの資料にも載ってなくて相当悩んだ。
        bind_textdomain_codeset($domain, "utf-8");
        
        // JS用ファイルの更新
        self::updateJSConvertFile();
    }
        
    /**
     * JavaScript用の getText/wordConvertファイルの更新
     *
     * @access  static
     * @param   $forceUpdate  強制的に更新するかどうか
     * @return  なし
     */
    static function updateJSConvertFile($forceUpdate = false) {
        //  ・files_dir に、Javascript getText（翻訳）/wordConvert（用語変換/カスタム項目）用の変換ファイル（json）を作成する。
        //      作成/更新するのは以下のいずれかの場合のみ。
        //          ・該当言語のjsonファイルが存在しない
        //          ・moファイルが更新された
        //          ・引数 $forceUpdate が trueの場合（wordConvert更新処理から呼ばれたとき）
        //  ・wordConvert処理も兼ねているため、日本語の場合であってもjsonファイルを作成する。
        //  ・moファイル全体をjsonに変換するとかなり重くなるため、scripts/gen_XXX.js に含まれるリテラルだけを対象とする。
        //　・以下の点に注意。
        //  　　※変換対象になるのは scripts/gen_XXX.js ファイルのみ。
        //          それ以外の名称のJSファイルは、getTextもwordConvertも効かないので注意。
        //  　　※jsファイルを更新してもjsonファイルは更新されない。
        //          moファイルを更新するか、wordConvert設定を変更をしない限り新たな翻訳は反映されない。
        //    　※getTextの訳語には用語変換（wordConvert）も反映される。
        //          例えばgetTextで「売上」⇒「Sales」、wordConvertで「Sales」⇒「Test」という設定がなされていた場合、
        //          ここで作成されるjsonファイルでは「売上」⇒「Test」という変換内容になる。
        //          しかしこの場合、「売上」⇒「テスト」というwordConvertが設定されていても、それは反映されない。（getTextが先に行われるので）
        
        $language = $_SESSION['gen_language'];
        
        $storage = new Gen_Storage("JSGetText");
        if (!$storage->exist($language)) {
            $storage->makeDir($language);
        }
        $modFlag = false;
        if ($language != "ja_JP") {
            // 該当言語のjsonファイルが作成されて以降、moファイルが更新されているかどうかを調べる
            $modFlag = true;
            $moModTime = filemtime(ROOT_DIR."messages/{$language}/LC_MESSAGES/messages.mo");
            if (!$moModTime) {
                throw new Exception("messages/{$language}/LC_MESSAGES/messages.mo がありません。");
            }
            $datFile = $storage->get("{$language}/modtime.dat");
            if ($datFile && file_exists($datFile)) {
                $moModTimeCache = file_get_contents($datFile); 
                if ($moModTimeCache == $moModTime) {
                    $modFlag = false;
                    //d("not mod");                
                } else {
                    //d("mod $moModTimeCache $moModTime");                
                }
            } else {
                $datFile = GEN_TEMP_DIR . "modtime.dat";
            }
            if ($modFlag) {
                $fh = fopen($datFile,"w");
                fwrite($fh, $moModTime);
                fclose($fh);
                $storage->put($datFile, true, "{$language}/modtime.dat");
            }
        }
        // 引数で更新が指示されているか、該当言語のjsonファイルがないか、moファイルが更新されている場合、jsonファイルを作成
        $jsonFile = $storage->get("{$language}/messages.json");
        if ($forceUpdate || !file_exists($jsonFile) || $modFlag) {
            if (!$jsonFile) {
                $jsonFile = GEN_TEMP_DIR . "messages.json";
            }
            $wordList = array();
            $wordArr = array();
            foreach(glob(APP_DIR . "scripts/gen_*.js") as $file) {
                $code = file_get_contents($file);
                for ($i=0;$i<=1;$i++) {
                    $quote = ($i==0 ? "\"" : "'");
                    $pos = 0;
                    while($pos = strpos($code, "_g(" . $quote, $pos)) {
                        $pos2 = strpos($code, $quote, $pos+4);
                        $word = substr($code, $pos+4, $pos2-$pos-4);
                        if (!in_array($word, $wordArr)) {
                            $convWord = _g($word);      // _g() なので getText だけでなく wordConvert も行われる
                            if ($word != $convWord) {
                                $wordList[$word] = array(null, $convWord);
                            }
                            $wordArr[] = $word;
                        }
                        $pos = $pos2+1;
                    }
                }
            }
            $json = json_encode(array("messages" => $wordList));
            file_put_contents($jsonFile, $json);
            $storage->put($jsonFile, true, "{$language}/messages.json");

            $lastModPropertyName = "jsGetText{$language}LastMod";
            $_SESSION['gen_setting_company']->$lastModPropertyName = filemtime($jsonFile);
        }
    }

    /**
     * サポート言語リスト
     * このクラスの initGetText() および Master_User_Edit から呼ばれる。
     * サポート言語を増やすときは、以下のリストを変更し、/messages/[言語]/LC_MESSAGES/ に poファイルを置く
     *
     * @access  static
     * @param   なし
     * @return  array       サポート言語リスト
     */
    static function getSupportLangList()
    {
        return
                array(
                    array("ja", "ja_JP", _g("日本語")),
                    array("en", "en_US", _g("英語")),
                //array("vi", "vi_VN", _g("ベトナム語")),
        );
    }

}
