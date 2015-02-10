<?php

class Manufacturing_BaseCost_List extends Base_ListBase
{

    function setSearchCondition(&$form)
    {
        global $gen_db;

        $query = "select item_group_id, item_group_name from item_group_master order by item_group_code";
        $option_item_group = $gen_db->getHtmlOptionArray($query, false, array(null => _g("(すべて)")));

        $query = "select worker_id, worker_name from worker_master order by worker_code";
        $option_worker = $gen_db->getHtmlOptionArray($query, false, array(null => _g("(すべて)")));

        $query = "select section_id, section_name from section_master order by section_code";
        $option_section = $gen_db->getHtmlOptionArray($query, false, array(null => _g("(すべて)")));

        // 検索項目を追加/変更したときは、このクラスのJSやReportクラスを変更する必要がある。
        $form['gen_searchControlArray'] = array(
            array(
                'label' => _g('受注日'),
                'type' => 'dateFromTo',
                'field' => 'received_date',
                'defaultFrom' => date('Y-m-01'),
                'defaultTo' => Gen_String::getThisMonthLastDateString(),
                'rowSpan' => 2,
                'nosql' => true,
            ),
            array(
                'label' => _g('納品日'),
                'type' => 'dateFromTo',
                'field' => 'delivery_date',
                'defaultFrom' => '',
                'defaultTo' => '',
                'rowSpan' => 2,
                'nosql' => true,
            ),
            array(
                'label' => _g('検収日'),
                'type' => 'dateFromTo',
                'field' => 'inspection_date',
                'defaultFrom' => '',
                'defaultTo' => '',
                'rowSpan' => 2,
                'nosql' => true,
            ),
            array(
                'label' => _g('得意先名'),
                'type' => 'dropdown',
                'field' => 'customer_id',
                'size' => '150',
                'dropdownCategory' => 'customer',
                'rowSpan' => 2,
                'nosql' => true,
            ),
            array(
                'label' => _g('予測/実績'),
                'field' => 'cost_type',
                'type' => 'select',
                'options' => array("0" => _g("予測"), "1" => _g("実績")),
                'default' => "0",
                'nosql' => true,
            ),
            array(
                'label' => _g('納品完了'),
                'field' => 'delivery_type',
                'type' => 'select',
                'options' => array("0" => _g("(すべて)"), "1" => _g("完了のみ"), "2" => _g("未完了のみ")),
                'default' => "0",
                'nosql' => true,
            ),
            array(
                'label' => _g('受注品目コード/名'),
                'field' => 'item_code',
                'field2' => 'item_name',
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
                'label' => _g('受注番号'),
                'field' => 'received_number',
                'notShowMatchBox' => true,
                'nosql' => true,
            ),
            array(
                'label' => _g('製番'),
                'field' => 'seiban',
                'notShowMatchBox' => true,
                'nosql' => true,
            ),
            array(
                'label' => _g('受注担当者'),
                'field' => 'worker_id',
                'type' => 'select',
                'options' => $option_worker,
                'nosql' => true,
                'hide' => true,
            ),
            array(
                'label' => _g('受注部門'),
                'field' => 'section_id',
                'type' => 'select',
                'options' => $option_section,
                'nosql' => true,
                'hide' => true,
            ),
        );
    }

    function convertSearchCondition($converter, &$form)
    {
    }

    function beforeLogic(&$form)
    {
        // データの取得
        Logic_BaseCost::getBaseCostReportData(
                @$form['gen_search_seiban']
                , @$form['gen_search_received_number']
                , @$form['gen_search_received_date_from']
                , @$form['gen_search_received_date_to']
                , @$form['gen_search_delivery_date_from']
                , @$form['gen_search_delivery_date_to']
                , @$form['gen_search_inspection_date_from']
                , @$form['gen_search_inspection_date_to']
                , @$form['gen_search_customer_id']
                , @$form['gen_search_worker_id']
                , @$form['gen_search_section_id']
                , @$form['gen_search_cost_type']
                , @$form['gen_search_delivery_type']
        );
    }

    function setQueryParam(&$form)
    {
        $this->selectQuery = "
            select
                *
                ,base_cost as base_cost2
            from
                temp_base_cost
                left join (select item_id as iid, item_group_id, item_group_id_2, item_group_id_3 from item_master
                    ) as t_item on temp_base_cost.item_id=t_item.iid
            [Where]
            [Orderby]
        ";
        $this->orderbyDefault = 'seiban, item_code_for_order, machining_sequence';
    }

    function setViewParam(&$form)
    {
        $form['gen_pageTitle'] = _g("原価リスト");
        $form['gen_menuAction'] = "Menu_Manufacturing";
        $form['gen_listAction'] = "Manufacturing_BaseCost_List";
        $form['gen_editAction'] = "";
        $form['gen_deleteAction'] = "";
        $form['gen_idField'] = 'seiban';
        $form['gen_excel'] = "true";
        $form['gen_pageHelp'] = _g("製番別原価");

        $form['gen_goLinkArray'] = array(
            array(
                'icon' => 'img/arrow.png',
                'onClick' => "index.php?action=Manufacturing_BaseCost_StandardCostList",
                'value' => _g('標準原価算定'),
            ),
            array(
                'icon' => 'img/arrow.png',
                'onClick' => "index.php?action=Manufacturing_BaseCost_StandardCostTotal",
                'value' => _g('標準原価リスト'),
            ),
        );
        
        $form['gen_reportArray'] = array(
            array(
                'label' => _g("原価表 印刷"),
                'link' => "javascript:reportPrint();",
                'reportEdit' => 'Manufacturing_BaseCost_Report'
            ),
        );

        $msg = "";
        if (@$form['gen_nodata']) {     // レポート表示時にデータがなかったとき
            $msg = "<span style='background-color:#ffcc99'>" . _g("該当するデータがありませんでした。") . "</span><BR><BR>";
        }
        $msg .= sprintf(_g("%sに対する受注が対象です。"), "<b>" . _g("製番品目") . "</b>") . "<BR>";
        $msg .= _g("製番品目のうち、実績・受入が未登録の品目の使用数はすべて「在庫使用数」に含まれています。また、MRP品目の使用数はすべて「在庫使用数」に含まれています。") . "<BR>";
        $form['gen_message_noEscape'] = $msg;

        // 非チェックボックス方式の帳票（表示条件に合致するレコードをすべて印刷）の場合、
        // Reportクラスではなく、「XXX_XXX_List&gen_report=XXX_XXX_Report」をactionとして指定する。
        // また gen.list.printReport() の第2引数は空欄とする。
        // すると Listクラスで現在の表示条件に合致するデータが取得され、gen_temp_for_reportテーブル
        // に挿入した上で Reportクラスが呼び出される。Reportクラスでは同テーブルからデータを取得するようにする。
        $form['gen_javascript_noEscape'] = "
            function reportPrint() {
                var postUrl = 'Manufacturing_BaseCost_List&gen_reportAction=Manufacturing_BaseCost_Report';
                gen.list.printReport(postUrl,'');
            }
        ";

        $form['gen_fixColumnArray'] = array(
            array(
                'label' => _g('受注番号'),
                'field' => 'received_number',
                'width' => '80',
                'align' => 'center',
                'sameCellJoin' => 'true',
            ),
            array(
                'label' => _g('製番'),
                'field' => 'seiban',
                'width' => '100',
                'align' => 'center',
                'sameCellJoin' => 'true',
            ),
            array(
                'label' => _g('受注品目コード'),
                'field' => 'item_code',
                'width' => '110',
                'sameCellJoin' => 'true',
                'parentColumn' => 'seiban',
            ),
            array(
                'label' => _g('受注品目名'),
                'field' => 'item_name',
                'sameCellJoin' => 'true',
                'parentColumn' => 'seiban',
            ),
        );

        $form['gen_columnArray'] = array(
            array(
                'label' => _g('受注数'),
                'field' => 'received_quantity',
                'width' => '60',
                'type' => 'numeric',
                'sameCellJoin' => 'true',
                'parentColumn' => 'seiban',
            ),
            array(
                'label' => _g('納品数'),
                'field' => 'delivery_period_quantity',
                'width' => '60',
                'type' => 'numeric',
                'sameCellJoin' => 'true',
                'parentColumn' => 'seiban',
            ),
            array(
                'label' => _g('納品合計'),
                'field' => 'delivery_quantity',
                'width' => '60',
                'type' => 'numeric',
                'sameCellJoin' => 'true',
                'parentColumn' => 'seiban',
            ),
            array(
                'label' => _g('受注残'),
                'field' => 'remained_quantity',
                'width' => '60',
                'type' => 'numeric',
                'sameCellJoin' => 'true',
                'parentColumn' => 'seiban',
            ),
            array(
                'label' => _g('単位'),
                'field' => 'measure',
                'type' => 'data',
                'width' => '35',
                'sameCellJoin' => 'true',
                'parentColumn' => 'seiban',
            ),
            array(
                'label' => _g('受注金額'),
                'field' => 'received_sum',
                'width' => '60',
                'type' => 'numeric',
                'sameCellJoin' => 'true',
                'parentColumn' => 'seiban',
            ),
            array(
                'label' => _g('合計原価'),
                'field' => 'base_cost',
                'width' => '60',
                'type' => 'numeric',
                'colorCondition' => array("#ffcc99" => "true"),
                'sameCellJoin' => 'true',
                'parentColumn' => 'seiban',
            ),
            array(
                'label' => _g('粗利'),
                'field' => 'profit',
                'width' => '60',
                'type' => 'numeric',
                'sameCellJoin' => 'true',
                'parentColumn' => 'seiban',
            ),
            array(
                'label' => _g('得意先名'),
                'field' => 'customer_name',
                'width' => '100',
                'sameCellJoin' => 'true',
                'parentColumn' => 'received_number',
            ),
            array(
                'label' => _g('受注担当者'),
                'field' => 'worker_name',
                'width' => '100',
                'sameCellJoin' => 'true',
                'parentColumn' => 'received_number',
                'hide' => true,
            ),
            array(
                'label' => _g('受注部門'),
                'field' => 'section_name',
                'width' => '100',
                'sameCellJoin' => 'true',
                'parentColumn' => 'received_number',
                'hide' => true,
            ),
            array(
                'label' => _g('受注日'),
                'field' => 'received_date',
                'type' => 'date',
                'sameCellJoin' => 'true',
                'parentColumn' => 'received_number',
            ),
            array(
                'label' => _g('受注納期'),
                'field' => 'dead_line',
                'type' => 'date',
                'sameCellJoin' => 'true',
                'parentColumn' => 'received_number',
            ),
            array(
                'label' => _g('使用品目コード'),
                'field' => 'detail_item_code',
                'sameCellJoin' => 'true',
                'parentColumn' => 'received_number',
            ),
            array(
                'label' => _g('使用品目名'),
                'field' => 'detail_item_name',
                'sameCellJoin' => 'true',
                'parentColumn' => 'received_number',
            ),
            array(
                'label' => _g('管理区分'),
                'field' => 'detail_order_class',
                'width' => '80',
                'type' => 'data',
                'align' => 'center',
                'sameCellJoin' => 'true',
                'parentColumn' => 'detail_item_code',
            ),
            array(
                'label' => _g('工順'),
                'field' => 'machining_sequence_show',
                'width' => '50',
                'align' => 'center',
                'sameCellJoin' => 'true',
                'parentColumn' => 'detail_item_code',
            ),
            array(
                'label' => _g('工程名'),
                'field' => 'process_name',
                'sameCellJoin' => 'true',
                'parentColumn' => 'detail_item_code',
            ),
            array(
                'label' => _g('工賃'),
                'field' => 'charge_price',
                'width' => '60',
                'type' => 'numeric',
                'sameCellJoin' => 'true',
                'parentColumn' => 'detail_item_code',
            ),
            array(
                'label' => _g('在庫使用数'),
                'field' => 'detail_hikiate_qty',
                'width' => '70',
                'type' => 'numeric',
                'helpText_noEscape' => '<b>' . _g('製番品目') . '</b>：' . _g('必要数のうち、製造も購入もしていない分の数です。') . '<br>' . _g('製造指示や注文を発行していても、実績・受入が未登録のものはここに含まれます。') . '<br><br><b>' . _g('MRP品目') . '</b>：' . _g('すべてここに含まれます。（つまりMRP品目はすべて標準原価で計算されます。）'),
            ),
            array(
                'label' => _g('単位'),
                'field' => 'detail_measure',
                'type' => 'data',
                'width' => '35',
            ),
            array(
                'label' => _g('標準原価'),
                'field' => 'detail_standard_base_cost',
                'width' => '70',
                'type' => 'numeric',
                'align' => 'right',
                'helpText_noEscape' => "<b>" . _g("内製品") . "</b>：" 
                    . _g("「(品目マスタの標準加工時間 * 品目マスタの工賃) + 品目マスタの外製単価 + 品目マスタの固定経費」で計算されます。") . "<br><br><b>" 
                    . _g("注文品") . "</b>：" . _g("「品目マスタの在庫評価単価」で計算されます。") . "<br><br>" 
                    . _g("下位品目分も含みます。複数の工程がある場合、下位品目分は最終工程に含まれます。") . "<br><br>" 
                    . _g("詳細は「標準原価リスト」画面で確認できます（この画面内にリンクがあります）") . "<br><br>" 
                    . _g("ただし、「在庫評価単価の更新」を行っている場合、受注納期時点の在庫評価単価更新履歴データが反映されます。"),
            ),
            array(
                'label' => _g('在庫使用分金額'),
                'field' => 'detail_hikiate_amount',
                'width' => '70',
                'type' => 'numeric',
                'colorCondition' => array("#d5ebff" => "true"),
                'helpText_noEscape' => _g('在庫使用数 × 標準原価　です。'),
            ),
            array(
                'label' => _g('製造数'),
                'field' => 'detail_achievement_qty',
                'width' => '70',
                'type' => 'numeric',
                'colorCondition' => array("#cccccc" => "[detail_order_class_number] != 0"),     // MRP品目はグレーにする。MRP品目は常に標準原価（在庫使用）なので、実績原価関連は表示されない
                'helpText_noEscape' => '<b>' . _g('製番品目') . '</b>：' . _g('製造実績を登録した数です。') . '<br>' . _g('製造指示を発行していても、実績登録前のものはここに含まれないことにご注意ください。') . '<br><br><b>' . _g('MRP品目') . '</b>：' . _g('ここには表示されません。（「在庫使用数」欄に表示されます。）'),
            ),
            array(
                'label' => _g('製造時間（分）'),
                'field' => 'detail_work_minute',
                'width' => '70',
                'type' => 'numeric',
                'helpText_noEscape' => _g('実績登録画面の「製造時間（分）」です。'),
                'colorCondition' => array("#cccccc" => "[detail_order_class_number] != 0"),
            ),
            array(
                'label' => _g('製造経費1'),
                'field' => 'detail_achievement_cost_1',
                'width' => '70',
                'type' => 'numeric',
                'helpText_noEscape' => _g('実績登録画面の「製造経費1」です。'),
                'colorCondition' => array("#cccccc" => "[detail_order_class_number] != 0"),
            ),
            array(
                'label' => _g('製造経費2'),
                'field' => 'detail_achievement_cost_2',
                'width' => '70',
                'type' => 'numeric',
                'helpText_noEscape' => _g('実績登録画面の「製造経費2」です。'),
                'colorCondition' => array("#cccccc" => "[detail_order_class_number] != 0"),
            ),
            array(
                'label' => _g('製造経費3'),
                'field' => 'detail_achievement_cost_3',
                'width' => '70',
                'type' => 'numeric',
                'helpText_noEscape' => _g('実績登録画面の「製造経費3」です。'),
                'colorCondition' => array("#cccccc" => "[detail_order_class_number] != 0"),
            ),
            array(
                'label' => _g('製造原価'),
                'field' => 'detail_achievement_amount',
                'width' => '70',
                'type' => 'numeric',
                'colorCondition' => array("#cccccc" => "[detail_order_class_number] != 0", "#d5ebff" => "true"),
                'helpText_noEscape' => _g('(品目マスタ「工賃」× 実績登録画面「製造時間（分）」) + (品目マスタ「固定経費」× 実績登録画面「製造数」) + 実績登録画面「製造経費1-3」 です。'),
            ),
            array(
                'label' => _g('購入/外製数'),
                'field' => 'detail_accepted_qty',
                'width' => '70',
                'type' => 'numeric',
                'colorCondition' => array("#cccccc" => "[detail_order_class_number] != 0"),
                'helpText_noEscape' => '<b>' . _g('製番品目') . '</b>：' . _g('注文受入を登録した数です。') . '<br>' . _g('注文書を発行していても、受入登録前のものはここに含まれないことにご注意ください。') . '<br><br><b>' . _g('MRP品目') . '</b>：' . _g('ここには表示されません。（「在庫使用数」欄に表示されます。）'),
            ),
            array(
                'label' => _g('購入単価'),
                'field' => 'detail_unit_price',
                'width' => '70',
                'type' => 'numeric',
                'colorCondition' => array("#cccccc" => "[detail_order_class_number] != 0"),
            ),
            array(
                'label' => _g('購入金額'),
                'field' => 'detail_order_amount',
                'width' => '70',
                'type' => 'numeric',
                'colorCondition' => array("#cccccc" => "[detail_order_class_number] != 0"),
            ),
            array(
                'label' => _g('購入/外製先名'),
                'field' => 'partner_name',
                'width' => '100',
                'align' => 'center',
                'colorCondition' => array("#cccccc" => "[detail_order_class_number] != 0"),
            ),
            array(
                'label' => _g('購入原価'),
                'field' => 'detail_order_base_cost',
                'width' => '70',
                'type' => 'numeric',
                'colorCondition' => array("#cccccc" => "[detail_order_class_number] != 0", "#d5ebff" => "true"),
                'helpText_noEscape' => _g('購入金額 です。'),
            ),
            array(
                'label' => _g('出庫数量'),
                'field' => 'inout_quantity',
                'width' => '70',
                'type' => 'numeric',
                'helpText_noEscape' => _g('[資材管理]-[出庫登録] で登録された出庫数量です。'),
            ),
            array(
                'label' => _g('出庫金額'),
                'field' => 'inout_amount',
                'width' => '70',
                'type' => 'numeric',
                'colorCondition' => array("#d5ebff" => "true"),
                'helpText_noEscape' => _g('[資材管理]-[出庫登録] で登録された出庫金額です。'),
            ),
            array(
                'label' => _g('原価'),
                'field' => 'detail_base_cost',
                'width' => '70',
                'type' => 'numeric',
                'colorCondition' => array("#ffcc99" => "true"),
                'helpText_noEscape' => _g('在庫使用分金額 + 製造原価 + 購入原価　です。'),
            ),
            array(
                'label' => _g('合計原価'),
                'field' => 'base_cost2',
                'width' => '80',
                'type' => 'numeric',
                'colorCondition' => array("#ffcc99" => "true"),
                'sameCellJoin' => 'true',
                'parentColumn' => 'received_number',
            ),
        );
    }

}
