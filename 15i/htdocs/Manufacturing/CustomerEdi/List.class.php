<?php

class Manufacturing_CustomerEdi_List extends Base_ListBase
{

    function setSearchCondition(&$form)
    {
        $form['gen_searchControlArray'] = array(
            array(
                'label' => _g('発注番号'),
                'field' => 'received_number',
            ),
            array(
                'label' => _g('自社注番'),
                'field' => 'customer_received_number',
            ),
            array(
                'label' => _g('品目コード/名'),
                'field' => 'item_code',
                'field2' => 'item_name',
            ),
            array(
                'label' => _g('発注日'),
                'type' => 'dateFromTo',
                'field' => 'received_date',
                'size' => '80',
                'rowSpan' => 2,
            ),
            array(
                'label' => _g('希望納期'),
                'type' => 'dateFromTo',
                'field' => 'dead_line',
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
                'label' => _g('発注登録備考'),
                'field' => 'received_detail___remarks',
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
                received_header.received_header_id
                ,received_detail.received_detail_id
                ,received_number
                ,customer_received_number
                ,line_no
                ,received_date
                ,item_code
                ,item_name
                ,received_quantity
                ,measure
                ,product_price
                ,received_quantity * product_price as amount
                ,total_amount
                ,case tax_class when 1 then '" . _g("非課税") . "' else '" . _g("課税") . "' end as tax_class
                ,dead_line
                ,case when delivery_completed then '" . _g("完") . "' else
                    '" . _g("未(残") . " ' || (coalesce(received_quantity,0) - coalesce(delivery_quantity,0)) || ')' end as delivery
                ,received_detail.remarks
                ,case when customer_received_printed_flag = true then '" . _g("印刷済") . "' else '' end as printed
                ,case when delivery_completed then 'false' else 'true' end as is_delete
                ,case when delivery_completed then 0 else coalesce(received_quantity,0) - coalesce(delivery_quantity,0) end as remained_qty
                ,case when delivery_completed then 0 else (coalesce(received_quantity,0) - coalesce(delivery_quantity,0)) * product_price end as remained_amount
                ,coalesce(delivery_quantity,0) as delivery_quantity
                ,t_delivery_customer.customer_no as delivery_customer_no
                ,t_delivery_customer.customer_name as delivery_customer_name
                ,max_delivery_date

                -- foreign_currency
                ,currency_name
                ,foreign_currency_rate
                ,foreign_currency_product_price
                ,foreign_currency_product_price * received_quantity as foreign_currency_amount
                ,received_quantity * foreign_currency_sales_base_cost as foreign_currency_sales_base_cost
                ,received_quantity * (foreign_currency_product_price - foreign_currency_sales_base_cost) as foreign_currency_sales_gross_margin

                -- for csv
                ,case when foreign_currency_id is null then product_price else foreign_currency_product_price end as product_price_for_csv
                ,case when foreign_currency_id is null then sales_base_cost else foreign_currency_sales_base_cost end as sales_base_cost_for_csv
                ,null as delivery_regist_for_csv

            from
                received_header
                inner join received_detail on received_header.received_header_id = received_detail.received_header_id
                inner join item_master on received_detail.item_id = item_master.item_id
                inner join customer_master on received_header.customer_id = customer_master.customer_id
                left join customer_master as t_delivery_customer on received_header.delivery_customer_id = t_delivery_customer.customer_id
                left join currency_master on received_detail.foreign_currency_id = currency_master.currency_id

                /* 合計金額 */
                left join (
                    select
                        received_header_id
                        ,SUM(received_quantity * product_price) as total_amount
                    from
                        received_detail
                    group by
                        received_header_id
                ) as t01 on received_header.received_header_id = t01.received_header_id

                /* 納品済み数 */
                left join (
                    select
                        received_detail_id
                        ,SUM(delivery_quantity) as delivery_quantity
                        ,MAX(delivery_date) as max_delivery_date
                    from
                        delivery_detail
                        inner join delivery_header on delivery_detail.delivery_header_id = delivery_header.delivery_header_id
                    group by
                        received_detail_id
                ) as t02 on received_detail.received_detail_id = t02.received_detail_id

            [Where]
                and received_header.customer_id = {$_SESSION["user_customer_id"]}
                " . ($form['gen_search_completed_status'] == 'false' ? ' and (not(delivery_completed) or delivery_completed is null)' : '') . "
             	" . ($form['gen_search_printed'] == '1' ? ' and not coalesce(customer_received_printed_flag,false)' : '') . "
             	" . ($form['gen_search_printed'] == '2' ? ' and customer_received_printed_flag' : '') . "
            [Orderby]
        ";
        $this->orderbyDefault = "received_number desc, line_no";
    }

    function setCsvParam(&$form)
    {
        $form['gen_csvArray'] = array(
            array(
                'label' => _g('発注番号'),
                'field' => 'received_number',
                'unique' => true, // これを指定すると、インポート時にCSVファイル内での重複がチェックされる
            ),
            array(
                'label' => _g('自社注番'),
                'field' => 'customer_received_number',
            ),
            array(
                'label' => _g('発注日'),
                'field' => 'received_date',
            ),
            array(
                'label' => _g('品目コード'),
                'field' => 'item_code',
            ),
            array(
                'label' => _g('数量'),
                'field' => 'received_quantity',
            ),
            //array(
            //    'label'=>_g('単価'),
            //    'field'=>'product_price',
            //    'exportField'=>'product_price_for_csv',
            //),
            array(
                'label' => _g('希望納期'),
                'field' => 'dead_line',
            ),
            array(
                'label' => _g('発注登録備考'),
                'field' => 'remarks',
            ),
        );
    }

    function setViewParam(&$form)
    {
        $form['gen_pageTitle'] = _g("発注登録");
        $form['gen_menuAction'] = "Menu_CustomerUser";
        $form['gen_listAction'] = "Manufacturing_CustomerEdi_List";
        $form['gen_editAction'] = "Manufacturing_CustomerEdi_Edit";
        $form['gen_deleteAction'] = "";
        $form['gen_noCsvImport'] = "true";
        $form['gen_excel'] = "true";
        $form['gen_idField'] = "received_header_id";
        $form['gen_idFieldForUpdateFile'] = "received_header.received_header_id";   // これをセットすると「ファイル」「トークボード」列が自動追加される

        $form['gen_isClickableTable'] = "true";

        $form['gen_reportArray'] = array(
            array(
                'label' => _g("発注書 印刷"),
                'link' => "javascript:gen.list.printReport('Manufacturing_CustomerEdi_Report','check')",
                'reportEdit' => 'Manufacturing_CustomerEdi_Report'
            ),
        );

        $form['gen_javascript_noEscape'] = "";

        $form['gen_rowColorCondition'] = array(
            "#d7d7d7" => "'[delivery]'=='" . _g("完") . "'", // 納品済み
            "#aee7fa" => "'[delivery_quantity]'>0", // 一部納品済み
        );
        $form['gen_colorSample'] = array(
            "d7d7d7" => array(_g("シルバー"), _g("受注転記済み")),
            "aee7fa" => array(_g("ブルー"), _g("一部納品済み")),
        );

        $form['gen_dataMessage_noEscape'] = "";

        $form['gen_fixColumnArray'] = array(
            array(
                'label' => _g('明細'),
                'type' => 'edit',
            ),
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
                'label' => _g('発注番号'),
                'field' => 'received_number',
                'width' => '90',
                'align' => 'center',
                'sameCellJoin' => true,
            ),
            array(
                'label' => _g('自社注番'),
                'field' => 'customer_received_number',
                'width' => '90',
                'align' => 'center',
                'sameCellJoin' => true,
                'parentColumn' => 'received_number',
            ),
            array(
                'label' => _g('合計金額'),
                'field' => 'total_amount',
                'type' => 'numeric',
                'sameCellJoin' => true,
                'parentColumn' => 'received_header_id',
            ),
            array(
                'label' => _g('行'),
                'field' => 'line_no',
                'width' => '45',
                'align' => 'center',
            ),
        );
        $form['gen_columnArray'] = array(
            array(
                'label' => _g('発注日'),
                'field' => 'received_date',
                'type' => 'date',
                'sameCellJoin' => true,
                'parentColumn' => 'received_header_id',
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
                'field' => 'received_quantity',
                'type' => 'numeric',
            ),
            array(
                'label' => _g('単位'),
                'field' => 'measure',
                'type' => 'data',
                'align' => 'center',
                'width' => '40',
            ),
            array(
                'label' => _g('単価'),
                'field' => 'product_price',
                'type' => 'numeric',
            ),
            array(
                'label' => _g('金額'),
                'field' => 'amount',
                'type' => 'numeric',
            ),
            array(
                'label' => _g('課税区分'),
                'field' => 'tax_class',
                'type' => 'data',
                'align' => 'center',
                'width' => '65',
            ),
            array(
                'label' => _g('希望納期'),
                'field' => 'dead_line',
                'type' => 'date',
            ),
            array(
                'label' => _g('出荷状況'),
                'field' => 'delivery',
            ),
            array(
                'label' => _g('出荷日'),
                'field' => 'max_delivery_date',
                'helpText_noEscape' => _g('分納の場合は最終出荷日が表示されます。'),
            ),
            array(
                'label' => _g('発注登録備考'),
                'field' => 'remarks',
            ),
        );
    }

}
