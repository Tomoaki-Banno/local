<?php

/**
 * Gen_Date
 *
 * @author S.Ito
 * @copyright 2012 e-commode
 */
/**
 * 日付関連ユーティリティクラス
 *
 * @author S.Ito
 */

class Gen_Date
{

    // 日付マスタ(date_master)の整備。指定した期間の日付レコードがなければ作成する
    //  引数は日付文字列
    static function makeDateMaster($from, $to)
    {
        global $gen_db;

        $gen_db->begin();   // 高速化のため

        $query = "select min(date) as mindate, max(date) as maxdate from date_master";
        $obj = $gen_db->queryOneRowObject($query);
        $masterMin = strtotime($obj->mindate);
        $masterMax = strtotime($obj->maxdate);
        $showFrom = strtotime($from);
        $showTo = strtotime($to);
        if ($obj->mindate === null) {
            for ($date = $showFrom; $date <= $showTo; $date+=(3600 * 24)) {
                $query = "insert into date_master (date) values ('" . date('Y-m-d', $date) . "')";
                $gen_db->query($query);
            }
        } else {
            if ($masterMin > $showFrom) {
                for ($date = $showFrom; $date < $masterMin; $date+=(3600 * 24)) {
                    $query = "insert into date_master (date) values ('" . date('Y-m-d', $date) . "')";
                    $gen_db->query($query);
                }
            }
            if ($masterMax < $showTo) {
                for ($date = ($masterMax + 3600 * 24); $date <= $showTo; $date+=(3600 * 24)) {
                    $query = "insert into date_master (date) values ('" . date('Y-m-d', $date) . "')";
                    $gen_db->query($query);
                }
            }
        }

        $gen_db->commit();
    }

    // 休日を配列に取得
    //    Logic_MRPからも呼ばれるため、staticをつける
    static function getHolidayArray($from, $to)
    {
        global $gen_db;

        $query = "SELECT holiday FROM holiday_master WHERE holiday BETWEEN '" . date('Y-m-d', $from) . "' and '" . date('Y-m-d', $to) . "'";
        $holidayDbArr = $gen_db->getArray($query);
        $holidayArr = array();
        if (is_array($holidayDbArr)) {
            foreach ($holidayDbArr as $holidayDbRow) {
                $holidayArr[] = $holidayDbRow['holiday'];
            }
        }
        return $holidayArr;
    }

    // 納期($deadline)からオーダー日を求める（リードタイムと休日を考慮）
    //    $fromDateより前にはならないように。$fromDateより前になる場合は、最後の引数$alarmを'1'にする。
    static function getOrderDate($deadline, $leadtime, $holidayArr, $fromDate, &$alarm)
    {
        // リードタイム日分ずらしていきオーダー日を求める
        $date = $deadline;
        $alarm = "0";
        for ($d = 1; $d <= $leadtime; $d++) {
            $date -= (3600 * 24);        // 一日戻す
            while (in_array(date('Y-m-d', $date), $holidayArr)) {
                $date -= (3600 * 24);        // 一日戻す
            }
            // オーダー日が$fromDateより前になる場合、$fromDateをオーダー日とし、アラームフラグを立てる
            if ($date < $fromDate) {
                $date = $fromDate;
                $alarm = "1";
                break;
            }
        }
        return $date;
    }

    // オーダー日から納期を求める（リードタイムと休日を考慮）
    static function getDeadLine($orderDate, $leadtime)
    {
        // とりあえずリードタイムの４倍分の日数の休日情報を取得しておく。
        $holidayLimit = $orderDate + ($leadtime * 3600 * 24 * 4);
        $holidayArr = self::getHolidayArray($orderDate, $holidayLimit);

        // リードタイム日分ずらしていき納期を求める
        $date = $orderDate;
        for ($d = 1; $d <= $leadtime; $d++) {
            $date += (3600 * 24);        // 一日進める
            if ($date >= $holidayLimit) {
                // 休日情報が足りなくなったので、追加で取得
                $holidayLimit = $date + ($leadtime * 3600 * 24 * 4);
                $holidayArr = self::getHolidayArray($date, $holidayLimit);
            }
            while (in_array(date('Y-m-d', $date), $holidayArr)) {
                $date += (3600 * 24);        // 一日進める
                if ($date >= $holidayLimit) {
                    // 休日情報が足りなくなったので、追加で取得
                    $holidayLimit = $date + ($leadtime * 3600 * 24 * 4);
                    $holidayArr = self::getHolidayArray($date, $holidayLimit);
                }
            }
        }
        return $date;
    }

}