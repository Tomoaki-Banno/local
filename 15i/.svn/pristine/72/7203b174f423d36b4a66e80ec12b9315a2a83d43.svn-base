<?php

class Stock_Inout_BulkDelete extends Base_BulkDeleteBase
{

    function setParam(&$form)
    {
        $this->listAction = 'Stock_Inout_List';
        $this->deleteAfterAction = 'Stock_Inout_List';
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
                $query = "select item_in_out_date from item_in_out where item_in_out_id = {$id}";
                $date = $gen_db->queryOneValue($query);
                $validator->notLockDateForDelete($date, _g("日付"));
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
            ,item_in_out_date || ' [' || item_code || ']' as col2
            ,classification
        from
            item_in_out
            inner join item_master on item_in_out.item_id = item_master.item_id
        where
            item_in_out_id in ({$idCsv})
        order by
            item_in_out_date
            ,item_code
        ";
        $res = $gen_db->getArray($query);
        foreach ($res as $row) {
            $this->numberArray[] = $row['col1'];
            $this->numberDetailArray[] = $row['col2'];
            $title = Logic_Inout::classificationToTitle($row['classification'], false, true);
        }

        $numberDetailCsv = join(', ', $this->numberDetailArray);
        $numberCsvForMsg = $this->_makeNumberCsvForMsg($numberDetailCsv);

        // メッセージ
        $this->afterDeleteMessage = sprintf(_g("%1\$s件のデータ（%2\$s：%3\$s）を削除しました。"), $count, _g('日付') . ' [' . _g('品目コード') . ']', $numberCsvForMsg);

        // データアクセスログ
        $this->log1 = $title;
        $this->log2 = sprintf(_g("%1\$s件（%2\$s：%3\$s）"), $count, _g('日付') . ' [' . _g('品目コード') . ']', $numberDetailCsv);
    }

    function _delete(&$form)
    {
        global $gen_db;

        // 削除処理
        $idCsv = join(',', $this->deleteIdArray);
        $query = "delete from item_in_out where item_in_out_id in ({$idCsv})";
        $gen_db->query($query);
        // 支給の場合、サプライヤー在庫への入庫も登録されている可能性があるので削除しておく
        $query = "delete from item_in_out where payout_item_in_out_id in ({$idCsv}) ";
        $gen_db->query($query);
    }

}