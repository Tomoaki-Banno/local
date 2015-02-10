<?php
class Mobile_Mrp_List
{
    function execute(&$form)
    {
        global $gen_db;
        
        $form['gen_pageTitle'] = _g("所要量計算");

        // リロード対策
        // 実行後のList画面（URLはEntryになっている）でF5を押した場合や、実行後に他の画面に移ってから
        // 「戻る」で戻った場合に、計算が再実行されてしまう現象を防ぐ。
        // ここで作成したページリクエストIDを、javascriptのMRP実行部分で引数として埋め込んでいる。
        // Edit画面（Base継承）ならフレームワーク側で行われる処理だが、Listの場合は自前で行う必要がある。
        global $_SESSION;
        $reqId = sha1(uniqid(rand(), true));
        $_SESSION['gen_page_request_id'][] = $reqId;

        // 所要量計算終了日時デフォルト
        // パラメータdate_year等を受け取っていればそれを反映
        $defaultDate = @$form['date_year'] . "/" . @$form['date_month']. "/" . @$form['date_day'];
        if (!Gen_String::isDateString($defaultDate)) {
            $defaultDate = date('Y/m/d', time() + (3600*24*10));    // 10日後
        }

        // ユーザのパーミッションを取得し、チェックボックスの状態を設定
        // 戻り値  -1: セッション不正  0: アクセス権限なし  1: 読み取りのみ  2: 読み書き可能
        $notPOrder = (Gen_Auth::sessionCheck('partner_order')==2 ? "":"disabled");
        $notSubOrder = (Gen_Auth::sessionCheck('partner_subcontract')==2 ? "":"disabled");
        $notManOrder = (Gen_Auth::sessionCheck('manufacturing_order')==2 ? "":"disabled");
        $notSeiban = (Gen_Auth::sessionCheck('stock_seibanchange')==2 ? "":"disabled");

        // プログレスバーの長さ（px）これを縮めても、バー画像が自動縮小されるわけではない。変更時は画像の変更が必要
        $progressPx = 280;
        
        $form['gen_message_noEscape'] = "
            <script type=\"text/javascript\" src=\"yui/yahoo-dom-event/yahoo-dom-event.js\"></script>
            <script type=\"text/javascript\" src=\"yui/connection/connection-min.js\"></script>
            <script type=\"text/javascript\" src=\"scripts/gen_script.js\"></script>
            <script type=\"text/javascript\" src=\"scripts/gen_waitdialog.js\"></script>

            <script type=\"text/javascript\">
                $(document).on('pageinit', '#gen_mobile_page', function(event){
                  progress();
                });
                
                // 実行状況確認
                function progress() {
                    gen.ajax.connect('Manufacturing_Mrp_AjaxMrpProgress', {}, 
                        function(j){
                            var msgElm = $('#msg');
                            var arr = j.data.split(',');
                            if (j.doing == 'false' || isNaN(arr[3])) {
                                if (msgElm.html().substr(0,8) == '". _g("所要量計算実行中") . "'".  (@$form['mrp_flag'] == 'true' ? " || true" : "") . ") { // これまで実行中だった場合は、終了処理を行う
                                    $('#mrpProgress').html('"._g("100％  完了") . "<BR><BR><BR>');
                                    $('#graph').css('width', (100 * {$progressPx} / 100) + 'px');
                                    alert('". _g("所要量計算が終了しました。") . "');
                                    // データ表の表示を更新する。ちなみにreloadだとMRP実行開始時POSTの引数mrp_flagが残ってしまいうまくいかない
                                    location.href = \"index.php?action=Mobile_Mrp_List&date_year=". h(@$form['date_year']) . "&date_month=". h(@$form['date_month']). "&date_day=". h(@$form['date_day']) . "\";
                                } else {
                                   msgElm.html('". _g("現在実行されていません。") . "<BR>".
                                                   _g("最終実行時刻 ") . "　: ' + gen.util.escape(arr[0]) + '<BR>" .
                                                   _g("最終実行ユーザー") . "　: ' + gen.util.escape(arr[1]));
                                }
                                $('#mrpProgress').html('');
                                $('#mrpStart').show();
                                jqmEnabled($('#mrpStartButton'));
                                $('#gen_dataTable').show();     // データ表を表示
                                $('.gray_msg').show();

                                tm = setTimeout('progress()',30000);    // 30秒後に再実行
                                $('#graph').css('width','0px');
                            } else if (j.doing == 'true') {
                                msgElm.html('". _g("所要量計算実行中") . "<BR>" ._g("開始時刻") . " : ' + gen.util.escape(arr[0]));
                                $('#mrpProgress').html('". _g("開始ユーザー") . " : ' + gen.util.escape(arr[1]) + '<BR>' + gen.util.escape(arr[2]) + '<BR>' + gen.util.escape(arr[3]) + '％');
                                $('#mrpStart').hide();      // 実行中は実行開始ボタンを隠す
                                jqmDisabled($('#mrpStartButton'));
                                $('#gen_dataTable').hide(); // データ表も隠す
                                $('#graph').css('width',(gen.util.escape(arr[3]) * {$progressPx} / 100) + 'px');
                                $('.gray_msg').hide();

                                tm = setTimeout('progress()',1000);      // 1秒後に再実行
                            } else {
                                alert('". _g("状況確認時にエラーが発生しました。") . "');
                            }

                        }, true);
                }

                // 所要量計算実行
                function doMRP() {
                    jqmDisabled($('#mrpStartButton'));

                    var f1 = $('#form1');
                    var mrpDate = $('#mrpDate').val();
                    if (!gen.date.isDate(mrpDate)) {
                        alert('". _g("期間指定が正しくありません。") . "');
                        jqmEnabled($('#mrpStartButton'));
                        return;
                    }
                    var today = new Date(); // 明日
                    var toDate = gen.date.parseDateStr(mrpDate);
                    if (toDate < today) {
                        alert('". _g("期間指定が正しくありません。終了日には開始日以降の日付を指定してください。") . "');
                        jqmEnabled($('#mrpStartButton'));
                        return;
                    }
                    if ((toDate - today) > (3600*24*1000*" . GEN_MRP_DAYS . ")) {   // 期間制限はgen_configで指定。
                        alert('". _g("%days日を超える期間を指定することはできません。") . "'.replace('%days'," . GEN_MRP_DAYS . "));
                        jqmEnabled($('#mrpStartButton'));
                        return;
                    }
                    var year = toDate.getFullYear()
                    var month = toDate.getMonth() + 1;
                    var day = toDate.getDate();
                    var dateStr = year + '-' + month + '-' + day;
                    if (!confirm('". _g("明日から  %date までを対象期間として所要量計算を実行します。よろしいですか？") . "'.replace('%date',dateStr))) {
                        jqmEnabled($('#mrpStartButton'));
                        return;
                    }

                    var postUrl = 'index.php?action=Manufacturing_Mrp_Mrp' +
                       '&mrp_date=' + dateStr +
                       '&mrp_flag=true' +       // MRP実行中フラグ。このページが再ロードされたとき、実行中であることが認識できるように。
                       '&isMobile=true' +
                       '&gen_page_request_id={$reqId}';
                    if ($('#isNaiji')[0].checked) {
                       postUrl += '&isNaiji=true';
                    }
                    if ($('#isNonSafetyStock')[0].checked) {
                       postUrl += '&isNonSafetyStock=true';
                    }
                    location.href = postUrl;
                }
                                
                function jqmEnabled(jo) {
                    jo.removeAttr('disabled').removeClass('ui-disabled');
                }
                function jqmDisabled(jo) {
                    jo.attr('disabled', 'disabled').addClass('ui-disabled');
                }
            </script>
            
            <table>
               <tr align=\"center\">
                   <td>
                        <div class=\"graph_base_mobile\" style=\"width:{$progressPx}px; height:30px;
                         background:url(img/graph_base_mobile.gif) no-repeat; \">
                           <div class=\"graph\" id=\"graph\" style=\"width:0px; height:30px;
                             background:url(img/graph_mobile.gif) no-repeat; float:left;\">
                           </div>
                        </div>
                        <div id=\"msg\" align='left'>". _g("状況確認中") . "<BR>" . _g("しばらくお待ちください") . "... </div>
                   </td>
               </tr>
               
               <tr align=\"center\" valign=\"top\">
                   <td id=\"mrpProgress\"></td>
               </tr>
               
               <tr align=\"center\">
                   <td id=\"mrpStart\">
                   <table width=\"100%\">
                    <td align=\"center\" style=\"background-color:#d5ebff\">" .
                        sprintf(_g("対象日付： 明日（%s）から"),date("Y-m-d", strtotime("+1 day"))) .
                       "<br>
                        <input type='date' name='mrpDate' id='mrpDate' value='" . h($defaultDate) . "'>
                       <table border=\"0\">
                       <tr><td align=\"left\">
                         <input type=\"checkbox\" value=\"true\" id=\"isNaiji\"".((@$form['isNaiji']=='true')?" checked":"").">
                         <label for=\"isNaiji\">". _g("内示モード") . "</label>
                         <input type=\"checkbox\" value=\"true\" id=\"isNonSafetyStock\"".((@$form['isNonSafetyStock']=='true')?" checked":"").">
                         <label for=\"isNonSafetyStock\">". _g("安全在庫数を含めない") . "</label>
                       </td></tr>
                       </table>
                       <a href=\"javascript:doMRP()\" data-role=\"button\" id=\"mrpStartButton\" disabled=\"true\">". _g("所要量計算を開始する") . "</a>
                     </td>
                   </table>
                   </td>
               </tr>
            </table>

            <div class=\"gray_msg\">
            </div>
        ";
        
        return 'mobile_simplepage.tpl';
    }
}