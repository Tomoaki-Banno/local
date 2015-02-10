<?php

class Master_Waster_Model extends Base_ModelBase
{

    var $csvUpdateMode = false;

    protected function _getKeyColumn()
    {
        return 'waster_id';
    }

    protected function _setDefault(&$param, $entryMode)
    {
        global $gen_db;

        // 上書きモードの処理　（csv & excel）
        if ($this->csvUpdateMode && !isset($param['waster_id']) && $param['waster_code'] != "") {
            $query = "select waster_id from waster_master where waster_code = '{$param['waster_code']}'";
            $param['waster_id'] = $gen_db->queryOneValue($query);
            if ($param['waster_id'] === null)
                unset($param['waster_id']);
        }
    }

    protected function _getColumns()
    {
        $columns = array(
            array(
                "column" => "waster_id",
                "pattern" => "id",
            ),
            array(
                "column" => "waster_code",
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
                        "msg" => _g('不適合理由コードを指定してください。')
                    ),
                    // 新規登録時の重複チェック
                    array(
                        "cat" => "notExistRecord",
                        "msg" => _g('不適合理由コードはすでに使用されています。別のコードを指定してください。'),
                        "skipHasError" => true,
                        "skipValidatePHP" => "[[waster_id]]!=''", // 修正はスキップ
                        "param" => "select waster_id from waster_master where waster_code = $1"
                    ),
                    // 修正時の重複チェック
                    array(
                        "cat" => "notExistRecord",
                        "msg" => _g('不適合理由コードはすでに使用されています。別のコードを指定してください。'),
                        "skipHasError" => true,
                        "skipValidatePHP" => "[[waster_id]]==''", // 新規登録はスキップ
                        // 更新のときは、自分自身の番号をチェック対象としない（自分自身と重複するのは当然）
                        "param" => "select waster_id from waster_master where waster_code = $1
                            and waster_id <> [[waster_id]]"
                    ),
                ),
            ),
            array(
                "column" => "waster_name",
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
                        "msg" => _g('不適合理由名を指定してください。')
                    ),
                    // 新規登録時の重複チェック
                    array(
                        "cat" => "notExistRecord",
                        "msg" => _g('不適合理由名はすでに使用されています。別の名前を指定してください。'),
                        "skipHasError" => true,
                        "skipValidatePHP" => "[[waster_id]]!=''", // 修正はスキップ
                        "param" => "select waster_id from waster_master where waster_name = $1"
                    ),
                    // 修正時の重複チェック
                    array(
                        "cat" => "notExistRecord",
                        "msg" => _g('不適合理由名はすでに使用されています。別の名前を指定してください。'),
                        "skipHasError" => true,
                        "skipValidatePHP" => "[[waster_id]]==''", // 新規登録はスキップ
                        // 更新のときは、自分自身の番号をチェック対象としない（自分自身と重複するのは当然）
                        "param" => "select waster_id from waster_master where waster_name = $1
                            and waster_id <> [[waster_id]]"
                    ),
                ),
            ),
        );

        return $columns;
    }

    protected function _regist(&$param, $isFirstRegist)
    {
        global $gen_db;

        if (isset($param['waster_id']) && is_numeric($param['waster_id'])) {
            $key = array("waster_id" => $param['waster_id']);
        } else {
            $key = null;
        }
        $data = array(
            'waster_code' => $param['waster_code'],
            'waster_name' => $param['waster_name'],
            'remarks' => $param['remarks'],
        );
        $gen_db->updateOrInsert('waster_master', $key, $data);

        // id(keyColumnの値)を戻す。keyColumnがないModelではfalseを戻す。
        if (isset($key)) {
            $key = $param['waster_id'];
        } else {
            $key = $gen_db->getSequence("waster_master_waster_id_seq");
        }
        return $key;
    }

}
