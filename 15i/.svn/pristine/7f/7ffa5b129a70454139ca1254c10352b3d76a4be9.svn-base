<?php

define("LINE_COUNT", 20);   // BarcodeEntryクラスと揃えること

class Monthly_StockInput_BarcodeEdit extends Base_EditBase
{

    function convert($converter, &$form)
    {
        $converter->notNumToValue("show_line_number", 1);
        $converter->nullBlankToValue('inventory_date', Gen_String::getLastMonthLastDateString());
    }

    // 既存データSQL実行前に処理
    function setQueryParam(&$form)
    {
        $this->keyColumn = "";    // 常に新規
        $this->selectQuery = ""; // dummy
    }

    // 既存データSQL実行後に処理
    // 画面表示のための設定
    function setViewParam(&$form)
    {
        global $gen_db;

        if (!isset($form['show_line_number']))
            $form['show_line_number'] = "1";

        $form['gen_pageTitle'] = _g("バーコード棚卸登録");
        $form['gen_entryAction'] = "Monthly_StockInput_BarcodeEntry&show_line_number={$form['show_line_number']}";
        $form['gen_listAction'] = "Monthly_StockInput_List";
        $form['gen_onLoad_noEscape'] = "";
        $form['gen_beforeEntryScript_noEscape'] = "entry();";
        $form['gen_pageHelp'] = _g("棚卸登録");

        $form['gen_focus_element_id'] = "item_code_1";

        $form['gen_hidePins'] = true;
        $form['gen_hideHistorys'] = true;

        $line_count = LINE_COUNT;
        $form['gen_javascript_noEscape'] = "
            // 品目/製番変更イベント
            function onItemSeibanChange(no) {
                var itemCode = $('#item_code_'+no).val();
                var seiban = $('#seiban_'+no).val();
                $('#item_name_'+no).val('');
                $('#logical_stock_quantity_'+no).val('');
                $('#lot_no_'+no).val('');
                $('#remarks_'+no).val('');
                if (itemCode == '') return;
                for (var i=1;i<={$line_count};i++) {
                   if (no != i) {
                       if ($('#item_code_'+i).val() == itemCode) {
                           alert('" . _g("この品目はすでに入力されています。") . "');
                           $('#item_code_'+no).val('');
                           $('#item_code_'+no).select();
                           return;
                       }
                   }
                }
                gen.edit.submitDisabled();
                var p = {
                    inventory_date: $('#inventory_date').val(),
                    item_code: itemCode,
                    seiban: seiban,
                    location_id: $('#location_id').val()
                };
                gen.ajax.connect('Monthly_StockInput_AjaxParamBarcode', p, 
                    function(j) {
                        if (j == '') {
                            alert('" . _g("品目コードが正しくありません。") . "');
                            // エラー時は入力された品目コードを消す。ホントは残したほうがいいが、そのまま登録されると困るので（サーバー側でもチェックはしているが）。
                            $('#item_code_'+no).val('');
                            $('#item_code_'+no).select();
                            gen.edit.submitEnabled('{$form['gen_readonly']}');
                            return;
                        }
                        if (j.inv_quantity != '') {
                            $('#inventory_quantity_'+no).val(j.inv_quantity);
                        }
                        $('#logical_stock_quantity_'+no).val(j.log_quantity);
                        $('#item_name_'+no).val(j.item_name);
                        $('#lot_no'+no).val(j.lot_no);
                        gen.edit.submitEnabled('" . h($form['gen_readonly']) . "');
                    });
            }

            // 登録
            function entry() {
                var cnt = 0;
                for (var i=1;i<={$line_count};i++) {
                    var code = $('#item_code_'+i).val();
                    var qty = gen.util.trim($('#inventory_quantity_'+i).val());
                    if (!gen.util.isNumeric(qty) && !(code == '' && qty == '')) {
                        str = '" . _g("数量%1が正しくありません。") . "'; alert(str.replace('%1', (i + " . h($form['show_line_number']) . " - 1))); $('#inventory_quantity_'+i).select(); return;
                    }
                    if (code != '') {
                        cnt++;
                    }
                }
                if (cnt == 0) {
                    alert('" . _g("データが入力されていません。") . "'); return;
                }
                document.forms[0].submit();
            };
        ";

        // 棚卸日コントロール作成
        $html_inventory_date = Gen_String::makeCalendarHtml(
            array(
                'label' => "",
                'name' => "inventory_date",
                'value' => @$form['inventory_date'],
                'size' => '85',
            )
        );

        // option（ロケ）
        $opt = $gen_db->getHtmlOptionArray("select location_id, location_name from location_master order by location_code", false, array("0" => _g(GEN_DEFAULT_LOCATION_NAME)));
        $html_location_id = Gen_String::makeSelectHtml("location_id", $opt, @$form['location_id'], "", "Monthly_StockInput_BarcodeEdit", @$form['gen_pins']);

        $form['gen_message_noEscape'] = "
            <table border='0'>
            <tr>
                <td style='text-align:right'>" . _g("棚卸日") . _g("：") . "</td>
                <td style='text-align:left'>{$html_inventory_date}</td>
                <td width='30'></td>
                <td style='text-align:right'>" . _g("ロケーション") . _g("：") . "</td>
                <td style='text-align:left'>{$html_location_id}</td>
            </tr>
            </table>
            <br>
        ";

        // ********** Table **********
        $form['gen_editControlArray'][] = array(
            'type' => "table",
            'tableCount' => LINE_COUNT,
            'rowCount' => 1, // 1セルに格納するコントロールの数（1セルの行数）
            'lineHeight' => 35,
            'isLineNo' => true,
            'onLastLineKeyPress' => true,
            'controls' => array(
                array(
                    'label' => _g("品目コード"),
                    'type' => 'textbox',
                    'name' => "item_code",
                    'value' => @$form["item_code"],
                    'onChange_noEscape' => "onItemSeibanChange([[lineNo]])",
                    'ime' => 'off',
                    'size' => '10',
                    'nowrap' => true,
                ),
                array(
                    'label' => _g("棚卸数量"),
                    'type' => 'textbox',
                    'name' => "inventory_quantity",
                    'value' => @$form["inventory_quantity"],
                    'size' => '6',
                    'ime' => 'off',
                    'style' => 'text-align:right',
                    'nowrap' => true,
                ),
                array(
                    'label' => _g("製番"),
                    'type' => 'textbox',
                    'name' => "seiban",
                    'value' => @$form["seiban"],
                    'onChange_noEscape' => "onItemSeibanChange([[lineNo]])",
                    'size' => '10',
                    'nowrap' => true,
                ),
                array(
                    'label' => _g("品目名"),
                    'type' => 'textbox',
                    'name' => "item_name",
                    'value' => "",
                    'size' => '18',
                    'readonly' => true,
                ),
                array(
                    'label' => _g("理論在庫"),
                    'type' => 'textbox',
                    'name' => "logical_stock_quantity",
                    'value' => "",
                    'size' => '8',
                    'alignForReadonlyTextbox' => "right",
                    'readonly' => true,
                ),
                array(
                    'label' => _g("ロット番号"),
                    'type' => 'textbox',
                    'name' => "lot_no",
                    'value' => "",
                    'size' => '9',
                    'readonly' => true,
                ),
                array(
                    'label' => _g("備考"),
                    'type' => 'textbox',
                    'name' => "remarks",
                    'value' => @$form["remarks"],
                    'tabindex' => -1,
                    'ime' => 'on',
                    'size' => '20',
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
