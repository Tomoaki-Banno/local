<?php

class Manufacturing_Mrp_AjaxEntry extends Base_AjaxBase
{

    function _execute(&$form)
    {
        global $gen_db;

        // パラメータチェック
        $obj = 
            array(
                "success" => "false"
            );
        if ($form['gen_readonly'] == "true") {
            return $obj;
        }
        if (!isset($form['date']) || !isset($form['value']) || !isset($form['item_process_seiban'])) {
            return $obj;
        }
        if (!Gen_String::isDateString($form['date'])) {
            return $obj;
        }
        $date = $form['date'];
        if (!Gen_String::isNumeric($form['value'])) {
            return $obj;
        }
        $value = $form['value'];

        $arr = explode('_', $form['item_process_seiban']);
        if (count($arr) == 0) {
            return $obj;
        }
        $itemId = $arr[0];
        if (!is_numeric($itemId)) {
            return $obj;
        }
        unset($arr[0]);
        unset($arr[1]);
        $seiban = join('_', $arr);

        // リードタイムの取得
        $query = "
        select
            order_class
            ,coalesce(item_master.lead_time, t_item_process.lt) as lead_time
            ,safety_lead_time
        from
            item_master
            left join (
                select
                    item_id
                    -- LT未指定のときは工程LTからLTを計算する。工程LTが空欄なら「(オーダー数÷製造能力)-1」。Logic_Mrpの着手日計算と同じ
                    -- 「-1」しているのは、工程はすべて安全LT=0（つまり前工程の納期日と後工程の着手日が重なる）とみなされるため
                    ,sum(coalesce(process_lt, trunc({$value} / coalesce(case when pcs_per_day=0 then 1 else pcs_per_day end,1) + 0.9999999999)-1)) as lt
                 from
                    item_process_master
                 group by
                    item_id
                 ) as t_item_process
                on item_master.item_id = t_item_process.item_id
        where
            item_master.item_id = '{$itemId}'
       	";
        $res = $gen_db->queryOneRowObject($query);
        if (!$res || $res == null) {
            return $obj;
        }
        $orderClass = $res->order_class;
        $lt = $res->lead_time;
        $slt = $res->safety_lead_time;

        // 所要量計算結果テーブル（mrp）の更新
        //  クライアントからはキーとして品目ID・製番・日付が送られてくるが、mrpテーブル上では必ずしもその3つの
        //  カラムでユニークとは限らない。それに、値を書き換えてしまうと前在庫等のデータが不整合となる問題もある。
        //  それで、該当するレコードはいったんすべて削除し、改めてレコード登録する。

        $gen_db->begin();

        $query = "select plan_qty from mrp where item_id = '{$itemId}' and seiban = '{$seiban}' and arrangement_finish_date = '{$date}'::date";
        $planQty = $gen_db->queryOneValue($query);
        if (!is_numeric($planQty))
            $planQty = 0;

        $query = "delete from mrp where item_id = '{$itemId}' and seiban = '{$seiban}' and arrangement_finish_date = '{$date}'::date";
        $gen_db->query($query);

        // 休日データを取得
        $tommorow = strtotime('+1 day');
        $holidayArr = Gen_Date::getHolidayArray($tommorow, strtotime($date));

        // 着手日計算
        $alarm = '0';
        $startDate = date('Y-m-d', Gen_Date::getOrderDate(strtotime($date), $lt, $holidayArr, $tommorow, $alarm));

        // 登録
        $handQty = $value - $planQty;
        $data = array(
            'item_id' => $itemId,
            'seiban' => $seiban,
            'calc_date' => $date,
            'order_class' => $orderClass,
            'arrangement_quantity' => $value,
            'arrangement_start_date' => $startDate,
            'arrangement_finish_date' => $date,
            'plan_qty' => $planQty,
            'hand_qty' => $handQty,
            'alarm_flag' => $alarm,
        );
        $gen_db->insert('mrp', $data);

        // 計画(plan)を登録（指定されたオーダー日付から安全LT分さかのぼった日に計画を立てる）

        $planDate = strtotime($date . ' +' . $slt . ' days');
        $year = date('Y', $planDate);
        $month = date('m', $planDate);
        $day = date('d', $planDate);

        // plan は 年/月/品目/classificationでユニーク
        $query = "select plan_id from plan where plan_year = '{$year}' and plan_month = '{$month}' and item_id = '{$itemId}' and classification = '3'";
        $planId = $gen_db->queryOneValue($query);

        if (is_numeric($planId)) {
            $data = array('day' . (int) $day => $handQty);
            $where = "plan_id = '$planId'";
            $gen_db->update("plan", $data, $where);
        } else {
            $data = array(
                'plan_year' => $year,
                'plan_month' => $month,
                'item_id' => $itemId,
                'seiban' => $seiban,
                'classification' => 3,
                'plan_quantity' => 0, // あとで計算
                'remarks' => '',
            );
            for ($i = 1; $i <= 31; $i++) {
                $data['day' . $i] = ($i == $day ? $handQty : 0);
            }
            $gen_db->insert('plan', $data);
            $planId = $gen_db->getSequence("plan_plan_id_seq");
        }

        Logic_Plan::updatePlanQuantity($planId);

        // データアクセスログ
        $itemCode = $gen_db->queryOneValue("select item_code from item_master where item_id = '{$itemId}'");
        $msg = "[" . _g("品目コード") . "] " . $itemCode . " [" . _g("製番") . "] " . $seiban . " [" . _g("日付") . "] " . $date;
        Gen_Log::dataAccessLog(_g("所要量計算結果"), _g("簡易更新"), $msg);

        $gen_db->commit();

        return 
            array(
                "success" => "true"
            );
    }

}