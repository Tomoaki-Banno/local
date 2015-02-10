<?php

class Mobile_Common_AjaxItemParam extends Base_AjaxBase
{
    function _execute(&$form)
    {
        global $gen_db;
        
        if (!isset($form['search']) || $form['search']=="")
            return;
        
        // like用のエスケープ
        $search = str_replace('%', '\\\\%', $form['search']);
        
        $query = "select item_code, item_name from item_master 
            where not coalesce(item_master.end_item, false) -- 非表示品目は表示しない
                and (item_code ilike '%{$search}%' or item_name ilike '%{$search}%')
            " . (isset($form['received']) ? " and received_object = 0" : "") . "
            order by item_code
            limit 10
            ";
        $arr = $gen_db->getArray($query);
        
        $obj = array();
        if ($arr!="") {
            foreach($arr as $row) {
                $obj[$row['item_code']] = $row['item_name'];
            }
        }
        return $obj;
    }
}