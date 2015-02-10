<?php

class Stock_Stocklist_List extends Base_ListBase
{

    var $locationArr;
    var $isLocationHolizonMode;

    function setSearchCondition(&$form)
    {
        global $gen_db;

        // 検索条件
        $query = "select item_group_id, item_group_name from item_group_master order by item_group_code";
        $option_item_group = $gen_db->getHtmlOptionArray($query, false, array(null => _g("(すべて)")));

        $query = "select location_id, location_name from location_master order by location_code";
        $option_location_group = $gen_db->getHtmlOptionArray($query, false, array(null => _g("(すべて)"), "0" => _g(GEN_DEFAULT_LOCATION_NAME)));
        $this->locationArr = $gen_db->getHtmlOptionArray($query, false, array("0" => _g(GEN_DEFAULT_LOCATION_NAME)));

        $form['gen_searchControlArray'] = array(
            array(
                'label' => _g('日付'),
                'type' => 'calendar',
                'field' => 'stock_date',
                'style' => 'background-color:#ffcccc',
                'nosql' => true,
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
                'nosql' => true,
                'hide' => true,
            ),
            array(
                'label' => _g('親品目'),
                'type' => 'select',
                'type' => 'dropdown',
                'field' => 'parent_item_id',
                'size' => '150',
                'dropdownCategory' => 'item',
                'rowSpan' => 2,
                'nosql' => true,
                'hide' => true,
            ),
            array(
                'label' => _g('ロケーション'),
                'type' => 'select',
                'field' => 'location_id',
                'options' => $option_location_group,
                'hide' => true,
            ),
            array(
                'label' => _g('ロケ別表示'),
                'type' => 'select',
                'field' => 'show_location',
                'options' => Gen_Option::getTrueOrFalse('search-holizon'),
                'nosql' => true,
                'default' => 'false',
            ),
            array(
                'label' => _g('管理区分'),
                'type' => 'select',
                'field' => 'order_class',
                'options' => Gen_Option::getOrderClass('search'),
            ),
            array(
                'label' => _g('製番'),
                'field' => 'seiban',
                'hide' => true,
            ),
            array(
                'label' => _g('製番別表示'),
                'type' => 'select',
                'field' => 'show_seiban',
                'options' => Gen_Option::getTrueOrFalse('search'),
                'nosql' => true,
                'default' => 'false',
            ),
            array(
                'label' => _g('ロット別表示'),
                'type' => 'select',
                'field' => 'show_lot',
                'options' => Gen_Option::getTrueOrFalse('search'),
                'nosql' => true,
                'default' => 'false',
            ),
            array(
                'label' => _g('サプライヤー在庫'),
                'type' => 'select',
                'field' => 'include_partner_stock',
                'options' => Gen_Option::getTrueOrFalse('search-include'),
                'nosql' => true,
                'default' => 'true',
                'hide' => true,
            ),
            array(
                'label' => _g('理論在庫数'),
                'type' => 'numFromTo',
                'field' => 'logical_stock_quantity',
                'rowSpan' => 2,
            ),
            array(
                'label' => _g('在庫の状態'),
                'type' => 'select',
                'field' => 'stock_status',
                'options' => array('all' => _g('(すべて)'), 'logical_alarm' => _g('理論在庫数 < 安全在庫数'), 'available_alarm' => _g('有効在庫数 < 安全在庫数')),
                'nosql' => true,
                'default' => 'true',
                'hide' => true,
            ),
            array(
                'label' => _g('受注残の状態'),
                'type' => 'select',
                'field' => 'received_remained_status',
                'options' => array('all' => _g('(すべて)'), 'exist' => _g('受注残あり'), 'zero' => _g('受注残なし')),
                'nosql' => true,
                'default' => 'all',
                'hide' => true,
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
                'label' => _g('棚番'),
                'field' => 'rack_no',
                'hide' => true,
            ),
            array(
                'label'=>_g('ロット番号'),
                'field'=>'lot_no',
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

        // プリセット表示条件パターン
        $form['gen_savedSearchConditionPreset'] =
            array(
                _g("消費期限別理論在庫数") => self::_getPreset("true", "use_by_day", "item_name"),
            );
    }

    function _getPreset($showLot, $horiz, $vert, $orderby = "", $value = "logical_stock_quantity", $method = "sum")
    {
        return
            array(
                "data" => array(
                    array("f" => "show_lot", "v" => $showLot),

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
        global $gen_db;

        // 製番/ロットが指定されたときは、強制的に製番別表示モードにする。ロケ・ロットも同じ。「・・別表示しない」モードだとレコードが表示されないため
        if (isset($form['gen_search_seiban']) && $form['gen_search_seiban'] != "") {
            $form['gen_search_show_seiban'] = 'true';
        }
        if (isset($form['gen_search_lot_no']) && $form['gen_search_lot_no'] != "") {
            $form['gen_search_show_lot_no'] = 'true';
        }
        if (isset($form['gen_search_location_id']) && is_numeric($form['gen_search_location_id'])) {
            $form['gen_search_show_location'] = 'true';
        }

        $stockDate = @$form['gen_search_stock_date'];
        // 指定されていなければずっと先まで。（2038年以降は日付と認識されない）
        if (!Gen_String::isDateString($stockDate))
            $stockDate = "2037-12-31";
        $isShowSeiban = (isset($form['gen_search_show_seiban']) && $form['gen_search_show_seiban'] == 'true');
        $isShowLot = (isset($form['gen_search_show_lot']) && $form['gen_search_show_lot'] == 'true');
        $isShowLocation = (isset($form['gen_search_show_location']) && ($form['gen_search_show_location'] == 'true' || @$form['gen_search_show_location'] == 'holizon'));
        $isIncludePartnerStock = (isset($form['gen_search_include_partner_stock']) && $form['gen_search_include_partner_stock'] == 'true');

        $this->isLocationHolizonMode = (isset($form['gen_search_show_location']) && $form['gen_search_show_location'] == 'holizon');

        // temp_stockテーブルにデータを取得
        Logic_Stock::createTempStockTable(
            $stockDate
            , null
            , ($isShowSeiban || $isShowLot ? null : "sum")
            , ($isShowLocation ? null : "sum")
            , "sum"
            , true                          // 有効在庫も取得
            , ($isIncludePartnerStock)      // サプライヤー在庫を含めるかどうか
            // use_plan の全期間分差し引くかどうか。差し引くならtrue。指定日分までの引当・予約しか含めないならfalse。
            //  これをtrueにするかfalseにするかは難しい。有効在庫の値をFlowおよびHistoryとあわせるにはfalseに
            //  する必要があるが、受注管理画面「引当可能数」と合わせるにはtrueにする必要がある。
            //  ここをtrueに変えるなら、受注管理Editの引当可能数のhelpText_noEscapeも変更すること
            , false
        );

        // 親品目が指定されている場合はtemp_bom_expandテーブルを準備
        if (isset($form['gen_search_parent_item_id']) && is_numeric($form['gen_search_parent_item_id'])) {
            Logic_Bom::expandBom($form['gen_search_parent_item_id'], 0, false, false, false);
        }

        // SQLの組み立て
        if ($this->isLocationHolizonMode) {
            // ロケを横軸に表示するモード
            $selectStr = "
                select
                    temp_stock.item_id,
                    max(item_code) as item_code,
                    max(item_name) as item_name,
                    max(measure) as measure,
                    max(maker_name) as maker_name,
                    max(spec) as spec,
                    max(rack_no) as rack_no,
                    max(comment) as comment,
                    max(comment_2) as comment_2,
                    max(comment_3) as comment_3,
                    max(comment_4) as comment_4,
                    max(comment_5) as comment_5,
                    max(safety_stock) as safety_stock,
            	    sum(temp_stock.logical_stock_quantity) as location_sum
                    " . ($isShowSeiban ? ",seiban" : "") . "
                    " . ($isShowLot ? ",max(use_by) as use_by,lot_no" : "") . "
                    ";
                    foreach ($this->locationArr as $locId => $locName) {
                        $selectStr .= ",SUM(case when temp_stock.location_id = '{$locId}' then temp_stock.logical_stock_quantity end) as location_{$locId}";
                    }
            $footStr = "
                group by
                    temp_stock.item_id
                    " . ($isShowSeiban ? ",seiban" : "") . "
                    " . ($isShowLot ? ",lot_no" : "") . "
                order by
                    item_code
            ";
        } else {
            // 通常モード
            $aggMax = ($isShowLot ? "max" : "");
            $aggSum = ($isShowLot ? "sum" : "");
            $selectStr = "
                select
                    temp_stock.item_id,
                    {$aggMax}(temp_stock.seiban) as seiban,
                    {$aggSum}(temp_stock.logical_stock_quantity) as logical_stock_quantity,
                    {$aggSum}(temp_stock.use_plan_quantity) as use_plan_quantity,
                    {$aggSum}(temp_stock.received_remained_quantity) as received_remained_quantity,
                    {$aggSum}(temp_stock.available_stock_quantity) as available_stock_quantity,
                    {$aggSum}(temp_stock.last_inventory_quantity) as last_inventory_quantity,
                    {$aggMax}(temp_stock.last_inventory_date) as last_inventory_date,
                    {$aggSum}(temp_stock.in_quantity) as in_quantity,
                    {$aggSum}(temp_stock.out_quantity) as out_quantity,
                    {$aggSum}(temp_stock.payout_quantity) as payout_quantity,
                    {$aggSum}(temp_stock.use_quantity) as use_quantity,
                    {$aggSum}(temp_stock.manufacturing_quantity) as manufacturing_quantity,
                    {$aggSum}(temp_stock.accepted_quantity) as accepted_quantity,
                    {$aggSum}(temp_stock.delivery_quantity) as delivery_quantity,
                    {$aggSum}(temp_stock.move_in_quantity) as move_in_quantity,
                    {$aggSum}(temp_stock.move_out_quantity) as move_out_quantity,
                    {$aggSum}(temp_stock.seiban_change_in_quantity) as seiban_change_in_quantity,
                    {$aggSum}(temp_stock.seiban_change_out_quantity) as seiban_change_out_quantity,
                    /* オーダー残と未オーダー計画は合計してひとつの欄に表示 */
                    {$aggSum}(coalesce(order_remained_quantity,0) + coalesce(plan_remained_quantity,0)) as in_remained_quantity,
                    {$aggMax}(item_code) as item_code,
                    {$aggMax}(item_name) as item_name,
                    {$aggMax}(t_item_group_1.item_group_code) as item_group_code_1,
                    {$aggMax}(t_item_group_1.item_group_name) as item_group_name_1,
                    {$aggMax}(t_item_group_2.item_group_code) as item_group_code_2,
                    {$aggMax}(t_item_group_2.item_group_name) as item_group_name_2,
                    {$aggMax}(t_item_group_3.item_group_code) as item_group_code_3,
                    {$aggMax}(t_item_group_3.item_group_name) as item_group_name_3,
                    {$aggMax}(measure) as measure,
                    {$aggMax}(maker_name) as maker_name,
                    {$aggMax}(spec) as spec,
                    {$aggMax}(rack_no) as rack_no,
                    {$aggMax}(comment) as comment,
                    {$aggMax}(comment_2) as comment_2,
                    {$aggMax}(comment_3) as comment_3,
                    {$aggMax}(comment_4) as comment_4,
                    {$aggMax}(comment_5) as comment_5,
           	    {$aggMax}(safety_stock) as safety_stock,
                    {$aggMax}(temp_stock_price.stock_price) as stock_price,
                    {$aggSum}(logical_stock_quantity * temp_stock_price.stock_price) as stock_amount,
                    {$aggMax}(last_in_date) as last_in_date,
                    {$aggMax}(last_out_date) as last_out_date
                    " . ($isShowSeiban ? ",{$aggMax}(case when seiban='' then 'nothing' else seiban end) as seiban_forlink" : "") . "
                    /* ロケが非表示でもカラムはつくっておく。ソート対象に指定されていた場合やクイック検索のエラーを回避するため */
                    " . ($isShowLocation ? ",{$aggMax}(temp_stock.location_id) as location_id,{$aggMax}(case when temp_stock.location_id=0 then '" . _g(GEN_DEFAULT_LOCATION_NAME) . "' else location_name end) as location_name"
                            : ",null as location_id, cast(null as text) as location_name") . "
                    " . ($isShowLot ? ",{$aggMax}(use_by) as use_by, lot_no " : ",null as use_by, null as lot_no") . "
            ";
            $footStr =
                    ($isShowLot ? " group by temp_stock.item_id," . ($isShowSeiban ? "seiban," : "") . "lot_no" : "") .
                    " [Orderby]";

            // 指定日時点の在庫評価単価をテンポラリテーブル（temp_stock_price）に取得
            Logic_Stock::createTempStockPriceTable($stockDate);

            // 下記をテンポラリテーブル化することで劇的に速度が上がる場合がある
            $query = "
                create temp table temp_item_in_out as
                select
                    item_id
                    ,max(case when classification in ('in','manufacturing') then item_in_out_date end) as last_in_date
                    ,max(case when classification in ('out','payout','use','delivery') then item_in_out_date end) as last_out_date
                from
                    item_in_out
                group by item_id;
                create index temp_item_in_out_index1 on temp_item_in_out (item_id);
            ";
            $gen_db->query($query);
        }

        $this->selectQuery =
                $selectStr .
                " from " .
                "   temp_stock " .
                "   left join item_master on temp_stock.item_id = item_master.item_id " .
                ($this->isLocationHolizonMode ? "" : " left join temp_stock_price on temp_stock.item_id = temp_stock_price.item_id") .
                ($this->isLocationHolizonMode ? "" : " left join temp_item_in_out on temp_stock.item_id = temp_item_in_out.item_id") .
                "   left join item_group_master as t_item_group_1 on item_master.item_group_id = t_item_group_1.item_group_id " .
                "   left join item_group_master as t_item_group_2 on item_master.item_group_id_2 = t_item_group_2.item_group_id " .
                "   left join item_group_master as t_item_group_3 on item_master.item_group_id_3 = t_item_group_3.item_group_id " .
                ($isShowLocation ? " left join (select location_id as locId, location_name from location_master) as t_loc on temp_stock.location_id = t_loc.locId" : "") .
                (is_numeric(@$form['gen_search_parent_item_id']) ?
                        " inner join (select item_id as exp_item_id from temp_bom_expand group by item_id) as t_exp on temp_stock.item_id = t_exp.exp_item_id " : "") .
                // ロット番号/消費期限が表示されるのはロット品目のみとする。
                // この制限がないと、製番品目の受入/実績でロット番号/消費期限を登録した場合に、同じ製番のオーダーすべてに同じロット番号/消費期限が
                // 表示されてしまうことになる。
                 ($isShowSeiban || $isShowLot ? " LEFT JOIN (select stock_seiban, use_by, lot_no from achievement where stock_seiban <> ''
                     union select stock_seiban, use_by, lot_no from accepted where stock_seiban <> ''
                     ) as t_ach_acc on temp_stock.seiban = t_ach_acc.stock_seiban and temp_stock.seiban <>'' and item_master.order_class = 2" : "") .
                // 非表示品目・ダミー品目は表示しない
                " [Where] and not coalesce(item_master.end_item, false)" .
                " and not coalesce(item_master.dummy_item, false)" .
                (isset($form['gen_search_item_group_id']) && is_numeric($form['gen_search_item_group_id']) ?
                        " and (item_master.item_group_id = '{$form['gen_search_item_group_id']}' " .
                           "or item_master.item_group_id_2 = '{$form['gen_search_item_group_id']}' " .
                           "or item_master.item_group_id_3 = '{$form['gen_search_item_group_id']}')" : "") .
                (@$form['gen_search_stock_status'] == 'logical_alarm' ?
                        " and logical_stock_quantity < safety_stock" : "") .
                (@$form['gen_search_stock_status'] == 'available_alarm' ?
                        " and available_stock_quantity < safety_stock" : "") .
                (@$form['gen_search_received_remained_status'] == 'exist' ?
                        " and received_remained_quantity > 0" : "") .
                (@$form['gen_search_received_remained_status'] == 'zero' ?
                        " and coalesce(received_remained_quantity,0) = 0" : "") .
                $footStr;

        // orderbyDefault にはseibanとlocation_nameも入れたいところだが、そうするとitem_masterとstockにindexが
        // 使用されなくなり、品目が多いときに極端に遅くなる（数十倍）。
        $this->orderbyDefault = 'item_code' . ($isShowSeiban ? ',seiban' : '') . ($isShowLocation && !$this->isLocationHolizonMode ? ',location_id' : '');
        $this->pageRecordCount = 20;
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
        $form['gen_pageTitle'] = _g("在庫リスト");
        $form['gen_listAction'] = "Stock_Stocklist_List";
        $form['gen_idField'] = "dummy";
        $form['gen_excel'] = "true";
        $form['gen_pageHelp'] = _g("在庫リスト");

        $form['gen_goLinkArray'] = array(
            array(
                'icon' => 'img/arrow.png',
                'onClick' => 'index.php?action=Stock_Assessment_List',
                'value' => _g('在庫評価単価の更新'),
            ),
            array(
                'icon' => 'img/arrow.png',
                'onClick' => 'index.php?action=Stock_Stocklist_Expand',
                'value' => _g('分解在庫リスト'),
            ),
            array(
                'icon' => 'img/arrow.png',
                'onClick' => 'index.php?action=Stock_StockProcess_List',
                'value' => _g('工程仕掛リスト'),
            ),
        );

        $form['gen_javascript_noEscape'] = "
            function goDetail(itemId, seiban, locationId, lotNo) {
                window.open('index.php?action=Stock_StockHistory_List&gen_search_temp_inout___item_id=' + itemId
                + '&gen_search_seiban=' + ($('#gen_search_show_seiban').val()=='true' ? seiban : '')
                + '&gen_search_temp_inout___location_id=' + ($('#gen_search_show_location').val()=='true' ? locationId : '')
                + '&gen_search_lot_no=' + ($('#gen_search_show_lot').val()=='true' ? lotNo : '')
                + '&gen_search_date_to=' + $('#gen_search_stock_date').val()
                ,'progress');
            }

            function showItemMaster(itemId) {
                gen.modal.open('index.php?action=Master_Item_Edit&item_id=' + itemId);
            }
        ";

        $form['gen_message_noEscape'] = _g("※表示条件の「日付」時点の在庫が表示されます。日付を空欄にすると、現在庫（最終入出庫時点の在庫）が表示されます。");
        if ($this->isLocationHolizonMode) {
            $form['gen_message_noEscape'] .= "<BR>" . _g("※理論在庫数が表示されています。");
        } else {
            $form['gen_message_noEscape'] .= "<BR>" . _g("※「入庫」「出庫」等は、最終棚卸日以降の入出庫数が表示されています。");
        }

        // ロケ別表示のときは、入庫予定・出庫予定・有効在庫を表示しない
        // 受注や引当にロケの概念がないため
        $isShowInoutPlan = (!isset($form['gen_search_show_location']) || $form['gen_search_show_location'] != 'true');
        if (!$isShowInoutPlan) {
            $form['gen_message_noEscape'] .= "<br>" . _g("※ロケ別表示「する」の時は、入庫予定・出庫予定・有効在庫は表示されません。");
        }

        // 1行おきの色付けを無効にする
        $form['gen_alterColorDisable'] = "true";

        //  モードにより動的に列を切り替える場合、モードごとに列情報（列順、列幅、ソートなど）を別々に保持できるよう、次の設定が必要。
        $form['gen_columnMode'] = ($this->isLocationHolizonMode ? "horizon" : "normal");

        $form['gen_fixColumnArray'] = array(
            array(
                'label' => _g('品目コード'),
                'width' => '130',
                'field' => 'item_code',
                'sameCellJoin' => true,
            ),
            array(
                'label' => _g('品目名'),
                'field' => 'item_name',
                'sameCellJoin' => true,
                'parentColumn' => 'item_code',
            ),
            array(
                'label' => _g('製番'),
                'field' => 'seiban',
                'width' => '100',
                'align' => 'center',
                'visible' => (isset($form['gen_search_show_seiban']) && $form['gen_search_show_seiban'] == 'true'),
            ),
            array(
                'label'=>_g('ロット番号'),
                'field'=>'lot_no',
                'width'=>'80',
                'align'=>'center',
                'visible'=>(isset($form['gen_search_show_lot']) && $form['gen_search_show_lot'] == 'true'),
                // ロット品目のみに限定している理由はSQL join部のコメントを参照。
                'helpText_noEscape'=>_g("実績/受入登録画面で登録（もしくは自動設定）した「ロット番号」が表示されます。") . "<br><br>"
                    . _g("品目マスタ「管理区分」が「ロット」の品目のみ表示されます。（「製番」「MRP」の品目は、実績/受入画面で登録していたとしても表示されません。）") . "<br><br>"
                    . _g("この列は、表示条件「製番/ロット別表示」を有効にしたときのみ表示されます。")
            ),
            array(
                'label'=>_g('消費期限'),
                'field'=>'use_by',
                'width'=>'80',
                'type'=>'date',
                'align'=>'center',
                'visible'=>(isset($form['gen_search_show_lot']) && $form['gen_search_show_lot'] == 'true'),
                // ロット品目のみに限定している理由はSQL join部のコメントを参照。
                'helpText_noEscape'=>_g("実績/受入登録画面で登録（もしくは自動設定）した「消費期限」が表示されます。") . "<br><br>"
                    . _g("品目マスタ「管理区分」が「ロット」の品目のみ表示されます。（「製番」「MRP」の品目は、実績/受入画面で登録していたとしても表示されません。）") . "<br><br>"
                    . _g("この列は、表示条件「製番/ロット別表示」を有効にしたときのみ表示されます。")
            ),
            array(
                'label' => _g('ロケーション'),
                'width' => '90',
                'field' => 'location_name',
                'visible' => (isset($form['gen_search_show_location']) && $form['gen_search_show_location'] == 'true'),
            ),
            array(
                'label' => _g('受払'),
                'width' => '35',
                'type' => 'literal',
                'literal_noEscape' => _g('受払'),
                'align' => 'center',
                'link' => "javascript:goDetail('[item_id]','[urlencode:seiban]','[location_id]','[lot_no]')",
            ),
            array(
                'label' => _g('理論在庫数'),
                'field' => 'logical_stock_quantity',
                'width' => '70',
                'type' => 'numeric',
                'colorCondition' => array("#facea6" => "true"), // 色付け条件。常にtrueになるようにしている
                'visible' => !$this->isLocationHolizonMode,
                'helpText_noEscape' => _g("指定日時点の在庫数です。") . "<br><br>" . _g("「前回棚卸数」＋「入庫」－「出庫」－「支給」－「使用」＋「製造」－「納品」＋「移動入庫」－「移動出庫」＋「製番引当入庫」－「製番引当出庫」で計算されます。") . "<br><br>"
            ),
            array(
                'label' => _g('単位'),
                'field' => 'measure',
                'type' => 'data',
                'width' => '35',
                'helpText_noEscape' => _g("品目マスタの「管理単位」です。")
            ),
        );

        // ロケーションを横軸に表示するモード
        if ($this->isLocationHolizonMode) {
            $form['gen_columnArray'][] = array(
                'label' => "(" . _g("合計") . ")",
                'field' => "location_sum",
                'type' => 'numeric',
                'colorCondition' => array("#facea6" => "true"), // 色付け条件。常にtrueになるようにしている
            );
            $form['gen_columnArray'][] = array(
                'label' => _g('安全在庫数'),
                'field' => 'safety_stock',
                'width' => '70',
                'type' => 'numeric',
                'helpText_noEscape' => _g("品目マスタの「安全在庫数」です。"),
            );

            foreach ($this->locationArr as $locId => $locName) {
                $form['gen_columnArray'][] = array(
                    'label' => $locName,
                    'field' => "location_$locId",
                    'type' => 'numeric',
                );
            }
            return;
        }

        $form['gen_columnArray'] = array(
            array(
                'label' => _g('在庫評価単価'),
                'field' => 'stock_price',
                'width' => '70',
                'type' => 'numeric',
                'colorCondition' => array("#b6eae4" => "true"), // 色付け条件。常にtrueになるようにしている
                'helpText_noEscape' => _g("次のいずれか場合は、品目マスタの「在庫評価単価」が表示されます。") . "<br>"
                . _g("・その品目の在庫評価単価更新（画面左上の「在庫評価単価の更新」）が一度も行われていない") . "<br>"
                . _g("・指定日付がその品目の最初の在庫評価単価更新日より前である") . "<br>"
                . _g("・指定日付がその品目の最後の在庫評価単価更新日より後である") . "<br><br>"
                . _g("上記以外の場合は、在庫評価単価更新の履歴データを参照し、指定日付時点の在庫評価単価が表示されます。"),
            ),
            array(
                'label' => _g('金額'),
                'field' => 'stock_amount',
                'type' => 'numeric',
                'colorCondition' => array("#b6eae4" => "true"), // 色付け条件。常にtrueになるようにしている
                'helpText_noEscape' => _g("「理論在庫数」×「評価単価」です。"),
            ),
            array(
                'label' => _g('入庫予定数'),
                // ロケ・ロット指定されているときは表示しない
                'field' => ($isShowInoutPlan ? "in_remained_quantity" : ""),
                'type' => 'numeric',
                'colorCondition' => array("#d7d7d7" => ($isShowInoutPlan ? "false" : "true")),
                'helpText_noEscape' => _g("入庫する予定であるものの、まだ入庫が登録されていない数量。") . "<br>" . _g("具体的には、以下を合計した数量です。") . "<br><br>"
                . "●<b>" . _g("注文残") . "</b><br>" . _g("この品目の注文書もしくは外製指示書のうち、まだ受入登録していない分の数量") . "<br><br>"
                . "●<b>" . _g("製造残") . "</b><br>" . _g("この品目の製造指示書のうち、まだ実績登録していない分の数量") . "<br><br>"
                . "●<b>" . _g("計画残") . "</b><br>" . _g("この品目の計画登録のうち、まだ発注・製造指示されていない分の数量") . "<br><br>"
                . _g("※注文残・製造残について：") . "<br>" . _g("表示条件の「日付」が指定されている場合、その日付の時点でまだ納期がきていない注文・製造指示については、注文残・製造残に含まれません。入庫予定は「その日の時点までに受入・実績が発生するはずだが、まだ登録されていないもの」をあらわしているためです。") . "<br><br>"
                . _g("※計画残について：") . "<br>" . _g("計画日が本日もしくはそれ以前の計画は含まれません。（計画日が明日以降の未オーダー計画だけが含まれます。）計画日が本日以前の計画は、所要量計算において無視されるためです。") . "<br><br>"
                . _g("※製番/ロット別表示の場合の注意点：") . "<br>" . _g("計画残は常に製番「なし」の欄に含まれます。計画ベースの注文・製造指示は、受入・実績登録時に製番「なし」になるためです。") . "<br><br>"
                . _g("なお、表示条件の「ロケーション」に「すべて」以外を指定したり、「ロケーション別表示」に「する」を指定した場合、この欄に数字は表示されません。ロケーション別の入庫予定数は計算できないためです。")
            ,
            ),
            array(
                'label' => _g('出庫予定数'),
                // ロケ・ロット指定されているときは表示しない
                'field' => ($isShowInoutPlan ? "use_plan_quantity" : ""),
                'type' => 'numeric',
                'colorCondition' => array("#d7d7d7" => ($isShowInoutPlan ? "false" : "true")),
                'helpText_noEscape' => _g("出庫する予定であるものの、まだ出庫が登録されていない数量。") . "<br>" . _g("具体的には、以下を合計した数量です。") . "<br><br>"
                . "●<b>" . _g("受注引当") . "</b><br>" . _g("受注登録画面の「在庫引当数」。ただし納品済の分は含まない") . "<br><br>"
                . "●<b>" . _g("使用予約") . "</b><br>" . _g("親品目の製造に使用するために確保されている数量。具体的には、親品目の製造指示書が登録される際（親品目がダミー品目の場合は受注登録の際）に、子品目の使用予定数（親品目の製造数×構成表マスタの員数）が使用予定となる。ただし実績登録済（親品目がダミー品目の場合は納品登録済）の分は含まれない"). "<br><br>"
                . _g("※使用予約について：") . "<br>" . _g("親品目の製造指示（親品目がダミー品目の場合は受注登録）のうち、実績（親品目がダミー品目の場合は納品）が未登録のものだけが算入されることに注意してください。実績が登録されると、その時点で「出庫予定」から「使用」の欄へ数字が移ります。") . "<br><br>"
                . _g("※製番/ロット別表示の場合の注意点：") . "<br>" . _g("MRP品目は製番「なし」の欄に表示されます。製番/ロット品目は、受注引当分は製番/ロット「なし」、使用予約分は親品目の製番/ロットの欄に表示されます。") . "<br><br>"
                . _g("なお、表示条件の「ロケーション」に「すべて」以外を指定したり、「ロケーション別表示」に「する」を指定した場合、この欄に数字は表示されません。ロケーション別の出庫予定数は計算できないためです。")
            ,
            ),
            array(
                'label' => _g('受注残'),
                // ロケ・ロット指定されているときは表示しない
                'field' => ($isShowInoutPlan ? "received_remained_quantity" : ""),
                'type' => 'numeric',
                'colorCondition' => array("#d7d7d7" => ($isShowInoutPlan ? "false" : "true")),
                'helpText_noEscape' => _g("受注のうち、未納品かつ未引当の分、つまり納品予定の分です。ちなみに引当済の分は「出庫予定数」、納品済の分は「納品」欄に表示されます。") . "<br><br>"
                . _g("なお、表示条件の「ロケーション」に「すべて」以外を指定したり、「ロケーション別表示」に「する」を指定した場合、この欄に数字は表示されません。ロケーション別の出庫在庫は計算できないためです。")
            ,
            ),
            array(
                'label' => _g('有効在庫数'),
                // ロケ・ロット指定されているときは表示しない
                'field' => ($isShowInoutPlan ? "available_stock_quantity" : ""),
                'type' => 'numeric',
                'colorCondition' => array("#d7d7d7" => ($isShowInoutPlan ? "false" : "true"), "#fae0a6" => "true"), // 色付け条件。常にtrueになるようにしている
                'helpText_noEscape' => _g("「理論在庫数」＋「入庫予定数」－「出庫予定数」－「受注残」です。") . "<br><br>"
                . _g("なお、表示条件の「ロケーション」に「すべて」以外を指定したり、「ロケーション別表示」に「する」を指定した場合、この欄に数字は表示されません。ロケーション別の有効在庫は計算できないためです。")
            ),
            array(
                'label' => _g('安全在庫数'),
                'field' => 'safety_stock',
                'width' => '70',
                'type' => 'numeric',
                'helpText_noEscape' => _g("品目マスタの「安全在庫数」です。"),
            ),
            // 以下、デフォルトhide
            array(
                'label' => _g('前回棚卸数'),
                'field' => 'last_inventory_quantity',
                'type' => 'numeric',
                'colorCondition' => array("#99ffcc" => "true"), // 色付け条件。常にtrueになるようにしている
                'helpText_noEscape' => _g("[資材管理]-[棚卸]画面で登録された、直近の棚卸数です。") . "<br><br>"
                . _g("なお、表示条件の「日付」が指定されている場合、その日付以前の棚卸が対象となります。"),
                'hide' => true,
            ),
            array(
                'label' => _g('前回棚卸日'),
                'field' => 'last_inventory_date',
                'type' => 'date',
                'helpText_noEscape' => _g("[資材管理]-[棚卸]画面で登録された、直近の棚卸の日付です。") . "<br><br>"
                . _g("なお、表示条件の「日付」が指定されている場合、その日付以前の棚卸が対象となります。"),
                'hide' => true,
            ),
            array(
                'label' => _g('入庫'),
                'field' => 'in_quantity',
                'type' => 'numeric',
                'helpText_noEscape' => _g("[資材管理]-[入庫登録]画面で登録された数量です。")
                . _g("外製登録時の子品目のサプライヤーロケーションへの支給入庫数も含みます。") . "<br><br>"
                . _g("前回棚卸日の翌日から、表示条件の「日付」の日（指定している場合）までの登録が対象となります。"),
                'hide' => true,
            ),
            array(
                'label' => _g('出庫'),
                'field' => 'out_quantity',
                'type' => 'numeric',
                'helpText_noEscape' => _g("[資材管理]-[出庫登録]画面で登録された数量です。") . "<br><br>"
                . _g("前回棚卸日の翌日から、表示条件の「日付」の日（指定している場合）までの登録が対象となります。"),
                'hide' => true,
            ),
            array(
                'label' => _g('支給'),
                'field' => 'payout_quantity',
                'type' => 'numeric',
                'helpText_noEscape' => _g("[購買管理]-[支給登録]画面で登録された数量です。[購買管理]-[外製指示登録]の登録の際に自動支給された分も含みます。") . "<br><br>"
                . _g("前回棚卸日の翌日から、表示条件の「日付」の日（指定している場合）までの登録が対象となります。"),
                'hide' => true,
            ),
            array(
                'label' => _g('使用'),
                'field' => 'use_quantity',
                'type' => 'numeric',
                'helpText_noEscape' => _g("親品目の製造の際に使用された数量です。[生産管理]-[実績登録]で登録された実績に対して、製造数×構成表マスタの員数 で計算されます。") . "<br>"
                . _g("詳細は[資材管理]-[使用数リスト]で確認することができます。") . "<br><br>"
                . _g("前回棚卸日の翌日から、表示条件の「日付」の日（指定している場合）までの登録が対象となります。"),
                'hide' => true,
            ),
            array(
                'label' => _g('製造'),
                'field' => 'manufacturing_quantity',
                'type' => 'numeric',
                'helpText_noEscape' => _g("[生産管理]-[実績登録]で登録された製造実績数です。") . "<br><br>"
                . _g("前回棚卸日の翌日から、表示条件の「日付」の日（指定している場合）までの登録が対象となります。"),
                'hide' => true,
            ),
            array(
                'label' => _g('受入'),
                'field' => 'accepted_quantity',
                'type' => 'numeric',
                'helpText_noEscape' => _g("[購買管理]-[受入登録]や[外製受入登録]で受入登録された数量です。")
                . _g("前回棚卸日の翌日から、表示条件の「日付」の日（指定している場合）までの登録が対象となります。"),
                'hide' => true,
            ),
            array(
                'label' => _g('納品'),
                'field' => 'delivery_quantity',
                'type' => 'numeric',
                'helpText_noEscape' => _g("[販売管理]-[納品登録]で登録された納品数です。") . "<br><br>"
                . _g("前回棚卸日の翌日から、表示条件の「日付」の日（指定している場合）までの登録が対象となります。"),
                'hide' => true,
            ),
            array(
                'label' => _g('移動入庫'),
                'field' => 'move_in_quantity',
                'type' => 'numeric',
                'helpText_noEscape' => _g("[資材管理]-[ロケーション間移動登録]で登録された移動入庫数です。") . "<br><br>"
                . _g("前回棚卸日の翌日から、表示条件の「日付」の日（指定している場合）までの登録が対象となります。"),
                'hide' => true,
            ),
            array(
                'label' => _g('移動出庫'),
                'field' => 'move_out_quantity',
                'type' => 'numeric',
                'helpText_noEscape' => _g("[資材管理]-[ロケーション間移動登録]で登録された移動出庫数です。") . "<br><br>"
                . _g("前回棚卸日の翌日から、表示条件の「日付」の日（指定している場合）までの登録が対象となります。"),
                'hide' => true,
            ),
            array(
                'label' => _g('製番引当入庫'),
                'field' => 'seiban_change_in_quantity',
                'type' => 'numeric',
                'helpText_noEscape' => _g("[資材管理]-[製番引当登録]で登録された製番引当による入庫数です。") . "<br><br>"
                . _g("前回棚卸日の翌日から、表示条件の「日付」の日（指定している場合）までの登録が対象となります。"),
                'hide' => true,
            ),
            array(
                'label' => _g('製番引当出庫'),
                'field' => 'seiban_change_out_quantity',
                'type' => 'numeric',
                'helpText_noEscape' => _g("[資材管理]-[製番引当登録]で登録された製番引当による出庫数です。") . "<br><br>"
                . _g("前回棚卸日の翌日から、表示条件の「日付」の日（指定している場合）までの登録が対象となります。"),
                'hide' => true,
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
                'label' => _g('棚番'),
                'field' => 'rack_no',
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
            array(
                'label'=>_g('最終入庫日'),
                'field'=>'last_in_date',
                'width'=>'100',
                'type'=>'date',
                'align'=>'center',
                'hide'=>true,
            ),
            array(
                'label'=>_g('最終出庫日'),
                'field'=>'last_out_date',
                'width'=>'100',
                'type'=>'date',
                'align'=>'center',
                'hide'=>true,
            ),
            array(
                'label' => _g('品目マスタ'),
                'width' => '40',
                'type' => 'literal',
                'literal_noEscape' => "<img src='img/application-form.png' class='gen_cell_img'>",
                'align' => 'center',
                'link' => "javascript:showItemMaster('[item_id]')",
                'hide' => true,
            ),
        );
    }

}