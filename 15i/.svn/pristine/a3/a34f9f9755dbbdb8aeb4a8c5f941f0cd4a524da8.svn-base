<?php

class Stock_Move_BulkEdit extends Base_ListBase
{

    function setSearchCondition(&$form)
    {
        global $gen_db;

        // この画面のqueryはカスタム項目に対応していない（メインテーブルをグループ化している）
        $this->denyCustomColumn = true;

        $query = "select location_id, location_name from location_master order by location_code";
        $option_location_group = $gen_db->getHtmlOptionArray($query, false, array("0" => _g(GEN_DEFAULT_LOCATION_NAME)));

        $form['gen_searchControlArray'] = array(
            array(
                'label' => _g('オーダー番号'),
                'type' => 'dropdown',
                'field' => 'order_detail_id',
                'dropdownCategory' => 'manufacturing',
                'nosql' => true,
                'helpText_noEscape' => _g('製造指示書のオーダー番号が検索対象となります。'),
                'hidePin' => true,
            ),
            array(
                'label' => _g('在庫日'),
                'type' => 'calendar',
                'field' => 'stock_date',
                'default' => date('Y-m-d'),
                'nosql' => true,
            ),
            array(
                'label' => _g('理論在庫数'),
                'type' => 'numFromTo',
                'field' => 'logical_stock_quantity',
                'size' => '80',
                'rowSpan' => 2,
            ),
            array(
                'label' => _g('移動元ロケーション'),
                'type' => 'select',
                'field' => 'temp_stock___location_id',
                'options' => $option_location_group,
            ),
        );
    }

    function convertSearchCondition($converter, &$form)
    {
        $converter->nullBlankToValue('move_date', date('Y-m-d'));
    }

    function beforeLogic(&$form)
    {
    }

    function setQueryParam(&$form)
    {
        $orderDetailId = null;
        if (isset($form['gen_search_order_detail_id']) && is_numeric($form['gen_search_order_detail_id']))
            $orderDetailId = $form['gen_search_order_detail_id'];
        $stockDate = @$form['gen_search_stock_date'];
        // 指定されていなければずっと先まで。（2038年以降は日付と認識されない）
        if (!Gen_String::isDateString($stockDate))
            $stockDate = "2037-12-31";

        // temp_stockテーブルにデータを取得
        Logic_Stock::createTempStockTable(
                $stockDate
                , null
                , null
                , null
                , null
                , true      // 有効在庫も取得
                , true      // サプライヤー在庫を含めるかどうか
                // use_plan の全期間分差し引くかどうか。差し引くならtrue。指定日分までの引当・予約しか含めないならfalse。
                //  これをtrueにするかfalseにするかは難しい。有効在庫の値をFlowおよびHistoryとあわせるにはfalseに
                //  する必要があるが、受注管理画面「引当可能数」と合わせるにはtrueにする必要がある。
                //  ここをtrueに変えるなら、受注管理Editの引当可能数のhelpText_noEscapeも変更すること
                , false
        );

        $this->selectQuery = "
            select
                cast(temp_stock.item_id as text) || '_' ||
                    temp_stock.seiban || '_' ||
                    cast(temp_stock.location_id as text) || '_' ||
                    '0' as item_seiban_location_lot
                ,temp_stock.item_id
                ,temp_stock.seiban
                ,temp_stock.location_id as source_location_id
                ,0 as lot
                ,item_code
                ,item_name
                ,location_code
                ,case when temp_stock.location_id=0 then '" . _g(GEN_DEFAULT_LOCATION_NAME) . "' else location_name end as location_name
                ,logical_stock_quantity
                ,use_child_quantity
                " . (is_numeric($orderDetailId) ?
                        ",case
                            when coalesce(logical_stock_quantity,0) > 0 and coalesce(logical_stock_quantity,0) > coalesce(use_child_quantity,0) then use_child_quantity
                            when coalesce(logical_stock_quantity,0) > 0 then logical_stock_quantity
                            else null end as quantity " :
                        ",'' as quantity ") . "
            from
                temp_stock
                inner join item_master on temp_stock.item_id = item_master.item_id
                left join location_master on temp_stock.location_id = location_master.location_id
                inner join (
                    select
                        child_item_id
                        ,seiban
                        ,order_detail_quantity * order_child_item.quantity as use_child_quantity
                    from
                        order_detail
                        left join order_child_item on order_detail.order_detail_id = order_child_item.order_detail_id
                    where
                        " . (is_numeric($orderDetailId) ? "order_detail.order_detail_id = {$orderDetailId}" : "1=0") . "
                    ) as t_order on temp_stock.item_id = t_order.child_item_id
                        and coalesce(temp_stock.seiban,'') = coalesce(t_order.seiban,'')
            [Where]
                " . (is_numeric($orderDetailId) ?
                        " and temp_stock.item_id in (select order_child_item.child_item_id from order_child_item
                        inner join order_detail on order_detail.order_detail_id = order_child_item.order_detail_id
                        where order_detail.order_detail_id = {$orderDetailId})" : " and 1=0") . "
            [Orderby]
        ";

        $this->orderbyDefault = 'item_code, location_name, seiban';
    }

    function setViewParam(&$form)
    {
        global $gen_db;

        $form['gen_pageTitle'] = _g("一括ロケーション間移動");
        $form['gen_menuAction'] = "Menu_Stock";
        $form['gen_listAction'] = "Stock_Move_BulkEdit";
        $form['gen_idField'] = 'item_seiban_location_lot';
        $form['gen_onLoad_noEscape'] = "";

        $form['gen_dataRowHeight'] = 25;        // データ部の1行の高さ

        $form['gen_returnUrl'] = "index.php?action=Stock_Move_List&gen_restore_search_condition=true";
        $form['gen_returnCaption'] = _g('ロケーション間移動へ戻る');

        // 更新許可がなければアクセス不可
        if ($form['gen_readonly'] == 'true') {
            $form['gen_message_noEscape'] = "<BR><Font color=red>" . _g("一括ロケーション間移動を行う権限がありません。") . "</Font>";
        } else {
            // 移動日コントロール作成
            $html_move_date = Gen_String::makeCalendarHtml(
                array(
                    'label' => _g("移動日") . _g("："),
                    'name' => "move_date",
                    'value' => @$form['move_date'],
                    'size' => '85',
                )
            );

            // option（移動先ロケ）
            $opt = $gen_db->getHtmlOptionArray("select location_id, location_name from location_master order by location_code", false, array("0" => _g(GEN_DEFAULT_LOCATION_NAME)));
            $html_dist_location_id = Gen_String::makeSelectHtml("dist_location_id", $opt, @$form['dist_location_id'], "", "Stock_Move_BulkEdit", @$form['gen_pins']);

            $form['gen_message_noEscape'] = "
                <table border='0'>
                <tr>
                    <td align='left' nowrap>{$html_move_date}</td>
                    <td width='20'></td>
                    <td align='left' nowrap>" . _g("移動先ロケーション") . _g("：") . "{$html_dist_location_id}</td>
                </tr><tr>
                    <td align='center' colspan='3'>
                        <div id=\"doButton\" style=\"margin-top:3px;\">
                            <input type=\"button\" class=\"gen-button\" value=\"&nbsp;&nbsp; " . _g("一括ロケーション間移動登録を実行") . " &nbsp;&nbsp;\" onClick=\"bulkEdit()\">
                        </div>
                    </td>
                </tr>
                </table>
            ";

            // リスト表示の際、gen_messageに記述しているコントロールをパラメータとして付与する。
            // gen_messageに記述しているコントロールは表示条件ではないので、gen_resotre_search_condition による復元が行われないため。
            $form['gen_beforeListUpdateScript_noEscape'] = "
                if (param === null) param = {};
                param['move_date'] = $('#move_date').val();
                param['dist_location_id'] = $('#dist_location_id').val();
            ";
        }

        $form['gen_javascript_noEscape'] = "
            function bulkEdit() {
                var frm = gen.list.table.getCheckedPostSubmit('item_seiban_location_lot', new Array('quantity','remarks'));
                if (frm.count == 0) {
                   alert('" . _g("データが選択されていません。") . "');
                   return;
                }
                var msg = '';
                msg += '" . _g("実行には時間がかかる場合があります。処理が終わるまでコンピュータに手を触れずにお待ちください。実行しますか？") . "';
                if (!window.confirm(msg)) return;

                document.body.style.cursor = 'wait';
                document.getElementById('doButton').innerHTML = \"<table><tr><td bgcolor='#ffcc33'>" . _g("実行中") . "...</td></tr></table>\";
                gen.ui.disabled($('#gen_searchButton'));

                var postUrl = 'index.php?action=Stock_Move_BulkEntry';
                postUrl += '&order_detail_id=' + $('#gen_search_order_detail_id').val();
                postUrl += '&move_date=' + $('#move_date').val();
                postUrl += '&dist_location_id=' + $('#dist_location_id').val();
                postUrl += '&gen_restore_search_condition=true';
                frm.submit(postUrl, null);
            }

            function onOrderIdForUserChange() {
               document.getElementById('gen_searchButton').focus();
            }

            function check(id) {
                $('#item_seiban_location_lot_'+id).attr('checked',true);
            }
        ";

        $form['gen_fixColumnArray'] = array(
            array(
                'label' => _g("移動"),
                'name' => 'item_seiban_location_lot',
                'type' => 'checkbox',
            ),
        );
        $form['gen_columnArray'] = array(
            array(
                'label' => _g('品目コード'),
                'field' => 'item_code',
                'width' => '100',
                'sameCellJoin' => true,
            ),
            array(
                'label' => _g('品目名'),
                'field' => 'item_name',
                'width' => '200',
                'sameCellJoin' => true,
                'parentColumn' => 'item_code',
            ),
            array(
                'label' => _g('ロケーション名'),
                'field' => 'location_name',
                'width' => '180',
            ),
            array(
                'label' => _g('製番'),
                'field' => 'seiban',
                'width' => '100',
                'align' => 'center',
            ),
            array(
                'label' => _g('移動手配数'),
                'field' => 'use_child_quantity',
                'width' => '100',
                'type' => 'numeric',
                'helpText_noEscape' => _g("指定オーダー番号の子品目の使用数です。")
            ),
            array(
                'label' => _g('理論在庫数'),
                'field' => 'logical_stock_quantity',
                'width' => '100',
                'type' => 'numeric',
                'colorCondition' => array("#ffcc99" => "true"), // 色付け条件。常にtrueになるようにしている
                'helpText_noEscape' => _g("指定日時点の在庫数です。")
            ),
            array(
                'label' => _g('数量'),
                'width' => '100',
                'type' => 'textbox',
                'align' => 'center',
                'field' => 'quantity',
                'ime' => 'off',
                'colorCondition' => array("#ffffcc" => "true"),
                'style' => 'text-align:right; background-color:#ffffcc',
                'onChange_noEscape' => "check('[id]')"
            ),
            array(
                'label' => _g('ロケ間移動備考'),
                'width' => '200',
                'type' => 'textbox',
                'field' => 'remarks',
                'ime' => 'on',
                'onChange_noEscape' => "check('[id]')"
            ),
        );
    }

}