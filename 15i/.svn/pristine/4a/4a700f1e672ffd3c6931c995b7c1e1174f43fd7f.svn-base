<?php

class Logic_DataLock
{

    // 過去データのロック
    //  companyテーブルの現在処理年月（各クラスでロック基準として使用している）を変更する。
    //  $cat    1: 販売、2: 購買、それ以外: すべて

    static function dataLock($cat, $lockYear, $lockMonth)
    {
        global $gen_db;

        $next_start_date = date('Y-m-d', mktime(0, 0, 0, $lockMonth + 1, 1, $lockYear));    // ロック年月の翌月1日。13月は翌年1月に換算される
        $next_end_date = date('Y-m-d', mktime(0, 0, 0, $lockMonth + 2, 0, $lockYear));      // ロック年月の翌月末。0日は前月末日に換算される
        // companyテーブルの更新（以前はLogic_Monthly::monthlyCompany にあった処理）
        switch ($cat) {
            case 1:
                $data = array("sales_lock_date" => $next_start_date);
                break;
            case 2:
                $data = array("buy_lock_date" => $next_start_date);
                break;
            default:
                $data = array(
                    "monthly_dealing_date" => "{$next_start_date}",
                    "logical_inventory_date" => "{$next_end_date}",
                    "last_dealing_date" => date('Y-m-d')
                );
        }
        $gen_db->update("company_master", $data, null);

        return true;
    }

    // データロック範囲の変更
    //  companyテーブルのデータロック対象外を変更する。
    //
    //  $object1  受注登録
    //  $object2  製造指示登録
    //  $object3  注文登録
    //  $object4  外製指示登録

    static function dataUnlock($object1, $object2, $object3, $object4)
    {
        global $gen_db;

        $data = array(
            "unlock_object_1" => $object1,
            "unlock_object_2" => $object2,
            "unlock_object_3" => $object3,
            "unlock_object_4" => $object4,
        );
        $gen_db->update("company_master", $data, null);

        return true;
    }

}