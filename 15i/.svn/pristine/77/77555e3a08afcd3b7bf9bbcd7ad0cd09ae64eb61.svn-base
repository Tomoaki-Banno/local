<?php

class Monthly_Bill_List extends Base_ListBase
{

    function setSearchCondition(&$form)
    {
        global $gen_db;

        $query = "select customer_group_id, customer_group_name from customer_group_master order by customer_group_code";
        $option_customer_group = $gen_db->getHtmlOptionArray($query, false, array(null => _g("(すべて)")));

        $form['gen_searchControlArray'] = array(
            array(
                'label' => _g('得意先コード/名'),
                'type' => 'strFromTo',
                'field' => 'customer_no',
                'field2' => 'customer_name',
                'rowSpan' => 2,
            ),
            array(
                'label' => _g('請求先コード/名'),
                'type' => 'strFromTo',
                'field' => 'bill_customer_no',
                'field2' => 'bill_customer_name',
                'rowSpan' => 2,
            ),
            array(
                'label' => _g('最終請求日'),
                'type' => 'dateFromTo',
                'field' => 'last_close_date_show1',  // 表示条件fieldの最後が「_show」だと、list.tpl冒頭のonchange設定が行われない
                'rowSpan' => 2,
            ),
            array(
                'label' => _g('請求パターン'),
                'type' => 'select',
                'field' => 'customer_master___bill_pattern',
                'options' => Gen_Option::getBillPattern('search-bill'),
            ),
            array(
                'label' => _g('締日グループ'),
                'type' => 'select',
                'field' => 'customer_master___monthly_limit_date',
                'options' => Gen_Option::getMonthlyLimit('search'),
                // JS onLoad()でonchangeイベントが追加される
            ),
            array(
                'label' => _g('前回請求額'),
                'type' => 'numFromTo',
                'field' => 'last_close_amount',
                'rowSpan' => 2,
            ),
            array(
                'label' => _g('発行'),
                'type' => 'select',
                'field' => 'is_bill_data',
                'options' => array(null => _g('(すべて)'), 1 => _g('発行対象のみ')),
                'nosql' => 'true',
                'default' => 1,
            ),
            array(
                'label' => _g('請求先指定'),
                'type' => 'select',
                'field' => 'bill_customer',
                'options' => array(null => _g('(すべて)'), 1 => _g('あり'), 2 => _g('なし')),
                'nosql' => 'true',
                'default' => 2,
                'hide' => true,
            ),
            array(
                'label' => _g('取引先グループ'),
                'field' => 'customer_group_id',
                'type' => 'select',
                'options' => $option_customer_group,
            ),
        );
    }

    function convertSearchCondition($converter, &$form)
    {
        $converter->nullBlankToValue('close_date', Gen_String::getLastMonthLastDateString());   // 先月末日
    }

    function beforeLogic(&$form)
    {
        // ******** 最終請求情報の計算（テンポラリテーブル temp_last_close & temp_delivery_base） ********
        // リストには請求締日以前の納品・請求だけを含める　ag.cgi?page=ProjectDocView&pid=1574&did=226877
        $closeDate = (isset($form['close_date']) && Gen_String::isDateString($form['close_date']) ? $form['close_date'] : "2037-12-31");
        Logic_Bill::createTempLastCloseTable($closeDate, null);     // 2038年以降は日付と認識されない
    }

    function setQueryParam(&$form)
    {
        global $gen_db;

        $keyCurrency = $gen_db->queryOneValue("select key_currency from company_master");

        $classQuery = Gen_Option::getBillPattern('list-query');

        $this->selectQuery = "
            select
                customer_master.customer_id
                ,customer_master.customer_no
                ,customer_master.customer_name
                ,customer_master.template_bill
                ,t_bill_customer.bill_customer_no
                ,t_bill_customer.bill_customer_name
                ,case customer_master.bill_pattern {$classQuery} end as bill_pattern_show
                ,case when currency_name is null then '{$keyCurrency}' else currency_name end as currency_name_show
                ,case customer_master.rounding when 'round' then '" . _g("四捨五入") . "' when 'floor' then '" . _g("切捨") . "' when 'ceil' then '" . _g("切上") . "' else '' end as rounding_show
                ,case customer_master.tax_category when 1 then '" . _g("納品書単位") . "' when 2 then '" . _g("納品明細単位") . "' else '" . _g("請求書単位") . "' end as tax_category_show
                ,case customer_master.monthly_limit_date when 31 then '" . _g("末") . "' else cast(customer_master.monthly_limit_date as text) end as monthly_limit_date_show
                ,t_delivery_base.last_close_date_show1
                ,case when currency_name is null then coalesce(t_delivery_base.last_close_amount,0) else coalesce(t_delivery_base.foreign_currency_last_close_amount,0) end as last_close_amount
                ,t_delivery_base.paying_in_amount
                ,t_customer_group_1.customer_group_code as customer_group_code_1
                ,t_customer_group_1.customer_group_name as customer_group_name_1
                ,t_customer_group_2.customer_group_code as customer_group_code_2
                ,t_customer_group_2.customer_group_name as customer_group_name_2
                ,t_customer_group_3.customer_group_code as customer_group_code_3
                ,t_customer_group_3.customer_group_name as customer_group_name_3
                ,t_delivery_base.delivery_count
                ,t_delivery_base.min_delivery_date
                ,case when coalesce(t_delivery_base.delivery_count,coalesce(t_delivery_base.paying_count,0)) = 0 and coalesce(t_delivery_base.last_close_amount,0) = 0 then null
                    else t_delivery_base.pattern_count end as pattern_count
                ,case when ((customer_master.bill_pattern = 1 and (coalesce(t_delivery_base.delivery_count,0) <> 0 or coalesce(t_delivery_base.paying_count,0) <> 0))
                    or (customer_master.bill_pattern = 0 and coalesce(t_delivery_base.delivery_count,0) <> 0)) then 1 else 0 end as data_flag
            from
                customer_master
                left join (
                    select
                        customer_id
                        ,max(last_close_date_show) as last_close_date_show1
                        ,max(last_close_amount) as last_close_amount
                        ,max(foreign_currency_last_close_amount) as foreign_currency_last_close_amount
                        ,max(paying_in_amount) as paying_in_amount
                        ,max(delivery_count) as delivery_count
                        ,min(min_delivery_date) as min_delivery_date
                        ,max(paying_count) as paying_count
                        ,count(customer_id) as pattern_count
                    from
                        temp_delivery_base
                    group by
                        customer_id
                    ) as t_delivery_base on customer_master.customer_id = t_delivery_base.customer_id
                left join (select customer_id as bcid, customer_no as bill_customer_no, customer_name as bill_customer_name
                    from customer_master) as t_bill_customer on customer_master.bill_customer_id = t_bill_customer.bcid
                left join (select currency_id as curid, currency_name from currency_master) as t_currency on customer_master.currency_id = t_currency.curid
                left join customer_group_master as t_customer_group_1 on customer_master.customer_group_id_1 = t_customer_group_1.customer_group_id
                left join customer_group_master as t_customer_group_2 on customer_master.customer_group_id_2 = t_customer_group_2.customer_group_id
                left join customer_group_master as t_customer_group_3 on customer_master.customer_group_id_3 = t_customer_group_3.customer_group_id
            [Where]
                and customer_master.classification = 0
                and customer_master.bill_pattern in (0,1)
                and (customer_master.end_customer is null or customer_master.end_customer = false)
                " .
                /* 対象データをピックアップ
                 *  締め（残高表示有）： 対象期間に納品か入金が存在する得意先
                 *  締め（残高表示無）： 対象期間に納品がある得意先（入金のみの場合は請求は発生しない）
                 */
                (isset($form['gen_search_is_bill_data']) && $form['gen_search_is_bill_data'] == "1" ?
                    " and ((customer_master.bill_pattern = 1 and (coalesce(t_delivery_base.delivery_count,0) <> 0 or coalesce(t_delivery_base.paying_count,0) <> 0))
                    or (customer_master.bill_pattern = 0 and coalesce(t_delivery_base.delivery_count,0) <> 0))" : "")
                . "
            [Orderby]
        ";
        $this->orderbyDefault = "customer_no";
        $this->customColumnTables = array(
            // array(カスタム項目があるテーブル名, テーブル名のエイリアス※1, classGroup※2, parentColumn※3, 明細カスタム項目取得(省略可)※4)
            //    ※1 テーブル名のエイリアス： ListのSQL内でテーブルにエイリアスをつけている場合、そのエイリアスを指定する。
            //    ※2 classGroup:　order_headerのように複数の画面で登録が行われるテーブルの場合、登録画面のクラスグループ（ex: Manufacturing_Order）を指定する
            //    ※3 parentColumn: そのテーブルのカスタム項目をsameCellJoinで表示する際、parentColumn となるカラム。
            //          例えば受注画面であれば、customer_master は received_header_id, item_master は received_detail_id となる。
            //    ※4 明細カスタム項目取得(省略可): これをtrueにすると明細カスタム項目も取得する。ただしSQLのfromに明細カスタム項目テーブルがJOINされている必要がある。
            //          estimate_detail, received_detail, delivery_detail, order_detail
            array("customer_master", "", "", "customer_id"),
        );
    }

    function setViewParam(&$form)
    {
        $form['gen_pageTitle'] = _g("請求書発行（締め）");
        $form['gen_menuAction'] = "Menu_Delivery";
        $form['gen_listAction'] = "Monthly_Bill_List";
        $form['gen_editAction'] = "";
        $form['gen_deleteAction'] = "";
        $form['gen_idField'] = "customer_id";
        $form['gen_excel'] = "true";
        $form['gen_onLoad_noEscape'] = "onLoad()";
        $form['gen_pageHelp'] = _g("請求書発行");

        $form['gen_isClickableTable'] = "false";
        
        // エクセル出力用の特別処理。
        //  請求締日の値をパラメータとして付与する。請求締日は表示条件ではないので、gen_resotre_search_condition による復元が行われないため。
        //　ちなみにリスト表示の際は、$form['gen_beforeListUpdateScript_noEscape']で設定されているJSでパラメータ付与される。
        if (isset($form['close_date'])) {
            $form['gen_excelParam_noEscape'] = "close_date=" . h($form['close_date']);
        }

        if ($form['gen_readonly'] == 'true') {
            $form['gen_message_noEscape'] = "<BR><font color=red>" . _g("請求書発行を行う権限がありません。") . "</font>";
        } else {
            // 請求締日コントロール作成
            // JSのonLoadでchangeイベントを追加している
            $html_close_date = Gen_String::makeCalendarHtml(
                array(
                    'label' => _g("請求締日") . _g("："),
                    'name' => "close_date",
                    'value' => @$form['close_date'],
                    'size' => '85',
                    'onChange_noEscape' => 'onCloseDateChange()',
                    //'require'=>true,
                )
            );

            // 再表示したときに各項目の値を復元
            $defaultNonPrint = "";
            if (@$form['no_print'] == "checked")
                $defaultNonPrint = "value='checked' checked";
            $defaultNonZero = "";
            if (@$form['no_zero'] == "checked")
                $defaultNonZero = "value='checked' checked";
            
            $form['gen_message_noEscape'] = "
            <table border='0'>
                <tr>
                    <td valign='top' id='mrpStart'>
                        <table border='1' cellspacing='0' cellpadding='2' style='border-style: solid; border-color: #999999; border-collapse: collapse;'>
                            <tr><td style='background-color: #d5ebff; padding: 7px;'>
                                <table border='0' style='background-color: #d5ebff;'>
                                    <tr><td colspan='2'>{$html_close_date}</td></tr>
                                    <tr>
                                        <td><input type='checkbox' id='no_print' name='no_print' onchange='onNonPrintChange()' {$defaultNonPrint}> " . _g("同時に請求書を印刷しない") . "</td>
                                        <td rowspan='2'>
                                            <div id='doButton' style='margin-top:3px;'>
                                                <table border='0'><tr>
                                                    <td width='30'></td>
                                                    <td><input type='button' class='gen-button' style='width:170px' value='" . _g("請求書発行") . "' onClick='billReport()'>
                                                    <td width='15' style='cursor:pointer'>
                                                        <a id='gen_reportEditButton_Monthly_Bill_Report' class='gen_reportEditButton' title='" . _g("帳票を自由にカスタマイズすることができます。") . "' onclick=\"javascript:gen_showReportEditDialog('Monthly_Bill_Report')\"><img src='img/list/list_reportedit.png' border='0'></a>
                                                    </td>
                                                </tr></table>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr><td><input type='checkbox' id='no_zero' name='no_zero' onchange='onNonZeroChange()' {$defaultNonZero}> " . _g("請求明細に数量０を含めない") . "</td></tr>
                                </table>
                            </td></tr>
                        </table>
                    </td>
                </tr>
            </table>
            <br>" . _g("リストには、請求締日以後の納品・入金は含まれていません。") . "
            ";
        }

        // 請求書を印刷しなかった時のメッセージ
        if (isset($form['gen_nonprint']) && $form['gen_nonprint'] != "") {
            $form['gen_message_noEscape'] .= "<script>alert('" . h($form['gen_nonprint']) . "')</script>";
        }

        // リスト表示の際、請求締日をパラメータとして付与する。請求締日は表示条件ではないので、gen_resotre_search_condition による復元が行われないため。
        $form['gen_beforeListUpdateScript_noEscape'] = "
            if (param === null) param = {};
            param['close_date'] = $('#close_date').val();
        ";
        
        $form['gen_javascript_noEscape'] = "
            function onLoad() {
                // 締日グループだけは、FW標準のchange動作（即時更新）をキャンセルして onLimitDateChange() に置き換える
                $('#gen_search_customer_master___monthly_limit_date').off('change').on('change',onLimitDateChange);
                onLimitDateChange();
            }

            // 請求締日変更イベント
            function onCloseDateChange() {
                $('#gen_searchButton').click();
            }

            // 締日グループ変更イベント
            function onLimitDateChange() {
                var limitDate = $('#gen_search_customer_master___monthly_limit_date').val();
                var closeDateElm = $('#close_date');
                var closeDate = closeDateElm.val();
                gen.ajax.connect('Monthly_Bill_AjaxLimitDateParam', {limit_date : limitDate},
                    function(j) {
                        if (j.status == 'success' && j.close_date != closeDate) {
                            $('#close_date').val(j.close_date);
                        }
                        $('#gen_searchButton').click();
                    });
            }

            // 同時に請求書を印刷しないチェックボックス変更イベント
            function onNonPrintChange() {
                if ($('#no_print').is(':checked')) {
                    $('#no_print').val('checked');
                } else {
                    $('#no_print').val('');
                }
            }

            // 明細に数量０を含めないチェックボックス変更イベント
            function onNonZeroChange() {
                if ($('#no_zero').attr('checked')) {
                    $('#no_zero').val('checked');
                } else {
                    $('#no_zero').val('');
                }
            }

            // 請求書発行
            function billReport() {
                var p = {};
                $('[name^=check_]').each(function() {
                    if (this.checked) {
                        p[this.name] = this.value;
                    }
                });
                p['close_date'] = $('#close_date').val();
                gen.ajax.connect('Monthly_Bill_AjaxBillCheck', p,
                    function(j) {
                        if (j.status == 'success') {
                            if (!confirm(j.msg)) return;
                            var url = 'Monthly_Bill_Report&close_date=' + $('#close_date').val();
                            url += '&no_print=' + $('#no_print').is(':checked');
                            url += '&no_zero=' + $('#no_zero').is(':checked');
                            gen.list.printReport(url,'check');
                        } else {
                            alert(j.msg);
                            return;
                        }
                    });
            }
        ";

        $form['gen_rowColorCondition'] = array(
            "#aee7fa" => "'[pattern_count]'>'1'",
        );

        $form['gen_colorSample'] = array(
            "aee7fa" => array(_g("ブルー"), _g("複数の請求条件が存在する得意先")),
        );

        $form['gen_fixColumnArray'] = array(
            array(
                'label' => _g("発行"),
                'name' => 'check',
                'type' => 'checkbox',
                'showCondition' => ($form['gen_readonly'] != 'true' ? "true" : "false") . " && ('[data_flag]'!='0')",
            ),
            array(
                'label' => _g('得意先コード'),
                'field' => 'customer_no',
                'width' => '100',
            ),
            array(
                'label' => _g('得意先名'),
                'field' => 'customer_name',
                'width' => '200',
            ),
        );
        $form['gen_columnArray'] = array(
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
            array(
                'label' => _g('請求先コード'),
                'field' => 'bill_customer_no',
                'width' => '100',
            ),
            array(
                'label' => _g('請求先名'),
                'field' => 'bill_customer_name',
                'width' => '200',
            ),
            array(
                'label' => _g('請求条件'),
                'field' => 'pattern_count',
                'type' => 'numeric',
                'width' => '60',
                'helpText_noEscape' => _g('納品データの請求条件の状態が表示されます。'),
            ),
            array(
                'label' => _g('取引通貨'),
                'field' => 'currency_name_show',
                'width' => '60',
                'align' => 'center',
            ),
            array(
                'label' => _g('締日'),
                'field' => 'monthly_limit_date_show',
                'width' => '60',
                'align' => 'center',
                'helpText_noEscape' => _g('現在の取引先マスタの設定が表示されます。'),
            ),
            array(
                'label' => _g('最終請求日'),
                'field' => 'last_close_date_show1',     // 最後に1がついている理由は表示条件のところのコメントを参照
                'type' => 'date',
                'width' => '100',
                'align' => 'center',
            ),
            array(
                'label' => _g('前回請求額'),
                'field' => 'last_close_amount',
                'type' => 'numeric',
                'width' => '100',
                'zeroToBlank' => true,
            ),
            array(
                'label' => _g('請求後入金額'),
                'field' => 'paying_in_amount',
                'type' => 'numeric',
                'width' => '100',
                'zeroToBlank' => true,
            ),
            array(
                'label' => _g('請求後納品件数'),
                'field' => 'delivery_count',
                'type' => 'numeric',
                'width' => '100',
                'zeroToBlank' => true,
            ),
            array(
                'label' => _g('請求後最新納品日'),
                'field' => 'min_delivery_date',
                'width' => '100',
                'align' => 'center',
                'helpText_noEscape' => _g('売上計上基準が検収日の場合は検収日になります。'),
            ),
            array(
                'label' => _g('請求パターン'),
                'field' => 'bill_pattern_show',
                'width' => '130',
                'align' => 'center',
                'helpText_noEscape' => _g('現在の取引先マスタの設定が表示されます。'),
            ),
            array(
                'label' => _g('税計算単位'),
                'field' => 'tax_category_show',
                'width' => '110',
                'align' => 'center',
                'helpText_noEscape' => _g('現在の取引先マスタの設定が表示されます。'),
            ),
            array(
                'label' => _g('端数処理'),
                'field' => 'rounding_show',
                'width' => '90',
                'align' => 'center',
                'helpText_noEscape' => _g('現在の取引先マスタの設定が表示されます。'),
            ),
            array(
                'label' => _g('帳票テンプレート'),
                'field' => 'template_bill',
                'helpText_noEscape' => _g("取引先マスタの [帳票(請求書)] です。指定されている場合はそのテンプレートが使用されます。未指定の場合、テンプレート設定画面で選択されたテンプレートが使用されます。"),
                'hide' => true,
            ),
        );
    }

}
