<?php

class Manufacturing_Estimate_BulkDelete extends Base_BulkDeleteBase
{

    function setParam(&$form)
    {
        $this->listAction = 'Manufacturing_Estimate_List';
        if (isset($form['returnHeaderId']) && is_numeric($form['returnHeaderId'])) {
            $act = "Edit";
            $form['estimate_header_id'] = $form['returnHeaderId'];
            $form['gen_updated'] = "true";
        } else {
            $act = "List";
        }
        $this->deleteAfterAction = 'Manufacturing_Estimate_' . $act;
    }

    function _validate($validator, &$form)
    {
        global $gen_db;

        $this->isDetailMode = isset($form['detail']) && $form['detail'] == "true";

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
                if ($this->isDetailMode) {
                    $date = Logic_Estimate::getEstimateDateByDetailId($id);
                } else {
                    $date = Logic_Estimate::getEstimateDateById($id);
                }
                $validator->notSalesLockDateForDelete($date, _g("見積日"));
                if ($validator->hasError())
                    break;

                // 受注状況チェック
                if ($this->isDetailMode) {
                    $query = "select * from received_header
                        inner join estimate_detail on received_header.estimate_header_id = estimate_detail.estimate_header_id
                        where estimate_detail.estimate_detail_id = '{$id}'";
                } else {
                    $query = "select * from received_header where estimate_header_id = '{$id}'";
                }
                $isErr = $gen_db->existRecord($query);
                if ($isErr) {
                    if ($this->isDetailMode) {
                        $query = "select estimate_number from estimate_header
                            inner join estimate_detail on estimate_header.estimate_header_id = estimate_detail.estimate_header_id
                            where estimate_detail.estimate_detail_id = '{$id}'";
                    } else {
                        $query = "select estimate_number from estimate_header where estimate_header_id = '{$id}'";
                    }
                    $number = $gen_db->queryOneValue($query);
                    $validator->raiseError(sprintf(_g("見積番号 %s が受注に転記されているため、削除できません。"), $number));
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

        // ログ用に見積番号を取得
        $idCsv = join(',', $this->deleteIdArray);
        if ($this->isDetailMode) {
            $query = "
            select
                estimate_number as col1
                ,estimate_number || ' [' || item_code || ']' as col2
            from
                estimate_header
                left join estimate_detail on estimate_header.estimate_header_id = estimate_detail.estimate_header_id
            where
                estimate_detail_id in ({$idCsv})
            order by
                estimate_header.estimate_number
            ";
        } else {
            $query = "select estimate_number as col1 from estimate_header where estimate_header_id in ({$idCsv}) order by estimate_number";
        }
        $res = $gen_db->getArray($query);
        foreach ($res as $row) {
            $this->numberArray[] = $row['col1'];
            if ($this->isDetailMode)
                $this->numberDetailArray[] = $row['col2'];
        }

        $numberCsv = join(', ', array_unique($this->numberArray));
        $numberDetailCsv = join(', ', $this->numberDetailArray);
        $numberCsvForMsg = $this->_makeNumberCsvForMsg($numberCsv);

        // メッセージ
        $this->afterDeleteMessage = sprintf(_g("%1\$s件のデータ（%2\$s：%3\$s）を削除しました。"), $count, _g('見積番号'), $numberCsvForMsg);

        // データアクセスログ
        $this->log1 = _g("見積");
        $this->log2 = sprintf(_g("%1\$s件（%2\$s：%3\$s）"), $count, ($this->isDetailMode ? _g('見積番号') . ' [' . _g('品目コード') . ']' : _g('見積番号')), ($this->isDetailMode ? $numberDetailCsv : $numberCsv));
    }

    function _delete(&$form)
    {
        global $gen_db;

        // 削除処理
        foreach ($this->deleteIdArray as $id) {
            if ($this->isDetailMode) {
                // 行番号の振りなおし
                //　削除行より後の行の行番号を-1する
                $query = "
                update
                    estimate_detail
                set
                    line_no = estimate_detail.line_no - 1
                from
                    estimate_detail as t2
                where
                    estimate_detail.estimate_header_id = t2.estimate_header_id
                    and t2.estimate_detail_id = '{$id}'
                    and estimate_detail.line_no > t2.line_no
                ";
                $gen_db->query($query);

                // 削除
                $headerId = Logic_Estimate::deleteEstimateDetail($id);

                // 添付ファイル削除用にヘッダーidを記録
                if (isset($headerId) && is_numeric($headerId)) {
                    $this->recordIdArray[] = $headerId;
                }
            } else {
                Logic_Estimate::deleteEstimateHeader($id);
            }
        }
    }

}