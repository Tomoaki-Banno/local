<?php

class Partner_PaymentList_Report extends Base_PDFReportBase
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

                currency_name as page_key
                ,from_date as 買掛残高表_開始日
                ,to_date as 買掛残高表_終了日
                ,currency_name as 買掛残高表_通貨

                 -- details

                ,before_accept_amount as detail_買掛残高表_繰越額
                ,accepted_amount as detail_買掛残高表_期間中仕入額
                ,accepted_tax as detail_買掛残高表_期間中消費税額
                ,payment as detail_買掛残高表_期間中支払額
                ,adjust_payment as detail_買掛残高表_期間中調整額
                ,payment_total as detail_買掛残高表_買掛金残高
                ,payment_1 as detail_買掛残高表_{$classArray[1]}
                ,payment_2 as detail_買掛残高表_{$classArray[2]}
                ,payment_3 as detail_買掛残高表_{$classArray[3]}
                ,payment_4 as detail_買掛残高表_{$classArray[4]}
                ,payment_5 as detail_買掛残高表_{$classArray[5]}
                ,payment_6 as detail_買掛残高表_{$classArray[6]}
                ,payment_7 as detail_買掛残高表_{$classArray[7]}
                ,payment_8 as detail_買掛残高表_{$classArray[8]}
                ,payment_9 as detail_買掛残高表_{$classArray[9]}
                ,payment_10 as detail_買掛残高表_{$classArray[10]}
            from
                gen_temp_for_report  
                /* タグリスト自動追加用 */
                left join customer_master as customer_master_partner on gen_temp_for_report.customer_id = customer_master_partner.customer_id
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
        $info['reportTitle'] = _g("買掛残高一覧表");
        $info['report'] = "PaymentList";
        $info['pageKeyColumn'] = "page_key";

        $keyCurrency = $gen_db->queryOneValue("select key_currency from company_master");
        // 支払種別
        $classArray = Gen_Option::getWayOfPayment('payment');
        
        // SQLのfromで指定されているテーブルのリスト。
        // ここで指定されたテーブルのカラムはSQL selectとタグリストに自動追加される。
        $info['tables'] = array(
            array("customer_master_partner", true, ""),
        );

        // タグリスト（この帳票固有のもの）
        $info['tagList'] = array(
            array("●" . _g("買掛残高表 ヘッダ")),
            array("買掛残高表_開始日", _g("対象となる期間の開始日。受入については受入日または検収日（自社情報マスタ「仕入計上基準」による）、支払については支払日が、この期間内にあるデータが対象となる"), "2014-01-01"),
            array("買掛残高表_終了日", _g("対象となる期間の終了日"), "2014-01-31"),
            array("買掛残高表_通貨", _g("取引通貨"), $keyCurrency),
            array("●" . _g("買掛残高表 明細")),
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
        );

        return $info;
    }

    // 印刷フラグの更新
    protected function _setPrintFlag($form)
    {
    }

}