<?php

class Master_ItemGroup_Model extends Base_ModelBase
{

    var $csvUpdateMode = false;

    protected function _getKeyColumn()
    {
        return 'item_group_id';
    }

    protected function _setDefault(&$param, $entryMode)
    {
        global $gen_db;

        // 上書きモードの処理　（csv & excel）
        if ($this->csvUpdateMode && (!isset($param['item_group_id']) || $param['item_group_id'] === null) && $param['item_group_code'] != "") {
            $query = "select item_group_id from item_group_master where item_group_code = '{$param['item_group_code']}'";
            $param['item_group_id'] = $gen_db->queryOneValue($query);
            if ($param['item_group_id'] === null)
                unset($param['item_group_id']);
        }
    }

    protected function _getColumns()
    {
        $columns = array(
            array(
                "column" => "item_group_id",
                "pattern" => "id",
            ),
            array(
                "column" => "item_group_code",
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
                        "msg" => _g('品目グループコードを指定してください。')
                    ),
                    // 新規登録時の重複チェック
                    array(
                        "cat" => "notExistRecord",
                        "msg" => _g('品目グループコードはすでに使用されています。別のコードを指定してください。'),
                        "skipHasError" => true,
                        "skipValidatePHP" => "[[item_group_id]]!=''", // 修正はスキップ
                        "param" => "select item_group_id from item_group_master where item_group_code = $1"
                    ),
                    // 修正時の重複チェック
                    array(
                        "cat" => "notExistRecord",
                        "msg" => _g('品目グループコードはすでに使用されています。別のコードを指定してください。'),
                        "skipHasError" => true,
                        "skipValidatePHP" => "[[item_group_id]]==''", // 新規登録はスキップ
                        // 更新のときは、自分自身の番号をチェック対象としない（自分自身と重複するのは当然）
                        "param" => "select item_group_id from item_group_master where item_group_code = $1
                                and item_group_id <> [[item_group_id]]"
                    ),
                ),
            ),
            array(
                "column" => "item_group_name",
                "convert" => array(
                    array(
                        "cat" => "trimEx",
                    ),
                ),
                "validate" => array(
                    array(
                        "cat" => "required",
                        "msg" => _g('品目グループ名を指定してください。')
                    ),
                ),
            ),
        );

        return $columns;
    }

    protected function _regist(&$param, $isFirstRegist)
    {
        global $gen_db;

        if (isset($param['item_group_id']) && is_numeric($param['item_group_id'])) {
            $key = array("item_group_id" => $param['item_group_id']);
        } else {
            $key = null;
        }
        $data = array(
            'item_group_code' => $param['item_group_code'],
            'item_group_name' => $param['item_group_name'],
        );
        $gen_db->updateOrInsert('item_group_master', $key, $data);

        // id(keyColumnの値)を戻す。keyColumnがないModelではfalseを戻す。
        if (isset($key)) {
            $key = $param['item_group_id'];
        } else {
            $key = $gen_db->getSequence("item_group_master_item_group_id_seq");
        }
        return $key;
    }

}
