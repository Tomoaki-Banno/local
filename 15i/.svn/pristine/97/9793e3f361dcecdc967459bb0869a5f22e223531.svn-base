<?php

class Manufacturing_Estimate_Model extends Base_ModelBase
{

    var $headerId;

    protected function _getKeyColumn()
    {
        return array('estimate_header_id', 'estimate_detail_id');
    }

    protected function _setDefault(&$param, $entryMode)
    {
        global $gen_db;
        
        if (!isset($param['gen_line_no']) || !is_numeric($param['gen_line_no'])) {
            $param['gen_line_no'] = 1;     // CSVはどの行も1行めとして登録する（ただしheaderが設定されている場合は調整される）
        }
        
        switch ($entryMode) {
            case "csv":
                // header_id
                if (isset($param['estimate_header_id']) && is_numeric($param['estimate_header_id'])) {
                    $this->_headerId = $param['estimate_header_id'];
                } else {
                    $param['estimate_header_id'] = null;
                }
                if (!isset($param['estimate_detail_id'])) {
                    $param['estimate_detail_id'] = null;
                }
                
                // code -> id
                self::_codeToId($param, "customer_no", "customer_id", "", "", "customer_master");
                if (!isset($param['customer_id'])) {
                    $param['customer_id'] = null;
                }
                // マスタ選択の場合は名称を取得する（指定されていても無視される）
                if (isset($param['customer_no']) && $param['customer_id'] !== null && $param['customer_id'] != "-99999999") {
                    $query = "select customer_name from customer_master where customer_no = '{$param['customer_no']}'";
                    $param['customer_name'] = $gen_db->queryOneValue($query);
                }
                self::_codeToId($param, "item_code", "item_id", "", "", "item_master");
                if (!isset($param['item_id'])) {
                    $param['item_id'] = null;
                }
                
                // マスタ選択の場合は名称を取得する（指定されていても無視される）
                if (isset($param['item_code']) && $param['item_id'] !== null && $param['item_id'] != "-99999999") {
                    $query = "select item_name from item_master where item_code = '{$param['item_code']}'";
                    $param['item_name'] = $gen_db->queryOneValue($query);
                }

                self::_codeToId($param, "section_code", "section_id", "", "", "section_master");
                self::_codeToId($param, "worker_code", "worker_id", "", "", "worker_master");

                break;
        }
    }

    protected function _getColumns()
    {
        $columns = array(
            // ****** estimate_header ********
            array(
                "column" => "estimate_header_id",
                "pattern" => "id",
            ),
            array(
                "column" => "estimate_number",
                "convert" => array(
                    array(
                        "cat" => "trimEx",
                    ),
                ),
                "skipValidatePHP" => "$1==''",
                "skipValidateJS" => "$1==''",
                // 見積番号のように「ユーザー指定できるが全体としてユニークでなければならない」値は、
                // validateでの重複チェックだけでなく、このlockNumberの指定が必要。
                // くわしくは ModelBase の lockNumber処理の箇所のコメントを参照。
                "lockNumber" => true,
                "validate" => array(
                    // 新規登録時の重複チェック
                    array(
                        "cat" => "notExistRecord",
                        "msg" => _g('見積番号はすでに使用されています。別の番号を指定するか空欄にしてください。'),
                        "skipHasError" => true,
                        "skipValidatePHP" => "[[estimate_header_id]]!=''", // 修正はスキップ
                        "param" => "select estimate_number from estimate_header where estimate_number = $1"
                    ),
                    // 修正時の重複チェック
                    array(
                        "cat" => "notExistRecord",
                        "msg" => _g('見積番号はすでに使用されています。別の番号を指定するか空欄にしてください。'),
                        "skipHasError" => true,
                        "skipValidatePHP" => "[[estimate_header_id]]==''", // 新規登録はスキップ
                        // 更新のときは、自分自身の番号をチェック対象としない（自分自身と重複するのは当然）
                        "param" => "select estimate_number from estimate_header
                            where estimate_number = $1 and estimate_header_id <> [[estimate_header_id]]"
                    ),
                ),
            ),
            array(
                "column" => "estimate_date",
                "validate" => array(
                    array(
                        "cat" => "salesLockDateOrLater",
                        "msg" => _g('発行日')
                    ),
                ),
            ),
            array(
                "column" => "customer_id",
                "pattern" => "customer_id",
                "label" => _g("得意先"),
                "addwhere" => "classification=0",
            ),
            array(
                "column" => "customer_name",
                "validate" => array(
                    array(
                        "cat" => "required",
                        "msg" => _g('得意先名を指定してください。')
                    ),
                ),
            ),
            array(
                "column" => "worker_id",
                "pattern" => "worker_id",
                "label" => _g("担当者(自社)"),
            ),
            array(
                "column" => "section_id",
                "pattern" => "section_id",
                "label" => _g("部門(自社)"),
            ),
            array(
                "column" => "estimate_rank",
                "validate" => array(
                    array(
                        "cat" => "range",
                        "msg" => _g('ランクが正しくありません。'),
                        "param" => Gen_Option::getEstimateRank('model')
                    ),
                ),
            ),
            // ****** estimate_detail ********
            array(
                "column" => "estimate_detail_id",
                "pattern" => "id",
            ),
            array(
                "column" => "item_id",
                "convert" => array(
                    array(
                        "cat" => "notNumToNull",
                    ),
                ),
            ),
            array(
                "column" => "item_name",
                "skipValidatePHP" => "[[quantity]]==''",
                "skipValidateJS" => "[[quantity]]==''",
                "validate" => array(
                    array(
                        "cat" => "required",
                        "msg" => _g('品目名を指定してください。')
                    ),
                ),
            ),
            array(
                "column" => "quantity",
                "convert" => array(
                    array(
                        "cat" => "strToNum",
                    ),
                ),
                "skipValidatePHP" => "$1==''&&[[item_name]]==''",
                "skipValidateJS" => "$1==''&&[[item_name]]==''",
                "validate" => array(
                    array(
                        "cat" => "numeric",
                        "msg" => _g('数量が正しくありません。')
                    ),
                ),
            ),
            array(
                "column" => "sale_price",
                "convert" => array(
                    array(
                        "cat" => "strToNum",
                    ),
                ),
                "skipValidatePHP" => "$1==''&&[[item_name]]==''",
                "skipValidateJS" => "$1==''&&[[item_name]]==''",
                "validate" => array(
                    array(
                        "cat" => "numeric",
                        "msg" => _g('見積単価が正しくありません。')
                    ),
                ),
            ),
            array(
                "column" => "base_cost",
                "convert" => array(
                    array(
                        "cat" => "strToNum",
                    ),
                    array(
                        "cat" => "nullBlankToValue",
                        "param" => 0
                    ),
                ),
                "validate" => array(
                    array(
                        "cat" => "numeric",
                        "msg" => _g('販売原単価が正しくありません。')
                    ),
                ),
            ),
            array(
                "column" => "tax_class",
                "validate" => array(
                    array(
                        "cat" => "selectString",
                        "msg" => _g('課税区分が正しくありません。'),
                        "param" => array(array('0', '1'))
                    ),
                ),
            ),
        );

        return $columns;
    }

    protected function _regist(&$param, $isFirstRegist)
    {
        // 親テーブル登録
        if ($isFirstRegist) {
            $this->_headerId =
                    Logic_Estimate::entryEstimate(
                            $param['estimate_header_id']
                            , $param['estimate_number']
                            , $param['customer_id']
                            , $param['customer_name']
                            , $param['estimate_date']
                            , $param['customer_zip']
                            , $param['customer_address1']
                            , $param['customer_address2']
                            , $param['customer_tel']
                            , $param['customer_fax']
                            , $param['person_in_charge']
                            , $param['subject']
                            , $param['delivery_date']
                            , $param['delivery_place']
                            , $param['mode_of_dealing']
                            , $param['expire_date']
                            , $param['worker_id']
                            , @$param['section_id']
                            , $param['estimate_rank']
                            , $param['remarks']
            );
        }

        // 子テーブル登録
        $estimateDetailId = Logic_Estimate::entryEstimateDetail(
                $param['estimate_detail_id']
                , $this->_headerId
                , $param['gen_line_no']
                , @$param['item_id']    // 09i/10iでは常に「0」だったが、 12iで再び登録されるようになった（品目名手入力の場合はnull）
                , $param['item_code']
                , $param['item_name']
                , $param['quantity']
                , $param['measure']
                , $param['base_cost']
                , $param['sale_price']
                , Gen_Math::mul(Gen_Math::sub($param['sale_price'], $param['base_cost']), $param['quantity'])
                , $param['tax_class']
                , @$param['remarks_detail']
                , @$param['remarks_detail_2']
        );

        // id(keyColumnの値)を戻す。明細があるModelではarray(ヘッダid, 明細id) とする。keyColumnがないModelではfalseを戻す。
        return array($this->_headerId, $estimateDetailId);
    }

    // EditListがある場合に必要
    protected function _detailDelete($detailId)
    {
        // EditList上で削除されたレコードの処理。
        // 本来はロック年月をチェックすべき。
        // とりあえずUIで制限されているのでよしとする
        Logic_Estimate::deleteEstimateDetail($detailId);
    }

    // EditListがある場合に必要
    protected function _lineDelete($lineNo)
    {
        global $gen_db;

        // EditList上で削除された行の処理。
        // 本来はロック年月をチェックすべき。
        // とりあえずUIで制限されているのでよしとする
        $query = "select estimate_detail_id from estimate_detail where estimate_header_id = '{$this->_headerId}' and line_no >= {$lineNo}";
        $res = $gen_db->getArray($query);
        if (is_array($res)) {
            foreach ($res as $row) {
                Logic_Estimate::deleteEstimateDetail($row['estimate_detail_id']);
            }
        }
    }

}
