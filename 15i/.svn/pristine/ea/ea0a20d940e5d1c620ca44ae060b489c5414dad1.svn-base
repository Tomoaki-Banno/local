<?php

abstract class Base_PDFReportBase
{

    var $noPrint = false;
    var $noPrintMsg;
    var $errorAction;

    abstract protected function _getQuery(&$form);

    abstract protected function _getReportParam();

    abstract protected function _setPrintFlag($form);

    function execute(&$form)
    {
        set_time_limit(GEN_REPORT_MAX_SECONDS);

        // 子クラスから帳票情報を取得
        $query = $this->_getQuery($form);
        $info = $this->getReportParam($form);

        // PDF発行可否
        if ($this->noPrint == true) {
            // メッセージの作成（子クラスで設定）
            $msg = h(@$this->noPrintMsg);
            if (isset($this->errorAction) && $this->errorAction != "") {
                $action = $this->errorAction;
            } else {
                $action = $form['gen_action_group'] . "List";
            }
            header('Location:' . "index.php?action={$action}&gen_restore_search_condition=true&gen_nonprint={$msg}");
            return;
        }

        // tables に設定されているテーブル（SQLのfromに出てくるテーブル）のカラムを
        // SQL select に追加する。
        $addSelectList = "";
        $tagInfo = self::_getTableTagInfo($info['tables']);
        foreach ($tagInfo as $tag) {
            if (count($tag) > 2) {      // 見出しタグはスキップ
                if ($addSelectList != "")
                    $addSelectList .= ",";
                $addSelectList .= "{$tag[0]} as " . ($tag[2] ? "detail_" : "") . "{$tag[1]}";
            }
        }
        if ($addSelectList != "") {
            if (is_array($query)) {
                foreach ($query as $key => $q) {
                    $selectPos = stripos($q, 'select') + 6;
                    $query[$key] =  substr($q, 0, $selectPos) . " " . $addSelectList . "," . substr($q, $selectPos + 1);
                }
            } else {
                $selectPos = stripos($query, 'select') + 6;
                $query =  substr($query, 0, $selectPos) . " " . $addSelectList . "," . substr($query, $selectPos + 1);
            }
        }

        if (isset($form['gen_unitTestMode'])) {
            return $query;
        }

        // PDF発行
        //  戻り値： 0:成功、1:データなし、2:データが多すぎる
        $pdf = new Gen_PDF();
        $res = $pdf->createPDFFromExcel(
                $info['report']
                , $info['report'] . "_" . date('Ymd_Hi') . ".pdf"
                , $query
                , $info['pageKeyColumn']
                , @$info['pageCountKeyColumn']
        );

        // 更新ログ用に情報取得
        $data = $pdf->getTemplateInfo($info['report']);

        // データなし、または多すぎの警告
        switch ($res) {
            case "0":
                $alert = '';
                // データアクセスログ
                Gen_Log::dataAccessLog($info['reportTitle'], _g("発行"), "[" . _g("テンプレート") . "] " . $data[0]);
                // 印刷済フラグの更新
                $this->_setPrintFlag($form);
                break;
            case "1":
                $alert = "alert('" . _g("データがありません。") . "');";
                break;
            case "2":
                // データアクセスログ
                Gen_Log::dataAccessLog($info['reportTitle'], _g("発行"), sprintf(_g("%sページを超えたため発行失敗。"), GEN_REPORT_MAX_PAGES). " [" . _g("テンプレート") . "] " . $data[0]);
                $alert = "alert('" . sprintf(_g("出力ページ数が多すぎます。1回に出力できるのは最大%sページです。表示条件を変更するなどして印刷対象件数を減らし、再発行してください。\\n（請求書もしくは所要量計算からのオーダー発行の場合、データはすべて作成されています）"), GEN_REPORT_MAX_PAGES) . "');";
                break;
            default:
                $alert = "alert('" . sprintf(_g("指定されたテンプレート「%s」が存在しません。"),h($res)) . "');";
                break;
        }

        // ダイアログ表示（データなし・ページ数多すぎ）・ウィンドウ閉じ（別ウィンドウ方式のとき）。
        // 　10iでは通常の帳票出力のときもこの処理を行なっていたが、それだとFirefoxで印刷後にすぐ再表示ボタンを
        // 　押したときに文字化けしてしまう（サーバーのみ）ことがわかったため、上記のケースだけとした。
        if ((isset($form['windowOpen']) && $form['windowOpen']) || $res <> "0") {
            $form['response_noEscape'] = "
                <!DOCTYPE html>
                <html lang=\"ja\" class=\"outer\">
                <head>
                <meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\">
                <meta http-equiv=\"Content-Script-Type\" content=\"text/javascript\">
                </head>
                <body>
                <script>{$alert}
            ";
            if (isset($form['windowOpen']) && $form['windowOpen']) {
                // 別ウィンドウ方式（このレポートクラスを新しいウィンドウでのPOSTの形で処理）の場合。
                // リダイレクトさせるとブラウザによってはJSエラーが発生する。また次回のAjaxで誤動作が発生する場合もある。
                $form['response_noEscape'] .= "window.close();";
            } else {
                if (isset($this->errorAction) && $this->errorAction != "") {
                    $action = $this->errorAction;
                } else {
                    $action = $form['gen_action_group'] . "List";
                }
                $form['response_noEscape'] .= "location.href='index.php?action={$action}&gen_restore_search_condition=true';";
            }
            $form['response_noEscape'] .= "</script></body></html>";
        }

        $form['gen_restore_search_condition'] = 'true';

        return "simple.tpl";
    }

    function getReportParam($form)
    {
        $info = $this->_getReportParam($form);

        // tables に設定されているテーブル（SQLのfromに出てくるテーブル）のカラムを
        // タグリストに追加する。
        $tagInfoAll = array();
        if (is_array($info['tables'])) {
            $tagInfo = $this->_getTableTagInfo($info['tables']);
            foreach($tagInfo as $key => $tag) {
                if (count($tag) > 2) {   // セパレータは除く
                    $tagInfo[$key] = array_slice($tagInfo[$key], 1, 3);     // 先頭要素（カラム名）を削除
                }
            }
            $tagInfoAll = array_merge($tagInfoAll, $tagInfo);
        }
        $info['tagList'] = array_merge($tagInfoAll, $info['tagList']);

        // システムタグ
        $info['tagList'] = array_merge($info['tagList'], $this->_getSystemTag());

        return $info;
    }

    function setPrintFlag($form)
    {
        $this->_setPrintFlag($form);
    }

    private function _getSystemTag()
    {
        return array(
            array("●" . _g("自社情報")),
            array("自社名", _g("自社情報マスタ [会社名]"), ""),
            array("自社名（英語表記）", _g("自社情報マスタ [会社名（英語表記）]"), ""),
            array("自社郵便番号", _g("自社情報マスタ [郵便番号]"), ""),
            array("自社住所1（英語表記）", _g("自社情報マスタ [住所1（英語表記）]"), ""),
            array("自社住所1", _g("自社情報マスタ [住所1]"), ""),
            array("自社住所2", _g("自社情報マスタ [住所2]"), ""),
            array("自社住所2（英語表記）", _g("自社情報マスタ [住所2（英語表記）]"), ""),
            array("自社電話番号", _g("自社情報マスタ [TEL]"), ""),
            array("自社ファックス番号", _g("自社情報マスタ [FAX]"), ""),
            array("自社取引銀行", _g("自社情報マスタ [取引銀行]"), ""),
            array("自社取引銀行口座", _g("自社情報マスタ [口座番号]"), ""),
            array("●" . _g("システムタグ")),
            array("ページ", _g("ページ番号"), ""),
            array("総ページ数", _g("総ページ数"), ""),
            array("複製ページ数", _g("複製ページ数"), ""),
            // 15iで廃止。理由は Gen_PDF を「複製ページ」で検索
            // array("複製ページ", _g("複製ページ番号"), ""),
        );
    }

    protected function getFromItemMasterChildren()
    {
        return "
            left join item_group_master as item_group_master1 on item_master.item_group_id = item_group_master1.item_group_id
            left join item_group_master as item_group_master2 on item_master.item_group_id_2 = item_group_master2.item_group_id
            left join item_group_master as item_group_master3 on item_master.item_group_id_3 = item_group_master3.item_group_id
            left join location_master as location_master_default_accepted on item_master.default_location_id = location_master_default_accepted.location_id
            left join location_master as location_master_default_use on item_master.default_location_id_2 = location_master_default_use.location_id
            left join location_master as location_master_default_finish on item_master.default_location_id_3 = location_master_default_finish.location_id
            left join item_order_master as item_order_master0 on item_master.item_id = item_order_master0.item_id and item_order_master0.line_number = 0
            left join customer_master as customer_master_item_order0 on item_order_master0.order_user_id = customer_master_item_order0.customer_id
        ";
    }

    protected function getFromItemMasterProcess()
    {
        $from = "";
        for ($i = 0; $i < GEN_ITEM_PROCESS_COUNT; $i++) {
            $from .= "
                left join item_process_master as item_process_master{$i} on item_master.item_id = item_process_master{$i}.item_id and item_process_master{$i}.machining_sequence = {$i}
                left join process_master as process_master{$i} on item_process_master{$i}.process_id = process_master{$i}.process_id
                left join customer_master as customer_master_process_subcontract{$i} on item_process_master{$i}.subcontract_partner_id = customer_master_process_subcontract{$i}.customer_id
            ";
        }
        return $from;
    }

    private function _getTableTagInfo($tables)
    {
        global $gen_db;

        $allArr = array();

        $keyCurrency = $gen_db->queryOneValue("select key_currency from company_master");

        $tableList = array();
        foreach($tables as $table) {
            $tableList[] = $table[0];
        }

        foreach($tables as $table) {
            $arr = array();
            $originalTableName = "";
            $tagPrefix = "";
            switch ($table[0]) {
                case "received_header":
                    $arr[] = array("●" . _g("受注ヘッダ") . $table[2]);
                    $arr[] = array("received_header.received_number", "受注_受注番号", _g("受注登録画面 [受注番号]"), "A10010001");
                    $arr[] = array("received_header.customer_received_number", "受注_客先注番", _g("受注登録画面 [客先注番]"), "C101");
                    $arr[] = array("case when received_header.guarantee_grade = 1 then '" . _g("予約") . "' else '" . _g("確定") . "' end", "受注_確定度", _g("受注登録画面 [確定度]"), "2014-01-01");
                    $arr[] = array("received_header.received_date", "受注_受注日", _g("受注登録画面 [受注日]"), "2014-01-01");
                    $arr[] = array("received_header.remarks_header", "受注_備考1", _g("受注登録画面 [受注備考1]"), _g("受注備考1"));
                    $arr[] = array("received_header.remarks_header_2", "受注_備考2", _g("受注登録画面 [受注備考2]"), _g("受注備考2"));
                    $arr[] = array("received_header.remarks_header_3", "受注_備考3", _g("受注登録画面 [受注備考3]"), _g("受注備考3"));
                    break;
                case "received_detail":
                    $arr[] = array("●" . _g("受注明細") . $table[2]);
                    $arr[] = array("received_detail.line_no", "受注_行番号", _g("受注登録画面 [行]"), "1");
                    $arr[] = array("received_detail.seiban", "受注_製番", _g("受注登録画面 [製番]"), "100");
                    $arr[] = array("received_detail.received_quantity", "受注_受注数", _g("受注登録画面 [受注数]"), 100);
                    $arr[] = array("case when received_detail.foreign_currency_id is null then received_detail.product_price else received_detail.foreign_currency_product_price end", "受注_単価", _g("受注登録画面 [受注単価]"), 1000);
                    $arr[] = array("received_detail.product_price", "受注_単価_基軸", _g("受注登録画面 [受注単価]。外貨ベースの場合は基軸通貨に換算した値"), 100);
                    $arr[] = array("case when received_detail.foreign_currency_id is null then received_detail.product_price * received_detail.received_quantity else received_detail.foreign_currency_product_price * received_detail.received_quantity end", "受注_金額", _g("受注登録画面 [受注数] × [受注単価]"), 100000);
                    $arr[] = array("received_detail.product_price * received_detail.received_quantity", "受注_金額_基軸", _g("受注登録画面 [受注数量] × [受注単価]。外貨ベースの場合は基軸通貨に換算した値"), 100000);
                    if (in_array("item_master", $tableList)) {
                        $arr[] = array("ceil(received_detail.received_quantity / item_master.quantity_per_carton)", "受注_箱数", _g("受注登録画面 [受注数量] ÷ 品目マスタ [入数]。小数点以下切り上げ"), 10);
                    }
                    $arr[] = array("case when received_detail.foreign_currency_id is null then received_detail.sales_base_cost else received_detail.foreign_currency_sales_base_cost end", "受注_販売原単価", _g("受注登録画面 [販売原単価]"), 500);
                    $arr[] = array("received_detail.sales_base_cost", "受注_販売原単価_基軸", _g("受注登録画面 [販売原単価]。外貨ベースの場合は基軸通貨に換算した値"), 500);
                    $arr[] = array("received_detail.foreign_currency_rate", "受注_適用レート", _g("受注の適用レート"), 1.0);
                    $arr[] = array("received_detail.dead_line", "受注_納期", _g("受注登録画面 [受注納期]"), "2014-01-01");
                    $arr[] = array("received_detail.remarks", "受注_明細備考1", _g("受注登録画面 [受注明細備考1]"), _g("受注明細備考1"));
                    $arr[] = array("received_detail.remarks_2", "受注_明細備考2", _g("受注登録画面 [受注明細備考2]"), _g("受注明細備考2"));
                    $arr[] = array("case when received_detail.received_printed_flag then 1 else 0 end", "受注_出荷指示書印刷フラグ", _g("出荷指示書 印刷済なら1、未印刷なら0"), "0");
                    $arr[] = array("case when received_detail.customer_received_printed_flag then 1 else 0 end", "受注_発注書印刷フラグ", _g("発注書 印刷済なら1、未印刷なら0"), "0");
                    $arr[] = array("case when received_detail.delivery_completed then 1 else 0 end", "受注_完了フラグ", _g("受注 完了なら1、未完了なら0"), "0");
                    break;
                case "delivery_header":
                    $arr[] = array("●" . _g("納品ヘッダ") . $table[2]);
                    $arr[] = array("delivery_header.delivery_no", "納品_納品書番号", _g("納品登録画面 [納品書番号]"), "1000");
                    $arr[] = array("delivery_header.delivery_date", "納品_納品日", _g("納品登録画面 [納品日]"), "2014-01-01");
                    $arr[] = array("delivery_header.inspection_date", "納品_検収日", _g("納品登録画面 [検収日]"), "2014-01-02");
                    $arr[] = array("delivery_header.person_in_charge", "納品_自社担当者名", _g("納品登録画面 [担当者(自社)]"), _g("自社 太郎"));
                    $arr[] = array("delivery_header.remarks_header", "納品_備考1", _g("納品登録画面 [納品備考1]"), _g("納品書の備考1"));
                    $arr[] = array("delivery_header.remarks_header_2", "納品_備考2", _g("納品登録画面 [納品備考2]"), _g("納品書の備考2"));
                    $arr[] = array("delivery_header.remarks_header_3", "納品_備考3", _g("納品登録画面 [納品備考3]"), _g("納品書の備考3"));
                    break;
                case "delivery_detail":
                    $arr[] = array("●" . _g("納品明細") . $table[2]);
                    $arr[] = array("delivery_detail.line_no", "納品_行番号", _g("納品登録画面 [行番号]"), 1);
                    $arr[] = array("delivery_detail.delivery_quantity", "納品_数量", _g("納品登録画面 [今回納品数]"), "2000");
                    if (in_array("item_master", $tableList)) {
                        $arr[] = array("ceil(delivery_detail.delivery_quantity / item_master.quantity_per_carton)", "納品_箱数", _g("納品登録画面 [今回納品数] ÷ 品目マスタ [入数]。小数点以下切り上げ"), 10);
                    }
                    if (in_array("delivery_header", $tableList)) {
                        $arr[] = array("case when delivery_header.foreign_currency_id is null then delivery_detail.delivery_price else delivery_detail.foreign_currency_delivery_price end", "納品_単価", _g("納品登録画面 [納品単価]"), 2000);
                    }
                    $arr[] = array("delivery_detail.delivery_price", "納品_単価_基軸", _g("納品登録画面 [納品単価]。外貨の場合は基軸に換算した値"), 2000);
                    if (in_array("delivery_header", $tableList)) {
                        $arr[] = array("case when delivery_header.foreign_currency_id is null then delivery_detail.delivery_amount else delivery_detail.foreign_currency_delivery_amount end", "納品_金額", _g("納品登録画面 [今回納品数] × [納品単価]"), 200000);
                    }
                    $arr[] = array("delivery_detail.delivery_amount", "納品_金額_基軸", _g("納品登録画面 [今回納品数] × [納品単価]。外貨の場合は基軸に換算した値"), 200000);
                    $arr[] = array("case when delivery_detail.tax_class = 1 then '" . _g("非課税") . "' else '" . _g("課税") . "' end", "納品_課税区分", _g("納品登録画面 登録時点の品目マスタ [課税区分]"), _g("課税"));
                    if (in_array("customer_master_bill", $tableList) && in_array("delivery_header", $tableList)) { // left join customer_master as customer_master_bill on coalesce(customer_master.bill_customer_id, customer_master.customer_id) = customer_master_bill.customer_id
                        $arr[] = array("(case when delivery_header.tax_category = 2 then case when delivery_header.foreign_currency_id is null then delivery_tax else foreign_currency_delivery_tax end else null end)", "納品_消費税", _g("納品登録画面 [消費税額]（明細レベル。請求先の税計算単位が「納品明細単位」の場合のみ）"), 10000);
                        $arr[] = array("(case when delivery_header.tax_category = 2 then coalesce(case when delivery_header.foreign_currency_id is null then delivery_detail.delivery_amount + delivery_tax else delivery_detail.foreign_currency_delivery_amount + foreign_currency_delivery_tax end ,0) end)", "納品_税込金額", _g("納品登録画面 [金額] + [消費税額]"), 210000);
                    }
                    $arr[] = array("delivery_detail.use_lot_no", "納品_ロット番号", _g("納品登録画面 [ロット番号]"), "LOT-1");
                    $arr[] = array("delivery_detail.remarks", "納品_明細備考", _g("納品登録画面 [納品明細備考]"), _g("納品明細備考"));
                    break;
                case "estimate_header":
                    $arr[] = array("●" . _g("見積ヘッダ") . $table[2]);
                    $arr[] = array("estimate_header.estimate_number", "見積_見積番号", _g("見積登録画面 [見積番号]"), "M10010001");
                    $arr[] = array("estimate_header.customer_name", "見積_取引先名", _g("見積登録画面 [得意先名]"), _g("テスト取引先"));
                    $arr[] = array("estimate_header.estimate_date", "見積_発行日", _g("見積登録画面 [発行日]"), "2014-01-01");
                    $arr[] = array("estimate_header.subject", "見積_件名", _g("見積登録画面 [件名]"), _g("テスト見積件名"));
                    $arr[] = array("estimate_header.customer_zip", "見積_郵便番号", _g("見積登録画面 [郵便番号]"), "123-4567");
                    $arr[] = array("estimate_header.customer_address1", "見積_取引先住所1", _g("見積登録画面 [得意先住所1]"), _g("愛知県名古屋市"));
                    $arr[] = array("estimate_header.customer_address2", "見積_取引先住所2", _g("見積登録画面 [得意先住所2]"), _g("緑区1-1"));
                    $arr[] = array("estimate_header.customer_tel", "見積_取引先電話番号", _g("見積登録画面 [得意先TEL]"), "012-3456-7890");
                    $arr[] = array("estimate_header.customer_fax", "見積_取引先ファックス番号", _g("見積登録画面 [得意先FAX]"), "012-3456-7890");
                    $arr[] = array("estimate_header.person_in_charge", "見積_客先担当者名", _g("見積登録画面 [客先担当者名]"), _g("担当者 次郎"));
                    $arr[] = array("estimate_header.delivery_date", "見積_受渡期日", _g("見積登録画面 [受渡期日]"), _g("見積受渡期日"));
                    $arr[] = array("estimate_header.delivery_place", "見積_受渡場所", _g("見積登録画面 [受渡場所]"), _g("見積受渡場所"));
                    $arr[] = array("estimate_header.mode_of_dealing", "見積_お支払条件", _g("見積登録画面 [お支払条件]"), _g("見積お支払条件"));
                    $arr[] = array("estimate_header.expire_date", "見積_有効期限", _g("見積登録画面 [有効期限]"), _g("見積有効期限"));
                    $classQuery = Gen_Option::getEstimateRank('list-query');
                    $arr[] = array("case estimate_header.estimate_rank {$classQuery} end", "見積_ランク", _g("見積登録画面 [ランク]"), _g("見積ランク"));
                    $arr[] = array("estimate_header.remarks", "見積_備考", _g("見積登録画面 [見積備考]"), _g("見積備考"));
                    break;
                case "estimate_detail":
                    $arr[] = array("●" . _g("見積明細") . $table[2]);
                    $arr[] = array("estimate_detail.line_no", "見積_行番号", _g("見積登録画面 [行]"), 1);
                    $arr[] = array("estimate_detail.item_code", "見積_品目コード", _g("見積登録画面 [品目コード]"), "code001");
                    $arr[] = array("estimate_detail.item_name", "見積_品目名", _g("見積登録画面 [品目名]"), _g("テスト品目"));
                    $arr[] = array("estimate_detail.quantity", "見積_数量", _g("見積登録画面 [数量]"), "2000");
                    $arr[] = array("estimate_detail.measure", "見積_単位", _g("見積登録画面 [単位]"), "kg");
                    $arr[] = array("case when estimate_detail.foreign_currency_id is null then estimate_detail.sale_price else estimate_detail.foreign_currency_sale_price end", "見積_単価", _g("見積登録画面 [見積単価]"), 1000);
                    $arr[] = array("estimate_detail.sale_price", "見積_単価_基軸", _g("見積登録画面 [見積単価]。外貨ベースの場合は基軸通貨に換算した値"), 100);
                    $arr[] = array("case when estimate_detail.foreign_currency_id is null then estimate_detail.estimate_amount else estimate_detail.foreign_currency_estimate_amount end", "見積_金額", _g("見積登録画面 [見積数量] × [見積単価]"), 100000);
                    $arr[] = array("estimate_detail.estimate_amount", "見積_金額_基軸", _g("見積登録画面 [見積数量] × [見積単価]。外貨ベースの場合は基軸通貨に換算した値"), 100000);
                    $arr[] = array("case when estimate_detail.foreign_currency_id is null then estimate_detail.base_cost else estimate_detail.foreign_currency_base_cost end", "見積_原単価", _g("見積登録画面 [販売原単価]"), 500);
                    $arr[] = array("estimate_detail.base_cost", "見積_原単価_基軸", _g("見積登録画面 [販売原単価]。外貨ベースの場合は基軸通貨に換算した値"), 500);
                    $arr[] = array("case when estimate_detail.foreign_currency_id is null then estimate_detail.base_cost_total else estimate_detail.foreign_currency_base_cost_total end", "見積_原価", _g("見積登録画面 [数量] × [販売原単価]"), 500);
                    $arr[] = array("estimate_detail.base_cost_total", "見積_原価_基軸", _g("見積登録画面 [見積数量] × [販売原単価]。外貨ベースの場合は基軸通貨に換算した値"), 500);
                    $arr[] = array("estimate_detail.foreign_currency_rate", "見積_適用レート", _g("見積の適用レート"), 1.0);
                    $arr[] = array("case when estimate_detail.tax_class = 1 then '" . _g("非課税") . "' else '" . _g("課税") . "' end", "見積_課税区分", _g("登録時点の品目マスタ [課税区分]"), _g("課税"));
                    $arr[] = array("case when estimate_detail.foreign_currency_id is null then estimate_detail.estimate_tax else estimate_detail.foreign_currency_estimate_tax end", "見積_消費税額", _g("金額 × 登録時点の税率。非課税品目は除く。"), "100");
                    $arr[] = array("estimate_detail.estimate_tax", "見積_消費税額_基軸", _g("金額 × 登録時点の税率。非課税品目は除く。外貨ベースの場合は基軸通貨に換算した値"), "10000");
                    $arr[] = array("estimate_detail.remarks", "見積_明細備考", _g("見積登録画面 [見積明細備考1]"), _g("見積明細備考1"));
                    $arr[] = array("estimate_detail.remarks_2", "見積_明細備考2", _g("見積登録画面 [見積明細備考2]"), _g("見積明細備考2")); // 15i
                    break;
                case "order_header_manufacturing":
                    $arr[] = array("●" . _g("製造指示") . $table[2]);
                    $arr[] = array("order_header_manufacturing.order_date", "製造指示_製造開始日", _g("製造指示登録画面 [製造開始日]"), "2014-01-01");
                    $arr[] = array("order_header_manufacturing.remarks_header", "製造指示_備考", _g("製造指示登録画面 [製造指示備考]"), _g("製造指示登録の備考"));
                    // 本来のテーブル名と名前が異なるとき（テーブル名にエイリアスをつけているとき）は次の2行が必要
                    $originalTableName = "order_header";
                    $tagPrefix = "製造指示";
                    break;
                case "order_detail_manufacturing":
                    $arr[] = array("●" . _g("製造指示") . $table[2]);
                    $arr[] = array("order_detail_manufacturing.order_no", "製造指示_オーダー番号", _g("製造指示登録画面 [オーダー番号]"), "1000");
                    $arr[] = array("order_detail_manufacturing.order_detail_dead_line", "製造指示_納期", _g("製造指示登録画面 [製造納期]"), "2014-01-05");
                    $arr[] = array("order_detail_manufacturing.item_code", "製造指示_品目コード", _g("製造指示登録画面 [品目]"), "code001");
                    $arr[] = array("order_detail_manufacturing.item_name", "製造指示_品目名", _g("製造指示登録画面 [品目]"), ("テスト品目"));
                    $arr[] = array("order_detail_manufacturing.order_detail_quantity", "製造指示_数量", _g("製造指示登録画面 [数量]"), "2000");
                    if (in_array("item_master", $tableList)) {
                        $arr[] = array("ceil(order_detail_manufacturing.order_detail_quantity / item_master.quantity_per_carton)", "製造指示_箱数", _g("製造指示登録画面 [数量] ÷ 品目マスタ [入数]。小数点以下切り上げ"), 10);
                    }
                    $arr[] = array("coalesce(order_detail_manufacturing.order_detail_quantity,0) - coalesce(order_detail_manufacturing.accepted_quantity,0)", "製造指示_製造残数", _g("製造指示登録画面 [数量] - 実績登録済数"), "1000");
                    $arr[] = array("order_detail_manufacturing.seiban", "製造指示_製番", _g("製造指示登録画面 [製番]（製番品目のみ）"), "100");
                    // 本来のテーブル名と名前が異なるとき（テーブル名にエイリアスをつけているとき）は次の2行が必要
                    $originalTableName = "order_detail";
                    $tagPrefix = "製造指示";
                    break;
                case "order_header_partner":
                    $arr[] = array("●" . _g("注文ヘッダ") . $table[2]);
                    $arr[] = array("order_header_partner.order_id_for_user", "注文_注文書番号", _g("注文書登録画面 [注文書番号]"), "100");
                    $arr[] = array("order_header_partner.order_date", "注文_発注日", _g("注文書登録画面 [発注日]"), "2014-01-01");
                    $arr[] = array("order_header_partner.remarks_header", "注文_備考", _g("注文登録画面 [注文備考]"), _g("注文書の備考"));
                    // 本来のテーブル名と名前が異なるとき（テーブル名にエイリアスをつけているとき）は次の2行が必要
                    $originalTableName = "order_header";
                    $tagPrefix = "注文";
                    break;
                case "order_detail_partner":
                    $arr[] = array("●" . _g("注文 明細") . $table[2]);
                    $arr[] = array("order_detail_partner.line_no", "注文_行番号", _g("注文書登録画面 [行]"), 1);
                    $arr[] = array("order_detail_partner.order_no", "注文_オーダー番号", _g("注文書登録画面 [オーダー番号]"), "1000");
                    $arr[] = array("order_detail_partner.item_code", "注文_品目コード", _g("注文書登録画面 [品目]"), "code001");
                    $arr[] = array("order_detail_partner.item_name", "注文_品目名", _g("注文書登録画面 [品目]"), ("テスト品目"));
                    $arr[] = array("order_detail_partner.order_detail_quantity / coalesce(order_detail_partner.multiple_of_order_measure,1)", "注文_数量", _g("注文書登録画面 [数量]"), "2000");
                    $arr[] = array("order_detail_partner.order_detail_quantity", "注文_管理単位数量", _g("注文書登録画面 [数量]。管理単位での数量"), "2000");
                    if (in_array("item_master", $tableList)) {
                        $arr[] = array("ceil(order_detail_partner.order_detail_quantity / coalesce(order_detail_partner.multiple_of_order_measure,1) / item_master.quantity_per_carton)", "注文_箱数", _g("注文書登録画面 [表示数量] ÷ 品目マスタ [入数]"), 10);
                    }
                    $arr[] = array("order_detail_partner.multiple_of_order_measure", "注文_手配単位倍数", _g("注文書登録画面 [倍数]"), "1000");
                    $arr[] = array("order_detail_partner.order_measure", "注文_単位", _g("注文書登録画面 [手配単位]"), "kg");
                    $arr[] = array("(case when order_detail_partner.foreign_currency_id is null then order_detail_partner.item_price else order_detail_partner.foreign_currency_item_price end) * coalesce(order_detail_partner.multiple_of_order_measure,1)", "注文_単価", _g("注文登録画面 [発注単価]"), 1000);
                    $arr[] = array("order_detail_partner.item_price", "注文_単価_基軸", _g("注文書登録画面 [発注単価]。外貨ベースの場合は基軸通貨に換算した値"), "10");
                    $arr[] = array("case when order_detail_partner.foreign_currency_id is null then order_detail_partner.order_amount else order_detail_partner.foreign_currency_order_amount end", "注文_金額", _g("注文書登録画面 [発注単価] × [数量]。取引先マスタ [端数処理]に従い整数丸め"), "200000");
                    $arr[] = array("order_detail_partner.order_amount", "注文_金額_基軸", _g("注文書登録画面 [発注単価] × [数量]。取引先マスタ [端数処理]に従い整数丸め。外貨ベースの場合は基軸通貨に換算した値"), "200000");
                    $arr[] = array("order_detail_partner.foreign_currency_rate", "注文_適用レート", _g("注文の適用レート"), 1.0);
                    $arr[] = array("case when order_detail_partner.foreign_currency_id is null then order_detail_partner.order_tax else 0 end", "注文_消費税", _g("注文登録画面 消費税。基軸通貨のみ。取引先マスタ [端数処理]に従い整数丸め"), "10000");
                    $arr[] = array("case when order_detail_partner.tax_class = 1 then '" . _g("非課税") . "' else '" . _g("課税") . "' end", "注文_課税区分", _g("注文登録画面 登録時点の品目マスタ [課税区分]"), _g("課税"));
                    $arr[] = array("case when order_detail_partner.foreign_currency_id is null then order_detail_partner.order_amount else order_detail_partner.foreign_currency_order_amount end + case when order_detail_partner.foreign_currency_id is null then order_detail_partner.order_tax else 0 end", "注文_税込金額", _g("注文書登録画面 金額 + 消費税"), "210000");
                    $arr[] = array("order_detail_partner.order_detail_dead_line", "注文_納期", _g("注文書登録画面 [注文納期]"), "2014-02-01");
                    $arr[] = array("order_detail_partner.accepted_quantity", "注文_受入数", _g("注文書登録画面 [受入状況]"), 0);
                    $arr[] = array("case when order_detail_completed then 0 else (coalesce(order_detail_partner.order_detail_quantity,0) - coalesce(order_detail_partner.accepted_quantity,0)) / coalesce(order_detail_partner.multiple_of_order_measure,1) end", "注文_残数", _g("注文書登録画面 [数量] - 受入登録済数"), "1000");
                    $arr[] = array("case when order_detail_partner.order_detail_completed then 1 else 0 end", "注文_完了フラグ", _g("注文 完了なら1、未完了なら0"), "0");
                    $arr[] = array("order_detail_partner.remarks", "注文_明細備考", _g("注文書登録画面 [注文明細備考]"), _g("注文明細備考"));
                    $arr[] = array("order_detail_partner.seiban", "注文_製番", _g("注文書登録画面 [製番]（製番品目のみ）"), "100");
                    $arr[] = array("order_detail_partner.item_sub_code", "注文_メーカー型番", _g("注文書登録画面 登録時の品目マスタ [メーカー型番]"), "no100");
                    // 本来のテーブル名と名前が異なるとき（テーブル名にエイリアスをつけているとき）は次の2行が必要
                    $originalTableName = "order_detail";
                    $tagPrefix = "注文";
                    break;
                case "accepted":    // 注文受入/外製受入 兼用
                    $arr[] = array("●" . _g("受入") . $table[2]);
                    $arr[] = array("accepted.order_no", "受入_オーダー番号", _g("受入登録画面 [オーダー番号]"), "100");
                    $arr[] = array("accepted.accepted_date", "受入_受入日", _g("受入登録画面 [受入日]"), "2014-01-01");
                    $arr[] = array("accepted.inspection_date", "受入_検収日", _g("受入登録画面 [検収日]"), "2014-01-02");
                    $arr[] = array("accepted.payment_date", "受入_支払予定日", _g("受入登録画面 [支払予定日]"), "2014-02-01");
                    $arr[] = array("accepted.lot_no", "受入_ロット番号", _g("受入登録画面 [ロット番号]"), "lot1");
                    $arr[] = array("accepted.use_by", "受入_消費期限", _g("受入登録画面 [消費期限]"), "2014-03-01");
                    $arr[] = array("accepted.accepted_quantity", "受入_受入数", _g("受入登録画面 [受入数]"), "100");
                    if (in_array("order_detail_partner", $tableList)) {
                        $arr[] = array("case when order_detail_partner.foreign_currency_id is null then accepted.accepted_price else accepted.foreign_currency_accepted_price end", "受入_単価", _g("注文受入登録画面 [受入単価]"), 1000);
                    }
                    $arr[] = array("accepted.accepted_price", "受入_単価_基軸", _g("受入登録画面 [受入単価]。外貨ベースの場合は基軸通貨に換算した値"), 100);
                    if (in_array("order_detail_partner", $tableList)) {
                        $arr[] = array("case when order_detail_partner.foreign_currency_id is null then accepted.accepted_amount else accepted.foreign_currency_accepted_amount end", "受入_金額", _g("注文受入登録画面 [受入数] × [受入単価]"), 100000);
                    }
                    $arr[] = array("accepted.accepted_amount", "受入_金額_基軸", _g("受入登録画面 [受入数] × [受入単価]。外貨ベースの場合は基軸通貨に換算した値"), 100000);
                    $arr[] = array("accepted.foreign_currency_rate", "受入_レート", _g("受入登録画面 [レート]"), 1.0);
                    $arr[] = array("case when accepted.tax_class = 1 then '" . _g("非課税") . "' else '" . _g("課税") . "' end", "受入_課税区分", _g("登録時点の品目マスタ [課税区分]"), _g("課税"));
                    $arr[] = array("accepted.accepted_tax", "受入_消費税額", _g("金額 × 登録時点の税率。非課税品目は除く"), "10000");
                    $arr[] = array("accepted.remarks", "受入_備考", _g("受入登録画面 [受入備考]"), _g("注文受入の備考"));
                    $arr[] = array("accepted.order_seiban", "受入_製番_オーダー", _g("受入登録画面 [製番（オーダー）]"), "seiban1");
                    $arr[] = array("accepted.stock_seiban", "受入_製番_在庫", _g("受入登録画面 [製番（在庫）]"), "seiban2");
                    break;
                case "order_header_subcontract":
                    $arr[] = array("●" . _g("外製指示ヘッダ") . $table[2]);
                    $arr[] = array("order_header_subcontract.order_date", "外製指示_発行日", _g("外製指示登録画面 [発行日]"), "2014-01-01");
                    $arr[] = array("order_header_subcontract.remarks_header", "外製指示_備考", _g("外製指示登録画面 [外製指示備考]"), _g("外製指示の備考"));
                    // 本来のテーブル名と名前が異なるとき（テーブル名にエイリアスをつけているとき）は次の2行が必要
                    $originalTableName = "order_header";
                    $tagPrefix = "外製";
                    break;
                case "order_detail_subcontract":
                    $arr[] = array("●" . _g("外製指示 明細") . $table[2]);
                    $arr[] = array("order_detail_subcontract.order_no", "外製指示_オーダー番号", _g("外製指示登録画面 [オーダー番号]"), "1000");
                    $arr[] = array("order_detail_subcontract.item_code", "外製指示_品目コード", _g("外製指示登録画面 [品目]"), "code001");
                    $arr[] = array("order_detail_subcontract.item_name", "外製指示_品目名", _g("外製指示登録画面 [品目]"), ("テスト品目"));
                    $arr[] = array("order_detail_subcontract.order_detail_quantity / coalesce(order_detail_subcontract.multiple_of_order_measure,1)", "外製指示_数量", _g("外製指示登録画面 [数量]"), "2000");
                    $arr[] = array("order_detail_subcontract.order_detail_quantity", "外製指示_管理単位数量", _g("外製指示登録画面 [数量]。管理単位での数量"), "2000");
                    if (in_array("item_master", $tableList)) {
                        $arr[] = array("ceil(order_detail_subcontract.order_detail_quantity / coalesce(order_detail_subcontract.multiple_of_order_measure,1) / item_master.quantity_per_carton)", "外製指示_箱数", _g("外製指示登録画面 [表示数量] ÷ 品目マスタ [入数]"), 10);
                    }
                    $arr[] = array("order_detail_subcontract.multiple_of_order_measure", "外製指示_手配単位倍数", _g("外製指示登録画面 [手配単位倍数]"), "1000");
                    $arr[] = array("order_detail_subcontract.order_measure", "外製指示_単位", _g("外製指示登録画面 [発注単位]"), "kg");
                    $arr[] = array("(case when order_detail_subcontract.foreign_currency_id is null then order_detail_subcontract.item_price else order_detail_subcontract.foreign_currency_item_price end) * coalesce(order_detail_subcontract.multiple_of_order_measure,1)", "外製指示_単価", _g("外製指示登録画面 [発注単価]"), 1000);
                    $arr[] = array("order_detail_subcontract.item_price", "外製指示_単価_基軸", _g("外製指示登録画面 [発注単価]。外貨ベースの場合は基軸通貨に換算した値"), "10");
                    $arr[] = array("case when order_detail_subcontract.foreign_currency_id is null then order_detail_subcontract.order_amount else order_detail_subcontract.foreign_currency_order_amount end", "外製指示_金額", _g("外製指示登録画面 [発注単価] × [数量]。取引先マスタ [端数処理]に従い整数丸め"), "200000");
                    $arr[] = array("order_detail_subcontract.order_amount", "外製指示_金額_基軸", _g("外製指示登録画面 [発注単価] × [数量]。取引先マスタ [端数処理]に従い整数丸め。外貨ベースの場合は基軸通貨に換算した値"), "200000");
                    $arr[] = array("order_detail_subcontract.foreign_currency_rate", "外製指示_適用レート", _g("注文の適用レート"), 1.0);
                    $arr[] = array("case when order_detail_subcontract.foreign_currency_id is null then order_detail_subcontract.order_tax else 0 end", "外製指示_消費税", _g("外製指示登録画面で内部的に計算される消費税。基軸通貨のみ。取引先マスタ [端数処理]に従い整数丸め"), "10000");
                    $arr[] = array("case when order_detail_subcontract.tax_class = 1 then '" . _g("非課税") . "' else '" . _g("課税") . "' end", "外製指示_課税区分", _g("外製指示登録画面 登録時点の品目マスタ [課税区分]"), _g("課税"));
                    $arr[] = array("case when order_detail_subcontract.foreign_currency_id is null then order_detail_subcontract.order_amount else order_detail_subcontract.foreign_currency_order_amount end + case when order_detail_subcontract.foreign_currency_id is null then order_detail_subcontract.order_tax else 0 end", "外製指示_税込金額", _g("外製指示登録画面 金額 + 消費税"), "210000");
                    $arr[] = array("order_detail_subcontract.order_detail_dead_line", "外製指示_納期", _g("外製指示登録画面 [外注納期]"), "2014-02-01");
                    $arr[] = array("order_detail_subcontract.accepted_quantity", "外製指示_受入数", _g("外製指示登録画面 [受入状況]"), 0);
                    $arr[] = array("case when order_detail_completed then 0 else (coalesce(order_detail_subcontract.order_detail_quantity,0) - coalesce(order_detail_subcontract.accepted_quantity,0)) / coalesce(order_detail_subcontract.multiple_of_order_measure,1) end", "外製指示_残数", _g("外製指示登録画面 [数量] - 受入登録済数"), "1000");
                    $arr[] = array("case when order_detail_subcontract.order_detail_completed then 1 else 0 end", "外製指示_完了フラグ", _g("外製指示 完了なら1、未完了なら0"), "0");
                    $arr[] = array("order_detail_subcontract.seiban", "外製指示_製番", _g("外製指示登録画面 [製番]（製番品目のみ）"), "100");
                    $arr[] = array("order_detail_subcontract.item_sub_code", "外製指示_メーカー型番", _g("外製指示登録画面 登録時の品目マスタ [メーカー型番]"), "no100");
                    $arr[] = array("order_detail_subcontract.subcontract_parent_order_no", "外製指示_親オーダー番号", _g("外製指示登録画面 [親オーダー番号]"), "p1");
                    $arr[] = array("order_detail_subcontract.subcontract_process_name", "外製指示_工程名", _g("外製指示登録画面 [工程名]"), _g("外製工程1"));
                    $arr[] = array("order_detail_subcontract.subcontract_process_remarks_1", "外製指示_工程メモ1", _g("外製指示登録画面 [工程メモ1]"), _g("外製工程メモ1"));
                    $arr[] = array("order_detail_subcontract.subcontract_process_remarks_2", "外製指示_工程メモ2", _g("外製指示登録画面 [工程メモ2]"), _g("外製工程メモ2"));
                    $arr[] = array("order_detail_subcontract.subcontract_process_remarks_3", "外製指示_工程メモ3", _g("外製指示登録画面 [工程メモ3]"), _g("外製工程メモ3"));
                    $arr[] = array("order_detail_subcontract.subcontract_ship_to", "外製指示_発送先", _g("外製指示登録画面 [発送先]"), _g("外製先株式会社"));
                    // 本来のテーブル名と名前が異なるとき（テーブル名にエイリアスをつけているとき）は次の2行が必要
                    $originalTableName = "order_detail";
                    $tagPrefix = "外製";
                    break;
                case "location_move":
                    $arr[] = array("●" . _g("ロケーション間移動") . $table[2]);
                    $arr[] = array("location_move.move_date", "移動_移動日", _g("ロケーション間移動画面 [移動日]"), "2014-01-01");
                    $arr[] = array("case when t_loc1.source_location_code is null then '0' else t_loc1.source_location_code end", "移動_移動元ロケーションコード", _g("ロケーション間移動画面 [移動元ロケーション]"), "code002");
                    $arr[] = array("case when t_loc2.dist_location_code is null then '0' else t_loc2.dist_location_code end", "移動_移動先ロケーションコード", _g("ロケーション間移動画面 [移動先ロケーション]"), "code003");
                    $arr[] = array("case when t_loc1.source_location_name is null then '" . _g(GEN_DEFAULT_LOCATION_NAME) . "' else t_loc1.source_location_name end", "移動_移動元ロケーション名", _g("ロケーション間移動画面 [移動元ロケーション]"), "テスト移動元ロケーション");
                    $arr[] = array("case when t_loc2.dist_location_name is null then '" . _g(GEN_DEFAULT_LOCATION_NAME) . "' else t_loc2.dist_location_name end", "移動_移動先ロケーション名", _g("ロケーション間移動画面 [移動先ロケーション]"), "テスト移動先ロケーション");
                    $arr[] = array("location_move.quantity", "移動_数量", _g("ロケーション間移動画面 [数量]"), "2000");
                    $arr[] = array("location_move.seiban", "移動_製番", _g("ロケーション間移動画面 [製番]（製番品目のみ）"), "100");
                    $arr[] = array("location_move.remarks", "移動_備考", _g("ロケーション間移動画面 [備考]"), _g("在庫移動表の備考"));
                    break;

                case "item_master":
                    // ■品目グループ・標準ロケ・標準手配先関連のタグを追加するには
                    //  　SQL の FROM句（item_masterより後）に以下を追加
                    //          " . self::getFromItemMasterChildren() . "
                    //  　$info['tables'] = array(... に以下を追加
                    //          array("item_master_childern", true, ""),
                    // ■工程関連のタグを追加するには
                    //  　SQL の FROM句（item_masterより後）に以下を追加
                    //          " . self::getFromItemMasterProcess() . "
                    //  　$info['tables'] = array(... に以下を追加
                    //          array("item_master_process", true, ""),
                    //  　※工程数×2のjoinが追加されるため、工程数が多い場合はパフォーマンスに影響あり
                    $arr[] = array("●" . _g("品目マスタ") . $table[2]);
                    $arr[] = array("item_master.item_code", "品目マスタ_品目コード", _g("品目マスタ [品目コード]"), "code001");
                    $arr[] = array("item_master.item_name", "品目マスタ_品目名", _g("品目マスタ [品目名]"), _g("テスト品目"));
                    $arr[] = array("case item_master.order_class when 0 then '" . _g("製番") . "' when 2 then '" . _g("ロット") . "' else '" . _g("MRP") . "' end", "品目マスタ_管理区分", _g("品目マスタ [管理区分]"), _g("製番"));
                    $arr[] = array("case when item_master.end_item then '" . _g("非表示") . "' else '' end", "品目マスタ_非表示", _g("品目マスタ [非表示] がオンなら「非表示」"), _g("非表示"));
                    if (in_array("item_master_children", $tableList)) {
                        $arr[] = array("item_group_master1.item_group_code", "品目マスタ_品目グループコード1", _g("品目マスタ [品目グループ1]"), "IG1");
                        $arr[] = array("item_group_master1.item_group_name", "品目マスタ_品目グループ名1", _g("品目マスタ [品目グループ1]"), _g("品目グループ名1"));
                        $arr[] = array("item_group_master2.item_group_code", "品目マスタ_品目グループコード2", _g("品目マスタ [品目グループ2]"), "IG2");
                        $arr[] = array("item_group_master2.item_group_name", "品目マスタ_品目グループ名2", _g("品目マスタ [品目グループ2]"), _g("品目グループ名2"));
                        $arr[] = array("item_group_master3.item_group_code", "品目マスタ_品目グループコード3", _g("品目マスタ [品目グループ3]"), "IG3");
                        $arr[] = array("item_group_master3.item_group_name", "品目マスタ_品目グループ名3", _g("品目マスタ [品目グループ3]"), _g("品目グループ名3"));
                    }
                    $arr[] = array("item_master.default_selling_price", "品目マスタ_標準販売単価1", _g("品目マスタ [標準販売単価1]"), 100);
                    $arr[] = array("item_master.selling_price_limit_qty_1", "品目マスタ_標準販売単価1適用数", _g("品目マスタ [標準販売単価1適用数]"), 10);
                    $arr[] = array("item_master.default_selling_price_2", "品目マスタ_標準販売単価2", _g("品目マスタ [標準販売単価2]"), 200);
                    $arr[] = array("item_master.selling_price_limit_qty_2", "品目マスタ_標準販売単価2適用数", _g("品目マスタ [標準販売単価2適用数]"), 20);
                    $arr[] = array("item_master.default_selling_price_3", "品目マスタ_標準販売単価3", _g("品目マスタ [標準販売単価3]"), 300);
                    $arr[] = array("item_master.stock_price", "品目マスタ_在庫評価単価", _g("品目マスタ [在庫評価単価]"), 100);
                    $arr[] = array("item_master.payout_price", "品目マスタ_支給単価", _g("品目マスタ [支給単価]"), 100);
                    $arr[] = array("case when item_master.tax_class = 1 then '" . _g("非課税") . "' else '" . _g("課税") . "' end", "品目マスタ_課税区分", _g("品目マスタ [課税区分]"), _g("課税"));
                    $arr[] = array("item_master.measure", "品目マスタ_管理単位", _g("品目マスタ [管理単位]"), _g("個"));
                    $arr[] = array("case when item_master.received_object = 1 then '" . _g("非対象") . "' else '" . _g("受注対象") . "' end", "品目マスタ_受注対象", _g("品目マスタ [受注対象]"), _g("受注対象"));
                    $arr[] = array("item_master.maker_name", "品目マスタ_メーカー", _g("品目マスタ [メーカー]"), _g("テストメーカー"));
                    $arr[] = array("item_master.spec", "品目マスタ_仕様", _g("品目マスタ [仕様]"), _g("テスト仕様"));
                    $arr[] = array("item_master.rack_no", "品目マスタ_棚番", _g("品目マスタ [棚番]"), _g("テスト棚番"));
                    $arr[] = array("item_master.quantity_per_carton", "品目マスタ_入数", _g("品目マスタ [入数]"), "100");
                    if (in_array("item_master_children", $tableList)) {
                        $arr[] = array("location_master_default_accepted.location_code", "品目マスタ_標準ロケコード_受入", _g("品目マスタ [標準ロケーション（受入）]"), "DL1");
                        $arr[] = array("location_master_default_accepted.location_name", "品目マスタ_標準ロケ名_受入", _g("品目マスタ [標準ロケーション（受入）]"), _g("標準ロケ（受入）"));
                        $arr[] = array("location_master_default_use.location_code", "品目マスタ_標準ロケコード_使用", _g("品目マスタ [標準ロケーション（使用）]"), "DL2");
                        $arr[] = array("location_master_default_use.location_name", "品目マスタ_標準ロケ名_使用", _g("品目マスタ [標準ロケーション（使用）]"), _g("標準ロケ（使用）"));
                        $arr[] = array("location_master_default_finish.location_code", "品目マスタ_標準ロケコード_完成", _g("品目マスタ [標準ロケーション（完成）]"), "DL3");
                        $arr[] = array("location_master_default_finish.location_name", "品目マスタ_標準ロケ名_完成", _g("品目マスタ [標準ロケーション（完成）]"), _g("標準ロケ（完成）"));
                    }
                    $arr[] = array("case when item_master.dummy_item then '" . _g("ダミー品目") . "' else '" . _g("通常品目") . "' end", "品目マスタ_ダミー品目", _g("品目マスタ [ダミー品目] がオンなら「ダミー品目」"), _g("通常品目"));
                    $arr[] = array("item_master.use_by_days", "品目マスタ_消費期限日数", _g("品目マスタ [消費期限日数]"), 1);
                    $arr[] = array("item_master.lot_header", "品目マスタ_ロット頭文字", _g("品目マスタ [ロット頭文字]"), "lot");
                    $arr[] = array("item_master.comment", "品目マスタ_備考1", _g("品目マスタ [品目備考1]"), _g("品目備考1"));
                    $arr[] = array("item_master.comment_2", "品目マスタ_備考2", _g("品目マスタ [品目備考2]"), _g("品目備考2"));
                    $arr[] = array("item_master.comment_3", "品目マスタ_備考3", _g("品目マスタ [品目備考3]"), _g("品目備考3"));
                    $arr[] = array("item_master.comment_4", "品目マスタ_備考4", _g("品目マスタ [品目備考4]"), _g("品目備考4"));
                    $arr[] = array("item_master.comment_5", "品目マスタ_備考5", _g("品目マスタ [品目備考5]"), _g("品目備考5"));
                    $arr[] = array("case when item_master.without_mrp = 1 then '" . _g("所要量計算から除外") . "' else '" . _g("所要量計算に含める") . "' end", "品目マスタ_所要量計算に含める", _g("品目マスタ [所要量計算に含める] がオンなら「所要量計算に含める」"), _g("所要量計算に含める"));
                    $arr[] = array("item_master.safety_stock", "品目マスタ_安全在庫数", _g("品目マスタ [安全在庫数]"), 100);
                    $arr[] = array("item_master.lead_time", "品目マスタ_リードタイム", _g("品目マスタ [リードタイム]"), 3);
                    $arr[] = array("item_master.safety_lead_time", "品目マスタ_安全リードタイム", _g("品目マスタ [安全リードタイム]"), 4);
                    if (in_array("item_master_children", $tableList)) {
                        $arr[] = array("item_order_master0.default_lot_unit", "品目マスタ_最低ロット数", _g("品目マスタ [最低ロット数]"), 0);
                        $arr[] = array("item_order_master0.default_lot_unit_2", "品目マスタ_手配ロット数", _g("品目マスタ [手配ロット数]"), 100);
                        $classArray = Gen_Option::getPartnerClass('options');
                        $classQuery = Gen_Option::getCaseConstruction($classArray);
                        $arr[] = array("case item_order_master0.partner_class {$classQuery} end", "品目マスタ_標準手配先_手配区分", _g("品目マスタ [手配区分]"), _g("製番"));
                        $arr[] = array("customer_master_item_order0.customer_no", "品目マスタ_標準手配先_手配先コード1", _g("品目マスタ [標準手配先]"), "DO1");
                        $arr[] = array("customer_master_item_order0.customer_name", "品目マスタ_標準手配先_手配先名1", _g("品目マスタ [標準手配先]"), _g("標準手配先"));
                        $arr[] = array("item_order_master0.item_sub_code", "品目マスタ_標準手配先_メーカー型番", _g("品目マスタ 標準手配先[メーカー型番]"), "IG1");
                        $arr[] = array("item_order_master0.order_measure", "品目マスタ_標準手配先_手配単位", _g("品目マスタ 標準手配先[手配単位]"), "kg");
                        $arr[] = array("item_order_master0.multiple_of_order_measure", "品目マスタ_標準手配先_手配単位倍数", _g("品目マスタ 標準手配先[手配単位倍数]"), 1000);
                        $arr[] = array("item_order_master0.default_order_price", "品目マスタ_標準手配先_購入単価1", _g("品目マスタ 標準手配先[購入単価1]"), 1000);
                        $arr[] = array("item_order_master0.order_price_limit_qty_1", "品目マスタ_標準手配先_購入単価1適用数", _g("品目マスタ 標準手配先[購入単価1適用数]"), 100);
                        $arr[] = array("item_order_master0.default_order_price_2", "品目マスタ_標準手配先_購入単価2", _g("品目マスタ 標準手配先[購入単価2]"), 900);
                        $arr[] = array("item_order_master0.order_price_limit_qty_2", "品目マスタ_標準手配先_購入単価2適用数", _g("品目マスタ 標準手配先[購入単価2適用数]"), 200);
                        $arr[] = array("item_order_master0.default_order_price_3", "品目マスタ_標準手配先_購入単価3", _g("品目マスタ 標準手配先[購入単価3]"), 800);
                    }
                    if (in_array("item_master_process", $tableList)) {
                        for ($i = 0; $i < GEN_ITEM_PROCESS_COUNT; $i++) {
                            $no = $i + 1;
                            $arr[] = array("process_master{$i}.process_code", "品目マスタ_工程{$no}_工程コード", sprintf(_g("品目マスタ [工程%s]"), $no), "process{$no}");
                            $arr[] = array("process_master{$i}.process_name", "品目マスタ_工程{$no}_工程名", sprintf(_g("品目マスタ [工程%s]"), $no), sprintf(_g("工程%s"), $no));
                            $arr[] = array("item_process_master{$i}.default_work_minute", "品目マスタ_工程{$no}_標準加工時間", sprintf(_g("品目マスタ 工程%s"), $no) . " [" . _g("標準加工時間(分)") . "]", 60);
                            $arr[] = array("item_process_master{$i}.pcs_per_day", "品目マスタ_工程{$no}_製造能力", sprintf(_g("品目マスタ 工程%s"), $no) . " [" . _g("製造能力(1日あたり)") . "]", 100);
                            $arr[] = array("item_process_master{$i}.charge_price", "品目マスタ_工程{$no}_工賃", sprintf(_g("品目マスタ 工程%s"), $no) . " [" . _g("工賃(1分あたり)") . "]", 100);
                            $arr[] = array("customer_master_process_subcontract{$i}.customer_no", "品目マスタ_工程{$no}_外製先コード", sprintf(_g("品目マスタ 工程%s"), $no) . " [" . _g("外製先") . "]", "sub{$i}");
                            $arr[] = array("customer_master_process_subcontract{$i}.customer_name", "品目マスタ_工程{$no}_外製先名", sprintf(_g("品目マスタ 工程%s"), $no) . " [" . _g("外製先") . "]", sprintf(_g("外製先名%s"), $no));
                            $arr[] = array("item_process_master{$i}.subcontract_unit_price", "品目マスタ_工程{$no}_外製単価", sprintf(_g("品目マスタ 工程%s"), $no) . " [" . _g("外製単価") . "]", 1000);
                            $arr[] = array("item_process_master{$i}.process_lt", "品目マスタ_工程{$no}_工程リードタイム", sprintf(_g("品目マスタ 工程%s"), $no) . " [" . _g("工程リードタイム") . "]", 5);
                            $arr[] = array("item_process_master{$i}.overhead_cost", "品目マスタ_工程{$no}_固定経費", sprintf(_g("品目マスタ 工程%s"), $no) . " [" . _g("固定経費") . "]", 1000);
                            $arr[] = array("item_process_master{$i}.process_remarks_1", "品目マスタ_工程{$no}_工程メモ1", sprintf(_g("品目マスタ 工程%s"), $no) . " [" . _g("工程メモ1") . "]", _g("工程メモ1"));
                            $arr[] = array("item_process_master{$i}.process_remarks_2", "品目マスタ_工程{$no}_工程メモ2", sprintf(_g("品目マスタ 工程%s"), $no) . " [" . _g("工程メモ2") . "]", _g("工程メモ2"));
                            $arr[] = array("item_process_master{$i}.process_remarks_3", "品目マスタ_工程{$no}_工程メモ3", sprintf(_g("品目マスタ 工程%s"), $no) . " [" . _g("工程メモ3") . "]", _g("工程メモ3"));
                        }
                    }
                    // 画像タグ（[[image:xxx]]）は、画像のフルパスを返すようにする。Gen_Storage に格納している画像の場合は、「カテゴリ（Gen_Storage 冒頭で指定。ItemImageなど）::ファイル名」という形にする
                    $arr[] = array("case when coalesce(item_master.image_file_name,'') = '' then '' else 'ItemImage::' || item_master.image_file_name end", "品目マスタ_画像", _g("品目マスタ [画像]。[[image:品目マスタ_画像]] として使用"), "");
                    break;
                case "customer_master":
                    $arr[] = array("●" . _g("得意先") . $table[2]);
                    $arr[] = array("customer_master.customer_no", "得意先_取引先コード", _g("取引先マスタ [取引先コード]"), "cust002");
                    $arr[] = array("customer_master.customer_name", "得意先_取引先名", _g("取引先マスタ [取引先名]"), _g("テスト得意先"));
                    $arr[] = array("case when customer_master.end_customer then '" . _g("非表示") . "' else '' end", "得意先_非表示", _g("取引先マスタ [非表示取引先] がオンなら「非表示」"), _g("非表示"));
                    $arr[] = array("customer_master.zip", "得意先_郵便番号", _g("取引先マスタ [郵便番号]"), "123-4567");
                    $arr[] = array("customer_master.address1", "得意先_住所1", _g("取引先マスタ [住所1]"), _g("愛知県名古屋市"));
                    $arr[] = array("customer_master.address2", "得意先_住所2", _g("取引先マスタ [住所2]"), _g("緑区1-1"));
                    $arr[] = array("customer_master.tel", "得意先_電話番号", _g("取引先マスタ [電話番号]"), "012-3456-7890");
                    $arr[] = array("customer_master.fax", "得意先_ファックス番号", _g("取引先マスタ [FAX番号]"), "012-3456-7890");
                    $arr[] = array("customer_master.e_mail", "得意先_メールアドレス", _g("取引先マスタ [メールアドレス]"), "test@test.co.jp");
                    $arr[] = array("customer_master.person_in_charge", "得意先_担当者", _g("取引先マスタ [担当者]"), _g("担当者 次郎"));
                    $arr[] = array("case customer_master.rounding when 'round' then '" . _g("四捨五入") . "' when 'floor' then '" . _g("切捨") . "' when 'ceil' then '" . _g("切上") . "' else '" . _g("なし") . "' end", "得意先_端数処理", _g("取引先マスタ [端数処理]"), _g("四捨五入"));
                    $arr[] = array("customer_master.precision", "得意先_小数点以下桁数", _g("取引先マスタ [金額の小数点以下桁数]"), 0);
                    $arr[] = array("coalesce(customer_master.report_language,0)", "得意先_帳票言語区分", _g("取引先マスタ [帳票言語区分]。0:日本語、1:英語"), "1");
                    $arr[] = array("customer_master.monthly_limit_date", "得意先_締日グループ", _g("取引先マスタ [締日グループ]"), 30);
                    $arr[] = array("customer_master.inspection_lead_time", "得意先_検収リードタイム", _g("取引先マスタ [検収リードタイム]"), 0);
                    $arr[] = array("case customer_master.tax_category when 0 then '" . _g("請求書単位") . "' when 1 then '" . _g("納品書単位") . "' else '" . _g("納品明細単位") . "' end", "得意先_税計算単位", _g("取引先マスタ [税計算単位]"), _g("請求書単位"));
                    $arr[] = array("customer_master.price_percent", "得意先_掛率", _g("取引先マスタ [掛率]"), 100);
                    $arr[] = array("case customer_master.bill_pattern when 0 then '" . _g("締め-残高表示なし") . "' when 1 then '" . _g("締め-残高表示あり") . "' else '" . _g("都度") . "' end", "得意先_請求パターン", _g("取引先マスタ [請求パターン]"), _g("都度"));
                    $arr[] = array("customer_master.credit_line", "得意先_与信限度額", _g("取引先マスタ [与信限度額]"), 100000);
                    $arr[] = array("customer_master.opening_balance", "得意先_売掛残高初期値", _g("取引先マスタ [売掛残高初期値]"), 0);
                    $arr[] = array("customer_master.opening_date", "得意先_売掛基準日", _g("取引先マスタ [売掛基準日]"), "2014-01-01");
                    $arr[] = array("customer_master.receivable_cycle1", "得意先_回収サイクル1", _g("取引先マスタ [回収サイクル1（x日後）]"), 30);
                    $arr[] = array("customer_master.receivable_cycle2_month", "得意先_回収サイクル2_1", _g("取引先マスタ [回収サイクル2（xヶ月後）]"), 1);
                    $arr[] = array("customer_master.receivable_cycle2_day", "得意先_回収サイクル2_2", _g("取引先マスタ [回収サイクル2（x日）]"), 10);
                    $arr[] = array("case when customer_master.receivable_cycle1 is not null then cast(customer_master.receivable_cycle1 as text) || '" . _g("日後") . "' else case when customer_master.receivable_cycle2_month is not null and customer_master.receivable_cycle2_day is not null then cast(customer_master.receivable_cycle2_month as text) || '" . _g("ヶ月後の") . "' || cast(customer_master.receivable_cycle2_day as text) || '" . _g("日") . "' end end", "得意先_回収サイクル表示", _g("取引先マスタ [回収サイクル]（「x日後」「xヶ月後のx日」等の表記）"), _g("2ヶ月後の10日"));
                    $arr[] = array("customer_master.remarks", "得意先_備考1", _g("取引先マスタ [取引先備考1]"), _g("取引先備考1"));
                    $arr[] = array("customer_master.remarks_2", "得意先_備考2", _g("取引先マスタ [取引先備考2]"), _g("取引先備考2"));
                    $arr[] = array("customer_master.remarks_3", "得意先_備考3", _g("取引先マスタ [取引先備考3]"), _g("取引先備考3"));
                    $arr[] = array("customer_master.remarks_4", "得意先_備考4", _g("取引先マスタ [取引先備考4]"), _g("取引先備考4"));
                    $arr[] = array("customer_master.remarks_5", "得意先_備考5", _g("取引先マスタ [取引先備考5]"), _g("取引先備考5"));
                    break;
                case "customer_master_partner":
                    $arr[] = array("●" . _g("取引先") . $table[2]);
                    $arr[] = array("customer_master_partner.customer_no", "取引先_取引先コード", _g("取引先マスタ [取引先コード]"), "cust002");
                    $arr[] = array("customer_master_partner.customer_name", "取引先_取引先名", _g("取引先マスタ [取引先名]"), _g("テスト取引先"));
                    $arr[] = array("case when customer_master_partner.end_customer then '" . _g("非表示") . "' else '' end", "取引先_非表示", _g("取引先マスタ [非表示取引先] がオンなら「非表示」"), _g("非表示"));
                    $arr[] = array("customer_master_partner.zip", "取引先_郵便番号", _g("取引先マスタ [郵便番号]"), "123-4567");
                    $arr[] = array("customer_master_partner.address1", "取引先_住所1", _g("取引先マスタ [住所1]"), _g("愛知県名古屋市"));
                    $arr[] = array("customer_master_partner.address2", "取引先_住所2", _g("取引先マスタ [住所2]"), _g("緑区1-1"));
                    $arr[] = array("customer_master_partner.tel", "取引先_電話番号", _g("取引先マスタ [電話番号]"), "012-3456-7890");
                    $arr[] = array("customer_master_partner.fax", "取引先_ファックス番号", _g("取引先マスタ [FAX番号]"), "012-3456-7890");
                    $arr[] = array("customer_master_partner.e_mail", "取引先_メールアドレス", _g("取引先マスタ [メールアドレス]"), "test@test.co.jp");
                    $arr[] = array("customer_master_partner.person_in_charge", "取引先_担当者", _g("取引先マスタ [担当者]"), _g("担当者 次郎"));
                    $arr[] = array("case customer_master_partner.rounding when 'round' then '" . _g("四捨五入") . "' when 'floor' then '" . _g("切捨") . "' when 'ceil' then '" . _g("切上") . "' else '" . _g("なし") . "' end", "取引先_端数処理", _g("取引先マスタ [端数処理]"), _g("四捨五入"));
                    $arr[] = array("customer_master_partner.precision", "取引先_小数点以下桁数", _g("取引先マスタ [金額の小数点以下桁数]"), 0);
                    $arr[] = array("coalesce(customer_master_partner.report_language,0)", "取引先_帳票言語区分", _g("取引先マスタ [帳票言語区分]。0:日本語、1:英語"), "1");
                    $arr[] = array("customer_master_partner.monthly_limit_date", "取引先_締日グループ", _g("取引先マスタ [締日グループ]"), 30);
                    $arr[] = array("customer_master_partner.inspection_lead_time", "取引先_検収リードタイム", _g("取引先マスタ [検収リードタイム]"), 0);
                    $arr[] = array("customer_master_partner.default_lead_time", "取引先_標準リードタイム", _g("取引先マスタ [標準リードタイム]"), 0);
                    $arr[] = array("customer_master_partner.delivery_port", "取引先_納入場所", _g("取引先マスタ [納入場所]"), _g("納入場所"));
                    $arr[] = array("customer_master_partner.payment_opening_balance", "取引先_買掛残高初期値", _g("取引先マスタ [買掛残高初期値]"), 0);
                    $arr[] = array("customer_master_partner.payment_opening_date", "取引先_買掛基準日", _g("取引先マスタ [買掛基準日]"), "2014-01-01");
                    $arr[] = array("customer_master_partner.payment_cycle1", "取引先_支払サイクル1", _g("取引先マスタ [支払サイクル1（x日後）]"), 30);
                    $arr[] = array("customer_master_partner.payment_cycle2_month", "取引先_支払サイクル2_1", _g("取引先マスタ [支払サイクル2（xヶ月後）]"), 1);
                    $arr[] = array("customer_master_partner.payment_cycle2_day", "取引先_支払サイクル2_2", _g("取引先マスタ [支払サイクル2（x日）]"), 10);
                    $arr[] = array("customer_master_partner.remarks", "取引先_備考1", _g("取引先マスタ [取引先備考1]"), _g("取引先備考1"));
                    $arr[] = array("customer_master_partner.remarks_2", "取引先_備考2", _g("取引先マスタ [取引先備考2]"), _g("取引先備考2"));
                    $arr[] = array("customer_master_partner.remarks_3", "取引先_備考3", _g("取引先マスタ [取引先備考3]"), _g("取引先備考3"));
                    $arr[] = array("customer_master_partner.remarks_4", "取引先_備考4", _g("取引先マスタ [取引先備考4]"), _g("取引先備考4"));
                    $arr[] = array("customer_master_partner.remarks_5", "取引先_備考5", _g("取引先マスタ [取引先備考5]"), _g("取引先備考5"));
                    // 本来のテーブル名と名前が異なるとき（テーブル名にエイリアスをつけているとき）は次の2行が必要
                    $originalTableName = "customer_master";
                    $tagPrefix = "取引先";
                    break;
                case "customer_master_shipping":
                    $arr[] = array("●" . _g("発送先") . $table[2]);
                    $arr[] = array("customer_master_shipping.customer_no", "発送先_取引先コード", _g("取引先マスタ [取引先コード]"), "cust002");
                    $arr[] = array("customer_master_shipping.customer_name", "発送先_取引先名", _g("取引先マスタ [取引先名]"), _g("テスト発送先"));
                    $arr[] = array("case when customer_master_shipping.end_customer then '" . _g("非表示") . "' else '' end", "発送先_非表示", _g("取引先マスタ [非表示] がオンなら「非表示」"), _g("非表示"));
                    $arr[] = array("customer_master_shipping.zip", "発送先_郵便番号", _g("取引先マスタ [郵便番号]"), "123-4567");
                    $arr[] = array("customer_master_shipping.address1", "発送先_住所1", _g("取引先マスタ [住所1]"), _g("愛知県名古屋市"));
                    $arr[] = array("customer_master_shipping.address2", "発送先_住所2", _g("取引先マスタ [住所2]"), _g("緑区1-1"));
                    $arr[] = array("customer_master_shipping.tel", "発送先_電話番号", _g("取引先マスタ [電話番号]"), "012-3456-7890");
                    $arr[] = array("customer_master_shipping.fax", "発送先_ファックス番号", _g("取引先マスタ [FAX番号]"), "012-3456-7890");
                    $arr[] = array("customer_master_shipping.e_mail", "発送先_メールアドレス", _g("取引先マスタ [メールアドレス]"), "test@test.co.jp");
                    $arr[] = array("customer_master_shipping.person_in_charge", "発送先_担当者", _g("取引先マスタ [担当者]"), _g("担当者 次郎"));
                    $arr[] = array("customer_master_shipping.delivery_port", "発送先_納入場所", _g("取引先マスタ [納入場所]"), _g("納入場所"));
                    $arr[] = array("customer_master_shipping.remarks", "発送先_備考1", _g("取引先マスタ [取引先備考1]"), _g("発送先備考1"));
                    $arr[] = array("customer_master_shipping.remarks_2", "発送先_備考2", _g("取引先マスタ [取引先備考2]"), _g("発送先備考2"));
                    $arr[] = array("customer_master_shipping.remarks_3", "発送先_備考3", _g("取引先マスタ [取引先備考3]"), _g("発送先備考3"));
                    $arr[] = array("customer_master_shipping.remarks_4", "発送先_備考4", _g("取引先マスタ [取引先備考4]"), _g("発送先備考4"));
                    $arr[] = array("customer_master_shipping.remarks_5", "発送先_備考5", _g("取引先マスタ [取引先備考5]"), _g("発送先備考5"));
                    // 本来のテーブル名と名前が異なるとき（テーブル名にエイリアスをつけているとき）は次の2行が必要
                    $originalTableName = "customer_master";
                    $tagPrefix = "発送先";
                    break;
                case "customer_master_bill":
                    $arr[] = array("●" . _g("請求先") . $table[2]);
                    $arr[] = array("customer_master_bill.customer_no", "請求先_取引先コード", _g("取引先マスタ [取引先コード]"), "cust002");
                    $arr[] = array("customer_master_bill.customer_name", "請求先_取引先名", _g("取引先マスタ [取引先名]"), _g("テスト請求先"));
                    $arr[] = array("case when customer_master_bill.end_customer then '" . _g("非表示") . "' else '' end", "請求先_非表示", _g("取引先マスタ [非表示取引先] がオンなら「非表示」"), _g("非表示"));
                    $arr[] = array("customer_master_bill.zip", "請求先_郵便番号", _g("取引先マスタ [郵便番号]"), "123-4567");
                    $arr[] = array("customer_master_bill.address1", "請求先_住所1", _g("取引先マスタ [住所1]"), _g("愛知県名古屋市"));
                    $arr[] = array("customer_master_bill.address2", "請求先_住所2", _g("取引先マスタ [住所2]"), _g("緑区1-1"));
                    $arr[] = array("customer_master_bill.tel", "請求先_電話番号", _g("取引先マスタ [電話番号]"), "012-3456-7890");
                    $arr[] = array("customer_master_bill.fax", "請求先_ファックス番号", _g("取引先マスタ [FAX番号]"), "012-3456-7890");
                    $arr[] = array("customer_master_bill.e_mail", "請求先_メールアドレス", _g("取引先マスタ [メールアドレス]"), "test@test.co.jp");
                    $arr[] = array("customer_master_bill.person_in_charge", "請求先_担当者", _g("取引先マスタ [担当者]"), _g("担当者 次郎"));
                    $arr[] = array("case customer_master_bill.rounding when 'round' then '" . _g("四捨五入") . "' when 'floor' then '" . _g("切捨") . "' when 'ceil' then '" . _g("切上") . "' else '" . _g("なし") . "' end", "請求先_端数処理", _g("取引先マスタ [端数処理]"), _g("四捨五入"));
                    $arr[] = array("customer_master_bill.precision", "請求先_小数点以下桁数", _g("取引先マスタ [金額の小数点以下桁数]"), 0);
                    $arr[] = array("coalesce(customer_master_bill.report_language,0)", "請求先_帳票言語区分", _g("取引先マスタ [帳票言語区分]。0:日本語、1:英語"), "1");
                    $arr[] = array("customer_master_bill.monthly_limit_date", "請求先_締日グループ", _g("取引先マスタ [締日グループ]"), 30);
                    $arr[] = array("customer_master_bill.inspection_lead_time", "請求先_検収リードタイム", _g("取引先マスタ [検収リードタイム]"), 0);
                    $arr[] = array("case customer_master_bill.tax_category when 0 then '" . _g("請求書単位") . "' when 1 then '" . _g("納品書単位") . "' else '" . _g("納品明細単位") . "' end", "請求先_税計算単位", _g("取引先マスタ [税計算単位]"), _g("請求書単位"));
                    $arr[] = array("customer_master_bill.price_percent", "請求先_掛率", _g("取引先マスタ [掛率]"), 100);
                    $arr[] = array("case customer_master_bill.bill_pattern when 0 then '" . _g("締め-残高表示なし") . "' when 1 then '" . _g("締め-残高表示あり") . "' else '" . _g("都度") . "' end", "請求先_請求パターン", _g("取引先マスタ [請求パターン]"), _g("都度"));
                    $arr[] = array("customer_master_bill.credit_line", "請求先_与信限度額", _g("取引先マスタ [与信限度額]"), 100000);
                    $arr[] = array("customer_master_bill.opening_balance", "請求先_売掛残高初期値", _g("取引先マスタ [売掛残高初期値]"), 0);
                    $arr[] = array("customer_master_bill.opening_date", "請求先_売掛基準日", _g("取引先マスタ [売掛基準日]"), "2014-01-01");
                    $arr[] = array("customer_master_bill.receivable_cycle1", "請求先_回収サイクル1", _g("取引先マスタ [回収サイクル1（x日後）]"), 30);
                    $arr[] = array("customer_master_bill.receivable_cycle2_month", "請求先_回収サイクル2_1", _g("取引先マスタ [回収サイクル2（xヶ月後）]"), 1);
                    $arr[] = array("customer_master_bill.receivable_cycle2_day", "請求先_回収サイクル2_2", _g("取引先マスタ [回収サイクル2（x日）]"), 10);
                    $arr[] = array("customer_master_bill.remarks", "請求先_備考1", _g("取引先マスタ [取引先備考1]"), _g("請求先備考1"));
                    $arr[] = array("customer_master_bill.remarks_2", "請求先_備考2", _g("取引先マスタ [取引先備考2]"), _g("請求先備考2"));
                    $arr[] = array("customer_master_bill.remarks_3", "請求先_備考3", _g("取引先マスタ [取引先備考3]"), _g("請求先備考3"));
                    $arr[] = array("customer_master_bill.remarks_4", "請求先_備考4", _g("取引先マスタ [取引先備考4]"), _g("請求先備考4"));
                    $arr[] = array("customer_master_bill.remarks_5", "請求先_備考5", _g("取引先マスタ [取引先備考5]"), _g("請求先備考5"));
                    // 本来のテーブル名と名前が異なるとき（テーブル名にエイリアスをつけているとき）は次の2行が必要
                    $originalTableName = "customer_master";
                    $tagPrefix = "請求先";
                    break;
                case "worker_master":
                    $arr[] = array("●" . _g("従業員マスタ") . $table[2]);
                    $arr[] = array("worker_master.worker_code", "従業員マスタ_従業員コード", _g("従業員マスタ [従業員コード]"), "emp001");
                    $arr[] = array("worker_master.worker_name", "従業員マスタ_従業員名", _g("従業員マスタ [従業員名]"), _g("テスト従業員"));
                    $arr[] = array("case when worker_master.end_worker then '" . _g("退職") . "' else '' end", "得意先_退職", _g("従業員マスタ [退職] がオンなら「退職」"), _g("退職"));
                    $arr[] = array("worker_master.remarks", "従業員マスタ_備考", _g("従業員マスタ [従業員備考]"), _g("従業員備考"));
                    break;
                case "section_master":
                    $arr[] = array("●" . _g("部門マスタ") . $table[2]);
                    $arr[] = array("section_master.section_code", "部門マスタ_部門コード", _g("部門マスタ [部門コード]"), "section001");
                    $arr[] = array("section_master.section_name", "部門マスタ_部門名", _g("部門マスタ [部門名]"), _g("テスト部門"));
                    $arr[] = array("section_master.remarks", "部門マスタ_備考", _g("部門マスタ [部門備考]"), _g("部門備考"));
                    break;
                case "currency_master":
                    $arr[] = array("●" . _g("通貨マスタ") . $table[2]);
                    $arr[] = array("case when currency_master.currency_name is null then '{$keyCurrency}' else currency_master.currency_name end", "通貨マスタ_通貨", _g("通貨マスタ [取引通貨]"), "USD");
                    break;
                case "item_master_children":
                case "item_master_process":
                    // item_master の中で処理
                    break;
                default:
                    throw new Exception("_getReportParam() の tables で指定されているテーブルが、PDFReportBase の _getTableTagInfo() にありません。");
            }

            // カスタム項目
            if ($originalTableName == "") {
                $originalTableName = $table[0];
            }
            $customColumnArr = Logic_CustomColumn::getCustomColumnParamByTableName($originalTableName, $tagPrefix);
            if ($customColumnArr) {
                $customColumnParamArr = $customColumnArr[1];
                foreach ($customColumnParamArr as $customCol => $customArr) {
                    $customName = $customArr[1];            // カスタム項目名（getText/wordconvert）
                    $tagName = $customArr[3];    // 帳票タグ（非getText）
                    $arr[] = array("{$table[0]}.{$customCol}", $tagName, $customName, $customName);
                }
            }

            // detailフラグ
            $isDetail = $table[1];
            foreach ($arr as $key => $val) {
                if (count($val) > 2) {  // セパレータは除く
                    $arr[$key][] = $isDetail;
                }
            }

            // allArrへの追加
            $allArr = array_merge($allArr, $arr);
        }
        return $allArr;
    }

}
