<?php

define("LINE_COUNT", 20);   // BarcodeEntryクラスと揃えること

class Partner_SubcontractAccepted_BarcodeEdit extends Base_EditBase
{

    function convert($converter, &$form)
    {
        $converter->notNumToValue("show_line_number", 1);
        $converter->nullBlankToValue('accepted_date', date('Y-m-d'));
        $converter->nullBlankToValue('inspection_date', date('Y-m-d'));
    }

    // 既存データSQL実行前に処理
    function setQueryParam(&$form)
    {
        $this->keyColumn = "";    // 常に新規
        $this->selectQuery = "";
    }

    // 既存データSQL実行後に処理
    // 画面表示のための設定
    function setViewParam(&$form)
    {
        global $gen_db;

        $form['gen_pageTitle'] = _g("バーコード外製受入登録");
        $form['gen_entryAction'] = "Partner_SubcontractAccepted_BarcodeEntry&show_line_number={$form['show_line_number']}";
        $form['gen_listAction'] = "Partner_SubcontractAccepted_List";
        $form['gen_onLoad_noEscape'] = "";
        $form['gen_beforeEntryScript_noEscape'] = "entry();";
        $form['gen_pageHelp'] = _g("バーコード登録");

        $form['gen_focus_element_id'] = "order_no_1";

        $form['gen_hidePins'] = true;
        $form['gen_hideHistorys'] = true;

        $form['gen_javascript_noEscape'] =
        $line_count = LINE_COUNT;
        $msg1 = _g("この番号はすでに入力されています。");
        $msg2 = _g("指定された番号は製造指示書のオーダー番号です。製造指示書の製造実績登録は[実績登録]画面で行ってください。");
        $msg3 = _g("指定されたオーダー番号は存在しません。");
        $msg4 = _g("数量%1が正しくありません。");
        $msg6 = _g("オーダー番号%1が正しくありません。");
        $msg7 = _g("データが入力されていません。");
        $form['gen_javascript_noEscape'] = "
            // オーダー変更イベント
            function onOrderNoChange(no) {
                var orderNo = $('#order_no_'+no).val();
                $('#accepted_quantity_'+no).val('');
                $('#lot_no_'+no).val('');
                $('#item_code_'+no).val('');
                $('#item_name_'+no).val('');
                if (orderNo == '') return;
                for (var i=1;i<={$line_count};i++) {
                   if (no != i) {
                       if ($('#order_no_'+i).val() == orderNo) {
                           alert('{$msg1}');
                           $('#order_no_'+no).val('');
                           $('#order_no_'+no).select();
                           return;
                       }
                   }
                }
                gen.edit.submitDisabled();
                gen.ajax.connect('Partner_SubcontractAccepted_AjaxOrderParamBarcode', {order_no : orderNo}, 
                    function(j) {
                        if (j != '') {
                            // このクラスは注文書・外製指示書共通であるため、注文書と外製指示書の違いはチェックできない（どちらでも登録できてしまう）
                            if (j.classification=='0') {
                               alert('{$msg2}');
                                // エラー時は入力されたオーダー番号を消す。ホントは残したほうがいいが、そのまま登録されると困るので（サーバー側でもチェックはしているが）。
                               $('#order_no_'+no).val('');
                               $('#order_no_'+no).select();
                               gen.edit.submitDisabled();
                               return;
                           }
                           $('#accepted_quantity_'+no).val(j.quantity);
                           $('#item_code_'+no).val(j.item_code);
                           $('#item_name_'+no).val(j.item_name);
                       } else {
                           alert('{$msg3}');
                            // エラー時は入力されたオーダー番号を消す。ホントは残したほうがいいが、そのまま登録されると困るので（サーバー側でもチェックはしているが）。
                           $('#order_no_'+no).val('');
                           $('#order_no_'+no).select();
                       }
                       gen.edit.submitEnabled('" . h($form['gen_readonly']) . "');
                    });
            }

            // 登録
            function entry() {
               var cnt = 0;
               for (var i=1;i<={$line_count};i++) {
                   var no = $('#order_no_'+i).val();
                   var qty = $('#accepted_quantity_'+i).val();
                   if (!gen.util.isNumeric(qty) && !(no == '' && qty == '')) {
                       str = '{$msg4}'; alert(str.replace('%1', (i + {$form['show_line_number']} - 1))); $('#quantity_'+i).select(); return;
                   }
                   if (no == '' && qty != '') {
                       str = '{$msg6}'; alert(str.replace('%1', (i + {$form['show_line_number']} - 1))); $('#order_no_'+i).select(); return;
                   }
                   if (no != '') {
                       cnt++;
                   }
               }
               if (cnt == 0) {
                   alert('{$msg7}'); return;
               }
               for (var i=1;i<={$line_count};i++) {
                   $('#item_code_'+i).val('');
                   $('#item_name_'+i).val('');
               }
               document.forms[0].submit();
            };
        ";

        // 受入日コントロール作成
        $html_accepted_date = Gen_String::makeCalendarHtml(
            array(
                'label' => "",
                'name' => "accepted_date",
                'value' => @$form['accepted_date'],
                'size' => '85',
            )
        );

        // 検収日コントロール作成
        $html_inspection_date = Gen_String::makeCalendarHtml(
            array(
                'label' => "",
                'name' => "inspection_date",
                'value' => @$form['inspection_date'],
                'size' => '85',
            )
        );

        // option（入庫ロケ）
        $opt = $gen_db->getHtmlOptionArray("select location_id, location_name from location_master order by location_code", false, array("-1" => _g('(標準ロケ)'), "0" => _g(GEN_DEFAULT_LOCATION_NAME)));
        $html_location_id = Gen_String::makeSelectHtml("location_id", $opt, @$form['location_id'], "", "Partner_SubcontractAccepted_BarcodeEdit", @$form['gen_pins']);

        $form['gen_message_noEscape'] = "
            <table border='0'>
            <tr>
                <td align='right'>" . _g("受入日") . _g("：") . "</td>
                <td align='left'>{$html_accepted_date}</td>
                <td width='30'></td>
                <td align='right'>" . _g("入庫ロケーション") . _g("：") . "</td>
                <td align='left'>{$html_location_id}</td>
            </tr>
            <tr>
                <td align='right'>" . _g("検収日") . _g("：") . "</td>
                <td align='left'>{$html_inspection_date}</td>
                <td width='30'></td>
                <td align='right'>" . _g("残数があっても完了扱いにする") . _g("：") . "</td>
                <td align='left'><input type='checkbox' id='order_detail_completed' name='order_detail_completed' value='true' " . (@$form['order_detail_completed'] == "true" ? "checked" : "") . "></td>
            </tr>
            </table>
            <br>
        ";

        // ********** Table **********
        $form['gen_editControlArray'][] = array(
            'type' => "table",
            'tableCount' => LINE_COUNT,
            'lineHeight' => 35,
            'isLineNo' => true,
            'onLastLineKeyPress' => true,
            'controls' => array(
                array(
                    'label' => _g("オーダー番号"),
                    'type' => 'textbox',
                    'name' => "order_no",
                    'value' => @$form["order_no"],
                    'onChange_noEscape' => "onOrderNoChange([[lineNo]])",
                    'ime' => 'off',
                    'size' => '10',
                    'nowrap' => true,
                ),
                array(
                    'label' => _g("数量"),
                    'type' => 'textbox',
                    'name' => "accepted_quantity",
                    'value' => @$form["accepted_quantity"],
                    'size' => '8',
                    'ime' => 'off',
                    'style' => 'text-align:right',
                    'tabindex' => -1,
                    'nowrap' => true,
                ),
                array(
                    'label' => _g("ロット番号"),
                    'type' => 'textbox',
                    'name' => "lot_no",
                    'value' => @$form["lot_no"],
                    'tabindex' => -1,
                    'size' => '9',
                    'helpText_noEscape' => _g('購買ロット番号を入力します。この番号を製造実績登録画面や納品登録画面で入力することで、製造や納品と使用部材ロットを結びつけることができ、トレーサビリティを実現できます。') . '<br>' .
                    _g('ロット管理（トレーサビリティ）を必要としない場合は、入力の必要はありません。'),
                    'nowrap' => true,
                ),
                array(
                    'label' => _g("品目コード"),
                    'type' => 'textbox',
                    'name' => "item_code",
                    'value' => "",
                    'size' => '15',
                    'readonly' => true,
                ),
                array(
                    'label' => _g("品目名"),
                    'type' => 'textbox',
                    'name' => "item_name",
                    'value' => "",
                    'size' => '25',
                    'readonly' => true,
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
