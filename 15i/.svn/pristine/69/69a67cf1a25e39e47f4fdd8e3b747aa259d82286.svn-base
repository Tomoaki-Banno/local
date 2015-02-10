<?php

require_once("Model.class.php");

class Master_PricePercentGroup_Edit extends Base_EditBase
{

    function setQueryParam(&$form)
    {
        $this->keyColumn = 'price_percent_group_id';
        $this->selectQuery = "
            select
                *
                ,coalesce(record_update_date, record_create_date) as gen_last_update
                ,coalesce(record_updater, record_creator) as gen_last_updater
            from
                price_percent_group_master
            [Where]
        ";
    }

    function setViewParam(&$form)
    {
        $this->modelName = "Master_PricePercentGroup_Model";

        $form['gen_pageTitle'] = _g('掛率グループマスタ');
        $form['gen_entryAction'] = "Master_PricePercentGroup_Entry";
        $form['gen_listAction'] = "Master_PricePercentGroup_List";

        $form['gen_editControlArray'] = array(
            array(
                'label' => _g('掛率グループコード'),
                'type' => 'textbox',
                'name' => 'price_percent_group_code',
                'value' => @$form['price_percent_group_code'],
                'require' => true,
                'ime' => 'off',
                'size' => '15'
            ),
            array(
                'label' => _g('掛率グループ名'),
                'type' => 'textbox',
                'name' => 'price_percent_group_name',
                'value' => @$form['price_percent_group_name'],
                'require' => true,
                'ime' => 'on',
                'size' => '20'
            ),
            array(
                'label' => _g('掛率 (％)'),
                'type' => 'textbox',
                'name' => 'price_percent',
                'value' => @$form['price_percent'],
                'require' => true,
                'size' => '8',
                'ime' => 'off',
            ),
        );
    }

}
