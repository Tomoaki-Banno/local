<?php

require_once("Model.class.php");

class Master_Waster_Edit extends Base_EditBase
{

    function setQueryParam(&$form)
    {
        $this->keyColumn = 'waster_id';
        $this->selectQuery = "
            select
                *
                ,coalesce(record_update_date, record_create_date) as gen_last_update
                ,coalesce(record_updater, record_creator) as gen_last_updater
            from
                waster_master
            [Where]
        ";
    }

    function setViewParam(&$form)
    {
        $this->modelName = "Master_Waster_Model";

        $form['gen_pageTitle'] = _g('不適合理由マスタ');
        $form['gen_entryAction'] = "Master_Waster_Entry";
        $form['gen_listAction'] = "Master_Waster_List";

        $form['gen_editControlArray'] = array(
            array(
                'label' => _g('不適合理由コード'),
                'type' => 'textbox',
                'name' => 'waster_code',
                'value' => @$form['waster_code'],
                'size' => '12',
                'ime' => 'off',
                'require' => true,
            ),
            array(
                'label' => _g('不適合理由名'),
                'type' => 'textbox',
                'name' => 'waster_name',
                'value' => @$form['waster_name'],
                'size' => '20',
                'ime' => 'on',
                'require' => true,
            ),
            array(
                'label' => _g('不適合備考'),
                'type' => 'textbox',
                'name' => 'remarks',
                'value' => @$form['remarks'],
                'ime' => 'on',
                'size' => '20',
            ),
        );
    }

}
