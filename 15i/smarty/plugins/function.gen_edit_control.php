<?php
function smarty_function_gen_edit_control($params, &$smarty)
{
    require_once $smarty->_get_plugin_filepath('function', 'html_options');

    $_html_result = "";

    define(LIST_LINE_HEIGHT, 20);   // type=list の1行の高さ

    //************************************************
    // コントロールごとにループ
    //************************************************

    // 列数は可変
    $colCount = @$params['editColCount'];
    if (!is_numeric($colCount)) 
        $colCount = 2;

    $col = 1;

    // タブ関連
    $tabName_escape = "";
    $tabIdArr = array();
    foreach ($params['editControlArray'] as $ctl) {
        if (isset($ctl['tabId'])) {
            $tabIdArr[$ctl['tabId']] = $ctl['tabLabel'];
        }
    }
    reset($params['editControlArray']);

    // コントロールループ
    foreach ($params['editControlArray'] as $num => $ctl) {

        //************************************************
        // Tab
        //************************************************

        if ($ctl['type'] == 'tab') {
            $_html_result .= "</tr></table>\n";
            $col = 1;

            if ($tabName_escape === "") {
                $tabId_escape = h($ctl['tabId']);
                
                // 新規 tab
                $_html_result .= "
                    <div class=\"yui-skin-sam\">
                    <div id=\"gen_tab_container_{$tabId_escape}\" class=\"yui-navset\">
                        <ul class=\"yui-nav\">
                ";
                $first = true;
                foreach ($tabIdArr as $id => $label) {
                    $_html_result .= "<li" . ($first ? " class=\"selected\"" : "") . "><a href=\"#" . h($id) . "\"><em>" . h($label) . "</em></a></li>";
                    $first = false;
                }
                $_html_result .= "
                    </ul>
                    <div class=\"yui-content\">
                    <div id=\"{$tabId_escape}\">
                ";

                $tabName_escape = "gen_tab_container_{$tabId_escape}";
            } else {
                // tabページ追加
                $_html_result .= "
                    </div>
                    <div id=\"{$tabId_escape}\">
                ";
            }
            $_html_result .= "<table>\n";

            continue;
        }

        if ($ctl['type'] == 'tabEnd') {
            $_html_result .= "
                </tr></table>\n
                </div></div></div>
                <script>var gen_tabObj{$tabName_escape} = new YAHOO.widget.TabView('{$tabName_escape}');gen.tab.init(gen_tabObj{$tabName_escape},'{$tabName_escape}');</script>\n
                <table>
            ";
            $col = 1;
            continue;
        }

        //************************************************
        // 非表示項目
        //************************************************
        // JSでの処理の問題を回避するため、非表示でもdisplay:noneで配置しておく。

        if (isset($ctl['hide']) && $ctl['hide']) {
            $_html_result .= "<div id='gen_hide_div_" . h($ctl['name']) . "' style='display:none'>";
            $_html_result .= gen_edit_control_showControls($ctl, $params, $smarty, $colCount);
            $_html_result .= "</div>";
            continue;
        }

        //************************************************
        // <tr>タグ
        //************************************************

        if ($col == 1) {
            $_html_result .= "<tr height='26' style='text-align:left; vertical-align:top;'>\n";
        }

        //************************************************
        // <td> ラベルとチップヘルプ
        //************************************************
        // HTMLタグを使う場合があるのでエスケープはしない

        $labelWidth_escape = "";
        if (isset($ctl['labelWidth']) && is_numeric($ctl['labelWidth'])) {
            $labelWidth_escape = "max-width:" . $col['labelWidth'] . "px";
        } else if (isset($params['labelWidth']) && is_numeric($params['labelWidth'])) {
            $labelWidth_escape = "max-width:" . $params['labelWidth'] . "px";
        }
        
        if ($ctl['type'] == "literal" || $ctl['type'] == "section") {
            // literalの特別処理。
            // colspanが効くのはliteralのみ
            $colspan = (is_numeric($ctl['colspan']) ? $ctl['colspan'] : 1);
            if ($col + $colspan - 1 > $colCount) {
                $colspan = $colCount - $col + 1;
            }
            // 実際のcolspanの計算。ひとつの列にはラベル、チップヘルプ、コントロール、列間（右端にはナシ）の4つのtdが含まれる。
            $realColspan = $colspan * 4;
            if ($col + $colspan - 1 == $colCount) { // 右端の分は列間tdを含めない
                $realColspan--;
            }
            // ラベル
            //  idはD&D用オブジェクト作成に使用
            $label_escape = (isset($ctl['label']) ? h($ctl['label']) : '') . (isset($ctl['label_noEscape']) ? $ctl['label_noEscape'] : '');
            if ($ctl['type'] == "section") {
                $label_escape = "<span style='height:20px;font-size:14px;color:blue;border-left:solid 5px blue'>&nbsp;&nbsp;{$label_escape}</span>";
            }
            $_html_result .= "<td id='gen_editLabel_{$num}' colspan={$realColspan} "
                    // nowrapにすると、英語モードでの品目マスタ[工程]タブの最上部の表示がはみ出してしまう
                    . "style='cursor:move;vertical-align:middle;{$labelWidth_escape};" . (isset($ctl['style']) ? h($ctl['style']) : "") . "'>"
                    . "<b style='font-size:13px'>{$label_escape}</b>"
                    . "</td>\n";
            $col += $colspan - 1;
        } elseif ($ctl['type'] == "list" || $ctl['type'] == "table") {
            // list, table。なにもしない（ラベルtdをつくると余計なD&Dオブジェクトができてしまう）
        } else {
            // literal以外

            // ラベル
            //  idはD&D用オブジェクト作成に使用
            $label_escape = (isset($ctl['label']) ? h($ctl['label']) : '') . (isset($ctl['label_noEscape']) ? $ctl['label_noEscape'] : '');
            $_html_result .= "<td id='gen_editLabel_{$num}' "
                    . "style='cursor:move;vertical-align:middle;{$labelWidth_escape}' align=right>"
                    . "<b style='font-size:13px'>{$label_escape}</b>"
                    . "</td>";
            // チップヘルプ（日本語モードのみ）
            $_html_result .= "<td style='width:20px' valign='middle'>";
            $helpText = $ctl['helpText_noEscape'];  // 設定側でエスケープされているはず
            if ($helpText != "" && $_SESSION["gen_language"]=="ja_JP") {
                $name_escape = h($ctl['name']);
                $_html_result .= "<a class='gen_chiphelp' href='#' rel='p.helptext_{$name_escape}' title='{$label_escape}' tabindex='-1'><img class='imgContainer sprite-question' src='img/space.gif' style='border:none'></a>";
                $_html_result .= "<p class='helptext_{$name_escape}' style='display:none;'>{$helpText}</p>";
            }
            $_html_result .= "</td>\n";
        }

        //************************************************
        // <td> コントロール表示
        //************************************************
        //
        // td開始
        $nowrap = "";
        if (isset($ctl['nowrap']) && $ctl['nowrap'] == "true") 
            $nowrap = "nowrap";
        if ($ctl['type'] != "literal" && $ctl['type'] != "section" && $ctl['type'] != "list" && $ctl['type'] != "table")
            $_html_result .= "<td {$nowrap} " . (is_numeric($ctl['colspan']) ? "colspan=\"{$ctl['colspan']}\" " : "") 
                . "style=\"text-align:left; min-height: 20px; line-height: 20px; " 
                . (is_numeric($params['dataWidth']) ? "width:{$params['dataWidth']}px; white-space:nowrap" : "") . "\">";

        // コントロール
        $_html_result .= gen_edit_control_showControls($ctl, $params, $smarty, $colCount);

        // td閉じ
        if ($ctl['type'] != "literal" && $ctl['type'] != "section" && $ctl['type'] != "list" && $ctl['type'] != "table")
            $_html_result .= "</td>";
        if ($ctl['type'] == "list")
            $col = $colCount;
        if ($ctl['type'] == "table")
            $col = $colCount;

        //************************************************
        // </tr>タグ
        //************************************************

        if ($col < $colCount) {
            if ($ctl['type'] != "literal" && $ctl['type'] != "section") {
                $_html_result .= "<td width=\"30\"></td>";
            }
            $col++;
        } else {
            $_html_result .= "</tr>\n";
            $col = 1;
        }
    }

    //************************************************
    // 最後の</tr> タグ
    //************************************************

    if ($col != 1) {
        $_html_result .= "</tr>\n";
    }

    //************************************************
    // リターン
    //************************************************

    return $_html_result;
}



//************************************************
// コントロールの表示
//************************************************
function gen_edit_control_showControls($ctl, $params, $smarty, $colCount, $isInList = false, $editListLineNo = null, $editListOrgName = null)
{
    $_html_result = "";

    $name = $ctl['name'];
    $name_escape = h($name);
    $value = $ctl['value'];
    $value_escape = h($value);
    $size = is_numeric($ctl['size']) ? $ctl['size'] : "";

    // onChangeの設定
    $onChange_escape = "";
    $onChangeTag_escape = "";
    if (isset($ctl['onChange_noEscape'])) {
        $onChange_escape = $ctl['onChange_noEscape'];  // 設定側でエスケープされているはず。エスケープ済みとして扱う
    }

    // onKeyPressの設定
    $onKeyPress_escape = "";
    $onKeyPressTag_escape = "";
    if (isset($ctl['onKeyPress'])) {
        $onKeyPress_escape = h($ctl['onKeyPress']);
        $onKeyPressTag_escape = " onKeyPress=\"{$onKeyPress_escape}\"";
    }
    
    // readonlyの設定
    $isReadonly = (isset($ctl['readonly']) && $ctl['readonly'] == "true" ? true : false);

    // クライアントバリデーション
    $checkName = ($isInList ? $editListOrgName: $name);
    $paramLineNo = ($isInList ? $editListLineNo : "");
    if (is_array($params['clientValidArr'])) {
        if (isset($params['clientValidArr'][$checkName])) {
            if ($onChange_escape != '')
                $onChange_escape .= ";";
            $onChange_escape .= h($checkName) . "_check({$paramLineNo});";
            if (isset($params['clientValidArr'][$checkName]['dependentColumn'])) {
                foreach ($params['clientValidArr'][$checkName]['dependentColumn'] as $dc) {
                    $onChange_escape .= h($dc) . "_check({$paramLineNo});";
                }
            }
        }
    }

    if ($ctl['type'] == 'calendar') {
        $onChangeTag_escape = " onChange=\"gen.dateBox.dateFormat('{$name_escape}');{$onChange_escape}\" ";
    } else if ($ctl['type'] == 'dateFromTo') {
        $onChangeTag_escape = " onChange=\"gen.dateBox.dateFormat('{$name_escape}_from');gen.dateBox.dateFormat('{$name_escape}_to');{$onChange_escape}\" ";
    } else if ($ctl['type'] == "checkbox" && $value == "[multi]") {
        $onChangeTag_escape = " onChange=\"$('#{$name_escape}').val('true');$('#gen_cb_multi_{$name_escape}').html('');{$onChange_escape}\" "; 
    } else if ($onChange_escape != "") {
        $onChangeTag_escape = " onChange=\"{$onChange_escape}\" ";
    }

    // tabindexの設定
    $tabindexTag = "";
    if (is_numeric($ctl['tabindex'])) {
        $tabindexTag = " tabindex='{$ctl['tabindex']}'";
    }

    // style の設定
    $style_escape = (isset($ctl['style']) ? h($ctl['style']) . ";" : "");

    // IME制御 （IEとFF3以上のみ）
    if (isset($ctl['ime'])) {
        if ($ctl['ime'] == "on")
            $style_escape .= "ime-mode:active;";
        if ($ctl['ime'] == "off")
            $style_escape .= "ime-mode:inactive;";
    } else {
        // 強制off
        if ($ctl['type'] == 'calendar' || $ctl['type'] == 'dropdown') {
            $style_escape .= "ime-mode:inactive;";
        }
    }

    // 数値の丸めと3桁区切り
    if (isset($ctl['numberFormat']) && Gen_String::isNumeric($value)) {
        $point = $ctl['numberFormat'];
        if (!is_numeric($point)) 
            $point = GEN_DECIMAL_POINT_EDIT;
        if ($point == -1) {
            // 自然丸め： 小数点以下の無駄な0を削除する（例：「75.1000」⇒「75.1」　「75.0000」⇒「75」）
            //  カンマをつける処理は、number_format($value, 10) して自然丸め  というのがスマートに思えるが、
            //  小数点以下を10にした時点で演算誤差が発生することがあり、うまくいかない。
            //  下記では文字扱いすることにより誤差の発生を避けている。
            $decPos = strpos($value, ".");
            if ($value < 0) {
                // 負の値に対応する
                // echo ceil(4.3);    // 5
                // echo ceil(9.999);  // 10
                // echo ceil(-3.14);  // -3
                $left = number_format(ceil($value));  // 整数部。カンマをつける
            } else {
                // 正の値
                // echo floor(4.3);   // 4
                // echo floor(9.999); // 9
                // echo floor(-3.14); // -4
                $left = number_format(floor($value));  // 整数部。カンマをつける
            }
            if ($decPos === FALSE) {
                // 小数なし
                $value = $left;
            } else {
                // 小数あり
                $right = substr($value, $decPos + 1);         // 小数部。数値計算すると丸め誤差が発生することがあるので、文字として取り出す
                $right = preg_replace('/0+$/', '', $right);   // 小数点以下の有効数値部を取り出し
                $value = $left . ($right == '' ? '' : '.' . $right);
            }
        } else {
            // 小数点以下桁数指定
            $value = number_format($value, $ctl['numberFormat']);
        }
        $value_escape = $value;
    }

    // コントロール
    switch ($ctl['type']) {
        case 'calendar':        // カレンダー機能付テキストボックス
            if (is_numeric($size)) {
                $width = $size * 10;
                $style_escape .= "width:{$width}px;";
            }
            $_html_result .= "<input type='text' name='{$name_escape}' id='{$name_escape}' value='{$value_escape}' class='editTextbox'";
            $_html_result .= $onChangeTag_escape;
            $_html_result .= $onKeyPressTag_escape;
            if ($isReadonly) {
                $_html_result .= " readonly tabindex='-1' ";
                $style_escape .= "background-color:#cccccc;";
            } else  {
                $_html_result .= " {$tabindexTag} ";
                if (isset($ctl['require'])) {
                    $style_escape .= "background-color:#e4f0fd;";
                }
                $_html_result .= " onKeyDown='return gen.dateBox.onKeyDown(\"{$name_escape}\");'; ";
                $_html_result .= " onKeyUp='return gen.dateBox.onKeyUp(\"{$name_escape}\");'; ";
                $_html_result .= gen_edit_control_focusBlur($ctl);
            }
            $_html_result .= " style='{$style_escape};border:1px solid #7b9ebd; vertical-align:top;'>";
            $_html_result .= "<input type='button' class='mini_button' value='▼' onClick=\"gen.calendar.init('{$name_escape}_calendar', '{$name_escape}');\"" . ($isReadonly ? " disabled" : "") .
                            " tabindex='-1' style=\"margin-left: 2px;\">";

            if (!@$ctl['hideSubButton']) {
                $_html_result .= "&nbsp;&nbsp;<input type='button' class='mini_button' value='＜' onClick=\"gen.dateBox.dayChange('{$name_escape}',true)\"" . ($isReadonly ? " disabled" : "") . " tabindex='-1'>";
                $_html_result .= "<input type='button' class='mini_button' value=' T ' onClick=\"gen.dateBox.setToday('{$name_escape}')\"" . ($isReadonly ? " disabled" : "") . " tabindex='-1'>";
                $_html_result .= "<input type='button' class='mini_button' value='＞' onClick=\"gen.dateBox.dayChange('{$name_escape}',false)\"" . ($isReadonly ? " disabled" : "") . " tabindex='-1'>";
            }

            $_html_result .= gen_edit_control_showToolIcons($ctl, $params, $name, "", $isInList);
            $_html_result .= "<div id='{$name_escape}_calendar'></div>\n";
            break;

       case 'checkbox':        // チェックボックス
            if ($value == "[multi]") 
                $ctl['onvalue'] = "[multi]";

            $_html_result .= "<input type='checkbox' name='{$name_escape}' id='{$name_escape}' value='" . h($ctl['onvalue']) . "' {$tabindexTag}";
            $_html_result .= $onChangeTag_escape;
            $_html_result .= $onKeyPressTag_escape;
            $_html_result .= " style='{$style_escape}' ";
            // 't'を追加。Postgresのboolean値を表す文字リテラル  ag.cgi?page=ProjectDocView&pid=1195&did=112257
            if ($value === true || $value == 'true' || $value == 't' || $value == "[multi]") 
                $_html_result .= " checked";
            if ($isReadonly) {
                $_html_result .= " disabled tabindex='-1' ";
            }
            $_html_result .= ">";
            if ($value == "[multi]") 
                $_html_result .= "<span id='gen_cb_multi_{$name_escape}'>[multi]</span>";

            $_html_result .= gen_edit_control_showToolIcons($ctl, $params, $name, "", $isInList);
            break;

        case 'div':     // divタグ
            if (is_numeric($size)) {
                $width = $size * 10;
                // 基本的に、width指定/height未指定/overflow:hidden の場合、表示範囲を超える文字列は縦幅を拡張して折り返し表示される。
                // ここではその動作を利用して折り返し表示している。
                // ただし、IEとFFでは、数字の途中では折り返さないというルールがある。
                // IEに関しては独自プロパティ（CSS3草案で採用されたのでChromeにも実装されている）のword-breakで強制折り返しの指定ができる。
                // ここではそれを指定している。
                // FFに関しては制御の方法がないので、FFだけは数字の途中で折り返し表示されないという動きになっている。
                $style_escape .= "width:{$width}px; overflow:hidden; word-break:break-all;";
            }
            if (isset($ctl['align']))
                $style_escape .= "text-align:" . h($ctl['align']) . ";";
            if (isset($ctl['literal_noEscape']))
                $value_escape = str_replace('[gen_line_no]', $editListLineNo, $ctl['literal_noEscape']);
                
            $_html_result .= "<div id='{$name_escape}' name='{$name_escape}' style='{$style_escape}'>{$value_escape}</div>";
            if (!isset($ctl['literal_noEscape'])) {
                // hidden（value）
                $_html_result .= "<input type='hidden' id='div_value_{$name_escape}' name='div_value_{$name_escape}' value='{$value_escape}'>";
            }
            break;

        case 'dropdown':        // 拡張ドロップダウン
            if (is_numeric($size)) {
                $width = $size * 10;
            } else {
                $width = 150;
            }
            $dropdownCategory_escape = h($ctl['dropdownCategory']);
            $dropdownParam_escape = str_replace("'", "[gen_quot]", h($ctl['dropdownParam']));
            $dropdownShowCondition_escape = str_replace("'", "[gen_quot]", (isset($ctl['dropdownShowCondition_noEscape']) ? $ctl['dropdownShowCondition_noEscape'] : ""));
            $dropdownShowConditionAlert_escape = str_replace("'", "[gen_quot]", (isset($ctl['dropdownShowConditionAlert']) ? h($ctl['dropdownShowConditionAlert']) : ""));

            // スクリプト
            $_html_result .=
                "<script>" .
                // テキストボックス変更イベント。Ajaxによるidや表示名の取得を行う。
                // フィールドリストで定義した onChange は第5引数として渡され、上記の処理後に実行される。
                // ちなみに onChange をここではなくわざわざ gen.dropdown.onTextChange() に渡して実行しているのは、
                // gen.dropdown.onTextChange() における処理が確実にすべて終了した後に実行されるようにするため。
                // またautoCompleteに関しては、上のAutoCompleteイベントの箇所に書いたような理由で制御を行っている。
                "function {$name_escape}_onTextChange() {gen.dropdown.onTextChange('{$dropdownCategory_escape}','{$name_escape}_show','{$name_escape}','{$name_escape}_sub','{$name_escape}_show_onchange()','{$dropdownParam_escape}');}\n" .

                // 以前はonChangeスクリプトをエレメントのonChangeの中（onTextChangeの第4引数）に直接書いていたが、
                // ドロップダウンの選択時にonTextChangeを経由せず直接onChangeスクリプトを実行することになったため、
                // 独立させた
                "function {$name_escape}_show_onchange() {{$onChange_escape}}" .
                "</script>";

            // テキストボックス（_show）
            $styleShow_escape = "{$style_escape} width:{$width}px; border:1px solid #7b9ebd; vertical-align:top;";
            if ($isReadonly) {
                $styleShow_escape .= " background-color:#cccccc;";
            } else if (@$ctl['require']) {
                $styleShow_escape .= " background-color:#e4f0fd;";
            }

            // プレースホルダの処理。くわしくはテキストボックスのプレースホルダ処理の箇所を参照
            $isPlaceholder = (isset($ctl['placeholder']) && $ctl['placeholder'] != '' && !$isReadonly);
            if ($isPlaceholder) {
                $_html_result .= "<div style='position:relative; float:left; width:" . ($width + 2) . "px' onclick=\"$('#{$name_escape}_show').focus()\"><span id='{$name_escape}_show_placeholder' style='position:absolute;top:3px;left:3px;height:15px;line-height:15px;top;color:#9c9a9c;display:" . ($value === '' ? '' : 'none') . "'>" . h($ctl['placeholder']) . "</span>";
            }

            $_html_result .=
                "<input type='text' name='{$name_escape}_show' id='{$name_escape}_show' class='editTextbox'" .
                // 脆弱性回避と表示乱れ対処のため、エスケープ済みの値を使うようにした
                " value='" . h($ctl['dropdownShowtext']) . "'" .
                // 前のほうで定義されている
                " onChange=\"{$name_escape}_onTextChange();\"" .
                // readonlyのときは背景をグレーにしてreadonlyにする。（DDボタンもreadonly）
                // noWriteのときは背景を通常色にしてreadonlyにする。（DDボタンは使える）
                ($isReadonly ? " readonly tabindex='-1'" : // readonly
                        (@$ctl['noWrite'] ? " readonly tabindex='-1'" :  // noWrite
                                " {$tabindexTag}")) .                // 通常
                " style='{$styleShow_escape}'" .
                " {$tabindexTag} " .
                " onKeyDown=\"if (event.keyCode == 40 && (typeof(gen_ac_openflag_{$name_escape})=='undefined' || !gen_ac_openflag_{$name_escape})) $('#{$name_escape}_dropdown').click();\"" .
                //(!isset($ctl['autoCompleteCategory']) ? " onKeyDown=\"if (event.keyCode == 40 && gen_ac_openflag_{$name_escape}) $('#{$name_escape}_dropdown').click();\"" : "") .
                // onFocus, onBlur
                // lockアトリビュートは、Partner_Order_EditのJSでの製番のreadonlyコントロール用
                ($isReadonly ? " lock='true'" : gen_edit_control_focusBlur($ctl)) .
                ">";

            if ($isPlaceholder) {
                $_html_result .= "</div>";
            }

            // ドロップダウンボタン
            $_html_result .=
                "<input type='button' id='{$name_escape}_dropdown' name='{$name_escape}_dropdown' value='▼'" .
                " class='mini_button'" .
                " onClick=\"" . (isset($ctl['onClick']) ? h($ctl['onClick']) . ";" : "") .
                "gen.dropdown.show('{$name_escape}_show'," .                        
                "'{$dropdownCategory_escape}'," .
                "'{$dropdownParam_escape}'," .
                "'{$dropdownShowCondition_escape}'," .
                "'{$dropdownShowConditionAlert_escape}'" .
                ")\"" .
                " style='margin-left:2px;'" .
                ($isReadonly ? " disabled" : "") .
                " tabindex='-1' " .
                ">";

            // pin
            $_html_result .= gen_edit_control_showToolIcons($ctl, $params, $name, "{$name}_show", $isInList);

            // サブテキスト（テキストボックスの下に表示される項目。_sub）
            if ($ctl['dropdownHasSubtext']) {
                if (isset($ctl['isNewline']) && $ctl['isNewline'] == "true") {
                    $_html_result .= "<br>";
                }
                $width = (isset($ctl['subSize']) && is_numeric($ctl['subSize']) ? $ctl['subSize'] * 10 : $width);
                $styleSub_escape = "{$style_escape} margin-left:2px; width:{$width}px; border:none; background-color:#f0f0f0;'";
                $_html_result .= "<input type='text' id='{$name_escape}_sub' name='{$name_escape}_sub' class='editTextbox' value='" . h($ctl['dropdownSubtext']) . "' style='{$styleSub_escape}' readonly tabindex='-1'>";                
            }

            // AutoComplete
            if (isset($ctl['autoCompleteCategory']) && !$isReadonly) {
                $_html_result .= "
                <script>
                $('#{$name_escape}_show').autocomplete({
                    source:
                    function( request, response ) {
                        $.ajax({
                            url: \"index.php?action=Dropdown_AutoCompleteData&category=" . h($ctl['autoCompleteCategory']) . "&query=\"+encodeURIComponent(request.term)
                                +\"". (@$ctl['autoCompleteWhere']=="" ? "" : "&where=" . h($ctl['autoCompleteWhere'])) . "\",
                            dataType: \"json\",
                            success: function( data ) {
                                response(data);
                            },
                        });
                    },
                    open:
                    function( event, ui ) {
                        gen_ac_openflag_{$name_escape} = true;
                    },
                    close:
                    function( event, ui ) {
                        gen_ac_openflag_{$name_escape} = false;
                    },
                    minLength: 1,
                    delay: 500,
                    position: {collision: 'flipfit', my: 'right'},
                    select: function(event, ui) {
                        code = ui.item.value.replace(/ :  .*/, '');
                        ui.item.value = code;
                        $('#{$name_escape}_show').val(code);
                        {$name_escape}_onTextChange();
                    }
                });
                </script>
                ";
            }

            // hidden（value）
            $_html_result .= "<input type='hidden' id='{$name_escape}' name='{$name_escape}' value='{$value_escape}'>\n";

            break;

        case 'list':            // リスト
            $listRowCount = (is_numeric(@$ctl['rowCount']) ? $ctl['rowCount'] : 1);
            $listCtls = $ctl['controls'];
            if (!is_array($listCtls))
                throw new Exception("listのControlsが設定されていません。");

            // ********** 行ロック（readonly）の処理 **********
            $existReadonlyRow = false;
            if (isset($ctl['readonlyCondition'])) {
                if (isset($ctl['data']) && is_array($ctl['data'])) {   // EditBaseで プロパティ'query'が実行され、$ctl['data']に結果配列が格納される
                    foreach ($ctl['data'] as $num => $row) {
                        if (isset($ctl['dataForReadOnlyCondition'])) {
                            $row = $ctl['dataForReadOnlyCondition'][$num];
                        }
                       // 条件式の[...]をフィールドの値に置き換える
                        $exp1 = gen_edit_control_bracketToFieldValue($ctl['readonlyCondition'], $row);
                        // returnを入れることにより式の評価結果（真偽）を返している。文字列の最後にはセミコロンが必要。
                        if ($exp1 != '' && eval("return({$exp1});")) {
                            $ctl['data'][$num]['readonly'] = true;
                            $existReadonlyRow = true;
                        }
                    }
                }
            }

            // ********** EditListコントローラの作成 **********
            $controller = "<tr>";
            if ($existReadonlyRow) {
                //  ロックされた行が1行でもある場合、すべての行の挿入・削除・移動及び最大行数の変更を不可とする。
                //    この仕様は不便（とくに未ロック行まで削除できなくなってしまうのが）だが、
                //    ロック行が移動するといろいろな不都合が出てくるので、やむを得ずこのようにした。
                //
                //    この問題を解決するには、行移動の方式を変える必要がある。
                //    ※ いずれも主な変更箇所は gen_script.js の最後のほう。
                //
                //    方式A. 値移動方式（現行）
                //      行の各エレメントの値（valやhtml）および属性を入れ替える。
                //          現行の方式。
                //
                //          [問題点]
                //            ・選択ラジオボタンのdisabled、AutoCompleteの有無、onFocus/onBlurイベントの有無が
                //            　　入れ替えできていない（いずれも難しい）ため、ロック行を移動するとおかしくなる。
                //            ・行数を変更したり、validエラーになったりすると製番などが戻ってしまう。
                //            　　idが元の行に埋め込まれてしまうため。
                //            ・今後、新たなエレメント属性（プロパティ）を使用するようになった場合は、その入れ替え
                //            　　処理を実装しなければならない。
                //            ・行が多いとき、他の方式に比べて遅いと思う
                //
                //    方式B. TR移動・エレメントid非移動方式
                //          行(TR)自体を jQueryで移動したあと、行番号および各エレメントのidを入れ替えて戻す。
                //
                //          [問題点]
                //            ・イベントスクリプト内のidの交換が難しい。
                //            ・行数を変更したり、validエラーになったりすると製番などが戻ってしまう。
                //            　　idが元の行に埋め込まれてしまうため。
                //            ・移動時にalterColorがおかしくなる。
                //            ・行が多いとき、上のほうの行で挿入削除すると遅い（Cよりも。Aよりはマシか）
                //
                //    方式C. TR移動・エレメントid移動方式
                //            行(TR)自体を jQueryで移動（各エレメントのidも一緒に移動）し、行番号のみ入れ替えて戻す。
                //            行番号とIDを分離することができるため設計的には理想だが、実装は大変そう。
                //
                //          [問題点]
                //            ・行数を変更したり、validエラーになったりすると行が戻ってしまう。
                //            ・移動時にalterColorがおかしくなる。
                //            ・各所で各エレメントidの値が行番号として使用されている。下記のような変更が必要。
                //                全コードの lineNo/line_no は lineId/line_idに置き換える
                //                    lineNo      lineIdに全置換
                //                    line_no     個別に対処。.sql や PHPExcel, smartyなど対象からはずすべき箇所も
                //                行番号にhiddenを入れる。行番号エレメント（line_no_xxx）を頼っている箇所は、
                //                    このhiddenを頼るようにする
                //                入れ替え時、行番号とhiddenは再入れ替えして戻す
                //                Modelで、line_no は gen_line_no ではなくPOSTされたものを使用する
                //
                //                行挿入
                //                    新規行を挿入
                //                    新規行にline_no、opt、各コントロールのname/idを設定
                //                    挿入した行以下の line_no, optを書き換え（idからline_noを得る必要あり）
                //                行削除
                //                    行を削除
                //                    削除した行以下の line_no, optを書き換え（idからline_noを得る必要あり）
                //                    一番下に新規行を追加
                //                    新規行にline_no、opt、各コントロールのname/idを設定
                $controller .= "<td style='width:50px;'></td>";
                $controller .= "<td colspan='2' style='text-align:left;font-size:11px;color:#999999'>";
                $controller .= _g("ロックされている行があるため、行の挿入・削除・移動は行えません。");
                $controller .= "</td><tr><td></td></tr>";
            } else {
                // 行数セレクタ
                $listId_escape = h($ctl['listId']);
                $controller .= "<td rowspan='2' style='width:150px;text-align:left'>";
                $controller .= _g("明細行の数：");
                $controller .= "<select tabindex='-1' id='gen_select_{$listId_escape}'"; // 同じIDのエレメントが2つできてしまうが、ページJSでこのIDを頼っている箇所があるので消さないこと
                // 
                $controller .= " onchange=\"gen.edit.changeNumberOfList('" . $params['actionWithKey'] . "', '{$listId_escape}', $(this).val())\">\n";
                $controller .= ">\n";
                for ($i = 1; $i <= GEN_EDIT_DETAIL_COUNT; $i++) {
                    $controller .= "<option value='{$i}'" . ($ctl['lineCount'] == $i ? " selected" : "") . ">{$i}</option>\n";
                }
                $controller .= "</select>\n";
                $controller .= "</td>";
                // 行コントロールボタン
                $controller .= "<td rowspan='2' style='width:150px;text-align:left'>";
                $keyCol_escape = isset($ctl['keyColumn']) && !$params['isCopyMode'] ? h($ctl['keyColumn']) : "";
                $controller .= "<a href=\"javascript:gen.edit.editListDelete('{$listId_escape}',gen_getListCtls_{$listId_escape}(),'{$keyCol_escape}','" . h($ctl['deleteAction']) . "');{$onChange_escape}\" tabindex='-1'><img class='imgContainer sprite-cross' src='img/space.gif' border='0' title='" . _g("選択した行を削除") . "'></a>";
                $controller .= "<img src='img/space.gif' style='width:10px'>";
                $controller .= "<a href=\"javascript:gen.edit.editListInsert('{$listId_escape}',gen_getListCtls_{$listId_escape}(),false);{$onChange_escape}\" tabindex='-1'><img class='imgContainer sprite-plus' src='img/space.gif' border='0' title='" . _g("通常行を挿入") . "'></a>";
                $controller .= "<img src='img/space.gif' style='width:10px'>";
                $controller .= "<a href=\"javascript:gen.edit.editListUpDown('{$listId_escape}',gen_getListCtls_{$listId_escape}(),true);{$onChange_escape}\" tabindex='-1'><img class='imgContainer sprite-arrow-090' src='img/space.gif' border='0' title='" . _g("選択した行を上へ移動") . "'/></a>";
                $controller .= "<img src='img/space.gif' style='width:10px'>";
                $controller .= "<a href=\"javascript:gen.edit.editListUpDown('{$listId_escape}',gen_getListCtls_{$listId_escape}(),false);{$onChange_escape}\" tabindex='-1'><img class='imgContainer sprite-arrow-270' src='img/space.gif' border='0' title='" . _g("選択した行を下へ移動") . "' /></a>";
                $controller .= "</td>";
                // ヘッダヘルプ
                $controller .= "<td align='left' style='font-size:10px;color:#999999'>" . _g("※") . _g("入力中にも行数を変更できます。（入力内容は失われません。）") . "</td></tr>\n";
                $controller .= "<tr><td align='left' style='font-size:10px;color:#999999'>" . _g("※") . _g("見出しをマウスオーバーすると項目の説明が表示されます。") . "</td></tr>\n";
            }

            // 枠カラー
            //$color = "cccccc";  // gray
            //$color = "7f9db9";  // default
            //$color = "8faac2";  // 1 rank up
            //$color = "a0b7cb";  // 2 rank up
            $color = "b0c3d4";  // 3 rank up

            // ********** リスト上部 **********
            $_html_result .= "</tr></table>\n";
            $_html_result .= "<div style='height:5px'></div>\n";
            $_html_result .= "<table cellpadding='0' cellspacing='0'>\n";   // セレクタ等配置用の不可視テーブル

            // EditListコントローラの表示（上）
            $_html_result .= $controller;

            // リストテーブル開始
            $_html_result .= "<tr><td colspan='3'>";
                // ここで table-layout:fixed にすると、IE7で問題が生じる（どの列も固定幅になってしまう）
            $_html_result .= "<table border='0' cellspacing='1' cellpadding='2' style='background-color:#{$color};'>\n";

            // ********** 見出し行 **********
            $isEnglish = isset($_SESSION["gen_language"]) && $_SESSION["gen_language"] == "en_US";
            
            if (!$isEnglish) {
                $cellRow = 1;
                $_html_result .= "<tr style='height:" . ($listRowCount * LIST_LINE_HEIGHT) . "px;' class='editListTitle'>\n";
                $_html_result .= "<td align='center' width='30px'>" . _g("選択") . "</td>";
                $_html_result .= "<td align='center'>" . _g("行") . "</td>";

                foreach ($listCtls as $listCtl) {
                    // td
                    if ($cellRow == 1) {
                        $cellWidth = (is_numeric($listCtl['width']) ? "width:{$listCtl['width']}px;" : "");
                        $_html_result .= "<td style='{$cellWidth} text-align:center'>";
                    } else {
                        $_html_result .= "<br>";
                    }
                    // 見出しとチップヘルプ（日本語モードのみ）
                    $helpText_noEscape = $listCtl['helpText_noEscape'];
                    $label_escapae = h($listCtl['label']);
                    if ($helpText_noEscape != "" && $_SESSION["gen_language"]=="ja_JP") {
                        $name_escape = h($listCtl['name']);
                        $_html_result .= "<a class='gen_chiphelp' href='#' rel='p.helptext_{$name_escape}' title='{$label_escapae}' style='color:black; text-decoration:none;' tabindex='-1'>{$label_escapae}</a>";
                        $_html_result .= "<p class='helptext_{$name_escape}' style='display:none;'>{$helpText_noEscape}</p>";
                    } else {
                        $_html_result .= $label_escapae;
                    }
                    // td閉じ
                    if ($cellRow == $listRowCount) {
                        $_html_result .= "</td>\n";
                        $cellRow = 1;
                    } else {
                        $cellRow++;
                    }
                }
                $_html_result .= "</tr>\n";
                
            } else {

                // 英語モード用。
                // 英語モードでは上下段に区切りがないと見づらい。 ag.cgi?page=ProjectDocView&pid=1574&did=195595
                for ($cellRow=1; $cellRow<=$listRowCount; $cellRow++) {
                    $_html_result .= "<tr style='height:" . ($listRowCount * LIST_LINE_HEIGHT / 2) . "px;' class='editListTitle'>\n";
                    if ($cellRow == 1) {
                        $_html_result .= "<td width='30px' rowspan='{$listRowCount}'>" . _g("選択") . "</td>";
                        $_html_result .= "<td align='center' rowspan='{$listRowCount}'>" . _g("行") . "</td>";
                    }
                    $cnt = 0;
                    foreach ($listCtls as $listCtl) {
                        if ($cnt % $listRowCount != ($cellRow - 1)) {
                            ++$cnt;
                            continue;
                        }

                        // td
                        $cellWidth = (is_numeric($listCtl['width']) ? "width:{$listCtl['width']}px;" : "");
                        $_html_result .= "<td style='{$cellWidth} text-align:center'>";
                        
                        // 見出し（英語モードではチップヘルプなし）
                        $label_escapae = h($listCtl['label']);
                        if ($label_escapae == "") {
                            $label_escapae = "&nbsp;";
                        }
                        $_html_result .= $label_escapae;
                        // td閉じ
                        $_html_result .= "</td>\n";
                        ++$cnt;
                    }
                    $_html_result .= "</tr>\n";
                    reset($listCtls);
                }
            }

            $lineNo = 1;

            // ********** データ行 **********
            if (is_array(@$ctl['data'])) {   // EditBaseで プロパティ'query'が実行され、$ctl['data']に結果配列が格納される
                foreach ($ctl['data'] as $num => $row) {
                    $rowForColReadOnlyCondition = null;
                    if (isset($ctl['dataForReadOnlyCondition'])) {
                        $rowForColReadOnlyCondition = $ctl['dataForReadOnlyCondition'][$num];
                    }
                    // 行の表示
                    $_html_result .= gen_edit_control_showListLine($ctl['listId'], $lineNo, $listCtls, $listRowCount, $row, isset($row['readonly']), $rowForColReadOnlyCondition, @$ctl['rowColorCondition'], $params, $colCount, $smarty);

                    // キーをhiddenで埋め込む。JSで触ることがあるのでidも設定しておく。
                    // ただしコピーモードでは埋めこまない。
                    // ちなみにコピーモードではページ全体のキーも渡さないようにする必要があるが、その処理はEditBaseで
                    // 行っている.
                    if (isset($ctl['keyColumn'])) {
                        $keyCol_escape = h($ctl['keyColumn']);
                        $keyVal_escape = "";
                        if (isset($row[$ctl['keyColumn']]) && !$params['isCopyMode'])
                            $keyVal_escape = h($row[$ctl['keyColumn']]);
                        $_html_result .= "<input type='hidden' id='{$keyCol_escape}_{$lineNo}' name='{$keyCol_escape}_{$lineNo}' value='{$keyVal_escape}'>";
                    }

                    $lineNo++;
                }
            }

            // ********** 新規データ行 **********
            if ($lineNo <= $ctl['lineCount']) {
                for ($i = $lineNo; $i <= $ctl['lineCount']; $i++) {
                    $_html_result .= gen_edit_control_showListLine($ctl['listId'], $lineNo, $listCtls, $listRowCount, null, false, null, null, $params, $colCount, $smarty);

                    $lineNo++;
                }
            }

            // ********** リスト下部 **********
            $_html_result .= "</table>\n";  // リストテーブル
            // EditListコントローラの表示（下）
            $_html_result .= $controller;
            $_html_result .= "</tr></td></table>\n";    // 行数セレクタを含む不可視テーブル

            // ********** スクリプト **********
            // 行の中のすべてのコントロールの名前を取得するスクリプト（行入れ替えなどで使用）
            $_html_result .= "<script>";
            $_html_result .= "function gen_getListCtls_" . h($ctl['listId']) . "() {";
            $_html_result .= "return [";
            if (isset($ctl['keyColumn']) && !$params['isCopyMode']) {   // hidden埋込のキーカラム。最初に挿入する必要がある
                $_html_result .= "'" . h($ctl['keyColumn']) . "'";
            } else {
                $_html_result .= "''";
            }
            foreach($listCtls as $listCtl) {
                $_html_result .= ",'" . h($listCtl['name']) . "'";
            }
            if ($params['customEditListControlArray']) {
                foreach($params['customEditListControlArray'] as $listCtl) {
                    $_html_result .= ",'" . h($listCtl['name']) . "'";
                }
            }
            $_html_result .= "];} </script>";
            $_html_result .= "<table border='0' cellspacing='0' cellpadding='0'><tr height='22'>\n";

            break;

        case 'literal':     // リテラル。すでにラベル表示のところで処理しているのでここでは何もしない。
            break;

        case 'password':        // パスワード入力テキストボックス。
            if (is_numeric($size)) {
                $width = ($size * 10);
            } else {
                $width = 150;
            }
            $_html_result .= "<input type='password' name='{$name_escape}' id='{$name_escape}' value='{$value_escape}'";
            $_html_result .= " width='{$width}' {$tabindexTag} ";
            $_html_result .= $onChangeTag_escape;
            $_html_result .= $onKeyPressTag_escape;
            if (isset($ctl['require']) && $ctl['require']) {
                $style_escape .= "border:1px solid #7b9ebd; background-color:#e4f0fd;";
            }
            $_html_result .= gen_edit_control_focusBlur($ctl);
            $_html_result .= " style='{$style_escape}' ";
            $_html_result .= ">";
            break;
            
        case 'section':     // セクション見出し。すでにラベル表示のところで処理しているのでここでは何もしない。
            break;

        case 'select':          // セレクタ
            // 表示の崩れを防ぐため、選択肢の文字数を制限する。
            // <select>にCSSでwidthを指定する方法もあるが、それだとセレクタ自体のサイズは指定できるが、
            // ドロップダウンしたときの選択肢のサイズはコントロールできない。場合によっては選択肢が
            // 画面の右側にはみ出してしまい、スクロールバーが操作できなくなる。
            // ⇒取引先マスタの帳票選択で不便なので、とりあえず制限をはずしてみた
//            $maxLen = 30;      // gen_search_control, gen_db::getHtmlOptionArray と同じ
//            if (is_array($ctl['options'])) {
//                foreach ($ctl['options'] as $key => $val) {
//                    $ctl['options'][$key] = mb_substr($val, 0, $maxLen, 'UTF-8');
//                }
//            }
            
            if (is_array($ctl['options'])) {
                if ($ctl['selected']=="[multi]") {
                    $ctl['options']['[multi]'] = "[multi]";
                }            
            }
            $optionsParams = array('options' => $ctl['options'], 'selected' => $ctl['selected']);
            $_html_result .= "<select name='{$name_escape}' id='{$name_escape}'";
            $_html_result .= $onChangeTag_escape;
            $_html_result .= $onKeyPressTag_escape;
            if ($isReadonly) {    // セレクタはreadonlyが効かないのでdisabledにしてる
                $_html_result .= " disabled ";
                $style_escape .= "background-color:#cccccc;";
            } else {
                if (isset($ctl['require'])) {
                    $style_escape .= "background-color:#e4f0fd;";
                }
                $_html_result .= gen_edit_control_focusBlur($ctl);
            }
            $_html_result .= " style='{$style_escape} border:1px solid #7b9ebd;max-width:250px' {$tabindexTag} ";
            $_html_result .= ">";
            $_html_result .= smarty_function_html_options($optionsParams, $smarty);
            $_html_result .= "</select>\n";
            if ($isReadonly) {    // disabledだと値がPOSTされないので、hiddenを入れる
                $_html_result .= "<input type='hidden' name='{$name_escape}' value=\"" . h($ctl['selected']) . "\">\n";
            }

            $_html_result .= gen_edit_control_showToolIcons($ctl, $params, $name, "", $isInList);
            break;

        case 'select_hour_minute':          // 時・分セレクタ（5分刻み）
            $optArr = array();
            for ($hour = 0; $hour <= 23; $hour++) {
                $optArr[$hour] = $hour;
            }
            $optionsParams = array('options' => $optArr, 'selected' => $ctl['hourSelected']);
            $_html_result .= "<select id='{$name_escape}_hour' name='{$name_escape}_hour'";
            $_html_result .= $onChangeTag_escape;
            $_html_result .= $onKeyPressTag_escape;
            if ($isReadonly) {    // セレクタはreadonlyが効かないのでdisabledにしてる
                $_html_result .= " disabled ";
                $style_escape .= "background-color:#cccccc;";
            } else {
                if (isset($ctl['require'])) {
                    $style_escape .= "background-color:#e4f0fd;";
                }
                $_html_result .= gen_edit_control_focusBlur($ctl);
            }
            $_html_result .= " style='{$style_escape}' ";
            $_html_result .= ">";
            if (@$ctl['hasNothingRow']) {
                $_html_result .= "<option value=''>--</option>";
            }
            $_html_result .= smarty_function_html_options($optionsParams, $smarty);
            $_html_result .= "</select>" . _g("時") . "\n";
            if ($isReadonly) {    // disabledだと値がPOSTされないので、hiddenを入れる
                $_html_result .= "<input type='hidden' id='{$name_escape}_hour' name='{$name_escape}_hour' value='" . h($ctl['selected']) . "'>\n";
            }

            $optArr = array();
            for ($min = 0; $min <= 59; $min+=5) {
                $optArr[$min] = $min;
            }
            $optionsParams = array('options' => $optArr, 'selected' => $ctl['minuteSelected']);
            $_html_result .= "<select id='{$name_escape}_minute' name='{$name_escape}_minute'";
            if (!isset($ctl['onChangeHourOnly']) || !$ctl['onChangeHourOnly']) {
                $_html_result .= $onChangeTag_escape;
            }
            $_html_result .= $onKeyPressTag_escape;
            if ($isReadonly) {    // セレクタはreadonlyが効かないのでdisabledにしてる
                $_html_result .= " disabled ";
                $style_escape .= "background-color:#cccccc;";
            } else {
                if (isset($ctl['require'])) {
                    $style_escape .= "background-color:#e4f0fd;";
                }
                $_html_result .= gen_edit_control_focusBlur($ctl);
            }
            $_html_result .= " style='{$style_escape}' ";
            $_html_result .= ">";
            if (@$ctl['hasNothingRow']) {
                $_html_result .= "<option value=''>--</option>";
            }
            $_html_result .= smarty_function_html_options($optionsParams, $smarty);
            $_html_result .= "</select>" . _g("分") . "\n";
            if ($isReadonly) {    // disabledだと値がPOSTされないので、hiddenを入れる
                $_html_result .= "<input type='hidden' id='{$name_escape}_minute' name='{$name_escape}_minute' value='" . h($ctl['selected']) . "'>\n";
            }

            $_html_result .= gen_edit_control_showToolIcons($ctl, $params, "{$name}_hour", "{$name}_minute", $isInList);
            break;

        case 'table':            // テーブル
            $tableCount = $ctl['tableCount'];
            $tableCtls = $ctl['controls'];
            if (!is_array($tableCtls))
                throw new Exception("tableのControlsが設定されていません。");

            $_html_result .= gen_edit_control_showTableLine($ctl, $tableCtls, $tableCount, $params, $smarty);

            break;
            
        case 'textarea':        // テキストエリア
            $_html_result .= "<textarea id='{$name_escape}' name='{$name_escape}'";
            if (isset($ctl['cols'])) {
                $_html_result .= " cols=\"" . h($ctl['cols']) . "\"";
            }
            if (isset($ctl['rows'])) {
                $_html_result .= " rows=\"" . h($ctl['rows']) . "\"";
            }
            $_html_result .= $onChangeTag_escape;
            $_html_result .= $onKeyPressTag_escape;
            if ($isReadonly) {
                $_html_result .= " readonly tabindex='-1' ";
                $style_escape .= "border:none; background-color:#ffffff;";
            } else {
                $_html_result .= " {$tabindexTag} ";
                if (isset($ctl['require'])) {
                    $style_escape .= "border:1px solid #7b9ebd; background-color:#e4f0fd;";
                }
                // フォーカスを得たときに背景色を変える。
                // またテキストエリア内でのEnterを有効にするため、コントロールにフォーカスがあたったときにページ全体のonKeyDownを無効化する。
                $_html_result .= " onFocus=\"window.document.onkeydown=null;gen.ui.onFocus(this);\"";
                $_html_result .= " onBlur=\"window.document.onkeydown=gen.window.onkeydown;gen.ui.onBlur(this);\"";
            }
            $_html_result .= " style='{$style_escape}' ";
            $_html_result .= ">{$value_escape}</textarea>";

            $_html_result .= gen_edit_control_showToolIcons($ctl, $params, $name, "", $isInList);
            break;

        default:                // テキストボックス
            if (is_numeric($size)) {
                $width = ($size * 10);
            } else {
//                $width = 150;
            }
            $_html_result .= "<div nowrap style=\"display:inline-block; display:inline;\">";

            // プレースホルダの処理。
            //  テキストボックス上に淡い文字でテキストを表示する方法もあるが、その文字がPOSTされてしまう問題や、JSで読み取ってしまうなどがあり面倒なので、
            //  ここではspanタグでレイヤー処理するようにした。テキストボックスのfocus/blurイベント(gen.ui.onFocus)でspanタグの表示/非表示を切り替えている。
            $isPlaceholder = ($value == '' && isset($ctl['placeholder']) && $ctl['placeholder'] != '' && (!isset($ctl['readonly']) || !$ctl['readonly']));
            if ($isPlaceholder) {
                // ・外側divにwidthを指定しているのは、プレースホルダなしの場合と表示位置をあわせるため。
                // 　通常のinput(text)は後ろに3pxくらいのスキマがつくのに対し、relativeなdivで囲んでいるとそれがつかない。
                // ・外側divをspanにするとIE9で表示位置がおかしくなることがある。divでなければならない。
                // ・外側divに float:left を設定することで、テキストボックスの後ろで強制的に改行されてしまう問題に対処している
                //　（拡張DDのテキストボックスとドロップボタンの間で改行されてしまうなど）
                // ・内側spanのtopは、IE/FFだと3px、Chだと5pxでちょうどいい。間をとって4pxにしてある
                $_html_result .= "<div style='position:relative; float:left; width:" . ($width + 6) . "px' onclick=\"$('#{$name_escape}').focus()\"><span id='{$name_escape}_placeholder' style='position:absolute;top:2px;left:3px;color:#9c9a9c;'>" . h($ctl['placeholder']) . "</span>";
            }

            $_html_result .= "<input type='text' name='{$name_escape}' id='{$name_escape}' value='{$value_escape}' class='editTextbox'";
            $_html_result .= $onChangeTag_escape;
            $_html_result .= $onKeyPressTag_escape;
            if (isset($ctl['readonly']) && $ctl['readonly']) {
                $_html_result .= " readonly tabindex='-1' ";
                if ($isInList) {
                    $bgColor = "#cccccc";
                    $border = "1px solid #7b9ebd";        
                } else {
                    $bgColor = "#ffffff";
                    $border = "none";
                    $style_escape .= "text-align:";
                    if (isset($ctl['alignForReadonlyTextbox'])) {
                        $style_escape .= $ctl['alignForReadonlyTextbox'] . ";";
                    } else {
                        $style_escape .= "left;";
                    }
                }
            } else {
                $_html_result .= " {$tabindexTag} ";
                $bgColor = "#ffffff";
                $border = "1px solid #7b9ebd";        
                if (isset($ctl['require'])) {
                    $bgColor = "#e4f0fd";
                }
                $_html_result .= gen_edit_control_focusBlur($ctl);
            }
            $_html_result .= " style='{$style_escape} width:{$width}px; border:{$border}; background-color:{$bgColor};'";
            $_html_result .= ">";

            if ($isPlaceholder) {
                $_html_result .= "</div>";
            }

            // 過去の登録内容 Dropdown
            if (!$isInList && !$isReadonly && (!isset($params['hideHistorys']) || !$params['hideHistorys']) && (!isset($ctl['hideHistory']) || !$ctl['hideHistory'])) {
                $_html_result .= "<a href=\"javascript:gen.dropdown.show('{$name_escape}','textHistory','" . h($params['action']) . ":{$name_escape}','','');\" tabindex='-1'><img src='img/document-clock.png' style='vertical-align: middle;' border='0'></a>";
            }
            if (isset($ctl['afterLabel_noEscape'])) {
                $_html_result .= $ctl['afterLabel_noEscape'];
            }

            // pin
            $_html_result .= gen_edit_control_showToolIcons($ctl, $params, $name, "", $isInList);
            $_html_result .= "</div>";
            break;
    }

    // ボタン
    if (isset($ctl['buttonName'])) {
        $_html_result .= "<input type='button'";
        $_html_result .= " id='" . h($ctl['buttonName']) . "'";
        $_html_result .= " name='" . h($ctl['buttonName']) . "'";
        $_html_result .= " value='" . h($ctl['buttonValue']) . "'";
        $_html_result .= " style='{$style_escape}' onClick=\"" . h($ctl['buttonOnClick']) . "\" {$tabindexTag}>";
    }

    // クライアントバリデーションエラー表示エリア
    $checkName = ($isInList ? $editListOrgName : $name);
    if (is_array($params['clientValidArr'])) {
        if (isset($params['clientValidArr'][$checkName])) {
            // height指定はIE7対策。とりあえず0pxにしておき、表示時にautoに変更している。
            // IE7以外はheight指定なしでも大丈夫なのだが、IE7だけは指定しておかないと表示が崩れる。
            $_html_result .= "<div id='{$name_escape}_error' style='color:red; height:0px;" . ($isInList ? (isset($width) ? "width:{$width}px;" : "") : "width:" . (int) (480 / $colCount) . "px; white-space:normal") . "'></div>";
        }
    }

    return $_html_result;

}



//************************************************
// EditListの行表示
//************************************************
function gen_edit_control_showListLine($listId, $lineNo, $listCtls, $listRowCount, $row, $rowReadOnly, $rowForColReadOnlyCondition, $rowColorCondition, $params, $colCount, $smarty)
{
    $listId_escape = h($listId);
    
    // 行の色付け
    if ($rowReadOnly) {
        //$rowColor = "#cccccc";
        $rowColor = "#d9d9d9";  // 1 rank up
        $conditionColor = $rowColor;
    } else {
        $rowColor = "#" . ($lineNo % 2 == 0 ? "f2f3f4" : "ffffff");  // gen_script.js の gen.edit.editListSelect() とあわせる
        $conditionColor = "";
        if (is_array($rowColorCondition)) {
            // 色付け条件
            // 指定された条件が成立したときに、行に色がつく。
            // 配列のnameにカラーコード、valueに条件式（PHP構文）を指定する。
            // 条件式内の[]で囲まれた部分は、DBフィールドの値に置き換えられて評価される。
            // 色と条件の組合せはいくつでも指定できる。先に書かれている条件ほど優先度が高い
            //   例： 'rowColorCondition' => array(
            //          "#cccccc" => "[classification]=='受注'",
            //          "#999999" => "[classification]=='計画'"),
            $conditionColor = gen_edit_control_evalConditionArray($rowColorCondition, $row);
            if ($conditionColor) {
                $rowColor = $conditionColor;
            }
        }
    }
    // 行選択が解除されたときや行入れ替えがあったときのために、行の色を保存しておく
    //  行入れ替えの場合を考え、alterColor は保存しない（表示時に考慮する）。conditionColorのみ保存する。
    $_html_result = "<input type='hidden' id='gen_orgRowColor_{$listId_escape}_{$lineNo}' value='{$conditionColor}'>";

    // tr開始
    $_html_result .= "<tr id='gen_editlist_{$listId_escape}_{$lineNo}' style='background-color:{$rowColor};'>\n";
//    $_html_result .= "<tr id='gen_editlist_{$listId_escape}_{$lineNo}' style='border-bottom: solid 10px #cccccc; background-color:{$rowColor};'>\n";

    // td開始
    $cellRow = 1;
    $prevType = "";
    $prevName = "";
    
    // 行選択ラジオボタン列
    //  idは行の存在チェックに使用
    $_html_result .= "<td align='center'" . ($params['customEditListControlArray'] ? " rowspan='2'" : "") . ">";
    $_html_result .= "<input type='radio' name='gen_editlist_select_{$listId_escape}' value='{$lineNo}' ";
    $_html_result .= "onClick=\"gen.edit.editListSelect('{$listId_escape}')\" tabindex='-1'";
    $_html_result .= ($rowReadOnly ? " disabled" : "") . ">";
    $_html_result .= "</td>";

    // 行番号列　（注文書などを考えると、widthは35pxがぎりぎり。これ以上広くしないこと）
    $_html_result .= "<td align='center'" . ($params['customEditListControlArray'] ? " rowspan='2'" : "") . ">";
    $_html_result .= "<div style='height: 20px; margin-top: 2px; width: 35px; text-align: center;' id='line_no_{$lineNo}'>{$lineNo}</div>";
    // hidden（ロック用）
    $_html_result .= "<input type='hidden' id='readonly_line_no_{$lineNo}' name='readonly_line_no_{$lineNo}' value='" . ($rowReadOnly ? "true" : "false") . "'>";
    $_html_result .= "</td>";

    // コントロール
    $cellCol = 2;   // 「2」はラジオボタン列、行番号列の分
    foreach ($listCtls as $listCtl) {
        if ($cellRow == 1) {
            // IE8でリストに乱れが発生するため縦揃えのプロパティを追加する
            $_html_result .= "<td style='[[[vertical-align]]]'>";
        } else {
            // IE8でリストに乱れが発生するためliteral以外は縦揃えのプロパティをtopにする
            if ($listCtl['type'] == "literal" || $listCtl['type'] == "section") {
                $_html_result = str_replace("[[[vertical-align]]]", "", $_html_result);
            } else {
                $_html_result = str_replace("[[[vertical-align]]]", "vertical-align:top", $_html_result);
            }
            // テキストボックスをdivで囲んで対応
            //// 上段か下段がcalendarかdivのとき、またクライアントバリデーション表示エリアがあるときはbrを入れない
            //if ($listCtl['type']!="div" && $listCtl['type']!="calendar" && $prevType!="div" && $prevType!="calendar"
            //    && (!is_array($params['clientValidArr']) || !isset($params['clientValidArr'][$prevName])))
            //    $_html_result .= "<br>";
        }
        $prevType = $listCtl['type'];
        $prevName = $listCtl['name'];
        
        // コントロールを表示
        $_html_result .= gen_edit_control_showListLineSub($listCtl, $lineNo, $row, $rowReadOnly, $rowForColReadOnlyCondition, $params, $smarty, $colCount, false);

        // td閉じ
        if ($cellRow == $listRowCount) {
            $_html_result .= "</td>\n";
            $cellRow = 1;
            $cellCol++;
        } else {
            $cellRow++;
        }
    }
    $_html_result .= "</tr>\n";

    // カスタム項目行
    if ($params['customEditListControlArray']) {
        $_html_result .= "<tr id='gen_editlist_{$listId_escape}_{$lineNo}_custom' style='height:20px; background:red; border-bottom: solid 1px #cccccc; background-color:{$rowColor};'>\n";
        $_html_result .= "<td colspan='{$cellCol}' style='vertical-align:middle;'>";
        foreach($params['customEditListControlArray'] as $customCtl) {
            // 内側tableは、各コントロールの縦位置をそろえるため、および日付項目のカレンダーの表示乱れを避けるため
            $_html_result .= "<table style='display:inline-block;_display:inline'><tr style='height:25px'>";
            $_html_result .= "<td>" . h($customCtl['label']) . "</td>";
            $_html_result .= "<td>" . gen_edit_control_showListLineSub($customCtl, $lineNo, $row, $rowReadOnly, $rowForColReadOnlyCondition, $params, $smarty, $colCount, true) . "</td>";
            $_html_result .= "</tr></table>";
        }
        $_html_result .= "</td></tr>\n";
    }
    
    return $_html_result;
}

function gen_edit_control_showListLineSub($listCtl, $lineNo, $row, $rowReadOnly, $rowForColReadOnlyCondition, $params, $smarty, $colCount, $isCustomCol)
{
    // 各プロパティ値の「[gen_line_id]」を行IDに置き換える
    foreach ($listCtl as $key => $prop) {
        if (!is_array($prop) && $prop !== null) {
            $listCtl[$key] = str_replace("[gen_line_no]", $lineNo, $prop);
        }
    }

    // コントロールの表示値を設定
    $valueName = "value";
    if ($listCtl['type'] == "select") {
        $valueName = "selected";
    }
    $rowName = $listCtl['name'];
    if (!isset($listCtl[$valueName]) && is_array($row) && isset($row[$rowName])) {
        // 固定値が指定されていないとき：　データ配列（$row）から取得
        $listCtl[$valueName] = $row[$rowName];
    }

    // 拡張ドロップダウンのテキスト・サブテキスト（EditBaseで取得済みの値を埋め込む）
    if (isset($row[$listCtl['name'] . "_dropdownShowtext"])) {
        $listCtl['dropdownShowtext'] = $row[$listCtl['name'] . "_dropdownShowtext"];
    }
    if (isset($row[$listCtl['name'] . "_dropdownSubtext"])) {
        $listCtl['dropdownSubtext'] = $row[$listCtl['name'] . "_dropdownSubtext"];
    }
    if (isset($row[$listCtl['name'] . "_dropdownHasSubtext"])) {
        $listCtl['dropdownHasSubtext'] = $row[$listCtl['name'] . "_dropdownHasSubtext"];
    }

    // divタグの表示調整。表示値がないときも高さを確保する。
    // また、テキストボックスと文字の表示位置（縦方向）をあわせるため、上にマージンを入れる
    if ($listCtl['type'] == "div") {
        if (strpos($listCtl['style'], "height") === FALSE) {
            $listCtl['style'] = $listCtl['style'] . ";min-height:" . (LIST_LINE_HEIGHT - 2) . "px;margin-top:2px;";
        }
    }

    // readonlyCondition（コントロールレベル）の処理
    if ($rowReadOnly) {
        $listCtl['readonly'] = true;
    } else if (isset($listCtl['readonlyCondition'])) {
        if ($rowForColReadOnlyCondition) {
            $expRow = $rowForColReadOnlyCondition;
        } else {
            $expRow = $row;
        }
        // 条件式の[...]をフィールドの値に置き換える
        $exp1 = gen_edit_control_bracketToFieldValue($listCtl['readonlyCondition'], $expRow);

        // evalは文字列をPHPコードとして実行する関数。returnを入れることにより
        // 式の評価結果（真偽）を返している。文字列の最後にはセミコロンが必要。
        if (eval("return(" . $exp1 . ");")) {
            $listCtl['readonly'] = true;
        }
    }

    // deleteのキー設定
    if ($listCtl['type'] == "delete") {
        if (isset($row[$listCtl['idField']])) {
            $listCtl['idValue'] = $row[$listCtl['idField']];
        }
    }

    // nameの設定
    $orgName = $listCtl['name'];
    $listCtl['name'] = $listCtl['name'] . "_" . $lineNo;    // name の後ろに「_行ID」をつける

    // 表示
    if (!$isCustomCol) {
        $_html_result = "<div>";
    }
    $_html_result .= gen_edit_control_showControls($listCtl, $params, $smarty, $colCount, true, $lineNo, $orgName);
    if (!$isCustomCol) {
        $_html_result .= "</div>";
    }
    
    return $_html_result;
}


//************************************************
// EditTableの行表示
//************************************************
function gen_edit_control_showTableLine($ctl, $tableCtls, $tableCount, $params, $smarty)
{
    $_html_result = "";

    // 枠カラー
    //$color = "cccccc";  // gray
    //$color = "7f9db9";  // default
    //$color = "8faac2";  // 1 rank up
    //$color = "a0b7cb";  // 2 rank up
    $color = "b0c3d4";  // 3 rank up

    // ********** リスト上部 **********
    $_html_result .= "</tr></table>";
    // ここで table-layout:fixed にすると、IE7で問題が生じる（どの列も固定幅になってしまう）
    $_html_result .= "<table border='0' cellspacing='1' cellpadding='2' style='background-color:#{$color};'>\n";
    $_html_result .= "<tr style='height:" . LIST_LINE_HEIGHT . "px; background-color:#ffdead'>";
    $isLineNo = (isset($ctl['isLineNo']) && $ctl['isLineNo'] == "true" ? true : false);
    // 1行のコントロール数
    $rowCount = (isset($ctl['rowCount']) && is_numeric($ctl['rowCount']) ? $ctl['rowCount'] : 1);
    // 行番号
    if ($isLineNo)
        $_html_result .= "<td style='width:30px; padding:5px; text-align:center;'>No.</td>\n";
    // 見出し作成
    $cellRow = 1;
    foreach ($tableCtls as $tableCtl) {
        $cellWidth = "";
        $label_escape = h($tableCtl['label']);
        $name_escape = h($tableCtl['name']);
        if (isset($tableCtl['size']) && is_numeric($tableCtl['size'])) {
            $width = $tableCtl['size'] * 10;
            if ($tableCtl['type'] == "dropdown" || $tableCtl['type'] == "calendar") {
                $cellWidth = "width:" . ($width + 29) . "px;";
            } else {
                $cellWidth = "width:{$width}px;";
            }
        }
        if ($cellRow==1)
            $_html_result .= "<td style='{$cellWidth} padding:5px; text-align:center;'>";
        // 見出しとチップヘルプ（日本語モードのみ）
        $helpText_noEscape = $tableCtl['helpText_noEscape'];
        if ($helpText_noEscape != "" && $_SESSION["gen_language"]=="ja_JP") {
            $_html_result .= "<a class='gen_chiphelp' href='#' rel='p.helptext_{$name_escape}' title='{$label_escape}' style='color:black; text-decoration:none;' tabindex='-1'>{$label_escape}</a>";
            $_html_result .= "<p class='helptext_{$name_escape}' style='display:none;'>{$helpText_noEscape}</p>";
        } else {
            $_html_result .= $label_escape;
        }
        if ($cellRow < $rowCount) {
            $_html_result .= "<br>";
            $cellRow++;
        } else {
            $_html_result .= "</td>\n";
            $cellRow = 1;
        }
    }
    $_html_result .= "</tr>";

    // ********** データ行 **********
    $lineHeight = (isset($ctl['lineHeight']) && is_numeric($ctl['lineHeight']) ? $ctl['lineHeight'] : 30);
    $onLastLineKeyPress = (isset($ctl['onLastLineKeyPress']) && $ctl['onLastLineKeyPress'] == "true" ? true : false);
    $count = ($tableCount == 0 ? 1 : $tableCount);
    for ($i = 1; $i <= $count; $i++) {
        // 行の色付け
        $rowColor = "#" . ($i % 2 == 0 ? "f2f3f4" : "ffffff");  // gen_script.js の gen.edit.editListSelect() とあわせる
        $_html_result .= "<tr id='gen_edittable_{$i}' style='height: {$lineHeight}px; border-bottom: solid 10px #cccccc; background-color:{$rowColor};'>\n";
        // 行番号
        if ($isLineNo)
            $_html_result .= "<td style='width:30px; padding:5px; text-align:center;'>{$i}</td>\n";
        // データ行作成
        $cellRow = 1;
        foreach ($tableCtls as $tableCtl) {    // コントロール
            // IE8でリストに乱れが発生するため縦揃えのプロパティを追加する
            if ($cellRow==1)
                $_html_result .= "<td " . ($tableCtl['nowrap'] == "true" ? "nowrap" : "") . " style='padding:5px; text-align:left;'>";
            // 行番号設定
            if ($tableCount != 0) {
                $tableCtl['name'] = $tableCtl['name'] . "_{$i}";
                $tableCtl['onChange_noEscape'] = str_replace("[[lineNo]]", $i, $tableCtl['onChange_noEscape']);
                $tableCtl['value'] = $ctl[$tableCtl['name']];
                // セレクタ対応
                if ($tableCtl['type'] == "select") {
                    $tableCtl['selected'] = str_replace("[[lineNo]]", $i, $tableCtl['selected']);
                    if (isset($tableCtl['value']) && $tableCtl['value'] != null) $tableCtl['selected'] = $tableCtl['value'];
                }
                // ドロップダウン対応
                if ($tableCtl['type'] == "dropdown") {
                    $tableCtl['dropdownShowtext'] = $tableCtl[$tableCtl['name'] . "_dropdownShowtext"];
                    $tableCtl['dropdownSubtext'] = $tableCtl[$tableCtl['name'] . "_dropdownSubtext"];
                }
            } else {
                // ドロップダウン対応
                if ($tableCtl['type'] == "dropdown") {
                    $tableCtl['dropdownShowtext'] = $tableCtl[$tableCtl['name'] . "_dropdownShowtext"];
                    $tableCtl['dropdownSubtext'] = $tableCtl[$tableCtl['name'] . "_dropdownSubtext"];
                }
            }
            // 最終行Enterイベント
            if ($i == $count && $onLastLineKeyPress)
                $tableCtl['onKeyPress'] = "gen.edit.editTableLastLineEnter(this.name);";
            // 表示
            $_html_result .= gen_edit_control_showControls($tableCtl, $params, $smarty, $colCount, false, null, null);
            if ($cellRow < $rowCount) {
                $_html_result .= "<br>";
                $cellRow++;
            } else {
                $_html_result .= "</td>\n";
                $cellRow = 1;
            }
        }
        $_html_result .= "</tr>";
    }

    // ********** 合計行 **********
    if ($tableCount != 0 && isset($ctl['isTotal']) && $ctl['isTotal'] == "true") {
        $_html_result .= "<tr id='gen_edittable_total' style='border-bottom: solid 10px #cccccc; background-color:#ffdead;'>\n";
        if ($isLineNo)
            $_html_result .= "<td align='center'>" . _g('計') . "</td>\n";
        // 合計行作成
        $cellRow = 1;
        foreach ($tableCtls as $tableCtl) {    // コントロール
            // IE8でリストに乱れが発生するため縦揃えのプロパティを追加する
            if ($cellRow==1)
                $_html_result .= "<td " . ($tableCtl['nowrap'] == "true" ? "nowrap" : "") . " style='padding: 5px; text-align:left;'>";
            if (isset($tableCtl['totalLine']) && $ctl['isTotal'] == "true") {
                $tableCtl['name'] = $tableCtl['name'] . "_total";
                $tableCtl['readonly'] = true;
                $_html_result .= gen_edit_control_showControls($tableCtl, $params, $smarty, $colCount, false, null, null);
            }
            if ($cellRow < $rowCount) {
                $_html_result .= "<br>";
                $cellRow++;
            } else {
                $_html_result .= "</td>\n";
                $cellRow = 1;
            }
        }
        $_html_result .= "</tr>";
    }
    $_html_result .= "</table>";
    $_html_result .= "<table border='0' cellspacing='0' cellpadding='0'><tr height='22'>";

    return $_html_result;
}


//************************************************
// onFocus, onBlur
//************************************************
function gen_edit_control_focusBlur($ctl)
{
    $focusZoom = "";
    $blurZoom = "";
    if (isset($ctl['focusZoom'])) {
        list($direction, $width, $height) = $ctl['focusZoom'];
        $focusZoom = "gen.ui.onFocusZoom(this,'" . h($direction) . "','" . h($width) . "','" . h($height) . "');";
        $blurZoom = "gen.ui.onBlurZoom(this);";
    }
    return
        " onFocus=\"{$focusZoom}gen.ui.onFocus(this);\"" .
        " onBlur=\"{$blurZoom}gen.ui.onBlur(this," . (isset($ctl['require']) ? "true" : "false") . ");\"";
}


//************************************************
// ピンの表示
//************************************************
function gen_edit_control_showToolIcons($ctl, $params, $name1, $name2, $isInList, $dropdownDataName = "")
{
    if ($isInList)
        return "";           // EditList内では表示しない
    if (!$params['isNew'])
        return "";   // 新規のみ
    if (isset($ctl['readonly']) && $ctl['readonly'])
        return "";

    $res = "";

    // ピン
    if (isset($params['hidePins']) && $params['hidePins'])
        return $res; // ページ全体
    if (isset($ctl['hidePin']) && $ctl['hidePin'])
        return $res;         // 個別コントロール

    if ($dropdownDataName != "")
        $name1 = $dropdownDataName; // ドロップダウンのみ：hiddenをピンの対象とする
    if (!is_array($params['pins'])) {
        $isOn = false;
    } else {
        $isOn = in_array($name1, $params['pins']);
    }
    
    $name1_escape = h($name1);
    $name2_escape = h($name2);
    $actionWithPageMode_escape = h($params['actionWithPageMode_noEscape']);
    $res .= "<img src=\"img/pin02.png\" id=\"gen_pin_off_{$name1_escape}\" style=\"vertical-align: middle; cursor:pointer;" . ($isOn ? "display:none;" : "") . "\" onclick=\"gen.pin.turnOn('{$actionWithPageMode_escape}', '{$name1_escape}', '{$name2_escape}');\">";
    $res .= "<img src=\"img/pin01.png\" id=\"gen_pin_on_{$name1_escape}\" style=\"vertical-align: middle; cursor:pointer;" . ($isOn ? "" : "display:none;") . "\" onclick=\"gen.pin.turnOff('{$actionWithPageMode_escape}', '{$name1_escape}', '{$name2_escape}');\">";

    return $res;
}


//************************************************
// 汎用ユーティリティ関数群
//************************************************
//
// 文字列内の[...]をフィールドの値に置き換える
function gen_edit_control_bracketToFieldValue($sourceStr, $row)
{
    $matches = "";
    $res = $sourceStr;
    if (preg_match_all("(\[[^\]]*\])", $res, $matches) > 0) {
        foreach ($matches[0] as $match) {
            $matchStr = $match;
            $matchStr = str_replace('[', '', $matchStr);
            $matchStr = str_replace(']', '', $matchStr);
            $res = str_replace($match, @$row[$matchStr], $res);
        }
    }
    return $res;
}

// 与えられた条件配列を評価し、合致する条件のkeyを返す。
//  引数$condArrは array("key1" => "条件式1", "key2" => "条件式2",・・・）　のような形。
//  最初の条件式から順に評価され（PHP構文）、trueならそのkeyが返る。
//  trueになる条件が見つかった時点で評価は終了する（つまり前に書かれている条件が優先）。
//  成立する条件がなければfalseが返る。
//  条件式の中の [...] の部分はフィールドの値に置き換えられて評価される。
// 　　例： array(
//          "#cccccc" => "[classification]=='受注'",
//          "#999999" => "[classification]=='計画'"),
//   もしフィールドclassification の値が '受注' なら、'#cccccc' が返る。
function gen_edit_control_evalConditionArray($condArr, $row)
{
    if (!is_array($condArr))
        return false;

    while (list($key, $exp1) = each($condArr)) {
        // 条件式の[...]をフィールドの値に置き換える
        $exp1 = gen_edit_control_bracketToFieldValue($exp1, $row);

        // evalは文字列をPHPコードとして実行する関数。returnを入れることにより
        // 式の評価結果（真偽）を返している。文字列の最後にはセミコロンが必要。
        if (eval("return({$exp1});")) {
            return $key;
        }
    }
    return false;
}
