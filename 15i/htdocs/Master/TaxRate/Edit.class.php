<?php

require_once("Model.class.php");

class Master_TaxRate_Edit extends Base_EditBase
{

    function setQueryParam(&$form)
    {
        $this->keyColumn = 'tax_rate_id';
        $this->selectQuery = "
            select
                *
                ,coalesce(record_update_date, record_create_date) as gen_last_update
                ,coalesce(record_updater, record_creator) as gen_last_updater
            from
                tax_rate_master
            [Where]
        ";
    }

    function setViewParam(&$form)
    {
        $this->modelName = "Master_TaxRate_Model";

        $form['gen_pageTitle'] = _g('消費税率マスタ');
        $form['gen_entryAction'] = "Master_TaxRate_Entry";
        $form['gen_listAction'] = "Master_TaxRate_List";

        $form['gen_editControlArray'] = array(
            array(
                'label' => _g('適用開始日'),
                'type' => 'calendar',
                'name' => 'apply_date',
                'value' => (isset($form['apply_date']) ? $form['apply_date'] : date('Y-m-01')),
                'size' => '8',
                'helpText_noEscape' => _g('この税率を適用開始する日付を入力します。'),
                'require' => true,
            ),
            array(
                'label' => _g('税率') . '(％)',
                'type' => 'textbox',
                'name' => 'tax_rate',
                'value' => @$form['tax_rate'],
                'size' => '8',
                'ime' => 'off',
                'require' => true,
            ),
        );
    }

}
