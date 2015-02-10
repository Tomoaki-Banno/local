<?php

class Mobile_StockInput_List extends Base_MobileListBase
{
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
                    'field'=>'inventory_date',
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
        global $gen_db;
        
        // 日付指定が不正のときは、前月末とする
        if (!Gen_String::isDateString(@$form['gen_search_inventory_date'])) {
            $form["gen_search_inventory_date"] = Gen_String::getLastMonthLastDateString();
        }

        // 指定日付時点の理論在庫（棚卸前）を計算
        // 09iまでは単に理論在庫を取得していたため、棚卸後は「理論在庫=棚卸数」になっていた。
        // 10iでは棚卸前の理論在庫を取得することで、棚卸差異を計算できるようにした。
        Logic_Stock::createTempStockTable(
            $form['gen_search_inventory_date'],
            null,   // item_id
            null,   // seiban
            null,   // location_id
            null,   // lot_id
            false,  // 有効在庫を取得しない
            true,   // サプライヤーロケを含める
            false,  // use_planを将来分まで差し引かない（有効在庫を取得しないので無関係）
            true    // stockDate当日の棚卸を計算から除外する。つまり棚卸がある場合、棚卸前の数値を取得する
            );
       
        $this->selectQuery = "
            select
                '{$form['gen_search_inventory_date']}___' || item_code || '___' || cast(inventory.location_id as text) || '___' || inventory.seiban 
                     || '___' || cast(coalesce(logical_stock_quantity,0) as text) || '___' || cast(coalesce(inventory_quantity,0) as text)
                    as param
                ,inventory.item_id
                ,item_code
                ,item_name
                ,inventory.seiban
                ,inventory.location_id
                ,case when inventory.location_id=0 then '" . GEN_DEFAULT_LOCATION_NAME . "' else location_name end as location_name
                ,inventory_quantity
                ,logical_stock_quantity
                 -- inventory_quantity は coalesceしないことに注意（実在庫未入力のときは差異0ではなく空欄とするため）
                ,inventory_quantity - coalesce(temp_stock.logical_stock_quantity,0) as diff_quantity
                ,measure
            from 
                inventory
                left join temp_stock
                 on inventory.item_id = temp_stock.item_id
                 and inventory.seiban = temp_stock.seiban
                 and inventory.location_id = temp_stock.location_id
                 and inventory.lot_id = temp_stock.lot_id
                left join item_master on inventory.item_id = item_master.item_id
                left join location_master on inventory.location_id = location_master.location_id
            [Where]
                and inventory_date = '{$form["gen_search_inventory_date"]}'
            [Orderby]
            ";
                
        $this->orderbyDefault = 'item_code, location_code';
    }

    function setViewParam(&$form)
    {
        $this->tpl = "mobile/list.tpl";
        
        $form['gen_pageTitle'] = _g("棚卸リスト");
        
        $form['gen_headerRightButtonURL'] = "index.php?action=Mobile_StockInput_Edit";
        $form['gen_headerRightButtonIcon'] = "add";
        $form['gen_headerRightButtonText'] = _g("新規登録");
        
        $form['gen_listAction'] = "Mobile_StockInput_List";
        $form['gen_linkAction'] = "Mobile_StockInput_Edit";
        $form['gen_idField'] = "param";
        
        $form['gen_columnArray'] =
            array(
                array(
                    'sortLabel'=>_g('品目コード'),
                    'label'=>"",
                    'field'=>'item_code',
                    'fontSize'=>14,
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
                    'sortLabel'=>_g('ロケ'),
                    'field'=>'location_name',
                    'fontSize'=>13,
                    'after_noEscape'=>'<br>',
                ),
                array(
                    'sortLabel'=>_g('実在庫'),
                    'label'=>_g('実在庫'),
                    'field'=>'inventory_quantity',
                    'labelFontSize'=>13,
                    'fontSize'=>17,
                    'numberFormat'=>true,
                    'after_noEscape'=>'<br>',
                ),
                array(
                    'sortLabel'=>_g('理論在庫'),
                    'label'=>_g('理論在庫'),
                    'field'=>'logical_stock_quantity',
                    'labelFontSize'=>13,
                    'fontSize'=>17,
                    'numberFormat'=>true,
                    'after_noEscape'=>'&nbsp;&nbsp;',
                ),
                array(
                    'label'=>_g('差数'),
                    'field'=>'diff_quantity',
                    'labelFontSize'=>13,
                    'fontSize'=>17,
                    'numberFormat'=>true,
                    'after_noEscape'=>'',
                ),
            );
    }
}