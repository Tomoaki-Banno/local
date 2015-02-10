<?php

class Monthly_StockInput_Model extends Base_ModelBase
{

    protected function _getKeyColumn()
    {
        // キー項目がない場合、常に新規として扱いたければtrue、修正として扱いたければfalseを返す。
        return true;
    }

    protected function _setDefault(&$param, $entryMode)
    {
        // Entry/BulkEntry/BarcodeEntry の headerArray/detailArray にないパラメータを生成するときは
        // ここで行う。

        if (!isset($param['lot_id'])) {
            $param['lot_id'] = '0';
        }

        switch ($entryMode) {
            case "csv":
                // コード => ID
                self::_codeToId($param, "item_code", "item_id", "", "", "item_master");
                self::_codeToId($param, "location_code", "location_id", "", "", "location_master");
                if ($param['location_code'] == "")
                    $param['location_id'] = "0";
                
            case "barcode":
                // コード => ID
                self::_codeToId($param, "item_code", "item_id", "", "", "item_master");
                // lot_it は 0
                // 　lot_noとは異なる。実質未使用の項目
                $param["lot_id"] = "0";
                break;
        }
    }

    protected function _getColumns()
    {
        $columns = array(
            array(
                "column" => "inventory_date",
                "validate" => array(
                    array(
                        "cat" => "systemDateOrLater",
                        "msg" => _g('棚卸日')
                    ),
                    array(
                        // 誤登録防止
                        "cat" => "eval",
                        "evalPHP" => "\$res=(strtotime($1)<strtotime(date('Y-m-01').' +1 month'))",
                        "msg" => _g('棚卸日に翌月以降の日付を指定することはできません。')
                    ),
                ),
            ),
            // 本来は非表示品目やダミー品目を排除すべきだろうが（CSVからは登録できてしまう）、
            // 「裏技」としてインポートできるようにしておく。
            array(
                "column" => "item_id",
                "pattern" => "item_id_required",
            ),
            array(
                "column" => "seiban",
                "validate" => array(
                    // 10iまでは製番の妥当性をチェックしていなかった（10iの製番は数値のみだったので、数値チェックのみ
                    // 行なっていた）。そのため、棚卸CSVインポートで新たな製番の在庫をつくるという裏技が使えてしまっていた。
                    // 12iでは受注製番および在庫製番のみ使用できるようにした。
                    array(
                        "cat" => "existRecord",
                        "msg" => _g("製番が正しくありません。"),
                        "skipValidatePHP" => "$1==''",
                        "skipValidateJS" => "$1==''",
                        "skipHasError" => true,
                        "param" => "select seiban from received_detail where seiban = $1 union select seiban from item_in_out where seiban = $1",
                    ),
                    array(
                        "cat" => "existRecord",
                        "msg" => _g("MRP品目に対して製番を指定することはできません。"),
                        "skipValidatePHP" => "$1==''",
                        "skipValidateJS" => "$1==''",
                        "skipHasError" => true,
                        "param" => "select * from item_master where item_id = [[item_id]] and order_class in (0,2)",
                    ),
                ),
            ),
            array(
                "column" => "location_id",
                "pattern" => "location_id_required",
            ),
            array(
                "column" => "lot_id",
                "pattern" => "lot_id",
            ),
            array(
                "column" => "inventory_quantity",
                "convert" => array(
                    array(
                        "cat" => "strToNum",
                    ),
                ),
                "validate" => array(
                    array(
                        "cat" => "blankOrNumeric",
                        "msg" => _g('実在庫が正しくありません。')
                    ),
                ),
            ),
        );

        return $columns;
    }

    protected function _regist(&$param, $isFirstRegist)
    {
        if ($param['inventory_quantity'] == "") {
            Logic_Stock::deleteRealStock(
                    $param['item_id']
                    , $param['seiban']
                    , $param['location_id']
                    , $param['lot_id']
                    , $param['inventory_date']
            );
        } else {
            Logic_Stock::entryRealStock(
                    $param['item_id']
                    , $param['seiban']
                    , $param['location_id']
                    , $param['lot_id']
                    , $param['inventory_quantity']
                    , $param['inventory_date']
                    , $param['remarks']
            );
        }
        
        // id(keyColumnの値)を戻す。keyColumnがないModelではfalseを戻す。
        return false;
    }

}
