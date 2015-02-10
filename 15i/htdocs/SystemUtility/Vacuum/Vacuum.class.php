<?php

class SystemUtility_Vacuum_Vacuum
{

    function execute(&$form)
    {
        global $gen_db;

        set_time_limit(600);

        $query = "vacuum analyze";
        $gen_db->query($query);

        // データアクセスログ
        Gen_Log::dataAccessLog(_g("バキューム"), "", "");

        $form['gen_done'] = "true";

        return 'action:Menu_Admin';
    }

}