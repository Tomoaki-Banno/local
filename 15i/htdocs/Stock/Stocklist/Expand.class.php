<?php

class Stock_Stocklist_Expand extends Base_ListBase
{

    function setSearchCondition(&$form)
    {
        global $gen_db;

        $query = "select item_group_id, item_group_name from item_group_master order by item_group_code";
        $option_item_group = $gen_db->getHtmlOptionArray($query, false, array(null => _g("(すべて)")));

        $form['gen_searchControlArray'] = array(
            array(
                'label' => _g('品目コード/名'),
                'field' => 'item_code',
                'field2' => 'item_name',
            ),
            array(
                'label' => _g('品目グループ'),
                'field' => 'item_group_id',
                'type' => 'select',
                'options' => $option_item_group,
            ),
            array(
                'label' => _g('合計在庫数'),
                'type' => 'numFromTo',
                'field' => 'total_stock',
                'size' => '80',
                'rowSpan' => 2,
            ),
            array(
                'label' => _g('日付'),
                'field' => 'stock_date',
                'type' => 'calendar',
                'nosql' => true,
            ),
            array(
                'label' => _g('サプライヤー在庫'),
                'field' => 'include_partner_stock',
                'type' => 'select',
                'options' => Gen_Option::getTrueOrFalse('search-include'),
                'nosql' => true,
                'default' => 'true',
            ),
        );
    }

    function convertSearchCondition($converter, &$form)
    {
    }

    function beforeLogic(&$form)
    {
        Logic_Stock::createFullExpandStockTable(@$form['gen_search_stock_date'], (@$form['gen_search_include_partner_stock'] != "false"));
    }

    function setQueryParam(&$form)
    {
        $this->selectQuery = "
            select
                temp_full_expand_stock.item_id
                ,item_code
                ,item_name
                ,inner_stock
                ,logical_stock
                ,total_stock
                ,measure
            from
                temp_full_expand_stock
                inner join item_master on temp_full_expand_stock.item_id = item_master.item_id
            [Where] 
                /* 非表示品目・ダミー品目は表示しない */
                and not coalesce(item_master.end_item, false)
                and not coalesce(item_master.dummy_item, false)
            [Orderby]
        ";

        $this->orderbyDefault = 'item_code';
        $this->customColumnTables = array(
            // array(カスタム項目があるテーブル名, テーブル名のエイリアス※1, classGroup※2, parentColumn※3, 明細カスタム項目取得(省略可)※4)
            //    ※1 テーブル名のエイリアス： ListのSQL内でテーブルにエイリアスをつけている場合、そのエイリアスを指定する。
            //    ※2 classGroup:　order_headerのように複数の画面で登録が行われるテーブルの場合、登録画面のクラスグループ（ex: Manufacturing_Order）を指定する
            //    ※3 parentColumn: そのテーブルのカスタム項目をsameCellJoinで表示する際、parentColumn となるカラム。
            //          例えば受注画面であれば、customer_master は received_header_id, item_master は received_detail_id となる。
            //    ※4 明細カスタム項目取得(省略可): これをtrueにすると明細カスタム項目も取得する。ただしSQLのfromに明細カスタム項目テーブルがJOINされている必要がある。
            //          estimate_detail, received_detail, delivery_detail, order_detail
            array("item_master", "", "", "item_id"),
        );        
    }

    function setViewParam(&$form)
    {
        $form['gen_pageTitle'] = _g("分解在庫リスト");
        $form['gen_listAction'] = "Stock_Stocklist_Expand";
        $form['gen_idField'] = "dummy";
        $form['gen_excel'] = "true";
        $form['gen_pageHelp'] = _g("分解在庫");

        $form['gen_returnUrl'] = "index.php?action=Stock_Stocklist_List&gen_restore_search_condition=true";
        $form['gen_returnCaption'] = _g('在庫リストへ戻る');

        $form['gen_message_noEscape'] = _g("末端品目（子品目を持たない品目）だけが表示されます。") . "<br>"
            . _g("上位品目はすべて末端品目まで分解されて「内包在庫」として表示されています。") . "<br>";

        $form['gen_columnArray'] = array(
            array(
                'label' => _g('品目コード'),
                'field' => 'item_code',
            ),
            array(
                'label' => _g('品目名'),
                'field' => 'item_name',
                'width' => '200',
            ),
            array(
                'label' => _g('内包在庫'),
                'field' => 'inner_stock',
                'width' => '100',
                'type' => 'numeric',
                'helpText_noEscape' => _g('上位品目在庫の中に内包されている品目数です（上位品目の理論在庫数×員数）。'),
            ),
            array(
                'label' => _g('単位'),
                'field' => 'measure',
                'type' => 'data',
                'width' => '35',
            ),
            array(
                'label' => _g('独立在庫'),
                'field' => 'logical_stock',
                'width' => '100',
                'type' => 'numeric',
                'helpText_noEscape' => _g('在庫リストの「理論在庫」と同じです。'),
            ),
            array(
                'label' => _g('合計在庫'),
                'field' => 'total_stock',
                'width' => '100',
                'type' => 'numeric',
            ),
        );
    }

}