<?php

class Manufacturing_Received_EasyMrp extends Base_ListBase
{

    var $itemName;
    var $receivedNumber;
    var $receivedQty;

    function setSearchCondition(&$form)
    {
        $form['gen_searchControlArray'] = array(
            array(
                'label' => _g('受注製番'),
                'type' => 'dropdown',
                'field' => 'received_detail_id',
                'dropdownCategory' => 'received_detail',
                'nosql' => 'true',
            ),
        );
    }

    function convertSearchCondition($converter, &$form)
    {
    }

    function beforeLogic(&$form)
    {
        global $gen_db;

        if (is_numeric(@$form['gen_search_received_detail_id'])) {
            // 受注が指定されているとき - データ取得
            $query = "
            select
                received_detail.item_id
                ,item_name
                ,received_number
                ,received_quantity
            from
                received_detail
                inner join received_header on received_detail.received_header_id = received_header.received_header_id
                inner join item_master on received_detail.item_id = item_master.item_id
            where
                received_detail_id = '" . $form['gen_search_received_detail_id'] . "'
            ";
            $res = $gen_db->queryOneRowObject($query);
            $this->itemName = $res->item_name;
            $this->receivedNumber = $res->received_number;
            $this->receivedQty = $res->received_quantity;

            // 明細クエリ用：　一時テーブルtemp_bom_expandに子品目リストを取得
            Logic_Bom::expandBom($res->item_id, $res->received_quantity, false, false, true);

            $query = "select item_id from temp_bom_expand group by item_id";
            $arr = $gen_db->getArray($query);
            $itemArr = array();
            foreach ($arr as $row) {
                $itemArr[] = $row['item_id'];
            }

            // 最終有効在庫を計算
            Logic_Stock::createTempStockTable(
                    ""
                    , $itemArr
                    , ''        // seiban
                    , 'sum'     // location_id
                    , 'sum'     // lot_id
                    , true      // 有効在庫
                    , false     // サプライヤーロケを含めない
                    , false     // use_planを将来分まで差し引かない
                    , false     // stockDate当日の棚卸を計算から除外しない
            );
        } else {
            // 受注が指定されていないとき - ダミーデータ
            $query = "create temp table  temp_bom_expand (item_id int, quantity numeric, lc numeric)";
            $gen_db->query($query);
        }
    }

    function setQueryParam(&$form)
    {
        $classQuery = Gen_Option::getPartnerClass('list-query');
        
        $this->selectQuery = "
             select
                temp_bom_expand.lc
                ,t_item.item_code
                ,t_item.item_name
                ----- lot ver ----- change 1 line
                ,case t_item.order_class when 0 then '" . _g('製番') . "' when 2 then '" . _g('ロット') . "' else '" . _g('MRP') . "' end as order_class
                ,temp_bom_expand.quantity
                ,measure
                ,temp_bom_expand.quantity * " . $this->receivedQty . " as need_quantity
                 /* 内製品の判断は手配区分で */
                ,case t_item_order.partner_class {$classQuery} end as arrangement_class
                ,available_stock_quantity
             from
                temp_bom_expand
                inner join
                   (select
                       item_master.item_id as id
                       ,max(item_code) as item_code
                       ,max(item_name) as item_name
                       ,max(order_class) as order_class
                       ,max(measure) as measure
                    from item_master
                       left join item_process_master on item_master.item_id = item_process_master.item_id
                       left join process_master on item_process_master.process_id = process_master.process_id
                    group by item_master.item_id
                   ) as t_item
                       on temp_bom_expand.item_id = t_item.id
                left join
                   (select item_id as id, order_user_id, default_order_price, partner_class
                       from item_order_master where line_number=0) as t_item_order
                       on temp_bom_expand.item_id = t_item_order.id
                left join temp_stock on temp_bom_expand.item_id = temp_stock.item_id
             [Where]
             [Orderby]
        ";
        $this->orderbyDefault = 'item_code_key';
    }

    function setViewParam(&$form)
    {
        $form['gen_pageTitle'] = _g("かんたん受注展開");
        $form['gen_menuAction'] = "Menu_Manufacturing";
        $form['gen_listAction'] = "Manufacturing_Received_EasyMrp";
        $form['gen_idField'] = 'item_id';
        $form['gen_excel'] = "false";

        $form['gen_titleRowHeight'] = 50;
        $form['gen_dataRowHeight'] = 25;        // データ部の1行の高さ

        $form['gen_returnUrl'] = "index.php?action=Manufacturing_Received_List&gen_restore_search_condition=true";
        $form['gen_returnCaption'] = _g('受注登録へ戻る');

        $form['gen_message_noEscape'] =
                "品目名：&nbsp;&nbsp;" . h($this->itemName) .
                "<br>受注番号：&nbsp;&nbsp;" . h($this->receivedNumber) .
                "<br>数量：&nbsp;&nbsp;" . h($this->receivedQty);

        $form['gen_fixColumnArray'] = array(
            array(
                'label' => _g('階層'),
                'field' => 'lc',
                'width' => '40',
                'align' => 'center',
            ),
            array(
                'label' => _g('品目コード'),
                'field' => 'item_code',
                'width' => '110',
            ),
            array(
                'label' => _g('品目名'),
                'field' => 'item_name',
                'width' => '200',
            ),
        );
        $form['gen_columnArray'] = array(
            array(
                'label' => _g('管理区分'),
                'field' => 'order_class',
                'width' => '60',
                'align' => 'center',
            ),
            array(
                'label' => _g('手配区分'),
                'field' => 'arrangement_class',
                'width' => '80',
            ),
            array(
                'label' => _g('員数'),
                'field' => 'quantity',
                'width' => '40',
                'type' => 'numeric',
                'helpText_noEscape' => _g('最上位の品目（算定対象品目）に対する員数です（構成上の親品目に対する員数ではありません）。つまり、' .
                        '計算対象品目ひとつに対して、この品目がいくつ必要になるかをあらわしています。')
            ),
            array(
                'label' => _g('単位'),
                'field' => 'measure',
                'type' => 'data',
                'width' => '35',
            ),
            array(
                'label' => _g('必要数'),
                'field' => 'need_quantity',
                'width' => '70',
                'type' => 'numeric',
            ),
            array(
                'label' => _g('手配数量'),
                'width' => '80',
                'type' => 'textbox',
                'align' => 'center',
                'field' => 'order_detail_quantity',
                'colorCondition' => array("#ffffcc" => "true"),
                'style' => 'text-align:right; background-color:#ffffcc',
                'onChange_noEscape' => "check([id])"
            ),
            array(
                'label' => _g('有効在庫数'),
                'field' => 'available_stock_quantity',
                'width' => '70',
                'type' => 'numeric',
            ),
        );
    }

}