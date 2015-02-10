<?php

class Partner_PartnerEdi_List extends Base_ListBase
{

    function setSearchCondition(&$form)
    {
        $form['gen_searchControlArray'] = array(
            array(
                'label' => _g('注文書番号'),
                'field' => 'order_id_for_user',
            ),
            array(
                'label' => _g('オーダー番号'),
                'field' => 'order_no',
            ),
            array(
                'label' => _g('品目コード/名'),
                'field' => 'item_code',
                'field2' => 'item_name',
            ),
            array(
                'label' => _g('注文日'),
                'type' => 'dateFromTo',
                'field' => 'order_date',
                'size' => '80',
                'rowSpan' => 2,
            ),
            array(
                'label' => _g('注文納期'),
                'type' => 'dateFromTo',
                'field' => 'order_detail_dead_line',
                'size' => '80',
                'rowSpan' => 2,
            ),
            array(
                'label' => _g('完了分の表示'),
                'type' => 'select',
                'field' => 'completed_status',
                'options' => Gen_Option::getTrueOrFalse('search-show'),
                'nosql' => 'true',
                'default' => 'false',
            ),
            array(
                'label' => _g('印刷状況'),
                'type' => 'select',
                'field' => 'printed',
                'options' => Gen_Option::getPrinted('search'),
                'nosql' => 'true',
                //'hidePin'=>'true',
                'default' => '0',
            ),
            array(
                'label' => _g('注文備考'),
                'field' => 'order_detail___remarks',
                'field2' => 'order_header___remarks_header',
                'ime' => 'on',
            ),
        );
    }

    function convertSearchCondition($converter, &$form)
    {
    }

    function beforeLogic(&$form)
    {
    }

    function setQueryParam(&$form)
    {
        $this->selectQuery = "
            select
                order_header.order_header_id
                ,order_detail.order_detail_id
                ,order_no
                ,order_id_for_user
                ,line_no
                ,order_date
                ,item_code
                ,item_name
                ,order_detail_quantity
                ,order_measure
                ,order_detail_quantity / coalesce(multiple_of_order_measure,1) as show_quantity
                ,item_price * coalesce(multiple_of_order_measure,1) as show_price
                ,multiple_of_order_measure
                ,coalesce(order_amount, item_price * order_detail_quantity) as amount
                ,item_price
                ,currency_name
                ,foreign_currency_rate
                ,foreign_currency_item_price
                ,gen_round_precision(foreign_currency_item_price * coalesce(multiple_of_order_measure,1), customer_master.rounding, customer_master.precision) as foreign_currency_show_price
                ,coalesce(foreign_currency_order_amount, foreign_currency_item_price * order_detail_quantity) as foreign_currency_order_amount
                ,order_detail_dead_line
                ,coalesce(accepted_quantity,0) as accepted_quantity
                ,completed_status
                ,case when completed_status = 1 then '" . _g("完") . "' else
                    '" . _g("未(残") . " ' || (COALESCE(order_detail_quantity,0) - COALESCE(accepted_quantity,0)) || ')' end as completed
                ,case when order_detail_completed then 0
                    else coalesce(order_detail_quantity,0) - coalesce(accepted_quantity,0) end as remained_qty
                ,case when order_detail_completed then 0
                    else (coalesce(order_detail_quantity,0) - coalesce(accepted_quantity,0)) * item_price end as remained_amount
                ,case when t02.oid02 is null then 0 else 1 end as accepted_exist
                ,case when alarm_flag then 't' else 'f' end as alarm_flag
                ,order_header.remarks_header
                ,order_detail.remarks
                ,case when partner_order_printed_flag = true then '" . _g("印刷済") . "' else '' end as printed

                -- for csv
                ,case when foreign_currency_id is null then item_price else foreign_currency_item_price end as item_price_for_csv

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

                left join (select order_detail_id as oid02 from accepted group by order_detail_id
                    ) as t02 on order_detail.order_detail_id = t02.oid02

            [Where]
                and order_header.partner_id = {$_SESSION["user_customer_id"]}
                and order_header.classification <> 0
                " . (@$form['gen_search_completed_status'] == "false" ? " and completed_status = 0" : "") . "
             	" . ($form['gen_search_printed'] == '1' ? ' and not coalesce(partner_order_printed_flag,false)' : '') . "
                " . ($form['gen_search_printed'] == '2' ? ' and partner_order_printed_flag' : '') . "
            [Orderby]
        ";
        $this->orderbyDefault = "order_id_for_user desc, line_no, order_no";
    }

    function setCsvParam(&$form)
    {
        $form['gen_csvArray'] = array(
            array(
                'label' => _g('オーダー番号'),
                'field' => 'order_no',
                'unique' => true, // これを指定すると、インポート時にCSVファイル内での重複がチェックされる
            ),
            array(
                'label' => _g('注文書番号'),
                'field' => 'order_id_for_user',
            ),
            array(
                'label' => _g('注文日'),
                'field' => 'order_date',
            ),
            array(
                'label' => _g('品目コード'),
                'field' => 'item_code',
            ),
            array(
                'label' => _g('発注数'),
                'field' => 'order_detail_quantity',
            ),
            array(
                'label' => _g('単価'),
                'field' => 'item_price',
                'exportField' => 'item_price_for_csv',
            ),
            array(
                'label' => _g('単位'),
                'field' => 'order_measure',
            ),
            array(
                'label' => _g('単位倍数'),
                'field' => 'multiple_of_order_measure',
            ),
            array(
                'label' => _g('注文納期'),
                'field' => 'order_detail_dead_line',
            ),
            array(
                'label' => _g('注文備考'),
                'field' => 'remarks_header',
            ),
            array(
                'label' => _g('注文明細備考'),
                'field' => 'remarks',
            ),
        );
    }

    function setViewParam(&$form)
    {
        global $gen_db;

        $form['gen_pageTitle'] = _g("注文受信");
        $form['gen_menuAction'] = "Menu_PartnerUser";
        $form['gen_listAction'] = "Partner_PartnerEdi_List";
        $form['gen_editAction'] = "";
        $form['gen_deleteAction'] = "";
        $form['gen_noCsvImport'] = "true";
        $form['gen_excel'] = "true";
        $form['gen_idField'] = "order_detail_id";

        $form['gen_isClickableTable'] = "false";

        $form['gen_goLinkArray'] = array(
            array(
                'id' => 'bulkEdit',
                'value' => _g('一括出荷登録'),
                'onClick' => "javascript:location.href='index.php?action=Partner_PartnerEdi_BulkEdit'",
            ),
            array(
                'id' => 'barcodeAccept',
                'value' => _g('バーコード出荷登録'),
                'onClick' => "javascript:gen.modal.open('index.php?action=Partner_PartnerEdi_BarcodeEdit')",
            ),
        );

        $form['gen_reportArray'] = array(
            array(
                'label' => _g("現品票 印刷"),
                'link' => "javascript:gen.list.printReport('Partner_PartnerEdi_Report','check')",
                'reportEdit' => 'Partner_PartnerEdi_Report'
            ),
            array(
                'label' => _g("注文リスト 印刷"),
                'link' => "javascript:gen.list.printReport('Partner_PartnerEdi_Report2','check')",
                'reportEdit' => 'Partner_PartnerEdi_Report2'
            ),
        );

        $form['gen_javascript_noEscape'] = "";

        $form['gen_rowColorCondition'] = array(
            "#d7d7d7" => "'[completed_status]'=='1'", // 出荷済み
            "#aee7fa" => "'[completed_status]'=='0' && '[accepted_quantity]'>0", // 一部出荷済み
        );
        $form['gen_colorSample'] = array(
            "d7d7d7" => array(_g("シルバー"), _g("出荷済み")),
            "aee7fa" => array(_g("ブルー"), _g("一部出荷済み")),
        );

        $form['gen_dataMessage_noEscape'] = "";

        $keyCurrency = $gen_db->queryOneValue("select key_currency from company_master");
        $form['gen_fixColumnArray'] = array(
            array(
                'label' => _g("印刷"),
                'name' => 'check',
                'type' => 'checkbox',
            ),
            array(
                'label' => _g('印刷済'),
                'field' => 'printed',
                'width' => '47',
                'align' => 'center',
                'cellId' => 'check_[id]_printed', // 印刷時書き換え用
            ),
            array(
                'label' => _g('注文書番号'),
                'field' => 'order_id_for_user',
                'width' => '90',
                'align' => 'center',
                'sameCellJoin' => true,
            ),
            array(
                'label' => _g('行'),
                'field' => 'line_no',
                'width' => '45',
                'align' => 'center',
            ),
            array(
                'label' => _g('オーダー番号'),
                'field' => 'order_no',
                'width' => '90',
                'align' => 'center',
            ),
        );
        $form['gen_columnArray'] = array(
            array(
                'label' => _g('注文日'),
                'field' => 'order_date',
                'type' => 'date',
                'sameCellJoin' => true,
                'parentColumn' => 'order_header_id',
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
                'label' => _g('発注数'),
                'field' => 'show_quantity',
                'type' => 'numeric',
            ),
            array(
                'label' => sprintf(_g('単価(%s)'), $keyCurrency),
                'field' => 'item_price',
                'type' => 'numeric',
                'width' => '100',
            ),
            array(
                'label' => sprintf(_g('発注金額(%s)'), $keyCurrency),
                'field' => 'amount',
                'type' => 'numeric',
                'width' => '100',
            ),
            array(
                'label' => _g('発注単位'),
                'field' => 'order_measure',
                'width' => '60',
                'align' => 'left',
            ),
            array(
                'label' => sprintf(_g('注文書単価(%s)'), $keyCurrency),
                'field' => 'show_price',
                'type' => 'numeric',
                'width' => '100',
            ),
            array(
                'label' => _g('取引通貨'),
                'field' => 'currency_name',
                'width' => '50',
                'align' => 'center',
            ),
            array(
                'label' => _g('レート'),
                'field' => 'foreign_currency_rate',
                'type' => 'numeric',
            ),
            array(
                'label' => _g('単価(外貨)'),
                'field' => 'foreign_currency_item_price',
                'type' => 'numeric',
            ),
            array(
                'label' => _g('注文書単価(外貨)'),
                'field' => 'foreign_currency_show_price',
                'type' => 'numeric',
                'width' => '100',
            ),
            array(
                'label' => _g('発注金額(外貨)'),
                'field' => 'foreign_currency_order_amount',
                'type' => 'numeric',
                'width' => '100',
            ),
            array(
                'label' => _g('注文納期'),
                'field' => 'order_detail_dead_line',
            ),
            array(
                'label' => _g('注文備考'),
                'field' => 'remarks_header',
            ),
            array(
                'label' => _g('注文明細備考'),
                'field' => 'remarks',
            ),
        );
    }

}
