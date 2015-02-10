<?php

require_once("Model.class.php");

@define('ROW_NUM', 10);

class Partner_Payment_Edit extends Base_EditBase
{

    function convert($converter, &$form)
    {
        $converter->nullBlankToValue('payment_date', date("Y-m-d"));
    }

    // 既存データSQL実行前に処理
    function setQueryParam(&$form)
    {
        $this->keyColumn = 'payment_id';
        $this->selectQuery = "
             select
                payment.*
                ,case when foreign_currency_id is null then amount else foreign_currency_amount end as amount
                ,case when foreign_currency_id is null then adjust_amount else foreign_currency_adjust_amount end as adjust_amount

                ,coalesce(payment.record_update_date, payment.record_create_date) as gen_last_update
                ,coalesce(payment.record_updater, payment.record_creator) as gen_last_updater
             from
                payment
                inner join customer_master on payment.customer_id = customer_master.customer_id
            [Where]
        ";

        // データロックの判断基準となるフィールドを指定
        $form["gen_buyDateLockFieldArray"] = array("payment_date");
    }

    // 既存データSQL実行後に処理
    // 画面表示のための設定
    function setViewParam(&$form)
    {
        $this->modelName = "Partner_Payment_Model";

        $form['gen_pageTitle'] = _g("支払登録");
        $form['gen_entryAction'] = "Partner_Payment_Entry";
        $form['gen_listAction'] = "Partner_Payment_List";
        $form['gen_onLoad_noEscape'] = "";
        $form['gen_pageHelp'] = _g("支払登録");

        $customerId = "";
        $paymentDate = "";
        $foreignCurrencyRate = "";
        if (isset($form['customer_id']))
            $customerId = $form['customer_id'];
        if (isset($form['payment_date']))
            $paymentDate = $form['payment_date'];
        if (isset($form['foreign_currency_rate']))
            $foreignCurrencyRate = $form['foreign_currency_rate'];

        $isReload = (isset($form['gen_validError']) && $form['gen_validError']) || isset($form['gen_editReload']);

        if (!isset($form['payment_id']) && !$isReload) {
            $paymentDate = date('Y-m-d');
            $foreignCurrencyRate = "";
        }

        $form['gen_focus_element_id'] = "customer_id_show";

        // 入力履歴ボタンを表示しない。このボタンは複数行表示（controlArrayのnameがSQLのカラムと一致しないケース）に対応できないため。
        $form['gen_hideHistorys'] = true;

        $isFirst = false;
        if (isset($form['customer_id'])) {
            $form['gen_onLoad_noEscape'] = "onLoadForEdit()";
            $isFirst = true;
        }
        $form['gen_beforeEntryScript_noEscape'] = "beforeEntryCheck()";

        $form['gen_javascript_noEscape'] = "
            function onLoadForEdit() {
                onCustomerChange();
                onAmountChange('amount','total');
                onAmountChange('adjust_amount','adjTotal');
                showAfterAdjustAmount();
            }

            function onCustomerChange() {
                var p = { customer_id : $('#customer_id').val() };
                $('#currency_name').val('');
                " . ($isReload || $isFirst ? "" : "$('#foreign_currency_rate').val('');") . "
                gen.ajax.connect('Partner_Payment_AjaxCustomerHistory', p,
                    function(j) {
                        if (j.status == 'success') {
                            if (j.currency_id == '-1') {
                                gen.ui.alterDisabled($('#foreign_currency_rate'), true);
                                $('#foreign_currency_rate').val('');
                            } else {
                                gen.ui.alterDisabled($('#foreign_currency_rate'), false);
                                " . ($isReload || $isFirst ? "" : "$('#foreign_currency_rate').val(j.rate);") . "
                            }
                            $('#currency_name').val(j.currency_name);
                            var str = '<table style=\"table-layout:fixed; width:550px\">'+
                                '<tr bgcolor=\"#cccccc\">'+
                                '<td width=\"80px\" align=center>" . _g('受入日') . "</td>'+
                                '<td width=\"80px\" align=center>" . _g('検収日') . "</td>'+
                                '<td width=\"80px\" align=center>" . _g('金額（税込）') . "</td>'+
                                '<td width=\"80px\" align=center>" . _g('支払予定日') . "</td>'+
                                '<td width=\"80px\" align=center>" . _g('オーダー番号') . "</td>'+
                                '<td width=\"150px\" align=center>" . _g('品目名') . "</td>'+
                                '</tr>';
                            for(var i=0;i<j.recentAccept.length;i++) {
                                date1 = gen.util.escape(j.recentAccept[i]['inspection_date']);
                                if (date1==null || date1=='null') date1='';
                                date2 = gen.util.escape(j.recentAccept[i]['payment_date']);
                                if (date2==null || date2=='null') date2='';
                                str += '<tr>';
                                str += '<td align=center>' + gen.util.escape(j.recentAccept[i]['accepted_date']) + '</td>';
                                str += '<td align=center>' + date1 + '</td>';
                                str += '<td align=right>' + gen.util.addFigure(gen.util.escape(j.recentAccept[i]['accepted_with_tax'])) + '</td>';
                                str += '<td align=center>' + date2 + '</td>';
                                str += '<td align=left>' + gen.util.escape(j.recentAccept[i]['order_no']) + '</td>';
                                str += '<td align=left>' + gen.util.escape(j.recentAccept[i]['item_name']) + '</td>';
                                str += '</tr>';
                            }
                            str += '</table>';
                            $('#recentAccept').html(str);

                            str = '<table style=\"table-layout:fixed; width:390px\">'+
                              '<tr bgcolor=\"#cccccc\">'+
                              '<td width=\"80px\" align=center>" . _g('支払日') . "</td>'+
                              '<td width=\"80px\" align=center>" . _g('支払額') . "</td>'+
                              '<td width=\"80px\" align=center>" . _g('支払方法') . "</td>'+
                              '<td width=\"200px\" align=left>" . _g('支払備考') . "</td>'+
                              '</tr>';
                            for(var i=0;i<j.recentPayment.length;i++) {
                                str += '<tr>';
                                str += '<td align=center>' + j.recentPayment[i]['payment_date'] + '</td>';
                                str += '<td align=right>' + gen.util.addFigure(j.recentPayment[i]['payment_with_adjust']) + '</td>';
                                str += '<td align=center>' + j.recentPayment[i]['way_of_payment_show'] + '</td>';
                                str += '<td align=left>' + j.recentPayment[i]['remarks'] + '</td>';
                                str += '</tr>';
                            }
                            str += '</table>';
                            $('#recentPayment').html(str);
                        }
                    }
                );
            }

            function onAmountChange() {
                showAfterAdjustAmount();
                var total = 0;
                var amount;
                $('#amount_total').val('');
                $('[id^=amount_]').each(function(){
                    if (this.id == 'amount_total') {
                        return true;
                    }
                    amount = gen.util.fullNumToHalfNum(this.value);
                    if (gen.util.isNumeric(amount)) {
                        total = gen.util.decCalc(total, amount, '+');
                    }
                });
                $('#amount_total').css('text-align','right').val(gen.util.addFigure(total));
            }

            function onAdjustChange() {
                showAfterAdjustAmount();
                var total = 0;
                var amount;
                $('[id^=adjust_amount_]').each(function(){
                    if (this.id == 'adjust_amount_total') {
                        return true;
                    }
                    amount = gen.util.fullNumToHalfNum(this.value);
                    if (gen.util.isNumeric(amount)) {
                        total = gen.util.decCalc(total, amount, '+');
                    }
                });
                $('#adjust_amount_total').css('text-align','right').val(gen.util.addFigure(total));
            }
            
            function showAfterAdjustAmount() {
                var no, amt, adj;
                if ($('#amount').length > 0) {
                    showAfterSub('');     // 編集モード
                } else {
                    $('[id^=amount_]').each(function(){
                        no = this.id.substr(7);
                        if (gen.util.isNumeric(no)) {
                            showAfterSub('_' + no); 
                        }
                    });
                }
            }
            
            function showAfterSub(afterfix) {
                amt = gen.util.fullNumToHalfNum($('#amount' + afterfix).val());
                adj = gen.util.fullNumToHalfNum($('#adjust_amount' + afterfix).val());
                if (amt == '' && adj == '') {
                    $('#after_adjust_amount' + afterfix).html('');
                    return;
                }
                if (!gen.util.isNumeric(amt)) {
                    amt = 0;
                }
                if (!gen.util.isNumeric(adj)) {
                    adj = 0;
                }
                $('#after_adjust_amount' + afterfix).html(gen.util.addFigure(gen.util.decCalc(amt, adj, '+')));
            }

            function beforeEntryCheck() {
                var exist = false;
                $('[id^=amount]').each(function(){
                    if (this.id != 'amount_total' && this.id != 'adjust_amount_total' && this.type == 'text' && this.value != '') exist = true;
                });
                if (exist) {
                    document.forms[0].submit()
                } else {
                    alert('" . _g("支払データを入力してください。") . "');
                }
            }
        ";

        $msg1 = _g("発注先");
        $msg2 = _g("取引通貨");
        $msg3 = _g("日付");
        $msg4 = _g("レート");

        // 発注先コントロール作成
        $html_customer_id = Gen_String::makeDropdownHtml(
            array(
                'label' => "",
                'name' => 'customer_id',
                'value' => @$form['customer_id'],
                'size' => '11',
                'subSize' => '20',
                'dropdownCategory' => 'partner',
                'onChange_noEscape' => "onCustomerChange()",
                'require' => true,
                'pinForm' => (!isset($form['payment_id']) ? "Partner_Payment_Edit" : ""),
                'genPins' => @$form['gen_pins'],
            )
        );

        // 日付コントロール作成
        $html_payment_date = Gen_String::makeCalendarHtml(
            array(
                'label' => "",
                'name' => "payment_date",
                'value' => @$form['payment_date'],
                'size' => '85',
                'require' => true,
                'pinForm' => (!isset($form['payment_id']) ? "Partner_Payment_Edit" : ""),
                'genPins' => @$form['gen_pins'],
            )
        );

        $form['gen_message_noEscape'] = "
            <table cellspacing='0' cellpadding='0' border='0'>
                <tr style='height:25px;'>
                    <td style='text-align:right; font-weight:bold;'>{$msg1}</td>
                    <td style='width:20px;'></td>
                    <td style='text-align:left;'>{$html_customer_id}</td>
                    <td width='15'></td>
                    <td style='text-align:right; font-weight:bold;'>{$msg2}</td>
                    <td style='width:20px;'></td>
                    <td style='text-align:left'><input id='currency_name' style='background-color:#cccccc; width:40px;' type='textbox' tabIndex='-1' readonly></td>
                </tr>

                <tr style='height:25px;'>
                    <td style='text-align:right; font-weight:bold;'>{$msg3}</td>
                    <td style='width:20px;'></td>
                    <td style='text-align:left'>{$html_payment_date}</td>
                    <td width='15'></td>
                    <td style='text-align:right; font-weight:bold;'>{$msg4}</td>
                    <td style='width:20px;'></td>
                    <td style='text-align:left'><input id='foreign_currency_rate' name='foreign_currency_rate' style='width:80px; ime-mode:inactive;' type='textbox' value='" . h($foreignCurrencyRate) . "'></td>
                </tr>
            </table>

            <br>
            <table>
            <tr>
                <td>■" . _g('この発注先からの最近の受入') . "
                    <table><tr><td style='background-color:#cccccc;'>
                    <div id='recentAccept' style='width:360px; height:150px; background-color:#ffffff; overflow:scroll'>
                    </div>
                    </td>
                    </tr>
                    </table>
                </td>
                <td>■" . _g('この発注先への最近の支払') . "
                    <table><tr><td style='background-color:#cccccc;'>
                    <div id='recentPayment' style='width:360px; height:150px; background-color:#ffffff; overflow:scroll'>
                    </div>
                    </td>
                    </tr>
                    </table>
                </td>
            </tr>
            </table>
            <br>
            ";

        // ********** Table **********
        $form['gen_editControlArray'][] = array(
            'type' => "table",
            'tableCount' => (isset($form['payment_id']) && is_numeric($form['payment_id']) ? 0 : ROW_NUM),
            'isTotal' => true,
            'lineHeight' => 35,
            'isLineNo' => true,
            'controls' => array(
                array(
                    'label' => _g('支払種別'),
                    'type' => 'select',
                    'name' => "way_of_payment",
                    'options' => Gen_Option::getWayOfPayment('options'),
                    'selected' => (isset($form["way_of_payment"]) ? $form["way_of_payment"] : '[[lineNo]]'),
                    'require' => true,
                    'size' => '10',
                    'nowrap' => true,
                ),
                array(
                    'label' => _g('支払金額'),
                    'type' => 'textbox',
                    'name' => 'amount',
                    'value' => @$form['amount'],
                    'size' => '10',
                    'ime' => 'off',
                    'style' => "text-align:right",
                    'onChange_noEscape' => "onAmountChange()",
                    'helpText_noEscape' => _g('実際の支払金額を登録します。'),
                    'require' => true,
                    'nowrap' => true,
                    'totalLine' => true,
                ),
                array(
                    'label' => _g('調整金額'),
                    'type' => 'textbox',
                    'name' => 'adjust_amount',
                    'value' => @$form['adjust_amount'],
                    'size' => '10',
                    'ime' => 'off',
                    'style' => "text-align:right",
                    'onChange_noEscape' => "onAdjustChange()",
                    'helpText_noEscape' => _g('支払金額と、ジェネシス上の買掛金額（受入登録画面の受入金額+消費税額）が異なる場合、その差額を入力します。たとえば実際の支払金額が10,600、ジェネシスの買掛金額が10,500だった場合、-100と入力します。この入力は、買掛残高一覧表に反映されます。'),
                    'nowrap' => true,
                    'totalLine' => true,
                ),
                array(
                    'label' => _g('調整後金額'),
                    'type' => 'div',
                    'name' => "after_adjust_amount",
                    'size' => '8',
                    'style' => "text-align:right",
                ),
                array(
                    'label' => _g('支払備考'),
                    'type' => 'textbox',
                    'name' => "remarks",
                    'value' => @$form["remarks"],
                    'ime' => 'on',
                    'size' => '30',
                    'nowrap' => true,
                ),
            ),
        );
        $form['gen_editControlArray'][] = array(
            'type' => 'literal',
            'denyMove' => true,
        );
        $form['gen_editControlArray'][] = array(
            'type' => 'literal',
            'denyMove' => true,
        );
    }

}