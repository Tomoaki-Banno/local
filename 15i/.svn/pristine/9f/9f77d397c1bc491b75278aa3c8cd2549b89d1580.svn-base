<?php

require_once("Model.class.php");

class Master_ItemGroup_Edit extends Base_EditBase
{

    function setQueryParam(&$form)
    {
        $this->keyColumn = 'item_group_id';
        $this->selectQuery = "
            select
                *
                ,coalesce(record_update_date, record_create_date) as gen_last_update
                ,coalesce(record_updater, record_creator) as gen_last_updater
            from
                item_group_master
            [Where]
        ";
    }

    function setViewParam(&$form)
    {
        $this->modelName = "Master_ItemGroup_Model";

        $form['gen_pageTitle'] = _g('品目グループマスタ');
        $form['gen_entryAction'] = "Master_ItemGroup_Entry";
        $form['gen_listAction'] = "Master_ItemGroup_List";
        $form['gen_pageHelp'] = _g("品目グループ");

        $form['gen_editControlArray'] = array(
            array(
                'label' => _g('品目グループコード'),
                'type' => 'textbox',
                'name' => 'item_group_code',
                'value' => @$form['item_group_code'],
                'size' => '15',
                'ime' => 'off',
                'require' => true
            ),
            array(
                'label' => _g('品目グループ名'),
                'type' => 'textbox',
                'name' => 'item_group_name',
                'value' => @$form['item_group_name'],
                'size' => '20',
                'ime' => 'on',
                'require' => true
            ),
        );
    }

}
