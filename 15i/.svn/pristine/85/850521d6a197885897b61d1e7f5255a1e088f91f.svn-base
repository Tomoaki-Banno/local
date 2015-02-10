<?php

class Master_User_BulkDelete extends Base_BulkDeleteBase
{

    function setParam(&$form)
    {
        $this->listAction = 'Master_User_List';
        $this->deleteAfterAction = 'Master_User_List';
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

                $query = "select user_id from user_master where user_name = '{$_SESSION['user_name']}' and user_id = {$id}";
                if ($gen_db->existRecord($query)) {
                    $validator->raiseError(_g("現在ログインしているユーザーを削除することはできません。"));
                    break;
                }
            }
        }
        if (count($this->deleteIdArray) == 0) {
            $validator->raiseError(_g("削除するデータがありません。"));
            
        } else if (Gen_Auth::getCurrentUserId() != -1) {
            // 一般ユーザーは一般ユーザーを削除できない（機能限定ユーザーのみ削除できる）
            $idCsv = join(",", $this->deleteIdArray);
            $query = "select restricted_user from user_master where user_id in ({$idCsv}) and not restricted_user";
            if ($gen_db->existRecord($query)) {
                $validator->raiseError(_g("指定されたユーザーを削除することはできません。"));
            }
        }
    }

    function setLogParam($form)
    {
        global $gen_db;

        // 削除件数
        $count = count($this->deleteIdArray);

        // ログ用にユーザー名を取得
        $idCsv = join(',', $this->deleteIdArray);
        $query = "select user_name as col1 from user_master where user_id in ({$idCsv}) order by user_name";
        $res = $gen_db->getArray($query);
        foreach ($res as $row) {
            $this->numberArray[] = $row['col1'];
        }

        $numberCsv = join(', ', $this->numberArray);
        $numberCsvForMsg = $this->_makeNumberCsvForMsg($numberCsv);

        // メッセージ
        $this->afterDeleteMessage = sprintf(_g("%1\$s件のデータ（%2\$s：%3\$s）を削除しました。"), $count, _g('ユーザー名'), $numberCsvForMsg);

        // データアクセスログ
        $this->log1 = _g("ユーザー");
        $this->log2 = sprintf(_g("%1\$s件（%2\$s：%3\$s）"), $count, _g('ユーザー名'), $numberCsv);
    }

    function _delete(&$form)
    {
        global $gen_db;

        // 削除処理
        $idCsv = join(',', $this->deleteIdArray);
        $query = "delete from column_info where user_id in ({$idCsv}) ";
        $gen_db->query($query);
        $query = "delete from control_info where user_id in ({$idCsv}) ";
        $gen_db->query($query);
        $query = "delete from dropdown_info where user_id in ({$idCsv}) ";
        $gen_db->query($query);
        $query = "delete from page_info where user_id in ({$idCsv}) ";
        $gen_db->query($query);
        $query = "delete from search_column_info where user_id in ({$idCsv}) ";
        $gen_db->query($query);
        $query = "delete from session_table where user_id in ({$idCsv}) ";
        $gen_db->query($query);
        $query = "delete from staff_schedule_user where user_id in ({$idCsv}) ";
        $gen_db->query($query);
        $query = "delete from stickynote_info where user_id in ({$idCsv}) ";
        $gen_db->query($query);
        $query = "delete from user_template_info where user_id in ({$idCsv}) ";
        $gen_db->query($query);
        $query = "delete from permission_master where user_id in ({$idCsv}) ";
        $gen_db->query($query);
        $query = "delete from user_master where user_id in ({$idCsv}) ";
        $gen_db->query($query);
    }

}