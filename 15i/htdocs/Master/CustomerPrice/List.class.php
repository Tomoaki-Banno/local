<?php

class Master_CustomerPrice_List extends Base_ListBase
{

    function setSearchCondition(&$form)
    {
        $form['gen_searchControlArray'] = array(
            array(
                'label' => _g('得意先'),
                'type' => 'dropdown',
                'field' => 'customer_id',
                'size' => '150',
                'dropdownCategory' => 'customer',
                'nosql' => true,
            ),
            array(
                'label' => _g('品目'),
                'type' => 'dropdown',
                'field' => 'item_id',
                'size' => '150',
                'dropdownCategory' => 'item_received',
                'nosql' => true,
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
        global $gen_db;

        $keyCurrency = $gen_db->queryOneValue("select key_currency from company_master");
        $this->selectQuery = "
            select
                customer_price_master.*
                ,customer_master.customer_no
                ,customer_master.customer_name
                ,item_master.item_code
                ,item_master.item_name
                ,case when currency_name is null then '{$keyCurrency}' else currency_name end as currency_name

                ,customer_price_master.record_create_date as gen_record_create_date
                ,customer_price_master.record_creator as gen_record_creater
                ,coalesce(customer_price_master.record_update_date, customer_price_master.record_create_date) as gen_record_update_date
                ,coalesce(customer_price_master.record_updater, customer_price_master.record_creator) as gen_record_updater

            from
                customer_price_master
             	left join customer_master on customer_price_master.customer_id = customer_master.customer_id
             	left join item_master on customer_price_master.item_id = item_master.item_id
                left join currency_master on customer_master.currency_id = currency_master.currency_id
            [Where]
                " . (isset($form['gen_search_customer_id']) && is_numeric($form['gen_search_customer_id']) ? " and customer_price_master.customer_id = '{$form['gen_search_customer_id']}'" : "") . "
                " . (isset($form['gen_search_item_id']) && is_numeric($form['gen_search_item_id']) ? " and customer_price_master.item_id = '{$form['gen_search_item_id']}'" : "") . "
            [Orderby]
        ";

        $this->orderbyDefault = 'customer_no, item_code';
    }

    function setCsvParam(&$form)
    {
        $form['gen_importLabel'] = _g("得意先販売価格");
        $form['gen_importMsg_noEscape'] = "";
        $form['gen_allowUpdateCheck'] = true;
        $form['gen_allowUpdateLabel'] = _g("上書き許可　（得意先コード/品目コードが既存の場合はレコードを上書きする）");

        $form['gen_csvArray'] = array(
            array(
                'label' => _g('得意先コード'),
                'field' => 'customer_no',
            ),
            array(
                'label' => _g('品目コード'),
                'field' => 'item_code',
            ),
            array(
                'label' => _g('販売価格'),
                'field' => 'selling_price',
            ),
        );
    }

    function setViewParam(&$form)
    {
        $form['gen_pageTitle'] = _g("得意先販売価格マスタ");
        $form['gen_menuAction'] = "Menu_Master";
        $form['gen_listAction'] = "Master_CustomerPrice_List";
        $form['gen_editAction'] = "Master_CustomerPrice_Edit";
        $form['gen_idField'] = 'customer_price_id';
        $form['gen_idFieldForUpdateFile'] = "customer_price_master.customer_price_id";   // これをセットすると「ファイル」「トークボード」列が自動追加される
        $form['gen_excel'] = "true";
        $form['gen_pageHelp'] = _g("販売価格");

        $form['gen_isClickableTable'] = "true";     // 行をクリックして明細を開く
        $form['gen_directEditEnable'] = "true";     // 直接編集

        // 編集用エクセルファイル
        $form['gen_editExcel'] = "true";

        $form['gen_message_noEscape'] = _g("標準価格で販売する得意先や、掛率を適用する得意先は、このマスタを登録する必要はありません。");

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
                'deleteAction' => 'Master_CustomerPrice_BulkDelete',
                // readonlyであれば表示しない
                'showCondition' => ($form['gen_readonly'] != 'true' ? "true" : "false"),
            ),
            array(
                'label' => _g('得意先コード'),
                'field' => 'customer_no',
                'width' => '100',
                'editType'=>'dropdown',
                'dropdownCategory'=>'customer',
                'entryField'=>'customer_id', 
            ),
            array(
                'label' => _g('得意先名'),
                'field' => 'customer_name',
                'width' => '200',
                'editType'=>'none'
            ),
            array(
                'label' => _g('品目コード'),
                'field' => 'item_code',
                'width' => '100',
                'editType'=>'dropdown',
                'dropdownCategory'=>'item_received',
                'entryField'=>'item_id', 
            ),
            array(
                'label' => _g('品目名'),
                'field' => 'item_name',
                'width' => '200',
                'editType'=>'none'
            ),
            array(
                'label' => _g('取引通貨'),
                'field' => 'currency_name',
                'type' => 'data',
                'align' => 'center',
                'width' => '70',
                'editType'=>'none'
            ),
            array(
                'label' => _g('販売価格'),
                'field' => 'selling_price',
                'type' => 'numeric',
            ),
        );
    }

}
