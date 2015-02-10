<?php

require_once(BASE_DIR . "EditBase.class.php");
require_once("Model.class.php");

class Master_CustomerGroup_Edit extends Base_EditBase
{

    function setQueryParam(&$form)
    {
        $this->keyColumn = 'customer_group_id';
        $this->selectQuery = "
            select
                *
                ,coalesce(record_update_date, record_create_date) as gen_last_update
                ,coalesce(record_updater, record_creator) as gen_last_updater
            from
                customer_group_master
            [Where]
        ";
    }

    function setViewParam(&$form)
    {
        $this->modelName = "Master_CustomerGroup_Model";

        $form['gen_pageTitle'] = _g('取引先グループマスタ');
        $form['gen_entryAction'] = "Master_CustomerGroup_Entry";
        $form['gen_listAction'] = "Master_CustomerGroup_List";
        $form['gen_pageHelp'] = _g("取引先グループ");

        $form['gen_editControlArray'] = array(
            array(
                'label' => _g('取引先グループコード'),
                'type' => 'textbox',
                'name' => 'customer_group_code',
                'value' => @$form['customer_group_code'],
                'size' => '15',
                'ime' => 'off',
                'require' => true
            ),
            array(
                'label' => _g('取引先グループ名'),
                'type' => 'textbox',
                'name' => 'customer_group_name',
                'value' => @$form['customer_group_name'],
                'size' => '20',
                'ime' => 'on',
                'require' => true
            ),
        );
    }

}