<?php

class Monthly_Bill_BulkDelete extends Base_BulkDeleteBase
{

    function setParam(&$form)
    {
        $this->listAction = 'Monthly_Bill_BillList';
        $this->deleteAfterAction = 'Monthly_Bill_BillList';
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

                $code = $gen_db->queryOneValue("select bill_number from bill_header where bill_header_id = '{$id}'");
                if ($code != "") {
                    if (!$this->_checkData($validator, _g("請求書番号 %s は入金が登録されているため削除できません。"), $code, "select bill_header_id from paying_in where bill_header_id = {$id}"))
                        break;
                } else {
                    $validator->raiseError(_g("存在しないデータが指定されています。"));
                    break;
                }
            }
        }
        if (count($this->deleteIdArray) == 0) {
            $validator->raiseError(_g("削除するデータがありません。"));
        } else {
            $idCsv = join(',', $this->deleteIdArray);
            // 締め請求の時は各得意先の最終請求書以外は削除できない。
            // （ただし最終とその前の請求書を同時に削除するのはOK）
            $query = "
            select
                bill_header.bill_number
            from
                bill_header
                inner join bill_header as t_after on bill_header.customer_id = t_after.customer_id
                    and bill_header.close_date < t_after.close_date and t_after.bill_header_id not in ({$idCsv})
            where
                bill_header.bill_pattern <> 2
                and bill_header.bill_header_id in ({$idCsv})
            order by
                bill_number
            ";
            $num = $gen_db->queryOneValue($query);
            if ($num) {
                $validator->raiseError(sprintf(_g("請求書番号 %s を削除できません。同じ得意先に対して、指定された請求書より新しい請求書が存在するためです。レコードは1件も削除されませんでした。"), $num));
            }
        }
    }

    function setLogParam($form)
    {
        global $gen_db;

        // 削除件数
        $count = count($this->deleteIdArray);

        // ログ用に請求書番号を取得
        $idCsv = join(',', $this->deleteIdArray);
        $query = "
        select
            bill_number as col1
            ,cast(bill_number as text) || ' [' || customer_no || ' ' || cast(close_date as text) || ']' as col2
        from
            bill_header
            left join customer_master on bill_header.customer_id = customer_master.customer_id
        where
            bill_header_id in ({$idCsv})
        order by
            bill_header_id
        ";
        $res = $gen_db->getArray($query);
        foreach ($res as $row) {
            $this->numberArray[] = $row['col1'];
            $this->numberDetailArray[] = $row['col2'];
        }

        $numberCsv = join(', ', $this->numberArray);
        $numberDetailCsv = join(', ', $this->numberDetailArray);
        $numberCsvForMsg = $this->_makeNumberCsvForMsg($numberCsv);

        // メッセージ
        $this->afterDeleteMessage = sprintf(_g("%1\$s件のデータ（%2\$s：%3\$s）を削除しました。"), $count, _g('請求書番号'), $numberCsvForMsg);

        // データアクセスログ
        $this->log1 = _g("請求書");
        $this->log2 = sprintf(_g("%1\$s件（%2\$s：%3\$s）"), $count, _g('請求書番号') . ' [' . _g('得意先コード・締日') . ']', $numberDetailCsv);
    }

    function _delete(&$form)
    {
        global $gen_db;

        // 削除処理
        $idCsv = join(',', $this->deleteIdArray);
        // 納品データ関連
        $query = "update delivery_header set bill_header_id = null where bill_header_id in ({$idCsv})";
        $gen_db->query($query);
        // データの削除
        $query = "delete from bill_detail where bill_header_id in ({$idCsv})";
        $gen_db->query($query);
        $query = "delete from bill_header where bill_header_id in ({$idCsv})";
        $gen_db->query($query);
    }

}