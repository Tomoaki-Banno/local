<?php

class Master_Location_Model extends Base_ModelBase
{

    var $csvUpdateMode = false;

    protected function _getKeyColumn()
    {
        return 'location_id';
    }

    protected function _setDefault(&$param, $entryMode)
    {
        global $gen_db;

        // 上書きモードの処理　（csv）
        if ($this->csvUpdateMode && !isset($param['location_id']) && $param['location_code'] != "") {
            $query = "select location_id from location_master where location_code = '{$param['location_code']}'";
            $param['location_id'] = $gen_db->queryOneValue($query);
            if ($param['location_id'] === null)
                unset($param['location_id']);
        }

        // エクセル登録で新規登録の時はidとして空文字が送られてくるので、nullにしておく
        // （サプライヤー重複チェックSQLでエラーになるのを回避するため）
        if (@$param['location_id'] === '')
            unset($param['location_id']);

        // ***** for csv *****
        // code -> id
        self::_codeToId($param, "customer_no", "customer_id", "", "", "customer_master");
    }

    protected function _getColumns()
    {
        $columns = array(
            array(
                "column" => "location_id",
                "pattern" => "id",
            ),
            array(
                "column" => "location_code",
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
                        "msg" => _g('ロケーションコードを指定してください。')
                    ),
                    // ロケコード「-1」は納品・受入等のCSVインポートで、品目の標準ロケの意味で使用されている
                    array(
                        "cat" => "notEqualString",
                        "msg" => _g('ロケーションコードに「-1」を指定することはできません。'),
                        "skipHasError" => true,
                        "param" => "-1"
                    ),
                    // 新規登録時の重複チェック
                    array(
                        "cat" => "notExistRecord",
                        "msg" => _g('ロケーションコードはすでに使用されています。別のコードを指定してください。'),
                        "skipHasError" => true,
                        "skipValidatePHP" => "[[location_id]]!=''", // 修正はスキップ
                        "param" => "select location_id from location_master where location_code = $1"
                    ),
                    // 修正時の重複チェック
                    array(
                        "cat" => "notExistRecord",
                        "msg" => _g('ロケーションコードはすでに使用されています。別のコードを指定してください。'),
                        "skipHasError" => true,
                        "skipValidatePHP" => "[[location_id]]==''", // 新規登録はスキップ
                        // 更新のときは、自分自身の番号をチェック対象としない（自分自身と重複するのは当然）
                        "param" => "select location_id from location_master where location_code = $1
                            and location_id <> [[location_id]]"
                    ),
                ),
            ),
            array(
                "column" => "location_name",
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
                        "msg" => _g('ロケーション名を指定してください。')
                    ),
                    // 新規登録時の重複チェック
                    array(
                        "cat" => "notExistRecord",
                        "msg" => _g('ロケーション名はすでに使用されています。別の名前を指定してください。'),
                        "skipHasError" => true,
                        "skipValidatePHP" => "[[location_id]]!=''", // 修正モードはスキップ
                        "param" => "select location_id from location_master where location_name = $1"
                    ),
                    // 修正時の重複チェック
                    array(
                        "cat" => "notExistRecord",
                        "msg" => _g('ロケーション名はすでに使用されています。別の名前を指定してください。'),
                        "skipHasError" => true,
                        "skipValidatePHP" => "[[location_id]]==''", // 新規登録モードはスキップ
                        // 更新のときは、自分自身の番号をチェック対象としない（自分自身と重複するのは当然）
                        "param" => "select location_id from location_master where location_name = $1
                            and location_id <> [[location_id]]"
                    ),
                ),
            ),
            array(
                "column" => "customer_id",
                "convert" => array(
                    array(
                        "cat" => "notNumToNull",
                    ),
                ),
                "validate" => array(
                    array(
                        "cat" => "existRecord",
                        "msg" => _g('サプライヤー名が取引先マスタに登録されていません。または区分が「サプライヤー」ではありません。'),
                        "skipHasError" => true,
                        "skipValidatePHP" => "$1===null",
                        "param" => "select customer_id from customer_master where customer_id = $1 and classification=1"
                    ),
                    array(
                        "cat" => "notExistRecord",
                        "msg" => _g('このサプライヤーのロケーションはすでに登録されています。'),
                        "skipHasError" => true,
                        "skipValidatePHP" => "$1===null",
                        "param" => "select customer_id from location_master where customer_id = $1 and (location_id <> [[location_id]] or [[location_id]] is null) "
                    ),
                ),
            ),
        );

        return $columns;
    }

    protected function _regist(&$param, $isFirstRegist)
    {
        global $gen_db;

        if (isset($param['location_id']) && is_numeric($param['location_id'])) {
            $key = array("location_id" => $param['location_id']);
        } else {
            $key = null;
        }
        $data = array(
            'location_code' => $param['location_code'],
            'location_name' => $param['location_name'],
            'customer_id' => $param['customer_id'],
        );
        $gen_db->updateOrInsert('location_master', $key, $data);

        // id(keyColumnの値)を戻す。keyColumnがないModelではfalseを戻す。
        if (isset($key)) {
            $key = $param['location_id'];
        } else {
            $key = $gen_db->getSequence("location_master_location_id_seq");
        }
        return $key;
    }

}
