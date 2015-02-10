<?php

class Mobile_Common_AjaxCustomerParam extends Base_AjaxBase
{
    function _execute(&$form)
    {
        global $gen_db;
        
        if (!isset($form['search']) || $form['search']=="")
            return;
        
        // like用のエスケープ
        $search = str_replace('%', '\\\\%', $form['search']);
        
        $query = "select customer_no, customer_name from customer_master 
            where not coalesce(customer_master.end_customer, false) -- 非表示取引先は表示しない
                and (customer_no ilike '%{$search}%' or customer_name ilike '%{$search}%')
            " . (isset($form['received']) ? " and classification = 0" : "") . "
            order by customer_no
            limit 10
            ";
        $arr = $gen_db->getArray($query);
        
        $obj = array();
        if ($arr!="") {
            foreach($arr as $row) {
                $obj[$row['customer_no']] = $row['customer_name'];
            }
        }
        return $obj;
    }
}