<?php

class Monthly_Bill_Report extends Base_PDFReportBase
{

    function validate($validator, $form)
    {
        if (!isset($form['reprint_mode']) && !isset($form['isDelivery'])) {
            $validator->salesLockDateOrLater('close_date', _g('請求締日'));
        }

        $form['gen_restore_search_condition'] = 'true';
        if (isset($form['is_delivery']) && $form['is_delivery'] == "true") {
            return 'action:Delivery_Delivery_List';
        } else {
            if (isset($form['is_delivery']) && $form['is_delivery'] == "true") {
                return 'action:Delivery_Delivery_List';
            } else {
                return 'action:Monthly_Bill_List';
            }
        }
    }

    protected function _getQuery(&$form)
    {
        global $gen_db;

        //------------------------------------------------------
        //  印刷確認
        //------------------------------------------------------
        // 同時に請求書を印刷しない場合
        if (isset($form['no_print']) && $form['no_print'] == "true") {
            $this->noPrint = true;
        }

        // 都度請求時の画面遷移
        if (isset($form['is_delivery']) && $form['is_delivery'] == "true") {
            $this->errorAction = "Delivery_Delivery_List";
        }

        // 請求書リストからの発行の場合
        if (isset($form['reprint_mode'])) {
            $this->errorAction = "Monthly_Bill_BillList";
        }

        //------------------------------------------------------
        //  明細の数量０非表示の確認
        //------------------------------------------------------
        $detailDisplay = 0;     // 表示
        if (isset($form['no_zero']) && $form['no_zero'] == "true") {
            $detailDisplay = 1;     // 非表示
        }

        //------------------------------------------------------
        // 請求データの作成
        //------------------------------------------------------
        // 都度請求データ作成
        if (isset($form['is_delivery']) && $form['is_delivery'] == "true") {
            // 印刷対象データを配列に列挙する
            $checkArr = array();
            foreach ($form as $name => $value) {
                if (substr($name, 0, 9) == "chk_bill_") {
                    $checkArr[] = substr($name, 9, strlen($name) - 9);
                }
            }
            // 明細モードのときはdetail_idが指定されているので、header_idに変換しておく
            if (isset($form['detail']) && $form['detail'] == "true" && count($checkArr) > 0) {
                $query = "select delivery_header_id from delivery_detail where delivery_detail_id in (" . join(",", $checkArr) . ") group by delivery_header_id";
                $checkArr2 = $gen_db->getArray($query);
                $checkArr = array();
                foreach ($checkArr2 as $row) {
                    $checkArr[] = $row['delivery_header_id'];
                }
            }
            // 納品番号順にソートする
            asort($checkArr);
            $idArr = array();   // 初期化
            foreach ($checkArr as $value) {
                // データの新規作成
                $id = Logic_Bill::makeBillData(2, $form['close_date'], $detailDisplay, null, $value);
                if (isset($id) && is_numeric($id))
                    $idArr[] = $id;
            }
            Gen_Log::dataAccessLog(_g("請求書"), _g("データ作成"), "[" . _g("請求パターン") . "] " . _g("都度") . " " . "[" . _g("締日") . "] " .$form['close_date']);

        // 締め請求データ作成
        } elseif (!isset($form['reprint_mode'])) {
            // 印刷対象データを配列に列挙する
            $checkArr = array();
            foreach ($form as $name => $value) {
                if (substr($name, 0, 6) == "check_") {
                    $checkArr[] = substr($name, 6, strlen($name) - 6);
                }
            }
            // データの新規作成
            $idArr = Logic_Bill::makeBillData(0, $form['close_date'], $detailDisplay, $checkArr, null);
            Gen_Log::dataAccessLog(_g("請求書"), _g("データ作成"), "[" . _g("請求パターン") . "] " . _g("締め") . " " . "[" . _g("締日") . "] " .$form['close_date']);
        } else {
            // 既存データの印刷
            // 印刷対象データを配列に列挙する
            $idArr = array();
            foreach ($form as $name => $value) {
                if (substr($name, 0, 6) == "check_") {
                    $idArr[] = substr($name, 6, strlen($name) - 6);
                }
            }
        }
        $idCsv = join(",", $idArr);
        if ($idCsv == "") {
            $idCsv = "-9999999";    // dummy
            $this->noPrintMsg = _g("データがありません。"); // 非印刷時のメッセージ
        } else {
            $this->noPrintMsg = _g('請求書データを作成しました。'); // 非印刷時のメッセージ
        }

        // 印刷フラグ更新用
        $form['idArr'] = $idArr;

        // 通知メール
        $isNew = (!isset($form['reprint_mode']));
        $title = ($isNew ? _g("請求書の新規発行") : _g("請求書の再印刷"));
        $body = ($isNew ? _g("請求書が新規発行されました。") : _g("請求書が再印刷されました。")) . "\n\n"
                . "[" . _g("発行日時") . "] " . date('Y-m-d H:i:s') . "\n"
                . "[" . _g("発行者") . "] " . $_SESSION['user_name'] . "\n\n"
                . ($isNew ? "[" . _g("請求締日") . "] " . $form['close_date'] . "\n" : "")
                . "";
        Gen_Mail::sendAlertMail('monthly_bill_' . ($isNew ? "new" : "reprint"), $title, $body);

        //------------------------------------------------------
        //  印刷データの取得
        //------------------------------------------------------
        //
        // 基本的に、請求書発行時のマスタ・トランザクションの内容に基づき印刷が行われる。
        // （発行後のデータ変更は反映されない）
        // ただし、取引先住所と取引先名だけは印刷時点のマスタの状態が反映される。

        $classQuery = Gen_Option::getWayOfPayment('list-query');

        // 受注・納品のカスタム項目を追加。
        //  詳細は self::_getCustomColumn() のコメントを参照。
        $customSelect = self::_getCustomColumn(true);
        
        $keyCurrency = $gen_db->queryOneValue("select key_currency from company_master");
        $query_top = "
            select
                -- headers

                bill_header.bill_header_id as bill_header_id

                -- 受注取引先ごとに改ページする仕様にしたい場合のためのカラム。
                -- このカラムをページキーにし、次のカラムをページ数キーにする（getReportParam() 冒頭参照）
                ,cast(bill_header.bill_header_id as text) || '_' || coalesce(t_received_customer.customer_no,'') as bill_header_id_customer_no
                ,bill_header.bill_header_id as page_count_key

                ,bill_header.bill_number as 請求書_請求書番号

                ,case when bill_header.foreign_currency_id is null then
                    bill_header.before_amount
                    else bill_header.foreign_currency_before_amount
                    end as 請求書_前回ご請求高
                ,case when bill_header.foreign_currency_id is null then
                    bill_header.paying_in
                    else bill_header.foreign_currency_paying_in
                    end as 請求書_ご入金高
                ,case when bill_header.foreign_currency_id is null then
                    coalesce(bill_header.before_amount,0) - coalesce(bill_header.paying_in,0)
                    else coalesce(bill_header.foreign_currency_before_amount,0) - coalesce(bill_header.foreign_currency_paying_in,0)
                    end as 請求書_繰越高
                ,case when bill_header.foreign_currency_id is null then
                    bill_header.sales_amount
                    else bill_header.foreign_currency_sales_amount
                    end as 請求書_今回お買い上高
                ,case when bill_header.foreign_currency_id is null then
                    bill_header.tax_amount
                    end as 請求書_合計消費税
                ,case when bill_header.foreign_currency_id is null then
                    coalesce(bill_header.sales_amount,0) + coalesce(bill_header.tax_amount,0)
                    else bill_header.foreign_currency_sales_amount
                    end as 請求書_合計金額
                ,case when bill_header.foreign_currency_id is null then
                    bill_header.bill_amount
                    else bill_header.foreign_currency_bill_amount
                    end as 請求書_今回ご請求高
                ,case when coalesce(customer_master.report_language,0)=0 then
                    bill_header.close_date_show
                    else cast(bill_header.close_date as text)
                    end as 請求書_締日
                ,bill_header.receivable_date as 請求書_回収予定日
                ,t_received_customer_sum.received_customer_sum as 請求書_受注取引先小計
                
                /* 「gen_template」というカラムが存在すると、そのカラムで指定されているテンプレートが使用される */
                ,customer_master.template_bill as gen_template

                -- details

                ,cast(delivery_no_forsort as text) as detail_請求書_納品書番号（ソート用）
                ,line_no_forsort as detail_請求書_納品書行番号（ソート用）
                ,delivery_date_forsort as detail_請求書_納品日（ソート用） /* このタグの用途については、下のタグリストのコメントを参照 */

                ,t_detail.delivery_date as detail_請求書_納品日
                ,t_detail.inspection_date as detail_請求書_検収日
                ,t_detail.delivery_no as detail_請求書_納品書番号
                ,t_detail.received_number as detail_請求書_受注番号
                ,t_detail.customer_received_number as detail_請求書_客先注番
                ,t_detail.received_line_no as detail_請求書_受注行番号
                ,t_detail.received_seiban as detail_請求書_受注製番
                ,t_detail.item_code as detail_請求書_品目コード
                ,t_detail.item_name as detail_請求書_品目名
                ,t_detail.measure as detail_請求書_単位
                ,t_detail.quantity as detail_請求書_数量
                ,t_detail.price as detail_請求書_単価
                ,t_detail.amount as detail_請求書_金額
                ,coalesce(t_detail.amount,0) + coalesce(t_detail.tax,0) as detail_請求書_納品書金額
                ,case bill_header.tax_category 
                    when 1 then /* 税計算単位：納品書単位。小計行のみ税を表示。ちなみに13i時代のデータは納品書単位でも明細行の税（bill_detail.tax）が記録されていることがある */
                        case when line_no_forsort = 99999998 then t_detail.tax end 
                    when 2 then /* 税計算単位：納品明細単位。小計行以外のみ税を表示 */
                        t_detail.tax 
                    end as detail_請求書_消費税
                ,t_detail.tax_rate as detail_請求書_消費税率
                ,t_detail.lot_no as detail_請求書_ロット番号
                ,t_detail.remarks as detail_請求書_納品備考
                ,t_received_customer.customer_no as detail_請求書_受注取引先コード
                ,t_received_customer.customer_name as detail_請求書_受注取引先名
                
                {$customSelect}
                
            from
                bill_header
                left join customer_master on bill_header.customer_id = customer_master.customer_id
                left join currency_master on bill_header.foreign_currency_id = currency_master.currency_id

                left join (
                    select
                        bill_detail.bill_header_id
                        ,bill_detail_id
                        ,delivery_no as delivery_no_forsort
                        ,line_no as line_no_forsort
                        ,delivery_date as delivery_date_forsort
                        ,delivery_date
                        ,inspection_date
                        ,delivery_detail_id
                        ,delivery_no
                        ,received_number
                        ,customer_received_number
                        ,received_line_no
                        ,received_seiban
                        ,item_id
                        ,item_code
                        ,item_name
                        ,measure
                        ,quantity
                        ,case when bill_header.foreign_currency_id is null then price else foreign_currency_price end as price
                        ,case when bill_header.foreign_currency_id is null then amount else foreign_currency_amount end as amount
                        ,case when bill_header.foreign_currency_id is null then tax end as tax
                        ,case when bill_header.foreign_currency_id is null then tax_rate end as tax_rate
                        ,lot_no
                        ,remarks
                        ,bill_detail.received_customer_id
                        ,bill_detail.delivery_customer_id
                    from
                        bill_detail
                        inner join bill_header on bill_detail.bill_header_id = bill_header.bill_header_id
                    where
                        case when bill_header.detail_display = 1 then bill_detail.quantity <> 0 else true end
        ";
        $query_subsum = "
                    -- 納品書小計行
                    union all
                    select
                        max(bill_detail.bill_header_id) as bill_header_id
                        ,max(bill_detail_id) as bill_detail_id
                        ,delivery_no as delivery_no_forsort
                        ,99999998 as line_no_forsort
                        ,max(delivery_date) as delivery_date_forsort
                        ,null as delivery_date
                        ,null as inspection_date
                        ,null as delivery_detail_id
                        ,null as delivery_no
                        ,null as received_number
                        ,null as customer_received_number
                        ,null as received_line_no
                        ,null as received_seiban
                        ,null as item_id
                        ,null as item_code
                        ,'" . _g("小計") . "' as item_name
                        ,null as measure
                        ,null as quantity
                        ,null as price
                        ,max(case when bill_header.foreign_currency_id is null then delivery_note_amount else foreign_currency_delivery_note_amount end) as amount
                        ,max(case when bill_header.foreign_currency_id is null then delivery_note_tax end) as tax
                        ,max(case when bill_header.foreign_currency_id is null then tax_rate end) as tax_rate
                        ,null as lot_no
                        ,null as remarks
                        ,null as received_customer_id
                        ,null as delivery_customer_id
                    from
                        bill_detail
                        inner join bill_header on bill_detail.bill_header_id = bill_header.bill_header_id
                    where
                        case when bill_header.detail_display = 1 then bill_detail.quantity <> 0 else true end
                    group by
                        delivery_no

                    -- 納品書小計のあとの空行
                    union all
                    select
                        max(bill_header_id) as bill_header_id
                        ,max(bill_detail_id) as bill_detail_id
                        ,delivery_no as delivery_no_forsort
                        ,99999999 as line_no_forsort
                        ,max(delivery_date) as delivery_date_forsort
                        ,null as delivery_date
                        ,null as inspection_date
                        ,null as delivery_detail_id
                        ,null as delivery_no
                        ,null as received_number
                        ,null as customer_received_number
                        ,null as received_line_no
                        ,null as received_seiban
                        ,null as item_id 
                        ,null as item_code
                        ,null as item_name
                        ,null as measure
                        ,null as quantity
                        ,null as price
                        ,null as amount
                        ,null as tax
                        ,null as tax_rate
                        ,null as lot_no
                        ,null as remarks
                        ,null as received_customer_id
                        ,null as delivery_customer_id
                    from
                        bill_detail
                    group by
                        delivery_no
        ";
        $query_payingin = "
                    -- 入金行　（最後に）
                    -- before_close_date　から 指定された請求書のclose_dateまでの間に入力された入金を取得。
                    -- 本来は請求書発行時（データ作成時）に入金データも作成しておくべき。だが、請求書発行したら入金データにロックが
                    -- かかるため矛盾は発生しないはず。
                    union all
                    select
                        t1.bill_header_id as bill_header_id
                        ,null as bill_detail_id
                        ,'ZZZZZZZZZZ' as delivery_no_forsort
                        ,99999999 as line_no_forsort
                        ,t0.paying_in_date as delivery_date_forsort
                        ,t0.paying_in_date as delivery_date
                        ,t0.paying_in_date as inspection_date
                        ,null as delivery_detail_id
                        ,null as delivery_no
                        ,null as received_number
                        ,null as customer_received_number
                        ,null as received_line_no
                        ,null as received_seiban
                        ,null as item_id 
                        ,null as item_code
                        ,case t0.way_of_payment {$classQuery} else 'err' end as item_name
                        ,null as measure
                        ,null as quantity
                        ,null as price
                        ,t0.amount as amount
                        ,null as tax
                        ,null as tax_rate
                        ,null as lot_no
                        ,null as remarks
                        ,null as received_customer_id
                        ,null as delivery_customer_id
                    from
                        (select
                            customer_id
                            ,paying_in_date
                            ,way_of_payment
                            ,amount
                            ,foreign_currency_id
                            ,foreign_currency_amount
                        from
                            paying_in
                        order by
                            paying_in_date, paying_in_id
                        ) as t0
                    inner join
                        bill_header as t1
                        on t0.customer_id = t1.customer_id
                            and t0.paying_in_date between t1.begin_date and t1.close_date
                            and coalesce(t0.foreign_currency_id, 999999) = coalesce(t1.foreign_currency_id, 999999)
                    where
                        bill_pattern = 1   -- 都度請求は入金額を表示しない
        ";
        $query_bottom = "
                ) as t_detail
                on bill_header.bill_header_id = t_detail.bill_header_id
                left join item_master on t_detail.item_id = item_master.item_id
                
                " . ($customSelect == "" ? "" : "
                    left join delivery_detail on t_detail.delivery_detail_id = delivery_detail.delivery_detail_id
                    left join delivery_header on delivery_detail.delivery_header_id = delivery_header.delivery_header_id
                    left join received_detail on delivery_detail.received_detail_id = received_detail.received_detail_id
                    left join received_header on received_detail.received_header_id = received_header.received_header_id
                    ") . "
                    
                -- 受注取引先合計
                left join customer_master as t_received_customer on t_detail.received_customer_id = t_received_customer.customer_id
                left join (
                    select
                        bill_header.bill_header_id
                        ,bill_detail.received_customer_id
                        ,sum(case when bill_header.foreign_currency_id is null then amount else foreign_currency_amount end) as received_customer_sum
                    from
                        bill_detail
                        inner join bill_header on bill_detail.bill_header_id = bill_header.bill_header_id
                    group by
                        bill_header.bill_header_id, bill_detail.received_customer_id
                ) as t_received_customer_sum
                on t_detail.bill_header_id = t_received_customer_sum.bill_header_id
                and t_detail.received_customer_id = t_received_customer_sum.received_customer_id

                -- 発送先
                left join customer_master as customer_master_shipping on t_detail.delivery_customer_id = customer_master_shipping.customer_id
            where
                bill_header.bill_header_id in ({$idCsv})
            order by
                -- テンプレート内の指定が優先されることに注意。
                bill_header.bill_number, delivery_no_forsort, line_no_forsort
        ";

        // queryを2種類用意。
        //    テンプレートA1セルで[[querymode:0]]が指定されていれば（もしくは未指定なら）前者、[[querymode:1]]なら後者。
        $query0 = $query_top . $query_bottom;                                       // 納品書小計なし・入金明細なし
        $query1 = $query_top . $query_subsum . $query_payingin . $query_bottom;     // 納品書小計あり・入金明細あり
        $query2 = $query_top . $query_subsum . $query_bottom;                       // 納品書小計あり・入金明細なし
        $query3 = $query_top . $query_payingin . $query_bottom;                     // 納品書小計なし・入金明細あり

        return array($query0, $query1, $query2, $query3);
    }

    // テンプレート情報
    protected function _getReportParam()
    {
        global $gen_db;

        $info = array();
        $info['reportTitle'] = _g("請求書");
        $info['report'] = "Bill";
        $info['pageKeyColumn'] = "bill_header_id";
        // 下の２行を有効にし、テンプレートのorderbyタグを [[orderby:請求書番号,受注取引先コード,...]] のようにすると
        // 受注取引先ごとに改ページするようになる。
        //$info['pageKeyColumn'] = "bill_header_id_customer_no";
        //$info['pageCountKeyColumn'] = "page_count_key";
        
        // SQLのfromで指定されているテーブルのリスト。
        // ここで指定されたテーブルのカラムはSQL selectとタグリストに自動追加される。
        $info['tables'] = array(
            //  本来、請求書のタグは請求書発行時に bill_xxx に記録された情報に限定するのが原則だが、
            //  ここに出ているマスタ関連のタグについてはそうではない。
            //  仕様としては不統一だが、ag.cgi?page=ProjectDocView&pid=1574&did=230601の要望などをふまえ、利便性を優先した。
            //  [[請求書_xxx]]タグは請求書発行時点のマスタ情報、[[マスタ名_xxx]]タグは印刷時点のマスタ情報を反映する。
            array("customer_master", false, ""),
            array("customer_master_shipping", true, ""),
            array("currency_master", false, ""),
            array("item_master", true, ""),
        );

        // タグリスト（この帳票固有のもの）
        $keyCurrency = $gen_db->queryOneValue("select key_currency from company_master");
        $info['tagList'] = array(
            array("●" . _g("請求書 ヘッダ")),
            array("請求書_請求書番号", _g("発行時に自動採番（任意指定はできない）"), "100"),
            array("●" . _g("請求書 ヘッダ（合計金額）")),
            array("請求書_前回ご請求高", _g("前回請求書の「今回ご請求高」"), "10000"),
            array("請求書_ご入金高", _g("今回請求期間中の入金額（入金登録画面）"), "10000"),
            array("請求書_繰越高", _g("「前回ご請求高」-「ご入金高」"), "0"),
            array("請求書_今回お買い上高", _g("明細金額の合計"), "20000"),
            array("請求書_合計消費税", _g("消費税額。課税されるかどうかは品目マスタの課税区分、および取引先マスタの取引通貨による"), "1000"),
            array("請求書_合計金額", _g("「今回お買い上高」+「今回消費税」"), "21000"),
            array("請求書_今回ご請求高", _g("「繰越高」+「合計金額」"), "30500"),
            array("請求書_締日", _g("請求書発行画面で指定した締日"), _g("2014-4-1締切分")),
            array("請求書_回収予定日", _g("締日と取引先マスタの回収サイクルから計算された回収予定日"), "2014-5-31"),
            array("請求書_受注取引先小計", _g("受注得意先小計"), "1000"),
            array("●" . _g("請求書 明細")),
            array("請求書_納品日", _g("明細行： 納品登録画面[納品日]"), "2014-3-31"),
            array("請求書_検収日", _g("明細行： 納品登録画面[検収日]"), "2014-3-31"),
            array("請求書_納品書番号", _g("明細行： 納品登録画面[納品書番号]"), "1000"),
            array("請求書_受注番号", _g("明細行： 受注登録画面[受注番号]"), "A100000"),
            array("請求書_客先注番", _g("明細行： 受注登録画面[客先注番]"), "C101"),
            array("請求書_受注行番号", _g("明細行： 受注登録画面[行番号]"), "1"),
            array("請求書_受注製番", _g("明細行： 受注登録画面[製番]"), "1000"),
            array("請求書_品目コード", _g("明細行： 納品登録画面[品目]。[[品目マスタ_品目コード]]タグは請求書印刷時点の品目マスタを反映するが、このタグは請求書発行時点のマスタを反映する。"), "code001"),
            array("請求書_品目名", _g("明細行： 納品登録画面[品目]。小計行では「小計」、入金行では入金種別を表示。[[品目マスタ_品目名]]タグは請求書印刷時点の品目マスタを反映するが、このタグは請求書発行時点のマスタを反映する。"), _g("テスト品目")),
            array("請求書_単位", _g("明細行： 品目マスタ[管理単位]。[[品目マスタ_管理単位]]タグは請求書印刷時点の品目マスタを反映するが、このタグは請求書発行時点のマスタを反映する。"), "kg"),
            array("請求書_数量", _g("明細行： 納品登録画面[今回納品数]"), "200"),
            array("請求書_単価", _g("明細行： 納品登録画面[納品単価]"), "100"),
            array("請求書_金額", _g("明細行： 金額（数量×単価）。小計行では小計の額、入金行では入金額を表示"), "20000"),
            array("請求書_納品書金額", _g("明細行： 金額 + 消費税"), "21000"),
            array("請求書_消費税", _g("明細行： 税額"), "1000"),
            array("請求書_消費税率", _g("明細行： 消費税率"), "5"),
            array("請求書_ロット番号", _g("明細行： 納品登録画面 明細行[ロット番号]"), "LOT-1"),
            array("請求書_納品備考", _g("明細行： 納品登録画面 明細行[備考]"), _g("納品の備考")),
            array("請求書_受注取引先コード", _g("受注登録画面 [得意先]"), "C001"),
            array("請求書_受注取引先名", _g("受注登録画面 [得意先]"), _g("受注取引先名")),
            array("●" . _g("ソート（orderbyタグ）用")),
            array("請求書_納品書番号（ソート用）", _g("納品書小計や入金明細を表示する場合（querymode指定がある場合）、orderbyタグは [[orderby:請求書_請求書番号,請求書_納品書番号（ソート用）,請求書_納品書行番号（ソート用）]] と指定する必要がある。 納品書を納品書番号順ではなく納品日順に並べたい場合、[[orderby:請求書_請求書番号,請求書_納品日（ソート用）,請求書_納品書番号（ソート用）,請求書_納品書行番号（ソート用）]] とする。"), "100"),
            array("請求書_納品書行番号（ソート用）", "", "100"),
            // このタグは、納品書小計や入金明細を表示する場合（querymode指定がある場合）に、納品書を納品日順に並べることができるようにするためのもの。
            // 上の「請求書_納品書番号（ソート用）」タグの説明文を参照。
            // アサヒ製作所の強い要望で実装した。ag.cgi?page=ProjectDocView&pid=1574&did=236490
            array("請求書_納品日（ソート用）", "", "2014-3-31"),
        );

        // 受注・納品のカスタム項目を追加。
        //  詳細は self::_getCustomColumn() のコメントを参照。
        $customTagSelectArr = self::_getCustomColumn(false);
        if (count($customTagSelectArr) > 0) {
            $info['tagList'][] = array("●" . _g("受注・納品 カスタム項目"));
            $info['tagList'] = array_merge($info['tagList'], $customTagSelectArr);
        }

        return $info;
    }

    // 印刷フラグの更新
    protected function _setPrintFlag($form)
    {
        // 帳票発行済みフラグ
        Logic_Bill::setBillPrintedFlag($form['idArr'], true);
        return;
    }
    
    private function _getCustomColumn($isSelect)
    {
        // 納品・受注のカスタム項目を追加。
        //  ユーシン精機様からの強い要望で、カスタム項目だけを追加した。（ag.cgi?page=ProjectDocView&pid=1574&did=210208）
        //  ・FWの仕組みを使用して納品・受注のすべての情報を追加するほうが簡単だが、
        //  　そうすると請求テーブル（bill_xxx）に保存されている情報と重なる部分が多くなり、
        //  　ユーザーが混乱する可能性がある。それでユーシン精機様から要望があったカスタム項目に限定した。
        //  ・本来、請求書のタグは請求書発行時に bill_xxx に記録された情報に限定するのが原則だが、
        //  　カスタム項目についてはそのような扱いが難しいため、ここは妥協した。
        //  　請求書が発行されると納品・受注データはロックされるため、問題はないはず。
        
        $customSelect = "";
        $customTagSelectArr = array();
        for ($i=1; $i<=4; $i++) {
            switch($i) {
                case 1: $table = "received_header"; break;
                case 2: $table = "received_detail"; break;
                case 3: $table = "delivery_header"; break;
                case 4: $table = "delivery_detail"; break;
            }
            $customColumnArr = Logic_CustomColumn::getCustomColumnParamByTableName($table);
            if ($customColumnArr) {
                $customColumnParamArr = $customColumnArr[1];
                foreach ($customColumnParamArr as $customCol => $customArr) {
                    $customSelect .= ",{$table}.{$customCol} as {$customArr[3]}";
                    $customTagSelectArr[] = array($customArr[3], $customArr[1], $customArr[1]);
                }
            }
        }
        
        return $isSelect ? $customSelect : $customTagSelectArr;
    }

}
