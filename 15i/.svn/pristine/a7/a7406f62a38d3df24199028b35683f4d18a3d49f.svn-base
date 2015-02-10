<?php

/**
 * Customer関連の処理を集めたクラス
 *
 * @copyright 2011 e-commode
 */
class Logic_Customer
{

    /**
     * 取引先の換算レートを取得する
     *
     * 取引先マスタの「取引通貨」に従い、為替レートマスタから「為替レート」を取得する。
     *
     * @param   int     $customerId     取引先id
     * @param   date    $date           レート取得日
     *
     * @return  numeric                 為替レート値。取引通貨換算しないときはfalse。
     */
    static function getCustomerRate($customerId, $date)
    {
        global $gen_db;

        if (!Gen_String::isNumeric($customerId))
            return false;
        if (!Gen_String::isDateString($date))
            return false;

        // 取引通貨取得
        $query = "select currency_id from customer_master where customer_id = '{$customerId}'";
        $currencyId = $gen_db->queryOneValue($query);

        if (!Gen_String::isNumeric($currencyId))
            return false;

        // 為替レート取得
        $query = "
        select
            coalesce(rate,1) as rate
        from
            rate_master
            inner join (
                select
                    currency_id
                    , max(rate_date) as rate_date
                from
                    rate_master
                where
                    rate_date <= '{$date}'::date
                    and currency_id = '{$currencyId}'
                group by
                    currency_id
                ) as t_rate_date
                on rate_master.currency_id = t_rate_date.currency_id
                and rate_master.rate_date = t_rate_date.rate_date
        ";

        $rate = $gen_db->queryOneValue($query);
        if ($rate == null)
            $rate = 1;

        return $rate;
    }

    /**
     * 取引先の基準にしたがって数値の丸めを行う
     *
     * 取引先マスタの「端数処理」および「小数点以下の桁数」に従い、数値の丸めを行う。
     *
     * @param   numeric $val            丸める数値
     * @param   int     $customerId     取引先id
     *
     * @return  numeric                 丸められた数値。引数不正・取引先なしのときはfalse
     */
    static function round($val, $customerId)
    {
        global $gen_db;

        if (!is_numeric($customerId))
            return false;

        $query = "
        select
            coalesce(rounding,'round') as rounding
            ,coalesce(precision,0) as precision
        from
            customer_master
        where
            customer_id = '{$customerId}'
        ";
        $obj = $gen_db->queryOneRowObject($query);
        if (!$obj || $obj == null)
            return false;

        return Gen_Math::round($val, $obj->rounding, $obj->precision);
    }

    /**
     * 回収予定日・支払予定日の計算
     *
     * 日付と、取引先マスタ「回収サイクル」「支払サイクル」をもとに、回収予定日・支払予定日を計算。
     * 結果はテンポラリテーブル（temp_cycle_date）に挿入する。
     *
     * サイクル1を優先とする。
     * サイクル1（x日後）:
     *     予定日 = 締日 + サイクル1日数。
     *     ただし1ヶ月を30日とみなして計算する。
     *         予定日が30日だったら、その月の末日とする
     *           例：7/31締めの30日後 -> 8/30ではなく8/31
     *         予定日が30日を過ぎる場合、30日を過ぎた日数分だけ翌月へ繰り越す。
     *           例：7/20締めの15日後 -> 8/4ではなく8/5
     *         サイクル1が31日以上の場合も、1ヶ月を30日とみなして計算。
     *           例：7/31締めの60日後 -> 9/30
     * サイクル2（xヶ月後のy日）：
     *      「日」については、1-30はそのとおりの日とみなすが、31は末日とみなす。
     *
     * @param   date    $date           日付（回収予定日計算なら締日、支払予定日計算なら受入日）
     * @param   bool    $isReceivable   true:回収予定日、false:支払予定日
     * @param   int     $customerId     取引先id。 nullなら全取引先
     *
     * @return  null
     */
    static function makeCycleDateTable($date, $isReceivable, $customerId)
    {
        global $gen_db;

        $col = ($isReceivable ? 'receivable' : 'payment');

        $query = "
        select
            customer_master.customer_id
            ,case when {$col}_cycle1 is not null then
                -- サイクル1（x日後。1ヶ月を30日として計算）
                -- 指定日付が2月末日のときも、月末日条件として補正する。
                case when date_part('day', date '{$date}') + mod({$col}_cycle1, 30) in (30,31)
                  or (date_part('month', date '{$date}') = 2 and date_trunc('month', date '{$date}') + '1 month -1 day' = '{$date}' and mod(receivable_cycle1, 30) = 0) then
                    -- 計算後の日付が30日か31日になるとき。月末日に補正する
                    date_trunc('month', date '{$date}')
                    + cast(cast(trunc({$col}_cycle1 / 30) + 1 as text) || ' month' as interval)
                    + '-1 day'
                else
                    -- 計算後の日付が1-29日のとき。
                    -- 月またぎの際は、justify_daysで30日補正していることに注意（2月末日のときも30日補正する）
                    date_trunc('month', date '{$date}')
                    + cast(cast(trunc({$col}_cycle1 / 30) as text) || ' month' as interval)
                    + justify_days(cast(cast(case when date_part('day', date '{$date}')=31 or date_trunc('month', date '{$date}') + '1 month -1 day' = '{$date}'
                        then 30 else date_part('day', date '{$date}') end + mod({$col}_cycle1, 30) - 1 as text) || ' days' as interval))
                end

            else case when ({$col}_cycle2_month is not null) and ({$col}_cycle2_day is not null) then
                -- サイクル2（xヶ月後のy日）
                case when {$col}_cycle2_day = 31 then
                    -- 31（月末日）
                    date_trunc('month', date '{$date}')
                    + cast(cast({$col}_cycle2_month + 1 as text) || ' months' as interval)
                    + '-1 day'
                else
                    -- 月末日以外
                    date_trunc('month', date '{$date}')
                    + cast(cast({$col}_cycle2_month as text) || ' months' as interval)
                    + cast(cast({$col}_cycle2_day - 1 as text) || ' days' as interval)
                end
            end end as cycle_date
        from
            customer_master
            " . (is_numeric($customerId) ? "where customer_id = {$customerId}" : "") . "
        ";

        // 1セッション中で同じテーブルを複数回作成する可能性があるときは、CREATE TEMP TABLE文ではなくこのメソッドを使う
        $gen_db->createTempTable("temp_cycle_date", $query, true);

        return;
    }

    /**
     * 回収予定日・支払予定日の計算 (締日基準)
     *
     * 日付と、取引先マスタ「回収サイクル」「支払サイクル」をもとに、回収予定日・支払予定日を計算。
     * 結果はテンポラリテーブル（temp_cycle_date）に挿入する。
     *
     * マスタの締日に基づき引数の日付から基本となる締日を算出する。
     *
     * サイクル1を優先とする。
     * サイクル1（x日後）:
     *     予定日 = 締日 + サイクル1日数。
     *     ただし1ヶ月を30日とみなして計算する。
     *         予定日が30日だったら、その月の末日とする
     *           例：7/31締めの30日後 -> 8/30ではなく8/31
     *         予定日が30日を過ぎる場合、30日を過ぎた日数分だけ翌月へ繰り越す。
     *           例：7/20締めの15日後 -> 8/4ではなく8/5
     *         サイクル1が31日以上の場合も、1ヶ月を30日とみなして計算。
     *           例：7/31締めの60日後 -> 9/30
     * サイクル2（xヶ月後のy日）：
     *      「日」については、1-30はそのとおりの日とみなすが、31は末日とみなす。
     *
     * @param   date    $date           日付（回収予定日計算なら締日、支払予定日計算なら受入日）
     * @param   bool    $isReceivable   true:回収予定日、false:支払予定日
     * @param   int     $customerId     取引先id。 nullなら全取引先
     *
     * @return  null
     */
    static function makeCycleDateWithMonthlyLimitDateTable($date, $isReceivable, $customerId)
    {
        global $gen_db;

        $col = ($isReceivable ? 'receivable' : 'payment');

        // 取引先マスタベースの締日を算出する
        $monthlyLimitDate = $gen_db->queryOneValue("select monthly_limit_date from customer_master where customer_id = '{$customerId}'");
        $limitDate = ($monthlyLimitDate == "31" ? date('Y-m-t', strtotime($date)) : date("Y-m-{$monthlyLimitDate}", strtotime($date)));
        if (strtotime($date) <= strtotime($limitDate)) {
            // マスタベースの締日前（マスタの締日を取得）
            $date = $limitDate;
        } else {
            // マスタベースの締日後（翌締日を取得）
            $date = ($monthlyLimitDate == "31" ? date('Y-m-t', strtotime(date('Y-m-01', strtotime($date)) . '+1 month')) : date("Y-m-{$monthlyLimitDate}", strtotime(date('Y-m-01', strtotime($date)) . '+1 month')));
        }

        $query = "
        select
            customer_master.customer_id
            ,case when {$col}_cycle1 is not null then
                -- サイクル1（x日後。1ヶ月を30日として計算）
                -- 指定日付が2月末日のときも、月末日条件として補正する。
                case when date_part('day', date '{$date}') + mod({$col}_cycle1, 30) in (30,31)
                  or (date_part('month', date '{$date}') = 2 and date_trunc('month', date '{$date}') + '1 month -1 day' = '{$date}' and mod(receivable_cycle1, 30) = 0) then
                    -- 計算後の日付が30日か31日になるとき。月末日に補正する
                    date_trunc('month', date '{$date}')
                    + cast(cast(trunc({$col}_cycle1 / 30) + 1 as text) || ' month' as interval)
                    + '-1 day'
                else
                    -- 計算後の日付が1-29日のとき。
                    -- 月またぎの際は、justify_daysで30日補正していることに注意（2月末日のときも30日補正する）
                    date_trunc('month', date '{$date}')
                    + cast(cast(trunc({$col}_cycle1 / 30) as text) || ' month' as interval)
                    + justify_days(cast(cast(case when date_part('day', date '{$date}')=31 or date_trunc('month', date '{$date}') + '1 month -1 day' = '{$date}'
                        then 30 else date_part('day', date '{$date}') end + mod({$col}_cycle1, 30) - 1 as text) || ' days' as interval))
                end

            else case when ({$col}_cycle2_month is not null) and ({$col}_cycle2_day is not null) then
                -- サイクル2（xヶ月後のy日）
                case when {$col}_cycle2_day = 31 then
                    -- 31（月末日）
                    date_trunc('month', date '{$date}')
                    + cast(cast({$col}_cycle2_month + 1 as text) || ' months' as interval)
                    + '-1 day'
                else
                    -- 月末日以外
                    date_trunc('month', date '{$date}')
                    + cast(cast({$col}_cycle2_month as text) || ' months' as interval)
                    + cast(cast({$col}_cycle2_day - 1 as text) || ' days' as interval)
                end
            end end as cycle_date
        from
            customer_master
            " . (is_numeric($customerId) ? "where customer_id = {$customerId}" : "") . "
        ";

        // 1セッション中で同じテーブルを複数回作成する可能性があるときは、CREATE TEMP TABLE文ではなくこのメソッドを使う
        $gen_db->createTempTable("temp_cycle_date", $query, true);

        return;
    }

}