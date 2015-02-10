<?php

require_once(COMPONENTS_DIR . "String.class.php");
require_once(COMPONENTS_DIR . "Date.class.php");
require_once(LOGIC_DIR . "Stock.class.php");

// ここから他のクラスのメソッドを呼ぶときは、そのメソッドはstatic宣言しておく必要がある
// （宣言されていないとPHPのバージョン・設定によりエラーになることがある）
//
//************************************************
// エラーハンドラ
//************************************************
// エラー発生時には進捗記録ファイルを消去しておく必要がある。

function userErrorHandler($errno, $errstr, $errfile, $errline)
{
    restore_error_handler();
    self::deleteProgress();

    error_log("$errno, $errstr, $errfile, $errline");    // php.iniで設定されたログファイルにログする
    exit;
}

//************************************************
// メインクラス
//************************************************

class Logic_Mrp
{

    var $user_name;

    //===============================================================================================================
    // MRP・製番共通部分
    //===============================================================================================================
    //
    //************************************************
    // 所要量計算実行
    //************************************************
    //
    // 通常モードと内示モード（第3引数 true）の違いは、通常モードでは受注「確定」のみが対象だが、
    // 内示モードでは受注「予約」も対象になるという点だけ。
    function mrpMain($startDateStr, $endDateStr, $isNaiji, $isNonSafetyStock, $userName, $days)
    {
        global $gen_db;

        $startDate = strtotime($startDateStr);
        $endDate = strtotime($endDateStr);

        // 実行ユーザー名をクラス変数に保存
        $this->user_name = $userName;

        //-----------------------------------------------------------
        // データチェック
        //-----------------------------------------------------------
        // 品目マスタ登録チェック
        if ($gen_db->queryOneValue("select COUNT(*) from item_master") == 0) {
            self::deleteProgress();
            return false;
        }

        // STARTとendが逆だったときの対処
        //    UIでチェックしているが、ここでも一応チェック
        if ($startDate > $endDate) {
            $temp = $endDate;
            $endDate = $startDate;
            $startDate = $temp;
        }

        // 期間制限（誤設定により長期間busyになるのを避ける）
        // 期間制限はgen_configで指定
        if ($endDate - $startDate > 3600 * 24 * $days) {
            self::deleteProgress();
            return false;
        }

        //-----------------------------------------------------------
        // 進捗状況テーブルの初期化
        //-----------------------------------------------------------
        // 実際の進捗ファイル作成と初期化は Manufacturing_Mrp_Mrp->execute()内で行われるため、
        // 通常はここで行われることはない（進捗ファイルが既存なので何もせず帰ってくる）。
        // このクラスが Manufacturing_Mrp_Mrpを経由せず単体実行されたときのみ意味を持つ。
        self::initProgress();

        //-----------------------------------------------------------
        // 実行時間制限をはずす
        //-----------------------------------------------------------
        // 無制限にする。PHPがセーフモードの場合は効かない
        // このスクリプトの中だけで有効
        set_time_limit(0);

        //-----------------------------------------------------------
        // エラーハンドラの定義（エラー時に進捗ファイルを削除するため）
        //-----------------------------------------------------------
        //$old_error_handler = set_error_handler("userErrorHandler");

        //-----------------------------------------------------------
        // トランザクション開始
        //-----------------------------------------------------------

        $gen_db->begin();

        //-----------------------------------------------------------
        // 結果テーブルのクリア
        //-----------------------------------------------------------

        $gen_db->query("delete from mrp");

        //-----------------------------------------------------------
        // 着手日テーブルを作成
        //-----------------------------------------------------------

        self::writeProgress(_g("着手日テーブルを作成中"), 5);
        self::makeDayTable($startDate, $endDate);

        //-----------------------------------------------------------
        // 計画一覧テーブルを作成
        //-----------------------------------------------------------

        self::writeProgress(_g("計画一覧テーブルを作成中"), 9);
        self::makePlanListTable($startDate, $endDate, $isNaiji);

        //-----------------------------------------------------------
        // 棚卸日テーブルを作成
        //-----------------------------------------------------------

        self::writeProgress(_g("棚卸日テーブルを作成中"), 10);
        self::makeInventoryDateTable($startDate);

        //-----------------------------------------------------------
        // 製番展開
        //-----------------------------------------------------------

        self::writeProgress(_g("製番展開中"), 30);
        self::seiban($startDate, $endDate);

        //-----------------------------------------------------------
        // MRP計算
        //-----------------------------------------------------------

        self::writeProgress(_g("MRP計算中"), 40);
        self::mrp($startDate, $endDate, $isNonSafetyStock);

        //-----------------------------------------------------------
        // 工程テーブル（mrp_process）を作成
        //-----------------------------------------------------------

        self::writeProgress(_g("工程の処理中"), 90);
        self::mrpProcess($startDate);

        //-----------------------------------------------------------
        // 最終実行日時の記録と進捗状況テーブルの消去
        //-----------------------------------------------------------

        self::writeProgress(_g("終了処理中"), 95);
        // Winの場合は$userNameに「'」がついているので削除。Linuxの場合はつかない
        if (substr($userName, 0, 1) == "'")
            $userName = substr($userName, 0, 1);
        if (substr($userName, -1) == "'")
            $userName = substr($userName, 0, strlen($userName) - 1);
        $userName = $gen_db->quoteParam($userName);
        $gen_db->query("UPDATE company_master SET last_mrp_date = '" . date('Y-m-d H:i:s') . "', last_mrp_user = '{$userName}'");

        //-----------------------------------------------------------
        // トランザクションのコミット
        //-----------------------------------------------------------

        $gen_db->commit();

        //-----------------------------------------------------------
        // 結果テーブルのvacuum
        //-----------------------------------------------------------

//        $gen_db->query("vacuum full ANALYZE mrp");

        //-----------------------------------------------------------
        // 進捗ファイル削除
        //-----------------------------------------------------------
        self::deleteProgress();

        //-----------------------------------------------------------
        // エラーハンドラを元に戻す
        //-----------------------------------------------------------

        restore_error_handler();

        // エラーにならずにここまでこれば・・。
        return true;
    }

    //************************************************
    // 進捗状況をファイルに書き込む
    //************************************************
    // データベースに書き込むとトランザクション終了まで他トランから
    // 更新内容が見えないので、テキストファイルを使用する。
    //
    // 進捗の記録開始
    // 進捗ファイルが既存（すでに実行中）であればfalseを返す
    // ちなみに実際の進捗ファイル作成は Manufacturing_Mrp_Mrp->execute()内で行われるため、
    // 通常はここが使用されることはない。これはこのクラスが Manufacturing_Mrp_Mrpを
    // 経由せず単体実行されたときのための関数。
    function initProgress()
    {
        $storage = new Gen_Storage("MRPProgress");
        if ($storage->exist('mrp_progress.dat')) {
            return false;
        }

        $fp = fopen(GEN_TEMP_DIR . 'mrp_progress.dat', 'w');
        fputs($fp, date("Y-m-d H:i:s") . "," . $this->user_name . "," . _g("初期化処理中") . ",0");
        fclose($fp);
        $storage->put(GEN_TEMP_DIR . 'mrp_progress.dat', true);

        return true;
    }

    // 進捗の記録
    function writeProgress($status, $percent)
    {
        // 最初の項目（開始時刻）は変数に保持するのが面倒なので、進捗ファイルから
        // 読み取る。そのため、最初は読み取りで開く必要がある（r+でもいいが）
        $storage = new Gen_Storage("MRPProgress");
        $pFile = $storage->get('mrp_progress.dat');
        $fp = fopen($pFile, 'r');
        $data = explode(",", fgets($fp));
        fclose($fp);    // ポインタを先頭に戻すため一度クローズ
        $data = $data[0] . "," . $this->user_name . "," . $status . "," . $percent;
        file_put_contents($pFile, $data, LOCK_EX);   // LOCK_EXはPHP5.1以降
        $storage->put($pFile, true);
    }

    // 終了処理。進捗ファイルを削除する
    function deleteProgress()
    {
        $storage = new Gen_Storage("MRPProgress");
        $storage->delete('mrp_progress.dat');
    }

    //************************************************
    // 着手日テーブルを作成
    //************************************************
    //
    // 完成日とリードタイムから、着手日を求めるためのテーブル（temp_mrp_day_table）を作成する。
    // （着手日計算は、休日の要素があるため単純な計算はできない。よってテーブルが必要）
    //
    // 09iまではここで品目マスタからLTパターンをピックアップして計算していたが、
    // 10iからはLTが可変になる場合があるため、すべてのLTを計算するようになった。
    //
    // 着手日が対象期間より前になる場合、対象期間開始日を着手日とし、アラームフラグを立てる。
    // ※仮に着手日が計算対象開始日以前になることを許すとすると、この上の初期在庫計算を
    //   考え直さなくてはならない（計算対象開始日以前の在庫は計算されていない）

    function makeDayTable($finishDayFrom, $finishDayTo)
    {
        global $gen_db;

        // テンポラリテーブルを作成（セッション終了時に自動破棄される。他セッションからは見えない）
        $query = "
        create temp table temp_mrp_day_table (
            finish_day date not null
            ,lead_time int not null
            ,begin_day date not null
            ,alarm_flag int not null
        )
        ";
        $gen_db->query($query);

        // 対象期間の日数
        $days = ($finishDayTo - $finishDayFrom) / 3600 / 24;

        // 休日データを取得
        $holidayArr = Gen_Date::getHolidayArray($finishDayFrom, $finishDayFrom + ($days * 3600 * 24));

        // 着手日テーブルを作る
        for ($i = 0; $i <= $days; $i++) {                   // 開始日からの日数
            $calcDate = $finishDayFrom + ($i * 3600 * 24);  // 計算対象日（計算開始日から$i日後）
            for ($lt = 0; $lt <= $i; $lt++) {               // LT
                // 以下のロジックは Gen_Date::getOrderDate() と同じ。
                // しかし対象期間が長い時の計算時間を短縮するため、上記を呼び出さずここで処理している。
                $date = $calcDate;

                // リードタイム日分ずらしていき着手日を求める
                $alarm = "0";
                for ($d = 1; $d <= $lt; $d++) {
                    $date -= (3600 * 24);       // 一日戻す
                    while (in_array(date('Y-m-d', $date), $holidayArr)) {
                        $date -= (3600 * 24);   // 一日戻す
                    }
                    // 着手日が対象期間より前になる場合、対象期間開始日を着手日とし、アラームフラグを立てる
                    if ($date < $finishDayFrom) {
                        $date = $finishDayFrom;
                        $alarm = "1";
                        break;
                    }
                }

                $query = "
                insert into temp_mrp_day_table (
                    finish_day,
                    lead_time,
                    begin_day,
                    alarm_flag
                )
                values (
                    '" . date('Y-m-d', $calcDate) . "',
                    {$lt},
                    '" . date('Y-m-d', $date) . "',
                    {$alarm}
                )
                ";
                $gen_db->query($query);
            }
        }
    }

    //************************************************
    // 独立需要テーブル（テンポラリ）を作成
    //************************************************
    //    ビューにする方法もあるが、複数回使用されるのでパフォーマンスを考えてテーブル使用にした。
    //    （現実的には、ビューとテンポラリテーブルのどちらが速いかは微妙だが・・）
    //  ここで作成するテーブルはテンポラリテーブルなので、別セッションからは見えない。

    function makePlanListTable($startDate, $endDate, $isNaiji)
    {
        global $gen_db;

        $query = "create temp table temp_plan_list as ";

        for ($i = 1; $i <= 31; $i++) {
            $query .= "
            select
                cast(plan_year || '-' || plan_month || '-{$i}' as date) as plan_date
                ,seiban
                ,plan.item_id
                ,classification   /* 0(計画)/3(計算)。受注、所要量計算結果調整 */
                /*  MRP品目の場合は、MRP計算で在庫にかかわらず計画数を強制オーダーしているため、オーダー発行済み分はここで引いておく必要がある。 */
                /*  製番品目の場合はもともと製番展開の中でオーダー済み分の差し引き処理があるので、ここでは行わない。 */
                ,day{$i} - (case when order_class in (1,2) then coalesce(order{$i},0) else 0 end) as plan_quantity
            from
                plan
                left join item_master on plan.item_id = item_master.item_id
            where
                day{$i} is not null and day{$i} <> 0
                and classification in (0,3)
            ";

            if ($i != 31) {
                $query .= " UNION ALL ";
            }
        }

        // 受注数を取得。
        // 　数量は「受注数 - (引当数 + フリー在庫納品済数)」。つまり未引当かつ未納品の数のみを含める。
        // 　また「完了」扱いになっている受注は除く。
        // 　第3引数がtrueのときは、受注「予約」レコードも含める。
        //   また、MRP計算の冒頭でロット品目の独立需要数を調整していることに注意。
        //      MRP計算の初期在庫計算部分のコメントを参照。
        $query .= "
        UNION ALL
            select
                dead_line
                ,seiban
                ,received_detail.item_id
                ,1 as classification     /* 受注 */
                ,COALESCE(received_quantity,0) - COALESCE(use_plan_quantity,0) as plan_quantity
            from
                item_master
                inner join received_detail on item_master.item_id=received_detail.item_id
                left join received_header on received_detail.received_header_id = received_header.received_header_id
                left join (
                    select
                        use_plan.received_detail_id
                        ,COALESCE(SUM(use_plan.quantity),0)+COALESCE(MAX(T0.delivery_qty),0) as use_plan_quantity
                    from
                        use_plan
                        left join (
                            select
                                received_detail_id
                                ,SUM(free_stock_quantity) as delivery_qty
                            from
                                delivery_detail
                            group by
                                received_detail_id
                            ) as T0 on use_plan.received_detail_id = T0.received_detail_id
                    where
                        use_plan.received_detail_id is not null
                        and use_plan.quantity<>0
                    group by
                        use_plan.received_detail_id
                    ) as T1 on received_detail.received_detail_id = T1.received_detail_id
                left join (
                    select
                        received_detail_id_for_dummy
                    from
                        use_plan
                    where
                        received_detail_id_for_dummy is not null
                    group by
                        received_detail_id_for_dummy
                ) as T2 on received_detail.received_detail_id = T2.received_detail_id_for_dummy
            where
                " . ($isNaiji ? "" : "received_header.guarantee_grade=0 and") . "
                COALESCE(received_quantity,0) - COALESCE(use_plan_quantity,0) <> 0
                /* 完納扱いの受注は除外 */
                and (delivery_completed = false or delivery_completed is null)
                and dead_line between '" . date('Y-m-d', $startDate) . "'::date and '" . date('Y-m-d', $endDate) . "'::date
        ";
        $gen_db->query($query);
    }

    //************************************************
    // 棚卸日テーブルの作成
    //************************************************
    // 計算期間前の最後の棚卸日

    function makeInventoryDateTable($startDate)
    {
        global $gen_db;

        $beforeDateStr = date('Y-m-d', $startDate - (3600 * 24)); // 計算開始日の前日
        $query = "
        create temp table temp_inventory_date as
        select
            item_id
            ,seiban
            ,location_id
            ,lot_id
            ,max(inventory_date) as last_inventory_date
        from
            inventory
        where
            inventory_date <= '{$beforeDateStr}'::date
        group by
            item_id
            ,seiban
            ,location_id
            ,lot_id
        ";
        $gen_db->query($query);
    }

    //************************************************
    // 最終処理：　工程テーブル（mrp_process）の作成
    //************************************************

    function mrpProcess($startDate)
    {
        global $gen_db;

        $gen_db->query("truncate mrp_process");

        $query = "
        insert into mrp_process (
            item_id
            ,seiban
            ,process_id
            ,machining_sequence
            ,process_dead_line
            ,arrangement_quantity
            ,order_class
            ,order_flag
            ,alarm_flag
            ,llc
            ,process_lt
            ,pcs_per_day
        )
        select
            mrp.item_id
            ,mrp.seiban
            ,process_id
            ,machining_sequence
            ,arrangement_finish_date as process_dead_line
            ,mrp.arrangement_quantity
            ,order_class
            ,order_flag
            ,0 as alarm_flag
            ,llc
            -- LT未指定のときは、工程LTからLTを計算する。工程LTが空欄なら「(オーダー数÷製造能力)-1」。Logic_Mrpの着手日計算と同じ
            -- 「-1」しているのは、製造が1日以内で終わるときはLT=0、足掛け2日かかる場合はLT=1とする必要があるため
            ,coalesce(process_lt, trunc(mrp.arrangement_quantity / coalesce(case when pcs_per_day=0 then 1 else pcs_per_day end,1) + 0.9999999999)-1) as process_lt
            ,pcs_per_day
        from
            mrp
            inner join item_process_master on mrp.item_id = item_process_master.item_id
            left join item_order_master on mrp.item_id = item_order_master.item_id
                and item_order_master.line_number = 0
        where
            arrangement_quantity <> 0
            and order_class <> '99'     -- 製番引当は除く
            and partner_class = 3       -- 内製のみ
        ";
        $gen_db->query($query);

        // 工程ごとに納期をずらす
        // 各製番/品目の、最後の工程からさかのぼって納期を決めていく
        // この時点では、process_dead_line には工程納期ではなくオーダー全体の納期が入っていることに注意（そのためオーダーの区別に使える）
        $query = "select * from mrp_process order by item_id, seiban, process_dead_line, machining_sequence desc";
        $arr = $gen_db->getArray($query);
        if (!is_array($arr))
            return;
        $itemIdCache = "";
        $seibanCache = "";
        $deadlineCache = "";
        $deadLine = "";
        $alarmFlag = '0';
        foreach ($arr as $row) {
            // 最後の条件は、同一品目・同一製番で複数のオーダーがあったときに混ざってしまわないため。
            // この時点では、$row['process_dead_line'] には工程納期ではなくオーダー全体の納期が入っていることに注意（そのためオーダーの区別に使える）
            if ($row['item_id'] != $itemIdCache || $row['seiban'] != $seibanCache || $row['process_dead_line'] != $deadlineCache) {
                $itemIdCache = $row['item_id'];
                $seibanCache = $row['seiban'];
                $deadlineCache = $row['process_dead_line'];
                $deadLine = $deadlineCache;
            }
            $data = array(
                "process_dead_line" => $deadLine,
                "alarm_flag" => $alarmFlag,
            );
            $where = "mrp_process_id = {$row['mrp_process_id']}";
            $gen_db->update("mrp_process", $data, $where);

            // 次の工程の納期を決める
            // 2010: 可変LTに対応
            $plt = $row['process_lt'];
            $query = "select begin_day, alarm_flag from temp_mrp_day_table where finish_day = '{$deadLine}' and lead_time = '{$plt}'";
            $obj = $gen_db->queryOneRowObject($query);
            if ($obj) {
                $deadLine = $obj->begin_day;
                $alarmFlag = $obj->alarm_flag;
            } else {
                // 着手日テーブルに含まれないLTはアラーム
                $deadLine = date('Y-m-d', $startDate);
                $alarmFlag = '1';
            }
        }
    }

    //===============================================================================================================
    // 製番展開関連
    //===============================================================================================================
    //
    //************************************************
    // 製番展開準備：　製番展開テーブル（テンポラリ）を作成
    //************************************************
    // このテーブルに製番展開の結果が格納され、あとでmrpテーブルに転記される。
    // 以前は製番展開の結果を直接mrpテーブルに挿入していたが、製番在庫差し引きや自動製番引当機能の実装により
    // 製番展開テーブルにid列が必要となった。しかしmrpテーブルに自動インクリメント列を作ると、データが大きいときの
    // mrp計算のパフォーマンスが心配。それで、製番展開はid列付きのテンポラリテーブルで行い、最後に転記することにした。

    function makeSeibanTable()
    {
        global $gen_db;

        $query = "
        create temp table temp_seiban (
            id serial
            ,seiban text
            ,parent_id int   /* 製番引当時の子オーダーキャンセル時に、親オーダーを識別するためのid */
            ,inzu numeric
            ,location_id int
            ,item_id int
            ,calc_date date
            ,arrangement_quantity numeric
            ,arrangement_start_date date
            ,arrangement_finish_date date
            ,llc int
            ,alarm_flag int
            ,order_class int
            ,arrangement_quantity_for_child numeric
            ,plan_qty numeric
            ,hand_qty numeric
        )
        ";
        $gen_db->query($query);
    }

    //************************************************
    // 製番展開準備： 使用可能数テーブル（テンポラリ）を作成
    //************************************************
    // フリー製番分だけを含める（製番在庫は除外）
    // これをMRPと共通化することも試したが、MRPがかなり遅くなったのでやめた

    function makeSeibanUsableStockTable($startDate, $endDate)
    {
        global $gen_db;

        // パラメータの設定
        $startDateStr = date('Y-m-d', $startDate);
        $beforeDateStr = date('Y-m-d', $startDate - (3600 * 24)); // 計算開始日の前日

        $calcStart = $startDate;
        $dayCount = ($endDate - $calcStart) / (3600 * 24);

        // 前在庫算定の基準日の決定
        //  現在処理月の初日。
        //  前在庫は、現在処理月の月初在庫（=前月末在庫）に、現在処理月の初日 から 計算開始日の前日
        //  までの入出庫を足し引きして求める。
        //  計算開始日が、現在処理月の2日以降であることが前提となる（function mrpMainでチェックしている）
        //  Acc版（v1.1.1）では「計算対象開始日を含む月の1日」にしていたが、それだと
        //  現在処理月と計算対象開始日の月が異なる場合に正しく計算されない。
        //  2008で、在庫設計の変更により、計算の起点を「現在処理月の初日」から、品目ごとの最終棚卸日
        //   （ただし計算期間初日より前）に変更した
        // テンポラリテーブル作成
        $query = "
        create temp table temp_usable_stock (
            item_id int
            ,location_id int
            ,calc_date date
            ,before_usable_qty numeric
            ,in_out_qty numeric
            ,usable_quantity numeric
        )
        ";
        $gen_db->query($query);

        // 日付ループ
        for ($i = -1; $i <= $dayCount; $i++) {

            // 現在処理中のLLC階層について、1日分の計算を行う。
            // 休日であっても計画や入出庫が発生することがあるかもしれないので、
            // 休日分も計算を行う。ただし休日に手配が発生することはないようにする

            $date = date('Y-m-d', $calcStart + ($i * 3600 * 24));
            $yesterday = date('Y-m-d', $calcStart + (($i - 1) * 3600 * 24));

            // 日付範囲
            if ($i == -1) {
                // 前在庫計算
                $dateCriteria = "< '{$startDateStr}'::date ";
            } else {
                // 日次
                $dateCriteria = "= '{$date}'::date ";
            }

            // メインSQL。
            $query = "
            insert into temp_usable_stock (
                item_id
                ,location_id
                ,calc_date
                ,before_usable_qty
                ,in_out_qty
                ,usable_quantity
            )
            select
                 item_id
                 ,location_id
                 ,'{$date}'::date as calc_date
                 ,before_usable_qty
                 ,in_out_qty
                 ,COALESCE(before_usable_qty,0)
                   + COALESCE(in_out_qty,0) + COALESCE(order_remained_qty,0) - COALESCE(use_plan_qty,0) as usable_quantity
            from
                 (select
                     item_id
                     ,location_id
                     ,before_usable_qty
                     ,in_out_qty
                     ,order_remained_qty
                     ,use_plan_qty
                 from
                     (select
                         t_base.item_id
                         ,t_base.location_id
                         ,before_usable_qty
                         ,COALESCE(in_out_qty,0) as in_out_qty
                         ,COALESCE(order_remained_qty,0) as order_remained_qty
                         " . ($i == -1 ? ",use_plan_qty" : ",0 as use_plan_qty") . "
                     from
                        /* ●ベースになるのはitem_master と location_master */
                        (select
                            item_id
                            ,location_id
                            ,lead_time
                            ,safety_lead_time
                        from
                            item_master
                            CROSS JOIN (
                                (select location_id from location_master where location_master.customer_id is null)  /* サプライヤーロケ分は排除 */
                                union (select 0 as location_id)    /* デフォルト(既定)ロケ（location_id = 0)を追加 */
                            ) as t_base_1
                        where
                            item_master.order_class = 0  /* 製番品目のみ */
                        ) as t_base
               		left join
            ";

            if ($i == -1) {
                // ●初期在庫（期間前の最終棚卸在庫[inventory]）
                $query .= "
                    (select
                        inventory.item_id
                        ,inventory.location_id
                        ,SUM(inventory_quantity) as before_usable_qty
                    from
                        inventory
                        inner join temp_inventory_date
                            on inventory.item_id = temp_inventory_date.item_id
                            and inventory.location_id = temp_inventory_date.location_id
                            and coalesce(inventory.seiban,'') = coalesce(temp_inventory_date.seiban,'')
                            and inventory.lot_id = temp_inventory_date.lot_id
                            and inventory.inventory_date = temp_inventory_date.last_inventory_date
                        inner join item_master on inventory.item_id = item_master.item_id
                    where
                        /* 製番在庫は排除。製番展開における使用可能数の使用目的がフリー在庫数を調べることであるため。 */
                        coalesce(inventory.seiban,'') = ''
                    group by
                        inventory.item_id, inventory.location_id
                    ) as t1
                    on t_base.item_id = t1.item_id
                        and t_base.location_id = t1.location_id
                ";
            } else {
                // 日次
                // ●前日利用可能数
                $query .= "
                    (select
                        item_id
                        ,location_id
                        ,SUM(usable_quantity) as before_usable_qty
                    from
                        temp_usable_stock
                    where
                        calc_date = '{$yesterday}'::date
                    group by
                        item_id
                        ,location_id
                    ) as t_before_usable_qty
                    on t_base.item_id = t_before_usable_qty.item_id
                        and t_base.location_id = t_before_usable_qty.location_id
                ";
            }

            // ●発注製造残
            $query .= "
                left join (
                    select
                        item_id
                        ,SUM(case when order_detail_completed then 0 else COALESCE(order_detail_quantity,0) - COALESCE(accepted_quantity,0) end) as order_remained_qty
                    from
                        order_detail
                    where
                        order_detail_dead_line {$dateCriteria}
                        /* 製番品目の場合、計画ベースオーダー（入庫時にフリーになる）のみ。 */
                        /* 製番展開における使用可能数の使用目的がフリー在庫数を調べることであるため。 */
                        and (coalesce(seiban,'') = '' or seiban not in (select seiban from received_detail))
                        /* 外製工程は除く。オーダーは出ているが、受入時に在庫が増えるわけではないため */
                        and (subcontract_order_process_no is null or subcontract_order_process_no = '')
                    group by
                        item_id
                    ) as t_order_remained_qty
                    on t_base.item_id = t_order_remained_qty.item_id
                        and t_base.location_id = 0
            ";

            // ●使用予約・引当数
            //   使用予約は前在庫計算のときに一括で引く（日付無関係）
            //     引当横取り対処にもなっている
            //    use_planのうち、受注ベースのオーダーの子品目使用予約は計算から除外するようにした。
            //    （受注引当、および計画ベースオーダーの使用予約だけ含める）
            //    このfunctionで計算対象となっているのはフリー在庫であり、製番在庫の使用予約分（受注ベースオーダー）
            //    まで使用可能数から差し引くと、引きすぎになってしまう。
            if ($i == -1) {
                $query .= "
                    left join (
                        select
                            item_id
                            ,SUM(case when coalesce(quantity,0) > coalesce(received_base_useplan,0) then
                                coalesce(quantity,0)-coalesce(received_base_useplan,0) else 0 end) as use_plan_qty
                        from
                            use_plan
                            /* 受注ベースの子品目使用予約数を計算。 */
                            left join (
                                select
                                    order_detail.order_header_id
                                    ,child_item_id
                                    ,sum((coalesce(order_detail_quantity,0)-coalesce(accepted_quantity,0))
                                        * order_child_item.quantity) as received_base_useplan
                                from
                                    order_detail
                                    inner join order_child_item on order_detail.order_detail_id = order_child_item.order_detail_id
                                where
                                    order_detail.seiban in (select seiban from received_detail)
                                group by
                                    order_detail.order_header_id
                                    ,child_item_id
                                ) as t_order
                                on use_plan.order_header_id = t_order.order_header_id and use_plan.item_id = t_order.child_item_id
                        where
                            /* 15iで追加。ダミー品目受注時の子品目使用予約は除外する。（上の受注ベースオーダーの子品目使用予約と同じ理由） */
                            /* ag.cgi?page=ProjectDocView&pid=1574&did=214244 */
                            received_detail_id_for_dummy is null
                        group by
                            item_id
                        ) as t_use_plan_qty
                        on t_base.item_id = t_use_plan_qty.item_id
                            and t_base.location_id = 0
                ";
            }

            // ●入出庫数
            $query .= "
                left join (
                    select
                        item_in_out.item_id
                        ,item_in_out.location_id
                        ,SUM(" . Logic_Stock::getInoutField(true) . ") as in_out_qty
                    from
                        item_in_out
            ";
            if ($i == -1) {
                $query .= "
                        left join temp_inventory_date
                            on item_in_out.item_id = temp_inventory_date.item_id
                            and item_in_out.location_id = temp_inventory_date.location_id
                            and coalesce(item_in_out.seiban,'') = coalesce(temp_inventory_date.seiban,'')
                            and item_in_out.lot_id = temp_inventory_date.lot_id
                ";
            }
            $query .= "
                        left join seiban_change on item_in_out.seiban_change_id = seiban_change.change_id
                    where
            ";
            if ($i == -1) {
                $query .= "
                        (temp_inventory_date.last_inventory_date is null
                            or item_in_out.item_in_out_date > temp_inventory_date.last_inventory_date)
                        and item_in_out.item_in_out_date <= '{$beforeDateStr}'::date
                ";
            } else {
                $query .= " item_in_out.item_in_out_date = '{$date}'::date";
            }

            $query .= "
                        /* 製番在庫は排除。製番展開における使用可能数の使用目的がフリー在庫数を調べることであるため。 */
                       and coalesce(item_in_out.seiban,'') = ''
                    group by
                        item_in_out.item_id
                        ,item_in_out.location_id
                    ) as t_in_out_qty
                    on t_base.item_id = t_in_out_qty.item_id
                        and t_base.location_id = t_in_out_qty.location_id
                ) as T0
            ) as t1
            ";
            $gen_db->query($query);
            $gen_db->analyze('temp_usable_stock');
        }
    }

    //************************************************
    // 製番展開準備： 製番在庫数テーブル（テンポラリ）を作成
    //************************************************
    //
    // 製番展開の際、既存の製番在庫があればオーダー数から差し引く必要がある。
    // 構成ツリーの中で同じ品目が複数箇所に出てくる場合を考えると、一括処理はできない。
    // ここでテーブルに在庫データを保存しておき、差し引き処理をしながら消しこんでいく。
    //
    // 注意点
    // ・全ロケ分を合計する（サプライヤーロケも含めて）。
    // ・製番オーダーの受入済み数量を差し引く（製番オーダーの差し引き処理とダブリになってしまうので）
    //   ※オーダーの差し引き処理のほうで受入済みオーダーを排除したほうが簡単に思えるが、
    //     それだと受け入れ在庫を他へ移動した場合に「オーダーは受入済みでしかも在庫もない」という
    //     状態になり、うまくいかない。
    //
    //   製番オーダーの受入済み数量だけでなく、製番引当数も差し引くようにした。
    //     従来は製番在庫の有無で製番引当がおこなわれたかどうかを判断していた。つまり製番在庫があれば前回すでに製番引当
    //     が行われたものとみなして、オーダーを発行しないようにしていた。
    //     しかしこれだと製番在庫が実績引き落としなどで無くなったあとで再計算した場合に、製番引当済みであることがわからず、
    //     オーダーが再発行されてしまうという問題があった。
    //     そこで製番在庫数だけでなく、製番引当数も差し引くようにした。それにともなって、ダブり差し引きを避けるため、ここでオーダー
    //     済み数と同じように製番引当済数も差し引くようにした。
    //
    //     ちなみに現行の仕様では、製番在庫分の差し引き処理自体が無意味。
    //     なぜなら、製番在庫ができるのは製番オーダーの受入時と製番引当時のみ。
    //     ここではその両方を差し引いているので、実質的にここで算出される製番在庫数は常に0となる。
    //     それなら製番在庫分の差し引き処理自体を廃止してもいいようなものだが、将来的に入出庫などで自由に製番在庫が
    //     作成できるように仕様変更された場合のことを考え、処理を残すことにした。

    function makeSeibanStockTable($startDate)
    {
        global $gen_db;

        $beforeDateStr = date('Y-m-d', $startDate - (3600 * 24)); // 計算開始日の前日
        // 在庫数の取得
        //    ・ロット・ロケは区別しない。ただしPロケは排除
        //    ・取得するのは理論在庫のみ
        Logic_Stock::createTempStockTable($beforeDateStr, null, null, "sum", "sum", false, false, false);

        $query = "
        create temp table temp_seiban_stock (
            seiban
            ,item_id
            ,seiban_stock_quantity
        ) as
        select
            temp_stock.seiban
            ,temp_stock.item_id
            ,coalesce(temp_stock.logical_stock_quantity,0) - coalesce(t1.accepted_quantity,0)
               - coalesce(change_quantity,0)

        /* ●ベース */
        from
            temp_stock

        /* ●製番オーダーの受入済み数量 */
        left join (
            select
                seiban
                ,item_id
                ,sum(accepted_quantity) as accepted_quantity
            from
                order_detail
            group by
                seiban
                ,item_id
            ) as t1
            on temp_stock.seiban = t1.seiban
                and temp_stock.item_id = t1.item_id
                and temp_stock.location_id = 0
                and temp_stock.lot_id = 0

         /* ●製番引当数量 */
         left join (
            select
                dist_seiban as seiban
                ,item_id
                ,sum(quantity) as change_quantity
            from
                seiban_change
            group by
                seiban
                ,item_id
            ) as t2
            on temp_stock.seiban = t2.seiban
                and temp_stock.item_id = t2.item_id
                and temp_stock.location_id = 0
                and temp_stock.lot_id = 0
        where
            temp_stock.seiban <> ''
        ";
        $gen_db->query($query);
        $gen_db->analyze('temp_seiban_stock');
    }

    //************************************************
    // 製番展開準備：　製番オーダー数テーブル（テンポラリ）を作成
    //************************************************
    // 製番展開の際、既存の製番オーダーがあれば今回オーダー数から差し引く必要がある。
    // 構成ツリーの中で同じ品目が複数箇所に出てくる場合を考えると、一括処理はできない。
    // ここでテーブルに既存オーダーのデータを保存しておき、差し引き処理をしながら消しこんでいく。

    function makeSeibanOrderTable()
    {
        global $gen_db;

        // 通常オーダーだけでなく、製番引当もピックアップするようになった。
        //    「既存製番オーダー分在庫の差し引き」に加え「製番引当発行済み分の差し引き」も行うようになったことにともなう変更。
        //   詳細は makeSeibanStockTable(), afterSeibanExpand() の冒頭コメントを参照
        $query = "
        create temp table temp_seiban_order (
            seiban
            ,item_id
            ,order_quantity
            ,is_seiban_change
        ) as
        select
            seiban
            ,item_id
            ,sum(order_detail_quantity)
            ,false
        from
            order_detail
        where
            coalesce(seiban,'') <> ''
            /* 外製工程は除く。オーダーは出ているが、受入時に在庫が増えるわけではないため */
            and (subcontract_order_process_no is null or subcontract_order_process_no = '')
        group by
            seiban
            ,item_id
        ";
        $gen_db->query($query);

        $query = "
        insert into temp_seiban_order (
            seiban
            ,item_id
            ,order_quantity
            ,is_seiban_change
        )
        select
            dist_seiban as seiban
            ,item_id
            ,sum(quantity)
            ,true
        from
            seiban_change
        group by
            dist_seiban
            ,item_id
        ";
        $gen_db->query($query);
        $gen_db->analyze('temp_seiban_order');
    }

    //************************************************
    // 製番展開準備：　受注製番がついた計画数テーブル（テンポラリ）を作成
    //************************************************
    // 詳細は deductReceivedSeibanPlan() のコメント参照

    function makeReceivedSeibanTable()
    {
        global $gen_db;

        // 通常オーダーだけでなく、製番引当もピックアップするようになった。
        //    「既存製番オーダー分在庫の差し引き」に加え「製番引当発行済み分の差し引き」も行うようになったことにともなう変更。
        //   詳細は makeSeibanStockTable(), afterSeibanExpand() の冒頭コメントを参照
        $query = "
        create temp table temp_received_seiban_plan (
            seiban
            ,item_id
            ,plan_quantity
        ) as
        select
            seiban
            ,item_id
            ,sum(plan_quantity) as plan_quantity
        from
            temp_plan_list
        where
            classification in (0,3)   -- 計画、所要量計算結果調整
            and seiban in (select seiban from received_detail)
        group by
            seiban
            ,item_id
        ";
        $gen_db->query($query);
        $gen_db->analyze('temp_received_seiban_plan');
    }

    //************************************************
    // 製番展開メイン
    //************************************************

    function seiban($startDate, $endDate)
    {
        global $gen_db;

        $max_lc = 30;   // 展開する最大階層。これを超えた場合は構成ループが存在したとみなす
        // Logic_Bom冒頭で設定している値（30）より大きい値とすること
        //
        // 計算用テンポラリテーブルの作成
        self::makeSeibanTable();
        self::makeSeibanUsableStockTable($startDate, $endDate);
        self::makeSeibanOrderTable();
        self::makeSeibanStockTable($startDate);
        self::makeReceivedSeibanTable();


        // 最上位品目（レベル0）の処理。
        //
        // 計画テーブル(plan)のレコードを元にする。
        // 計画テーブルには製番品とMRP品が混じっているため、製番品だけを選び出して処理する必要がある。
        // 製番品を見分けるにあたり、計画テーブルのレコードに製番があるかどうかでは判断できない。
        // （計画テーブルのレコードはMRP品であっても製番入りであるため。）
        // そこで品マスで手配区分を調べている。

        $startDateStr = date('Y-m-d', $startDate);
        $endDateStr = date('Y-m-d', $endDate);

        $query = "
        insert into temp_seiban (
            seiban
            ,item_id
            ,calc_date
            ,arrangement_quantity
            ,llc
            ,order_class
            ,plan_qty
            ,hand_qty
        )
        select
            seiban1
            ,t01.item_id
            ,CAST(calc_date as date)
            ,COALESCE(arrangement_quantity,0)
            ,0
            ,0
            ,plan_qty
            ,hand_qty
        from
            (select
               COALESCE(seiban,'') as seiban1
               ,t1.item_id
               ,calc_date
               ,arrangement_quantity
               -- 10i以降、着手日テーブルに含まれないLTはアラームとみなすようになった
               ,plan_qty
               ,hand_qty
            from
                (select
                    seiban
                    ,item_master.item_id
                    ,plan_date as calc_date
                    ,plan_quantity as arrangement_quantity
                    ,safety_lead_time
                    ,order_class
                    ,case when classification = 0 then plan_quantity end as plan_qty
                    ,case when classification = 3 then plan_quantity end as hand_qty
                from
                    temp_plan_list
                    inner join item_master on temp_plan_list.item_id = item_master.item_id
                ) as t1
            where
                calc_date between '{$startDateStr}'::date and '{$endDateStr}'::date
                and order_class = 0
            ) as t01
        ";
        $gen_db->query($query);

        // 展開後処理（製番在庫既存分の差し引き、製番引当処理）
        // 全展開後ではなく、1レベル展開ごとに処理するようにした。
        // 差し引き数や引当数は、手配丸めが行われる前の状態で計算する必要があるため。
        self::afterSeibanExpand(0, $startDate);

        // 以前にここにあった「発行済み製番オーダーの差引」「手配丸め処理」は、
        //  上のself::afterSeibanExpand()内に移動した
        //
        // 2階層目以下をレベル順に展開する。

        $lc = 1;
        while (true) {
            $query = "
            insert into temp_seiban (
                seiban
                ,parent_id
                ,inzu
                ,item_id
                ,calc_date
                ,arrangement_quantity
                ,llc
                ,order_class
            )
            select
                t01.seiban
                ,id
                ,inzu
                ,child_item_id
                ,plan_date
                ,COALESCE(plan_quantity,0)
                ,lc
                ,0
            from
                (select
                    seiban
                    ,id
                    ,inzu
                    ,child_item_id
                    ,plan_date
                    ,plan_quantity
                    ,{$lc} as lc
                from
                    (select
                        seiban
                        ,id
                        ,bom_master.quantity as inzu
                        ,child_item_id
                        ,arrangement_start_date as plan_date
                        /* 子品目の数量計算は、親品目の手配数量(arrangement_quantity)ではなく、子品目計算用数量(arrangement_ */
                        /* quantity_for_child)に基づいて計算するようにした。理由はself::afterSeibanExpand「発行済み製番オーダーの差し引き」を参照。*/
                        ,temp_seiban.arrangement_quantity_for_child * bom_master.quantity as plan_quantity
                    from
                        temp_seiban
                        inner join bom_master on temp_seiban.item_id = bom_master.item_id
                        inner join item_master on bom_master.child_item_id = item_master.item_id
                        /* 変更点（親品目の標準手配先が「支給無し」の設定である場合、子品目以下は計算しない） */
                        /* joinされるのは標準手配先のレコード（line_number=0）のみであることに注意。*/
                        /* （ちなみに内製の場合は、すべて「支給有り」になっている。品目マスタ登録時の処理で。）*/
                        inner join item_order_master on temp_seiban.item_id = item_order_master.item_id
                            and item_order_master.line_number=0
                            and item_order_master.partner_class in (2,3)     /* 親品目が内製か、支給ありの場合のみ */
                    where
                        temp_seiban.llc = " . ($lc - 1) . "
                        and seiban <> ''
                        /* MRP品が製番展開されないよう除外 */
                        and item_master.order_class = 0
                        /* 親が製番引当のときは子は不要 */
                        and temp_seiban.order_class <> '99'
                    ) as t1
                ) as t01
            ";
            $gen_db->query($query);

            // 展開後処理（製番在庫既存分の差し引き、製番引当処理）
            // 全展開後ではなく、1レベル展開ごとに処理するようにした。
            // 差し引き数や引当数は、手配丸めが行われる前の状態で計算する必要があるため。
            //  「発行済み製番オーダーの差引」「手配丸め処理」 もここで行うようになった
            self::afterSeibanExpand($lc, $startDate);

            // 終了の判断
            $query = "select item_id from temp_seiban where llc = {$lc} AND seiban <> ''";

            if (!$gen_db->existRecord($query)) {
                break;
            }

            $lc++;
            if ($lc > $max_lc) {
                // 階層の深さが規定値を超えた
                break;
            }
        }

        $gen_db->analyze('temp_seiban');

        // 発行済み製番オーダーの差し引きはここで処理するのではなく、レベル別に行うようにした。
        //  理由はself::afterSeibanExpandの「発行済み製番オーダーの差し引き」のコメントを参照。
        //
        // テーブル転記（temp_seiban から mrp）
        $query = "
        insert into mrp (
            seiban
            ,location_id
            ,item_id
            ,calc_date
            ,arrangement_quantity
            ,arrangement_start_date
            ,arrangement_finish_date
            ,llc
            ,alarm_flag
            ,order_class
            ,plan_qty
            ,hand_qty
        )
        select
            seiban
            ,location_id
            ,item_id
            ,calc_date
            ,arrangement_quantity
            ,arrangement_start_date
            ,arrangement_finish_date
            ,llc
            ,alarm_flag
            ,order_class
            ,plan_qty
            ,hand_qty
        from
            temp_seiban
        ";
        $gen_db->query($query);
    }

    //************************************************
    // 製番展開：　展開後処理
    //************************************************
    //  全展開後ではなく、1レベル展開するたびに処理するようにした（そうしないと発注単位をうまく処理できない）
    //  製番オーダー発行済み分の差し引きは除外（それだけは1レベルごとではなく、全レベル展開後に行うため）
    //
    // 製番展開後に行う、以下の2つの処理の共通部分
    // ・製番在庫既存分の差し引き
    // ・フリー製番在庫の引当
    //
    // 既存オーダーだけでなく、既存の製番引当分も今回オーダーから差し引くようになった。
    //     詳細はmakeSeibanStockTable() の冒頭コメント参照。
    // またそれにともない処理の順番を変更。
    //  既存オーダー差し引きに既存製番引当分も含まれるようになったが、その処理は製番引当より前に行う必要があるため。
    //     従来は「製番在庫既存分の差し引き」⇒「製番引当処理」⇒「子品目への反映有無の切り替えの準備」⇒「既存オーダー分の差し引き」
    //       だったが
    //     「子品目への反映有無の切り替えの準備」⇒「既存オーダー/製番引当分の差し引き」⇒「製番在庫既存分の差し引き」⇒「製番引当処理」
    //       に変更。

    function afterSeibanExpand($lc, $startDate)
    {
        global $gen_db;

        // ●子品目への反映有無の切り替えの準備
        //  カラム arrangement_quantity_for_child 追加にともなう処理。
        //  理由は下の「発行済み製番オーダーの差し引き」のコメントを参照。
        $query = "update temp_seiban set arrangement_quantity_for_child = arrangement_quantity where llc = '{$lc}'";
        $gen_db->query($query);

        // ●受注製番がついた計画があれば、その分をオーダーから差し引く処理
        //  くわしくはfunctionのコメントを参照
        self::deductReceivedSeibanPlan($lc);

        // ●発行済み製番オーダーの差し引き
        // 以前は展開後に全階層分をまとめて処理していたが、ここでレベル別に処理するように変更した。
        // 以前の方式だと、手配まるめ処理を行ったあとの値に対して差し引きを行うことになる。
        // 通常はそれで問題ない（発行済みオーダーもまるめ処理された値であるため）。
        // しかし前回の所要量計算時と今回のフリー在庫の数が異なると、問題が生じる場合があることがわかった。
        //  ※ 上記の場合、製番引当数とオーダー数が前回とは異なったものになる。
        //    オーダー数を手配丸めするとトータル数（製番引当数 + 丸め後オーダー数）が前回分より多くなること
        //    がある。その場合、発行済みオーダーを差し引いても余分なオーダーが残ることになる。
        // そこで、差し引き処理はレベル別に行うことにした。
        // これまで全階層分をまとめて差し引きしていた理由は、「中間品のみオーダー発行済み、
        // 子品目は未発行」という状況のとき、中間品のみ差し引き処理をする必要があるため（子品目まで
        // 差し引いてはいけない）。
        // 中間品のみオーダー発行済みだった場合の対応として、temp_seibanに手配数量のカラム
        // (arrangement_quantity) とは別に子品目数量計算用のカラム（arrangement_quantity_for_child)
        // を設けた。後者はオーダー差し引きの対象としない。
        // 既存製番引当の差し引きも含まれるようになった

        self::deductSeibanOrder($lc);

        // ●製番オーダーをひとつずつ取り出して処理。
        // order by に注意。
        //   在庫既存分の差し引きやフリー在庫引当は日付の速いオーダーから
        //   優先して行う必要がある。
        $query = "
        select
            id
            ,temp_seiban.seiban
            ,temp_seiban.item_id
            -- 計画ベースの分は自動引当処理を行わない（また、製番在庫既存分もありえないので差し引きしない）
            ,coalesce(arrangement_quantity,0) - coalesce(plan_qty,0) - coalesce(hand_qty,0) as arrangement_quantity
            -- 子品目納期から安全LT経過した日を着手日とする。着手日テーブルに含まれていない分（日付オーバー）は開始日とする
            ,coalesce(t_finish.begin_day, '" . date('Y-m-d', $startDate) . "')  as finish_date
        from
            temp_seiban
            left join item_master on temp_seiban.item_id = item_master.item_id
            left join temp_mrp_day_table as t_finish
                on item_master.safety_lead_time = t_finish.lead_time
                and temp_seiban.calc_date = t_finish.finish_day
            left join received_detail on temp_seiban.seiban = received_detail.seiban
        where
            coalesce(arrangement_quantity,0) - coalesce(plan_qty,0)- coalesce(hand_qty,0) > 0
            and temp_seiban.llc = {$lc}
        order by
            finish_date
            ,received_detail.dead_line
            ,temp_seiban.seiban
        ";

        // ●製番在庫既存分の差し引き
        $arr = $gen_db->getArray($query);
        if (is_array($arr)) {
            foreach ($arr as $row) {
                self::deductSeibanStock($row['id'], $row['seiban'], $row['item_id'], $row['arrangement_quantity']);
            }
        }

        // ●フリー製番在庫の引当
        $arr = $gen_db->getArray($query);
        if (is_array($arr)) {
            foreach ($arr as $row) {
                self::allocatedSeibanFreeStock($row['id'], $row['item_id'], $row['arrangement_quantity'], $row['finish_date']);
            }
        }

        // ●手配丸め処理
        //  arrangement_quantity と arrangement_quantity_for_child の両方を丸める
        self::orderRound($lc);

        // ●オーダー日と納期の決定
        self::setOrderDate($lc, $startDate);
    }

    //************************************************
    // 製番展開：　オーダー日・納期を決める
    //************************************************
    //  2010で追加。09iまではメイン展開SQLの中で行っていたが、LTを可変にできるようになったことに伴い分離した。

    function setOrderDate($lc, $startDate)
    {
        global $gen_db;

        $query = "
        update
            temp_seiban
        set
            arrangement_start_date = coalesce(t_start.begin_day, cast('" . date('Y-m-d', $startDate) . "' as date))
            ,arrangement_finish_date = coalesce(t_finish.begin_day, cast('" . date('Y-m-d', $startDate) . "' as date))
            ,alarm_flag = case when t_start.begin_day is null then '1' else t_start.alarm_flag end
        from
            -- LT未指定のときは、工程LTからLTを計算する。工程LTが空欄なら「(オーダー数÷製造能力)-1」
            -- 「-1」しているのは、製造が1日以内で終わるときはLT=0、足掛け2日かかる場合はLT=1とする必要があるため
            (select id
                ,max(temp_seiban.item_id) as item_id
                ,max(calc_date) as calc_date
                ,sum(coalesce(process_lt, trunc(temp_seiban.arrangement_quantity / coalesce(case when pcs_per_day=0 then 1 else pcs_per_day end,1) + 0.9999999999)-1)) as lt
                from temp_seiban
                left join item_process_master on temp_seiban.item_id = item_process_master.item_id
                group by temp_seiban.id
                ) as t_temp_seiban
            left join item_master on t_temp_seiban.item_id = item_master.item_id
            left join temp_mrp_day_table as t_finish
                on item_master.safety_lead_time = t_finish.lead_time
                        and t_temp_seiban.calc_date = t_finish.finish_day
            left join temp_mrp_day_table as t_start
                on COALESCE(item_master.lead_time, t_temp_seiban.lt) + COALESCE(item_master.safety_lead_time,0) = t_start.lead_time
                        and t_temp_seiban.calc_date = t_start.finish_day
        where
            temp_seiban.id = t_temp_seiban.id
            and temp_seiban.llc = '{$lc}'
            and temp_seiban.order_class<>'99'
        ";
        $gen_db->query($query);
    }

    //************************************************
    // 製番展開：　受注製番がついた計画があれば、その分をオーダーから差し引く処理
    //************************************************
    //  2010で追加。所要量計算結果でオーダー数を変更できるようになったことにともなう処理。
    //  「受注製番がついた計画」は、製番品目の所要量計算結果を手動で変更したときに
    //  自動的に作成された計画。
    //  手動で指定した計画が存在するわけなので、そのぶん受注品目やその子品目のオーダー
    //  は減らしておかなくてはならない。

    function deductReceivedSeibanPlan($lc)
    {
        global $gen_db;

        // 製番オーダーをひとつずつ取り出して処理。
        // order by に注意。古いオーダーから消す（手動修正により作成された計画を
        // 優先するため。手動修正は前倒しにされることが多い）
        $query = "
        select
            id
            ,temp_seiban.seiban
            ,temp_seiban.item_id
            /* 計画ベースの分は、自動引当処理を行わない（また、製番在庫既存分もありえないので差し引きしない） */
            ,coalesce(arrangement_quantity,0) - coalesce(plan_qty,0) - coalesce(hand_qty,0) as arrangement_quantity
            ,arrangement_finish_date
        from
            temp_seiban
        where
            coalesce(arrangement_quantity,0) - coalesce(plan_qty,0) - coalesce(hand_qty,0) > 0
            and temp_seiban.llc = {$lc}
        order by
            arrangement_finish_date desc
        ";

        $arr = $gen_db->getArray($query);
        if (!is_array($arr))
            return;
        foreach ($arr as $row) {
            self::deductReceivedSeibanPlanSub($row['id'], $row['seiban'], $row['item_id'], $row['arrangement_quantity']);
        }
    }

    function deductReceivedSeibanPlanSub($id, $seiban, $itemId, $arrangeQty)
    {
        global $gen_db;

        // 「受注製番がついた計画」を取得
        $query = "
        select
            plan_quantity
        from
            temp_received_seiban_plan
        where
            seiban = '{$seiban}'
            and item_id = '{$itemId}'
        ";

        $planQty = $gen_db->queryOneValue($query);

        if ($planQty > 0) {
            // 「受注製番がついた計画」があった
            // 消込数を決める
            $deleteQty = ($planQty > $arrangeQty ? $arrangeQty : $planQty);

            // オーダー差し引き。
            self::deductOrderArrangeQty($id, $deleteQty);

            // テーブルを更新
            $query = "
            update
                temp_received_seiban_plan
            set
                plan_quantity = plan_quantity - {$deleteQty}
            where
                seiban = '{$seiban}'
                and item_id = '{$itemId}'
            ";
            $gen_db->query($query);
        }
    }

    //************************************************
    // 製番展開：　製番在庫があればオーダーから差し引く処理
    //************************************************
    //    この処理は現行の仕様では無意味になったが、将来の仕様変更のために残してある。
    //    詳しくは makeSeibanStockTable() 冒頭コメントを参照。

    function deductSeibanStock($id, $seiban, $itemId, $arrangeQty)
    {
        global $gen_db;

        // 製番在庫テーブルから在庫数を取得
        $query = "
        select
            seiban_stock_quantity
        from
            temp_seiban_stock
        where
            seiban = '{$seiban}'
            and item_id = '{$itemId}'
        ";
        $stockQty = $gen_db->queryOneValue($query);

        if ($stockQty > 0) {
            // 製番在庫があった
            // 在庫使用数を決める
            $useStockQty = ($stockQty > $arrangeQty ? $arrangeQty : $stockQty);

            // オーダー差し引き。
            self::deductOrderArrangeQty($id, $useStockQty);

            // 製番在庫数テーブルを更新
            $query = "
            update
                temp_seiban_stock
            set
                seiban_stock_quantity = seiban_stock_quantity - {$useStockQty}
            where
                seiban = '{$seiban}'
                and item_id = '{$itemId}'
            ";
            $gen_db->query($query);
        }
    }

    //************************************************
    // 製番展開：　フリー製番在庫の引当処理
    //************************************************
    // フリー製番在庫があれば引当を行う（オーダーを仮引当オーダーに変更する）。

    function allocatedSeibanFreeStock($id, $itemId, $arrangeQty, $arrangeDate)
    {
        global $gen_db;

        // フリー在庫数
        $query = "
        select
            calc_date
            ,location_id
            ,usable_quantity
        from
            temp_usable_stock
        where
            item_id = '{$itemId}'
            and calc_date <= '{$arrangeDate}'::date
            and usable_quantity > 0
        order by
            calc_date
        ";
        if (!($usableArr = $gen_db->getArray($query))) {
            return;
        }

        // 日付・ロケごとにループ
        // 計算開始日から順に、存在する使用可能在庫を引き当てていく（つまり入庫したとたんに引き当てられる）
        for ($i = 0; $i < count($usableArr); $i++) {

            // 引当数を決める
            $allocQty = ($arrangeQty > $usableArr[$i]['usable_quantity'] ? $usableArr[$i]['usable_quantity'] : $arrangeQty);

            if ($allocQty <> 0) {

                // 仮引当オーダーを出す
                $query = "
                insert into temp_seiban (
                    seiban
                    ,item_id
                    ,location_id
                    ,calc_date
                    ,arrangement_quantity
                    ,arrangement_start_date
                    ,arrangement_finish_date
                    ,llc
                    ,alarm_flag
                    ,order_class
                )
                select
                    seiban
                    ,item_id
                    ,'{$usableArr[$i]['location_id']}'
                    ,'{$usableArr[$i]['calc_date']}'
                    ,{$allocQty}
                    ,'{$usableArr[$i]['calc_date']}'
                    ,'{$usableArr[$i]['calc_date']}'
                    ,llc
                    ,alarm_flag
                    ,'99'        /* order_class = '99' は 製番引当オーダー */
                from
                    temp_seiban
                where
                    id = '{$id}'
                ";
                $gen_db->query($query);

                // 計算中の製番オーダーを調整する
                self::deductOrderArrangeQty($id, $allocQty);

                // 使用可能数テーブルの更新
                $query = "
                update
                    temp_usable_stock
                set
                    in_out_qty = in_out_qty - {$allocQty}
                where
                    item_id = '{$itemId}'
                    and calc_date = '{$usableArr[$i]['calc_date']}'
                    and location_id = '{$usableArr[$i]['location_id']}';

                update
                    temp_usable_stock
                set
                    usable_quantity = usable_quantity - {$allocQty}
                where
                    item_id = '{$itemId}'
                    and calc_date >= '{$usableArr[$i]['calc_date']}'
                    and location_id = '{$usableArr[$i]['location_id']}';

                update
                    temp_usable_stock
                set
                    before_usable_qty = before_usable_qty - {$allocQty}
                where
                    item_id = '{$itemId}'
                    and calc_date > '{$usableArr[$i]['calc_date']}'
                    and location_id = '{$usableArr[$i]['location_id']}';
                ";
                $gen_db->query($query);

                for ($j = 0; $j < count($usableArr); $j++) {
                    if (strtotime($usableArr[$j]['calc_date']) >= strtotime($usableArr[$i]['calc_date'])
                            && $usableArr[$j]['location_id'] == $usableArr[$i]['location_id']) {

                        $usableArr[$j]['usable_quantity'] -= $allocQty;
                    }
                }

                $arrangeQty -= $allocQty;
                if ($arrangeQty <= 0)
                    break;
            }
        }
    }

    //************************************************
    // 製番展開： オーダー差し引き処理
    //************************************************
    // 展開済みツリー（temp_order）の中の特定のオーダー数量を差し引きする処理。
    // 製番在庫既存分の差し引き処理や、フリー在庫引当処理で使用している。
    // 子品目にも差し引きを反映させるようにした（arrangement_quantity_for_child の処理）
    //    arrangement_quantity_for_child の扱いが変わったことに伴う変更。

    function deductOrderArrangeQty($id, $deductQty)
    {
        global $gen_db;

        $query = "
        update
            temp_seiban
        set
            arrangement_quantity = (case when arrangement_quantity >= {$deductQty} then
                arrangement_quantity - {$deductQty} else 0 end)
            ,arrangement_quantity_for_child = (case when arrangement_quantity_for_child >= {$deductQty} then
                arrangement_quantity_for_child - {$deductQty} else 0 end)
        where
            id = '{$id}'
        ";
        $gen_db->query($query);
    }

    //************************************************
    // 製番展開： 手配丸め処理
    //************************************************
    //  展開中ではなく、1レベル展開ごとにまとめて処理するようにしたことに伴いfuncとして独立した

    function orderRound($lc)
    {
        global $gen_db;

        // 手配まるめ数が0のときは丸めを行わない。
        // 手配まるめ数は、手配先マスタのものを使用する（item_order_master.default_lot_unit）のだが、
        // FROM句において、手配先マスタは「標準」手配先（line_number=0）のレコードだけがJOIN
        // されていることに注目。つまりここで使用する手配まるめ数は、標準手配先のものである。
        // 手配先はユーザーが指示書/注文登録時に変更することができる。詳細は注文登録のコードの
        // コメントを参照。
        $query = "
        update
            temp_seiban
        set
            arrangement_quantity = t1.new_arrangement_quantity
            ,arrangement_quantity_for_child = t1.new_arrangement_quantity_for_child
        from
            (select
                id
                ,temp_seiban.llc
                /* 手配丸め1 適用分 */
                ,case when arrangement_quantity <= 0 then 0
                    else coalesce(case when coalesce(default_lot_unit,0) <= 0 then
                    /* まるめなし */
                    case when arrangement_quantity <= coalesce(default_lot_unit_limit,0) then arrangement_quantity
                    else coalesce(default_lot_unit_limit, arrangement_quantity) end
                else
                    /* まるめあり */
                    trunc((case when arrangement_quantity <= coalesce(default_lot_unit_limit,0) then arrangement_quantity
                        else coalesce(default_lot_unit_limit, arrangement_quantity) end) / default_lot_unit + 0.999999) * default_lot_unit
                end, 0) +
                /* 手配丸め2 適用分 */
                case when arrangement_quantity <= coalesce(default_lot_unit_limit,0) or default_lot_unit_limit is null then 0
                    else coalesce(case when COALESCE(default_lot_unit_2,0) <= 0 then
                        /* まるめなし */
                        arrangement_quantity - default_lot_unit_limit
                    else
                        /* まるめあり */
                        trunc((arrangement_quantity - default_lot_unit_limit)
                            / default_lot_unit_2 + 0.999999) * default_lot_unit_2
                    end, 0)
                end
                end as new_arrangement_quantity
                /* 手配まるめ数を超えた場合、手配丸め数2を適用 */
                /* 手配丸め1 適用分 */
                ,case when arrangement_quantity_for_child <= 0 then 0
                    else coalesce(case when coalesce(default_lot_unit,0) <= 0 then
                        /* まるめなし */
                        case when arrangement_quantity_for_child <= coalesce(default_lot_unit_limit,0) then arrangement_quantity_for_child
                            else coalesce(default_lot_unit_limit,arrangement_quantity_for_child) end
                    else
                        /* まるめあり */
                        trunc((case when arrangement_quantity_for_child <= coalesce(default_lot_unit_limit,0) then arrangement_quantity_for_child
                            else coalesce(default_lot_unit_limit,arrangement_quantity_for_child) end) / default_lot_unit + 0.999999) * default_lot_unit
                end ,0) +
                /* 手配丸め2 適用分 */
                case when arrangement_quantity_for_child <= coalesce(default_lot_unit_limit,0) or default_lot_unit_limit is null then 0
                    else coalesce(case when COALESCE(default_lot_unit_2,0) <= 0 then
                        /* まるめなし */
                        arrangement_quantity_for_child - default_lot_unit_limit
                    else
                        /* まるめあり */
                        trunc((arrangement_quantity_for_child - default_lot_unit_limit) / default_lot_unit_2 + 0.999999) * default_lot_unit_2
                    end, 0)
                end
                end as new_arrangement_quantity_for_child
            from
                temp_seiban
                inner join item_master on temp_seiban.item_id = item_master.item_id
                left join item_order_master on item_master.item_id = item_order_master.item_id and item_order_master.line_number=0
            ) as t1
        where
            temp_seiban.id = t1.id
            and t1.llc = '{$lc}'
            /* 製番引当の場合は手配丸めしない。ここポイント */
            and order_class <> '99'
        ";
        $gen_db->query($query);
    }

    //************************************************
    // 製番展開： 製番オーダーがあれば、今回オーダーから差し引く処理
    //************************************************
    // オーダーの場合は在庫とは異なり、中間レベルで既存オーダーがあるためにオーダーキャンセル
    // したとしても、それ以下のレベルはオーダーを出さなくてはならない。
    // そのため全展開後に行うようにしていたが、1階層ごとに行うよう変更した。self::afterSeibanExpandの
    //  コメントを参照

    function deductSeibanOrder($lc)
    {
        global $gen_db;

        // 製番オーダーをひとつずつ取り出して処理。
        // order by に注意。日付の速いオーダーから優先して行う必要がある。
        $query = "
        select
            id
            ,temp_seiban.seiban
            ,temp_seiban.item_id
            ,arrangement_quantity
            ,arrangement_finish_date
        from
            temp_seiban
        where
            arrangement_quantity > 0
            and llc = '{$lc}'
        order by
            /* 製番引当よりもオーダーを優先とするように変更。 */
            /* 変更の理由はTestSeiban.class.phpの case_fujisawa1 と case_fujisawa2の説明を参照。 */
            case when order_class='99' then 1 else 0 end, arrangement_finish_date
        ";

        // 製番オーダー発行済み分の差し引き
        $arr = $gen_db->getArray($query);
        if (!is_array($arr))
            return;
        foreach ($arr as $row) {
            self::deductSeibanOrderSub($row['id'], $row['seiban'], $row['item_id'], $row['arrangement_quantity']);
        }
    }

    //************************************************
    // 製番展開： 上記のSub
    //************************************************
    function deductSeibanOrderSub($id, $seiban, $itemId, $arrangeQty)
    {
        global $gen_db;

        $query = "
        select
            sum(order_quantity) as order_quantity
            ,sum(case when is_seiban_change then order_quantity else 0 end) as seiban_change_qty
        from
            temp_seiban_order
        where
            seiban = '{$seiban}'
            and item_id = '{$itemId}'
        ";
        $obj = $gen_db->queryOneRowObject($query);
        $currentOrderQty = @$obj->order_quantity;

        if ($currentOrderQty > 0) {
            // 製番オーダーがあった
            // 消しこみ数を決める
            $deleteQty = ($currentOrderQty > $arrangeQty ? $arrangeQty : $currentOrderQty);

            // オーダー取り消し
            $query = "
            update
                temp_seiban
            set
                arrangement_quantity = arrangement_quantity - {$deleteQty}
            where
                id = '{$id}'
            ";
            $gen_db->query($query);

            // 製番引当分は「arrangement_quantity_for_child」も差し引く（つまり子品目はオーダーを出さない）
            //    通常オーダー分は差し引かない（つまり子品目のオーダーを出す）
            $seibanChangeQty = @$obj->seiban_change_qty;
            $seibanChangeQty = ($seibanChangeQty > $arrangeQty ? $arrangeQty : $seibanChangeQty);
            if ($seibanChangeQty > 0) {
                $query = "
                update
                    temp_seiban
                set
                    arrangement_quantity_for_child = arrangement_quantity_for_child - {$seibanChangeQty}
                where
                    id = '{$id}'
                ";
                $gen_db->query($query);
            }

            // 取り消し対象が製番引当であった場合、引当可能数テーブルを元に戻す
            $query = "select item_id, location_id, calc_date from temp_seiban where id = '{$id}'";
            $obj = $gen_db->queryOneRowObject($query);

            $query = "
            update
                temp_usable_stock
            set
                in_out_qty = in_out_qty + {$deleteQty}
            where
                item_id = '" . $obj->item_id . "'
                and calc_date = '" . $obj->calc_date . "'
                and location_id = " . (is_numeric($obj->location_id) ? $obj->location_id : "null") . ";

            update
                temp_usable_stock
            set
                usable_quantity = usable_quantity + {$deleteQty}
            where
                item_id = '" . $obj->item_id . "'
                and calc_date >= '" . $obj->calc_date . "'
                and location_id = " . (is_numeric($obj->location_id) ? $obj->location_id : "null") . ";

            update
                temp_usable_stock
            set
                before_usable_qty = before_usable_qty + {$deleteQty}
            where
                item_id = '" . $obj->item_id . "'
                and calc_date > '" . $obj->calc_date . "'
                and location_id = " . (is_numeric($obj->location_id) ? $obj->location_id : "null") . ";
            ";
            $gen_db->query($query);

            // 製番オーダー数テーブルを更新
            //  構成の中で同一品目が複数個所に出てきて、しかも「注文/製造指示」と「引当」の両方のオーダーが既存の場合の不具合に対処。
            //  temp_seiban_stock には、品目/製番ごとに最大２レコードできる（is_seiban_changeがtrueとfalse）ことが考慮されていなかった。
            $query = "select order_quantity from temp_seiban_order
                        where seiban = '{$seiban}' and item_id = '{$itemId}' and is_seiban_change = true";

            $seibanChangeQty = $gen_db->queryOneValue($query);
            if (!is_numeric($seibanChangeQty))
                $seibanChangeQty = 0;

            $query = "
            update
                temp_seiban_order
            set
                order_quantity = order_quantity - " . ($deleteQty > $seibanChangeQty ? $seibanChangeQty : $deleteQty) . "
            where
                seiban = '{$seiban}'
                and item_id = '{$itemId}'
                and is_seiban_change = true
            ";
            $gen_db->query($query);

            if ($deleteQty > $seibanChangeQty) {
                $query = "
                update
                    temp_seiban_order
                set
                    order_quantity = order_quantity - " . ($deleteQty - $seibanChangeQty) . "
                where
                    seiban = '{$seiban}'
                    and item_id = '{$itemId}'
                    and is_seiban_change = false
                ";
                $gen_db->query($query);
            }
        }
    }

    //===============================================================================================================
    // MRP関連
    //===============================================================================================================
    //
    //************************************************
    // MRP計算
    //************************************************
    //
    // MRP品目が計算対象となる。ただしMRP除外品およびその下位品目を除く。
    //
    //   ※MRP品の下に製番品があった場合はどうなるか？
    //   現在の仕様では、「MRP品の下に製番品」という構成はありえない（構成表マスターと品目マスターにおいて
    //   そのような登録を禁止している）が、仮にあったとすると、製番品が出てきたところで展開がとまってしまう
    //   （このファンクションでは製番品は処理対象にならないため）。
    //   もし「MRP品の下に製番品」を許すようにカスタマイズしたい場合、この問題をどうするか考える必要がある。

    function mrp($startDate, $endDate, $isNonSafetyStock)
    {
        global $gen_db;

        // 従属需要数テンポラリテーブルを作成
        // （セッション終了時に自動破棄される。他セッションからは見えない）
        $query = "
        create temp table temp_mrp_depend_demand (
            item_id int not null
            ,demand_date date not null
            ,quantity numeric not null
        )
        ";
        $gen_db->query($query);

        // パラメータの設定
        $llcmax = $gen_db->queryOneValue("select MAX(llc) from item_master");
        $beforeDateStr = date('Y-m-d', $startDate - (3600 * 24)); // 計算開始日の前日

        // ロット品目のための処理
        // ロット引当（製番引当）済の独立需要は、納期日ではなく引当日時点で発生させる。
        // ロット引当済の在庫を横取りされてしまうのを防ぐため。ag.cgi?page=ProjectDocView&pid=1574&did=197358
        $query = "
            update 
                temp_plan_list 
            set 
                plan_date = change_date
            from 
                seiban_change 
                inner join received_detail on seiban_change.dist_seiban = received_detail.seiban
            where 
                temp_plan_list.item_id = seiban_change.item_id 
                and temp_plan_list.seiban = seiban_change.dist_seiban 
                and temp_plan_list.classification = 1 /* 受注 */
                and received_detail.dead_line > seiban_change.change_date
        ";
        $gen_db->query($query);

        // 初期値（前在庫）を設定
        //    ・ロケは区別しない。ただしPロケは排除
        //    ・USE_PLANは全期間分（将来分も含めて）差し引く。引当横取り対処
        //      ⇒ rev.20071109で、使用予約分については全期間差し引きされなくなった
        //    ※13iまでは第3引数は空文字（製番なしの在庫のみ取得）だったが、15iからsum（全製番合計を取得）に変更。
        //      ロット品目は製番在庫も取得する必要があるため。
        Logic_Stock::createTempStockTable($beforeDateStr, null, "sum", "sum", "sum", true, false, true);
        
        // 初期在庫計算
        $query = "
        insert into mrp (
            item_id
            ,calc_date
            ,seiban
            ,before_useable_quantity
            ,independent_demand
            ,due_in
            ,order_remained
            ,use_plan
            ,useable_quantity
            ,llc
            ,order_class
        )
        select
            temp_stock.item_id
            ,'{$beforeDateStr}'::date
            ,''
            ,SUM(COALESCE(last_inventory_quantity,0))
            ,SUM(COALESCE(received_remained_quantity,0))
            ,SUM(COALESCE(total_in_qty,0) - COALESCE(total_out_qty,0))
            ,SUM(COALESCE(order_remained_quantity,0))
            ,SUM(COALESCE(use_plan_quantity,0))
            ,SUM(COALESCE(available_stock_quantity,0))
            ,MAX(llc)
            ,1
        from
            temp_stock
            inner join item_master on temp_stock.item_id = item_master.item_id
        where      /* Pロケはすでに排除済み */
            item_master.without_mrp <> 1
            and item_master.order_class in (1,2) /* MRP,ロット */
        group by
            temp_stock.item_id
        ";
        $gen_db->query($query);

        self::writeProgress("MRP計算実行中", 40);


        // LLC、日付の順で処理
        //
        // LLCループ
        for ($llc = 0; $llc <= $llcmax; $llc++) {

            // 計算開始日の決定
            // 最上位階層（LLC=0）は引数で指定された計算対象開始日からでよいが、
            // LLC=1より下の階層の場合は、リードタイムずらしにより、従属需要が
            // 計算対象開始日より前に発生している可能性がある。それで従属需要
            // の着手日のうちもっとも早い日を計算開始日とする。
            //
            //  着手日テーブル作成の時点で計算対象開始日にはならないように調整しているので、
            //  この開始日ずらしは意味がないかも（常に計算対象開始日からでよい）
            //  仮に着手日が計算対象開始日以前になることを許すとすると、この上の初期在庫計算を
            //  考え直さなくてはならない（計算対象開始日以前の在庫は計算されていない）
            if ($llc == 0) {
                $calcStart = $startDate;
            } else {
                $temp = $gen_db->queryOneValue("select MIN(demand_date) from temp_mrp_depend_demand");
                if (Gen_String::isDateString($temp)) {
                    $calcStart = strtotime($temp);
                    if ($calcStart > $startDate) {
                        $calcStart = $startDate;
                    }
                } else {
                    $calcStart = $startDate;
                }
            }

            $dayCount = ($endDate - $calcStart) / (3600 * 24);

            // 日付ループ
            for ($i = 0; $i <= $dayCount; $i++) {

                // 現在処理中のLLC階層について、1日分の計算を行う
                //
                // 休日であっても計画や入出庫が発生することがあるかもしれないので、
                // 休日分も計算を行う。ただし休日に手配が発生することはないようにする

                $date = date('Y-m-d', $calcStart + ($i * 3600 * 24));
                $yesterday = date('Y-m-d', $calcStart + (($i - 1) * 3600 * 24));

                // メインSQL。
                //  「(計算日数 + LTずらし日数) × 階層」回 実行されるため、このSQLの実行時間を
                //   短くするのが全体の時間短縮のポイント。所要量計算のMRP部の時間のほとんどはこのSQLの
                //   実行に費やされる。

                $query = "
                insert into mrp (
                    item_id
                    ,calc_date
                    ,seiban
                    ,before_useable_quantity
                    ,independent_demand
                    ,plan_qty
                    ,hand_qty
                    ,depend_demand
                    ,order_remained
                    ,due_in
                    ,use_plan
                    ,arrangement_quantity
                    ,useable_quantity
                    ,safety_stock
                    ,stock_quantity
                    ,llc
                    ,order_class
                )
                select
                    item_id
                    ,'{$date}'::date
                    ,''
                    ,before_usable_qty
                    ,independent_demand_qty
                    ,plan_qty
                    ,hand_qty
                    ,depend_demand_qty
                    ,order_remained_qty
                    ,in_out_qty
                    ,use_plan_qty
                    ,order_qty
                    ,COALESCE(before_usable_qty,0) - COALESCE(independent_demand_qty,0) - COALESCE(depend_demand_qty,0)
                        + COALESCE(in_out_qty,0) + COALESCE(order_remained_qty,0) - COALESCE(use_plan_qty,0) + order_qty
                    ,safety_stock_qty
                        /* この値（stock_quantity）は前の2つの項目の値を足したもので、最初はSQL実行後に */
                        /* あらためてUPDATEするようにしていたが、ここに組み込んだほうが速いことがわかった。 */
                    ,COALESCE(before_usable_qty,0) - COALESCE(independent_demand_qty,0) - COALESCE(depend_demand_qty,0)
                        + COALESCE(in_out_qty,0) + COALESCE(order_remained_qty,0) - COALESCE(use_plan_qty,0) + order_qty + safety_stock_qty
                    ,llc
                    ,1
                from
                    (select
                        item_id
                        ,independent_demand_qty
                        ,plan_qty
                        ,hand_qty
                        ,depend_demand_qty
                        ,order_remained_qty
                        ,in_out_qty
                        ,use_plan_qty
                        ,before_usable_qty
                        ,safety_stock_qty
                 " .
                        //    10iで変更
                        //    ・計画による入庫数は、当日納期の受注や従属需要に使用できるとみなされるようになった。
                        //        例えば、在庫0で 5/31 に受注100、計画100 があった場合、09iでは 100のオーダーが出ていた。
                        //        （計画による入荷分は、当日納期の受注や従属需要には使えないものとみなされていた。
                        //        　納期が翌日以降であれば使える。上の例で言えば、受注納期が6/1であればオーダーは出てこない）
                        //        10iでは このようなケースでオーダーは出てこない。計画による製造分は、当時納期の受注や従属
                        //        需要に使用できるとみなされる。
                        //
                        //        この変更の理由は、ひとつは理論的な整合性の問題。入庫の場合、入庫当日が納期の受注に使用
                        //        できるとみなされる（当日の朝に入庫するとみなされる）のに、計画の場合はそうではなかった
                        //        ので、若干の矛盾があった。
                        //
                        //        もうひとつは、10iで実装した所要量計算結果の修正機能の使い勝手の問題。その機能でオーダー
                        //        まとめや山崩しをした場合、自動的に計画が登録されるが、計画日の受注や従属需要にその分が
                        //        使用されないと感覚的におかしい。
                        //        たとえば 5/20 受注 100、5/21 受注 200があり、LTが0、在庫0だったとき、所要量計算では
                        //        5/20 100、5/21 200 のオーダーがでる。それに対してオーダーをまとめようとして結果を
                        //        5/20 300 に変更すると、09iの仕様では次回の計算で 5/20 に 400のオーダーが出てしまう
                        //        （5/20の計画分は 5/20納期の受注に使用できないため）。
                        //        10iでは 5/20に300のオーダーが出る。

                        "
                        /* 手配丸め1 適用分 */
                        ,case when COALESCE(case when order_qty1 > 0 then order_qty1 else 0 end,0) <= 0 then 0
                            else
                            coalesce(case when coalesce(default_lot_unit,0) <= 0 then
                                /* まるめなし */
                               case when COALESCE(case when order_qty1 > 0 then order_qty1 else 0 end,0) <= coalesce(default_lot_unit_limit,0) then
                                  COALESCE(case when order_qty1 > 0 then order_qty1 else 0 end,0)
                               else coalesce(default_lot_unit_limit,COALESCE(case when order_qty1 > 0 then order_qty1 else 0 end,0)) end
                            else
                                /* まるめあり */
                               trunc(
                                (case when COALESCE(case when order_qty1 > 0 then order_qty1 else 0 end,0) <= coalesce(default_lot_unit_limit,0) then
                                   COALESCE(case when order_qty1 > 0 then order_qty1 else 0 end,0)
                                 else coalesce(default_lot_unit_limit,COALESCE(case when order_qty1 > 0 then order_qty1 else 0 end,0)) end )
                                / default_lot_unit + 0.999999) * default_lot_unit
                            end ,0) +
                        /* 手配丸め2 適用分 */
                        case when COALESCE(case when order_qty1 > 0 then order_qty1 else 0 end,0) <= coalesce(default_lot_unit_limit,0)
                                or default_lot_unit_limit is null then 0
                            else
                               coalesce(case when COALESCE(default_lot_unit_2,0) <= 0 then
                                    /* まるめなし */
                                   COALESCE(case when order_qty1 > 0 then order_qty1 else 0 end,0) - coalesce(default_lot_unit_limit,0)
                               else
                                    /* まるめあり */
                                   trunc(
                                    (COALESCE(case when order_qty1 > 0 then order_qty1 else 0 end,0) - coalesce(default_lot_unit_limit,0))
                                     / default_lot_unit_2 + 0.999999) * default_lot_unit_2
                               end,0)
                            end
                            end as order_qty

                        ,llc
                    from
                        (select
                            item_master.item_id
                            ,independent_demand_qty
                            ,plan_qty
                            ,hand_qty
                            ,depend_demand_qty
                            ,COALESCE(order_remained_qty,0) as order_remained_qty
                            ,COALESCE(in_out_qty,0) as in_out_qty
                            ,use_plan_qty
                            ,t_before_usable_qty.before_usable_qty
                            -- 安全在庫数。ダミー品目は安全在庫数が設定されていても無視する
                            ,case when coalesce(dummy_item, false) then 0 else " . ($isNonSafetyStock ? "0" : "COALESCE(safety_stock,0)") . " end as safety_stock_qty
                            -- 10iで変更。上のコメントを参照
                            ,case when
                                COALESCE(independent_demand_qty,0) + COALESCE(depend_demand_qty,0)
                                    - COALESCE(t_before_usable_qty.before_usable_qty,0) - COALESCE(order_remained_qty,0) - COALESCE(in_out_qty,0)
                                    + COALESCE(use_plan_qty,0) + " . ($isNonSafetyStock ? "0" : "COALESCE(safety_stock,0)") . "
                                < coalesce(plan_qty,0) + coalesce(hand_qty,0)
                              then
                                coalesce(plan_qty,0) + coalesce(hand_qty,0)
                              else
                                COALESCE(independent_demand_qty,0) + COALESCE(depend_demand_qty,0)
                                    - COALESCE(t_before_usable_qty.before_usable_qty,0) - COALESCE(order_remained_qty,0) - COALESCE(in_out_qty,0)
                                    + COALESCE(use_plan_qty,0) + case when coalesce(dummy_item, false) then 0 else " . ($isNonSafetyStock ? "0" : "COALESCE(safety_stock,0)") . " end
                              end as order_qty1

                            ,item_order_master.default_lot_unit
                            ,item_order_master.default_lot_unit_2
                            ,item_order_master.default_lot_unit_limit
                            ,llc
                        from

                        /* ●ベースになるのはitem_master。最後にWHERE指定があることに注意 */
                            item_master

                        /* ●標準手配先（手配まるめ数を取得するため） */
                        left join
                            item_order_master
                            on item_master.item_id = item_order_master.item_id and item_order_master.line_number=0

                        /* ●独立需要数 */
                        left join
                            (select
                                item_id
                                ,sum(case when classification in (1,2) then plan_quantity end) as independent_demand_qty
                                ,sum(case when classification = 0  then plan_quantity end) as plan_qty
                                ,sum(case when classification = 3  then plan_quantity end) as hand_qty
                            from
                                temp_plan_list
                            where
                                plan_date = '{$date}'::date
                            group by
                                item_id
                            ) as t_independent_demand_qty
                            on item_master.item_id = t_independent_demand_qty.item_id

                        /* ●従属需要数 */
                        left join
                            (select
                                item_id
                                ,SUM(quantity) as depend_demand_qty
                            from
                                temp_mrp_depend_demand
                            where
                                demand_date = '{$date}'::date
                            group by
                                item_id
                            ) as t_depend_demand_qty
                            on item_master.item_id = t_depend_demand_qty.item_id

                        /* ●前日利用可能数 */
                        left join
                            (select
                                mrp.item_id
                                ,SUM(useable_quantity) as before_usable_qty
                            from
                                mrp
                            where
                                calc_date = '{$yesterday}'::date
                            group by
                                mrp.item_id
                            ) as t_before_usable_qty
                            on item_master.item_id = t_before_usable_qty.item_id

                        /* これ以下の部分は製番展開の使用可能数計算と共通化（テンポラリテーブルに */
                        /*  作成しておいて読み出す）することも可能だが、試したところかなり遅くなったのでやめた */

                        /* ●発注製造残 */
                        left join
                            (select
                                item_id
                                ,SUM(case when order_detail_completed then 0 else COALESCE(order_detail_quantity,0)
                                    - COALESCE(accepted_quantity,0) end) as order_remained_qty
                            from
                                order_detail
                            where
                                order_detail_dead_line = '{$date}'::date
                                /* 外製工程は除く。オーダーは出ているが、受入時に在庫が増えるわけではないため */
                                and (subcontract_order_process_no is null or subcontract_order_process_no='')
                            group by
                                item_id
                            ) as t_order_remained_qty
                            on item_master.item_id = t_order_remained_qty.item_id

                        /* ●使用予約 */
                        /*      受注引当分はすでに前在庫の段階で全期間分が差し引きされている（引当横取り対処）ので、*/
                        /*      ここでは使用予約分（use_planにreceived_detail_idなし）のみ計算する。*/
                        left join
                            (select
                                item_id
                                ,SUM(quantity) as use_plan_qty
                            from
                                use_plan
                            where
                                use_date = '{$date}'::date and received_detail_id is null
                            group by
                                item_id
                            ) as t_use_plan_qty
                            on item_master.item_id = t_use_plan_qty.item_id

                        /* ●入出庫数 */
                        left join
                            (select
                                item_master.item_id
                                ,SUM(" . Logic_Stock::getInoutField(false) . ") as in_out_qty
                            from
                                item_in_out
                                left join location_master on item_in_out.location_id = location_master.location_id
                                left join item_master on item_in_out.item_id = item_master.item_id
                            where
                                item_in_out_date = '{$date}'::date
                                -- サプライヤーロケ分の排除
                                and location_master.customer_id is null
                                -- ダミー品目の排除（ダミー品目の入出庫は登録されないはずだが、念のため排除しておく）
                                and not coalesce(item_master.dummy_item, false)
                            group by
                                item_master.item_id
                            ) as t_in_out_qty
                            on item_master.item_id = t_in_out_qty.item_id

                        /* 最初のitem_masterに対するWHERE */
                        where
                            item_master.llc = '{$llc}' and item_master.without_mrp <> 1
                            and item_master.order_class in (1,2) /* MRP・ロット */
                        ) as T0
                    ) as t1
                ";
                $gen_db->query($query);

                // 進捗
                // 以前は1日分ごとに書き込んでいたが、15.1iで変更。5日分ごとに書き込むことにした。
                // ag.cgi?page=ProjectDocView&pid=1574&did=238160
                if (($i + 1) % 5 == 1) {
                    $percent = (int) ((($llc / ($llcmax + 1)) + (($i + 1) / ($dayCount + 1) / 10)) * 50 + 40);
                    self::writeProgress(_g("MRP計算実行中"), $percent);
                }
            }

            // 着手日・納期計算
            //    09iまでは上の日次SQLの中で行っていたが、10iからLTを可変にできるようになったことに伴って分離した。
            $query = "
            update
                mrp
            set
                arrangement_start_date = coalesce(t_start.begin_day, cast('" . date('Y-m-d', $startDate) . "' as date))
                ,arrangement_finish_date = coalesce(t_finish.begin_day, cast('" . date('Y-m-d', $startDate) . "' as date))
                ,alarm_flag = case when t_start.begin_day is null then '1' else t_start.alarm_flag end
            from
                -- LT未指定のときは、工程LTからLTを計算する。工程LTが空欄なら「(オーダー数÷製造能力)-1」。
                -- 「-1」しているのは、工程はすべて安全LT=0（つまり前工程の納期日と後工程の着手日が重なる）とみなされるため
                (select mrp_id
                    ,max(mrp.item_id) as item_id
                    ,max(calc_date) as calc_date
                    ,sum(coalesce(process_lt, trunc(mrp.arrangement_quantity / coalesce(case when pcs_per_day=0 then 1 else pcs_per_day end,1) + 0.9999999999)-1)) as lt
                from mrp
                    left join item_process_master on mrp.item_id = item_process_master.item_id
                group by mrp_id
                ) as t_mrp
                left join item_master on t_mrp.item_id = item_master.item_id
                left join temp_mrp_day_table as t_finish
                    on item_master.safety_lead_time = t_finish.lead_time
                        and t_mrp.calc_date = t_finish.finish_day
                left join temp_mrp_day_table as t_start
                    on COALESCE(item_master.lead_time, t_mrp.lt) + COALESCE(item_master.safety_lead_time,0) = t_start.lead_time
                        and t_mrp.calc_date = t_start.finish_day
            where
                mrp.mrp_id = t_mrp.mrp_id
                and mrp.order_class in (1,2)
                and mrp.llc = '{$llc}'
                and arrangement_quantity<>0
            ";
            $gen_db->query($query);

            // 従属需要の計算
            $query = "
            insert into temp_mrp_depend_demand
                (item_id, demand_date, quantity)
            select
                child_item_id
                ,arrangement_start_date
                ,arrangement_quantity * bom_master.quantity
            from
                mrp
                inner join bom_master on mrp.item_id = bom_master.item_id
                inner join item_master on mrp.item_id = item_master.item_id
                /* joinされるのは標準手配先のレコード（line_number=0）のみであることに注意。 */
                inner join item_order_master
                    on mrp.item_id = item_order_master.item_id
                    and item_order_master.line_number=0
                    and item_order_master.partner_class in (2,3)     /* 親品目が内製か、支給ありの場合のみ */
                    and mrp.order_class<>'99'
            where
                arrangement_quantity <> 0 and mrp.llc = '{$llc}'
                /* (15i以降)ダミー品目の子品目は従属需要に含めない。ダミー品目は受注登録の時点で子品目の使用予定が登録されるようになったため。 */
                and not coalesce(item_master.dummy_item, false)
            ";
            $gen_db->query($query);
        }
    }

}
