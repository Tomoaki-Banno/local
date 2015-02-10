<?php

class Logic_BaseCost
{

    //************************************************
    // 標準原価計算
    //************************************************
    // 構成展開して原価計算を行う。原価は実績ではなくマスタベース。
    // 見積書および原価リスト(引当分の原価)で使用
    // ※原価表の受注製造分の原価計算はこのファンクションではなく実績値を使用
    //
    // 原価の計算方法
    //   標準手配先が
    //      「内製」                   数量 * (品目マスタ「標準加工時間(分)」 * 品目マスタ「工賃(￥/分)」 + 品目マスタ「外製単価(￥)」+ 品目マスタ「固定経費(￥)」)
    //      「外製 支給あり」           数量 * (品目マスタ「購入単価1」)
    //      「発注」or「外製 支給なし」 数量 * (品目マスタ「在庫評価単価」)
    //
    // 第3引数：　工程別に計算するか
    //      false：  デフォルト。全工程の原価合計が単一値として返る
    //      true：   親品目の工程(process_id)別の原価を配列で返す。子品目原価は最終工程につく。

    static function calcStandardBaseCost($itemId, $quantity, $processMode = false, $stockDate = false)
    {
        global $gen_db;

        // 構成表展開。
        // temp_bom_expand に構成ツリー内の品目と員数（親品目ではなく、最上位品目からみた数）が入る
        if (!Logic_Bom::expandBom($itemId, $quantity, false, false, true)) {
            return 0;
        }

        // 工程別モードでは、親品目の最終工程を取得
        if ($processMode) {
            $query = "select partner_class from item_order_master where item_id = '{$itemId}' and line_number = 0";
            if ($gen_db->queryOneValue($query) == "3") {
                // 内製
                $query = "
                select
                    process_id
                from
                    item_process_master
                where
                    machining_sequence = (select max(machining_sequence) from item_process_master where item_id = '{$itemId}')
                    and item_id = '{$itemId}'
                ";

                $finalProcessId = $gen_db->queryOneValue($query);
                if (!is_numeric($finalProcessId)) {
                    $finalProcessId = -1;
                }
            } else {
                // 外製・注文は工程を拾わないようにする（process_id = -1とする）
                $finalProcessId = -1;
            }
        }
        
        // 日付が指定されている場合は、在庫評価単価の履歴を取得する。
        //  ちなみに履歴を使用するのは指定された品目（親品目）のみ。
        //  厳密には、「親品目の履歴はないが子品目はある」という場合、計算時に子品目の履歴を使用すべきだろう。
        //  しかしそうすると標準原価計算がかなり複雑になる（履歴を使用すると、それ以下の構成品目の原価を排除
        //  しなければならないため）。
        //  それで履歴は親品目についてのみ使用するようにしている。
        $useHistory = false;
        $isDate = Gen_String::isDateString($stockDate);
        if ($isDate) {
            Logic_Stock::createTempStockPriceTable($stockDate, $itemId);
            $query = "select stock_price, use_history from temp_stock_price";
            $obj = $gen_db->queryOneRowObject($query);
            $stockPrice = $obj->stock_price;
            $useHistory = ($obj->use_history == "t" && is_numeric($stockPrice));
        }
        
        if ($useHistory) {
            // 指定日時点の在庫評価単価の履歴データが存在する場合、その値を用いる。
            if ($processMode) {
                $res = $gen_db->getArray("select {$finalProcessId} as process_id ,{$stockPrice} as base_cost");
            } else {
                $res = $stockPrice;
            }
        } else {
            // 標準原価を取得
            // このロジックは、Manufacturing_BaseCost_StandardCostList の SQL と合わせておく必要がある。
            $query = "
            select
                " . ($processMode ? "coalesce(case when temp_bom_expand.item_id = '{$itemId}' then item_process_master.process_id
                    else {$finalProcessId} end, -1) as process_id, " : "") . "
               coalesce(SUM(
                    case
                        when item_order_master.partner_class = 3 then
                            /* 内製 */
                            item_process_master.default_work_minute * item_process_master.charge_price
                                + coalesce(item_process_master.subcontract_unit_price,0) + item_process_master.overhead_cost
                        when item_order_master.partner_class = 2 then
                            /* 外製 支給あり */
                            item_order_master.default_order_price
                        else
                            /* 発注 or 外製 支給なし */
                            item_master.stock_price
                    end
                    * cast(quantity as numeric)),0) as base_cost
            from
                temp_bom_expand
                inner join item_master on temp_bom_expand.item_id = item_master.item_id
                left join item_order_master on item_master.item_id = item_order_master.item_id and line_number = 0
                left join item_process_master on item_master.item_id = item_process_master.item_id
                    and partner_class = 3    /* 標準手配先が内製以外なのに工程が登録されていたとき、発注価格が膨らんでしまわぬよう、この条件が必要 */
                " // 工程別モードでは、最上位品目の工程でグループ化。子品目(item_id <> '$itemId')はすべて最終工程に入れる
                . ($processMode ? " group by coalesce(case when temp_bom_expand.item_id = '{$itemId}' then item_process_master.process_id
                    else {$finalProcessId} end, -1) " : "") . "
            ";

            if ($processMode) {
                $res = $gen_db->getArray($query);
            } else {
                $res = Gen_String::nz($gen_db->queryOneValue($query));
            }
        }

        return $res;
    }

    //************************************************
    // 標準原価合計 (内製品/外製品のみ）
    //************************************************
    // 指定された品目および品目グループに基づき原価を計算。
    // 子品目を指定された範囲内で集計する。

    static function calcStandardBaseCostTotal($searchItem, $matchMode, $groupId, $partnerClass, $orderClass)
    {
        global $gen_db;

        $where = "";
        $searchWord = trim($searchItem);

        // エスケープ
        //    like では「_」「%」がワイルドカードとして扱われる
        $searchWord = str_replace('%', '\\\\%', str_replace('_', '\\\\_', $searchWord));

        // 品目コード/名
        // 「gen_search_match_mode_」はsmarty_function_gen_search_controlで定義
        if (isset($searchWord) && strlen($searchWord) > 0) {
            switch (@$matchMode) {
                case "1":   // 前方一致
                    $where .= " and (item_code ilike '{$searchWord}%' or item_name ilike '{$searchWord}%')";
                    break;
                case "2":   // 後方一致
                    $where .= " and (item_code ilike '%{$searchWord}' or item_name ilike '%{$searchWord}')";
                    break;
                case "3":   // 完全一致
                    $where .= " and (item_code ilike '{$searchWord}' or item_name ilike '{$searchWord}')";
                    break;
                case "4":   // 含まない
                    $where .= " and (item_code not ilike '%{$searchWord}%' and item_name not ilike '%{$searchWord}%')";
                    break;
                case "5":   // で始まらない
                    $where .= " and (item_code not ilike '{$searchWord}%' and item_name not ilike '{$searchWord}%')";
                    break;
                case "6":   // で終わらない
                    $where .= " and (item_code not ilike '%{$searchWord}' and item_name not ilike '%{$searchWord}')";
                    break;
                case "9":   // 正規表現　-> 現在未使用。不正なパターンを指定されたときSQLエラーになる問題の対処が難しいため
                    $where .= " and (item_code ~* '{$searchWord}' and item_name ~* '{$searchWord}')";
                    break;
                default:    // 部分一致（デフォルト）
                    $where .= " and (cast(item_code as text) ilike '%{$searchWord}%' or cast(item_name as text) ilike '%{$searchWord}%')";
                    break;
            }
        }
        // 品目グループ
        if (isset($groupId) && is_numeric($groupId)) {
            $where .= " and (item_group_id = {$groupId} or item_group_id_2 = {$groupId} or item_group_id_3 = {$groupId})";
        }
        // 手配区分
        if (isset($partnerClass) && is_numeric($partnerClass)) {
            $where .= " and (partner_class = {$partnerClass})";
        }
        // 管理区分
        if (isset($orderClass) && is_numeric($orderClass)) {
            $where .= " and (order_class = {$orderClass})";
        }

        // 検索対象が存在しない場合
        $existCond = true;
        if ($where == "") {
            $where = " and 1=0";
            $existCond = false;
        }

        // 集計テーブルの作成
        $query = "create temp table temp_standard_cost_total (item_id int, item_cost numeric)";
        $gen_db->query($query);

        // 集計対象品目の取得
        $query = "
        select
            item_id
        from
            item_master as t01
            left join (
                select
                    item_id as id,
                    partner_class
                from
                    item_order_master
                where
                    line_number=0
                ) as t02 on t01.item_id = t02.id
        where
            1=1
            {$where}
        ";
        $arr = $gen_db->getArray($query);

        if (is_array($arr)) {
            foreach ($arr as $row) {
                $cost = 0;
                $cost = self::calcStandardBaseCost($row['item_id'], 1);

                $query = "
                insert into temp_standard_cost_total (
                    item_id,
                    item_cost
                ) values (
                    '{$row['item_id']}',
                    {$cost}
                )
                ";
                $gen_db->query($query);
            }
        }

        return $existCond;
    }

    //************************************************
    // 原価表（画面/レポート/excel）用 データ取得
    //************************************************
    // テンポラリテーブル temp_base_cost に結果が入る
    //
    // 受注製番/品目/工程 単位で計算される。
    //  製番品目：
    //      オーダー＆受入済みの分は・・
    //          実績原価（購入品：購入価格、製造品：(製造時間 * チャージ料) + (外製単価 * 製造数量) + (固定経費 * 製造数量)）。
    //          工程があれば工程別に表示される。
    //          子品目があれば子品目も表示される。
    //      未オーダー・未受入・在庫引当の分は・・
    //          標準原価（購入品：品目マスタ「在庫評価単価」、製造品：品目マスタ (標準製造時間 * チャージ料 + 外製単価 + 固定経費) ）* 必要数量
    //          工程があれば工程別に表示されるが、標準原価は最終工程だけに表示される。
    //          子品目は表示されない。標準原価の中に子品目分まで含まれている。
    //          一部受入済の場合は子品目が表示されるが、子品目の「在庫使用数」に表示されるのは受入済数に
    //          対応する分のみ。
    //      ※13iの「実績モード」では、未オーダー・未受入・在庫引当の分を含めない。
    //
    //  MRP品目： すべて標準原価（購入品：品目マスタ「在庫評価単価」、製造品：品目マスタ (標準製造時間 * チャージ料 + 外製単価 + 固定経費) ）* 必要数量
    //          工程があれば工程別に表示されるが、標準原価は最終工程だけに表示される。
    //          子品目は表示されない。標準原価の中に子品目分まで含まれている。
    //
    // 履歴：
    //  07i
    //      ・「オール製番で部品在庫なし」の場合のみ正しく原価計算できた。
    //         MRP品目は計算対象外だった（07iはハイブリッドではなかったので関係なし）
    //         標準原価が計算されるのは受注品目だけで、子品目の在庫使用分は無視されていた。
    //  08i
    //      ・ハイブリッド化に伴い、MRP品目分も計算に含まれるようになった（標準原価で）。
    //      ・子品目の在庫引当分も計算に含まれるようになった（標準原価で）。
    //      ・レポートは「製造発注分」と「在庫引当分」を同一行に表示するようになった。
    //  09i
    //      ・工程別の原価を表示するようになった。
    //      ・標準原価を、品目マスタ「在庫評価単価」で計算するようになった。
    //        08iまでは品目マスタ「標準手配先」の「標準購入単価」から計算していた
    //  10i
    //      ・コード整理とコメント改善
    //      ・(rev.20101210) 構成に含まれていない品目のオーダーでも製番が一致していれば原価に含めるようにした。
    //        10iから製造指示書や注文書に自由に受注製番を付与できるようになったことに伴う変更。
    //　13i
    //      ・実績モードの追加
    //      　従来からの動作（予想モード）では、未発行や未完了のオーダー、あるいは在庫引当の分は標準原価で計算される。
    //      　（まだ実績値がわからないので、標準原価で予想をたてておくということ）
    //      　一方、新設の実績モードでは、製番品目のうち、未発行や未完了のオーダー、あるいは在庫引当の分は含められない。
    //      　製造実績や受入が登録されたものだけを含める。
    //      　財務諸表用など、特定の締日で切った実績原価を知りたい場合のためのモード。
    //  15i
    //      ・製造経費等の反映
    //      
    // 取得データが多いときは時間がかかるので、なるべく絞り込み条件を指定して使用すること。
    // 以前は取得データを1000件に制限していたが、呼び出し側で絞り込みができない、レポート系の画面も1000件分しか
    // 原価の計算が行われない、という問題があったため、制限をはずした。
    //
    static function getBaseCostReportData($seiban, $receivedNumber, $receivedDateFrom, $receivedDateTo, $deliveryDateFrom, $deliveryDateTo,
            $inspectionDateFrom, $inspectionDateTo, $customerId, $workerId, $sectionId, $costType, $deliveryType)
    {
        global $gen_db;

        $where = "";
        if ($seiban != "") {
            $where .= " and received_detail.seiban= '{$seiban}'";
        }
        if ($receivedNumber != "") {
            $where .= " and received_number= '{$receivedNumber}'";
        }
        if (isset($receivedDateFrom) && Gen_String::isDateString($receivedDateFrom)) {
            $where .= " and received_header.received_date >= '{$receivedDateFrom}'::date";
        }
        if (isset($receivedDateTo) && Gen_String::isDateString($receivedDateTo)) {
            $where .= " and received_header.received_date <= '{$receivedDateTo}'::date";
        }
        if (isset($customerId) && is_numeric($customerId)) {
            $where .= " and received_header.customer_id= '{$customerId}'";
        }
        if (isset($workerId) && is_numeric($workerId)) {
            $where .= " and received_header.worker_id= '{$workerId}'";
        }
        if (isset($sectionId) && is_numeric($sectionId)) {
            $where .= " and received_header.section_id= '{$sectionId}'";
        }
        if ($deliveryType == "1") {
            $where .= "  and not (not(delivery_completed) or delivery_completed is null)";
        }
        if ($deliveryType == "2") {
            $where .= "  and (not(delivery_completed) or delivery_completed is null)";
        }
        if (isset($deliveryDateFrom) && Gen_String::isDateString($deliveryDateFrom)) {
            $where .= " and received_detail.received_detail_id in
                (select received_detail_id from delivery_detail
                inner join delivery_header on delivery_detail.delivery_header_id = delivery_header.delivery_header_id
                where delivery_date >= '{$deliveryDateFrom}'::date)";
        }
        if (isset($deliveryDateTo) && Gen_String::isDateString($deliveryDateTo)) {
            $where .= " and received_detail.received_detail_id in
                (select received_detail_id from delivery_detail
                inner join delivery_header on delivery_detail.delivery_header_id = delivery_header.delivery_header_id
                where delivery_date <= '{$deliveryDateTo}'::date)";
        }
        if (isset($inspectionDateFrom) && Gen_String::isDateString($inspectionDateFrom)) {
            $where .= " and received_detail.received_detail_id in
                (select received_detail_id from delivery_detail
                inner join delivery_header on delivery_detail.delivery_header_id = delivery_header.delivery_header_id
                where inspection_date >= '{$inspectionDateFrom}'::date)";
        }
        if (isset($inspectionDateTo) && Gen_String::isDateString($inspectionDateTo)) {
            $where .= " and received_detail.received_detail_id in
                (select received_detail_id from delivery_detail
                inner join delivery_header on delivery_detail.delivery_header_id = delivery_header.delivery_header_id
                where inspection_date <= '{$inspectionDateTo}'::date)";
        }

        $query = "
        /* 高速化のためこの部分だけテンポラリテーブルに切り出し */
        /* 以前はメインSQL内に埋め込んでいたが、これを切り出したことでデータが多いときの処理が大幅に高速化された */
        create temp table temp_order_child as
        select
            child_item_id
            ,t_detail_2.seiban
        from
            order_child_item
            inner join
                order_detail as t_detail_2
            on  order_child_item.order_detail_id = t_detail_2.order_detail_id;

        create index temp_order_child_index1 on temp_order_child (seiban);
        

        /* メイン */
        create temp table temp_base_cost as
        select

        /* ヘッダ項目（見出し項目） */
            received_detail.seiban as seiban
            ,received_detail.item_id
            ,item_master.item_code
            ,item_master.item_name
            ,item_master.measure
            ,received_detail.received_detail_id
            ,received_header.received_number
            ,customer_master.customer_id
            ,customer_master.customer_name
            ,worker_master.worker_code
            ,worker_master.worker_name
            ,section_master.section_code
            ,section_master.section_name
            ,received_detail.received_quantity
            ,coalesce(t_delivery.delivery_quantity,0) as delivery_quantity
            ,coalesce(t_delivery_period.delivery_period_quantity,0) as delivery_period_quantity
            ,case when received_detail.delivery_completed then 0
                else coalesce(received_detail.received_quantity,0) - coalesce(t_delivery.delivery_quantity,0) end as remained_quantity
            /* 納品済分は納品単価、未納品分は受注単価で計算 */
            ,(coalesce(received_quantity,0)-coalesce(delivery_quantity,0)) * product_price
                + coalesce(delivery_amount,0) as received_sum
            ,received_header.received_date
            ,received_detail.dead_line

        /* 明細 */
            ,t_required.item_id as detail_item_id
            ,t_req_item.item_code as detail_item_code
            ,t_req_item.item_name as detail_item_name
            ,t_req_item.measure as detail_measure
            ,case when t_required.item_id = received_detail.item_id then ''
               else t_req_item.item_code end as item_code_for_order  /* 並べ替え用。受注品目を先頭に持ってくる */
            ,t_req_item.order_class as detail_order_class_number
            ,case when t_req_item.order_class = 0 then '" . _g("製番") . "' else '" . _g("MRP") . "' end as detail_order_class

            ,t_detail.process_id
            ,t_detail.process_code
            ,t_detail.process_name
            ,t_detail.machining_sequence
            ,(t_detail.machining_sequence + 1) as machining_sequence_show           /* 工順 */
            ,coalesce(t_detail.charge_price,0) as charge_price                      /* 工賃 */

            /* 在庫使用分（標準原価）※「実績モード」の場合はあとでクリアされる */
            ,case when t_required.required_qty >= (coalesce(t_detail.achievement_qty,0) + coalesce(t_detail.accepted_qty,0)) then
               t_required.required_qty
                   - coalesce(t_detail.achievement_qty,0)
                   - coalesce(t_detail.accepted_qty,0)
             else 0 end as detail_hikiate_qty                                       /* 在庫使用数。マイナスにならないようにした。過製造・過受入した場合に原価が小さくなってしまうのを避けるため */
            ,cast(0 as numeric) as detail_standard_base_cost                        /* 標準原価（あとで計算） */
            ,cast(0 as numeric) as detail_hikiate_amount                            /* 在庫使用分金額（あとで計算） */

            /* 製造実績 */
            ,t_detail.achievement_qty as detail_achievement_qty                     /* 製造数量 */
            ,t_detail.work_minute as detail_work_minute                             /* 製造時間（分） */
            ,case when coalesce(t_detail.achievement_qty,0) = 0 then null
               else (t_detail.achievement_amount / t_detail.achievement_qty) end
               as detail_process_amount                                             /* 製造単価（工賃） */
            ,t_detail.achievement_cost_1 as detail_achievement_cost_1               /* 製造経費1 */
            ,t_detail.achievement_cost_2 as detail_achievement_cost_2               /* 製造経費2 */
            ,t_detail.achievement_cost_3 as detail_achievement_cost_3               /* 製造経費3 */
            ,t_detail.achievement_amount as detail_achievement_amount               /* 製造原価 */

            /* 購入実績 */
            ,t_detail.accepted_qty as detail_accepted_qty                           /* 購入数量 */
            ,round(t_detail.order_price / case when coalesce(t_detail.accepted_qty,0) = 0 then 1
                else t_detail.accepted_qty end,2) as detail_unit_price              /* 購入単価 */
            ,t_detail.order_price as detail_order_amount                            /* 購入金額 */
            ,coalesce(t_detail.order_price,0) as detail_order_base_cost             /* 購入原価 */
            ,case when t_detail.accepted_qty is not null then t_detail.customer_name
                else '' end as partner_name                                         /* 発注先 */

            /* 出庫金額 */
            ,t_inout.inout_amount
            ,t_inout.inout_quantity
            
            /* 原価・粗利 */
            /* とりあえず引当分を除いた額を計算しておく。引当なしの行はあとで計算しなくてすむように */
            ,coalesce(t_detail.achievement_amount,0) + coalesce(t_detail.order_price,0) + coalesce(t_inout.inout_amount,0)
                as detail_base_cost                                                 /* 行合計原価（あとで引当分を加算） */

            ,cast(0 as numeric) as base_cost                                        /* 製番合計原価（あとで計算） */
            ,cast(0 as numeric) as profit                                           /* 粗利（あとで計算） */

        from
            /* ----- ヘッダ関係 ----- */

            received_detail
            inner join received_header on received_header.received_header_id=received_detail.received_header_id
            inner join item_master on received_detail.item_id = item_master.item_id
            left join customer_master on received_header.customer_id = customer_master.customer_id
            left join worker_master on received_header.worker_id = worker_master.worker_id
            left join section_master on received_header.section_id = section_master.section_id
            left join (
                select
                    received_detail_id,
                    sum(delivery_quantity) as delivery_quantity,
                    sum(delivery_quantity * delivery_price) as delivery_amount
                from
                    delivery_detail
                group by
                    received_detail_id
                ) as t_delivery
                on received_detail.received_detail_id = t_delivery.received_detail_id
            left join (
                select
                    received_detail_id,
                    sum(delivery_quantity) as delivery_period_quantity,
                    sum(delivery_quantity * delivery_price) as delivery_period_amount
                from
                    delivery_detail
                    inner join delivery_header on delivery_detail.delivery_header_id = delivery_header.delivery_header_id
                where 1=1
                    " . ($deliveryDateFrom != "" ? " and delivery_date >= '{$deliveryDateFrom}'::date" : "") . "
                    " . ($deliveryDateTo != "" ? " and delivery_date <= '{$deliveryDateTo}'::date" : "") . "
                    " . ($inspectionDateFrom != "" ? " and inspection_date >= '{$inspectionDateFrom}'::date" : "") . "
                    " . ($inspectionDateTo != "" ? " and inspection_date <= '{$inspectionDateTo}'::date" : "") . "
                group by
                    received_detail_id
                ) as t_delivery_period
                on received_detail.received_detail_id = t_delivery_period.received_detail_id

            /* ----- 明細関係 ----- */
            
            /* 品目ごとの必要数の取得 */
            left join (
                /*  受注品目分（受注数） */
                select
                    seiban, item_id, received_quantity as required_qty
                from
                    received_detail

                /*  子品目分 */
                UNION   /* UNION ALL ではない */
                select
                    /* order_detail_quantityではなくaccepted_quanitty。受入済分に対応する子品目のみを必要数として算入。 */
                    seiban, child_item_id as item_id, sum(accepted_quantity * quantity) as required_qty
                from
                    order_detail
                    inner join order_child_item on order_detail.order_detail_id = order_child_item.order_detail_id
                where
                    coalesce(seiban,'') <> ''
                group by
                    seiban, child_item_id

                /*  「受注品目でもオーダー子品目でもないオーダー品目」を必要数0でリストアップ。 */
                /*   構成にない品目のオーダーを製番に結びつけた（製造指示書や注文書の発行時に画面上で製番を付与）場合の */
                /*   製造注文実績数を含められるようにするため。 */
                UNION   /* UNION ALL ではない */
                select
                    seiban, item_id, 0 as required_qty
                from
                    order_detail as t_detail_1
                where
                    coalesce(seiban,'') <> ''
                    and item_id not in (select item_id from received_detail where t_detail_1.seiban = received_detail.seiban)
                    and item_id not in (select child_item_id from temp_order_child where t_detail_1.seiban = temp_order_child.seiban)
                group by
                    seiban, item_id
                    
                /*  上記に含まれていない出庫品目を必要数0でリストアップ。 */
                UNION   /* UNION ALL ではない */
                select
                    seiban, item_id, 0 as required_qty
                from
                    item_in_out as t_inout_1
                where
                    coalesce(seiban,'') <> ''
                    and coalesce(stock_amount,0) <> 0
                    and item_id not in (select item_id from received_detail where t_inout_1.seiban = received_detail.seiban)
                    and item_id not in (select item_id from order_detail where t_inout_1.seiban = order_detail.seiban)
                group by
                    seiban, item_id

            ) as t_required on received_detail.seiban = t_required.seiban
            
            left join item_master as t_req_item on t_required.item_id = t_req_item.item_id

            /* オーダー関連情報の取得1 */
            /*  オーダー関連のうち、非数値項目はここで取得する。 */
            /*  また数値項目のうち achievement/accepted にカラムがある項目もここで取得する。*/
            /*  一方、数値項目でなおかつorder_detailに存在する項目は、ここではなく次のjoinで取得する。*/
            left join (
                select
                    order_detail.seiban,
                    order_detail.item_id,
                    coalesce(order_process.process_id,-1) as process_id,
                    max(process_master.process_code) as process_code,
                    max(process_master.process_name) as process_name,
                    max(order_process.machining_sequence) as machining_sequence,
                     /* 工賃 */
                    max(order_process.charge_price) as charge_price,
                     /* 製造数量 */
                    sum(achievement.achievement_quantity) as achievement_qty,
                     /* 製造時間（分） */
                    sum(achievement.work_minute) as work_minute,
                     /* 製造経費 */
                    sum(achievement.cost_1) as achievement_cost_1,
                    sum(achievement.cost_2) as achievement_cost_2,
                    sum(achievement.cost_3) as achievement_cost_3,
                     /* 製造金額 */
                    sum((coalesce(achievement.work_minute,0) * coalesce(order_process.charge_price,0))
                    	+ (coalesce(order_process.overhead_cost,0) * coalesce(achievement.achievement_quantity,0))
                        ) + coalesce(sum(achievement.cost_1),0) + coalesce(sum(achievement.cost_2),0) + coalesce(sum(achievement.cost_3),0)
                        as achievement_amount,
                     /* 発注先 取得用 */
                    max(coalesce(t_subcontract_process_accepted.customer_name, customer_master.customer_name)) as customer_name,
                     /* 購買数 */
                    sum(coalesce(accepted.accepted_quantity,0) + coalesce(t_subcontract_process_accepted.accepted_quantity,0)) as accepted_qty,
                     /* 購買単価 */
                    sum(coalesce(accepted.accepted_amount,0) + coalesce(t_subcontract_process_accepted.accepted_amount,0)) as order_price
                    
                from
                    order_detail
                    left join item_master on order_detail.item_id = item_master.item_id
                    left join order_process on order_detail.order_detail_id = order_process.order_detail_id
                    left join process_master on order_process.process_id = process_master.process_id
                    left join achievement on order_detail.order_detail_id = achievement.order_detail_id
                       and achievement.process_id = order_process.process_id
                    left join accepted on order_detail.order_detail_id = accepted.order_detail_id
                    /* 外製工程の受入 */
                    left join (
                        select
                            order_process.order_detail_id,
                            order_process.process_id,
                            max(customer_master.customer_name) as customer_name,
                            sum(accepted.accepted_quantity) as accepted_quantity,
                            sum(accepted_amount) as accepted_amount
                    	from
                            accepted
                            inner join order_detail on accepted.order_detail_id = order_detail.order_detail_id
                            inner join order_header on order_detail.order_header_id = order_header.order_header_id
                            inner join order_process on order_detail.subcontract_order_process_no = order_process.order_process_no
                            left join customer_master on order_header.partner_id = customer_master.customer_id
                        group by
                            order_process.order_detail_id, order_process.process_id
                        ) as t_subcontract_process_accepted
                            on order_detail.order_detail_id = t_subcontract_process_accepted.order_detail_id
                            and order_process.process_id = t_subcontract_process_accepted.process_id
                    left join order_header on order_detail.order_header_id = order_header.order_header_id /* 発注先 取得用 */
                    left join customer_master on order_header.partner_id = customer_master.customer_id
                where
                    /* 外製工程は工程行の中に含めるため、ここでは排除 */
                    (order_detail.subcontract_order_process_no is null or subcontract_order_process_no = '')
                group by
                    order_detail.seiban,
                    order_detail.item_id,
                    coalesce(order_process.process_id,-1)

                ) as t_detail
                   on t_required.seiban = t_detail.seiban
                   and t_required.item_id = t_detail.item_id
                   
            /* オーダー関連情報の取得2 */
            /*  オーダー関連のうち、数値項目でなおかつorder_detailに存在する項目はここで取得する。*/
            /*  それ以外は上のjoinで取得する。 */
            left join (
                select
                    order_detail.seiban,
                    order_detail.item_id
                from
                    order_detail
                where
                    /* 外製工程は工程行の中に含めるため、ここでは排除 */
                    (order_detail.subcontract_order_process_no is null or subcontract_order_process_no = '')
                group by
                    order_detail.seiban,
                    order_detail.item_id

                ) as t_detail2
                   on t_required.seiban = t_detail2.seiban
                   and t_required.item_id = t_detail2.item_id
                   
            /* 入出庫金額 */
            /* いまのところ、出庫画面でのみ登録される */
            left join (
                select
                    item_in_out.seiban,
                    item_in_out.item_id,
                    sum(item_in_out.stock_amount) as inout_amount,
                    sum(item_in_out.item_in_out_quantity) as inout_quantity
                from
                    item_in_out
                where
                    item_in_out.seiban <> ''
                    /* この条件を外すと、出庫金額には影響ない（いまのところ金額は出庫画面でしか登録されないので）ものの */
                    /* 出庫数量に影響する（製番つきの入出庫の数量がすべて含まれてしまう）ので注意 */
                    and item_in_out.classification = 'out'
                group by
                    item_in_out.seiban,
                    item_in_out.item_id
                ) as t_inout
                   on t_required.seiban = t_inout.seiban
                   and t_required.item_id = t_inout.item_id
                   /* 複数工程が存在する場合、最初の工程の行に表示する */
                   and coalesce(t_detail.machining_sequence,0) = 0

         where
            item_master.order_class = 0     /* 受注品目が製番 */
            {$where}
        ";
         $gen_db->query($query);

        if ($costType == "1") {
            // 実績モードの処理。
            // 製番品目のうち、未発行・未完了・製番引当のもの（標準原価で計算されている）をクリアする。
            // 実際に完了しているオーダーだけを原価に含める。
            $query = "
            update
                temp_base_cost
            set
                detail_hikiate_qty = null,
                detail_standard_base_cost = null,
                detail_hikiate_amount = null
            where
                detail_item_id in (select item_id from item_master where order_class = 0)
            ";
            $gen_db->query($query);
        }
        
        // 標準原価と行合計金額の計算
        // 速度向上のため、引当がある品目のみ標準原価を計算する
        $query = "select detail_item_id, max(dead_line) as dead_line from temp_base_cost where detail_hikiate_qty <> 0 group by detail_item_id";
        $arr = $gen_db->getArray($query);
        
        if (is_array($arr)) {
            $query = "";
            foreach ($arr as $row) {
                $itemId = $row['detail_item_id'];
                // 標準原価を取得。
                //  標準原価は納期日の時点のもの。（納期日でよいかどうかは検討が必要か）
                $baseCostArr = self::calcStandardBaseCost($itemId, 1, true, (Gen_String::isDateString($row['dead_line']) ? $row['dead_line'] : null));
                if (is_array($baseCostArr)) {
                    foreach ($baseCostArr as $bRow) {
                        $baseCost = $bRow['base_cost'];

                        $query .= "
                        update
                            temp_base_cost
                        set
                            detail_standard_base_cost = coalesce(detail_standard_base_cost,0) + '{$baseCost}'
                            ,detail_hikiate_amount = coalesce(detail_hikiate_amount,0) + (detail_hikiate_qty * {$baseCost})
                            ,detail_base_cost = coalesce(detail_base_cost,0) + (coalesce(detail_hikiate_qty,0) * {$baseCost})
                        where
                            detail_item_id = '{$itemId}'
                            and (process_id is null or process_id = '{$bRow['process_id']}');
                        ";
                    }
                }
            }
            if ($query != "")
                $gen_db->query($query);
        }

        // 製番合計金額と粗利の計算
        $query = "
        update
            temp_base_cost
        set
            base_cost = coalesce(t2.base_cost,0)
            ,profit = received_sum - coalesce(t2.base_cost,0)
        from
            (select seiban, sum(detail_base_cost) as base_cost from temp_base_cost group by seiban) as t2
        where
            temp_base_cost.seiban = t2.seiban    
        ";
        $gen_db->query($query);
    }
}
