<?php

class Master_TaxRate_BulkDelete extends Base_BulkDeleteBase
{

    function setParam(&$form)
    {
        $this->listAction = 'Master_TaxRate_List';
        $this->deleteAfterAction = 'Master_TaxRate_List';
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

        // ログ用に適用開始日・税率を取得
        $idCsv = join(',', $this->deleteIdArray);
        $query = "
        select
            apply_date as col1
            ,apply_date || ' [' || tax_rate || ']' as col2
        from
            tax_rate_master
        where
            tax_rate_id in ({$idCsv})
        order by
            apply_date
        ";
        $res = $gen_db->getArray($query);
        foreach ($res as $row) {
            $this->numberArray[] = $row['col1'];
            $this->numberDetailArray[] = $row['col2'];
        }

        $numberDetailCsv = join(', ', $this->numberDetailArray);
        $numberCsvForMsg = $this->_makeNumberCsvForMsg($numberDetailCsv);

        // メッセージ
        $this->afterDeleteMessage = sprintf(_g("%1\$s件のデータ（%2\$s：%3\$s）を削除しました。"), $count, _g('適用開始日') . ' [' . _g('税率') . ']', $numberCsvForMsg);

        // データアクセスログ
        $this->log1 = _g("消費税率");
        $this->log2 = sprintf(_g("%1\$s件（%2\$s：%3\$s）"), $count, _g('適用開始日') . ' [' . _g('税率') . ']', $numberDetailCsv);
    }

    function _delete(&$form)
    {
        global $gen_db;

        // 削除処理
        $idCsv = join(',', $this->deleteIdArray);
        $query = "delete from tax_rate_master where tax_rate_id in ({$idCsv}) ";
        $gen_db->query($query);
    }

}