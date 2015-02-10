<?php

class Master_Process_Model extends Base_ModelBase
{

    var $csvUpdateMode = false;

    protected function _getKeyColumn()
    {
        return 'process_id';
    }

    protected function _setDefault(&$param, $entryMode)
    {
        global $gen_db;

        // 上書きモードの処理　（csv & excel）
        if ($this->csvUpdateMode && !isset($param['process_id']) && $param['process_code'] != "") {
            $query = "select process_id from process_master where process_code = '{$param['process_code']}'";
            $param['process_id'] = $gen_db->queryOneValue($query);
            if ($param['process_id'] === null)
                unset($param['process_id']);
        }
    }

    protected function _getColumns()
    {
        $columns = array(
            array(
                "column" => "process_id",
                "pattern" => "id",
            ),
            array(
                "column" => "process_code",
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
                        "msg" => _g('工程コードを指定してください。')
                    ),
                    // 新規登録時の重複チェック
                    array(
                        "cat" => "notExistRecord",
                        "msg" => _g('工程コードはすでに使用されています。別のコードを指定してください。'),
                        "skipHasError" => true,
                        "skipValidatePHP" => "[[process_id]]!=''", // 修正はスキップ
                        "param" => "select process_id from process_master where process_code = $1"
                    ),
                    // 修正時の重複チェック
                    array(
                        "cat" => "notExistRecord",
                        "msg" => _g('工程コードはすでに使用されています。別のコードを指定してください。'),
                        "skipHasError" => true,
                        "skipValidatePHP" => "[[process_id]]==''", // 新規登録はスキップ
                        // 更新のときは、自分自身の番号をチェック対象としない（自分自身と重複するのは当然）
                        "param" => "select process_id from process_master where process_code = $1
                            and process_id <> [[process_id]]"
                    ),
                ),
            ),
            array(
                "column" => "process_name",
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
                        "msg" => _g('工程名を指定してください。')
                    ),
                    // 新規登録時の重複チェック
                    array(
                        "cat" => "notExistRecord",
                        "msg" => _g('工程名はすでに使用されています。別の名前を指定してください。'),
                        "skipHasError" => true,
                        "skipValidatePHP" => "[[process_id]]!=''", // 修正はスキップ
                        "param" => "select process_id from process_master where process_name = $1"
                    ),
                    // 修正時の重複チェック
                    array(
                        "cat" => "notExistRecord",
                        "msg" => _g('工程名はすでに使用されています。別の名前を指定してください。'),
                        "skipHasError" => true,
                        "skipValidatePHP" => "[[process_id]]==''", // 新規登録はスキップ
                        // 更新のときは、自分自身の番号をチェック対象としない（自分自身と重複するのは当然）
                        "param" => "select process_id from process_master where process_name = $1
                            and process_id <> [[process_id]]"
                    ),
                ),
            ),
            array(
                "column" => "default_lead_time",
                "convert" => array(
                    array(
                        "cat" => "strToNum",
                    ),
                    array(
                        "cat" => "nullBlankToValue",
                        "param" => '0'
                    ),
                ),
                "skipValidatePHP" => "$1===null",
                "skipValidateJS" => "$1===null",
                "validate" => array(
                    array(
                        "cat" => "range",
                        "msg" => _g('標準リードタイムには0から365日の日数を指定してください。'),
                        "skipHasError" => true,
                        "param" => array(0, 365),
                    ),
                    array(
                        "cat" => "integer",
                        "skipHasError" => true,
                        "msg" => _g('標準リードタイムには整数を指定してください。'),
                    ),
                ),
            ),
        );

        return $columns;
    }

    protected function _regist(&$param, $isFirstRegist)
    {
        global $gen_db;

        if (isset($param['process_id']) && is_numeric($param['process_id'])) {
            $key = array("process_id" => $param['process_id']);
        } else {
            $key = null;
        }
        $data = array(
            'process_code' => $param['process_code'],
            'process_name' => $param['process_name'],
            'equipment_name' => $param['equipment_name'],
            'default_lead_time' => (is_numeric($param['default_lead_time']) ? $param['default_lead_time'] : null),
        );
        $gen_db->updateOrInsert('process_master', $key, $data);

        // id(keyColumnの値)を戻す。keyColumnがないModelではfalseを戻す。
        if (isset($key)) {
            $key = $param['process_id'];
        } else {
            $key = $gen_db->getSequence("process_master_process_id_seq");
        }
        return $key;
    }

}
