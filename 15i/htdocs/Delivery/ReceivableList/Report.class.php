<?php

class Delivery_ReceivableList_Report extends Base_PDFReportBase
{

    protected function _getQuery(&$form)
    {
        // 入金種別
        $classArray = Gen_Option::getWayOfPayment('receivable');
        
        // 印刷対象データの取得。
        // この帳票のような非チェックボックス方式の帳票（表示条件に合致するレコードをすべて印刷。
        // action=XXX_XXX_List&gen_report=XXX_XXX_Report として印刷）の場合、
        // gen_temp_for_report テーブルに Listクラスで取得したデータが入っている。
        $query = "
             select
                -- headers

                currency_name as page_key
                ,from_date as 売掛残高表_開始日
                ,to_date as 売掛残高表_終了日
                ,currency_name as 売掛残高表_通貨
                ,mode as 売掛残高表_モード

                -- details

                ,before_sales as detail_売掛残高表_繰越額
                ,sales as detail_売掛残高表_期間中売上額
                ,sales_tax as detail_売掛残高表_期間中消費税額
                ,paying_in as detail_売掛残高表_期間中入金額
                ,receivable_balance as detail_売掛残高表_売掛金残高
                ,paying_in_1 as detail_売掛残高表_{$classArray[1]}
                ,paying_in_2 as detail_売掛残高表_{$classArray[2]}
                ,paying_in_3 as detail_売掛残高表_{$classArray[3]}
                ,paying_in_4 as detail_売掛残高表_{$classArray[4]}
                ,paying_in_5 as detail_売掛残高表_{$classArray[5]}
                ,paying_in_6 as detail_売掛残高表_{$classArray[6]}
                ,paying_in_7 as detail_売掛残高表_{$classArray[7]}
                ,paying_in_8 as detail_売掛残高表_{$classArray[8]}
                ,paying_in_9 as detail_売掛残高表_{$classArray[9]}
                ,paying_in_10 as detail_売掛残高表_{$classArray[10]}
            from
                gen_temp_for_report  
                /* タグリスト自動追加用 */
                left join customer_master on gen_temp_for_report.customer_id = customer_master.customer_id
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
        $info['reportTitle'] = _g("売掛残高一覧表");
        $info['report'] = "ReceivableList";
        $info['pageKeyColumn'] = "page_key";

        $keyCurrency = $gen_db->queryOneValue("select key_currency from company_master");
        // 入金種別
        $classArray = Gen_Option::getWayOfPayment('receivable');
        
        // SQLのfromで指定されているテーブルのリスト。
        // ここで指定されたテーブルのカラムはSQL selectとタグリストに自動追加される。
        $info['tables'] = array(
            array("customer_master", true, ""),
        );

        // タグリスト（この帳票固有のもの）
        $info['tagList'] = array(
            array("●" . _g("売掛残高表　ヘッダ")),
            array("売掛残高表_開始日", _g("対象となる期間の開始日"), "2014-01-01"),
            array("売掛残高表_終了日", _g("対象となる期間の終了日"), "2014-01-31"),
            array("売掛残高表_通貨", _g("取引通貨"), $keyCurrency),
            array("売掛残高表_モード", _g("発行画面の表示条件「売掛管理表のモード」（「納品ベース」「受注ベース」「請求ベース」）。"), _g("請求ベース")),
            array("●" . _g("売掛残高表　明細")),
            array("売掛残高表_繰越額", _g("期間前からの繰越額"), "100000"),
            array("売掛残高表_期間中売上額", _g("期間中の売上額（請求書）"), "80000"),
            array("売掛残高表_期間中消費税額", _g("期間中の消費税額（請求書）"), "4000"),
            array("売掛残高表_期間中入金額", _g("期間中の入金額（入金登録画面）"), "50000"),
            array("売掛残高表_売掛金残高", _g("繰越額 ＋ 期間中売上額 ＋ 期間中消費税額 － 期間中入金額"), "134000"),
            array("売掛残高表_" . $classArray[1], _g("期間中の現金入金額（入金登録画面）"), "1000"),
            array("売掛残高表_" . $classArray[2], _g("期間中の振込入金額（入金登録画面）"), "1000"),
            array("売掛残高表_" . $classArray[3], _g("期間中の小切手入金額（入金登録画面）"), "1000"),
            array("売掛残高表_" . $classArray[4], _g("期間中の手形入金額（入金登録画面）"), "1000"),
            array("売掛残高表_" . $classArray[5], _g("期間中の相殺入金額（入金登録画面）"), "1000"),
            array("売掛残高表_" . $classArray[6], _g("期間中の値引入金額（入金登録画面）"), "1000"),
            array("売掛残高表_" . $classArray[7], _g("期間中の振込手数料入金額（入金登録画面）"), "1000"),
            array("売掛残高表_" . $classArray[9], _g("期間中の先振込入金額（入金登録画面）"), "1000"),
            array("売掛残高表_" . $classArray[10], _g("期間中の代引入金額（入金登録画面）"), "1000"),
            array("売掛残高表_" . $classArray[8], _g("期間中のその他入金額（入金登録画面）"), "1000"),
        );

        return $info;
    }

    // 印刷フラグの更新
    protected function _setPrintFlag($form)
    {
    }
    
}