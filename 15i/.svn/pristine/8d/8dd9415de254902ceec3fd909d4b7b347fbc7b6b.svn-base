<?php

class Master_Section_List extends Base_ListBase
{

    function setSearchCondition(&$form)
    {
        $form['gen_searchControlArray'] = array(
            array(
                'label' => _g('部門コード'),
                'field' => 'section_code',
            ),
            array(
                'label' => _g('部門名'),
                'field' => 'section_name',
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
                section_id
                ,section_code
                ,section_name
                ,remarks

                ,section_master.record_create_date as gen_record_create_date
                ,section_master.record_creator as gen_record_creater
                ,coalesce(section_master.record_update_date, section_master.record_create_date) as gen_record_update_date
                ,coalesce(section_master.record_updater, section_master.record_creator) as gen_record_updater

            from
                section_master
            [Where]
            [Orderby]
        ";

        $this->orderbyDefault = 'section_code';
    }

    function setCsvParam(&$form)
    {
        $form['gen_importLabel'] = _g("部門");
        $form['gen_importMsg_noEscape'] = "";
        $form['gen_allowUpdateCheck'] = true;
        $form['gen_allowUpdateLabel'] = _g("上書き許可　（部門コードが既存の場合はレコードを上書きする）");

        $form['gen_csvArray'] = array(
            array(
                'label' => _g('部門コード'),
                'field' => 'section_code',
                'unique' => true, // これを指定すると、インポート時にCSVファイル内での重複がチェックされる
            ),
            array(
                'label' => _g('部門名'),
                'field' => 'section_name',
            ),
            array(
                'label' => _g('部門備考'),
                'field' => 'remarks',
            ),
        );
    }

    function setViewParam(&$form)
    {
        $form['gen_pageTitle'] = _g("部門マスタ");
        $form['gen_menuAction'] = "Menu_Master";
        $form['gen_listAction'] = "Master_Section_List";
        $form['gen_editAction'] = "Master_Section_Edit";
        $form['gen_deleteAction'] = "Master_Section_Delete";
        $form['gen_idField'] = 'section_id';
        $form['gen_idFieldForUpdateFile'] = "section_master.section_id";   // これをセットすると「ファイル」「トークボード」列が自動追加される
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
                'deleteAction' => 'Master_Section_BulkDelete',
                // readonlyであれば表示しない
                'showCondition' => ($form['gen_readonly'] != 'true' ? "true" : "false"),
            ),
            array(
                'label' => _g('部門コード'),
                'field' => 'section_code',
            ),
            array(
                'label' => _g('部門名'),
                'field' => 'section_name',
                'width' => '200',
            ),
            array(
                'label' => _g('部門備考'),
                'field' => 'remarks',
                'width' => '200',
            ),
        );
    }

}