<?php

class Config_Setting_AjaxDemoMode extends Base_AjaxBase
{

    function _execute(&$form)
    {
        $_SESSION['gen_setting_user']->demo_mode = (!isset($_SESSION['gen_setting_user']->demo_mode) || !$_SESSION['gen_setting_user']->demo_mode ? true : false);
        Gen_Setting::saveSetting();
        
        return 
            array(
                "state" => ($_SESSION['gen_setting_user']->demo_mode ? "on" : "off")
            );
    }

}