<?php

class Master_Waster_BulkDelete extends Base_BulkDeleteBase
{

    function setParam(&$form)
    {
        $this->listAction = 'Master_Waster_List';
        $this->deleteAfterAction = 'Master_Waster_List';
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

                $code = $gen_db->queryOneValue("select waster_code from waster_master where waster_id = '{$id}'");
                if ($code != "") {
                    if (!$this->_checkData($validator, _g("不適合理由 %s を含む実績が登録されているために削除できません。"), $code, "select waster_id from waster_detail where waster_id = {$id}"))
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

        // ログ用に不適合理由コードを取得
        $idCsv = join(',', $this->deleteIdArray);
        $query = "select waster_code as col1 from waster_master where waster_id in ({$idCsv}) order by waster_code";
        $res = $gen_db->getArray($query);
        foreach ($res as $row) {
            $this->numberArray[] = $row['col1'];
        }

        $numberCsv = join(', ', $this->numberArray);
        $numberCsvForMsg = $this->_makeNumberCsvForMsg($numberCsv);

        // メッセージ
        $this->afterDeleteMessage = sprintf(_g("%1\$s件のデータ（%2\$s：%3\$s）を削除しました。"), $count, _g('不適合理由コード'), $numberCsvForMsg);

        // データアクセスログ
        $this->log1 = _g("不適合理由");
        $this->log2 = sprintf(_g("%1\$s件（%2\$s：%3\$s）"), $count, _g('不適合理由コード'), $numberCsv);
    }

    function _delete(&$form)
    {
        global $gen_db;

        // 削除処理
        $idCsv = join(',', $this->deleteIdArray);
        $query = "delete from waster_master where waster_id in ({$idCsv}) ";
        $gen_db->query($query);
    }

}