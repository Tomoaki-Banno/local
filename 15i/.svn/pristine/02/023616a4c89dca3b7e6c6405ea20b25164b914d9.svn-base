<?php

class Manufacturing_Plan_BulkDelete extends Base_BulkDeleteBase
{

    function setParam(&$form)
    {
        $this->listAction = 'Manufacturing_Plan_List';
        $this->deleteAfterAction = 'Manufacturing_Plan_List';
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

                // ロック年月チェック
                $query = "select plan_year, plan_month from plan where plan_id = '{$id}'";
                $obj = $gen_db->queryOneRowObject($query);
                $date = $obj->plan_year . '-' . $obj->plan_month . '-1';
                $validator->notLockDateForDelete($date, _g("計画年月"));
                if ($validator->hasError())
                    break;
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

        // ログ用に品目コードを取得
        $idCsv = join(',', $this->deleteIdArray);
        $query = "select item_code as col1 from plan inner join item_master on plan.item_id = item_master.item_id where plan_id in ({$idCsv}) order by item_code";
        $res = $gen_db->getArray($query);
        foreach ($res as $row) {
            $this->numberArray[] = $row['col1'];
        }

        $numberCsv = join(', ', array_unique($this->numberArray));
        $numberCsvForMsg = $this->_makeNumberCsvForMsg($numberCsv);

        // メッセージ
        $this->afterDeleteMessage = sprintf(_g("%1\$s件のデータ（%2\$s：%3\$s）を削除しました。"), $count, _g('品目コード'), $numberCsvForMsg);

        // データアクセスログ
        $this->log1 = _g("計画");
        $this->log2 = sprintf(_g("%1\$s件（%2\$s：%3\$s）"), $count, _g('品目コード'), $numberCsv);
    }

    function _delete(&$form)
    {
        global $gen_db;

        // 削除処理
        $idCsv = join(',', $this->deleteIdArray);
        $query = "delete from plan where plan_id in ({$idCsv})";
        $gen_db->query($query);
    }

}