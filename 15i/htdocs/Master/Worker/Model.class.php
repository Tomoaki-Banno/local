<?php

class Master_Worker_Model extends Base_ModelBase
{

    var $csvUpdateMode = false;

    protected function _getKeyColumn()
    {
        return 'worker_id';
    }

    protected function _setDefault(&$param, $entryMode)
    {
        global $gen_db;

        // 上書きモードの処理　（csv & excel）
        if ($this->csvUpdateMode && !isset($param['worker_id']) && $param['worker_code'] != "") {
            $query = "select worker_id from worker_master where worker_code = '{$param['worker_code']}'";
            $param['worker_id'] = $gen_db->queryOneValue($query);
            if ($param['worker_id'] === null)
                unset($param['worker_id']);
        }
        // code -> id
        self::_codeToId($param, "section_code", "section_id", "", "", "section_master");
        
        switch ($entryMode) {
            case "csv":
                if (@$param['end_worker'] == "1") {
                    $param['end_worker'] = "true";
                }
        }
    }

    protected function _getColumns()
    {
        $columns = array(
            array(
                "column" => "worker_id",
                "pattern" => "id",
            ),
            array(
                "column" => "worker_code",
                "convert" => array(
                    array(
                        "cat" => "trimEx",
                    ),
                ),
                // 「ユーザー指定できるが全体としてユニークでなければならない」値は、
                // validateでの重複チェックだけでなく、このlockNumberの指定が必要。
                // くわしくは ModelBase の lockNumber処理の箇所のコメントを参照。
                "lockNumber" => true,
                "validate" => array(
                    array(
                        "cat" => "required",
                        "msg" => _g('従業員コードを指定してください。')
                    ),
                    // 新規登録時の重複チェック
                    array(
                        "cat" => "notExistRecord",
                        "msg" => _g('従業員コードはすでに使用されています。別のコードを指定してください。'),
                        "skipHasError" => true,
                        "skipValidatePHP" => "[[worker_id]]!=''", // 修正はスキップ
                        "param" => "select worker_id from worker_master where worker_code = $1"
                    ),
                    // 修正時の重複チェック
                    array(
                        "cat" => "notExistRecord",
                        "msg" => _g('従業員コードはすでに使用されています。別のコードを指定してください。'),
                        "skipHasError" => true,
                        "skipValidatePHP" => "[[worker_id]]==''", // 新規登録はスキップ
                        // 更新のときは、自分自身の番号をチェック対象としない（自分自身と重複するのは当然）
                        "param" => "select worker_id from worker_master where worker_code = $1
                            and worker_id <> [[worker_id]]"
                    ),
                ),
            ),
            array(
                "column" => "worker_name",
                "convert" => array(
                    array(
                        "cat" => "trimEx",
                    ),
                ),
                // 「ユーザー指定できるが全体としてユニークでなければならない」値は、
                // validateでの重複チェックだけでなく、このlockNumberの指定が必要。
                // くわしくは ModelBase の lockNumber処理の箇所のコメントを参照。
                "lockNumber" => true,
                "validate" => array(
                    array(
                        "cat" => "required",
                        "msg" => _g('従業員名を指定してください。')
                    ),
                    // 新規登録時の重複チェック
                    array(
                        "cat" => "notExistRecord",
                        "msg" => _g('従業員名はすでに使用されています。別の名前を指定してください。'),
                        "skipHasError" => true,
                        "skipValidatePHP" => "[[worker_id]]!=''", // 修正はスキップ
                        "param" => "select worker_id from worker_master where worker_name = $1"
                    ),
                    // 修正時の重複チェック
                    array(
                        "cat" => "notExistRecord",
                        "msg" => _g('従業員名はすでに使用されています。別の名前を指定してください。'),
                        "skipHasError" => true,
                        "skipValidatePHP" => "[[worker_id]]==''", // 新規登録はスキップ
                        // 更新のときは、自分自身の番号をチェック対象としない（自分自身と重複するのは当然）
                        "param" => "select worker_id from worker_master where worker_name = $1
                            and worker_id <> [[worker_id]]"
                    ),
                ),
            ),
            array(
                "column" => "section_id",
                "pattern" => "section_id",
            ),
            array(
                "column" => "end_worker",
                "pattern" => "bool",
            ),
        );

        return $columns;
    }

    protected function _regist(&$param, $isFirstRegist)
    {
        global $gen_db;

        if (isset($param['worker_id']) && is_numeric($param['worker_id'])) {
            $key = array("worker_id" => $param['worker_id']);
        } else {
            $key = null;
        }
        $data = array(
            'worker_code' => $param['worker_code'],
            'worker_name' => $param['worker_name'],
            'section_id' => @$param['section_id'],
            'end_worker' => @$param['end_worker'],
            'remarks' => $param['remarks'],
        );
        $gen_db->updateOrInsert('worker_master', $key, $data);

        // id(keyColumnの値)を戻す。keyColumnがないModelではfalseを戻す。
        if (isset($key)) {
            $key = $param['worker_id'];
        } else {
            $key = $gen_db->getSequence("worker_master_worker_id_seq");
        }
        return $key;
    }

}
