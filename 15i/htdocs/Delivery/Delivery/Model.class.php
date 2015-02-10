<?php

class Delivery_Delivery_Model extends Base_ModelBase
{

    private $_headerId;
    private $_isBulkMode;
    private $_recHeaderIdArrayForBulk = array();
    private $_customerIdArrayForBulk = array();

    protected function _getKeyColumn()
    {
        return array('delivery_header_id', 'delivery_detail_id');
    }

    protected function _setDefault(&$param, $entryMode)
    {
        // Entry/BulkEntry/BarcodeEntry の headerArray/detailArray にないパラメータを生成するときは
        // ここで行う。

        global $gen_db;
        
        if (!isset($param['gen_line_no']) || !is_numeric($param['gen_line_no'])) {
            $param['gen_line_no'] = 1;     // CSV等はどの行も受注１行めとして登録する
        }
        
        // delivery_noの取得　（修正・上書きモードでは既存値の取得が必要。変更は不可）
        if (isset($param['delivery_header_id']) && is_numeric($param['delivery_header_id'])) {
            $query = "select delivery_header.delivery_no from delivery_header where delivery_header_id = '{$param['delivery_header_id']}'";
            $param['delivery_no'] = $gen_db->queryOneValue($query);
        }

        // 上書きモードの処理 （csv & excel）
        if (isset($this->csvUpdateMode) && $this->csvUpdateMode && !isset($param['delivery_header_id']) && isset($param['delivery_no']) && $param['delivery_no'] != "") {
            //  納品書番号（delivery_no）をキーに delivery_header_id を取得する
            $query = "select delivery_header_id from delivery_detail where delivery_header.delivery_no = '{$param['delivery_no']}'";
            $param['delivery_header_id'] = $gen_db->queryOneValue($query);
            if ($param['delivery_header_id'] === null || !isset($param['delivery_header_id']) || !is_numeric($param['delivery_header_id']))
                unset($param['delivery_header_id']);
        }

        switch ($entryMode) {
            case "bulk":
                if (isset($param['isZeroFinish']) && $param['isZeroFinish']) {
                    $param['delivery_quantity'] = 0;
                    $param['delivery_completed'] = "true";
                }
                if (!isset($param['delivery_completed'])) {
                    $param['delivery_completed'] = "false";
                }

                $param['remarks_header'] = "";
                $param['remarks_header_2'] = "";
                $param['remarks_header_3'] = "";
                $param['person_in_charge'] = "";

                // 受注明細備考を納品明細備考にコピー
                $param['remarks'] = "";
                if (isset($param['received_detail_id']) && is_numeric($param['received_detail_id'])) {
                    $param['remarks'] = $gen_db->queryOneValue("select remarks from received_detail where received_detail_id = '{$param['received_detail_id']}'");
                }

                $this->_isBulkMode = true;
                break;

            case "barcode":
                self::_codeToId($param, "seiban", "received_detail_id", "", "", "received_detail inner join received_header on received_header.received_header_id=received_detail.received_header_id");
                $param['remarks_header'] = "";
                $param['remarks_header_2'] = "";
                $param['remarks_header_3'] = "";
                $param['person_in_charge'] = "";

                // 受注明細備考を納品明細備考にコピー
                $param['remarks'] = "";
                if (isset($param['received_detail_id']) && is_numeric($param['received_detail_id'])) {
                    $param['remarks'] = $gen_db->queryOneValue("select remarks from received_detail where received_detail_id = '{$param['received_detail_id']}'");
                }

                $this->_isBulkMode = true;
                break;

            case "csv":
                // コード => ID
                self::_codeToId($param, "seiban", "received_detail_id", "", "", "received_detail inner join received_header on received_header.received_header_id=received_detail.received_header_id");
                
                if (@$param['location_code'] == "-1") {
                    $param['location_id'] = -1;
                } else {
                    self::_codeToId($param, "location_code", "location_id", "", "", "location_master");
                    if (@$param['location_id'] == "") {
                        $param['location_id'] = "0";
                    }
                }
                
                if (isset($param['delivery_completed']) && $param['delivery_completed'] == "1")
                    $param['delivery_completed'] = "true";
                break;
        }

        // 販売原単価が指定されていないときは、受注の販売原単価をそのまま用いる（CSV/Bulk/Barcode）
        if (!isset($param['sales_base_cost']) || !is_numeric($param['sales_base_cost'])) {
            if (Gen_String::isNumeric(@$param['received_detail_id'])) {
                $query = "select case when foreign_currency_id is null then sales_base_cost else foreign_currency_sales_base_cost end from received_detail where received_detail_id = '{$param['received_detail_id']}'";
                $param['sales_base_cost'] = $gen_db->queryOneValue($query);
            } else {
                $param['sales_base_cost'] = 0;
            }
        }
   }

    protected function _getColumns()
    {
        global $gen_db;

        $query = "select receivable_report_timing from company_master";
        $timing = $gen_db->queryOneValue($query);

        $columns = array(
            // ****** delivery_header ********
            array(
                "column" => "delivery_header_id",
                "pattern" => "id",
            ),
            array(
                "column" => "delivery_no",
                "skipValidatePHP" => "$1==''",
                "skipValidateJS" => "$1==''",
                // 納品書番号のように「ユーザー指定できるが全体としてユニークでなければならない」値は、
                // validateでの重複チェックだけでなく、このlockNumberの指定が必要。
                // くわしくは ModelBase の lockNumber処理の箇所のコメントを参照。
                "lockNumber" => true,
                "validate" => array(
                    // 新規登録時の重複チェック
                    array(
                        "cat" => "notExistRecord",
                        "msg" => _g('納品書番号はすでに使用されています。別の番号を指定するか空欄にしてください。'),
                        "skipHasError" => true,
                        "skipValidatePHP" => "[[delivery_header_id]]!=''", // 修正はスキップ
                        "param" => "select delivery_no from delivery_header where delivery_no = $1"
                    ),
                    // 修正時の重複チェック
                    array(
                        "cat" => "notExistRecord",
                        "msg" => _g('納品書番号はすでに使用されています。別の番号を指定するか空欄にしてください。'),
                        "skipHasError" => true,
                        "skipValidatePHP" => "[[delivery_header_id]]==''", // 新規登録はスキップ
                        // 更新のときは、自分自身の番号はチェック対象としない（自分自身と重複するのは当然）
                        "param" => "select delivery_no from delivery_header
                            where delivery_header_id <> [[delivery_header_id]]
                            and delivery_no = $1"
                    ),
                ),
            ),
            array(
                "column" => "delivery_date",
                "validate" => array(
                    array(
                        "cat" => "salesLockDateOrLater",
                        "msg" => _g('納品日')
                    ),
                ),
            ),
            array(
                "column" => "inspection_date",
                "skipValidatePHP" => "$1==''",
                "skipValidateJS" => "$1==''",
                "validate" => array(
                    array(
                        "cat" => "salesLockDateOrLater",
                        "msg" => _g('検収日')
                    ),
                ),
            ),
            array(
                "column" => "delivery_customer_id",
                "pattern" => "customer_id",
                "label" => _g("発送先"),
                "addwhere" => "classification in (0,2)",
            ),
            array(
                "column" => "foreign_currency_rate",
                "pattern" => "numeric", // null ok
            ),
            array(
                "column" => "remarks_header",
                "pattern" => "nullToBlank",
            ),
            array(
                "column" => "remarks_header_2",
                "pattern" => "nullToBlank",
            ),
            array(
                "column" => "remarks_header_3",
                "pattern" => "nullToBlank",
            ),

            // ****** delivery_detail ********
            array(
                "column" => "delivery_detail_id",
                "pattern" => "id",
            ),
            array(
                "column" => "received_detail_id",
                "skipValidatePHP" => "$1===''",
                "skipValidateJS" => "$1===''",
                "validate" => array(
                    array(
                        "cat" => "numeric",
                        "msg" => _g('受注番号が正しくありません。')
                    ),
                    array(
                        "cat" => "existRecord",
                        "msg" => _g('受注番号が正しくありません。もしくは受注確定度が「確定」ではありません。'),
                        "skipHasError" => true,
                        "param" => "select received_detail_id from received_detail inner join received_header on received_header.received_header_id = received_detail.received_header_id where received_detail_id = $1 and received_header.guarantee_grade=0"
                    ),
                    array(
                        "cat" => "existRecord",
                        "msg" => _g('指定された受注はすでに納品が完了しています。'),
                        "skipHasError" => true,
                        "skipValidatePHP" => "[[delivery_detail_id]]!='' || [[delivery_quantity]]<'0'", // 修正モード、または納品数がマイナスのときはチェックしない
                        "param" => "select received_detail_id from received_detail where received_detail_id = $1 and (delivery_completed=false or delivery_completed is null)",
                    ),
                    array(
                        // 15iでは、edit画面表示時（修正モードのみ）にもこのチェックを行うようになった
                        "cat" => "notExistRecord",
                        "msg" => sprintf(_g("この得意先に対し、%sより後の日付の請求書が発行されているため、登録を行えません。%sを変えるか請求書を削除してから登録してください。"), ($timing == '1' ? _g("検収日") : _g("納品日")), ($timing == '1' ? _g("検収日") : _g("納品日"))),
                        "skipValidatePHP" => ($timing == '1' ? "[[inspection_date]]==''" : "false"),
                        "skipHasError" => true,
                        "param" => "
                            select
                                max(close_date)
                            from
                                bill_header
                            where
                                customer_id =
                                (select
                                    coalesce(customer_master.bill_customer_id, received_header.customer_id)
                                from
                                    received_detail
                                    inner join received_header on received_header.received_header_id=received_detail.received_header_id
                                    inner join customer_master on received_header.customer_id = customer_master.customer_id
                                where
                                    received_detail_id = $1
                                    and bill_pattern <> 2)
                            having
                                max(close_date) >= [[" . ($timing == '1' ? 'inspection' : 'delivery') . "_date]]
                        "
                    ),
                ),
            ),
            array(
                "column" => "location_id",
                "convert" => array(
                    array(
                        "cat" => "nullBlankToValue",
                        "param" => "0"
                    ),
                ),
            ),
            array(
                "column" => "location_id",
                "pattern" => "location_id",
                "label" => _g("出庫ロケーション"),
            ),
            array(
                "column" => "lot_id",
                "pattern" => "lot_id",
                "label" => _g("出庫ロット"),
            ),
            array(
                "column" => "delivery_quantity",
                "pattern" => "numeric", // null ok
                "label" => _g("納品数量"),
            ),
            array(
                "column" => "delivery_price",
                "pattern" => "numeric", // null ok
                "label" => _g("納品単価"),
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
                        "msg" => _g("税率には0以上の数値を入力してください。"),
                        "param" => 0,
                    ),
                ),
            ),
            array(
                "column" => "sales_base_cost",
                "convert" => array(
                    array(
                        "cat" => "nullBlankToValue",
                        "param" => 0
                    ),
                    array(
                        "cat" => "strToNum",
                    ),
                ),
                "skipValidatePHP" => "$1===''&&[[item_id]]===''", // 品目が選択されていない行はブランクOK
                "skipValidateJS" => "$1===''&&[[item_id]]===''",
                "validate" => array(
                    array(
                        "cat" => "numeric",
                        "msg" => _g('販売原単価が正しくありません。'),
                    ),
                ),
            ),
            array(
                "column" => "delivery_completed",
                "pattern" => "bool",
            ),
            array(
                "column" => "person_in_charge",
                "pattern" => "nullToBlank",
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

        // 納品書まとめ（一括・バーコード納品用）
        if ($this->_isBulkMode) {
            switch (@$param['delivery_note_group']) {
                case 1:     // 受注ごと
                    $recHeaderId = $gen_db->queryOneValue("select received_header_id from received_detail where received_detail_id = '{$param['received_detail_id']}'");
                    if (isset($this->_recHeaderIdArrayForBulk[$recHeaderId])) {
                        $isFirstRegist = false;
                        $this->_headerId = $this->_recHeaderIdArrayForBulk[$recHeaderId][0];
                    } else {
                        $isFirstRegist = true;
                    }
                    // 納品書の行番号は受注と同じにする。（行番号に欠番が生じる可能性はあるが、ダブることはない）
                    $param['gen_line_no'] = $gen_db->queryOneValue("select line_no from received_detail where received_detail_id = '{$param['received_detail_id']}'");
                    break;
                case 2:     // 得意先ごと
                    $customerId = $gen_db->queryOneValue("select customer_id from received_header inner join received_detail on received_header.received_header_id = received_detail.received_header_id where received_detail_id = '{$param['received_detail_id']}'");
                    if (isset($this->_customerIdArrayForBulk[$customerId])) {
                        $isFirstRegist = false;
                        $this->_headerId = $this->_customerIdArrayForBulk[$customerId][0];
                        $param['gen_line_no'] = $this->_customerIdArrayForBulk[$customerId][1] + 1;
                    } else {
                        $isFirstRegist = true;
                        $param['gen_line_no'] = 1;
                    }
                    break;
                 case 3:     // 発送先ごと
                    $deliveryCustomerId = $gen_db->queryOneValue("select customer_id::text || '_' || delivery_customer_id::text from received_header inner join received_detail on received_header.received_header_id = received_detail.received_header_id where received_detail_id = '{$param['received_detail_id']}'");
                    if (isset($this->_deliveryCustomerIdArrayForBulk[$deliveryCustomerId])) {
                        $isFirstRegist = false;
                        $this->_headerId = $this->_deliveryCustomerIdArrayForBulk[$deliveryCustomerId][0];
                        $param['gen_line_no'] = $this->_deliveryCustomerIdArrayForBulk[$deliveryCustomerId][1] + 1;
                    } else {
                        $isFirstRegist = true;
                        $param['gen_line_no'] = 1;
                    }
                    break;
               default:    // 明細ごと（すべてバラバラの納品書に。2010iの仕様）
                    $isFirstRegist = true;
            }
        }
 
        // 更新時、単純なUpdateはできない（入出庫等の調整があるので）。
        // いったん既存データを削除してから登録を行う。
        // このように updateではなく delete & insert をおこなう場合、isFirstRegist の判断処理が必要になる。
        //
        // ***** 既存データの削除 *****
        //
        // 複数の明細行を連続登録する場合、削除処理は最初の1回だけおこなう。
        if ($isFirstRegist && isset($param['delivery_header_id']) && is_numeric($param['delivery_header_id'])) {
            Logic_Delivery::deleteDelivery($param['delivery_header_id']);
        }

        // ***** 登録 *****
        //
        // header
        // 複数の明細行を連続登録する場合、ヘッダ登録は最初の1回だけおこなう。
        // （ヘッダを削除すると明細行がすべて消えてしまうため）
        if ($isFirstRegist) {
            // 明細行連続登録の場合のため、header_idをプロパティとして保存しておく。
            // EditListがある場合は、このようにヘッダのidをクラス変数化してとっておく必要がある。
            $this->_headerId = Logic_Delivery::entryDeliveryHeader(
                @$param['delivery_header_id']
                , @$param['delivery_no']
                , $param['delivery_date']
                , $param['inspection_date']
                , $param['received_detail_id']
                , @$param['delivery_customer_id']
                , @$param['foreign_currency_rate']
                , $param['person_in_charge']
                , $param['remarks_header']
                , $param['remarks_header_2']
                , $param['remarks_header_3']
            );
        }

        // detail
        $deliveryDetailId = Logic_Delivery::entryDeliveryDetail(
                @$param['delivery_detail_id']
                , $this->_headerId
                , $param['gen_line_no']
                , $param['received_detail_id']
                , $param['delivery_quantity']
                , @$param['delivery_price']
                , @$param['tax_rate']
                , $param['sales_base_cost']
                , $param['remarks']
                , $param['location_id']
                , @$param['use_lot_no']
                , $param['delivery_completed']
        );

        // 納品書データの更新
        Logic_Delivery::updateDeliveryNote($this->_headerId);

        // 帳票発行済みフラグをオフにする
        if (isset($param['delivery_header_id']))
            Logic_Delivery::setDeliveryPrintedFlag(array($param['delivery_header_id']), false);

        // 納品書まとめの処理のためにheader_idと行番号を保存（一括登録用）
        if ($this->_isBulkMode) {
            switch (@$param['delivery_note_group']) {
                case 1:     // 受注ごと
                    $this->_recHeaderIdArrayForBulk[$recHeaderId] = array($this->_headerId, $param['gen_line_no']);
                    break;
                case 2:     // 得意先ごと
                    $this->_customerIdArrayForBulk[$customerId] = array($this->_headerId, $param['gen_line_no']);
                    break;
                case 3:     // 発送先ごと
                    $this->_deliveryCustomerIdArrayForBulk[$deliveryCustomerId]= array($this->_headerId, $param['gen_line_no']);
                    break;
               default:
            }
        }
        
        // id(keyColumnの値)を戻す。明細があるModelではarray(ヘッダid, 明細id) とする。keyColumnがないModelではfalseを戻す。
        return array($this->_headerId, $deliveryDetailId);
    }

    // EditListがある場合に必要
    protected function _detailDelete($detailId)
    {
        // EditList上で削除されたレコードの処理。
        // 本来はロック年月・請求書発行状況をチェックすべき。
        // とりあえずUIで制限されているのでよしとする
        Logic_Delivery::deleteDelivery($detailId);
    }

    // EditListがある場合に必要
    protected function _lineDelete($lineNo)
    {
        global $gen_db;

        // EditList上で削除された行の処理。
        // 本来はロック年月・請求書発行状況をチェックすべき。
        // とりあえずUIで制限されているのでよしとする
        $query = "select delivery_detail_id from delivery_detail where delivery_header_id = '{$this->_headerId}' and line_no >= {$lineNo}";
        $res = $gen_db->getArray($query);
        if (is_array($res)) {
            foreach ($res as $row) {
                Logic_Delivery::deleteDelivery($row['delivery_detail_id']);
            }
        }
    }

}
