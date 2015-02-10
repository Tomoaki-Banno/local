<?php

require_once("Model.class.php");

class Master_Rate_Edit extends Base_EditBase
{

    function setQueryParam(&$form)
    {
        $this->keyColumn = 'rate_id';
        $this->selectQuery = "
            select
                *
                ,coalesce(record_update_date, record_create_date) as gen_last_update
                ,coalesce(record_updater, record_creator) as gen_last_updater
            from
                rate_master
            [Where]
        ";
    }

    function setViewParam(&$form)
    {
        global $gen_db;

        $this->modelName = "Master_Rate_Model";

        $form['gen_pageTitle'] = _g('為替レートマスタ');
        $form['gen_entryAction'] = "Master_Rate_Entry";
        $form['gen_listAction'] = "Master_Rate_List";

        $query = "select currency_id from currency_master";
        if (!$gen_db->existRecord($query)) {
            $form['gen_message_noEscape'] = "<font color='red'>" . _g('通貨マスタに取引通貨が登録されていません。先に通貨マスタの登録を行ってください。') . "</font>";
        }

        $form['gen_editControlArray'] = array(
            array(
                'label' => _g('取引通貨'),
                'type' => 'select',
                'name' => 'currency_id',
                'options' => $gen_db->getHtmlOptionArray("select currency_id, currency_name from currency_master order by currency_name", false),
                'selected' => @$form['currency_id'],
                'helpText_noEscape' => _g('通貨マスタで登録された取引通貨が選択肢に表示されます。')
            ),
            array(
                'label' => _g('適用開始日'),
                'type' => 'calendar',
                'name' => 'rate_date',
                'value' => (isset($form['rate_date']) ? $form['rate_date'] : date('Y-m-01')),
                'size' => '8',
                'helpText_noEscape' => _g('このレートを適用開始する日付を入力します。'),
                'require' => true,
            ),
            array(
                'label' => _g('為替レート'),
                'type' => 'textbox',
                'name' => 'rate',
                'value' => @$form['rate'],
                'size' => '8',
                'ime' => 'off',
                'require' => true,
            ),
            array(
                'label' => _g('為替備考'),
                'type' => 'textbox',
                'name' => 'remarks',
                'value' => @$form['remarks'],
                'ime' => 'on',
                'size' => '20',
            ),
        );
    }

}
