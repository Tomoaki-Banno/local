<?php

class Manufacturing_Received_Model extends Base_ModelBase
{

    private $_headerId;
    public $_deliveryHeaderId;
    private $_deliveryLineNo = 1;

    protected function _getKeyColumn()
    {
        return array('received_header_id', 'received_detail_id');
    }

    protected function _setDefault(&$param, $entryMode)
    {
        global $gen_db;

        if (!isset($param['gen_line_no']) || !is_numeric($param['gen_line_no'])) {
            $param['gen_line_no'] = 1;     // CSVはどの行も受注１行めとして登録する（ただしheaderが設定されている場合は調整される）
        }

        switch ($entryMode) {
            case "csv":
                if (!isset($param['reserve_quantity']) || !is_numeric($param['reserve_quantity'])) {
                    $param['reserve_quantity'] = 0;
                }

                // header_id
                if (isset($param['received_header_id']) && is_numeric($param['received_header_id'])) {
                    $this->_headerId = $param['received_header_id'];
                } else {
                    $param['received_header_id'] = null;
                }

                // code -> id
                self::_codeToId($param, "customer_no", "customer_id", "", "", "customer_master");
                self::_codeToId($param, "delivery_customer_no", "delivery_customer_id", "customer_no", "customer_id", "customer_master");
                self::_codeToId($param, "item_code", "item_id", "", "", "item_master");
                self::_codeToId($param, "section_code", "section_id", "", "", "section_master");
                self::_codeToId($param, "worker_code", "worker_id", "", "", "worker_master");

                // 請求先id
                $billCustomerId = null;
                if (isset($param['customer_id']) && is_numeric($param['customer_id'])) {
                    $billCustomerId = $gen_db->queryOneValue("select coalesce(bill_customer_id, customer_id) as id from customer_master where customer_id = '{$param['customer_id']}'");
                }

                if (!isset($param['sales_base_cost']) || !is_numeric($param['sales_base_cost'])) {
                    // 販売原価空欄の時は標準原価を適用する
                    if (Gen_String::isNumeric(@$param['item_id'])) {
                        $param['sales_base_cost'] = floatval(Logic_BaseCost::calcStandardBaseCost(@$param['item_id'], 1));
                    } else {
                        $param['sales_base_cost'] = 0;
                    }
                    // 販売原価を外貨に換算（請求先の取引通貨で計算）
                    $rate = Logic_Customer::getCustomerRate($billCustomerId, @$param['received_date']);
                    if (Gen_String::isNumeric($rate))
                        $param['sales_base_cost'] = Logic_Customer::round(Gen_Math::div($param['sales_base_cost'], $rate), $billCustomerId);
                }

                if (isset($param['item_id']) && is_numeric($param['item_id'])) {
                    // 単価空欄の時は標準販売単価を適用する（EDIでは単価が省略されるケースも多い）
                    if (!isset($param['product_price']) || $param['product_price'] == "") {
                        // 標準販売単価は請求先ではなく得意先で取得する
                        $customerId = null;
                        if (is_numeric(@$param['customer_id']))
                            $customerId = $param['customer_id'];
                        $qty = null;
                        if (Gen_String::isNumeric(@$param['received_quantity']))
                            $qty = $param['received_quantity'];
                        $param['product_price'] = Logic_Received::getSellingPrice($param['item_id'], $customerId, $qty);
                    }
                } else {
                    // 品目名が空だったときにエラーを発生させるための措置
                    // （画面からの明細行登録のため、validとしては null ok にしてある）
                    $param['item_id'] = 'error';
                }

                break;
        }

        // 共通処理
        // 発送先が指定されていない場合、得意先と同じにする
        if (!isset($param['delivery_customer_id']) || !is_numeric($param['delivery_customer_id'])) {
            $param['delivery_customer_id'] = @$param['customer_id'];
        }
        
        // ダミー品目チェック
        $param['is_dummy'] = false;
        if (isset($param['item_id']) && is_numeric($param['item_id'])) {
            $param['is_dummy'] = $gen_db->existRecord("select item_id from item_master where item_id = '{$param['item_id']}' and dummy_item");
        }
    }

    protected function _getColumns()
    {
        global $gen_db;

        // データロック対象外
        $query = "select unlock_object_1 from company_master";
        $unlock = $gen_db->queryOneValue($query);

        $columns = array(
            // ***** received_header *****
            array(
                "column" => "received_header_id",
                "pattern" => "id",
            ),
            array(
                "column" => "received_number",
                "convert" => array(
                    array(
                        "cat" => "trimEx",
                    ),
                ),
                "skipValidatePHP" => "$1==''",
                "skipValidateJS" => "$1==''",
                // 受注番号のように「ユーザー指定できるが全体としてユニークでなければならない」値は、
                // validateでの重複チェックだけでなく、このlockNumberの指定が必要。
                // くわしくは ModelBase の lockNumber処理の箇所のコメントを参照。
                "lockNumber" => true,
                "validate" => array(
                    // 新規登録時の重複チェック
                    array(
                        "cat" => "notExistRecord",
                        "msg" => _g('受注番号はすでに使用されています。別の番号を指定するか空欄にしてください。'),
                        "skipHasError" => true,
                        "skipValidatePHP" => "[[received_header_id]]!=''", // 修正はスキップ
                        "param" => "select received_number from received_header where received_number = $1"
                    ),
                    // 修正時の重複チェック
                    array(
                        "cat" => "notExistRecord",
                        "msg" => _g('受注番号はすでに使用されています。別の番号を指定するか空欄にしてください。'),
                        "skipHasError" => true,
                        "skipValidatePHP" => "[[received_header_id]]==''", // 新規登録はスキップ
                        // 更新のときは、自分自身の番号をチェック対象としない（自分自身と重複するのは当然）
                        "param" => "select received_number from received_header
                            where received_number = $1 and received_header_id <> [[received_header_id]]"
                    ),
                ),
            ),
            array(
                "column" => "estimate_header_id",
                "pattern" => "estimate_header_id",
                "label" => _g("見積"),
                "addwhere" => "customer_id is not null",
            ),
            array(
                "column" => "customer_id",
                "pattern" => "customer_id_required",
                "label" => _g("得意先"),
                "addwhere" => "classification=0",
            ),
            array(
                "column" => "received_date",
                "validate" => array(
                    array(
                        "cat" => "salesLockDateOrLater",
                        "msg" => _g('受注日'),
                        "skipValidatePHP" => "{$unlock}=='1'",
                        "skipValidateJS" => "{$unlock}=='1'",
                    ),
                    array(
                        "cat" => "dateString",
                        "msg" => _g('受注日が正しくありません。'),
                    ),
                ),
            ),
            array(
                "column" => "received_date",
                "skipValidatePHP" => "$1===''||[[delivery_regist]]!='true'", // 「同時に納品を登録」の場合のみチェック
                "skipValidateJS" => "$1===''||[[delivery_regist]]!='true'",
                "validate" => array(
                    array(
                        "cat" => "notExistRecord",
                        "msg" => _g("この得意先に対し、受注日より後の日付の請求書が発行されているため登録を行えません。「同時に納品を登録する」チェックがオンのときは、受注日が得意先の最終請求書の締日より後でなければなりません。受注日を変えるか請求書を削除してください。"),
                        "skipHasError" => true,
                        "param" => "
                            select
                                max(close_date)
                            from
                                bill_header
                                left join (select customer_id as cid, bill_pattern from customer_master) as t_customer on bill_header.customer_id = t_customer.cid
                            where
                                customer_id = (select coalesce(bill_customer_id, customer_id) from customer_master where customer_id = [[customer_id]])
                                and t_customer.bill_pattern <> 2
                            having
                                max(close_date) >= $1
                        "
                    ),
                ),
            ),
            array(
                "column" => "worker_id",
                "pattern" => "worker_id",
                "label" => _g("担当者"),
            ),
            array(
                "column" => "section_id",
                "pattern" => "section_id",
            ),
            array(
                "column" => "guarantee_grade",
                "dependentColumn" => "delivery_regist",
                "validate" => array(
                    array(
                        "cat" => "selectString",
                        "msg" => _g('受注確定度が正しくありません。'),
                        "param" => array(array('0', '1'))
                    ),
                    array(
                        "cat" => "eval",
                        "msg" => _g('「同時に納品を登録する」チェックがオンのとき、受注確定度「予約」を選択することはできません。'),
                        "evalPHP" => "\$res=($1=='0' || [[delivery_regist]]!='true');",
                        "evalJS" => "res=($1=='0' || !$('#delivery_regist').is(':checked'));"
                    ),
                    // ダミー品目の場合、受注の段階で子品目の使用予定をたてているので、受注「予約」で所要量計算が「通常（非内示）モード」
                    // でも子オーダーが出てしまう。
                    // 一方、受注が「予約」なら子品目の使用予定をたてない、という手もあるが、そうすると所要量計算を内示モードで回したとき
                    // に子オーダーが出なくなってしまう。 
                    // そのため、ダミー品目が含まれる場合は「予約」にできないようにした。
                    // ag.cgi?page=ProjectDocView&pid=1574&did=196034
                    array(
                        "cat" => "eval",
                        "msg" => _g('受注品目にダミー品目が含まれているとき、受注確定度「予約」を選択することはできません。'),
                        "skipHasError" => true,
                        "evalPHP" => "\$res=($1=='0' || [[is_dummy]]==false);",
                        "evalJS" => "res=true;"
                    ),
                ),
            ),
            array(
                "column" => "delivery_customer_id",
                "pattern" => "customer_id", // null ok
                "label" => _g("発送先"),
                "addwhere" => "classification in (0,2)",
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
            array(
                "column" => "delivery_regist",
                "pattern" => "bool",
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
                "column" => "item_id",
                "validate" => array(
                    array(
                        "cat" => "existRecord",
                        "msg" => _g("指定された品目は受注対象ではありません。"),
                        "skipHasError" => true,
                        "param" => "select item_id from item_master where item_id = $1 and received_object = 0"
                    ),
                ),
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
                        "msg" => _g('受注数が正しくありません。'),
                        "param" => 0,
                    ),
                ),
            ),
            array(
                "column" => "product_price",
                "convert" => array(
                    array(
                        "cat" => "strToNum",
                    ),
                ),
                "skipValidatePHP" => "$1===''&&[[item_id]]===''", // 品目が選択されていない行はブランクOK
                "skipValidateJS" => "$1===''&&[[item_id]]===''",
                "validate" => array(
                    array(
                        "cat" => "numeric",
                        "skipHasError" => true,     // モバイル版で得意先エラーの際に単価もエラーと表示されてしまう現象に対処
                        "msg" => _g('受注単価が正しくありません。'),
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
                "column" => "dead_line",
                "skipValidatePHP" => "$1===''&&[[item_id]]===''", // 品目が選択されていない行はブランクOK
                "skipValidateJS" => "$1===''&&[[item_id]]===''",
                "validate" => array(
                    array(
                        "cat" => "eval",
                        "msg" => _g("受注納期には受注日より後の日付を指定してください。"),
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
                "validate" => array(
                    array(
                        "cat" => "salesLockDateOrLater",
                        "msg" => _g('受注納期'),
                        "skipValidatePHP" => "$1===''&&[[item_id]]===''||({$unlock}=='1'&&[[delivery_regist]]!='true')", // 品目が選択されていない行はブランクOK
                        "skipValidateJS" => "$1===''&&[[item_id]]===''||({$unlock}=='1'&&[[delivery_regist]]!='true')",
                    ),
                    array(
                        "cat" => "dateString",
                        "msg" => _g('受注納期が正しくありません。'),
                        "skipValidatePHP" => "$1===''&&[[item_id]]===''", // 品目が選択されていない行はブランクOK
                        "skipValidateJS" => "$1===''&&[[item_id]]===''",
                    ),
                ),
            ),
            array(
                "column" => "dead_line",
                "skipValidatePHP" => "($1===''&&[[item_id]]==='')||[[delivery_regist]]!='true'", // 同時に納品登録時にチェック
                "skipValidateJS" => "($1===''&&[[item_id]]==='')||[[delivery_regist]]!='true'",
                "validate" => array(
                    array(
                        // この制限は09iにはなかった。
                        // 厳密には「同時に納品を登録」チェックがオンのときだけ制限すればいいのだが、より慎重にした。
                        "cat" => "notExistRecord",
                        "msg" => _g("この得意先に対し、受注納期より後の日付の請求書が発行されているため登録を行えません。受注納期を変えるか請求書を削除してください。"),
                        "skipHasError" => true,
                        "param" => "
                            select
                                max(close_date)
                            from
                                bill_header
                                left join (select customer_id as cid, bill_pattern from customer_master) as t_customer on bill_header.customer_id = t_customer.cid
                            where
                                customer_id = (select coalesce(bill_customer_id, customer_id) from customer_master where customer_id = [[customer_id]])
                                and t_customer.bill_pattern <> 2
                            having
                                max(close_date) >= $1
                        "
                    ),
                ),
            ),
            array(
                "column" => "seiban",
                "validate" => array(
                    array(
                        "cat" => "blankOrNumeric",
                        "msg" => _g('製番が正しくありません。'),
                    ),
                ),
            ),
            array(
                "column" => "reserve_quantity",
                // 登録時（Logic_Received::entryReceivedDetail）に、非数値および製番品目は
                // 強制的に0に変換される。
                "convert" => array(
                    array(
                        "cat" => "strToNum",
                    ),
                    array(
                        "cat" => "nullBlankToValue",
                        "param" => 0,
                    ),
                ),
                "skipValidatePHP" => "($1===''&&[[item_id]]==='')||[[readonly_line_no]]=='true'", // 品目が選択されていない行、及びロックされている行はスキップ
                "skipValidateJS" => "($1===''&&[[item_id]]==='')||[[readonly_line_no]]=='true'",
                "dependentColumn" => "received_quantity",
                "validate" => array(
                    array(
                        "cat" => "minNum",
                        "msg" => _g('在庫引当数には0以上の数値を入力してください。'),
                        "param" => 0,
                    ),
                    array(
                        "cat" => "eval",
                        "msg" => _g("在庫引当数は引当可能数以下にしてください。"),
                        "skipHasError" => true,
                        "skipValidatePHP" => "[[item_id]]===null",
                        "evalPHP" => "\$rq=Logic_Reserve::calcReservableQuantity([[item_id]],[[received_detail_id]],'');\$res=($1<=\$rq);",
                        "evalJS" => "rq=$('#reservable_quantity_'+lineNo).html();res=gen.util.isNumeric(rq)?$1<=parseFloat(rq):true;",
                    ),
                    array(
                        "cat" => "eval",
                        "msg" => _g("在庫引当数は受注数以下にしてください。"),
                        "skipHasError" => true,
                        "skipValidateJS" => "!gen.util.isNumeric([[received_quantity]])",
                        "evalPHP" => "\$res=($1<=[[received_quantity]]);",
                        "evalJS" => "res=($1<=parseFloat([[received_quantity]]));",
                    ),
                    array(
                        "cat" => "eval",
                        "msg" => _g('この品目はすでに納品済です。在庫引当数を納品済数より少なくすることはできません。'),
                        "skipHasError" => true,
                        "evalPHP" => "\$res=([[received_detail_id]]=='' || Logic_Delivery::getFreeDeliveryQtyByReceivedDetailId([[received_detail_id]])<=$1);",
                        // クライアントバリデーションはなし
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
            $this->_headerId = Logic_Received::entryReceivedHeader(
                @$param['received_header_id']
                , $param['received_number']
                , @$param['customer_received_number']
                , $param['customer_id']
                , @$param['delivery_customer_id']
                , $param['received_date']
                , @$param['worker_id']
                , @$param['section_id']
                , @$param['guarantee_grade']
                , @$param['estimate_header_id']
                , @$param['remarks_header']
                , @$param['remarks_header_2']
                , @$param['remarks_header_3']
            );
        }

        // 明細データ登録
        $receivedDetailId = Logic_Received::entryReceivedDetail(
            $this->_headerId
            , @$param['received_detail_id']
            , $param['gen_line_no']
            , $param['item_id']
            , $param['received_quantity']
            , $param['product_price']
            , $param['sales_base_cost']
            , $param['reserve_quantity']
            , $param['dead_line']
            , @$param['remarks']
            , @$param['remarks_2']
        );
        
        // 出荷指示書印刷済みフラグをオフにする
        Logic_Received::setReceivedPrintedFlag(array($receivedDetailId), false);

        // 同時に納品を登録
        if ($param['delivery_regist'] == "true") {
            if ($this->_deliveryHeaderId === null) {    // $isFirstRegist で判断してはダメ。前のレコードが「同時納品」ではない場合にエラーとなる
                // 検収リードタイムの計算
                $query = "select inspection_lead_time from customer_master where customer_id = '{$param['customer_id']}'";
                $lt = $gen_db->queryOneValue($query);
                if ($lt == '') {
                    $inspectionDate = $param['received_date'];  // 検収LTが設定されていない場合は受注納期日を検収日とする
                } else {
                    // func名は「getDeadLine」だが、ここでは検収日取得に使用している
                    $inspectionDate = date('Y-m-d', Gen_Date::getDeadLine(strtotime($param['received_date']), $lt));
                }

                // 納品登録
                $this->_deliveryHeaderId = Logic_Delivery::entryDeliveryHeader(
                    null
                    , ""                        // 納品書番号
                    , $param['received_date']   // 納品日 = 受注日とする。受注納期の最も遅い日にすべきか？
                    , $inspectionDate           // 検収日
                    , $receivedDetailId         // 受注明細id
                    , null                      // 発送先id
                    , null                      // 取引通貨レート
                    , ""                        // 担当者。 worker_idから引用すべきか
                    , $param['remarks_header']
                    , $param['remarks_header_2']
                    , $param['remarks_header_3']
                );
            }
            
            // 出庫ロケは品目マスタ「標準ロケーション（完成）」とする
            $query = "select default_location_id_3 from item_master where item_id = '{$param['item_id']}'";
            $locationId = $gen_db->queryOneValue($query);
            if (!is_numeric($locationId))
                $locationId = 0;

            // 取引通貨処理されている可能性があるので、単価・原価はいま登録した明細テーブルから読み出す
            $query = "
            select
                coalesce(foreign_currency_product_price, product_price) as product_price
                ,coalesce(foreign_currency_sales_base_cost, sales_base_cost) as sales_base_cost
            from
                received_detail
            where
                received_detail_id = '{$receivedDetailId}'
            ";
            $obj = $gen_db->queryOneRowObject($query);

            Logic_Delivery::entryDeliveryDetail(
                null
                , $this->_deliveryHeaderId
                , $this->_deliveryLineNo
                , $receivedDetailId
                , $param['received_quantity']
                , $obj->product_price
                , null
                , $obj->sales_base_cost
                , $param['remarks']
                , $locationId
                , ''                        // use_lot_no
                , "true"
            );
            $this->_deliveryLineNo++;

            // 納品書データの更新
            Logic_Delivery::updateDeliveryNote($this->_deliveryHeaderId);
        }
        
        // id(keyColumnの値)を戻す。明細があるModelではarray(ヘッダid, 明細id) とする。keyColumnがないModelではfalseを戻す。
        return array($this->_headerId, $receivedDetailId);
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
