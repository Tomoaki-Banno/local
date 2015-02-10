<?php

require_once("Model.class.php");

class Master_Company_Entry extends Base_EntryBase
{

    function setParam(&$form)
    {
        // 基本パラメータ
        $this->listAction = "Master_Company_Edit&afterEntry";
        $this->errorAction = "Master_Company_Edit";
        $this->modelName = "Master_Company_Model";
    }

    function setLogParam($form)
    {
        $this->log1 = _g("自社情報");
        $this->log2 = "";
        $this->afterEntryMessage = _g("自社情報を更新しました。");
    }

}
