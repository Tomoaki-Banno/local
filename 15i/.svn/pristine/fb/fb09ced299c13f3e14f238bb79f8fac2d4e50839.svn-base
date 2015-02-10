<?php

class Stock_Move_Model extends Base_ModelBase
{

    protected function _getKeyColumn()
    {
        return 'move_id';
    }

    protected function _setDefault(&$param, $entryMode)
    {
        switch ($entryMode) {
            case "csv":
                self::_codeToId($param, "item_code", "item_id", "", "", "item_master");
                self::_codeToId($param, "source_location_code", "source_location_id", "location_code", "location_id", "location_master");
                if ($param['source_location_code'] == "")
                    $param['source_location_id'] = "0";
                self::_codeToId($param, "dist_location_code", "dist_location_id", "location_code", "location_id", "location_master");
                if ($param['dist_location_code'] == "")
                    $param['dist_location_id'] = "0";
                break;
        }
    }

    protected function _getColumns()
    {
        $columns = array(
            array(
                "column" => "move_id",
                "pattern" => "id",
            ),
            array(
                "column" => "move_date",
                "validate" => array(
                    array(
                        "cat" => "systemDateOrLater",
                        "msg" => _g('移動日')
                    ),
                ),
            ),
            array(
                "column" => "item_id",
                "pattern" => "item_id_required",
            ),
            array(
                "column" => "seiban",
                "convert" => array(
                    array(
                        "cat" => "nullBlankToValue",
                        "param" => ''
                    ),
                ),
                "skipValidateJS" => "true", // JSではチェックの必要なし
                "validate" => array(
                    // 製番の整合性チェック。
                    array(
                        "skipHasError" => true,
                        "skipValidatePHP" => "$1==='' || [[item_id]]===''",
                        "cat" => "existRecord",
                        "msg" => _g('MRP/ロット品目に対して製番を指定することはできません。'),
                        "param" => "select item_id from item_master where item_id=[[item_id]] and order_class=0",
                    ),
                ),
            ),
            array(
                "column" => "quantity",
                "convert" => array(
                    array(
                        "cat" => "strToNum",
                    ),
                ),
                "validate" => array(
                    array(
                        "cat" => "minNum",
                        "msg" => _g('数量が正しくありません。0以上の数値を指定してください。'),
                        "param" => 0
                    ),
                    array(
                        "cat" => "eval",
                        "msg" => _g('移動元の在庫が不足しているため、移動できません。'),
                        "skipHasError" => true,
                        "evalPHP" => "\$res=\$this->_checkStock([[item_id]], [[seiban]], [[source_location_id]], [[move_id]], [[quantity]]);",
                    ),
                ),
            ),
            array(
                "column" => "source_location_id",
                "pattern" => "location_id_required",
                "label" => _g("移動元ロケーション"),
            ),
            array(
                "column" => "dist_location_id",
                "skipValidatePHP" => "$1==='0'",
                "skipValidateJS" => "$1==='0'",
                "dependentColumn" => "source_location_id",
                "validate" => array(
                    array(
                        "cat" => "numeric",
                        "msg" => _g('移動先ロケーションが正しくありません。'),
                    ),
                    array(
                        "cat" => "existRecord",
                        "msg" => _g('移動先ロケーションがマスタに登録されていません。'),
                        "skipHasError" => true,
                        "param" => "select location_id from location_master where location_id = $1"
                    ),
                    array(
                        "cat" => "eval",
                        "msg" => _g('移動元と移動先のロケーションが同じです。'),
                        "evalPHP" => "\$res=($1!=[[source_location_id]])",
                        "evalJS" => "res=($1!=[[source_location_id]])",
                    ),
                ),
            ),
            array(
                "column" => "order_detail_id",
                "pattern" => "order_detail_id",
                "addwhere" => "classification=0",
            ),
            array(
                "column" => "order_detail_id",
                "convert" => array(
                    array(
                        "cat" => "blankToNull",
                    ),
                ),
                "skipValidatePHP" => "$1===null",
                "skipValidateJS" => "$1===null",
                "validate" => array(
                    array(
                        "cat" => "existRecord",
                        "msg" => _g('実績登録が完了したオーダーは指定できません。'),
                        "skipValidatePHP" => "[[move_id]]!=''", // 修正モードではチェックしない
                        "skipValidateJS" => "[[move_id]]!=''", // 修正モードではチェックしない
                        "skipHasError" => true,
                        "param" => "select order_detail_id from order_detail where order_detail_id = $1 and (order_detail_completed = false or order_detail_completed is null)",
                    ),
                ),
            ),
        );
        return $columns;
    }

    // validatorのevalでの在庫数チェック用
    protected function _checkStock($itemId, $seiban, $sourceLocationId, $moveId, $quantity)
    {
        global $gen_db;

        // 移動元の現在庫数を取得
        $stock = Logic_Stock::getLogicalStock($itemId, $seiban, $sourceLocationId, 0, $moveId);

        // 修正モードで、なおかつ品目・製番・ロケが変更されていない場合、移動元現在庫数に元の移動数量を足しておく必要がある
        if (isset($moveId)) {
            $query = "
            select
                location_move.item_id
                ,location_move.seiban
                ,location_move.source_location_id
                ,quantity
            from
                location_move
            where
               move_id = '{$moveId}'
               and location_move.item_id = '{$itemId}'
               and coalesce(location_move.seiban,'') = '{$seiban}'
               and location_move.source_location_id = '{$sourceLocationId}'
               and location_move.lot_id = '0'
            ";
            $res = $gen_db->queryOneRowObject($query);

            if (isset($res->quantity) && is_numeric($res->quantity)) {
                $stock += $res->quantity;
            }
        }
        return ($stock - $quantity >= 0);
    }

    protected function _regist(&$param, $isFirstRegist)
    {
        // 更新の場合は、先に削除を行う
        // （入出庫等の調整があるので、単純にUpdateしてはダメ。いったん削除し、あらためて登録を行う）
        if (isset($param['move_id'])) {
            Logic_Move::deleteMove($param['move_id']);
        }

        // 移動と入出庫の登録、現在庫更新
        $moveId = Logic_Move::entryMove(
                        @$param['move_id']
                        , $param['move_date']
                        , $param['item_id']
                        , $param['seiban']
                        , $param['source_location_id']
                        , $param['dist_location_id']
                        , 0 // lot
                        , $param['quantity']
                        , @$param['order_detail_id']
                        , $param['remarks']
        );

        // 在庫移動表発行済みフラグをオフにする
        Logic_Move::setMovePrintedFlag(array($moveId), false);

        // id(keyColumnの値)を戻す。keyColumnがないModelではfalseを戻す。
        return $moveId;
    }

}
