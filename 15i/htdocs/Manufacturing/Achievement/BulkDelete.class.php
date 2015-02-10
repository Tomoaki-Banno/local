<?php

class Manufacturing_Achievement_BulkDelete extends Base_BulkDeleteBase
{

    function setParam(&$form)
    {
        $this->listAction = 'Manufacturing_Achievement_List';
        $this->deleteAfterAction = 'Manufacturing_Achievement_List';
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
                $date = Logic_Achievement::getAchievementDate($id);
                $validator->notLockDateForDelete($date, _g("製造日"));
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

        // ログ用にオーダー番号を取得
        $idCsv = join(',', $this->deleteIdArray);
        $query = "
        select
            order_no as col1
            ,order_no || ' [' || item_code || ']' as col2
        from
            achievement
            inner join order_detail on achievement.order_detail_id = order_detail.order_detail_id
        where
            achievement_id in ({$idCsv})
        order by
            order_no
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
        $this->afterDeleteMessage = sprintf(_g("%1\$s件のデータ（%2\$s：%3\$s）を削除しました。"), $count, _g('オーダー番号'), $numberCsvForMsg);

        // データアクセスログ
        $this->log1 = _g("実績");
        $this->log2 = sprintf(_g("%1\$s件（%2\$s：%3\$s）"), $count, _g('オーダー番号') . ' [' . _g('品目コード') . ']', $numberDetailCsv);
    }

    function _delete(&$form)
    {
        // 削除処理
        foreach ($this->deleteIdArray as $id) {
            Logic_Achievement::deleteAchievement($id);
        }
    }

}