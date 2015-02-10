<?php

class Config_Setting_AjaxDirectEdit extends Base_AjaxBase
{
    function _execute(&$form)
    {
        if (isset($form['directEdit'])) {
            $_SESSION['gen_setting_user']->directEdit = $form['directEdit'];
            Gen_Setting::saveSetting();
        }

        return;
    }
}