<?php

class Master_Equip_Model extends Base_ModelBase
{

    var $csvUpdateMode = false;

    protected function _getKeyColumn()
    {
        return 'equip_id';
    }

    protected function _setDefault(&$param, $entryMode)
    {
        global $gen_db;

        // 上書きモードの処理　（csv & excel）
        if ($this->csvUpdateMode && !isset($param['equip_id']) && $param['equip_code'] != "") {
            $query = "select equip_id from equip_master where equip_code = '{$param['equip_code']}'";
            $param['equip_id'] = $gen_db->queryOneValue($query);
            if ($param['equip_id'] === null)
                unset($param['equip_id']);
        }
    }

    protected function _getColumns()
    {
        $columns = array(
            array(
                "column" => "equip_id",
                "pattern" => "id",
            ),
            array(
                "column" => "equip_code",
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
                        "msg" => _g('設備コードを指定してください。')
                    ),
                    // 新規登録時の重複チェック
                    array(
                        "cat" => "notExistRecord",
                        "msg" => _g('設備コードはすでに使用されています。別のコードを指定してください。'),
                        "skipHasError" => true,
                        "skipValidatePHP" => "[[equip_id]]!=''", // 修正はスキップ
                        "param" => "select equip_id from equip_master where equip_code = $1"
                    ),
                    // 修正時の重複チェック
                    array(
                        "cat" => "notExistRecord",
                        "msg" => _g('設備コードはすでに使用されています。別のコードを指定してください。'),
                        "skipHasError" => true,
                        "skipValidatePHP" => "[[equip_id]]==''", // 新規登録はスキップ
                        // 更新のときは、自分自身の番号をチェック対象としない（自分自身と重複するのは当然）
                        "param" => "select equip_id from equip_master where equip_code = $1
                            and equip_id <> [[equip_id]]"
                    ),
                ),
            ),
            array(
                "column" => "equip_name",
                "convert" => array(
                    array(
                        "cat" => "trimEx",
                    ),
                ),
                "validate" => array(
                    array(
                        "cat" => "required",
                        "msg" => _g('設備名を指定してください。')
                    ),
                ),
            ),
        );

        return $columns;
    }

    protected function _regist(&$param, $isFirstRegist)
    {
        global $gen_db;

        if (isset($param['equip_id']) && is_numeric($param['equip_id'])) {
            $key = array("equip_id" => $param['equip_id']);
        } else {
            $key = null;
        }
        $data = array(
            'equip_code' => $param['equip_code'],
            'equip_name' => $param['equip_name'],
            'remarks' => $param['remarks'],
        );
        $gen_db->updateOrInsert('equip_master', $key, $data);

        // id(keyColumnの値)を戻す。keyColumnがないModelではfalseを戻す。
        if (isset($key)) {
            $key = $param['equip_id'];
        } else {
            $key = $gen_db->getSequence("equip_master_equip_id_seq");
        }
        return $key;
    }

}
