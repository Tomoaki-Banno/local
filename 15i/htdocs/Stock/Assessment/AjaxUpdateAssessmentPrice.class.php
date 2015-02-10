<?php

class Stock_Assessment_AjaxUpdateAssessmentPrice extends Base_AjaxBase
{

    function _execute(&$form)
    {
        global $gen_db;

        if (!Gen_String::isDateString(@$form['date']))
            return;

        $gen_db->begin();

        // ありえないはずだが、今回基準日のデータが履歴に記録されているなら削除しておく
        $query = "delete from stock_price_history where assessment_date = '{$form['date']}'";
        $gen_db->query($query);

        // 在庫数
        Logic_Stock::createTempStockTable(
                $form['date']
                , null      // 品目別に取得
                , 'sum'     // 製番別にしない
                , 'sum'     // ロケ別にしない
                , 'sum'     // Lot別にしない
                , false     // 有効在庫は取得しない
                , true      // サプライヤー在庫を含める（13iまでは含めなかったが、ag.cgi?page=ProjectDocView&pid=1574&did=207546 により変更）
                , false     // use_plan の全期間分差し引き。無関係
        );

        $dataExist = false;

        // -----------------------------
        //　発注品
        // -----------------------------

        if (isset($form['check1']) && $form['check1'] == "true") {
            $query = "select stock_price_assessment from company_master";
            $res = $gen_db->queryOneRowObject($query);

            // t_temp_assessment に在庫評価単価データを作成
            if ($res->stock_price_assessment != "1") {
                // 最終仕入原価法
                $query = "
                create temp table t_temp_assessment as
                select
                    t2.item_id
                    ,t2.accepted_price
                    ,coalesce(temp_stock.logical_stock_quantity,0) as stock_quantity
                from
                    (select
                        order_detail.item_id
                        ,MAX(accepted.accepted_price) as accepted_price
                    from
                        accepted
                        inner join order_detail on accepted.order_detail_id = order_detail.order_detail_id
                        inner join (
                            select
                                order_detail.item_id
                                ,MAX(accepted_date) as accepted_date
                            from
                                accepted
                                inner join order_detail on accepted.order_detail_id = order_detail.order_detail_id
                                inner join order_header on order_detail.order_header_id = order_header.order_header_id
                            where
                                /* 最終仕入原価法の計算では受入単価0を除外する。*/
                                accepted.accepted_price <> 0
                                and accepted.accepted_quantity > 0
                                and accepted_date <= '{$form['date']}'::date
                                /* 2010では、外製を計算に含めないようにした（注文のみ計算される）。*/
                                /* 外製は支給品目分の金額も含めなければ正確な費用にならないため。*/
                                /* 支給品目の金額を含めようとすると、原価のような計算が必要になる。*/
                                and classification = 1
                            group by
                                order_detail.item_id
                            ) as t1
                            on order_detail.item_id = t1.item_id
                            and accepted.accepted_date = t1.accepted_date
                        inner join item_master on order_detail.item_id = item_master.item_id
                        inner join item_order_master on item_master.item_id = item_order_master.item_id
                            and item_order_master.line_number = 0
                    where
                        /* 13iまでは 現在の手配区分にかかわらず、発注データのある品目はすべて計算対象としていた。*/
                        /* しかし15iから内製・外製も更新対象となったことに伴い、バッティング回避のため、現在の手配区分が「発注」の品目だけを対象とするようにした */
                        item_order_master.partner_class = 0
                    group by
                        order_detail.item_id
                    ) as t2
                    left join temp_stock on t2.item_id = temp_stock.item_id;
                ";
                $gen_db->query($query);
            } else {
                // 総平均法

                // 発注品の最終在庫評価単価更新日を取得
                $query = "
                select
                    max(assessment_date)
                from
                    stock_price_history
                    inner join item_order_master on stock_price_history.item_id = item_order_master.item_id
                        and item_order_master.line_number = 0
                where
                    item_order_master.partner_class = 0
                ";
                $lastDate = $gen_db->queryOneValue($query);
                if ($lastDate) {
                    $from = date('Y-m-d', strtotime($lastDate . " +1 days"));
                } else {
                    $from = "1970-01-01";
                    $lastDate = $from;
                }

                //　　(前回更新時の評価単価 × 在庫数 + 前回更新時の基準日から今回基準日までの仕入[発注受入]額)
                //　　　÷ (前回更新時の在庫数 + 前回更新時の基準日から今回基準日までの仕入[発注受入]数)
                //
                //  期間内に受入がある品目だけを更新。それ以外の品目は、これまでの評価単価のまま。
                //
                //  [rev. 20150113]
                //  期間内に受入が無い品目もデータを生成する。
                //  在庫評価単価の更新日時点の在庫数を起点として次回の計算に含めるため。
                //
                $query = "
                create temp table t_temp_assessment as
                select
                    order_detail.item_id
                    ,coalesce(MAX(temp_stock.logical_stock_quantity),0) as stock_quantity
                    ,gen_round_precision(
                      (coalesce(MAX(" . (@$form['type'] == "1" ? "item_master.stock_price" : "stock_price_history.stock_price") .
                        " * stock_price_history.stock_quantity),0) + coalesce(SUM(accepted.accepted_amount),0))
                       /
                      (coalesce(MAX(stock_price_history.stock_quantity),0)
                      + coalesce(SUM(accepted.accepted_quantity),0))
                      , MAX(company_master.assessment_rounding), MAX(company_master.assessment_precision)) as accepted_price
                from
                    accepted
                    inner join order_detail on accepted.order_detail_id = order_detail.order_detail_id
                    inner join order_header on order_detail.order_header_id = order_header.order_header_id
                    inner join company_master on 1=1
                    inner join item_master on order_detail.item_id = item_master.item_id
                    inner join item_order_master on item_master.item_id = item_order_master.item_id
                        and item_order_master.line_number = 0
                    left join (select item_id, max(assessment_date) as max_assessment_date from stock_price_history group by item_id) as t_max_assessment
                        on order_detail.item_id = t_max_assessment.item_id
                    left join stock_price_history on order_detail.item_id = stock_price_history.item_id
                        and stock_price_history.assessment_date = t_max_assessment.max_assessment_date
                    left join temp_stock on order_detail.item_id = temp_stock.item_id
                where
                    /* 以前は受入単価0以外の受入のみ対象としていたが、評価単価の計算に受入単価0も含めるようにした。*/
                    /* accepted.accepted_price <> 0 */
                    /* 以前は数量0以上の受入のみ対象としていたが、それだと赤伝が考慮されないのでマイナスも対象とした。ただし0割り回避のため0は排除する */
                    accepted.accepted_quantity <> 0
                    and accepted_date between '{$from}'::date and '{$form['date']}'::date
                    /* 2010以降、外製を計算に含めないようにした（注文のみ計算される）。理由は最終仕入原価法のSQL内のコメントを参照 */
                    and classification = 1
                    /* 13iまでは 現在の手配区分にかかわらず、発注データのある品目はすべて計算対象としていた。*/
                    /* しかし15iから内製・外製も更新対象となったことに伴い、バッティング回避のため、現在の手配区分が「発注」の品目だけを対象とするようにした */
                    and item_order_master.partner_class = 0
                group by
                    order_detail.item_id
                having
                    /* 0割り回避 */
                    coalesce(MAX(stock_price_history.stock_quantity),0)
                      + coalesce(SUM(accepted.accepted_quantity),0) <> 0
                ;

                /* 受入の無い品目のデータ生成 */
                insert into t_temp_assessment (
                    item_id
                    ,stock_quantity
                    ,accepted_price
                )
                select
                    temp_stock.item_id
                    ,coalesce(temp_stock.logical_stock_quantity,0) as stock_quantity
                    ," . (@$form['type'] == "1" ? "item_master.stock_price" : "stock_price_history.stock_price") . " as accepted_price
                from
                    temp_stock
                    inner join item_master on temp_stock.item_id = item_master.item_id
                    inner join item_order_master on item_master.item_id = item_order_master.item_id
                        and item_order_master.line_number = 0
                    left join (select item_id, max(assessment_date) as max_assessment_date
                        from stock_price_history group by item_id) as t_max_assessment on temp_stock.item_id = t_max_assessment.item_id
                    left join stock_price_history on temp_stock.item_id = stock_price_history.item_id
                        and t_max_assessment.max_assessment_date = stock_price_history.assessment_date
                where
                    /* しかし15iから内製・外製も更新対象となったことに伴い、バッティング回避のため、現在の手配区分が「発注」の品目だけを対象とするようにした */
                    item_order_master.partner_class = 0
                    /* 前述のクエリで更新されなかった品目のデータを生成 */
                    and temp_stock.item_id not in (select item_id from t_temp_assessment group by item_id)
                    " . (@$form['type'] == "1" ? "" : " and coalesce(stock_price_history.stock_price,0) <> 0") . "
                ;
                ";
                $gen_db->query($query);
            }

            $query = "select 1 from t_temp_assessment";
            if ($gen_db->existRecord($query)) {
                // 更新処理
                $query = "
                /* 品目マスタ 在庫評価単価の更新 */
                update
                    item_master
                set
                    stock_price = (
                        select accepted_price
                        from t_temp_assessment
                        where t_temp_assessment.item_id = item_master.item_id
                    )
                where
                    item_id in (
                        select item_id
                        from t_temp_assessment
                    );

                /* 履歴データの記録 */
                insert into stock_price_history (
                    assessment_date
                    ,item_id
                    ,stock_price
                    ,stock_quantity
                )
                select
                    '{$form['date']}' as assessment_date
                    ,item_id
                    ,accepted_price
                    ,stock_quantity
                from
                    t_temp_assessment;
                ";
                $gen_db->query($query);

                $dataExist = true;
            }
        }

        // -----------------------------
        //　内製品・外注品
        // -----------------------------
        //  標準原価を在庫評価単価とする。
        //  標準原価は発注品の在庫評価単価をベースに計算されるため、発注品より後に計算する必要がある。

        if (isset($form['check2']) && $form['check2'] == "true") {
            // 集計対象品目と在庫数の取得
            $query = "
            select
                item_master.item_id
                ,coalesce(temp_stock.logical_stock_quantity,0) as stock_quantity
            from
                item_master
                left join item_order_master on item_master.item_id = item_order_master.item_id
                    and item_order_master.line_number = 0
                left join temp_stock on item_master.item_id = temp_stock.item_id
            where
                item_order_master.partner_class in (1,2,3) /* 内製・外注 */
            order by
                llc desc
            ";
            $arr = $gen_db->getArray($query);

            if (is_array($arr)) {
                foreach ($arr as $row) {
                    $cost = Logic_BaseCost::calcStandardBaseCost($row['item_id'], 1, false, $form['date']);

                    // 更新処理
                    $query = "
                    /* 品目マスタ 在庫評価単価の更新 */
                    update item_master set stock_price = '{$cost}' where item_id = '{$row['item_id']}';

                    /* 履歴データの記録 */
                    insert into stock_price_history (
                        assessment_date
                        ,item_id
                        ,stock_price
                        ,stock_quantity
                    ) values (
                        '{$form['date']}'
                        ,'{$row['item_id']}'
                        ,{$cost}
                        ,'{$row['stock_quantity']}'
                    );
                    ";
                    $gen_db->query($query);
                }

                $dataExist = true;
            }
        }

        // -----------------------------
        //　共通処理
        // -----------------------------

        if ($dataExist) {
            // データアクセスログ
            $log = _g("基準日") . _g("：") . $form['date'];
            if (isset($form['check1']) && $form['check1'] == "true") {
                $cat = ($res->stock_price_assessment != "1" ? _g("最終仕入原価法") : _g("総平均法"));
                $log .= ",  [" . _g("発注品") . "] " . _g("評価法") . _g("：") . $cat;
            }
            if (isset($form['check2']) && $form['check2'] == "true") {
                $log .= ",  [" . _g("内製品・外注品") . "]";
            }
            Gen_Log::dataAccessLog(_g("在庫評価単価"), _g("更新"), $log);

            // 通知メール
            $title = ("在庫評価単価の更新");
            $body = _g("在庫評価単価が更新されました。") . "\n\n"
                    . "[" . _g("更新日時") . "] " . date('Y-m-d H:i:s') . "\n"
                    . "[" . _g("更新者") . "] " . $_SESSION['user_name'] . "\n\n"
                    . "[" . _g("基準日") . "] " . $form['date'] . "\n"
                    . "";
            Gen_Mail::sendAlertMail('stock_assessment_update', $title, $body);

            $gen_db->commit();

            return
                    array(
                        "result" => "success"
            );
        } else {
            return
                    array(
                        "result" => "nodata"
            );
        }
    }

}
