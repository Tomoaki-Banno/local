<?php

require_once("Model.class.php");

class Master_Worker_Edit extends Base_EditBase
{

    function setQueryParam(&$form)
    {
        $this->keyColumn = 'worker_id';
        $this->selectQuery = "
            select
                -- end_workerの置き換えを行っているので * は使えない
                worker_id
                ,worker_code
                ,worker_name
                ,section_id
                ,case when end_worker then 'true' else '' end as end_worker
                ,remarks
                
                ,coalesce(record_update_date, record_create_date) as gen_last_update
                ,coalesce(record_updater, record_creator) as gen_last_updater
            from
                worker_master
            [Where]
        ";
    }

    function setViewParam(&$form)
    {
        global $gen_db;

        $this->modelName = "Master_Worker_Model";

        $form['gen_pageTitle'] = _g('従業員マスタ');
        $form['gen_entryAction'] = "Master_Worker_Entry";
        $form['gen_listAction'] = "Master_Worker_List";

        // 退職メッセージ
        if (isset($form['end_worker']) && $form['end_worker'] == 'true') {
            $form['gen_message_noEscape'] = "<font color=\"red\"><b>" . _g("この従業員は退職しています。") . "</b></font>";
        }
        
        $form['gen_editControlArray'] = array(
            array(
                'label' => _g('従業員コード'),
                'type' => 'textbox',
                'name' => 'worker_code',
                'value' => @$form['worker_code'],
                'size' => '12',
                'ime' => 'off',
                'readonly' => (@$form['gen_overlapFrame'] == "true" && !isset($form['gen_dropdownNewRecordButton'])), // 拡張DDからのジャンプ登録の場合、コード変更されると動作不具合。ただし拡張DD内新規ボタンを除く
                'require' => true,
            ),
            array(
                'label' => _g('従業員名'),
                'type' => 'textbox',
                'name' => 'worker_name',
                'value' => @$form['worker_name'],
                'size' => '20',
                'ime' => 'on',
                'require' => true,
            ),
            array(
                'label' => _g('所属部門'),
                'type' => 'select',
                'name' => 'section_id',
                'options' => $gen_db->getHtmlOptionArray("select section_id, section_name from section_master order by section_code", true),
                'selected' => @$form['section_id'],
            ),
            array(
                'label' => _g('退職'),
                'type' => 'checkbox',
                'name' => 'end_worker',
                'onvalue' => 'true', // trueのときの値。デフォルト値ではない
                'value' => @$form['end_worker'],
                'helpText_noEscape' => _g('このチェックをオンにすると退職した従業員とみなされ、各画面の従業員選択ドロップダウンに表示されなくなります（コードを手入力することはできます）。'),
            ),
            array(
                'label' => _g('従業員備考'),
                'type' => 'textbox',
                'name' => 'remarks',
                'value' => @$form['remarks'],
                'ime' => 'on',
                'size' => '20',
            ),
        );
    }

}
