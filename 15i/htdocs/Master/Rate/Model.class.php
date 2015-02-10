<?php

class Master_Rate_Model extends Base_ModelBase
{

    var $csvUpdateMode = false;

    protected function _getKeyColumn()
    {
        return 'rate_id';
    }

    protected function _setDefault(&$param, $entryMode)
    {
        // code -> id
        self::_codeToId($param, "currency_name", "currency_id", "", "", "currency_master");
    }

    protected function _getColumns()
    {
        $columns = array(
            array(
                "column" => "rate_id",
                "pattern" => "id",
            ),
            array(
                "column" => "currency_id",
                "validate" => array(
                    array(
                        "cat" => "numeric",
                        "msg" => _g('取引通貨が正しくありません。'),
                        "skipHasError" => true,
                    ),
                    array(
                        "cat" => "existRecord",
                        "msg" => _g('取引通貨が正しくありません。'),
                        "skipHasError" => true,
                        "param" => "select currency_id from currency_master where currency_id = $1"
                    ),
                ),
            ),
            array(
                "column" => "rate_date",
                "validate" => array(
                    array(
                        "cat" => "systemDateOrLater",
                        "msg" => _g('適用開始日')
                    ),
                    // 新規登録時の重複チェック
                    array(
                        "cat" => "notExistRecord",
                        "msg" => _g('指定された日と同じ日付で、この取引通貨の為替レートがすでに登録されています。'),
                        "skipHasError" => true,
                        "skipValidatePHP" => "[[rate_id]]!=''", // 修正はスキップ
                        "param" => "select rate_id from rate_master where currency_id = [[currency_id]] and rate_date = $1"
                    ),
                    // 修正時の重複チェック
                    array(
                        "cat" => "notExistRecord",
                        "msg" => _g('指定された日と同じ日付で、この取引通貨の為替レートがすでに登録されています。'),
                        "skipHasError" => true,
                        "skipValidatePHP" => "[[rate_id]]==''", // 新規登録はスキップ
                        // 更新のときは、自分自身をチェック対象としない（自分自身と重複するのは当然）
                        "param" => "select rate_id from rate_master where currency_id = [[currency_id]] and rate_date = $1
                            and rate_id <> [[rate_id]]"
                    ),
                ),
            ),
            array(
                "column" => "rate",
                "pattern" => "numeric",
                "label" => _g("為替レート"),
            ),
            array(
                "column" => "rate",
                "validate" => array(
                    array(
                        "cat" => "required",
                        "msg" => _g('為替レートを指定してください。')
                    ),
                ),
            ),
        );

        return $columns;
    }

    protected function _regist(&$param, $isFirstRegist)
    {
        global $gen_db;

        if (isset($param['rate_id']) && is_numeric($param['rate_id'])) {
            $key = array("rate_id" => $param['rate_id']);
        } else {
            $key = null;
        }
        $data = array(
            'currency_id' => $param['currency_id'],
            'rate_date' => $param['rate_date'],
            'rate' => $param['rate'],
            'remarks' => $param['remarks'],
        );
        $gen_db->updateOrInsert('rate_master', $key, $data);

        // id(keyColumnの値)を戻す。keyColumnがないModelではfalseを戻す。
        if (isset($key)) {
            $key = $param['rate_id'];
        } else {
            $key = $gen_db->getSequence("rate_master_rate_id_seq");
        }
        return $key;
    }

}
