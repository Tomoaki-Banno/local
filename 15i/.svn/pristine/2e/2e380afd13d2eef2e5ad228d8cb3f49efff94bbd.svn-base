<?php

class Logic_SystemDate
{

    //************************************************
    // データベースより現在処理年月を取得してセッション変数に格納
    //************************************************
    // 現在処理年月の参照は頻繁に行われる。パフォーマンス向上のため、
    // 毎回データベースから読み出さなくてよいよう、セッション変数にキャッシュ
    // しておく。
    // このメソッドはこのクラス内で使用されるほか、月次処理で現在処理月が
    // 変わったときに呼ばれる。
    // 08iでは、現在処理月はデータロック基準日としてのみ使用されるようになった

    static function systemDateToSessionVariable()
    {
        global $gen_db;

        $query = "select monthly_dealing_date, logical_inventory_date from company_master";
        $res = $gen_db->queryOneRowObject($query);
        unset($_SESSION['gen_start_date']);
        unset($_SESSION['gen_end_date']);

        $_SESSION['gen_start_date'] = $res->monthly_dealing_date;
        $_SESSION['gen_end_date'] = $res->logical_inventory_date;
    }

    //************************************************
    // 現在処理年月の取得（文字列版）
    //************************************************
    // ここで取得した文字列は、そのままSQL文の中で日付文字列として使える。
    // MRPから呼ばれるため、static宣言しておく必要がある
    // （宣言されていないとPHPのバージョン・設定によりエラーになることがある）

    static function getStartDateString()
    {
        self::systemDateToSessionVariable();
        return $_SESSION['gen_start_date'];
    }

    static function getEndDateString()
    {
        self::systemDateToSessionVariable();
        return $_SESSION['gen_end_date'];
    }

    //************************************************
    // 現在処理年月の取得（UNIXタイムスタンプ版）
    //************************************************
    // あとで表示形式を自由に指定したい場合に便利
    //    取得した値を「date("Y/m/d l H:i:s", $start_date)」のように使える。
    //    また、日時計算（秒単位で加減算）、日時の大小比較の際にも便利

    static function getStartDate($cat = 0)
    {
        $lock_date = strtotime(self::getStartDateString());
        if ($cat == 1) {    // 販売ロック
            $sales_lock_date = self::getSalesLockDate();
            if ($lock_date < $sales_lock_date)
                $lock_date = $sales_lock_date;
        }
        if ($cat == 2) {    // 購買ロック
            $buy_lock_date = self::getBuyLockDate();
            if ($lock_date < $buy_lock_date)
                $lock_date = $buy_lock_date;
        }
        return $lock_date;
    }

    static function getEndDate()
    {
        return strtotime(self::getEndDateString());
    }

    static function getSalesLockDate()
    {
        global $gen_db;
        $query = "select sales_lock_date from company_master ";
        return strtotime($gen_db->queryOneValue($query));
    }

    static function getBuyLockDate()
    {
        global $gen_db;
        $query = "select buy_lock_date from company_master ";
        return strtotime($gen_db->queryOneValue($query));
    }

    //************************************************
    // 指定された文字列が現在処理年月であるかどうかを返す。
    //************************************************
    // 現在処理年月であれば true
    // 現在処理年月でないか、文字列が日付解釈できなければ false

    static function isSystemDate($str)
    {
        if (($timestamp = strtotime($str)) === -1) {
            // 文字列が日付解釈できなかった
            return false;
        } else {
            $start = self::getStartDate();
            $end = self::getEndDate();
            return ($timestamp >= $start && $timestamp <= $end);
        }
    }

    //************************************************
    // 年度の開始日を返す（UNIXタイムスタンプ版）
    //************************************************

    static function getYearStartDate()
    {
        global $gen_db;

        $query = "select startup_date_year from company_master ";
        return strtotime($gen_db->queryOneValue($query));
    }

}