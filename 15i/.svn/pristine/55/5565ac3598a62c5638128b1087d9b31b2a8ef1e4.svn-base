<?php

class Logic_Bill
{

    //************************************************
    //  請求書データの作成
    //************************************************
    //  bill_header, bill_detail テーブルにデータが作成される。
    //
    //  締め請求：
    //      「その得意先の前回請求締日の翌日」から、指定された締日までの納品・入金データが対象となる。
    //  都度請求：
    //      指定された納品書が対象となる。
    //
    //  引数：
    //      $pattern            請求パターン  0:締め, 2:都度
    //      $closeDate          締日（この日までのデータを対象とする）
    //      $detailDisplay      明細表示  0:全表示, 1:数量０を非表示
    //      $customerArr        対象得意先idの配列
    //      $deliveryHeaderId   納品ヘッダーid
    //  返値：
    //      締め請求            請求書id配列
    //      都度請求            請求書id(単体)
    //
    static function makeBillData($pattern, $closeDate, $detailDisplay, $customerArr, $deliveryHeaderId)
    {
        global $gen_db;
        $CLASS_FUNCTION = __CLASS__ . "::" . __FUNCTION__;

        $gen_db->begin();

        if ($pattern == 2) {
            //---------------------------------------------
            //  都度請求
            //---------------------------------------------
            // ******** 回収予定日の計算（テンポラリテーブル temp_cycle_date） ********
            Logic_Customer::makeCycleDateTable($closeDate, true, null);

            // ******** ヘッダデータ ********
            //  明細データ登録時に bill_header_id が必要になるため、先にヘッダデータを用意する。
            //  ここでは一部の項目のみ。今回請求額等はあとでupdateする。
            //
            $query = "
            insert into bill_header (
                customer_id
                ,begin_date
                ,close_date
                ,receivable_date
                ,before_amount
                ,paying_in
                ,sales_amount
                ,tax_amount
                ,bill_amount
                ,rounding
                ,precision
                ,tax_category
                ,bill_pattern
                ,foreign_currency_id
                ,foreign_currency_before_amount
                ,foreign_currency_paying_in
                ,detail_display

                ,record_creator
                ,record_create_date
                ,record_create_func
            )
            select
                delivery_header.bill_customer_id
                ,'1970-01-01' as begin_date
                ,'1970-01-01' as close_date                     /* 処理中フラグ */
                ,temp_cycle_date.cycle_date as receivable_date  /* 回収予定日 */
                ,0 as before_amount                             /* 売掛残高 */
                ,0 as paying_in                                 /* 今回入金額 */
                ,0
                ,0
                ,0
                ,delivery_header.rounding
                ,delivery_header.precision
                ,delivery_header.tax_category
                ,delivery_header.bill_pattern
                ,delivery_header.foreign_currency_id
                ,0 as foreign_currency_before_amount
                ,0 as foreign_currency_paying_in
                ,{$detailDisplay} as detail_display

                ,'" . $_SESSION['user_name'] . "' as record_creator
                ,'" . date('Y-m-d H:i:s') . "' as record_create_date
                ,'" . $CLASS_FUNCTION . "' as record_create_func
            from
                delivery_header
                /* 回収予定日（Logic_Customer::makeCycleDateTable()） */
                left join temp_cycle_date on delivery_header.bill_customer_id = temp_cycle_date.customer_id
            where
                delivery_header.delivery_header_id = '{$deliveryHeaderId}'
                /* 都度請求書発行済データは含めない */
                and coalesce(delivery_header.bill_header_id,0) = 0
            ";

            $gen_db->query($query);

        } else {
            //---------------------------------------------
            //  締め請求
            //---------------------------------------------
            // ******** 最終請求情報の計算（テンポラリテーブル temp_last_close & temp_delivery_base） ********
            self::createTempLastCloseTable($closeDate, $customerArr);

            // ******** 回収予定日の計算（テンポラリテーブル temp_cycle_date） ********
            Logic_Customer::makeCycleDateTable($closeDate, true, null);

            // ******** ヘッダデータ ********
            //  明細データ登録時に bill_header_id が必要になるため、先にヘッダデータを用意する。
            //  ここでは一部の項目のみ。今回請求額等はあとでupdateする。
            //
            // *** 2013/07/05 comment ***
            //  売掛残高の計算は「締め請求のみ。前回請求の時点から小数点の丸め桁数が変更された場合のため、ここで丸めておく」
            //  という仕様であったが、前回請求額を丸めなおしてしまうと売掛上不整合が生じてしまうので、丸めない。
            //
            $query = "
            insert into bill_header (
                customer_id
                ,begin_date
                ,close_date
                ,receivable_date
                ,before_amount
                ,paying_in
                ,sales_amount
                ,tax_amount
                ,bill_amount
                ,rounding
                ,precision
                ,tax_category
                ,bill_pattern
                ,foreign_currency_id
                ,foreign_currency_before_amount
                ,foreign_currency_paying_in
                ,detail_display

                ,record_creator
                ,record_create_date
                ,record_create_func
            )
            select
                temp_delivery_base.customer_id
                ,temp_delivery_base.last_close_date + cast('1 days' as interval)
                /* 処理中フラグ */
                ,'1970-01-01' as close_date
                /* 回収予定日 */
                ,temp_cycle_date.cycle_date as receivable_date
                ,case when temp_delivery_base.bill_pattern = 1 then temp_delivery_base.last_close_amount else 0 end as before_amount
                /* 今回入金額。締め請求のみ。金額の小数点以下桁数指定機能の実装に伴い、round処理はやめた */
                ,coalesce(case when temp_delivery_base.bill_pattern = 1 then temp_delivery_base.paying_in_amount else 0 end,0) as paying_in
                ,0
                ,0
                ,0
                ,temp_delivery_base.rounding
                ,temp_delivery_base.precision
                ,temp_delivery_base.tax_category
                ,temp_delivery_base.bill_pattern
                ,temp_delivery_base.foreign_currency_id
                ,case when temp_delivery_base.bill_pattern = 1 then temp_delivery_base.foreign_currency_last_close_amount else 0 end as foreign_currency_before_amount
                ,case when temp_delivery_base.foreign_currency_id is not null then coalesce(case when temp_delivery_base.bill_pattern = 1 then temp_delivery_base.foreign_currency_paying_in_amount else 0 end,0)
                    end as foreign_currency_paying_in
                ,{$detailDisplay} as detail_display

                ,'" . $_SESSION['user_name'] . "' as record_creator
                ,'" . date('Y-m-d H:i:s') . "' as record_create_date
                ,'" . $CLASS_FUNCTION . "' as record_create_func
            from
                temp_delivery_base
                /* 回収予定日（Logic_Customer::makeCycleDateTable()） */
                left join temp_cycle_date on temp_delivery_base.customer_id = temp_cycle_date.customer_id
            where
                /* 対象データをピックアップ */
                /*  締め（残高表示有）： 対象期間に納品か入金が存在する取引先 */
                /*  締め（残高表示無）： 対象期間に納品がある取引先（入金のみの場合は請求は発生しない） */
                ((temp_delivery_base.bill_pattern = 1 and (temp_delivery_base.delivery_count <> 0 or temp_delivery_base.paying_count <> 0))
                    or (temp_delivery_base.bill_pattern = 0 and temp_delivery_base.delivery_count <> 0))
                /* 請求締日以降に請求書が発行されている得意先を除外する */
                and temp_delivery_base.customer_id not in (select bill_header.customer_id from bill_header
                    inner join customer_master on bill_header.customer_id = customer_master.customer_id
                    where close_date >= '{$closeDate}'::date)
                /* 条件が複数存在する得意先は請求書を作成させない */
                and temp_delivery_base.customer_id not in (select customer_id from temp_delivery_base group by customer_id having count(customer_id) > 1)
                " . (isset($customerArr) && is_array($customerArr) ? " and temp_delivery_base.customer_id in (" . join(",", $customerArr) . ") " : "") . "
            ";
            $gen_db->query($query);
        }

        //---------------------------------------------
        //  共通処理
        //---------------------------------------------
        // ******** bill_header_idリスト ********
        // 今回作成されたデータのidリストを返す必要がある。ここで作成しておく
        // 総務部の要望により、取引先コード順に請求書番号を付番
        $query = "
        select
            bill_header_id
        from
            bill_header
            inner join customer_master on bill_header.customer_id = customer_master.customer_id
        where
            close_date = '1970-01-01'::date
        order by
            customer_no
        ";
        $arr = $gen_db->getArray($query);
        if (!is_array($arr)) {
            $gen_db->commit();
            if ($pattern == 2) {
                return "";
            }
            return array();      // データなし
        }
        $idArr = array();
        foreach ($arr as $row) {
            $idArr[] = $row['bill_header_id'];
            $billHeaderId = $row['bill_header_id'];     // 都度請求用
            // ******** bill_headerに請求書番号を挿入 ********
            // idリストの作成と同時に請求書番号を作成
            $billNumber = Logic_NumberTable::getMonthlyAutoNumber(GEN_PREFIX_BILL_NUMBER, 'bill_number', 5, '');
            $query = "update bill_header set bill_number = '{$billNumber}' where bill_header_id = '{$row['bill_header_id']}'";
            $gen_db->query($query);
        }

        // ******** delivery_headerに請求書idを記録 ********
        if ($pattern == 2) {
            //---------------------------------------------
            //  都度請求
            //---------------------------------------------
            $query = "update delivery_header set bill_header_id = '{$billHeaderId}' where delivery_header_id = '{$deliveryHeaderId}'";
            $gen_db->query($query);
        } else {
            //---------------------------------------------
            //  締め請求
            //---------------------------------------------
            $query = "
            update
                delivery_header
            set
                bill_header_id = t_bill.bill_header_id
            from
                (select bill_header_id, customer_id, rounding, precision, tax_category, bill_pattern
                    from bill_header where bill_header_id in (" . join(",", $idArr) . ")) as t_bill
                inner join temp_last_close on t_bill.customer_id = temp_last_close.customer_id
            where
                delivery_header.bill_customer_id = t_bill.customer_id
                and delivery_header.rounding = t_bill.rounding
                and delivery_header.precision = t_bill.precision
                and delivery_header.tax_category = t_bill.tax_category
                and delivery_header.bill_pattern = t_bill.bill_pattern
                and case when delivery_header.receivable_report_timing = 1 then inspection_date > last_close_date and inspection_date <= '{$closeDate}'::date
                        else delivery_date > last_close_date and delivery_date <= '{$closeDate}'::date end
            ";
            $gen_db->query($query);
        }

        // ******** 明細データ ********
        $query = "
        insert into bill_detail (
            bill_header_id
            ,delivery_date
            ,inspection_date
            ,delivery_detail_id
            ,delivery_no
            ,line_no
            ,received_number
            ,customer_received_number
            ,received_line_no
            ,received_seiban
            ,item_id
            ,item_code
            ,item_name
            ,measure
            ,quantity
            ,price
            ,amount
            ,tax
            ,tax_rate
            ,delivery_note_amount
            ,delivery_note_tax
            ,tax_class
            ,lot_no
            ,remarks

            ,foreign_currency_rate
            ,foreign_currency_price
            ,foreign_currency_amount
            ,foreign_currency_tax
            ,foreign_currency_delivery_note_amount

            ,received_customer_id
            ,delivery_customer_id

            ,record_creator
            ,record_create_date
            ,record_create_func
        )
        select
            bill_header.bill_header_id
            ,delivery_header.delivery_date
            ,delivery_header.inspection_date
            ,delivery_detail.delivery_detail_id
            ,delivery_header.delivery_no
            ,delivery_detail.line_no
            ,received_header.received_number
            ,received_header.customer_received_number
            ,received_detail.line_no
            ,received_detail.seiban
            ,item_master.item_id
            ,item_master.item_code
            ,item_master.item_name
            ,item_master.measure
            ,delivery_detail.delivery_quantity as quantity
            ,delivery_detail.delivery_price as price
            ,delivery_detail.delivery_amount as amount
            ,delivery_detail.delivery_tax as tax
            ,delivery_detail.tax_rate
            ,delivery_header.delivery_note_amount
            ,delivery_header.delivery_note_tax
            ,delivery_detail.tax_class
            ,delivery_detail.use_lot_no
            ,delivery_detail.remarks

            ,delivery_header.foreign_currency_rate as foreign_currency_rate
            ,delivery_detail.foreign_currency_delivery_price as foreign_currency_price
            ,delivery_detail.foreign_currency_delivery_amount as foreign_currency_amount
            ,delivery_detail.foreign_currency_delivery_tax as foreign_currency_tax
            ,delivery_header.foreign_currency_delivery_note_amount

            ,received_header.customer_id as received_customer_id
            ,delivery_header.delivery_customer_id as delivery_customer_id

            ,'" . $_SESSION['user_name'] . "' as record_creator
            ,'" . date('Y-m-d H:i:s') . "' as record_create_date
            ,'" . $CLASS_FUNCTION . "' as record_create_func
        from
            delivery_detail
            inner join delivery_header on delivery_detail.delivery_header_id = delivery_header.delivery_header_id
            inner join bill_header on delivery_header.bill_header_id = bill_header.bill_header_id
            inner join received_detail on delivery_detail.received_detail_id = received_detail.received_detail_id
            inner join received_header on received_header.received_header_id=received_detail.received_header_id
            inner join item_master on received_detail.item_id = item_master.item_id
        where
            delivery_header.bill_header_id in (" . join(",", $idArr) . ")
        order by
            delivery_header.delivery_no, delivery_detail.delivery_detail_id;
        ";
        $gen_db->query($query);

        // ******** ヘッダデータに金額を挿入 ********
        // 日付の出力形式の取得
        $query = "select excel_date_type from company_master";
        $excelDateType = $gen_db->queryOneValue($query);
        $showDate = ($excelDateType == "1" ? date('Y/n/j', strtotime($closeDate)) : $closeDate);

        $query = "
        update
            bill_header
        set
            close_date = '{$closeDate}'::date
            ,close_date_show = t_detail.close_date_show
            ,sales_amount = coalesce(t_detail.amount,0)
            ,tax_amount = coalesce(t_detail.tax_amount,0)
            ,bill_amount = coalesce(bill_header.before_amount,0) - coalesce(bill_header.paying_in,0)
                + coalesce(t_detail.amount,0) + coalesce(t_detail.tax_amount,0)
            ,foreign_currency_sales_amount = t_detail.foreign_currency_amount
            ,foreign_currency_tax_amount = t_detail.foreign_currency_tax_amount
            ,foreign_currency_bill_amount =
                case when bill_header.foreign_currency_id is not null then
                    coalesce(bill_header.foreign_currency_before_amount,0) - coalesce(bill_header.foreign_currency_paying_in,0)
                    + coalesce(t_detail.foreign_currency_amount,0) + coalesce(t_detail.foreign_currency_tax_amount,0)
                end
        from
            (select
                bill_header.bill_header_id
                ,max(case when bill_header.bill_pattern = 1 then '{$showDate}' || '" . _g("締切分") . "'
                    else '{$showDate}' || '" . _g("発行") . "' end) as close_date_show
                /* 売上金額は納品登録の時点で丸め済み */
                ,sum(bill_detail.amount) as amount
                /* 税計算単位による計算 */
                ,case max(bill_header.tax_category)
                    when 1 then      /* 1: 納品書単位 */
                        max(t_delivery.delivery_note_tax)
                    when 2 then      /* 2: 納品明細単位 */
                        sum(bill_detail.tax)
                    else             /* 0: 請求書単位 */
                        gen_round_precision(sum(bill_detail.amount * bill_detail.tax_rate / 100), max(bill_header.rounding), max(bill_header.precision))
                    end as tax_amount
                ,sum(bill_detail.foreign_currency_amount) as foreign_currency_amount
                /* 税計算単位による計算 */
                ,case max(bill_header.tax_category)
                    when 1 then      /* 1: 納品書単位 */
                        max(t_delivery.foreign_currency_delivery_note_tax)
                    when 2 then      /* 2: 納品明細単位 */
                        sum(bill_detail.foreign_currency_tax)
                    else             /* 0: 請求書単位 */
                        gen_round_precision(sum(bill_detail.foreign_currency_amount * bill_detail.tax_rate / 100), max(bill_header.rounding), max(bill_header.precision))
                    end as foreign_currency_tax_amount
           from
               bill_header
               left join bill_detail on bill_detail.bill_header_id = bill_header.bill_header_id
               left join (select bill_header_id, sum(delivery_note_tax) as delivery_note_tax, sum(foreign_currency_delivery_note_tax) as foreign_currency_delivery_note_tax
	           from delivery_header group by bill_header_id) as t_delivery on bill_header.bill_header_id = t_delivery.bill_header_id
            group by
                /* bill_header は（今回締日分に関しては） customer_id ごとにユニークになっている */
                /* （上のほうの where 作成部で、その取引先の最終請求書の締日以前のデータは入らないようにしているため。） */
                /* したがってこの Group By は得意先ごとの集計になっている */
                bill_header.bill_header_id
            ) as t_detail
        where
            bill_header.bill_header_id = t_detail.bill_header_id
            and bill_header.bill_header_id in (" . join(",", $idArr) . ")
        ";
        $gen_db->query($query);

        $gen_db->commit();

        if ($pattern == 2) {
            return $billHeaderId;
        }
        return $idArr;
    }

    //  最終請求データおよび請求先データをテンポラリテーブルとして作成
    //
    //  temp_last_close     ：最終請求データ
    //  temp_delivery_base  ：請求先データ
    //
    //  $closeDate          ：最終請求を算出する基準日（$closeDateより前の期間が算出対象）
    //　$customerArr        ：取引先id(配列)

    static function createTempLastCloseTable($closeDate, $customerArr)
    {
        global $gen_db;

        // ******** 取引先別の請求対象期間の開始日と残高 ＆ 納品ベースの請求先情報作成（テンポラリテーブル temp_delivery_base） ********
        // 各取引先の請求対象期間の開始日（最終請求書の締日、もしくは取引先マスタの売掛基準日　のどちらか遅いほう）と残高を取得。
        // 請求書がなく売掛基準日も設定されていない取引先については、「1970-01-01」になる
        //
        // *** 2013/07/05 comment ***
        //  これまでは「取引先の取引通貨が途中で変わったり、取引通貨が異なる複数の得意先がひとつの請求先を指定していた場合は、取引通貨ごとに請求書が作られる」
        //  という仕様だったが、取引先の取引通貨は受注データが作成された時点で変更できなくなる。
        //  さらに、請求先が指定されていた場合、その得意先の受注および納品は請求先の取引通貨単位で取引される。
        //  よって、各取引通貨に請求書を対応させない。一取引先に対して、複数の取引通貨が存在すると売掛の計算が容易でない。
        //
        $query = "
        /* 最終請求情報を作成 */
        select
            customer_master.customer_id
            ,customer_master.monthly_limit_date
            ,coalesce(case when coalesce(t_last_bill.close_date,'1970-01-01') > coalesce(last_opening_date,'1970-01-01') then t_last_bill.close_date
                else last_opening_date end, '1970-01-01') as last_close_date
            ,coalesce(case when coalesce(t_last_bill.close_date,'1970-01-01') > coalesce(last_opening_date,'1970-01-01') then t_last_bill.bill_amount
                else last_opening_balance * (case when customer_master.currency_id is null then 1 else rate_master.rate end) end, 0) as last_close_amount
            ,coalesce(case when coalesce(t_last_bill.close_date,'1970-01-01') > coalesce(last_opening_date,'1970-01-01') then
                (case when customer_master.currency_id is null then null else t_last_bill.foreign_currency_bill_amount end)
                else last_opening_balance end, 0) as foreign_currency_last_close_amount
        from
            customer_master
            /* 取引先マスタの売掛基準を取得 */
            inner join (
                select
                    customer_id
                    ,case when coalesce(opening_date,'1970-01-01') < '{$closeDate}'::date then coalesce(opening_date,'1970-01-01') end as last_opening_date
                    ,case when coalesce(opening_date,'1970-01-01') < '{$closeDate}'::date then opening_balance end as last_opening_balance
                from
                    customer_master
                ) as t_opening on customer_master.customer_id = t_opening.customer_id
            /* opening_balance の換算用 */
            /* 売掛残高基準日時点のレートを適用しているが検討の余地あり。請求書には共通の適用レートがない（各売上時点のレートを適用）ので難しい */
            left join (
                select
                    customer_id
                    ,customer_master.currency_id as currency_id
                    ,max(rate_date) as rate_date
                from
                    customer_master
                    inner join rate_master on customer_master.currency_id = rate_master.currency_id
                where
                    case when coalesce(opening_date,'1970-01-01') < '{$closeDate}'::date then rate_date <= opening_date else rate_date <= '{$closeDate}'::date end
                group by
                    customer_id
                    ,customer_master.currency_id
                ) as t_rate_prev on customer_master.customer_id = t_rate_prev.customer_id
            left join rate_master on t_rate_prev.currency_id = rate_master.currency_id and t_rate_prev.rate_date = rate_master.rate_date
            left join (
                select
                    customer_id
                    ,foreign_currency_id
                    ,max(close_date) as last_close_date
                from
                    bill_header
                group by
                    customer_id
                    ,foreign_currency_id
                ) as t_close on customer_master.customer_id = t_close.customer_id and coalesce(customer_master.currency_id,-99999) = coalesce(t_close.foreign_currency_id,-99999)
            left join bill_header as t_last_bill on t_close.customer_id = t_last_bill.customer_id
                and coalesce(customer_master.currency_id,-99999) = coalesce(t_last_bill.foreign_currency_id,-99999)
                and t_close.last_close_date = t_last_bill.close_date
                /* 締め請求のみを対象とする */
                and t_last_bill.bill_pattern in (0,1)
        where
            customer_master.classification = 0;
        ";
        $gen_db->createTempTable("temp_last_close", $query, true);

        $query = "
        /* 請求先情報を作成 */
        /* 入金データは存在するが納品データが存在しない場合は請求先のマスタ情報で補間する */
        select
            temp_last_close.customer_id
            ,temp_last_close.monthly_limit_date
            ,temp_last_close.last_close_date
            ,case when temp_last_close.last_close_date = '1970-01-01' then null else temp_last_close.last_close_date end as last_close_date_show
            ,temp_last_close.last_close_amount
            ,temp_last_close.foreign_currency_last_close_amount
            ,coalesce(t_delivery.rounding, case when coalesce(t_paying.paying_count,0) > 0 then t_bill_customer.rounding end) as rounding
            ,coalesce(t_delivery.precision, case when coalesce(t_paying.paying_count,0) > 0 then t_bill_customer.precision end) as precision
            ,coalesce(t_delivery.tax_category, case when coalesce(t_paying.paying_count,0) > 0 then t_bill_customer.tax_category end) as tax_category
            ,coalesce(t_delivery.bill_pattern, case when coalesce(t_paying.paying_count,0) > 0 then t_bill_customer.bill_pattern end) as bill_pattern
            ,coalesce(t_delivery.foreign_currency_id, case when coalesce(t_paying.paying_count,0) > 0 then t_bill_customer.currency_id end) as foreign_currency_id
            ,coalesce(t_delivery.delivery_count,0) as delivery_count
            ,t_delivery.min_delivery_date
            ,coalesce(t_paying.paying_count,0) as paying_count
            ,coalesce(t_paying.paying_in_amount,0) as paying_in_amount
            ,coalesce(t_paying.foreign_currency_paying_in_amount,0) as foreign_currency_paying_in_amount
        from
            temp_last_close
            inner join customer_master on temp_last_close.customer_id = customer_master.customer_id
            inner join customer_master as t_bill_customer on coalesce(customer_master.bill_customer_id,customer_master.customer_id) = t_bill_customer.customer_id
            left join (
                select
                    delivery_header.bill_customer_id as customer_id
                    ,delivery_header.rounding
                    ,delivery_header.precision
                    ,delivery_header.tax_category
                    ,delivery_header.bill_pattern
                    ,delivery_header.foreign_currency_id
                    ,count(delivery_header.bill_customer_id) as delivery_count
                    ,min(case when delivery_header.receivable_report_timing = 1 then inspection_date else delivery_date end) as min_delivery_date
                from
                    delivery_header
                    inner join temp_last_close on delivery_header.bill_customer_id = temp_last_close.customer_id
                where
                    case when delivery_header.receivable_report_timing = 1 then inspection_date > last_close_date and inspection_date <= '{$closeDate}'::date
                        else delivery_date > last_close_date and delivery_date <= '{$closeDate}'::date end
                    " . (isset($customerArr) && is_array($customerArr) ? " and delivery_header.bill_customer_id in (" . join(",", $customerArr) . ") " : "") . "
                group by
                    delivery_header.bill_customer_id
                    ,delivery_header.rounding
                    ,delivery_header.precision
                    ,delivery_header.tax_category
                    ,delivery_header.bill_pattern
                    ,delivery_header.foreign_currency_id
                ) as t_delivery on temp_last_close.customer_id = t_delivery.customer_id
            left join (
                select
                    paying_in.customer_id
                    ,count(paying_in.customer_id) as paying_count
                    ,sum(amount) as paying_in_amount
                    ,sum(foreign_currency_amount) as foreign_currency_paying_in_amount
                from
                    paying_in
                    inner join temp_last_close on paying_in.customer_id = temp_last_close.customer_id
                where
                    paying_in_date > last_close_date and paying_in_date <= '{$closeDate}'::date
                group by
                    paying_in.customer_id
                ) as t_paying on temp_last_close.customer_id = t_paying.customer_id
        ";
        $gen_db->createTempTable("temp_delivery_base", $query, true);

        return;
    }

    //************************************************
    //  印刷済みフラグのセット
    //************************************************

    static function setBillPrintedFlag($idArr, $isSet)
    {
        global $gen_db;

        $idWhere = join(",", $idArr);
        if ($idWhere == "")
            return;

        $query = "
        update
            bill_header
        set
            bill_printed_flag = " . ($isSet ? 'true' : 'false') . "
            ,record_updater = '" . $_SESSION['user_name'] . "'
            ,record_update_date = '" . date('Y-m-d H:i:s') . "'
            ,record_update_func = '" . __CLASS__ . "::" . __FUNCTION__ . "'
        where
            bill_header_id in ({$idWhere})
        ";
        $gen_db->query($query);
    }

    //************************************************
    //  指定された得意先の、最終請求締日を返す
    //************************************************

    static function getLastCloseDateByCustomerId($customerId)
    {
        global $gen_db;

        $query = "
        select
            max(close_date)
        from
            bill_header
        where
            customer_id = (select coalesce(bill_customer_id, customer_id) from customer_master where customer_id = '{$customerId}')
        ";
        return $gen_db->queryOneValue($query);
    }

    //************************************************
    //  取引先の請求書が存在するかどうかを返す
    //************************************************

    static function existBill($customerId)
    {
        global $gen_db;

        // 取引先の請求書
        $query = "select customer_id from bill_header where customer_id = {$customerId}";
        $exist1 = $gen_db->existRecord($query);

        // 請求先の請求書
        // 取引先に請求書が発行された後、請求先がマスタ上で設定される場合もあるため。
        $query = "
        select
            bill_header.customer_id
        from
            bill_header
            inner join customer_master on bill_header.customer_id = coalesce(customer_master.bill_customer_id, customer_master.customer_id)
        where
            customer_master.customer_id = {$customerId}
        ";
        $exist2 = $gen_db->existRecord($query);

        return ($exist1 || $exist2);
    }

    //************************************************
    //  締め（残高表示あり）の入金について、請求があるかどうかを返す。
    //************************************************

    static function hasBillByPayingInId($payingInId)
    {
        global $gen_db;

        // 締め（残高表示あり）の請求書が発行された場合、入金データも残高情報として計算される。
        // そのため、請求日以前の入金データは編集不可とする必要がある。
        $query = "
        select
            paying_in.paying_in_id
        from
            paying_in
            inner join (select customer_id, max(close_date) as bill_date from bill_header where bill_pattern = 1 group by customer_id) as t_bill
                on paying_in.customer_id = t_bill.customer_id and paying_in.paying_in_date < t_bill.bill_date
        where
            paying_in.paying_in_id = {$payingInId}
        ";
        return $gen_db->existRecord($query);
    }

    //************************************************
    //  納品データに対応する請求データがあるかどうかを返す
    //************************************************

    static function hasBillByDeliveryId($isDetail, $deliveryId)
    {
        global $gen_db;

        if ($isDetail) {
            // 明細モード
            $query = "
            select
                bill_detail_id
            from
                bill_detail
            where
                delivery_detail_id = {$deliveryId}
            ";
        } else {
            // ヘッダーモード
            $query = "
            select
                bill_detail.bill_detail_id
            from
                bill_detail
                left join delivery_detail on bill_detail.delivery_detail_id = delivery_detail.delivery_detail_id
            where
                delivery_detail.delivery_header_id = {$deliveryId}
            ";
        }
        return $gen_db->existRecord($query);
    }
}
