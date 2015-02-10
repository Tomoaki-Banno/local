<?php
class Mobile_CustomerMaster_Detail
{
    function execute(&$form)
    {
        global $gen_db;

        $form['gen_pageTitle'] = _g("取引先マスタ");
        $form['gen_headerLeftButtonURL'] = "index.php?action=Mobile_CustomerMaster_List";
        $form['gen_headerLeftButtonIcon'] = "arrow-l";
        $form['gen_headerLeftButtonText'] = _g("戻る");

        if (!isset($form['customer_no'])) {
            return "action:Mobile_CustomerMaster_List";
        }

        // カスタム項目
        $customSelectList = "";
        if (isset($form['gen_customColumnArray'])) {
            foreach($form['gen_customColumnArray'] as $customCol => $customArr) {
                $customSelectList .= ",{$form['gen_customColumnTable']}.{$customCol} as gen_custom_{$customCol}";
            }
        }
        
        $keyCurrency = $gen_db->queryOneValue("select key_currency from company_master");
        $billPattern = Gen_Option::getBillPattern('list-query');
        $query = "
            select
                -- end_customerの置き換えを行っているので * は使えない
                customer_master.customer_id
                ,customer_master.customer_no
                ,customer_master.customer_name
                ,case classification when 0 then '" . _g("得意先") . "' when 1 then '" . _g("サプライヤー") . "' else '" . _g("発送先") . "' end as classification
                ,case when end_customer then '" . _g("非表示") . "' else '' end as end_customer
                ,customer_master.zip
                ,customer_master.address1
                ,customer_master.address2
                ,customer_master.tel
                ,customer_master.fax
                ,customer_master.e_mail
                ,customer_master.person_in_charge
                ,customer_master.remarks
                ,customer_master.remarks_2
                ,customer_master.remarks_3
                ,customer_master.remarks_4
                ,customer_master.remarks_5
                ,case rounding when 'round' then '" . _g("四捨五入") . "' when 'floor' then '" . _g("切捨") . "' when 'ceil' then '" . _g("切上") . "' else '' end as rounding
                ,customer_master.precision
                ,customer_master.inspection_lead_time
                ,case when currency_name is null then '{$keyCurrency}' else currency_name end as currency_name
                ,case when report_language = 1 then '" . _g("英語") . "' else '" . _g("日本語") . "' end as report_language
                ,case when customer_master.dropdown_flag then '"._g("ドロップダウンから追加")."' else '' end as dropdown_flag
                ,case bill_pattern {$billPattern} end as bill_pattern
                ,case monthly_limit_date when 31 then '" . _g("末") . "' else cast(monthly_limit_date as text) end as monthly_limit_date
                ,case tax_category when 1 then '" . _g("納品書単位") . "' when 2 then '" . _g("納品明細単位") . "' else '" . _g("請求書単位") . "' end as tax_category
                ,customer_master.price_percent
                ,price_percent_group_name
                ,customer_master.opening_balance
                ,customer_master.opening_date
                ,customer_master.credit_line
                ,cast(customer_master.receivable_cycle1 as text) || '" . _g("日後に請求") . "' as receivable_cycle1
                ,cast(receivable_cycle2_month as text) || '" . _g("ヶ月後の") . "' || cast(receivable_cycle2_day as text) || '" . _g("日") . "' as receivable_cycle2
                ,customer_master.default_lead_time
                ,customer_master.delivery_port
                ,customer_master.payment_opening_balance
                ,customer_master.payment_opening_date
                ,cast(customer_master.payment_cycle1 as text) || '" . _g("日後に支払") . "' as payment_cycle1
                ,cast(payment_cycle2_month as text) || '" . _g("ヶ月後の") . "' || cast(payment_cycle2_day as text) || '" . _g("日") . "' as payment_cycle2
                ,bill_customer_name

                ,case when classification = 1 then last_order_date else last_received_date end as last_trade_date
                {$customSelectList}

                ,coalesce(customer_master.record_update_date, customer_master.record_create_date) as gen_last_update
                ,coalesce(customer_master.record_updater, customer_master.record_creator) as gen_last_updater
            from
                customer_master
                left join (select customer_id as cid, max(received_date) as last_received_date from received_header group by customer_id) as t_rec on customer_master.customer_id = t_rec.cid
                left join (select partner_id as cid, max(order_date) as last_order_date from order_header group by partner_id) as t_ord on customer_master.customer_id = t_ord.cid
                left join (select currency_id as curid, currency_name from currency_master) as t_cur on customer_master.currency_id = t_cur.curid
                left join (select customer_id as cid, customer_no as bill_customer_no, customer_name as bill_customer_name from customer_master) as t_bill on customer_master.bill_customer_id = t_bill.cid
                left join (select price_percent_group_id as ppgid, price_percent_group_code, price_percent_group_name from price_percent_group_master) as t_pricepercent on customer_master.price_percent_group_id = t_pricepercent.ppgid
            where
                customer_no = '{$form['customer_no']}'
            ";
        $form['gen_data'] = $gen_db->getArray($query);

        // フリックによるレコード遷移
        $listAction = "Mobile_CustomerMaster_List";
        $detailAction = "Mobile_CustomerMaster_Detail";
        $tableName = "customer_master";
        $where = "";    // ListのSQLとあわせておく。本来は表示条件も読みだして反映すべき
        $idColumn = "customer_no";  // このDetailページが呼ばれるときのキーパラメータ。DBカラム名でもある必要がある
        $defaultSortColumn = "customer_no";

        // 以下はフリック用共通コード。いずれどこかに切り出す
        $userId = Gen_Auth::getCurrentUserId();
        $query = "select orderby from page_info where user_id = '{$userId}' and action = '{$listAction}'";
        $sortColumn = $gen_db->queryOneValue($query);
        if ($sortColumn=='') $sortColumn = $defaultSortColumn;
        $query = "select prev_id, next_id from (select {$idColumn} as id, lag({$idColumn},1) over(order by {$sortColumn}) as prev_id, lead({$idColumn},1) over(order by {$sortColumn}) as next_id
            from {$tableName} where 1=1 {$where}) as t_temp where id = '".$form[$idColumn]."'";
        $obj = $gen_db->queryOneRowObject($query);
        if ($obj->prev_id) $form['gen_prevAction'] = $detailAction . "&{$idColumn}=".$obj->prev_id;
        if ($obj->next_id) $form['gen_nextAction'] = $detailAction . "&{$idColumn}=".$obj->next_id;

        // カラム
        $form['gen_columnArray'] =
            array(
                array(
                    'label'=>_g('取引先コード'),
                    'field'=>'customer_no',
                ),
                array(
                    'label'=>_g('取引先名'),
                    'field'=>'customer_name',
                ),
                array(
                    'label'=>_g('区分'),
                    'field'=>'classification',
                ),
                array(
                    'label'=>_g('最終取引日'),
                    'field'=>'last_trade_date',
                ),
                array(
                    'label'=>_g('非表示'),
                    'field'=>'end_customer',
                ),
                array(
                    'label'=>_g('郵便番号'),
                    'field'=>'zip',
                ),
                array(
                    'label'=>_g('住所1'),
                    'field'=>'address1',
                ),
                array(
                    'label'=>_g('住所2'),
                    'field'=>'address2',
                ),
                array(
                    'label'=>_g('TEL'),
                    'field'=>'tel',
                ),
                array(
                    'label'=>_g('FAX'),
                    'field'=>'fax',
                ),
                array(
                    'label'=>_g('メールアドレス'),
                    'field'=>'e_mail',
                ),
                array(
                    'label'=>_g('担当者'),
                    'field'=>'person_in_charge',
                ),
                array(
                    'label'=>_g('取引先備考1'),
                    'field'=>'remarks',
                ),
                array(
                    'label'=>_g('取引先備考2'),
                    'field'=>'remarks_2',
                ),
                array(
                    'label'=>_g('取引先備考3'),
                    'field'=>'remarks_3',
                ),
                array(
                    'label'=>_g('取引先備考4'),
                    'field'=>'remarks_4',
                ),
                array(
                    'label'=>_g('取引先備考5'),
                    'field'=>'remarks_5',
                ),
                array(
                    'label'=>_g('端数処理'),
                    'field'=>'rounding',
                ),
                array(
                    'label'=>_g('金額の小数点以下桁数'),
                    'field'=>'precision',
                ),
                array(
                    'label'=>_g('取引通貨'),
                    'field'=>'currency_name',
                ),
                array(
                    'label'=>_g('帳票言語区分'),
                    'field'=>'report_language',
                ),
                array(
                    'label'=>_g('検収リードタイム'),
                    'field'=>'inspection_lead_time',
                ),
                array(
                    'label'=>_g("●得意先のみ"),
                    'sectionHeader'=>true,
                ),
                array(
                    'label'=>_g('税計算単位'),
                    'field'=>'tax_category',
                ),
                array(
                    'label'=>_g('請求先'),
                    'field'=>'bill_customer_name',
                ),
                array(
                    'label'=>_g('掛率（％）'),
                    'field'=>'price_percent',
                ),
                array(
                    'label'=>_g('掛率グループ'),
                    'field'=>'price_percent_group_name',
                ),
                array(
                    'label'=>_g('請求パターン'),
                    'field'=>'bill_pattern',
                ),
                array(
                    'label'=>_g('締日グループ'),
                    'field'=>'monthly_limit_date',
                ),
                array(
                    'label'=>_g('売掛残高初期値'),
                    'field'=>'opening_balance',
                    'numberFormat'=>'true',
                ),
                array(
                    'label'=>_g('売掛基準日'),
                    'field'=>'opening_date',
                ),
                array(
                    'label'=>_g('与信限度額'),
                    'field'=>'credit_line',
                    'numberFormat'=>'true',
                ),
                array(
                    'label'=>_g('回収サイクル1'),
                    'field'=>'receivable_cycle1',
                ),
                array(
                    'label'=>_g('回収サイクル2'),
                    'field'=>'receivable_cycle2',
                ),
                array(
                    'label'=>_g("●サプライヤーのみ"),
                    'sectionHeader'=>true,
                ),
                array(
                    'label'=>_g('標準リードタイム'),
                    'field'=>'default_lead_time',
                ),
                array(
                    'label'=>_g('納入場所'),
                    'field'=>'delivery_port',
                ),
                array(
                    'label'=>_g('買掛残高初期値'),
                    'field'=>'payment_opening_balance',
                    'numberFormat'=>'true',
                ),
                array(
                    'label'=>_g('買掛基準日'),
                    'field'=>'payment_opening_date',
                ),
                array(
                    'label'=>_g('支払サイクル1'),
                    'field'=>'payment_cycle1',
                ),
                array(
                    'label'=>_g('支払サイクル2'),
                    'field'=>'payment_cycle2',
                ),
            );
        
        // カスタム項目
        if (isset($form['gen_customColumnArray'])) {
            $form['gen_columnArray'][] =
                array(
                    'label' => "●" . _g("フィールド・クリエイター"),
                    'sectionHeader'=>true,
                );
            foreach($form['gen_customColumnArray'] as $customCol => $customArr) {
                $form['gen_columnArray'][] =
                    array(
                        'label' => $customArr[1],
                        'field' => "gen_custom_{$customCol}",
                    );
            }
        }

        return 'mobile_detail.tpl';
    }
}