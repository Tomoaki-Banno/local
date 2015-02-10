<?php

class Master_TaxRate_List extends Base_ListBase
{

    function setSearchCondition(&$form)
    {
        $form['gen_searchControlArray'] = array(
            array(
                'label' => _g('適用開始日'),
                'type' => 'dateFromTo',
                'field' => 'apply_date',
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
                tax_rate_id
                ,tax_rate
                ,apply_date

                ,tax_rate_master.record_create_date as gen_record_create_date
                ,tax_rate_master.record_creator as gen_record_creater
                ,coalesce(tax_rate_master.record_update_date, tax_rate_master.record_create_date) as gen_record_update_date
                ,coalesce(tax_rate_master.record_updater, tax_rate_master.record_creator) as gen_record_updater

            from
                tax_rate_master
            [Where] [Orderby]
        ";

        $this->orderbyDefault = 'apply_date desc';
    }

    function setCsvParam(&$form)
    {
        $form['gen_importLabel'] = _g("消費税率マスタ");
        $form['gen_importMsg_noEscape'] = "";
        $form['gen_allowUpdateCheck'] = true;

        $form['gen_csvArray'] = array(
            array(
                'label' => _g('税率') . '(％)',
                'field' => 'tax_rate',
            ),
            array(
                'label' => _g('適用開始日'),
                'field' => 'apply_date',
            ),
        );
    }

    function setViewParam(&$form)
    {
        $form['gen_pageTitle'] = _g("消費税率マスタ");
        $form['gen_menuAction'] = "Menu_Master";
        $form['gen_listAction'] = "Master_TaxRate_List";
        $form['gen_editAction'] = "Master_TaxRate_Edit";
        $form['gen_idField'] = 'tax_rate_id';
        $form['gen_idFieldForUpdateFile'] = "tax_rate_master.tax_rate_id";   // これをセットすると「ファイル」「トークボード」列が自動追加される
        $form['gen_excel'] = "true";
        $form['gen_pageHelp'] = _g("税率");

        $form['gen_isClickableTable'] = "true";     // 行をクリックして明細を開く
        $form['gen_directEditEnable'] = "true";     // 直接編集

        $defaultTaxRate = Logic_Tax::getDefaultTaxRate();
        $form['gen_message_noEscape'] = sprintf(_g("マスタ未設定の期間は税率%s％が適用されます。"), $defaultTaxRate);

        $form['gen_columnArray'] = array(
            array(
                'label' => _g('明細'),
                'type' => 'edit',
            ),
            array(
                'label' => _g('削除'),
                'type' => 'delete_check',
                'deleteAction' => 'Master_TaxRate_BulkDelete',
                // readonlyであれば表示しない
                'showCondition' => ($form['gen_readonly'] != 'true' ? "true" : "false"),
            ),
            array(
                'label' => _g('適用開始日'),
                'field' => 'apply_date',
                'width' => '120',
                'align' => 'center',
            ),
            array(
                'label' => _g('税率') . '(％)',
                'field' => 'tax_rate',
                'width' => '120',
                'type' => 'numeric',
            ),
        );
    }

}