<?php

require_once("Model.class.php");

class Manufacturing_Achievement_Entry extends Base_EntryBase
{

    function setParam(&$form)
    {
        // 基本パラメータ
        $isEasyMode = @$form['easy_mode'];
        $this->listAction = $isEasyMode ? "Manufacturing_Order_List" : "Manufacturing_Achievement_List";
        $this->errorAction = $isEasyMode ? "Manufacturing_Order_List" : "Manufacturing_Achievement_Edit";
        $this->modelName = "Manufacturing_Achievement_Model";
        $this->entryMode = $isEasyMode ? "easy" : "";
        $this->newRecordNextAction = $isEasyMode ? $this->listAction : "";  // 簡易登録後はnewRecordNextActionに遷移
        $this->newRecordNotKeepField = array("order_detail_id", "achievement_quantity", "begin_time", "end_time", "process_id", "lot_no", "use_lot_no", "cost_1", "cost_2", "cost_3", "use_by", "remarks", "order_detail_completed", "process_completed");
        for ($i = 1; $i <= GEN_WASTER_COUNT; $i++) {
            $this->newRecordNotKeepField[] = "waster_quantity_$i";
            $this->newRecordNotKeepField[] = "waster_id_$i";
        }
    }

    function setLogParam($form)
    {
        global $gen_db;

        $orderNo = "";
        if (is_numeric($form['order_detail_id'])) {
            $orderNo = $gen_db->queryOneValue("select order_no from order_detail where order_detail_id = '{$form['order_detail_id']}'");
        }

        $this->log1 = _g("実績");
        $this->log2 = "[" . _g("オーダー番号") . "] {$orderNo} [" . _g("数量") . "] {$form['achievement_quantity']}";
        $this->afterEntryMessage = sprintf(_g("オーダー番号 %s の製造実績を登録しました。"), $orderNo);
    }

}
