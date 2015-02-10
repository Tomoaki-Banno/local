<?php

class Mobile_CustomerMaster_List extends Base_MobileListBase
{
    function setSearchCondition(&$form)
    {
        global $gen_db;

        $form['gen_searchControlArray'] =
            array(
                array(
                    'label'=>_g('コード'),
                    'type'=>'textbox',
                    'field'=>'customer_no',
                ),
                array(
                    'label'=>_g('取引先名'),
                    'type'=>'textbox',
                    'field'=>'customer_name',
                ),
                array(
                    'label'=>_g('区分'),
                    'type'=>'select',
                    'field'=>'classification',
                    'options'=>array('null'=>'(' . _g("すべて") . ')', 0=>_g('得意先'), 1=>_g('サプライヤー'), 2=>_g('発送先')),
                ),
                array(
                    'label'=>_g('非表示取引先の表示'),
                    'type'=>'select',
                    'field'=>'end_customer',
                    'options'=>array("false"=>_g("しない"), "true"=>_g("する")),   // 「しない」時は end_customer = false のレコードに限定
                    'default'=>'false',
                    'nosql'=>'true',
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
                customer_no
                ,customer_name
            from 
                customer_master
            [Where]
                " . (@$form['gen_search_end_customer'] == "false" ? " and (end_customer is null or end_customer = false)" : "") . "
            [Orderby]
            ";

        $this->orderbyDefault = 'customer_no';
    }

    function setViewParam(&$form)
    {
        global $gen_db;
        
        $this->tpl = "mobile/list.tpl";
        
        $form['gen_pageTitle'] = _g("取引先マスタ");
        $form['gen_listAction'] = "Mobile_CustomerMaster_List";
        $form['gen_linkAction'] = "Mobile_CustomerMaster_Detail";
        $form['gen_idField'] = "customer_no";
        
        $form['gen_columnArray'] =
            array(
                array(
                    'sortLabel'=>_g('取引先コード'),
                    'label'=>"",
                    'field'=>'customer_no',
                    'fontSize'=>13,
                    'after_noEscape'=>'<br>',
                ),
                array(
                    'sortLabel'=>_g('取引先名'),
                    'label'=>"",
                    'field'=>'customer_name',
                    'fontSize'=>15,
                ),
            );
    }
}