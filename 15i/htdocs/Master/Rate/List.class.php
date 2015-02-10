<?php

class Master_Rate_List extends Base_ListBase
{

    function setSearchCondition(&$form)
    {
        global $gen_db;

        $query = "select currency_id, currency_name from currency_master order by currency_name";
        $option_section = $gen_db->getHtmlOptionArray($query, true);

        $form['gen_searchControlArray'] = array(
            array(
                'label' => _g('取引通貨'),
                'type' => 'select',
                'field' => 'rate_master___currency_id',
                'options' => $option_section,
            ),
            array(
                'label' => _g('適用開始日'),
                'type' => 'dateFromTo',
                'field' => 'rate_date',
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
                rate_id
                ,rate_date
                ,rate
                ,currency_name
                ,rate_master.remarks

                ,rate_master.record_create_date as gen_record_create_date
                ,rate_master.record_creator as gen_record_creater
                ,coalesce(rate_master.record_update_date, rate_master.record_create_date) as gen_record_update_date
                ,coalesce(rate_master.record_updater, rate_master.record_creator) as gen_record_updater

            from
                rate_master
                left join currency_master on rate_master.currency_id = currency_master.currency_id
            [Where]
            [Orderby]
        ";

        $this->orderbyDefault = 'rate_date desc, currency_name';
    }

    function setCsvParam(&$form)
    {
        $form['gen_importLabel'] = _g("為替レート");
        $form['gen_importMsg_noEscape'] = _g("※データは新規登録されます。（既存データの上書きはできません）");
        $form['gen_allowUpdateCheck'] = false;

        $form['gen_csvArray'] = array(
            array(
                'label' => _g('取引通貨'),
                'field' => 'currency_name',
            ),
            array(
                'label' => _g('適用開始日'),
                'field' => 'rate_date',
            ),
            array(
                'label' => _g('為替レート'),
                'field' => 'rate',
            ),
            array(
                'label' => _g('為替備考'),
                'field' => 'remarks',
            ),
        );
    }

    function setViewParam(&$form)
    {
        global $gen_db;
        
        $form['gen_pageTitle'] = _g("為替レートマスタ");
        $form['gen_menuAction'] = "Menu_Master";
        $form['gen_listAction'] = "Master_Rate_List";
        $form['gen_editAction'] = "Master_Rate_Edit";
        $form['gen_idField'] = 'rate_id';
        $form['gen_idFieldForUpdateFile'] = "rate_master.rate_id";   // これをセットすると「ファイル」「トークボード」列が自動追加される
        $form['gen_excel'] = "true";
        $form['gen_pageHelp'] = _g("為替");

        $form['gen_isClickableTable'] = "true";     // 行をクリックして明細を開く
        $form['gen_directEditEnable'] = "true";     // 直接編集

        $form['gen_columnArray'] = array(
            array(
                'label' => _g('明細'),
                'type' => 'edit',
            ),
            array(
                'label' => _g('コピー'),
                'type' => 'copy',
            ),
            array(
                'label' => _g('削除'),
                'type' => 'delete_check',
                'deleteAction' => 'Master_Rate_BulkDelete',
                // readonlyであれば表示しない
                'showCondition' => ($form['gen_readonly'] != 'true' ? "true" : "false"),
            ),
            array(
                'label' => _g('適用開始日'),
                'field' => 'rate_date',
                'width' => '90',
                'align' => 'center',
            ),
            array(
                'label' => _g('取引通貨'),
                'field' => 'currency_name',
                'width' => '70',
                'align' => 'center',
                'editType'=>'select',
                'editOptions'=>$gen_db->getHtmlOptionArray("select currency_id, currency_name from currency_master order by currency_name", false),
                'entryField'=>'currency_id',                    
            ),
            array(
                'label' => _g('為替レート'),
                'field' => 'rate',
                'width' => '70',
                'type' => 'numeric',
            ),
            array(
                'label' => _g('為替備考'),
                'field' => 'remarks',
                'width' => '300',
            ),
        );
    }

}