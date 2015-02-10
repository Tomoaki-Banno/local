<?php

class Manufacturing_Order_Model extends Base_ModelBase
{

    var $csvUpdateMode = false;
    var $headerId;

    protected function _getKeyColumn()
    {
        return array('order_header_id', 'order_detail_id');
    }

    protected function _setDefault(&$param, $entryMode)
    {
        global $gen_db;

        // order_noの取得　（修正・上書きモードでは既存値の取得が必要）
        if (isset($param['order_header_id']) && $param['order_no'] === null) {
            $query = "select order_no from order_detail where order_header_id = '{$param['order_header_id']}'";
            $param['order_no'] = $gen_db->queryOneValue($query);
        }

        // 上書きモードの処理 （csv & excel）
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
                self::_codeToId($param, "item_code", "item_id", "", "", "item_master");
                break;
        }
    }

    protected function _getColumns()
    {
        global $gen_db;

        // データロック対象外
        $query = "select unlock_object_2 from company_master";
        $unlock = $gen_db->queryOneValue($query);

        $columns = array(
            // ****** order_header ********
            array(
                "column" => "order_header_id",
                "pattern" => "id",
            ),
            array(
                "column" => "order_header_id",
                "skipValidatePHP" => "$1==''",
                "skipValidateJS" => "$1==''||$1==undefined", // 画面上には存在しない項目
                "validate" => array(
                    array(
                        "cat" => "blankOrNumeric",
                        "msg" => _g('IDが正しくありません。'),
                    ),
                    array(
                        "cat" => "notExistRecord",
                        "msg" => _g('この製造指示に対し、すでに実績が登録されているため内容を変更できません。'),
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
                        "cat" => "systemDateOrLater",
                        "msg" => _g('製造開始日'),
                        "skipValidatePHP" => "{$unlock}=='1'",
                        "skipValidateJS" => "{$unlock}=='1'",
                    ),
                    array(
                        "cat" => "dateString",
                        "msg" => _g('製造開始日が正しくありません。'),
                    ),
                ),
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
                "column" => "item_id",
                "pattern" => "item_id_required",
            ),
            // ダミー品目の内製・外製オーダーは登録できない。
            // 理由は、ダミー品目のオーダーが発行されると実績登録や外製払出での子品目の扱いに困るため。
            // そのダミー品目を販売する場合は、実績や払出で子品目を引き落としてはいけない（ダミー品目は納品時に子品目が引き落とされるため）が、
            // 販売しない場合は実績や払出で子品目を引き落としておかなければならない。その判断が難しい。
            // そのため、ダミー品目の内製・外製オーダー発行は一律禁止とした。
            // （子品目の問題なので、注文書は許可してある）
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
                "column" => "seiban",
                "convert" => array(
                    array(
                        "cat" => "nullBlankToValue",
                        "param" => ''
                    ),
                ),
                "validate" => array(
                    // 製番の手動付与や振り替えが可能になったため、下記の3つのチェックが必要になった。
                    // 詳細は Partner_Order_Edit の カラムリストの製番の箇所のコメントを参照。
                    array(
                        "skipHasError" => true,
                        "skipValidatePHP" => "$1==='' || [[item_id]]===''",
                        "cat" => "existRecord",
                        "msg" => _g('MRP品目に対して製番を指定することはできません。'),
                        "param" => "select item_id from item_master where item_id=[[item_id]] and order_class=0",
                    ),
                    array(// 新規登録用チェック
                        "skipHasError" => true,
                        "skipValidatePHP" => "$1==='' || [[item_id]]==='' || [[order_header_id]]!=''", // 更新時はスキップ
                        "cat" => "existRecord",
                        "msg" => _g('製番が正しくありません。指定できるのは製番品目に対する確定受注（未納品）の受注製番だけです。'),
                        "param" => "select seiban from
                            received_header
                            inner join received_detail on received_header.received_header_id = received_detail.received_header_id
                            inner join item_master on received_detail.item_id = item_master.item_id
                            where seiban = $1 and order_class = 0 and guarantee_grade = 0",
                    ),
                    array(// 更新用チェック（新規用との違いは、製番が未変更の場合、受注が納品済みであってもOKとするところ）
                        "skipHasError" => true,
                        "skipValidatePHP" => "$1==='' || [[item_id]]==='' || [[order_header_id]]==''", // 新規時はスキップ
                        "cat" => "existRecord",
                        "msg" => _g('製番が正しくありません。指定できるのは製番品目に対する確定受注（未納品）の受注製番だけです。'),
                        "param" => "select seiban from
                            received_header
                            inner join received_detail on received_header.received_header_id = received_detail.received_header_id
                            inner join item_master on received_detail.item_id = item_master.item_id
                            where seiban = $1 and order_class = 0 and guarantee_grade = 0",
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
            //array(
            //    "column" => "order_detail_dead_line",
            //    "validate" => array(
            //        array(
            //            "cat" => "eval",
            //            "msg" => _g("製造納期には製造開始日以降の日付を指定してください。"),
            //            "skipHasError" => true,
            //            "evalPHP" => "\$res=(strtotime(date($1))>=strtotime(date([[order_date]])));",
            //            "evalJS" => "res=(gen.date.parseDateStr($1)>=gen.date.parseDateStr($('#order_date').val()));",
            //        ),
            //    ),
            //),
            array(
                "column" => "order_detail_dead_line",
                "validate" => array(
                    array(
                        "cat" => "systemDateOrLater",
                        "msg" => _g('製造納期'),
                        "skipValidatePHP" => "{$unlock}=='1'",
                        "skipValidateJS" => "{$unlock}=='1'",
                    ),
                    array(
                        "cat" => "dateString",
                        "msg" => _g('製造納期が正しくありません。'),
                    ),
                ),
            ),
            array(
                "column" => "remarks_header",
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

        // 親テーブル登録
        $headerId = Logic_Order::entryOrderHeader(
            0           // classification
            , @$param['order_header_id']
            , null      // order_id_for_user
            , $param['order_date']
            , "0"       // partner_id
            , $param['remarks_header']
            , null      // worker_id
            , null      // section_id
            , null      // delivery_partner_id
            , null
        );

        // 更新の場合、対応するorder_detail_id を読み出しておく
        $detailId = null;
        if (isset($param['order_header_id'])) {
            $query = "select order_detail_id from order_detail WHERE order_header_id = '" . $param['order_header_id'] . "'";
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
            , 0         // item_price
            , ''        // item_sub_code
            , $param['order_detail_quantity']
            , $param['order_detail_dead_line']
            , false     // alarm_flag
            , "0"       // partner_id
            , 0         // payout_location_id
            , 0         // source_lot_id
            , null      // planDate
            , null      // planQty
            , null      // handQty
            , ""        // order_measure
            , 1         // multiple_of_order_measure
            , null      // remarks
            , false     // processFlag
        );
        // 指示書発行済みフラグをオフにする
        Logic_Order::setOrderPrintedFlag(array($headerId), false);

        // id(keyColumnの値)を戻す。keyColumnがないModelではfalseを戻す。
        return array($headerId, $detailId);
    }

}
