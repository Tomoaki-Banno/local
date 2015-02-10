<?php

require_once("Model.class.php");

class Manufacturing_Plan_Entry extends Base_EntryBase
{

    function setParam(&$form)
    {
        // 基本パラメータ
        $this->listAction = "Manufacturing_Plan_List";
        $this->errorAction = "Manufacturing_Plan_Edit";
        $this->modelName = "Manufacturing_Plan_Model";
        $this->newRecordNotKeepField = array("item_id", "seiban");
        for ($i = 1; $i <= 31; $i++) {
            $this->newRecordNotKeepField[] = "day{$i}";
        }
    }

    function setLogParam($form)
    {
        global $gen_db;

        $itemCode = "";
        if (is_numeric($form['item_id'])) {
            $itemCode = $gen_db->queryOneValue("select item_code from item_master where item_id = '{$form['item_id']}'");
        }

        $this->log1 = _g("計画");
        $this->log2 = "[" . _g("年月") . "] " . sprintf(_g("%1\$s年%2\$s月"), $form['plan_year'], $form['plan_month']) . " [" . _g("品目コード") . "] {$itemCode}";
        $this->afterEntryMessage = sprintf(_g("品目コード %s の計画を登録しました。"), $itemCode);
    }

}
