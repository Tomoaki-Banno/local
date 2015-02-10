<?php

class Partner_Subcontract_Model extends Base_ModelBase
{

    var $csvUpdateMode;
    var $headerId;

    protected function _getKeyColumn()
    {
        return 'order_header_id';
    }

    protected function _setDefault(&$param, $entryMode)
    {
        global $gen_db;

        // order_noの取得　（修正・上書きモードでは既存値の取得が必要）
        if (isset($param['order_header_id']) && $param['order_no'] === null) {
            $query = "select order_no from order_detail where order_header_id = '{$param['order_header_id']}'";
            $param['order_no'] = $gen_db->queryOneValue($query);
        }

        // 上書きモードの処理　（csv & excel）
        if ($this->csvUpdateMode && !isset($param['order_header_id']) && $param['order_no'] != "") {
            //  オーダー番号（order_no）をキーに order_header_id を取得する
            $query = "select order_header_id from order_detail where order_no = '{$param['order_no']}'";
            $param['order_header_id'] = $gen_db->queryOneValue($query);
            if ($param['order_header_id'] === null || !isset($param['order_header_id']) || !is_numeric($param['order_header_id']))
                unset($param['order_header_id']);
        }

        switch ($entryMode) {
            case "csv":
                // code -> id
                self::_codeToId($param, "partner_no", "partner_id", "customer_no", "customer_id", "customer_master");
                self::_codeToId($param, "worker_code", "worker_id", "", "", "worker_master");
                self::_codeToId($param, "section_code", "section_id", "", "", "section_master");
                self::_codeToId($param, "item_code", "item_id", "", "", "item_master");
                if (!isset($param['payout_location_code']) || $param['payout_location_code'] == "") {
                    $param['payout_location_id'] = "-1";    // 子品目標準ロケ
                } else if ($param['payout_location_code'] == "0") {
                    $param['payout_location_id'] = "0";    // 規定ロケ
                } else {
                    self::_codeToId($param, "payout_location_code", "payout_location_id", "location_code", "location_id", "location_master");
                }
                break;
        }
    }

    protected function _getColumns()
    {
        global $gen_db;

        // データロック対象外
        $query = "select unlock_object_4 from company_master";
        $unlock = $gen_db->queryOneValue($query);

        $columns = array(
            // ****** order_header ********
            array(
                "column" => "order_header_id",
                "skipValidatePHP" => "$1==''",
                "skipValidateJS" => "$1==''||$1==undefined", // 画面上には存在しない項目
                "validate" => array(
                    array(
                        "cat" => "blankOrNumeric",
                        "msg" => _g('注文IDが正しくありません。'),
                    ),
                    array(
                        // すでに受入登録がある場合、更新を行えない
                        // （更新を許すと品目変更や子品目使用予約等でいろいろ問題が起こる）
                        "cat" => "notExistRecord",
                        "msg" => _g('この外製指示に対し、すでに受入が登録されているため内容を変更できません。'),
                        "skipHasError" => true,
                        "param" => "
                            select
                                achievement_id
                            from
                                achievement
                            where
                                order_detail_id in (select order_detail_id from order_detail where order_header_id = $1)
                        "
                    ),
                ),
            ),
            array(
                "column" => "order_date",
                "validate" => array(
                    array(
                        "cat" => "buyLockDateOrLater",
                        "msg" => _g('発行日'),
                        "skipValidatePHP" => "{$unlock}=='1'",
                        "skipValidateJS" => "{$unlock}=='1'",
                    ),
                    array(
                        "cat" => "dateString",
                        "msg" => _g('発行日が正しくありません。'),
                    ),
                ),
            ),
            array(
                "column" => "accepted_regist",
                "pattern" => "bool",
            ),

            // ****** order_detail ********
            array(
                "column" => "order_no",
                "skipValidatePHP" => "$1==''",
                "skipValidateJS" => "$1==''",
                // オーダー番号のように「ユーザー指定できるが全体としてユニークでなければならない」値は、
                // validateでの重複チェックだけでなく、このlockNumberの指定が必要。
                // くわしくは ModelBase の lockNumber処理の箇所のコメントを参照。
                "lockNumber" => true,
                "validate" => array(
                    // 新規登録時の重複チェック
                    array(
                        "cat" => "notExistRecord",
                        "msg" => _g('オーダー番号はすでに使用されています。別の番号を指定するか空欄にしてください。'),
                        "skipHasError" => true,
                        "skipValidatePHP" => "[[order_header_id]]!=''", // 修正はスキップ
                        "param" => "select order_no from order_detail where order_no = $1"
                    ),
                    // 修正時の重複チェック
                    array(
                        "cat" => "notExistRecord",
                        "msg" => _g('オーダー番号はすでに使用されています。別の番号を指定するか空欄にしてください。'),
                        "skipHasError" => true,
                        "skipValidatePHP" => "[[order_header_id]]==''", // 新規登録はスキップ
                        // 更新のときは、自分自身の番号はチェック対象としない（自分自身と重複するのは当然）
                        "param" => "select order_no from order_detail
                            where order_detail_id not in
                            (select order_detail_id from order_detail where order_header_id = [[order_header_id]])
                            and order_no = $1"
                    ),
                ),
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
                    // 製番の手動付与や振り替えが可能になったため、下記の2つのチェックが必要になった。
                    // 詳細は Partner_Order_Edit の カラムリストの製番の箇所のコメントを参照。
                    array(
                        "skipHasError" => true,
                        "skipValidatePHP" => "$1==='' || [[item_id]]===''",
                        "cat" => "existRecord",
                        "msg" => _g('MRP/ロット品目に対して製番を指定することはできません。'),
                        "param" => "select item_id from item_master where item_id=[[item_id]] and order_class=0",
                    ),
                    array(
                        "skipHasError" => true,
                        "skipValidatePHP" => "$1==='' || [[item_id]]===''",
                        "cat" => "existRecord",
                        "msg" => _g('製番が正しくありません。指定できる製番は、製番品目に対する確定受注のものだけです。'),
                        "param" => "select seiban from
                            received_header
                            inner join received_detail on received_header.received_header_id = received_detail.received_header_id
                            inner join item_master on received_detail.item_id = item_master.item_id
                            where seiban = $1 and order_class = 0 and guarantee_grade = 0",
                    ),
                ),
            ),
            array(
                "column" => "item_id",
                "pattern" => "item_id_required",
            ),
            // ダミー品目のオーダーは登録できない。
            // 理由は Manufacturing_Order_Model の item_id の validate部分のコメントを参照
            array(
                "column" => "item_id",
                "validate" => array(
                    array(
                        "skipHasError" => true,
                        "cat" => "notExistRecord",
                        "msg" => _g('ダミー品目のオーダーを登録することはできません。'),
                        "param" => "select item_id from item_master where item_id=[[item_id]] and dummy_item",
                    ),
                ),
            ),
            array(
                "column" => "order_detail_quantity",
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
                "column" => "order_detail_dead_line",
                "validate" => array(
                    array(
                        "cat" => "buyLockDateOrLater",
                        "msg" => _g('外注納期'),
                        "skipValidatePHP" => "{$unlock}=='1'&&[[accepted_regist]]!='true'",
                        "skipValidateJS" => "{$unlock}=='1'&&[[accepted_regist]]!='true'",
                    ),
                    array(
                        "cat" => "dateString",
                        "msg" => _g('外注納期が正しくありません。'),
                    ),
                ),
            ),
            array(
                "column" => "remarks_header",
                "pattern" => "nullToBlank",
            ),
            // ここから先は製造指示書にない部分
            array(
                "column" => "partner_id",
                "pattern" => "customer_id_required",
                "label" => _g("発注先"),
                "addwhere" => "classification=1",
            ),
            array(
                "column" => "item_price",
                "convert" => array(
                    array(
                        "cat" => "strToNum",
                    ),
                ),
                "validate" => array(
                    array(
                        "cat" => "numeric",
                        "msg" => _g('発注単価が正しくありません。'),
                    ),
                ),
            ),
            array(
                "column" => "multiple_of_order_measure",
                "convert" => array(
                    array(
                        "cat" => "strToNum",
                    ),
                    array(
                        "cat" => "nullBlankToValue",
                        "param" => "1",
                    ),
                ),
                "validate" => array(
                    array(
                        "cat" => "numeric",
                        "msg" => _g('手配単位倍数が正しくありません。'),
                    ),
                ),
            ),
            array(
                "column" => "payout_location_id",
                "pattern" => "location_id_required",
                "label" => _g("支給元ロケーション"),
            ),
            array(
                "column" => "payout_lot_id",
                "pattern" => "lot_id",
                "label" => _g("支給元ロット"),
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
        );

        return $columns;
    }

    protected function _regist(&$param, $isFirstRegist)
    {
        global $gen_db;

        // 親テーブル登録
        $headerId = Logic_Order::entryOrderHeader(
            2           // classification
            , @$param['order_header_id']
            , null      // order_id_for_user
            , $param['order_date']
            , $param['partner_id']
            , $param['remarks_header']
            , @$param['worker_id']
            , @$param['section_id']
            , null      // delivery_partner_id
            , null
        );

        // 更新の場合、対応するorder_detail_id を読み出しておく
        $detailId = null;
        if (isset($param['order_header_id'])) {
            $query = "select order_detail_id from order_detail WHERE order_header_id = '{$param['order_header_id']}'";
            $detailId = $gen_db->queryOneValue($query);
        }

        // 子テーブル登録
        // 同時に子品目の使用予約・支給処理も行う
        $detailId = Logic_Order::entryOrderDetail(
            $detailId
            , $headerId
            , 1         // line_no
            , $param['order_no']
            , $param['seiban']
            , $param['item_id']
            , null      // item_code
            , null      // item_name
            , $param['item_price']
            , ''        // item_sub_code
            , $param['order_detail_quantity']
            , $param['order_detail_dead_line']
            , false     // alarm_flag
            , $param['partner_id']
            , @$param['payout_location_id']
            , @$param['payout_lot_id']
            , null      // planDate
            , null      // planQty
            , null      // handQty
            , @$param['order_measure']
            , @$param['multiple_of_order_measure']
            , null      // remarks
            , (isset($param['subcontract_order_process_no']) && $param['subcontract_order_process_no'] != '' ? true : false)    // processFlag
        );

        // 外製工程関連の登録
        // 　外製工程オーダーを新規登録することはできない（外製工程オーダーは常に製造指示書の一部として
        // 　発行する）仕様のため、10iではここで subcontract_process_remarks と subcontract_ship_to
        // 　しか登録していなかった。そのため、外製工程オーダーのコピーができなかった。
        // 　（コピーすると通常の外製オーダーになってしまう）
        // 　しかし12iでは下記5項目を登録することで、外製工程オーダーのコピーを行なえるようにした。
        // 　外製工程オーダーをコピー可能としたのは、外製工程オーダーを複数の発注先に振り分けたいという要望があるため。
        if (isset($param['subcontract_order_process_no']) && $param['subcontract_order_process_no'] != '') {    // このチェックは重要。subcontract_order_process_noが空欄になることを想定していない箇所が多いため
            $data = array(
                "subcontract_order_process_no" => $param['subcontract_order_process_no'],
                "subcontract_parent_order_no" => @$param['subcontract_parent_order_no'],
                "subcontract_process_name" => @$param['subcontract_process_name'],
                "subcontract_process_remarks_1" => @$param['subcontract_process_remarks_1'],
                "subcontract_process_remarks_2" => @$param['subcontract_process_remarks_2'],
                "subcontract_process_remarks_3" => @$param['subcontract_process_remarks_3'],
                "subcontract_ship_to" => @$param['subcontract_ship_to'],
            );
            $where = "order_detail_id = '$detailId'";
            $gen_db->update("order_detail", $data, $where);
        }

        // 指示書発行済みフラグをオフにする
        Logic_Order::setOrderPrintedFlag(array($headerId), false);

        // 同時に受入を登録
        if ($param['accepted_regist'] == "true") {
            // オーダー番号取得
            $query = "select order_no from order_detail where order_detail_id = '{$detailId}'";
            $orderNo = $gen_db->queryOneValue($query);

            // 検収日は、取引先マスタの「検収リードタイム」が設定されている場合、
            // 受入日に検収リードタイムを足した日付（休日も考慮します）が自動設定されます。
            $query = "select inspection_lead_time from customer_master where customer_id =
            	(select partner_id from order_header
                inner join order_detail on order_header.order_header_id = order_detail.order_header_id
            	where order_detail_id = '{$detailId}')";
            $lt = $gen_db->queryOneValue($query);

            if ($lt == '') {
                $insDate = '';
            } else {
                // func名は「getDeadLine」だが、ここでは検収日取得に使用している
                $insDate = date('Y-m-d', Gen_Date::getDeadLine(strtotime($param['order_detail_dead_line']), $lt));
            }

            // 受入と入出庫の登録、発注データの受入数等の調整、現在庫更新
            Logic_Accepted::entryAccepted(
                    null
                    , $detailId
                    , $orderNo
                    , $param['order_detail_dead_line']  // 受入日 = 外注納期とする。
                    , $insDate                          // 検収日
                    , $param['order_detail_quantity']   // 受入数 = 発注数とする。
                    , null                              // 単価 = 発注単価とする。（登録ロジックで自動取得）
                    , null                              // 税率（登録ロジックで仕入計上基準日の税率を自動取得）
                    , null                              // レート（登録ロジックで仕入計上基準日のレートで自動計算）
                    , $param['remarks_header']
                    , -1                                // location_id  入庫ロケは品目マスタ「標準ロケーション（受入）」とする。（登録ロジックで自動取得）
                    , ''                                // lot_no
                    , null                              // payment_date
                    , "true"                            // completed
                    , null                              // use_by
            );
        }

        // id(keyColumnの値)を戻す。keyColumnがないModelではfalseを戻す。
        return $headerId;
    }

}
