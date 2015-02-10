<?php

class Config_Background_AjaxImageParam extends Base_AjaxBase
{

    function _execute(&$form)
    {
        global $gen_db;

        if (!isset($_SESSION['user_id']) || !is_numeric($_SESSION['user_id']))
            return;

        $query = "select background_image from user_master where user_id = {$_SESSION['user_id']}";
        $res = $gen_db->queryOneValue($query);

        $image = rtrim($res, ";");
        if ($image != "") {
            return array(
                'image' => $image,
            );
        } else {
            return;
        }
    }

}
