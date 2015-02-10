<?php

class Logic_Stock
{

    //************************************************
    // 入出庫SQLのフィールド部。
    //************************************************
    // 引数は、製番引当計算のときのみtrueにする
    // MRPから呼ばれるため、static宣言しておく必要がある
    // （宣言されていないとPHPのバージョン・設定によりエラーになることがある）

    static function getInoutField($isSeibanChangeFreeOnly)
    {
        $query = "
             coalesce(case when classification='in' then item_in_out_quantity ELSE 0 end,0)
             - coalesce(case when classification='out' then item_in_out_quantity ELSE 0 end,0)
             - coalesce(case when classification='payout' then item_in_out_quantity ELSE 0 end,0)
             - coalesce(case when classification='use' then item_in_out_quantity ELSE 0 end,0)
             + coalesce(case when classification='manufacturing' then item_in_out_quantity ELSE 0 end,0)
                -- 受入はitem_in_out上ではinだが、temp_stock作成時にacceptedに置き換えられる
             + coalesce(case when classification='accepted' then item_in_out_quantity ELSE 0 end,0)
             - coalesce(case when classification='delivery' then item_in_out_quantity ELSE 0 end,0)
             + coalesce(case when classification='move_in' then item_in_out_quantity ELSE 0 end,0)
             - coalesce(case when classification='move_out' then item_in_out_quantity ELSE 0 end,0)
        ";
        if ($isSeibanChangeFreeOnly) {
            // 使用可能数SQL用
            //  製番引当について、製番在庫から or 製番在庫への引当分は排除する。
            //  製番品目の使用可能数を取得する際、フリー在庫のみを含める必要があるため。
            $query .=
                    "     + coalesce(case when classification='seiban_change_in' and seiban_change.dist_seiban = '' then item_in_out_quantity ELSE 0 end,0) " .
                    "     - coalesce(case when classification='seiban_change_out' and seiban_change.source_seiban = '' then item_in_out_quantity ELSE 0 end,0)";
        } else {
            // 製番在庫取得SQL用
            // 　製番引当は入も出も含める
            $query .=
                    "     + coalesce(case when classification='seiban_change_in' then item_in_out_quantity ELSE 0 end,0) " .
                    "     - coalesce(case when classification='seiban_change_out' then item_in_out_quantity ELSE 0 end,0)";
        }

        return $query;
    }

    //************************************************
    // 未オーダー計画データをテンポラリテーブルに入れる
    //************************************************
    // 全期間分の、未オーダー計画データをtemp_plan_remainedに入れる
    // MRPから(別メソッド経由で)呼ばれるため、static宣言しておく必要がある

    static function createTempPlanRemainedTable()
    {
        global $gen_db;

        // テンポラリテーブルの作成処理
        $query = "";
        for ($i = 1; $i <= 31; $i++) {
            $query .= "
            select
                cast(plan_year || '-' || plan_month || '-{$i}' as date) as plan_date
                ,seiban
                ,item_id
                ,day{$i} - coalesce(order{$i},0) as plan_quantity
                ,classification
            from
                plan
            where
                classification in (0,3)
                and day{$i} is not null and day{$i} <> 0
                and coalesce(day{$i},0) > coalesce(order{$i},0)
            ";
            if ($i != 31) {
                $query .= " union all ";
            }
        }

        // 1セッション中で同じテーブルを複数回作成する可能性があるときは、CREATE TEMP TABLE文ではなくこのメソッドを使う
        $gen_db->createTempTable("temp_plan_remained", $query, true);
    }

    //************************************************
    // 指定日時点の在庫数をテンポラリテーブルに入れる
    //************************************************
    // ・最終棚卸日を起点に入出庫数を加減して、指定日時点の在庫数（および最終棚卸以降の入出庫数）を求め、temp_stockテーブルを作成する。
    // 　　History・Flow・MRP では前在庫計算に、Stocklistでは全体の計算に使用される
    // ・History と Flow はこのfunc を直接呼ぶのではなく、この下にある createTempInoutTable() 経由で利用する
    // ・入庫予定や出庫予定、有効在庫については、ロケ・ロットの概念がないため、すべて規定ロケ・ロット（id=0）
    //     に対するものとして取得している。しかし実際には規定ロケ・ロットだけではなく、全ロケ・ロットに対するものであることに注意。
    //     そのためロケやロットを区別しない場合にのみ、入庫予定や出庫予定、有効在庫を使用すべきである。
    //     ちなみに、それならこのfuncで、引数$locationIdや$lotIdが'sum'のときのみ上記を取得するようにすればいいように
    //     思われるが、それではうまくいかない。たとえばHistoryではレコードをロケ・ロット別（引数null）に取得するにも
    //     かかわらず、有効在庫の情報も使用する（個別取得しても全ロケ・ロット分を表示するため、それでも大丈夫）。
    //     よってここでは常に入庫予定・出庫予定・有効在庫を取得し、利用に関しては利用側の判断にゆだねている。
    //
    // 受注引当と使用予約について
    //  08i初期版では、$isUsePlanAllMinusが指定された場合（MRPの前在庫計算）、
    //  use_planにある値すべて（受注引当と使用予約の両方）を全期間分差し引きしていた。これは「引当横取り」の対処で、
    //  使用予定日より前の日付にあとから割り込んできた需要に引当してある在庫を使用されてしまうのを防ぐ意味がある。
    //  前在庫計算の段階で将来分も含めて引当数を有効在庫から差し引いておけば、別の需要に横取りされてしまうことはない。
    //  しかし rev.20071109 で、使用予約については、$isUsePlanAllMinusが指定されても全期間差し引きせず、
    //  $stockDateより前の予約のみ差し引くように変更した。（受注引当は従来どおり全期間差し引きされる）
    //  その理由は、受注引当の場合は登録時に理論在庫があることをチェックしているのに対し、使用予約の場合は在庫がマイナスに
    //  なるような登録も可能であるため。使用予約によって在庫がマイナスになり、なおかつ全期間分を前在庫で引いてしまうと、
    //  初日に不足分のオーダーが出てしまう。しかしどこかの時点で使用予約のためのオーダーがすでに出ている可能性もあり、
    //  初日にマイナスだからといってオーダーをかけてしまうのはまずい。
    //  受注引当は確保が必要だが、使用予約は必ずしも確保されなくてもよい（使用日に間に合うようにオーダーが出されればよい）
    //  という理由もある。
    //
    // 引数：
    //        $stockDate                    この日以前の最終棚卸日から、この日までがデータ取得の対象になる。空欄にすると最終在庫
    //        $itemId                       指定：　その品目のみ、　null：　全品目分を個別に取得
    //                                      2010iでは配列での指定も可能。複数の品目を指定できる
    //        $seiban                       指定：　その製番のみ、　null：　全製番分を個別に取得、  'sum'：　全製番分を合計
    //        $locationId                   指定：　そのロケのみ、　null：　全ロケ分を個別に取得、　'sum'：　全ロケ分を合計
    //        $lotId                        指定：　そのロットのみ、null：　全ロット分を個別に取得、'sum'：　全ロット分を合計
    //        $isGetAvailable               有効在庫および未確定データ（オーダー残、受注計画残、引当、使用予定）を取得するか。falseだと速い
    //        $isIncludePartnerStock        Pロケ分を含めるかどうか
    //        $isUsePlanAllMinus            use_planを将来分まで差し引くか（MRPとStocklistではtrue、HistoryとFlowではfalse）
    //        $isExceptStockDateInventory   stockDate当日の棚卸を計算から除外するかどうか。棚卸差数の計算用のモード
    // 戻り：
    //        なし。
    //        テンポラリテーブル temp_stock に結果が入る。
    //
    // MRPから呼ばれるため、static宣言しておく必要がある
    //
    // ダミー品目（dummy_item）は結果に含まれない。ダミー品目は在庫管理しないため

    static function createTempStockTable($stockDate, $itemId, $seiban, $locationId, $lotId, $isGetAvailable, $isIncludePartnerStock, $isUsePlanAllMinus, $isExceptStockDateInventory = false)
    {
        global $gen_db;

        if ($stockDate == "" || $stockDate == null) {
            // 最終在庫。現在庫リストで日付を空欄にしたのと同じ状態（2038年以降は日付と認識されない）
            $stockDate = date('2037-12-31');
        }

        // 指定日（$stockDate）以前の最終棚卸日テーブルを作成
        $query = "
        select
            inventory.item_id
            ,inventory.seiban
            ,inventory.location_id
            ,inventory.lot_id
            ,inventory_quantity
            ,last_inventory_date
        from (
            select
                item_id
                ,seiban
                ,location_id
                ,lot_id
                ,MAX(inventory_date) as last_inventory_date
            from
               inventory
            where
                inventory_date " . ($isExceptStockDateInventory ? "<" : "<=") . " '{$stockDate}'::date
            group by
                item_id, seiban, location_id, lot_id
            ) as t1
            inner join inventory on t1.item_id = inventory.item_id
                and coalesce(t1.seiban,'') = coalesce(inventory.seiban,'')
                and t1.location_id = inventory.location_id
                and t1.lot_id = inventory.lot_id
                and t1.last_inventory_date = inventory.inventory_date
        where 1=1
             " . (is_numeric($itemId) ? " and t1.item_id = {$itemId}" : (is_array($itemId) ? " and t1.item_id in (" . join($itemId, ",") . ")" : "")) . "
        ";

        // 1セッション中で同じテーブルを複数回作成する可能性があるときは、CREATE TEMP TABLE文ではなくこのメソッドを使う
        $gen_db->createTempTable("temp_inventory", $query, true);

        //  製番レコードが多い場合に、計算に異常に時間がかかる場合がある不具合を修正。
        //  ※藤沢産業では製番レコードが多いため、これの有無によりこの次のSQLの実行速度が劇的に違う（160sec⇒2sec）
        $query = "
        drop index if exists temp_inventory_index1;
        create index temp_inventory_index1 on temp_inventory (
            item_id
            ,coalesce(location_id,0)
            ,coalesce(seiban,'')
            ,coalesce(lot_id,0)
        );
        ";
        $gen_db->query($query);

        // 計画残データをtemp_plan_remainedテーブルに取得
        if ($isGetAvailable) {
            Logic_Stock::createTempPlanRemainedTable();
        }

        //  型を明示的に指定するため、およびデータがなかったときのため、CREATE select ではなく先にテーブルだけ作っておく
        $query = "
        (
            item_id int
            ,seiban text
            ,location_id int
            ,lot_id int

            ,last_inventory_date date
            ,last_inventory_quantity numeric

            ,in_quantity numeric
            ,out_quantity numeric
            ,payout_quantity numeric
            ,use_quantity numeric
            ,manufacturing_quantity numeric
            ,accepted_quantity numeric
            ,delivery_quantity numeric
            ,move_in_quantity numeric
            ,move_out_quantity numeric
            ,seiban_change_in_quantity numeric
            ,seiban_change_out_quantity numeric
            ,in_out_quantity numeric

            -- 以下は isGetAvailable が trueのときのみ必要なカラムだが、create temp table の方式が変わったことに伴い、
            -- isGetAvailableにかかわりなく作成するようにした。（同一テーブル名で別スキーマが許可されなくなったため）
            ,received_remained_quantity numeric
            ,plan_remained_quantity numeric      /* 「納期が明日以降の」未オーダー計画 */
            ,order_remained_quantity numeric
            ,use_plan_quantity numeric

            ,total_in_plan_qty numeric
            ,total_out_plan_qty numeric

            ,available_stock_quantity numeric
            -- ここまで

            ,total_in_qty numeric
            ,total_out_qty numeric
            ,logical_stock_quantity numeric
        )
        ";

        // 1セッション中で同じテーブルを複数回作成する可能性があるときは、CREATE TEMP TABLE文ではなくこのメソッドを使う
        $gen_db->createTempTable("temp_stock", $query, false);

        //   この1文により、次のSQLの速度がだいぶ改善される場合がある。（とくに入出庫データが多いとき）
        //   ただしトランザクション中だとエラーになる。
        if ($gen_db->tranCount == 0) {
            $query = "vacuum analyze temp_inventory;";
            $gen_db->query($query);
        }

        // 指定日時点の在庫数を取得
        // (指定日以前の)最終棚卸日から、指定日までの合計入出庫数も取得する
        //            品目/製番/ロケ/ロット、最終棚卸日、棚卸数、入出庫(カテゴリ別)、受注残、オーダー残、
        //            使用予定・引当、有効在庫数、理論在庫数
        $query = "
        insert into temp_stock

        select
            t0.item_id as item_id
            ,t0.seiban
            ,t0.location_id
            ,t0.lot_id

            ,t_inv.last_inventory_date
            ,inventory_quantity

            ,in_quantity
            ,out_quantity
            ,payout_quantity
            ,use_quantity
            ,manufacturing_quantity
            ,accepted_quantity
            ,delivery_quantity
            ,move_in_quantity
            ,move_out_quantity
            ,seiban_change_in_quantity
            ,seiban_change_out_quantity
            ,in_out_quantity
        ";

        if ($isGetAvailable) {
            $query .= "
                ,received_remained_quantity
                ,plan_remained_quantity
                ,order_remained_quantity
                ,use_plan_quantity
            ";
        }

        $query .= "
        from (
            select
                t_all.item_id
                ," . ($seiban == "sum" ? "cast('' as text) as seiban" : "coalesce(t_all.seiban,'') as seiban") . "
                ," . ($locationId == "sum" ? "0 as location_id" : "coalesce(t_all.location_id,0) as location_id") . "
                ," . ($lotId == "sum" ? "0 as lot_id" : "coalesce(t_all.lot_id,0) as lot_id") . "
                ,SUM(case when classification='in'  then item_in_out_quantity end) as in_quantity
                ,SUM(case when classification='out' then item_in_out_quantity end) as out_quantity
                ,SUM(case when classification='payout' then item_in_out_quantity end) as payout_quantity
                ,SUM(case when classification='use' then item_in_out_quantity end) as use_quantity
                ,SUM(case when classification='manufacturing' then item_in_out_quantity end) as manufacturing_quantity
                ,SUM(case when classification='accepted' then item_in_out_quantity end) as accepted_quantity
                ,SUM(case when classification='delivery' then item_in_out_quantity end) as delivery_quantity
                ,SUM(case when classification='move_in' then item_in_out_quantity end) as move_in_quantity
                ,SUM(case when classification='move_out' then item_in_out_quantity end) as move_out_quantity
                ,SUM(case when classification='seiban_change_in' then item_in_out_quantity end) as seiban_change_in_quantity
                ,SUM(case when classification='seiban_change_out' then item_in_out_quantity end) as seiban_change_out_quantity
                ,SUM(case when classification='inventory' then item_in_out_quantity end) as inventory_quantity
                ,SUM(" . Logic_Stock::getInoutField(false) . ") as in_out_quantity
        ";

        if ($isGetAvailable) {
            $query .= "
                    ,SUM(case when classification='received_remained'  then item_in_out_quantity end) as received_remained_quantity
                    ,SUM(case when classification='plan_remained'  then item_in_out_quantity end) as plan_remained_quantity
                    ,SUM(case when classification='order_remained'  then item_in_out_quantity end) as order_remained_quantity
                    ,SUM(case when classification='use_plan'  then item_in_out_quantity end) as use_plan_quantity
                ";
        }

        $query .= "
            from
            (    /* t_all */

        /* ●ベース */
            (select
                item_id
                ,'' as seiban
                ,0 as location_id
                ,0 as lot_id
                ,'' as classification
                ,0 as item_in_out_quantity
            from
                item_master
            where 1=1
                " . (is_numeric($itemId) ? " and item_id = {$itemId}" : (is_array($itemId) ? " and item_id in (" . join($itemId, ",") . ")" : "")) . "
                " . (!($seiban === null) && $seiban != "sum" ? " and '' = '{$seiban}'" : "") . "
                " . (is_numeric($locationId) ? " and 0 = {$locationId}" : "") . "
                " . (is_numeric($lotId) ? " and 0 = {$lotId}" : "") . "
            )

        /* ●入出庫 */
        union all (
            select
                item_in_out.item_id
                ,coalesce(item_in_out.seiban,'') as seiban
                ,coalesce(item_in_out.location_id,0) as location_id
                ,coalesce(item_in_out.lot_id,0) as lot_id
                -- 入庫と受入を区別する（両方ともclassification は in）
                ,case when classification='in' and accepted_id is not null then 'accepted' else classification end as classification
                ,item_in_out_quantity
            from
                item_in_out
                left join temp_inventory
                    on item_in_out.item_id = temp_inventory.item_id
                    and coalesce(item_in_out.location_id,0) = coalesce(temp_inventory.location_id,0)
                    and coalesce(item_in_out.seiban,'') = coalesce(temp_inventory.seiban,'')
                    and coalesce(item_in_out.lot_id,0) = coalesce(temp_inventory.lot_id,0)
            where
                without_stock <> 1
                and (temp_inventory.last_inventory_date is null
                    or item_in_out.item_in_out_date > temp_inventory.last_inventory_date)
                and item_in_out.item_in_out_date <= '{$stockDate}'::date
                " . (is_numeric($itemId) ? " and item_in_out.item_id = {$itemId}" : (is_array($itemId) ? " and item_in_out.item_id in (" . join($itemId, ",") . ")" : "")) . "
                " . (!($seiban === null) && $seiban != "sum" ? " and item_in_out.seiban = '{$seiban}'" : "") . "
                " . (is_numeric($locationId) ? " and coalesce(item_in_out.location_id,0) = {$locationId}" : "") . "
                " . (is_numeric($lotId) ? " and coalesce(item_in_out.lot_id,0) = {$lotId}" : "") . "
              )

        /* ●棚卸数 */
        /*  棚卸日はここでは取得できないので、あとでleft joinで取っていることに注意 */
        union all (
            select
                item_id
                ,coalesce(temp_inventory.seiban,'') as seiban
                ,coalesce(temp_inventory.location_id,0) as location_id
                ,coalesce(temp_inventory.lot_id,0) as lot_id
                ,'inventory' as classification
                ,inventory_quantity as item_in_out_quantity
            from
                temp_inventory
            where 1=1
                " . (is_numeric($itemId) ? " and temp_inventory.item_id = {$itemId}" : (is_array($itemId) ? " and temp_inventory.item_id in (" . join($itemId, ",") . ")" : "")) . "
                " . (!($seiban === null) && $seiban != "sum" ? " and temp_inventory.seiban = '{$seiban}'" : "") . "
                " . (is_numeric($locationId) ? " and temp_inventory.location_id = {$locationId}" : "") . "
                " . (is_numeric($lotId) ? " and temp_inventory.lot_id = {$lotId}" : "") . "
            )
        ";

        if ($isGetAvailable) {
            $query .= "

            /* ●発注製造残 */
            union all (
                select
                    max(order_detail.item_id) as item_id
                    /* 受注製番のみを製番とみなす（計画製番はフリー在庫になる）ことに注意 */
                    ,coalesce(max(received_detail.seiban),'') as seiban
                    ,0 as locaiton_id
                    ,0 as lot_id
                    ,'order_remained' as classification
                    ,max(coalesce(order_detail_quantity,0) - coalesce(accepted_quantity,0)) as item_in_out_quantity
                from
                    order_detail
                    left join received_detail on order_detail.seiban = received_detail.seiban
                where
                    /* 指定された日付の時点でまだ納期が来ていない場合、オーダー残とはみなさない。オーダー残は「その日の時点で入庫しているはずなのに、*/
                    /* まだ入庫登録していない数量」 であるため。まだ納期が来ていないオーダーは、有効在庫とはみなせない */
                    order_detail_dead_line <= '{$stockDate}'::date
                    and (not(order_detail_completed) or (order_detail_completed is null))
                    /* 外製工程は除く。オーダーは出ているが、受入時に在庫が増えるわけではないため */
                    and (subcontract_order_process_no is null or subcontract_order_process_no='')
                    " . (is_numeric($itemId) ? " and order_detail.item_id = {$itemId}" : (is_array($itemId) ? " and order_detail.item_id in (" . join($itemId, ",") . ")" : "")) . "
                    " . (!($seiban === null) && $seiban != "sum" ? " and coalesce(order_detail.seiban,'') = '{$seiban}'" : "") . "
                group by
                    order_detail_id
                )

            /* ●使用予約・受注引当数 */
            /*  ※受注引当はここではなく、下の受注数計算で考慮するほうが一見合理的に思えるが、 */
            /*    それだと isUsePlanAllMinus の処理がうまくいかない。*/
            union all (
                select
                    use_plan.item_id
                    /* 製番のとり方に注意：
                     *   使用予約 ： MRP品目：製番なし、製番品目：order_detail.seiban（ただ、製番品目で計画ベースのオーダーの場合、オーダー製番と在庫製番が異なるのが微妙）
                                    ※15iでは受注したダミー品目の子品目にも使用予約が立つようになったが、その場合は常に製番なし
                     *   受注引当 ： MRP品目・製番品目とも製番なし

                     * ハイブリッド構成の場合の使用予約数計算の不具合を修正。
                     *  従来、使用予約数の計算における製番は、オーダー（製造指示書）の製番を基準にしていた。
                     *  しかしそれだとハイブリッド構成（親が製番品目、子がMRP品目）の場合、使用予約数は製番つきとなり、結果としてMRPにおいて
                     *  子の使用予約数が計算に含まれない。（MRPでは製番なしのデータだけを計算対象とするため）
                     *  MRPではこのfunctionを前在庫計算に使用しているため、「ハイブリッド親品目の指示書の製造開始日が本日より前であるとき、
                     *  その子品目の使用予約が計算に含まれない」という現象が発生していた。
                     *  それで、MRP品目の場合はオーダーの製番にかかわらず、製番なしとみなすように変更した。
                     */
                    ,(case when use_plan.received_detail_id is null and item_master.order_class='0' then coalesce(order_detail.seiban, coalesce(received_detail.seiban, ''))
                       else '' end) as seiban
                    ,0 as locaiton_id
                    ,0 as lot_id
                    ,'use_plan' as classification
                    ,quantity as item_in_out_quantity
                from
                    use_plan
                    left join order_detail on use_plan.order_detail_id = order_detail.order_detail_id
                    left join item_master on use_plan.item_id = item_master.item_id
                    left join received_detail on use_plan.received_detail_id_for_dummy = received_detail.received_detail_id
                where
                   (quantity > 0 or quantity < 0)
                    /* isUsePlanAllMinus == trueのとき、全期間分差し引きするのは受注引当（製番が記録されているもの）のみ。 */
                    /* 使用予約は将来分を差し引きしない。*/
                   " . ($isUsePlanAllMinus ? " and (use_date <= '{$stockDate}'::date or use_plan.received_detail_id is not null)" : " and use_date <= '{$stockDate}'::date") . "
                   " . (is_numeric($itemId) ? " and use_plan.item_id = {$itemId}" : (is_array($itemId) ? " and use_plan.item_id in (" . join($itemId, ",") . ")" : "")) . "
                   " . (!($seiban === null) && $seiban != "sum" ? " and (case when use_plan.received_detail_id is null and item_master.order_class='0' then coalesce(order_detail.seiban,'') else '' end) = '{$seiban}'" : "") . "
                )

            /* ●受注（未納品・未引当のみ）*/
            /*  ・未納品分のみ。納品済み分はすでに出庫実績に含まれているため。*/
            /*  ・未引当分のみ。引当済み分はすでにuse_planに含まれているため。*/
            /*  ・納期が過去の受注であっても未納品であれば出庫予定に含める（08iでのMRP変更点）*/
            /*  ・完了フラグが立っている受注を除く。*/
            union all (
                select
                    max(received_detail.item_id) as item_id
                    /* 製番/ロット品目のみ製番をとる*/
                    ,max(case when order_class in (0,2) then received_detail.seiban else '' end) as seiban
                    ,0 as locaiton_id
                    ,0 as lot_id
                    ,'received_remained' as classification
                    ,coalesce(max(received_quantity),0) - coalesce(max(delivery_quantity),0) - coalesce(sum(t_use_plan.quantity),0) as item_in_out_quantity
                from
                    received_detail
                    left join (
                        select
                            delivery_detail.received_detail_id
                            ,SUM(delivery_quantity) as delivery_quantity
                        from
                            delivery_detail
                            inner join received_detail on delivery_detail.received_detail_id = received_detail.received_detail_id
                        where
                            dead_line <= '2037-12-31'::date
                            and (delivery_completed = false OR delivery_completed IS null)
                        group by
                            delivery_detail.received_detail_id
                    ) as T1 on received_detail.received_detail_id = T1.received_detail_id
                    left join item_master on received_detail.item_id = item_master.item_id
                    left join use_plan as t_use_plan on received_detail.received_detail_id = t_use_plan.received_detail_id and received_detail.item_id = t_use_plan.item_id
                where
                    /*  予約分を含めるかどうかは微妙。しかし予約に対して引当があった場合に、その引当数は有効在庫計算に加味される*/
                    /*  ことを考えると、予約分も含めておかないと整合性がとれない。 */
                    coalesce(received_quantity,0) - coalesce(delivery_quantity,0) > 0
                    and dead_line <= '{$stockDate}'::date
                    and (delivery_completed = false or delivery_completed is null)
                    " . (is_numeric($itemId) ? " and received_detail.item_id = {$itemId}" : (is_array($itemId) ? " and received_detail.item_id in (" . join($itemId, ",") . ")" : "")) . "
                    " . (!($seiban === null) && $seiban != "sum" ? " and (case when order_class=0 then received_detail.seiban else '' end) = '{$seiban}'" : "") . "
                group by
                    received_detail.received_detail_id
                )

            /* ●計画（未オーダー）*/
            /*  ・未オーダー分のみ。オーダー済み分はオーダー残としてすでに入庫予定に含まれているため。*/
            /*  ・明日以降が納期の分のみ。本日以前に納期だが、まだ未オーダーの計画は無視される。*/
            /*     納期が過ぎてしまうと所要量計算に含まれないのでオーダーがかかる見込みがない。*/
            /*     それにMRPでも期間外の未オーダー計画は無視される。*/
            union all (
                select
                    temp_plan_remained.item_id
                    /* 製番は常に「なし」。計画ベースの場合、入庫時に製番フリーになるため */
                    ,'' as seiban
                    ,0 as locaiton_id
                    ,0 as lot_id
                    ,'plan_remained' as classification
                    ,plan_quantity as item_in_out_quantity
                from
                    temp_plan_remained
                where
                    plan_quantity <> 0
                    and plan_date <= '{$stockDate}'::date
                    and plan_date > '" . date('Y-m-d') . "'  /* 明日以降納期分のみ。上のコメント参照 */
                    " . (is_numeric($itemId) ? " and temp_plan_remained.item_id = {$itemId}" : (is_array($itemId) ? " and temp_plan_remained.item_id in (" . join($itemId, ",") . ")" : "")) . "
                    " . (!($seiban === null) && $seiban != "sum" ? " and '' = '{$seiban}'" : "") . "
                )
           ";
        }

        $query .= "
        ) as t_all

        /* ●Pロケ排除用 */
        left join location_master on t_all.location_id = location_master.location_id

        /* ●ダミー品目排除用 */
        left join item_master on t_all.item_id = item_master.item_id

        /* ●where */
        where
            -- ダミー品目は在庫計算に含めない
            not coalesce(item_master.dummy_item, false)

            " . (is_numeric($itemId) ? " and t_all.item_id = {$itemId}" : (is_array($itemId) ? " and t_all.item_id in (" . join($itemId, ",") . ")" : "")) . "
            " . (!($seiban === null) && $seiban != "sum" ? " and t_all.seiban = '{$seiban}'" : "") . "
            " . (is_numeric($locationId) ? " and t_all.location_id = {$locationId}" : "") . "
            " . (is_numeric($lotId) ? " and t_all.lot_id = {$lotId}" : "") . "
            " . ($isIncludePartnerStock ? "" : " and location_master.customer_id is null") . "
        group by
            t_all.item_id
            " . ($seiban == "sum" ? "" : ",coalesce(t_all.seiban,'')") . "
            " . ($locationId == "sum" ? "" : ",coalesce(t_all.location_id,0)") . "
            " . ($lotId == "sum" ? "" : ",coalesce(t_all.lot_id,0)") . "

        ) as t0

        /* ●棚卸日 */
        /*  以前はここで棚卸数も取得していたが、それだと棚卸はあるが入出庫がないパターンでレコードが */
        /*  含まれなくなってしまうので、棚卸数は上のUNIONの中で取得するように変更した。 */
        left join (
            select
                item_id,
                " . ($seiban == "sum" ? "" : "coalesce(seiban,'') as seiban,") . "
                " . ($locationId == "sum" ? "" : "coalesce(location_id,0) as location_id,") . "
                " . ($lotId == "sum" ? "" : "coalesce(lot_id,0) as lot_id,") . "
                MAX(last_inventory_date) as last_inventory_date
            from
                temp_inventory
            group by
                item_id
                " . ($seiban == "sum" ? "" : ",coalesce(seiban,'')") . "
                " . ($locationId == "sum" ? "" : ",coalesce(location_id,0)") . "
                " . ($lotId == "sum" ? "" : ",coalesce(lot_id,0)") . "
           ) as t_inv
                on t0.item_id = t_inv.item_id
                " . ($seiban == "sum" ? "" : " and coalesce(t0.seiban,'') = coalesce(t_inv.seiban,'')") . "
                " . ($locationId == "sum" ? "" : " and coalesce(t0.location_id,0) = coalesce(t_inv.location_id,0)") . "
                " . ($lotId == "sum" ? "" : " and coalesce(t0.lot_id,0) = coalesce(t_inv.lot_id,0)") . "
        ";

        $gen_db->query($query);

        // 導出項目

        $query = "
        update
            temp_stock
        set
            total_in_qty =
                coalesce(in_quantity,0)
                + coalesce(manufacturing_quantity,0)
                + coalesce(accepted_quantity,0)
                + coalesce(move_in_quantity,0)
                + coalesce(seiban_change_in_quantity,0)

            ,total_out_qty =
                coalesce(out_quantity,0)
                + coalesce(payout_quantity,0)
                + coalesce(use_quantity,0)
                + coalesce(delivery_quantity,0)
                + coalesce(move_out_quantity,0)
                + coalesce(seiban_change_out_quantity,0)

            ,logical_stock_quantity =
                coalesce(last_inventory_quantity,0)
                + coalesce(in_out_quantity,0)
        ";

        if ($isGetAvailable) {
            $query .= "
                ,total_in_plan_qty =
                    coalesce(order_remained_quantity,0)
                    + coalesce(plan_remained_quantity,0)

                ,total_out_plan_qty =
                    coalesce(received_remained_quantity,0)
                    + coalesce(use_plan_quantity,0)

                ,available_stock_quantity =
                    coalesce(last_inventory_quantity,0)
                    + coalesce(in_out_quantity,0)
                    + coalesce(order_remained_quantity,0)
                    + coalesce(plan_remained_quantity,0)
                    - coalesce(use_plan_quantity,0)
                    - coalesce(received_remained_quantity,0)
            ";
        }

        $gen_db->query($query);
        
        $gen_db->query('analyze temp_stock');
    }

    //************************************************
    // 指定期間の入出庫数をテンポラリテーブルに入れる
    //************************************************
    //
    // ・入出庫明細単位での取得。合計取得はできない（合計しようとするとdescの扱いに困る。string_aggだと遅くなるし）
    // ・入庫予定・出庫予定・有効在庫データについては、規定ロケ・ロットに対するものとして取得されている。
    //     くわしくは createTempStockTable() の冒頭コメント参照。
    // ・以下の引数の説明ではMRPやStocklistのことが出てくるが、今のところこのメソッドを使用しているのは
    //  FlowとHistoryのみ
    //
    // 引数：
    //      $dateFrom
    //      $dateTo
    //      $itemId                 指定するとその品目のみ、nullだと全品目分を個別に取得
    //                               2010では配列での指定も可能。複数の品目を指定できる
    //      $seiban                 指定するとその製番のみ、nullだと全製番分を個別に取得
    //      $locationId             〃
    //      $lotId                  〃
    //      $isIncludePartnerStock  Pロケ分を含めるかどうか
    //      $isIncludeUsePlan       use_planを含めるか（MRPとStocklistではfalse、HistoryとFlowではtrue）
    //      $isUsePlanAllMinus      use_planを全期間分差し引くか（MRPとStocklistではtrue、HistoryとFlowではfalse）
    //      $isCalcStockBySum       在庫数(logical, available)を製番/ロケ/ロットに分けず、品目合計で取得するか（HistoryとStocklistではtrue、Flowではfalse）
    //      $isDailyStockMode       これをtrueにすると、理論在庫数(logical_stock_quantity)・有効在庫数(available_stock_quantity)の
    //                                  計算が一日単位になる（一日の最後の行だけ計算されるようになる）。明細行ごとに計算したい場合はfalse。
    //                                  入出庫数が多いときの計算時間がかなり違うので、なるべくtrueにすること。（Flowではtrue、Historyではfalse）
    //      $gen_db
    // 戻り：
    //      なし。
    //      テンポラリテーブル temp_stock に結果が入る。
    //
    // このテーブルの明細情報を使用するときには、order by id すること（id順に在庫数が計算されているため）
    //
    // createTempStockTable()とは異なり、処理速度の関係で、このfunctionではダミー品目（item_master.dummy_item）を排除していない。
    // ダミー品目の在庫数は不正確（ダミー品目は在庫管理しないことが前提）なので、利用側で排除すること

    static function createTempInoutTable($dateFrom, $dateTo, $itemId, $seiban, $locationId, $lotId, $isIncludePartnerStock, $isIncludeUsePlan, $isUsePlanAllMinus, $isCalcStockBySum, $isDailyStockMode)
    {
        global $gen_db;

        // 計画残データをtemp_plan_remainedテーブルに取得
        Logic_Stock::createTempPlanRemainedTable();

        // 日付期間
        $dateCrit = "'{$dateFrom}'::date and '{$dateTo}'::date";

        // メイン（temp_inout に入出庫情報を取得）
        //  型を明示的に指定するため、およびデータがなかったときのため、CREATE select ではなく先にテーブルだけ作っておく
        //
        // テンポラリテーブルの作成処理
        $query = "
         (
            id serial       /* あとで行順に在庫数を計算するためのオートID。前在庫は-1 */
            ,item_id int
            ,seiban text
            ,location_id int
            ,lot_id int
            ,date date
            ,in_qty numeric
            ,out_qty numeric
            ,in_plan_qty numeric
            ,out_plan_qty numeric
            ,logical_stock_quantity numeric
            ,available_stock_quantity numeric
            ,description text
            ,link text
            ,remarks text
            ,user_name text
            ,record_update_date timestamp
        )
        ";

        // 1セッション中で同じテーブルを複数回作成する可能性があるときは、CREATE TEMP TABLE文ではなくこのメソッドを使う
        $gen_db->createTempTable("temp_inout", $query, false);

        // ●このインデックスを作っておくと、後の「update temp_inout・・」処理が劇的に早くなることがある
        $query = "drop index if exists temp_inout_index1; create index temp_inout_index1 on temp_inout(item_id, seiban, location_id, lot_id)";
        $gen_db->query($query);
        
        // ****** 確定要素 *******

        $query = "
        insert into temp_inout (
            item_id
            ,seiban
            ,location_id
            ,lot_id
            ,date
            ,in_qty
            ,out_qty
            ,in_plan_qty
            ,out_plan_qty
            ,logical_stock_quantity
            ,available_stock_quantity
            ,description
            ,link
            ,remarks
            ,user_name
            ,record_update_date
        )

        /* ●入出庫数（実績・受入分は個別に取得するのでここでは除外） */
        select
            item_in_out.item_id
            ,coalesce(item_in_out.seiban, '') as seiban
            ,coalesce(item_in_out.location_id,0) as location_id
            ,coalesce(item_in_out.lot_id,0) as lot_id
            ,item_in_out_date as date
            ,coalesce((case when classification='in'
                or classification='manufacturing'
                or classification='move_in'
                or classification='seiban_change_in'
                then item_in_out_quantity end),0) as in_qty
            ,coalesce((case when classification='out'
                or classification='payout'
                or classification='use'
                or classification='delivery'
                or classification='move_out'
                or classification='seiban_change_out'
                then item_in_out_quantity end),0) as out_qty
            ,0 as in_plan_qty
            ,0 as out_plan_qty
            ,0 as logical_stock_quantity
            ,0 as available_stock_quantity
            ,(case
                when classification='in' then
                    case
                        when t_acc_od.order_no is null then '" . _g("入庫") . "'
                        when t_acc_od_h.order_header_class=1 then '" . _g("注文受入") . "（" . _g("オーダー番号") . ":' || t_acc_od.order_no || '）'
                        else '" . _g("外製受入") . "（" . _g("オーダー番号") . ":' || t_acc_od.order_no || '）'
                    end
                when classification='out' then '" . _g("出庫") . "'
                when classification='payout' then '" . _g("支給") . "'
                when classification='use' then
                    case when t_ach_od.order_no is null then '" . _g("使用") . "'
                    else '" . _g("製造に使用（オーダー番号:") . "' || t_ach_od.order_no || '）' end
                when classification='manufacturing' then 
                    case when t_ach_od.order_no is null then '" . _g("製造（外製指示オーダー番号:") . "' || t_acc_od.order_no || '）'
                    else '" . _g("製造実績（オーダー番号:") . "' || t_ach_od.order_no || '）' end
                when classification='delivery' then '" . _g("納品（納品書番号:") . "' || delivery_header.delivery_no
                    || '／" . ("受注番号:") . "' || t_del_rec_h.received_number || '）'
                when classification='move_in' then '" . _g("移動入庫") . "'
                when classification='move_out' then '" . _g("移動出庫") . "'
                when classification='seiban_change_in' then '" . _g("製番引当入庫") . "'
                when classification='seiban_change_out' then '" . _g("製番引当出庫") . "'
            end) as description
           -- link
           ,(case
                when classification='in' THEN
                    case
                        when t_acc_od.order_no is null then 'Stock_Inout_List&classification=in&gen_search_item_in_out_id=' || cast(item_in_out.item_in_out_id as text)
                        when t_acc_od_h.order_header_class=1 then 'Partner_Accepted_List&gen_search_order_no=' || t_acc_od.order_no || '&gen_search_match_mode_gen_search_order_no=3'
                        else 'Partner_SubcontractAccepted_List&gen_search_order_no=' || t_acc_od.order_no || '&gen_search_match_mode_gen_search_order_no=3'
                    end
                when classification='out' then 'Stock_Inout_List&classification=out&gen_search_item_in_out_id=' || cast(item_in_out.item_in_out_id as text)
                when classification='payout' then 'Stock_Inout_List&classification=payout&gen_search_item_in_out_id=' || cast(item_in_out.item_in_out_id as text)
                when classification='use' then
                    case when t_ach_od.order_no is null then 'Stock_Inout_List&classification=use&gen_search_item_in_out_id=' || cast(item_in_out.item_in_out_id as text)
                    else 'Manufacturing_Achievement_List&gen_search_order_no=' || t_ach_od.order_no || '&gen_search_match_mode_gen_order_no=3' end
                when classification='manufacturing' then 
                    case when t_ach_od.order_no is null then 'Partner_SubcontractAccepted_List&gen_search_order_no=' || t_acc_od.order_no
                    else 'Manufacturing_Achievement_List&gen_search_order_no=' || t_ach_od.order_no
                    end  || '&gen_search_match_mode_gen_order_no=3'
                when classification='delivery' then 'Delivery_Delivery_List&gen_search_seiban=' || t_del_rec.received_seiban || '&gen_search_match_mode_gen_search_seiban=3'
                when classification='move_in' then 'Stock_Move_List&gen_search_move_id=' || cast(location_move.move_id as text)
                when classification='move_out' then 'Stock_Move_List&gen_search_move_id=' || cast(location_move.move_id as text)
                when classification='seiban_change_in' then 'Stock_SeibanChange_List&gen_search_change_id=' || cast(seiban_change.change_id as text)
                when classification='seiban_change_out' then 'Stock_SeibanChange_List&gen_search_change_id=' || cast(seiban_change.change_id as text)
            end) as link
            ,coalesce(achievement.remarks, coalesce(accepted.remarks, coalesce(location_move.remarks
            ,coalesce(delivery_detail.remarks, coalesce(seiban_change.remarks, item_in_out.remarks))))) as remarks
            ,(coalesce(item_in_out.record_updater, item_in_out.record_creator)) as user_name
            ,coalesce(item_in_out.record_update_date, item_in_out.record_create_date) as record_update_date
        from
            item_in_out
            left join location_master on coalesce(item_in_out.location_id,0) = location_master.location_id
            left join achievement on item_in_out.achievement_id = achievement.achievement_id
            left join accepted on (item_in_out.accepted_id = accepted.accepted_id or item_in_out.achievement_id = accepted.subcontract_inout_achievement_id) /* 'or'以降は製造の最終工程が外製だったとき用 */
            left join order_detail as t_ach_od on achievement.order_detail_id = t_ach_od.order_detail_id
            left join order_detail as t_acc_od on accepted.order_detail_id = t_acc_od.order_detail_id
            left join (select order_header_id, classification as order_header_class from order_header) as t_acc_od_h on t_acc_od.order_header_id = t_acc_od_h.order_header_id
            left join delivery_detail on item_in_out.delivery_id = delivery_detail.delivery_detail_id
            left join delivery_header on delivery_detail.delivery_header_id = delivery_header.delivery_header_id
            left join (select received_detail_id, seiban as received_seiban, received_header_id from received_detail) as t_del_rec on delivery_detail.received_detail_id = t_del_rec.received_detail_id
            left join (select received_header_id, received_number from received_header) as t_del_rec_h on t_del_rec.received_header_id = t_del_rec_h.received_header_id
            left join location_move on item_in_out.move_id = location_move.move_id
            left join seiban_change on item_in_out.seiban_change_id = seiban_change.change_id
        where
            item_in_out_date between {$dateCrit}
            and without_stock <> 1
            and item_in_out_quantity <> 0
            " . (is_numeric($itemId) ? " and item_in_out.item_id = {$itemId}" : (is_array($itemId) ? " and item_in_out.item_id in (" . join($itemId, ",") . ")" : "")) . "
            " . (!($seiban === null) ? " and item_in_out.seiban = '{$seiban}'" : "") . "
            " . (is_numeric($locationId) ? " and coalesce(item_in_out.location_id,0) = '{$locationId}'" : "") . "
            " . (is_numeric($lotId) ? " and coalesce(item_in_out.lot_id,0) = '{$lotId}'" : "") . "
            " . ($isIncludePartnerStock ? "" : " and location_master.customer_id is null") . "

        /* ●棚卸 */
        /*  ここではとりあえず棚卸数をlogical_stock_quantity に入れておき、あとであらためて棚卸差数計算を行う*/
        union all
        select
            inventory.item_id
            ,coalesce(inventory.seiban,'') as seiban
            ,inventory.location_id
            ,inventory.lot_id
            ,inventory_date as date
            ,0 as in_qty
            ,0 as out_qty
            ,0 as in_plan_qty
            ,0 as out_plan_qty
            ,inventory_quantity as logical_stock_quantity
            ,0 as available_stock_quantity
            /* 「棚卸」の文字は後のSQLで棚卸行の判断に使用しているので変えないこと */
            ,'" . _g("棚卸調整（棚卸数：") . "' || inventory_quantity || '）' as description
            ,'' as link  -- 面倒なのでとりあえずリンクなし
            ,inventory.remarks as remarks
            ,(coalesce(inventory.record_updater, inventory.record_creator)) as user_name
            ,coalesce(inventory.record_update_date, inventory.record_create_date) as record_update_date
        from
            inventory
            left join location_master on inventory.location_id = location_master.location_id
        where
            inventory_date between {$dateCrit}
            " . (is_numeric($itemId) ? " and inventory.item_id = {$itemId}" : (is_array($itemId) ? " and inventory.item_id in (" . join($itemId, ",") . ")" : "")) . "
            " . (!($seiban === null) ? " and inventory.seiban = '{$seiban}'" : "") . "
            " . (is_numeric($locationId) ? " and inventory.location_id = '{$locationId}'" : "") . "
            " . (is_numeric($lotId) ? " and inventory.lot_id = '{$lotId}'" : "") . "
            " . ($isIncludePartnerStock ? "" : " and location_master.customer_id is null") . "
        ";

        // ****** 未確定要素 *******
        // 以下の未確定要素については、ロケ・ロットの概念がないため、すべて規定ロケ・ロット（id=0）
        //    に対するものとして取得している。しかし実際には規定ロケ・ロットだけではなく、全ロケ・ロット
        //  に対するものであることに注意。
        //
        // また、ロケ・ロット指定のときは取得しない

        if (!is_numeric($locationId) && !is_numeric($lotId)) {
            $query .= "
            /* ●オーダー残 */
            union all
            select
                order_detail.item_id
                ,coalesce(t1.seiban,'') as seiban
                ,0 as location_id
                ,0 as lot_id
                ,order_detail_dead_line as date
                ,0 as in_qty
                ,0 as out_qty
                ,(coalesce(order_detail_quantity,0) - coalesce(accepted_quantity,0)) as in_plan_qty
                ,0 as out_plan_qty
                ,0 as logical_stock_quantity
                ,0 as available_stock_quantity
                ,(case when classification = '0' then '" . _g("製造指示書") . "'
                    when classification = '1' then '" . _g("注文書") . "'
                    else '" . _g("外製発注") . "' end
                    || '（" . _g("オーダー番号：") . "' || order_no || '）') as description
                ,(case when classification = '0' then 'Manufacturing_Order_List&gen_search_order_no=' || order_detail.order_no || '&gen_search_match_mode_gen_search_order_no=3'
                    when classification = '1' then 'Partner_Order_List&gen_search_order_no=' || order_detail.order_no || '&gen_search_match_mode_gen_search_order_no=3'
                    else 'Partner_Subcontract_List&gen_search_order_no=' || order_detail.order_no || '&gen_search_match_mode_gen_search_order_no=3'
                    end) as link
                ,(order_detail.remarks) as remarks
                ,(coalesce(order_detail.record_updater, order_detail.record_creator)) as user_name
                ,coalesce(order_detail.record_update_date, order_detail.record_create_date) as record_update_date
            from
                order_detail
                inner join order_header on order_detail.order_header_id = order_header.order_header_id
                /* 受注製番のみを製番とみなす（計画製番はフリー在庫になる）ことに注意*/
                left join (select seiban from received_detail group by seiban) as t1 on order_detail.seiban = t1.seiban
            where
                (not(order_detail_completed) or (order_detail_completed is null))
                and coalesce(order_detail_quantity,0) - coalesce(accepted_quantity,0) <> 0
                and order_detail_dead_line between {$dateCrit}
                " . (is_numeric($itemId) ? " and order_detail.item_id = {$itemId}" : (is_array($itemId) ? " and order_detail.item_id in (" . join($itemId, ",") . ")" : "")) . "
                /* 外製工程は除く。オーダーは出ているが、受入時に在庫が増えるわけではないため */
                and (subcontract_order_process_no is null or subcontract_order_process_no='')
                /* order_detail.seibanではないことに注意 */
                " . (!($seiban === null) ? " and coalesce(t1.seiban,'') = '{$seiban}'" : "") . "

            /* ●受注（未納品のみ） */
            /*  ・未納品分のみ。納品済み分はすでに出庫実績に含まれているため。*/
            /*  ・納期が過去の受注であっても未納品であれば出庫予定に含める（08iでのMRP変更点）*/
            /*  くわしくは前在庫計算funcの受注計算部分のコメント参照。*/
            union all
            select
                received_detail.item_id
                /* 製番品目のみ製番をとる */
                ,(case when order_class=0 then received_detail.seiban else '' end) as seiban
                ,0 as location_id
                ,0 as lot_id
                ,dead_line as date
                ,0 as in_qty
                ,0 as out_qty
                ,0 as in_plan_qty
                ,(coalesce(received_quantity,0) - coalesce(delivery_quantity,0)) as out_plan_qty
                ,0 as logical_stock_quantity
                ,0 as available_stock_quantity
                ,('" . _g("受注") . "[' || case when received_header.guarantee_grade=0 then '" . _g("確定") . "' else '" . _g("予約") . "' end || '] " . _g("納品予定") . "（" . _g("受注番号") . "：' || received_number || '）') as description
                ,'Manufacturing_Received_List&gen_search_seiban=' || received_detail.seiban || '&gen_search_match_mode_gen_search_seiban=3' as link
                ,(received_detail.remarks) as remarks
                ,(coalesce(received_detail.record_updater, received_detail.record_creator)) as user_name
                ,coalesce(received_detail.record_update_date, received_detail.record_create_date) as record_update_date
            from
                received_detail
                inner join received_header on received_header.received_header_id=received_detail.received_header_id
                left join (
                    select
                        received_detail_id
                        ,sum(delivery_quantity) as delivery_quantity
                    from
                        delivery_detail
                    group by
                        received_detail_id
                ) as T1 on received_detail.received_detail_id = T1.received_detail_id
                left join item_master on received_detail.item_id = item_master.item_id
            where
                /*  createTempStockTableでも受注「予約」分を計算に加味しているので、ここでもそうする。*/
                /*  createTempStockTableでは予約に対する引当も引当数に含めているので、受注に関しても予約を含めておかないと*/
                /*  整合性がとれない。*/
                coalesce(received_quantity,0) - coalesce(delivery_quantity,0) > 0
                and dead_line between {$dateCrit}
                and (delivery_completed = false or delivery_completed is null)
                " . (is_numeric($itemId) ? " and received_detail.item_id = {$itemId}" : (is_array($itemId) ? " and received_detail.item_id in (" . join($itemId, ",") . ")" : "")) . "
                " . (!($seiban === null) ? " and coalesce(received_detail.seiban,'') = '{$seiban}'" : "") . "

            /* ●計画（未オーダー） */
            /*  ・未オーダー分のみ。オーダー済み分はオーダー残としてすでに入庫予定に含まれているため。*/
            /*  ・明日以降が納期（計画日）の分のみ。本日以前に納期だが、まだ未オーダーの計画は無視される。*/
            /*     納期が過ぎてしまうと所要量計算に含まれないのでオーダーがかかる見込みがない。*/
            /*     それにMRPでも期間外の未オーダー計画は無視される。*/
            union all
            select
                temp_plan_remained.item_id
                /* 製番は常に「なし」。計画ベースの場合、入庫時に製番フリーになるため */
                ,'' as seiban
                ,0 as location_id
                ,0 as lot_id
                ,plan_date as date
                ,0 as in_qty
                ,0 as out_qty
                ,(plan_quantity) as in_plan_qty
                ,0 as out_plan_qty
                ,0 as logical_stock_quantity
                ,0 as available_stock_quantity
                ,case when classification = 3 then '" . _g("所要量計算結果画面での直接登録（未オーダー）") . "' else '" . _g("計画（未オーダー）") . "' end as description
                ,case when classification = 3 then '' else 'Manufacturing_Plan_List&gen_search_item_code=' || item_master.item_code
                    || '&gen_search_match_mode_gen_search_item_code=3'
                    || '&gen_search_plan_Year=' || to_char(plan_date,'yyyy')
                    || '&gen_search_plan_Month=' || to_char(plan_date,'mm') end as link
                ,'' as remarks
                ,'' as user_name
                ,plan_date as record_update_date
            from
                temp_plan_remained
                left join item_master on temp_plan_remained.item_id = item_master.item_id
            where
                plan_quantity <> 0
                and plan_date between {$dateCrit}
                and plan_date > '" . date('Y-m-d') . "'  /* 明日以降納期分のみ。上のコメント参照 */
                " . (is_numeric($itemId) ? " and temp_plan_remained.item_id = {$itemId}" : (is_array($itemId) ? " and temp_plan_remained.item_id in (" . join($itemId, ",") . ")" : "")) . "
                /* 製番は常に「なし」（計画ベースの場合、入庫時に製番フリーになるため）とみなされる */
                " . (!($seiban === null) ? " and '' = '{$seiban}'" : "") . "
            ";

            if ($isIncludeUsePlan) {

                $query .= "
                /* ●使用予約 (引当は受注残に含まれているのでここでは含めない) */
                union all
                select
                    use_plan.item_id
                    /* 製番はuse_planのものではなくorder_detailのものを使うことに注意。 */
                    /* use_planのうち、order_header_idが記録されているレコードは使用予定数（製造指示書により登録）である。*/
                    /* （use_plan.received_detail_id は製番ではなく受注引当を表す）*/
                    /* ただ、製番品目で計画ベースのオーダーの場合、オーダー製番と在庫製番が異なるのが微妙だが・・。*/
                    ,(coalesce(order_detail.seiban,'')) as seiban
                    ,0 as location_id
                    ,0 as lot_id
                    ,use_date as date
                    ,0 as in_qty
                    ,0 as out_qty
                    ,0 as in_plan_qty
                    ,(quantity) as out_plan_qty
                    ,0 as logical_stock_quantity
                    ,0 as available_stock_quantity
                    ,case when order_detail.order_no is null then
                        /* ダミー品目を受注した場合の子品目仕様予約 */
                        '" . _g("子品目使用予約（親受注製番：") . "' || coalesce(received_detail.seiban,'" . _g("不明") . "')
                        || '／' || '" . _g("親品目：") . "' || coalesce(t_received_item.item_name,'" . _g("不明") . "') || '）'
                     else
                        '" . _g("子品目使用予約（親オーダー番号：") . "' || order_detail.order_no
                        || '／' || '" . _g("親品目：") . "' || item_master.item_name || '）'
                     end as description
                    ,case
                        when order_detail.order_no is null then ''
                        when order_header.classification = 0 then 'Manufacturing_Order_List&gen_search_order_no=' || order_detail.order_no || '&gen_search_match_mode_gen_search_order_no=3'
                        when order_header.classification = 2 then 'Partner_Subcontract_List&gen_search_order_no=' || order_detail.order_no || '&gen_search_match_mode_gen_search_order_no=3'
                        else 'Partner_Order_List&gen_search_order_no=' || order_detail.order_no || '&gen_search_match_mode_gen_search_order_no=3' end as link
                    ,'' as remarks
                    ,'' as user_name
                    ,coalesce(use_plan.record_update_date, use_plan.record_create_date) as record_update_date
                from
                    use_plan
                    left join order_detail on use_plan.order_detail_id = order_detail.order_detail_id
                    left join order_header on order_detail.order_header_id = order_header.order_header_id
                    left join item_master on order_detail.item_id = item_master.item_id
                    left join received_detail on use_plan.received_detail_id_for_dummy = received_detail.received_detail_id
                    left join item_master t_received_item on received_detail.item_id = t_received_item.item_id
                where
                    use_plan.received_detail_id is null    /* 引当は含めない */
                    and quantity <> 0
                    and use_date between {$dateCrit}
                    " . (is_numeric($itemId) ? " and use_plan.item_id = '{$itemId}'" : (is_array($itemId) ? " and use_plan.item_id in (" . join($itemId, ",") . ")" : "")) . "
                    " . (!($seiban === null) ? " and coalesce(order_detail.seiban,'') = '{$seiban}'" : "") . "
                ";
            }
        }

        $query .= " order by date, record_update_date ";

        $gen_db->query($query);


        // temp_stock に前在庫情報を取得
        $beforeDate = date('Y-m-d', strtotime($dateFrom) - (3600 * 24));
        // $isCalcStockBySum がtrueのとき、前在庫取得は・・
        //        個別指定：　    そのまま個別指定
        //        合計（sum）：    そのままsum
        //        個別（null）：    sumで取得する

        $seiban_for_before = $seiban;
        $locationId_for_before = $locationId;
        $lotId_for_before = $lotId;

        Logic_Stock::createTempStockTable(
                $beforeDate
                , $itemId
                , $seiban_for_before
                , $locationId_for_before
                , $lotId_for_before
                , true
                , $isIncludePartnerStock
                , $isUsePlanAllMinus
        );

        // temp_inout（入出庫）にtemp_stock（前在庫）を結合。
        //  id順に取り出されることを前提に、前在庫のidは-1とする。
        $query = "
        insert into temp_inout (
            id, item_id, seiban, location_id, lot_id, date, in_qty, out_qty, in_plan_qty, out_plan_qty
            ,logical_stock_quantity, available_stock_quantity, description, remarks, user_name)
        select
            -1
            ,item_id
            ,seiban
            ,location_id
            ,lot_id
            ,null as date
            ,coalesce(total_in_qty,0) + coalesce(last_inventory_quantity,0) as in_qty
            ,total_out_qty as out_qty
            ,coalesce(total_in_plan_qty,0) as in_plan_qty
            ,total_out_plan_qty as out_plan_qty
            ,logical_stock_quantity
            ,available_stock_quantity
            ,case when last_inventory_date is null then '" . _g("前在庫：なし") . "'
                else '" . _g("前在庫(最終棚卸日：") . "' || cast(last_inventory_date as text) || '" . _g("合計棚卸数：") . "'
                || cast(last_inventory_quantity as text) || ')' end as description
            ,'' as remarks
            ,'' as user_name
        from
            temp_stock
        ";

        $gen_db->query($query);

        // 棚卸差数の計算
        //  期間中に棚卸がある場合、temp_inout の　「棚卸・・」行のlogical_stock_quantity に棚卸数が入った状態になっている。
        //  棚卸日までの理論在庫を計算し、棚卸数との差数を棚卸差数として入庫（in_qty）欄に入れる。
        //  日付順に計算する必要があるため、日付ごとにSQLを実行している。
        $query = "select inventory_date from inventory where inventory_date between {$dateCrit} group by inventory_date order by inventory_date";
        $arr = $gen_db->getArray($query);

        if (is_array($arr)) {
            foreach ($arr as $row) {
                $query = "
                update
                    temp_inout
                set
                    in_qty =
                        (select
                            coalesce(temp_inout.logical_stock_quantity,0) -sum(coalesce(in_qty,0)-coalesce(out_qty,0))
                        from
                            temp_inout as t1
                        where
                            (temp_inout.date >= t1.date or id = -1)
                            and temp_inout.item_id = t1.item_id
                            and coalesce(temp_inout.seiban,'') = coalesce(t1.seiban,'')
                            and coalesce(temp_inout.location_id,0) = coalesce(t1.location_id,0)
                            and coalesce(temp_inout.lot_id,0) = coalesce(t1.lot_id,0)
                        )
                where
                    description like '%" . _g("棚卸") . "%'
                    and date = '{$row['inventory_date']}'
                ";
                $gen_db->query($query);
            }
        }

        // temp_inout の理論在庫・有効在庫数を更新（日ごとの入出庫数を累積加減算）
        //  前の行から順に計算するため、temp_inout.id（serialなので自動的に生成されている）を頼っていることに注意
        //  前のほうでインデックス temp_inout_index1 を作成していることに注意。これの有無で劇的に速度が変わる
        //  場合がある（Sycom 1万品目  60sec ⇒　0.5sec） */
        $query = "
        create temp table temp_stock_calc as
        select
            t0.id
            ,SUM(logical) as logical
            ,SUM(available) as available
        from (
            select
                item_id, seiban, location_id, lot_id, date, MAX(id) as id
            from
                temp_inout
            where
                id<>-1
            group by
                item_id, seiban, location_id, lot_id, date " . ($isDailyStockMode ? "" : ",id") . "
            ) as t0
        inner join (
            select
                item_id, seiban, location_id, lot_id, date, MAX(id) as id
                ,SUM(coalesce(in_qty, 0) - coalesce(out_qty, 0)) as logical
                ,SUM(coalesce(in_qty, 0) - coalesce(out_qty, 0) + coalesce(in_plan_qty, 0) - coalesce(out_plan_qty, 0)) as available
            from
                temp_inout
            group by
                item_id, seiban, location_id, lot_id, date " . ($isDailyStockMode ? "" : ",id") . "
            ) as t1
            on t0.id >= t1.id
            and t0.item_id = t1.item_id
            " . ($isCalcStockBySum ? "" : " and coalesce(t0.seiban,'') = coalesce(t1.seiban,'')") . "
            " . ($isCalcStockBySum ? "" : " and coalesce(t0.location_id,0) = coalesce(t1.location_id,0)") . "
            " . ($isCalcStockBySum ? "" : " and coalesce(t0.lot_id,0) = coalesce(t1.lot_id,0)") . "
        group by
            t0.id;

        CREATE INDEX temp_stock_calc_index1 on temp_stock_calc(id);

        update
            temp_inout
        set
            logical_stock_quantity = (
                select
                    logical
                from
                    temp_stock_calc
                where
                    temp_inout.id = temp_stock_calc.id
            )
            ,available_stock_quantity = (
                select
                    available
                from
                    temp_stock_calc
                where
                    temp_inout.id = temp_stock_calc.id
            );

        update
            temp_inout
        set
            logical_stock_quantity = coalesce(in_qty, 0) - coalesce(out_qty, 0)
            ,available_stock_quantity = coalesce(in_qty, 0) - coalesce(out_qty, 0) + coalesce(in_plan_qty, 0) - coalesce(out_plan_qty, 0)
        where
            id=-1;
        ";
        $gen_db->query($query);
    }

    //************************************************
    // 実在庫数を登録する
    //************************************************
    // 該当する在庫がすでに存在することが前提（存在しなければ登録しない）
    // 値が数字でない場合は登録しない

    static function entryRealStock($itemId, $seiban, $locationId, $lotId, $value, $inventoryDate, $remarks)
    {
        global $gen_db;

        if (is_numeric($value)) {
            $gen_db->begin();

            // inventoryテーブルに登録
            $key = array(
                'item_id' => $itemId,
                'seiban' => $seiban,
                'location_id' => $locationId,
                'lot_id' => $lotId,
                'inventory_date' => $inventoryDate,
            );
            $data = array('inventory_quantity' => $value, 'remarks' => $remarks);
            $gen_db->updateOrInsert('inventory', $key, $data);

            $gen_db->commit();
        }
    }

    //************************************************
    // 実在庫数(棚卸)を削除する
    //************************************************

    static function deleteRealStock($itemId, $seiban, $locationId, $lotId, $inventoryDate)
    {
        global $gen_db;

        $gen_db->begin();

        // inventoryを削除
        $query = "
        delete from
            inventory
        where
            item_id = {$itemId}
            and seiban = '{$seiban}'
            and location_id = {$locationId}
            and lot_id = {$lotId}
            and inventory_date = '{$inventoryDate}'::date
        ";
        $gen_db->query($query);

        $gen_db->commit();
    }

    //************************************************
    // 品目・製番・ロケ・ロット　⇒　理論在庫数
    //************************************************
    // 最終理論在庫（現在庫リストで日付を空欄にしたときの理論在庫数）を取得。Pロケ含む

    static function getLogicalStock($itemId, $seiban, $locationId, $lotId)
    {
        global $gen_db;

        Logic_Stock::createTempStockTable("", $itemId, $seiban, $locationId, $lotId, false, true, false);
        $stock = $gen_db->queryOneValue("select logical_stock_quantity from temp_stock");
        if (!is_numeric($stock))
            $stock = 0;
        return $stock;
    }

    //************************************************
    // 末端品目まで展開した理論在庫数を取得
    //************************************************
    // 親品目・中間品目（子を持つ品目）をすべて末端品目（子を持たない品目）まで展開して「内包在庫」として在庫数をカウントする。
    // したがって、ここでリストアップされるのは末端品目のみ。
    // 分解在庫リストで使用。

    static function createFullExpandStockTable($stockDate, $isIncludePartnerStock)
    {
        global $gen_db;

        // 全品目の理論在庫を取得
        Logic_Stock::createTempStockTable($stockDate, null, "sum", "sum", "sum", false, $isIncludePartnerStock, false);

        // 1階層目。子を持たない品目（末端品目）は含まれないことに注意
        $query = "
        create temp table temp_pre_full_expand_stock as
        select
            child_item_id as item_id
            ,temp_stock.logical_stock_quantity * bom_master.quantity as inner_stock
            ,0 as lc
        from
            temp_stock
            inner join bom_master on temp_stock.item_id = bom_master.item_id
        ";

        $gen_db->query($query);

        // 内包在庫の計算
        $lc = 1;
        while (true) {
            $query = "
            insert into temp_pre_full_expand_stock (item_id, inner_stock, lc)
            select
                bom_master.child_item_id
                ,temp_pre_full_expand_stock.inner_stock * bom_master.quantity as inner_stock
                ,{$lc}
            from
                temp_pre_full_expand_stock
                inner join bom_master on temp_pre_full_expand_stock.item_id = bom_master.item_id
            where
                temp_pre_full_expand_stock.lc = " . ($lc - 1) . "
            ";

            $gen_db->query($query);

            $query = "select item_id from temp_pre_full_expand_stock where lc = {$lc}";
            if (!$gen_db->existRecord($query)) {
                break;
            }

            $lc++;
        }

        // 末端品目の内包在庫を集計し、また独立在庫を加算して、結果テーブルを作る。
        //  末端品目（子を持たない品目）のみ。中間品目は下位品目に含まれているので集計しない。
        $query = "
        create temp table temp_full_expand_stock as
        select
            temp_stock.item_id
            ,inner_stock
            ,logical_stock_quantity as logical_stock
            ,coalesce(inner_stock,0) + coalesce(logical_stock_quantity,0) as total_stock
        from
            temp_stock
            left join (
                select
                    item_id
                    ,sum(inner_stock) as inner_stock
                from
                    temp_pre_full_expand_stock
                where
                    /* 子がない品目（末端品目）のみ */
                    /* 親がない品目も排除（内包在庫には含まない）*/
                    item_id not in (select item_id from bom_master)
                    and item_id in (select child_item_id from bom_master)
                group by
                    item_id
            ) as t1 on temp_stock.item_id = t1.item_id
        where
            /* 子がない品目（末端品目）のみ*/
            temp_stock.item_id not in (select item_id from bom_master)
        ";

        $gen_db->query($query);
    }


    //************************************************
    // 指定日時点の在庫評価単価リストを作成
    //************************************************
    // 指定日時点の品目ごとの在庫評価単価リストをテンポラリテーブルとして作成する。
    // 
    // その品目の在庫評価単価更新が一度も行われていないか、指定日付がその品目の最後の在庫評価単価更新日以後であれば、
    // 品目マスタの在庫評価単価を参照する。
    // それ以外であれば、在庫評価単価の履歴テーブル（stock_price_history）を参照する。

    static function createTempStockPriceTable($stockDate, $itemId = null)
    {
        global $gen_db;
        
        $query = "
            select
                item_master.item_id
                
                /*
                下記のいずれかであれば、品目マスタの在庫評価単価を使用する。
                ・その品目の在庫評価単価更新が一度も行われていない
                ・指定日付がその品目の最初の在庫評価単価更新日より前である（⇒指定日時点の履歴情報がない）
                ・指定日付がその品目の最後の在庫評価単価更新日以後である（⇒品目マスタを直接書き換えている可能性がある）
                */
                ,case when t_history_2.last_assessment_date_2 is null 
                  or t_history.last_assessment_date <= '{$stockDate}' then
                    item_master.stock_price
                /*
                上記以外であれば、在庫評価単価の履歴データを使用する
                */
                 else
                    t_history_3.stock_price
                 end as stock_price
                
                /* 履歴データを使用したかどうかのフラグ。*/
                /* 最新データ（item_master.stock_price）なら false、履歴データなら true */
                ,case when t_history_2.last_assessment_date_2 is null 
                  or t_history.last_assessment_date <= '{$stockDate}' then false else true end
                  as use_history
                
            from
                item_master
                left join 
                    (select
                        item_id
                        ,max(assessment_date) as last_assessment_date
                     from
                        stock_price_history
                     group by
                        item_id
                     ) as t_history
                    on item_master.item_id = t_history.item_id
                left join 
                    (select
                        item_id
                        ,max(assessment_date) as last_assessment_date_2
                     from
                        stock_price_history
                     where
                        assessment_date <= '{$stockDate}'
                     group by
                        item_id
                     ) as t_history_2
                    on item_master.item_id = t_history_2.item_id
                left join 
                    stock_price_history as t_history_3
                        on item_master.item_id = t_history_3.item_id
                        and t_history_2.last_assessment_date_2 = t_history_3.assessment_date
                " . (is_numeric($itemId) ? "where item_master.item_id = {$itemId}" : "") . "
        ";
        
        $gen_db->createTempTable("temp_stock_price", $query, true);
    }
    
    // 上の関数の品目指定版。テーブルではなく在庫評価単価を直接返す。
    static function getStockPrice($stockDate, $itemId)
    {
        global $gen_db;
        
        self::createTempStockPriceTable($stockDate, $itemId);
        
        return $gen_db->queryOneValue("select stock_price from temp_stock_price");
    }
}