<?php

class Master_Currency_Model extends Base_ModelBase
{

    var $csvUpdateMode = false;

    protected function _getKeyColumn()
    {
        return 'currency_id';
    }

    protected function _setDefault(&$param, $entryMode)
    {
        global $gen_db;

        // 上書きモードの処理　（csv & excel）
        if ($this->csvUpdateMode && !isset($param['currency_id']) && $param['currency_name'] != "") {
            $query = "select currency_id from currency_master where currency_name = '{$param['currency_name']}'";
            $param['currency_id'] = $gen_db->queryOneValue($query);
            if ($param['currency_id'] === null)
                unset($param['currency_id']);
        }
    }

    protected function _getColumns()
    {
        global $gen_db;

        $keyCurrency = $gen_db->queryOneValue("select key_currency from company_master");
        $columns = array(
            array(
                "column" => "currency_id",
                "pattern" => "id",
            ),
            array(
                "column" => "currency_name",
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
                        "msg" => _g('取引通貨を指定してください。')
                    ),
                    array(
                        "cat" => "notEqualString",
                        "param" => $keyCurrency,
                        "msg" => sprintf(_g('「%s」は基軸通貨として登録されていますので、ここで登録する必要はありません。'), $keyCurrency)
                    ),
                    // 新規登録時の重複チェック
                    array(
                        "cat" => "notExistRecord",
                        "msg" => _g('取引通貨はすでに使用されています。別の取引通貨を指定してください。'),
                        "skipHasError" => true,
                        "skipValidatePHP" => "[[currency_id]]!=''", // 修正はスキップ
                        "param" => "select currency_id from currency_master where currency_name = $1"
                    ),
                    // 修正時の重複チェック
                    array(
                        "cat" => "notExistRecord",
                        "msg" => _g('取引通貨はすでに使用されています。別の取引通貨を指定してください。'),
                        "skipHasError" => true,
                        "skipValidatePHP" => "[[currency_id]]==''", // 新規登録はスキップ
                        // 更新のときは、自分自身の番号をチェック対象としない（自分自身と重複するのは当然）
                        "param" => "select currency_id from currency_master where currency_name = $1
                            and currency_id <> [[currency_id]]"
                    ),
                ),
            ),
        );

        return $columns;
    }

    protected function _regist(&$param, $isFirstRegist)
    {
        global $gen_db;

        if (isset($param['currency_id']) && is_numeric($param['currency_id'])) {
            $key = array("currency_id" => $param['currency_id']);
        } else {
            $key = null;
        }
        $data = array(
            'currency_name' => $param['currency_name'],
        );
        $gen_db->updateOrInsert('currency_master', $key, $data);
        
        // id(keyColumnの値)を戻す。keyColumnがないModelではfalseを戻す。
        if (isset($key)) {
            $key = $param['currency_id'];
        } else {
            $key = $gen_db->getSequence("currency_master_currency_id_seq");
        }
        return $key;
    }

}
