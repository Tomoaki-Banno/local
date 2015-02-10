<?php

class Config_Setting_AjaxListClickEnable extends Base_AjaxBase
{

    function _execute(&$form)
    {
        $_SESSION['gen_setting_user']->listClickEnable = (isset($form['listClickEnable']) && $form['listClickEnable'] == "true" ? 'true' : 'false');
        Gen_Setting::saveSetting();
        return;
    }

}