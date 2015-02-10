<?php

class Mobile_PartnerOrder_List extends Base_MobileListBase
{

    function setSearchCondition(&$form)
    {
        global $gen_db;
        // 検索条件
        $query = "select item_group_id, item_group_name from item_group_master order by item_group_code";
        $option_item_group = $gen_db->getHtmlOptionArray($query, false, array(null => _g("(すべて)")));

        $form['gen_searchControlArray'] = array(
            array(
                'label' => _g('注文書番号'),
                'field' => 'order_id_for_user',
            ),
            array(
                'label' => _g('オーダー番号'),
                'field' => 'order_no',
            ),
            array(
                'label' => _g('発注先'),
                'type' => 'textbox',
                'field' => 'customer_no',
                'field2' => 'customer_name',
            ),
            array(
                'label' => _g('品目'),
                'type' => 'textbox',
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
                'label' => _g('完了分の表示'),
                'type' => 'select',
                'field' => 'completed_status',
                'options' => array("false" => _g("表示しない"), "true" => _g("表示する")), // 「しない」時は order_detail_completed = false のレコードに限定
                'nosql' => 'true',
                'default' => 'false',
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

        $this->isDetailMode = true;

        // 親品目が指定されている場合はtemp_bom_expandテーブルを準備
        if (is_numeric(@$form['gen_search_parent_item_id'])) {
            Logic_Bom::expandBom($form['gen_search_parent_item_id'], 0, false, false, false);
        }

        $this->selectQuery = "
             SELECT
                order_header.order_header_id
        ";
        if ($this->isDetailMode) {
            // 明細モード
            $this->selectQuery .= "
                    ,order_detail.order_detail_id
                    ,line_no
                    ,case when order_printed_flag = true then '" . _g("印刷済") . "' else '' end as printed
                    ,order_id_for_user
                    ,order_date
                    ,customer_master.customer_no
                    ,customer_name
                    ,delivery_partner_no
                    ,delivery_partner_name
                    ,order_header.remarks_header
                    ,worker_code
                    ,worker_name
                    ,section_code
                    ,section_name
                    ,order_no
                    ,seiban
                    ,received_number
                    ,item_code
                    ,item_name
                    ,item_price
                    ,maker_name
                    ,spec
                    ,rack_no
                    ,item_sub_code
                    ,comment
                    ,order_detail_quantity
                    ,order_measure
                    ,order_detail_quantity / coalesce(multiple_of_order_measure,1) as show_quantity
                    ,gen_round_precision(item_price * coalesce(multiple_of_order_measure,1), customer_master.rounding, customer_master.precision) as show_price
                    ,multiple_of_order_measure
                    ,coalesce(order_amount, item_price * order_detail_quantity) as amount
                    ,currency_name
                    ,foreign_currency_rate
                    ,foreign_currency_item_price
                    ,gen_round_precision(foreign_currency_item_price * coalesce(multiple_of_order_measure,1), customer_master.rounding, customer_master.precision) as foreign_currency_show_price
                    ,coalesce(foreign_currency_order_amount, foreign_currency_item_price * order_detail_quantity) as foreign_currency_order_amount
                    ,order_detail_dead_line
                    ,coalesce(accepted_quantity,0) as accepted_quantity
                    ,case when completed_status = 1 then '" . _g("完") . "' else
                      '" . _g("未(残") . " ' || (COALESCE(order_detail_quantity,0) - COALESCE(accepted_quantity,0)) || ')' END as completed
                    ,case when t_acc.odi is null then 0 else 1 end as accepted_exist
                    ,case when alarm_flag then 't' else 'f' end as alarm_flag
                    ,order_detail.remarks

                    ,coalesce(order_detail.record_update_date, order_detail.record_create_date) as gen_record_update_date
                    ,coalesce(order_detail.record_updater, order_detail.record_creator) as gen_record_updater

                    -- for csv
                    ,case when foreign_currency_id is null then item_price else foreign_currency_item_price end as item_price_for_csv

                ";
        } else {
            // 通常（ヘッダ）モード
            $this->selectQuery .= "
                    ,count(order_detail.*) as detail_count
                    ,max(order_id_for_user) as order_id_for_user
                    ,max(customer_master.customer_no) as customer_no
                    ,max(customer_name) as customer_name
                    ,max(delivery_partner_no) as delivery_partner_no
                    ,max(delivery_partner_name) as delivery_partner_name
                    ,max(order_date) as order_date
                    ,max(order_header.remarks_header) as remarks_header
                    ,max(worker_code) as worker_code
                    ,max(worker_name) as worker_name
                    ,max(section_code) as section_code
                    ,max(section_name) as section_name
                    ,max(CASE WHEN order_printed_flag = true THEN '" . _g("印刷済") . "' ELSE '' END ) as printed
                    ,case when min(completed_status) = 1 then  '" . _g("完") . "' else
                      '" . _g("未(残") . " ' || (COALESCE(sum(order_detail_quantity),0) - COALESCE(sum(accepted_quantity),0)) || ')' END as completed
                    ,coalesce(sum(accepted_quantity),0) as accepted_quantity
                    ,max(case when t_acc.odi is null then 0 else 1 end) as accepted_exist
                    ,max(case when alarm_flag then 't' else 'f' end) as alarm_flag

                    ,max(coalesce(order_detail.record_update_date, order_detail.record_create_date)) as gen_record_update_date
                    ,max(coalesce(order_detail.record_updater, order_detail.record_creator)) as gen_record_updater

                ";
        }
        $this->selectQuery .= "
             FROM
                order_header
                LEFT JOIN customer_master ON order_header.partner_id = customer_master.customer_id
                LEFT JOIN (select customer_id as cid, customer_no as delivery_partner_no, customer_name as delivery_partner_name from customer_master) as t_delivery_partner ON order_header.delivery_partner_id = t_delivery_partner.cid
                INNER JOIN order_detail on order_header.order_header_id = order_detail.order_header_id
                LEFT JOIN worker_master ON order_header.worker_id = worker_master.worker_id
                LEFT JOIN section_master ON order_header.section_id = section_master.section_id
                LEFT JOIN currency_master ON order_detail.foreign_currency_id = currency_master.currency_id
                LEFT JOIN (select item_id as iid, item_group_id, item_group_id_2, item_group_id_3, maker_name, spec, rack_no, comment from item_master) as t_item on order_detail.item_id = t_item.iid
                " . ($this->isDetailMode ?
                        "    INNER JOIN (select order_detail_id as oid,
                           (case when order_detail_completed then 1
                           else 0 end) as completed_status from order_detail) as t0
                           on order_detail.order_detail_id = t0.oid
                    " :
                        "    INNER JOIN (select order_header_id as oid,
                           min(case when order_detail_completed then 1
                           else 0 end) as completed_status from order_detail group by order_header_id) as t0
                           on order_header.order_header_id = t0.oid
                    "
                ) . "
                LEFT JOIN (select seiban as s2, received_number from received_detail inner join received_header on received_header.received_header_id=received_detail.received_header_id) as t_rec on order_detail.seiban = t_rec.s2 and order_detail.seiban <> ''
                LEFT JOIN (select order_detail_id as odi from accepted group by order_detail_id) as t_acc on order_detail.order_detail_id = t_acc.odi
                " . (is_numeric(@$form['gen_search_parent_item_id']) ?
                        " INNER JOIN (select item_id as exp_item_id from temp_bom_expand group by item_id) as t_exp on order_detail.item_id = t_exp.exp_item_id " : "") . "
             [Where]
                and order_header.classification=1
                /* 所要量計算の結果取込モードの場合。取り込まれたデータのみを表示 */
                " . (isset($form['mrp']) && $orderHeaderIdList == "" ? " and 1=0" : "") . "
                " . (isset($form['mrp']) && $orderHeaderIdList != "" ? " and order_header.order_header_id in ($orderHeaderIdList)" : "") . "
                " . (@$form['gen_search_completed_status'] == "false" ? " and completed_status = 0" : "") . "
             	" . (@$form['gen_search_printed'] == '1' ? ' and not coalesce(order_printed_flag,false)' : '') . "
             	" . (@$form['gen_search_printed'] == '2' ? ' and order_printed_flag' : '') . "

             " . ($this->isDetailMode ? "" : " GROUP BY order_header.order_header_id") . "
             [Orderby]
            ";

        $this->orderbyDefault = 'order_id_for_user desc';
        if ($this->isDetailMode) {
            $this->orderbyDefault .= ",line_no";
        }
    }

    function setViewParam(&$form)
    {
        global $gen_db;

        $this->tpl = "mobile/list.tpl";

        $form['gen_pageTitle'] = _g("注文書リスト");
        $form['gen_listAction'] = "Mobile_PartnerOrder_List";
        $form['gen_linkAction'] = "Mobile_PartnerOrder_Detail";
        $form['gen_idField'] = ($this->isDetailMode ? 'order_detail_id' : 'order_header_id');

        if ($this->isDetailMode) {
            $form['gen_sumColumnArray'] = array("注文金額：" => "amount");
        }

        $keyCurrency = $gen_db->queryOneValue("select key_currency from company_master");
        $form['gen_columnArray'] = array(
            array(
                'sortLabel' => _g('注文書番号'),
                'label' => "",
                'field' => 'order_id_for_user',
                'fontSize' => 12,
                'after_noEscape' => ' / ',
            ),
            array(
                'sortLabel' => _g('オーダー番号'),
                'label' => "",
                'field' => 'order_no',
                'fontSize' => 12,
                'after_noEscape' => '<br>',
            ),
            array(
                'sortLabel' => _g('発注先名'),
                'label' => "",
                'field' => 'customer_name',
                'fontSize' => 14,
                'after_noEscape' => '<br>',
            ),
            array(
                'sortLabel' => _g('品目名'),
                'field' => 'item_name',
                'fontSize' => 15,
                'after_noEscape' => '<br>',
            ),
            array(
                'sortLabel' => _g('数量'),
                'label' => _g('数量'),
                'field' => 'order_detail_quantity',
                'type' => 'numeric', // aggregateのために必要
                'labelFontSize' => 12,
                'fontSize' => 12,
                'labelStyle' => 'color:#999999;',
                'numberFormat' => true,
                'after_noEscape' => '&nbsp;&nbsp;',
            ),
            array(
                'sortLabel' => _g('金額'),
                'label' => sprintf(_g('金額　%s'), $keyCurrency),
                'field' => 'amount',
                'type' => 'numeric', // aggregateのために必要
                'labelFontSize' => 12,
                'fontSize' => 12,
                'labelStyle' => 'color:#999999;',
                'numberFormat' => true,
                'after_noEscape' => '<br>',
            ),
            array(
                'sortLabel' => _g('発注日'),
                'label' => _g('発注'),
                'field' => 'order_date',
                'labelFontSize' => 12,
                'fontSize' => 12,
                'labelStyle' => 'color:#999999;',
                'after_noEscape' => '&nbsp;&nbsp;',
            ),
            array(
                'sortLabel' => _g('注文納期'),
                'label' => _g('注文納期'),
                'field' => 'order_detail_dead_line',
                'labelFontSize' => 12,
                'fontSize' => 12,
                'labelStyle' => 'color:#999999;',
                'after_noEscape' => '',
            ),
        );
    }

}