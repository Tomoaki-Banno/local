<?php

class Partner_PartnerEdi_Report extends Base_PDFReportBase
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
        $keyCurrency = $gen_db->queryOneValue("select key_currency from company_master");
        $query = "
            select
                -- headers

                order_header.order_header_id
                ,order_header.partner_id

                ,order_header.order_id_for_user as 注文書番号
                ,order_header.order_date as 注文日
                ,coalesce(worker_master.worker_name,'') as 自社担当者名
                ,coalesce(section_master.section_name,'') as 自社部門名
                ,order_header.remarks_header as 備考

                ,customer_master.customer_no as 取引先コード
                ,customer_master.customer_name as 取引先名
                ,customer_master.person_in_charge as 取引先担当者名
                ,customer_master.tel as 取引先電話番号
                ,customer_master.fax as 取引先ファックス番号
                ,customer_master.zip as 取引先郵便番号
                ,customer_master.address1 as 取引先住所1
                ,customer_master.address2 as 取引先住所2
                ,coalesce(customer_master.report_language,0) as 現品票区分
                ,customer_master.remarks as 取引先備考1
                ,customer_master.remarks_2 as 取引先備考2
                ,customer_master.remarks_3 as 取引先備考3
                ,customer_master.remarks_4 as 取引先備考4
                ,customer_master.remarks_5 as 取引先備考5
                ,customer_master.delivery_port as 取引先納入場所

                ,coalesce(t_dp.customer_no,'') as 発送先コード
                ,coalesce(t_dp.customer_name,'') as 発送先名
                ,coalesce(t_dp.tel,'') as 発送先電話番号
                ,coalesce(t_dp.fax,'') as 発送先ファックス番号
                ,coalesce(t_dp.zip,'') as 発送先郵便番号
                ,coalesce(t_dp.address1,'') as 発送先住所1
                ,coalesce(t_dp.address2,'') as 発送先住所2
                ,coalesce(t_dp.remarks,'') as 発送先備考1
                ,coalesce(t_dp.remarks_2,'') as 発送先備考2
                ,coalesce(t_dp.remarks_3,'') as 発送先備考3
                ,coalesce(t_dp.remarks_4,'') as 発送先備考4
                ,coalesce(t_dp.remarks_5,'') as 発送先備考5
                ,coalesce(t_dp.delivery_port,'') as 発送先納入場所

                ,t_total.total_amount as 総合計金額
                ,t_total.total_tax as 総合計消費税
                ,coalesce(t_total.total_amount,0) + coalesce(t_total.total_tax,0) as 総税込金額

                -- details

                ,order_detail.line_no as detail_現品票行番号
                ,order_detail.order_no as detail_オーダー番号
                ,order_detail.seiban as 製番
                ,order_detail.order_detail_quantity / coalesce(order_detail.multiple_of_order_measure,1) as detail_数量
                ,item_master.quantity_per_carton as detail_入数
                ,ceil(order_detail.order_detail_quantity / coalesce(order_detail.multiple_of_order_measure,1) / item_master.quantity_per_carton) as detail_箱数
                ,case when order_detail_completed then 0 else
                	(coalesce(order_detail.order_detail_quantity,0) - coalesce(order_detail.accepted_quantity,0)) / coalesce(order_detail.multiple_of_order_measure,1)
                 end as detail_発注残
                ,order_detail.order_measure as detail_単位
                ,order_detail.order_detail_quantity as detail_管理単位数量
                ,item_master.measure as detail_管理単位
                ,coalesce(order_detail.multiple_of_order_measure,1) as detail_手配単位倍数
                ,(case when order_detail.foreign_currency_id is null then order_detail.item_price else order_detail.foreign_currency_item_price end)
                     * coalesce(order_detail.multiple_of_order_measure,1) as detail_単価
                ,order_detail.order_detail_dead_line as detail_納期
                ,order_detail.item_name as detail_品目名
                ,order_detail.item_code as detail_品目コード
                ,order_detail.item_sub_code as detail_型番
                ,item_master.maker_name as detail_メーカー
                ,item_master.rack_no as detail_棚番
                ,item_master.spec as detail_仕様
                ,item_master.comment as detail_品目備考1
                ,item_master.comment_2 as detail_品目備考2
                ,item_master.comment_3 as detail_品目備考3
                ,item_master.comment_4 as detail_品目備考4
                ,item_master.comment_5 as detail_品目備考5
                ,cast(item_master.image_file_oid as text) || ',' || item_master.image_file_name as 品目画像

                ,t_tax.amount as detail_金額
                ,t_tax.price_tax as detail_消費税
                ,t_tax.amount + t_tax.price_tax as detail_税込金額
                ,t_tax.tax_class as detail_課税区分
            	,case when currency_name is null then '{$keyCurrency}' else currency_name end as detail_取引通貨
                ,order_detail.remarks as detail_明細備考

                -- 外製用

                ,order_detail.subcontract_parent_order_no as detail_親オーダー番号
                ,order_detail.subcontract_process_name as detail_工程名
                ,order_detail.subcontract_process_remarks_1 as detail_工程メモ1
                ,order_detail.subcontract_process_remarks_2 as detail_工程メモ2
                ,order_detail.subcontract_process_remarks_3 as detail_工程メモ3
                ,order_detail.subcontract_ship_to as detail_発送先


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

                /* 総合計 計算用 */
                inner join (
                    select
                        order_header.order_header_id
                        ,sum(coalesce(case when order_detail.foreign_currency_id is null then order_detail.order_amount else order_detail.foreign_currency_order_amount end
                        -- 旧verデータ用
                        ,round((case when order_detail.foreign_currency_id is null then order_detail.item_price else order_detail.foreign_currency_item_price end)
                            * order_detail_quantity)
                            )) as total_amount
                        ,sum(coalesce(case when order_detail.foreign_currency_id is null then order_detail.order_tax else 0 end
                        -- 旧verデータ用
                        ,case when tax_class in (1) then 0 else round(round((case when order_detail.foreign_currency_id is null then order_detail.item_price else order_detail.foreign_currency_item_price end)
                            * order_detail_quantity) * " . Gen_Math::div($taxRate, 100) . ") end
                            )) as total_tax
                    from
                        order_header
                        inner join order_detail on order_header.order_header_id = order_detail.order_header_id
                    where
                        order_header.order_header_id in (" . join(",", $idArr) . ")
                    group by
                        order_header.order_header_id
                    ) as t_total on order_header.order_header_id = t_total.order_header_id

                left join customer_master on order_header.partner_id = customer_master.customer_id
                left join customer_master as t_dp on order_header.delivery_partner_id = t_dp.customer_id
                left join worker_master on order_header.worker_id = worker_master.worker_id
                left join section_master on order_header.section_id = section_master.section_id
                left join item_master on order_detail.item_id = item_master.item_id
            	left join currency_master on order_detail.foreign_currency_id = currency_master.currency_id

            where
                order_header.partner_id = {$_SESSION["user_customer_id"]}
                and order_header.order_header_id in (" . join(",", $idArr) . ")
            order by
                -- テンプレート内の指定が優先されることに注意
                order_id_for_user, line_no
        ";

        return $query;
    }

    // テンプレート情報
    protected function _getReportParam()
    {
        global $gen_db;

        $info = array();
        $info['reportTitle'] = _g("現品票");
        $info['report'] = "PartnerEdi";
        $info['pageKeyColumn'] = "order_header_id";

        $keyCurrency = $gen_db->queryOneValue("select key_currency from company_master");

        // SQLのfromで指定されているテーブルのリスト。
        // ここで指定されたテーブルのカラムはSQL selectとタグリストに自動追加される。
        $info['tables'] = array(
            array("item_master", true, ""),
        );

        // タグリスト（この帳票固有のもの）
        $info['tagList'] = array(
            array("●" . _g("現品票 ヘッダ")),
            array("注文書番号", _g("注文受信 [注文書番号]"), "1000"),
            array("注文日", _g("注文受信 [注文日]"), "2014-01-01"),
            array("自社担当者名", _g("注文受信 [担当者(自社)]"), _g("自社 太郎")),
            array("自社部門名", _g("注文受信 [部門(自社)]"), _g("自社 太郎")),
            array("備考", _g("注文受信 [備考]"), _g("現品票の備考")),
            array("●" . _g("現品票 ヘッダ（取引先関連）")),
            array("取引先コード", _g("注文受信 [取引先]"), "100"),
            array("取引先名", _g("注文受信 [取引先]"), _g("取引先株式会社")),
            array("取引先担当者名", _g("取引先マスタ [担当者]"), _g("取引先 太郎")),
            array("取引先電話番号", _g("取引先マスタ [電話番号]"), "012-345-6789"),
            array("取引先ファックス番号", _g("取引先マスタ [FAX番号]"), "012-345-6789"),
            array("取引先郵便番号", _g("取引先マスタ [郵便番号]"), _g("123-4567")),
            array("取引先住所1", _g("取引先マスタ [住所1]"), _g("愛知県名古屋市東区泉1-21-27")),
            array("取引先住所2", _g("取引先マスタ [住所2]"), _g("泉ファーストスクエアビル5F")),
            array("現品票区分", _g("取引先マスタ [帳票言語区分]。0:日本語、1:英語"), "1"),
            array("取引先備考1", _g("取引先マスタ [取引先備考1]"), _g("取引先備考1")),
            array("取引先備考2", _g("取引先マスタ [取引先備考2]"), _g("取引先備考2")),
            array("取引先備考3", _g("取引先マスタ [取引先備考3]"), _g("取引先備考3")),
            array("取引先備考4", _g("取引先マスタ [取引先備考4]"), _g("取引先備考4")),
            array("取引先備考5", _g("取引先マスタ [取引先備考5]"), _g("取引先備考5")),
            array("取引先納入場所", _g("取引先マスタ [納入場所]"), _g("取引先納入場所")),
            array("●" . _g("現品票 ヘッダ（発送先関連）")),
            array("発送先コード", _g("注文受信 [発送先]"), "200"),
            array("発送先名", _g("注文受信 [発送先]"), _g("発送先株式会社")),
            array("発送先電話番号", _g("取引先マスタ [電話番号]"), "123-456-7890"),
            array("発送先ファックス番号", _g("取引先マスタ [FAX番号]"), "123-456-7890"),
            array("発送先郵便番号", _g("取引先マスタ [郵便番号]"), _g("123-4567")),
            array("発送先住所1", _g("取引先マスタ [住所1]"), _g("愛知県名古屋市東区泉1-21-27")),
            array("発送先住所2", _g("取引先マスタ [住所2]"), _g("泉ファーストスクエアビル5F")),
            array("発送先備考1", _g("取引先マスタ [取引先備考1]"), _g("発送先備考1")),
            array("発送先備考2", _g("取引先マスタ [取引先備考2]"), _g("発送先備考2")),
            array("発送先備考3", _g("取引先マスタ [取引先備考3]"), _g("発送先備考3")),
            array("発送先備考4", _g("取引先マスタ [取引先備考4]"), _g("発送先備考4")),
            array("発送先備考5", _g("取引先マスタ [取引先備考5]"), _g("発送先備考5")),
            array("発送先納入場所", _g("取引先マスタ [納入場所]"), _g("発送先納入場所")),
            array("●" . _g("現品票 ヘッダ（合計金額）")),
            array("総合計金額", _g("金額の合計"), "200000"),
            array("総合計消費税", _g("税額の合計"), "10000"),
            array("総税込金額", _g("税込金額の合計"), "210000"),
            array("●" . _g("現品票 明細")),
            array("現品票行番号", _g("明細行： 注文受信 [行番号]"), 1),
            array("オーダー番号", _g("明細行： 注文受信 [オーダー番号]"), "1000"),
            array("製番", _g("明細行： 注文受信 [製番]（製番品目のみ）"), "100"),
            array("品目コード", _g("明細行： 注文受信 [品目]"), "code001"),
            array("品目名", _g("明細行： 注文受信 [品目]"), _g("テスト品目")),
            array("数量", _g("明細行： 注文受信 [表示数量]"), "2000"),
            array("箱数", _g("明細行： 注文受信 [表示数量] ÷ 品目マスタ [入数]"), 10),
            array("発注残", _g("明細行： 発注残"), "0"),
            array("単位", _g("明細行： 品目マスタ [手配単位]"), _g("箱")),
            array("管理単位数量", _g("明細行： 注文受信 [数量]。管理単位での数量"), "20000"),
            array("管理単位", _g("明細行： 品目マスタ [管理単位]"), _g("個")),
            array("手配単位倍数", _g("明細行： 注文受信 [倍数]"), 10),
            array("単価", _g("明細行： 注文受信 [単価]"), "100"),
            array("納期", _g("明細行： 注文受信 [注文納期]"), "2014-01-02"),
            array("金額", _g("明細行： 金額（数量×単価）"), "200000"),
            array("消費税", _g("明細行： 税額"), "10000"),
            array("税込金額", _g("明細行： 金額 + 消費税"), "210000"),
            array("課税区分", _g("明細行： 品目マスタ [課税区分]"), _g("課税")),
            array("取引通貨", _g("注文登録画面 [取引通貨]"), $keyCurrency),
            array("明細備考", _g("明細行：注文受信 明細行 [備考]"), _g("現品票の明細備考")),
            array("●" . _g("現品票 明細（品目関連）")),
            array("型番", _g("明細行： 品目マスタ [型番]"), _g("テスト型番")),
            array("仕様", _g("明細行： 品目マスタ [仕様]"), _g("テスト仕様")),
            array("メーカー", _g("明細行： 品目マスタ [メーカー]"), _g("テストメーカー")),
            array("棚番", _g("明細行： 品目マスタ [棚番]"), _g("テスト棚番")),
            array("入数", _g("明細行： 品目マスタ [入数]"), "100"),
            array("品目備考1", _g("明細行： 品目マスタ [備考1]"), _g("テスト品目備考1")),
            array("品目備考2", _g("明細行： 品目マスタ [備考2]"), _g("テスト品目備考2")),
            array("品目備考3", _g("明細行： 品目マスタ [備考3]"), _g("テスト品目備考3")),
            array("品目備考4", _g("明細行： 品目マスタ [備考4]"), _g("テスト品目備考4")),
            array("品目備考5", _g("明細行： 品目マスタ [備考5]"), _g("テスト品目備考5")),
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