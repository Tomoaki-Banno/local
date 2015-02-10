<?php

class Master_Worker_List extends Base_ListBase
{

    function setSearchCondition(&$form)
    {
        global $gen_db;

        $query = "select section_id, section_name from section_master order by section_code";
        $option_section = $gen_db->getHtmlOptionArray($query, true);

        $form['gen_searchControlArray'] = array(
            array(
                'label' => _g('従業員コード'),
                'field' => 'worker_code',
            ),
            array(
                'label' => _g('従業員名'),
                'field' => 'worker_name',
                'ime' => 'on',
            ),
            array(
                'label' => _g('部門'),
                'type' => 'select',
                'field' => 'section_master___section_id',
                'options' => $option_section,
            ),
            array(
                'label' => _g('退職従業員の表示'),
                'type' => 'select',
                'field' => 'end_worker',
                'options' => array('' => "(" . _g('すべて') . ")", '0' => _g('通常のみ'), '1' => _g('退職のみ')),
                'nosql' => 'true',
                'default' => 'false',
                'hide' => true,
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
                worker_id
                ,worker_code
                ,worker_name
                ,worker_master.remarks
                ,section_code
                ,section_name
                ,case when end_worker then '" . _g("退職") . "' else '' end as show_end_worker
                ,case when end_worker then 1 else null end as end_worker_csv

                ,worker_master.record_create_date as gen_record_create_date
                ,worker_master.record_creator as gen_record_creater
                ,coalesce(worker_master.record_update_date, worker_master.record_create_date) as gen_record_update_date
                ,coalesce(worker_master.record_updater, worker_master.record_creator) as gen_record_updater

            from
                worker_master
                left join section_master on worker_master.section_id = section_master.section_id
            [Where]
                and worker_id <> 0
                " . (@$form['gen_search_end_worker'] == "0" ? " and (end_worker is null or end_worker = false)" : "") . "
                " . (@$form['gen_search_end_worker'] == "1" ? " and end_worker = true" : "") . "
            [Orderby]
        "; // 0 は従業員無し

        $this->orderbyDefault = 'worker_code';
    }

    function setCsvParam(&$form)
    {
        $form['gen_importLabel'] = _g("従業員");
        $form['gen_importMsg_noEscape'] = "";
        $form['gen_allowUpdateCheck'] = true;
        $form['gen_allowUpdateLabel'] = _g("上書き許可　（従業員コードが既存の場合はレコードを上書きする）");

        $form['gen_csvArray'] = array(
            array(
                'label' => _g('従業員コード'),
                'field' => 'worker_code',
                'unique' => true, // これを指定すると、インポート時にCSVファイル内での重複がチェックされる
            ),
            array(
                'label' => _g('従業員名'),
                'field' => 'worker_name',
            ),
            array(
                'label' => _g('所属部門コード'),
                'field' => 'section_code',
            ),
            array(
                'label' => _g('退職'),
                'addLabel' => _g('(1なら退職)'),
                'field' => 'end_worker',
                'exportField' => 'end_worker_csv',
            ),
            array(
                'label' => _g('従業員備考'),
                'field' => 'remarks',
            ),
        );
    }

    function setViewParam(&$form)
    {
        global $gen_db;
        
        $form['gen_pageTitle'] = _g("従業員マスタ");
        $form['gen_menuAction'] = "Menu_Master";
        $form['gen_listAction'] = "Master_Worker_List";
        $form['gen_editAction'] = "Master_Worker_Edit";
        $form['gen_idField'] = 'worker_id';
        $form['gen_idFieldForUpdateFile'] = "worker_master.worker_id";   // これをセットすると「ファイル」「トークボード」列が自動追加される
        $form['gen_excel'] = "true";

        $form['gen_isClickableTable'] = "true";     // 行をクリックして明細を開く
        $form['gen_directEditEnable'] = "true";     // 直接編集
        
        $form['gen_rowColorCondition'] = array(
            "#d7d7d7" => "'[show_end_worker]'!=''"     // 非表示
        );
        $form['gen_colorSample'] = array(
            "d7d7d7" => array(_g("シルバー"), _g("非表示")),
        );

        $form['gen_columnArray'] =
                array(
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
                        'deleteAction' => 'Master_Worker_BulkDelete',
                        // readonlyであれば表示しない
                        'showCondition' => ($form['gen_readonly'] != 'true' ? "true" : "false"),
                    ),
                    array(
                        'label' => _g('従業員コード'),
                        'field' => 'worker_code',
                    ),
                    array(
                        'label' => _g('従業員名'),
                        'field' => 'worker_name',
                    ),
                    array(
                        'label' => _g('所属部門'),
                        'field' => 'section_name',
                        'editType'=>'select',
                        'editOptions'=>$gen_db->getHtmlOptionArray("select section_id, section_name from section_master order by section_code", true),
                        'entryField'=>'section_id',                    
                    ),
                    array(
                        'label' => _g('退職'),
                        'field' => 'show_end_worker',
                        'width' => '40',
                        'align' => 'center',
                        'editType'=>'select',
                        'editOptions'=> array('false' => "", 'true' => _g('退職')),
                        'entryField'=>'end_worker',                    
                        'hide' => true,
                    ),
                    array(
                        'label' => _g('従業員備考'),
                        'field' => 'remarks',
                        'width' => '200',
                    ),
        );
    }

}