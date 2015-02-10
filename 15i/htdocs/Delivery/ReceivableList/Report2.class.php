<?php

class Delivery_ReceivableList_Report2 extends Base_PDFReportBase
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

                gen_temp_for_report.currency_name as page_key
                ,gen_temp_for_report.from_date as 売掛残高表_開始日
                ,gen_temp_for_report.to_date as 売掛残高表_終了日
                ,gen_temp_for_report.currency_name as 売掛残高表_通貨
                ,gen_temp_for_report.mode as 売掛残高表_モード

                -- details

                ,gen_temp_for_report.before_sales as detail_売掛残高表_繰越額
                ,gen_temp_for_report.sales as detail_売掛残高表_期間中売上額
                ,gen_temp_for_report.sales_tax as detail_売掛残高表_期間中消費税額
                ,gen_temp_for_report.paying_in as detail_売掛残高表_期間中入金額
                ,gen_temp_for_report.receivable_balance as detail_売掛残高表_売掛金残高
                ,gen_temp_for_report.paying_in_1 as detail_売掛残高表_{$classArray[1]}
                ,gen_temp_for_report.paying_in_2 as detail_売掛残高表_{$classArray[2]}
                ,gen_temp_for_report.paying_in_3 as detail_売掛残高表_{$classArray[3]}
                ,gen_temp_for_report.paying_in_4 as detail_売掛残高表_{$classArray[4]}
                ,gen_temp_for_report.paying_in_5 as detail_売掛残高表_{$classArray[5]}
                ,gen_temp_for_report.paying_in_6 as detail_売掛残高表_{$classArray[6]}
                ,gen_temp_for_report.paying_in_7 as detail_売掛残高表_{$classArray[7]}
                ,gen_temp_for_report.paying_in_8 as detail_売掛残高表_{$classArray[8]}
                ,gen_temp_for_report.paying_in_9 as detail_売掛残高表_{$classArray[9]}
                ,gen_temp_for_report.paying_in_10 as detail_売掛残高表_{$classArray[10]}
                    
                ,gen_temp_for_report.delivery_no as detail_売掛残高表_納品書番号
                ,gen_temp_for_report.show_date_1 as detail_売掛残高表_納品日
                ,gen_temp_for_report.show_date_2 as detail_売掛残高表_検収日
                ,gen_temp_for_report.item_code as detail_売掛残高表_品目コード
                ,gen_temp_for_report.item_name as detail_売掛残高表_品目名
                ,gen_temp_for_report.quantity as detail_売掛残高表_数量
                ,gen_temp_for_report.measure as detail_売掛残高表_単位
                ,gen_temp_for_report.price as detail_売掛残高表_単価
                ,gen_temp_for_report.amount as detail_売掛残高表_金額
                
                ,item_master.spec as detail_売掛残高表_仕様
                ,item_master.maker_name as detail_売掛残高表_メーカー
                ,item_master.rack_no as detail_売掛残高表_棚番
                ,item_master.comment as detail_売掛残高表_品目備考1
            	,item_master.comment_2 as detail_売掛残高表_品目備考2
            	,item_master.comment_3 as detail_売掛残高表_品目備考3
            	,item_master.comment_4 as detail_売掛残高表_品目備考4
            	,item_master.comment_5 as detail_売掛残高表_品目備考5
                ,item_group_code_1 as detail_売掛残高表_品目グループコード1
                ,item_group_code_2 as detail_売掛残高表_品目グループコード2
                ,item_group_code_3 as detail_売掛残高表_品目グループコード3
                ,item_group_name_1 as detail_売掛残高表_品目グループ名1
                ,item_group_name_2 as detail_売掛残高表_品目グループ名2
                ,item_group_name_3 as detail_売掛残高表_品目グループ名3
            from
                gen_temp_for_report  
                left join item_master on gen_temp_for_report.item_id = item_master.item_id
                left join (select item_group_id as gid1, item_group_code as item_group_code_1, item_group_name as item_group_name_1
                    from item_group_master) as t_group_1 on item_master.item_group_id = t_group_1.gid1
                left join (select item_group_id as gid2, item_group_code as item_group_code_2, item_group_name as item_group_name_2
                    from item_group_master) as t_group_2 on item_master.item_group_id_2 = t_group_2.gid2
                left join (select item_group_id as gid3, item_group_code as item_group_code_3, item_group_name as item_group_name_3
                    from item_group_master) as t_group_3 on item_master.item_group_id_3 = t_group_3.gid3
                /* タグリスト自動追加用 */
                left join customer_master on gen_temp_for_report.customer_id = customer_master.customer_id
                left join received_header on gen_temp_for_report.received_header_id = received_header.received_header_id
                left join received_detail on gen_temp_for_report.received_detail_id = received_detail.received_detail_id
                left join delivery_header on gen_temp_for_report.delivery_header_id = delivery_header.delivery_header_id
                left join delivery_detail on gen_temp_for_report.delivery_detail_id = delivery_detail.delivery_detail_id
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
        $info['reportTitle'] = _g("売掛残高明細");
        $info['report'] = "ReceivableDetailList";
        $info['pageKeyColumn'] = "page_key";

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
            array("●" . _g("売掛残高明細　ヘッダ")),
            array("売掛残高表_開始日", _g("対象となる期間の開始日"), "2014-01-01"),
            array("売掛残高表_終了日", _g("対象となる期間の終了日"), "2014-01-31"),
            array("売掛残高表_通貨", _g("取引通貨"), $keyCurrency),
            array("売掛残高表_モード", _g("発行画面の表示条件「売掛管理表のモード」（「納品ベース」「受注ベース」「請求ベース」）。"), _g("請求ベース")),
            array("●" . _g("売掛残高明細　明細")),
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
            array("売掛残高表_納品書番号", _g("納品書番号（受注ベースを除く）"), "S140100001"),
            array("売掛残高表_納品日", _g("納品日"), "2014-01-15"),
            array("売掛残高表_検収日", _g("検収日"), "2014-01-20"),
            array("売掛残高表_品目コード", _g("品目コード"), "code001"),
            array("売掛残高表_品目名", _g("品目名"), ("テスト品目")),
            array("売掛残高表_数量", _g("数量"), "2000"),
            array("売掛残高表_単位", _g("品目マスタ [管理単位]"), "kg"),
            array("売掛残高表_単価", _g("納品単価"), "10"),
            array("売掛残高表_金額", _g("金額"), "20000"),
            array("売掛残高表_品目グループコード1", _g("品目マスタ [品目グループ1]"), _g("G001")),
            array("売掛残高表_品目グループコード2", _g("品目マスタ [品目グループ2]"), _g("G002")),
            array("売掛残高表_品目グループコード3", _g("品目マスタ [品目グループ3]"), _g("G003")),
            array("売掛残高表_品目グループ名1", _g("品目マスタ [品目グループ1]"), _g("品目グループ1")),
            array("売掛残高表_品目グループ名2", _g("品目マスタ [品目グループ2]"), _g("品目グループ2")),
            array("売掛残高表_品目グループ名3", _g("品目マスタ [品目グループ3]"), _g("品目グループ3")),
            array("売掛残高表_仕様", _g("品目マスタ [仕様]"), _g("テスト仕様")),
            array("売掛残高表_メーカー", _g("品目マスタ [メーカー]"), _g("テストメーカー")),
            array("売掛残高表_棚番", _g("品目マスタ [棚番]"), _g("テスト棚番")),
            array("売掛残高表_品目備考1", _g("品目マスタ [備考1]"), _g("テスト品目備考1")),
            array("売掛残高表_品目備考2", _g("品目マスタ [備考2]"), _g("テスト品目備考2")),
            array("売掛残高表_品目備考3", _g("品目マスタ [備考3]"), _g("テスト品目備考3")),
            array("売掛残高表_品目備考4", _g("品目マスタ [備考4]"), _g("テスト品目備考4")),
            array("売掛残高表_品目備考5", _g("品目マスタ [備考5]"), _g("テスト品目備考5")),
        );

        return $info;
    }

    // 印刷フラグの更新
    protected function _setPrintFlag($form)
    {
    }

}
