<?php

class Manufacturing_BaseCost_StandardCostList extends Base_ListBase
{

    var $totalBaseCost;

    function setSearchCondition(&$form)
    {
        $form['gen_searchControlArray'] = array(
        );
    }

    function convertSearchCondition($converter, &$form)
    {
    }

    function beforeLogic(&$form)
    {
        global $gen_db;
        
        // エクセル出力
        if (isset($form['gen_excelMode']) && isset($_SESSION['gen_search_item_id'])) {
            $form['item_id'] = $_SESSION['gen_search_item_id'];
        }

        // データの取得
        if (isset($form['item_id']) && is_numeric($form['item_id'])) {
            // 品目が指定されているとき - データ取得
            // 合計表示用：　金額取得
            $this->totalBaseCost = Logic_BaseCost::calcStandardBaseCost($form['item_id'], 1);
            // 明細クエリ用：　一時テーブルtemp_bom_expandに子品目リストを取得
            Logic_Bom::expandBom($form['item_id'], 1, false, false, true);
            // エクセル出力用
            $_SESSION['gen_search_item_id'] = $form['item_id'];
        } else {
            // 品目が指定されていないとき - ダミーデータ
            $this->totalBaseCost = 0;
            $query = "create temp table temp_bom_expand (item_id int, quantity numeric, lc numeric, item_code_key text)";
            $gen_db->query($query);
            // エクセル出力用
            unset($_SESSION['gen_search_item_id']);
        }
    }

    function setQueryParam(&$form)
    {
        $classQuery1 = Gen_Option::getOrderClass('list-query');
        $classQuery2 = Gen_Option::getPartnerClass('list-query');
        
        $this->selectQuery = "
            select
                temp_bom_expand.lc
                ,temp_bom_expand.item_code_key
                ,t_item.item_code
                ,t_item.item_name
                ,case t_item.order_class {$classQuery1} end as order_class
                ,temp_bom_expand.quantity
                ,measure
                /* 手配区分 */
                ,case t_item_order.partner_class {$classQuery2} end as arrangement_class
                    
                /*　以下は Logic_BaseCost::calcStandardBaseCost() と合わせておく必要がある */
                
                /* 製造原価（内製のみ） */
                ,case when t_item_order.partner_class = 3 then
                    work_amount else 0 end as work_amount
                /* 外製工賃（外製(支給あり)のみ） */
                ,case when t_item_order.partner_class = 2 then
                    t_item_order.default_order_price else 0 end as order_price
                /* 在庫評価単価（発注・外製(支給なし)のみ） */
                ,case when t_item_order.partner_class in (0,1) then
                    t_item.stock_price else 0 end as stock_price
                /* 標準原単価 */
                ,case
                    when t_item_order.partner_class = 3 then work_amount
                    when t_item_order.partner_class = 2 then t_item_order.default_order_price
                    else t_item.stock_price end as standard_base_cost
                /* 原単価合計 */
                ,(case
                    when t_item_order.partner_class = 3 then work_amount
                    when t_item_order.partner_class = 2 then t_item_order.default_order_price
                    else t_item.stock_price end) * temp_bom_expand.quantity as total_standard_base_cost
            from
                temp_bom_expand
                inner join (
                    select
                       item_master.item_id as id
                       ,max(item_code) as item_code
                       ,max(item_name) as item_name
                       ,max(order_class) as order_class
                       ,sum(default_work_minute * item_process_master.charge_price + coalesce(item_process_master.subcontract_unit_price,0)
                            + coalesce(item_process_master.overhead_cost,0)) as work_amount
                       ,max(stock_price) as stock_price
                       ,max(measure) as measure
                    from
                        item_master
                        left join item_process_master on item_master.item_id = item_process_master.item_id
                        left join process_master on item_process_master.process_id = process_master.process_id
                    group by
                        item_master.item_id
                    ) as t_item on temp_bom_expand.item_id = t_item.id
                left join (
                    select
                        item_id as id
                        ,order_user_id
                        ,default_order_price
                        ,partner_class
                    from
                        item_order_master
                    where
                        line_number = 0
                    ) as t_item_order on temp_bom_expand.item_id = t_item_order.id
            [Where]
            [Orderby]
        ";

        $this->orderbyDefault = 'item_code_key';
    }

    function setViewParam(&$form)
    {
        global $gen_db;

        $form['gen_pageTitle'] = _g("標準原価算定");
        $form['gen_menuAction'] = "Menu_Manufacturing";
        $form['gen_listAction'] = "Manufacturing_BaseCost_StandardCostList";
        $form['gen_idField'] = 'item_id';
        $form['gen_excel'] = "true";
        $form['gen_pageHelp'] = _g("標準原価");

        $form['gen_titleRowHeight'] = 50;

        $form['gen_returnUrl'] = "index.php?action=Manufacturing_BaseCost_List&gen_restore_search_condition=true";
        $form['gen_returnCaption'] = _g('原価リストへ戻る');

        $keyCurrency = $gen_db->queryOneValue("select key_currency from company_master");

        $form['gen_excelShowArray'] = array(array(3, 0, _g("標準原価") . " {$keyCurrency} " . number_format($this->totalBaseCost)));
        
        $form['gen_javascript_noEscape'] = "
            function onItemChange() {
                
            }
        ";

        // 品目コントロール作成
        $html_item_id = Gen_String::makeDropdownHtml(
            array(
                'label' => "",
                'name' => 'item_id',
                'value' => @$form['item_id'],
                'size' => '11',
                'subSize' => '20',
                'dropdownCategory' => 'item',
                'onChange_noEscape' => "gen.list.postForm()",
                'require' => true,
                'pinForm' => "Manufacturing_BaseCost_StandardCostList",
                'genPins' => @$form['gen_pins'],
            )
        );
        
        $form['gen_message_noEscape'] =
                _g("算定品目") . "&nbsp;&nbsp;" . $html_item_id .
                "<br><br>" . 
                _g("標準原価") . "&nbsp;&nbsp;" . h($keyCurrency) . "<input type='text' id='totalBaseCost' size=20 readonly style='background-color:#ffccff; text-align:right' value='" . number_format($this->totalBaseCost) . "'>" .
                "<br><br>" . 
                _g("標準手配先が「発注」および「外注(支給無)」の品目の子品目は、構成表マスタで登録されていてもここには含まれていません。");

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
                'width' => '75',
                'align' => 'center',
            ),
            array(
                'label' => _g('員数'),
                'field' => 'quantity',
                'width' => '50',
                'type' => 'numeric',
                'helpText_noEscape' => _g('最上位の品目（算定対象品目）に対する員数です（構成上の親品目に対する員数ではありません）。つまり、' .
                        '算定対象品目ひとつに対して、この品目がいくつ必要になるかをあらわしています。')
            ),
            array(
                'label' => _g('単位'),
                'field' => 'measure',
                'type' => 'data',
                'width' => '50',
                'align' => 'center',
            ),
            array(
                'label' => _g('手配区分'),
                'field' => 'arrangement_class',
                'width' => '100',
                'align' => 'center',
            ),
            array(
                'label' => _g('製造原価'),
                'field' => 'work_amount',
                'width' => '90',
                'type' => 'numeric',
                'helpText_noEscape' => _g('単位') . '：' . h($keyCurrency) . '<br>' . _g('手配区分が「内製」の場合のみ表示されます。') . '<br>' . _g('（品目マスタ「標準加工時間」 * 品目マスタ「工賃」) + 品目マスタ「外製単価」+ 品目マスタ「固定経費」 です。')
            ),
            array(
                'label' => _g('外製工賃'),
                'field' => 'order_price',
                'width' => '90',
                'type' => 'numeric',
                'helpText_noEscape' => _g('単位') . '：' . h($keyCurrency) . '<br>' . _g('手配区分が「外注(支給あり)」の場合のみ表示されます。') . '<br>' . _g('品目マスタ「購入単価1」です。')
            ),
            array(
                'label' => _g('在庫評価単価'),
                'field' => 'stock_price',
                'width' => '90',
                'type' => 'numeric',
                'helpText_noEscape' => _g('単位') . '：' . h($keyCurrency) . '<br>' . _g('手配区分が「発注」もしくは「外注(支給なし)」の場合のみ表示されます。') . '<br>' . _g('品目マスタ「在庫評価単価」です。')
            ),
            array(
                'label' => _g('標準原単価'),
                'field' => 'standard_base_cost',
                'width' => '90',
                'type' => 'numeric',
                'helpText_noEscape' => _g('単位') . '：' . h($keyCurrency) . '<br>' . _g('品目ひとつあたりの標準原価です。内製品は「製造原価」、外製品（支給あり）は「外製工賃」、発注品もしくは外製品（支給なし）は「在庫評価単価」です。')
            ),
            array(
                'label' => _g('原単価合計'),
                'field' => 'total_standard_base_cost',
                'width' => '90',
                'type' => 'numeric',
                'helpText_noEscape' => _g('単位') . '：' . h($keyCurrency) . '<br>' . _g('「員数 * 標準原単価」です。'),
                'colorCondition' => array("#ffcc99" => "true"),
            ),
        );
    }

}