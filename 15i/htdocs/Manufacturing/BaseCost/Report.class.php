<?php

class Manufacturing_BaseCost_Report extends Base_PDFReportBase
{

    protected function _getQuery(&$form)
    {
        global $gen_db;

        // 印刷対象データの取得。
        // この帳票のような非チェックボックス方式の帳票（表示条件に合致するレコードをすべて印刷。
        // action=XXX_XXX_List&gen_report=XXX_XXX_Report として印刷）の場合、
        // gen_temp_for_report テーブルに Listクラスで取得したデータが入っている。
        $query = "
            select
                -- headers

                seiban
                ,seiban as 原価表_製番
                ,received_number as 原価表_受注番号
                ,received_quantity as 原価表_受注数
                ,received_sum as 原価表_受注金額
                ,received_date as 原価表_受注日
                ,dead_line as 原価表_納期
                
                ,base_cost as 原価表_原価

                -- details

                ,item_code_for_order as detail_原価表_品目コード（ソート用）
                ,machining_sequence as detail_原価表_工順

                ,detail_item_code as detail_原価表_子品目コード
                ,detail_item_name as detail_原価表_子品目名
                ,detail_order_class as detail_原価表_子品目区分
                ,detail_measure as detail_原価表_子品目単位
                ,process_code as detail_原価表_工程コード
                ,process_name as detail_原価表_工程名

                ,detail_hikiate_qty as detail_原価表_在庫使用数
                ,detail_standard_base_cost as detail_原価表_標準原価
                ,detail_hikiate_amount as detail_原価表_在庫使用分金額

                ,detail_achievement_qty as detail_原価表_製造数
                ,detail_work_minute  as detail_原価表_製造時間
                ,detail_process_amount as detail_原価表_製造単価
                ,detail_achievement_cost_1 as detail_原価表_製造経費1
                ,detail_achievement_cost_2 as detail_原価表_製造経費2
                ,detail_achievement_cost_3 as detail_原価表_製造経費3
                ,detail_achievement_amount as detail_原価表_製造原価

                ,detail_accepted_qty as detail_原価表_購入外製数
                ,detail_unit_price as detail_原価表_購入単価
                ,detail_order_amount as detail_原価表_購入原価
                ,partner_name as detail_原価表_購入外製先名

                ,inout_quantity as detail_原価表_出庫数
                ,inout_amount as detail_原価表_出庫金額
                
                ,detail_base_cost as detail_原価表_子品目原価

            from
                gen_temp_for_report  
                /* タグリスト自動追加用 */
                left join customer_master on gen_temp_for_report.customer_id = customer_master.customer_id
                left join item_master on gen_temp_for_report.item_id = item_master.item_id
                " . self::getFromItemMasterChildren() . "
                /* " . self::getFromItemMasterProcess() . " */
            order by
                -- テンプレート内の指定が優先されることに注意
                seiban, item_code_for_order, machining_sequence
        ";

        return $query;
    }

    // テンプレート情報
    protected function _getReportParam()
    {
        $info = array();
        $info['reportTitle'] = _g("原価表");
        $info['report'] = "BaseCost";
        $info['pageKeyColumn'] = "seiban";
        
        // SQLのfromで指定されているテーブルのリスト。
        // ここで指定されたテーブルのカラムはSQL selectとタグリストに自動追加される。
        $info['tables'] = array(
            array("item_master", false, " (" . _g("受注品目") . ")"),
            array("item_master_children", true, ""),    // 品目グループ・標準ロケ・標準手配先関連。SQL from に「self::getFromItemMasterChildren()」が必要
            // パフォーマンス上の理由でコメントアウト
            //array("item_master_process", true, ""),    // 工程関連。SQL from に「self::getFromItemMasterProcess()」が必要。工程数×2のjoinが追加されるため、工程数が多い場合はパフォーマンスに影響あり
            array("customer_master", false, " (" . _g("受注得意先") . ")"),
        );

        // タグリスト（この帳票固有のもの）
        $info['tagList'] = array(
            array("●" . _g("原価表 ヘッダ（受注関連）")),
            array("原価表_製番", _g("受注製番"), "100"),
            array("原価表_受注番号", _g("受注番号"), "A10010101"),
            array("原価表_受注数", _g("受注数"), "100"),
            array("原価表_受注金額", _g("受注金額"), "20000"),
            array("原価表_受注日", _g("受注日"), "2014-01-01"),
            array("原価表_納期", _g("受注納期"), "2014-01-05"),
            array("●" . _g("原価表 ヘッダ（合計原価）")),
            array("原価表_原価", _g("原価"), "6000"),
            array("●" . _g("原価表 明細（子品目関連）")),
            array("原価表_子品目コード", _g("子品目の品目コード"), _g("子品目コード")),
            array("原価表_子品目名", _g("子品目の品目名"), _g("子品目名")),
            array("原価表_子品目区分", _g("子品目の区分（製番/MRP）"), _g("製番")),
            array("原価表_子品目単位", _g("品目マスタ [管理単位]"), "m"),
            array("原価表_工程コード", _g("工程コード"), _g("テスト工程コード")),
            array("原価表_工程名", _g("工程名"), _g("テスト工程名")),
            array("原価表_品目コード（ソート用）", _g("このタグをorderbyタグ内で指定すると、受注品目を先頭とした品目コード順になる"), ""),
            array("原価表_工順", _g("工順"), 1),
            array("●" . _g("原価表 明細（在庫使用数関連）")),
            array("原価表_在庫使用数", _g("在庫使用数（製番品目：実績・受入が未登録の品目の使用数。MRP品目：すべての使用数）"), "20"),
            array("原価表_標準原価", _g("内製品は「(品目マスタの標準加工時間 * 品目マスタの工賃) + 品目マスタの外製単価 + 品目マスタの固定経費」、注文品は「品目マスタの標準発注単価」。下位品目分も含む"), "100"),
            array("原価表_在庫使用分金額", _g("在庫使用数 × 標準原価"), "2000"),
            array("●" . _g("原価表 明細（製造数関連）")),
            array("原価表_製造数", _g("製造数。実績登録画面で登録された値"), "20"),
            array("原価表_製造時間", _g("製造にかかった時間。実績登録画面で登録された値（分）"), "10"),
            array("原価表_製造経費1", _g("製造経費。実績登録画面で登録された値"), "1000"),
            array("原価表_製造経費2", _g("製造経費。実績登録画面で登録された値"), "1000"),
            array("原価表_製造経費3", _g("製造経費。実績登録画面で登録された値"), "1000"),
            array("原価表_製造原価", _g("（製造時間 × 品目マスタ「工賃」）+（外製単価 × 外製受入登録画面「受入数」）+ 実績登録画面「製造経費1-3」 +（品目マスタ「固定経費」× 実績登録画面「製造数」）"), "2000"),
            array("原価表_製造単価", _g("製造原価 ÷ 製造時間"), "200"),
            array("●" . _g("原価表 明細（購入数関連）")),
            array("原価表_購入外製数", _g("購入および外製した数"), "20"),
            array("原価表_購入単価", _g("購入および外製の単価"), "100"),
            array("原価表_購入原価", _g("購入外製数 × 購入単価 + 注文原価"), "2000"),
            array("原価表_購入外製先名", _g("購入および外製先の名称"), _g("取引先株式会社")),
            array("●" . _g("原価表 明細（出庫関連）")),
            array("原価表_出庫数", _g("[資材管理]-[出庫登録] で登録された出庫数量"), "20"),
            array("原価表_出庫金額", _g("[資材管理]-[出庫登録] で登録された出庫金額"), "2000"),
            array("●" . _g("原価表 明細（明細原価）")),
            array("原価表_子品目原価", _g("子品目の原価"), "6000"),
        );

        return $info;
    }

    // 印刷フラグの更新
    protected function _setPrintFlag($form)
    {
    }

}