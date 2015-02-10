<?php

class Logic_Receivable
{

    // 売掛残高データをテンポラリテーブルとして作成する。
    //  $fromDate:          期間開始日
    //　$toDate:            期間終了日
    //  $dataMode:          データモード（0:納品ベース、1:受注ベース、2:請求ベース）
    //  $yenMode:           外貨の扱い（true:基軸通貨換算、false:取引通貨別）
    //  $customerId:        取引先id
    //  $showCustomer:      売掛がない取引先の表示

    static function createTempReceivableTable($fromDate, $toDate, $dataMode, $yenMode, $customerId = null, $showCustomer = false)
    {
        global $gen_db;

        $keyCurrency = $gen_db->queryOneValue("select key_currency from company_master");
        //  再帰クエリで繰越残高を求めておく
        //  請求関係と売掛関係は切り離す
        $query = "
        with recursive
        t_last_balance as (
            select
                customer_master.customer_id
                ,case when coalesce(customer_master.opening_date,'1970-01-01') < '{$fromDate}'::date
                    then coalesce(customer_master.opening_date,'1970-01-01') else '1970-01-01' end as last_close_date
                ,case " . (!$yenMode ? "
                    when coalesce(customer_master.opening_date,'1970-01-01') < '{$fromDate}'::date
                        then coalesce(customer_master.opening_balance,0)
                    else
                        0
                " : "
                    when coalesce(customer_master.opening_date,'1970-01-01') < '{$fromDate}'::date and customer_master.currency_id is not null
                        then coalesce(customer_master.opening_balance,0) * coalesce(rate_master.rate,1)
                    when coalesce(customer_master.opening_date,'1970-01-01') < '{$fromDate}'::date
                        then coalesce(customer_master.opening_balance,0)
                    else
                        0
                ") . " end as last_balance
                ,customer_master.currency_id
                ,case
                    when coalesce(customer_master.opening_date,'1970-01-01') < '{$fromDate}'::date and customer_master.currency_id is not null
                        then customer_master.opening_balance * coalesce(rate_master.rate,1) end as foreign_currency_last_amount
                ," . (!$yenMode ? "customer_master.credit_line" : "case when customer_master.currency_id is not null then customer_master.credit_line * coalesce(t_now_rate.rate,1) else customer_master.credit_line end") . " as credit_line
            from
                customer_master
                left join (
                    select
                        customer_id
                        ,max(rate_date) as max_rate_date
                    from
                        customer_master
                        left join rate_master on customer_master.currency_id = rate_master.currency_id
                            and customer_master.opening_date > rate_master.rate_date
                    group by
                        customer_id
                    ) as t_rate_date on customer_master.customer_id = t_rate_date.customer_id
                left join rate_master on customer_master.currency_id = rate_master.currency_id
                    and t_rate_date.max_rate_date = rate_master.rate_date
                left join (
                    select
                        customer_id
                        ,customer_master.currency_id
                        ,max(rate_date) as max_now_rate_date
                    from
                        customer_master
                        left join rate_master on customer_master.currency_id = rate_master.currency_id and '{$fromDate}'::date > rate_master.rate_date
                    group by
                        customer_id
                        ,customer_master.currency_id
                    ) as t_now_rate_date on customer_master.customer_id = t_now_rate_date.customer_id
                left join rate_master as t_now_rate on t_now_rate_date.currency_id = t_now_rate.currency_id
                    and t_now_rate_date.max_now_rate_date = t_now_rate.rate_date
        )
        ";
        $query .= "
        select
            currency_id
            ,currency_name
            ,customer_id
            ,receivable_bill_customer_id                        /* where条件用 */
            ,receivable_end_customer                            /* where条件用 */
            ,customer_no
            ,customer_name
            ,bill_pattern                                       /* 請求パターン */
            ,monthly_limit_date                                 /* 締日 */
            ,credit_line                                        /* 与信限度額 */
            ,coalesce(before_sales,0) as before_sales           /* 繰越額 */
            ,coalesce(sales,0) as sales                         /* 期間中売上額 */
            ,coalesce(sales_tax,0) as sales_tax                 /* 期間中消費税額 */
            ,coalesce(paying_in,0) as paying_in                 /* 期間中入金額 */
            ,coalesce(before_sales,0)
                + coalesce(sales,0)
                + coalesce(sales_tax,0)
                - coalesce(paying_in,0) as receivable_balance   /* 売掛金残高 */
            ,coalesce(paying_in_0,0) as paying_in_0             /* 期間中入金(登録無し) */
            ,coalesce(paying_in_1,0) as paying_in_1             /* 期間中入金(現金) */
            ,coalesce(paying_in_2,0) as paying_in_2             /* 期間中入金(振込み) */
            ,coalesce(paying_in_3,0) as paying_in_3             /* 期間中入金(小切手) */
            ,coalesce(paying_in_4,0) as paying_in_4             /* 期間中入金(手形) */
            ,coalesce(paying_in_5,0) as paying_in_5             /* 期間中入金(相殺) */
            ,coalesce(paying_in_6,0) as paying_in_6             /* 期間中入金(値引き) */
            ,coalesce(paying_in_7,0) as paying_in_7             /* 期間中入金(振込手数料) */
            ,coalesce(paying_in_8,0) as paying_in_8             /* 期間中入金(その他) */
            ,coalesce(paying_in_9,0) as paying_in_9             /* 期間中入金(先振込) */
            ,coalesce(paying_in_10,0) as paying_in_10           /* 期間中入金(代引) */
        from
            (select
                t_currency.currency_id
                ,currency_name
                ,customer_master.customer_id
                ,customer_master.bill_customer_id as receivable_bill_customer_id
                ,customer_master.end_customer as receivable_end_customer
                ,customer_no
                ,customer_name
                ,bill_pattern
                ,monthly_limit_date
                ,t_last_balance.credit_line as credit_line
                /* 繰越額（取引先マスタ売掛残高 + 期間前請求合計 + 期間前消費税合計 - 期間前入金合計） */
                /* 上記「期間前・・」はすべて残高基準日以前は含まないことに注意 */
                /* 残高初期値はその取引先の取引通貨のみに適用 */
                ,coalesce(last_balance,0)
                    + coalesce(before_sales,0)
                    + coalesce(before_sales_tax,0)
                    - coalesce(before_paying_in,0) as before_sales
                /* 請求額、税額（期間内の請求。残高確定前は含まず） */
                ,coalesce(sales,0) as sales
                ,coalesce(sales_tax,0) as sales_tax
                /* 入金額（期間内の入金。残高確定前は含まず） */
                ,coalesce(paying_in,0) as paying_in
                /* 期間中入金（期間内の種別毎の入金。残高確定前は含まず） */
                ,coalesce(paying_in_0,0) as paying_in_0
                ,coalesce(paying_in_1,0) as paying_in_1
                ,coalesce(paying_in_2,0) as paying_in_2
                ,coalesce(paying_in_3,0) as paying_in_3
                ,coalesce(paying_in_4,0) as paying_in_4
                ,coalesce(paying_in_5,0) as paying_in_5
                ,coalesce(paying_in_6,0) as paying_in_6
                ,coalesce(paying_in_7,0) as paying_in_7
                ,coalesce(paying_in_8,0) as paying_in_8
                ,coalesce(paying_in_9,0) as paying_in_9
                ,coalesce(paying_in_10,0) as paying_in_10
            from
                " . (!$yenMode ?
                // 取引通貨別モード
                "customer_master
                    /* ここを innerにするとかなり遅くなる。leftでいいはず */
                    left JOIN (select currency_id, currency_name from currency_master union select null as currency_id, '{$keyCurrency}' as currency_name) as t_currency
                        on coalesce(customer_master.currency_id,-99999) = coalesce(t_currency.currency_id,-99999)                
                    left join t_last_balance on customer_master.customer_id = t_last_balance.customer_id and coalesce(t_currency.currency_id,-99999) = coalesce(t_last_balance.currency_id,-99999)
                " :
                // 基軸通貨換算モード
                "customer_master CROSS JOIN (select cast(null as integer) as currency_id, cast('{$keyCurrency}' as text) as currency_name) as t_currency
                left join t_last_balance on customer_master.customer_id = t_last_balance.customer_id
                ") . "

                left join (
        ";

        if ($dataMode == '0') {
            // 納品ベース
            $deliveryCurrencyId = (!$yenMode ? ",delivery_header.foreign_currency_id" : "");
            $deliverySales1 = (!$yenMode ? "case when delivery_header.foreign_currency_id is null then delivery_header.delivery_note_amount else delivery_header.foreign_currency_delivery_note_amount end" : "delivery_header.delivery_note_amount");
            $deliverySales2 = (!$yenMode ? "case when delivery_header.foreign_currency_id is null then delivery_detail.delivery_amount else delivery_detail.foreign_currency_delivery_amount end" : "delivery_detail.delivery_amount");
            $deliverySales3 = (!$yenMode ? "case when delivery_header.foreign_currency_id is null then delivery_detail.delivery_amount else delivery_detail.foreign_currency_delivery_amount end" : "delivery_detail.delivery_amount");
            $deliveryTax1 = (!$yenMode ? "case when delivery_header.foreign_currency_id is null then delivery_header.delivery_note_tax else delivery_header.foreign_currency_delivery_note_tax end" : "delivery_header.delivery_note_tax");
            $deliveryTax2 = (!$yenMode ? "case when delivery_header.foreign_currency_id is null then delivery_detail.delivery_tax else delivery_detail.foreign_currency_delivery_tax end" : "delivery_detail.delivery_tax");
            $query .= "
                    select
                        delivery_header.bill_customer_id as customer_id
                        {$deliveryCurrencyId}

                        /* 期間前：請求額 */
                            /* 1: 納品書単位 */
                        ,sum(case
                            when delivery_header.tax_category = 1 and delivery_header.receivable_report_timing = 1 and delivery_header.inspection_date < '{$fromDate}'::date then {$deliverySales1}
                            when delivery_header.tax_category = 1 and delivery_header.receivable_report_timing = 0 and delivery_header.delivery_date < '{$fromDate}'::date then {$deliverySales1}
                            else 0 end)
                            /* 2: 納品明細単位 */
                            + max(coalesce(before_sales_2,0))
                            /* 0: 請求書単位 */
                            + max(coalesce(before_sales_0,0)) as before_sales
                        /* 期間前：税額 */
                            /* 1: 納品書単位 */
                        ,sum(case
                            when delivery_header.tax_category = 1 and delivery_header.receivable_report_timing = 1 and delivery_header.inspection_date < '{$fromDate}'::date then {$deliveryTax1}
                            when delivery_header.tax_category = 1 and delivery_header.receivable_report_timing = 0 and delivery_header.delivery_date < '{$fromDate}'::date then {$deliveryTax1}
                            else 0 end)
                            /* 2: 納品明細単位 */
                            + max(coalesce(before_sales_tax_2,0))
                            /* 0: 請求書単位 */
                            + max(coalesce(before_sales_tax_0,0)) as before_sales_tax
                        /* 期間内： 請求額 */
                            /* 1: 納品書単位 */
                        ,sum(case
                            when delivery_header.tax_category = 1 and delivery_header.receivable_report_timing = 1 and delivery_header.inspection_date >= '{$fromDate}'::date and delivery_header.inspection_date <= '{$toDate}'::date then {$deliverySales1}
                            when delivery_header.tax_category = 1 and delivery_header.receivable_report_timing = 0 and delivery_header.delivery_date >= '{$fromDate}'::date and delivery_header.delivery_date <= '{$toDate}'::date then {$deliverySales1}
                            else 0 end)
                            /* 2: 納品明細単位 */
                            + max(coalesce(sales_2,0))
                            /* 0: 請求書単位 */
                            + max(coalesce(sales_0,0)) as sales
                        /* 期間内： 税額 */
                            /* 1: 納品書単位 */
                        ,sum(case
                            when delivery_header.tax_category = 1 and delivery_header.receivable_report_timing = 1 and delivery_header.inspection_date >= '{$fromDate}'::date and delivery_header.inspection_date <= '{$toDate}'::date then {$deliveryTax1}
                            when delivery_header.tax_category = 1 and delivery_header.receivable_report_timing = 0 and delivery_header.delivery_date >= '{$fromDate}'::date and delivery_header.delivery_date <= '{$toDate}'::date then {$deliveryTax1}
                            else 0 end)
                            /* 2: 納品明細単位 */
                            + max(coalesce(sales_tax_2,0))
                            /* 0: 請求書単位 */
                            + max(coalesce(sales_tax_0,0)) as sales_tax
                    from
                        delivery_header
                        left join t_last_balance on delivery_header.bill_customer_id = t_last_balance.customer_id
                        /* 2: 納品明細単位は期間外と期間内を別途集計する */
                        left join (
                            select
                                delivery_header.bill_customer_id
                                ,sum(case
                                    when delivery_header.receivable_report_timing = 1 and delivery_header.inspection_date < '{$fromDate}'::date then {$deliverySales2}
                                    when delivery_header.receivable_report_timing = 0 and delivery_header.delivery_date < '{$fromDate}'::date then {$deliverySales2}
                                    else 0 end) as before_sales_2
                                ,sum(case
                                    when delivery_header.receivable_report_timing = 1 and delivery_header.inspection_date < '{$fromDate}'::date then {$deliveryTax2}
                                    when delivery_header.receivable_report_timing = 0 and delivery_header.delivery_date < '{$fromDate}'::date then {$deliveryTax2}
                                    else 0 end) as before_sales_tax_2
                                ,sum(case
                                    when delivery_header.receivable_report_timing = 1 and delivery_header.inspection_date >= '{$fromDate}'::date and delivery_header.inspection_date <= '{$toDate}'::date then {$deliverySales2}
                                    when delivery_header.receivable_report_timing = 0 and delivery_header.delivery_date >= '{$fromDate}'::date and delivery_header.delivery_date <= '{$toDate}'::date then {$deliverySales2}
                                    else 0 end) as sales_2
                                ,sum(case
                                    when delivery_header.receivable_report_timing = 1 and delivery_header.inspection_date >= '{$fromDate}'::date and delivery_header.inspection_date <= '{$toDate}'::date then {$deliveryTax2}
                                    when delivery_header.receivable_report_timing = 0 and delivery_header.delivery_date >= '{$fromDate}'::date and delivery_header.delivery_date <= '{$toDate}'::date then {$deliveryTax2}
                                    else 0 end) as sales_tax_2
                            from
                                delivery_header
                                inner join delivery_detail on delivery_header.delivery_header_id = delivery_detail.delivery_header_id
                                left join t_last_balance on delivery_header.bill_customer_id = t_last_balance.customer_id
                            where
                                case when delivery_header.receivable_report_timing = 1 then delivery_header.inspection_date > coalesce(last_close_date,'1970-01-01')
                                    else delivery_header.delivery_date > coalesce(last_close_date,'1970-01-01') end
                                /* 2: 納品明細単位 */
                                and delivery_header.tax_category = 2
                            group by
                                delivery_header.bill_customer_id
                            ) as temp_delivery on delivery_header.bill_customer_id = temp_delivery.bill_customer_id
                        /* 0: 請求書単位は期間外と期間内を別途集計する */
                        /* 月度毎の計算になるためサブクエリが肥大化しているため要今後改善 */
                        left join (
                            select
                                bill_customer_id
                                ,sum(before_sales_0) as before_sales_0
                                ,sum(before_sales_tax_0) as before_sales_tax_0
                                ,sum(sales_0) as sales_0
                                ,sum(sales_tax_0) as sales_tax_0
                            from (
                                select
                                    bill_customer_id
                                    ,delivery_year
                                    ,delivery_month
                                    ,sum(before_sales_0) as before_sales_0
                                    ,sum(gen_round_precision((before_sales_0 * tax_rate / 100), rounding, precision)) as before_sales_tax_0
                                    ,sum(sales_0) as sales_0
                                    ,sum(gen_round_precision((sales_0 * tax_rate / 100), rounding, precision)) as sales_tax_0
                                from (
                                    select
                                        delivery_header.bill_customer_id
                                        ,delivery_header.rounding
                                        ,delivery_header.precision
                                        ,delivery_header.foreign_currency_id
                                        ,delivery_detail.tax_rate
                                        ,case when delivery_header.receivable_report_timing = 1 then date_part('YEAR', delivery_header.inspection_date)
                                            else date_part('YEAR', delivery_header.delivery_date) end as delivery_year
                                        ,case when delivery_header.receivable_report_timing = 1 then date_part('MONTH', delivery_header.inspection_date)
                                            else date_part('MONTH', delivery_header.delivery_date) end as delivery_month
                                        ,sum(case
                                            when delivery_header.receivable_report_timing = 1 and delivery_header.inspection_date < '{$fromDate}'::date then {$deliverySales3}
                                            when delivery_header.receivable_report_timing = 0 and delivery_header.delivery_date < '{$fromDate}'::date then {$deliverySales3}
                                            else 0 end) as before_sales_0
                                        ,sum(case
                                            when delivery_header.receivable_report_timing = 1 and delivery_header.inspection_date >= '{$fromDate}'::date and delivery_header.inspection_date <= '{$toDate}'::date then {$deliverySales3}
                                            when delivery_header.receivable_report_timing = 0 and delivery_header.delivery_date >= '{$fromDate}'::date and delivery_header.delivery_date <= '{$toDate}'::date then {$deliverySales3}
                                            else 0 end) as sales_0
                                    from
                                        delivery_header
                                        inner join delivery_detail on delivery_header.delivery_header_id = delivery_detail.delivery_header_id
                                        left join t_last_balance on delivery_header.bill_customer_id = t_last_balance.customer_id
                                    where
                                        case when delivery_header.receivable_report_timing = 1 then delivery_header.inspection_date > coalesce(last_close_date,'1970-01-01')
                                            else delivery_header.delivery_date > coalesce(last_close_date,'1970-01-01') end
                                        /* 0: 請求書単位 */
                                        and delivery_header.tax_category = 0
                                    group by
                                        delivery_header.bill_customer_id
                                        ,delivery_header.rounding
                                        ,delivery_header.precision
                                        ,delivery_header.foreign_currency_id
                                        ,delivery_detail.tax_rate
                                        ,case when delivery_header.receivable_report_timing = 1 then date_part('YEAR', delivery_header.inspection_date)
                                            else date_part('YEAR', delivery_header.delivery_date) end
                                        ,case when delivery_header.receivable_report_timing = 1 then date_part('MONTH', delivery_header.inspection_date)
                                            else date_part('MONTH', delivery_header.delivery_date) end
                                    ) as t_delivery
                                group by
                                    bill_customer_id
                                    ,delivery_year
                                    ,delivery_month
                                ) as t_delivery_year_month
                            group by
                                bill_customer_id
                            ) as temp_bill on delivery_header.bill_customer_id = temp_bill.bill_customer_id
                    where
                        case when delivery_header.receivable_report_timing = 1 then delivery_header.inspection_date > coalesce(last_close_date,'1970-01-01')
                            else delivery_header.delivery_date > coalesce(last_close_date,'1970-01-01') end
                    group by
                        delivery_header.bill_customer_id
                        {$deliveryCurrencyId}
            ";
        } elseif ($dataMode == '2') {
            // 請求ベース
            $billCurrencyId = (!$yenMode ? ",bill_header.foreign_currency_id" : "");
            $billSales = (!$yenMode ? "case when foreign_currency_id is null then bill_header.sales_amount else bill_header.foreign_currency_sales_amount end" : "bill_header.sales_amount");
            $billTax = (!$yenMode ? "case when foreign_currency_id is null then bill_header.tax_amount end" : "bill_header.tax_amount");
            $query .= "
                    select
                        bill_header.customer_id
                        {$billCurrencyId}

                        /* 期間前： 請求額、税額 */
                        ,sum(case when bill_header.close_date < '{$fromDate}'::date then {$billSales} end) as before_sales
                        ,sum(case when bill_header.close_date < '{$fromDate}'::date then {$billTax} end) as before_sales_tax

                        /* 期間内： 請求額、税額 */
                        ,sum(case when bill_header.close_date >= '{$fromDate}'::date and bill_header.close_date <= '{$toDate}'::date then {$billSales} end) as sales
                        ,sum(case when bill_header.close_date >= '{$fromDate}'::date and bill_header.close_date <= '{$toDate}'::date then {$billTax} end) as sales_tax
                    from
                        bill_header
                        left join t_last_balance on bill_header.customer_id = t_last_balance.customer_id
                    where
                        bill_header.close_date > coalesce(last_close_date,'1970-01-01')
                    group by
                        bill_header.customer_id
                        {$billCurrencyId}
            ";
        } else {
            // 受注ベース
            // すべて納品明細単位で計算する。（請求モード及び納品モードの納品明細単位と合致する。）
            $receivedCurrencyId = (!$yenMode ? ",t_detail.foreign_currency_id" : "");
            $receivedPrice = (!$yenMode ? "case when t_detail.foreign_currency_id is null then t_detail.product_price else t_detail.foreign_currency_product_price end" : "t_detail.product_price");
            $receivedSales = "gen_round_precision({$receivedPrice} * t_detail.received_quantity, t_bill_customer.rounding, t_bill_customer.precision)";
            $receivedTax = "case when t_detail.foreign_currency_id is null then gen_round_precision({$receivedPrice} * t_detail.received_quantity * t_detail.received_tax_rate / 100, t_bill_customer.rounding, t_bill_customer.precision) end";
            $query .= "
                    select
                        coalesce(customer_master.bill_customer_id,customer_master.customer_id) as customer_id
                        {$receivedCurrencyId}

                        /* 期間前： 請求額、税額 */
                        ,sum(case when t_detail.dead_line < '{$fromDate}'::date then {$receivedSales} end) as before_sales
                        ,sum(case when t_detail.dead_line < '{$fromDate}'::date then {$receivedTax} end) as before_sales_tax

                        /* 期間内： 請求額、税額 */
                        ,sum(case when t_detail.dead_line >= '{$fromDate}'::date and t_detail.dead_line <= '{$toDate}'::date then {$receivedSales} end) as sales
                        ,sum(case when t_detail.dead_line >= '{$fromDate}'::date and t_detail.dead_line <= '{$toDate}'::date then {$receivedTax} end) as sales_tax
                    from
                        received_header
                        inner join (
                            select
                                received_detail.*
                                /* 消費税率の取得 */
                                ,coalesce(coalesce(item_master.tax_rate,tax_rate_master.tax_rate),0) as received_tax_rate
                            from
                                received_detail
                                inner join item_master on received_detail.item_id = item_master.item_id
                                left join (
                                    select
                                        received_detail_id
                                        ,max(apply_date) as max_apply_date
                                    from
                                        received_detail
                                        left join tax_rate_master on received_detail.dead_line >= tax_rate_master.apply_date
                                    group by
                                        received_detail_id
                                    ) as t_max_apply_date on received_detail.received_detail_id = t_max_apply_date.received_detail_id
                                left join tax_rate_master on t_max_apply_date.max_apply_date = tax_rate_master.apply_date
                            ) as t_detail on received_header.received_header_id = t_detail.received_header_id
                        inner join customer_master on received_header.customer_id = customer_master.customer_id
                        inner join customer_master as t_bill_customer on coalesce(customer_master.bill_customer_id, customer_master.customer_id) = t_bill_customer.customer_id
                        left join t_last_balance on coalesce(customer_master.bill_customer_id,customer_master.customer_id) = t_last_balance.customer_id
                    where
                        t_detail.dead_line > coalesce(last_close_date,'1970-01-01')
                    group by
                        coalesce(customer_master.bill_customer_id,customer_master.customer_id)
                        {$receivedCurrencyId}
            ";
        }

        // 各モード共通
        $query .= "
                ) as t_invoice on customer_master.customer_id = t_invoice.customer_id
                " . (!$yenMode ? " and coalesce(t_currency.currency_id,-99999) = coalesce(t_invoice.foreign_currency_id,-99999)" : "") . "
        ";

        // 入金データ
        // 売掛残高確定日前の入金は対象外であることに注意
        $payingIn = (!$yenMode ? "case when foreign_currency_id is null then paying_in.amount else paying_in.foreign_currency_amount end" : "paying_in.amount");
        $psyingInCurrencyId = (!$yenMode ? ",paying_in.foreign_currency_id" : "");
        $payingInJoin = (!$yenMode ? " and coalesce(t_currency.currency_id,-99999) = coalesce(t_paying_in.foreign_currency_id,-99999)" : "");
        $payingInDetail = "";
        for ($i = 0; $i <= 10; $i++) {
            /* 0:登録無し 1:現金 2:振込み 3:小切手 4:手形 5:相殺 6:値引き 7:振込手数料 8:その他 9:先振込 10:代引 */
            $payingInDetail .= ",sum(case when paying_in.way_of_payment = {$i} and paying_in_date >= '{$fromDate}'::date and paying_in_date <= '{$toDate}'::date then {$payingIn} end) as paying_in_{$i}";
        }
        $query .= "
                left join (
                    select
                        paying_in.customer_id
                        {$psyingInCurrencyId}
                        /* 入金額（期間前） */
                        ,sum(case when paying_in_date < '{$fromDate}'::date then {$payingIn} end) as before_paying_in
                        /* 入金額（期間後） */
                        ,sum(case when paying_in_date >= '{$fromDate}'::date and paying_in_date <= '{$toDate}'::date then {$payingIn} end) as paying_in
                        {$payingInDetail}
                    from
                        paying_in
                        /* 締日の取得 */
                        left join t_last_balance on paying_in.customer_id = t_last_balance.customer_id
                    where
                        paying_in_date > coalesce(last_close_date,'1970-01-01')
                    group by
                        paying_in.customer_id
                        {$psyingInCurrencyId}
                    ) as t_paying_in on customer_master.customer_id = t_paying_in.customer_id {$payingInJoin}
        ";
        $query .= "
                /* 得意先のみ表示する */
                where customer_master.classification=0
            ) as t_sales
        where
            " . ($showCustomer ? "coalesce(receivable_bill_customer_id,-1) = -1 and not receivable_end_customer" : "(before_sales <> 0 or paying_in <> 0 or sales <> 0)") . "
            " . (isset($customerId) && is_numeric($customerId) ? " and t_sales.customer_id = '{$customerId}'" : '') . "
        ";

        // 1セッション中で同じテーブルを複数回作成する可能性があるときは、CREATE TEMP TABLE文ではなくこのメソッドを使う
        $gen_db->createTempTable("temp_receivable", $query, true);
     
        return true;
    }

    // 回収予定データをテンポラリテーブルとして作成する。
    //  $fromDate:          期間開始日
    //　$toDate:            期間終了日
    //  $dateSpan:          日付間隔
    //  $yenMode:           外貨の扱い（true:基軸通貨換算、false:取引通貨別）

    static function createTempCollectTable($fromDate, $toDate, $dateSpan, $yenMode, $dateColumnCount, $customerId = false)
    {
        global $gen_db;
        // 締日データ（10・20・末 or 月次）
        $arr = self::getCollectCloseData($fromDate, $toDate, $dateSpan, $dateColumnCount);
        $begin = $arr[0];
        $close = $arr[1];

        // データの取得
        $keyCurrency = $gen_db->queryOneValue("select key_currency from company_master");
        $billPattern = Gen_Option::getBillPattern('list-query');
        $query = "
        select
            customer_master.customer_id
            ," . ($yenMode ? "'{$keyCurrency}'" : "MAX(case when bill_header.foreign_currency_id is null then '{$keyCurrency}' else currency_name end)") . " as currency_name

            ,max(customer_master.customer_no) as customer_no
            ,max(customer_master.customer_name) as customer_name
            ,max(case customer_master.bill_pattern {$billPattern} end) as bill_pattern
            ,max(customer_master.monthly_limit_date) as monthly_limit_date
            ,max(case when receivable_cycle1 is not null then
                cast(receivable_cycle1 as text) || '" . _g("日後") . "'
                else case when receivable_cycle2_month is not null and receivable_cycle2_day is not null then
                cast(receivable_cycle2_month as text) || '" . _g("ヶ月後の") . "' || cast(receivable_cycle2_day as text) || '" . _g("日") . "'
                end end) as collect_cycle
            ,sum(" . ($yenMode ? 'sales_amount' : 'case when bill_header.foreign_currency_id is null then sales_amount else foreign_currency_sales_amount end') . " + coalesce(bill_header.tax_amount,0)) as total_sales_with_tax
            ,sum(" . ($yenMode ? 'sales_amount' : 'case when bill_header.foreign_currency_id is null then sales_amount else foreign_currency_sales_amount end') . ") as total_sales
            ,sum(coalesce(bill_header.tax_amount, 0)) as total_tax
        ";
        for ($i = 0; $i < $dateColumnCount; $i++) {
            $query .= ",max('" . (strtotime($close[$i]) > strtotime($toDate) ? '' : $close[$i]) . "') as collect_date_" . ($i + 1);
            $query .= ",sum(case when bill_header.receivable_date between '{$begin[$i]}' and '{$close[$i]}' then " . ($yenMode ? 'sales_amount' : 'case when bill_header.foreign_currency_id is null then sales_amount else foreign_currency_sales_amount end') . " + coalesce(bill_header.tax_amount, 0) end) as sales_with_tax_" . ($i + 1);
            $query .= ",sum(case when bill_header.receivable_date between '{$begin[$i]}' and '{$close[$i]}' then " . ($yenMode ? 'sales_amount' : 'case when bill_header.foreign_currency_id is null then sales_amount else foreign_currency_sales_amount end') . " end) as sales_" . ($i + 1);
            $query .= ",sum(case when bill_header.receivable_date between '{$begin[$i]}' and '{$close[$i]}' then bill_header.tax_amount end) as tax_" . ($i + 1);
        }
        $query .= "
        from
            bill_header
            inner join customer_master on bill_header.customer_id = customer_master.customer_id
            " . ($yenMode ? '' : 'left join currency_master on bill_header.foreign_currency_id = currency_master.currency_id') . "
        where
            bill_header.receivable_date between '{$fromDate}'::date and '{$toDate}'::date
            /* かつては締め請求のみだったが、現在は都度請求も含まれる */
            /* and customer_master.bill_pattern = 2 */
            " . (is_numeric($customerId) ? " and customer_id = '{$customerId}'" : '') . "
        group by
            customer_master.customer_id" . ($yenMode ? '' : ',bill_header.foreign_currency_id') . "
        ";

        // 1セッション中で同じテーブルを複数回作成する可能性があるときは、CREATE TEMP TABLE文ではなくこのメソッドを使う
        $gen_db->createTempTable("temp_collect", $query, true);

        return true;
    }

    // 回収予定表用sub。ListやReportクラスからも呼ばれる
    static function getCollectCloseData($fromDate, $toDate, $dateSpan, $dateColumnCount)
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