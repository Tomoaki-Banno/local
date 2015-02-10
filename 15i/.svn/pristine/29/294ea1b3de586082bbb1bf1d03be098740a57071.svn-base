<?php

class Partner_PartnerEdi_Report2 extends Base_PDFReportBase
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

        // order_detail_idが指定されているので、order_header_idに変換しておく
        if (count($idArr) > 0) {
            $query = "
            select
                order_header_id
            from
                order_detail
            where
                order_detail_id in (" . join(",", $idArr) . ")
            group by
                order_header_id
            ";
            $idArr2 = $gen_db->getArray($query);
            $idArr = array();
            foreach ($idArr2 as $row) {
                $idArr[] = $row['order_header_id'];
            }
        }

        // 印刷フラグ更新用
        $form['idArr'] = $idArr;

        // 消費税率（旧verデータ用）
        // 本来はSQL内でオーダー日に応じた税率を取得すべきだが、税額を保持していない旧verでのみ使用される値のため、
        // 手を抜いて本日時点の税額を取得している。
        $taxRate = Logic_Tax::getTaxRate(date('Y-m-d'));

        // 印刷対象データの取得
        $query = "
            select
                -- headers

                order_header.partner_id

                -- details

                ,customer_master.customer_no as detail_取引先コード
                ,customer_master.customer_name as detail_取引先名
                ,customer_master.person_in_charge as detail_取引先担当者名
                ,customer_master.tel as detail_取引先電話番号
                ,customer_master.fax as detail_取引先ファックス番号
                ,customer_master.zip as detail_取引先郵便番号
                ,customer_master.address1 as detail_取引先住所1
                ,customer_master.address2 as detail_取引先住所2
                ,customer_master.remarks as 取引先備考1
                ,customer_master.remarks_2 as 取引先備考2
                ,customer_master.remarks_3 as 取引先備考3
                ,customer_master.remarks_4 as 取引先備考4
                ,customer_master.remarks_5 as 取引先備考5
                ,customer_master.delivery_port as 取引先納入場所

                ,order_header.order_date as detail_発注日
                ,coalesce(worker_master.worker_name,'') as detail_自社担当者名
                ,coalesce(section_master.section_name,'') as detail_自社部門名
                ,order_header.remarks_header as detail_備考

                ,order_detail.order_no as detail_オーダー番号
                ,order_detail.seiban as 製番
                ,order_detail.order_detail_quantity / coalesce(order_detail.multiple_of_order_measure,1) as detail_数量
                ,order_detail.order_measure as detail_単位
                ,(case when order_detail.foreign_currency_id is null then order_detail.item_price else order_detail.foreign_currency_item_price end)
                     * coalesce(order_detail.multiple_of_order_measure,1) as detail_単価
                ,order_detail.order_detail_dead_line as detail_納期
                ,order_detail.item_code as detail_品目コード
                ,order_detail.item_name as detail_品目名
                ,order_detail.item_sub_code as detail_型番
                ,item_master.maker_name as detail_メーカー
                ,item_master.rack_no as detail_棚番
                ,item_master.spec as detail_仕様
                ,item_master.comment as detail_品目備考1
                ,item_master.comment_2 as detail_品目備考2
                ,item_master.comment_3 as detail_品目備考3
                ,item_master.comment_4 as detail_品目備考4
                ,item_master.comment_5 as detail_品目備考5

                ,coalesce(received_item.received_number,'') as detail_受注番号
                ,coalesce(received_item.customer_received_number,'') as detail_客先注番
                ,coalesce(received_item.item_code,'') as detail_受注品目コード
                ,coalesce(received_item.item_name,'') as detail_受注品目名
                ,coalesce(received_item.customer_name,'') as detail_発送先名
                ,coalesce(received_item.remarks_header,'') as detail_受注備考1
                ,coalesce(received_item.remarks_header_2,'') as detail_受注備考2
                ,coalesce(received_item.remarks_header_3,'') as detail_受注備考3
                ,coalesce(received_item.remarks,'') as detail_受注明細備考

                ,t_tax.amount as detail_金額
                ,t_tax.price_tax as detail_消費税
                ,t_tax.amount + t_tax.price_tax as detail_税込金額
                ,t_tax.tax_class as detail_課税区分

            from
                order_header
                inner join order_detail on order_header.order_header_id = order_detail.order_header_id

                /* 金額・消費税 計算用 */
                inner join
                    ( select
                        order_detail_id
                        ,coalesce(case when order_detail.foreign_currency_id is null then order_detail.order_amount else order_detail.foreign_currency_order_amount end
                        	-- 旧verデータ用
                        	,round((case when order_detail.foreign_currency_id is null then order_detail.item_price else order_detail.foreign_currency_item_price end)
                            	* order_detail_quantity)
                         ) as amount
                        ,coalesce(case when order_detail.foreign_currency_id is null then order_detail.order_tax else 0 end
                        	-- 旧verデータ用
                        	,case when tax_class in (1) then 0 else round(round((case when order_detail.foreign_currency_id is null then order_detail.item_price else order_detail.foreign_currency_item_price end)
                            	* order_detail_quantity) * " . Gen_Math::div($taxRate, 100) . ") end
                         ) as price_tax
                        ,case tax_class when 1 then '" . _g("非課税") . "' else '" . _g("課税") . "' end as tax_class
                      from order_detail
                    )
                 as t_tax on order_detail.order_detail_id = t_tax.order_detail_id

                left join customer_master on order_header.partner_id = customer_master.customer_id
                left join customer_master as t_dp on order_header.delivery_partner_id = t_dp.customer_id
                left join worker_master on order_header.worker_id = worker_master.worker_id
                left join section_master on order_header.section_id = section_master.section_id
                left join item_master on order_detail.item_id = item_master.item_id
                left join (
                    select
                        seiban
                        ,received_number
                        ,customer_received_number
                        ,item_code
                        ,item_name
                        ,customer_name
                        ,received_header.remarks_header
                        ,received_header.remarks_header_2
                        ,received_header.remarks_header_3
                        ,received_detail.remarks
                    from
                        received_detail
                        inner join received_header on received_header.received_header_id=received_detail.received_header_id
                        left join customer_master on received_header.delivery_customer_id = customer_master.customer_id
                        left join item_master on received_detail.item_id = item_master.item_id
                    ) as received_item on order_detail.seiban = received_item.seiban and order_detail.seiban <> ''

            where
                order_header.partner_id = {$_SESSION["user_customer_id"]}
                and order_header.order_header_id in (" . join(",", $idArr) . ")
            order by
                -- テンプレート内の指定が優先されることに注意
                customer_no, order_id_for_user, line_no
        ";

        return $query;
    }

    // テンプレート情報
    protected function _getReportParam()
    {
        $info = array();
        $info['reportTitle'] = _g("注文リスト");
        $info['report'] = "PartnerEdi2";
        $info['pageKeyColumn'] = "partner_id";

        // SQLのfromで指定されているテーブルのリスト。
        // ここで指定されたテーブルのカラムはSQL selectとタグリストに自動追加される。
        $info['tables'] = array(
            array("item_master", true, ""),
        );

        // タグリスト（この帳票固有のもの）
        $info['tagList'] = array(
            array("●" . _g("注文書")),
            array("オーダー番号", _g("注文登録画面[オーダー番号]"), _g("1000")),
            array("発注日", _g("注文登録画面 [発注日]"), "2014-01-01"),
            array("自社担当者名", _g("注文登録画面 [担当者(自社)]"), _g("自社 太郎")),
            array("自社部門名", _g("注文登録画面 [部門(自社)]"), _g("自社 太郎")),
            array("備考", _g("注文登録画面 [備考]"), _g("注文書の備考")),
            array("製番", _g("注文登録画面[製番]（製番品目のみ）"), _g("100")),
            array("数量", _g("注文登録画面[表示数量]"), _g("2000")),
            array("単位", _g("品目マスタ [単位]"), _g("個")),
            array("単価", _g("注文登録画面[発注単価]"), _g("100")),
            array("納期", _g("注文登録画面[注文納期]"), "2014-01-02"),
            array("品目コード", _g("注文登録画面[品目]"), _g("code001")),
            array("品目名", _g("注文登録画面[品目]"), _g("テスト品目")),
            array("金額", _g("金額（数量×単価）"), "200000"),
            array("消費税", _g("税額"), "10000"),
            array("税込金額", _g("金額 + 消費税"), "210000"),
            array("課税区分", _g("品目マスタ [課税区分]"), _g("課税")),
            array("●" . _g("注文書（品目関連）")),
            array("型番", _g("品目マスタ [型番]"), _g("テスト型番")),
            array("メーカー", _g("品目マスタ [メーカー]"), _g("テストメーカー")),
            array("棚番", _g("品目マスタ [棚番]"), _g("テスト棚番")),
            array("仕様", _g("品目マスタ [仕様]"), _g("テスト仕様")),
            array("品目備考1", _g("品目マスタ [備考1]"), _g("テスト品目備考1")),
            array("品目備考2", _g("品目マスタ [備考2]"), _g("テスト品目備考2")),
            array("品目備考3", _g("品目マスタ [備考3]"), _g("テスト品目備考3")),
            array("品目備考4", _g("品目マスタ [備考4]"), _g("テスト品目備考4")),
            array("品目備考5", _g("品目マスタ [備考5]"), _g("テスト品目備考5")),
            array("●" . _g("注文書（受注関連）※製番品目のみ")),
            array("受注番号", _g("受注番号（製番品目のみ）"), "A10010001"),
            array("客先注番", _g("客先注番（製番品目のみ）"), "C101"),
            array("受注品目コード", _g("受注品目コード（製番品目のみ）"), _g("code01")),
            array("受注品目名", _g("受注品目名（製番品目のみ）"), _g("テスト受注品目")),
            array("発送先名", _g("受注登録画面 [発送先]（製番品目のみ）"), _g("発送先株式会社")),
            array("受注備考1", _g("受注登録画面 [備考1]（製番品目のみ）"), _g("受注備考1")),
            array("受注備考2", _g("受注登録画面 [備考2]（製番品目のみ）"), _g("受注備考2")),
            array("受注備考3", _g("受注登録画面 [備考3]（製番品目のみ）"), _g("受注備考3")),
            array("受注明細備考", _g("受注登録画面 明細行[備考]（製番品目のみ）"), _g("受注明細備考")),
            array("●" . _g("注文書（取引先関連）")),
            array("取引先コード", _g("注文登録画面 [取引先]"), _g("100")),
            array("取引先名", _g("注文登録画面 [取引先]"), _g("取引先株式会社")),
            array("取引先担当者名", _g("取引先マスタ [担当者]"), _g("取引先 太郎")),
            array("取引先電話番号", _g("取引先マスタ [電話番号]"), _g("012-345-6789")),
            array("取引先ファックス番号", _g("取引先マスタ [FAX番号]"), _g("012-345-6789")),
            array("取引先郵便番号", _g("取引先マスタ [郵便番号]"), _g("123-4567")),
            array("取引先住所1", _g("取引先マスタ [住所1]"), _g("愛知県名古屋市東区泉1-21-27")),
            array("取引先住所2", _g("取引先マスタ [住所2]"), _g("泉ファーストスクエアビル5F")),
            array("取引先備考1", _g("取引先マスタ [取引先備考1]"), _g("取引先備考1")),
            array("取引先備考2", _g("取引先マスタ [取引先備考2]"), _g("取引先備考2")),
            array("取引先備考3", _g("取引先マスタ [取引先備考3]"), _g("取引先備考3")),
            array("取引先備考4", _g("取引先マスタ [取引先備考4]"), _g("取引先備考4")),
            array("取引先備考5", _g("取引先マスタ [取引先備考5]"), _g("取引先備考5")),
            array("取引先納入場所", _g("取引先マスタ [納入場所]"), _g("取引先納入場所")),
        );

        return $info;
    }

    // 印刷フラグの更新
    protected function _setPrintFlag($form)
    {
        // 帳票発行済みフラグ
        Logic_Order::setPartnerOrderPrintedFlag($form['idArr'], true);
        return;
    }

}