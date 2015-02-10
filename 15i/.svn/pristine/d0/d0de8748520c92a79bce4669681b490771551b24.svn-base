<?php

class Master_Process_List extends Base_ListBase
{

    function setSearchCondition(&$form)
    {
        $form['gen_searchControlArray'] = array(
            array(
                'label' => _g('工程コード'),
                'field' => 'process_code',
            ),
            array(
                'label' => _g('工程名'),
                'field' => 'process_name',
                'ime' => 'on',
            ),
            array(
                'label' => _g('設備名'),
                'field' => 'equipment_name',
                'ime' => 'on',
            ),
        );
    }

    function convertSearchCondition($converter, &$form)
    {
    }

    function beforeLogic(&$form)
    {
    }

    function setQueryParam(&$form)
    {
        $this->selectQuery = "
            select
                process_id
                ,process_code
                ,process_name
                ,equipment_name
                ,default_lead_time

                ,process_master.record_create_date as gen_record_create_date
                ,process_master.record_creator as gen_record_creater
                ,coalesce(process_master.record_update_date, process_master.record_create_date) as gen_record_update_date
                ,coalesce(process_master.record_updater, process_master.record_creator) as gen_record_updater

            from
                process_master
                /* 標準工程（id=0）は非表示とする */
            [Where]
                and process_id <> 0
            [Orderby]
        ";

        $this->orderbyDefault = 'process_code';
    }

    function setCsvParam(&$form)
    {
        $form['gen_importLabel'] = _g("工程");
        $form['gen_importMsg_noEscape'] = "";
        $form['gen_allowUpdateCheck'] = true;
        $form['gen_allowUpdateLabel'] = _g("上書き許可　（工程コードが既存の場合はレコードを上書きする）");

        $form['gen_csvArray'] = array(
            array(
                'label' => _g('工程コード'),
                'field' => 'process_code',
                'unique' => true, // これを指定すると、インポート時にCSVファイル内での重複がチェックされる
            ),
            array(
                'label' => _g('工程名'),
                'field' => 'process_name',
            ),
            array(
                'label' => _g('設備名'),
                'field' => 'equipment_name',
            ),
            array(
                'label' => _g('標準リードタイム'),
                'field' => 'default_lead_time',
            ),
        );
    }

    function setViewParam(&$form)
    {
        $form['gen_pageTitle'] = _g("工程マスタ");
        $form['gen_menuAction'] = "Menu_Master";
        $form['gen_listAction'] = "Master_Process_List";
        $form['gen_editAction'] = "Master_Process_Edit";
        $form['gen_deleteAction'] = "Master_Process_Delete";
        $form['gen_idField'] = 'process_id';
        $form['gen_idFieldForUpdateFile'] = "process_master.process_id";   // これをセットすると「ファイル」「トークボード」列が自動追加される
        $form['gen_excel'] = "true";

        $form['gen_isClickableTable'] = "true";     // 行をクリックして明細を開く
        $form['gen_directEditEnable'] = "true";     // 直接編集

        $form['gen_columnArray'] = array(
            array(
                'label' => _g('明細'),
                'type' => 'edit',
            ),
            array(
                'label' => _g('コピー'),
                'type' => 'copy',
            ),
            array(
                'label' => _g('削除'),
                'type' => 'delete_check',
                'deleteAction' => 'Master_Process_BulkDelete',
                // readonlyであれば表示しない
                'showCondition' => ($form['gen_readonly'] != 'true' ? "true" : "false"),
            ),
            array(
                'label' => _g('工程コード'),
                'field' => 'process_code',
            ),
            array(
                'label' => _g('工程名'),
                'field' => 'process_name',
                'width' => '200',
            ),
            array(
                'label' => _g('設備名'),
                'field' => 'equipment_name',
            ),
            array(
                'label' => _g('標準リードタイム'),
                'field' => 'default_lead_time',
                'width' => '100',
                'type' => 'numeric',
            ),
        );
    }

}