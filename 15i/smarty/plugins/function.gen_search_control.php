<?php

function smarty_function_gen_search_control($params, &$smarty)
{
    require_once $smarty->_get_plugin_filepath('function', 'html_options');
    require_once $smarty->_get_plugin_filepath('function', 'html_select_date');

    $_html_result = "";

    //************************************************
    // コントロールごとにループ
    //************************************************

    foreach ($params['searchControlArray'] as $ctl) {

        if ($ctl['type'] == "hidden")
            continue;

        $label_escape = (isset($ctl['label']) ? h($ctl['label']) : '') . (isset($ctl['label_noEscape']) ? $ctl['label_noEscape'] : '');
        $name = $ctl['name'];
        $name_escape = h($name);
        $value = $ctl['value'];
        $value_escape = h($value);

        // 値保持用
        if ($ctl['type'] == "holder") {
            $_html_result .= "<input type='hidden' name='{$name_escape}' id='{$name_escape}' value='{$value_escape}'>";
            continue;
        }

        //************************************************
        // 非表示項目
        //************************************************
        // JSでの処理の問題を回避するため、非表示でもdisplay:noneで配置しておく。

        if (isset($ctl['hide']) && $ctl['hide']) {
            $_html_result .= "<div id='gen_search_hide_div_{$name_escape}' style='display:none'>";
            $_html_result .= gen_edit_control_showControls($ctl, $params, $name, $ctl['size'], $value, $smarty);
            $_html_result .= "</div>";
            continue;
        }

        //************************************************
        // 見出し
        //************************************************

        $helpText_noEscape = $ctl['helpText_noEscape']; 

        // intro用
        if (isset($ctl['introId'])) {
            $_html_result .= "<span id=\"" . h($ctl['introId']) . "\">";
        }
        
        // ラベル本体
        $_html_result .= $label_escape;

        // 日付範囲セレクタ
        if ($ctl['type'] == 'dateFromTo' || $ctl['type'] == 'dateTimeFromTo') {
            // 選択肢を追加・削除・変更するときは、ListBase と gen_calendar.js も変更が必要（'datePattern'で検索）
            $datePattern = (isset($ctl['datePattern']) ? $ctl['datePattern'] : "");
            $_html_result .= "<select id='gen_datePattern_{$name_escape}' name='gen_datePattern_{$name_escape}' onchange=\"gen.dateBox.setDatePattern('{$name_escape}')\">";
            $_html_result .= "<option value=-1" . ($datePattern == '-1' ? ' selected' : '') . ">" . "" . "</option>";
            $_html_result .= "<option value=0" . ($datePattern == '0' ? ' selected' : '') . ">" . _g("なし") . "</option>";
            $_html_result .= "<option value=1" . ($datePattern == '1' ? ' selected' : '') . ">" . _g("今日") . "</option>";
            $_html_result .= "<option value=16" . ($datePattern == '16' ? ' selected' : '') . ">" . _g("今日以前") . "</option>";
            $_html_result .= "<option value=3" . ($datePattern == '3' ? ' selected' : '') . ">" . _g("今週") . "</option>";
            $_html_result .= "<option value=5" . ($datePattern == '5' ? ' selected' : '') . ">" . _g("今月") . "</option>";
            $_html_result .= "<option value=7" . ($datePattern == '7' ? ' selected' : '') . ">" . _g("今年") . "</option>";
            $_html_result .= "<option value=13" . ($datePattern == '13' ? ' selected' : '') . ">" . _g("今年度") . "</option>";
            $_html_result .= "<option value=2" . ($datePattern == '2' ? ' selected' : '') . ">" . _g("昨日") . "</option>";
            $_html_result .= "<option value=4" . ($datePattern == '4' ? ' selected' : '') . ">" . _g("先週") . "</option>";
            $_html_result .= "<option value=6" . ($datePattern == '6' ? ' selected' : '') . ">" . _g("先月") . "</option>";
            $_html_result .= "<option value=8" . ($datePattern == '8' ? ' selected' : '') . ">" . _g("昨年") . "</option>";
            $_html_result .= "<option value=14" . ($datePattern == '14' ? ' selected' : '') . ">" . _g("昨年度") . "</option>";
            $_html_result .= "<option value=9" . ($datePattern == '9' ? ' selected' : '') . ">" . _g("明日") . "</option>";
            $_html_result .= "<option value=10" . ($datePattern == '10' ? ' selected' : '') . ">" . _g("来週") . "</option>";
            $_html_result .= "<option value=11" . ($datePattern == '11' ? ' selected' : '') . ">" . _g("来月") . "</option>";
            $_html_result .= "<option value=12" . ($datePattern == '12' ? ' selected' : '') . ">" . _g("来年") . "</option>";
            $_html_result .= "<option value=15" . ($datePattern == '15' ? ' selected' : '') . ">" . _g("来年度") . "</option>";
            $_html_result .= "</select>";
            $_html_result .= gen_search_control_showToolIcons($ctl, $params, "gen_datePattern_{$name_escape}", "");
            // チップヘルプ
            if ($helpText_noEscape != "") {
                $_html_result .= "&nbsp;<a class='gen_chiphelp' href='#' rel='p.helptext_{$name_escape}_from' title='{$label_escape}'><img class='imgContainer sprite-question' src='img/space.gif' style='border:none'></a>";
                $_html_result .= "<p class='helptext_{$name_escape}_from' style='display:none;'>{$helpText_noEscape}</p>";
            }
            
        // 文字列範囲セレクタ
        } elseif($ctl['type'] == 'strFromTo') {
            // 選択肢を追加・削除・変更するときは、ListBase と gen_script.jp も変更が必要（'strPattern'で検索）
            $strPattern = (isset($ctl['strPattern']) ? $ctl['strPattern'] : "-1");
            $_html_result .= "<br><select id='gen_strPattern_{$name_escape}' name='gen_strPattern_{$name_escape}' onchange=\"gen.list.strPatternChange('{$name_escape}')\">";
            $_html_result .= "<option value=\"-1\"" . ($strPattern=="-1" ? " selected" : "") . ">" . _g("範囲") . "</option>";
            $_html_result .= "<option value=\"0\"" . ($strPattern=="0" ? " selected" : "") . ">" . _g("を含む") . "</option>";
            $_html_result .= "<option value=\"1\"" . ($strPattern=="1" ? " selected" : "") . ">" . _g("で始まる") . "</option>";
            $_html_result .= "<option value=\"2\"" . ($strPattern=="2" ? " selected" : "") . ">" . _g("で終わる") . "</option>";
            $_html_result .= "<option value=\"3\"" . ($strPattern=="3" ? " selected" : "") . ">" . _g("と一致") . "</option>";
            $_html_result .= "<option value=\"4\"" . ($strPattern=="4" ? " selected" : "") . ">" . _g("を含まない") . "</option>";
            $_html_result .= "<option value=\"5\"" . ($strPattern=="5" ? " selected" : "") . ">" . _g("で始まらない") . "</option>";
            $_html_result .= "<option value=\"6\"" . ($strPattern=="6" ? " selected" : "") . ">" . _g("で終わらない") . "</option>";
            $_html_result .= "</select>";
            $_html_result .= gen_search_control_showToolIcons($ctl, $params, "gen_strPattern_{$name_escape}", "");
            // チップヘルプ
            if ($helpText_noEscape != "") {
                $_html_result .= "&nbsp;<a class='gen_chiphelp' href='#' rel='p.helptext_{$name_escape}_from' title='{$label_escape}'><img class='imgContainer sprite-question' src='img/space.gif' style='border:none'></a>";
                $_html_result .= "<p class='helptext_{$name_escape}_from' style='display:none;'>{$helpText_noEscape}</p>";
            }
            
        } else {
            // チップヘルプ
            if ($helpText_noEscape != "") {
                $name2_escape = $name_escape . ($ctl['type'] == 'dropdown' ? '_show' : '');
                $name2_escape = str_replace('#', '_', $name2_escape);     // #が含まれていると正しく表示されない
                $_html_result .= "&nbsp;<a class='gen_chiphelp' href='#' rel='p.helptext_{$name2_escape}' title='{$label_escape}'><img class='imgContainer sprite-question' src='img/space.gif' style='border:none'></a>";
                $_html_result .= "<p class='helptext_{$name2_escape}' style='display:none;'>{$helpText_noEscape}</p>";
            }
        }

        if($ctl['type']!='calendar') 
            $_html_result .= "<br>";

        //************************************************
        // コントロール
        //************************************************

        $_html_result .= gen_edit_control_showControls($ctl, $params, $name, $ctl['size'], $value, $smarty);


        //************************************************
        // 閉じ
        //************************************************

        // intro用
        if (isset($ctl['introId'])) {
            $_html_result .= "</span>";
        }
        
        if($ctl['type']!='calendar' && $ctl['type']!='dateFromTo')
            $_html_result .= "<br>";
        $_html_result .= "<div style='height:7px'></div>";
    }

    //************************************************
    // リターン
    //************************************************

    return $_html_result;
}

//************************************************
// コントロールの表示
//************************************************
function gen_edit_control_showControls($ctl, $params, $name, $size, $value, $smarty)
{
    $_html_result = "";

    $name_escape = h($name);
    $value_escape = h($value);
    $size_escape = h($size);
    $onChange_escape = h($ctl['onChange_noEscape']);

    $onChangeTag = "";
    if ($ctl['type'] == 'calendar') {
        $onChangeTag = " onChange=\"gen.dateBox.dateFormat('{$name_escape}');gen.dateBox.checkDateFormat('{$name_escape}','" . _g('日付が正しくありません。') . "');{$onChange_escape}\" ";
    } else if ($ctl['type'] == 'dateFromTo') {
        $onChangeTag = " onChange=\"gen.dateBox.dateFormat('{$name_escape}_from');gen.dateBox.dateFormat('{$name_escape}_to');gen.dateBox.checkDateFromToFormat('{$name_escape}','" . _g('日付が正しくありません。') . "');{$onChange_escape}\" ";
    } else if (isset($ctl['onChange_noEscape'])) {
        $onChangeTag = " onChange=\"{$onChange_escape}\" ";
    }

    $style_escape = (isset($ctl['style']) ? h($ctl['style']) : "");

    // IME制御 （IEとFF3以上のみ）
    if (isset($ctl['ime'])) {
        if ($ctl['ime'] == "on")
            $style_escape .= ";ime-mode:active;";
        if ($ctl['ime'] == "off")
            $style_escape .= ";ime-mode:inactive;";
    } else {
        // 強制off
        if ($ctl['type'] == 'dateFromTo' || $ctl['type'] == 'dateTimeFromTo' || $ctl['type'] == 'numFromTo' || $ctl['type'] == 'calendar' || $ctl['type'] == 'dropdown') {
            $style_escape .= ";ime-mode:inactive;";
        }
    }

    switch ($ctl['type']) {
        case 'select':          // セレクタ
            // gen_edit_control や gen_db::getHtmlOptionArrayではセレクタの文字数を制限している。
            // selectタグにcssでwidthを設定してもドロップダウンした時の選択肢のサイズには適用されないので、
            // 画面右端からはみ出してレイアウトが崩れることがあるため。
            // しかし search_control の場合、文字数制限があると表示条件パターンの削除ができなくなるという問題がある。
            // 表示条件の場合は右端からはみ出すという可能性は小さいので、文字数制限しないこととした。

            // searchControlArrayでのデフォルト値の設定プロパティは「selected」ではなく「default」であることに注意
            //  ListBaseで $ctl['default'] から $ctl['selected'] に詰め替えている。
            //  edit では「selected」で指定するのでややこしいが、search の場合はあくまでデフォルト値（ユーザー指定や
            //  cookie、pinが優先される）なのでこうなっている
            $optionsParams = array('options' => $ctl['options'], 'selected' => $ctl['selected']);
            $_html_result .= "<select name='{$name_escape}' id='{$name_escape}'";
            $_html_result .= $onChangeTag;
            if (@$ctl['readonly']) {    // セレクタはreadonlyが効かないのでdisabledにしてる
                $_html_result .= " disabled ";
                $style_escape .= ";background-color:#cccccc";
            } else if (isset($ctl['require'])) {
                $style_escape .= ";background-color:#e4f0fd";
            }
            $_html_result .= " style='{$style_escape};max-width:200px'";
            $_html_result .= ">";
            $_html_result .= smarty_function_html_options($optionsParams, $smarty);
            $_html_result .= "</select>\n";
            if (@$ctl['readonly']) {    // disabledだと値がPOSTされないので、hiddenを入れる
                $_html_result .= "<input type='hidden' name='{$name_escape}' value='" . h($ctl['selected']) . "'>\n";
            }
            $_html_result .= gen_search_control_showToolIcons($ctl, $params, $name, "");
            break;

        case 'yearMonth':       // 年月セレクタ
            $optionsParams = array(
                'prefix' => $name_escape . "_",
                'display_days' => false,
                'time' => $ctl['valueYear'] . '-' . $ctl['valueMonth'] . '-01',
                'start_year' => $ctl['start_year'],
                'end_year' => $ctl['end_year'],
                'month_format' => "%m",
                'field_order' => "YM",
                'year_extra' => " id='{$name_escape}_Year'",
                'month_extra' => " id='{$name_escape}_Month'",
            );
            $_html_result .= smarty_function_html_select_date($optionsParams, $smarty);
            $_html_result .= gen_search_control_showToolIcons($ctl, $params, $name . "_Year", $name . "_Month");
            break;

        case 'dateFromTo':      // スルー
        case 'dateTimeFromTo':  // 日付範囲（テキストボックス2つ）
            $_html_result .= "<input type='text' name='{$name_escape}_from' id='{$name_escape}_from' value='" . h($ctl['valueFrom']) . "'";
            $_html_result .= $onChangeTag;
            $_html_result .= " style='{$style_escape}; width:{$size_escape}px'>\n";
            $_html_result .= "<input type='button' class='mini_button' name='{$name_escape}_from_button' id='{$name_escape}_from_button'";
            $_html_result .= " value='▼' onClick=\"gen.calendar.init('{$name_escape}_from_calendar', '{$name_escape}_from');\">";
            $_html_result .= " " . _g("から");
            $_html_result .= gen_search_control_showToolIcons($ctl, $params, "{$name_escape}_from", "");
            $_html_result .= "<div id='{$name_escape}_from_calendar'></div>\n";

            $_html_result .= "<input type='text' name='{$name_escape}_to' id='{$name_escape}_to' value='" . h($ctl['valueTo']) . "'";
            $_html_result .= $onChangeTag;
            $_html_result .= " style='{$style_escape}; width:{$size_escape}px'>\n";
            $_html_result .= "<input type='button' class='mini_button' name='{$name_escape}_to_button' id='{$name_escape}_to_button'";
            $_html_result .= " value='▼' onClick=\"gen.calendar.init('{$name_escape}_to_calendar', '{$name_escape}_to');\">";
            $_html_result .= " " . _g("まで");
            $_html_result .= gen_search_control_showToolIcons($ctl, $params, "{$name}_to", "");
            $_html_result .= "<div id='{$name_escape}_to_calendar'></div>\n";

        // 日付パターンがピンどめされていたときの処理
        if (isset($ctl['datePattern']))
            $_html_result .= "<script>gen.dateBox.setDatePattern('{$name_escape}');</script>";

            break;

        case 'numFromTo':   // 数値範囲（テキストボックス2つ）
            $_html_result .= "<input type='text' name='{$name_escape}_from' id='{$name_escape}_from' value='" . h($ctl['valueFrom']) . "'";
            $_html_result .= $onChangeTag;
            $_html_result .= " style='{$style_escape}; width:{$size_escape}px'>\n";
            $_html_result .= " " . _g("以上");
            $_html_result .= gen_search_control_showToolIcons($ctl, $params, "{$name}_from", "");

            $_html_result .= "<br>";
            $_html_result .= "<input type='text' name='{$name_escape}_to' id='{$name_escape}_to' value='" . h($ctl['valueTo']) . "'";
            $_html_result .= $onChangeTag;
            $_html_result .= " style='{$style_escape}; width:{$size_escape}px'>\n";
            $_html_result .= " " . _g("以下");
            $_html_result .= gen_search_control_showToolIcons($ctl, $params, "{$name}_to", "");
            break;

        case 'strFromTo':   // 文字列範囲
            $_html_result .= "<input type='text' name='{$name_escape}_from' id='{$name_escape}_from' value='" . h($ctl['valueFrom']) . "'";
            $_html_result .= $onChangeTag;
            $_html_result .= " style='{$style_escape}; width:{$size_escape}px'>\n";
            $_html_result .= "&nbsp;" . _g("から");
            $_html_result .= gen_search_control_showToolIcons($ctl, $params, "{$name}_from", "");

            $_html_result .= "<br>";
            $_html_result .= "<input type='text' name='{$name_escape}_to' id='{$name_escape}_to' value='" . h($ctl['valueTo']) . "'";
            $_html_result .= $onChangeTag;
            $_html_result .= " style='{$style_escape}; width:{$size_escape}px'>\n";
            $_html_result .= "&nbsp;" . _g("まで");
            $_html_result .= gen_search_control_showToolIcons($ctl, $params, "{$name}_to", "");
            $_html_result .= "<div id='{$name_escape}_to_str'></div>\n";
            break;

        case 'calendar':        // カレンダー機能付テキストボックス
            $_html_result .= "<input type='text' name='{$name_escape}' id='{$name_escape}' value='{$value_escape}'";
            $_html_result .= $onChangeTag;
            $_html_result .= " style='{$style_escape}; width:{$size_escape}px'>\n";
            $_html_result .= "<input type='button' class='mini_button' name='{$name_escape}_button' id='{$name_escape}_button'";
            $_html_result .= " value='▼' onClick=\"gen.calendar.init('{$name_escape}_calendar', '{$name_escape}');\" tabindex=-1>";
            $_html_result .= gen_search_control_showToolIcons($ctl, $params, $name, "");
            $_html_result .= "<div id='{$name_escape}_calendar'></div>\n";
            break;

        case 'dropdown':        // 拡張ドロップダウン
            $dropdownCategory_escape = h($ctl['dropdownCategory']);
            $dropdownParam_escape = str_replace("'", "[gen_quot]", h($ctl['dropdownParam']));
            $dropdownShowCondition_escape = str_replace("'", "[gen_quot]", (isset($ctl['dropdownShowCondition_noEscape']) ? $ctl['dropdownShowCondition_noEscape'] : ""));
            $dropdownShowConditionAlert_escape = str_replace("'", "[gen_quot]", (isset($ctl['dropdownShowConditionAlert']) ? h($ctl['dropdownShowConditionAlert']) : ""));

            // テキストボックス（_show）
            $nameForFunc = str_replace("#", "_", $name_escape);
            $_html_result .=
                    "<input type='text'" .
                    " name='{$name_escape}_show'" .
                    " id='{$name_escape}_show'" .
                    " value='" . h($ctl['dropdownShowtext']) . "'" .
                    " style='{$style_escape}; width:{$size_escape}px; background-color:" .
                    (@$ctl['readonly'] ?
                            "#cccccc' tabindex=-1" :
                            "#e4f0fd'") .
                    // gen.dropdown.onTextChange() は gen_dropdown.js で定義されている共通スクリプト。
                    // Ajaxによるidや表示名の取得を行う。
                    // フィールドリストで定義した onChange は第4引数として渡され、上記の処理後に実行される。
                    // ちなみに onChange をここではなくわざわざ gen.dropdown.onTextChange() に渡して実行しているのは、
                    // gen.dropdown.onTextChange() における処理が確実にすべて終了した後に実行されるようにするため。
                    " onChange=\"gen.dropdown.onTextChange(" .
                        "'{$dropdownCategory_escape}'," .
                        "'{$name_escape}_show'," .
                        "'{$name_escape}'," .
                        "'{$name_escape}_sub'," .
                        "'{$name_escape}_show_onchange(true)'," .
                        "''," .
                        "true
                    );\"" .
                    (@$ctl['readonly'] ? "readonly" : "") .
                    ">\n" .
                    // 以前はonChangeスクリプトをエレメントのonChangeの中（onTextChangeの第4引数）に直接書いていたが、
                    // ドロップダウンの選択時にonTextChangeを経由せず直接onChangeスクリプトを実行することになったため独立させた
                    // 最後の $('#gen_searchButton').click() は表示条件変更時のリスト即時更新処理。
                    // 通常のコントロールは list.tpl の JSでその処理を行っているが、拡張DDだけはここで行う必要がある。
                    // list.tplの該当箇所のコメントを参照。
                    // ちなみに以前ここでやっていたリスト再表示（gen_searchButtonのclick）は、この前に実行される
                    // gen.dropdown.onTextChange()の中で行われている。
                    "<script>function {$nameForFunc}_show_onchange(isTextChange) {{$onChange_escape};if(!isTextChange)$('#gen_searchButton').click()}</script>";

            // ドロップダウンボタン
            $_html_result .=
                    "<input type='button'" .
                    " value='▼'" .
                    " class='mini_button'" .
                    " id='{$name_escape}_dropdown'" .
                    " onClick=\"gen.dropdown.show('{$name_escape}_show'," .
                    "'{$dropdownCategory_escape}'," .
                    "'{$dropdownParam_escape}'," .
                    "'{$dropdownShowCondition_escape}'," .
                    "'{$dropdownShowConditionAlert_escape}'," .
                    "true)\"" .
                    (@$ctl['readonly'] ? " disabled" : "") .
                    " tabindex = -1 " .
                    ">";

            // サブテキスト（テキストボックスの下に表示される項目。_sub）
            $size_escape = (isset($ctl['subSize']) && is_numeric($ctl['subSize']) ? $ctl['subSize'] : $size_escape);
            if ($ctl['dropdownHasSubtext']) {
                $_html_result .=
                        "<BR><input type='text'" .
                        " name='{$name_escape}_sub'" .
                        " id='{$name_escape}_sub'" .
                        " value='" . h($ctl['dropdownSubtext']) . "'" .
                        " style='{$style_escape}; width:{$size_escape}px; background-color:#cccccc'" .
                        " readonly tabindex=-1 " .
                        ">\n";
            }

            // hidden（value）
            $_html_result .=
                    "<input type='hidden'" .
                    " name='{$name_escape}'" .
                    " id='{$name_escape}'" .
                    " value='{$value_escape}'" .
                    ">\n";

            $_html_result .= gen_search_control_showToolIcons($ctl, $params, $name . "_show", "", $name);
            break;

        case 'separater':        // 区切り線
            $_html_result .= "<hr>\n";

            break;

        case 'literal':         // リテラル  すでに見出しセルで処理しているので何もしない
            break;

        default:                // テキストボックス
            $_html_result .= "<input type='text' name='{$name_escape}' id='{$name_escape}' value='{$value_escape}'";
            $_html_result .= $onChangeTag;
            $_html_result .= " style='{$style_escape}; width:{$size_escape}px'>\n";

            //  $ctl['matchMode'] は ListBaseの「検索条件 一致モードの状態復帰」の箇所で設定される
            if (@$ctl['notShowMatchBox'] != "true") {
                $_html_result .=
                        "<select name='gen_search_match_mode_{$name_escape}' id='gen_search_match_mode_{$name_escape}'>" .
                        "<option value='0'" . ($ctl['matchMode'] == "0" ? " selected" : "") . ">" . _g("を含む") . "</option>" .
                        "<option value='1'" . ($ctl['matchMode'] == "1" ? " selected" : "") . ">" . _g("で始まる") . "</option>" .
                        "<option value='2'" . ($ctl['matchMode'] == "2" ? " selected" : "") . ">" . _g("で終わる") . "</option>" .
                        "<option value='3'" . ($ctl['matchMode'] == "3" ? " selected" : "") . ">" . _g("と一致") . "</option>" .
                        "<option value='4'" . ($ctl['matchMode'] == "4" ? " selected" : "") . ">" . _g("を含まない") . "</option>" .
                        "<option value='5'" . ($ctl['matchMode'] == "5" ? " selected" : "") . ">" . _g("で始まらない") . "</option>" .
                        "<option value='6'" . ($ctl['matchMode'] == "6" ? " selected" : "") . ">" . _g("で終わらない") . "</option>" .
                        // 現在未使用。不正なパターンを指定されたときSQLエラーになる問題の対処が難しいため
                        //"<option value='9'" . ($ctl['matchMode']=="9" ? " selected" : "") . ">" . _g("正規表現") . "</option>" .
                        "</select>";
            }
            $_html_result .= gen_search_control_showToolIcons($ctl, $params, $name, "gen_search_match_mode_" . $name);
            break;
        }

        return $_html_result;
    }

//************************************************
// ピン等の表示
//************************************************
function gen_search_control_showToolIcons($ctl, $params, $name1, $name2, $dropdownDataName = "")
{
    $res = "";

    // ピン
    if (isset($ctl['hidePin']) && $ctl['hidePin'])
        return $res;

    if ($dropdownDataName != "")
        $name1 = $dropdownDataName; // ドロップダウンのみ：hiddenをピンの対象とする
    
    $name1_escape = h($name1);
    $name2_escape = h($name2);
    $isOn = (is_array($params['pins']) && in_array($name1, $params['pins']));
    $actionWithPageMode_escape = h($params['actionWithPageMode']);
    $res .= "<img src='img/pin02.png' id='gen_pin_off_{$name1_escape}' style='vertical-align: middle; cursor:pointer;" . ($isOn ? "display:none;" : "") . "' onclick=\"gen.pin.turnOn('{$actionWithPageMode_escape}', '{$name1_escape}', '{$name2_escape}');\">";
    $res .= "<img src='img/pin01.png' id='gen_pin_on_{$name1_escape}' style='vertical-align: middle; cursor:pointer;" . ($isOn ? "" : "display:none;") . "' onclick=\"gen.pin.turnOff('{$actionWithPageMode_escape}', '{$name1_escape}', '{$name2_escape}');\">";

    return $res;
}
