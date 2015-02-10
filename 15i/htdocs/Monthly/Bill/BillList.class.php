<?php

class Monthly_Bill_BillList extends Base_ListBase
{

    function setSearchCondition(&$form)
    {
        global $gen_db;

        $query = "select customer_group_id, customer_group_name from customer_group_master order by customer_group_code";
        $option_customer_group = $gen_db->getHtmlOptionArray($query, false, array(null => _g("(すべて)")));

        $form['gen_searchControlArray'] = array(
            array(
                'label' => _g('請求締日'),
                'type' => 'dateFromTo',
                'field' => 'close_date',
                'defaultFrom' => date('Y-m-01', strtotime('-1 month')),
                'defaultTo' => Gen_String::getThisMonthLastDateString(),
            ),
           array(
                'label' => _g('回収予定日'),
                'type' => 'dateFromTo',
                'field' => 'receivable_date',
            ),
            array(
                'label' => _g('最終入金日'),
                'type' => 'dateFromTo',
                'field' => 'last_paying_in_date',
            ),
            array(
                'label' => _g('得意先コード/名'),
                'type' => 'strFromTo',
                'field' => 'customer_no',
                'field2' => 'customer_name',
            ),
            array(
                'label' => _g('請求書番号'),
                'type' => 'strFromTo',
                'field' => 'bill_number',
            ),
            array(
                'label' => _g('請求額'),
                'type' => 'numFromTo',
                'field' => 'bill_amount',
            ),
            array(
                'label' => _g('請求パターン'),
                'type' => 'select',
                'field' => 'bill_pattern',
                'options' => Gen_Option::getBillPattern('search'),
            ),
            array(
                'label' => _g('印刷状況'),
                'type' => 'select',
                'field' => 'printed',
                'options' => Gen_Option::getPrinted('search'),
                'nosql' => 'true',
                'default' => '0',
            ),
            array(
                'label' => _g('取引先グループ'),
                'field' => 'customer_group_id',
                'type' => 'select',
                'options' => $option_customer_group,
                'hide' => true,
            ),
        );
    
        // プリセット表示条件パターン
        $form['gen_savedSearchConditionPreset'] =
            array(
                _g("得意先請求履歴（今回お買い上高）") => self::_getPreset("0", "close_date_day", "customer_name", "", "sales_amount"),
                _g("得意先請求履歴（今回お買い上高 + 税）") => self::_getPreset("0", "close_date_day", "customer_name", "", "sub_total"),
                _g("得意先請求履歴（請求額(繰越含む)）") => self::_getPreset("0", "close_date_day", "customer_name", ""),
                _g("回収予定リスト（今回お買い上高）") => self::_getPreset("7", "receivable_date_day", "customer_name", "", "sales_amount"),
                _g("回収予定リスト（今回お買い上高 + 税）") => self::_getPreset("7", "receivable_date_day", "customer_name", "", "sub_total"),
                _g("回収予定リスト（請求額(繰越含む)）") => self::_getPreset("7", "receivable_date_day", "customer_name", ""),
            );
    }
    
    function _getPreset($datePattern, $horiz, $vert, $orderby, $value = "bill_amount", $method = "sum")
    {
        return
            array(
                "data" => array(
                    array("f" => "close_date", "dp" => $datePattern),
                    
                    array("f" => "gen_crossTableHorizontal", "v" => $horiz),
                    array("f" => "gen_crossTableVertical", "v" => $vert),
                    array("f" => "gen_crossTableValue", "v" => $value),
                    array("f" => "gen_crossTableMethod", "v" => $method),
                    array("f" => "gen_crossTableChart", "v" => _g("すべて")),
                ),
                "orderby" => $orderby,
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
        $classQuery = Gen_Option::getBillPattern('list-query');

        $this->selectQuery = "
            select
                bill_header.bill_header_id
                ,bill_header.bill_number
                ,bill_header.customer_id
                ,t_cust.customer_no
                ,t_cust.customer_name
                ,t_cust.template_bill
                ,bill_header.close_date
                ,bill_header.receivable_date

                ,bill_header.before_amount
                ,bill_header.paying_in
                ,coalesce(bill_header.before_amount,0) - coalesce(bill_header.paying_in,0) as kurikosi
                ,bill_header.sales_amount
                ,bill_header.tax_amount
                ,coalesce(bill_header.sales_amount,0) + coalesce(bill_header.tax_amount,0) as sub_total
                ,bill_header.bill_amount

                ,currency_master.currency_name
                ,bill_header.foreign_currency_before_amount
                ,bill_header.foreign_currency_paying_in
                ,coalesce(bill_header.foreign_currency_before_amount,0) - coalesce(bill_header.foreign_currency_paying_in,0) as foreign_currency_kurikoshi
                ,bill_header.foreign_currency_sales_amount
                ,bill_header.foreign_currency_bill_amount
                ,bill_header.bill_pattern
                ,case bill_pattern {$classQuery} end as bill_pattern_show
                ,case when bill_pattern = 2 and coalesce(bill_header.bill_amount,0) > coalesce(t_payingin.payingin_amount,0) then '" . _g("入金") . "' else '' end as payingin
                ,case
                    when bill_pattern = 2 and coalesce(bill_header.bill_amount,0) <= coalesce(t_payingin.payingin_amount,0) and coalesce(t_payingin.payingin_amount,0) <> 0 then 1
                    when bill_pattern = 2 and coalesce(t_payingin.payingin_amount,0) <> 0 then 2
                    else 0 end as paying_flag
                ,t_paying.last_paying_in_date
                ,t_paying.last_paying_in_amount
                
                ,t_customer_group_1.customer_group_code as customer_group_code_1
                ,t_customer_group_1.customer_group_name as customer_group_name_1
                ,t_customer_group_2.customer_group_code as customer_group_code_2
                ,t_customer_group_2.customer_group_name as customer_group_name_2
                ,t_customer_group_3.customer_group_code as customer_group_code_3
                ,t_customer_group_3.customer_group_name as customer_group_name_3

                ,case when detail_display = 1 then '" . _g("非表示") . "' else '" . _g("表示") . "' end as detail_display_show
                ,case when bill_printed_flag = true then '" . _g("印刷済") . "' else '' end as printed

            from
                bill_header
                inner join (select customer_id as cid, customer_no, customer_name, template_bill, customer_group_id_1, customer_group_id_2, customer_group_id_3 from customer_master) as t_cust 
                    on bill_header.customer_id = t_cust.cid
                left join customer_group_master as t_customer_group_1 on t_cust.customer_group_id_1 = t_customer_group_1.customer_group_id
                left join customer_group_master as t_customer_group_2 on t_cust.customer_group_id_2 = t_customer_group_2.customer_group_id
                left join customer_group_master as t_customer_group_3 on t_cust.customer_group_id_3 = t_customer_group_3.customer_group_id
                left join currency_master on bill_header.foreign_currency_id = currency_master.currency_id
                left join (select bill_header_id as bhid, sum(amount) as payingin_amount
                    from paying_in group by bill_header_id) as t_payingin on bill_header.bill_header_id = t_payingin.bhid
                left join (
                    select
                        customer_id as paying_customer_id
                        ,paying_in_date as last_paying_in_date
                        ,sum(amount) as last_paying_in_amount
                    from
                        paying_in
                        inner join (
                            select
                                customer_id as cid
                                ,max(paying_in_date) as max_date
                            from
                                paying_in
                            group by
                                customer_id
                            ) as t_max on paying_in.customer_id = t_max.cid
                                and paying_in.paying_in_date = t_max.max_date
                    group by
                        customer_id
                        ,paying_in_date
                    ) as t_paying on bill_header.customer_id = t_paying.paying_customer_id
            [Where]
             	" . (@$form['gen_search_printed'] == '1' ? ' and not coalesce(bill_printed_flag,false)' : '') . "
             	" . (@$form['gen_search_printed'] == '2' ? ' and bill_printed_flag' : '') . "
            [Orderby]
        ";

        $this->orderbyDefault = 'close_date desc, bill_header.customer_id';
    }

    function setViewParam(&$form)
    {
        global $gen_db;

        $form['gen_pageTitle'] = _g("請求書リスト");
        $form['gen_menuAction'] = "Monthly_Bill";
        $form['gen_listAction'] = "Monthly_Bill_BillList";
        $form['gen_deleteAction'] = "Monthly_Bill_Delete";
        $form['gen_idField'] = 'bill_header_id';
        $form['gen_excel'] = "true";
        $form['gen_pageHelp'] = _g("請求書");

        $form['gen_reportArray'] = array(
            array(
                'label' => _g("請求書 印刷"),
                'link' => "javascript:gen.list.printReport('Monthly_Bill_Report&reprint_mode','check')",
                'reportEdit' => 'Monthly_Bill_Report'
            ),
        );

        $form['gen_javascript_noEscape'] = "
            function goPayinginEdit(customerId, billHeaderId, billAmount) {
                gen.modal.open('index.php?action=Delivery_PayingIn_Edit&isBill=true&customer_id=' + customerId + '&billHeaderId=' + billHeaderId);
            }
        ";

        $form['gen_rowColorCondition'] = array(
            "#d7d7d7" => "'[paying_flag]'=='1'", // 入金完了（シルバー）
            "#aee7fa" => "'[paying_flag]'=='2'", // 一部入金（ブルー）
        );

        $form['gen_colorSample'] = array(
            "d7d7d7" => array(_g("シルバー"), _g("入金完了") . ' ' . _g("※都度請求の場合のみ")),
            "aee7fa" => array(_g("ブルー"), _g("一部入金") . ' ' . _g("※都度請求の場合のみ")),
        );

        $keyCurrency = $gen_db->queryOneValue("select key_currency from company_master");

        $form['gen_fixColumnArray'] = array(
            array(
                'label' => _g('削除'),
                'type' => 'delete_check',
                'deleteAction' => 'Monthly_Bill_BulkDelete',
                'showCondition' => ($form['gen_readonly'] != 'true' ? "true" : "false") . " && '[paying_flag]'!='1' && '[paying_flag]'!='2'",
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
                'label' => _g('請求書番号'),
                'field' => 'bill_number',
                'width' => '80',
                'align' => 'center',
            ),
            array(
                'label' => _g('請求締日'),
                'field' => 'close_date',
                'width' => '100',
                'type' => 'date',
            ),
            array(
                'label' => _g('得意先コード'),
                'field' => 'customer_no',
                'width' => '100',
                'hide' => true,
            ),
            array(
                'label' => _g('得意先名'),
                'field' => 'customer_name',
                'width' => '250',
            ),
        );
        $form['gen_columnArray'] = array(
            array(
                'label' => sprintf(_g('前回ご請求高(%s)'), $keyCurrency),
                'field' => 'before_amount',
                'width' => '100',
                'type' => 'numeric',
                'hide' => true,
            ),
            array(
                'label' => sprintf(_g('ご入金高(%s)'), $keyCurrency),
                'field' => 'paying_in',
                'width' => '100',
                'type' => 'numeric',
                'hide' => true,
            ),
            array(
                'label' => sprintf(_g('繰越高(%s)'), $keyCurrency),
                'field' => 'kurikosi',
                'width' => '100',
                'type' => 'numeric',
                'hide' => true,
            ),
            array(
                'label' => sprintf(_g('今回お買い上高(%s)'), $keyCurrency),
                'field' => 'sales_amount',
                'width' => '100',
                'type' => 'numeric',
                'hide' => true,
            ),
            array(
                'label' => sprintf(_g('消費税(%s)'), $keyCurrency),
                'field' => 'tax_amount',
                'width' => '100',
                'type' => 'numeric',
                'hide' => true,
            ),
            array(
                'label' => sprintf(_g('合計金額(%s)'), $keyCurrency),
                'field' => 'sub_total',
                'width' => '100',
                'type' => 'numeric',
                'hide' => true,
            ),
            array(
                'label' => sprintf(_g('請求額(%s)'), $keyCurrency),
                'field' => 'bill_amount',
                'width' => '100',
                'type' => 'numeric',
            ),
            array(
                'label' => _g('取引通貨'),
                'field' => 'currency_name',
                'width' => '70',
                'type' => 'date',
            ),
            array(
                'label' => _g('前回ご請求高(外貨)'),
                'field' => 'foreign_currency_before_amount',
                'width' => '100',
                'type' => 'numeric',
                'hide' => true,
            ),
            array(
                'label' => _g('ご入金高(外貨)'),
                'field' => 'foreign_currency_paying_in',
                'width' => '100',
                'type' => 'numeric',
                'hide' => true,
            ),
            array(
                'label' => _g('繰越高(外貨)'),
                'field' => 'foreign_currency_kurikoshi',
                'width' => '100',
                'type' => 'numeric',
                'hide' => true,
            ),
            array(
                'label' => _g('今回お買い上高(外貨)'),
                'field' => 'foreign_currency_sales_amount',
                'width' => '100',
                'type' => 'numeric',
                'hide' => true,
            ),
            array(
                'label' => _g('請求額(外貨)'),
                'field' => 'foreign_currency_bill_amount',
                'width' => '100',
                'type' => 'numeric',
            ),
            array(
                'label' => _g('回収予定日'),
                'field' => 'receivable_date',
                'width' => '100',
                'type' => 'date',
                'helpText_noEscape' => _g('請求締日と取引先マスタ「回収サイクル」に基づき、自動的に計算された日付です。回収予定表に反映されます。詳しくは、取引先マスタ「回収サイクル」のチップヘルプをご覧ください。'),
            ),
            array(
                'label' => _g('請求パターン'),
                'field' => 'bill_pattern_show',
                'width' => '130',
                'align' => 'center',
            ),
// ********            
            array(
                'label' => _g('数量０'),
                'field' => 'detail_display_show',
                'width' => '60',
                'align' => 'center',
                'hide' => true,
            ),
            array(
                'label' => _g('入金'),
                'field' => 'payingin',
                'width' => '50',
                'align' => 'center',
                'link' => "javascript:goPayinginEdit('[urlencode:customer_id]','[urlencode:bill_header_id]')",
            ),
            array(
                'label' => _g('最終入金日'),
                'field' => 'last_paying_in_date',
                'width' => '100',
                'type' => 'date',
            ),
            array(
                'label' => _g('最終入金合計'),
                'field' => 'last_paying_in_amount',
                'width' => '100',
                'type' => 'numeric',
            ),
            array(
                'label' => _g('帳票テンプレート'),
                'field' => 'template_bill',
                'helpText_noEscape' => _g("取引先マスタの [帳票(請求書)] です。指定されている場合はそのテンプレートが使用されます。未指定の場合、テンプレート設定画面で選択されたテンプレートが使用されます。"),
                'hide' => true,
            ),
            array(
                'label' => _g('取引先グループコード1'),
                'field' => 'customer_group_code_1',
                'hide' => true,
            ),
            array(
                'label' => _g('取引先グループ名1'),
                'field' => 'customer_group_name_1',
                'hide' => true,
            ),
            array(
                'label' => _g('取引先グループコード2'),
                'field' => 'customer_group_code_2',
                'hide' => true,
            ),
            array(
                'label' => _g('取引先グループ名2'),
                'field' => 'customer_group_name_2',
                'hide' => true,
            ),
            array(
                'label' => _g('取引先グループコード3'),
                'field' => 'customer_group_code_3',
                'hide' => true,
            ),
            array(
                'label' => _g('取引先グループ名3'),
                'field' => 'customer_group_name_3',
                'hide' => true,
            ),
        );
    }

}
