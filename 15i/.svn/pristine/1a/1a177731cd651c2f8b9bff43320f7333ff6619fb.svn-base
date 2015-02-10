<?php

class Manufacturing_Mrp_Analyze extends Base_ListBase
{

    function validate($validator, &$form)
    {
        if (isset($form['item_id'])) {
            $validator->existRecord('item_id', _g('品目がマスタに登録されていません。'), "select item_id from item_master where item_id = $1");
        }
        if ($validator->hasError()) {
            unset($form['item_id']);
        }
        return "action:Manufacturing_Mrp_Analyze";
    }

    function setSearchCondition(&$form)
    {
        $form['gen_searchControlArray'] = array(
            array(
                'label' => _g('品目'),
                'type' => 'dropdown',
                'field' => 'item_id',
                'size' => '150',
                'dropdownCategory' => 'item',
            ),
            array(
                'label' => _g('製番'),
                'field' => 'seiban',
            ),
        );

        // 引数が渡されたらその値を優先する
        if (is_numeric(@$form['item_id'])) {
            $form['gen_search_item_id'] = $form['item_id'];
        }
        if (isset($form['seiban'])) {
            $form['gen_search_seiban'] = $form['seiban'];
        }
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
                 case when calc_date=min_date then calc_date || 'まで' else cast(calc_date as text) end as calc_date
                 ,case when calc_date=min_date then 'before' else '' end as before_flag
                 ,seiban
                 ,before_useable_quantity
                 ,independent_demand
                 ,plan_qty
                 ,depend_demand
                 ,order_remained
                 ,due_in
                 ,use_plan
                 ,arrangement_quantity
                 ,measure
                 ,arrangement_start_date
                 ,arrangement_finish_date
                 ,useable_quantity
                 ,safety_stock
                 ,stock_quantity
                 ,holiday_master.holiday
            from
                mrp
                left join (select item_id as min_item_id, seiban as min_seiban, min(calc_date) as min_date from mrp group by item_id, seiban) as t1
                    on mrp.item_id = t1.min_item_id and mrp.seiban = t1.min_seiban
                left join holiday_master on mrp.calc_date = holiday_master.holiday
                left join (select item_id as iid, measure from item_master) as t_item on mrp.item_id = t_item.iid
            " . (is_numeric(@$form['gen_search_item_id']) ? "[Where]" : "where 1=0") . "
            [Orderby]
        ";
        if (is_numeric(@$form['gen_search_item_id'])) {
            $this->orderbyDefault = 'calc_date';
        } else {
            // Dummy
            $this->orderby = array('');
            $this->orderbyDefault = '';
        }

        $this->pageRecordCount = 30;
    }

    function setViewParam(&$form)
    {
        global $gen_db;

        $form['gen_pageTitle'] = _g("所要量計算結果分析");
        $form['gen_menuAction'] = "Menu_Manufacturing";
        $form['gen_listAction'] = "Manufacturing_Mrp_Analyze";
        $form['gen_idField'] = "item_id";
        $form['gen_excel'] = "true";
        $form['gen_pageHelp'] = _g("結果分析");

        $form['gen_returnUrl'] = "index.php?action=Manufacturing_Mrp_List&gen_restore_search_condition=true";
        $form['gen_returnCaption'] = _g('所要量計算画面へ戻る');

        // Excel出力
        if (is_numeric(@$form['gen_search_item_id'])) {
            $query = "select item_code || ' (' || item_name || ')' from item_master where item_id = '{$form['gen_search_item_id']}'";
            $form['gen_excelShowArray'] = array(array(0, 2, _g("品目：") . ' ' . $gen_db->queryOneValue($query)));
            $form['gen_excelDetailRow'] = 4;
        }

        if (is_numeric(@$form['gen_search_item_id'])) {
            $form['gen_onLoad_noEscape'] = "ddUpdate();";    // ジャンプしてきたとき、品目DDのサブテキストが正しく表示されない不具合に対処
            $form['gen_javascript_noEscape'] = "
                function ddUpdate() {
                    document.getElementById('gen_search_item_id_show').onchange();
                }
            ";

            $itemId = $gen_db->quoteParam($form['gen_search_item_id']);

            $query = "
            select
                item_code
                ,item_name
                ,case order_class when 0 then '" . _g("製番") . "' when 2 then '" . _g("ロット") . "' else '" . _g("MRP") . "' end as order_class
                ,safety_stock
                ,case partner_class when 0 then '" . _g("発注") . "' when 1 then '" . _g("外注(支給無)") . "' when 2 then '" . _g("外注(支給有)") . "' else '" . _g("内製") . "' end as partner_class
                ,item_order_master.default_lot_unit
                ,item_order_master.default_lot_unit_2
                ,coalesce(item_order_master.multiple_of_order_measure,1) as multiple_of_order_measure
                ,item_master.measure
                ,case when lead_time is null then '" . _g("自動計算") . "' else cast(lead_time as text) end as lead_time
                ,safety_lead_time
            from
                item_master
                left join item_order_master on item_master.item_id = item_order_master.item_id and line_number=0
            where
                item_master.item_id = '{$itemId}';
            ";
            $item = $gen_db->queryOneRowObject($query);

            $query = "
            select
                bom_master.item_id
                ,item_master.item_code
                ,quantity
                ,t_item2.measure
            from
                bom_master
                inner join item_master on bom_master.item_id = item_master.item_id
                inner join item_master t_item2 on bom_master.child_item_id = t_item2.item_id
            where
                child_item_id = '{$itemId}'
            order by
                seq
                ,item_master.item_code
            ";
            $parent = "";
            if (is_array($arr = $gen_db->getArray($query))) {
                foreach ($arr as $row) {
                    $parent .=
                            "<span style='" . (@$form['link_item_id'] === $row['item_id'] ? "background-color:#00ff99" : "") . "'>" .
                            "<a href=index.php?action=Manufacturing_Mrp_Analyze" .
                            "&gen_search_item_id=" . h($row['item_id']) .
                            "&link_item_id={$itemId}>" .
                            h($row['item_code']) . "</a></span> (" . h($row['quantity'] . $row['measure']) .
                            ")&nbsp;&nbsp;&nbsp;";
                }
            }

            // 子品目表示
            $query = "
            select
                bom_master.child_item_id
                ,item_code
                ,quantity
                ,measure
            from
                bom_master
                inner join item_master on bom_master.child_item_id = item_master.item_id
            where
                bom_master.item_id = '{$itemId}'
            order by
                seq
                ,item_code
            ";
            $child = "";
            if (is_array($arr = $gen_db->getArray($query))) {
                foreach ($arr as $row) {
                    $child .=
                            "<span style='" . (@$form['link_item_id'] === $row['child_item_id'] ? "background-color:#00ff99" : "") . "'>" .
                            "<a href=index.php?action=Manufacturing_Mrp_Analyze" .
                            "&gen_search_item_id=" . h($row['child_item_id']) .
                            "&link_item_id={$itemId}>" .
                            h($row['item_code']) . "</a></span> (" . h($row['quantity'] . $row['measure']) .
                            ")&nbsp;&nbsp;&nbsp;";
                }
            }

            $form['gen_message_noEscape'] =
                    "<table  style='table-layout:fixed; width:640px;' border=1 cellpadding=3 cellspacing=1>" .
                    "<tr>" .
                    "    <td width='120' bgcolor='#cccccc'>" . _g("製番") . "</td><td width='150'>" . (@$form['gen_search_seiban'] != "" ? h($form['gen_search_seiban']) : "(" . _g("なし") . ")") . "&nbsp;</td>" .
                    "    <td width='120' bgcolor='#cccccc'>" . _g("管理区分") . "</td><td width='150'>" . h($item->order_class) . "</td>" .
                    "    <td width='120' bgcolor='#cccccc'>" . _g("手配区分") . "</td><td width='150'>" . h($item->partner_class) . "</td>" .
                    "</tr><tr>" .
                    "    <td bgcolor='#cccccc'>" . _g("最低ロット数") . "</td><td>" . h($item->default_lot_unit) . " " . h($item->measure) . "</td>" .
                    "    <td bgcolor='#cccccc'>" . _g("手配ロット数") . "</td><td>" . h($item->default_lot_unit_2) . " " . h($item->measure) . "</td>" .
                    "    <td bgcolor='#cccccc'>" . _g("安全在庫数") . "</td><td>" . h($item->safety_stock) . " " . h($item->measure) . "</td>" .
                    "</tr>" .
                    "</tr><tr>" .
                    "    <td bgcolor='#cccccc'>" . _g("リードタイム") . "</td><td>" . h($item->lead_time) . "</td>" .
                    "    <td bgcolor='#cccccc'>" . _g("安全リードタイム") . "</td><td>" . h($item->safety_lead_time) . "</td>" .
                    "    <td colspan='2'>&nbsp;</td>" .
                    "</tr>" .
                    "<tr>" .
                    "    <td bgcolor='#cccccc'>" . _g("親品目(員数)") . "</td><td colspan='5'>" . $parent . "&nbsp;</td>" .
                    "</tr><tr>" .
                    "    <td bgcolor='#cccccc'>" . _g("子品目(員数)") . "</td><td colspan='5'>" . $child . "&nbsp;</td>" .
                    "</tr>" .
                    "</table>";
        }

        @$form['gen_message_noEscape'] .=
                "<BR>" .
                _g("※各項目の説明を見るには、見出しの「？」にマウスを当ててください。");

        $form['gen_rowColorCondition'] = array(
            "#d7d7d7" => "'[before_flag]'=='before'",
            "#eaeaea" => "'[holiday]'!=''"
        );

        $form['gen_colorSample'] = array(
            "eaeaea" => array(_g("グレー"), _g("休日")),
        );

        $form['gen_fixColumnArray'] = array(
            array(
                'label' => _g('日付'),
                'field' => 'calc_date',
                'type' => 'date',
                'width' => '140',
            ),
        );

        $form['gen_columnArray'] = array(
            array(
                'label' => _g('前在庫数'),
                'field' => 'before_useable_quantity',
                'type' => 'numeric',
                'helpText_noEscape' => _g("その日が始まる時点での有効在庫数（在庫数から引当済数を引いたもの）です。前日の有効在庫数と一致します。") . "<br>" . _g("サプライヤーロケーション分は含まれません。") . "<br>" . _g("最初の行に表示されている前在庫数は、前回の棚卸数を表しています。"),
            ),
            array(
                'label' => _g('単位'),
                'field' => 'measure',
                'type' => 'data',
                'width' => '35',
            ),
            array(
                'label' => _g('受注数'),
                'field' => 'independent_demand',
                'type' => 'numeric',
                'zeroToBlank' => true,
                'helpText_noEscape' => _g("受注数のうち未引当の分と、計画数の合計です。") . "<br>" . _g("最初の行に表示されている数は、受注残（すでに受注納期が過ぎているが引当も納品もされていない数）を表しています。"),
            ),
            array(
                'label' => _g('従属需要数'),
                'field' => 'depend_demand',
                'type' => 'numeric',
                'zeroToBlank' => true,
                'helpText_noEscape' => _g("親品目の製造（今回のMRPで発行される製造指示による）の際に使用される数量です。"),
            ),
            array(
                'label' => _g('使用予定数'),
                'field' => 'use_plan',
                'type' => 'numeric',
                'zeroToBlank' => true,
                // 受注引当は全期間分差し引きされるが使用予約はそうではない。
                //  Logic_Stock::createTempPlanRemainedTable() 冒頭コメント参照
                'helpText_noEscape' => _g("受注に対する引当数（未納品分）や、発行済みの製造指示により子品目として消費する予定の数量です。") . "<br>" .
                _g("受注引当分については計算期間前・期間中・期間後のすべての引当数が合計され、計算開始日の時点で有効在庫から差し引かれます（他の需要に使用されないよう確保しておくため）。") .
                _g("したがって最初の行に全期間分の引当数が表示されます。") . "<br>" .
                _g("子品目として使用予定の分は、使用予定日に有効在庫から差し引かれます。使用予定日に予定数が表示されます。"),
            ),
            array(
                'label' => _g('発注製造残'),
                'field' => 'order_remained',
                'type' => 'numeric',
                'zeroToBlank' => true,
                'helpText_noEscape' => _g("すでに発行済みの製造指示・注文により入庫する予定の数量です。"),
            ),
            array(
                'label' => _g('入出庫数'),
                'field' => 'due_in',
                'type' => 'numeric',
                'zeroToBlank' => true,
                'helpText_noEscape' => _g("「入庫登録」等の画面で登録した数量です。全ロケーションの合計数ですが、サプライヤーロケーション分は含まれません。") . "<BR>" . _g("最初の行に表示されている数は、前回棚卸から期間前日までの入出庫数の合計を表しています。"),
            ),
            array(
                'label' => _g('計画数'),
                'field' => 'plan_qty',
                'type' => 'numeric',
                'zeroToBlank' => true,
                'helpText_noEscape' => _g("計画登録画面で登録された数量です。在庫の有無にかかわらず、そのままの数量がオーダー数になります。"),
            ),
            array(
                'label' => _g('オーダー数'),
                'field' => 'arrangement_quantity',
                'type' => 'numeric',
                'colorCondition' => array("#ffcc99" => "true"), // 色付け条件。常にtrueになるようにしている
                'zeroToBlank' => true,
                'helpText_noEscape' => _g("所要量計算の結果として算出された、製造指示や注文を発行すべき数量です。") . "<BR>" . _g("オーダー数 = 受注数 + 従属需要数 - 前在庫数 + 使用予定数 - 発注製造残 - 入出庫数 + 安全在庫数。 ") . "<BR>" . _g("ただし手配単位数の倍数となるよう丸められます。") . "<BR>" . _g("計画数は在庫の有無にかかわらずそのままオーダー数に加算されます。"),
            ),
            array(
                'label' => _g('オーダー日'),
                'field' => 'arrangement_start_date',
                'type' => 'date',
                'showCondition' => "'[arrangement_quantity]' != '0'",
                'zeroToBlank' => true,
                'helpText_noEscape' => _g("オーダー納期からリードタイムの日数分さかのぼった日付です。"),
            ),
            array(
                'label' => _g('オーダー納期'),
                'field' => 'arrangement_finish_date',
                'type' => 'date',
                'showCondition' => "'[arrangement_quantity]' != '0'",
                'zeroToBlank' => true,
                'helpText_noEscape' => _g("使用予定日から安全リードタイムの日数分さかのぼった日付です。"),
            ),
            array(
                'label' => _g('有効在庫数'),
                'field' => 'useable_quantity',
                'type' => 'numeric',
                'helpText_noEscape' => _g("有効在庫数 = 前在庫数 - 受注/計画数 - 従属需要数 - 使用予定数 + 発注製造残 + 入出庫数  + オーダー数。") . "<BR>" . _g("初日の有効在庫数 = 前月棚卸数 + 初日までの入出庫 + 初日までの発注製造残。"),
            ),
        );
    }

}
