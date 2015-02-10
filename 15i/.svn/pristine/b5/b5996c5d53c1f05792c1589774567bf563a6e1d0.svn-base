<?php

class Config_AdminTaxRate_AjaxTaxRate extends Base_AjaxBase
{

    function _execute(&$form)
    {
        global $gen_db;

        $gen_db->begin();

        // 対象データを取得する
        $count = 0;
        $msg = "";
        foreach ($form as $name => $value) {
            if (substr($name, 0, 6) == "check_") {
                $string = substr($name, 6, strlen($name) - 6);
                $data = explode('___', $string);
                $date = $data[0];
                $rate = $data[1];
                if (isset($date) && Gen_String::isDateString($date) && isset($rate) && is_numeric($rate)) {
                    // データ登録
                    $key = array("apply_date" => $date);
                    $data = array(
                        'tax_rate' => $rate,
                    );
                    $msg .= ($msg != "" ? ", " : "") . "{$date} [{$rate}]";
                    $gen_db->updateOrInsert('tax_rate_master', $key, $data);
                    $count++;
                }
            }
        }

        // データアクセスログ
        Gen_Log::dataAccessLog(_g("消費税率一括登録"), _g("一括登録"), sprintf(_g("%1\$s件（%2\$s：%3\$s）"), $count, _g('適用開始日') . ' [' . _g('税率') . ']', $msg));

        $gen_db->commit();
        
        return
            array(
                'status' => $count == 0 ? 'error' : 'success',
            );
    }

}