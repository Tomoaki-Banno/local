<?php

class Manufacturing_CustomerEdi_Report extends Base_PDFReportBase
{

    protected function _getQuery(&$form)
    {
        // 印刷対象データを配列に列挙する
        $idArr = array();
        foreach ($form as $name => $value) {
            if (substr($name, 0, 6) == "check_") {
                $idArr[] = substr($name, 6, strlen($name) - 6);
            }
        }

        // 印刷フラグ更新用
        $form['idArr'] = $idArr;

        // 印刷対象データの取得
        // この帳票はEDI取引先が使用するものなので、FWの共通タグ機能は使用しない
        $query = "
             select
                -- headers

                1 as dummy

                ,customer_master.customer_no as 取引先コード
                ,customer_master.customer_name as 取引先名
                ,customer_master.person_in_charge as 取引先担当者名
                ,customer_master.tel as 取引先電話番号
                ,customer_master.fax as 取引先ファックス番号
                ,customer_master.zip as 取引先郵便番号
                ,customer_master.address1 as 取引先住所1
                ,customer_master.address2 as 取引先住所2
                ,coalesce(customer_master.report_language,0) as 帳票言語区分
                ,customer_master.remarks as 取引先備考1
                ,customer_master.remarks_2 as 取引先備考2
                ,customer_master.remarks_3 as 取引先備考3
                ,customer_master.remarks_4 as 取引先備考4
                ,customer_master.remarks_5 as 取引先備考5

                ,worker_master.worker_name as 自社担当者名
                
                -- details

                ,received_header.received_number as detail_発注番号
                ,received_header.customer_received_number as detail_自社注番
                ,received_header.received_date as detail_発注日

                ,received_detail.received_detail_id
                ,received_detail.line_no as detail_発注行番号
                ,received_detail.dead_line as detail_希望納期
                ,received_detail.received_quantity as detail_数量
                ,(case when delivery_detail.delivery_quantity is null then received_detail.received_quantity
                    else (received_detail.received_quantity-delivery_detail.delivery_quantity) end) as detail_発注残数
                ,(case when received_detail.foreign_currency_id is null then received_detail.product_price
                    else received_detail.foreign_currency_product_price end) as detail_単価
                ,(case when received_detail.foreign_currency_id is null then received_detail.product_price * received_detail.received_quantity
                    else received_detail.foreign_currency_product_price * received_detail.received_quantity end) as detail_金額
                ,received_detail.remarks as detail_備考

                ,item_master.item_code as detail_品目コード
                ,item_master.item_name as detail_品目名
                ,item_master.measure as detail_単位
                ,case tax_class when 1 then '" . _g("非課税") . "' else '" . _g("課税") . "' end as detail_課税区分

            from
                received_header
                inner join received_detail on received_header.received_header_id = received_detail.received_header_id
                inner join customer_master on received_header.customer_id = customer_master.customer_id
                left join item_master on received_detail.item_id = item_master.item_id
                left join worker_master on received_header.worker_id = worker_master.worker_id
                left join (
                    select
                        received_detail_id
                        ,SUM(delivery_quantity) as delivery_quantity
                     from
                        delivery_detail
                     group by
                        received_detail_id
                    ) as delivery_detail on received_detail.received_detail_id = delivery_detail.received_detail_id
            where
                 received_header.received_header_id in (" . join(",", $idArr) . ")
            order by
                -- テンプレート内の指定が優先されることに注意
                received_number
                ,line_no
        ";

        return $query;
    }

    // テンプレート情報
    protected function _getReportParam()
    {
        $info = array();
        $info['reportTitle'] = _g("発注書");
        $info['report'] = "CustomerEdi";
        $info['pageKeyColumn'] = "dummy";
        
        // この帳票はEDI取引先が使用するものなので、FWの共通タグ機能は使用しない
        $info['tables'] = array(
        );

        // タグリスト（この帳票固有のもの）
        $info['tagList'] = array(
            array("●" . _g("発注 ヘッダ")),
            array("発注番号", _g("発注登録画面 [発注番号]"), "A10010001"),
            array("自社注番", _g("発注登録画面 [自社注番]"), "C101"),
            array("発注日", _g("発注登録画面 [発注日]"), "2014-01-01"),
            array("自社担当者名", _g("受注登録画面 [担当者(自社)]"), _g("自社 太郎")),
            array("●" . _g("発注 ヘッダ（取引先関連）")),
            array("取引先コード", _g("受注登録画面 [得意先]"), "100"),
            array("取引先名", _g("受注登録画面 [得意先]"), _g("取引先株式会社")),
            array("取引先担当者名", _g("取引先マスタ [担当者]"), _g("取引先 太郎")),
            array("取引先電話番号", _g("取引先マスタ [電話番号]"), "012-345-6789"),
            array("取引先ファックス番号", _g("取引先マスタ [FAX番号]"), "012-345-6789"),
            array("取引先郵便番号", _g("取引先マスタ [郵便番号]"), "123-4567"),
            array("取引先住所1", _g("取引先マスタ [住所1]"), _g("愛知県名古屋市東区泉1-21-27")),
            array("取引先住所2", _g("取引先マスタ [住所2]"), _g("泉ファーストスクエアビル5F")),
            array("帳票言語区分", _g("取引先マスタ [帳票言語区分]。0:日本語、1:英語"), "1"),
            array("取引先備考1", _g("取引先マスタ [取引先備考1]"), _g("取引先備考1")),
            array("取引先備考2", _g("取引先マスタ [取引先備考2]"), _g("取引先備考2")),
            array("取引先備考3", _g("取引先マスタ [取引先備考3]"), _g("取引先備考3")),
            array("取引先備考4", _g("取引先マスタ [取引先備考4]"), _g("取引先備考4")),
            array("取引先備考5", _g("取引先マスタ [取引先備考5]"), _g("取引先備考5")),
            array("●" . _g("発注 明細")),
            array("発注行番号", _g("発注登録画面 [行]"), "1"),
            array("品目コード", _g("発注登録画面 [品目]"), "code001"),
            array("品目名", _g("発注登録画面 [品目]"), _g("テスト品目")),
            array("数量", _g("発注登録画面 [数量]"), 100),
            array("単位", _g("発注登録画面 [管理単位]"), _g("個")),
            array("単価", _g("発注登録画面 [単価]"), 100),
            array("金額", _g("発注登録画面 [金額]"), 100),
            array("課税区分", _g("発注登録画面 [課税区分]"), _g("課税")),
            array("希望納期", _g("発注登録画面 [希望納期]"), "2014-01-10"),
            array("発注残数", _g("発注残数"), 100),
            array("備考", _g("発注登録画面 [発注登録備考]"), _g("発注登録備考")),
        );

        return $info;
    }

    // 印刷フラグの更新
    protected function _setPrintFlag($form)
    {
        // 帳票発行済みフラグ
        Logic_Received::setCustomerReceivedPrintedFlag($form['idArr'], true);
        return;
    }

}