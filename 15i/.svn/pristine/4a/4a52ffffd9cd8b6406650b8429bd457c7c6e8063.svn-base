<?php

class Partner_Accepted_Model extends Base_ModelBase
{

    protected function _getKeyColumn()
    {
        return 'accepted_id';
    }

    protected function _setDefault(&$param, $entryMode)
    {
        // Entry/BulkEntry/BarcodeEntry の headerArray/detailArray にないパラメータを生成するときは
        // ここで行う。
        global $gen_db;

        // order_detail_id が設定されていない場合、order_no　をキーにして取得
        if (!isset($param['order_detail_id']) && isset($param['order_no']) && $param['order_no'] != "") {
            $param['order_detail_id'] = Logic_Order::getDetailIdByOrderNo($param['order_no'], "classification<>'0'");
        }

        // オーダー情報の読出し
        if (isset($param['order_detail_id']) && is_numeric($param['order_detail_id'])) {
            $orderInfo = Logic_Order::getOrderData($param['order_detail_id'], true);
            if (!isset($param['order_no'])) {
                $param['order_no'] = $orderInfo[0]['order_no'];
            }
        } else {
            $orderInfo = array(array());
        }

        if (Gen_String::isNumeric($param['order_detail_id'])) {
            $query = "select order_class from order_detail inner join item_master on order_detail.item_id = item_master.item_id where order_detail_id = '{$param['order_detail_id']}'";
            $param['order_class'] = $gen_db->queryOneValue($query);
        }
 
        switch ($entryMode) {
            case "easy":
                $param['remarks'] = $orderInfo[0]['remarks'];
                $param['accepted_quantity'] = $orderInfo[0]['order_detail_quantity'];
                $param['accepted_date'] = $orderInfo[0]['order_detail_dead_line'];
                $param['inspection_date'] = $orderInfo[0]['order_detail_dead_line'];
                $param['location_id'] = -1;
                break;

            case "bulk":
                $param['remarks'] = $orderInfo[0]['remarks'];
                $param['order_no'] = $orderInfo[0]['order_no'];

                if (isset($param['isZeroFinish']) && $param['isZeroFinish']) {
                    $param['accepted_quantity'] = "0";
                    $param['order_detail_completed'] = "true";
                }
                break;

            case "barcode":
                $param['remarks'] = $orderInfo[0]['remarks'];
                break;

            case "csv":
                // コード => ID
                if (@$param['location_code'] == "-1") {
                    $param['location_id'] = -1;
                } else {
                    self::_codeToId($param, "location_code", "location_id", "", "", "location_master");
                    if (@$param['location_id'] == "") {
                        $param['location_id'] = "0";
                    }
                }
                if ($param['order_detail_completed'] == "1") {
                    $param['order_detail_completed'] = "true";
                }

                break;
        }
    }

    protected function _getColumns()
    {
        $columns = array(
            array(
                "column" => "accepted_id",
                "pattern" => "id",
            ),
            array(
                "column" => "accepted_date",
                "validate" => array(
                    array(
                        "cat" => "buyLockDateOrLater",
                        "msg" => _g('受入日')
                    ),
                ),
            ),
            array(
                "column" => "inspection_date",
                "skipValidatePHP" => "$1==''",
                "skipValidateJS" => "$1==''",
                "validate" => array(
                    array(
                        "cat" => "buyLockDateOrLater",
                        "msg" => _g('検収日')
                    ),
                ),
            ),
            array(
                "column" => "payment_date",
                "skipValidatePHP" => "$1==''",
                "skipValidateJS" => "$1==''",
                "validate" => array(
                    array(
                        "cat" => "buyLockDateOrLater",
                        "msg" => _g('支払予定日')
                    ),
                ),
            ),
            array(
                "column" => "order_detail_id",
                "pattern" => "order_detail_id_required",
                "addwhere" => "classification=1"
            ),
            array(
                "column" => "order_detail_id",
                "skipValidatePHP" => "[[accepted_id]]!=''||[[accepted_quantity]]<0", // 修正モードではチェックしない。また赤伝登録を可能にするため、マイナス登録はチェックしない。
                "skipValidateJS" => "[[accepted_id]]!=''||[[accepted_quantity]]<0",
                "validate" => array(
                    array(
                        "cat" => "existRecord",
                        "msg" => _g('指定されたオーダー番号が正しくないか、そのオーダーの受入登録がすでに完了しています。'),
                        "skipHasError" => true,
                        "param" => "select order_detail_id from order_detail where order_detail_id = $1 and (order_detail_completed = false or order_detail_completed is null)",
                    ),
                ),
           ),
            array(
                "column" => "accepted_quantity",
                "convert" => array(
                    array(
                        "cat" => "strToNum",
                    ),
                ),
                "validate" => array(
                    array(
                        "cat" => "numeric",
                        "msg" => _g('受入数量が正しくありません。'),
                    ),
                ),
            ),
            array(
                "column" => "accepted_price",
                "pattern" => "numeric", // null ok
            ),
            array(
                "column" => "tax_rate",
                "convert" => array(
                    array(
                        "cat" => "strToNum",
                    ),
                    array(
                        "cat" => "nullBlankToValue",
                        "param" => '',
                    ),
                ),
                "skipValidatePHP" => "$1===''",
                "skipValidateJS" => "$1===''",
                "validate" => array(
                    array(
                        "cat" => "minNum",
                        "msg" => _g('税率には0以上の数値を入力してください。'),
                        "param" => 0,
                    ),
                ),
            ),
            array(
                "column" => "foreign_currency_rate",
                "pattern" => "numeric", // null ok
            ),
            array(
                "column" => "order_detail_completed",
                "pattern" => "bool",
            ),
            array(
                "column" => "location_id",
                "pattern" => "location_id",
                "label" => _g("入庫ロケーション"),
            ),
            array(
                "column" => "lot_no",
                "convert" => array(
                    array(
                        "cat" => "trimEx",
                    ),
                ),
                "skipValidatePHP" => "$1==''",
                "skipValidateJS" => "$1==''",
                // ロット品目に関しては、ロット番号の重複不可とする。
                // ロット管理機能では実績/受入とロット番号が1:1ということが前提となっているため。
                // ag.cgi?page=ProjectDocView&ppid=1574&pbid=224713
                "validate" => array(
                    // 新規登録時の重複チェック
                    array(
                        "cat" => "notExistRecord",
                        "msg" => _g('ロット番号はすでに使用されています。ロット管理品目の場合、ロット番号は重複できません。'),
                        "skipHasError" => true,
                        "skipValidatePHP" => "[[order_class]]!='2' || [[accepted_id]]!=''", // 修正はスキップ
                        "param" => "select lot_no from accepted where lot_no = $1 union select lot_no from achievement where lot_no = $1"
                    ),
                    // 修正時の重複チェック
                    array(
                        "cat" => "notExistRecord",
                        "msg" => _g('ロット番号はすでに使用されています。ロット管理品目の場合、ロット番号は重複できません。'),
                        "skipHasError" => true,
                        "skipValidatePHP" => "[[order_class]]!='2' || [[accepted_id]]==''", // 新規登録はスキップ
                        // 更新のときは、自分自身の番号をチェック対象としない（自分自身と重複するのは当然）
                        "param" => "select lot_no from accepted
                            where lot_no = $1 and accepted_id <> [[accepted_id]]
                            union select lot_no from achievement where lot_no = $1"
                    ),
                ),
            ),
            array(
                "column" => "use_by",
                "skipValidatePHP" => "$1==''",
                "skipValidateJS" =>  "$1==''",
            	"validate" => array(
                    array(
                        "cat" => "systemDateOrLater",
                        "msg" => _g('消費期限')
                    ),
                ),
            ),
            array(
                "column" => "remarks",
                "pattern" => "nullToBlank",
            ),
        );

        return $columns;
    }

    protected function _regist(&$param, $isFirstRegist)
    {
        global $gen_db;

        // トランザクション開始
        $gen_db->begin();

        // 更新の場合は、先に削除を行う
        // （入出庫等の調整があるので、単純にUpdateしてはダメ。いったん削除し、あらためて登録を行う）
        if (isset($param['accepted_id']) && is_numeric($param['accepted_id'])) {
            //    ロット品目の場合は在庫製番を登録ごとに新規取得するので、
            //    更新の場合に在庫製番が変わらないよう、ここで既存の製番を取得しておく。
            $query = "select stock_seiban from accepted where accepted_id = '{$param['accepted_id']}'";
            $stockSeiban = $gen_db->queryOneValue($query);

            Logic_Accepted::deleteAccepted($param['accepted_id']);
        }

        // 受入と入出庫の登録、発注データの受入数等の調整、現在庫更新
        Logic_Accepted::entryAccepted(
            @$param['accepted_id']
            , $param['order_detail_id']
            , @$param['order_no']
            , $param['accepted_date']
            , $param['inspection_date']
            , $param['accepted_quantity']
            , @$param['accepted_price']
            , @$param['tax_rate']
            , @$param['foreign_currency_rate']
            , $param['remarks']
            , $param['location_id']
            , @$param['lot_no']
            , @$param['payment_date']
            , $param['order_detail_completed']
            ,@$param['use_by']
            ,@$stockSeiban
        );

        if (isset($param['accepted_id'])) {
            $keyValue = $param['accepted_id'];
        } else {
            $keyValue = $gen_db->getSequence("accepted_accepted_id_seq");
        }

        // コミット
        $gen_db->commit();

        // id(keyColumnの値)を戻す。keyColumnがないModelではfalseを戻す。
        return $keyValue;
    }

}
