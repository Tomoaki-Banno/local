<?php

function smarty_function_gen_data_list($params, &$smarty)
{
    require_once $smarty->_get_plugin_filepath('shared', 'escape_special_chars');
    require_once $smarty->_get_plugin_filepath('function', 'html_options');


    //************************************************
    // 見出し行
    //************************************************

    if ($params['isTitle'] == "true") {
        $html = titleRow($params);
        $html .= aggregateRow($params);
        return $html;
    }

    //************************************************
    // データ行
    //************************************************

    $html = "";

    // セルdiv用のCSSを先に出力しておく　(gen_xd[colNum]。列ごと)
    $colNum = ($params['isFixTable'] == "true" ? 0 : 1000);
    $html .= getColumnCss($params, $colNum);

    // 表示
    $rowNum = 0;
    $isEditable = ($params['isClickableTable'] == 'true' && $params['readonly'] != 'true'); // 編集可能かどうか
    $idField = $params['idField'];
    $rowSpanArray = array();

    // 小計行    
    $existSubSum = (isset($params['subSumCriteria']) && $params['subSumCriteria'] != "");
    if ($existSubSum) {
        $subSumCriteriaCache = $params['data'][0][$params['subSumCriteria']];
        $subSumCriteriaDateTypeStr = "";
        if (isset($params['subSumCriteriaDateType'])) {
            switch ($params['subSumCriteriaDateType']) {
                case 0: $subSumCriteriaDateTypeStr = "Y"; break;
                case 1: $subSumCriteriaDateTypeStr = "Y-m"; break;
                default: $subSumCriteriaDateTypeStr = "Y-m-d";
            }
            if (Gen_String::isDateString($subSumCriteriaCache)) {
                $subSumCriteriaCache = date($subSumCriteriaDateTypeStr, strtotime($subSumCriteriaCache));
            }
        }
        $subSumArray = array();
        foreach ($params['columnArray'] as $col) {
            if ($col['type'] == "numeric" && $col['field'] != "") {
                $subSumArray[$col['field']] = 0;
            }
        }
    }

    $domArrIndex = 0;
    $isAppendSection = false;
    $needSectionAppend = false;
    $isFirstSubSum = true;
    foreach ($params['data'] as $row) {

        // リストを30行ごとに分割する。
        // ただし rowSpanの途中では分割しない。（rowSpanが終わった時点で分割する）
        $mark = ($params['isFixTable']=="true" ? 'F' : 'D');
        if ($rowNum % 30 == 0 && $rowNum > 0) {
            $needSectionAppend = true;
        }
        $isRowSpan = false;
        foreach ($rowSpanArray as $rs) {
            if ($rs > 1) {
                $isRowSpan = true;
                break;
            }
        }
        if ($needSectionAppend && !$isRowSpan) {
            if (!$isAppendSection) {
                // ページ追加 初回
                $html .= "<script>var gen_domArr_{$mark} = new Array;" . ($mark == "D" ? "var gen_numArr = new Array;" : "");
                $isAppendSection = true;
            } else {
                $html .= "\";";
            }
            if ($mark == "D") {
                $html .= "gen_numArr[{$domArrIndex}] = {$rowNum};";
            }
            $html .= "gen_domArr_{$mark}[{$domArrIndex}] = \"";
            $domArrIndex++;
            $needSectionAppend = false;
        }

        if ($existSubSum) {
            // 「===」で比較していることに注意。  「==」や「!=」は文字を数値変換して比較するので、
            //  "00" === "000" のようなケースでもtrueになってしまう
            $subSumCriteriaValue = $row[$params['subSumCriteria']];
            if ($subSumCriteriaDateTypeStr != "") {
                if ($subSumCriteriaValue != "") {
                    $subSumCriteriaValue = date($subSumCriteriaDateTypeStr, strtotime($subSumCriteriaValue));
                }
            }
            if ($subSumCriteriaValue === $subSumCriteriaCache) {
                foreach ($subSumArray as $key => $val) {
                    if (is_numeric($row[$key]) && $rowSpanArray[$key] <= 1) {
                        // 小数点以下の数値は4桁程度までしか正確に計算できない。　
                        // 以下のようにすればもっと精度は高まるが、表示速度が低下する。ag.cgi?page=ProjectDocView&pid=1574&did=198830
                        // 　$subSumArray[$key] = Gen_Math::add($val, $row[$key]);
                        // ※ただし、上記の設定をした場合でも小数点以下の桁数が多い場合は丸められてしまうこともある。
                        //　 精度はphp.iniのbcmath.scaleの設定次第。PHP内でbcscaleにより設定することもできる。
                        $subSumArray[$key] = $val + $row[$key];
                    }
                }
            } else {
                // 小計行表示
                // 先頭ページ以外の最初の小計は、小計区間が前のページからまたがっている可能性がある。それで「一部」と表示する（第6引数）
                $rowDataAgg = aggregateRow($params, true, $subSumArray, $params['subSumCriteria'], $subSumCriteriaCache, $isFirstSubSum && $params['page'] > 1);
                $isFirstSubSum = false;
                if ($isAppendSection) {
                    $rowDataAgg = str_replace(array("\r\n","\r","\n"), "\"\n + \"", str_replace("\"", "\\\"", $rowDataAgg));
                }
                $html .= $rowDataAgg;
                $subSumCriteriaCache = $subSumCriteriaValue;
                foreach ($subSumArray as $key => $val) {
                    if (is_numeric($row[$key])) {
                        $subSumArray[$key] = $row[$key];
                    } else {
                        $subSumArray[$key] = 0;
                    }
                }
            }
        }

        // データ
        $rowData = dataRow($params, $row, $rowNum, $isEditable, $idField, $rowSpanArray, $smarty);
        if ($isAppendSection) {
            $rowData =str_replace("</script>", "</scr\" + \"ipt>",str_replace("\n", "\"\n + \"" ,str_replace("\r", "\n" ,str_replace("\r\n", "\n", str_replace("\"", "\\\"", $rowData)))));
        }
        $html .= $rowData;

        $rowNum++;
    }

    // ページの最後の小計
    if ($existSubSum) {
        // 最終ページ以外の場合、小計区間が次のページにまたがる可能性がある。それで「一部」と表示する（第6引数）
        $rowDataAgg = aggregateRow($params, true, $subSumArray, $params['subSumCriteria'], $subSumCriteriaCache, !$params['isLastPage'] || ($isFirstSubSum && $params['isLastPage'] && $params['page'] > 1));
        if ($isAppendSection) {
            $rowDataAgg = str_replace("\n", "\"\n + \"" ,str_replace("\r", "\n" ,str_replace("\r\n", "\n", str_replace("\"", "\\\"", $rowDataAgg))));
        }
        $html .= $rowDataAgg;
    }

    if ($isAppendSection) {
        // リスト遅延表示用のタイマーをセット。listtable.tpl の冒頭で前回表示時のタイマーをクリアしている
        $html .= "\";
            gen_listappend_timer{$mark} = setTimeout('gen_lazy_{$mark}(0)', 1000);
            function gen_lazy_{$mark}(index){
                $('#{$mark}1 table:first-child').append(gen_domArr_{$mark}[index]);
                " . ($mark == 'D' ? "if (gen.list.existWrapOn) gen.list.table.adjustRowHeight(gen_numArr[index]);" : "") . "
                gen_domArr_{$mark}[index] = null;
                " . // IE10/11では、大きな文字列を$.append()するとUIがロック状態になる。
                    // DOMを動かすと解除されるようなので以下の処理を入れた。ag.cgi?page=ProjectDocView&pid=1574&did=227953
                "
                if (gen.util.isIE) {
                    $('body').append('<span></span>');
                }
                index++;
                if (gen_domArr_{$mark}[index] != undefined) {
                    gen_listappend_timer{$mark} = setTimeout('gen_lazy_{$mark}('+index+')',1000);
                } else {
                    gen_domArr_{$mark} = null;
                }" . // ag.cgi?page=ProjectDocView&pid=1574&did=233228  ちなみにinit実行済エレメントは再実行されない（gen_script.js initChipHelp()）
                "gen.ui.initChipHelp();
            }
            </script>";
    }

    return $html;
}

//************************************************
// 行ごとの処理
//************************************************

// タイトル（見出し）行の処理
function titleRow($params)
{
    $html = "";
    $colNum = ($params['isFixTable'] == "true" ? 0 : 1000);
    
    // tr開始
    $html .= "<tr";
    $html .= " align=\"center\"";
    $html .= ">";

    foreach ($params['columnArray'] as $col) {
        // visibleはページ側で指定されるプロパティ（モードによる列の切り替えなど）、hideはフレームワーク側で制御されるプロパティ
        if ((isset($col['visible']) && !$col['visible']) || (isset($col['hide']) && $col['hide'])) {
            $colNum++;
            continue;
        }

        // td開始
        $html .= "<td";
        $html .= " id=\"gen_td_{$colNum}_title\"";
        $html .= " class=\"gen_cell gen_x{$colNum} gen_list_header\"";
        $html .= " style=\"width:" . h($col['width']) . "px;";
        if (isset($params['titleRowHeight'])) {
            $html .= "height:" . h($params['titleRowHeight']) . "px;";
        }
        $html .= "\"";
        $html .= ">";
        
        // 並べ替え関連
        //  SQLエラーを防ぐため、typeがliteralの列は並べ替え対象にしない。
        //  また edit, delete, delete_check も対象としないようにした。これらは本来、fieldが指定されていないので
        //  並べ替え対象にならないはずなのだが、間違って指定されている箇所もあるのでここに判断を追加した。
        $noOrderbyTypeArr = array("literal", "edit", "delete", "delete_check", "checkbox", "textbox", "select", "dropdown");    // 並べ替え対象としない列
        $isOrderbyCol = ($col['field'] != "" && !in_array($col['type'], $noOrderbyTypeArr));    // この列が並べ替え対象かどうか
        if ($isOrderbyCol) {
            $isOrderbyDesc = false;
            if (count($params['orderby']) > 0) {
                foreach ($params['orderby'] as $orderbyPart) {
                    if ($col['field'] == $orderbyPart['column']) {
                        $isOrderbyDesc = $orderbyPart['isDesc'];
                        break;
                    }
                }
            }
            
            // ここで作成される並べ替え処理用パラメータ（$orderbyListUpdateParam）は、次の2か所で使用される
            //  ・コンテキストメニューの「並べ替え」（このあとのhiddenタグに埋め込み ⇒ gen_script.js の gen.list.table.doOrderby() で処理）
            //  ・列タイトルクリックによる並べ替え（下のほうのタイトル表示部で処理）
            $orderbyParam = h($col['field']) . ($isOrderbyDesc ? "" : " desc");
            $orderbyListUpdateParam = "{gen_search_orderby:'{$orderbyParam}'";

            if (isset($col['optionParam']) && isset($col['optionValue'])) {
                $orderbyListUpdateParam .= "," . h($col['optionParam']) . ":'" . h($col['optionValue']) . "'";
            }
            $orderbyListUpdateParam .= "}";
        }
        
        // この列が現在、小計基準になっているかどうか
        $existSubSum = ($params['subSumCriteria'] != "" && $params['subSumCriteria'] === $col['field']);
        
        // この列が現在、ソート対象になっているかどうか
        $existOrderby = false;
        if (count($params['orderby']) > 0) {
            foreach ($params['orderby'] as $no => $orderbyPart) {
                $opColumn = $orderbyPart['column'];
                // テーブル名は外して比較
                if (($periodPos = strpos($opColumn, '.')) !== false) {
                    $opColumn = substr($opColumn, $periodPos + 1);
                }
                if ($col['field'] == $opColumn) {
                    $existOrderby = true;
                    break;
                }
            }
        }

        // 列の種類と情報を示す隠しタグ
        $html .= "<input type=\"hidden\"";
        // このidを変更するときは、 gen_script.js の regContextMenu 内も変更すること
        $html .= " id=\"gen_hidden_{$colNum}_info\"";
        $alignNum = ($col['align'] == "center" ? "1" : ($col['align'] == "right" ? "2" : "0"));
        $denyMoveNum = (isset($col['denyMove']) && $col['denyMove'] ? '1' : '0');
        $entryField = (isset($col['entryField']) ? $col['entryField'] : $col['field']);
        $editType = @$col['editType'];
        $options = "";
        if ($editType == 'select' && isset($col['editOptions'])) {
            foreach ($col['editOptions'] as $optNum => $optLabel) {
                if ($options != "") $options .= "||";
                $options .= $optNum . "__" . $optLabel;
            }
        } 
        if ($editType == 'dropdown' && isset($col['dropdownCategory'])) {
            $options = @$col['dropdownCategory'] 
                . "__" . @$col['dropdownParam']
                . "__" . @$col['dropdownShowCondition_noEscape']
                . "__" . @$col['dropdownShowConditionAlert'];
        } 
        $wrapOnNum = (isset($col['wrapOn']) && $col['wrapOn'] ? '1' : '0');
        // このデータの並び順を変更するときは、gen_script.js の getHtmlInfoNum 内も変更すること
        $html .= " value=\"" . h($col['type']) . "," . h($col['keta']) . "," . h($col['kanma']) . ",{$alignNum}," . h($col['bgcolor']) . 
                ",{$denyMoveNum}," . h($entryField) . "," . h($editType) . "," . h($options) . "," . h($col['filter']) . ",{$wrapOnNum}" .
                "," . ($isOrderbyCol ? $orderbyListUpdateParam : "") . "," . ($existOrderby ? "1" : "") . "," . ($existSubSum ? "1" : "") .
                "\"";
        $html .= ">";

        // 見出しセル（インナーテーブル）
        // 見出しセルは、見出しセルと列幅変更用の隙間セルの2つを収容する必要があるため、入れ子テーブルにしている
        $html .= "<table";
        $html .= " border=\"0\"";
        $html .= " cellspacing=\"0\"";
        $html .= " cellpadding=\"0\"";
        $html .= " style=\"width:100%; height:100%;\"";
        $html .= ">";

        $html .= "<tr>";

        // 見出しセル（内側）開始
        $html .= "<td";
        $html .= " id=\"gen_td_{$colNum}_innerTitle\"";
        $html .= " align=\"center\"";
        $html .= " style=\"cursor:move;\"";
        $html .= ">";

        // div開始
        // IE以外のブラウザではtdに対するoverflow指定が効かないので、すべてのセルのコンテンツをdiv囲みするようにした。
        // overflow:hidden を実現するためのdiv。
        // heightを指定してはいけない。height省略によりdiv高さをセル高いっぱいとし、親tdのcssにvertical-align:middleを指定することでクロスブラウザな垂直中央を実現している。
        // class名は、gen_colwidth.js 内での操作用(IE以外)。
        $html .= "<div";
        $html .= " id=\"gen_div_{$colNum}_innerTitle\"";
        $html .= " class=\"gen_xd{$colNum}_title\"";
        $html .= " style=\"width:" . ((int) ($col['width']) - 7) . "px;"; // 「7」はスキマ列の幅3 + マージン2×両側
        $html .= " overflow:hidden;";
        $html .= " text-align:center;";
        $html .= " margin-left:2px;";
        $html .= " margin-right:2px;";
        $html .= " white-space:nowrap;";
        $html .= "\"";
        $html .= ">";

        // 見出し表示
        $isDesc = true;
        $label = (isset($col['label']) ? h($col['label']) : '') . (isset($col['label_noEscape']) ? $col['label_noEscape'] : '');
        $label = str_replace("[br]", "<br>", $label);
        if ($col['type'] == 'delete_check') {
            // 一括削除列
            $html .= "<input";
            $html .= " type='button'";
            $html .= " class='gen_list_mini_button'";
            $html .= " value='". _g("削除") . "'";
            if (isset($col['deleteClick'])) {
                $html .= " onclick=\"" . h($col['deleteClick']) . "\"";
            } else {
                $html .= " onclick=\"gen.list.table.bulkDelete(
                    '" . h(@$col['deleteAction']) . "'" 
                    . ", '" . _g("削除するデータを選択してください。") . "'"
                    . ", '" . _g("チェックボックスがオンになっているレコードを削除します。この操作は取り消すことができません。実行してもよろしいですか？") . "'"
                    . ", '" . _g("印刷済レコードが選択されています。削除を進めてよろしいですか？") . "'"
                    . ", '" . h(@$col['beforeAction']) . "'"
                    . ", '" . (@$col['beforeDetail'] == "true" ? "true" : "false") . "'"
                    . ")\"";
            }
            $html .= ">";

        } else {
            // 一般列
            if ($isOrderbyCol) {
                // 並べ替え対象となる列
                $html .= "<a class='gen_list_header'";
                $html .= " href=\"javascript:listUpdate({$orderbyListUpdateParam}, false)\"";
                $html .= ">";

                $html .= $label;

                $html .= "</a>";
            } else {
                // 並べ替え対象とならない列
                $html .= $label;
            }
        }

        // チェックボックス切り替えボタン
        if ($col['type'] == 'delete_check' || $col['type'] == 'checkbox') {
            $cbName = isset($col['name']) ? $col['name'] : (isset($col['field']) ? $col['field'] : "check");
            $html .= "<br>";
            $html .= "<img";
            $html .= " class=\"imgContainer sprite-ui-check-box\" src=\"img/space.gif\"";
            $html .= " onclick=\"" . ($col['type'] == 'delete_check' ? "gen.list.table.alterDeleteCheck()" : "gen.list.table.alterCheckbox('" . h($cbName) . "')") . "\"";
            $html .= " style=\"cursor:default;\"";
            $html .= ">";
            if ($col['type'] == 'delete_check') {
                $html .= "<input type=\"hidden\" id=\"gen_hidden_delete_check\" name=\"gen_hidden_delete_check\" value=\"0\">";
            } else {
                $html .= "<input type=\"hidden\" id=\"gen_hidden_" . h($cbName) . "_checkbox\" name=\"gen_hidden_" . h($cbName) . "_checkbox\" value=\"0\">";
            }
        }

        // チップヘルプアイコン （日本語モードのみ）
        if (@$col['helpText_noEscape'] != "" && $_SESSION["gen_language"]=="ja_JP") {
            $html .= "<br>";
            $html .= "<p class='helptext_{$colNum}' style='display:none;'>{$col['helpText_noEscape']}</p><a class='gen_chiphelp' href='#' rel='p.helptext_{$colNum}' title='{$label}' style='color:black;text-decoration:none;'><img src='img/question_white.png' style='border:none'></a>";
        }

        // フィルタアイコン
        if (@$col['filter'] != "") {
            if (@$col['helpText_noEscape'] == "") $html .= "<br>";
            $html .= "<a href='javascript:gen.list.table.showFilterDialog({$colNum}, \"gen_td_{$colNum}_innerTitle\")' title='" . h($col['filterText']) . "' style='color:black;text-decoration:none;'><img src='img/flag.png' style='border:none'></a>";
        } 

        // 小計基準アイコン
        if ($existSubSum) {
            if (@$col['helpText_noEscape'] == "" && @$col['filter'] == "") $html .= "<br>";
            $html .= "<a href='javascript:listUpdate({gen_subSumCriteriaColNum : {$colNum}, gen_subSumCriteriaClear : true},false,false)' title='" . _g("小計基準（クリックで解除）") . "' class='gen_list_header'>Σ</a>";
        } 

        // ソートアイコン
        if ($existOrderby) {
            if (@$col['helpText_noEscape'] == "" && @$col['filter'] == "" && !$existSubSum)
                $html .= "<br>";
            $html .= "<span class=\"gen_list_header_sortmark" . ($orderbyPart['isDefault'] ? "" : "_selected") . "\">";
            $html .= ($no+1);
            $html .= ($orderbyPart['isDesc'] ? "▼" : "▲");
            $html .= "</span>&nbsp;";
            $html .= ($orderbyPart['isDefault'] ? "" : "<a href=\"javascript:listUpdate({gen_orderby_delete:'" . h($col['field']) . "'}, false)\" class=\"gen_list_header_sortmark_selected\" style=\"font-size:" . ($params['isiPad'] ? "15" : "12") . "px;\">×</a>");
        }

        // 内側セル終了
        $html .= "</div>";
        $html .= "</td>\n";

        // 列幅変更用スキマセル（ここでマウスダウンすると列幅変更モードに入る）
        $html .= "<td";
        $html .= " id=\"gen_td_{$colNum}_handle\"";  // for gen_intro.js
        $html .= " class=\"gen_list_handle\"";  // for gen_intro.js
        $html .= " ondblclick=\"gen.colwidth.autofit({$colNum})\"";
        $html .= " onmousedown=\"gen.colwidth.onStartDrag({$colNum})\"";
        $html .= " style=\"width:3px; cursor: e-resize; white-space:nowrap;\"";
        $html .= ">";
        // つっかい棒。Firefoxではwidth指定が無視されて縮小されることがある（CSSで指定しても）ために挿入
        $html .= "<div style=\"width:3px;height:0px;\"></div>";
        $html .= "</td>\n";

        // セル終了
        $html .= "</tr>\n";
        $html .= "</table>";
        $html .= "</td>\n";

        $colNum++;
    }
    $html .= "</tr>\n";

    return $html;
}

// 集計行の処理
function aggregateRow($params, $isSubSum = false, $subSumArray = null, $subSumCriteriaField = null, $subSumCriteriaValue = null, $isSubSumNotAll = false)
{
    if (!$isSubSum && (!isset($params['data']) || count($params['data']) == 0))
        return "";

    $html = "";

    // tr開始
    $html .= "<tr";
    $html .= " bgcolor=\"#d5ebff\"";
    $html .= " align=\"center\"";
    $html .= " style=\"height:" . h($params['aggregateRowHeight']) . "px;\"";
    $html .= ">";

    $colNum = ($params['isFixTable'] == "true" ? 0 : 1000);
    foreach ($params['columnArray'] as $col) {
        if ((isset($col['visible']) && !$col['visible']) || (isset($col['hide']) && $col['hide'])) {
            $colNum++;
            continue;
        }

        // td開始
        $html .= "<td";
        $html .= " id=\"gen_td_{$colNum}_agg\"";
        $html .= " class=\"gen_cell gen_x{$colNum} gen_list_agg\"";
        $html .= " valign=\"middle\"";
        $html .= ">";

        if ($isSubSum) {
            $value = $subSumArray[$col['field']];
            if ($col['field'] == $subSumCriteriaField) {
                $value = $subSumCriteriaValue;
            }
        } else {
            $value = $params['data'][0]["gen_aggregate_{$col['field']}"];
        }

        // div開始
        // 上のタイトル行表示部のdivのコメント参照
        $html .= "<div";
        if ($col['field'] != '' && $col['type'] != 'label')
            $html .= " id=\"gen_aggregate_" . h($col['field']) . "\""; // 集計値をJSで直接触ることがあるので（例：Stock_StockInput_List）
        $html .= " class=\"gen_xd{$colNum}_title\"";
        $html .= " style=\"width:" . ((int) ($col['width']) - 7) . "px;"; // 「7」はスキマ列の幅3 + マージン2×両側
        $html .= " overflow:hidden;";
        $html .= " text-align:" . (Gen_String::isNumeric($value) ? "right" : "middle") . ";";
        $html .= " margin-left:2px;";
        $html .= " margin-right:2px;";
        $html .= " white-space:nowrap;";
        $html .= "\"";
        $html .= ">";

        if ($isSubSum) {
            if ($col['field'] == $subSumCriteriaField) {
                $html .= "<span style='color:green'>";
                if ($isSubSumNotAll) {
                    // 最終ページ以外の最終行、また最初のページ以外の先頭行は、小計区間が前後ページにまたがっている可能性がある
                    $html .= "<span style='color:red;font-weight:bold'>(" . _g("一部") .")</span> ";
                }
            } else {
                $html .= "<b>"; 
            }
        }    

        // 集計行表示
        //  ちなみに type='label' は、集計データが表示されない（Gen_Pagerの集計処理部参照）
        if ($col['type'] == "numeric") {
            $html .= numericOperation($col, $value);
        } else {
            $html .= h($value);
        }

        if ($isSubSum) {
            if ($col['field'] == $subSumCriteriaField) {
                $html .= "</span>";
            } else {
                $html .= "</b>"; 
            }
        }    

        // セル終了
        $html .= "</div>\n";
        $html .= "</td>\n";

        $colNum++;
    }

    $html .= "</tr>\n";

    return $html;
}

// データ行1行分の処理
function dataRow ($params, $row, $rowNum, $isEditable, $idField, &$rowSpanArray, &$smarty)
{
    //************************************************
    // 行（TR）
    //************************************************

    // ********** 行全体で使用する変数の準備 ***********

    $id = $row[$idField];
    $row['gen_line_id'] = $rowNum;  // colorCondition等で [gen_line_id] が行数に置換されるように
    $colNum = ($params['isFixTable'] == "true" ? 0 : 1000);

    // 編集・コピーリンク
    //  editAction は HTMLエスケープするが、「&」だけはエスケープしない
    $editAction = str_replace("[and]", "&", h(str_replace("&", "[and]", $params['editAction'])));
    $editUrl = "index.php?action={$editAction}&" . h($idField) . "=" . h($id);
    if ($params['isDetailEdit'] == 'true') {
        $editScript = "location.href='{$editUrl}'";
        $copyScript = "location.href='{$editUrl}&gen_record_copy=true'";
    } else {
        $editScript = "gen.modal.open('{$editUrl}')";
        $copyScript = "gen.modal.open('{$editUrl}&gen_record_copy=true')";
    }

    // ********** trタグ用のパラメータ ***********

    // 行の色
    $rowColor = getRowBgColor($params, $row, $rowNum % 2 != 0); // 最後の引数は、1行おきに着色するためのもの

    // 行の高さ
    $rowHeight = getRowHeight($params);

    // 行id
    $fixRowId = "gen_tr_fixtable_{$rowNum}";
    $scrRowId = "gen_tr_scrtable_{$rowNum}";
    $rowId = $params['isFixTable'] == "true" ? $fixRowId : $scrRowId;

    if ($isEditable) {

        // マウスオーバー用スクリプト
        $mouseover = "if(gen_isLCE && !gen_isDE){";
        $mouseover .= $params['existFixTable'] ? "$('#" . h($fixRowId) . "').css('background-color', '#8dc4fc');" : "";
        $mouseover .= $params['existScrollTable'] ? "$('#" . h($scrRowId) . "').css('background-color', '#8dc4fc');" : "";
        $mouseover .= "}";

        $mouseout = "if(gen_isLCE && !gen_isDE){";
        $mouseout .= $params['existFixTable'] ? "var e=$('#" . h($fixRowId) . "');e.css('background-color',e.attr('srccolor'));" : "";
        $mouseout .= $params['existScrollTable'] ? "var e=$('#" . h($scrRowId) . "');e.css('background-color',e.attr('srccolor'));" : "";
        $mouseout .= "}";
    }

    // ********** trタグ ***********
    $html .= "<tr";
    $html .= " bgcolor=\"" . h($rowColor) . "\"";
    $html .= " srccolor=\"" . h($rowColor) . "\""; // 削除チェックオンで書き換わる
    $html .= " height=\"" . h($rowHeight) . "\"";
    $html .= " id=\"" . h($rowId) . "\"";
    $html .= " class=\"gen_listTR\"";    // 「リスト行のクリックで明細画面を開く」を切り替えたときにマウスカーソル形状を変更するためのclass
    if ($isEditable && !$params['isiPad']) {
        $html .= " onmouseover=\"{$mouseover}\"";
        $html .= " onmouseout=\"{$mouseout}\"";
        if ((!isset($_SESSION['gen_setting_user']->listClickEnable) || $_SESSION['gen_setting_user']->listClickEnable === 'true')
            && (!isset($_SESSION['gen_setting_user']->directEdit) || $_SESSION['gen_setting_user']->directEdit === 'false'))
            $html .= " style=\"cursor: pointer;\"";
    }
    $html .= ">";

    if ($isEditable) {
        $html .= "<input type='hidden' id='gen_row_id_{$rowNum}' value='" . h($id) . "'>";
    }


    //************************************************
    // セル（TD）
    //************************************************

    foreach ($params['columnArray'] as $col) {
        if ((isset($col['visible']) && !$col['visible']) || (isset($col['hide']) && $col['hide'])) {
            $colNum++;
            continue;
        }
        // Rowspan
        if (rowSpanOperation($rowSpanArray, $params, $col, $row, $rowNum)) {
            $colNum++;
            continue;
        }

        // ********** tdタグ用のパラメータ ***********

        // 列id　（列幅変更・列ドラッグ用。最初の行だけに指定）
        $colId = "gen_td_{$colNum}_data";

        // セルid （tdではなく内側divにつける）
        if (isset($col['cellId']) && $col['cellId'] != "") {
            // ページでセルidが指定されていた場合。[id]はその行のid, [...]はフィールドの値に置き換えられる
            $cellId0 = str_replace('[id]', $id, $col['cellId']);
            $cellId = h(bracketToFieldValue($cellId0, $row));
        } else {
            $cellId = $rowNum . "_" . $colNum;
        }

        // セル色
        $cellColor = getCellColor($col, $row);

        // セル幅
        $cellWidth = h($col['width']);

        // onClick
        $onClick = "";
        if ($col['onClick'] != "") {
            // ページでonClickが指定されていた場合
            $onClick = h(getOnClickTag($row, isset($col['onClickCondition']) ? $col['onClickCondition'] : null , $col['onClick'], $idField));
        } else if ($isEditable
            && !($col['link'] != "") && $col['type'] != 'edit' && $col['type'] != 'copy' && $col['type'] != 'delete'
            && $col['type'] != 'checkbox' && $col['type'] != 'delete_check' && $col['type'] != 'textbox'
            && $col['type'] != 'select' && $col['type'] != 'dropdown') {
            // クリックリンク（行クリックで編集画面へジャンプ）。
            // 編集可能な表で、リンク・明細・コピー・削除・チェックボックス列以外の場合、クリックしたらedit画面に飛ぶようにする。
            // onClickはTRではなくTDごとに設定。チェックボックス列など、onClick対象にしたくない列があるため。
            // 「func_$rowNum()」は、trのところで出力しているスクリプト。
                $onClick = "rc('" . h($cellId) . "',{$rowNum},'" . h($id) . "');";
        }

        // *********** tdタグ **********
        $tdStyle = "";
        $html .= "<td";
        $html .= " class=\"gen_cell gen_x{$colNum}\"";
        if (isset($col['valign']) && $col['valign'] == "top") {
            $tdStyle = "vertical-align:top";
        } else {
            $html .= " valign=\"middle\"";
        }
        if ($cellColor != "") {
            $html .= " bgColor=\"" . h($cellColor) . "\"";
        }
        if ($rowNum == 0) {
            $html .= " id=\"" . h($colId) . "\"";    // 列幅変更・列ドラッグ用。最初の行だけに指定。2行目以降にidがついているとドラッグ列幅変更で不具合あり
            $tdStyle .= ";width:" . h($cellWidth) . "px";
        }
        if ($onClick != "") {
            $html .= " onclick=\"{$onClick}\""; // onClick は escapeずみ
        }
        if ($rowSpanArray[$col['field']] > 1) {
            $html .= " rowspan=\"" . h($rowSpanArray[$col['field']]) . "\"";
        }
        if ($tdStyle != "") {
            $html .= " style=\"{$tdStyle}\"";
        }
        $html .= ">";

        // *********** divタグ **********

        //  IE以外のブラウザではtdに対するoverflow指定が効かないので、すべてのセルのコンテンツをdiv囲みする。
        //  divのサイズ等は、上のほうで出力しているcssで制御している（HTMLサイズを小さくするため、ここでは指定しない）
        $html .= "<div";
        $html .= " id=\"" . h($cellId) . "\"";
        $html .= " class=\"gen_xd{$colNum}\"";
        $html .= ">";

        // *********** コンテンツ **********

        if (isShowCell($col, $row)) {
            // 本体（セルコンテンツ）
            $contents = (getCellContents($col, $row, $rowNum, $params, $row[$col['field']],
                    $editScript, $copyScript, $idField, $id, 
                    isset($row[$col['divField']]) ? $row[$col['divField']] : null,
                    isset($row['dropdownShowtext']) ? $row['dropdownShowtext'] : null,
                    isset($row['dropdownSubtext']) ? $row['dropdownSubtext'] : null,
                    isset($row['dropdownHasSubtext']) ? $row['dropdownHasSubtext']: null,
                    $rowColor, $smarty));

            // リンク
            $tag = getCellLink($col, $row);
            $endTag = (substr($tag, 0, 2) == "<a" ? "</a>" : (substr($tag, 0, 2) == "<f" ? "</font>" : ""));
            $contents = $tag . $contents . $endTag;

            // ツールチップ
            if (isset($col['tooltip_noEscape'])) {
                $contents = getCellTooltip($cellId, $col, $row, $contents);
            }

            // divに追加
            $html .= $contents;     // $contents は getCellContents()内で escape ずみ
        }

        // *********** 閉じタグ **********

        // div end
        $html .= "</div>";

        // td end
        $html .= "</td>\n";
        $colNum++;
    }

    // tr end
    $html .= "</tr>\n";
    
    return $html;
}



//************************************************
// 個別機能関数群
//************************************************

// 列CSS
function getColumnCss($params, $colNum)
{
    $html = "";

    $html .= "<style type=\"text/css\">";

    foreach ($params['columnArray'] as $col) {
        if ((isset($col['visible']) && !$col['visible']) || (isset($col['hide']) && $col['hide'])) {
            $colNum++;
            continue;
        }

        $divWidth = (int) ($col['width']) - 4;    // -4はdivの両側マージン
        $whiteSpace = "";
        if (@$col['wrapOn'] != "true") {
            $whiteSpace = "white-space:nowrap";
        } else {
            $whiteSpace = "word-break:break-all";
        }
        $align = h($col['align']);
        $html .= " .gen_xd{$colNum} {width: {$divWidth}px; overflow:hidden; margin-left:2px; margin-right:2px; text-align:{$align};{$whiteSpace}}\n";

        $colNum++;
    }
    return $html . "</style>\n";
}

// 行（tr）：背景色
function getRowBgColor($params, $row, $alterColor)
{
    $rowColor = "#ffffff";    // 白
    if (isset($params['hilightId']) && $row[$params['idField']] == @$params['hilightId']) {
        // ハイライト処理（修正直後の行には色を付ける）
        $rowColor = "#ffffcc";
    } else if (isset($params['rowColorCondition'])) {
        // 色付け条件
        // 指定された条件が成立したときに、行に色がつく。
        // 配列のnameにカラーコード、valueに条件式（PHP構文）を指定する。
        // 条件式内の[]で囲まれた部分は、DBフィールドの値に置き換えられて評価される。
        // 色と条件の組合せはいくつでも指定できる。先に書かれている条件ほど優先度が高い
        //   例： 'rowColorCondition' => array(
        //          "#cccccc" => "[classification]=='受注'",
        //          "#999999" => "[classification]=='計画'"),
        $colorCode = evalConditionArray($params['rowColorCondition'], $row);
        if ($colorCode) {
            $rowColor = $colorCode;
        }
    }
    if ($alterColor && $rowColor == "#ffffff") {
        // 1行おきに背景色（GEN_LIST_ALTER_COLOR は フロントコントローラで指定）
        if (GEN_LIST_ALTER_COLOR != "" && $params['alterColorDisable'] != "true") {
            $rowColor = GEN_LIST_ALTER_COLOR;
        }
    }
    return $rowColor;
}

// 行（tr）：高さ
function getRowHeight($params)
{
    //  dataRowHeightに0以下を指定した場合は、行の高さを固定しない。
    //  ただしその場合は固定列（fixColumn）が使えない。固定列とスクロール列の高さがそろわないので。
    if (isset($params['dataRowHeight']) && $params['dataRowHeight'] > "0") {
        // 行の高さが指定されている場合
        return h($params['dataRowHeight']);
    }
    return "";
}

// rowSpanの処理
// rowSpanArrayの書き換え。rowSpan中であればtrueを返す。
function rowSpanOperation(&$rowSpanArray, $params, $col, $row, $y)
{
    if (@$col['sameCellJoin']) {
        if (!isset($col['field'])) {
            throw new Exception('sameCellJoinパラメータを指定するカラムには、必ず fieldパラメータの指定が必要です。'.
                    'fieldパラメータは fixColumnArray, columnArray の中でユニークでなければなりません。');
        }

        // 現在rowspan中であれば、セル表示せずに次の列へ進む
        if ($rowSpanArray[$col['field']] > 1) {
            $rowSpanArray[$col['field']]--;
            return true;
        }
        // 下方向にセルの内容を調べ、現セルと同内容であればcolSpanカウントする
        $currentCellValue = $row[$col['field']];
        $existSubSum = (isset($params['subSumCriteria']) && $params['subSumCriteria'] != "");
        if ($existSubSum) $currentSubSumCriteriaValue = $row[$params['subSumCriteria']];

        for ($currentY = $y + 1; $currentY < count($params['data']); $currentY++) {
            if ($existSubSum) {
                // 「!==」で比較していることに注意。  「==」や「!=」は文字を数値変換して比較するので、
                //  "00" === "000" のようなケースでもtrueになってしまう
                if ($params['data'][$currentY][$params['subSumCriteria']] !== $currentSubSumCriteriaValue)
                    break;
            }
            
            // 「!==」で比較していることに注意。  「==」や「!=」は文字を数値変換して比較するので、
            //  "00" === "000" のようなケースでもtrueになってしまう
            if ($params['data'][$currentY][$col['field']] !== $currentCellValue)  break;
            // 親カラムが指定されている場合、親カラムがrowspan中でなければこの列もrowspanを終了する
            if (@$col['parentColumn'] != "") {
                if ($params['data'][$currentY - 1][$col['parentColumn']] !== @$params['data'][$currentY][$col['parentColumn']])
                    break;
            }
            if (is_numeric($rowSpanArray[$col['field']])) {
                $rowSpanArray[$col['field']]++;
            } else {
                $rowSpanArray[$col['field']]=2;
            }
        }
    }
    return false;
}

// 列表示の判断
function isShowCell($col, $row)
{
    //  showCondition項目が指定されている場合、条件が成立する場合のみ列が表示される。
    //  条件式の中で[...]と書いた部分はフィールドの値に置き換えられて評価される。
    //  たとえば　'showCondition' => "[class] == '受注'" であれば、classフィールドが
    //  「受注」のときのみ表示される
    if (isset($col['showCondition']) && $col['showCondition'] != "") {
        return evalCondition($col['showCondition'], $row);
    }
    return true;
}

// セル（td/div）:色
function getCellColor($col, $row)
{
    // 一括削除列
    if ($col['type'] == 'delete_check') {
        return "#fdd9db";
    }

    // セル色の決定
    // 指定された条件が成立したときに、セルに色がつく。
    // 配列のnameにカラーコード、valueに条件式（PHP構文）を指定する。
    // 条件式内の[]で囲まれた部分は、DBフィールドの値に置き換えられて評価される。
    // 色と条件の組合せはいくつでも指定できる。先に書かれている条件ほど優先度が高い
    // 例： 'colorCondition' => array(
    //          "#cccccc" => "[classification]=='受注'",
    //          "#999999" => "[classification]=='計画'"),
    if (isset($col['colorCondition'])) {
        $colorCode = evalConditionArray($col['colorCondition'], $row);
        if ($colorCode) {
            return $colorCode;
        }
    }
    return "";
}

// セル（td/div）:リンク処理
function getCellLink($col, $row)
{
    if (isset($col['link']) && $col['link'] != "") {
        //  linkCondition処理
        //  linkCondition項目が指定されている場合、条件が成立する場合のみリンクが有効になる。
        //  条件式の中で[...]と書いた部分はフィールドの値に置き換えられて評価される。
        //  たとえば　'showCondition' => "[class] == '受注'" であれば、classフィールドが
        //  「受注」のときのみリンク有効になる
        $linkEnabled = true;
        if (isset($col['linkCondition']) && @$col['linkCondition'] != "") {
            $linkEnabled = evalCondition($col['linkCondition'], $row);
        }

        if ($linkEnabled) {
            // リンク有効のとき
            $url0 = h($col['link']);
            // 条件式の[...]をフィールドの値に置き換える
            $url = bracketToFieldValue($url0, $row);

            return "<a href=\"{$url}\">";
        } else {
            // ディセーブル状態のとき
            if (isset($col['linkDisableColor']) && $col['linkDisableColor'] != "") {
                return "<font color='{$col['linkDisableColor']}'>";
            } else {
                return "<font color='#cccccc'>";
            }
        }
    }
    // リンクなし
    return "";
}

// セル（td/div）:ツールチップ処理
function getCellTooltip($cellId, $col, $row, $contents)
{
    // 条件式の[...]をフィールドの値に置き換える
    $tooltip = bracketToFieldValue($col['tooltip_noEscape'], $row);
    if ($tooltip != "") {
        $label = (isset($col['label']) ? h($col['label']) : '') . (isset($col['label_noEscape']) ? $col['label_noEscape'] : '');
        // $contents は getCellContents()内で escape ずみ
        return "<p class='helptext_$cellId' style='display:none;'>{$tooltip}</p><a class='gen_chiphelp' href='#' rel='p.helptext_" . h($cellId) . "' title='{$label}' style='color:black;text-decoration:none;'>{$contents}</a>";
    }
    return $contents;
}

// セル（td/div）:コンテンツ（メイン部分）
function getCellContents($col, $row, $rowNum, $params, $value, $editScript_escape, $copyScript_escape, $idField, $id, $divValue,
            $dropdownShowtext, $dropdownSubtext, $dropdownHasSubtext, $rowColor, &$smarty)
{
    $res = "";
    $id_escape = h($id);
    $value_escape = h($value);

    switch ($col['type']) {
        case 'edit':
            // 明細
            $res .= "<a href=\"javascript:{$editScript_escape}\"><img class=\"imgContainer sprite-pencil\" src=\"img/space.gif\" border=\"0\"/></a>";
            break;
        case 'copy':
            // コピー
            if ($params['readonly'] == 'true') {
                // アクセス権がreadonlyのとき。グレーアウト状態
                $res .= "<font color=\"#cccccc\"><img class=\"imgContainer sprite-document-copy\" src=\"img/space.gif\" border=\"0\" /></font>";
            } else {
                // 通常
                $res .= "<a href=\"javascript:{$copyScript_escape}\"><img class=\"imgContainer sprite-document-copy\" src=\"img/space.gif\" border=\"0\"/></a>";
            }
            break;
        case 'delete':
            // 削除
            if ($params['readonly'] == 'true') {
                // アクセス権がreadonlyのとき。グレーアウト状態
                $res .= "<font color=\"#cccccc\">" . _g("削除") . "</font>";
            } else {
                // 通常
                $res .= "<a href=\"javascript:gen.list.table.deleteItem('index.php?action=" .
                    h($params['deleteAction']) . "&" . h($idField) . "={$id_escape}', '" . _g("削除してもよろしいですか？") . "')\">" . _g("削除") . "</a>";
            }
            break;
        case 'delete_check':
            // 一括削除
            if ($params['readonly'] == 'true') {
                // アクセス権がreadonlyのとき。グレーアウト状態
                $res .= "<input type=\"checkbox\" disabled> ";
            } else {
                // 通常
                $fixRowId = "gen_tr_fixtable_{$rowNum}";
                $scrRowId = "gen_tr_scrtable_{$rowNum}";
                $delRowColor = "#FDD9DB";
                $onclick = "if(this.checked){";
                $onclick .= $params['existFixTable'] ? "var e=$('#" . h($fixRowId) . "');e.attr('srccolor2',e.attr('srccolor'));e.attr('srccolor','{$delRowColor}');e.css('background-color','{$delRowColor}');" : "";
                $onclick .= $params['existScrollTable'] ? "var e=$('#" . h($scrRowId) . "');e.attr('srccolor2',e.attr('srccolor'));e.attr('srccolor','{$delRowColor}');e.css('background-color','{$delRowColor}');" : "";
                $onclick .= "}else{";
                $onclick .= $params['existFixTable'] ? "var e=$('#" . h($fixRowId) . "');var s=e.attr('srccolor2');e.attr('srccolor',s);e.css('background-color',s);" : "";
                $onclick .= $params['existScrollTable'] ? "var e=$('#" . h($scrRowId) . "');var s=e.attr('srccolor2');e.attr('srccolor',s);e.css('background-color',s);" : "";
                $onclick .= "}";
                $res .= "<input type=\"checkbox\" name=\"delete_{$id_escape}\" id=\"delete_{$id_escape}\" onclick=\"{$onclick}\"> ";
            }
            break;
        case 'checkbox':
            // チェックボックス
            $chkId = (isset($col['name']) ? $col['name'] : (isset($col['field']) ? $col['field'] : "check")) . "_{$id}";
            // onValueが設定されていないときは、行idをvalueとする。一括登録の選択チェック用の仕様
            $onValue = (isset($col['onValue']) ? $col['onValue'] : $id);
            $style = (isset($col['style']) ? $col['style'] : "");
            $res .= "<input type=\"checkbox\" name=\"" . h($chkId) . "\" id=\"" . h($chkId) . "\" value=\"" . h($onValue) . "\" style=\"" . h($style) . "\" tabIndex=\"-1\"";
            if (isset($col['onChange_noEscape']))
                $res .= " onchange=\"" . str_replace('[id]', $id_escape, $col['onChange_noEscape']) . "\"";
            if ($value === true || $value == 'true')
                $res .= " checked";
            $res .= ">";
            break;
        case 'textbox':
            // テキストボックス
            $elmId_escape = (isset($col['field']) ? h($col['field']) : "text") . "_{$id_escape}";
            $style = (isset($col['style']) ? $col['style'] : "");
            if (isset($col['ime']) && $col['ime']=="on")
                $style .= ";ime-mode:active;";
            if (isset($col['ime']) && $col['ime']=="off")
                $style .= ";ime-mode:inactive;";
            $res .= "<input type=\"text\" name=\"{$elmId_escape}\" id=\"{$elmId_escape}\" value=\"{$value_escape}\" style=\"width:90%;height:15px;" . h($style) . "\"";
            if (isset($col['onChange_noEscape']))
                $res .= " onchange=\"" . str_replace('[id]', $id_escape, $col['onChange_noEscape']) . "\"";
            if (isset($col['tabindex']))
                $res .= " tabindex=\"" . h($col['tabindex']) . "\"";
            $res .= ">";
            break;
        case 'dropdown':
            // 基本情報
            $elmId_escape = (isset($col['field']) ? h($col['field']) : "dropdown") . "_{$id_escape}";
            $onChange_escape = (isset($col['onChange_noEscape']) ? str_replace('[id]', $elmId_escape, $col['onChange_noEscape']) : ""); // 設定側でエスケープされているはず

            // 拡張ドロップダウン
            $dropdownCategory_escape = h($col['dropdownCategory']);
            $dropdownParam_escape = str_replace("'", "[gen_quot]", str_replace('[id]', $id_escape, h($col['dropdownParam'])));
            $dropdownShowCondition_escape = str_replace("'", "[gen_quot]", (isset($col['dropdownShowCondition_noEscape']) ? $col['dropdownShowCondition_noEscape'] : ""));
            $dropdownShowConditionAlert_escape = str_replace("'", "[gen_quot]", (isset($col['dropdownShowConditionAlert']) ? h($col['dropdownShowConditionAlert']) : ""));

            // スクリプト
            $res .=
                "<script>" .
                // テキストボックス変更イベント。Ajaxによるidや表示名の取得を行う。
                // フィールドリストで定義した onChange は第4引数として渡され、上記の処理後に実行される。
                // ちなみに onChange をここではなくわざわざ gen.dropdown.onTextChange() に渡して実行しているのは、
                // gen.dropdown.onTextChange() における処理が確実にすべて終了した後に実行されるようにするため。
                "function {$elmId_escape}_onTextChange() {gen.dropdown.onTextChange('{$dropdownCategory_escape}','{$elmId_escape}_show','{$elmId_escape}','{$elmId_escape}_sub','{$elmId_escape}_show_onchange()');}\n" .
                // 以前はonChangeスクリプトをエレメントのonChangeの中（onTextChangeの第4引数）に直接書いていたが、
                // ドロップダウンの選択時にonTextChangeを経由せず直接onChangeスクリプトを実行することになったため、
                // 独立させた
                "function {$elmId_escape}_show_onchange() {{$onChange_escape}}" .
                "</script>";

            $res .= "<input type=\"text\" name=\"{$elmId_escape}_show\" id=\"{$elmId_escape}_show\"" .
                // 脆弱性回避と表示乱れ対処のため、エスケープ済みの値を使うようにした
                " value=\"" . h($dropdownShowtext) . "\"" .
                // 前のほうで定義されている
                " onChange=\"{$elmId_escape}_onTextChange();\"" .
                " style=\"" . (isset($col['style']) ? h($col['style']) : "") . ";width:" . $col['size'] * 9 . "px;\"" .
                (@$col['readonly'] ? " readonly tabindex=-1 " : (isset($col['tabindex']) ? " tabindex=\"" . h($col['tabindex']) . "\"" : "")) .
                " onKeyDown=\"if (event.keyCode == 40) $('#{$elmId_escape}_dropdown').click();\"" .
                ">\n";

            // ドロップダウンボタン
            $res .=
                "<input type='button' value=\"▼\" id=\"{$elmId_escape}_dropdown\" class=\"mini_button\"" .
                    " onClick=\"" . h(@$col['onClick']) .
                    ";gen.dropdown.show('{$elmId_escape}_show'," .
                    "'{$dropdownCategory_escape}'," .
                    "'{$dropdownParam_escape}'," .
                    "'{$dropdownShowCondition_escape}'," .
                    "'{$dropdownShowConditionAlert_escape}'" .
                    ")\"" .
                    (@$col['readonly'] ? " disabled" : "") .
                    " tabindex=-1" .
                ">";

            // サブテキスト（テキストボックスの下に表示される項目。_sub）
            if (isset($dropdownHasSubtext) && $dropdownHasSubtext == "true") {
                $subSize = (isset($col['subSize']) && is_numeric($col['subSize']) ? $col['subSize'] * 9 : $col['size'] * 9);
                $res .= "<input type=\"text\" name=\"{$elmId_escape}_sub\" id=\"{$elmId_escape}_sub\" value=\"" . h($dropdownSubtext) . "\"" .
                        "readonly style='border:none; width:{$subSize}px; background-color:" . h($rowColor) . ";' tabindex=-1>\n";
            }
           // hidden（value）
            $res .= "<input type=\"hidden\" name=\"{$elmId_escape}\" id=\"{$elmId_escape}\" value=\"{$value_escape}\">\n";
            if (isset($divValue))
                $res .= "<input type=\"hidden\" name=\"div_{$elmId_escape}\" id=\"div_{$elmId_escape}\" value=\"" . h($divValue) . "\">\n";
            break;
        case 'select':          // セレクタ
            // 表示の崩れを防ぐため、選択肢の文字数を制限する。
            // <select>にCSSでwidthを指定する方法もあるが、それだとセレクタ自体のサイズは指定できるが、
            // ドロップダウンしたときの選択肢のサイズはコントロールできない。場合によっては選択肢が
            // 画面の右側にはみ出してしまい、スクロールバーが操作できなくなる。
            $maxLen = 20;      // gen_search_control, gen_db::getHtmlOptionArray と同じ
            if (is_array($col['options'])) {
                foreach ($col['options'] as $key => $val) {
                    $col['options'][$key] = mb_substr($val, 0, $maxLen, 'UTF-8');
                }
            }

            $elmId_escape = (isset($col['field']) ? h($col['field']) : "select") . "_{$id_escape}";
            $style = (isset($col['style']) ? $col['style'] : "");

            $optionsParams = array('options' => $col['options'], 'selected' => $value);
            $res .= "<select name=\"{$elmId_escape}\" id=\"{$elmId_escape}\"";
            if (isset($col['onChange_noEscape']))
                $res .= " onchange=\"" . str_replace('[id]', $elmId_escape, $col['onChange_noEscape']) . "\"";
            if (@$col['readonly']) {    // セレクタはreadonlyが効かないのでdisabledにしてる
                $res .= " disabled ";
                $style .= ";background-color:#cccccc";
            } else {
                $res .= (isset($col['tabindex']) ? " tabindex=\"" . h($col['tabindex']) . "\"" : "");
                if (isset($col['require']))
                    $style .= ";background-color:#ccffcc";
            }
            $res .= " style='" . h($style) . "';";
            $res .= ">";
            $res .= smarty_function_html_options($optionsParams, $smarty);
            $res .= "</select>\n";
            if (@$col['readonly']) {    // disabledだと値がPOSTされないので、hiddenを入れる
                $res .= "<input type=\"hidden\" name=\"{$elmId_escape}\" value=\"{$value_escape}\">\n";
            }
            break;
        case 'div':
            // divタグ
            $elmId_escape = (isset($col['field']) ? h($col['field']) : "div") . "_{$id_escape}";
            $res .= "<div id=\"{$elmId_escape}\" style=\"height:15px;" . (isset($col['style']) ? h($col['style']) : "") . "\">{$value_escape}</div>";
            break;
        case 'divAndTextbox':
            // divとテキストボックス
            $textId_escape = (isset($col['field']) ? h($col['field']) : "text") . "_{$id_escape}";
            $divId_escape = (isset($col['divField']) ? h($col['divField']) : "div") . "_{$id_escape}";
            $divValue_escape = h(numericOperation($col, $divValue));
            $divStyle_escape = (isset($col['divStyle']) ? h($col['divStyle']) : "");
            $res .= "<div id='{$divId_escape}' style=\"height:15px;{$divStyle_escape}\">{$divValue_escape}</div>";

            $style = (isset($col['style']) ? $col['style'] : "");
            $res .= "<input type=\"text\" name=\"{$textId_escape}\" id=\"{$textId_escape}\" value=\"{$value_escape}\" size=\"" . h($col['size']) . "\" style=\"height:15px;" . (isset($col['style']) ? h($col['style']) : "") . "\"";
            if (isset($col['onChange_noEscape']))
                $res .= " onchange=\"" . str_replace('[id]', $textId_escape, $col['onChange_noEscape']) . "\"";
            $res .= ">";
            break;
        case 'literal':
            // リテラル
            // literal2Condition が成立したら、literal2を使用
            if (isset($col['literal2_noEscape']) && isset($col['literal2Condition'])) {
                if (evalCondition($col['literal2Condition'], $row)) {
                    $col['literal_noEscape'] = $col['literal2_noEscape'];
                }
            }
            
            $res .= $col['literal_noEscape'];
            break;
        case 'numeric':
            // 数字
            $num = numericOperation($col, $value);
            if (is_numeric($value) && $value < 0) {
                // マイナス値
                $res .= "<font color='red'>{$num}</font>";
            } else {
                $res .= h($num);
            }
            break;
        case 'schedule':
            // スケジュール用リンクの作成
            $res .= Logic_Schedule::replaceTagToHTML($value_escape);
            break;
        default:
            // 上記以外（文字データ）
            // zeroToBlank が trueの場合は、0は表示しない
            if (!$col['zeroToBlank'] || $value != 0) {
                $res .= str_replace("[br]", "<br>", $value_escape);
            }
    }

    return $res;
}

function numericOperation($col, $value)
{
    $res = "";
    // notNumToZeroの処理
    if (isset($col['notNumToZero']) && $col['notNumToZero'] && !is_numeric($value)) {
        return "0";
    }

    // zeroToBlankの処理
    if (isset($col['zeroToBlank']) && $col['zeroToBlank'] && $value == "0") {
        return "";
    }

    if (!is_numeric($value)) 
        return $value;

    // 数値表示
    $keta = $col['keta'];           // デフォルト値はListBaseで設定済み
    $kanma = (int) $col['kanma'];    // デフォルト値はListBaseで設定済み
    if ($keta == -1) {
        // 自然丸め： 小数点以下の無駄な0を削除する（例：「75.1000」⇒「75.1」　「75.0000」⇒「75」）
        //  カンマをつける処理は、number_format($value, 10) して自然丸め  というのがスマートに思えるが、
        //  小数点以下を10にした時点で演算誤差が発生することがあり、うまくいかない。
        //  下記では文字扱いすることにより誤差の発生を避けている。
        $decPos = strpos($value, ".");

        // 整数部。カンマをつける
        if ($value >= 0) {
            $left = number_format(floor($value));
        } else {
            $left = number_format(ceil($value));    // 負数はceilする必要があることに注意
            if ($left=='0')
                $left = '-0';
        }

        if ($decPos === FALSE) {
            // 小数なし
            $res = $left;
        } else {
            // 小数あり
            $right0 = substr($value, $decPos + 1);         // 小数部。数値計算すると丸め誤差が発生することがあるので、文字として取り出す
            $right = preg_replace('/0+$/', '', $right0);   // 小数点以下の有効数値部を取り出し
            $res = $left . ($right == '' ? '' : '.' . $right);
        }
    } else {
        // 桁数指定：　規定の桁数にそろえる（例：「75.1000」⇒「75.10」　「75.0000」⇒「75.00」）
        if ($kanma == 1) {
            $res = number_format($value, $keta);
        } else {
            $res = number_format($value, $keta, ".", "");
        }
    }
    return $res;
}

// セル（td/div）:onClick処理
function getOnClickTag($row, $onClickCondition, $onClick, $idField)
{
    $res = "";

    // オンクリックを有効化するかどうかの判断
    //    onClickCondition項目が指定されている場合、条件が成立する場合のみオンクリックが有効になる。
    //    条件式の中で[...]と書いた部分はフィールドの値に置き換えられて評価される。
    //    たとえば　'showCondition' => "[class] == '受注'" であれば、classフィールドが
    //  「受注」のときのみオンクリック有効になる
    $onClickEnable = true;
    if ($onClickCondition != "") {
        $onClickEnable = evalCondition($onClickCondition, $row);
    }

    // オンクリックで指定されたjavascriptを実行。[id]はその列のid値に置き換えられる。
    //       その他 [] はフィールドの内容に置き換えられる
    if ($onClickEnable) {
        $onClickValue0 = $onClick;
        $onClickValue1 = str_replace('[id]', $row[$idField], $onClickValue0 );
        // 条件式の[...]をフィールドの値に置き換える
        $onClickValue = bracketToFieldValue($onClickValue1, $row);
        // sameCellJoinでの表示乱れ対処として、onClickの前にスペースを入れた
        $res .= $onClickValue;
    }

    return $res;
}

//************************************************
// 汎用ユーティリティ関数群
//************************************************

// 文字列内の[...]をフィールドの値に置き換える
function bracketToFieldValue($sourceStr, $row)
{
    $matches = "";
    $res = $sourceStr;
    if (preg_match_all("(\[[^\]]*\])", $res, $matches) > 0) {
        foreach ($matches[0] as $match) {
            $matchStr0 = $match;
            $matchStr1 = str_replace('[', '', $matchStr0);
            $matchStr = str_replace(']', '', $matchStr1);
            $val = @$row[$matchStr];
            if (substr($matchStr, 0, 10)=='urlencode:')
                    $val = urlencode(@$row[substr($matchStr, 10, strlen($matchStr)-10)]);
            $res = str_replace($match, $val, $res);
        }
    }
    return $res;
}

// 与えられた条件式を評価し、結果を返す。
//  引数$condは PHP構文で評価され、その結果（true or false）が返る。
//  条件式の中の [...] の部分はフィールドの値に置き換えられて評価される。
function evalCondition($cond, $row)
{
    if ($cond == "")
        return false;

    // 条件式の[...]をフィールドの値に置き換える
    $cond = bracketToFieldValue($cond, $row);

    // evalは文字列をPHPコードとして実行する関数。returnを入れることにより
    // 式の評価結果（真偽）を返している。文字列の最後にはセミコロンが必要。
    return eval("return({$cond});");
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
function evalConditionArray($condArr, $row)
{
    if (!is_array($condArr))
        return false;

    while (list($key, $exp0) = each($condArr)) {
        // 条件式の[...]をフィールドの値に置き換える
        $exp1 = bracketToFieldValue($exp0, $row);
        // evalは文字列をPHPコードとして実行する関数。returnを入れることにより
        // 式の評価結果（真偽）を返している。文字列の最後にはセミコロンが必要。
        if (eval("return({$exp1});")) {
            return $key;
        }
    }
    return false;
}
