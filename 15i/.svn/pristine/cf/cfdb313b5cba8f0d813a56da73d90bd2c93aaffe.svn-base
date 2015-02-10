<?php

require_once("Model.class.php");

@define('ROW_NUM', 10);

class Delivery_PayingIn_Edit extends Base_EditBase
{

    function convert($converter, &$form)
    {
        $converter->nullBlankToValue('paying_in_date', date("Y-m-d"));
    }

    // 既存データSQL実行前に処理
    function setQueryParam(&$form)
    {
        $this->keyColumn = 'paying_in_id';
        $this->selectQuery = "
            select
                paying_in.*
                ,case when foreign_currency_id is null then amount else foreign_currency_amount end as amount

                ,coalesce(paying_in.record_update_date, paying_in.record_create_date) as gen_last_update
                ,coalesce(paying_in.record_updater, paying_in.record_creator) as gen_last_updater
            from
                paying_in
                inner join customer_master on paying_in.customer_id = customer_master.customer_id
            [Where]
        ";

        // データロックの判断基準となるフィールドを指定
        $form["gen_salesDateLockFieldArray"] = array("paying_in_date");
    }

    // 既存データSQL実行後に処理
    // 画面表示のための設定
    function setViewParam(&$form)
    {
        global $gen_db;

        $this->modelName = "Delivery_PayingIn_Model";

        $form['gen_pageTitle'] = _g("入金登録");
        $form['gen_entryAction'] = "Delivery_PayingIn_Entry";
        $form['gen_listAction'] = "Delivery_PayingIn_List";
        $form['gen_onLoad_noEscape'] = "";
        $form['gen_pageHelp'] = _g("入金登録");
        
        $form['gen_message_noEscape'] = "";
        $hasBill = false;
        if (isset($form['paying_in_id']) && is_numeric($form['paying_in_id'])) {
            if (Logic_Bill::hasBillByPayingInId($form['paying_in_id'])) {
                $hasBill = true;
                $form['gen_message_noEscape'] .= "<font color=red>" . _g("この入金を含む請求書が発行されているため、備考以外の内容を変更することはできません。") . "</font><br><br>";
            }
        }

        $isBill = false;
        $customerId = "";
        $payingInDate = "";
        $foreignCurrencyRate = "";
        $billHeaderId = "";
        $billHeaderIdShow = "";
        if (isset($form['customer_id']))
            $customerId = $form['customer_id'];
        if (isset($form['paying_in_date']))
            $payingInDate = $form['paying_in_date'];
        if (isset($form['foreign_currency_rate']))
            $foreignCurrencyRate = $form['foreign_currency_rate'];

        $isReload = (isset($form['gen_validError']) && $form['gen_validError']) || isset($form['gen_editReload']);

        if (!isset($form['paying_in_id']) && !$isReload) {
            $payingInDate = date('Y-m-d');
            $foreignCurrencyRate = "";
        }
        if (isset($form['isBill']) && $form['isBill'] == "true") {
            $isBill = true;
            $billHeaderId = $form['billHeaderId'];
            $billHeaderIdShow = $gen_db->queryOneValue("select bill_number from bill_header where bill_header_id = '{$form['billHeaderId']}'");
        }

        $form['gen_focus_element_id'] = "customer_id_show";

        // 入力履歴ボタンを表示しない。このボタンは複数行表示（controlArrayのnameがSQLのカラムと一致しないケース）に対応できないため。
        $form['gen_hideHistorys'] = true;

        $isFirst = false;
        if (isset($form['customer_id'])) {
            $form['gen_onLoad_noEscape'] = "onCustomerChange();onAmountChange()";
            $isFirst = true;
        }
        if ($isBill)
            $form['gen_onLoad_noEscape'] .= ";setBillData();onBillChange(1)";
        $form['gen_beforeEntryScript_noEscape'] = "beforeEntryCheck()";

        $form['gen_javascript_noEscape'] = "
            function onCustomerChange() {
            	var rbd =  $('#recentBillorDelivery').val();
                var p = { customer_id : $('#customer_id').val(), recent_bill_or_delivery : rbd };
                $('#currency_name').val('');
                " . ($isReload || $isFirst ? "" : "$('#foreign_currency_rate').val('');") . "
                gen.ajax.connect('Delivery_PayingIn_AjaxCustomerHistory', p,
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
                            if (rbd=='1') {
                                var str = '<table style=\"table-layout:fixed;width:320px\">'+
                                    '<tr bgcolor=\"#cccccc\">'+
                                    '<td width=\"80px\" align=center>" . _g('納品日') . "</td>'+
                                    '<td width=\"70px\" align=center>" . _g('金額') . "</td>'+
                                    '<td width=\"120px\" align=center>" . _g('品目') . "</td>'+
                                    '<td width=\"50px\" align=center>" . _g('数量') . "</td>'+
                                    '</tr>';
                                for (var i=0;i<j.recentBill.length;i++) {
                                    date = gen.util.escape(j.recentBill[i]['receivable_date']);
                                    if (date == null) 
                                        date = '';
                                    str += '<tr>';
                                    str += '<td align=center>' + gen.util.escape(j.recentBill[i]['delivery_date']) + '</td>';
                                    str += '<td align=right>' + gen.util.addFigure(gen.util.escape(j.recentBill[i]['sales_with_tax'])) + '</td>';
                                    str += '<td align=left>' + gen.util.escape(j.recentBill[i]['item_name']) + '</td>';
                                    str += '<td align=right>' + gen.util.addFigure(gen.util.escape(j.recentBill[i]['delivery_quantity'])) + '</td>';
                                    str += '</tr>';
                                }
                                str += '</table>';
                            } else {
                        	var str = '<table style=\"table-layout:fixed;width:320px\">'+
                                    '<tr bgcolor=\"#cccccc\">'+
                                    '<td width=\"80px\" align=center>" . _g('請求締日') . "</td>'+
                                    '<td width=\"80px\" align=center>" . _g('今回売上') . "</td>'+
                                    '<td width=\"80px\" align=center>" . _g('繰越請求') . "</td>'+
                                    '<td width=\"80px\" align=center>" . _g('入金予定日') . "</td>'+
                                    '</tr>';
                                for (var i=0;i<j.recentBill.length;i++) {
                                    date = j.recentBill[i]['receivable_date'];
                                    if (date == null) 
                                        date = '';
                                    str += '<tr>';
                                    str += '<td align=center>' + gen.util.escape(j.recentBill[i]['close_date']) + '</td>';
                                    str += '<td align=right>' + gen.util.addFigure(gen.util.round(gen.util.escape(j.recentBill[i]['sales_with_tax']), parseFloat(j.precision))) + '</td>';
                                    str += '<td align=right>' + gen.util.addFigure(gen.util.round(gen.util.escape(j.recentBill[i]['bill_amount']), parseFloat(j.precision))) + '</td>';
                                    str += '<td align=center>' + date + '</td>';
                                    str += '</tr>';
                                }
                                str += '</table>';
                            }
                            $('#recentBill').html(str);

                            str = '<table style=\"table-layout:fixed;width:440px\">'+
                                '<tr bgcolor=\"#cccccc\">'+
                                '<td width=\"80px\" align=center>" . _g('入金日') . "</td>'+
                                '<td width=\"80px\" align=center>" . _g('入金額') . "</td>'+
                                '<td width=\"80px\" align=center>" . _g('入金方法') . "</td>'+
                                '<td width=\"200px\" align=left>" . _g('入金備考') . "</td>'+
                                '</tr>';
                            for (var i=0;i<j.recentPayingIn.length;i++) {
                                str += '<tr>';
                                str += '<td align=center>' + gen.util.escape(j.recentPayingIn[i]['paying_in_date']) + '</td>';
                                str += '<td align=right>' + gen.util.addFigure(gen.util.escape(j.recentPayingIn[i]['amount'])) + '</td>';
                                str += '<td align=center>' + gen.util.escape(j.recentPayingIn[i]['way_of_payment_show']) + '</td>';
                                str += '<td align=left>' + gen.util.escape(j.recentPayingIn[i]['remarks']) + '</td>';
                                str += '</tr>';
                            }
                            str += '</table>';
                            $('#recentPayingIn').html(str);
                        }
                    }
                );
            }

            function setBillData() {
                $('#bill_header_id_1').val('{$billHeaderId}');
                $('#bill_header_id_1_show').val('{$billHeaderIdShow}');
            }

            function onBillChange(col) {
                if (!gen.util.isNumeric(col)) {
                    var colName1 = 'bill_header_id';
                    var colName2 = 'amount';
                } else {
                    var colName1 = 'bill_header_id_'+col;
                    var colName2 = 'amount_'+col;
                }
                var billHeaderId = $('#'+colName1).val();
                if (!gen.util.isNumeric(billHeaderId)) {
                    $('#'+colName2).val('');
                    onAmountChange();
                } else {
                    var p = {};
                    $('[id^=bill_header_id_]').each(function(){
                        var colNo = this.name.replace(/bill_header_id_/, '');
                        if (gen.util.isNumeric(colNo) && col != colNo && gen.util.isNumeric(this.value)) {
                            p[this.name] = this.value;
                            p['amount_'+colNo] = $('#amount_'+colNo).val();
                        }
                    });
                    p['bill_header_id'] = billHeaderId;
                    " . (isset($form['paying_in_id']) && is_numeric($form['paying_in_id']) ? "p['paying_in_id'] = {$form['paying_in_id']};" : "") . "
                    gen.ajax.connect('Delivery_PayingIn_AjaxBillParam', p, 
                        function(j) {
                            $('#'+colName2).val(j.bill_amount);
                            onAmountChange();
                        });
                }
            }

            function onAmountChange() {
                var total = 0;
                var amount;
                $('#amount_total').val('');
                $('[id^=amount_]').each(function(){
                    amount = gen.util.fullNumToHalfNum(this.value);
                    if (gen.util.isNumeric(amount)) {
                        total = gen.util.decCalc(total, amount, '+');
                    }
                });
                $('#amount_total').css('text-align','right').val(gen.util.addFigure(total));
            }

            function beforeEntryCheck() {
                var exist = false;
                $('[id^=amount]').each(function(){
                    if (this.id != 'amount_total' && this.type == 'text' && this.value != '') exist = true;
                });
                if (exist) {
                    document.forms[0].submit()
                } else {
                    alert('" . _g("入金データを入力してください。") . "');
                }
            }
        ";

        $msg1 = _g("得意先");
        $msg2 = _g("取引通貨");
        $msg3 = _g("日付");
        $msg4 = _g("レート");

        // 得意先コントロール作成
        $html_customer_id = Gen_String::makeDropdownHtml(
            array(
                'label' => "",
                'name' => 'customer_id',
                'value' => @$form['customer_id'],
                'size' => '11',
                'subSize' => '20',
                'dropdownCategory' => 'customer',
                'onChange_noEscape' => "onCustomerChange()",
                'require' => true,
                'readonly' => (isset($form['paying_in_id']) && is_numeric($form['paying_in_id']) ? true : false),
                'pinForm' => (!isset($form['paying_in_id']) ? "Delivery_PayingIn_Edit" : ""),
                'genPins' => @$form['gen_pins'],
                'readonly' => $hasBill,
            )
        );

        // 日付コントロール作成
        $html_paying_in_date = Gen_String::makeCalendarHtml(
            array(
                'label' => "",
                'name' => "paying_in_date",
                'value' => @$form['paying_in_date'],
                'size' => '85',
                'ime' => 'off',
                'require' => true,
                'pinForm' => (!isset($form['paying_in_id']) ? "Delivery_PayingIn_Edit" : ""),
                'genPins' => @$form['gen_pins'],
                'readonly' => $hasBill,
            )
        );

        $form['gen_message_noEscape'] .= "
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
                    <td style='text-align:left'>{$html_paying_in_date}</td>
                    <td width='15'></td>
                    <td style='text-align:right; font-weight:bold;'>{$msg4}</td>
                    <td style='width:20px;'></td>
                    <td style='text-align:left'><input id='foreign_currency_rate' name='foreign_currency_rate' style='width:80px; ime-mode:inactive;' type='textbox' value='{$foreignCurrencyRate}'></td>
                </tr>
            </table>

            <br>
            <table>
            <tr>
                <td>■" . _g('この得意先への最近の') . "<select id='recentBillorDelivery' onchange='onCustomerChange()'><option value='0'>" . _g('請求') . "</option><option value='1'>" . _g('納品') . "</option></select></td>
                <td>■" . _g('この得意先からの最近の入金') . "</td>
            </tr>
            <tr>
                <td>
                    <table><tr><td style='background-color:#cccccc;'>
                    <div id='recentBill' style='width:360px; height:150px; background-color:#ffffff; overflow:scroll'>
                    </div>
                    </td>
                    </tr>
                    </table>
                </td>
                <td>
                    <table><tr><td style='background-color:#cccccc;'>
                    <div id='recentPayingIn' style='width:360px; height:150px; background-color:#ffffff; overflow:scroll'>
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
            'tableCount' => (isset($form['paying_in_id']) && is_numeric($form['paying_in_id']) ? 0 : ROW_NUM),
            'isTotal' => true,
            'lineHeight' => 35,
            'isLineNo' => true,
            'controls' => array(
                array(
                    'label' => _g('入金種別'),
                    'type' => 'select',
                    'name' => "way_of_payment",
                    'options' => Gen_Option::getWayOfPayment('options'),
                    'selected' => (isset($form["way_of_payment"]) ? $form["way_of_payment"] : '[[lineNo]]'),
                    'require' => true,
                    'size' => '10',
                    'nowrap' => true,
                    'readonly' => $hasBill,
                ),
                array(
                    'label' => _g("請求書番号") . "(" . _g("都度") . ")",
                    'type' => 'dropdown',
                    'name' => "bill_header_id",
                    'value' => @$form["bill_header_id"],
                    'size' => '10',
                    'dropdownCategory' => 'bill_number_each',
                    // このCategoryでは2つのパラメータをセミコロン区切りで指定する。Logic_Dropdownの該当箇所を参照
                    'dropdownParam' => '[customer_id];' . (isset($form['paying_in_id']) && is_numeric($form['paying_in_id']) ? $form['paying_in_id'] : ""),
                    'onChange_noEscape' => "onBillChange('[[lineNo]]')",
                    'readonly' => (isset($form['paying_in_id']) && is_numeric($form['paying_in_id']) ? true : false),
                    'nowrap' => true,
                    'hidePin' => true,
                    'helpText_noEscape' => _g("都度請求の請求書のみ指定できます。締め請求の請求書はセレクタに表示されません。")
                ),
                array(
                    'label' => _g("入金金額"),
                    'type' => 'textbox',
                    'name' => "amount",
                    'value' => @$form["amount"],
                    'size' => '10',
                    'ime' => 'off',
                    'style' => "text-align:right",
                    'onChange_noEscape' => 'onAmountChange()',
                    'require' => true,
                    'nowrap' => true,
                    'totalLine' => true,
                    'readonly' => $hasBill,
                ),
                array(
                    'label' => _g('入金備考'),
                    'type' => 'textbox',
                    'name' => "remarks",
                    'value' => @$form["remarks"],
                    'size' => '30',
                    'ime' => 'on',
                    'nowrap' => true,
                ),
            ),
        );
    }

}