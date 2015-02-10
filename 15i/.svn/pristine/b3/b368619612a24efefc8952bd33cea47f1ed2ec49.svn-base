<?php

define("LINE_COUNT", 20);   // BarcodeEntryクラスと揃えること

class Manufacturing_Achievement_BarcodeEdit extends Base_EditBase
{

    function convert($converter, &$form)
    {
        $converter->notNumToValue("show_line_number", 1);
        $converter->nullBlankToValue('achievement_date', date('Y-m-d'));
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

        $form['gen_pageTitle'] = _g("バーコード実績登録");
        $form['gen_entryAction'] = "Manufacturing_Achievement_BarcodeEntry&show_line_number={$form['show_line_number']}";
        $form['gen_listAction'] = "Manufacturing_Achievement_List";
        $form['gen_onLoad_noEscape'] = "";
        $form['gen_beforeEntryScript_noEscape'] = "entry();";
        $form['gen_pageHelp'] = _g("バーコード登録");

        $form['gen_focus_element_id'] = "order_process_no_1";

        $form['gen_hidePins'] = true;
        $form['gen_hideHistorys'] = true;

        $line_count = LINE_COUNT;
        $msg1 = _g("この番号はすでに入力されています。");
        $msg2 = _g("指定された番号は注文書のオーダー番号です。注文書の受入登録は[受入登録]画面で行ってください。");
        $msg2a = _g("指定された番号は外製工程のオーダー番号です。外製工程の受入登録は[外製受入登録]画面で行ってください。");
        $msg2b = _g("指定された番号は外製指示書のオーダー番号です。外製指示書の受入登録は[外製受入登録]画面で行ってください。");
        $msg3 = _g("指定された実績登録コードは存在しません。");
        $msg4 = _g("数量%1が正しくありません。");
        $msg5 = _g("製造時間（分）%1が正しくありません。");
        $msg6 = _g("オーダー番号%1が正しくありません。");
        $msg7 = _g("データが入力されていません。");
        $msg8 = _g("不適合数%1が正しくありません。");
        $msg9 = _g("不適合理由%1を選択してください。");
        $form['gen_javascript_noEscape'] = "
            // オーダー変更イベント
            function onOrderNoChange(no) {
                var orderNo = $('#order_process_no_'+no).val();
                $('#achievement_quantity_'+no).val('');
                $('#item_code_'+no).val('');
                $('#item_name_'+no).val('');
                $('#process_name_'+no).val('');
                $('#work_minute_'+no).val('');
                $('#remarks_'+no).val('');
                if (orderNo == '') return;
                for (var i=1;i<={$line_count};i++) {
                   if (no != i) {
                       if ($('#order_process_no_'+i).val() == orderNo) {
                           alert('{$msg1}');
                           $('#order_process_no_'+no).val('');
                           $('#order_process_no_'+no).select();
                           return;
                       }
                   }
                }
                gen.edit.submitDisabled();
                gen.ajax.connect('Manufacturing_Achievement_AjaxOrderParamBarcode', {no : orderNo}, 
                    function(j) {
                        if (j != '' || j.is_subcontract_process=='1') {
                            if (j.classification!='0' || j.is_subcontract_process=='1') {
                                if (j.classification=='1') {
                                    alert('{$msg2}');
                                } else if (j.is_subcontract_process=='1') {
                                    alert('{$msg2a}');
                                } else {
                                    alert('{$msg2b}');
                                }
                                // エラー時は入力されたオーダー番号を消す。ホントは残したほうがいいが、そのまま登録されると困るので（サーバー側でもチェックはしているが）。
                                $('#order_process_no_'+no).val('');
                                $('#order_process_no_'+no).select();
                                gen.edit.submitEnabled('{$form['gen_readonly']}');
                                return;
                            }
                            $('#achievement_quantity_'+no).val(j.quantity);
                            $('#item_code_'+no).val(j.item_code);
                            $('#item_name_'+no).val(j.item_name);
                            $('#process_name_'+no).val(j.process_name);
                            $('#work_minute_'+no).val('0');
                            $('#remarks_'+no).val(j.remarks);
                        } else {
                            alert('{$msg3}');
                            // エラー時は入力されたオーダー番号を消す。ホントは残したほうがいいが、そのまま登録されると困るので（サーバー側でもチェックはしているが）。
                            $('#order_process_no_'+no).val('');
                            $('#order_process_no_'+no).select();
                        }
                        gen.edit.submitEnabled('" . h($form['gen_readonly']) . "');
                    });
            }

            // 登録
            function entry() {
                var cnt = 0;
                for (var i=1;i<={$line_count};i++) {
                    var no = $('#order_process_no_'+i).val();
                    var qty = gen.util.trim($('#achievement_quantity_'+i).val());
                    var time = gen.util.trim($('#work_minute_'+i).val());
                    var waster_id = $('#waster_id_1_'+i).val();
                    var waster_qty = gen.util.trim($('#waster_quantity_1_'+i).val());
                    if (!gen.util.isNumeric(qty) && !(no == '' && qty == '')) {
                        str = '{$msg4}'; alert(str.replace('%1', (i + " . h($form['show_line_number']) . " - 1))); $('#achievement_quantity_'+i).select(); return;
                    }
                    if (!gen.util.isNumeric(time) && !(no == '' && time == '')) {
                        str = '{$msg5}'; alert(str.replace('%1', (i + " . h($form['show_line_number']) . " - 1))); $('#work_minute_'+i).select(); return;
                    }
                    if (no == '' && (qty != '' || time != '')) {
                        str = '{$msg6}'; alert(str.replace('%1', (i + " . h($form['show_line_number']) . " - 1))); $('#order_process_no_'+i).select(); return;
                    }
                    if (waster_id != '' && !gen.util.isNumeric(waster_qty)) {
                        str = '{$msg8}'; alert(str.replace('%1', (i + " . h($form['show_line_number']) . " - 1))); $('#waster_quantity_1_'+i).select(); return;
                    }
                    if (waster_id == '' && gen.util.isNumeric(waster_qty)) {
                        str = '{$msg9}'; alert(str.replace('%1', (i + " . h($form['show_line_number']) . " - 1))); $('#waster_id_1_'+i).select(); return;
                    }
                    if (no != '') {
                        cnt++;
                    }
                }
                if (cnt == 0) {
                    alert('{$msg7}'); return;
                }
                document.forms[0].submit();
            };

            function onWorkerIdChange() {
                var wid = $('#worker_id').val();
                if (wid == 'null') return;
                gen.ajax.connect('Manufacturing_Achievement_AjaxWorkerParam', {worker_id : wid}, 
                    function(j) {
                        document.getElementById('section_id').value = j.section_id;
                    });
            }
        ";

        // 製造日コントロール作成
        $html_achievement_date = Gen_String::makeCalendarHtml(
            array(
                'label' => "",
                'name' => "achievement_date",
                'value' => @$form['achievement_date'],
                'size' => '85',
            )
        );

        // option（作業者）
        $opt = $gen_db->getHtmlOptionArray("select worker_id, worker_name from worker_master order by worker_code", true);
        $html_worker_id = Gen_String::makeSelectHtml("worker_id", $opt, @$form['worker_id'], "onWorkerIdChange()", "Manufacturing_Achievement_BarcodeEdit", @$form['gen_pins']);
        // option（部門）
        $opt = $gen_db->getHtmlOptionArray("select section_id, section_name from section_master order by section_code", true);
        $html_section_id = Gen_String::makeSelectHtml("section_id", $opt, @$form['section_id'], "", "Manufacturing_Achievement_BarcodeEdit", @$form['gen_pins']);
        // option（設備）
        $opt = $gen_db->getHtmlOptionArray("select equip_id, equip_name from equip_master order by equip_code", true);
        $html_equip_id = Gen_String::makeSelectHtml("equip_id", $opt, @$form['equip_id'], "", "Manufacturing_Achievement_BarcodeEdit", @$form['gen_pins']);
        // option（入庫ロケ）
        $opt = $gen_db->getHtmlOptionArray("select location_id, location_name from location_master order by location_code", false, array("-1" => _g("(標準ロケ)"), "0" => _g(GEN_DEFAULT_LOCATION_NAME)));
        $html_location_id = Gen_String::makeSelectHtml("location_id", $opt, @$form['location_id'], "", "Manufacturing_Achievement_BarcodeEdit", @$form['gen_pins']);
        // option（子ロケ）
        $opt = $gen_db->getHtmlOptionArray("select location_id, location_name from location_master order by location_code", false, array("-1" => _g("(標準ロケ)"), "0" => _g(GEN_DEFAULT_LOCATION_NAME)));
        $html_child_location_id = Gen_String::makeSelectHtml("child_location_id", $opt, @$form['child_location_id'], "", "Manufacturing_Achievement_BarcodeEdit", @$form['gen_pins']);

        $form['gen_message_noEscape'] = "
            <table border='0'>
            <tr>
                <td style='text-align:right'>" . _g("製造日") . _g("：") . "</td>
                <td style='text-align:left'>{$html_achievement_date}</td>
                <td colspan='3'></td>
            </tr><tr>
                <td style='text-align:right'>" . _g("作業者") . _g("：") . "</td>
                <td style='text-align:left'>{$html_worker_id}</td>
                <td width='30'></td>
                <td style='text-align:right'>" . _g("入庫ロケーション（最終工程のみ）") . _g("：") . "</td>
                <td style='text-align:left'>{$html_location_id}</td>
            </tr><tr>
                <td style='text-align:right'>" . _g("部門") . _g("：") . "</td>
                <td style='text-align:left'>{$html_section_id}</td>
                <td width='30'></td>
                <td style='text-align:right'>" . _g("子品目出庫ロケーション（最終工程のみ）") . _g("：") . "</td>
                <td style='text-align:left'>{$html_child_location_id}</td>
            </tr><tr>
                <td style='text-align:right'>" . _g("設備") . _g("：") . "</td>
                <td style='text-align:left'>{$html_equip_id}</td>
                <td width='30'></td>
                <td style='text-align:right'>" . _g("工程完了もしくはオーダー完了") . _g("：") . "</td>
                <td style='text-align:left'><input type='checkbox' id='accept_completed' name='accept_completed' value='true' " . (@$form['accept_completed'] == "true" ? "checked" : "") . "></td>
            </tr>
            </table>
            <br>
        ";

        $query = "select waster_id, waster_name from waster_master order by waster_name";
        $option_waster = $gen_db->getHtmlOptionArray($query, true);

        // ********** Table **********
        $form['gen_editControlArray'][] = array(
            'type' => "table",
            'tableCount' => LINE_COUNT,
            'rowCount' => 2, // 1セルに格納するコントロールの数（1セルの行数）
            'lineHeight' => 35,
            'isLineNo' => true,
            'onLastLineKeyPress' => true,
            'controls' => array(
                array(
                    'label' => _g("実績登録コード"),
                    'type' => 'textbox',
                    'name' => "order_process_no",
                    'value' => @$form["order_process_no"],
                    'onChange_noEscape' => "onOrderNoChange([[lineNo]])",
                    'ime' => 'off',
                    'size' => '10',
                    'helpText_noEscape' => _g('製造指示書の「実績登録コード」を入力してください。'),
                    'nowrap' => true,
                ),
                array(
                    'label' => _g("品目コード"),
                    'type' => 'textbox',
                    'name' => "item_code",
                    'value' => "",
                    'size' => '10',
                    'readonly' => true,
                ),
                array(
                    'label' => _g("数量"),
                    'type' => 'textbox',
                    'name' => "achievement_quantity",
                    'value' => @$form["achievement_quantity"],
                    'size' => '8',
                    'ime' => 'off',
                    'style' => 'text-align:right',
                    'tabindex' => -1,
                    'nowrap' => true,
                ),
                array(
                    'label' => _g("品目名"),
                    'type' => 'textbox',
                    'name' => "item_name",
                    'value' => "",
                    'size' => '15',
                    'readonly' => true,
                ),
                array(
                    'label' => _g("製造時間（分）"),
                    'type' => 'textbox',
                    'name' => "work_minute",
                    'value' => @$form["work_minute"],
                    'size' => '8',
                    'ime' => 'off',
                    'style' => 'text-align:right',
                    'tabindex' => -1,
                    'nowrap' => true,
                ),
                array(
                    'label' => _g("工程名"),
                    'type' => 'textbox',
                    'name' => "process_name",
                    'value' => "",
                    'size' => '15',
                    'readonly' => true,
                ),
                array(
                    'label' => _g("不適合理由"),
                    'type' => 'select',
                    'name' => "waster_id_1",
                    'options' => $option_waster,
                    'selected' => @$form["waster_id_1"],
                    'tabindex' => -1,
                    'nowrap' => true,
                ),
                array(
                    'label' => _g("不適合数"),
                    'type' => 'textbox',
                    'name' => "waster_quantity_1",
                    'value' => @$form["waster_quantity_1"],
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
                    'size' => '15',
                    'helpText_noEscape' => '<b>' . _g('最終工程のみ有効です。') . '</b>' . _g('製造ロット番号を入力します。この番号を納品登録画面や親品目の製造実績登録画面で入力することで、製造や納品と使用ロットを結びつけることができ、トレーサビリティを実現できます。') . '<br>' .
                    _g('ロット管理（トレーサビリティ）を必要としない場合は、入力の必要はありません。'),
                    'nowrap' => true,
                ),
                array(
                    'label' => _g("実績備考"),
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
