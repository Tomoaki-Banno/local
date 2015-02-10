<?php

class Partner_Payment_BulkDelete extends Base_BulkDeleteBase
{

    function setParam(&$form)
    {
        $this->listAction = 'Partner_Payment_List';
        $this->deleteAfterAction = 'Partner_Payment_List';
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
                $date = $gen_db->queryOneValue("select payment_date from payment where payment_id = '{$id}'");
                $validator->notBuyLockDateForDelete($date, _g("支払日"));
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

        // ログ用に発注先コード・金額を取得
        $idCsv = join(',', $this->deleteIdArray);
        $query = "
        select
            customer_no as col1
            ,customer_no || ' [' || cast(amount as text) || ']' as col2
        from
            payment
            inner join customer_master on payment.customer_id =  customer_master.customer_id
        where
            payment_id in ({$idCsv})
        order by
            customer_no
        ";
        $res = $gen_db->getArray($query);
        foreach ($res as $row) {
            $this->numberArray[] = $row['col1'];
            $this->numberDetailArray[] = $row['col2'];
        }

        $numberDetailCsv = join(', ', $this->numberDetailArray);
        $numberCsvForMsg = $this->_makeNumberCsvForMsg($numberDetailCsv);

        // メッセージ
        $this->afterDeleteMessage = sprintf(_g("%1\$s件のデータ（%2\$s：%3\$s）を削除しました。"), $count, _g('発注先コード') . ' [' . _g('金額') . ']', $numberCsvForMsg);

        // データアクセスログ
        $this->log1 = _g("支払");
        $this->log2 = sprintf(_g("%1\$s件（%2\$s：%3\$s）"), $count, _g('発注先コード') . ' [' . _g('金額') . ']', $numberDetailCsv);
    }

    function _delete(&$form)
    {
        global $gen_db;

        // 削除処理
        $idCsv = join(',', $this->deleteIdArray);
        $query = "delete from payment where payment_id in ({$idCsv})";
        $gen_db->query($query);
    }

}