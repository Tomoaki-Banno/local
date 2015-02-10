<?php

class Partner_PartnerEdi_Model extends Base_ModelBase
{

    protected function _getKeyColumn()
    {
        return 'accepted_id';
    }

    protected function _setDefault(&$param, $entryMode)
    {
        // Entry/BulkEntry/BarcodeEntry の headerArray/detailArray にないパラメータを生成するときは
        // ここで行う。

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

        switch ($entryMode) {
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
                        "msg" => _g('出荷日')
                    ),
                ),
            ),
            array(
                "column" => "order_detail_id",
                "pattern" => "order_detail_id_required",
                "addwhere" => "classification in (1,2)"
            ),
            array(
                "column" => "order_detail_id",
                "validate" => array(
                    array(
                        "cat" => "existRecord",
                        "msg" => _g('指定されたオーダー番号が正しくないか、そのオーダーの出荷登録がすでに完了しています。'),
                        "skipValidatePHP" => "[[accepted_id]]!=''||[[accepted_quantity]]<0", // 修正モードではチェックしない。また赤伝登録を可能にするため、マイナス登録はチェックしない。
                        "skipValidateJS" => "[[accepted_id]]!=''||[[accepted_quantity]]<0",
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
                        "msg" => _g('出荷数が正しくありません。'),
                    ),
                ),
            ),
            array(
                "column" => "order_detail_completed",
                "pattern" => "bool",
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
            Logic_Accepted::deleteAccepted($param['accepted_id']);
        }

        // 受入と入出庫の登録、発注データの受入数等の調整、現在庫更新
        Logic_Accepted::entryAccepted(
                null
                , $param['order_detail_id']
                , @$param['order_no']
                , $param['accepted_date']
                , ''
                , $param['accepted_quantity']
                , null
                , null      // 税率（登録ロジックで仕入計上基準日の税率を自動取得）
                , null      // レート（登録ロジックで仕入計上基準日のレートで自動計算）
                , ''
                , -1
                , @$param['lot_no']
                , ''
                , $param['order_detail_completed']
                , ""
                , ""
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
