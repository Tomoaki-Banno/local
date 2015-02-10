<?php

class Logic_Plan
{

    //************************************************
    // 計画テーブルに「受注」レコードを作成
    //************************************************
    // 数量は「受注数 - (引当数 + フリー在庫納品済数)」。つまり未引当かつ未納品の数のみを含める。
    // また「完了」扱いになっている受注は除く。
    // 第3引数がtrueのときは、受注「予約」レコードも含める。

    static function makeReceivedDataForPlan($year, $month, $isIncludeReserve)
    {
        global $gen_db;

        $gen_db->begin();

        // 既存の受注データを削除する。
        $query = "delete from plan where classification in (1,2) and plan_year = '{$year}' and plan_month = '{$month}'";
        $gen_db->query($query);

        // 計算
        $query = "
        insert into plan (
            plan_year, plan_month, seiban, item_id,
            classification, plan_quantity,
            day1,day2,day3,day4,day5,day6,day7,day8,day9,day10,
            day11,day12,day13,day14,day15,day16,day17,day18,day19,day20,
            day21,day22,day23,day24,day25,day26,day27,day28,day29,day30,
            day31, remarks
        )
        select
            {$year} as plan_year
            ,{$month} as plan_month
            ,'' as seiban
            ,item_id
            ,case when guarantee_grade=0 then 1 else 2 end
            ,sum(qty)
        ";
        for ($i = 1; $i <= 31; $i++) {
            $query .= ",sum(case when dayX = {$i} then qty else 0 end) as day{$i}";
        }
        $query .= "
            ,''
        from
            (select
                item_master.item_id
                ,seiban
                ,item_code
                ,item_name
                ,received_header.guarantee_grade
                ,date_part('day', dead_line) as dayX
                ,COALESCE(received_quantity,0) - COALESCE(use_plan_quantity,0) as qty
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
                            ) as T0 on use_plan.received_detail_id=T0.received_detail_id
                    where
                        use_plan.received_detail_id is not null and
                        use_plan.quantity<>0
                    group by
                        use_plan.received_detail_id
                    ) as T1 on received_detail.received_detail_id=T1.received_detail_id
            where
                " . ($isIncludeReserve ? "" : "received_header.guarantee_grade=0 and") . "
                COALESCE(received_quantity,0) - COALESCE(use_plan_quantity,0) <> 0
                and (delivery_completed = false or delivery_completed is null)  /* 完納扱いの受注は除外 */
                and dead_line between '" . date("Y-m-d", mktime(0, 0, 0, $month, 1, $year)) . "'
                and '" . date("Y-m-d", mktime(0, 0, 0, $month + 1, 0, $year)) . "'    /* 0日は前月末日 */
            ) as T2
        group by
            item_id
            ,guarantee_grade
        ";
        $gen_db->query($query);

        // コミット
        $gen_db->commit();
    }

    //************************************************
    // 合計数の再計算
    //************************************************

    static function updatePlanQuantity($planId = null)
    {
        global $gen_db;

        // このモジュールは単体実行されるMRPクラスから呼ばれることがある。->現在は呼ばれない
        // その際にはセッションは定義されていないので、その対策
        if (isset($_SESSION['user_name'])) {
            $userName = $_SESSION['user_name'];
        } else {
            $userName = "";
        }

        // 全レコード
        $query = "
        update
            plan
        set
            plan_quantity = COALESCE(day1,0) + COALESCE(day2,0) + COALESCE(day3,0) + COALESCE(day4,0) + COALESCE(day5,0) +
            COALESCE(day6,0) + COALESCE(day7,0) + COALESCE(day8,0) + COALESCE(day9,0) + COALESCE(day10,0) +
            COALESCE(day11,0) + COALESCE(day12,0) + COALESCE(day13,0) + COALESCE(day14,0) + COALESCE(day15,0) +
            COALESCE(day16,0) + COALESCE(day17,0) + COALESCE(day18,0) + COALESCE(day19,0) + COALESCE(day20,0) +
            COALESCE(day21,0) + COALESCE(day22,0) + COALESCE(day23,0) + COALESCE(day24,0) + COALESCE(day25,0) +
            COALESCE(day26,0) + COALESCE(day27,0) + COALESCE(day28,0) + COALESCE(day29,0) + COALESCE(day30,0) + COALESCE(day31,0)
            ,record_updater = '{$userName}'
            ,record_update_date = '" . date('Y-m-d H:i:s') . "'
            ,record_update_func = '" . __CLASS__ . "::" . __FUNCTION__ . "'
        ";
        if (is_numeric($planId))
            $query .= " where plan_id = '{$planId}'";

        $gen_db->query($query);
    }

}