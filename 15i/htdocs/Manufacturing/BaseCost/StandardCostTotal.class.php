<?php

class Manufacturing_BaseCost_StandardCostTotal extends Base_ListBase
{

    var $totalBaseCost;
    var $existCond;

    function setSearchCondition(&$form)
    {
        global $gen_db;

        $query = "select item_group_id, item_group_name from item_group_master order by item_group_code";
        $option_item_group = $gen_db->getHtmlOptionArray($query, false, array(null => _g("(なし)")));

        $form['gen_searchControlArray'] = array(
            array(
                'label' => _g('品目コード/名'),
                'field' => 'item_code',
                'field2' => 'item_name',
                'nosql' => true,
            ),
            array(
                'label' => _g('手配区分'),
                'field' => 'partner_class',
                'type' => 'select',
                'options' => Gen_Option::getPartnerClass('search'),
                'nosql' => true,
            ),
            array(
                'label' => _g('管理区分'),
                'field' => 'order_class',
                'type' => 'select',
                'options' => Gen_Option::getOrderClass('search'),
                'nosql' => true,
            ),
            array(
                'label' => _g('品目グループ'),
                'field' => 'item_group_id',
                'type' => 'select',
                'options' => $option_item_group,
                'nosql' => true,
            ),
        );
    }

    function convertSearchCondition($converter, &$form)
    {
    }

    function beforeLogic(&$form)
    {
        $this->existCond =
            Logic_BaseCost::calcStandardBaseCostTotal(
                @$form['gen_search_item_code']
                , @$form['gen_search_match_mode_gen_search_item_code']
                , @$form['gen_search_item_group_id']
                , @$form['gen_search_partner_class']
                , @$form['gen_search_order_class']
        );
    }

    function setQueryParam(&$form)
    {
        $classQuery1 = Gen_Option::getOrderClass('list-query');
        $classQuery2 = Gen_Option::getPartnerClass('list-query');
        
        $this->selectQuery = "
            select
                t01.item_id
                ,t02.item_code
                ,t02.item_name
                ,case t02.order_class {$classQuery1} end as order_class
                ,t01.item_cost
                ,measure
                ,t03.partner_class
                 /* 内製品の判断は手配区分で */
                ,case t03.partner_class {$classQuery2} end as partner_class_show
                 /* 1品目1工程ではなくなったため、加工時間と工賃を分けて表示することができなくなった。 */
                 /* 別項目で「製造金額」として加工時間*工賃を表示するようにした。 */
                 /* 品目別ではなく工程別に表示するようにすれば分けて表示できるが、とりあえず今回はこの形に。 */
                ,(case when t03.partner_class <> 3 then t02.stock_price else 0 end) as stock_price
                ,(case when t03.partner_class = 3 then work_amount else 0 end) as work_amount
                ,(case when t03.partner_class = 3 then work_amount else t02.stock_price end) as standard_base_cost
            from
                temp_standard_cost_total as t01
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
                    ) as t02 on t01.item_id = t02.id
                left join (
                    select
                        item_id as id,
                        order_user_id,
                        default_order_price,
                        partner_class
                    from
                        item_order_master
                    where
                        line_number=0
                    ) as t03 on t01.item_id = t03.id
            [Where]
            [Orderby]
        ";

        $this->orderbyDefault = 'item_code';
    }

    function setViewParam(&$form)
    {
        $form['gen_pageTitle'] = _g("標準原価リスト");
        $form['gen_menuAction'] = "Menu_Manufacturing";
        $form['gen_listAction'] = "Manufacturing_BaseCost_StandardCostTotal";
        $form['gen_idField'] = 'item_id';
        $form['gen_excel'] = "true";
        $form['gen_pageHelp'] = _g("標準原価");

        $form['gen_titleRowHeight'] = 50;

        $form['gen_returnUrl'] = "index.php?action=Manufacturing_BaseCost_List&gen_restore_search_condition=true";
        $form['gen_returnCaption'] = _g('原価リストへ戻る');

        if (!$this->existCond) {
            $form['gen_message_noEscape'] = "<font color='red'>" . _g("表示条件を指定してください。") . "</font>";
        }

        $form['gen_fixColumnArray'] = array(
            array(
                'label' => _g('品目コード'),
                'field' => 'item_code',
                'width' => '150',
            ),
            array(
                'label' => _g('品目名'),
                'field' => 'item_name',
                'width' => '250',
            ),
        );
        $form['gen_columnArray'] = array(
            array(
                'label' => _g('手配区分'),
                'field' => 'partner_class_show',
                'width' => '110',
                'align' => 'center',
            ),
            array(
                'label' => _g('管理区分'),
                'field' => 'order_class',
                'width' => '80',
                'align' => 'center',
            ),
            array(
                'label' => _g('単位'),
                'field' => 'measure',
                'type' => 'data',
                'width' => '50',
                'align' => 'center',
            ),
            array(
                'label' => _g('標準原価'),
                'field' => 'item_cost',
                'width' => '100',
                'type' => 'numeric',
            ),
        );
    }

}
