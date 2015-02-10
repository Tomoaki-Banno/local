<?php

require_once(BASE_DIR . "BulkDeleteBase.class.php");

class Master_CustomerGroup_BulkDelete extends Base_BulkDeleteBase
{

    function setParam(&$form)
    {
        $this->listAction = 'Master_CustomerGroup_List';
        $this->deleteAfterAction = 'Master_CustomerGroup_List';
    }

    function _validate($validator, &$form)
    {
        global $gen_db;

        foreach ($form as $name => $value) {
            if (substr($name, 0, 7) == "delete_") {
                // id配列を取得
                $id = $gen_db->quoteParam(substr($name, 7, strlen($name) - 7));
                if (!is_numeric($id)) {
                    continue;
                } else {
                    $this->deleteIdArray[] = $id;
                }

                $code = $gen_db->queryOneValue("select customer_group_code from customer_group_master where customer_group_id = '{$id}'");
                if ($code != "") {
                    if (!$this->_checkData($validator, _g("取引先グループコード %s は取引先マスタで使用されているために削除できません。"), $code, "select customer_group_id_1 from customer_master where customer_group_id_1 = {$id} or customer_group_id_2 = {$id} or customer_group_id_3 = {$id}"))
                        break;
                } else {
                    $validator->raiseError(_g("存在しないデータが指定されています。"));
                    break;
                }
            }
        }
        if (count($this->deleteIdArray) == 0)
            $validator->raiseError(_g("削除するデータがありません。"));
    }

    function setLogParam($form)
    {
        global $gen_db;

        // 削除件数
        $count = count($this->deleteIdArray);

        // ログ用に取引先グループコードを取得
        $idCsv = join(',', $this->deleteIdArray);
        $query = "select customer_group_code as col1 from customer_group_master where customer_group_id in ({$idCsv}) order by customer_group_code";
        $res = $gen_db->getArray($query);
        foreach ($res as $row) {
            $this->numberArray[] = $row['col1'];
        }

        $numberCsv = join(', ', $this->numberArray);
        $numberCsvForMsg = $this->_makeNumberCsvForMsg($numberCsv);

        // メッセージ
        $this->afterDeleteMessage = sprintf(_g("%1\$s件のデータ（%2\$s：%3\$s）を削除しました。"), $count, _g('取引先グループコード'), $numberCsvForMsg);

        // データアクセスログ
        $this->log1 = _g("取引先グループ");
        $this->log2 = sprintf(_g("%1\$s件（%2\$s：%3\$s）"), $count, _g('取引先グループコード'), $numberCsv);
    }

    function _delete(&$form)
    {
        global $gen_db;

        // 削除処理
        $idCsv = join(',', $this->deleteIdArray);
        $query = "delete from customer_group_master where customer_group_id in ({$idCsv}) ";
        $gen_db->query($query);
    }

}