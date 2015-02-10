<?php

class Master_Section_BulkDelete extends Base_BulkDeleteBase
{

    function setParam(&$form)
    {
        $this->listAction = 'Master_Section_List';
        $this->deleteAfterAction = 'Master_Section_List';
    }

    function _validate($validator, &$form)
    {
        global $gen_db;

        foreach ($form as $name => $value) {
            // id配列を取得
            if (substr($name, 0, 7) == "delete_") {
                $id = $gen_db->quoteParam(substr($name, 7, strlen($name) - 7));
                if (!is_numeric($id)) {
                    continue;
                } else {
                    $this->deleteIdArray[] = $id;
                }

                $code = $gen_db->queryOneValue("select section_code from section_master where section_id = '{$id}'");
                if ($code != "") {
                    if (!$this->_checkData($validator, _g("部門コード %s に所属する従業員が登録されているため削除できません。"), $code, "select section_id from worker_master where section_id = {$id}"))
                        break;
                    if (!$this->_checkData($validator, _g("部門コード %s に関連する見積が登録されているため削除できません。"), $code, "select section_id from estimate_header where section_id = {$id}"))
                        break;
                    if (!$this->_checkData($validator, _g("部門コード %s に関連する受注が登録されているため削除できません。"), $code, "select section_id from received_header where section_id = {$id}"))
                        break;
                    if (!$this->_checkData($validator, _g("部門コード %s に関連する発注が登録されているため削除できません。"), $code, "select section_id from order_header where section_id = {$id}"))
                        break;
                    if (!$this->_checkData($validator, _g("部門コード %s に関連する製造実績が登録されているため削除できません。"), $code, "select section_id from achievement where section_id = {$id}"))
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

        // ログ用に部門コードを取得
        $idCsv = join(',', $this->deleteIdArray);
        $query = "select section_code as col1 from section_master where section_id in ({$idCsv}) order by section_code";
        $res = $gen_db->getArray($query);
        foreach ($res as $row) {
            $this->numberArray[] = $row['col1'];
        }

        $numberCsv = join(', ', $this->numberArray);
        $numberCsvForMsg = $this->_makeNumberCsvForMsg($numberCsv);

        // メッセージ
        $this->afterDeleteMessage = sprintf(_g("%1\$s件のデータ（%2\$s：%3\$s）を削除しました。"), $count, _g('部門コード'), $numberCsvForMsg);

        // データアクセスログ
        $this->log1 = _g("部門");
        $this->log2 = sprintf(_g("%1\$s件（%2\$s：%3\$s）"), $count, _g('部門コード'), $numberCsv);
    }

    function _delete(&$form)
    {
        global $gen_db;

        // 削除処理
        $idCsv = join(',', $this->deleteIdArray);
        $query = "delete from section_master where section_id in ({$idCsv}) ";
        $gen_db->query($query);
    }

}