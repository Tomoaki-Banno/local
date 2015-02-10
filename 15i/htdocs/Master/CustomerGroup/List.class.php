<?php

require_once(BASE_DIR . "ListBase.class.php");

class Master_CustomerGroup_List extends Base_ListBase
{

    function setSearchCondition(&$form)
    {
        $form['gen_searchControlArray'] = array(
            array(
                'label' => _g('取引先グループコード'),
                'field' => 'customer_group_code',
            ),
            array(
                'label' => _g('取引先グループ名'),
                'field' => 'customer_group_name',
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
                customer_group_id
                ,customer_group_code
                ,customer_group_name

                ,customer_group_master.record_create_date as gen_record_create_date
                ,customer_group_master.record_creator as gen_record_creater
                ,coalesce(customer_group_master.record_update_date, customer_group_master.record_create_date) as gen_record_update_date
                ,coalesce(customer_group_master.record_updater, customer_group_master.record_creator) as gen_record_updater
             from
                customer_group_master
             [Where] [Orderby]
        ";

        $this->orderbyDefault = 'customer_group_code';
    }

    function setCsvParam(&$form)
    {
        $form['gen_importLabel'] = _g("取引先グループ");
        $form['gen_importMsg_noEscape'] = "";
        $form['gen_allowUpdateCheck'] = true;
        $form['gen_allowUpdateLabel'] = _g("上書き許可　（取引先グループコードが既存の場合はレコードを上書きする）");

        $form['gen_csvArray'] = array(
            array(
                'label' => _g('取引先グループコード'),
                'field' => 'customer_group_code',
                'unique' => true, // これを指定すると、インポート時にCSVファイル内での重複がチェックされる
            ),
            array(
                'label' => _g('取引先グループ名'),
                'field' => 'customer_group_name',
            ),
        );
    }

    function setViewParam(&$form)
    {
        $form['gen_pageTitle'] = _g("取引先グループマスタ");
        $form['gen_menuAction'] = "Menu_Master";
        $form['gen_listAction'] = "Master_CustomerGroup_List";
        $form['gen_editAction'] = "Master_CustomerGroup_Edit";
        $form['gen_idField'] = 'customer_group_id';
        $form['gen_idFieldForUpdateFile'] = "customer_group_master.customer_group_id";   // これをセットすると「ファイル」「トークボード」列が自動追加される
        $form['gen_excel'] = "true";
        $form['gen_pageHelp'] = _g("取引先グループ");

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
                'deleteAction' => 'Master_CustomerGroup_BulkDelete',
                // readonlyであれば表示しない
                'showCondition' => ($form['gen_readonly'] != 'true' ? "true" : "false"),
            ),
            array(
                'label' => _g('取引先グループコード'),
                'field' => 'customer_group_code',
            ),
            array(
                'label' => _g('取引先グループ名'),
                'field' => 'customer_group_name',
                'width' => '300',
            ),
        );
    }

}