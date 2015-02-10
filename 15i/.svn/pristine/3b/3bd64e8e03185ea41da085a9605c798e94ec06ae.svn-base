<?php

class Stock_Assessment_List extends Base_ListBase
{

    function setSearchCondition(&$form)
    {
        $form['gen_noSavedSearchCondition'] = true;

        $form['gen_searchControlArray'] = array(
        );
    }

    function convertSearchCondition($converter, &$form)
    {
    }

    function beforeLogic(&$form)
    {
    }

    function setQueryParam(&$form)
    {
        $this->selectQuery = "select 1 from company_master ";    // ダミーSQL
        $this->orderbyDefault = '';
    }

    function setViewParam(&$form)
    {
        global $gen_db;

        $this->tpl = 'issue.tpl';

        $form['gen_pageTitle'] = _g("在庫評価単価の更新");
        $form['gen_pageHelp'] = _g("在庫評価単価");

        $form['gen_returnUrl'] = "index.php?action=Stock_Stocklist_List&gen_restore_search_condition=true";
        $form['gen_returnCaption'] = _g('在庫リストへ戻る');
        $form['gen_onLoad_noEscape'] = "onLoad()";

        $query = "select stock_price_assessment from company_master";
        $stockPriceAssesment = $gen_db->queryOneValue($query);
        
        $lastDate = "";
        $lastDate2 = "";
        $query = "select assessment_date from stock_price_history group by assessment_date order by assessment_date desc limit 2";
        $assArr = $gen_db->getArray($query);
        if ($assArr) {
            $lastDate = $assArr[0]["assessment_date"];
            $lastDate2 = isset($assArr[1]["assessment_date"]) ? $assArr[1]["assessment_date"] : "";
        }

        if ($stockPriceAssesment == "2") {
            $form['gen_message_noEscape'] = "
                <span style='font-size:14px'>
                " . _g("選択されている在庫評価法が「標準原価法」であるため、この画面で評価単価を更新することはできません。") . "
                <br>" . _g("品目マスタで在庫評価単価を直接更新してください。") . "
                <br>" . _g("（在庫評価法は[メンテナンス]-[自社情報マスタ]で変更できます）。") . "
                </span>
                ";
        } else {
            $html_close_date = Gen_String::makeCalendarHtml(
                array(
                    'label' => "",
                    'name' => "gen_search_close_date",
                    'value' => isset($form['gen_search_close_date']) ? $form['gen_search_close_date']: Gen_String::getLastMonthLastDateString(),
                    'size' => '85',
                )
            );
            $html_last_assessment_price = Gen_String::makeSelectHtml("gen_search_last_assessment_price", array("0" => _g('前回更新時の評価単価'), "1" => _g('品目マスタの「在庫評価単価」'))
                    , @$form['gen_search_last_assessment_price'], "", "Stock_Assessment_List", @$form['gen_pins']);
            $html_init_date = Gen_String::makeCalendarHtml(
                array(
                    'label' => "",
                    'name' => "initDate",
                    'value' => date('Y-m-d'),
                    'size' => '85',
                )
            );
            $form['gen_message_noEscape'] = "
                <center>
                <table border='0'>
                    <tr align='center'>
                        <td>
                            <div id='msg'>
                            " . _g("品目マスタの「在庫評価単価」を更新します。") . "<br><br>
                            <table>
                            <tr><td>" . _g("※在庫評価単価は手動で変更することもできます（品目マスタの項目「在庫評価単価」を書き換える）。") . "</td></tr>
                            <tr><td>" . _g("※在庫評価単価が更新されると、原価に影響が出る場合があります。") . "</td></tr>
                            <tr><td>" . sprintf(_g("※更新後、%1\$s データロック処理 %2\$s を行うことをお勧めします。"),"<a href='index.php?action=Monthly_Process_Monthly' target='_blank'>","</a>") . "</td></tr>
                            </table>
                            <br>
                            <table>
                            <tr><td>" . _g("前回更新時の基準日") . " " . _g("：") . " " . "</td><td>" . h($lastDate) . "</td></tr>
                            <tr><td>" . _g("基準日") . " " . _g("：") . " " . "</td><td>" . "{$html_close_date}</td></tr>
                            <tr><td colspan='2' align='center'>
                                <div id='buttonArea'>
                                    <input type='button' id='doButton' class='gen-button' value='" . _g("在庫評価単価を更新する") . "' onClick='updateAssessmentPrice()'>
                                </div>
                            </td></tr>
                            </table>
                            <br>
                            <br>
                            <table border='0'>
                                <tr>
                                    <td>
                                        <table><tr><td><input type='checkbox' id='check1' onclick='alterContentMode(1)'></td><td>" . _g("発注品を対象とする") . "</td></tr></table>
                                    </td>
                                    
                                    <td></td>
                                    
                                    <td>
                                        <table><tr><td><input type='checkbox' id='check2' onclick='alterContentMode(2)'></td><td>" . _g("内製品・外注品を対象とする") . "</td></tr></table>
                                    </td>
                                </tr>
                                <tr>
                                    <td id='content1' style='width:600px; position:relative; padding: 20px; padding-bottom:50px; border: solid 1px #999999;' valign='top' align='center'>
                                        <span style='color:#000; font-size:15px; font-weight:bold'>■" . _g("発注品") . "</span><br>
                                        <table style='font-size:16px'>
                                        <tr><td>" . _g("在庫評価法") . " " . _g("：") . " " . "</td><td>" . ($stockPriceAssesment == "1" ? _g("総平均法") : _g("最終仕入原価法")) . "</td></tr>
                                        <tr><td>" . _g("基準単価") . " " . _g("：") . " " . "</td><td>{$html_last_assessment_price}</td></tr>
                                        </td></tr></table>
                                        " . _g("※基準単価は在庫評価法が「総平均法」の場合だけ選択できます。 ") . "
                                        <br><br>
                                        <table>
                                        <tr><td align='left'>" . _g("発注品の評価単価を自社情報マスタの「在庫評価法」に基づいて計算し、品目マスタの「在庫評価単価」に登録します。") . "</td></tr>
                                        <tr><td align='left'>" . _g("●最終仕入原価法： 基準日以前の最後の仕入（注文受入）単価が評価単価となります。") . "</td></tr>
                                        <tr><td align='left'>" . _g("●総平均法： 以下の式で計算した金額が在庫評価単価となります。") . "</td></tr>
                                        <tr><td align='left'>
                                            <table style='padding-left:20px'>
                                                <tr><td>" . _g("(「基準単価」で選択した単価 × 前回更新時の在庫数 + 前回更新時の基準日から今回基準日までの仕入[注文受入]額 ) ÷ (前回更新時の在庫数 + 前回更新時の基準日から今回基準日までの仕入[注文受入]数)") . "</td></tr>
                                                <tr><td>" . _g("※「前回更新時の在庫数」には、サプライヤーロケーションの在庫も含まれます。") . "</td></tr>
                                            </table>
                                        </td></tr>
                                        <tr height='10px'><td></td></tr>
                                        <tr><td align='left'>" . _g("※更新対象となるのは品目マスタ「手配区分」が「発注」であり、なおかつ期間内に注文受入が行われた品目のみです。（内製や外製は対象になりません。）") . "</td></tr>
                                        <tr><td align='left'>" . _g("※日付はすべて受入日ベースとなります（自社情報マスタ「仕入計上基準」で「検収日」を指定していても、ここでは受入日が使用されます）。") . "</td></tr>
                                        </table>
                                    </td>
                                    
                                    <td width='10'></td>
                                    
                                    <td id='content2' style='width:600px; position:relative; padding: 20px; border: solid 1px #999999;' valign='top' align='center'>
                                        <span style='color:#000; font-size:15px; font-weight:bold'>■" . _g("内製品・外注品") . "</span><br><br>
                                        <table>
                                        <tr><td align='left'>" . _g("内製品・外注品の標準原価を、品目マスタの「在庫評価単価」に登録します。") . "</td></tr>
                                        <tr height='10px'><td></td></tr>
                                        <tr><td align='left'>" . _g("※更新対象となるのは品目マスタ「手配区分」が「内製」もしくは「外注」になっている品目のみです。") . "</td></tr>
                                        <tr><td align='left'>" . _g("※標準原価は [販売管理] - [原価リスト] - [標準原価算定]画面で表示されるものと同じです。計算方法については同画面のリスト見出しのチップヘルプを参照してください。") . "</td></tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                            </div>
                        </td>
                    </tr>
                    <tr style='height:30px'></tr>
                    <tr><td><hr></td></tr>
                    <tr style='height:10px'></tr>
                    <tr align='center'>
                        <td>
                            ■" . _g("更新の取り消し") . "<br><br>
                            " . ($lastDate2 == "" ? 
                                    ($lastDate == "" ? 
                                        _g("在庫評価単価の更新が行われていないため、更新を取り消すことはできません。") 
                                        :            
                                        _g("在庫評価単価の更新が1回しか行われていないため、更新を取り消すことはできません。") 
                                    ) . "<br><br>"
                                    . "<input type='button' class='gen-button' value='" . _g("最後に実行した更新を取り消す") . "' disabled>"
                                :
                                    sprintf(_g("最後に実行した在庫評価単価の更新（基準日：%s）を取り消します。"), h($lastDate)) . "<br><br>
                                    " . sprintf(_g("この処理を行うと、品目マスタの在庫評価単価が、前々回の更新（基準日：%s）直後の時点に戻ります。"), h($lastDate2)) . "<br><br>
                                    <div id='buttonArea2'>
                                        <input type='button' id='deleteButton' class='gen-button' value='" . _g("最後に実行した更新を取り消す") . "' onClick='deleteAssessmentPrice()'>
                                    </div>"
                                ) . "
                        </td>
                    </tr>
                    <tr style='height:30px'></tr>
                    " . ($stockPriceAssesment == 1 ? "
                        <tr><td><hr></td></tr>
                        <tr style='height:10px'></tr>
                        <tr align='center'>
                            <td>
                                ■" . _g("総平均法の初期値データのインポート") . "<br><br>
                                " . _g("発注品の計算を総平均法で行う場合の「前回更新時の評価単価」と「前回更新時の在庫数」をインポートします。") . "<br><br>
                                <table align='center'>
                                <tr><td align='left'>" . _g("※インポートデータはCSV形式です（品目コード,在庫評価単価,在庫数）。1行目からデータとみなされます。") . "</td></tr>
                                <tr><td align='left'>" . _g("※ここでインポートした在庫数は理論在庫等には反映されません。総平均法の初期値を設定するためのものです。") . "</td></tr>
                                </table><br>
                                <div id='msgArea'></div>
                                <table><tr><td>
                                " . _g("基準日") . " " . _g("：") . " " . "" . "{$html_init_date}
                                </td></tr></table>
                                <div id='initDataImport'></div>
                            </td>
                        </tr>
                    " : "") . "
                </table>
                </center>
                ";
        }

        $form['gen_javascript_noEscape'] = "
            // ページロード
            function onLoad() {
                alterContentMode(1);
                alterContentMode(2);
                gen.fileUpload.init2('initDataImport', 'index.php?action=Stock_Assessment_ImportInitData&initDate=[initDate]', '', 'initDataImportCallback', '', undefined, undefined, false);
            }
            
            function initDataImportCallback(res, callbackParam) {
                var color = (res.success ? '99ffff' : 'ffcccc');
                var html = \"<table width='100%'><tr><td bgcolor='#\" + color + \"' align='center'>\";
                if (res.success) {
                    html += \"" . _g("初期値データのインポートに成功しました。") . "\";
                } else if (res.msg instanceof Array) {
                    html += '" . _g("下記のエラーが発生しました。データは1件も登録されませんでした。") . "<br><br>';
                    html += \"<table border=1 cellspacing='0' cellpadding='2'>\";
                    html += \"<tr bgcolor='#cccccc'><td width='50px' align='center'>" . _g("行") . "</td><td align='center' nowrap>" . _g("メッセージ") . "</td></tr>\";
                    $.each(res.msg, function(i, val) {
                        html += \"<tr bgcolor='#ffffff'><td align='center'>\" + gen.util.escape(val[0]) + \"</td><td>\" + gen.util.escape(val[1]) + \"</td></tr>\";
                    });
                    html += '</table>';
                } else {
                    html += gen.util.escape(res.msg);
                }
                html += '</td></tr></table>';
                $('#msgArea').html(html);
            }
            
            // ----- 説明エリア 有効/無効 -----
            
            function alterContentMode(no) {
                var enabled = ($('#check' + no).is(':checked'));
                $('#content' + no)
                    .css('color','#' + (enabled ? '000' : 'ccc'))
                    .css('background-color','#' + (enabled ? 'd5ebff' : 'f5f5f5'));
                if (no == 1) {
                    gen.ui.alterDisabled($('#gen_search_last_assessment_price'), !enabled ||  " . ($stockPriceAssesment != "1" ? "true" : "false") . ");
                }
            }

            // ----- 更新処理実行 -----
            
            function updateAssessmentPrice() {
                var date = $('#gen_search_close_date').val();
                var type = $('#gen_search_last_assessment_price').val();
                var check1 = $('#check1').is(':checked');
                var check2 = $('#check2').is(':checked');
                if (!check1 && !check2) {
                    alert('" . _g("「発注品を対象とする」「内製品・外注品を対象とする」のどちらか、あるいは両方を選択してください。") . "'); return;
                }
                if (!gen.date.isDate(date)) {
                    alert('" . _g("基準日の指定が正しくありません。") . "'); return;
                }
                if (" . ($stockPriceAssesment == "1" && $lastDate != "" ? "true" : "false") . ") {
                    if (Date.parse(date.replace('-','/').replace('-','/')) <= Date.parse('" . $lastDate . "'.replace('-','/').replace('-','/'))) {
                        alert('" . _g("基準日には、前回更新時の基準日（%date）より後の日付を指定する必要があります。") . "'.replace('%date','" . $lastDate . "')); return;
                    }
                }
                
                if (!confirm('" . _g("在庫評価単価を更新します。この操作を元に戻すことはできません。本当に実行してもよろしいですか？") . "')) return;

                var buttonArea = $('#buttonArea');
                document.body.style.cursor = 'wait';
                buttonArea.html(\"<table><tr><td bgcolor='#ffcc33'>" . _g("実行中") . "...</td></tr></table>\");
                gen.ui.disabled($('#deleteButton'));

                gen.ajax.connect('Stock_Assessment_AjaxUpdateAssessmentPrice', {date : date, type : type, check1 : check1, check2 : check2}, 
                    function(j) {
                        if (j.result=='success') {
                            alert('" . _g("在庫評価単価が更新されました。在庫リストで単価を確認できます。") . "');
                            buttonArea.html(\"<table><tr><td bgcolor='#66ffcc'>" . _g("実行終了") . "</td></tr></table>\");
                        } else if (j.result=='nodata') {
                            alert('" . _g("対象となるデータが1件もないため、更新が行われませんでした。") . "');
                            buttonArea.html(\"<table><tr><td bgcolor='#66ffcc'>" . _g("対象データなし") . "</td></tr></table>\");
                        } else {
                            alert('" . _g("在庫評価単価の更新に失敗しました。") . "');
                            buttonArea.html(\"<table><tr><td bgcolor='#ff6666'>" . _g("更新失敗") . "</td></tr></table>\");
                        }
                        location.href='index.php?action=Stock_Assessment_List';
                    });
            }
            
            // ----- 最後の更新を取り消す -----
            
            function deleteAssessmentPrice() {
                if (!confirm('" . _g("最後に実行した在庫評価単価の更新を削除します。この操作を元に戻すことはできません。本当に実行してもよろしいですか？") . "')) return;
                
                document.body.style.cursor = 'wait';
                gen.ui.disabled($('#deleteButton'));
                gen.ui.disabled($('#deleteButton2'));

                gen.ajax.connect('Stock_Assessment_AjaxDeleteAssessmentPrice', {}, 
                    function(j) {
                        if (j.result=='success') {
                            alert('" . _g("在庫評価単価が削除されました。") . "');
                        } else {
                            alert('" . _g("在庫評価単価の削除に失敗しました。") . "');
                        }
                        location.href='index.php?action=Stock_Assessment_List';
                    });
            }
        ";
    }

}
