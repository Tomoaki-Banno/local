<?php

class Dropdown_AutoCompleteData
{

    function execute(&$form)
    {
        global $gen_db;

        if (!isset($form['category']))
            return 'simple.tpl';
        if (!isset($form['query'])) // YUIで自動的に付加される
            return 'simple.tpl';

        // SQL Injection対策
        if (!Gen_String::checkSQLInjection(@$form['query']))
            return 'simple.tpl';
        if (!Gen_String::checkSQLInjection(@$form['where']))
            return 'simple.tpl';

        switch($form['category']) {
            case 'customer_received':
            case 'customer_partner':
            case 'customer_received_shipping':
            case 'customer_partner_shipping':
                $query = "select customer_no || ' :  ' || customer_name as show from customer_master 
                    where (customer_no ilike '%{$form['query']}%' or customer_name ilike '%{$form['query']}%') 
                        and customer_id <> 0 and classification in (";
                    switch($form['category']) {
                        case 'customer_received': $query .= "0"; break;
                        case 'customer_partner': $query .= "1"; break;
                        case 'customer_received_shipping': $query .= "0,2"; break;
                        case 'customer_partner_shipping': $query .= "1,2"; break;
                    }
                $query .= ") and not coalesce(end_customer, false) ";    // 非表示取引先は表示しない
                $query .=(@$form['where'] == "" ? "" : " and {$form['where']}");
                break;
            case 'item_received':
                $query = "select item_code || ' :  ' || item_name as show from item_master where (item_code ilike '%{$form['query']}%' or item_name ilike '%{$form['query']}%') and received_object = 0 ";
                //$query = "select item_code as show from item_master where item_code ilike '{$form['query']}%' and received_object = 0 ";
                $query .= " and not coalesce(end_item, false) ";    // 非表示品目は表示しない
                $query .=(@$form['where'] == "" ? "" : " and {$form['where']}");
                break;
            case 'item_order_partner':
                $query = "select item_code || ' :  ' || max(item_name) as show from item_master 
                    inner join item_order_master on item_master.item_id=item_order_master.item_id
                    where (item_code ilike '%{$form['query']}%' or item_name ilike '%{$form['query']}%') 
                        and partner_class = 0 ";
                $query .= " and not coalesce(end_item, false) ";    // 非表示品目は表示しない
                $query .=(@$form['where'] == "" ? "" : " and {$form['where']}");
                $query .= " group by item_code";
                break;
            case 'item_order_manufacturing':
                // where条件の意味については Manufacturing_Order_Edit の品目部分のコメントを参照
                $query = "select item_code || ' :  ' || item_name as show from item_master 
                    inner join item_order_master on item_master.item_id=item_order_master.item_id
                    where (item_code ilike '%{$form['query']}%' or item_name ilike '%{$form['query']}%') 
                        and (partner_class=1 or partner_class=2 or partner_class=3) and not coalesce(item_master.dummy_item,false) and line_number=0 ";
                $query .= " and not coalesce(end_item, false) ";    // 非表示品目は表示しない
                $query .=(@$form['where'] == "" ? "" : " and {$form['where']}");
                break;
            case 'item_order_subcontract':
                $query = "select item_code || ' :  ' || max(item_name) as show from item_master 
                    inner join item_order_master on item_master.item_id=item_order_master.item_id
                    where (item_code ilike '%{$form['query']}%' or item_name ilike '%{$form['query']}%') 
                        and (partner_class=1 or partner_class=2 or partner_class=3) and not coalesce(item_master.dummy_item,false)";
                $query .= " and not coalesce(end_item, false) ";    // 非表示品目は表示しない
                $query .=(@$form['where'] == "" ? "" : " and {$form['where']}");
                $query .= " group by item_code";
                break;
            default:
        }
        
        $query .= " order by show limit 20";

        $arr = $gen_db->getArray($query);
        $res = "";
        if (is_array($arr)) {
            foreach ($arr as $row) {
                if ($res != "") $res .= ",";                
                $show = str_replace("\\", "\\\\", $row['show']);
                $show = str_replace("\"", "\\\"", $show);
                $res .= "\"" . $show . "\"";
            }
        }
        
        // json_encodeしたデータではうまくいかない。
        // HTMLエスケープはクライアント側で行われる。
        $form['response_noEscape'] = "[{$res}]";
        return 'simple.tpl';
    }

}