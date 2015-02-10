<?php

class Master_PricePercentGroup_List extends Base_ListBase
{

    function setSearchCondition(&$form)
    {
        $form['gen_searchControlArray'] = array(
            array(
                'label' => _g('掛率グループコード'),
                'field' => 'price_percent_group_code',
            ),
            array(
                'label' => _g('掛率グループ名'),
                'field' => 'price_percent_group_name',
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
                price_percent_group_id
                ,price_percent_group_code
                ,price_percent_group_name
                ,price_percent

                ,price_percent_group_master.record_create_date as gen_record_create_date
                ,price_percent_group_master.record_creator as gen_record_creater
                ,coalesce(price_percent_group_master.record_update_date, price_percent_group_master.record_create_date) as gen_record_update_date
                ,coalesce(price_percent_group_master.record_updater, price_percent_group_master.record_creator) as gen_record_updater

            from
                price_percent_group_master
            [Where]
            [Orderby]
        ";

        $this->orderbyDefault = 'price_percent_group_code';
    }

    function setCsvParam(&$form)
    {
        $form['gen_importLabel'] = _g("掛率グループ");
        $form['gen_importMsg_noEscape'] = "";
        $form['gen_allowUpdateCheck'] = true;
        $form['gen_allowUpdateLabel'] = _g("上書き許可　（掛率グループコードが既存の場合はレコードを上書きする）");

        $form['gen_csvArray'] = array(
            array(
                'label' => _g('掛率グループコード'),
                'field' => 'price_percent_group_code',
                'unique' => true, // これを指定すると、インポート時にCSVファイル内での重複がチェックされる
            ),
            array(
                'label' => _g('掛率グループ名'),
                'field' => 'price_percent_group_name',
                'unique' => true, // これを指定すると、インポート時にCSVファイル内での重複がチェックされる
            ),
            array(
                'label' => _g('掛率 (％)'),
                'field' => 'price_percent',
            ),
        );
    }

    function setViewParam(&$form)
    {
        $form['gen_pageTitle'] = _g("掛率グループマスタ");
        $form['gen_menuAction'] = "Menu_Master";
        $form['gen_listAction'] = "Master_PricePercentGroup_List";
        $form['gen_editAction'] = "Master_PricePercentGroup_Edit";
        $form['gen_idField'] = 'price_percent_group_id';
        $form['gen_idFieldForUpdateFile'] = "price_percent_group_master.price_percent_group_id";   // これをセットすると「ファイル」「トークボード」列が自動追加される
        $form['gen_excel'] = "true";
        $form['gen_pageHelp'] = _g("掛率グループ");

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
                'deleteAction' => 'Master_PricePercentGroup_BulkDelete',
                // readonlyであれば表示しない
                'showCondition' => ($form['gen_readonly'] != 'true' ? "true" : "false"),
            ),
            array(
                'label' => _g('掛率グループコード'),
                'field' => 'price_percent_group_code',
            ),
            array(
                'label' => _g('掛率グループ名'),
                'field' => 'price_percent_group_name',
                'width' => '200',
            ),
            array(
                'label' => _g('掛率 (％)'),
                'field' => 'price_percent',
                'width' => '100',
                'type' => 'numeric',
            ),
        );
    }

}