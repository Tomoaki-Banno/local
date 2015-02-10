<?php

class Partner_SubcontractAccepted_BulkEdit extends Base_ListBase
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
                'label' => _g('製番'),
                'field' => 'seiban',
                'helpText_noEscape' => _g('製番オーダーだけが検索対象となります。'),
                'hide' => true,
            ),
            array(
                'label' => _g('発注先名'),
                'field' => 'customer_name',
            ),
            array(
                'label' => _g('品目コード/名'),
                'field' => 'order_detail___item_code',
                'field2' => 'order_detail___item_name',
                'hide' => true,
            ),
            array(
                'label' => _g('受注番号'),
                'field' => 'received_number',
                'helpText_noEscape' => _g('製番オーダーだけが検索対象となります。'),
                'hide' => true,
            ),
            array(
                'label' => _g('発行日'),
                'field' => 'order_date',
                'type' => 'dateFromTo',
                'rowSpan' => 2,
            ),
            array(
                'label' => _g('外注納期'),
                'field' => 'order_detail_dead_line',
                'type' => 'dateFromTo',
                'rowSpan' => 2,
            ),
            array(
                'label' => _g('担当者コード/名'),
                'field' => 'worker_code',
                'field2' => 'worker_name',
                'hide' => true,
            ),
            array(
                'label' => _g('部門コード/名'),
                'field' => 'section_code',
                'field2' => 'section_name',
                'hide' => true,
            ),
            array(
                'label' => _g('受入済の品目'),
                'type' => 'select',
                'field' => 'accepted_show',
                'options' => Gen_Option::getTrueOrFalse('search-show'),
                'default' => 'false',
                'nosql' => true,
            ),
            array(
                'label' => _g('品目グループ'),
                'field' => 'item_group_id',
                'type' => 'select',
                'options' => $option_item_group,
                'hide' => true,
            ),
        );
    }

    function convertSearchCondition($converter, &$form)
    {
        $converter->nullBlankToValue('accepted_date', date('Y-m-d'));
        $converter->nullBlankToValue('inspection_date', date('Y-m-d'));
    }

    function beforeLogic(&$form)
    {
    }

    function setQueryParam(&$form)
    {
        $this->selectQuery = "
            select
                order_header.order_id_for_user
                ,order_header.order_date
                ,order_header.partner_id
                ,customer_master.customer_name
                ,order_header.remarks_header

                ,order_detail.order_detail_id
                ,order_detail.line_no
                ,order_detail.order_no
                ,coalesce(order_detail.order_detail_quantity,0)-coalesce(order_detail.accepted_quantity,0) as accepted_quantity
                ,order_detail.order_detail_quantity
                ,order_detail.order_detail_dead_line
                ,order_detail.item_code
                ,order_detail.item_name
                ,order_detail.item_price
                ,order_detail.accepted_quantity as accepted_done_quantity
                ,item_master.measure
                ,order_detail.order_detail_completed
                ,order_detail.seiban
                ,case when order_detail_completed then '" . _g("完了") . "' else '' end as order_detail_completed
                ,order_detail.remarks
                ,cast('' as text) as lot_no	-- 集計モードを「データの数」にしていたときのエラー回避
                ,worker_code
                ,worker_name
                ,section_code
                ,section_name

            from
                order_header
                inner join order_detail on order_header.order_header_id = order_detail.order_header_id
                left join item_master on order_detail.item_id = item_master.item_id
                left join (select seiban as s2, received_number from received_detail inner join received_header on received_header.received_header_id=received_detail.received_header_id) as t_rec on order_detail.seiban = t_rec.s2
                left join customer_master on order_header.partner_id = customer_master.customer_id
                left join worker_master on order_header.worker_id = worker_master.worker_id
                left join section_master on order_header.section_id = section_master.section_id

            [Where]
                and order_header.classification = 2
                and order_header.classification <> 0
                " . ($form['gen_search_accepted_show'] == 'false' ? " and (order_detail_completed = false or order_detail_completed is null) " : "") . "
            [Orderby]
        ";

        $this->orderbyDefault = 'order_id_for_user, line_no';
    }

    function setViewParam(&$form)
    {
        global $gen_db;

        $form['gen_pageTitle'] = _g("外製一括受入");
        $form['gen_menuAction'] = "Menu_Partner";
        $form['gen_listAction'] = "Partner_SubcontractAccepted_BulkEdit";
        $form['gen_idField'] = 'order_detail_id';
        $form['gen_onLoad_noEscape'] = "onInspectionChange()";
        $form['gen_afterListUpdateScript_noEscape'] = "onInspectionChange();";

        $form['gen_dataRowHeight'] = 25;        // データ部の1行の高さ

        $form['gen_returnUrl'] = "index.php?action=Partner_SubcontractAccepted_List&gen_restore_search_condition=true";
        $form['gen_returnCaption'] = _g('外製受入登録へ戻る');

        // 更新許可がなければアクセス不可
        if ($form['gen_readonly'] == 'true') {
            $form['gen_message_noEscape'] = "<BR><Font color=red>" . _g("一括受入を行う権限がありません。") . "</Font>";
        } else {
            // 受入日コントロール作成
            $html_accepted_date = Gen_String::makeCalendarHtml(
                array(
                    'label' => _g("受入日") . _g("："),
                    'name' => "accepted_date",
                    'value' => @$form['accepted_date'],
                    'size' => '85',
                )
            );

            // 検収日コントロール作成
            $html_inspection_date = Gen_String::makeCalendarHtml(
                array(
                    'label' => _g("検収日") . _g("："),
                    'name' => "inspection_date",
                    'value' => @$form['inspection_date'],
                    'size' => '85',
                )
            );

            // 再表示したときに各項目の値を復元
            $defaultInspection = "";
            if (@$form['inspection'] == "checked")
                $defaultInspection = "value='checked' checked";

            // option（入庫ロケ）
            $opt = $gen_db->getHtmlOptionArray("select location_id, location_name from location_master order by location_code", false, array("-1" => _g('(標準ロケ)'), "0" => _g(GEN_DEFAULT_LOCATION_NAME)));
            $html_location_id = Gen_String::makeSelectHtml("location_id", $opt, @$form['location_id'], "", "Partner_SubcontractAccepted_BulkEdit", @$form['gen_pins']);

            $form['gen_message_noEscape'] = "
                <table border='0'>
                <tr>
                    <td align='left' nowrap>{$html_accepted_date}</td>
                    <td width='20'></td>
                    <td align='left' nowrap><input type='checkbox' id='inspection' name='inspection' onchange='onInspectionChange()' {$defaultInspection}>" . _g("検収") . "</td>
                    <td width='8'></td>
                    <td align='left' nowrap>{$html_inspection_date}</td>
                </tr><tr>
                    <td align='left' colspan='5' nowrap>" . _g("入庫ロケーション") . _g("：") . " {$html_location_id}</td>
                </tr><tr>
                    <td align='center' colspan='5'>
                        <div id=\"doButton\" style=\"margin-top:3px;\">
                            <input type=\"button\" class=\"gen-button\" value=\"&nbsp;&nbsp; " . _g("一括受入登録を実行") . " &nbsp;&nbsp;\" onClick=\"bulkEdit()\">
                        </div>
                    </td>
                </tr>
                </table>
            ";

            // リスト表示の際、gen_messageに記述しているコントロールをパラメータとして付与する。
            // gen_messageに記述しているコントロールは表示条件ではないので、gen_resotre_search_condition による復元が行われないため。
            $form['gen_beforeListUpdateScript_noEscape'] = "
                if (param === null) param = {};
                param['accepted_date'] = $('#accepted_date').val();
                param['inspection'] = $('#inspection').val();
                param['inspection_date'] = $('#inspection_date').val();
                param['location_id'] = $('#location_id').val();
            ";
        }

        $form['gen_javascript_noEscape'] = "
            function bulkEdit() {
                var frm = gen.list.table.getCheckedPostSubmit('order_detail_id', new Array('accepted_quantity','lot_no','order_detail_completed'));
                if (frm.count == 0) {
                   alert('" . _g("データが選択されていません。") . "');
                   return;
                }
                var msg = '';
                msg += '" . _g("実行には時間がかかる場合があります。処理が終わるまでコンピュータに手を触れずにお待ちください。実行しますか？") . "';
                if (!window.confirm(msg)) return;

                document.body.style.cursor = 'wait';
                document.getElementById('doButton').innerHTML = \"<table><tr><td bgcolor='#ffcc33'>" . _g("実行中") . "...</td></tr></table>\";
                gen.ui.disabled($('#gen_searchButton'));

                var postUrl = 'index.php?action=Partner_SubcontractAccepted_BulkEntry';
                postUrl += '&accepted_date=' + $('#accepted_date').val();
                postUrl += '&inspection_date=';
                if ($('#inspection').is(':checked')) {
                    postUrl += $('#inspection_date').val();
                }
                postUrl += '&location_id=' + $('#location_id').val();
                postUrl += '&gen_restore_search_condition=true';
                frm.submit(postUrl, null);
            }

            function onOrderIdForUserChange() {
               document.getElementById('gen_searchButton').focus();
            }

            function onInspectionChange() {
                if ($('#inspection').is(':checked')) {
                    gen.ui.enabled($('#inspection_date'));
                    $('#inspection_date_calendar_button_1').removeAttr('disabled');
                    $('#inspection_date_calendar_button_2').removeAttr('disabled');
                    $('#inspection_date_calendar_button_3').removeAttr('disabled');
                    $('#inspection_date_calendar_button_4').removeAttr('disabled');
                    $('#inspection_date_label').html(\"<font color='black'>" . _g("検収日") . _g("：") . "</font>\");
                    $('#inspection').val('checked');
                } else {
                    gen.ui.disabled($('#inspection_date'));
                    $('#inspection_date_calendar_button_1').attr('disabled', 'disabled');
                    $('#inspection_date_calendar_button_2').attr('disabled', 'disabled');
                    $('#inspection_date_calendar_button_3').attr('disabled', 'disabled');
                    $('#inspection_date_calendar_button_4').attr('disabled', 'disabled');
                    $('#inspection_date_label').html(\"<font color='#cccccc'>" . _g("検収日") . _g("：") . "</font>\");
                    $('#inspection').val('');
                }
            }

            function check(id) {
                $('#order_detail_id_'+id).attr('checked',true);
            }
        ";

        $form['gen_fixColumnArray'] = array(
            array(
                'label' => _g("受入"),
                'name' => 'order_detail_id',
                'type' => 'checkbox',
            ),
        );

        $form['gen_columnArray'] = array(
            array(
                'label' => _g('発行日'),
                'field' => 'order_date',
                'type' => 'date',
            ),
            array(
                'label' => _g('発注先名'),
                'field' => 'customer_name',
            ),
            array(
                'label' => _g('オーダー番号'),
                'field' => 'order_no',
                'width' => '85',
                'align' => 'center',
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
                'field' => 'accepted_quantity',
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
                'helpText_noEscape' => _g('購買ロット番号を入力します。この番号を製造実績登録画面や納品登録画面で入力することで、製造や納品と使用部材ロットを結びつけることができ、トレーサビリティを実現できます。') . '<br>' .
                _g('ロット管理（トレーサビリティ）を必要としない場合は、入力の必要はありません。')
            ),
            array(
                'label' => _g("完了"),
                'width' => '40',
                'type' => 'checkbox',
                'field' => 'order_detail_completed',
                'onValue' => 'true',
                'onChange_noEscape' => "check('[id]')"
            ),
            array(
                'label' => _g('発注数'),
                'field' => 'order_detail_quantity',
                'type' => 'numeric',
            ),
            array(
                'label' => _g('受入済数'),
                'field' => 'accepted_done_quantity',
                'type' => 'numeric',
            ),
            array(
                'label' => _g('外注納期'),
                'field' => 'order_detail_dead_line',
                'type' => 'date',
            ),
            array(
                'label' => _g('製番(オーダー)'),
                'field' => 'seiban',
                'width' => '90',
                'align' => 'center',
            ),
            array(
                'label' => _g('担当者コード'),
                'field' => 'worker_code',
                'width' => '80',
                'hide' => true,
            ),
            array(
                'label' => _g('担当者名'),
                'field' => 'worker_name',
                'width' => '100',
                'hide' => true,
            ),
            array(
                'label' => _g('部門コード'),
                'field' => 'section_code',
                'width' => '80',
                'hide' => true,
            ),
            array(
                'label' => _g('部門名'),
                'field' => 'section_name',
                'width' => '100',
                'hide' => true,
            ),
            array(
                'label' => _g('外製受入備考'),
                'field' => 'remarks_header',
            ),
        );
    }

}
