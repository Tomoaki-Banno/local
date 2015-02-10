<?php

class Manufacturing_Achievement_BulkEdit extends Base_ListBase
{

    function setSearchCondition(&$form)
    {
        global $gen_db;

        // この画面のqueryはカスタム項目に対応していない（メインテーブルをグループ化している）
        $this->denyCustomColumn = true;

        $query = "select item_group_id, item_group_name from item_group_master order by item_group_code";
        $option_item_group = $gen_db->getHtmlOptionArray($query, false, array(null => _g("(すべて)")));

        $form['gen_searchControlArray'] = array(
            array(
                'label' => _g('オーダー番号'),
                'type' => 'strFromTo',
                'field' => 'order_no',
                'size' => '100',
                'rowSpan' => 2,
            ),
            array(
                'label' => _g('製造開始日'),
                'type' => 'dateFromTo',
                'field' => 'order_date',
                'rowSpan' => 2,
            ),
            array(
                'label' => _g("製造納期"),
                'type' => 'dateFromTo',
                'field' => 'order_detail_dead_line',
                'rowSpan' => 2,
            ),
            array(
                'label' => _g('受注番号'),
                'field' => 'received_number',
                'helpText_noEscape' => _g('製番オーダーだけが検索対象となります。'),
                'hide' => true,
            ),
            array(
                'label' => _g('工程コード/名'),
                'field' => 'process_code',
                'field2' => 'process_name',
                'hide' => true,
            ),
            array(
                'label' => _g('品目コード/名'),
                'field' => 'order_detail___item_code',
                'field2' => 'order_detail___item_name',
                'hide' => true,
            ),
            array(
                'label' => _g('品目グループ'),
                'field' => 'item_group_id',
                'type' => 'select',
                'options' => $option_item_group,
                'hide' => true,
            ),
            array(
                'label' => _g('登録済の品目'),
                'type' => 'select',
                'field' => 'achievement_show',
                'options' => Gen_Option::getTrueOrFalse('search-show'),
                'default' => "false",
                'nosql' => true,
            ),
        );
    }

    function convertSearchCondition($converter, &$form)
    {
        $converter->nullBlankToValue('achievement_date', date('Y-m-d'));
    }

    function beforeLogic(&$form)
    {
    }

    function setQueryParam(&$form)
    {
        $this->selectQuery = "
            select
                order_header.order_date
                ,order_header.remarks_header
                ,order_detail.order_detail_id
                ,order_detail.order_no
                ,(order_process.machining_sequence + 1) as machining_sequence_show
                ,order_detail.item_code
                ,order_detail.item_name
                ,coalesce(order_detail.order_detail_quantity,0) - coalesce(ach_qty,0) as achievement_quantity
                ,measure
                ,order_detail.order_detail_quantity
                ,ach_qty as process_achievement_quantity
                ,order_detail.order_detail_dead_line
                ,order_detail.item_price
                ,order_detail.order_detail_completed
                ,order_detail.seiban
                ,process_master.process_name
                ,order_process.order_process_no
                ,order_process.machining_sequence
                ,case when order_detail_completed then '" . _g("完了") . "' else '' end as order_detail_completed
                /* 以下2行は集計モードを「データの数」にしていたときのエラー回避 */
                ,cast('' as text) as lot_no
                ,cast('' as text) as use_lot_no
            from
                order_header
                inner join order_detail on order_header.order_header_id = order_detail.order_header_id
                left join order_process on order_detail.order_detail_id = order_process.order_detail_id
                left join process_master on order_process.process_id = process_master.process_id
                left join (select order_detail_id, process_id, sum(achievement_quantity) as ach_qty from achievement group by order_detail_id, process_id) as t_ach
                   on order_detail.order_detail_id = t_ach.order_detail_id and order_process.process_id = t_ach.process_id
                left join (select item_id as iid, measure, item_group_id, item_group_id_2, item_group_id_3 from item_master) as t_item on order_detail.item_id = t_item.iid
                left join (select seiban as s2, received_number from received_detail inner join received_header on received_header.received_header_id=received_detail.received_header_id) as t_rec on order_detail.seiban = t_rec.s2
            [Where]
                and order_header.classification = 0
                /* 外製工程は実績登録できない（外製受入登録で登録する）*/
                and coalesce(order_process.subcontract_partner_id,0) = 0
            ";
        if ($form['gen_search_achievement_show'] == 'false') {
            // 完了オーダー/工程を含めないモード。
            //  完了は、フラグだけでなく工程ごとの実績数も見て判断する。
            $this->selectQuery .= "
                and not coalesce(order_detail_completed, false)
                and coalesce(ach_qty,0) < order_detail_quantity
                and not coalesce(order_process.process_completed, false)
            ";
        }
        $this->selectQuery .= "
            [Orderby]
        ";

        $this->orderbyDefault = 'order_no, machining_sequence_show';
    }

    function setViewParam(&$form)
    {
        global $gen_db;

        $form['gen_pageTitle'] = _g("一括実績登録");
        $form['gen_menuAction'] = "Menu_Manufacturing";
        $form['gen_listAction'] = "Manufacturing_Achievement_BulkEdit";
        $form['gen_idField'] = 'order_process_no';

        $form['gen_dataRowHeight'] = 25;        // データ部の1行の高さ

        $form['gen_returnUrl'] = "index.php?action=Manufacturing_Achievement_List&gen_restore_search_condition=true";
        $form['gen_returnCaption'] = _g('実績登録へ戻る');

        if ($form['gen_readonly'] == 'true') {
            $form['gen_message_noEscape'] = "<BR><font color=red>" . _g("一括登録を行う権限がありません。") . "</font>";
        } else {
            // 納品日コントロール作成
            $html_achievement_date = Gen_String::makeCalendarHtml(
                array(
                    'label' => "",
                    'name' => "achievement_date",
                    'value' => @$form['achievement_date'],
                    'size' => '85',
                )
            );

            // 再表示したときに各項目の値を復元
            $defaultZerofinish = "";
            if (@$form['zerofinish'] == "checked")
                $defaultZerofinish = "value='checked' checked";

            // option（作業者）
            $opt = $gen_db->getHtmlOptionArray("select worker_id, worker_name from worker_master order by worker_code", true);
            $html_worker_id = Gen_String::makeSelectHtml("worker_id", $opt, @$form['worker_id'], "onWorkerIdChange()", "Manufacturing_Achievement_BulkEdit", @$form['gen_pins']);
            // option（部門）
            $opt = $gen_db->getHtmlOptionArray("select section_id, section_name from section_master order by section_code", true);
            $html_section_id = Gen_String::makeSelectHtml("section_id", $opt, @$form['section_id'], "", "Manufacturing_Achievement_BulkEdit", @$form['gen_pins']);
            // option（設備）
            $opt = $gen_db->getHtmlOptionArray("select equip_id, equip_name from equip_master order by equip_code", true);
            $html_equip_id = Gen_String::makeSelectHtml("equip_id", $opt, @$form['equip_id'], "", "Manufacturing_Achievement_BulkEdit", @$form['gen_pins']);
            // option（入庫ロケ）
            $opt = $gen_db->getHtmlOptionArray("select location_id, location_name from location_master order by location_code", false, array("-1" => _g("(標準ロケ)"), "0" => _g(GEN_DEFAULT_LOCATION_NAME)));
            $html_location_id = Gen_String::makeSelectHtml("location_id", $opt, @$form['location_id'], "", "Manufacturing_Achievement_BulkEdit", @$form['gen_pins']);
            // option（子ロケ）
            $opt = $gen_db->getHtmlOptionArray("select location_id, location_name from location_master order by location_code", false, array("-1" => _g("(標準ロケ)"), "0" => _g(GEN_DEFAULT_LOCATION_NAME)));
            $html_child_location_id = Gen_String::makeSelectHtml("child_location_id", $opt, @$form['child_location_id'], "", "Manufacturing_Achievement_BulkEdit", @$form['gen_pins']);

            $form['gen_message_noEscape'] = "
                <table border='0'>
                <tr>
                    <td align='right'>" . _g("製造日") . _g("：") . "</td>
                    <td align='left'>{$html_achievement_date}</td>
                    <td colspan='3'></td>
                </tr><tr>
                    <td align='right'>" . _g("作業者") . _g("：") . "</td>
                    <td align='left'>{$html_worker_id}</td>
                    <td width='30'></td>
                    <td align='right'>" . _g("入庫ロケーション（最終工程のみ）") . _g("：") . "</td>
                    <td align='left'>{$html_location_id}</td>
                </tr><tr>
                    <td align='right'>" . _g("部門") . _g("：") . "</td>
                    <td align='left'>{$html_section_id}</td>
                    <td width='30'></td>
                    <td align='right'>" . _g("子品目出庫ロケーション（最終工程のみ）") . _g("：") . "</td>
                    <td align='left'>{$html_child_location_id}</td>
                </tr><tr>
                    <td align='right'>" . _g("設備") . _g("：") . "</td>
                    <td align='left'>{$html_equip_id}</td>
                    <td width='30'></td>
                    <td align='right'>" . _g("製造数0で登録") . _g("：") . "</td>
                    <td align='left'><input type='checkbox' id='zerofinish' name='zerofinish' onClick='onZeroFinishClick()' {$defaultZerofinish}></td>
                </tr>
                </table>
                <table><tr height=3><td></td></tr></table>
                <div id=\"doButton\">
                <input type=\"button\" class=\"gen-button\" value=\"&nbsp;&nbsp; " . _g("一括実績登録を実行") . " &nbsp;&nbsp;\" onClick=\"bulkEdit()\">
                </div>
            ";

            // リスト表示の際、gen_messageに記述しているコントロールをパラメータとして付与する。
            // gen_messageに記述しているコントロールは表示条件ではないので、gen_resotre_search_condition による復元が行われないため。
            $form['gen_beforeListUpdateScript_noEscape'] = "
                if (param === null) param = {};
                param['achievement_date'] = $('#achievement_date').val();
                param['worker_id'] = $('#worker_id').val();
                param['location_id'] = $('#location_id').val();
                param['section_id'] = $('#section_id').val();
                param['child_location_id'] = $('#child_location_id').val();
                param['equip_id'] = $('#equip_id').val();
                param['zerofinish'] = $('#zerofinish').val();
            ";
        }

        $form['gen_javascript_noEscape'] = "
            function bulkEdit() {
                var frm = gen.list.table.getCheckedPostSubmit('order_process_no', new Array('achievement_quantity','lot_no','use_lot_no','order_detail_completed'));
                if (frm.count == 0) {
                    alert('" . _g("データが選択されていません。") . "');
                    return;
                }

                var msg = '';
                var zerofinish = $('#zerofinish')[0].checked;
                if (zerofinish) {
                    msg += '" . _g("選択された製造指示に対し、「製造数0で」完了登録を行います。") . "';
                } else {
                    msg += '" . _g("選択された製造指示に対し、製造実績の一括登録を行います。") . "';
                }
                msg += '" . _g("実行には時間がかかる場合があります。処理が終わるまでコンピュータに手を触れずにお待ちください。実行しますか？") . "';
                if (!window.confirm(msg)) return;

                document.body.style.cursor = 'wait';
                $('#doButton').html(\"<table><tr><td bgcolor='#ffcc33'>" . _g("実行中") . "...</td></tr></table>\");
                gen.ui.disabled($('#gen_searchButton'));

                var postUrl = 'index.php?action=Manufacturing_Achievement_BulkEntry';
                postUrl += '&achievement_date=' + $('#achievement_date').val();
                postUrl += '&section_id=' + $('#section_id').val();
                postUrl += '&equip_id=' + $('#equip_id').val();
                postUrl += '&worker_id=' + $('#worker_id').val();
                postUrl += '&location_id=' + $('#location_id').val();
                postUrl += '&child_location_id=' + $('#child_location_id').val();
                if (zerofinish) postUrl += '&isZeroFinish=true';
                postUrl += '&gen_restore_search_condition=true';
                frm.submit(postUrl, null);
            }

            function onZeroFinishClick() {
                if ($('#zerofinish')[0].checked) {
                    $('#zerofinish').val('checked');
                    alert('" . _g("このチェックをオンにすると、「製造数0で」完了登録を行います。") . "');
                } else {
                    $('#zerofinish').val('');
                }
            }

            function onWorkerIdChange() {
                var wid = $('#worker_id').val();
                if (wid == 'null') return;
                gen.ajax.connect('Manufacturing_Achievement_AjaxWorkerParam', {worker_id : wid},
                    function(j) {
                        document.getElementById('section_id').value = j.section_id;
                    });
            }

            function check(id) {
                $('#order_process_no_'+id).attr('checked',true);
            }
        ";

        $form['gen_fixColumnArray'] = array(
            array(
                'label' => _g("登録"),
                'type' => 'checkbox',
                'name' => 'order_process_no'
            ),
        );
        $form['gen_columnArray'] = array(
            array(
                'label' => _g('製造開始日'),
                'field' => 'order_date',
                'type' => 'date',
            ),
            array(
                'label' => _g('オーダー番号'),
                'field' => 'order_no',
                'width' => '85',
                'align' => 'center',
            ),
            array(
                'label' => _g('工順'),
                'field' => 'machining_sequence_show',
                'width' => '50',
                'align' => 'center',
            ),
            array(
                'label' => _g('工程'),
                'field' => 'process_name',
            ),
            array(
                'label' => _g('品目コード'),
                'field' => 'item_code',
            ),
            array(
                'label' => _g('品目名'),
                'field' => 'item_name',
            ),
            array(
                'label' => _g('数量'),
                'width' => '80',
                'type' => 'textbox',
                'align' => 'center',
                'field' => 'achievement_quantity',
                'colorCondition' => array("#ffffcc" => "true"),
                'style' => 'text-align:right; background-color:#ffffcc',
                'onChange_noEscape' => "check('[id]')"
            ),
            array(
                'label' => _g('単位'),
                'field' => 'measure',
                'type' => 'data',
                'width' => '35',
            ),
            array(
                'label' => _g('ロット番号'),
                'width' => '80',
                'type' => 'textbox',
                'align' => 'center',
                'field' => 'lot_no',
                'onChange_noEscape' => "check('[id]')",
                'helpText_noEscape' => '<b>' . _g('最終工程のみ有効です。') . '</b>' . _g('製造ロット番号を入力します。この番号を納品登録画面や親品目の製造実績登録画面で入力することで、製造や納品と使用ロットを結びつけることができ、トレーサビリティを実現できます。') . '<br>' .
                _g('ロット管理（トレーサビリティ）を必要としない場合は、入力の必要はありません。')
            ),
            array(
                'label' => _g('使用ロット番号'),
                'width' => '80',
                'type' => 'textbox',
                'align' => 'center',
                'field' => 'use_lot_no',
                'onChange_noEscape' => "check('[id]')",
                'helpText_noEscape' => '<b>' . _g('最終工程のみ有効です。') . '</b>' . _g('製造に使用した子品目の製造・購買ロット番号を入力します。複数の子品目ロットがある場合はカンマ区切りで入力してください。') . '<br>' .
                _g('「製造・購買ロット番号」とは、内製品であれば製造実績画面の製造ロット番号、発注品であれば受入画面の購買ロット番号を指します。この登録により、製造に使用した部材・原料のロットを調べることができるようになり、トレーサビリティを実現できます。') . '<br>' .
                _g('ロット管理（トレーサビリティ）を必要としない場合は、入力の必要はありません。')
            ),
            // このフラグは最終工程以外では工程完了、最終工程ではオーダー完了として働く。
            // 実績登録画面の「完了」と同じなので、詳しくはそちらのチップヘルプを参照。
            array(
                'label' => _g("完了"),
                'width' => '40',
                'type' => 'checkbox',
                'field' => 'order_detail_completed',
                'onValue' => 'true',
                'onChange_noEscape' => "check('[id]')",
            ),
            array(
                'label' => _g('指示数'),
                'field' => 'order_detail_quantity',
                'type' => 'numeric',
            ),
            array(
                'label' => _g('製造済数'),
                'field' => 'process_achievement_quantity',
                'type' => 'numeric',
            ),
            array(
                'label' => _g('製造納期'),
                'field' => 'order_detail_dead_line',
                'type' => 'date',
                'align' => 'center',
            ),
            array(
                'label' => _g('製番(オーダー)'),
                'field' => 'seiban',
                'width' => '90',
                'align' => 'center',
            ),
            array(
                'label' => _g('実績備考'),
                'field' => 'remarks_header',
            ),
        );
    }

}
