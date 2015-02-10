<?php

class Master_TaxRate_Model extends Base_ModelBase
{

    var $csvUpdateMode = false;

    protected function _getKeyColumn()
    {
        return 'tax_rate_id';
    }

    protected function _setDefault(&$param, $entryMode)
    {
        global $gen_db;

        // 上書きモードの処理（csv & excel）
        if ($this->csvUpdateMode && !isset($param['tax_rate_id'])) {
            $query = "select tax_rate_id from tax_rate_master where apply_date = '{$param['apply_date']}'";
            $param['tax_rate_id'] = $gen_db->queryOneValue($query);
            if ($param['tax_rate_id'] === null)
                unset($param['tax_rate_id']);
        }
    }

    protected function _getColumns()
    {
        $columns = array(
            array(
                "column" => "tax_rate_id",
                "pattern" => "id",
            ),
            array(
                "column" => "apply_date",
                "validate" => array(
                    array(
                        "cat" => "dateString",
                        "msg" => _g('適用開始日が正しくありません。')
                    ),
                    // 新規登録時の重複チェック
                    array(
                        "cat" => "notExistRecord",
                        "msg" => _g('この適用開始日の税率がすでに登録されています。'),
                        "skipHasError" => true,
                        "skipValidatePHP" => "[[tax_rate_id]]!=''", // 修正はスキップ
                        "param" => "select tax_rate_id from tax_rate_master where apply_date = $1"
                    ),
                    // 修正時の重複チェック
                    array(
                        "cat" => "notExistRecord",
                        "msg" => _g('この適用開始日の税率がすでに登録されています。'),
                        "skipHasError" => true,
                        "skipValidatePHP" => "[[tax_rate_id]]==''", // 新規登録はスキップ
                        // 更新のときは、自分自身をチェック対象としない（自分自身と重複するのは当然）
                        "param" => "select tax_rate_id from tax_rate_master where apply_date = $1
                            and tax_rate_id <> [[tax_rate_id]]"
                    ),
                ),
            ),
            array(
                "column" => "tax_rate",
                "pattern" => "numeric",
                "label" => _g("税率"),
            ),
            array(
                "column" => "tax_rate",
                "validate" => array(
                    array(
                        "cat" => "required",
                        "msg" => _g('税率を指定してください。')
                    ),
                ),
            ),
        );

        return $columns;
    }

    protected function _regist(&$param, $isFirstRegist)
    {
        global $gen_db;

        if (isset($param['tax_rate_id']) && is_numeric($param['tax_rate_id'])) {
            $key = array("tax_rate_id" => $param['tax_rate_id']);
        } else {
            $key = null;
        }
        $data = array(
            'apply_date' => $param['apply_date'],
            'tax_rate' => $param['tax_rate'],
        );
        $gen_db->updateOrInsert('tax_rate_master', $key, $data);

        // id(keyColumnの値)を戻す。keyColumnがないModelではfalseを戻す。
        if (isset($key)) {
            $key = $param['tax_rate_id'];
        } else {
            $key = $gen_db->getSequence("tax_rate_master_tax_rate_id_seq");
        }
        return $key;
    }

}
