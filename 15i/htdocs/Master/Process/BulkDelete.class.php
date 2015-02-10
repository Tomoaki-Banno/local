<?php

class Master_Process_BulkDelete extends Base_BulkDeleteBase
{

    function setParam(&$form)
    {
        $this->listAction = 'Master_Process_List';
        $this->deleteAfterAction = 'Master_Process_List';
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

                $code = $gen_db->queryOneValue("select process_code from process_master where process_id = '{$id}'");
                if ($code != "") {
                    if (!$this->_checkData($validator, _g("工程コード %s は品目マスタで使用されているために削除できません。"), $code, "select process_id from item_process_master where process_id = {$id}"))
                        break;
                    if (!$this->_checkData($validator, _g("工程コード %s に関連する実績が登録されているために削除できません。"), $code, "select process_id from achievement where process_id = {$id}"))
                        break;
                    if (!$this->_checkData($validator, _g("工程コード %s に関連する製造指示が登録されているために削除できません。"), $code, "select process_id from order_process where process_id = {$id}"))
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

        // ログ用に工程コードを取得
        $idCsv = join(',', $this->deleteIdArray);
        $query = "select process_code as col1 from process_master where process_id in ({$idCsv}) order by process_code";
        $res = $gen_db->getArray($query);
        foreach ($res as $row) {
            $this->numberArray[] = $row['col1'];
        }

        $numberCsv = join(', ', $this->numberArray);
        $numberCsvForMsg = $this->_makeNumberCsvForMsg($numberCsv);

        // メッセージ
        $this->afterDeleteMessage = sprintf(_g("%1\$s件のデータ（%2\$s：%3\$s）を削除しました。"), $count, _g('工程コード'), $numberCsvForMsg);

        // データアクセスログ
        $this->log1 = _g("工程");
        $this->log2 = sprintf(_g("%1\$s件（%2\$s：%3\$s）"), $count, _g('工程コード'), $numberCsv);
    }

    function _delete(&$form)
    {
        global $gen_db;

        // 削除処理
        $idCsv = join(',', $this->deleteIdArray);
        $query = "delete from mrp_process where process_id in ({$idCsv}) ";
        $gen_db->query($query);
        $query = "delete from process_master where process_id in ({$idCsv}) ";
        $gen_db->query($query);
    }

}