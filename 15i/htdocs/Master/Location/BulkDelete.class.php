<?php

class Master_Location_BulkDelete extends Base_BulkDeleteBase
{

    function setParam(&$form)
    {
        $this->listAction = 'Master_Location_List';
        $this->deleteAfterAction = 'Master_Location_List';
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

                $code = $gen_db->queryOneValue("select location_code from location_master where location_id = '{$id}'");
                if ($code != "") {
                    if (!$this->_checkData($validator, _g("ロケーション %s は品目マスタに標準ロケーションとして登録されているため削除できません。"), $code, "select item_id from item_master where default_location_id = {$id} or default_location_id_2 = {$id} or default_location_id_3 = {$id}"))
                        break;
                    if (!$this->_checkData($validator, _g("ロケーション %s は納品データが存在しているため削除できません。"), $code, "select location_id from delivery_detail where location_id = {$id}"))
                        break;
                    if (!$this->_checkData($validator, _g("ロケーション %s は受入データが存在しているため削除できません。"), $code, "select location_id from accepted where location_id = {$id}"))
                        break;
                    if (!$this->_checkData($validator, _g("ロケーション %s は実績データが存在しているため削除できません。"), $code, "select location_id from achievement where (location_id = {$id} or child_location_id = {$id})"))
                        break;
                    if (!$this->_checkData($validator, _g("ロケーション %s は外製指示データが存在しているため削除できません。"), $code, "select payout_location_id from order_detail where payout_location_id = {$id}"))
                        break;
                    if (!$this->_checkData($validator, _g("ロケーション %s は棚卸データが存在しているため削除できません。"), $code, "select location_id from inventory where location_id = {$id}"))
                        break;
                    if (!$this->_checkData($validator, _g("ロケーション %s は移動データが存在しているため削除できません。"), $code, "select source_location_id from location_move where (source_location_id = {$id} or dist_location_id = {$id})"))
                        break;
                    if (!$this->_checkData($validator, _g("ロケーション %s は製番引当データが存在しているため削除できません。"), $code, "select location_id from seiban_change where location_id = {$id}"))
                        break;
                    if (!$this->_checkData($validator, _g("ロケーション %s は入出庫データが存在しているため削除できません。"), $code, "select location_id from item_in_out where location_id = {$id}"))
                        break;
                } else {
                    $validator->raiseError(_g("存在しないデータが指定されています。"));
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

        // ログ用にロケコードを取得
        $idCsv = join(',', $this->deleteIdArray);
        $query = "select location_code as col1 from location_master where location_id in ({$idCsv}) order by location_code";
        $res = $gen_db->getArray($query);
        foreach ($res as $row) {
            $this->numberArray[] = $row['col1'];
        }

        $numberCsv = join(', ', $this->numberArray);
        $numberCsvForMsg = $this->_makeNumberCsvForMsg($numberCsv);

        // メッセージ
        $this->afterDeleteMessage = sprintf(_g("%1\$s件のデータ（%2\$s：%3\$s）を削除しました。"), $count, _g('ロケーションコード'), $numberCsvForMsg);

        // データアクセスログ
        $this->log1 = _g("ロケーション");
        $this->log2 = sprintf(_g("%1\$s件（%2\$s：%3\$s）"), $count, _g('ロケーションコード'), $numberCsv);
    }

    function _delete(&$form)
    {
        global $gen_db;

        // 削除処理
        $idCsv = join(',', $this->deleteIdArray);
        $query = "delete from location_master where location_id in ({$idCsv}) ";
        $gen_db->query($query);
    }

}