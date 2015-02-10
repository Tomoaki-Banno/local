<?php

class Master_Waster_List extends Base_ListBase
{

    function setSearchCondition(&$form)
    {
        $form['gen_searchControlArray'] = array(
            array(
                'label' => _g('不適合理由コード'),
                'field' => 'waster_code',
            ),
            array(
                'label' => _g('不適合理由名'),
                'field' => 'waster_name',
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
                waster_id
                ,waster_code
                ,waster_name
                ,remarks

                ,waster_master.record_create_date as gen_record_create_date
                ,waster_master.record_creator as gen_record_creater
                ,coalesce(waster_master.record_update_date, waster_master.record_create_date) as gen_record_update_date
                ,coalesce(waster_master.record_updater, waster_master.record_creator) as gen_record_updater

            from
                waster_master
            [Where]
            [Orderby]
        ";

        $this->orderbyDefault = 'waster_code';
    }

    function setCsvParam(&$form)
    {
        $form['gen_importLabel'] = _g("不適合理由");
        $form['gen_importMsg_noEscape'] = "";
        $form['gen_allowUpdateCheck'] = true;
        $form['gen_allowUpdateLabel'] = _g("上書き許可　（不適合理由コードが既存の場合はレコードを上書きする）");

        $form['gen_csvArray'] = array(
            array(
                'label' => _g('不適合理由コード'),
                'field' => 'waster_code',
                'unique' => true, // これを指定すると、インポート時にCSVファイル内での重複がチェックされる
            ),
            array(
                'label' => _g('不適合理由名'),
                'field' => 'waster_name',
            ),
            array(
                'label' => _g('不適合備考'),
                'field' => 'remarks',
            ),
        );
    }

    function setViewParam(&$form)
    {
        $form['gen_pageTitle'] = _g("不適合理由マスタ");
        $form['gen_menuAction'] = "Menu_Master";
        $form['gen_listAction'] = "Master_Waster_List";
        $form['gen_editAction'] = "Master_Waster_Edit";
        $form['gen_deleteAction'] = "Master_Waster_Delete";
        $form['gen_idField'] = 'waster_id';
        $form['gen_idFieldForUpdateFile'] = "waster_master.waster_id";   // これをセットすると「ファイル」「トークボード」列が自動追加される
        $form['gen_excel'] = "true";
        $form['gen_pageHelp'] = _g("不適合");

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
                'deleteAction' => 'Master_Waster_BulkDelete',
                // readonlyであれば表示しない
                'showCondition' => ($form['gen_readonly'] != 'true' ? "true" : "false"),
            ),
            array(
                'label' => _g('不適合理由コード'),
                'field' => 'waster_code',
            ),
            array(
                'label' => _g('不適合理由名'),
                'field' => 'waster_name',
                'width' => '200',
            ),
            array(
                'label' => _g('不適合備考'),
                'field' => 'remarks',
                'width' => '200',
            ),
        );
    }

}
