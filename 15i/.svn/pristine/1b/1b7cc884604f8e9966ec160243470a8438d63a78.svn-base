<?php

require_once("Model.class.php");

class Master_Currency_Edit extends Base_EditBase
{

    function setQueryParam(&$form)
    {
        $this->keyColumn = 'currency_id';
        $this->selectQuery = "
            select
                *
                ,coalesce(record_update_date, record_create_date) as gen_last_update
                ,coalesce(record_updater, record_creator) as gen_last_updater
            from
                currency_master
            [Where]
        ";
    }

    function setViewParam(&$form)
    {
        $this->modelName = "Master_Currency_Model";

        $form['gen_pageTitle'] = _g('通貨マスタ');
        $form['gen_entryAction'] = "Master_Currency_Entry";
        $form['gen_listAction'] = "Master_Currency_List";

        $form['gen_editControlArray'] = array(
            array(
                'label' => _g('取引通貨'),
                'type' => 'textbox',
                'name' => 'currency_name',
                'value' => @$form['currency_name'],
                'size' => '10',
                'ime' => 'on',
                'require' => true,
                'helpText_noEscape' => _g('取引通貨記号1文字か、半角3文字で入力してください。（例：「＄」「EUR」）。画面や帳票に表示されます。')
            ),
        );
    }

}
