<?php

class Manufacturing_CustomerEdi_Model extends Base_ModelBase
{

    private $_headerId;
    public $_deliveryHeaderId;

    protected function _getKeyColumn()
    {
        return 'received_header_id';
    }

    protected function _setDefault(&$param, $entryMode)
    {
        if (!isset($param['gen_line_no']) || !is_numeric($param['gen_line_no'])) {
            $param['gen_line_no'] = 1;     // CSVはどの行も発注１行めとして登録する
        }

        // 共通処理
        // 発送先が指定されていない場合、得意先と同じにする
        if (!isset($param['delivery_customer_id']) || !is_numeric($param['delivery_customer_id'])) {
            $param['delivery_customer_id'] = @$param['customer_id'];
        }
    }

    protected function _getColumns()
    {
        global $gen_db;

        $columns = array(
            // ***** received_header *****
            array(
                "column" => "received_header_id",
                "pattern" => "id",
            ),
            array(
                "column" => "received_date",
                "validate" => array(
                    array(
                        "cat" => "salesLockDateOrLater",
                        "msg" => _g('発注日')
                    ),
                ),
            ),
            // ***** received_detail *****
            array(
                "column" => "received_detail_id",
                "pattern" => "id",
            ),
            array(
                "column" => "item_id",
                "pattern" => "item_id", // null ok
            ),
            array(
                "column" => "received_quantity",
                "convert" => array(
                    array(
                        "cat" => "strToNum",
                    ),
                ),
                "skipValidatePHP" => "$1===''&&[[item_id]]===''", // 品目が選択されていない行はブランクOK
                "skipValidateJS" => "$1===''&&[[item_id]]===''",
                "validate" => array(
                    array(
                        "cat" => "minNum",
                        "msg" => _g('数量が正しくありません。'),
                        "param" => 0,
                    ),
                ),
            ),
            array(
                "column" => "dead_line",
                "skipValidatePHP" => "$1===''&&[[item_id]]===''", // 品目が選択されていない行はブランクOK
                "skipValidateJS" => "$1===''&&[[item_id]]===''",
                "validate" => array(
                    array(
                        "cat" => "salesLockDateOrLater",
                        "msg" => _g('希望納期')
                    ),
                    array(
                        "cat" => "eval",
                        "msg" => _g("希望納期には発注日より後の日付を指定してください。"),
                        "skipHasError" => true,
                        // ここではダブルブラケット参照が使えないことに注意。
                        // EditList内の項目のバリデーションでヘッダ項目を参照する際（あるいはその逆）は、
                        // ダブルブラケット参照やマルチカラムの$参照が使えない。
                        // （FWでnameにsuffixをつけるかどうかの判断ができず、不正なJSが生成されてJSエラーになる）
                        "skipValidateJS" => "$1===''||$('#received_date').val()===''",
                        "evalPHP" => "\$res=(strtotime(date($1))>=strtotime(date([[received_date]])));",
                        // ここではダブルブラケット参照が使えないことに注意。上のコメント参照
                        "evalJS" => "res=(gen.date.parseDateStr($1)>=gen.date.parseDateStr($('#received_date').val()));",
                    ),
                ),
            ),
            array(
                "column" => "dead_line",
                "skipValidatePHP" => "($1===''&&[[item_id]]==='')",
                "skipValidateJS" => "($1===''&&[[item_id]]==='')",
                "validate" => array(
                    array(
                        "cat" => "notExistRecord",
                        "msg" => _g("希望納期より後の日付の請求書が発行されているため登録を行えません。希望納期を変更してください。"),
                        "skipHasError" => true,
                        "param" => "
                            select
                                max(close_date)
                            from
                                bill_header
                            where
                                customer_id = (select coalesce(bill_customer_id, customer_id) from customer_master where customer_id = {$_SESSION["user_customer_id"]})
                            having
                                max(close_date) >= $1
                        "
                    ),
                ),
            ),
        );

        return $columns;
    }

    protected function _regist(&$param, $isFirstRegist)
    {
        global $gen_db;

        // ヘッダデータ登録
        if ($isFirstRegist) {
            $this->_headerId =
                Logic_Received::entryReceivedHeader(
                    @$param['received_header_id']
                    , $param['received_number']
                    , @$param['customer_received_number']
                    , $_SESSION["user_customer_id"]
                    , $_SESSION["user_customer_id"]
                    , $param['received_date']
                    , null
                    , null
                    , 0
                    , null
                    , ''
                    , ''
                    , ''
            );
        }

        // 得意先販売価格
        $query = "select selling_price from customer_price_master where item_id = '{$param['item_id']}' and customer_id = '{$_SESSION["user_customer_id"]}'";
        $sellingPrice = $gen_db->queryOneValue($query);

        // 標準原価
        $query = "select currency_id from customer_master where customer_id = '{$_SESSION["user_customer_id"]}'";
        $currencyId = $gen_db->queryOneValue($query);
        $baseCost = floatval(Logic_BaseCost::calcStandardBaseCost($param['item_id'], 1));
        if ($currencyId !== null) {
            $query = "
                select
                    coalesce(rate,1) as rate
                from
                    rate_master
                    inner join
                        (select currency_id, max(rate_date) as rate_date from rate_master
                            where rate_date <= '" . date('Y-m-d') . "'
                            and currency_id = '{$currencyId}'
                            group by currency_id
                        ) as t_rate_date
                        on rate_master.currency_id = t_rate_date.currency_id
                        and rate_master.rate_date = t_rate_date.rate_date
            ";
            $rate = $gen_db->queryOneValue($query);
            if ($rate == null)
                $rate = 1;

            // 標準原価を外貨に換算
            $baseCost = Logic_Customer::round(Gen_Math::div($baseCost, $rate), $_SESSION["user_customer_id"]);
        }

        // 明細データ登録
        Logic_Received::entryReceivedDetail(
                $this->_headerId
                , @$param['received_detail_id']
                , $param['gen_line_no']
                , $param['item_id']
                , $param['received_quantity']
                , $sellingPrice
                , $baseCost
                , 0
                , $param['dead_line']
                , @$param['remarks']
                , ""
        );

        // 発注書印刷済みフラグをオフにする
        Logic_Received::setCustomerReceivedPrintedFlag(array($this->_headerId), false);
        
        // id(keyColumnの値)を戻す。keyColumnがないModelではfalseを戻す。
        return $this->_headerId;
    }

    // EditListがある場合に必要
    protected function _detailDelete($detailId)
    {
        // EditList上で削除されたレコードの処理。
        // 本来はロック年月・納品書発行状況・請求書発行状況をチェックすべき。
        // とりあえずUIで制限されているのでよしとする
        Logic_Received::deleteReceivedDetail($detailId);
    }

    // EditListがある場合に必要
    protected function _lineDelete($lineNo)
    {
        global $gen_db;

        // EditList上で削除された行の処理。
        // 本来はロック年月・納品書発行状況・請求書発行状況をチェックすべき。
        // とりあえずUIで制限されているのでよしとする
        $query = "select received_detail_id from received_detail where received_header_id = '{$this->_headerId}' and line_no >= {$lineNo}";
        $res = $gen_db->getArray($query);
        if (is_array($res)) {
            foreach ($res as $row) {
                Logic_Received::deleteReceivedDetail($row['received_detail_id']);
            }
        }
    }

}
