<?php

class Stock_SeibanChange_List extends Base_ListBase
{

    function setSearchCondition(&$form)
    {
        global $gen_db;

        $query = "select item_group_id, item_group_name from item_group_master order by item_group_code";
        $option_item_group = $gen_db->getHtmlOptionArray($query, false, array(null => _g("(すべて)")));

        $form['gen_searchControlArray'] = array(
            array(
                'label' => _g('日付'),
                'field' => 'change_date',
                'type' => 'dateFromTo',
                'defaultFrom' => date('Y-m-01'),
                'defaultTo' => Gen_String::getThisMonthLastDateString(),
                'rowSpan' => 2,
            ),
            array(
                'label' => _g('引当元製番'),
                'field' => 'source_seiban',
            ),
            array(
                'label' => _g('引当後製番'),
                'field' => 'dist_seiban',
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
                'label' => _g('製番引当備考'),
                'field' => 'remarks',
                'ime' => 'on',
                'hide' => true,
            ),
            // 受払画面のリンクではidが指定される
            array(
                'type' => 'hidden',
                'field' => 'change_id',
            ),
        );

        // プリセット表示条件パターン
        $form['gen_savedSearchConditionPreset'] =
            array(
                _g("引当クロス表（当月）") => self::_getPreset("5", "source_seiban", "dist_seiban"),
                _g("引当クロス表（当年）") => self::_getPreset("7", "source_seiban", "dist_seiban"),
            );
    }
    
    function _getPreset($datePattern, $horiz, $vert, $orderby = "", $value = "quantity", $method = "sum")
    {
        return
            array(
                "data" => array(
                    array("f" => "change_date", "dp" => $datePattern),
                    
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
        // 所要量計算結果の取り込み
        $changeIdList = "";
        if (isset($form['mrp'])) {
            $changeIdArray = Logic_SeibanChange::mrpToSeibanChange();

            if (is_array($changeIdArray)) {
                $changeIdList = join($changeIdArray, ",");    // 配列をカンマ区切り文字列にする

                $_SESSION['gen_seiban_change_list_for_mrp_mode'] = $changeIdList;

                Gen_Log::dataAccessLog(_g("製番引当登録"), _g("新規"), _g("所要量計算結果からの一括引当"));
            }
        }

        // 取込モードで、明細画面へ行ってから戻ったときに、モード解除
        // されてしまわないようにするためのsession処理
        if (isset($form['mrp']) || (isset($form['gen_restore_search_condition']) && @$_SESSION['gen_seiban_change_list_for_mrp_mode'] != "")) {
            if (!isset($form['mrp'])) {
                $changeIdList = $_SESSION['gen_seiban_change_list_for_mrp_mode'];
                $form['mrp'] = true;
            }
        } else {
            unset($_SESSION['gen_seiban_change_list_for_mrp_mode']);
        }

        $this->selectQuery = "
            select
                change_id
                ,change_date
                ,item_code
                ,item_name
                ,location_name
                ,case when source_seiban='' then '" . _g("(なし)") . "' else source_seiban end as source_seiban
                ,case when dist_seiban='' then '" . _g("(なし)") . "' else dist_seiban end as dist_seiban
                ,t_source_lot.lot_no as source_lot_no
                ,quantity
                ,measure
                ,remarks

                ,coalesce(seiban_change.record_update_date, seiban_change.record_create_date) as gen_record_update_date
                ,coalesce(seiban_change.record_updater, seiban_change.record_creator) as gen_record_updater

            from
                seiban_change
                left join item_master on seiban_change.item_id = item_master.item_id
                left join location_master on seiban_change.location_id = location_master.location_id
                left join (select stock_seiban, lot_no from achievement where stock_seiban <> ''
                     union select stock_seiban, lot_no from accepted where stock_seiban <> ''
                     ) as t_source_lot on seiban_change.source_seiban = t_source_lot.stock_seiban and item_master.order_class = 2
            [Where]
        ";
        if (isset($form['mrp'])) {
            // 所要量計算の結果取込モードの場合
            //  取り込まれたデータのみを表示
            if ($changeIdList == "") {
                $this->selectQuery .= " and 1=0";
            } else {
                $this->selectQuery .= " and change_id in ({$changeIdList})";
            }
        }
        $this->selectQuery .=
                "[Orderby]";

        $this->orderbyDefault = 'change_date desc, source_seiban, dist_seiban, item_code';
        $this->customColumnTables = array(
            // array(カスタム項目があるテーブル名, テーブル名のエイリアス※1, classGroup※2, parentColumn※3, 明細カスタム項目取得(省略可)※4)
            //    ※1 テーブル名のエイリアス： ListのSQL内でテーブルにエイリアスをつけている場合、そのエイリアスを指定する。
            //    ※2 classGroup:　order_headerのように複数の画面で登録が行われるテーブルの場合、登録画面のクラスグループ（ex: Manufacturing_Order）を指定する
            //    ※3 parentColumn: そのテーブルのカスタム項目をsameCellJoinで表示する際、parentColumn となるカラム。
            //          例えば受注画面であれば、customer_master は received_header_id, item_master は received_detail_id となる。
            //    ※4 明細カスタム項目取得(省略可): これをtrueにすると明細カスタム項目も取得する。ただしSQLのfromに明細カスタム項目テーブルがJOINされている必要がある。
            //          estimate_detail, received_detail, delivery_detail, order_detail
            array("item_master", "", "", "change_id"),
            array("location_master", "", "", "change_id"),
        );        
    }

    function setViewParam(&$form)
    {
        $form['gen_pageTitle'] = _g("製番引当登録");
        // gen_restore_search_conditionは、MRP取込モードで絞り込み条件検索したとき、取込モードが解除されてしまうのを避けるため。
        $form['gen_listAction'] = "Stock_SeibanChange_List&gen_restore_search_condition=true";
        $form['gen_editAction'] = "Stock_SeibanChange_Edit";
        $form['gen_deleteAction'] = "Stock_SeibanChange_Delete";
        $form['gen_idField'] = 'change_id';
        $form['gen_idFieldForUpdateFile'] = "seiban_change.change_id";   // これをセットすると「ファイル」「トークボード」列が自動追加される
        $form['gen_excel'] = "true";
        $form['gen_pageHelp'] = _g("製番引当");

        $form['gen_isClickableTable'] = "true";

        $form['gen_message_noEscape'] = "";
        if (isset($form['mrp'])) {
            $form['gen_message_noEscape'] .= "<br>" . _g("今回の所要量計算により作成された引当データだけが表示されています。") . "<BR>";
            $form['gen_message_noEscape'] .= "<a href=\"index.php?action=Manufacturing_Mrp_List\">" . _g("所要量計算の結果に戻る") . "</a><br>";
        }

        $form['gen_fixColumnArray'] = array(
            array(
                'label' => _g('明細'),
                'type' => 'edit',
            ),
            array(
                'label' => _g('削除'),
                'type' => 'delete_check',
                'deleteAction' => 'Stock_SeibanChange_BulkDelete',
                // readonlyであれば表示しない
                'showCondition' => ($form['gen_readonly'] != 'true' ? "true" : "false"),
            ),
            array(
                'label' => _g('日付'),
                'field' => 'change_date',
                'type' => 'date',
            ),
            array(
                'label' => _g('品目コード'),
                'field' => 'item_code',
            ),
            array(
                'label' => _g('品目名'),
                'field' => 'item_name',
                'width' => '200',
            ),
        );
        $form['gen_columnArray'] = array(
            array(
                'label' => _g('ロケーション'),
                'field' => 'location_name',
                'width' => '200',
            ),
            array(
                'label' => _g('引当元製番'),
                'field' => 'source_seiban',
                'width' => '100',
                'align' => 'center',
            ),
            array(
                'label' => _g('引当元ロット'),
                'field' => 'source_lot_no',
                'width' => '100',
                'align' => 'center',
                'helpText_noEscape' => _g("品目マスタ [管理区分] が「ロット」の品目のみ表示されます。") . "<br><br>"
                    ._g("受注登録画面で在庫ロットを受注に引き当てると、この欄に在庫ロットが表示されます。引当先の受注は「引当後製番」欄に表示されます。") . "<br>"
                    ._g("引当を削除すると、在庫ロットの受注への引当を解除したことになります。"),
            ),
            array(
                'label' => _g('引当後製番'),
                'field' => 'dist_seiban',
                'width' => '100',
                'align' => 'center',
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
                'label' => _g('製番引当備考'),
                'field' => 'remarks',
                'hide' => true,
            ),
        );
    }

}
