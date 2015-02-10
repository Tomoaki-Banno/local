<?php

require_once(LOGIC_DIR . "Tax.class.php");

class Logic_Payment
{

    // 買掛残高データをテンポラリテーブルとして作成する。
    //  $fromDate:          期間開始日
    //　$toDate:            期間終了日
    //  $yenMode:           外貨の扱い（true:基軸通貨換算、false:取引通貨別）
    //  $showCustomer:      買掛がない取引先の表示

    static function createTempPaymentTable($fromDate, $toDate, $yenMode, $showCustomer = false)
    {
        global $gen_db;

        $keyCurrency = $gen_db->queryOneValue("select key_currency from company_master");

        // 再帰クエリでサプライヤーを抽出しておく（非表示は除外）
        $query = "
        with recursive
        t_last_balance as (
            select
                customer_master.customer_id
                ,customer_master.currency_id
                ,coalesce(payment_opening_date,'1970-01-01') as last_close_date
                ," . (!$yenMode ? "coalesce(last_opening_balance,0)" : "coalesce(last_opening_balance,0) * (case when customer_master.currency_id is null then 1 else coalesce(rate_master.rate,1) end)" ) . " as last_balance
            from
                customer_master CROSS JOIN (select currency_id, currency_name from currency_master union select null as currency_id, '{$keyCurrency}' as currency_name) as t_currency
                /* 取引先マスタの買掛基準を取得 */
                inner join (
                    select
                        customer_id
                        ,case when coalesce(payment_opening_date,'1970-01-01') < '{$fromDate}'::date then coalesce(payment_opening_date,'1970-01-01') end as last_opening_date
                        ,case when coalesce(payment_opening_date,'1970-01-01') < '{$fromDate}'::date then payment_opening_balance end as last_opening_balance
                    from
                        customer_master
                    ) as t_last_opening on customer_master.customer_id = t_last_opening.customer_id
                /* opening_balance の換算用 */
                /* 買掛残高基準日時点のレートを適用しているが検討の余地あり。請求書には共通の適用レートがない（各売上時点のレートを適用）ので難しい */
                left join (
                    select
                        customer_id
                        ,max(customer_master.currency_id) as currency_id
                        ,max(rate_date) as rate_date
                    from
                        customer_master
                        inner join rate_master on customer_master.currency_id = rate_master.currency_id
                    where
                        case when coalesce(payment_opening_date,'1970-01-01') < '{$fromDate}'::date then rate_date <= payment_opening_date else rate_date <= '{$fromDate}'::date end
                    group by
                        customer_id
                    ) as t_rate_prev on customer_master.customer_id = t_rate_prev.customer_id
                left join rate_master on t_rate_prev.currency_id = rate_master.currency_id and t_rate_prev.rate_date = rate_master.rate_date
            where
                coalesce(customer_master.currency_id,-99999) = coalesce(t_currency.currency_id,-99999)
        )
        ";
        $query .= "
        select
            currency_id
            ,currency_name
            ,customer_id
            ,payment_end_customer                   /* where条件用 */
            ,customer_no
            ,customer_name
            ,coalesce(before_accept_amount,0) as before_accept_amount
            ,coalesce(accepted_amount,0) as accepted_amount
            ,coalesce(accepted_tax,0) as accepted_tax
            ,coalesce(payment,0) as payment
            ,coalesce(adjust_payment,0) as adjust_payment
            ,coalesce(before_accept_amount,0)
                + coalesce(accepted_amount,0)
                + coalesce(accepted_tax,0)
                - coalesce(payment,0)
                - coalesce(adjust_payment,0) as payment_total
            ,coalesce(payment_0,0) as payment_0     /* 期間中支払(登録無し) */
            ,coalesce(payment_1,0) as payment_1     /* 期間中支払(現金) */
            ,coalesce(payment_2,0) as payment_2     /* 期間中支払(振込み) */
            ,coalesce(payment_3,0) as payment_3     /* 期間中支払(小切手) */
            ,coalesce(payment_4,0) as payment_4     /* 期間中支払(手形) */
            ,coalesce(payment_5,0) as payment_5     /* 期間中支払(相殺) */
            ,coalesce(payment_6,0) as payment_6     /* 期間中支払(値引き) */
            ,coalesce(payment_7,0) as payment_7     /* 期間中支払(振込手数料) */
            ,coalesce(payment_8,0) as payment_8     /* 期間中支払(その他) */
            ,coalesce(payment_9,0) as payment_9     /* 期間中支払(先振込) */
            ,coalesce(payment_10,0) as payment_10   /* 期間中支払(代引) */
        from
            (select
                t_supplier.customer_id
                ,t_supplier.end_customer as payment_end_customer
                ,customer_no
                ,customer_name
                ,t_currency.currency_id
                ,t_currency.currency_name
                /* 繰越額（取引先マスタ買掛残高 + 期間前受入合計 + 期間前消費税合計 - 期間前支払合計 - 期間前調整額合計） */
                /*  上記「期間前・・」はすべて残高基準日以前は含まないことに注意 */
                /* 残高初期値はその取引先の取引通貨のみに適用 */
                ,coalesce(last_balance,0)
                    + coalesce(before_accept_amount,0)
                    + coalesce(before_accept_tax,0)
                    - coalesce(before_payment,0)
                    - coalesce(before_adjust_payment,0) as before_accept_amount
                /* 受入額、税額（期間内の受入。残高確定前は含まず） */
                ,coalesce(accepted_amount,0) as accepted_amount
                ,coalesce(accepted_tax,0) as accepted_tax
                /* 支払額、調整額（期間内の支払。残高確定前は含まず） */
                ,coalesce(payment,0) as payment
                ,coalesce(adjust_payment,0) as adjust_payment
                /* 期間中支払（期間内の種別毎の支払。残高確定前は含まず） */
                ,coalesce(payment_0,0) as payment_0
                ,coalesce(payment_1,0) as payment_1
                ,coalesce(payment_2,0) as payment_2
                ,coalesce(payment_3,0) as payment_3
                ,coalesce(payment_4,0) as payment_4
                ,coalesce(payment_5,0) as payment_5
                ,coalesce(payment_6,0) as payment_6
                ,coalesce(payment_7,0) as payment_7
                ,coalesce(payment_8,0) as payment_8
                ,coalesce(payment_9,0) as payment_9
                ,coalesce(payment_10,0) as payment_10
            from
        ";
        $accountsPayable1 = (!$yenMode ? "case when foreign_currency_id is null then accepted_amount else foreign_currency_accepted_amount end" : "accepted.accepted_amount");
        $accountsPayable2 = (!$yenMode ? "case when foreign_currency_id is null then accepted.accepted_tax end" : "accepted.accepted_tax");
        if (!$yenMode) {
            // 取引通貨別モード
            $query .= "
                /* サプライヤーを抽出（非表示は除外） */
                (select * from customer_master where classification = 1 and coalesce(customer_master.end_customer,false) = false) as t_supplier
                CROSS JOIN (select currency_id, currency_name from currency_master union select null as currency_id, '{$keyCurrency}' as currency_name) as t_currency
                inner join t_last_balance on t_supplier.customer_id = t_last_balance.customer_id and coalesce(t_currency.currency_id,-99999) = coalesce(t_last_balance.currency_id,-99999)

                /* 受入テーブル */
                left join (
                    select
                        order_header.partner_id as customer_id
                        ,order_detail.foreign_currency_id
                        /* 期間前：受入額 */
                        ,sum(case
                            when accepted.payment_report_timing = 1 and accepted.inspection_date < '{$fromDate}'::date then {$accountsPayable1}
                            when accepted.payment_report_timing = 0 and accepted.accepted_date < '{$fromDate}'::date then {$accountsPayable1}
                            else 0 end) as before_accept_amount
                        /* 期間前：税額 */
                        ,sum(case
                            when accepted.payment_report_timing = 1 and accepted.inspection_date < '{$fromDate}'::date then {$accountsPayable2}
                            when accepted.payment_report_timing = 0 and accepted.accepted_date < '{$fromDate}'::date then {$accountsPayable2}
                            else 0 end) as before_accept_tax
                        /* 期間内：受入額 */
                        ,sum(case
                            when accepted.payment_report_timing = 1 and accepted.inspection_date >= '{$fromDate}'::date and accepted.inspection_date <= '{$toDate}'::date then {$accountsPayable1}
                            when accepted.payment_report_timing = 0 and accepted.accepted_date >= '{$fromDate}'::date and accepted.accepted_date <= '{$toDate}'::date then {$accountsPayable1}
                            else 0 end) as accepted_amount
                        /* 期間内：税額 */
                        ,sum(case
                            when accepted.payment_report_timing = 1 and accepted.inspection_date >= '{$fromDate}'::date and accepted.inspection_date <= '{$toDate}'::date then {$accountsPayable2}
                            when accepted.payment_report_timing = 0 and accepted.accepted_date >= '{$fromDate}'::date and accepted.accepted_date <= '{$toDate}'::date then {$accountsPayable2}
                            else 0 end) as accepted_tax
                    from
                        accepted
                        inner join order_detail on accepted.order_detail_id = order_detail.order_detail_id
                        inner join order_header on order_detail.order_header_id = order_header.order_header_id
                        left join t_last_balance on order_header.partner_id = t_last_balance.customer_id
                    where
                        case when accepted.payment_report_timing = 1 then accepted.inspection_date > coalesce(last_close_date,'1970-01-01')
                            else accepted.accepted_date > coalesce(last_close_date,'1970-01-01') end
                    group by
                        order_header.partner_id
                        ,order_detail.foreign_currency_id
                    ) as t_accepted on t_supplier.customer_id = t_accepted.customer_id
                        and coalesce(t_currency.currency_id,-99999) = coalesce(t_accepted.foreign_currency_id,-99999)

                /* 支払テーブル（買掛残高確定日前の支払は対象外であることに注意） */
                left join (
                    select
                        payment.customer_id
                        ,payment.foreign_currency_id
                        /* 支払額、調整額（期間前） */
                        ,sum(case when payment_date < '{$fromDate}'::date then case when foreign_currency_id is null then payment.amount else payment.foreign_currency_amount end end) as before_payment
                        ,sum(case when payment_date < '{$fromDate}'::date then case when foreign_currency_id is null then payment.adjust_amount else payment.foreign_currency_adjust_amount end end) as before_adjust_payment
                        /* 支払額、調整額（期間後） */
                        ,sum(case when payment_date >= '{$fromDate}'::date and payment_date <= '{$toDate}'::date then case when foreign_currency_id is null then payment.amount else payment.foreign_currency_amount end end) as payment
                        ,sum(case when payment_date >= '{$fromDate}'::date and payment_date <= '{$toDate}'::date then case when foreign_currency_id is null then payment.adjust_amount else payment.foreign_currency_adjust_amount end end) as adjust_payment
            ";
            /* 0:登録無し 1:現金 2:振込み 3:小切手 4:手形 5:相殺 6:値引き 7:振込手数料 8:その他 9:先振込 10:代引 */
            for ($i = 0; $i <= 10; $i++) {
                $query .= "
                        ,sum(case when payment.way_of_payment = {$i} and payment_date >= '{$fromDate}'::date and payment_date <= '{$toDate}'::date
                            then case when foreign_currency_id is null then payment.amount else payment.foreign_currency_amount end end) as payment_{$i}
                ";
            }
            $query .= "
                    from
                        payment
                        left join t_last_balance on payment.customer_id = t_last_balance.customer_id
                    where
                        payment_date > coalesce(last_close_date,'1970-01-01')
                    group by
                        payment.customer_id
                        ,payment.foreign_currency_id
                    ) as t_payment on t_supplier.customer_id = t_payment.customer_id
                        and coalesce(t_currency.currency_id,-99999) = coalesce(t_payment.foreign_currency_id,-99999)
            ";
        } else {
            // 基軸通貨換算モード
            $query .= "
                /* サプライヤーを抽出（非表示は除外） */
                (select * from customer_master where classification = 1 and coalesce(customer_master.end_customer,false) = false) as t_supplier
                CROSS JOIN (select cast(null as integer) as currency_id, cast('{$keyCurrency}' as text) as currency_name) as t_currency
                left join t_last_balance on t_supplier.customer_id = t_last_balance.customer_id

                /* 受入テーブル */
                left join (
                    select
                        order_header.partner_id as customer_id
                        /* 期間前：受入額 */
                        ,sum(case
                            when accepted.payment_report_timing = 1 and accepted.inspection_date < '{$fromDate}'::date then {$accountsPayable1}
                            when accepted.payment_report_timing = 0 and accepted.accepted_date < '{$fromDate}'::date then {$accountsPayable1}
                            else 0 end) as before_accept_amount
                        /* 期間前：税額 */
                        ,sum(case
                            when accepted.payment_report_timing = 1 and accepted.inspection_date < '{$fromDate}'::date then {$accountsPayable2}
                            when accepted.payment_report_timing = 0 and accepted.accepted_date < '{$fromDate}'::date then {$accountsPayable2}
                            else 0 end) as before_accept_tax
                        /* 期間内：受入額 */
                        ,sum(case
                            when accepted.payment_report_timing = 1 and accepted.inspection_date >= '{$fromDate}'::date and accepted.inspection_date <= '{$toDate}'::date then {$accountsPayable1}
                            when accepted.payment_report_timing = 0 and accepted.accepted_date >= '{$fromDate}'::date and accepted.accepted_date <= '{$toDate}'::date then {$accountsPayable1}
                            else 0 end) as accepted_amount
                        /* 期間内：税額 */
                        ,sum(case
                            when accepted.payment_report_timing = 1 and accepted.inspection_date >= '{$fromDate}'::date and accepted.inspection_date <= '{$toDate}'::date then {$accountsPayable2}
                            when accepted.payment_report_timing = 0 and accepted.accepted_date >= '{$fromDate}'::date and accepted.accepted_date <= '{$toDate}'::date then {$accountsPayable2}
                            else 0 end) as accepted_tax
                    from
                        accepted
                        inner join order_detail on accepted.order_detail_id = order_detail.order_detail_id
                        inner join order_header on order_detail.order_header_id = order_header.order_header_id
                        left join t_last_balance on order_header.partner_id = t_last_balance.customer_id
                    where
                        case when accepted.payment_report_timing = 1 then accepted.inspection_date > coalesce(last_close_date,'1970-01-01')
                            else accepted.accepted_date > coalesce(last_close_date,'1970-01-01') end
                    group by
                        order_header.partner_id
                    ) as t_accepted on t_supplier.customer_id = t_accepted.customer_id

                /* 支払テーブル（買掛残高確定日前の支払は対象外であることに注意） */
                left join (
                    select
                        payment.customer_id
                        /* 支払額、調整額（期間前） */
                        ,sum(case when payment_date < '{$fromDate}'::date then payment.amount end) as before_payment
                        ,sum(case when payment_date < '{$fromDate}'::date then payment.adjust_amount end) as before_adjust_payment
                        /* 支払額、調整額（期間後） */
                        ,sum(case when payment_date >= '{$fromDate}'::date and payment_date <= '{$toDate}'::date then payment.amount end) as payment
                        ,sum(case when payment_date >= '{$fromDate}'::date and payment_date <= '{$toDate}'::date then payment.adjust_amount end) as adjust_payment
            ";
            /* 0:登録無し 1:現金 2:振込み 3:小切手 4:手形 5:相殺 6:値引き 7:振込手数料 8:その他 9:先振込 10:代引 */
            for ($i = 0; $i <= 10; $i++) {
                $query .= "
                        ,sum(case when payment.way_of_payment = {$i} and payment_date >= '{$fromDate}'::date and payment_date <= '{$toDate}'::date
                            then case when foreign_currency_id is null then payment.amount else payment.foreign_currency_amount end end) as payment_{$i}
                ";
            }
            $query .= "
                    from
                        payment
                        left join t_last_balance on payment.customer_id = t_last_balance.customer_id
                    where
                        payment_date > coalesce(last_close_date,'1970-01-01')
                    group by
                        payment.customer_id
                    ) as t_payment on t_supplier.customer_id = t_payment.customer_id
            ";
        }
        $query .= "
            ) as t_accepted_payment
        where
            " . ($showCustomer ? "not payment_end_customer" : "(coalesce(before_accept_amount,0) <> 0 or coalesce(payment,0) <> 0 or coalesce(accepted_amount,0) <> 0)") . "
        ";

        // 1セッション中で同じテーブルを複数回作成する可能性があるときは、CREATE TEMP TABLE文ではなくこのメソッドを使う
        $gen_db->createTempTable("temp_payment", $query, true);

        return true;
    }

    // 支払予定表データをテンポラリテーブルとして作成する。
    //  $fromDate:          期間開始日
    //　$toDate:            期間終了日
    //  $yenMode:           外貨の扱い（true:基軸通貨換算、false:取引通貨別）

    static function createTempPaymentPlanTable($fromDate, $toDate, $dateSpan, $yenMode, $dateColumnCount, $customerId = false)
    {
        global $gen_db;

        // 締日データ（10・20・末 or 月次）
        $arr = self::getPaymentCloseData($fromDate, $toDate, $dateSpan, $dateColumnCount);
        $begin = $arr[0];
        $close = $arr[1];

        $keyCurrency = $gen_db->queryOneValue("select key_currency from company_master");
        $query = "
        select
            customer_master.customer_id
            ," . ($yenMode ? "'{$keyCurrency}'" : "MAX(case when order_detail.foreign_currency_id is null then '{$keyCurrency}' else currency_name end)") . " as currency_name

            ,max(customer_master.customer_no) as customer_no
            ,max(customer_master.customer_name) as customer_name
            ,max(case when payment_cycle1 is not null then
                cast(payment_cycle1 as text) || '" . _g("日後") . "'
                else case when payment_cycle2_month is not null and payment_cycle2_day is not null then
                cast(payment_cycle2_month as text) || '" . _g("ヶ月後の") . "' || cast(payment_cycle2_day as text) || '" . _g("日") . "'
                end end) as payment_cycle
            ,sum(coalesce(" . ($yenMode ? 'accepted_amount' : 'case when order_detail.foreign_currency_id is null then accepted_amount else foreign_currency_accepted_amount end') . ",0) + coalesce(accepted.accepted_tax,0)) as total_accepted_amount_with_tax
            ,sum(" . ($yenMode ? 'accepted_amount' : 'case when order_detail.foreign_currency_id is null then accepted_amount else foreign_currency_accepted_amount end') . ") as total_accepted_amount
            ,sum(coalesce(accepted.accepted_tax, 0)) as total_tax
        ";
        for ($i = 0; $i < $dateColumnCount; $i++) {
            $query .= ",max('" . (strtotime($close[$i]) > strtotime($toDate) ? '' : $close[$i]) . "') as payment_date_" . ($i + 1);
            $query .= ",sum(case when accepted.payment_date between '{$begin[$i]}' and '{$close[$i]}' then " . ($yenMode ? 'coalesce(accepted_amount,0)' : 'case when order_detail.foreign_currency_id is null then accepted_amount else foreign_currency_accepted_amount end') . " + coalesce(accepted.accepted_tax,0) end) as accepted_amount_with_tax_" . ($i + 1);
            $query .= ",sum(case when accepted.payment_date between '{$begin[$i]}' and '{$close[$i]}' then " . ($yenMode ? 'accepted_amount' : 'case when order_detail.foreign_currency_id is null then accepted_amount else foreign_currency_accepted_amount end') . " end) as accepted_amount_" . ($i + 1);
            $query .= ",sum(case when accepted.payment_date between '{$begin[$i]}' and '{$close[$i]}' then accepted.accepted_tax end) as tax_" . ($i + 1);
        }
        $query .= "
        from
            accepted
            inner join order_detail on accepted.order_detail_id = order_detail.order_detail_id
            inner join order_header on order_detail.order_header_id = order_header.order_header_id
            inner join customer_master on order_header.partner_id = customer_master.customer_id
            " . ($yenMode ? '' : 'left join currency_master on order_detail.foreign_currency_id = currency_master.currency_id') . "

        where
            accepted.payment_date between '{$fromDate}'::date and '{$toDate}'::date
            " . (is_numeric($customerId) ? " and customer_id = '{$customerId}'" : '') . "
        group by
            customer_master.customer_id" . ($yenMode ? '' : ',order_detail.foreign_currency_id') . "
        ";

        // 1セッション中で同じテーブルを複数回作成する可能性があるときは、CREATE TEMP TABLE文ではなくこのメソッドを使う
        $gen_db->createTempTable("temp_payment_calendar", $query, true);

        return true;
    }

    // 支払予定表sub。ListやReportクラスからも呼ばれる
    static function getPaymentCloseData($fromDate, $toDate, $dateSpan, $dateColumnCount)
    {
        $begin = array();
        $close = array();
        $from = strtotime($fromDate);
        $to = strtotime($toDate);
        if ($dateSpan == 30) {
            // 1ヶ月毎
            $ymd = date('Y-m-01', $from);
            for ($i = 0; $i < $dateColumnCount; $i++) {
                $begin[$i] = date('Y-m-01', strtotime($ymd . " +{$i} month"));
                $close[$i] = date('Y-m-t', strtotime($ymd . " +{$i} month"));
            }
        } elseif ($dateSpan == 10) {
            // 10日毎
            $ymd = date('Y-m-01', $from);
            $day = ceil(date('d', $from) / 10) * 10;
            for ($i = 0; $i < $dateColumnCount; $i++) {
                $begin[$i] = date('Y-m-d', mktime(0, 0, 0, date('m', strtotime($ymd)), ($day - 9), date('Y', strtotime($ymd))));
                if ($day == 30) {
                    $close[$i] = date('Y-m-t', strtotime($ymd));
                    $ymd = date('Y-m-01', strtotime($ymd . ' +1 month'));
                    $day = 10;
                } else {
                    $close[$i] = date('Y-m-d', mktime(0, 0, 0, date('m', strtotime($ymd)), $day, date('Y', strtotime($ymd))));
                    $day += 10;
                }
            }
        } else {
            // 5日毎
            $ymd = date('Y-m-01', $from);
            $day = ceil(date('d', $from) / 5) * 5;
            for ($i = 0; $i < $dateColumnCount; $i++) {
                $begin[$i] = date('Y-m-d', mktime(0, 0, 0, date('m', strtotime($ymd)), ($day - 4), date('Y', strtotime($ymd))));
                if ($day >= 30) {
                    $close[$i] = date('Y-m-t', strtotime($ymd));
                    $ymd = date('Y-m-01', strtotime($ymd . ' +1 month'));
                    $day = 5;
                } else {
                    $close[$i] = date('Y-m-d', mktime(0, 0, 0, date('m', strtotime($ymd)), $day, date('Y', strtotime($ymd))));
                    $day += 5;
                }
            }
        }
        $begin[0] = date('Y-m-d', $from);

        return array($begin, $close);
    }

}