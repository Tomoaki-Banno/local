<?php

class Manufacturing_Estimate_Report extends Base_PDFReportBase
{

    protected function _getQuery(&$form)
    {
        global $gen_db;

        // 印刷対象データを配列に列挙する
        $idArr = array();
        foreach ($form as $name => $value) {
            if (substr($name, 0, 6) == "check_") {
                $idArr[] = substr($name, 6, strlen($name) - 6);
            }
        }
        // 明細モードのときはdetail_idが指定されているので、header_idに変換しておく
        if (@$form['detail'] == "true" && count($idArr) > 0) {
            $query = "select estimate_header_id from estimate_detail
                where estimate_detail_id in (" . join(",", $idArr) . ")
                group by estimate_header_id";
            $idArr2 = $gen_db->getArray($query);
            $idArr = array();
            foreach ($idArr2 as $row) {
                $idArr[] = $row['estimate_header_id'];
            }
        }

        // 印刷対象データの取得
        $idCsv = join(',', $idArr);
        $query = "
            select
                -- headers
                estimate_header.estimate_header_id
                
                ,t_total.total_amount as 見積_合計金額
                ,t_total.total_tax as 見積_合計消費税額
                ,t_total.total_amount + coalesce(t_total.total_tax,0) as 見積_税込合計金額

                -- details

            from
                estimate_detail
                inner join estimate_header on estimate_detail.estimate_header_id = estimate_header.estimate_header_id
                -- 合計計算
                inner join (
                    select
                        estimate_header_id
                        ,sum(case when foreign_currency_id is null then estimate_amount else foreign_currency_estimate_amount end) as total_amount
                        ,sum(case when foreign_currency_id is null then estimate_detail.estimate_tax else foreign_currency_estimate_tax end) as total_tax
                    from
                        estimate_detail
                    group by
                        estimate_header_id
                    ) as t_total
                    on estimate_header.estimate_header_id = t_total.estimate_header_id
                left join item_master on estimate_detail.item_id = item_master.item_id
                " . self::getFromItemMasterChildren() . "
                /* " . self::getFromItemMasterProcess() . " */
                left join worker_master on estimate_header.worker_id = worker_master.worker_id
                left join section_master on estimate_header.section_id = section_master.section_id
                left join currency_master on estimate_detail.foreign_currency_id = currency_master.currency_id

            where
                estimate_header.estimate_header_id in ({$idCsv})
            order by
                -- テンプレート内の指定が優先されることに注意
                estimate_header.estimate_number, line_no
        ";

        return $query;
    }

    // テンプレート情報
    protected function _getReportParam()
    {
        $info = array();
        $info['reportTitle'] = _g("見積書");
        $info['report'] = "Estimate";
        $info['pageKeyColumn'] = "estimate_header_id";

        // SQLのfromで指定されているテーブルのリスト。
        // ここで指定されたテーブルのカラムはSQL selectとタグリストに自動追加される。
        $info['tables'] = array(
            array("estimate_header", false, ""),
            array("estimate_detail", true, ""),
            array("item_master", true, " (" . _g("品目ドロップダウンから登録された場合のみ") . ")"),
            array("item_master_children", true, ""),    // 品目グループ・標準ロケ・標準手配先関連。SQL from に「self::getFromItemMasterChildren()」が必要
            // パフォーマンス上の理由でコメントアウト
            //array("item_master_process", true, ""),    // 工程関連。SQL from に「self::getFromItemMasterProcess()」が必要。工程数×2のjoinが追加されるため、工程数が多い場合はパフォーマンスに影響あり
            array("currency_master", true, " (" . _g("見積取引通貨") . ")"),
            array("worker_master", false, " (" . _g("見積書ヘッダ「担当者(自社)」") . ")"),
            array("section_master", false, " (" . _g("見積書ヘッダ「部門(自社)」") . ")"),
        );
        
        // タグリスト（この帳票固有のもの）
        $info['tagList'] = array(
            array("●" . _g("見積ヘッダ（合計金額）")),
            array("見積_合計金額", _g("明細欄の「金額」の合計"), "200000"),
            array("見積_合計消費税額", _g("明細欄の「消費税額」の合計"), "10000"),
            array("見積_税込合計金額", _g("合計金額 + 合計消費税額"), "210000"),
        );

        return $info;
    }
    
    // 印刷フラグの更新
    protected function _setPrintFlag($form)
    {
    }

}