<?php

class Config_Personalize_Export
{

    function execute(&$form)
    {
        global $gen_db;
        
        if (!isset($form['userId']) || !Gen_String::isNumeric($form['userId'])) {
            return 'simple.tpl';
        }
        
        $filename = tempnam(GEN_TEMP_DIR, "");
        $allArr = array();
        
        $format = Logic_Personalize::getPersonalizeParams();

        foreach($format as $table => $columns) {
            $colArr = array();
            foreach($columns as $col => $type) {
                $colArr[] = $col;
            }
            $query = "select " . join(",", $colArr) . " from {$table} where user_id = '{$form['userId']}'";
            $arr = $gen_db->getArray($query);
            if ($arr) {
                $allArr[$table] = $arr;
            }
        }
        
        // Setting関連
        if ($form['userId'] == '-1') {
            $query = "select admin_setting from company_master";
        } else {
            $query = "select setting from user_master where user_id = {$form['userId']}";
        }
        $allArr['user_setting'] = $gen_db->queryOneValue($query);

        file_put_contents($filename, json_encode($allArr));
        Gen_Download::DownloadFile($filename, "Gen_15i_Personalize_" . date('Ymd_Hi') . ".gpd");
    }

}