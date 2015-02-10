<?php

class Manufacturing_Achievement_AjaxWorkerParam extends Base_AjaxBase
{

    function _execute(&$form)
    {
        global $gen_db;

        if (!isset($form['worker_id']) || !is_numeric(@$form['worker_id']))
            return;

        $query = "select section_id from worker_master where worker_id = '{$form['worker_id']}'";
        $sectionId = $gen_db->queryOneValue($query);

        return
            array(
                'section_id' => $sectionId,
            );
    }

}