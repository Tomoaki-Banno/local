<?php

class Master_Location_List extends Base_ListBase
{

    function setSearchCondition(&$form)
    {
        $form['gen_searchControlArray'] = array(
            array(
                'label' => _g('ロケーションコード'),
                'field' => 'location_code',
            ),
            array(
                'label' => _g('ロケーション名'),
                'field' => 'location_name',
                'ime' => 'on',
            ),
            array(
                'label' => _g('サプライヤー名'),
                'field' => 'customer_name',
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
                location_id
                ,location_code
                ,location_name
                ,customer_no
                ,customer_name

                ,location_master.record_create_date as gen_record_create_date
                ,location_master.record_creator as gen_record_creater
                ,coalesce(location_master.record_update_date, location_master.record_create_date) as gen_record_update_date
                ,coalesce(location_master.record_updater, location_master.record_creator) as gen_record_updater

            from
                location_master
                left join customer_master on location_master.customer_id = customer_master.customer_id
            [Where]
            [Orderby]
        ";

        $this->orderbyDefault = 'location_code';
    }

    function setCsvParam(&$form)
    {
        $form['gen_importLabel'] = _g("ロケーション");
        $form['gen_importMsg_noEscape'] = "";
        $form['gen_allowUpdateCheck'] = true;
        $form['gen_allowUpdateLabel'] = _g("上書き許可　（ロケーションコードが既存の場合はレコードを上書きする）");

        $form['gen_csvArray'] = array(
            array(
                'label' => _g('ロケーションコード'),
                'field' => 'location_code',
                'unique' => true, // これを指定すると、インポート時にCSVファイル内での重複がチェックされる
            ),
            array(
                'label' => _g('ロケーション名'),
                'field' => 'location_name',
            ),
            array(
                'label' => _g('サプライヤーコード'),
                'field' => 'customer_no',
            ),
        );
    }

    function setViewParam(&$form)
    {
        $form['gen_pageTitle'] = _g("ロケーションマスタ");
        $form['gen_menuAction'] = "Menu_Master";
        $form['gen_listAction'] = "Master_Location_List";
        $form['gen_editAction'] = "Master_Location_Edit";
        $form['gen_idField'] = 'location_id';
        $form['gen_idFieldForUpdateFile'] = "location_master.location_id";   // これをセットすると「ファイル」「トークボード」列が自動追加される
        $form['gen_excel'] = "true";
        $form['gen_pageHelp'] = _g("ロケーション");

        $form['gen_isClickableTable'] = "true";     // 行をクリックして明細を開く
        $form['gen_directEditEnable'] = "true";     // 直接編集

        // 編集用エクセルファイル
        $form['gen_editExcel'] = "true";

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
                'deleteAction' => 'Master_Location_BulkDelete',
                // readonlyであれば表示しない
                'showCondition' => ($form['gen_readonly'] != 'true' ? "true" : "false"),
            ),
            array(
                'label' => _g('ロケーションコード'),
                'field' => 'location_code',
            ),
            array(
                'label' => _g('ロケーション名'),
                'field' => 'location_name',
                'width' => '250',
            ),
            array(
                'label' => _g('サプライヤー名'),
                'field' => 'customer_name',
                'width' => '250',
                'editType'=>'dropdown',
                'dropdownCategory'=>'partner_for_location',
                'entryField'=>'customer_id', 
            ),
        );
    }

}
