<?php

class Master_Customer_List extends Base_ListBase
{

    function setSearchCondition(&$form)
    {
        global $gen_db;

        $query = "select customer_group_id, customer_group_name from customer_group_master order by customer_group_code";
        $option_customer_group = $gen_db->getHtmlOptionArray($query, false, array(null => _g("(すべて)")));

        $form['gen_searchControlArray'] = array(
            array(
                'label' => _g('取引先コード'),
                'field' => 'customer_no',
            ),
            array(
                'label' => _g('取引先名'),
                'field' => 'customer_name',
                'ime' => 'on',
            ),
            array(
                'label' => _g('区分'),
                'type' => 'select',
                'field' => 'classification',
                'options' => array('null' => _g("(すべて)"), 0 => _g('得意先'), 1 => _g('サプライヤー'), 2 => _g('発送先')),
            ),
            array(
                'label' => _g('取引先グループ'),
                'type' => 'select',
                'field' => 'customer_group_id',
                'options' => $option_customer_group,
            ),
            array(
                'label' => _g('取引先備考1'),
                'field' => 'remarks',
                'ime' => 'on',
                'hide' => true,
            ),
            array(
                'label' => _g('取引先備考2'),
                'field' => 'remarks_2',
                'ime' => 'on',
                'hide' => true,
            ),
            array(
                'label' => _g('取引先備考3'),
                'field' => 'remarks_3',
                'ime' => 'on',
                'hide' => true,
            ),
            array(
                'label' => _g('取引先備考4'),
                'field' => 'remarks_4',
                'ime' => 'on',
                'hide' => true,
            ),
            array(
                'label' => _g('取引先備考5'),
                'field' => 'remarks_5',
                'ime' => 'on',
                'hide' => true,
            ),
            array(
                'label' => _g('非表示取引先の表示'),
                'type' => 'select',
                'field' => 'end_customer',
                'options' => array('' => "(" . _g('すべて') . ")", '0' => _g('通常のみ'), '1' => _g('非表示のみ')),
                'nosql' => 'true',
                'default' => 'false',
                'hide' => true,
            ),
            array(
                'label' => _g('登録方法'),
                'type' => 'select',
                'field' => 'customer_master___dropdown_flag',
                'options' => array('' => _g("(すべて)"), true => _g('マスタ以外から登録された取引先')),
                'helpText_noEscape' => _g("「マスタ以外から登録された取引先」とは、各画面の取引先選択の拡張ドロップダウンから登録された取引先のことです。") . "<br><br>" . 
                    _g("たとえば受注登録画面や注文登録画面などで、取引先選択のドロップダウンに目的の取引先がない場合、その画面からジャンプしてマスタに新規登録することができます。そのようにして登録された取引先が「マスタ以外から登録された取引先」になります。") . "<br><br>" . 
                    _g("受注登録や注文登録の時点では仮に取引先登録しておき、あとから取引先マスタで項目を編集する、という場合にこの項目での絞り込みを利用すると便利です。"),
                'hide' => true,
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
        global $gen_db;

        $keyCurrency = $gen_db->queryOneValue("select key_currency from company_master");

        $classQuery = Gen_Option::getBillPattern('list-query');
        
        $this->selectQuery = "
            select
                *
                ,case classification when 0 then '" . _g("得意先") . "' when 1 then '" . _g("サプライヤー") . "' else '" . _g("発送先") . "' end as classification_show
                ,case classification when 0 then (case bill_pattern {$classQuery} end) end as bill_pattern_show
                ,case rounding when 'round' then '" . _g("四捨五入") . "' when 'floor' then '" . _g("切捨") . "' when 'ceil' then '" . _g("切上") . "' else '' end as rounding_show
                ,case when currency_name is null then '{$keyCurrency}' else currency_name end as currency_name_show
                ,case when report_language = 1 then '" . _g("英語") . "' else '" . _g("日本語") . "' end as report_language_show
                ,case classification when 0 then (case tax_category when 1 then '" . _g("納品書単位") . "' when 2 then '" . _g("納品明細単位") . "' else '" . _g("請求書単位") . "' end) end as tax_category_show
                ,case monthly_limit_date when 31 then '" . _g("末") . "' else cast(monthly_limit_date as text) end as monthly_limit_date_show
                ,case when end_customer then '" . _g("非表示") . "' else '' end as show_end_customer
                ,case when end_customer then 1 else null end as end_customer_csv
                ,case when classification = 1 then last_order_date else last_received_date end as last_trade_date
                ,cast(receivable_cycle2_month as text) || '" . _g("ヶ月後の") . "' || cast(receivable_cycle2_day as text) || '" . _g("日") . "' as receivable_cycle2_show
                ,cast(payment_cycle2_month as text) || '" . _g("ヶ月後の") . "' || cast(payment_cycle2_day as text) || '" . _g("日") . "' as payment_cycle2_show

                ,customer_master.record_create_date as gen_record_create_date
                ,customer_master.record_creator as gen_record_creater
                ,coalesce(customer_master.record_update_date, customer_master.record_create_date) as gen_record_update_date
                ,coalesce(customer_master.record_updater, customer_master.record_creator) as gen_record_updater

            from
                customer_master
                left join (select customer_group_id as gid, customer_group_code as customer_group_code_1, customer_group_name as customer_group_name_1 from customer_group_master) as t1 on customer_master.customer_group_id_1 = t1.gid
                left join (select customer_group_id as gid, customer_group_code as customer_group_code_2, customer_group_name as customer_group_name_2 from customer_group_master) as t2 on customer_master.customer_group_id_2 = t2.gid
                left join (select customer_group_id as gid, customer_group_code as customer_group_code_3, customer_group_name as customer_group_name_3 from customer_group_master) as t3 on customer_master.customer_group_id_3 = t3.gid
                left join (select customer_id as cid, max(received_date) as last_received_date from received_header group by customer_id) as t_rec on customer_master.customer_id = t_rec.cid
                left join (select partner_id as cid, max(order_date) as last_order_date from order_header group by partner_id) as t_ord on customer_master.customer_id = t_ord.cid
                left join (select currency_id as curid, currency_name from currency_master) as t_cur on customer_master.currency_id = t_cur.curid
                left join (select customer_id as cid, customer_no as bill_customer_no, customer_name as bill_customer_name from customer_master) as t_bill on customer_master.bill_customer_id = t_bill.cid
                left join (select price_percent_group_id as ppgid, price_percent_group_code, price_percent_group_name from price_percent_group_master) as t_pricepercent on customer_master.price_percent_group_id = t_pricepercent.ppgid

            [Where]
                " . (@$form['gen_search_end_customer'] == "0" ? " and (end_customer is null or end_customer = false)" : "") . "
                " . (@$form['gen_search_end_customer'] == "1" ? " and end_customer = true" : "") . "
            [Orderby]
        ";

        $this->orderbyDefault = 'customer_no';
    }

    function setCsvParam(&$form)
    {
        $form['gen_importLabel'] = _g("取引先");
        $form['gen_importMsg_noEscape'] = _g("※未請求納品データが存在する得意先の場合、下記項目を変更すると請求条件の異なる納品データが作成されます。") . "<br>" .
                _g("　　端数処理, 金額の小数点以下桁数, 税計算単位, 請求パターン");
        $form['gen_allowUpdateCheck'] = true;
        $form['gen_allowUpdateLabel'] = _g("上書き許可　（取引先コードが既存の場合はレコードを上書きする）");

        $form['gen_csvArray'] = array(
            array(
                'label' => _g('取引先コード'),
                'field' => 'customer_no',
                'unique' => true, // これを指定すると、インポート時にCSVファイル内での重複がチェックされる
            ),
            array(
                'label' => _g('取引先名'),
                'field' => 'customer_name',
            ),
            array(
                'label' => _g('取引先区分'),
                'addLabel' => _g('(0:得意先 / 1:サプライヤー / 2:発送先)'),
                'field' => 'classification',
            ),
            array(
                'label' => _g('非表示'),
                'addLabel' => _g('(1なら非表示)'),
                'field' => 'end_customer',
                'exportField' => 'end_customer_csv',
            ),
            array(
                'label' => _g('取引先グループコード1'),
                'field' => 'customer_group_code_1',
            ),
            array(
                'label' => _g('取引先グループコード2'),
                'field' => 'customer_group_code_2',
            ),
            array(
                'label' => _g('取引先グループコード3'),
                'field' => 'customer_group_code_3',
            ),
            array(
                'label' => _g('郵便番号'),
                'field' => 'zip',
            ),
            array(
                'label' => _g('住所1'),
                'field' => 'address1',
            ),
            array(
                'label' => _g('住所2'),
                'field' => 'address2',
            ),
            array(
                'label' => _g('電話番号'),
                'field' => 'tel',
            ),
            array(
                'label' => _g('FAX番号'),
                'field' => 'fax',
            ),
            array(
                'label' => _g('メールアドレス'),
                'field' => 'e_mail',
            ),
            array(
                'label' => _g('担当者名'),
                'field' => 'person_in_charge',
            ),
            array(
                'label' => _g('取引先備考1'),
                'field' => 'remarks',
            ),
            array(
                'label' => _g('取引先備考2'),
                'field' => 'remarks_2',
            ),
            array(
                'label' => _g('取引先備考3'),
                'field' => 'remarks_3',
            ),
            array(
                'label' => _g('取引先備考4'),
                'field' => 'remarks_4',
            ),
            array(
                'label' => _g('取引先備考5'),
                'field' => 'remarks_5',
            ),
            array(
                'label' => _g('端数処理'),
                'addLabel' => _g('(round:四捨五入 / floor:切捨 / ceil:切上)'),
                'field' => 'rounding',
            ),
            array(
                'label' => _g('金額の小数点以下桁数'),
                'field' => 'precision',
            ),
            
            // 得意先のみ
            array(
                'label' => _g('締日グループ'),
                'addLabel' => _g('(「31」は「末」を意味)'),
                'field' => 'monthly_limit_date',
            ),
            array(
                'label' => _g('検収リードタイム'),
                'field' => 'inspection_lead_time',
            ),
            array(
                'label' => _g('取引通貨'),
                'field' => 'currency_name',
                'exportField' => 'currency_name_show',
            ),
            array(
                'label' => _g('帳票言語区分'),
                'field' => 'report_language',
                'exportField' => 'report_language_show',
            ),
            array(
                'label' => _g('請求パターン'),
                'addLabel' => _g('(0:締め(残高表示なし) / 1:締め(残高表示あり) / 2:都度)'),
                'field' => 'bill_pattern',
            ),
            array(
                'label' => _g('請求先コード'),
                'field' => 'bill_customer_no',
            ),
            array(
                'label' => _g('税計算単位'),
                'addLabel' => _g('(0:請求書単位 / 1:納品書単位 / 2:納品明細単位)'),
                'field' => 'tax_category',
            ),
            array(
                'label' => _g('掛率'),
                'addLabel' => _g('(％)'),
                'field' => 'price_percent',
            ),
            array(
                'label' => _g('掛率グループコード'),
                'field' => 'price_percent_group_code',
            ),
            array(
                'label' => _g('売掛残高初期値'),
                'field' => 'opening_balance',
            ),
            array(
                'label' => _g('売掛基準日'),
                'field' => 'opening_date',
                'type' => 'date',
            ),
            array(
                'label' => _g('与信限度額'),
                'field' => 'credit_line',
            ),
            array(
                'label' => _g('回収サイクル1'),
                'field' => 'receivable_cycle1',
            ),
            array(
                'label' => _g('回収サイクル2（xヶ月後）'),
                'field' => 'receivable_cycle2_month',
            ),
            array(
                'label' => _g('回収サイクル2（x日）'),
                'field' => 'receivable_cycle2_day',
            ),
            array(
                'label' => _g('帳票（納品書）'),
                'field' => 'template_delivery',
            ),
            array(
                'label' => _g('帳票（請求書）'),
                'field' => 'template_bill',
            ),

            // サプライヤーのみ
            array(
                'label' => _g('標準リードタイム'),
                'field' => 'default_lead_time',
            ),
            array(
                'label' => _g('納入場所'),
                'field' => 'delivery_port',
            ),
            array(
                'label' => _g('買掛残高初期値'),
                'field' => 'payment_opening_balance',
            ),
            array(
                'label' => _g('買掛基準日'),
                'field' => 'payment_opening_date',
                'type' => 'date',
            ),
            array(
                'label' => _g('支払サイクル1'),
                'field' => 'payment_cycle1',
            ),
            array(
                'label' => _g('支払サイクル2（xヶ月後）'),
                'field' => 'payment_cycle2_month',
            ),
            array(
                'label' => _g('支払サイクル2（x日）'),
                'field' => 'payment_cycle2_day',
            ),
            array(
                'label' => _g('帳票（注文書）'),
                'field' => 'template_partner_order',
            ),
            array(
                'label' => _g('帳票（外製指示書）'),
                'field' => 'template_subcontract',
            ),
        );
    }

    function setViewParam(&$form)
    {
        global $gen_db;
        
        $form['gen_pageTitle'] = _g("取引先マスタ");
        $form['gen_menuAction'] = "Menu_Master";
        $form['gen_listAction'] = "Master_Customer_List";
        $form['gen_editAction'] = "Master_Customer_Edit";
        $form['gen_deleteAction'] = "Master_Customer_Delete";
        $form['gen_idField'] = 'customer_id';
        $form['gen_idFieldForUpdateFile'] = "customer_master.customer_id";   // これをセットすると「ファイル」「トークボード」列が自動追加される
        $form['gen_excel'] = "true";
        $form['gen_pageHelp'] = _g("取引先");

        $form['gen_isClickableTable'] = "true";     // 行をクリックして明細を開く
        $form['gen_directEditEnable'] = "true";     // 直接編集
        $form['gen_multiEditEnable'] = "true";      // 一括編集

        // 編集用エクセルファイル
        $form['gen_editExcel'] = "true";

        $form['gen_rowColorCondition'] = array(
            "#d7d7d7" => "'[show_end_customer]'!=''"     // 非表示
        );
        $form['gen_colorSample'] = array(
            "d7d7d7" => array(_g("シルバー"), _g("非表示")),
        );
        
        $query = "select price_percent_group_id, price_percent_group_name from price_percent_group_master order by price_percent_group_name";
        $option_price_percent_group = $gen_db->getHtmlOptionArray($query, true);

        $query = "select customer_group_id, customer_group_name from customer_group_master order by customer_group_code";
        $option_customer_group_id = $gen_db->getHtmlOptionArray($query, true);

        // 帳票テンプレート
        for ($i=0; $i<=3; $i++) {
            switch($i) {
                case 0: $cat = "Delivery"; break;
                case 1: $cat = "Bill"; break;
                case 2: $cat = "PartnerOrder"; break;
                case 3: $cat = "PartnerSubcontract"; break;
            }
            $info = Gen_PDF::getTemplateInfo($cat);
            $templates[$i] = array("" => "(" . _g("標準") . ")");
            foreach($info[2] as $infoOne) {
                $templates[$i][$infoOne['file']] = $infoOne['file'] . ($infoOne['comment'] == "" ? "" : " (" . $infoOne['comment'] . ")");
            }
        }
        
        $keyCurrency = $gen_db->queryOneValue("select key_currency from company_master");

        $form['gen_fixColumnArray'] = array(
            array(
                'label' => _g('明細'),
                'type' => 'edit',
            ),
            array(
                'label' => _g('コピー'),
                'type' => 'copy',
            ),
            array(
                'label'=>_g("選択"),
                //'label'=>_g("印刷"),
                'name'=>'check',
                'type'=>'checkbox',
            ),
            array(
                'label' => _g('削除'),
                'type' => 'delete_check',
                'deleteAction' => 'Master_Customer_BulkDelete',
                // readonlyであれば表示しない
                'showCondition' => ($form['gen_readonly'] != 'true' ? "true" : "false"),
            ),
            array(
                'label' => _g('取引先コード'),
                'field' => 'customer_no',
                'width' => '120',
            ),
        );
        $form['gen_columnArray'] = array(
            array(
                'label' => _g('取引先名'),
                'field' => 'customer_name',
                'width' => '200',
            ),
            array(
                'label' => _g('区分'),
                'field' => 'classification_show',
                'width' => '70',
                'align' => 'center',
                'editType'=>'select',
                'editOptions'=>array(0 => _g('得意先'), 1 => _g('サプライヤー'), 2 => _g('発送先')),
                'entryField'=>'classification',                    
            ),
            array(
                'label' => _g('取引先グループ名1'),
                'field' => 'customer_group_name_1',
                'editType'=>'select',
                'editOptions'=>$option_customer_group_id,
                'entryField'=>'customer_group_id_1',                    
                'hide' => true,
            ),
            array(
                'label' => _g('取引先グループ名2'),
                'field' => 'customer_group_name_2',
                'editType'=>'select',
                'editOptions'=>$option_customer_group_id,
                'entryField'=>'customer_group_id_2',                    
                'hide' => true,
            ),
            array(
                'label' => _g('取引先グループ名3'),
                'field' => 'customer_group_name_3',
                'editType'=>'select',
                'editOptions'=>$option_customer_group_id,
                'entryField'=>'customer_group_id_3',                    
                'hide' => true,
            ),
            array(
                'label' => _g('郵便番号'),
                'field' => 'zip',
                'width' => '70',
                'align' => 'center',
                'hide' => true,
            ),
            array(
                'label' => _g('住所1'),
                'field' => 'address1',
                'width' => '100',
                'hide' => true,
            ),
            array(
                'label' => _g('住所2'),
                'field' => 'address2',
                'width' => '100',
                'hide' => true,
            ),
            array(
                'label' => _g('電話番号'),
                'field' => 'tel',
                'width' => '90',
                'align' => 'center',
                'hide' => true,
            ),
            array(
                'label' => _g('FAX番号'),
                'field' => 'fax',
                'width' => '90',
                'align' => 'center',
                'hide' => true,
            ),
            array(
                'label' => _g('メールアドレス'),
                'field' => 'e_mail',
                'width' => '90',
                'align' => 'center',
                'hide' => true,
            ),
            array(
                'label' => _g('担当者'),
                'field' => 'person_in_charge',
                'width' => '90',
                'align' => 'center',
                'hide' => true,
            ),
            array(
                'label' => _g('取引先備考1'),
                'field' => 'remarks',
                'hide' => true,
            ),
            array(
                'label' => _g('取引先備考2'),
                'field' => 'remarks_2',
                'hide' => true,
            ),
            array(
                'label' => _g('取引先備考3'),
                'field' => 'remarks_3',
                'hide' => true,
            ),
            array(
                'label' => _g('取引先備考4'),
                'field' => 'remarks_4',
                'hide' => true,
            ),
            array(
                'label' => _g('取引先備考5'),
                'field' => 'remarks_5',
                'hide' => true,
            ),
            array(
                'label' => _g('端数処理'),
                'field' => 'rounding_show',
                'width' => '80',
                'align' => 'center',
                'editType'=>'select',
                'editOptions'=>array('round' => _g('四捨五入'), 'floor' => _g('切捨'), 'ceil' => _g('切上')),
                'entryField'=>'rounding',                    
                'hide' => true,
            ),
            array(
                'label' => _g('金額の小数点以下桁数'),
                'field' => 'precision',
                'width' => '80',
                'type' => 'numeric',
                'hide' => true,
            ),
            array(
                'label' => _g('締日グループ'),
                'field' => 'monthly_limit_date_show',
                'width' => '80',
                'align' => 'center',
                'editType'=>'select',
                'editOptions'=>Gen_Option::getMonthlyLimit('options'),
                'entryField'=>'monthly_limit_date',                    
                'hide' => true,
            ),
            array(
                'label' => _g('検収リードタイム'),
                'field' => 'inspection_lead_time',
                'hide' => true,
            ),
            array(
                'label' => _g('取引通貨'),
                'field' => 'currency_name_show',
                'editType'=>'select',
                'editOptions'=>$gen_db->getHtmlOptionArray("select null as currency_id, '{$keyCurrency}' as currency_name, '' as for_order union all select currency_id, currency_name, currency_name as for_order from currency_master order by for_order", false),
                'entryField'=>'currency_id',                    
                'hide' => true,
            ),
            array(
                'label' => _g('帳票言語区分'),
                'field' => 'report_language_show',
                'editType'=>'select',
                'editOptions'=>array('0' => _g('日本語'), '1' => _g('英語')),
                'entryField'=>'report_language',                    
                'hide' => true,
            ),
            array(
                'label' => _g('請求先コード'),
                'field' => 'bill_customer_no',
                'editType'=>'dropdown',
                'dropdownCategory'=>'customer_bill_close',
                'entryField'=>'bill_customer_id', 
                'hide' => true,
            ),
            array(
                'label' => _g('請求先名'),
                'field' => 'bill_customer_name',
                'editType'=>'none',
                'hide' => true,
            ),
            array(
                'label' => _g('請求パターン'),
                'field' => 'bill_pattern_show',
                'width' => '130',
                'align' => 'center',
                'editType'=>'select',
                'editOptions'=>Gen_Option::getBillPattern('options'),
                'entryField'=>'bill_pattern',                    
                'hide' => true,
            ),
            array(
                'label' => _g('税計算単位'),
                'field' => 'tax_category_show',
                'width' => '80',
                'align' => 'center',
                'editType'=>'select',
                'editOptions'=>array('0' => _g('請求書単位'), '1' => _g('納品書単位'), '2' => _g('納品明細単位')),
                'entryField'=>'tax_category',                    
                'hide' => true,
            ),
            array(
                'label' => _g('掛率'),
                'field' => 'price_percent',
                'width' => '70',
                'align' => 'right',
                'hide' => true,
            ),
            array(
                'label' => _g('掛率グループコード'),
                'field' => 'price_percent_group_code',
                'editType'=>'select',
                'editOptions'=>$option_price_percent_group,
                'entryField'=>'price_percent_group_id',                    
                'hide' => true,
            ),
            array(
                'label' => _g('掛率グループ名'),
                'field' => 'price_percent_group_name',
                'editType'=>'none',
                'hide' => true,
            ),
            array(
                'label' => _g('売掛残高初期値'),
                'field' => 'opening_balance',
                'type' => 'numeric',
                'width' => '100',
                'hide' => true,
            ),
            array(
                'label' => _g('売掛残高基準日'),
                'field' => 'opening_date',
                'type' => 'date',
                'width' => '100',
                'hide' => true,
            ),
            array(
                'label' => _g('与信限度額'),
                'field' => 'credit_line',
                'type' => 'numeric',
                'width' => '100',
                'hide' => true,
            ),
            array(
                'label' => _g('回収サイクル1'),
                'field' => 'receivable_cycle1',
                'width' => '90',
                'align' => 'center',
                'hide' => true,
            ),
            array(
                'label' => _g('回収サイクル2'),
                'field' => 'receivable_cycle2_show',
                'width' => '90',
                'align' => 'center',
                'editType'=>'none',     // 「xヶ月後のx日」という形式なのでダイレクト編集は無理
                'hide' => true,
            ),
            array(
                'label' => _g('標準リードタイム'),
                'field' => 'default_lead_time',
                'type' => 'numeric',
                'hide' => true,
            ),
            array(
                'label' => _g('納入場所'),
                'field' => 'delivery_port',
                'width' => '90',
                'align' => 'center',
                'hide' => true,
            ),
            array(
                'label' => _g('買掛残高初期値'),
                'field' => 'payment_opening_balance',
                'type' => 'numeric',
                'width' => '100',
                'hide' => true,
            ),
            array(
                'label' => _g('買掛残高基準日'),
                'field' => 'payment_opening_date',
                'type' => 'date',
                'width' => '100',
                'hide' => true,
            ),
            array(
                'label' => _g('支払サイクル1'),
                'field' => 'payment_cycle1',
                'width' => '90',
                'align' => 'center',
                'hide' => true,
            ),
            array(
                'label' => _g('支払サイクル2'),
                'field' => 'payment_cycle2_show',
                'width' => '90',
                'align' => 'center',
                'editType'=>'none',     // 「xヶ月後のx日」という形式なのでダイレクト編集は無理
                'hide' => true,
            ),
            array(
                'label' => _g('帳票（納品書）'),
                'field' => 'template_delivery',
                'width' => '40',
                'align' => 'center',
                'editType'=>'select',
                'editOptions'=> $templates[0],
                'hide' => true,
            ),
            array(
                'label' => _g('帳票（請求書）'),
                'field' => 'template_bill',
                'width' => '40',
                'align' => 'center',
                'editType'=>'select',
                'editOptions'=> $templates[1],
                'hide' => true,
            ),
            array(
                'label' => _g('帳票（注文書）'),
                'field' => 'template_partner_order',
                'width' => '40',
                'align' => 'center',
                'editType'=>'select',
                'editOptions'=> $templates[2],
                'hide' => true,
            ),
            array(
                'label' => _g('帳票（外製指示書）'),
                'field' => 'template_subcontract',
                'width' => '40',
                'align' => 'center',
                'editType'=>'select',
                'editOptions'=> $templates[3],
                'hide' => true,
            ),
            array(
                'label' => _g('最終取引日'),
                'field' => 'last_trade_date',
                'type' => 'date',
                'width' => '90',
                'align' => 'center',
                'editType'=>'none',
                'hide' => true,
            ),
            array(
                'label' => _g('非表示'),
                'field' => 'show_end_customer',
                'width' => '40',
                'align' => 'center',
                'editType'=>'select',
                'editOptions'=> array('false' => "", 'true' => _g('非表示')),
                'entryField'=>'end_customer',                    
                'hide' => true,
            ),
        );
    }

}
