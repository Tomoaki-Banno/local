<?php

require_once("Model.class.php");

class Master_ItemGroup_Entry extends Base_EntryBase
{

    function setParam(&$form)
    {
        // 基本パラメータ
        $this->listAction = "Master_ItemGroup_List";
        $this->errorAction = "Master_ItemGroup_Edit";
        $this->modelName = "Master_ItemGroup_Model";
        $this->newRecordNotKeepField = array("item_group_code", "item_group_name");
    }

    function setLogParam($form)
    {
        $this->log1 = _g("品目グループ");
        $this->log2 = "[" . _g("品目グループコード") . "] {$form['item_group_code']}";
        $this->afterEntryMessage = sprintf(_g("品目グループコード %s を登録しました。"), $form['item_group_code']);
    }

}
