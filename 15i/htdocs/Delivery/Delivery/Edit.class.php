<?php

require_once("Model.class.php");

class Delivery_Delivery_Edit extends Base_EditBase
{

    function convert($converter, &$form)
    {
        global $gen_db;

        $converter->nullBlankToValue('delivery_date', date("Y-m-d"));

        // List明細モードから来た場合は delivery_header_id が指定されていない。delivery_detail_id から delivery_header_id を取得する。
        if (is_numeric(@$form['delivery_detail_id'])) {
            $query = "select delivery_header_id from delivery_detail where delivery_detail_id = '{$form['delivery_detail_id']}'";
            $tranId = $gen_db->queryOneValue($query);
            $converter->nullBlankToValue('delivery_header_id', $tranId);
        }
    }

    function validate($validator, &$form)
    {
        $validator->blankOrNumeric('delivery_header_id', _g('delivery_header_idが正しくありません。'));
        return 'action:Delivery_Delivery_List';        // if error
    }

    // 既存データSQL実行前に処理
    function setQueryParam(&$form)
    {
        global $gen_db;

        $this->keyColumn = 'delivery_header_id';
        $keyCurrency = $gen_db->queryOneValue("select key_currency from company_master");
        $this->selectQuery = "
            select
                delivery_header.*
                ,delivery_header.delivery_customer_id
                ,case when currency_name is null then '{$keyCurrency}' else currency_name end as currency_name

                ,coalesce(delivery_header.record_update_date, delivery_header.record_create_date) as gen_last_update
                ,coalesce(delivery_header.record_updater, delivery_header.record_creator) as gen_last_updater

            from
            	delivery_header
                left join customer_master on delivery_header.customer_id = customer_master.customer_id
                left join customer_master as t_bill_customer on coalesce(customer_master.bill_customer_id,customer_master.customer_id) = t_bill_customer.customer_id
                left join currency_master on t_bill_customer.currency_id = currency_master.currency_id

            [Where]
        ";

        // データロックの判断基準となるフィールドを指定
        $form["gen_salesDateLockFieldArray"] = array("delivery_date");
    }

    // 既存データSQL実行後に処理
    // 画面表示のための設定
    function setViewParam(&$form)
    {
        global $gen_db;

        $this->modelName = "Delivery_Delivery_Model";

        $form['gen_pageTitle'] = _g("納品登録");
        $form['gen_entryAction'] = "Delivery_Delivery_Entry";
        $form['gen_listAction'] = "Delivery_Delivery_List";
        $form['gen_onLoad_noEscape'] = "onLoad()";
        $form['gen_pageHelp'] = _g("納品登録");

        $form['gen_message_noEscape'] = "";
        if (is_numeric(@$form['delivery_header_id']) && !isset($form['gen_redMode'])) {
            if (Logic_Delivery::hasBillByDeliveryHeaderId($form['delivery_header_id'])) {
                $form['gen_readonly'] = "true";
                $form['gen_message_noEscape'] .= "<font color=red>" 
                        . _g("この納品を含む請求書が発行されているため、内容を変更することはできません。") . "<br>" 
                        . _g("ただし「完了」状態だけは変更できます。") ."&nbsp;<a href='javascript:completedFlagRegist()'>" . _g("「完了」状態を登録する") . "</a></font><br><br>";
            } else {
                // このチェックは登録時にも行っているが（Model）、15iではここでも行うようにした。
                // 「完了」状態の変更を可能にするため。詳しくは ag.cgi?page=ProjectDocView&pid=1574&did=216214
                $query = "select receivable_report_timing from company_master";
                $timing = $gen_db->queryOneValue($query);
                $query = "select customer_id, " . ($timing == '1' ? 'inspection' : 'delivery') . "_date as date from delivery_header where delivery_header_id = '{$form['delivery_header_id']}'";
                $obj = $gen_db->queryOneRowObject($query);
                if ($obj->date !== null) {
                    $query = "
                        select
                            1
                        from
                            bill_header
                        where
                            customer_id = '{$obj->customer_id}'
                            and bill_pattern <> 2
                        having
                            max(close_date) >= '{$obj->date}'
                    ";
                    if ($gen_db->existRecord($query)) {
                        $form['gen_readonly'] = "true";
                        $form['gen_message_noEscape'] .= "<font color=red>" 
                                . sprintf(_g("この得意先に対し、%sより後の日付の請求書が発行されているため、登録を行えません。"), ($timing == '1' ? _g("検収日") : _g("納品日"))) . "<br>" 
                                . _g("ただし「完了」状態だけは変更できます。") ."&nbsp;<a href='javascript:completedFlagRegist()'>" . _g("「完了」状態を登録する") . "</a></font><br><br>";
                    }
                }
            }
        }

        // これを設定すると、「登録して印刷」ボタンと「帳票を印刷」ボタン（編集モードのみ）が表示される。
        $form['gen_reportArray'] = array(
            'action' => "Delivery_Delivery_Report",
            'param' => "check_[id]",
            'seq' => "delivery_header_delivery_header_id_seq",
        );

        $isReload = (isset($form['gen_validError']) && $form['gen_validError']) || isset($form['gen_editReload']);

        $form['gen_javascript_noEscape'] = "
            var isPageDataModified = false;	// データ（受注製番やロケ）が変更されたかどうかのフラグ

            // ページロード
            function onLoad() {
                " . ($isReload || isset($form['gen_redMode']) ? "
                // リロード（新規登録モードでのバリデーションエラーによる差し戻し、および項目変更・並べ替え・リセット・明細行数変更）のときのみ、
                // 各行の品目名や在庫数等を再取得する。
                // ちなみに通常時（リロード以外の場合）はSQLによってそれらが取得されるのでこれを実行する必要はない。
                // また、EditListに関しては修正モードのエラー時にもSQL取得される。
                $('[id^=line_no]').each(function(){
                    lineNo = this.innerHTML;
                    if ($('#received_detail_id_'+lineNo+'_show').val()!='') {
                        onReceivedIdChange(lineNo, false, true);
                    }
                });
                " : "") . "
                " . (isset($form['gen_receivedCopy']) ? "copyFromReceived(true);" : "") . "
                calcTotalAmount();
                // 売掛残高と与信限度額の設定
                onCustomerIdChange();
                // レート制御
                setCurrencyRate(true);
                // レートチェック
                checkCurrency();
            }

            // 受注製番セレクタ変更イベント（ロケ変更時にも実行）
            function onReceivedIdChange(lineNo, isLocationChange, isPageLoad) {
                if ($('#received_detail_id_'+lineNo+'_show').val()=='') return;
                var receivedDetailId = $('#received_detail_id_'+lineNo).val();
                if (!gen.util.isNumeric(receivedDetailId)) {
                    alert('" . _g("指定された受注製番は存在しないか、納品済みであるか、もしくは「得意先」欄に表示されている得意先の受注ではありません。（ひとつの伝票に異なる得意先の受注を入力することはできません。）") . "');
                    $('#received_detail_id_'+lineNo+'_show').get(0).select();
                    return;
                }
                if (!isPageLoad) isPageDataModified = true;

                gen.edit.submitDisabled();
                $('#received_number_'+lineNo).html('');
                $('#received_line_no_'+lineNo).html('');
                $('#item_code_'+lineNo).html('');
                $('#item_name_'+lineNo).html('');
                $('#received_quantity_'+lineNo).html('');
                $('#delivery_amount_'+lineNo).html('');
                $('#remained_quantity_'+lineNo).html('');
                $('#stock_quantity_'+lineNo).html('');
                $('#remarks_'+lineNo).html('');

                var custIdCache = $('#customer_id').val();

                var p = {};
                p.received_detail_id = receivedDetailId;
                p.delivery_detail_id = $('#delivery_detail_id_'+lineNo).val();
                if (p.delivery_detail_id == undefined) p.delivery_detail_id = '';
                if (isLocationChange)   // ロケIDを指定するのはロケ変更時のみ。受注製番変更のときはロケIDを指定しない（標準ロケの在庫数が取得される）
                    p.location_id = $('#location_id_'+lineNo).val();
                gen.ajax.connect('Delivery_Delivery_AjaxReceivedParam', p,
                    function(j) {
                        if (j.status == 'success') {
                            $('#received_line_no_'+lineNo).text(j.received_number + ' / ' + j.received_line_no);
                            $('#dead_line_'+lineNo).text(j.dead_line);
                            $('#item_code_'+lineNo).text(j.item_code);
                            $('#item_name_'+lineNo).text(j.item_name);
                            $('#received_quantity_'+lineNo).text(j.rec_qty);
                            $('#remained_quantity_'+lineNo).text(j.rem_qty);
                            if (j.is_dummy!='1')    // ダミー品目は在庫数を表示しない。登録時の警告処理で、在庫数が表示されていなければダミーと判断している
                                $('#stock_quantity_'+lineNo).text(gen.util.addFigure(j.stock_qty));
                            $('#measure_'+lineNo).text(j.measure);
                            var scr = $('#gen_contents').scrollTop();   // ag.cgi?page=ProjectDocView&pid=1574&did=233395
                            $('body').css({visibility:'hidden'});    // スクロールによるがたつき防止
                            $('#customer_id_show').val(j.customer_no).trigger('onchange');
                            $('#customer_id').val(j.customer_id);	// 上記triggerでセットされるはずだが、このあとのsetInspectionDate()処理に間に合わない場合があるためセットする
                            $('#gen_contents').scrollTop(scr);
                            $('body').css({visibility: 'visible'});
                            
                            var elm = $('#delivery_quantity_'+lineNo);
                            if (!isPageLoad) {  // ページロードでは実行しない （修正モードで値が上書きされてしまうのを避けるため）
                                $('#delivery_price_'+lineNo).val(j.price);
                            if (j.sales_base_cost!=null) 
                                $('#sales_base_cost_'+lineNo).val(j.sales_base_cost);
                            if (!isLocationChange) 
                                $('#location_id_'+lineNo).val(j.loc3===null ? '0' : j.loc3);
                            $('#remarks_'+lineNo).val(j.remarks);

                            // 09iまでは理論在庫を上限としていたが、10iでは受注残がそのままデフォルト納品数となるようにした
                            if (j.rem_qty<0) 
                                j.rem_qty=0;
                            elm.val(j.rem_qty);

                            // 得意先が変わったときはデフォルト検収日をセット
                            if (j.customer_id != custIdCache) {
                            	setInspectionDate();
                            }

                            if (!isPageLoad) {
                                // レート設定
                                setCurrencyRate(false);
                            }

                            // 取引通貨チェック
                            checkCurrency();

                            // 売掛残高と与信限度額の設定
                            onCustomerIdChange();

                            // 税率設定（各明細行）
                            setTaxRate(lineNo);
                        }
                        calcAmount(lineNo);

                        if (" . (!isset($form['gen_validError']) && isset($form['gen_redMode']) ? "true" : "false") . ") {
                            // 赤伝モード
                            elm.val(elm.val()*-1);
                            calcAmount(lineNo);
                            $('#delivery_completed_'+lineNo).attr('checked', false);
                        }
                        elm.get(0).select();    // バーコード入力対応
                      }
                      gen.edit.submitEnabled('" . h($form['gen_readonly']) ."');
                      calcTotalAmount();
                      onListChange();
                    });
            }

            // 得意先が変わったらAjaxで売掛残高と与信限度額を取得する。
            function onCustomerIdChange() {
                if (!gen.util.isNumeric(customerId = $('#customer_id').val())) {
                    $('#receivable_balance').val('');
                    $('#credit_line').val('');
                    return;
                }

                gen.ajax.connect('Delivery_Delivery_AjaxCustomerParam', {customerId : customerId" .
                    (isset($form['delivery_header_id']) && is_numeric($form['delivery_header_id']) ? ",deliveryHeaderId : " . $form['delivery_header_id'] : "") . "}, 
                    function(j) {
                        $('#receivable_balance').val(gen.util.addFigure(j.receivable_balance));
                        $('#credit_line').val(gen.util.addFigure(j.credit_line));
                    });
            }

            // 発送先設定
            function onListChange() {
                var p = {};
                p.received_detail_id = $('#received_detail_id_1').val();
                p.delivery_detail_id = $('#delivery_detail_id_1').val();
                if (p.delivery_detail_id == undefined) p.delivery_detail_id = '';
                gen.ajax.connect('Delivery_Delivery_AjaxReceivedParam', p,
                    function(j) {
                        if (j.status == 'success') {
                            //$('#delivery_customer_id_show').val(j.delivery_customer_no).trigger('onchange');
                            // 上記triggerでは遅延が生じたりカーソル移動に問題が生じるため全て値をセットする
                            $('#delivery_customer_id_show').val(j.delivery_customer_no);
                            $('#delivery_customer_id_sub').val(j.delivery_customer_name);
                            $('#delivery_customer_id').val(j.delivery_customer_id);
                        }
                    });
            }

            // 納品数変更イベント
            function onQtyChagne(lineNo) {
                calcAmount(lineNo);
                calcTotalAmount();
            }

            // 単価変更イベント
            function onPriceChange(lineNo) {
                calcAmount(lineNo);
                calcTotalAmount();
            }

            // 販売原単価変更イベント
            function onBaseCostChange(lineNo) {
                calcAmount(lineNo);
                calcTotalAmount();
            }

            // 金額・販売粗利の計算（各明細行）
            function calcAmount(lineNo) {
                var qty = $('#delivery_quantity_'+lineNo).val();
                var price = $('#delivery_price_'+lineNo).val();
                if (gen.util.isNumeric(qty) && gen.util.isNumeric(price)) {
                    $('#delivery_amount_'+lineNo).html(gen.util.addFigure(gen.util.decCalc(qty, price, '*')));
                    var baseCost = gen.util.delFigure($('#sales_base_cost_'+lineNo).val());
                    if (!gen.util.isNumeric(baseCost)) baseCost = 0;
                    $('#sales_gross_margin_'+lineNo).html(gen.util.addFigure(gen.util.decCalc(gen.util.decCalc(price, baseCost, '-'),qty,'*')));
                } else {
                    $('#delivery_amount_'+lineNo).html('');
                    $('#sales_gross_margin_'+lineNo).html();
                }
            }

            // 合計金額・合計粗利の計算
            function calcTotalAmount() {
               var total = 0;
               $('[id^=delivery_amount_]').each(function(){
                    var amount = gen.util.delFigure(this.innerHTML);
                    if (gen.util.isNumeric(amount)) {
                        total = gen.util.decCalc(total, amount, '+');
                    }
               });
               $('#total_amount').val(gen.util.addFigure(total));

               total = 0;
               $('[id^=sales_gross_margin_]').each(function(){
                    var gm = gen.util.delFigure(this.innerHTML);
                    if (gen.util.isNumeric(gm)) {
                        total = gen.util.decCalc(total, gm, '+');
                    }
               });
               $('#total_gross_margin').val(gen.util.addFigure(total));
            }

            // 税率設定
            function setAllTaxRate() {
                $('[id^=line_no]').each(function(){
                    lineNo = this.innerHTML;
                    setTaxRate(lineNo);
                });
            }

            // 税率設定（各明細行）
            function setTaxRate(lineNo) {
                var deliveryDate = $('#delivery_date').val();
                var inspectionDate = $('#inspection_date').val();
                var receivedDetailId = $('#received_detail_id_'+lineNo).val();

                var p = {
                    received_detail_id: receivedDetailId,
                    delivery_date: deliveryDate,
                    inspection_date: inspectionDate
                };

                gen.ajax.connect('Delivery_Delivery_AjaxTaxRateParam', p,
                    function(j) {
                        if (j.status == 'success' && j.is_currency == false) {
                            $('#tax_rate_'+lineNo).val(j.tax_rate);
                        } else {
                            $('#tax_rate_'+lineNo).val('');
                        }
                        gen.ui.alterDisabled($('#tax_rate_'+lineNo), j.is_currency);
                    });
            }
            
            // 受注コピー
            function copyFromReceived(isAfterCopy) {
                var id = $('#received_header_id').val();
                if (id == '') return;
                if (!isAfterCopy && isPageDataModified && !confirm('" . _g("受注の内容をこの伝票にコピーします。この伝票の現在の入力内容が上書きされます。実行してもよろしいですか？") . "')) return;

                gen.ajax.connect('Delivery_Delivery_AjaxReceivedDetail', {received_header_id : id},
                    function(j) {
                        if (j.status == 'success') {
                            $('#currency_name').val(j.currency_name);
                            $('#remarks_header').val(j.remarks_header);
                            $('#remarks_header_2').val(j.remarks_header_2);
                            $('#remarks_header_3').val(j.remarks_header_3);

                            // 明細行の数が足りるかどうか確認
                            var count = 0;
                            for(var i in j){
                                if (!isNaN(i)) count++;
                            }
                            if ($('#gen_select_list1').val() < count) {
                                // 行が足りないときは増やす
                                gen.edit.changeNumberOfList('" . $form['gen_editActionWithKey'] . "&gen_receivedCopy','list1',count);
                                return;
                            }

                            // いったんすべての行を消す
                            // EditListのすべての行に対する処理（idがline_noで始まるすべてのエレメント、つまり行番号divをたどる）
                            $('[id^=line_no]').each(function(){
                                lineNo = this.innerHTML;
                                gen.edit.editListClearLine('list1', lineNo);
                            });

                            // 受注明細を書き込む
                            $.each(j, function(key, value){
                                if (gen.util.isNumeric(key)) {
                                    v = value.split(':');
                                    $('#received_detail_id_'+key).val(v[0]);
                                    $('#received_detail_id_'+key+'_show').val(v[1]).focus();    // focus() はプレースホルダを消すため
                                    onReceivedIdChange(key, false, false);
                                }
                            });
                        } else {
                            alert('" . _g("受注データの取得に失敗しました。") . "');
                        }
                    }
                );
            }

            // デフォルト検収日のセット
            function setInspectionDate() {
                var custId = $('#customer_id').val();
                if (custId == '') return;
                var deliveryDate = $('#delivery_date').val();
                if (deliveryDate == '') return;

                var p = {customer_id: custId, delivery_date: deliveryDate};

                gen.ajax.connect('Delivery_Delivery_AjaxInspectionDate', p,
                    function(j) {
                        if (j.status == 'success') {
                            $('#inspection_date').val(j.inspection_date);
                        }
                    });
            }

            // レート設定
            function setCurrencyRate(isFirst) {
                var customerId = $('#customer_id').val();
                if (customerId == '') return;
                var deliveryDate = $('#delivery_date').val();
                var inspectionDate = $('#inspection_date').val();

                var p = {
                    customer_id: customerId,
                    delivery_date: deliveryDate,
                    inspection_date: inspectionDate
                };

                gen.ajax.connect('Delivery_Delivery_AjaxCurrencyRateParam', p,
                    function(j) {
                        if (j.status == 'success') {
                            gen.ui.alterDisabled($('#foreign_currency_rate'), false);
                            $('#foreign_currency_rate').css('background-color','#ffffff');
                            if (!isFirst) {
                                $('#foreign_currency_rate').val(j.foreign_currency_rate);
                            }
                        } else {
                            gen.ui.alterDisabled($('#foreign_currency_rate'), true);
                            $('#foreign_currency_rate').css('background-color','#cccccc');
                            $('#foreign_currency_rate').val('');
                        }
                    });
            }
            
            // レートチェック
            function checkCurrency() {
                var p = {
                    customer_id: $('#customer_id').val(),
                    " . (isset($form['delivery_header_id']) && is_numeric($form['delivery_header_id']) ? "delivery_header_id : {$form['delivery_header_id']}," : "") . "
                    received_header_id : $('#received_header_id').val()
                };

                gen.ajax.connect('Delivery_Delivery_AjaxCurrencyCheck', p,
                    function(j) {
                        if (j.currency_flag == 1) {
                            alert('" . _g('取引通貨が変更されています。納品単価のレート換算にご注意ください。') . "');
                        }
                    });
            }

            // 登録
            function beforeEntry() {
                // EditListのすべての行に対する処理（idがline_noで始まるすべてのエレメント、つまり行番号divをたどる）
                var dq;
                var cnt = 0;
                var error = false;
                var stockError = false;
                var stockMsg = '';
                var lineNo;
                var recId;
                var recIdArr = [];
                var cmp = 0;
                var checkData = '';
                $('[id^=line_no]').each(function(){
                    lineNo = this.innerHTML;
                    dq = $('#delivery_quantity_'+lineNo);
                    if ((recId = $('#received_detail_id_'+lineNo).val()) != '') {
                        if (recIdArr[recId]) {
                           alert('" . _g("同じ受注が複数回指定されています。") . "');
                           $('#received_detail_id_'+lineNo+'_show').focus();
                           error = true;
                           return false;    // eachを抜ける
                        }
                        recIdArr[recId] = true;
                        if (!gen.util.isNumeric(dq.val())) {
                           alert('" . _g("納品数量には数字を入力してください。") . "');
                           dq.get(0).focus();
                           error = true;
                           return false;    // eachを抜ける
                        }
                        sq = $('#stock_quantity_'+lineNo).html();
                        if (sq != '' && eval(gen.util.delFigure(sq)) < eval(dq.val())) {
                            stockError = true;
                            if (stockMsg != '') stockMsg = stockMsg + ', '
                            stockMsg = stockMsg + '" . _g("%d行目") . "'.replace('%d',lineNo);
                        }
                        if ($('#delivery_completed_'+lineNo).is(':checked')) {
                            cmp = 1;
                        } else {
                            cmp = 0;
                        }
                        checkData = checkData + lineNo + ':' + $('#received_detail_id_'+lineNo).val() + ':' + $('#delivery_quantity_'+lineNo).val() + ':' + cmp + ';';
                        cnt++;
                    }
                });
                if (error) return;
                if (stockError) {
                    if (!confirm('" . _g("この登録により、在庫数がマイナスになりますがよろしいですか？") . "\\n' + '(' + stockMsg + ')')) {
                        error = true;
                        return false;
                    }
                }
                if (error) return;
                if (cnt==0) {
                    alert('" . _g("登録するデータがありません。") . "'); return;
                }

                // 与信限度額チェック
                var cl = gen.util.delFigure($('#credit_line').val());
                if (gen.util.isNumeric(cl)) {
                    var rb = gen.util.nz(gen.util.delFigure($('#receivable_balance').val()));
                    var ta = gen.util.nz(gen.util.delFigure($('#total_amount').val()));
                    if (gen.util.decCalc(rb, ta, '+') > cl) {
                        if (!window.confirm('" . _g("売掛残高と今回納品額の合計が与信限度額をオーバーしていますが、このまま登録してもよろしいですか？") . "')) {
                            return;
                       }
                    }
                }

                " . (isset($form['delivery_header_id']) && is_numeric($form['delivery_header_id']) && !isset($form['gen_redMode']) ? "
                var p = {delivery_header_id: {$form['delivery_header_id']}, delivery_data: checkData};
                gen.ajax.connect('Delivery_Delivery_AjaxDeliveryParam', p,
                    function(j) {
                        if (j.status == 'success' && j.check == 'error') {
                            if (!confirm('" . _g("納品合計数が受注数に満たなくなりますが、受注を完了してよろしいですか？") . "\\n' + '" . 
                                _g("完了しない場合は「完了」のチェックを外してください。") . "\\n' + '(' + j.message + ')')) {
                                error = true;
                                return false;
                            }
                        }
                        document.forms[0].submit();
                    });
                " : "document.forms[0].submit();") . "
            }
            
            // 完了フラグの登録（請求済のためレコードを登録できないケース用）
            function completedFlagRegist() {
                var p = {};
                $('[id^=line_no]').each(function(){
                    lineNo = this.innerHTML;
                    id = $('#delivery_detail_id_'+lineNo).val();
                    if (id !== undefined) {
                        cmp = 0;
                        if ($('#delivery_completed_'+lineNo).is(':checked')) {
                            cmp = 1;
                        }
                        p[id] = cmp;
                    }
                });
                gen.ajax.connect('Delivery_Delivery_AjaxCompletedFlagRegist', p,
                    function(j) {
                        if (j.status == 'success') {
                            alert('" . _g("「完了」状態を登録しました。") . "');
                        } else {
                            alert('" . _g("登録に失敗しました。") . "');
                        }
                    });
            }
        ";

        $form['gen_beforeEntryScript_noEscape'] = "beforeEntry()";

        // 赤伝発行
        if (is_numeric(@$form['delivery_header_id']) && !isset($form['gen_redMode'])) {
            $form['gen_message_noEscape'] .= "<a href='javascript:onRedClick()'>" . _g("赤伝票を登録する") . "</a>";
            $form['gen_javascript_noEscape'] .= "
                function onRedClick() {
                    var p = '&delivery_header_id=" . h($form['delivery_header_id']) . "';
                    p += '&gen_redMode=true';
                    p += '&gen_record_copy=true';
                    location.href='index.php?action=Delivery_Delivery_Edit' + p;
                }
            ";
        }
        if (isset($form['gen_redMode'])) {
            // 赤伝モードで数字を反転させる処理はJSの中で行っている
            unset($form['delivery_no']);
            $form['delivery_date'] = date('Y-m-d');
            if (isset($form['inspection_date']) && Gen_String::isDateString($form['inspection_date'])) {
                $form['inspection_date'] = date('Y-m-d');
            }
            $form['gen_entryAction'] .= "&gen_redMode";    // 登録後、Listへ戻るため
        }

        // ロケセレクタ選択肢
        $query = "select location_id, location_name from location_master order by location_code";
        $option_location_group = $gen_db->getHtmlOptionArray($query, false, array("0" => _g(GEN_DEFAULT_LOCATION_NAME)));

        // 修正モードでの在庫数テーブルの取得 （EditListのSQLで使用）
        if (isset($form['delivery_header_id']) && is_numeric($form['delivery_header_id'])) {
            $query = "create temp table temp_delivery_stock (delivery_detail_id int, stock_quantity numeric)";
            $gen_db->query($query);
            $query = "
            select
                received_detail_id
                ,delivery_detail_id
                ,location_id
            from
                delivery_detail
            where
                delivery_header_id = '{$form['delivery_header_id']}'
            ";
            $arr = $gen_db->getArray($query);
            foreach ($arr as $row) {
                $res = Logic_Delivery::getDeliveryData($row['received_detail_id'], $row['delivery_detail_id'], $row['location_id']);
                $qty = $res['stock_quantity'];
                $query = "insert into temp_delivery_stock (delivery_detail_id, stock_quantity) values ('{$row['delivery_detail_id']}','{$qty}')";
                $gen_db->query($query);
            }
        }

        $form['gen_editControlArray'] = array(
            array(
                'label' => _g('受注参照'),
                'type' => 'dropdown',
                'name' => 'received_header_id',
                'value' => @$form['received_header_id'],
                'size' => '11',
                'dropdownCategory' => 'received_header',
                'onChange_noEscape' => 'copyFromReceived()',
                'readonly' => isset($form['delivery_header_id']) && $form['delivery_header_id'] != '',
                'hidePin' => true,
                'helpText_noEscape' => _g("受注を選択すると、その内容がこの納品伝票にコピーされます。") . "<br><br>" . _g("修正モードでは使用できません。"),
            ),
            array(
                'label' => _g('納品書番号'),
                'type' => 'textbox',
                'name' => 'delivery_no',
                'value' => @$form['delivery_no'],
                'size' => '12',
                'readonly' => (isset($form['delivery_header_id'])),
                'ime' => 'off',
                'hidePin' => true,
                'helpText_noEscape' => _g("自動的に採番されますので、指定する必要はありません。") . "<br>" .
                _g("修正モードでは番号を変更することはできません。"),
            ),
            array(
                'label' => _g('納品日'),
                'type' => 'calendar',
                'name' => 'delivery_date',
                'value' => @$form['delivery_date'],
                'onChange_noEscape' => 'setInspectionDate();setCurrencyRate(false);setAllTaxRate()',
                'size' => '8',
                'helpText_noEscape' => _g("出荷日を入力します。") . "<br>" . _g("この日付で在庫に計上されます。また、納品書にこの日付が記載されます。") . "<br>" . _g("また、自社情報マスタの「売上計上基準」が「納品日」の場合、この日付を基準として請求書が発行され、売掛管理が行われます。"),
                'require' => true,
            ),
            array(
                'label' => _g('検収日'),
                'type' => 'calendar',
                'name' => 'inspection_date',
                'value' => @$form['inspection_date'],
                'onChange_noEscape' => 'setCurrencyRate(false);setAllTaxRate()',
                'size' => '8',
                // 検収日は、ピンどめしても品目を選択した時点で自動計算された値で上書きされてしまい意味がない。
                // そのためピンをなくすことにした。 ag.cgi?page=ProjectDocView&ppid=1516&pbid=189235
                'hidePin' => true,
                'helpText_noEscape' => _g("検収日を入力します。") . "<br>" . _g("自社情報マスタの「売上計上基準」が「検収日」の場合、この日付を基準として請求書が発行され、売掛管理が行われます。") . '<br><br>' .
                _g('取引先マスタの「検収リードタイム」が設定されている場合、納品日に検収リードタイムを足した日付（休日も考慮します）が自動設定されます。'),
            ),
            array(
                'label' => _g('得意先'),
                'type' => 'dropdown',
                'name' => 'customer_id',
                'value' => @$form['customer_id'],
                'size' => '12',
                'subSize' => '20',
                'dropdownCategory' => 'customer',
                'readonly' => true,
                'helpText_noEscape' => _g('受注明細を選択すると自動的にセットされます。変更はできません。ここに得意先が表示されている場合、受注明細ではその得意先に対する受注だけが選択できます。')
            ),
            array(
                'label' => _g("取引通貨"),
                'type' => 'textbox',
                'name' => "currency_name",
                'value' => @$form['currency_name'],
                'size' => '8',
                'readonly' => 'true',
                'helpText_noEscape' => _g("取引先マスタで設定した取引通貨が表示されます。「納品単価」はこの取引通貨で設定してください。") . "<br><br>" . _g("なお、レートは納品日時点のレート（「為替レートマスタ」）が適用されます。"),
            ),
            array(
                'label' => _g('発送先'),
                'type' => 'dropdown',
                'name' => 'delivery_customer_id',
                'value' => @$form['delivery_customer_id'],
                'size' => '12',
                'subSize' => '20',
                'dropdownCategory' => 'delivery_customer',
                'helpText_noEscape' => _g('納品明細1行目の発送先が表示されます。')
            ),
            array(
                'label' => _g('レート'),
                'type' => 'textbox',
                'name' => 'foreign_currency_rate',
                'value' => @$form['foreign_currency_rate'],
                'ime' => 'off',
                'size' => '10',
                'helpText_noEscape' => _g('外貨取引の場合、適用するレートを入力します。') . '<br>' . _g('「納品日」を基準とした[為替レートマスタ]の適用レートが自動で表示されます。 ') . '<br>' .
                _g('ただし、[自社情報]の「売上計上基準」が“検収日”の場合、「検収日」の入力があった時点で「検収日」を基準とした[為替レートマスタ]の適用レートが表示されます。') . '<br>' .
                _g('「納品日」あるいは「検収日」の日付を変更すると、「レート」の表示も更新されますのでご注意ください。 '),
            ),
            array(
                'label' => _g('合計金額'),
                'type' => 'textbox',
                'name' => 'total_amount',
                'value' => '',
                'size' => '10',
                'style' => 'text-align:right',
                'readonly' => true,
            ),
            array(
                'label' => _g('売掛残高'),
                'type' => 'textbox',
                'name' => 'receivable_balance',
                'value' => '',
                'size' => '10',
                'style' => 'text-align:right',
                'readonly' => true,
                'helpText_noEscape' => _g("この得意先に対する売掛残高（納品ベース）が表示されます。この納品の金額は含みません。") . '<br><br>'
                . _g("この得意先に請求先が設定されているときは、請求先の売掛残高が表示されます。") . '<br><br>'
                . _g("「売掛残高表」を納品ベースで発行したときの売掛残高と同じです（詳しくは売掛残高表画面のページヒントをご覧ください）。") . '<br>'
                . _g("ただし、修正モードでは、売掛残高表の金額から今回納品額を引いた金額になります。"),
            ),
            array(
                'label' => _g('合計粗利'),
                'type' => 'textbox',
                'name' => 'total_gross_margin',
                'value' => '',
                'size' => '10',
                'style' => 'text-align:right',
                'readonly' => true,
            ),
            array(
                'label' => _g('与信限度額'),
                'type' => 'textbox',
                'name' => 'credit_line',
                'value' => '',
                'size' => '10',
                'style' => 'text-align:right',
                'readonly' => true,
                'helpText_noEscape' => _g("この得意先の与信限度額が表示されます。") . '<br>'
                . _g("与信限度額は、取引先マスタで設定することができます。") . '<br>'
                . _g("この得意先に請求先が設定されているときは、請求先の与信限度額が表示されます。"),
            ),
            array(
                'label' => _g('担当者(自社)'),
                'type' => 'textbox',
                'name' => 'person_in_charge',
                'value' => @$form['person_in_charge'],
                'size' => '12',
                'ime' => 'on',
                'helpText_noEscape' => _g('納品書に記載されます。必要なければ空欄にしてください。')
            ),
            array(
                'label' => _g('納品備考1'),
                'type' => 'textbox',
                'name' => 'remarks_header',
                'value' => @$form['remarks_header'],
                'size' => '25',
                'ime' => 'on',
            ),
            array(
                'label' => _g('納品備考2'),
                'type' => 'textbox',
                'name' => 'remarks_header_2',
                'value' => @$form['remarks_header_2'],
                'size' => '25',
                'ime' => 'on',
            ),
            array(
                'label' => _g('納品備考3'),
                'type' => 'textbox',
                'name' => 'remarks_header_3',
                'value' => @$form['remarks_header_3'],
                'size' => '25',
                'ime' => 'on',
            ),

            // ********** List **********
            array(
                'type' => "list",
                'listId' => 'list1', // リストのID。ページ内に複数リストがある場合、ユニークになるようにすること
                'rowCount' => 2, // 1セルに格納するコントロールの数（1セルの行数）
                'rowColorCondition' => array(
                    "#cccccc" => "'[completed]'=='" . _g("完") . "'", // 完了行（グレー）
                ),
                'keyColumn' => 'delivery_detail_id', // 明細行のキーとなるカラム
                'onChange_noEscape' => 'onListChange()',
                'query' => // Listデータを取得するSQL。 EditBaseで実行され、結果配列が'data'という名前で格納される
                isset($form['delivery_header_id']) && is_numeric($form['delivery_header_id']) ? "
                        select
                            delivery_detail.*
                            ,received_number || ' / ' || received_detail.line_no as received_line_no
                            ,received_detail.dead_line
                            ,item_master.item_name
                            ,item_master.item_code
                            ,item_master.measure
                            ,location_id
                            ,received_quantity
                            ,coalesce(received_quantity,0) - coalesce(dq,0) as remained_quantity
                            ,coalesce(seiban_stock_quantity,0) + coalesce(free_stock_quantity,0) as delivery_quantity
                            ,case when delivery_completed then 'true' else '' end as delivery_completed
                                -- 登録時の警告処理で、在庫数が表示されていなければダミー品目と判断している
                            ,case when dummy_item then null else stock_quantity end as stock_quantity
                            ,case when delivery_header.foreign_currency_id is null then delivery_price else foreign_currency_delivery_price end as delivery_price
                            ,case when delivery_header.foreign_currency_id is null then delivery_price else foreign_currency_delivery_price end * delivery_quantity as delivery_amount
                            ,case when delivery_header.foreign_currency_id is null then delivery_detail.sales_base_cost else delivery_detail.foreign_currency_sales_base_cost end as sales_base_cost
                            ,(case when delivery_header.foreign_currency_id is null then delivery_detail.delivery_amount else delivery_detail.foreign_currency_delivery_amount end
                                - coalesce(case when delivery_header.foreign_currency_id is null then delivery_detail.sales_base_cost_total else delivery_detail.foreign_currency_sales_base_cost_total end,0))
                                as sales_gross_margin
                        from
                            delivery_detail
                            left join delivery_header on delivery_detail.delivery_header_id = delivery_header.delivery_header_id
                            left join received_detail on delivery_detail.received_detail_id = received_detail.received_detail_id
                            left join received_header on received_detail.received_header_id = received_header.received_header_id
                            left join item_master on received_detail.item_id = item_master.item_id
                            left join (select received_detail_id, sum(delivery_quantity) as dq from delivery_detail group by received_detail_id) as t_del on received_detail.received_detail_id = t_del.received_detail_id
                            left join temp_delivery_stock on delivery_detail.delivery_detail_id = temp_delivery_stock.delivery_detail_id
                        where
                            delivery_detail.delivery_header_id = '{$form['delivery_header_id']}'
                        order by
                            delivery_detail.line_no
                        " : "",
                'controls' => array(
                    array(
                        'label' => _g('受注番号 / 行'),
                        'type' => 'div',
                        'name' => 'received_line_no',
                        'style' => 'text-align:center',
                        'size' => '10',
                    ),
                    array(
                        'label' => _g('受注納期'),
                        'type' => 'div',
                        'name' => 'dead_line',
                        'style' => 'text-align:center',
                        'size' => '10',
                    ),
                    array(
                        'label' => _g('受注製番'),
                        'type' => 'dropdown',
                        'name' => 'received_detail_id',
                        // 得意先が決まっている場合、その得意先の受注だけを表示する。
                        // （新規登録の場合はその伝票の最初の受注明細を選択した時点で自動的に得意先がセットされる。
                        //　 修正モードでは最初からセットされている）
                        // ドロップダウンボタンを押したときのcustomer_id_show（取引先コード）の値で判断する。
                        // customer_id ではなく customer_id_show で判断しているのは、伝票の最初の製番を選んですぐ次の
                        // 製番を選択した時の誤動作を避けるため。
                        // 最初の製番を選択したときに、(1) JSで customer_id_show をセット (2) Ajaxでcustomer_idを取得してセット
                        // という動作をするが、(1) と (2) の間に次の受注明細を選択すると customer_id が取れない。
                        'dropdownCategory' => 'received_detail_customer',
                        'dropdownParam' => '[customer_id_show]',
                        'size' => '10',
                        'width' => '130',
                        'require' => true,
                        'onChange_noEscape' => 'onReceivedIdChange([gen_line_no], false, false)',
                        'placeholder' => _g('受注製番'),
                    ),
                    array(
                        'label' => _g('出庫ロケーション'),
                        'type' => 'select',
                        'name' => 'location_id',
                        'style' => 'width:130px',
                        'options' => $option_location_group,
                        'onChange_noEscape' => 'onReceivedIdChange([gen_line_no], true, false)',
                        'helpText_noEscape' => _g('品目を出庫するロケーションを指定します。新規登録の場合、デフォルト値として品目マスタの「標準ロケーション（完成）」の値が表示されます。'),
                    ),
                    array(
                        'label' => _g('品目コード'),
                        'type' => 'div',
                        'name' => 'item_code',
                        'size' => '14',
                    ),
                    array(
                        'label' => _g('品目名'),
                        'type' => 'div',
                        'name' => 'item_name',
                        'size' => '14',
                    ),
                    array(
                        'label' => _g('受注数'),
                        'type' => 'div',
                        'name' => 'received_quantity',
                        'style' => 'text-align:right',
                        'size' => '6',
                        'helpText_noEscape' => _g('受注数が表示されます。')
                    ),
                    array(
                        'label' => _g('残'),
                        'type' => 'div',
                        'name' => 'remained_quantity',
                        'style' => 'text-align:right',
                        'size' => '6',
                        'helpText_noEscape' => _g('受注数から、これまでに納品済みの数量を引いた数です。'),
                    ),
                    array(
                        'label' => _g('今回納品数'),
                        'type' => 'textbox',
                        'name' => 'delivery_quantity',
                        'ime' => 'off',
                        'size' => '6',
                        'style' => "text-align:right",
                        'require' => true,
                        'onChange_noEscape' => "onQtyChagne([gen_line_no])",
                    ),
                    //  ホントは理論在庫ではなく有効在庫を表示したいところだが、ロケ別では有効在庫を取得できない
                    array(
                        'label' => _g('理論在庫数'),
                        'type' => 'div',
                        'name' => 'stock_quantity',
                        'style' => 'text-align:right',
                        'size' => '6',
                        'numberFormat' => '', // 桁区切り
                        'helpText_noEscape' => _g('在庫リストで日付を空欄にしたときのロケーション別の理論在庫数と同じです。ただし、登録済みの行は「今回納品数」を加味しない数量を表示します。'),
                    ),
                    array(
                        'label' => _g('納品単価'),
                        'type' => 'textbox',
                        'name' => 'delivery_price',
                        'ime' => 'off',
                        'size' => '6',
                        'style' => "text-align:right",
                        'require' => true,
                        'onChange_noEscape' => "onPriceChange([gen_line_no])",
                    ),
                    array(
                        'label' => _g('単位'),
                        'type' => 'div',
                        'name' => 'measure',
                        'style' => "text-align:right",
                        'size' => '6',
                        'helpText_noEscape' => _g('品目マスタ「管理単位」です。')
                    ),
                    array(
                        'label' => _g('販売原単価'),
                        'type' => 'textbox',
                        'name' => 'sales_base_cost',
                        'ime' => 'off',
                        'size' => '6',
                        'style' => "text-align:right",
                        'onChange_noEscape' => 'onBaseCostChange([gen_line_no])',
                        'helpText_noEscape' => _g("この製品の販売原価（単価）を指定します。デフォルトは受注登録画面の「販売原単価」です。") . '<br>' .
                        _g("ここで登録した販売原価は、納品画面（リスト）や販売レポートに表示されます。") .
                        _g("詳細については、受注登録画面の「販売原価」のチップヘルプをご参照ください。"),
                    ),
                    array(
                        'label' => _g('税率'),
                        'type' => 'textbox',
                        'name' => 'tax_rate',
                        'ime' => 'off',
                        'size' => '6',
                        'style' => "text-align:right",
                    ),
                    array(
                        'label' => _g('金額'),
                        'type' => 'div',
                        'name' => 'delivery_amount',
                        'size' => '6',
                        'numberFormat' => '', // 桁区切り
                        'style' => 'text-align:right',
                        'helpText_noEscape' => _g('今回納品数 × 納品単価 で計算されます。') . '<br><br>'
                        . _g('小数点以下の数値がある場合、入力した時点では小数点以下まで表示されますが、登録時に取引先マスタの設定（端数処理）にしたがって整数に丸められます。'),
                    ),
                    array(
                        'label' => _g('販売粗利'),
                        'type' => 'div',
                        'name' => 'sales_gross_margin',
                        'size' => '6',
                        'numberFormat' => '', // 桁区切り
                        'style' => "text-align:right",
                        'helpText_noEscape' => _g('「(納品単価 - 販売原単価) × 数量)」で計算されます。'),
                    ),
                    array(
                        'label' => _g('納品明細備考'),
                        'type' => 'textbox',
                        'name' => 'remarks',
                        'size' => '12',
                        'width' => '125',
                        'focusZoom' => array('left', 400, 25),     // フォーカス時のサイズ拡張(方向,width,height)
                    ),
                    array(
                        'label' => _g('ロット番号'),
                        'type' => 'textbox',
                        'name' => 'use_lot_no',
                        'size' => '12',
                        'placeholder' => _g('ロット番号'),
                        'helpText_noEscape' => _g('MRP/製番品目で簡易的なロット管理（トレーサビリティ）を行う場合に、出荷した品目の製造・購買ロット番号を入力します。')
                        . _g('複数のロットがある場合はカンマ区切りで入力してください。ロット管理を必要としない場合は、入力の必要はありません。')  . '<br><br>'
                        . _g('上記の「製造・購買ロット番号」とは、内製品であれば製造実績画面の製造ロット番号、発注品であれば受入画面の購買ロット番号を指します。')
                        . _g('この登録により、出荷した品目の製造ロットや購買ロットを調べることができるようになり、トレーサビリティを実現できます。')  . '<br><br>'
                        . _g('ロット品目の場合は、この項目を使用するのではなく、受注画面でロット引当処理を行うことをお勧めします。そうすれば、ロット別在庫数に反映されるからです。') . '<br>'
                        . _g('この項目にロット番号を入力しても在庫数が変化することはありません。この項目は、あくまで納品と製造実績・購買を結びつけるためだけのものです。')
                    ),
                    // 完了フラグ。ここでオンにしなくても、受注数 >= 納品数 ならDB登録時に自動的にオンになる
                    // 受注数未達でも完了とみなしたいときは手動でオンにする
                    array(
                        'label' => _g('完了'),
                        'type' => 'checkbox', // チェックボックス
                        'name' => 'delivery_completed',
                        'onvalue' => 'true', // trueのときの値。デフォルト値ではない
                        'helpText_noEscape' => _g("このチェックをオンにすると、納品数が受注数に満たなくても完納したものとして扱われます。") .
                        _g("納品数が受注数と同じか上回っている場合、登録時に自動的にオンになります。"),
                    ),
                    array(
                        'type' => 'literal',
                    ),
                ),
            ),
        );
    }

}
