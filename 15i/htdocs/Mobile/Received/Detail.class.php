<?php

class Mobile_Received_Detail
{
    function execute(&$form)
    {
        global $gen_db;

        $form['gen_pageTitle'] = _g("受注明細");
        $form['gen_headerLeftButtonURL'] = "index.php?action=Mobile_Received_List";
        $form['gen_headerLeftButtonIcon'] = "arrow-l";
        $form['gen_headerLeftButtonText'] = _g("戻る");

        if (!$form['gen_readonly']) {
            $form['gen_headerRightButtonURL'] = "javascript:received_delete()";
            $form['gen_headerRightButtonIcon'] = "delete";
            $form['gen_headerRightButtonText'] = _g("削除");
        }

        // 削除ボタンを設置したため印刷機能は無効化してあるが、下記を有効にすれば使える
//        $form['gen_headerRightButtonURL'] = "index.php?action=Manufacturing_Received_Report&detail=true&check_" . $form['received_detail_id'];
//        $form['gen_headerRightButtonIcon'] = "";
//        $form['gen_headerRightButtonText'] = _g("印刷(PDF)");
//        $form['gen_headerRightButtonParam'] = "target='_blank' data_ajax='false'";  // 印刷のときはこの設定が必須

        if (!isset($form['received_header_id'])) {
            if (isset($form['received_detail_id'])) {
                $query = "select received_header_id from received_detail where received_detail_id = '{$form['received_detail_id']}'";
                $form['received_header_id'] = $gen_db->queryOneValue($query);
            } else {
                return "action:Mobile_Received_List";
            }
        }
        
        // カスタム項目
        $customSelectList = "";
        if (isset($form['gen_customColumnArray'])) {
            foreach($form['gen_customColumnArray'] as $customCol => $customArr) {
                $customSelectList .= ",{$form['gen_customColumnTable']}.{$customCol} as gen_custom_{$customCol}";
            }
        }

        $keyCurrency = $gen_db->queryOneValue("select key_currency from company_master");
        $query = "
            select
                received_number
                ,customer_no
                ,customer_name
                ,received_date
                ,remarks_header
                ,section_name
                ,worker_name
                {$customSelectList}
            from
                received_header
                inner join customer_master on received_header.customer_id = customer_master.customer_id
                left join section_master on received_header.section_id = section_master.section_id
                left join worker_master on received_header.worker_id = worker_master.worker_id
            where
                received_header.received_header_id = '{$form['received_header_id']}'
            ";
        $form['gen_data'] = $gen_db->getArray($query);
        
        $form['gen_javascript_noEscape'] = "
            function received_delete() {
                if (window.confirm('"._g("このレコードを削除してもよろしいですか？")."')) {
                    location.href='index.php?action=Manufacturing_Received_BulkDelete&delete_" . h($form['received_header_id']) ."';
                }    
            }
        ";

        // フリックによるレコード遷移 ⇒ detail.tplでも無効化してある
//        $listAction = "Mobile_Received_List";
//        $detailAction = "Mobile_Received_Detail";
//        $tableName = "received_header inner join (select received_header_id as hid from received_detail
//            where (not(delivery_completed) or delivery_completed is null) group by hid) as t1
//            on received_header.received_header_id = t1.hid";     // いちおう完了分は非表示としている。本来は表示条件を読みだして反映すべき
//        $where = "";    // ListのSQLとあわせておく。本来は表示条件も読みだして反映すべき
//        $idColumn = "received_header_id";  // このDetailページが呼ばれるときのキーパラメータ。DBカラム名でもある必要がある
//        $defaultSortColumn = "received_number";

        // 以下はフリック用共通コード。いずれどこかに切り出す
//        $userId = Gen_Auth::getCurrentUserId();
//        $query = "select orderby from page_info where user_id = {$userId} and action = '{$listAction}'";
//        $sortColumn = $gen_db->queryOneValue($query);
//        if ($sortColumn=='') $sortColumn = $defaultSortColumn;
//        $query = "select prev_id, next_id from (select {$idColumn} as id, lag({$idColumn},1) over(order by {$sortColumn}) as prev_id, lead({$idColumn},1) over(order by {$sortColumn}) as next_id
//            from {$tableName} where 1=1 {$where}) as t_temp where id = '".$form[$idColumn]."'";
//        $obj = $gen_db->queryOneRowObject($query);
//        if ($obj->prev_id) $form['gen_prevAction'] = $detailAction . "&{$idColumn}=".$obj->prev_id;
//        if ($obj->next_id) $form['gen_nextAction'] = $detailAction . "&{$idColumn}=".$obj->next_id;

        $form['gen_columnArray'] =
            array(
                array(
                    'label'=>_g("受注番号"),
                    'field'=>'received_number',
                ),
                array(
                    'label'=>_g("得意先コード"),
                    'field'=>'customer_no',
                ),
                array(
                    'label'=>_g("得意先名"),
                    'field'=>'customer_name',
                ),
                array(
                    'label'=>_g("部門名"),
                    'field'=>'section_name',
                ),
                array(
                    'label'=>_g("担当者名"),
                    'field'=>'worker_name',
                ),
                array(
                    'label'=>_g("受注日"),
                    'field'=>'received_date',
                ),
                array(
                    'label'=>_g("受注備考"),
                    'field'=>'remarks_header',
                ),
            );
        
        // カスタム項目
        if (isset($form['gen_customColumnArray'])) {
            foreach($form['gen_customColumnArray'] as $customCol => $customArr) {
                $form['gen_columnArray'][] =
                    array(
                        'label' => $customArr[1],
                        'field' => "gen_custom_{$customCol}",
                    );
            }
        }

        // 明細
        $query = "
            select
                seiban
                ,item_code
                ,item_name
                ,dead_line
                ,received_quantity
                ,product_price
                ,received_quantity * product_price as amount
                ,case when currency_name is null then '{$keyCurrency}' else currency_name end as currency_name
                ,received_detail.remarks
                ,case when delivery_completed then 'true' else '' end as completed
            from
                received_header
                inner join received_detail on received_header.received_header_id = received_detail.received_header_id
                inner join item_master on received_detail.item_id = item_master.item_id
                inner join customer_master on received_header.customer_id = customer_master.customer_id
                left join currency_master on customer_master.currency_id = currency_master.currency_id
            where
                received_header.received_header_id = '{$form['received_header_id']}'
            order by
                line_no
            ";
        $form['gen_detailData'] = $gen_db->getArray($query);

        $form['gen_detailColumnArray'] =
            array(
                array(
                    'label'=>_g("製番"),
                    'field'=>'seiban',
                ),
                array(
                    'label'=>_g("品目コード"),
                    'field'=>'item_code',
                ),
                array(
                    'label'=>_g("品目名"),
                    'field'=>'item_name',
                ),
                array(
                    'label'=>_g("数量"),
                    'field'=>'received_quantity',
                    'numberFormat'=>'true',
                ),
                array(
                    'label'=>_g("受注単価"),
                    'preField'=>'currency_name',
                    'field'=>'product_price',
                    'numberFormat'=>'true',
                ),
                array(
                    'label'=>_g("金額"),
                    'preField'=>'currency_name',
                    'field'=>'amount',
                    'numberFormat'=>'true',
                ),
                array(
                    'label'=>_g("受注納期"),
                    'field'=>'dead_line',
                ),
                array(
                    'label'=>_g("受注備考"),
                    'field'=>'remarks',
                ),
            );

        return 'mobile_detail.tpl';
    }
}