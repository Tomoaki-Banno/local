<?php

class Stock_Move_List extends Base_ListBase
{

    function setSearchCondition(&$form)
    {
        global $gen_db;

        $query = "select item_group_id, item_group_name from item_group_master order by item_group_code";
        $option_item_group = $gen_db->getHtmlOptionArray($query, false, array(null => _g("(すべて)")));

        $query = "select location_id, location_name from location_master order by location_code";
        $option_location_group = $gen_db->getHtmlOptionArray($query, false, array(null => _g("(すべて)"), "0" => _g(GEN_DEFAULT_LOCATION_NAME)));

        $form['gen_searchControlArray'] = array(
            array(
                'label' => _g('移動日'),
                'field' => 'move_date',
                'type' => 'dateFromTo',
                'defaultFrom' => date('Y-m-01'),
                'rowSpan' => 2,
            ),
            array(
                'label' => _g('移動元'),
                'type' => 'select',
                'field' => 'source_location_id',
                'options' => $option_location_group,
            ),
            array(
                'label' => _g('移動先'),
                'type' => 'select',
                'field' => 'dist_location_id',
                'options' => $option_location_group,
            ),
            array(
                'label' => _g('品目コード/名'),
                'field' => 'item_code',
                'field2' => 'item_name',
                'hide' => true,
            ),
            array(
                'label' => _g('品目グループ'),
                'field' => 'item_group_id',
                'type' => 'select',
                'options' => $option_item_group,
                'hide' => true,
            ),
            array(
                'label' => _g('製番'),
                'field' => 'seiban',
                'hide' => true,
            ),
            array(
                'label' => _g('オーダー番号'),
                'field' => 'order_no',
                'ime' => 'off',
                'hide' => true,
            ),
            array(
                'label' => _g('ロケ間移動備考'),
                'field' => 'remarks',
                'ime' => 'on',
                'hide' => true,
            ),
            // 受払画面のリンクではidが指定される
            array(
                'type' => 'hidden',
                'field' => 'move_id',
            ),
        );

        // プリセット表示条件パターン
        $form['gen_savedSearchConditionPreset'] =
            array(
                _g("品目/ロケ別入庫（当月）") => self::_getPreset("5", "dist_location_name", "item_name"),
                _g("品目/ロケ別入庫（当年）") => self::_getPreset("7", "dist_location_name", "item_name"),
                _g("品目/ロケ別出庫（当月）") => self::_getPreset("5", "source_location_name", "item_name"),
                _g("品目/ロケ別出庫（当年）") => self::_getPreset("7", "source_location_name", "item_name"),
                _g("移動クロス表（当月）") => self::_getPreset("5", "source_location_name", "dist_location_name"),
                _g("移動クロス表（当年）") => self::_getPreset("7", "source_location_name", "dist_location_name"),
            );
    }
    
    function _getPreset($datePattern, $horiz, $vert, $orderby = "", $value = "quantity", $method = "sum")
    {
        return
            array(
                "data" => array(
                    array("f" => "move_date", "dp" => $datePattern),
                    
                    array("f" => "gen_crossTableHorizontal", "v" => $horiz),
                    array("f" => "gen_crossTableVertical", "v" => $vert),
                    array("f" => "gen_crossTableValue", "v" => $value),
                    array("f" => "gen_crossTableMethod", "v" => $method),
                    array("f" => "gen_crossTableChart", "v" => _g("すべて")),
                ),
                "orderby" => $orderby,
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
                move_id
                ,move_date
                ,item_code
                ,item_name
                ,case when source_location_code is null then '' else source_location_code end as source_location_code
                ,case when dist_location_code is null then '' else dist_location_code end as dist_location_code
                ,case when source_location_name is null then '" . _g(GEN_DEFAULT_LOCATION_NAME) . "' else source_location_name end as source_location_name
                ,case when dist_location_name is null then '" . _g(GEN_DEFAULT_LOCATION_NAME) . "' else dist_location_name end as dist_location_name
                ,quantity
                ,measure
                ,seiban
                ,order_no
                ,remarks
                ,case when move_printed_flag = true then '" . _g("印刷済") . "' else '' end as printed
                ,coalesce(location_move.record_update_date, location_move.record_create_date) as gen_record_update_date
                ,coalesce(location_move.record_updater, location_move.record_creator) as gen_record_updater
            from
                location_move
                left join item_master on location_move.item_id = item_master.item_id
                left join (select location_id as lid, location_code as source_location_code, location_name as source_location_name from location_master) as t_loc1 on location_move.source_location_id = t_loc1.lid
                left join (select location_id as lid, location_code as dist_location_code, location_name as dist_location_name from location_master) as t_loc2 on location_move.dist_location_id = t_loc2.lid
                left join (select order_detail_id as odid, order_no from order_detail) as t_order on location_move.order_detail_id = t_order.odid
            [Where]
            [Orderby]
        ";

        $this->orderbyDefault = 'move_date desc, item_code, seiban';
        $this->customColumnTables = array(
            // array(カスタム項目があるテーブル名, テーブル名のエイリアス※1, classGroup※2, parentColumn※3, 明細カスタム項目取得(省略可)※4)
            //    ※1 テーブル名のエイリアス： ListのSQL内でテーブルにエイリアスをつけている場合、そのエイリアスを指定する。
            //    ※2 classGroup:　order_headerのように複数の画面で登録が行われるテーブルの場合、登録画面のクラスグループ（ex: Manufacturing_Order）を指定する
            //    ※3 parentColumn: そのテーブルのカスタム項目をsameCellJoinで表示する際、parentColumn となるカラム。
            //          例えば受注画面であれば、customer_master は received_header_id, item_master は received_detail_id となる。
            //    ※4 明細カスタム項目取得(省略可): これをtrueにすると明細カスタム項目も取得する。ただしSQLのfromに明細カスタム項目テーブルがJOINされている必要がある。
            //          estimate_detail, received_detail, delivery_detail, order_detail
            array("item_master", "", "", "move_id"),
        );        
    }

    function setCsvParam(&$form)
    {
        $form['gen_importLabel'] = _g("ロケーション間移動登録");
        $form['gen_importMsg_noEscape'] = _g("※データは新規登録されます。（既存データの上書きはできません）");
        $form['gen_allowUpdateCheck'] = false;

        $form['gen_csvArray'] = array(
            array(
                'label' => _g('移動日'),
                'field' => 'move_date',
            ),
            array(
                'label' => _g('品目コード'),
                'field' => 'item_code',
            ),
            array(
                'label' => _g('製番'),
                'field' => 'seiban',
            ),
            array(
                'label' => _g('移動元ロケーションコード'),
                'field' => 'source_location_code',
            ),
            array(
                'label' => _g('移動先ロケーションコード'),
                'field' => 'dist_location_code',
            ),
            array(
                'label' => _g('数量'),
                'field' => 'quantity',
            ),
            array(
                'label' => _g('ロケ間移動備考'),
                'field' => 'remarks',
            ),
        );
    }

    function setViewParam(&$form)
    {
        $form['gen_pageTitle'] = _g("ロケーション間移動登録");
        $form['gen_listAction'] = "Stock_Move_List";
        $form['gen_editAction'] = "Stock_Move_Edit";
        $form['gen_deleteAction'] = "Stock_Move_Delete";
        $form['gen_idField'] = 'move_id';
        $form['gen_idFieldForUpdateFile'] = "location_move.move_id";   // これをセットすると「ファイル」「トークボード」列が自動追加される
        $form['gen_excel'] = "true";
        $form['gen_pageHelp'] = _g("ロケーション");

        $form['gen_isClickableTable'] = "true";

        $form['gen_goLinkArray'] = array(
            array(
                'id' => 'bulkEdit',
                'value' => _g('一括登録'),
                'onClick' => "javascript:location.href='index.php?action=Stock_Move_BulkEdit'",
            ),
        );

        $form['gen_reportArray'] = array(
            array(
                'label' => _g("在庫移動表 印刷"),
                'link' => "javascript:gen.list.printReport('Stock_Move_Report','check')",
                'reportEdit' => 'Stock_Move_Report'
            ),
        );

        $form['gen_fixColumnArray'] = array(
            array(
                'label' => _g('明細'),
                'type' => 'edit',
            ),
            array(
                'label' => _g('削除'),
                'type' => 'delete_check',
                'deleteAction' => 'Stock_Move_BulkDelete',
                // readonlyであれば表示しない
                'showCondition' => ($form['gen_readonly'] != 'true' ? "true" : "false"),
            ),
            array(
                'label' => _g("印刷"),
                'name' => 'check',
                'type' => 'checkbox',
            ),
            array(
                'label' => _g('印刷済'),
                'field' => 'printed',
                'width' => '47',
                'align' => 'center',
                'cellId' => 'check_[id]_printed', // 印刷時書き換え用
            ),
            array(
                'label' => _g('移動日'),
                'field' => 'move_date',
                'type' => 'date',
                'width' => '80',
                'align' => 'center',
            ),
        );
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
                'label' => _g('製番'),
                'field' => 'seiban',
                'width' => '100',
                'align' => 'center',
            ),
            array(
                'label' => _g('移動元'),
                'field' => 'source_location_name',
                'width' => '200',
            ),
            array(
                'label' => _g('移動先'),
                'field' => 'dist_location_name',
                'width' => '200',
            ),
            array(
                'label' => _g('数量'),
                'field' => 'quantity',
                'type' => 'numeric',
            ),
            array(
                'label' => _g('単位'),
                'field' => 'measure',
                'type' => 'data',
                'width' => '35',
            ),
            array(
                'label' => _g('オーダー番号'),
                'field' => 'order_no',
                'hide' => true,
            ),
            array(
                'label' => _g('ロケ間移動備考'),
                'field' => 'remarks',
                'hide' => true,
            ),
        );
    }

}
