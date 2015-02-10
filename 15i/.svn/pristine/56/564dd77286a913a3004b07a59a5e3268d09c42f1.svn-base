<?php

class Master_Worker_BulkDelete extends Base_BulkDeleteBase
{

    function setParam(&$form)
    {
        $this->listAction = 'Master_Worker_List';
        $this->deleteAfterAction = 'Master_Worker_List';
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

                $code = $gen_db->queryOneValue("select worker_code from worker_master where worker_id = '{$id}'");
                if ($code != "") {
                    if (!$this->_checkData($validator, _g("従業員 %s に関連する見積が登録されているため削除できません。"), $code, "select worker_id from estimate_header where worker_id = {$id}"))
                        break;
                    if (!$this->_checkData($validator, _g("従業員 %s に関連する受注が登録されているため削除できません。"), $code, "select worker_id from received_header where worker_id = {$id}"))
                        break;
                    if (!$this->_checkData($validator, _g("従業員 %s に関連する発注が登録されているため削除できません。"), $code, "select worker_id from order_header where worker_id = {$id}"))
                        break;
                    if (!$this->_checkData($validator, _g("従業員 %s に対する製造実績が登録されているため削除できません。"), $code, "select worker_id from achievement where worker_id = {$id}"))
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

        // ログ用に従業員コードを取得
        $idCsv = join(',', $this->deleteIdArray);
        $query = "select worker_code as col1 from worker_master where worker_id in ({$idCsv}) order by worker_code";
        $res = $gen_db->getArray($query);
        foreach ($res as $row) {
            $this->numberArray[] = $row['col1'];
        }

        $numberCsv = join(', ', $this->numberArray);
        $numberCsvForMsg = $this->_makeNumberCsvForMsg($numberCsv);

        // メッセージ
        $this->afterDeleteMessage = sprintf(_g("%1\$s件のデータ（%2\$s：%3\$s）を削除しました。"), $count, _g('従業員コード'), $numberCsvForMsg);

        // データアクセスログ
        $this->log1 = _g("従業員");
        $this->log2 = sprintf(_g("%1\$s件（%2\$s：%3\$s）"), $count, _g('従業員コード'), $numberCsv);
    }

    function _delete(&$form)
    {
        global $gen_db;

        // 削除処理
        $idCsv = join(',', $this->deleteIdArray);
        $query = "delete from worker_master where worker_id in ({$idCsv}) ";
        $gen_db->query($query);
    }

}
