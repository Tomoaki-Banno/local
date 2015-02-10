<?php

define("LINE_COUNT", 20);   // BarcodeEntryクラスと揃えること

class Delivery_Delivery_BarcodeEdit extends Base_EditBase
{

    function convert($converter, &$form)
    {
        $converter->notNumToValue("show_line_number", 1);
        $converter->nullBlankToValue('delivery_date', date('Y-m-d'));
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

        if (!isset($form['show_line_number']))
            $form['show_line_number'] = "1";

        $form['gen_pageTitle'] = _g("バーコード納品登録");
        $form['gen_entryAction'] = "Delivery_Delivery_BarcodeEntry&show_line_number={$form['show_line_number']}";
        $form['gen_listAction'] = "Delivery_Delivery_List";
        $form['gen_onLoad_noEscape'] = "";
        $form['gen_focus_element_id'] = "seiban_1";
        $form['gen_beforeEntryScript_noEscape'] = "entry();";
        $form['gen_pageHelp'] = _g("バーコード登録");

        $form['gen_hidePins'] = true;
        $form['gen_hideHistorys'] = true;

        $form['gen_javascript_noEscape'] = "
            // オーダー変更イベント
            function onReceivedNumberChange(no) {
                var receivedNumber = $('#seiban_'+no).val();
                $('#delivery_quantity_'+no).val('');
                $('#item_code_'+no).val('');
                $('#item_name_'+no).val('');
                if (receivedNumber == '') return;
                for (var i=1;i<=" . LINE_COUNT . ";i++) {
                   if (no != i) {
                       if ($('#seiban_'+i).val() == receivedNumber) {
                           alert('" . _g("この受注製番はすでに入力されています。") . "');
                           $('#seiban_'+no).val('');
                           $('#seiban_'+no).get(0).select();
                           return;
                       }
                    }
                }
                gen.edit.submitDisabled();
                gen.ajax.connect('Delivery_Delivery_AjaxReceivedParamBarcode', {seiban:receivedNumber},
                    function(j) {
                       if (j.status == 'success') {
                           $('#delivery_quantity_'+no).val(j.rem_qty);
                           $('#item_code_'+no).val(j.item_code);
                           $('#item_name_'+no).val(j.item_name);
                       } else {
                           alert('" . _g("指定された受注製番は存在しません。") . "');
                            // エラー時は入力された製番を消す。ホントは残したほうがいいが、そのまま登録されると困るので（サーバー側でもチェックはしているが）。
                           $('#seiban_'+no).val('');
                           $('#seiban_'+no).get(0).select();
                       }
                       gen.edit.submitEnabled('{$form['gen_readonly']}');
                    });
            }

            // 登録
            function entry() {
                var cnt = 0;
                var p = {};
                p['type'] = 'barcode';
                for (var i=1;i<=" . LINE_COUNT . ";i++) {
                    var no = document.getElementById('seiban_'+i).value;
                    var qty = gen.util.trim(document.getElementById('delivery_quantity_'+i).value);
                    if (!gen.util.isNumeric(qty) && !(no == '' && qty == '')) {
                        alert('" . _g("数量") . "' + (i + " . h($form['show_line_number']) . " - 1) + '" . _g("が正しくありません。") . "'); return;
                    }
                    if (no == '' && qty != '') {
                        alert('" . _g("受注明細番号") . "' + (i + " . h($form['show_line_number']) . " - 1) + '" . _g("が入力されていません。") . "'); return;
                    }
                    if (no != '') {
                        p['seiban_' + no] = qty;
                        cnt++;
                    }
                }
                if (cnt == 0) {
                    alert('" . _g("データが入力されていません。") . "'); return;
                }
                gen.ajax.connect('Delivery_Delivery_AjaxCreditLineCheck', p,
                    function(j) {
                        if (j.status == 'warning') {
                            if (!window.confirm('" . _g("売掛残高と今回納品額の合計が与信限度額をオーバーしている得意先が存在しますが、このまま登録してもよろしいですか？") . "')) {
                                return;
                            }
                        }
                        document.forms[0].submit();
                    });
            }
        ";

        // 納品日コントロール作成
        $html_delivery_date = Gen_String::makeCalendarHtml(
            array(
                'label' => "",
                'name' => "delivery_date",
                'value' => @$form['delivery_date'],
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

        // option（出庫ロケ）
        $opt = $gen_db->getHtmlOptionArray("select location_id, location_name from location_master order by location_code", false, array("-1" => _g('(標準ロケ)'), "0" => _g(GEN_DEFAULT_LOCATION_NAME)));
        $html_location_id = Gen_String::makeSelectHtml("location_id", $opt, @$form['location_id'], "", "Delivery_Delivery_BarcodeEdit", @$form['gen_pins']);
        
        // option（納品書まとめ）
        $opt = array("0" => _g("明細ごと"), "1" => _g("受注ごと"), "2" => _g("得意先ごと"), "3" => _g("発送先ごと"));
        if (!isset($form['delivery_note_group'])) {
            $form['delivery_note_group'] = "2";
        }
        $html_delivery_note_group = Gen_String::makeSelectHtml("delivery_note_group", $opt, @$form['delivery_note_group'], "", "Delivery_Delivery_BarcodeEdit", @$form['gen_pins']);

        $form['gen_message_noEscape'] = "
            <table border='0'>
            <tr>
                <td align='right'>" . _g("納品日") . _g("：") . "</td>
                <td align='left'>{$html_delivery_date}</td>
                <td width='30'></td>
                <td align='right'>" . _g("出庫ロケーション") . _g("：") . "</td>
                <td align='left'>{$html_location_id}</td>
            </tr>
            <tr>
                <td align='right'>" . _g("検収日") . _g("：") . "</td>
                <td align='left'>{$html_inspection_date}</td>
                <td width='30'></td>
                <td align='left' colspan='2' nowrap>
                    " . _g("納品書まとめ") . _g("：") . " {$html_delivery_note_group}
                </td>
            </tr>
            <tr>
                <td align='right'>" . _g("残数があっても完了扱いにする") . _g("：") . "</td>
                <td align='left'><input type='checkbox' id='delivery_completed' name='delivery_completed' value='true' " . (@$form['delivery_completed'] == "true" ? "checked" : "") . "></td>
                <td width='30'></td>
                <td colspan='2'></td>
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
                    'label' => _g("受注製番"),
                    'type' => 'textbox',
                    'name' => "seiban",
                    'value' => @$form["seiban"],
                    'onChange_noEscape' => "onReceivedNumberChange([[lineNo]])",
                    'ime' => 'off',
                    'size' => '10',
                    'nowrap' => true,
                ),
                array(
                    'label' => _g("数量"),
                    'type' => 'textbox',
                    'name' => "delivery_quantity",
                    'value' => @$form["delivery_quantity"],
                    'size' => '8',
                    'ime' => 'off',
                    'style' => 'text-align:right',
                    'tabindex' => -1,
                    'nowrap' => true,
                ),
                array(
                    'label' => _g("ロット番号"),
                    'type' => 'textbox',
                    'name' => "use_lot_no",
                    'value' => @$form["use_lot_no"],
                    'tabindex' => -1,
                    'size' => '9',
                    'helpText_noEscape' => _g('出荷した品目の製造・購買ロット番号を入力します。複数のロットがある場合はカンマ区切りで入力してください。') . '<br>' .
                    _g('「製造・購買ロット番号」とは、内製品であれば製造実績画面の製造ロット番号、発注品であれば受入画面の購買ロット番号を指します。この登録により、出荷した品目の製造ロットや購買ロットを調べることができるようになり、トレーサビリティを実現できます。') . '<br>' .
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
