<?php

class Partner_PartnerEdi_BulkEdit extends Base_ListBase
{

    function setSearchCondition(&$form)
    {
        $form['gen_searchControlArray'] = array(
            array(
                'label' => _g('注文書番号'),
                'type' => 'numFromTo',
                'field' => 'order_id_for_user',
                'size' => '80',
                'rowSpan' => 2,
            ),
            array(
                'label' => _g('オーダー番号'),
                'type' => 'strFromTo',
                'field' => 'order_no',
                'size' => '100',
                'rowSpan' => 2,
            ),
            array(
                'label' => _g('品目コード/名'),
                'field' => 'order_detail___item_code',
                'field2' => 'order_detail___item_name',
            ),
            array(
                'label' => _g('注文備考'),
                'field' => 'order_detail___remarks',
                'field2' => 'order_header___remarks_header',
                'ime' => 'on',
            ),
            array(
                'label' => _g('注文日'),
                'field' => 'order_date',
                'type' => 'dateFromTo',
                'rowSpan' => 2,
            ),
            array(
                'label' => _g('注文納期'),
                'field' => 'order_detail_dead_line',
                'type' => 'dateFromTo',
                'rowSpan' => 2,
            ),
        );
    }

    function convertSearchCondition($converter, &$form)
    {
        $converter->nullBlankToValue('accepted_date', date('Y-m-d'));
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
                ,order_detail.order_measure
                ,order_detail.order_detail_completed
                ,order_detail.seiban
                ,case when order_detail_completed then '" . _g("完了") . "' else '' end as order_detail_completed
                ,order_detail.remarks
                ,cast('' as text) as lot_no	-- 集計モードを「データの数」にしていたときのエラー回避

            from
                order_header
                inner join order_detail on order_header.order_header_id = order_detail.order_header_id
                inner join customer_master on order_header.partner_id = customer_master.customer_id
                left join currency_master on order_detail.foreign_currency_id = currency_master.currency_id

                /* 完了済み */
                left join (
                    select
                        order_detail_id as oid01,
                        (case when order_detail_completed then 1 else 0 end) as completed_status
                    from
                        order_detail
                ) as t01 on order_detail.order_detail_id = t01.oid01

                left join (select order_detail_id as oid02
                    from accepted group by order_detail_id) as t02 on order_detail.order_detail_id = t02.oid02

            [Where]
                and order_header.partner_id = {$_SESSION["user_customer_id"]}
                and order_header.classification <> 0
                and completed_status = 0
            [Orderby]
        ";

        $this->orderbyDefault = 'order_id_for_user, line_no';
    }

    function setViewParam(&$form)
    {
        $form['gen_pageTitle'] = _g("一括出荷登録");
        $form['gen_menuAction'] = "Menu_PartnerUser";
        $form['gen_listAction'] = "Partner_PartnerEdi_BulkEdit";
        $form['gen_idField'] = 'order_detail_id';
        $form['gen_onLoad_noEscape'] = "";

        $form['gen_dataRowHeight'] = 25;        // データ部の1行の高さ

        $form['gen_returnUrl'] = "index.php?action=Partner_PartnerEdi_List&gen_restore_search_condition=true";
        $form['gen_returnCaption'] = _g('注文受信へ戻る');

        // 出荷日コントロール作成
        $html_accepted_date = Gen_String::makeCalendarHtml(
            array(
                'label' => _g("出荷日") . _g("："),
                'name' => "accepted_date",
                'value' => @$form['accepted_date'],
                'size' => '85',
                'require' => true,
                'pinForm' => "Partner_PartnerEdi_BulkEdit",
                'genPins' => @$form['gen_pins'],
            )
        );

        $form['gen_message_noEscape'] = "
            <table border='0'>
                <tr>
                    <td align='center'>{$html_accepted_date}</td>
                </tr>
                <tr>
                    <td align='center'>
                        <div id=\"doButton\" style=\"margin-top:3px;\">
                            <input type=\"button\" class=\"gen-button\" value=\"&nbsp;&nbsp; " . _g("一括出荷登録を実行") . " &nbsp;&nbsp;\" onClick=\"bulkEdit()\">
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
        ";

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

                var postUrl = 'index.php?action=Partner_PartnerEdi_BulkEntry';
                postUrl += '&accepted_date=' + $('#accepted_date').val();
                postUrl += '&gen_restore_search_condition=true';
                frm.submit(postUrl, null);
            }

            function check(id) {
                $('#order_detail_id_'+id).attr('checked',true);
            }
        ";

        $form['gen_fixColumnArray'] = array(
            array(
                'label' => _g("出荷"),
                'name' => 'order_detail_id',
                'type' => 'checkbox',
            ),
        );
        $form['gen_columnArray'] = array(
            array(
                'label' => _g('注文日'),
                'field' => 'order_date',
                'width' => '80',
                'align' => 'center',
            ),
            array(
                'label' => _g('注文書番号'),
                'field' => 'order_id_for_user',
                'width' => '85',
                'align' => 'center',
            ),
            array(
                'label' => _g('行'),
                'field' => 'line_no',
                'width' => '30',
                'align' => 'center',
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
                'label' => _g('注文納期'),
                'field' => 'order_detail_dead_line',
                'type' => 'date',
            ),
            array(
                'label' => _g('発注数'),
                'field' => 'order_detail_quantity',
                'type' => 'numeric',
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
                'field' => 'order_measure',
                'type' => 'data',
                'width' => '40',
            ),
            array(
                'label' => _g('ロット番号'),
                'width' => '120',
                'type' => 'textbox',
                'align' => 'center',
                'field' => 'lot_no',
                'onChange_noEscape' => "check('[id]')",
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
                'label' => _g('出荷済数'),
                'field' => 'accepted_done_quantity',
                'type' => 'numeric',
            ),
            array(
                'label' => _g('注文備考'),
                'field' => 'remarks_header',
                'hide' => true,
            ),
            array(
                'label' => _g('注文明細備考'),
                'field' => 'remarks',
            ),
        );
    }

}