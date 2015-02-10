<?php

if (!defined("CLOSE_DATE_COLUMN_COUNT")) {
    define('CLOSE_DATE_COLUMN_COUNT', 18);  // 締日列の最大数
}

class Delivery_ReceivableCalendar_Report extends Base_PDFReportBase
{

    protected function _getQuery(&$form)
    {
        // 印刷対象データの取得
        // この帳票のような非チェックボックス方式の帳票（表示条件に合致するレコードをすべて印刷。
        // action=XXX_XXX_List&gen_report=XXX_XXX_Report として印刷）の場合、
        // gen_temp_for_report テーブルに Listクラスで取得したデータが入っている。
        $query = "
            select
                -- headers

                currency_name as pagekey
                ,gen_temp_for_report.customer_id
                ,from_date as 回収予定表_開始日
                ,to_date as 回収予定表_終了日
                ,currency_name as 回収予定表_通貨

                -- details

                ,total_sales_with_tax as detail_回収予定表_合計税込売上額
                ,total_sales as detail_回収予定表_合計税別売上額
                ,total_tax as detail_回収予定表_合計税額
            ";
            for ($i = 1; $i <= CLOSE_DATE_COLUMN_COUNT; $i++) {
                $query .= "
                    ,collect_date_{$i} as detail_回収予定表_回収予定日{$i}
                    ,sales_with_tax_{$i} as detail_回収予定表_税込売上額{$i}
                    ,sales_{$i} as detail_回収予定表_税別売上額{$i}
                    ,tax_{$i} as detail_回収予定表_税額{$i}
                ";
            }
            $query .= "
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
        $info['reportTitle'] = _g("回収予定表");
        $info['report'] = "ReceivableCalendar";
        $info['pageKeyColumn'] = "pagekey";

        $arr = Logic_Receivable::getCollectCloseData("2014-01-01", "2014-06-30", 0, CLOSE_DATE_COLUMN_COUNT);
        $close = $arr[0];
        
        $keyCurrency = $gen_db->queryOneValue("select key_currency from company_master");

        // SQLのfromで指定されているテーブルのリスト。
        // ここで指定されたテーブルのカラムはSQL selectとタグリストに自動追加される。
        $info['tables'] = array(
            array("customer_master", true, ""),
        );

        // タグリスト（この帳票固有のもの）
        $info['tagList'] = array(
            array("●" . _g("回収予定表 ヘッダ")),
            array("回収予定表_開始日", _g("対象となる期間の開始日"), "2014-01-01"),
            array("回収予定表_終了日", _g("対象となる期間の終了日"), "2014-01-31"),
            array("回収予定表_通貨", _g("取引通貨"), $keyCurrency),
            array("●" . _g("回収予定表 明細")),
            array("回収予定表_合計税込売上額", _g("期間中の合計売上額（税込）"), 1050 * CLOSE_DATE_COLUMN_COUNT),
            array("回収予定表_合計税別売上額", _g("期間中の合計売上額（税別）"), 1000 * CLOSE_DATE_COLUMN_COUNT),
            array("回収予定表_合計税額", _g("期間中の税額"), 50 * CLOSE_DATE_COLUMN_COUNT),
        );
        for ($i = 1; $i <= CLOSE_DATE_COLUMN_COUNT; $i++) {
            $info['tagList'][] = array("回収予定表_回収予定日{$i}", _g("回収予定日") . $i . _g("（見出し用）"), $close[($i - 1)]);
        }
        for ($i = 1; $i <= CLOSE_DATE_COLUMN_COUNT; $i++) {
            $info['tagList'][] = array("回収予定表_税込売上額{$i}", sprintf(_g("締日%sに対応する売上額（税込）"), $i), 1050);
        }
        for ($i = 1; $i <= CLOSE_DATE_COLUMN_COUNT; $i++) {
            $info['tagList'][] = array("回収予定表_税別売上額{$i}", sprintf(_g("締日%sに対応する売上額（税別）"), $i), 1000);
        }
        for ($i = 1; $i <= CLOSE_DATE_COLUMN_COUNT; $i++) {
            $info['tagList'][] = array("回収予定表_税額{$i}", sprintf(_g("締日%sに対応する税額"), $i), 50);
        }

        return $info;
    }

    // 印刷フラグの更新
    protected function _setPrintFlag($form)
    {
    }

}