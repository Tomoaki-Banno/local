<?php

require_once("Model.class.php");

class Master_Equip_Edit extends Base_EditBase
{

    function setQueryParam(&$form)
    {
        $this->keyColumn = 'equip_id';
        $this->selectQuery = "
            select
                *
                ,coalesce(record_update_date, record_create_date) as gen_last_update
                ,coalesce(record_updater, record_creator) as gen_last_updater
            from
                equip_master
            [Where]
        ";
    }

    function setViewParam(&$form)
    {
        $this->modelName = "Master_Equip_Model";

        $form['gen_pageTitle'] = _g('設備マスタ');
        $form['gen_entryAction'] = "Master_Equip_Entry";
        $form['gen_listAction'] = "Master_Equip_List";

        $form['gen_editControlArray'] = array(
            array(
                'label' => _g('設備コード'),
                'type' => 'textbox',
                'name' => 'equip_code',
                'value' => @$form['equip_code'],
                'size' => '12',
                'ime' => 'off',
                'require' => true
            ),
            array(
                'label' => _g('設備名'),
                'type' => 'textbox',
                'name' => 'equip_name',
                'value' => @$form['equip_name'],
                'size' => '20',
                'ime' => 'on',
                'require' => true
            ),
            array(
                'label' => _g('設備備考'),
                'type' => 'textbox',
                'name' => 'remarks',
                'value' => @$form['remarks'],
                'ime' => 'on',
                'size' => '20',
            ),
        );
    }

}
