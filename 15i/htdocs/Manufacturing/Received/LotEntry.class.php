<?php

class Manufacturing_Received_LotEntry
{
    function convert($converter, &$form)
    {
    }

    function validate($validator, &$form)
    {
    }

    function execute(&$form)
    {
        global $gen_db;

        if (!Gen_String::isDateString($changeDate = @$form['change_date'])) {
            return 'simple.tpl';
        }
        if (!Gen_String::isNumeric($locationId = @$form['location_id'])) {
            return 'simple.tpl';
        }
        if (($distSeiban = @$form['dist_seiban'])=='') {
            return 'simple.tpl';
        }
        $query = "select item_id from received_detail where seiban = '$distSeiban'";
        $itemId = $gen_db->queryOneValue($query);
        if (!Gen_String::isNumeric($itemId)) {
            return 'simple.tpl';
        }

        // トランザクション開始
        $gen_db->begin();

       // 引当処理
        foreach ($form as $name=>$value) {
            if (substr($name, 0, 16) == "change_quantity_") {
                $seiban = substr($name, 16, strlen($name)-16);
                if (!Gen_String::isNumeric($seiban) && $seiban!='') continue;
                if (!Gen_String::isNumeric($value)) continue;

                // 製番引当の登録
                Logic_SeibanChange::entrySeibanChange(
                    null,
                    $changeDate,
                    $itemId,
                    $seiban,
                    $distSeiban,
                    $locationId,
                    0,  // lot
                    $value,
                    'ロット別引当登録');
                }
        }

        $gen_db->commit();

        $form['gen_restore_search_condition'] = 'true';
        return 'action:Manufacturing_Received_List';
    }
}