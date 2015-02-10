<?php

class Delivery_Delivery_BulkEdit extends Base_ListBase
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
                'label' => _g('受注番号'),
                'field' => 'received_number',
            ),
            array(
                'label' => _g('客先注番'),
                'field' => 'customer_received_number',
                'hide' => true,
            ),
            array(
                'label' => _g('受注製番'),
                'field' => 'seiban',
                'hide' => true,
            ),
            array(
                'label' => _g('得意先コード/名'),
                'field' => 'customer_no',
                'field2' => 'customer_name',
            ),
            array(
                'label' => _g('品目コード/名'),
                'field' => 'item_code',
                'field2' => 'item_name',
            ),
            array(
                'label' => _g('品目グループ'),
                'field' => 'item_group_id',
                'type' => 'select',
                'options' => $option_item_group,
                'hide' => true,
            ),
            array(
                'label' => _g('納品明細備考'),
                'field' => 'remarks',
                'hide' => true,
            ),
            array(
                'label' => _g('受注日'),
                'field' => 'received_date',
                'type' => 'dateFromTo',
            ),
            array(
                'label' => _g('受注納期'),
                'field' => 'dead_line',
                'type' => 'dateFromTo',
            ),
        );
    }

    function convertSearchCondition($converter, &$form)
    {
        $converter->nullBlankToValue('delivery_date', date('Y-m-d'));
        $converter->nullBlankToValue('inspection_date', date('Y-m-d'));
    }

    function beforeLogic(&$form)
    {
    }

    function setQueryParam(&$form)
    {
        $this->selectQuery = "
            select
                received_detail.received_detail_id
                ,received_number
                ,customer_received_number
                ,line_no
                ,customer_no
                ,customer_name
                ,delivery_customer_no
                ,delivery_customer_name
                ,item_code
                ,item_name
                ,seiban
                ,received_date
                ,coalesce(received_quantity,0) - coalesce(delivery_quantity,0) as delivery_quantity
                ,measure
                ,product_price
                ,dead_line
                ,case when received_header.guarantee_grade=0 then '" . _g("確定") . "' else '" . _g("予約") . "' end as guarantee_grade
                ,received_detail.remarks
                ,case when delivery_completed then '" . _g("完") . "' else
                  '" . _g("未(残") . "' || (COALESCE(received_quantity,0) - COALESCE(delivery_quantity,0)) || ')' end as delivery
                ,use_plan_quantity
                ,cast('' as text) as use_lot_no	-- 集計モードを「データの数」にしていたときのエラー回避
                ,t_bill_customer.bill_customer_no
                ,t_bill_customer.bill_customer_name
                ,t_last_close.last_close_date
            from
                item_master
                inner join received_detail on item_master.item_id = received_detail.item_id
                inner join received_header on received_header.received_header_id = received_detail.received_header_id
                left join (select customer_id as cid, customer_no, customer_name, bill_customer_id from customer_master) as t_customer on received_header.customer_id = t_customer.cid
                left join (select customer_id as dcid, customer_no as delivery_customer_no, customer_name as delivery_customer_name from customer_master) as t_delivery_customer on received_header.delivery_customer_id = t_delivery_customer.dcid

                /* 引当数 */
                left join (
                    select
                        use_plan.received_detail_id,
                        COALESCE(SUM(use_plan.quantity),0)+COALESCE(MAX(T0.delivery_qty),0) as use_plan_quantity
                    from
                        use_plan
                        left join (
                            select
                                received_detail_id,
                                SUM(free_stock_quantity) as delivery_qty
                            from
                                delivery_detail
                            group by
                                received_detail_id
                            ) as T0 on use_plan.received_detail_id=T0.received_detail_id
                    where
                        use_plan.received_detail_id is not null
                        AND use_plan.quantity <> 0
                        /* 引当の完了調整レコードを除く。詳しくは Logic_Reserve::getReserveQuantity 参照 */
                        /* AND (use_plan.completed_adjust_delivery_id is null) 仕様変更に伴い完了調整レコードも含める */
                    group by
                        use_plan.received_detail_id
                ) as T1 on received_detail.received_detail_id = T1.received_detail_id

                /* 納品済み数 */
                left join (
                    select
                        received_detail_id,
                        SUM(delivery_quantity) as delivery_quantity
                    from
                        delivery_detail
                    group by
                        received_detail_id
                ) as T2 on received_detail.received_detail_id = T2.received_detail_id

                /* 請求先の最終請求締日（締め請求の得意先のみ） */
                left join (
                    select
                        customer_id as bill_cid
                        ,customer_no as bill_customer_no
                        ,customer_name as bill_customer_name
                    from
                        customer_master
                    where
                        bill_pattern <> 2   /* 締め請求のみ */
                ) as t_bill_customer on coalesce(t_customer.bill_customer_id, t_customer.cid) = t_bill_customer.bill_cid
                left join (
                    select
                        customer_id as bill_header_cid
                        ,max(close_date) as last_close_date
                    from
                        bill_header
                    group by
                        customer_id
                ) as t_last_close on t_bill_customer.bill_cid = t_last_close.bill_header_cid

            [Where]
                and (delivery_completed = false or delivery_completed is null)
                and received_header.guarantee_grade=0
            [Orderby]
        ";
        $this->orderbyDefault = "received_date desc, customer_name, received_number, line_no";
    }

    function setViewParam(&$form)
    {
        global $gen_db;

        $form['gen_pageTitle'] = _g("一括納品登録");
        $form['gen_menuAction'] = "Menu_Delivery";
        $form['gen_listAction'] = "Delivery_Delivery_BulkEdit";
        $form['gen_idField'] = 'received_detail_id';
        $form['gen_onLoad_noEscape'] = "onInspectionChange()";
        $form['gen_afterListUpdateScript_noEscape'] = "onInspectionChange();";
        $form['gen_pageHelp'] = _g("一括登録");

        $form['gen_dataRowHeight'] = 25;        // データ部の1行の高さ

        $form['gen_returnUrl'] = "index.php?action=Delivery_Delivery_List&gen_restore_search_condition=true";
        $form['gen_returnCaption'] = _g('納品登録へ戻る');

        // 更新許可がなければアクセス不可
        if ($form['gen_readonly'] == 'true') {
            $form['gen_message_noEscape'] = "<br><font color=red>" . _g("一括納品登録を行う権限がありません。") . "</font>";
        } else {
            // 納品日コントロール作成
            $html_delivery_date = Gen_String::makeCalendarHtml(
                array(
                    'label' => _g("納品日") . " : ",
                    'name' => "delivery_date",
                    'value' => @$form['delivery_date'],
                    'size' => '85',
                )
            );

            // 検収日コントロール作成
            $html_inspection_date = Gen_String::makeCalendarHtml(
                array(
                    'label' => _g("検収日") . " : ",
                    'name' => "inspection_date",
                    'value' => @$form['inspection_date'],
                    'size' => '85',
                )
            );

            // 再表示したときに各項目の値を復元
            $defaultInspection = "";
            if (@$form['inspection'] == "checked")
                $defaultInspection = "value='checked' checked";

            // option（出庫ロケ）
            $opt = $gen_db->getHtmlOptionArray("select location_id, location_name from location_master order by location_code", false, array("-1" => _g('(標準ロケ)'), "0" => _g(GEN_DEFAULT_LOCATION_NAME)));
            $html_location_id = Gen_String::makeSelectHtml("location_id", $opt, @$form['location_id'], "", "Delivery_Delivery_BulkEdit", @$form['gen_pins']);

            // option（納品書まとめ）
            $opt = array("0" => _g("明細ごと"), "1" => _g("受注ごと"), "2" => _g("得意先ごと"), "3" => _g("発送先ごと"));
            $html_delivery_note_group = Gen_String::makeSelectHtml("delivery_note_group", $opt, @$form['delivery_note_group'], "", "Delivery_Delivery_BulkEdit", @$form['gen_pins']);

            $form['gen_message_noEscape'] = "
                <table border='0'>
                <tr>
                    <td align='left' nowrap>{$html_delivery_date}</td>
                    <td width='20'></td>
                    <td align='left' nowrap><input type='checkbox' id='inspection' name='inspection' onchange='onInspectionChange()' {$defaultInspection}>" . _g("検収") . "</td>
                    <td width='8'></td>
                    <td align='left' nowrap>{$html_inspection_date}</td>
                </tr><tr>
                    <td align='left' nowrap>" . _g("出庫ロケーション") . _g("：") . "{$html_location_id}</td>
                    <td></td>
                    <td align='left' colspan='3' nowrap>
                        " . _g("納品書まとめ") . _g("：") . " {$html_delivery_note_group}
                    </td>
                </tr><tr>
                    <td align='center' colspan='5'>
                        <div id=\"doButton\" style=\"margin-top:3px;\">
                            <input type=\"button\" class=\"gen-button\" value=\"&nbsp;&nbsp; " . _g("一括納品登録を実行") . " &nbsp;&nbsp;\" onClick=\"bulkEdit()\">
                        </div>
                    </td>
                </tr>
                </table>
            ";

            // リスト表示の際、gen_messageに記述しているコントロールをパラメータとして付与する。
            // gen_messageに記述しているコントロールは表示条件ではないので、gen_resotre_search_condition による復元が行われないため。
            $form['gen_beforeListUpdateScript_noEscape'] = "
                if (param === null) param = {};
                param['delivery_date'] = $('#delivery_date').val();
                param['inspection'] = $('#inspection').val();
                param['inspection_date'] = $('#inspection_date').val();
                param['location_id'] = $('#location_id').val();
                param['delivery_note_group'] = $('#delivery_note_group').val();
            ";
        }

        $query = "select receivable_report_timing from company_master";
        $timing = $gen_db->queryOneValue($query);

        $form['gen_javascript_noEscape'] = "
            function bulkEdit() {
                var frm = gen.list.table.getCheckedPostSubmit('received_detail_id', new Array('delivery_quantity','use_lot_no','delivery_completed'));
                if (frm.count == 0) {
                   alert('" . _g("データが選択されていません。") . "');
                   return;
                }

                var delDate = $('#delivery_date').val();
                var insDate = $('#inspection').is(':checked') ? $('#inspection_date').val() : '';
                var critDate = gen.date.parseDateStr(" . ($timing == '1' ? "ins" : "del") . "Date);

                var p = {};
                p['type'] = 'bulk';
                var ok = true;
                $('[name^=received_detail_id_]').each(function() {
                    if (this.checked) {
                        var lineId = this.name.replace('received_detail_id_', '');
                        var lastClose = $('#last_close_' + lineId).html();
                        if (lastClose === undefined) {
                            ok = false;
                            alert('" . _g("リスト列「最終請求締日」が非表示の状態では登録を行えません。画面右端の表示項目選択ボタンで列を表示してください。") . "');
                            return false;
                        }
                        if (lastClose != '' && gen.date.parseDateStr(lastClose) >= critDate) {
                            ok = false;
                            alert('" . sprintf(_g("得意先「[customer]」に対し、%sより後の締日の請求書（[close]日付）が発行されているため、納品登録を行えません。日付を変えるか請求書を削除してから登録してください。"), ($timing == '1' ? _g("検収日") : _g("納品日"))) . "'.replace('[customer]', $('#customer_name_' + lineId).html()).replace('[close]', lastClose));
                            return false;
                        }
                        p['id_' + lineId] = $('#delivery_quantity_' + lineId).val();
                    }
                });
                if (!ok) {
                    return;
                }
                gen.ajax.connect('Delivery_Delivery_AjaxCreditLineCheck', p,
                    function(j) {
                        if (j.status == 'warning') {
                            if (!window.confirm('" . _g("売掛残高と今回納品額の合計が与信限度額をオーバーしている得意先が存在しますが、このまま登録してもよろしいですか？") . "')) {
                                return;
                            }
                        }
                        var msg = '';
                        msg += '" . _g("実行には時間がかかる場合があります。処理が終わるまでコンピュータに手を触れずにお待ちください。実行しますか？") . "';
                        if (!window.confirm(msg)) return;

                        document.body.style.cursor = 'wait';
                        $('#doButton').html(\"<table><tr><td bgcolor='#ffcc33'>" . _g("実行中") . "...</td></tr></table>\");
                        gen.ui.disabled($('#gen_searchButton'));

                        var postUrl = 'index.php?action=Delivery_Delivery_BulkEntry';
                        postUrl += '&delivery_date=' + delDate;
                        postUrl += '&inspection_date=' + insDate;
                        postUrl += '&location_id=' + $('#location_id').val();
                        postUrl += '&delivery_note_group=' + $('#delivery_note_group').val();
                        postUrl += '&gen_restore_search_condition=true';
                        frm.submit(postUrl, null);
                    });
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
                $('#received_detail_id_'+id).attr('checked',true);
            }
        ";

        $form['gen_fixColumnArray'] = array(
            array(
                'label' => _g("納品"),
                'width' => '40',
                'type' => 'checkbox',
                'name' => 'received_detail_id',
                'align' => 'center',
            ),
        );

        $form['gen_columnArray'] = array(
            array(
                'label' => _g('受注番号'),
                'field' => 'received_number',
                'width' => '100',
                'align' => 'center',
            ),
            array(
                'label' => _g('客先注番'),
                'field' => 'customer_received_number',
                'width' => '100',
                'align' => 'center',
                'hide' => true,
            ),
            array(
                'label' => _g('行'),
                'field' => 'line_no',
                'width' => '50',
                'align' => 'center',
            ),
            array(
                'label' => _g('製番'),
                'field' => 'seiban',
                'width' => '100',
                'align' => 'center',
            ),
            array(
                'label' => _g('得意先名'),
                'field' => 'customer_name',
                'cellId' => 'customer_name_[id]'
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
                'field' => 'delivery_quantity',
                'ime' => 'off',
                'colorCondition' => array("#ffffcc" => "true"),
                'style' => 'text-align:right; background-color:#ffffcc',
                'onChange_noEscape' => "check([id])"
            ),
            array(
                'label' => _g('単位'),
                'field' => 'measure',
                'type' => 'data',
                'width' => '35',
            ),
            array(
                'label' => _g('納品単価'),
                'field' => 'product_price',
                'type' => 'numeric',
            ),
            array(
                'label' => _g('ロット番号'),
                'width' => '80',
                'type' => 'textbox',
                'align' => 'center',
                'field' => 'use_lot_no',
                'onChange_noEscape' => "check('[id]')",
                'helpText_noEscape' => _g('出荷した品目の製造・購買ロット番号を入力します。複数のロットがある場合はカンマ区切りで入力してください。') . '<br>' .
                _g('上記「製造・購買ロット番号」とは、内製品であれば製造実績画面の製造ロット番号、発注品であれば受入画面の購買ロット番号を指します。この登録により、出荷した品目の製造ロットや購買ロットを調べることができるようになり、トレーサビリティを実現できます。') . '<br>' .
                _g('ロット管理（トレーサビリティ）を必要としない場合は、入力の必要はありません。')
            ),
            array(
                'label' => _g("完了"),
                'width' => '40',
                'type' => 'checkbox',
                'field' => 'delivery_completed',
                'onValue' => 'true',
                'onChange_noEscape' => "check([id])"
            ),
            array(
                'label' => _g('受注日'),
                'field' => 'received_date',
                'type' => 'date',
            ),
            array(
                'label' => _g('受注納期'),
                'field' => 'dead_line',
                'type' => 'date',
            ),
            array(
                'label' => _g('引当数'),
                'field' => 'use_plan_quantity',
                'type' => 'numeric',
            ),
            array(
                'label' => _g('納品'),
                'field' => 'delivery',
                'width' => '80',
                'align' => 'center',
            ),
            array(
                'label' => _g('発送先コード'),
                'field' => 'delivery_customer_no',
                'type' => 'data',
                'width' => '100',
            ),
            array(
                'label' => _g('発送先名'),
                'field' => 'delivery_customer_name',
                'type' => 'data',
                'width' => '130',
            ),
            array(
                'label' => _g('請求先コード'),
                'field' => 'bill_customer_no',
                'type' => 'data',
                'width' => '100',
            ),
            array(
                'label' => _g('請求先名'),
                'field' => 'bill_customer_name',
                'type' => 'data',
                'width' => '130',
            ),
            array(
                'label' => _g('最終請求締日（締め請求のみ）'),
                'field' => 'last_close_date',
                'type' => 'date',
                'width' => '70',
                'cellId' => 'last_close_[id]'
            ),
            array(
                'label' => _g('納品明細備考'),
                'field' => 'remarks',
            ),
        );
    }

}
