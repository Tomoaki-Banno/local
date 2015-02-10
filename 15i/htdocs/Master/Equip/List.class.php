<?php

class Master_Equip_List extends Base_ListBase
{

    function setSearchCondition(&$form)
    {
        $form['gen_searchControlArray'] = array(
            array(
                'label' => _g('設備コード'),
                'field' => 'equip_code',
            ),
            array(
                'label' => _g('設備名'),
                'field' => 'equip_name',
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
                equip_id
                ,equip_code
                ,equip_name
                ,remarks

                ,equip_master.record_create_date as gen_record_create_date
                ,equip_master.record_creator as gen_record_creater
                ,coalesce(equip_master.record_update_date, equip_master.record_create_date) as gen_record_update_date
                ,coalesce(equip_master.record_updater, equip_master.record_creator) as gen_record_updater

            from
                equip_master
            [Where]
            [Orderby]
        ";

        $this->orderbyDefault = 'equip_code';
    }

    function setCsvParam(&$form)
    {
        $form['gen_importLabel'] = _g("設備");
        $form['gen_importMsg_noEscape'] = "";
        $form['gen_allowUpdateCheck'] = true;

        $form['gen_csvArray'] = array(
            array(
                'label' => _g('設備コード'),
                'field' => 'equip_code',
                'unique' => true, // これを指定すると、インポート時にCSVファイル内での重複がチェックされる
            ),
            array(
                'label' => _g('設備名'),
                'field' => 'equip_name',
            ),
            array(
                'label' => _g('設備備考'),
                'field' => 'remarks',
            ),
        );
    }

    function setViewParam(&$form)
    {
        $form['gen_pageTitle'] = _g("設備マスタ");
        $form['gen_menuAction'] = "Menu_Master";
        $form['gen_listAction'] = "Master_Equip_List";
        $form['gen_editAction'] = "Master_Equip_Edit";
        $form['gen_idField'] = 'equip_id';
        $form['gen_idFieldForUpdateFile'] = "equip_master.equip_id";   // これをセットすると「ファイル」「トークボード」列が自動追加される
        $form['gen_excel'] = "true";
        $form['gen_allowUpdateLabel'] = _g("上書き許可　（設備コードが既存の場合はレコードを上書きする）");

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
                'deleteAction' => 'Master_Equip_BulkDelete',
                // readonlyであれば表示しない
                'showCondition' => ($form['gen_readonly'] != 'true' ? "true" : "false"),
            ),
            array(
                'label' => _g('設備コード'),
                'field' => 'equip_code',
            ),
            array(
                'label' => _g('設備名'),
                'field' => 'equip_name',
                'width' => '250',
            ),
            array(
                'label' => _g('設備備考'),
                'field' => 'remarks',
                'width' => '250',
            ),
        );
    }

}
