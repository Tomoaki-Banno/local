<?php

class Manufacturing_Plan_Model extends Base_ModelBase
{

    protected function _getKeyColumn()
    {
        return 'plan_id';
    }

    protected function _setDefault(&$param, $entryMode)
    {
        global $gen_db;

        switch ($entryMode) {
            case "csv":
                if (@$param['item_id'] === null && $param['item_code'] != "") {
                    $query = "select item_id from item_master where item_code = '{$param['item_code']}'";
                    $param['item_id'] = $gen_db->queryOneValue($query);
                }
                break;
        }
    }

    protected function _getColumns()
    {
        $columns = array(
            array(
                "column" => "plan_year",
                "convert" => array(
                    array(
                        "cat" => "strToNum",
                    ),
                ),
                "validate" => array(
                    array(
                        "cat" => "range",
                        "msg" => _g('年が正しくありません。'),
                        "param" => array(2000, date('Y') + 1),
                    ),
                ),
            ),
            array(
                "column" => "plan_month",
                "convert" => array(
                    array(
                        "cat" => "strToNum",
                    ),
                ),
                "validate" => array(
                    array(
                        "cat" => "range",
                        "msg" => _g('月が正しくありません。'),
                        "param" => array(1, 12),
                    ),
                ),
            ),
            array(
                "column" => "item_id",
                "pattern" => "item_id_required",
            ),
            array(
                "column" => "item_id",
                "validate" => array(
                    array(
                        "cat" => "existRecord",
                        "msg" => _g('製番品目の計画を登録することはできません。'),
                        "skipHasError" => true,
                        "param" => "select item_id from item_master where item_id = $1 and order_class in (1,2)"
                    ),
                    array(
                        "cat" => "notExistRecord",
                        "msg" => _g('この品目の計画はすでに登録済みです。'),
                        "skipHasError" => true,
                        "skipValidatePHP" => "[[plan_id]]!=''", // 修正モードではチェックしない
                        "param" => "select item_id from plan where item_id = $1 and plan_year=[[plan_year]]
                            and plan_month=[[plan_month]] and classification=0",
                    ),
                ),
            ),
            array(
                "column" => "remarks",
                "pattern" => "nullToBlank",
            ),
        );

        for ($i = 1; $i <= 31; $i++) {
            $columns[] = array(
                "column" => "day{$i}",
                "convert" => array(
                    array(
                        "cat" => "strToNum",
                    ),
                    array(
                        "cat" => "nullBlankToValue",
                        "param" => 0,
                    ),
                ),
                "validate" => array(
                    array(
                        "cat" => "numeric",
                        "msg" => sprintf(_g("%s日の数量が正しくありません。"), $i),
                    ),
                    array(
                        "cat" => "eval",
                        "skipHasError" => true,
                        "skipValidatePHP" => "$1=='0'",
                        "msg" => sprintf(_g("この年月に「%s日」は存在しません。"), $i),
                        "evalPHP" => "\$res=(Gen_String::isDateString([[plan_year]].'-'.[[plan_month]].'-{$i}'));",
                    ),
                ),
            );
        }

        return $columns;
    }

    protected function _regist(&$param, $isFirstRegist)
    {
        global $gen_db;

        // 製番の取得
        if (!isset($param['seiban']) || $param['seiban'] == "") {
            $param['seiban'] = Logic_Seiban::getSeiban();
        }

        // 登録
        if (isset($param['plan_id']) && is_numeric($param['plan_id'])) {
            $key = array("plan_id" => $param['plan_id']);
            $classification = $gen_db->queryOneValue("select classification from plan where plan_id = {$param['plan_id']}");
        } else {
            $key = null;
            $classification = 0;
        }
        $data =
                array(
                    'plan_year' => $param['plan_year'],
                    'plan_month' => $param['plan_month'],
                    'seiban' => $param['seiban'],
                    'item_id' => $param['item_id'],
                    'classification' => $classification,
                    'plan_quantity' => 0, // これはあとで再計算される
                    'remarks' => $param['remarks'],
        );
        for ($i = 1; $i <= 31; $i++) {
            $name = "day{$i}";
            $data[$name] = $param[$name];
        }
        $gen_db->updateOrInsert('plan', $key, $data);

        $planId = isset($key) ? $param['plan_id'] : $gen_db->getSequence("plan_plan_id_seq");

        // 合計の再計算
        Logic_Plan::updatePlanQuantity($planId);

        // id(keyColumnの値)を戻す。keyColumnがないModelではfalseを戻す。
        return $planId;
    }

}
