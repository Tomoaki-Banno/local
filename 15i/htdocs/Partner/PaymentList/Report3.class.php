<?php

class Partner_PaymentList_Report3 extends Base_PDFReportBase
{

    protected function _getQuery(&$form)
    {
        // 支払種別
        $classArray = Gen_Option::getWayOfPayment('payment');
        $classQuery1 = Gen_Option::getWayOfPayment('list-query');
        
        $yenMode = ($form['gen_search_foreign_currency_mode'] == 1);
        $fromDate = $form['from_date_for_report'];
        $toDate = $form['to_date_for_report'];
        
        // この帳票ではテンプレートでのorderby指定は禁止。この並びに固定する。
        //  どの表示条件のときでも確実に並び順が固定されるようにしておかないと、日付や残高の表示で問題が発生する。
        $orderby = "t1.currency_name, customer_master_partner.customer_no, t2.show_date, t2.line_category, t2.order_id_for_user, t2.order_no";

        // 印刷対象データの取得。
        // この帳票のような非チェックボックス方式の帳票（表示条件に合致するレコードをすべて印刷。
        // action=XXX_XXX_List&gen_report=XXX_XXX_Report として印刷）の場合、
        // gen_temp_for_report テーブルに Listクラスで取得したデータが入っている。
        $query = "
            select
                /* headers */

                t1.currency_name as page_key
                ,t1.from_date as 仕入先元帳_開始日
                ,t1.to_date as 仕入先元帳_終了日
                ,t1.currency_name as 仕入先元帳_通貨

                ,t1.before_accept_amount as 仕入先元帳_繰越額
                ,t1.accepted_amount as 仕入先元帳_期間中仕入額
                ,t1.accepted_tax as 仕入先元帳_期間中消費税額
                ,t1.payment as 仕入先元帳_期間中支払額
                ,t1.adjust_payment as 仕入先元帳_期間中調整額
                ,coalesce(t1.payment,0) + coalesce(t1.adjust_payment,0) as 仕入先元帳_期間中支払調整額
                ,t1.payment_total as 仕入先元帳_買掛金残高
                ,t1.payment_1 as 仕入先元帳_{$classArray[1]}
                ,t1.payment_2 as 仕入先元帳_{$classArray[2]}
                ,t1.payment_3 as 仕入先元帳_{$classArray[3]}
                ,t1.payment_4 as 仕入先元帳_{$classArray[4]}
                ,t1.payment_5 as 仕入先元帳_{$classArray[5]}
                ,t1.payment_6 as 仕入先元帳_{$classArray[6]}
                ,t1.payment_7 as 仕入先元帳_{$classArray[7]}
                ,t1.payment_8 as 仕入先元帳_{$classArray[8]}
                ,t1.payment_9 as 仕入先元帳_{$classArray[9]}
                ,t1.payment_10 as 仕入先元帳_{$classArray[10]}

                /* details */

                /* 日付・注文書番号・オーダー番号は、ひとつ上の行と同じであれば表示しない */
                ,case when lag(t2.show_date) over(partition by t1.customer_id, t1.currency_id order by {$orderby}) = t2.show_date then null else t2.show_date end as detail_仕入先元帳_日付
                ,case when lag(t2.order_id_for_user) over(partition by t1.customer_id, t1.currency_id order by {$orderby}) = t2.order_id_for_user then null else t2.order_id_for_user end as detail_仕入先元帳_注文書番号
                ,case when lag(t2.order_no) over(partition by t1.customer_id, t1.currency_id order by {$orderby}) = t2.order_no then null else t2.order_no end as detail_仕入先元帳_オーダー番号
                ,t2.item_code as detail_仕入先元帳_品目コード
                ,t2.item_name as detail_仕入先元帳_品目名
                ,t2.quantity as detail_仕入先元帳_数量
                ,t2.price as detail_仕入先元帳_単価
                ,t2.amount as detail_仕入先元帳_金額
                ,t2.detail_tax as detail_仕入先元帳_消費税額
                ,t2.payment_amount as detail_仕入先元帳_支払額
                ,t2.adjust_amount as detail_仕入先元帳_調整額
                ,coalesce(t2.payment_amount,0) + coalesce(t2.adjust_amount,0) as 仕入先元帳_支払調整額
                /* 残高。日付ごとに計算し、日付ごとの一番最後の行に表示する。 */
                ,case when 
                    lead(t2.show_date) over(partition by t1.customer_id, t1.currency_id order by {$orderby}) = t2.show_date then 
                        null 
                    else 
                        coalesce(t1.before_accept_amount,0) + sum(coalesce(t2.amount,0) + coalesce(t2.detail_tax,0) - coalesce(t2.payment_amount,0) - coalesce(t2.adjust_amount,0)) 
                        over(partition by t1.customer_id, t1.currency_id order by {$orderby}) 
                    end as detail_仕入先元帳_明細残高
                /* ちなみに、明細行ごとに出すには次のようにする 
                ,coalesce(t1.before_accept_amount,0) + sum(coalesce(t2.amount,0) + coalesce(t2.detail_tax,0) - coalesce(t2.payment_amount,0) - coalesce(t2.adjust_amount,0)) 
                        over(partition by t1.customer_id, t1.currency_id order by {$orderby}) 
                    as detail_仕入先元帳_明細残高
                */
                
                ,item_master.spec as detail_仕入先元帳_仕様
                ,item_master.maker_name as detail_仕入先元帳_メーカー
                ,item_master.rack_no as detail_仕入先元帳_棚番
                ,item_master.comment as 仕入先元帳_品目備考1
            	,item_master.comment_2 as detail_仕入先元帳_品目備考2
            	,item_master.comment_3 as detail_仕入先元帳_品目備考3
            	,item_master.comment_4 as detail_仕入先元帳_品目備考4
            	,item_master.comment_5 as detail_仕入先元帳_品目備考5
                ,item_group_code_1 as detail_仕入先元帳_品目グループコード1
                ,item_group_code_2 as detail_仕入先元帳_品目グループコード2
                ,item_group_code_3 as detail_仕入先元帳_品目グループコード3
                ,item_group_name_1 as detail_仕入先元帳_品目グループ名1
                ,item_group_name_2 as detail_仕入先元帳_品目グループ名2
                ,item_group_name_3 as detail_仕入先元帳_品目グループ名3
            from
                /***** 仕入先/取引通貨ヘッダ *****/
                (select 
                    customer_id
                    ,currency_id
                    ,max(from_date::text) as from_date
                    ,max(to_date::text) as to_date
                    ,max(currency_name) as currency_name
                    ,max(before_accept_amount) as before_accept_amount
                    ,max(accepted_amount) as accepted_amount
                    ,max(accepted_tax) as accepted_tax
                    ,max(payment) as payment
                    ,max(adjust_payment) as adjust_payment
                    ,max(payment_total) as payment_total
                    ,max(payment_1) as payment_1
                    ,max(payment_2) as payment_2
                    ,max(payment_3) as payment_3
                    ,max(payment_4) as payment_4
                    ,max(payment_5) as payment_5
                    ,max(payment_6) as payment_6
                    ,max(payment_7) as payment_7
                    ,max(payment_8) as payment_8
                    ,max(payment_9) as payment_9
                    ,max(payment_10) as payment_10
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
                        ,gen_temp_for_report.order_header_id
                        ,gen_temp_for_report.order_detail_id
                        ,gen_temp_for_report.accepted_id
                        ,gen_temp_for_report.order_id_for_user::text
                        ,gen_temp_for_report.order_no::text
                        ,gen_temp_for_report.item_id
                        ,gen_temp_for_report.show_date
                        ,gen_temp_for_report.item_code
                        ,gen_temp_for_report.item_name
                        ,gen_temp_for_report.quantity
                        ,gen_temp_for_report.measure
                        ,gen_temp_for_report.price
                        ,gen_temp_for_report.amount
                        ,gen_temp_for_report.detail_tax
                        ,null::numeric as payment_amount
                        ,null::numeric as adjust_amount
                    from
                        gen_temp_for_report

                    /* 支払行 */
                    union all
                    select
                        2 as line_category
                        ,customer_id
                        ," . ($yenMode ? "null" : "foreign_currency_id") . " as currency_id
                        ,null as order_header_id
                        ,null as order_detail_id
                        ,null as accepted_id
                        ,null as order_id_for_user
                        ,null as order_no
                        ,null as item_id
                        ,payment_date as show_date
                        ,null as item_code
                        ,case way_of_payment {$classQuery1} end as item_name
                        ,null as quantity
                        ,null as measure
                        ,null as price
                        ,null as amount
                        ,null as detail_tax
                        ,case when foreign_currency_id is null then amount else " . ($yenMode ? "amount" : "foreign_currency_amount") . " end as payment_amount
                        ,case when foreign_currency_id is null then adjust_amount else " . ($yenMode ? "adjust_amount" : "foreign_currency_adjust_amount") . " end as adjust_amount
                    from
                        payment
                    where
                        payment_date between '{$fromDate}'::date and '{$toDate}'::date
                        and customer_id in (select customer_id::int from gen_temp_for_report)
                        
                    ) as t2 on t1.customer_id = t2.customer_id and coalesce(t1.currency_id,-1) = coalesce(t2.currency_id,-1)
                    
                left join item_master on t2.item_id = item_master.item_id
                left join (select item_group_id as gid1, item_group_code as item_group_code_1, item_group_name as item_group_name_1
                    from item_group_master) as t_group_1 on item_master.item_group_id = t_group_1.gid1
                left join (select item_group_id as gid2, item_group_code as item_group_code_2, item_group_name as item_group_name_2
                    from item_group_master) as t_group_2 on item_master.item_group_id_2 = t_group_2.gid2
                left join (select item_group_id as gid3, item_group_code as item_group_code_3, item_group_name as item_group_name_3
                    from item_group_master) as t_group_3 on item_master.item_group_id_3 = t_group_3.gid3
                /* タグリスト自動追加用 */
                left join customer_master as customer_master_partner on t2.customer_id = customer_master_partner.customer_id
                left join order_header as order_header_partner on t2.order_header_id = order_header_partner.order_header_id
                left join order_detail as order_detail_partner on t2.order_detail_id = order_detail_partner.order_detail_id
                left join accepted on t2.accepted_id = accepted.accepted_id
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
        $info['reportTitle'] = _g("仕入先元帳");
        $info['report'] = "PaymentLedger";
        $info['pageKeyColumn'] = "page_key";
        $info['denyOrderby'] = true;    // 帳票テンプレートでのorderby指定禁止。アップロード時にチェック

        $keyCurrency = $gen_db->queryOneValue("select key_currency from company_master");
        // 支払種別
        $classArray = Gen_Option::getWayOfPayment('payment');
        
        // SQLのfromで指定されているテーブルのリスト。
        // ここで指定されたテーブルのカラムはSQL selectとタグリストに自動追加される。
        $info['tables'] = array(
            array("customer_master_partner", true, ""),
            array("order_header_partner", false, ""),
            array("order_detail_partner", false, ""),
            array("accepted", false, ""),
        );

        // タグリスト（この帳票固有のもの）
        $info['tagList'] = array(
            array("●" . _g("仕入先元帳 ヘッダ")),
            array("仕入先元帳_開始日", _g("対象となる期間の開始日。受入については受入日または検収日（自社情報マスタ「仕入計上基準」による）、支払については支払日が、この期間内にあるデータが対象となる"), "2014-01-01"),
            array("仕入先元帳_終了日", _g("対象となる期間の終了日"), "2014-01-31"),
            array("仕入先元帳_通貨", _g("取引通貨"), $keyCurrency),
            array("仕入先元帳_繰越額", _g("期間前からの繰越額"), "100000"),
            array("仕入先元帳_期間中仕入額", _g("期間中の受入額（受入登録画面）"), "80000"),
            array("仕入先元帳_期間中消費税額", _g("期間中の消費税額（受入登録画面）"), "4000"),
            array("仕入先元帳_期間中支払額", _g("期間中の支払額（支払登録画面）"), "50100"),
            array("仕入先元帳_期間中調整額", _g("期間中の調整額（支払登録画面）"), "-100"),
            array("仕入先元帳_期間中支払調整額", _g("期間中支払額 + 期間中調整額"), "50000"),
            array("仕入先元帳_買掛金残高", _g("繰越額 ＋ 期間中仕入額 ＋ 期間中消費税額 － 期間中支払額 － 期間中調整額"), "134000"),
            array("仕入先元帳_" . $classArray[1], _g("期間中の現金支払額（支払登録画面）"), "1000"),
            array("仕入先元帳_" . $classArray[2], _g("期間中の振込支払額（支払登録画面）"), "1000"),
            array("仕入先元帳_" . $classArray[3], _g("期間中の小切手支払額（支払登録画面）"), "1000"),
            array("仕入先元帳_" . $classArray[4], _g("期間中の手形支払額（支払登録画面）"), "1000"),
            array("仕入先元帳_" . $classArray[5], _g("期間中の相殺支払額（支払登録画面）"), "1000"),
            array("仕入先元帳_" . $classArray[6], _g("期間中の値引支払額（支払登録画面）"), "1000"),
            array("仕入先元帳_" . $classArray[7], _g("期間中の振込手数料支払額（支払登録画面）"), "1000"),
            array("仕入先元帳_" . $classArray[9], _g("期間中の先振込支払額（支払登録画面）"), "1000"),
            array("仕入先元帳_" . $classArray[10], _g("期間中の代引支払額（支払登録画面）"), "1000"),
            array("仕入先元帳_" . $classArray[8], _g("期間中のその他支払額（支払登録画面）"), "1000"),
            
            array("●" . _g("仕入先元帳 明細")),
            array("仕入先元帳_日付", _g("受入日・検収日・支払日"), "2014-01-15"),
            array("仕入先元帳_注文書番号", _g("注文書番号"), "P140100001"),
            array("仕入先元帳_オーダー番号", _g("オーダー番号"), "D140100001"),
            array("仕入先元帳_品目コード", _g("品目コード"), "code001"),
            array("仕入先元帳_品目名", _g("品目名"), ("テスト品目")),
            array("仕入先元帳_数量", _g("数量"), "2000"),
            array("仕入先元帳_単価", _g("受入単価"), "10"),
            array("仕入先元帳_金額", _g("金額"), "20000"),
            array("仕入先元帳_消費税額", _g("消費税額"), "1000"),
            array("仕入先元帳_支払額", _g("支払額"), "10000"),
            array("仕入先元帳_調整額", _g("調整額"), "1000"),
            array("仕入先元帳_支払調整額", _g("支払額 + 調整額"), "11000"),
            array("仕入先元帳_明細残高", _g("明細残高"), "12000"),
            array("仕入先元帳_品目グループコード1", _g("品目マスタ [品目グループ1]"), _g("G001")),
            array("仕入先元帳_品目グループコード2", _g("品目マスタ [品目グループ2]"), _g("G002")),
            array("仕入先元帳_品目グループコード3", _g("品目マスタ [品目グループ3]"), _g("G003")),
            array("仕入先元帳_品目グループ名1", _g("品目マスタ [品目グループ1]"), _g("品目グループ1")),
            array("仕入先元帳_品目グループ名2", _g("品目マスタ [品目グループ2]"), _g("品目グループ2")),
            array("仕入先元帳_品目グループ名3", _g("品目マスタ [品目グループ3]"), _g("品目グループ3")),
            array("仕入先元帳_仕様", _g("品目マスタ [仕様]"), _g("テスト仕様")),
            array("メーカー", _g("品目マスタ [メーカー]"), _g("テストメーカー")),
            array("仕入先元帳_棚番", _g("品目マスタ [棚番]"), _g("テスト棚番")),
            array("仕入先元帳_品目備考1", _g("品目マスタ [備考1]"), _g("テスト品目備考1")),
            array("仕入先元帳_品目備考2", _g("品目マスタ [備考2]"), _g("テスト品目備考2")),
            array("仕入先元帳_品目備考3", _g("品目マスタ [備考3]"), _g("テスト品目備考3")),
            array("仕入先元帳_品目備考4", _g("品目マスタ [備考4]"), _g("テスト品目備考4")),
            array("仕入先元帳_品目備考5", _g("品目マスタ [備考5]"), _g("テスト品目備考5")),
        );

        return $info;
    }

    // 印刷フラグの更新
    protected function _setPrintFlag($form)
    {
    }

}