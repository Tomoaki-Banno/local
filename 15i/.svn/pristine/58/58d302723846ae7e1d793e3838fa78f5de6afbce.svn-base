<?php

class Stock_Move_BulkDelete extends Base_BulkDeleteBase
{

    function setParam(&$form)
    {
        $this->listAction = 'Stock_Move_List';
        $this->deleteAfterAction = 'Stock_Move_List';
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
                $query = "select move_date from location_move where move_id = '{$id}'";
                $date = $gen_db->queryOneValue($query);
                $validator->notLockDateForDelete($date, _g("移動日"));
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

        // ログ用に日付・品目コードを取得
        $idCsv = join(',', $this->deleteIdArray);
        $query = "
        select
            item_code as col1
            ,move_date || ' [' || item_code || ']' as col2
        from
            location_move
            inner join item_master on location_move.item_id = item_master.item_id
        where
            move_id in ({$idCsv})
        order by
            move_date
            ,item_code
        ";
        $res = $gen_db->getArray($query);
        foreach ($res as $row) {
            $this->numberArray[] = $row['col1'];
            $this->numberDetailArray[] = $row['col2'];
        }

        $numberDetailCsv = join(', ', $this->numberDetailArray);
        $numberCsvForMsg = $this->_makeNumberCsvForMsg($numberDetailCsv);

        // メッセージ
        $this->afterDeleteMessage = sprintf(_g("%1\$s件のデータ（%2\$s：%3\$s）を削除しました。"), $count, _g('日付') . ' [' . _g('品目コード') . ']', $numberCsvForMsg);

        // データアクセスログ
        $this->log1 = _g("ロケーション間移動登録");
        $this->log2 = sprintf(_g("%1\$s件（%2\$s：%3\$s）"), $count, _g('日付') . ' [' . _g('品目コード') . ']', $numberDetailCsv);
    }

    function _delete(&$form)
    {
        // 削除処理
        foreach ($this->deleteIdArray as $id) {
            Logic_Move::deleteMove($id);
        }
    }

}