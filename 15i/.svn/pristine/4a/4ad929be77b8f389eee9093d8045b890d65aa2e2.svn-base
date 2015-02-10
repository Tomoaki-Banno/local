<?php

class Delivery_PayingIn_Report extends Base_PDFReportBase
{

    protected function _getQuery(&$form)
    {
        global $gen_db;

        // 印刷対象データを配列に列挙する
        $idArr = array();
        foreach ($form as $name => $value) {
            if (substr($name, 0, 12) == "print_check_") {
                $idArr[] = substr($name, 12, strlen($name) - 12);
            }
        }

        // 印刷対象データの取得
        $classQuery1 = Gen_Option::getWayOfPayment('list-query');
        $query = "
            select
                1 as dummy
                ,paying_in.paying_in_id
                ,paying_in_date as 入金_入金日
                ,case when paying_in.foreign_currency_id is null then paying_in.amount else paying_in.foreign_currency_amount end as 入金_金額
                ,amount as 入金_金額_基軸
                ,case way_of_payment {$classQuery1} end as 入金_入金種別
                ,case when currency_name is not null then foreign_currency_rate end as 入金_レート
                ,bill_number as 入金_請求書番号
                ,paying_in.remarks as 入金_備考
            from
                paying_in
                left join (select bill_header_id as bhid, bill_number from bill_header) as t_bill on paying_in.bill_header_id = t_bill.bhid
                inner join customer_master on paying_in.customer_id = customer_master.customer_id
                left join currency_master on paying_in.foreign_currency_id = currency_master.currency_id
            where
                paying_in.paying_in_id in (" . join(",", $idArr) . ")
            order by
                -- テンプレート内の指定が優先されることに注意
                paying_in_date, customer_master.customer_no
        ";

        return $query;
    }

    // テンプレート情報
    protected function _getReportParam()
    {
        global $gen_db;

        $info = array();
        $info['reportTitle'] = _g("入金一覧");
        $info['report'] = "PayingIn";
        $info['pageKeyColumn'] = "dummy";

        // SQLのfromで指定されているテーブルのリスト。
        // ここで指定されたテーブルのカラムはSQL selectとタグリストに自動追加される。
        $info['tables'] = array(
            array("customer_master", false, ""),
            array("currency_master", false, " (" . _g("取引通貨") . ")"),
        );

        // タグリスト（この帳票固有のもの）
        $info['tagList'] = array(
            array("●" . _g("入金")),
            array("入金_入金日", _g("入金登録画面 [入金日]"), "2014-01-01"),
            array("入金_金額", _g("入金登録画面 [金額]"), 10000),
            array("入金_金額_基軸", _g("入金登録画面 [金額]。外貨の場合は基軸に換算した値"), 10000),
            array("入金_入金種別", _g("入金登録画面 [種別]"), _g("振込")),
            array("入金_レート", _g("外貨レート"), 100),
            array("入金_請求書番号", _g("入金登録画面 [請求書番号]"), "B00001"),
            array("入金_備考", _g("入金登録画面 [備考]"), _g("入金登録の備考")),
        );

        return $info;
    }
    // 印刷フラグの更新
    protected function _setPrintFlag($form)
    {
    }
}
