<?php

class Config_AdminTaxRate_TaxRate
{

    function execute(&$form)
    {
        global $gen_db;

        // タブ、マイメニュー用。この画面では画面表示にも使用
        $form['gen_pageTitle'] = _g("消費税率一括登録");
        $form['gen_pageHelp'] = _g("消費税率一括登録");

        $form['addMsg'] = _g("※ 消費税のデフォルト値を指定して登録処理を行ってください。");

        //--------------------------------------------
        //  消費税設定
        //--------------------------------------------
        $form['bgColor'] = '#d5ebff';
        $form['titleMsg'] = _g("消費税デフォルト値");
        $form['ajaxAction'] = 'Config_AdminTaxRate_AjaxTaxRate';

        $taxRateArray = array(
            '1989-04-01' => 3,
            '1997-04-01' => 5,
            '2014-04-01' => 8,
        );

        $form['gen_taxRateArray'] = array();
        $i = 1;
        foreach ($taxRateArray as $key => $value) {
            $query = "select min(apply_date) from tax_rate_master where tax_rate = {$value}";
            $date = $gen_db->queryOneValue($query);
            $remarks = "";
            if (isset($date) && Gen_String::isDateString($date)) {
                $remarks = sprintf(_g("%1\$s は %2\$s で登録されています。"), "{$value}%", $date);
            }
            $form['gen_taxRateArray'][] = array(
                'id' => $key . '___' . $value,
                'date' => $key,
                'value' => $value,
                'remarks' => $remarks
            );
        }

        return 'config_admintaxrate_taxrate.tpl';
    }

}