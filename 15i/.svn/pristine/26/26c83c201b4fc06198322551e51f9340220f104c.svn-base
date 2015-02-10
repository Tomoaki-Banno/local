<?php

class Master_Rate_BulkDelete extends Base_BulkDeleteBase
{

    function setParam(&$form)
    {
        $this->listAction = 'Master_Rate_List';
        $this->deleteAfterAction = 'Master_Rate_List';
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

        // ログ用に取引通貨・為替レートを取得
        $idCsv = join(',', $this->deleteIdArray);
        $query = "
        select
            currency_name as col1
            ,currency_name || ' [' || rate || ']' as col2
        from
            rate_master
            inner join currency_master on rate_master.currency_id = currency_master.currency_id
        where
            rate_master.rate_id in ({$idCsv})
        order by
            currency_name
        ";
        $res = $gen_db->getArray($query);
        foreach ($res as $row) {
            $this->numberArray[] = $row['col1'];
            $this->numberDetailArray[] = $row['col2'];
        }

        $numberDetailCsv = join(', ', $this->numberDetailArray);
        $numberCsvForMsg = $this->_makeNumberCsvForMsg($numberDetailCsv);

        // メッセージ
        $this->afterDeleteMessage = sprintf(_g("%1\$s件のデータ（%2\$s：%3\$s）を削除しました。"), $count, _g('取引通貨') . ' [' . _g('レート') . ']', $numberCsvForMsg);

        // データアクセスログ
        $this->log1 = _g("為替レート");
        $this->log2 = sprintf(_g("%1\$s件（%2\$s：%3\$s）"), $count, _g('取引通貨') . ' [' . _g('レート') . ']', $numberDetailCsv);
    }

    function _delete(&$form)
    {
        global $gen_db;

        // 削除処理
        $idCsv = join(',', $this->deleteIdArray);
        $query = "delete from rate_master where rate_id in ({$idCsv}) ";
        $gen_db->query($query);
    }

}