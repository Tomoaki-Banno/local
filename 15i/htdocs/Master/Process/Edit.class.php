<?php

require_once("Model.class.php");

class Master_Process_Edit extends Base_EditBase
{

    function setQueryParam(&$form)
    {
        $this->keyColumn = 'process_id';
        $this->selectQuery = "
            select
                *
                ,coalesce(record_update_date, record_create_date) as gen_last_update
                ,coalesce(record_updater, record_creator) as gen_last_updater
            from
                process_master
            [Where]
        ";
    }

    function setViewParam(&$form)
    {
        $this->modelName = "Master_Process_Model";

        $form['gen_pageTitle'] = _g('工程マスタ');
        $form['gen_entryAction'] = "Master_Process_Entry";
        $form['gen_listAction'] = "Master_Process_List";

        $form['gen_editControlArray'] = array(
            array(
                'label' => _g('工程コード'),
                'type' => 'textbox',
                'name' => 'process_code',
                'value' => @$form['process_code'],
                'require' => true,
                'ime' => 'off',
                'size' => '15'
            ),
            array(
                'label' => _g('工程名'),
                'type' => 'textbox',
                'name' => 'process_name',
                'value' => @$form['process_name'],
                'require' => true,
                'ime' => 'on',
                'size' => '20'
            ),
            array(
                'label' => _g('設備名'),
                'type' => 'textbox',
                'name' => 'equipment_name',
                'value' => @$form['equipment_name'],
                'size' => '20',
                'ime' => 'on',
                'helpText_noEscape' => _g("現在のところ、この項目を使用する箇所はありません。設定しなくてもかまいません。"),
            ),
            array(
                'label' => _g('標準リードタイム'),
                'type' => 'textbox',
                'name' => 'default_lead_time',
                'value' => @$form['default_lead_time'],
                'size' => '8',
                'ime' => 'off',
                'helpText_noEscape' => _g("品目マスタの登録時に、工程リードタイムのデフォルト値として使用されます。"),
            ),
        );
    }

}
