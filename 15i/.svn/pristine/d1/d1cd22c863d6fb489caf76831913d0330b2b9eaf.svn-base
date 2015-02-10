<?php

class Master_PricePercentGroup_Model extends Base_ModelBase
{

    var $csvUpdateMode = false;

    protected function _getKeyColumn()
    {
        return 'price_percent_group_id';
    }

    protected function _setDefault(&$param, $entryMode)
    {
        global $gen_db;

        // 上書きモードの処理　（csv & excel）
        if ($this->csvUpdateMode && !isset($param['price_percent_group_id']) && $param['price_percent_group_code'] != "") {
            $query = "select price_percent_group_id from price_percent_group_master where price_percent_group_code = '{$param['price_percent_group_code']}'";
            $param['price_percent_group_id'] = $gen_db->queryOneValue($query);
            if ($param['price_percent_group_id'] === null)
                unset($param['price_percent_group_id']);
        }
    }

    protected function _getColumns()
    {
        $columns = array(
            array(
                "column" => "price_percent_group_id",
                "pattern" => "id",
            ),
            array(
                "column" => "price_percent_group_code",
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
                        "msg" => _g('掛率グループコードを指定してください。')
                    ),
                    // 新規登録時の重複チェック
                    array(
                        "cat" => "notExistRecord",
                        "msg" => _g('掛率グループコードはすでに使用されています。別のコードを指定してください。'),
                        "skipHasError" => true,
                        "skipValidatePHP" => "[[price_percent_group_id]]!=''", // 修正はスキップ
                        "param" => "select price_percent_group_id from price_percent_group_master where price_percent_group_code = $1"
                    ),
                    // 修正時の重複チェック
                    array(
                        "cat" => "notExistRecord",
                        "msg" => _g('掛率グループコードはすでに使用されています。別のコードを指定してください。'),
                        "skipHasError" => true,
                        "skipValidatePHP" => "[[price_percent_group_id]]==''", // 新規登録はスキップ
                        // 更新のときは、自分自身の番号をチェック対象としない（自分自身と重複するのは当然）
                        "param" => "select price_percent_group_id from price_percent_group_master where price_percent_group_code = $1
                            and price_percent_group_id <> [[price_percent_group_id]]"
                    ),
                ),
            ),
            array(
                "column" => "price_percent_group_name",
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
                        "msg" => _g('掛率グループ名を指定してください。')
                    ),
                    // 新規登録時の重複チェック
                    array(
                        "cat" => "notExistRecord",
                        "msg" => _g('掛率グループ名はすでに使用されています。別の名前を指定してください。'),
                        "skipHasError" => true,
                        "skipValidatePHP" => "[[price_percent_group_id]]!=''", // 修正はスキップ
                        "param" => "select price_percent_group_id from price_percent_group_master where price_percent_group_name = $1"
                    ),
                    // 修正時の重複チェック
                    array(
                        "cat" => "notExistRecord",
                        "msg" => _g('掛率グループ名はすでに使用されています。別の名前を指定してください。'),
                        "skipHasError" => true,
                        "skipValidatePHP" => "[[price_percent_group_id]]==''", // 新規登録はスキップ
                        // 更新のときは、自分自身の番号をチェック対象としない（自分自身と重複するのは当然）
                        "param" => "select price_percent_group_id from price_percent_group_master where price_percent_group_name = $1
                            and price_percent_group_id <> [[price_percent_group_id]]"
                    ),
                ),
            ),
            array(
                "column" => "price_percent",
                "convert" => array(
                    array(
                        "cat" => "strToNum",
                    ),
                ),
                "validate" => array(
                    array(
                        "cat" => "numeric",
                        "skipHasError" => true,
                        "msg" => _g('掛率には数値を指定してください。'),
                    ),
                ),
            ),
        );

        return $columns;
    }

    protected function _regist(&$param, $isFirstRegist)
    {
        global $gen_db;

        if (isset($param['price_percent_group_id']) && is_numeric($param['price_percent_group_id'])) {
            $key = array("price_percent_group_id" => $param['price_percent_group_id']);
        } else {
            $key = null;
        }
        $data = array(
            'price_percent_group_code' => $param['price_percent_group_code'],
            'price_percent_group_name' => $param['price_percent_group_name'],
            'price_percent' => (is_numeric($param['price_percent']) ? $param['price_percent'] : null),
        );
        $gen_db->updateOrInsert('price_percent_group_master', $key, $data);

        // id(keyColumnの値)を戻す。keyColumnがないModelではfalseを戻す。
        if (isset($key)) {
            $key = $param['price_percent_group_id'];
        } else {
            $key = $gen_db->getSequence("price_percent_group_master_price_percent_group_id_seq");
        }
        return $key;
    }

}
