<?php

class Master_CustomerPrice_BulkDelete extends Base_BulkDeleteBase
{

    function setParam(&$form)
    {
        $this->listAction = 'Master_CustomerPrice_List';
        $this->deleteAfterAction = 'Master_CustomerPrice_List';
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

        // ログ用に品目コードを取得
        $idCsv = join(',', $this->deleteIdArray);
        $query = "
        select
            item_code as col1
            ,customer_no || ' [' || item_code || ']' as col2
        from
            customer_price_master
            inner join item_master on customer_price_master.item_id = item_master.item_id
            inner join customer_master on customer_price_master.customer_id = customer_master.customer_id
        where
            customer_price_id in ({$idCsv})
        order by
            customer_no
            ,item_code
        ";
        $res = $gen_db->getArray($query);
        foreach ($res as $row) {
            $this->numberArray[] = $row['col1'];
            $this->numberDetailArray[] = $row['col2'];
        }

        $numberCsv = join(', ', $this->numberArray);
        $numberDetailCsv = join(', ', $this->numberDetailArray);
        $numberCsvForMsg = $this->_makeNumberCsvForMsg($numberDetailCsv);

        // メッセージ
        $this->afterDeleteMessage = sprintf(_g("%1\$s件のデータ（%2\$s：%3\$s）を削除しました。"), $count, _g('得意先コード') . ' [' . _g('品目コード') . ']', $numberCsvForMsg);

        // データアクセスログ
        $this->log1 = _g("得意先販売価格");
        $this->log2 = sprintf(_g("%1\$s件（%2\$s：%3\$s）"), $count, _g('得意先コード') . ' [' . _g('品目コード') . ']', $numberDetailCsv);
    }

    function _delete(&$form)
    {
        global $gen_db;

        // 削除処理
        $idCsv = join(',', $this->deleteIdArray);
        $query = "delete from customer_price_master where customer_price_id in ({$idCsv}) ";
        $gen_db->query($query);
    }

}