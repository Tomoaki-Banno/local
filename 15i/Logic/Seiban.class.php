<?php

class Logic_Seiban
{

    //************************************************
    // 製番の自動取得
    //************************************************
    // 12iでは、このロジックを使用するのは計画製番のみ。
    // 受注製番は「受注番号」+「-」+「行番号」とするようになった（Manufacturing_Received_Model 参照）

    static function getSeiban()
    {
        global $gen_db;

        // 将来的に、製番に文字も使用できるように仕様変更する可能性もあるので、
        // シーケンスは使用しない。
        $gen_db->begin();

        $query = "select current_number from seiban_master FOR UPDATE ";    // 行ロック
        $seiban = $gen_db->queryOneValue($query);
        if (!is_numeric($seiban)) {
            // 行がなかったと想定。いまのところ current_numberは文字型なので、
            // 数字以外の文字が入っていた、あるいはnullだったという可能性もあり、
            // その場合は複数行ができてしまい動作がおかしくなるが・・。
            $query = "insert into seiban_master (current_number) VALUES (1) ";
            $gen_db->query($query);
            $seiban = 1;
        }

        $query = "UPDATE seiban_master SET current_number = current_number + 1 ";
        $gen_db->query($query);

        $gen_db->commit();

        return $seiban;
    }

    //************************************************
    // オーダー製番の取得
    //************************************************
    // Achievement/Entry, Accepted/Entry で使用

    static function getOrderSeiban($orderDetailId)
    {
        global $gen_db;

        $query = "select seiban from order_detail where order_detail_id = '{$orderDetailId}'";
        return $gen_db->queryOneValue($query);
    }

    //************************************************
    // 実績・受入登録時の在庫製番の決定
    //************************************************
    // Achievement/AjaxOrderParam、Accepted/AjaxOrderParam、Accepted/BulkEntry で使用
    //
    // 受注によるオーダーの場合はオーダー製番をそのまま在庫製番とするが、
    // 計画によるオーダーの場合は在庫製番はなし（フリー在庫）とする。
    //
    //    ソース                製番（オーダー）    製番（在庫）
    //    -----------------------------------------------
    //    MRP（受注）                         受注製番        受注製番
    //    MRP（計画）                         計画製番        なし
    //    製造指示書/注文書           　なし                 なし

    static function getStockSeiban($orderSeiban)
    {
        global $gen_db;

        // 受注データの中に該当製番があるかどうかを確認する
        $query = "select seiban from received_detail where seiban = '{$orderSeiban}'";
        if ($gen_db->existRecord($query)) {
            // 受注製番である場合。製番をそのまま在庫製番とする
            $stockSeiban = $orderSeiban;
        } else {
            // 計画製番である場合。在庫製番なし（フリー在庫）とする
            $stockSeiban = "";
        }

        return $stockSeiban;
    }

    //************************************************
    // 製番展開
    //************************************************

    /**
     * 受注製番情報を展開する
     * 製番品目の受注明細idを受け取り、構成表に基づく子品目展開を行う。
     * ダミー品目も展開するが、展開結果にダミー品目は含めない。
     *
     * @param   array   $idCsv  受注明細id
     */
    static function expandReceivedSeiban($idCsv)
    {
        global $gen_db;

        $max_lc = 30;   // 展開する最大階層。これを超えた場合は構成ループが存在したとみなす
        // Logic_Bom冒頭で設定している値（30）より大きい値とすること
        //
        // テーブル作成
        $query = "
        create temp table temp_seiban_expand (
            received_detail_id int,
            item_id int,
            seiban text,
            quantity numeric,
            lc int,
            order_date date,
            dead_line date,
            dummy_flag boolean,
            alarm_flag int
        )
        ";
        $gen_db->query($query);

        // 受注明細idが存在しない場合
        if (strlen($idCsv) == 0)
            return;

        // 計算用テーブル作成
        $query = "
        create temp table temp_seiban_expand_calc (
            received_detail_id int,
            item_id int,
            seiban text,
            quantity numeric,
            lc int,
            order_date date,
            dead_line date,
            dummy_flag boolean,
            alarm_flag int
        )
        ";
        $gen_db->query($query);

        // 計算開始日（展開日翌日）
        $dateFrom = date('Y-m-d', strtotime(date('Y-m-d') . "+1 day"));

        // 計算終了日（展開受注の最大納品日）
        $query = "select max(dead_line) from received_detail where received_detail_id in ({$idCsv})";
        $dateTo = $gen_db->queryOneValue($query);

        // LTテーブル作成
        self::makeLeadTimeTable($dateFrom, $dateTo);

        // 最上位階層（LC = 0）
        //   numeric(10,4)は、員数フィールドの精度を設定するための記述。
        //   全体で10桁、そのうち小数点以下が4桁。
        //     このcastがないと、仮に$quantityが整数だったときにフィールドが整数型に
        //     なってしまい、その後の計算で小数が出てきても切り捨てられてしまう。
        $query = "
        insert into temp_seiban_expand_calc (
            received_detail_id,
            item_id,
            seiban,
            quantity,
            lc,
            order_date,
            dead_line,
            dummy_flag,
            alarm_flag
        )
        select
            t01.received_detail_id
            ,t01.item_id
            ,t01.seiban
            ,t01.received_quantity as quantity
            ,0 as lc
            ,coalesce(t04.begin_day, '{$dateFrom}'::date) as order_date
            ,coalesce(t03.begin_day, '{$dateFrom}'::date) as dead_line
            ,coalesce(t02.dummy_item, false) as dummy_flag
            ,(case when t04.begin_day is null then '1' else t04.alarm_flag end) as alarm_flag
        from
            received_detail as t01
            inner join item_master as t02 on t01.item_id = t02.item_id
            left join temp_seiban_expand_lt_table as t03
                on coalesce(t02.safety_lead_time,0) = coalesce(t03.lead_time,0) and t01.dead_line = t03.finish_day
            left join temp_seiban_expand_lt_table as t04
                on (coalesce(t02.lead_time,0) + coalesce(t02.safety_lead_time,0)) = coalesce(t04.lead_time,0) and t01.dead_line = t04.finish_day
        where
            t01.received_detail_id in ({$idCsv})
        ";
        $gen_db->query($query);

        // 2階層目以下を展開
        $lc = 1;
        while (true) {
            $query = "
            insert into temp_seiban_expand_calc (
                received_detail_id,
                item_id,
                seiban,
                quantity,
                lc,
                order_date,
                dead_line,
                dummy_flag,
                alarm_flag
            )
            select
                t01.received_detail_id
                ,t02.child_item_id
                ,t01.seiban
                ,t01.quantity * t02.quantity as quantity
                ,{$lc} as lc
                ,coalesce(t06.begin_day, '{$dateFrom}'::date) as order_date
                ,coalesce(t05.begin_day, '{$dateFrom}'::date) as dead_line
                ,coalesce(t04.dummy_item, false) as dummy_flag
                ,case when t06.begin_day is null then '1' else t06.alarm_flag end as alarm_flag
            from
                temp_seiban_expand_calc as t01
                inner join bom_master as t02 on t01.item_id = t02.item_id
                inner join item_master as t03 on t01.item_id = t03.item_id
                inner join item_master as t04 on t02.child_item_id = t04.item_id
                left join temp_seiban_expand_lt_table as t05
                    on coalesce(t04.safety_lead_time,0) = coalesce(t05.lead_time,0) and t01.order_date = t05.finish_day
                left join temp_seiban_expand_lt_table as t06
                    on (coalesce(t04.lead_time,0) + coalesce(t04.safety_lead_time,0)) = coalesce(t06.lead_time,0) and t01.order_date = t06.finish_day
            where
                t04.order_class = 0
                and t01.lc = " . ($lc - 1) . "
            ";
            $gen_db->query($query);

            $query = "select item_id from temp_seiban_expand_calc where lc = {$lc}";

            if (!$gen_db->existRecord($query))
                break;

            $lc++;
            // 階層の深さが規定値を超えた
            if ($lc > $max_lc)
                break;
        }

        // ダミー品目削除
        $query = "delete from temp_seiban_expand_calc where dummy_flag = true";
        $gen_db->query($query);

        // データ集計
        $query = "
        insert into temp_seiban_expand (
            received_detail_id,
            item_id,
            seiban,
            quantity,
            lc,
            order_date,
            dead_line,
            alarm_flag
        )
        select
            t01.received_detail_id,
            t01.item_id,
            t01.seiban,
            sum(quantity) as quantity,
            min(case when t02.lc_cnt > 1 then null else t01.lc end) as lc,
            min(t01.order_date) as order_date,
            min(t01.dead_line) as dead_line,
            max(t01.alarm_flag) as alarm_flag
        from
            temp_seiban_expand_calc as t01
            left join (
                select
                    received_detail_id,
                    item_id,
                    seiban,
                    count(lc) as lc_cnt
                from (
                    select received_detail_id, item_id, seiban, lc
                    from temp_seiban_expand_calc
                    group by received_detail_id, item_id, seiban, lc
                    ) as t_lc
                group by
                    received_detail_id,
                    item_id,
                    seiban
                ) as t02 on t01.received_detail_id = t02.received_detail_id
                    and t01.received_detail_id = t02.received_detail_id
                    and t01.item_id = t02.item_id
                    and t01.seiban = t02.seiban
        group by
            t01.received_detail_id,
            t01.item_id,
            t01.seiban
        ";
        $gen_db->query($query);

        return;
    }

    /**
     * リードタイムのパターンを作成する
     * 計算期間を受け取り、期間中のLTに基づく着手日を作成する。
     *
     * @param   date    $dateFrom   計算開始日
     * @param   date    $dateTo     計算終了日
     */
    static function makeLeadTimeTable($dateFrom, $dateTo)
    {
        global $gen_db;

        // タイムスタンプ変換
        $timestampFrom = strtotime($dateFrom);
        $timestampTo = strtotime($dateTo);

        // テンポラリテーブルを作成（セッション終了時に自動破棄される。他セッションからは見えない）
        $query = "
        create temp table temp_seiban_expand_lt_table (
            finish_day date not null
            ,lead_time int not null
            ,begin_day date not null
            ,alarm_flag int not null
        )
        ";
        $gen_db->query($query);

        // 対象期間の日数
        $days = ($timestampTo - $timestampFrom) / 3600 / 24;

        // 休日データを取得
        $holidayArr = Gen_Date::getHolidayArray($timestampFrom, $timestampFrom + ($days * 3600 * 24));

        // 着手日テーブルを作る
        for ($i = 0; $i <= $days; $i++) {                   // 開始日からの日数
            $calcDate = $timestampFrom + ($i * 3600 * 24);  // 計算対象日（計算開始日から$i日後）
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
                    if ($date < $timestampFrom) {
                        $date = $timestampFrom;
                        $alarm = "1";
                        break;
                    }
                }

                $query = "
                insert into temp_seiban_expand_lt_table (
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

}