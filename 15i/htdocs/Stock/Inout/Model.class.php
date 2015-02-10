<?php

class Stock_Inout_Model extends Base_ModelBase
{

    protected function _getKeyColumn()
    {
        return 'item_in_out_id';
    }

    protected function _setDefault(&$param, $entryMode)
    {
        switch ($entryMode) {
            case "csv":
                self::_codeToId($param, "item_code", "item_id", "", "", "item_master");
                self::_codeToId($param, "location_code", "location_id", "", "", "location_master");
                if ($param['location_code'] == "")
                    $param['location_id'] = "0";
                if ($param['classification'] == 'payout') {
                    self::_codeToId($param, "partner_no", "partner_id", "customer_no", "customer_id", "customer_master");
                }
                break;
        }
    }

    protected function _getColumns()
    {
        $columns = array(
            array(
                "column" => "item_in_out_id",
                "pattern" => "id",
            ),
            array(
                "column" => "item_in_out_date",
                "validate" => array(
                    array(
                        "cat" => "systemDateOrLater",
                        "msg" => _g('日付')
                    ),
                ),
            ),
            array(
                "column" => "item_id",
                "pattern" => "item_id",
            ),
            array(
                "column" => "location_id",
                "pattern" => "location_id_required",
            ),
            array(
                "column" => "item_in_out_quantity",
                "convert" => array(
                    array(
                        "cat" => "strToNum",
                    ),
                ),
                "validate" => array(
                    array(
                        "cat" => "numeric",
                        "msg" => _g('数量が正しくありません。'),
                    ),
                ),
            ),
            array(
                "column" => "classification",
                "validate" => array(
                    array(
                        "cat" => "selectString",
                        "msg" => _g('classが正しくありません。'),
                        "param" => array(array('in', 'out', 'payout', 'use'))
                    ),
                ),
            ),
            array(
                "column" => "remarks",
                "pattern" => "nullToBlank",
            ),
            array(
                "column" => "seiban",
                "convert" => array(
                    array(
                        "cat" => "nullBlankToValue",
                        "param" => ''
                    ),
                ),
                "validate" => array(
                    // MRP品目でも製番を指定できることに注意
                    array(
                        "skipHasError" => true,
                        "skipValidatePHP" => "$1==='' || [[item_id]]===''",
                        "cat" => "existRecord",
                        "msg" => _g('製番が正しくありません。指定できるのは製番品目に対する確定受注の受注製番だけです。'),
                        "param" => "select seiban from
                            received_header
                            inner join received_detail on received_header.received_header_id = received_detail.received_header_id
                            inner join item_master on received_detail.item_id = item_master.item_id
                            where seiban = $1 and order_class = 0 and guarantee_grade = 0",
                    ),
                ),
            ),
            array(
                "column" => "stock_amount",
                "skipValidatePHP" => "$1==''",  // === ではだめ。出庫以外の画面では空文字ではなくnullであるため
                "skipValidateJS" => "$1==''",
                "validate" => array(
                    array(
                        "cat" => "numeric",
                        "msg" => _g('出庫金額が正しくありません。'),
                    ),
                    array(
                        "cat" => "eval",
                        "msg" => _g('出庫金額は製番が指定されているときのみ登録可能です。'),
                        "evalPHP" => "\$res=([[seiban]]!='');",
                        "evalJS" => "res=([[seiban]]!='');"
                    ),
                ),
            ),
            array(
                "column" => "parent_item_id",
                "pattern" => "item_id",
                "label" => _g("親品目"),
            ),
            array(
                "column" => "partner_id",
                "convert" => array(
                    array(
                        "cat" => "strToNum",
                    ),
                    array(
                        "cat" => "blankToNull",
                    ),
                ),
                "skipValidatePHP" => "$1===null && [[classification]]!='payout'",
                "skipValidateJS" => "$1===null", // classificationは画面上にはない
                "validate" => array(
                    array(
                        "cat" => "numeric",
                        "msg" => _g('支給先が正しくありません。'),
                    ),
                    array(
                        "cat" => "existRecord",
                        "msg" => _g('支給先がマスタに登録されていません。'),
                        "skipHasError" => true,
                        "param" => "select customer_id from customer_master where customer_id = $1"
                    ),
                ),
            ),
            array(
                "column" => "item_price",
                "convert" => array(
                    array(
                        "cat" => "strToNum",
                    ),
                    array(
                        "cat" => "nullBlankToValue",
                        "param" => 0
                    ),
                ),
                "skipValidatePHP" => "$1==='0'",
                "skipValidateJS" => "$1==='0'",
                "validate" => array(
                    array(
                        "cat" => "numeric",
                        "msg" => _g('支給単価が正しくありません。'),
                    ),
                ),
            ),
            array(
                "column" => "delivery_id",
                "convert" => array(
                    array(
                        "cat" => "blankToNull",
                    ),
                ),
            ),
            array(
                "column" => "without_stock",
                "convert" => array(
                    array(
                        "cat" => "nullBlankToValue",
                        "param" => 0
                    ),
                ),
                "validate" => array(
                    array(
                        "cat" => "selectString",
                        "msg" => _g('「在庫から引き落とさない」が不正です。'),
                        "param" => array(array('0', '1'))
                    ),
                ),
            ),
        );

        return $columns;
    }

    protected function _regist(&$param, $isFirstRegist)
    {
        global $gen_db;

        if (isset($param['item_in_out_id'])) {
            $key = array("item_in_out_id" => $param['item_in_out_id']);
        } else {
            $key = null;
        }
        $data = array(
            'item_in_out_date' => $param['item_in_out_date'],
            'item_id' => $param['item_id'],
            'location_id' => $param['location_id'],
            'item_in_out_quantity' => $param['item_in_out_quantity'],
            'item_price' => $param['item_price'],
            'classification' => $param['classification'],
            'remarks' => $param['remarks'],
            'parent_item_id' => @$param['parent_item_id'],
            'partner_id' => @$param['partner_id'],
            'delivery_id' => @$param['delivery_id'],
            'without_stock' => @$param['without_stock']
        );
        if ($param['classification'] == "out" && $param['seiban'] != "") {
            $data['seiban'] = $param['seiban'];
            if ($param['stock_amount'] == "" ) {
                $param['stock_amount'] = Logic_Stock::getStockPrice($param['item_in_out_date'], $param['item_id']) * $param['item_in_out_quantity'];
            }
            $data['stock_amount'] = $param['stock_amount'];
        } else {
            $data['seiban'] = '';
        }
        $gen_db->updateOrInsert('item_in_out', $key, $data);

        if (isset($param['item_in_out_id'])) {
            $itemInOutId = $param['item_in_out_id'];
        } else {
            $itemInOutId = $gen_db->getSequence("item_in_out_item_in_out_id_seq");
        }

        // 支給の場合は、サプライヤーロケへの入庫処理を行う（ロケがあれば）
        if ($param['classification'] == "payout") {
            $orderDetailId = $gen_db->queryOneValue("select order_detail_id from item_in_out where item_in_out_id = '{$itemInOutId}'");

            Logic_Inout::deletePayoutInoutById($itemInOutId);

            $query = "select location_id from location_master where customer_id = '{$param['partner_id']}'";
            $distLocationId = $gen_db->queryOneValue($query);

            if (is_numeric($distLocationId) && $distLocationId != "0") {
                $ItemInOutId_payout = Logic_Inout::entryInout(
                    $param['item_in_out_date']
                    , $param['item_id']
                    , $param['seiban']
                    , $distLocationId
                    , '' // lotNo
                    , $param['item_in_out_quantity']
                    , $param['item_price']
                    , "in"
                    , "order_detail_id"
                    , $orderDetailId
                );

                $data = array("payout_item_in_out_id" => $itemInOutId);
                $where = "item_in_out_id = {$ItemInOutId_payout}";
                $gen_db->update("item_in_out", $data, $where);
            }
        }
        
        // id(keyColumnの値)を戻す。keyColumnがないModelではfalseを戻す。
        return $itemInOutId;
    }

}
