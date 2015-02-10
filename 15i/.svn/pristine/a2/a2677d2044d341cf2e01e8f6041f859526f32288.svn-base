<?php

class Monthly_StockInput_List extends Base_ListBase
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
                'label' => _g('棚卸日(表示/登録)'),
                'type' => 'calendar',
                'field' => 'inventory_date',
                'style' => 'background-color:#ffcccc',
                'nosql' => true,
                // デフォルトは前月末
                'default' => Gen_String::getLastMonthLastDateString(),
                'onChange_noEscape' => "onDateChange();",
                'denyMove' => true, // 非表示にできないようにする
                'helpText_noEscape' => _g("「再表示」ボタンを押したとき、この日付の時点の棚卸データが表示されます。") . "<br>"
                    . _g("また、登録の際はこの日付で棚卸が登録されます。"),
            ),
            array(
                'label' => _g('品目コード/名'),
                'field' => 'item_code',
                'field2' => 'item_name',
            ),
            array(
                'label' => _g('品目グループ'),
                'type' => 'select',
                'field' => 'item_group_id',
                'options' => $option_item_group,
                'hide' => true,
            ),
            array(
                'label' => _g('管理区分'),
                'type' => 'select',
                'field' => 'order_class',
                'options' => Gen_Option::getOrderClass('search'),
                'default' => 1,
                'hide' => true,
            ),
            array(
                'label' => _g('ロケーション'),
                'type' => 'select',
                'field' => 't_base___location_id',
                'options' => $option_location_group,
            ),
            array(      // クロス集計のために設けた項目
                'label' => _g('モード'),
                'type' => 'select',
                'field' => 'mode',
                'options' => array(0 => _g("入力モード"), 1 => _g('表示モード')),
                'default' => '0',
                'nosql' => true,
                'helpText_noEscape' => _g("入力モードでは実在庫数や備考の入力ができます。表示モードでは閲覧のみです。"),
            ),
            array(
                'label' => _g('差異がある行のみ'),
                'type' => 'select',
                'field' => 'diff_exist',
                'options' => array(0 => _g("すべての行を表示"), 1 => _g('差異がある行のみ表示')),
                'default' => '0',
                'nosql' => true,
            ),
            array(
                'label' => _g('理論在庫がある行のみ'),
                'type' => 'select',
                'field' => 'logical_stock_exist',
                'options' => array(0 => _g("すべての行を表示"), 1 => _g('理論在庫がある行のみ表示')),
                'default' => '0',
                'nosql' => true,
            ),
            array(
                'label' => _g('登録がある行のみ'),
                'type' => 'select',
                'field' => 'inventory_exist',
                'options' => array(0 => _g("すべての行を表示"), 1 => _g('登録がある行のみ表示')),
                'default' => '0',
                'nosql' => true,
            ),
            array(
                'label'=>_g('ロット番号'),
                'field'=>'lot_no',
            ),
        );
    
        // プリセット表示条件パターン
        $form['gen_savedSearchConditionPreset'] =
            array(
                _g("品目別 実在庫数") => self::_getPreset("0", "0", "1", "gen_all", "item_name", "order by field1 desc"),
                _g("品目別 理論在庫数") => self::_getPreset("0", "1", "0", "gen_all", "item_name", "order by field1 desc", "logical_stock_quantity"),
                _g("品目別 差異数") => self::_getPreset("1", "0", "0", "gen_all", "item_name", "order by field1 desc", "diff_quantity"),
                _g("ロケ別 実在庫数") => self::_getPreset("0", "0", "1", "location_name", "item_name"),
                _g("ロケ別 理論在庫数") => self::_getPreset("0", "1", "0", "location_name", "item_name", "", "logical_stock_quantity"),
                _g("ロケ別 差異数") => self::_getPreset("1", "0", "0", "location_name", "item_name", "", "diff_quantity"),
            );
    }
    
    function _getPreset($diffExist, $logicalStockExist, $inventoryExist, $horiz, $vert, $orderby = "", $value = "inventory_quantity", $method = "sum")
    {
        return
            array(
                "data" => array(
                    array("f" => "mode", "v" => "1"),   // 表示モード
                    array("f" => "diff_exist", "v" => $diffExist),              // 0:すべて、1:差異ありのみ
                    array("f" => "logical_stock_exist", "v" => $logicalStockExist),     // 0:すべて、1:理論在庫ありのみ
                    array("f" => "inventory_exist", "v" => $inventoryExist),         // 0:すべて、1:登録ありのみ
                    
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
        // 日付指定が不正のときは、前月末とする
        if (!Gen_String::isDateString($form['gen_search_inventory_date'])) {
            $form["gen_search_inventory_date"] = Gen_String::getLastMonthLastDateString();
        }

        // 指定日付時点の理論在庫（棚卸前）を計算
        // 09iまでは単に理論在庫を取得していたため、棚卸後は「理論在庫=棚卸数」になっていた。
        // 10iでは棚卸前の理論在庫を取得することで、棚卸差異を計算できるようにした。
        Logic_Stock::createTempStockTable(
            $form['gen_search_inventory_date']
            , null      // item_id
            , null      // seiban
            , null      // location_id
            , null      // lot_id
            , false     // 有効在庫を取得しない
            , true      // サプライヤーロケを含める
            , false     // use_planを将来分まで差し引かない（有効在庫を取得しないので無関係）
            , true      // stockDate当日の棚卸を計算から除外する。つまり棚卸がある場合、棚卸前の数値を取得する
        );
        
        // 指定日時点の在庫評価単価をテンポラリテーブル（temp_stock_price）に取得
        Logic_Stock::createTempStockPriceTable($form['gen_search_inventory_date']);
    }

    function setQueryParam(&$form)
    {
        // 以下では、マスタに登録されている品目とロケはすべてリストアップされる。（製番・ロットは動きがあったもののみ）
        // ただ、ロケの数が多いときにレコード数がかなり多くなるので、ロケは動きがあったものだけのほうが
        // いいかもしれない・・。
        $this->selectQuery = "
            select
                cast(t_base.item_id as text) || '_' ||
                    cast(t_base.location_id as text) || '_' ||
                    '0' || '_' ||
                    coalesce(t_base.seiban,'') as item_location_lot_seiban
                ,'" . $form['gen_search_inventory_date'] . "' as inventory_date  -- for csv
                ,item_code
                ,item_name
                ,t_base.seiban
                ,t_base.location_id
                ,location_code  -- for csv
                ,case when t_base.location_id=0 then '" . _g(GEN_DEFAULT_LOCATION_NAME) . "' else location_name end as location_name
                ,item_master.rack_no
                ,item_master.measure
                ,item_master.maker_name
                ,item_master.spec
                ,item_master.comment
                ,item_master.comment_2
                ,item_master.comment_3
                ,item_master.comment_4
                ,item_master.comment_5

                ,inventory_quantity
                ,temp_stock.logical_stock_quantity
                ,temp_stock_price.stock_price
                ,inventory_quantity * temp_stock_price.stock_price as stock_amount
                 /* inventory_quantity は coalesceしないことに注意（実在庫未入力のときは差異0ではなく空欄とするため） */
                ,inventory_quantity - coalesce(temp_stock.logical_stock_quantity,0) as diff_quantity
                ,(inventory_quantity - coalesce(temp_stock.logical_stock_quantity,0)) * temp_stock_price.stock_price as diff_amount
                ,t_ach_acc.lot_no
                ,t_ach_acc.use_by
                ,t_inv.remarks

                ,t_inv.record_create_date as gen_record_create_date
                ,t_inv.record_creator as gen_record_creater
                ,coalesce(t_inv.record_update_date, t_inv.record_create_date) as gen_record_update_date
                ,coalesce(t_inv.record_updater, t_inv.record_creator) as gen_record_updater

            from
                /* 品目・ロケはすべてリストアップ。製番とロットはそれまでに何らかの動きがあったものしかでてこない */
                (select
                    t0.item_id
                    ,location_id
                    ,seiban
                    ,lot_id
                from
                    (select
                        item_id
                        ,location_id
                    from
                        item_master,
                        (select
                            location_id
                        from
                            location_master
                        union
                        select
                            0 as location_id
                        from
                            item_master
                        ) as t00
                    ) as t0
                    /* 製番リストアップ */
                    left join (
                        select
                            item_id, seiban
                        from
                            temp_stock
                        group by
                            item_id, seiban
                        union
                        select
                            item_id, seiban
                        from
                            inventory
                        group by
                            item_id, seiban
                        ) as t1 on t0.item_id = t1.item_id
                    /* ロットリストアップ（実質未使用）*/
                    left join (
                        select
                            item_id, lot_id
                        from
                            temp_stock
                        group by
                            item_id, lot_id
                        union
                        select
                            item_id, lot_id
                        from
                            inventory
                        group by
                            item_id, lot_id
                        ) as t2 on t0.item_id = t2.item_id
                ) as t_base

                /* 理論在庫 */
                left join (
                    select
                        item_id
                        ,seiban
                        ,location_id as lid
                        ,lot_id
                        ,logical_stock_quantity
                    from
                        temp_stock
                    ) as temp_stock
                    on t_base.item_id = temp_stock.item_id
                    and t_base.seiban = temp_stock.seiban
                    and t_base.location_id = temp_stock.lid
                    and t_base.lot_id = temp_stock.lot_id

                /* 実在庫 */
                left join (
                    select
                        item_id
                        ,seiban
                        ,location_id as locId
                        ,lot_id as lotId
                        ,inventory_date
                        ,inventory_quantity
                        ,remarks

                        ,record_create_date
                        ,record_creator
                        ,record_update_date
                        ,record_updater
                    from
                        inventory
                    ) as t_inv
                    on t_base.item_id = t_inv.item_id
                    and t_base.seiban = t_inv.seiban
                    and t_base.location_id = t_inv.locId
                    and t_base.lot_id = t_inv.lotId
                    and inventory_date = '{$form['gen_search_inventory_date']}'::date

                left join item_master on t_base.item_id = item_master.item_id
                left join location_master on t_base.location_id = location_master.location_id
                
                /* 在庫評価単価 */
                left join temp_stock_price on t_base.item_id = temp_stock_price.item_id
                
                /* ロット番号/消費期限が表示されるのはロット品目のみ。
                    この制限がないと、製番品目の受入/実績でロット番号/消費期限を登録した場合に、同じ製番のオーダーすべてに同じロット番号/消費期限が
                    表示されてしまうことになる。 */
                LEFT JOIN (select stock_seiban, use_by, lot_no from achievement where stock_seiban <> ''
                     union select stock_seiban, use_by, lot_no from accepted where stock_seiban <> ''
                     ) as t_ach_acc on temp_stock.seiban = t_ach_acc.stock_seiban and temp_stock.seiban <>'' and item_master.order_class = 2
                
            [Where]
                /* 非表示品目・ダミー品目は表示しない */
                and not coalesce(item_master.end_item, false)
            	and not coalesce(item_master.dummy_item, false)
                " . (@$form['gen_search_diff_exist'] == "1" ? "and inventory_quantity - coalesce(temp_stock.logical_stock_quantity,0) <> 0" : "") . "
                " . (@$form['gen_search_logical_stock_exist'] == "1" ? "and coalesce(temp_stock.logical_stock_quantity,0) <> 0" : "") . "
                " . (@$form['gen_search_inventory_exist'] == "1" ? "and coalesce(t_inv.inventory_quantity,0) <> 0" : "") . "

            [Orderby]
        ";

        $this->orderbyDefault = 'item_code, location_name, seiban';
        $this->pageRecordCount = 50;
        $this->customColumnTables = array(
            // array(カスタム項目があるテーブル名, テーブル名のエイリアス※1, classGroup※2, parentColumn※3, 明細カスタム項目取得(省略可)※4)
            //    ※1 テーブル名のエイリアス： ListのSQL内でテーブルにエイリアスをつけている場合、そのエイリアスを指定する。
            //    ※2 classGroup:　order_headerのように複数の画面で登録が行われるテーブルの場合、登録画面のクラスグループ（ex: Manufacturing_Order）を指定する
            //    ※3 parentColumn: そのテーブルのカスタム項目をsameCellJoinで表示する際、parentColumn となるカラム。
            //          例えば受注画面であれば、customer_master は received_header_id, item_master は received_detail_id となる。
            //    ※4 明細カスタム項目取得(省略可): これをtrueにすると明細カスタム項目も取得する。ただしSQLのfromに明細カスタム項目テーブルがJOINされている必要がある。
            //          estimate_detail, received_detail, delivery_detail, order_detail
            array("item_master", "", "", "item_location_lot_seiban"),
            array("location_master", "", "", "item_location_lot_seiban"),
        );        

        // 「エクセル出力(ロケ別)」機能のときはロケ順に並べる
        if (isset($form['locExcel'])) {
            $form['gen_search_orderby_force'] = "location_id";
        }
    }

    function setCsvParam(&$form)
    {
        $form['gen_importLabel'] = _g("棚卸登録");
        $form['gen_importMsg_noEscape'] = _g("※データは新規登録されます。（既存データの上書きはできません）") . '<br><br>' .
                _g("※フォーマットは次のとおりです。") . '<br>' .
                _g("　　棚卸日, 品目コード, (品目名), ロケーションコード, (ロケーション名), 製番, (ロット番号), (理論在庫数), 実在庫数, (単位), 棚卸備考") . '<br><br>' .
                _g("※上記カッコ内の項目はインポートされませんが、項目としては必要です。どんな値でもかまいません。") . '<br><br>' .
                _g("※実在庫数を空欄にすると、その棚卸登録が削除されます。");
        $form['gen_allowUpdateCheck'] = false;

        if (isset($form['gen_csvMode'])) {
            $form['gen_search_orderby_force'] = "item_code, location_name, seiban";
        }

        $form['gen_csvArray'] = array(
            array(
                'label' => _g('棚卸日'),
                'field' => 'inventory_date',
            ),
            array(
                'label' => _g('品目コード'),
                'field' => 'item_code',
            ),
            array(
                'label' => _g('品目名'),
                'field' => 'item_name',
            ),
            array(
                'label' => _g('ロケーションコード'),
                'field' => 'location_code',
            ),
            array(
                'label' => _g('ロケーション名'),
                'field' => 'location_name',
            ),
            array(
                'label' => _g('製番'),
                'field' => 'seiban',
            ),
                array(
                    'label'=>_g('ロット番号'),
                    'field'=>'lot_no',
                ),
            array(
                'label' => _g('理論在庫'),
                'field' => 'logical_stock_quantity',
            ),
            array(
                'label' => _g('実在庫'),
                'field' => 'inventory_quantity',
            ),
            array(
                'label' => _g('単位'),
                'field' => 'measure',
            ),
            array(
                'label' => _g('棚卸備考'),
                'field' => 'remarks',
            ),
        );
    }

    function setViewParam(&$form)
    {
        global $gen_db;

        $form['gen_pageTitle'] = _g("棚卸登録");
        $form['gen_menuAction'] = "Menu_Stock";
        $form['gen_listAction'] = "Monthly_StockInput_List";
        $form['gen_editAction'] = "";
        $form['gen_deleteAction'] = "";
        $form['gen_idField'] = 'item_location_lot_seiban';
        $form['gen_excel'] = "true";
        $form['gen_pageHelp'] = _g("棚卸");

        $form['gen_titleRowHeight'] = 40;       // 見出し部の1行の高さ
        $form['gen_dataRowHeight'] = 25;        // データ部の1行の高さ
        
        $form['gen_goLinkArray'] = array(
            array(
                'id' => 'barcodeInput',
                'value' => _g('バーコード登録'),
                'onClick' => "javascript:gen.modal.open('index.php?action=Monthly_StockInput_BarcodeEdit')",
            )
        );

        $form['gen_excelLinkArray'] = array(
            array(
                'icon' => 'img/report-excel.png',
                'label' => _g('エクセル出力(ロケ別)'),
                'action' => "{$form['action']}&gen_excelMode&gen_restore_search_condition=true&locExcel",
            ),
        );
                
        // Entryでの登録後の遷移先ページを決める。
        //  $form['gen_isLastPage']は、ListBaseでセットされる変数
        if (@$form['gen_isLastPage']) {
            // 最終ページの場合。非数字をセットすることにより登録後にMenu画面に戻る
            $searchPage = "fin";
        } else {
            // 最終ページ以外の場合。登録後は次のページを表示する
            $searchPage = (is_numeric(@$form['gen_search_page']) ? $form['gen_search_page'] + 1 : 2);
        }

        $form['gen_beforeSearchScript'] = "onSearch()";

        $form['gen_javascript_noEscape'] = "
            var isModified = false;	// データが修正されたかどうか

            function onSearch() {
                if (isModified) {
                    if (!confirm('" . _g("データが変更されています。このまま再表示を行うと変更されたデータは破棄されます。データを破棄してもよろしいですか？（データを登録する場合は「キャンセル」を選択して、「登録」ボタンを押してください。））") . "')) {
                        return;
                    }
                }
                gen.list.postForm();
            }

            function mod() {
            	isModified = true;
            }

            function doEntry() {
                if ($('#gen_search_inventory_date').val()==undefined) {
                    alert('" . _g("表示条件の項目「棚卸日」が非表示の状態では登録できません。「棚卸日」を表示してください。") . "');
                    return;
                }

                if (!confirm('" . _g('このページの棚卸を登録します。\nよろしいですか？\n（登録後、次のページが表示されます。）') . "')) return;

                var postUrl = 'index.php?action=Monthly_StockInput_BulkEntry';
                postUrl += '&inventory_date=' + $('#gen_search_inventory_date').val();
                postUrl += '&gen_restore_search_condition=true';
                postUrl += '&gen_search_page=" . $searchPage . "';
                var frm = new gen.postSubmit();
                var elms = document.getElementById('form1').elements;
                for (i=0; i<elms.length; i++) {
                    // 空欄も登録（空欄の場合は既存データを削除する）
                   if (elms[i].name.substr(0,19) == 'inventory_quantity_') {
                       if (!gen.util.isNumeric(elms[i].value) && elms[i].value !='') {
                           alert('" . _g("数値を入力してください。") . "'); elms[i].focus(); return;
                       }
                       frm.add(elms[i].name, elms[i].value);
                   } else if (elms[i].name.substr(0,8) == 'remarks_') {
                       frm.add(elms[i].name, elms[i].value);
                   }
                }
                frm.submit(postUrl, null);
            }

            function onDateChange() {
               var invDateElm = document.getElementById('gen_search_inventory_date');
               var invDate = invDateElm.value;
               if (gen.date.isDate(invDate)) {
               } else {
                   alert('" . _g("日付が正しくありません。") . "');
                   invDateElm.focus();
               }
            }

            // 実在庫変更イベント。
            // 差異数・棚卸金額・差異金額、およびそれらの集計値を書き換える。
            function onQtyChange(id) {
                // 理論在庫
                var log = gen.util.nz(gen.util.delFigure($('#logical_stock_quantity_'+id).html()));
                // 実在庫
                var inv = $('#inventory_quantity_'+id).val();
                // 差異数・棚卸金額・差異金額の更新
                var dif = '';
                var amo = '';
                var difamo = '';
                if (gen.util.isNumeric(inv)) {
                    inv = parseFloat(inv);
                    dif = inv - log;
                    var pri = gen.util.delFigure($('#stock_price_'+id).html());
                    if (gen.util.isNumeric(pri)) {
                        pri = parseFloat(pri);
                        amo = inv * pri;
                        difamo = dif * pri;
                    }
                }
                showVal('diff_quantity', id, dif);
                showVal('stock_amount', id, amo);
                showVal('diff_amount', id, difamo);

                // 集計タイプcount/distinctのときは、inventory_quantity列にも集計値が表示される。
                // その集計値を更新する必要がある。
                var aggregateType = $('#gen_aggregateType').val();
                if (aggregateType=='count' || aggregateType=='distinct') {
                    $('#gen_aggregate_inventory_quantity').html($('#gen_aggregate_stock_amount').html());
                }
            }

            // セル(div)値と集計行の更新
            function showVal(field, id, val) {
                // セル値の更新
                var elm = $('#'+field+'_'+id);
                var aggregateType = $('#gen_aggregateType').val();
                var before = elm.html();
                elm.html(val);
                // 集計行の更新
                var aggElm = $('#gen_aggregate_'+field);
                var aggBefore = parseFloat(aggElm.html());
                if (gen.util.isNumeric(aggBefore)) {
                    // 集計タイプ別に処理。「avg」は処理しない。
                    switch(aggregateType) {
                    case 'sum':
                        var delta = parseFloat(gen.util.nz(before)) - parseFloat(gen.util.nz(val));
                        aggElm.html(aggBefore - delta);
                        break;
                    case 'max':
                        if (val > aggBefore) aggElm.html(val);
                        break;
                    case 'min':
                        if (val < aggBefore) aggElm.html(val);
                        break;
                    case 'count':
                    case 'distinct':    // countとdistinctは同じ処理とする。distinctはこれではおかしいのだが・・
                        if (before=='' && val!=='') aggElm.html(aggBefore+1);
                        if (before!='' && val==='') aggElm.html(aggBefore-1);
                        break;
                    case 'avg':  // 処理しようがないのであきらめる
                        break;
                    }
                }
            }
        ";

        // データロック基準日
        $lock_date = Logic_SystemDate::getStartDate();
        $show_date = strtotime($form['gen_search_inventory_date']);
        $locked = ($lock_date > $show_date);

        $form['gen_message_noEscape'] = _g("表示されている理論在庫数は、左側の表示条件内の「棚卸日」時点のものです。");

        if (isset($form['gen_readonly']) && $form['gen_readonly'] == "true") {
            $form['gen_message_noEscape'] .=
                "<br><br><font color='red'><b>" . _g("棚卸登録を行う権限がありません。") . "</b></font>";
        } else if ($form['gen_search_mode'] == "1") {
            $form['gen_readonly'] = "true";
        } else {
            $form['gen_message_noEscape'] .=
                ($locked ?
                    "<BR><font color='red'>" . _g("データがロックされているため、この日付での棚卸登録を行えません。") . "</font><BR>" :
                    "<BR>" . _g("登録ボタンを押すと、実在庫に入力した数が左側の表示条件内の<font color=\"red\">「棚卸日」時点の在庫として</font>登録されます。") .
                    "<BR>" . _g("表示されているページごとに「登録」ボタンを押してください。（再表示やページ移動をすると入力した内容がクリアされます） ") .
                    "<BR>" . _g("登録後、次のページがある場合は、次のページが表示されます。") .
                    "<BR><input type='button' class=\"gen-button\" id='entryButton' onClick='doEntry();' style='width:200px' value='" . _g("登録") . "'" . ($locked ? " disabled" : "") . ">"
                );
        }
        $query = "select max(inventory_date) from inventory";
        $form['gen_message_noEscape'] .= "<br><br><font color='blue'><b>" . _g("最終棚卸日") . _g("：") . h($gen_db->queryOneValue($query)) . "</b></font>";

        // 「エクセル出力(ロケ別)」機能（ロケ別に複数シートに出力）
        if (isset($form['locExcel'])) {
            $form['gen_excelTitleColumn'] = "location_name";
            $form['gen_excelSheetKeyColumn'] = "location_id";
        }

        $form['gen_fixColumnArray'] = array(
            array(
                'label' => _g('品目コード'),
                'field' => 'item_code',
                'width' => '120',
                'sameCellJoin' => true,
            ),
            array(
                'label' => _g('品目名'),
                'field' => 'item_name',
                'width' => '200',
                'sameCellJoin' => true,
                'parentColumn' => 'item_code',
            ),
        );

        $form['gen_columnArray'] = array(
            array(
                'label' => _g('棚番'),
                'field' => 'rack_no',
                'width' => '80',
                'align' => 'center',
                'sameCellJoin' => true,
                'parentColumn' => 'item_code',
                'hide' => true,
            ),
            array(
                'label' => _g('ロケーション'),
                'field' => 'location_name',
                'width' => '100',
            ),
            array(
                'label' => _g('製番'),
                'field' => 'seiban',
                'width' => '100',
                'align' => 'center',
            ),
            array(
                'label'=>_g('ロット番号'),
                'field'=>'lot_no',
                'width'=>'80',
                'align'=>'center',
                // ロット品目のみに限定している理由はSQL join部のコメントを参照。
                'helpText_noEscape'=>_g("実績/受入登録画面で登録（もしくは自動設定）した「ロット番号」が表示されます。") . "<br><br>"
                    . _g("品目マスタ「管理区分」が「ロット」の品目のみ表示されます。（「製番」「MRP」の品目は、実績/受入画面で登録していたとしても表示されません。）")
            ),
            array(
                'label' => _g('理論在庫'),
                'field' => 'logical_stock_quantity',
                'type' => 'numeric',
                'cellId' => 'logical_stock_quantity_[id]',
            ),
            array(
                'label' => _g('実在庫'),
                'width' => '80',
                'type' => (isset($form['gen_readonly']) && $form['gen_readonly'] == "true" ? 'numeric' : 'textbox'),
                'align' => (isset($form['gen_readonly']) && $form['gen_readonly'] == "true" ? 'right' : 'center'),
                'field' => 'inventory_quantity',
                'style' => 'text-align:right; background-color:#ffffcc',
                'onChange_noEscape' => "mod();onQtyChange('[id]')"
            ),
            array(
                'label' => _g('単位'),
                'field' => 'measure',
                'width' => '35',
            ),
            array(
                'label' => _g('差異数'),
                'field' => 'diff_quantity',
                'type' => 'numeric',
                'cellId' => 'diff_quantity_[id]',
            ),
            array(
                'label' => _g('在庫評価単価'),
                'field' => 'stock_price',
                'type' => 'numeric',
                'cellId' => 'stock_price_[id]',
                'helpText_noEscape' => _g("在庫リスト画面の「在庫評価単価」のチップヘルプを参照してください。"), 
            ),
            array(
                'label' => _g('棚卸金額'),
                'field' => 'stock_amount',
                'type' => 'numeric',
                'cellId' => 'stock_amount_[id]',
            ),
            array(
                'label' => _g('差異金額'),
                'field' => 'diff_amount',
                'type' => 'numeric',
                'cellId' => 'diff_amount_[id]',
            ),
            array(
                'label' => _g('棚卸備考'),
                'width' => '200',
                'type' => (isset($form['gen_readonly']) && $form['gen_readonly'] == "true" ? '' : 'textbox'),
                'field' => 'remarks',
                'style' => 'background-color:#ffffcc',
                'tabindex' => '-1',
                'onChange_noEscape' => "mod()"
            ),
            array(
                'label' => _g('メーカー'),
                'field' => 'maker_name',
                'hide' => true,
            ),
            array(
                'label' => _g('仕様'),
                'field' => 'spec',
                'hide' => true,
            ),
            array(
                'label' => _g('品目備考1'),
                'field' => 'comment',
                'hide' => true,
            ),
            array(
                'label' => _g('品目備考2'),
                'field' => 'comment_2',
                'hide' => true,
            ),
            array(
                'label' => _g('品目備考3'),
                'field' => 'comment_3',
                'hide' => true,
            ),
            array(
                'label' => _g('品目備考4'),
                'field' => 'comment_4',
                'hide' => true,
            ),
            array(
                'label' => _g('品目備考5'),
                'field' => 'comment_5',
                'hide' => true,
            ),
        );

        // Excel出力
        $form['gen_excelShowArray'] = array(array(1, 0, $form['gen_search_inventory_date'] . ""));

        // 「エクセル出力(ロケ別)」機能のときはOrder Byを強制的に指定しているので、カラムが必要
        if (isset($form['locExcel'])) {
            $form['gen_columnArray'][] = array(
                'label' => "",
                'width' => '0',
                'type' => 'numeric',
                'field' => 'location_id',
            );
        }
    }

}
