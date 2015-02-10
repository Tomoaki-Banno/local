<?php

class Master_Customer_AjaxBillAlarm extends Base_AjaxBase
{

    // 未請求の納品データの請求条件と整合がとれていなければ
    // 警告文を返す（整合がとれていれば'success:'のみを返す）

    function _execute(&$form)
    {
        global $gen_db;

        // 得意先id
        if (!isset($form['customer_id']) || !is_numeric($form['customer_id']))
            return;

        // ******** 最終請求情報の計算（テンポラリテーブル temp_last_close & temp_delivery_base） ********
        Logic_Bill::createTempLastCloseTable(date('2037-12-31'), array($form['customer_id']));     // 2038年以降は日付と認識されない

        // 納品データの異なる請求条件のデータ確認
        $query = "
        select
            customer_id
        from
            temp_delivery_base
        where
            customer_id = {$form['customer_id']}
            and (rounding <> '{$form['rounding']}'
            or precision <> '{$form['precision']}'
            or tax_category <> '{$form['tax_category']}'
            or bill_pattern <> '{$form['bill_pattern']}')
        ";
        $alert = 0;
        if ($gen_db->existRecord($query)) {
            $alert = 1;
        }

        return
            array(
                'alert' => $alert,
            );
    }

}