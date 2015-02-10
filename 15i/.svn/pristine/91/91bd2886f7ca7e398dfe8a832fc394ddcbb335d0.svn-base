<?php

require_once("Model.class.php");

class Master_Item_Entry extends Base_EntryBase
{

    function setParam(&$form)
    {
        // 基本パラメータ
        $this->listAction = "Master_Item_List";
        $this->errorAction = "Master_Item_Edit";
        $this->modelName = "Master_Item_Model";
        $this->newRecordNotKeepField = array("item_code", "item_name");

        $form['dropdown_flag'] = (@$form['gen_overlapFrame'] == "true" ? "true" : "false");
    }

    function setLogParam($form)
    {
        $this->log1 = _g("品目");
        $this->log2 = "[" . _g("品目コード") . "] {$form['item_code']}";
        $this->afterEntryMessage = sprintf(_g("品目コード %s を登録しました。"), $form['item_code']);

        // 通知メール
        $isNew = (!isset($form['item_id']) || !is_numeric($form['item_id']));
        $title = ($isNew ? _g("品目マスタ登録") : _g("品目マスタ修正"));
        $body = ($isNew ? _g("品目マスタが新規登録されました。") : _g("品目マスタが修正されました。")) . "\n\n"
                . "[" . _g("登録日時") . "] " . date('Y-m-d H:i:s') . "\n"
                . "[" . _g("登録者") . "] " . $_SESSION['user_name'] . "\n\n"
                . "[" . _g("品目コード") . "] " . $form['item_code'] . "\n"
                . "";
        Gen_Mail::sendAlertMail('item_master_' . ($isNew ? "new" : "edit"), $title, $body);
    }

}
