<?php

class Manufacturing_Mrp_BatchOrder
{

    function convert($converter, &$form)
    {
    }

    function validate($validator, &$form)
    {
    }

    function execute(&$form)
    {
        global $gen_db;

        if (!Gen_String::isDateString($fixDate = $form['fix_order_date'])) {
            return 'simple.tpl';
        }

        set_time_limit(300);

        // トランザクション開始
        $gen_db->begin();

        // 確定処理
        // $idArr および $act が関係するのは印刷を行うときのみ。
        // 印刷を行うときはカテゴリは一つしか指定されていないはず。
        $idArr = false;
        if (isset($form['manufacturing'])) {
            $idArr = Logic_Order::mrpToOrder(1, $fixDate);
            Gen_Log::dataAccessLog(_g("製造指示書"), _g("新規"), _g("所要量計算結果からの発行"));
            $act = 'Manufacturing_Order_Report';
        }
        if (isset($form['partner'])) {
            $idArr = Logic_Order::mrpToOrder(0, $fixDate);
            Gen_Log::dataAccessLog(_g("注文書"), _g("新規"), _g("所要量計算結果からの発行"));
            $act = 'Partner_Order_Report';
        }
        if (isset($form['subcontract'])) {
            $idArr = Logic_Order::mrpToOrder(2, $fixDate);
            Gen_Log::dataAccessLog(_g("外製指示書"), _g("新規"), _g("所要量計算結果からの発行"));
            $act = 'Partner_Subcontract_Report';
        }
        if (isset($form['seiban'])) {
            Logic_SeibanChange::mrpToSeibanChange($fixDate);
            Gen_Log::dataAccessLog(_g("製番引当"), _g("新規"), _g("所要量計算結果からの引当"));
        }

        Gen_Setting::saveSetting($gen_db);

        // 印刷処理
        if (isset($form['print']) && is_array($idArr)) {
            foreach ($idArr as $id) {
                $form['check_' . $id] = $id;
            }
            Logic_Order::setOrderPrintedFlag($idArr, true);

            $gen_db->commit();
            return 'action:' . $act;
        }

        // 印刷しないとき or データがなかったとき
        $gen_db->commit();

        if (isset($form['windowOpen'])) {
            return 'windowclose.tpl';
        } else {
            $form['gen_restore_search_condition'] = 'true';
            return 'action:Manufacturing_Mrp_List';
        }
    }

}