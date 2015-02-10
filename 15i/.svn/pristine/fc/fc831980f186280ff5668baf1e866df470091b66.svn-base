<?php

class Config_DataDelete_List
{

    function execute(&$form)
    {
        // タブ、マイメニュー用
        $form['gen_pageTitle'] = _g("過去データ削除");

        $form['bgColor'] = '#f5f5f5';

        $form['startup_date_year'] = date('Y-m-d', Logic_SystemDate::getYearStartDate());

        $start_date = Logic_SystemDate::getStartDate();
        $form['current_year'] = date('Y', $start_date);
        $form['current_month'] = date('m', $start_date);

        return 'config_datadelete_list.tpl';
    }

}