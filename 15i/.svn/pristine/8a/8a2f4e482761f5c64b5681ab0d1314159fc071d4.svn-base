<?php

class Master_Currency_List extends Base_ListBase
{

    function setSearchCondition(&$form)
    {
        $form['gen_searchControlArray'] = array(
            array(
                'label' => _g('取引通貨'),
                'field' => 'currency_name',
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
                currency_id
                ,currency_name

                ,currency_master.record_create_date as gen_record_create_date
                ,currency_master.record_creator as gen_record_creater
                ,coalesce(currency_master.record_update_date, currency_master.record_create_date) as gen_record_update_date
                ,coalesce(currency_master.record_updater, currency_master.record_creator) as gen_record_updater

            from
                currency_master
            [Where]
            [Orderby]
        ";

        $this->orderbyDefault = 'currency_name';
    }

    function setCsvParam(&$form)
    {
        $form['gen_importLabel'] = _g("取引通貨");
        $form['gen_importMsg_noEscape'] = "";
        $form['gen_allowUpdateCheck'] = true;

        $form['gen_csvArray'] = array(
            array(
                'label' => _g('取引通貨'),
                'field' => 'currency_name',
                'unique' => true, // これを指定すると、インポート時にCSVファイル内での重複がチェックされる
            ),
        );
    }

    function setViewParam(&$form)
    {
        $form['gen_pageTitle'] = _g("通貨マスタ");
        $form['gen_menuAction'] = "Menu_Master";
        $form['gen_listAction'] = "Master_Currency_List";
        $form['gen_editAction'] = "Master_Currency_Edit";
        $form['gen_idField'] = 'currency_id';
        $form['gen_idFieldForUpdateFile'] = "currency_master.currency_id";   // これをセットすると「ファイル」「トークボード」列が自動追加される
        $form['gen_excel'] = "true";
        $form['gen_allowUpdateLabel'] = _g("上書き許可　（取引通貨が既存の場合はレコードを上書きする）");
        $form['gen_pageHelp'] = _g("取引通貨");

        $form['gen_isClickableTable'] = "true";     // 行をクリックして明細を開く
        $form['gen_directEditEnable'] = "true";     // 直接編集

        $form['gen_columnArray'] = array(
            array(
                'label' => _g('明細'),
                'type' => 'edit',
            ),
            array(
                'label' => _g('削除'),
                'type' => 'delete_check',
                'deleteAction' => 'Master_Currency_BulkDelete',
                // readonlyであれば表示しない
                'showCondition' => ($form['gen_readonly'] != 'true' ? "true" : "false"),
            ),
            array(
                'label' => _g('取引通貨'),
                'field' => 'currency_name',
                'width' => '200',
                'align' => 'center'
            ),
        );
    }

}
