<?php

require_once("Model.class.php");

class Master_Section_Edit extends Base_EditBase
{

    function setQueryParam(&$form)
    {
        $this->keyColumn = 'section_id';
        $this->selectQuery = "
            select
                *
                ,coalesce(record_update_date, record_create_date) as gen_last_update
                ,coalesce(record_updater, record_creator) as gen_last_updater
            from
                section_master
            [Where]
        ";
    }

    function setViewParam(&$form)
    {
        $this->modelName = "Master_Section_Model";

        $form['gen_pageTitle'] = _g('部門マスタ');
        $form['gen_entryAction'] = "Master_Section_Entry";
        $form['gen_listAction'] = "Master_Section_List";

        $form['gen_editControlArray'] = array(
            array(
                'label' => _g('部門コード'),
                'type' => 'textbox',
                'name' => 'section_code',
                'value' => @$form['section_code'],
                'size' => '12',
                'ime' => 'off',
                'require' => true
            ),
            array(
                'label' => _g('部門名'),
                'type' => 'textbox',
                'name' => 'section_name',
                'value' => @$form['section_name'],
                'size' => '20',
                'ime' => 'on',
                'require' => true
            ),
            array(
                'label' => _g('部門備考'),
                'type' => 'textbox',
                'name' => 'remarks',
                'value' => @$form['remarks'],
                'ime' => 'on',
                'size' => '20',
            ),
         );
    }

}