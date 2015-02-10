<?php

require_once("Model.class.php");

class Master_PricePercentGroup_Entry extends Base_EntryBase
{

    function setParam(&$form)
    {
        // 基本パラメータ
        $this->listAction = "Master_PricePercentGroup_List";
        $this->errorAction = "Master_PricePercentGroup_Edit";
        $this->modelName = "Master_PricePercentGroup_Model";
        $this->newRecordNotKeepField = array("price_percent_group_code", "price_percent_group_name", "price_percent");

        // 登録項目
        $this->columnArray = array(
            'price_percent_group_id',
            'price_percent_group_code',
            'price_percent_group_name',
            'price_percent',
        );
    }

    function setLogParam($form)
    {
        $this->log1 = _g("掛率グループ");
        $this->log2 = "[" . _g("掛率グループコード") . "] {$form['price_percent_group_code']}";
        $this->afterEntryMessage = sprintf(_g("掛率グループコード %s を登録しました。"), $form['price_percent_group_code']);
    }

}
