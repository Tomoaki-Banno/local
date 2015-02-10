<?php

class Delivery_ReceivableList_Report3 extends Base_PDFReportBase
{

    protected function _getQuery(&$form)
    {
        // 入金種別
        $classArray = Gen_Option::getWayOfPayment('receivable');
        $classQuery1 = Gen_Option::getWayOfPayment('list-query');
        
        $dataMode = $form['gen_search_data_mode'];
        $yenMode = ($form['gen_search_foreign_currency_mode'] == 1);
        $fromDate = $form['from_date_for_report'];
        $toDate = $form['to_date_for_report'];
        
        // この帳票ではテンプレートでのorderby指定は禁止。この並びに固定する。
        //  どの表示条件のときでも確実に並び順が固定されるようにしておかないと、日付や残高の表示で問題が発生する。
        //  最後に received_detail_id を入れているのは、受注ベースで未納品のレコードの場合でも、並び順を固定するため。
        $orderby = "t1.currency_name, customer_master.customer_no, t2.show_date, t2.line_category, t2.delivery_no, t2.line_no, t2.received_detail_id";

        // 印刷対象データの取得。
        // この帳票のような非チェックボックス方式の帳票（表示条件に合致するレコードをすべて印刷。
        // action=XXX_XXX_List&gen_report=XXX_XXX_Report として印刷）の場合、
        // gen_temp_for_report テーブルに Listクラスで取得したデータが入っている。
        $query = "
             select
                /* headers */

                t1.currency_name as page_key
                ,t1.from_date as 得意先元帳_開始日
                ,t1.to_date as 得意先元帳_終了日
                ,t1.currency_name as 得意先元帳_通貨
                ,t1.mode as 得意先元帳_金額モード
                
                ,t1.before_sales as 得意先元帳_繰越額
                ,t1.sales as 得意先元帳_期間中売上額
                ,t1.sales_tax as 得意先元帳_期間中消費税額
                ,t1.paying_in as 得意先元帳_期間中入金額
                ,t1.receivable_balance as 得意先元帳_売掛金残高
                ,t1.paying_in_1 as 得意先元帳_{$classArray[1]}
                ,t1.paying_in_2 as 得意先元帳_{$classArray[2]}
                ,t1.paying_in_3 as 得意先元帳_{$classArray[3]}
                ,t1.paying_in_4 as 得意先元帳_{$classArray[4]}
                ,t1.paying_in_5 as 得意先元帳_{$classArray[5]}
                ,t1.paying_in_6 as 得意先元帳_{$classArray[6]}
                ,t1.paying_in_7 as 得意先元帳_{$classArray[7]}
                ,t1.paying_in_8 as 得意先元帳_{$classArray[8]}
                ,t1.paying_in_9 as 得意先元帳_{$classArray[9]}
                ,t1.paying_in_10 as 得意先元帳_{$classArray[10]}
                    
                /* details */

                /* 日付と納品書番号は、ひとつ上の行と同じであれば表示しない */
                ,case when lag(t2.show_date) over(partition by t1.customer_id, t1.currency_id order by {$orderby}) = t2.show_date then null else t2.show_date end as detail_得意先元帳_日付
                ,case when lag(t2.delivery_no) over(partition by t1.customer_id, t1.currency_id order by {$orderby}) = t2.delivery_no then null else t2.delivery_no end as detail_得意先元帳_納品書番号
                ,t2.item_code as detail_得意先元帳_品目コード
                ,t2.item_name as detail_得意先元帳_品目名
                ,t2.quantity as detail_得意先元帳_数量
                ,t2.measure as detail_得意先元帳_単位
                ,t2.price as detail_得意先元帳_単価
                ,t2.amount as detail_得意先元帳_金額
                ,t2.detail_tax as detail_得意先元帳_消費税額
                ,t2.paying_in_amount as detail_得意先元帳_入金額
                /* 残高。日付ごとに計算し、日付ごとの一番最後の行に表示する。 */
                ,case when 
                    lead(t2.show_date) over(partition by t1.customer_id, t1.currency_id order by {$orderby}) = t2.show_date then 
                        null 
                    else 
                        coalesce(t1.before_sales,0) + sum(coalesce(t2.amount,0) + coalesce(t2.detail_tax,0) - coalesce(t2.paying_in_amount,0)) 
                        over(partition by t1.customer_id, t1.currency_id order by {$orderby}) 
                    end as detail_得意先元帳_明細残高
                /* ちなみに、明細行ごとに出すには次のようにする 
                ,coalesce(t1.before_sales,0) + sum(coalesce(t2.amount,0) + coalesce(t2.detail_tax,0) - coalesce(t2.paying_in_amount,0)) 
                        over(partition by t1.customer_id, t1.currency_id order by {$orderby}) 
                    as detail_得意先元帳_明細残高
                */
                
                ,spec as detail_得意先元帳_仕様
                ,maker_name as detail_得意先元帳_メーカー
                ,rack_no as detail_得意先元帳_棚番
                ,comment as detail_得意先元帳_品目備考1
            	,comment_2 as detail_得意先元帳_品目備考2
            	,comment_3 as detail_得意先元帳_品目備考3
            	,comment_4 as detail_得意先元帳_品目備考4
            	,comment_5 as detail_得意先元帳_品目備考5
                ,item_group_code_1 as detail_得意先元帳_品目グループコード1
                ,item_group_code_2 as detail_得意先元帳_品目グループコード2
                ,item_group_code_3 as detail_得意先元帳_品目グループコード3
                ,item_group_name_1 as detail_得意先元帳_品目グループ名1
                ,item_group_name_2 as detail_得意先元帳_品目グループ名2
                ,item_group_name_3 as detail_得意先元帳_品目グループ名3
            from
                /***** 得意先/取引通貨ヘッダ *****/
                (select 
                    customer_id
                    ,currency_id
                    ,max(from_date::text) as from_date
                    ,max(to_date::text) as to_date
                    ,max(currency_name) as currency_name
                    ,max(mode::text) as mode
                    ,max(before_sales) as before_sales
                    ,max(sales) as sales
                    ,max(sales_tax) as sales_tax
                    ,max(paying_in) as paying_in
                    ,max(receivable_balance) as receivable_balance
                    ,max(paying_in_1) as paying_in_1
                    ,max(paying_in_2) as paying_in_2
                    ,max(paying_in_3) as paying_in_3
                    ,max(paying_in_4) as paying_in_4
                    ,max(paying_in_5) as paying_in_5
                    ,max(paying_in_6) as paying_in_6
                    ,max(paying_in_7) as paying_in_7
                    ,max(paying_in_8) as paying_in_8
                    ,max(paying_in_9) as paying_in_9
                    ,max(paying_in_10) as paying_in_10
                from 
                    gen_temp_for_report
                group by
                    customer_id, currency_id
                ) as t1
                
                /***** 明細行 *****/
                inner join
                    (/* データ行 */
                    select
                        0 as line_category
                        ,gen_temp_for_report.customer_id
                        ,gen_temp_for_report.currency_id
                        ,gen_temp_for_report.received_header_id
                        ,gen_temp_for_report.received_detail_id
                        ,gen_temp_for_report.delivery_header_id
                        ,gen_temp_for_report.delivery_detail_id
                        ,gen_temp_for_report.delivery_no::text
                        ,gen_temp_for_report.line_no
                        ,gen_temp_for_report.item_id
                        ,case when t_delivery.receivable_report_timing = 1 then gen_temp_for_report.show_date_2 else gen_temp_for_report.show_date_1 end as show_date
                        ,gen_temp_for_report.item_code
                        ,gen_temp_for_report.item_name
                        ,gen_temp_for_report.quantity
                        ,gen_temp_for_report.measure
                        ,gen_temp_for_report.price
                        ,gen_temp_for_report.amount
                        /* 消費税額は納品明細単位の場合のみ含める。納品書単位の場合は納品書ごと、請求書単位の場合は取引先ごとに消費税行が挿入される */
                        /* ただし受注ベースの場合は常に納品明細単位として計算する（Logic_Receivable::createTempReceivableTable() と仕様を合わせる）*/
                        ,case when tax_category = 2 or " . ($dataMode == 1 ? "true" : "false") ." then gen_temp_for_report.detail_tax end as detail_tax
                        ,null::numeric as paying_in_amount
                    from
                        gen_temp_for_report
                        left join (
                            select 
                                delivery_no
                                ,max(receivable_report_timing) as receivable_report_timing 
                                ,max(tax_category) as tax_category
                            from 
                                delivery_header
                            group by
                                delivery_no
                        ) as t_delivery on gen_temp_for_report.delivery_no::text = t_delivery.delivery_no::text

                    /* 消費税行（税計算単位：納品書単位用） */
                    /* 納品書ごとに消費税行を挿入する。納品ベース・請求ベース共通。*/
                    /* ちなみに受注ベースの場合は常に納品明細単位として扱われるので、ここでは含まれない。 */
                    union all
                    select
                        1 as line_category
                        ,bill_customer_id as customer_id
                        ,foreign_currency_id as currency_id
                        ,null as received_header_id
                        ,null as received_detail_id
                        ,null as delivery_header_id
                        ,null as delivery_detail_id
                        ,delivery_no
                        ,0 as line_no
                        ,null as item_id
                        ,show_date
                        ,null as item_code
                        ,item_name
                        ,null as quantity
                        ,null as measure
                        ,null as price
                        ,null as amount
                        ,detail_tax
                        ,null::numeric as paying_in_amount
                    from
                        (
                        select
                          bill_customer_id
                          ,foreign_currency_id
                          ,delivery_no
                          ,'" . _g("消費税") . "' as item_name
                          ,case when receivable_report_timing = 1 then inspection_date else delivery_date end as show_date
                          ,delivery_note_tax as detail_tax
                        from
                          delivery_header
                        where
                          delivery_header.tax_category = 1    /* 納品書単位 */
                          and " . ($dataMode == 1 ? "false" : "true") ."    /* 受注ベースの場合は含めない */
                          and delivery_no in (select delivery_no::text from gen_temp_for_report)
                        ) as t3

                    /* 入金行 */
                    union all
                    select
                        2 as line_category
                        ,customer_id
                        ," . ($yenMode ? "null" : "foreign_currency_id") . " as currency_id
                        ,null as received_header_id
                        ,null as received_detail_id
                        ,null as delivery_header_id
                        ,null as delivery_detail_id
                        ,null as delivery_no
                        ,0 as line_no
                        ,null as item_id
                        ,paying_in_date as show_date
                        ,null as item_code
                        ,case way_of_payment {$classQuery1} end as item_name
                        ,null as quantity
                        ,null as measure
                        ,null as price
                        ,null as amount
                        ,null as detail_tax
                        ,case when foreign_currency_id is null then amount else " . ($yenMode ? "amount" : "foreign_currency_amount") . " end as paying_in_amount
                    from
                        paying_in
                    where
                        paying_in_date between '{$fromDate}'::date and '{$toDate}'::date
                        and customer_id in (select customer_id::int from gen_temp_for_report)
                    
                    /* 消費税行（税計算単位：請求書単位用） */
                    /* 取引先の最後に全体の消費税合計行を挿入する。 */
                    /* 請求書単位の分がここに含められる。ただし受注ベースの場合は常に納品明細単位として扱われるので、ここには含まれない。*/
                    union all
                    select 
                        3 as line_category
                        ,gen_temp_for_report.customer_id
                        ,gen_temp_for_report.currency_id
                        ,null as received_header_id
                        ,null as received_detail_id
                        ,null as delivery_header_id
                        ,null as delivery_detail_id
                        ,null as delivery_no
                        ,0 as line_no
                        ,null as item_id
                        ,'{$toDate}'::date as show_date
                        ,null as item_code
                        ,'" . _g("消費税") . "' as item_name
                        ,null as quantity
                        ,null as measure
                        ,null as price
                        ,null as amount
                        /* 全体の税額から、納品明細単位・納品書単位の税額を引いて請求書単位の税額を求める。 */
                        /* 税計算単位が異なる納品が混在していた場合を考慮して、このような計算を行っている */
                        ,coalesce(max(sales_tax),0) - coalesce(max(delivery_tax),0) - coalesce(max(delivery_note_tax),0) as detail_tax
                        ,null as paying_in_amount
                    from 
                        gen_temp_for_report
                        left join (
                            select 
                                customer_id
                                ,foreign_currency_id as currency_id
                                ,sum(delivery_tax) as delivery_tax
                            from 
                                delivery_detail
                                inner join delivery_header on delivery_detail.delivery_header_id = delivery_header.delivery_header_id
                            where
                                delivery_header.tax_category = 2    /* 納品明細単位 */
                                and delivery_no in (select delivery_no::text from gen_temp_for_report)
                            group by
                                customer_id, currency_id
                        ) as t_delivery_detail on gen_temp_for_report.customer_id = t_delivery_detail.customer_id and coalesce(gen_temp_for_report.currency_id,-1) = coalesce(t_delivery_detail.currency_id,-1)
                        left join (
                            select 
                                customer_id
                                ,foreign_currency_id as currency_id
                                ,sum(delivery_note_tax) as delivery_note_tax
                            from 
                                delivery_header
                            where
                                delivery_header.tax_category = 1    /* 納品書単位 */
                                and delivery_no in (select delivery_no::text from gen_temp_for_report)
                            group by
                                customer_id, currency_id
                        ) as t_delivery_note on gen_temp_for_report.customer_id = t_delivery_note.customer_id and coalesce(gen_temp_for_report.currency_id,-1) = coalesce(t_delivery_note.currency_id,-1)
                    where
                        " . ($dataMode == 1 ? "false" : "true") ."    /* 受注ベースの場合は含めない */
                    group by
                        gen_temp_for_report.customer_id, gen_temp_for_report.currency_id
                    having
                        coalesce(max(sales_tax),0) - coalesce(max(delivery_tax),0) - coalesce(max(delivery_note_tax),0) <> 0
                        
                    ) as t2 on t1.customer_id = t2.customer_id and coalesce(t1.currency_id,-1) = coalesce(t2.currency_id,-1)

                left join item_master on t2.item_id = item_master.item_id
                left join (select item_group_id as gid1, item_group_code as item_group_code_1, item_group_name as item_group_name_1
                    from item_group_master) as t_group_1 on item_master.item_group_id = t_group_1.gid1
                left join (select item_group_id as gid2, item_group_code as item_group_code_2, item_group_name as item_group_name_2
                    from item_group_master) as t_group_2 on item_master.item_group_id_2 = t_group_2.gid2
                left join (select item_group_id as gid3, item_group_code as item_group_code_3, item_group_name as item_group_name_3
                    from item_group_master) as t_group_3 on item_master.item_group_id_3 = t_group_3.gid3
                /* タグリスト自動追加用 */
                left join customer_master on t2.customer_id = customer_master.customer_id
                left join received_header on t2.received_header_id = received_header.received_header_id
                left join received_detail on t2.received_detail_id = received_detail.received_detail_id
                left join delivery_header on t2.delivery_header_id = delivery_header.delivery_header_id
                left join delivery_detail on t2.delivery_detail_id = delivery_detail.delivery_detail_id
            order by
                /* この帳票に関しては、帳票テンプレートでのorderby指定が禁止されている */
                {$orderby}
        ";

        return $query;
    }

    // テンプレート情報
    protected function _getReportParam()
    {
        global $gen_db;

        $info = array();
        $info['reportTitle'] = _g("得意先元帳");
        $info['report'] = "ReceivableLedger";
        $info['pageKeyColumn'] = "page_key";
        $info['denyOrderby'] = true;    // 帳票テンプレートでのorderby指定禁止。アップロード時にチェック

        $keyCurrency = $gen_db->queryOneValue("select key_currency from company_master");
        // 入金種別
        $classArray = Gen_Option::getWayOfPayment('receivable');
        
        // SQLのfromで指定されているテーブルのリスト。
        // ここで指定されたテーブルのカラムはSQL selectとタグリストに自動追加される。
        $info['tables'] = array(
            array("customer_master", true, ""),
            array("received_header", false, ""),
            array("received_detail", false, ""),
            array("delivery_header", false, ""),
            array("delivery_detail", false, ""),
        );

        // タグリスト（この帳票固有のもの）
        $info['tagList'] = array(
            array("●" . _g("得意先元帳　ヘッダ")),
            array("得意先元帳_開始日", _g("対象となる期間の開始日"), "2014-01-01"),
            array("得意先元帳_終了日", _g("対象となる期間の終了日"), "2014-01-31"),
            array("得意先元帳_通貨", _g("取引通貨"), $keyCurrency),
            array("得意先元帳_金額モード", _g("表示条件「金額モード」（「納品ベース」「受注ベース」「請求ベース」）。"), _g("請求ベース")),
            array("得意先元帳_繰越額", _g("期間前からの繰越額"), "100000"),
            array("得意先元帳_期間中売上額", _g("期間中の売上額（請求書）"), "80000"),
            array("得意先元帳_期間中消費税額", _g("期間中の消費税額（請求書）"), "4000"),
            array("得意先元帳_期間中入金額", _g("期間中の入金額（入金登録画面）"), "50000"),
            array("得意先元帳_売掛金残高", _g("繰越額 ＋ 期間中売上額 ＋ 期間中消費税額 － 期間中入金額"), "134000"),
            array("得意先元帳_" . $classArray[1], _g("期間中の現金入金額（入金登録画面）"), "1000"),
            array("得意先元帳_" . $classArray[2], _g("期間中の振込入金額（入金登録画面）"), "1000"),
            array("得意先元帳_" . $classArray[3], _g("期間中の小切手入金額（入金登録画面）"), "1000"),
            array("得意先元帳_" . $classArray[4], _g("期間中の手形入金額（入金登録画面）"), "1000"),
            array("得意先元帳_" . $classArray[5], _g("期間中の相殺入金額（入金登録画面）"), "1000"),
            array("得意先元帳_" . $classArray[6], _g("期間中の値引入金額（入金登録画面）"), "1000"),
            array("得意先元帳_" . $classArray[7], _g("期間中の振込手数料入金額（入金登録画面）"), "1000"),
            array("得意先元帳_" . $classArray[9], _g("期間中の先振込入金額（入金登録画面）"), "1000"),
            array("得意先元帳_" . $classArray[10], _g("期間中の代引入金額（入金登録画面）"), "1000"),
            array("得意先元帳_" . $classArray[8], _g("期間中のその他入金額（入金登録画面）"), "1000"),
            
            array("●" . _g("得意先元帳　明細")),
            array("得意先元帳_日付", _g("納品日・検収日・入金日"), "2014-01-15"),
            array("得意先元帳_納品書番号", _g("納品書番号（受注ベースを除く）"), "S140100001"),
            array("得意先元帳_品目コード", _g("品目コード"), "code001"),
            array("得意先元帳_品目名", _g("品目名"), ("テスト品目")),
            array("得意先元帳_数量", _g("数量"), "2000"),
            array("得意先元帳_単位", _g("品目マスタ [管理単位]"), "kg"),
            array("得意先元帳_単価", _g("納品単価"), "10"),
            array("得意先元帳_金額", _g("金額"), "20000"),
            array("得意先元帳_消費税額", _g("消費税額"), "1000"),
            array("得意先元帳_入金額", _g("入金額"), "10000"),
            array("得意先元帳_明細残高", _g("明細残高"), "11000"),
            array("得意先元帳_品目グループコード1", _g("品目マスタ [品目グループ1]"), _g("G001")),
            array("得意先元帳_品目グループコード2", _g("品目マスタ [品目グループ2]"), _g("G002")),
            array("得意先元帳_品目グループコード3", _g("品目マスタ [品目グループ3]"), _g("G003")),
            array("得意先元帳_品目グループ名1", _g("品目マスタ [品目グループ1]"), _g("品目グループ1")),
            array("得意先元帳_品目グループ名2", _g("品目マスタ [品目グループ2]"), _g("品目グループ2")),
            array("得意先元帳_品目グループ名3", _g("品目マスタ [品目グループ3]"), _g("品目グループ3")),
            array("得意先元帳_仕様", _g("品目マスタ [仕様]"), _g("テスト仕様")),
            array("得意先元帳_メーカー", _g("品目マスタ [メーカー]"), _g("テストメーカー")),
            array("得意先元帳_棚番", _g("品目マスタ [棚番]"), _g("テスト棚番")),
            array("得意先元帳_品目備考1", _g("品目マスタ [備考1]"), _g("テスト品目備考1")),
            array("得意先元帳_品目備考2", _g("品目マスタ [備考2]"), _g("テスト品目備考2")),
            array("得意先元帳_品目備考3", _g("品目マスタ [備考3]"), _g("テスト品目備考3")),
            array("得意先元帳_品目備考4", _g("品目マスタ [備考4]"), _g("テスト品目備考4")),
            array("得意先元帳_品目備考5", _g("品目マスタ [備考5]"), _g("テスト品目備考5")),
        );

        return $info;
    }

    // 印刷フラグの更新
    protected function _setPrintFlag($form)
    {
    }

}
