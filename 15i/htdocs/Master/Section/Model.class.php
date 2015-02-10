<?php

class Master_Section_Model extends Base_ModelBase
{

    var $csvUpdateMode = false;

    protected function _getKeyColumn()
    {
        return 'section_id';
    }

    protected function _setDefault(&$param, $entryMode)
    {
        global $gen_db;

        // 上書きモードの処理　（csv & excel）
        if ($this->csvUpdateMode && !isset($param['section_id']) && $param['section_code'] != "") {
            $query = "select section_id from section_master where section_code = '{$param['section_code']}'";
            $param['section_id'] = $gen_db->queryOneValue($query);
            if ($param['section_id'] === null)
                unset($param['section_id']);
        }
    }

    protected function _getColumns()
    {
        $columns = array(
            array(
                "column" => "section_id",
                "pattern" => "id",
            ),
            array(
                "column" => "section_code",
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
                        "msg" => _g('部門コードを指定してください。')
                    ),
                    // 新規登録時の重複チェック
                    array(
                        "cat" => "notExistRecord",
                        "msg" => _g('部門コードはすでに使用されています。別のコードを指定してください。'),
                        "skipHasError" => true,
                        "skipValidatePHP" => "[[section_id]]!=''", // 修正はスキップ
                        "param" => "select section_id from section_master where section_code = $1"
                    ),
                    // 修正時の重複チェック
                    array(
                        "cat" => "notExistRecord",
                        "msg" => _g('部門コードはすでに使用されています。別のコードを指定してください。'),
                        "skipHasError" => true,
                        "skipValidatePHP" => "[[section_id]]==''", // 新規登録はスキップ
                        // 更新のときは、自分自身の番号をチェック対象としない（自分自身と重複するのは当然）
                        "param" => "select section_id from section_master where section_code = $1
                            and section_id <> [[section_id]]"
                    ),
                ),
            ),
            array(
                "column" => "section_name",
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
                        "msg" => _g('部門名を指定してください。')
                    ),
                    // 新規登録時の重複チェック
                    array(
                        "cat" => "notExistRecord",
                        "msg" => _g('部門名はすでに使用されています。別の名前を指定してください。'),
                        "skipHasError" => true,
                        "skipValidatePHP" => "[[section_id]]!=''", // 修正はスキップ
                        "param" => "select section_id from section_master where section_name = $1"
                    ),
                    // 修正時の重複チェック
                    array(
                        "cat" => "notExistRecord",
                        "msg" => _g('部門名はすでに使用されています。別の名前を指定してください。'),
                        "skipHasError" => true,
                        "skipValidatePHP" => "[[section_id]]==''", // 新規登録はスキップ
                        // 更新のときは、自分自身の番号をチェック対象としない（自分自身と重複するのは当然）
                        "param" => "select section_id from section_master where section_name = $1
                            and section_id <> [[section_id]]"
                    ),
                ),
            ),
        );

        return $columns;
    }

    protected function _regist(&$param, $isFirstRegist)
    {
        global $gen_db;

        if (isset($param['section_id']) && is_numeric($param['section_id'])) {
            $key = array("section_id" => $param['section_id']);
        } else {
            $key = null;
        }
        $data = array(
            'section_code' => $param['section_code'],
            'section_name' => $param['section_name'],
            'remarks' => $param['remarks'],
        );
        $gen_db->updateOrInsert('section_master', $key, $data);

        // id(keyColumnの値)を戻す。keyColumnがないModelではfalseを戻す。
        if (isset($key)) {
            $key = $param['section_id'];
        } else {
            $key = $gen_db->getSequence("section_master_section_id_seq");
        }
        return $key;
    }

}
