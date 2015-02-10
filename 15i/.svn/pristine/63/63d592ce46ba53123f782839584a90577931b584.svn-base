<?php

class Master_CustomerPrice_Model extends Base_ModelBase
{

    var $csvUpdateMode = false;

    protected function _getKeyColumn()
    {
        return 'customer_price_id';
    }

    protected function _setDefault(&$param, $entryMode)
    {
        global $gen_db;

        // 上書きモードの処理　（csv）
        if ($this->csvUpdateMode && !isset($param['customer_price_id']) && $param['customer_no'] != "" && $param['item_code'] != "") {
            $query = "select customer_price_id from customer_price_master
                 left join customer_master on customer_price_master.customer_id = customer_master.customer_id
                 left join item_master on customer_price_master.item_id = item_master.item_id
                where customer_no = '{$param['customer_no']}' and item_code = '{$param['item_code']}'";
            $param['customer_price_id'] = $gen_db->queryOneValue($query);
            if ($param['customer_price_id'] === null)
                unset($param['customer_price_id']);
        }

        // ***** for csv *****
        // code -> id
        self::_codeToId($param, "customer_no", "customer_id", "", "", "customer_master");
        self::_codeToId($param, "item_code", "item_id", "", "", "item_master");
    }

    protected function _getColumns()
    {
        $columns = array(
            array(
                "column" => "customer_price_id",
                "pattern" => "id",
            ),
            array(
                "column" => "customer_id",
                "pattern" => "customer_id_required",
                "label" => _g("得意先"),
                "addwhere" => "classification=0",
            ),
            array(
                "column" => "item_id",
                "pattern" => "item_id_required",
            ),
            array(
                "column" => "item_id",
                "validate" => array(
                    array(
                        "cat" => "existRecord",
                        "msg" => _g('この品目は受注対象外です。'),
                        "skipHasError" => true,
                        "param" => "select item_id from item_master where item_id = $1 and received_object=0"
                    ),
                    // 新規登録時の重複チェック
                    array(
                        "cat" => "notExistRecord",
                        "msg" => _g('この得意先・品目の販売単価はすでに存在しています。'),
                        "skipHasError" => true,
                        "skipValidatePHP" => "[[customer_price_id]]!=''", // 修正はスキップ
                        "param" => "select customer_price_id from customer_price_master where customer_id = [[customer_id]] and item_id = $1"
                    ),
                    // 修正時の重複チェック
                    array(
                        "cat" => "notExistRecord",
                        "msg" => _g('この得意先・品目の販売単価はすでに存在しています。'),
                        "skipHasError" => true,
                        "skipValidatePHP" => "[[customer_price_id]]==''", // 新規登録はスキップ
                        // 更新のときは、自分自身の番号をチェック対象としない（自分自身と重複するのは当然）
                        "param" => "select customer_price_id from customer_price_master where customer_id = [[customer_id]] and item_id = $1
                            and customer_price_id <> [[customer_price_id]]"
                    ),
                ),
            ),
            array(
                "column" => "selling_price",
                "convert" => array(
                    array(
                        "cat" => "strToNum",
                    ),
                ),
                "validate" => array(
                    array(
                        "cat" => "minNum",
                        "msg" => _g('販売単価が正しくありません。'),
                        "param" => 0,
                    ),
                ),
            ),
        );

        return $columns;
    }

    protected function _regist(&$param, $isFirstRegist)
    {
        global $gen_db;

        if (isset($param['customer_price_id']) && is_numeric($param['customer_price_id'])) {
            $key = array("customer_price_id" => $param['customer_price_id']);
        } else {
            $key = null;
        }
        $data = array(
            'customer_id' => $param['customer_id'],
            'item_id' => $param['item_id'],
            'selling_price' => $param['selling_price'],
        );
        $gen_db->updateOrInsert('customer_price_master', $key, $data);

        // id(keyColumnの値)を戻す。keyColumnがないModelではfalseを戻す。
        if (isset($key)) {
            $key = $param['customer_price_id'];
        } else {
            $key = $gen_db->getSequence("customer_price_master_customer_price_id_seq");
        }
        return $key;
    }

}
