<?php

class Gen_Log
{

    static function dataAccessLog($tableName, $classification, $remarks)
    {
        global $gen_db;

        $data = array(
            'table_name' => $tableName,
            'user_name' => !isset($_SESSION['user_name']) ? "*** user_name is null ***" : $_SESSION['user_name'],
            'access_time' => date('Y-m-d H:i:s'),
            'classification' => $classification,
            'remarks' => $remarks,
        );
        $gen_db->insert('data_access_log', $data);
    }

}