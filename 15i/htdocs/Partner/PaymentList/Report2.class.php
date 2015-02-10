<?php

class Partner_PaymentList_Report2 extends Base_PDFReportBase
{

    protected function _getQuery(&$form)
    {
        // 支払種別
        $classArray = Gen_Option::getWayOfPayment('payment');

        // 印刷対象データの取得。
        // この帳票のような非チェックボックス方式の帳票（表示条件に合致するレコードをすべて印刷。
        // action=XXX_XXX_List&gen_report=XXX_XXX_Report として印刷）の場合、
        // gen_temp_for_report テーブルに Listクラスで取得したデータが入っている。
        $query = "
            select
                 -- headers

                gen_temp_for_report.currency_name as page_key
                ,gen_temp_for_report.from_date as 買掛残高表_開始日
                ,gen_temp_for_report.to_date as 買掛残高表_終了日
                ,gen_temp_for_report.currency_name as 買掛残高表_通貨

                 -- details

                ,gen_temp_for_report.before_accept_amount as detail_買掛残高表_繰越額
                ,gen_temp_for_report.accepted_amount as detail_買掛残高表_期間中仕入額
                ,gen_temp_for_report.accepted_tax as detail_買掛残高表_期間中消費税額
                ,gen_temp_for_report.payment as detail_買掛残高表_期間中支払額
                ,gen_temp_for_report.adjust_payment as detail_買掛残高表_期間中調整額
                ,gen_temp_for_report.payment_total as detail_買掛残高表_買掛金残高
                ,gen_temp_for_report.payment_1 as detail_買掛残高表_{$classArray[1]}
                ,gen_temp_for_report.payment_2 as detail_買掛残高表_{$classArray[2]}
                ,gen_temp_for_report.payment_3 as detail_買掛残高表_{$classArray[3]}
                ,gen_temp_for_report.payment_4 as detail_買掛残高表_{$classArray[4]}
                ,gen_temp_for_report.payment_5 as detail_買掛残高表_{$classArray[5]}
                ,gen_temp_for_report.payment_6 as detail_買掛残高表_{$classArray[6]}
                ,gen_temp_for_report.payment_7 as detail_買掛残高表_{$classArray[7]}
                ,gen_temp_for_report.payment_8 as detail_買掛残高表_{$classArray[8]}
                ,gen_temp_for_report.payment_9 as detail_買掛残高表_{$classArray[9]}
                ,gen_temp_for_report.payment_10 as detail_買掛残高表_{$classArray[10]}
                    
                ,gen_temp_for_report.accepted_date as detail_買掛残高表_受入日
                ,gen_temp_for_report.inspection_date as detail_買掛残高表_検収日
                ,gen_temp_for_report.item_code as detail_買掛残高表_品目コード
                ,gen_temp_for_report.item_name as detail_買掛残高表_品目名
                ,gen_temp_for_report.quantity as detail_買掛残高表_数量
                ,gen_temp_for_report.price as detail_買掛残高表_単価
                ,gen_temp_for_report.amount as detail_買掛残高表_金額
                
                ,item_master.spec as detail_買掛残高表_仕様
                ,item_master.maker_name as detail_買掛残高表_メーカー
                ,item_master.rack_no as detail_買掛残高表_棚番
                ,item_master.comment as 買掛残高表_品目備考1
            	,item_master.comment_2 as detail_買掛残高表_品目備考2
            	,item_master.comment_3 as detail_買掛残高表_品目備考3
            	,item_master.comment_4 as detail_買掛残高表_品目備考4
            	,item_master.comment_5 as detail_買掛残高表_品目備考5
                ,item_group_code_1 as detail_買掛残高表_品目グループコード1
                ,item_group_code_2 as detail_買掛残高表_品目グループコード2
                ,item_group_code_3 as detail_買掛残高表_品目グループコード3
                ,item_group_name_1 as detail_買掛残高表_品目グループ名1
                ,item_group_name_2 as detail_買掛残高表_品目グループ名2
                ,item_group_name_3 as detail_買掛残高表_品目グループ名3
            from
                gen_temp_for_report  
                inner join item_master on gen_temp_for_report.item_id = item_master.item_id
                left join (select item_group_id as gid1, item_group_code as item_group_code_1, item_group_name as item_group_name_1
                    from item_group_master) as t_group_1 on item_master.item_group_id = t_group_1.gid1
                left join (select item_group_id as gid2, item_group_code as item_group_code_2, item_group_name as item_group_name_2
                    from item_group_master) as t_group_2 on item_master.item_group_id_2 = t_group_2.gid2
                left join (select item_group_id as gid3, item_group_code as item_group_code_3, item_group_name as item_group_name_3
                    from item_group_master) as t_group_3 on item_master.item_group_id_3 = t_group_3.gid3
                /* タグリスト自動追加用 */
                left join customer_master as customer_master_partner on gen_temp_for_report.customer_id = customer_master_partner.customer_id
                left join order_header as order_header_partner on gen_temp_for_report.order_header_id = order_header_partner.order_header_id
                left join order_detail as order_detail_partner on gen_temp_for_report.order_detail_id = order_detail_partner.order_detail_id
                left join accepted on gen_temp_for_report.accepted_id = accepted.accepted_id
            order by
                -- テンプレート内の指定が優先されることに注意
                currency_name, gen_temp_for_report.customer_no
        ";

        return $query;
    }

    // テンプレート情報
    protected function _getReportParam()
    {
        global $gen_db;

        $info = array();
        $info['reportTitle'] = _g("買掛残高明細");
        $info['report'] = "PaymentDetailList";
        $info['pageKeyColumn'] = "page_key";

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
            array("●" . _g("買掛残高明細 ヘッダ")),
            array("買掛残高表_開始日", _g("対象となる期間の開始日。受入については受入日または検収日（自社情報マスタ「仕入計上基準」による）、支払については支払日が、この期間内にあるデータが対象となる"), "2014-01-01"),
            array("買掛残高表_終了日", _g("対象となる期間の終了日"), "2014-01-31"),
            array("買掛残高表_通貨", _g("取引通貨"), $keyCurrency),
            array("●" . _g("買掛残高明細 明細")),
            array("買掛残高表_繰越額", _g("期間前からの繰越額"), "100000"),
            array("買掛残高表_期間中仕入額", _g("期間中の受入額（受入登録画面）"), "80000"),
            array("買掛残高表_期間中消費税額", _g("期間中の消費税額（受入登録画面）"), "4000"),
            array("買掛残高表_期間中支払額", _g("期間中の支払額（支払登録画面）"), "50100"),
            array("買掛残高表_期間中調整額", _g("期間中の調整額（支払登録画面）"), "-100"),
            array("買掛残高表_買掛金残高", _g("繰越額 ＋ 期間中仕入額 ＋ 期間中消費税額 － 期間中支払額 － 期間中調整額"), "134000"),
            array("買掛残高表_" . $classArray[1], _g("期間中の現金支払額（支払登録画面）"), "1000"),
            array("買掛残高表_" . $classArray[2], _g("期間中の振込支払額（支払登録画面）"), "1000"),
            array("買掛残高表_" . $classArray[3], _g("期間中の小切手支払額（支払登録画面）"), "1000"),
            array("買掛残高表_" . $classArray[4], _g("期間中の手形支払額（支払登録画面）"), "1000"),
            array("買掛残高表_" . $classArray[5], _g("期間中の相殺支払額（支払登録画面）"), "1000"),
            array("買掛残高表_" . $classArray[6], _g("期間中の値引支払額（支払登録画面）"), "1000"),
            array("買掛残高表_" . $classArray[7], _g("期間中の振込手数料支払額（支払登録画面）"), "1000"),
            array("買掛残高表_" . $classArray[9], _g("期間中の先振込支払額（支払登録画面）"), "1000"),
            array("買掛残高表_" . $classArray[10], _g("期間中の代引支払額（支払登録画面）"), "1000"),
            array("買掛残高表_" . $classArray[8], _g("期間中のその他支払額（支払登録画面）"), "1000"),
            array("買掛残高表_受入日", _g("受入日"), "2014-01-15"),
            array("買掛残高表_検収日", _g("検収日"), "2014-01-20"),
            array("買掛残高表_品目コード", _g("品目コード"), "code001"),
            array("買掛残高表_品目名", _g("品目名"), ("テスト品目")),
            array("買掛残高表_数量", _g("数量"), "2000"),
            array("買掛残高表_単価", _g("受入単価"), "10"),
            array("買掛残高表_金額", _g("金額"), "20000"),
            array("買掛残高表_品目グループコード1", _g("品目マスタ [品目グループ1]"), _g("G001")),
            array("買掛残高表_品目グループコード2", _g("品目マスタ [品目グループ2]"), _g("G002")),
            array("買掛残高表_品目グループコード3", _g("品目マスタ [品目グループ3]"), _g("G003")),
            array("買掛残高表_品目グループ名1", _g("品目マスタ [品目グループ1]"), _g("品目グループ1")),
            array("買掛残高表_品目グループ名2", _g("品目マスタ [品目グループ2]"), _g("品目グループ2")),
            array("買掛残高表_品目グループ名3", _g("品目マスタ [品目グループ3]"), _g("品目グループ3")),
            array("買掛残高表_仕様", _g("品目マスタ [仕様]"), _g("テスト仕様")),
            array("買掛残高表_メーカー", _g("品目マスタ [メーカー]"), _g("テストメーカー")),
            array("買掛残高表_棚番", _g("品目マスタ [棚番]"), _g("テスト棚番")),
            array("買掛残高表_品目備考1", _g("品目マスタ [備考1]"), _g("テスト品目備考1")),
            array("買掛残高表_品目備考2", _g("品目マスタ [備考2]"), _g("テスト品目備考2")),
            array("買掛残高表_品目備考3", _g("品目マスタ [備考3]"), _g("テスト品目備考3")),
            array("買掛残高表_品目備考4", _g("品目マスタ [備考4]"), _g("テスト品目備考4")),
            array("買掛残高表_品目備考5", _g("品目マスタ [備考5]"), _g("テスト品目備考5")),
        );

        return $info;
    }

    // 印刷フラグの更新
    protected function _setPrintFlag($form)
    {
    }

}