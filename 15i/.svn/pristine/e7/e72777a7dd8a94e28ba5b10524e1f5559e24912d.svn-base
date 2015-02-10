<?php

class Partner_Order_Model extends Base_ModelBase
{

    var $gen_line_no;
    var $csvUpdateMode = false;     // CSV上書きモード。trueの場合、order_noをキーに上書きする
    var $skip = false;              // 登録ズミをエラーとせずスキップする（画面登録の場合、一部の行だけ受入済みの場合にも登録できるようtrueにする）
    private $_headerId;

    protected function _getKeyColumn()
    {
        return array('order_header_id', 'order_detail_id');
    }

    protected function _setDefault(&$param, $entryMode)
    {
        global $gen_db;

        // order_no が設定されていない場合、id　をキーにして取得
        if (!isset($param['order_no']) && isset($param['order_detail_id']) && $param['order_detail_id'] != "") {
            $query = "select order_no from order_detail where order_detail_id = '{$param['order_detail_id']}'";
            $param['order_no'] = $gen_db->queryOneValue($query);
        }

        // multiple_of_order_measure
        if ($param['multiple_of_order_measure'] === null || $param['multiple_of_order_measure'] == "") {
            $param['multiple_of_order_measure'] = 1;
        }

        // payout_location_id は 外製指示書用
        if (!isset($param['payout_location_id'])) {
            $param['payout_location_id'] = "";
        }

        // 上書きモードの処理　（csv & excel）
        if ($this->csvUpdateMode && !isset($param['order_detail_id']) && isset($param['order_no']) && $param['order_no'] != "") {
            // CSV上書き許可で、オーダー番号が指定されている場合。
            //  オーダー番号（order_no）をキーに各データを取得する。（注文書番号は指定されていても無視）
            $query = "
            select
                order_detail.order_header_id,
                order_id_for_user,
                order_detail_id,
                line_no
            from
                order_detail
                inner join order_header on order_detail.order_header_id = order_header.order_header_id
            where
                order_no = '{$param['order_no']}'
            ";
            $obj = $gen_db->queryOneRowObject($query);
            $param['order_header_id'] = @$obj->order_header_id;
            $param['order_id_for_user'] = @$obj->order_id_for_user;
            $param['order_detail_id'] = @$obj->order_detail_id;
            $param['gen_line_no'] = @$obj->line_no;

            // オーダー番号が既存データに存在しなかった場合は新規登録扱い。
            // ※ 画面上からはオーダー番号を指定して新規登録することはできないが、CSVではそれができる
            // validatorの動作上、次の処理を行っておく必要がある。
            if ($param['order_header_id'] === null || !isset($param['order_header_id']) || !is_numeric($param['order_header_id']))
                unset($param['order_header_id']);
            if ($param['order_detail_id'] === null || !isset($param['order_detail_id']) || !is_numeric($param['order_detail_id']))
                unset($param['order_detail_id']);
        } else if ($entryMode == "csv") {
            // CSVで上書き不許可か、上書き許可だがオーダー番号未指定の場合。
            // 注文書番号を消しておく（validateで重複チェックに引っかかるのを防ぐため）
            $param['order_id_for_user'] = null;
        }

        // 行未指定の場合
        if (!isset($param['gen_line_no']) || !is_numeric($param['gen_line_no'])) {
            $param['gen_line_no'] = 1;     // CSV等はどの行も受注１行めとして登録する
        }

        switch ($entryMode) {
            case "csv":
                $param['classification'] = "1"; // 注文書
                // code -> id
                self::_codeToId($param, "partner_no", "partner_id", "customer_no", "customer_id", "customer_master");
                self::_codeToId($param, "worker_code", "worker_id", "", "", "worker_master");
                self::_codeToId($param, "section_code", "section_id", "", "", "section_master");
                self::_codeToId($param, "item_code", "item_id", "", "", "item_master");
                self::_codeToId($param, "delivery_partner_no", "delivery_partner_id", "customer_no", "customer_id", "customer_master");
                self::_codeToId($param, "payout_location_code", "payout_location_id", "location_code", "location_id", "location_master");
                if (!isset($param['payout_location_id'])) {
                    $param['payout_location_id'] = "0";
                }
                
                // CSVで取引先コード未設定の場合は、品目の標準手配先をセットする
                //  ag.cgi?page=ProjectDocView&pid=1516&did=196231
                if (($param['partner_no'] === null || $param['partner_no'] === "") && Gen_String::isNumeric($param['item_id'])) {
                    $query = "select order_user_id from item_order_master where item_id = '{$param['item_id']}' and line_number = 0";
                    $param['partner_id'] = $gen_db->queryOneValue($query);
                }

                break;
        }
    }

    protected function _getColumns()
    {
        global $gen_db;

        // データロック対象外
        $query = "select unlock_object_3 from company_master";
        $unlock = $gen_db->queryOneValue($query);

        $columns = array(
            // ***** order_header *****
            array(
                "column" => "order_header_id",
                "pattern" => "id",
            ),
            array(
                "column" => "order_id_for_user",
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
                        "msg" => _g('注文書番号はすでに使用されています。別の番号を指定するか空欄にしてください。'),
                        "skipHasError" => true,
                        "skipValidatePHP" => "[[order_header_id]]!=''", // 修正はスキップ
                        "param" => "select order_id_for_user from order_header where order_id_for_user = $1"
                    ),
                    // 修正時の重複チェック
                    array(
                        "cat" => "notExistRecord",
                        "msg" => _g('注文書番号はすでに使用されています。別の番号を指定するか空欄にしてください。'),
                        "skipHasError" => true,
                        "skipValidatePHP" => "[[order_header_id]]==''", // 新規登録はスキップ
                        // 更新のときは、自分自身の番号をチェック対象としない（自分自身と重複するのは当然）
                        "param" => "select order_id_for_user from order_header
                            where order_id_for_user = $1 and order_header_id <> [[order_header_id]]"
                    ),
                ),
            ),
            array(
                "column" => "partner_id",
                "pattern" => "customer_id_required",
                "addwhere" => "classification=1",
            ),
            array(
                "column" => "order_date",
                "validate" => array(
                    array(
                        "cat" => "buyLockDateOrLater",
                        "msg" => _g('発注日'),
                        "skipValidatePHP" => "{$unlock}=='1'",
                        "skipValidateJS" => "{$unlock}=='1'",
                    ),
                    array(
                        "cat" => "dateString",
                        "msg" => _g('発注日が正しくありません。'),
                    ),
                ),
            ),
            array(
                "column" => "classification",
                "validate" => array(
                    array(
                        "cat" => "selectString",
                        "msg" => _g('classificationが正しくありません。'),
                        "param" => array(array('0', '1', '2'))
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
                "column" => "delivery_partner_id",
                "pattern" => "customer_id",
                "label" => _g("発送先"),
                // 得意先・発送先だけでなくパートナーも選べるようにする。直送の場合のため
                "addwhere" => "classification in (0,1,2)",
            ),
            array(
                "column" => "accepted_regist",
                "pattern" => "bool",
            ),
            // ***** order_detail *****
            array(
                "column" => "order_detail_id",
                "skipValidatePHP" => "$1=='' || [[display]]", // 画面からの登録の場合、受入済みをエラーとせずスキップする。一部受入済みを登録できるようにするため。displayはEntryで設定している。
                "skipValidateJS" => "true", // 画面上には存在しない項目
                "validate" => array(
                    array(
                        "cat" => "blankOrNumeric",
                        "msg" => _g('order_detail_idが正しくありません。')
                    ),
                    // 受入済みチェック
                    //   画面登録の場合は、一部の行が受入済の状態でも未受入行を登録できるようにするため、エラーにせず無視（スキップ）
                    //   する必要があるが、そのスキップ処理はこのクラスの$acceptedSkipModeで行う。
                    array(
                        "cat" => "notExistRecord",
                        "msg" => _g('この注文に対し、すでに受入が登録されているため内容を変更できません。'),
                        "skipHasError" => true,
                        "param" => "select accepted_id from accepted where order_detail_id = $1"
                    ),
                ),
            ),
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
                        "skipValidatePHP" => "[[order_detail_id]]!=''", // 修正はスキップ
                        "param" => "select order_no from order_detail where order_no = $1"
                    ),
                    // 修正時の重複チェック
                    array(
                        "cat" => "notExistRecord",
                        "msg" => _g('オーダー番号はすでに使用されています。別の番号を指定するか空欄にしてください。'),
                        "skipHasError" => true,
                        "skipValidatePHP" => "[[order_detail_id]]==''", // 新規登録はスキップ
                        // 更新のときは、自分自身の番号をチェック対象としない（自分自身と重複するのは当然）
                        "param" => "select order_no from order_detail
                            where order_no = $1 and order_detail_id <> [[order_detail_id]]"
                    ),
                ),
            ),
            array(
                "column" => "seiban",
                "convert" => array(
                    array(
                        "cat" => "nullBlankToValue",
                        "param" => "",
                    ),
                ),
                "validate" => array(
                    // 製番の手動付与や振り替えが可能になったため、下記の3つのチェックが必要になった。
                    // 詳細は Partner_Order_Edit の カラムリストの製番の箇所のコメントを参照。
                    array(
                        "skipHasError" => true,
                        "skipValidatePHP" => "$1==='' || [[item_id]]===''",
                        "cat" => "existRecord",
                        "msg" => _g('MRP/ロット品目に対して製番を指定することはできません。'),
                        "param" => "select item_id from item_master where item_id=[[item_id]] and order_class=0",
                    ),
                    array(// 新規登録用チェック
                        "skipHasError" => true,
                        "skipValidatePHP" => "$1==='' || [[item_id]]==='' || [[order_header_id]]!=''", // 更新時はスキップ
                        "cat" => "existRecord",
                        "msg" => _g('製番が正しくありません。指定できるのは製番品目に対する確定受注の受注製番だけです。'),
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
                "column" => "item_id",
                "pattern" => "item_id", // null ok
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
                        "cat" => "blankOrNumeric",
                        "msg" => _g('発注単価が正しくありません。'),
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
                "skipValidatePHP" => "$1===''&&[[item_id]]===''", // 品目が選択されていない行はブランクOK
                "skipValidateJS" => "$1===''&&[[item_id]]===''",
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
                        "msg" => _g('注文納期'),
                        "skipValidatePHP" => "$1===''&&[[item_id]]===''||({$unlock}=='1'&&[[accepted_regist]]!='true')", // 品目が選択されていない行はブランクOK
                        "skipValidateJS" => "$1===''&&[[item_id]]===''||({$unlock}=='1'&&[[accepted_regist]]!='true')",
                    ),
                    array(
                        "cat" => "dateString",
                        "msg" => _g('注文納期が正しくありません。'),
                        "skipValidatePHP" => "$1===''&&[[item_id]]===''", // 品目が選択されていない行はブランクOK
                        "skipValidateJS" => "$1===''&&[[item_id]]===''",
                    ),
                ),
            ),
            array(
                "column" => "payout_location_id",
                "pattern" => "location_id", // null ok
                "label" => _g("子品目支給ロケーション"),
            ),
            array(
                "column" => "multiple_of_order_measure",
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
                        "msg" => _g('手配単位倍数には0より大きい数値を指定してください。'),
                        "param" => 0.00000001,
                    ),
                ),
            ),
        );

        return $columns;
    }

    protected function _regist(&$param, $isFirstRegist)
    {
        global $gen_db;

        // 親テーブル登録
        if ($isFirstRegist) {
            $this->_headerId = Logic_Order::entryOrderHeader(
                $param['classification']
                , @$param['order_header_id']
                , $param['order_id_for_user']
                , $param['order_date']
                , $param['partner_id']
                , $param['remarks_header']
                , @$param['worker_id']
                , @$param['section_id']
                , @$param['delivery_partner_id']
                , null
            );
        }

        // 子テーブル登録
        // 同時に子品目の使用予約・支給処理も行う
        $orderDetailId = Logic_Order::entryOrderDetail(
            @$param['order_detail_id']
            , $this->_headerId
            , $param['gen_line_no']
            , $param['order_no']
            , $param['seiban']
            , $param['item_id']
            , @$param['item_code']
            , @$param['item_name']
            , @$param['item_price']
            , @$param['item_sub_code']
            , $param['order_detail_quantity']
            , $param['order_detail_dead_line']
            , false     // alarm_flag
            , $param['partner_id']
            , @$param['payout_location_id']
            , 0         // source_lot_id
            , null      // planDate
            , null      // planQty
            , null      // handQty
            , @$param['order_measure']
            , @$param['multiple_of_order_measure']
            , @$param['remarks']
            , false     // processFlag
        );

        // 注文登録済みフラグをオフにする
        if (isset($param['order_header_id']))
            Logic_Order::setOrderPrintedFlag(array($param['order_header_id']), false);

        // 同時に受入を登録（新規のみ）
        if ($param['accepted_regist'] == "true") {
            // オーダー番号取得
            $query = "select order_no from order_detail where order_detail_id = '{$orderDetailId}'";
            $orderNo = $gen_db->queryOneValue($query);

            // 検収日は、取引先マスタの「検収リードタイム」が設定されている場合、
            // 受入日に検収リードタイムを足した日付（休日も考慮します）が自動設定されます。
            $query = "select inspection_lead_time from customer_master where customer_id =
            	(select partner_id from order_header
                inner join order_detail on order_header.order_header_id = order_detail.order_header_id
            	where order_detail_id = '{$orderDetailId}')";
            $lt = $gen_db->queryOneValue($query);

            if ($lt == '') {
                $insDate = '';
            } else {
                // func名は「getDeadLine」だが、ここでは検収日取得に使用している
                $insDate = date('Y-m-d', Gen_Date::getDeadLine(strtotime($param['order_detail_dead_line']), $lt));
            }

            Logic_Accepted::entryAccepted(
                    null
                    , $orderDetailId
                    , $orderNo
                    , $param['order_detail_dead_line']  // 受入日 = 注文納期とする
                    , $insDate                          // 検収日
                    , $param['order_detail_quantity']
                    , null                              // 単価 = 発注単価とする。（登録ロジックで自動取得）
                    , null                              // 税率（登録ロジックで仕入計上基準日の税率を自動取得）
                    , null                              // レート（登録ロジックで仕入計上基準日のレートで自動計算）
                    , $param['remarks']
                    , -1                                // location_id  入庫ロケは品目マスタ「標準ロケーション（受入）」とする。（登録ロジックで自動取得）
                    , ''                                // lot_no
                    , null                              // payment_date
                    , "true"                            // completed
                    , null                              // use_by
            );
        }
        
        // id(keyColumnの値)を戻す。明細があるModelではarray(ヘッダid, 明細id) とする。keyColumnがないModelではfalseを戻す。
        return array($this->_headerId, $orderDetailId);
    }

    // EditListがある場合に必要
    protected function _detailDelete($detailId)
    {
        // EditList上で削除されたレコードの処理。
        // 本来はロック年月・受入状況をチェックすべき。
        // とりあえずUIで制限されているのでよしとする
        Logic_Order::deleteOrderDetail($detailId);
    }

    // EditListがある場合に必要
    protected function _lineDelete($lineNo)
    {
        global $gen_db;

        // EditList上で削除された行の処理。
        // 本来はロック年月・受入状況をチェックすべき。
        // とりあえずUIで制限されているのでよしとする
        $query = "select order_detail_id from order_detail where order_header_id = '{$this->_headerId}' and line_no >= {$lineNo}";
        $res = $gen_db->getArray($query);
        if (is_array($res)) {
            foreach ($res as $row) {
                Logic_Order::deleteOrderDetail($row['order_detail_id']);
            }
        }
    }

}
