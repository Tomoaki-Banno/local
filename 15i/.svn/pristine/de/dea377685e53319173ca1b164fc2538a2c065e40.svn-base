<?php
class Mobile_PartnerOrder_Detail
{
    function execute(&$form)
    {
        global $gen_db;

        $form['gen_pageTitle'] = _g("注文書明細");
        $form['gen_headerLeftButtonURL'] = "index.php?action=Mobile_PartnerOrder_List";
        $form['gen_headerLeftButtonIcon'] = "arrow-l";
        $form['gen_headerLeftButtonText'] = _g("戻る");

        $form['gen_headerRightButtonURL'] = "index.php?action=Partner_Order_Report&detail=true&check_" . $form['order_detail_id'];
        $form['gen_headerRightButtonIcon'] = "";
        $form['gen_headerRightButtonText'] = _g("印刷(PDF)");
        $form['gen_headerRightButtonParam'] = "target='_blank' data_ajax='false'";  // 印刷のときはこの設定が必須

        if (!isset($form['order_header_id'])) {
            if (isset($form['order_detail_id'])) {
                $query = "select order_header_id from order_detail where order_detail_id = '{$form['order_detail_id']}'";
                $form['order_header_id'] = $gen_db->queryOneValue($query);
            } else {
                return "action:Mobile_PartnerOrder_List";
            }
        }

        // カスタム項目
        $customSelectList = "";
        if (isset($form['gen_customColumnArray'])) {
            foreach($form['gen_customColumnArray'] as $customCol => $customArr) {
                $customSelectList .= ",{$form['gen_customColumnTable']}.{$customCol} as gen_custom_{$customCol}";
            }
        }

        $keyCurrency = $gen_db->queryOneValue("select key_currency from company_master");
        $query = "
            select
                order_header.*
                ,customer_master.customer_name
                ,t_delivery_customer.customer_name as delivery_customer_name
                ,section_name
                ,worker_name
                {$customSelectList}
            from
                order_header
                inner join order_detail on order_header.order_header_id = order_detail.order_header_id
                left join customer_master on order_header.partner_id = customer_master.customer_id
                left join customer_master as t_delivery_customer on order_header.delivery_partner_id = t_delivery_customer.customer_id
                left join section_master on order_header.section_id = section_master.section_id
                left join worker_master on order_header.worker_id = worker_master.worker_id
            where
                order_header.order_header_id = '{$form['order_header_id']}'
            ";
        $form['gen_data'] = $gen_db->getArray($query);

        $form['gen_columnArray'] =
            array(
                array(
                    'label'=>_g("注文書番号"),
                    'field'=>'order_id_for_user',
                ),
                array(
                    'label'=>_g("発注日"),
                    'field'=>'order_date',
                ),
                array(
                    'label'=>_g("発注先"),
                    'field'=>'customer_name',
                ),
                array(
                    'label'=>_g("発送先"),
                    'field'=>'delivery_customer_name',
                ),
                array(
                    'label'=>_g("部門(自社)"),
                    'field'=>'section_name',
                ),
                array(
                    'label'=>_g("担当者(自社)"),
                    'field'=>'worker_name',
                ),
                array(
                    'label'=>_g("注文備考"),
                    'field'=>'remarks_header',
                ),
            );

        $query = "
             SELECT
                order_detail.*
                ,received_number
                ,order_detail_quantity / cast(coalesce(order_detail.multiple_of_order_measure,1) as numeric) as show_quantity
                ,(case when foreign_currency_id is null then item_price else foreign_currency_item_price end) as item_price
                ,(case when foreign_currency_id is null then item_price else foreign_currency_item_price end) * cast(coalesce(order_detail.multiple_of_order_measure,1) as numeric) as show_price
                ,(case when foreign_currency_id is null then coalesce(order_amount, item_price * order_detail_quantity) else coalesce(foreign_currency_order_amount, foreign_currency_item_price * order_detail_quantity) end) as amount
                ,case when t_acc.odi is null then 0 else 1 end as accepted_exist
                ,case when order_detail_completed then '" . _g("完") . "' else '" . _g("未(残") . " ' || (COALESCE(order_detail_quantity,0) - COALESCE(order_detail.accepted_quantity,0)) || ')' end as completed
                ,coalesce(cast(default_lot_unit as text),'-') || ' / ' || coalesce(cast(default_lot_unit_2 as text),'-') as lot_unit
                ,case tax_class when 1 then '" . _g("非課税") . "' else '" . _g("課税") . "' end as tax_class
                ,order_class
                ,case when currency_name is null then '{$keyCurrency}' else currency_name end as currency_name

             FROM order_detail
                LEFT JOIN (select seiban as s2, received_number from received_detail inner join received_header on received_header.received_header_id=received_detail.received_header_id) as t_rec on order_detail.seiban = t_rec.s2 and order_detail.seiban <> ''
                LEFT JOIN (select order_detail_id as odi from accepted group by order_detail_id) as t_acc on order_detail.order_detail_id = t_acc.odi
                LEFT JOIN (select item_id as iid, measure, order_class from item_master) as t_item on order_detail.item_id = t_item.iid
                LEFT JOIN (select order_header_id as oid, partner_id from order_header) as t_order_header on order_detail.order_header_id = t_order_header.oid
                LEFT JOIN (select item_id as iid2, order_user_id as oui, default_lot_unit, default_lot_unit_2 from item_order_master) as t_item_order
                    on order_detail.item_id = t_item_order.iid2
                    and t_order_header.partner_id = t_item_order.oui
                LEFT JOIN (select currency_id as curid, currency_name from currency_master) as t_currency on order_detail.foreign_currency_id = t_currency.curid

             WHERE
                order_header_id = '{$form['order_header_id']}'
             ORDER BY
                line_no
            ";
        $form['gen_detailData'] = $gen_db->getArray($query);

        $form['gen_detailColumnArray'] =
            array(
                array(
                    'label'=>_g("ｵｰﾀﾞｰ番号"),
                    'field'=>'order_no',
                ),
                array(
                    'label'=>_g("品目コード"),
                    'field'=>'item_code',
                ),
                array(
                    'label'=>_g("品目名"),
                    'field'=>'item_name',
                ),
                array(
                    'label'=>_g("数量"),
                    'field'=>'order_detail_quantity',
                    'numberFormat'=>'true',
                ),
                array(
                    'label'=>_g("表示数量"),
                    'field'=>'show_quantity',
                    'numberFormat'=>'true',
                ),
                array(
                    'label'=>_g("単位"),
                    'field'=>'order_measure',
                ),
                array(
                    'label'=>_g("倍数"),
                    'field'=>'multiple_of_order_measure',
                    'numberFormat'=>'true',
                ),
                array(
                    'label'=>_g("発注単価"),
                    'preLabel'=>$keyCurrency,
                    'field'=>'order_price',
                    'numberFormat'=>'true',
                ),
                array(
                    'label'=>_g("表示単価"),
                    'preField'=>'currency_name',
                    'field'=>'show_price',
                    'numberFormat'=>'true',
                ),
                array(
                    'label'=>_g("金額"),
                    'preLabel'=>$keyCurrency,
                    'field'=>'amount',
                    'numberFormat'=>'true',
                ),
                array(
                    'label'=>_g("課税区分"),
                    'field'=>'tax_class',
                ),
                array(
                    'label'=>_g("注文納期"),
                    'field'=>'order_detail_dead_line',
                ),
                array(
                    'label'=>_g("受入状況"),
                    'field'=>'completed',
                ),
                array(
                    'label'=>_g("最低/手配ﾛｯﾄ"),
                    'field'=>'lot_unit',
                ),
                array(
                    'label'=>_g("製番"),
                    'field'=>'seiban',
                ),
                array(
                    'label'=>_g("注文備考"),
                    'field'=>'remarks',
                ),
            );
        
        // カスタム項目
        if (isset($form['gen_customColumnArray'])) {
            foreach($form['gen_customColumnArray'] as $customCol => $customArr) {
                $form['gen_columnArray'][] =
                    array(
                        'label' => $customArr[1],
                        'field' => "gen_custom_{$customCol}",
                    );
            }
        }

        return 'mobile_detail.tpl';
    }
}