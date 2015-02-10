<?php

class Mobile_Stock_List extends Base_MobileListBase
{
    var $locationArr;

    function setSearchCondition(&$form)
    {
        global $gen_db;

        // 検索条件
        $query = "select item_group_id, item_group_name from item_group_master order by item_group_code";
        $option_item_group = $gen_db->getHtmlOptionArray($query, false, array(null=>_g("(すべて)")));

        $query = "select location_id, location_name from location_master order by location_code";
        $option_location_group = $gen_db->getHtmlOptionArray($query, false, array(null=>_g("(すべて)"), "0"=>GEN_DEFAULT_LOCATION_NAME));
        $this->locationArr = $gen_db->getHtmlOptionArray($query, false, array("0"=>GEN_DEFAULT_LOCATION_NAME));

        $form['gen_searchControlArray'] =
            array(
                array(
                    'label'=>_g('品目'),
                    'type'=>'textbox',
                    'field'=>'item_code',
                    'field2'=>'item_name',
                ),
                array(
                    'label'=>_g('品目グループ'),
                    'type'=>'select',
                    'field'=>'item_group_id',
                    'options'=>$option_item_group,
                ),
                array(
                    'label'=>_g('ロケーション'),
                    'type'=>'select',
                    'field'=>'location_id',
                    'options'=>$option_location_group,
                ),
                array(
                    'label'=>_g('日付'),
                    'type'=>'calendar',
                    'field'=>'stock_date',
                    'nosql'=>true,
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
        // 製番が指定されたときは、強制的に製番別表示モードにする。ロケ・ロットも同じ。「・・別表示しない」モードだとレコードが表示されないため
        if (@$form['gen_search_seiban']!="") $form['gen_search_show_seiban'] = 'true';
        if (is_numeric(@$form['gen_search_location_id'])) $form['gen_search_show_location'] = 'true';

        $stockDate = @$form['gen_search_stock_date'];
        if (!Gen_String::isDateString($stockDate)) $stockDate = "2999-12-31";   // 指定されていなければずっと先まで
        $isShowSeiban = (@$form['gen_search_show_seiban'] == 'true');
        $isShowLocation = (@$form['gen_search_show_location'] == 'true');
        $isIncludePartnerStock = (@$form['gen_search_include_partner_stock'] == 'true');

        // temp_stockテーブルにデータを取得
        Logic_Stock::createTempStockTable(
            $stockDate,
            null,
            ($isShowSeiban ? null : "sum"),
            ($isShowLocation ? null : "sum"),
            "sum",  // showLot
            true,                           // 有効在庫も取得
            ($isIncludePartnerStock ),      // サプライヤー在庫を含めるかどうか
            // use_plan の全期間分差し引くかどうか。差し引くならtrue。指定日分までの引当・予約しか含めないならfalse。
            //  これをtrueにするかfalseにするかは難しい。有効在庫の値をFlowおよびHistoryとあわせるにはfalseに
            //  する必要があるが、受注管理画面「引当可能数」と合わせるにはtrueにする必要がある。
            //  ここをtrueに変えるなら、受注管理Editの引当可能数のhelpText_noEscapeも変更すること
            false);
        
        // 指定日時点の在庫評価単価をテンポラリテーブル（temp_stock_price）に取得
        Logic_Stock::createTempStockPriceTable($stockDate);

        $this->selectQuery =
            " SELECT " .
            "   temp_stock.*, " .
                // オーダー残と未オーダー計画は合計してひとつの欄に表示
            "   coalesce(order_remained_quantity,0) + coalesce(plan_remained_quantity,0) as in_remained_quantity," .
            "   item_code," .
            "   item_name," .
            "   measure," .
            "   safety_stock," .
            "   temp_stock_price.stock_price," .
            "   logical_stock_quantity * temp_stock_price.stock_price as stock_amount " .
                ($isShowSeiban ? ",case when seiban='' then 'nothing' else seiban end as seiban_forlink" : "") .
                // ロケが非表示でもカラムはつくっておく。ソート対象に指定されていた場合のエラーを回避するため
                ($isShowLocation ? ",case when temp_stock.location_id=0 then '" . GEN_DEFAULT_LOCATION_NAME . "' else location_name end as location_name" : ",null as location_name") .
            " FROM " .
            "   temp_stock " .
            " LEFT JOIN " .
            "   item_master on temp_stock.item_id = item_master.item_id " .
                ($isShowLocation ? " LEFT JOIN (select location_id as locId, location_name from location_master) as t_loc on temp_stock.location_id = t_loc.locId" : "") .
                (is_numeric(@$form['gen_search_parent_item_id']) ?
                    " INNER JOIN (select item_id as exp_item_id from temp_bom_expand group by item_id) as t_exp on temp_stock.item_id = t_exp.exp_item_id "
                 : "") .
            " left join temp_stock_price on temp_stock.item_id = temp_stock_price.item_id " .
                 // 非表示品目・ダミー品目は表示しない
            " [Where] and not coalesce(item_master.end_item, false)" .
            	 " and not coalesce(item_master.dummy_item, false)" .
                (Gen_String::isNumeric(@$form['gen_search_logical_stock_quantity_from']) ?
                    " and logical_stock_quantity >= " . $form['gen_search_logical_stock_quantity_from'] : "") .
                (Gen_String::isNumeric(@$form['gen_search_logical_stock_quantity_to']) ?
                    " and logical_stock_quantity <= " . $form['gen_search_logical_stock_quantity_to'] : "") .
                (@$form['gen_search_stock_status']=='logical_alarm' ?
                    " and logical_stock_quantity < safety_stock" : "") .
                (@$form['gen_search_stock_status']=='available_alarm' ?
                    " and available_stock_quantity < safety_stock" : "") .
                (@$form['gen_search_received_remained_status']=='exist' ?
                    " and received_remained_quantity > 0" : "") .
                (@$form['gen_search_received_remained_status']=='zero' ?
                    " and coalesce(received_remained_quantity,0) = 0" : "") .
             " [Orderby]";

        // orderbyDefault にはseibanとlocation_nameも入れたいところだが、そうするとitem_masterとstockにindexが
        // 使用されなくなり、品目が多いときに極端に遅くなる（数十倍）。
        $this->orderbyDefault = 'item_code, temp_stock.seiban, location_id';
    }

    function setViewParam(&$form)
    {
        $this->tpl = "mobile/list.tpl";
        
        $form['gen_pageTitle'] = _g("在庫リスト");
        $form['gen_listAction'] = "Mobile_Stock_List";
        $form['gen_linkAction'] = "Mobile_Stock_History";
        $form['gen_idField'] = "item_id";

        $form['gen_columnArray'] =
            array(
                array(
                    'sortLabel'=>_g('品目コード'),
                    'label'=>"",
                    'field'=>'item_code',
                    'fontSize'=>13,
                    'after_noEscape'=>'<br>',
                ),
                array(
                    'sortLabel'=>_g('品目名'),
                    'label'=>"",
                    'field'=>'item_name',
                    'fontSize'=>17,
                    'style'=>'font-weight:bold;',
                    'after_noEscape'=>'<br>',
                ),
                array(
                    'sortLabel'=>_g('理論在庫'),
                    'label'=>_g('理論在庫'),
                    'field'=>'logical_stock_quantity',
                    'labelFontSize'=>12,
                    'labelStyle'=>'color:#999999;',
                    'fontSize'=>17,
                    'numberFormat'=>true,
                    'after_noEscape'=>'<br>',
                ),
                array(
                    'sortLabel'=>_g('有効在庫'),
                    'label'=>_g('有効在庫'),
                    'field'=>'available_stock_quantity',
                    'labelFontSize'=>12,
                    'labelStyle'=>'color:#999999;',
                    'fontSize'=>17,
                    'numberFormat'=>true,
                    'after_noEscape'=>'',
                ),
            );
    }
}