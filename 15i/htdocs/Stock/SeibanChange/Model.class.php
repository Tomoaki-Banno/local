<?php

class Stock_SeibanChange_Model extends Base_ModelBase
{

    protected function _getKeyColumn()
    {
        return 'change_id';
    }

    protected function _setDefault(&$param, $entryMode)
    {
    }

    protected function _getColumns()
    {
        $columns = array(
            array(
                "column" => "change_id",
                "pattern" => "id",
            ),
            array(
                "column" => "change_date",
                "validate" => array(
                    array(
                        "cat" => "systemDateOrLater",
                        "msg" => _g('日付')
                    ),
                ),
            ),
            array(
                "column" => "item_id",
                "pattern" => "item_id_required",
                "label" => _g("引当元品目"),
            ),
            array(
                "column" => "location_id",
                "pattern" => "location_id_required",
                "label" => _g("引当元ロケーション"),
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
                        "msg" => _g('引当元の在庫が不足しているため、引当できません。'),
                        "skipHasError" => true,
                        "evalPHP" => "\$res=\$this->_checkStock([[item_id]], [[source_seiban]], [[location_id]], [[change_id]], [[quantity]]);",
                    ),
                ),
            ),
            array(
                "column" => "dist_seiban",
                "dependentColumn" => "source_seiban",
                "validate" => array(
                    array(
                        "cat" => "eval",
                        "msg" => _g('引当元と引当先の製番が同じです。'),
                        "evalPHP" => "\$res=($1!=[[source_seiban]])",
                        "evalJS" => "res=($1!=[[source_seiban]])",
                    ),
                ),
            ),
        );
        return $columns;
    }

    // validatorのevalでの在庫数チェック用
    protected function _checkStock($itemId, $sourceSeiban, $locationId, $changeId, $quantity)
    {
        global $gen_db;

        // 移動数量にマイナスはありえない（上でチェックしている）ため、在庫切れチェックは移動元だけでよい。
        $stock = Logic_Stock::getLogicalStock($itemId, $sourceSeiban, $locationId, 0);
        if (!is_numeric($stock)) {
            $stock = 0;
        }

        // 修正モードでは引当可能数に現引当数を加える
        if (is_numeric($changeId)) {
            $currentQty = $gen_db->queryOneValue("select quantity from seiban_change where change_id = '{$changeId}'");
            if (is_numeric($currentQty))
                $stock += $currentQty;
        }

        return ($stock - $quantity >= 0);
    }

    protected function _regist(&$param, $isFirstRegist)
    {
        global $gen_db;
        
        // 更新の場合は、先に削除を行う
        // （入出庫等の調整があるので、単純にUpdateしてはダメ。いったん削除し、あらためて登録を行う）
        if (isset($param['change_id'])) {
            Logic_SeibanChange::deleteSeibanChange($param['change_id']);
        }

        // 移動と入出庫の登録、現在庫更新
        Logic_SeibanChange::entrySeibanChange(
                @$param['change_id']
                , $param['change_date']
                , $param['item_id']
                , $param['source_seiban']
                , $param['dist_seiban']
                , $param['location_id']
                , 0     // lot
                , $param['quantity']
                , $param['remarks']
        );

        // id(keyColumnの値)を戻す。keyColumnがないModelではfalseを戻す。
        if (isset($param['change_id'])) {
            $keyValue = $param['change_id'];
        } else {
            $keyValue = $gen_db->getSequence("seiban_change_change_id_seq");
        }
        return $keyValue;
    }

}
