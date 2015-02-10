<?php

class Mobile_Received_List extends Base_MobileListBase
{

    function setSearchCondition(&$form)
    {
        global $gen_db;
        // 検索条件
        $query = "select item_group_id, item_group_name from item_group_master order by item_group_code";
        $option_item_group = $gen_db->getHtmlOptionArray($query, false, array(null => _g("(すべて)")));

        $form['gen_searchControlArray'] =
            array(
                array(
                    'label'=>_g('得意先'),
                    'type'=>'textbox',
                    'field'=>'customer_no',
                    'field2'=>'customer_name',
                ),
                array(
                    'label'=>_g('品目'),
                    'type'=>'textbox',
                    'field'=>'item_code',
                    'field2'=>'item_name',
                ),
                array(
                    'label'=>_g('品目グループ'),
                    'type'=>'select',
                    'field'=>'item_group_id',
                    'options'=>$option_item_group,
                ),
                array(
                    'label'=>_g('完了分の表示'),
                    'type'=>'select',
                    'field'=>'completed_status',
                    'options'=>array("false"=>_g("表示しない"), "true"=>_g("表示する")),
                    'nosql'=>'true',
                    'default'=>'true',  // モバイル版受注登録では納品同時登録されるので、デフォルトが「表示しない」だと不便
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

        $this->selectQuery = "
            select
                received_detail_id
                ,received_number
                ,customer_name
                ,item_name
                ,received_date
                ,dead_line
                ,received_quantity
                ,received_quantity * product_price as amount
            from
                received_header
                inner join received_detail on received_header.received_header_id = received_detail.received_header_id
                inner join customer_master on received_header.customer_id = customer_master.customer_id
                inner join item_master on received_detail.item_id = item_master.item_id
            [Where]
                " . (@$form['gen_search_completed_status'] == "false" ? " and (not(delivery_completed) or delivery_completed is null)" : "") . "
                -- 確定のみ
                --and coalesce(guarantee_grade,0)=0
            [Orderby]
            ";

        $this->orderbyDefault = 'received_number desc';
    }

    function setViewParam(&$form)
    {
        global $gen_db;

        $this->tpl = "mobile/list.tpl";

        $form['gen_pageTitle'] = _g("受注リスト");
        $form['gen_listAction'] = "Mobile_Received_List";
        $form['gen_linkAction'] = "Mobile_Received_Detail";
        $form['gen_idField'] = "received_detail_id";

        $form['gen_headerRightButtonURL'] = "index.php?action=Mobile_Received_Edit";
        $form['gen_headerRightButtonIcon'] = "add";
        $form['gen_headerRightButtonText'] = _g("新規登録");

        $form['gen_sumColumnArray'] = array("受注金額："=>"amount");

        $form['gen_headerRightButtonURL'] = "index.php?action=Mobile_Received_Edit";
        $form['gen_headerRightButtonIcon'] = "add";
        $form['gen_headerRightButtonText'] = _g("新規登録");

        $form['gen_sumColumnArray'] = array("受注金額：" => "amount");

        $keyCurrency = $gen_db->queryOneValue("select key_currency from company_master");
        $form['gen_columnArray'] =
            array(
                array(
                    'sortLabel'=>_g('受注番号'),
                    'label'=>"",
                    'field'=>'received_number',
                    'fontSize'=>12,
                    'after_noEscape'=>'<br>',
                ),
                array(
                    'sortLabel'=>_g('得意先名'),
                    'label'=>"",
                    'field'=>'customer_name',
                    'fontSize'=>14,
                    'after_noEscape'=>'<br>',
                ),
                array(
                    'sortLabel'=>_g('品目名'),
                    'field'=>'item_name',
                    'fontSize'=>15,
                    'after_noEscape'=>'<br>',
                ),
                array(
                    'sortLabel'=>_g('数量'),
                    'label'=>_g('数量'),
                    'field'=>'received_quantity',
                    'type'=>'numeric',  // aggregateのために必要
                    'labelFontSize'=>12,
                    'fontSize'=>12,
                    'labelStyle'=>'color:#999999;',
                    'numberFormat'=>true,
                    'after_noEscape'=>'&nbsp;&nbsp;',
                ),
                array(
                    'sortLabel'=>_g('受注額'),
                    'label'=>_g('受注額') . $keyCurrency,
                    'field'=>'amount',
                    'type'=>'numeric',  // aggregateのために必要
                    'labelFontSize'=>12,
                    'fontSize'=>12,
                    'labelStyle'=>'color:#999999;',
                    'numberFormat'=>true,
                    'after_noEscape'=>'<br>',
                ),
                array(
                    'sortLabel'=>_g('受注日'),
                    'label'=>_g('受注'),
                    'field'=>'received_date',
                    'labelFontSize'=>12,
                    'fontSize'=>12,
                    'labelStyle'=>'color:#999999;',
                    'after_noEscape'=>'&nbsp;&nbsp;',
                ),
                array(
                    'sortLabel'=>_g('受注納期'),
                    'label'=>_g('受注納期'),
                    'field'=>'dead_line',
                    'labelFontSize'=>12,
                    'fontSize'=>12,
                    'labelStyle'=>'color:#999999;',
                    'after_noEscape'=>'',
                ),
            );
    }

}
