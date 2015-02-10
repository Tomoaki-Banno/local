<?php

require_once(BASE_DIR . "ModelBase.class.php");

class Master_CustomerGroup_Model extends Base_ModelBase
{

    var $csvUpdateMode = false;

    protected function _getKeyColumn()
    {
        return 'customer_group_id';
    }

    protected function _setDefault(&$param, $entryMode)
    {
        global $gen_db;

        // 上書きモードの処理　（csv & excel）
        if ($this->csvUpdateMode && (!isset($param['customer_group_id']) || $param['customer_group_id'] === null) && $param['customer_group_code'] != "") {
            $query = "select customer_group_id from customer_group_master where customer_group_code = '{$param['customer_group_code']}'";
            $param['customer_group_id'] = $gen_db->queryOneValue($query);
            if ($param['customer_group_id'] === null)
                unset($param['customer_group_id']);
        }
    }

    protected function _getColumns()
    {
        $columns = array(
            array(
                "column" => "customer_group_id",
                "pattern" => "id",
            ),
            array(
                "column" => "customer_group_code",
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
                        "msg" => _g('取引先グループコードを指定してください。')
                    ),
                    // 新規登録時の重複チェック
                    array(
                        "cat" => "notExistRecord",
                        "msg" => _g('取引先グループコードはすでに使用されています。別のコードを指定してください。'),
                        "skipHasError" => true,
                        "skipValidatePHP" => "[[customer_group_id]]!=''", // 修正はスキップ
                        "param" => "select customer_group_id from customer_group_master where customer_group_code = $1"
                    ),
                    // 修正時の重複チェック
                    array(
                        "cat" => "notExistRecord",
                        "msg" => _g('取引先グループコードはすでに使用されています。別のコードを指定してください。'),
                        "skipHasError" => true,
                        "skipValidatePHP" => "[[customer_group_id]]==''", // 新規登録はスキップ
                        // 更新のときは、自分自身の番号をチェック対象としない（自分自身と重複するのは当然）
                        "param" => "select customer_group_id from customer_group_master where customer_group_code = $1
                                and customer_group_id <> [[customer_group_id]]"
                    ),
                ),
            ),
            array(
                "column" => "customer_group_name",
                "convert" => array(
                    array(
                        "cat" => "trimEx",
                    ),
                ),
                "validate" => array(
                    array(
                        "cat" => "required",
                        "msg" => _g('取引先グループ名を指定してください。')
                    ),
                ),
            ),
        );

        return $columns;
    }

    protected function _regist(&$param, $isFirstRegist)
    {
        global $gen_db;

        if (isset($param['customer_group_id']) && is_numeric($param['customer_group_id'])) {
            $key = array("customer_group_id" => $param['customer_group_id']);
        } else {
            $key = null;
        }
        $data = array(
            'customer_group_code' => $param['customer_group_code'],
            'customer_group_name' => $param['customer_group_name'],
        );
        $gen_db->updateOrInsert('customer_group_master', $key, $data);
    }

}