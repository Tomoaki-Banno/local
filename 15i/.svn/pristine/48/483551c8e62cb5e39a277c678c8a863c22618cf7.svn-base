<?php

class Config_AdminPriceHistory_List extends Base_ListBase
{

    function setSearchCondition(&$form)
    {
        global $gen_db;

        $query = "select item_group_id, item_group_name from item_group_master order by item_group_code";
        $option_item_group = $gen_db->getHtmlOptionArray($query, false, array(null => _g("(すべて)")));

        $form['gen_searchControlArray'] = array(
            array(
                'label' => _g('日付'),
                'type' => 'dateFromTo',
                'field' => 'assessment_date',
                'size' => '80',
                'rowSpan' => 2,
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
            ),
            array(
                'label' => _g('手配区分'),
                'type' => 'select',
                'field' => 'partner_class',
                'options' => Gen_Option::getPartnerClass('search'),
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
        $classQuery = Gen_Option::getPartnerClass('list-query');

        $this->selectQuery = "
            select
                assessment_date
                ,item_id
                ,item_code
                ,item_name
                ,stock_price
                ,stock_quantity
                ,partner_class
                ,case partner_class {$classQuery} end as partner_class_show
            from
                stock_price_history
                inner join (select item_id as iid, item_code, item_name, item_group_id, item_group_id_2, item_group_id_3
                    from item_master) as t_item on stock_price_history.item_id = t_item.iid
                left join (select item_id as iid, order_user_id, partner_class
                    from item_order_master where line_number=0) as t_order on stock_price_history.item_id = t_order.iid
            [Where]
            [Orderby]
        ";

        $this->orderbyDefault = 'assessment_date, item_code';
    }

    function setViewParam(&$form)
    {
        $form['gen_pageTitle'] = _g("評価単価履歴一覧");
        $form['gen_menuAction'] = "Menu_Config";
        $form['gen_listAction'] = "Config_AdminPriceHistory_List";
        $form['gen_editAction'] = "";
        $form['gen_deleteAction'] = "";
        $form['gen_idField'] = 'item_id';
        $form['gen_excel'] = "true";

        $form['gen_columnArray'] = array(
            array(
                'label' => _g('日付'),
                'field' => 'assessment_date',
                'type' => 'date',
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
                'label' => _g('手配区分'),
                'field' => 'partner_class_show',
                'width' => '100',
                'align' => 'center',
            ),
            array(
                'label' => _g('単価'),
                'field' => 'stock_price',
                'type' => 'numeric',
            ),
            array(
                'label' => _g('在庫数'),
                'field' => 'stock_quantity',
                'type' => 'numeric',
            ),
        );
    }

}
