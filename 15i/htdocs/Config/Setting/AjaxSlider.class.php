<?php

class Config_Setting_AjaxSlider extends Base_AjaxBase
{

    function _execute(&$form)
    {
        if (@$form['name'] == '') {
            return;
        }
        $isOpen = (isset($form['isOpen']) && $form['isOpen'] == "true" ? "true" : "false");
        $name = "gen_slider_{$form['name']}";
        @$_SESSION['gen_setting_user']->$name = $isOpen;
        Gen_Setting::saveSetting();

        return;
    }

}