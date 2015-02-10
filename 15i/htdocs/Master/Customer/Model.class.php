<?php

class Master_Customer_Model extends Base_ModelBase
{

    var $csvUpdateMode = false;

    protected function _getKeyColumn()
    {
        return 'customer_id';
    }

    protected function _setDefault(&$param, $entryMode)
    {
        global $gen_db;

        // 上書きモードの処理　（csv）
        if ($this->csvUpdateMode && !isset($param['customer_id']) && $param['customer_no'] != "") {
            $query = "select customer_id from customer_master where customer_no = '{$param['customer_no']}'";
            $param['customer_id'] = $gen_db->queryOneValue($query);
            if ($param['customer_id'] === null)
                unset($param['customer_id']);
        }

        $keyCurrency = $gen_db->queryOneValue("select key_currency from company_master");
        switch ($entryMode) {
            case "csv":
                if (@$param['end_customer'] == "1") {
                    $param['end_customer'] = "true";
                }
                if (@$param['currency_name'] == "") {
                    $param['currency_name'] = $keyCurrency;
                }

                // code -> id
                self::_codeToId($param, "customer_group_code_1", "customer_group_id_1", "customer_group_code", "customer_group_id", "customer_group_master");
                self::_codeToId($param, "customer_group_code_2", "customer_group_id_2", "customer_group_code", "customer_group_id", "customer_group_master");
                self::_codeToId($param, "customer_group_code_3", "customer_group_id_3", "customer_group_code", "customer_group_id", "customer_group_master");
                if ($param['customer_group_code_1'] == '') {
                    $param['customer_group_id_1'] = '';
                }
                if ($param['customer_group_code_2'] == '') {
                    $param['customer_group_id_2'] = '';
                }
                if ($param['customer_group_code_3'] == '') {
                    $param['customer_group_id_3'] = '';
                }

                if ($param['currency_name'] == $keyCurrency) {
                    $param['currency_id'] = null;
                } else {
                    self::_codeToId($param, "currency_name", "currency_id", "", "", "currency_master");
                }
                if (isset($param['report_language']) && $param['report_language'] == _g('英語')) {
                    $param['report_language'] = 1;
                } else {
                    $param['report_language'] = 0;
                }
                self::_codeToId($param, "bill_customer_no", "bill_customer_id", "customer_no", "customer_id", "customer_master");

                self::_codeToId($param, "price_percent_group_code", "price_percent_group_id", "", "", "price_percent_group_master");
                if (!isset($param['price_percent_group_code']) || $param['price_percent_group_code'] == '') {
                    $param['price_percent_group_id'] = '';
                }

                break;
        }
    }
    
    // _setDefault() は ClickEditでは実行されない。
    // ClickEditでも実行したい処理についてはこのメソッドを使う。
    function beforeLogic(&$param)
    {
        global $gen_db;
        
        if (isset($param['customer_id']) && is_numeric($param['customer_id'])) {
            // 取引通貨を取得
            $query = "select currency_id from customer_master where customer_id = '{$param['customer_id']}'";
            $currency_id = $gen_db->queryOneValue($query);
            // オーダーが登録されている場合は取引通貨の変更を禁止する
            if (Logic_Order::existOrder($param['customer_id']) && $param['currency_id'] != $currency_id) {
                $param['currency_id_error1'] = 't';
            }
            // 受注が登録されている場合は取引通貨の変更を禁止する
            if (Logic_Received::existReceived($param['customer_id']) && $param['currency_id'] != $currency_id) {
                $param['currency_id_error2'] = 't';
            }

            // 請求情報を取得
            $query = "select bill_pattern, opening_balance, opening_date
                from customer_master where customer_id = '{$param['customer_id']}'";
            $res = $gen_db->queryOneRowObject($query);
            // 請求書が発行されている場合は売掛残高初期値,売掛基準日の変更を禁止する（請求パターンも変更禁止だが、それは別の方法でチェックしている）
            if (Logic_Bill::existBill($param['customer_id'])) {
                if ($param['opening_balance'] != $res->opening_balance) {
                    $param['opening_balance_error'] = 't';
                }
                if ($param['opening_date'] != $res->opening_date) {
                    $param['opening_date_error'] = 't';
                }
            }
        }
        // サプライヤーは「税計算単位」「請求パターン」が未入力でも登録できるようにする
        if (isset($param['classification']) && $param['classification'] == "1") {
            if (!isset($param['tax_category']) || $param['tax_category'] == "") {
                $param['tax_category'] = "0";
            }
            if (!isset($param['bill_pattern']) || $param['bill_pattern'] == "") {
                $param['bill_pattern'] = "0";
            }
        }
    }

    protected function _getColumns()
    {
        global $gen_db;
        
        // 帳票テンプレート
        for ($i=0; $i<=3; $i++) {
            switch($i) {
                case 0: $cat = "Delivery"; break;
                case 1: $cat = "Bill"; break;
                case 2: $cat = "PartnerOrder"; break;
                case 3: $cat = "PartnerSubcontract"; break;
            }
            $info = Gen_PDF::getTemplateInfo($cat);
            $templates[$i] = array();
            foreach($info[2] as $infoOne) {
                $templates[$i][] = $infoOne['file'];
            }
        }
        
        
        $columns = array(
            array(
                "column" => "customer_id",
                "pattern" => "id",
            ),
            array(
                "column" => "customer_no",
                "convert" => array(
                    array(
                        "cat" => "trimEx",
                    ),
                ),
                // 「ユーザー指定できるが全体としてユニークでなければならない」値は、
                // validateでの重複チェックだけでなく、このlockNumberの指定が必要。
                // くわしくは ModelBase の lockNumber処理の箇所のコメントを参照。
                "lockNumber" => true,
                "validate" => array(
                    array(
                        "cat" => "required",
                        "msg" => _g('取引先コードを指定してください。')
                    ),
                    array(
                        "cat" => "notContainTwoByteAlphaNum",
                        "msg" => _g('取引先コードに全角アルファベットや全角数字は使用できません。')
                    ),
                    // 新規登録時の重複チェック
                    array(
                        "cat" => "notExistRecord",
                        "msg" => _g('取引先コードはすでに使用されています。別の番号を指定してください。'),
                        "skipHasError" => true,
                        "skipValidatePHP" => "[[customer_id]]!=''", // 修正はスキップ
                        "param" => "select customer_id from customer_master where customer_no = $1"
                    ),
                    // 修正時の重複チェック
                    array(
                        "cat" => "notExistRecord",
                        "msg" => _g('取引先コードはすでに使用されています。別の番号を指定してください。'),
                        "skipHasError" => true,
                        "skipValidatePHP" => "[[customer_id]]==''", // 新規登録はスキップ
                        // 更新のときは、自分自身の番号をチェック対象としない（自分自身と重複するのは当然）
                        "param" => "select customer_id from customer_master where customer_no = $1
                            and customer_id <> [[customer_id]]"
                    ),
                ),
            ),
            array(
                "column" => "customer_name",
                "convert" => array(
                    array(
                        "cat" => "trimEx",
                    ),
                ),
                "validate" => array(
                    array(
                        "cat" => "required",
                        "msg" => _g('取引先名を指定してください。')
                    ),
                ),
            ),
            array(
                "column" => "end_customer",
                "pattern" => "bool",
            ),
            array(
                "column" => "classification",
                "validate" => array(
                    array(
                        "cat" => "selectString",
                        "msg" => _g('区分指定が正しくありません。'),
                        "param" => array(array(0, 1, 2)),
                    ),
                ),
            ),
            array(
                "column" => "customer_group_id_1",
                "pattern" => "customer_group_id",
                "label" => _g("取引先グループ1"),
            ),
            array(
                "column" => "customer_group_id_2",
                "pattern" => "customer_group_id",
                "label" => _g("取引先グループ2"),
            ),
            array(
                "column" => "customer_group_id_3",
                "pattern" => "customer_group_id",
                "label" => _g("取引先グループ3"),
            ),
            array(
                "column" => "rounding",
                "validate" => array(
                    array(
                        "cat" => "selectString",
                        "msg" => _g('端数処理が正しくありません。'),
                        "param" => array(array('round', 'floor', 'ceil')),
                    ),
                ),
            ),
            array(
                "column" => "precision",
                "convert" => array(
                    array(
                        "cat" => "strToNum",
                    ),
                ),
                "validate" => array(
                    // PHPの小数点の有効桁数を考慮して、小数点以下は6桁までとする。
                    array(
                        "cat" => "selectString",
                        "msg" => _g('金額の小数点以下桁数は0から6の数値で指定してください。'),
                        "param" => array(array(0, 1, 2, 3, 4, 5, 6)),
                    ),
                ),
            ),
            array(
                "column" => "inspection_lead_time",
                "pattern" => "integer", // null ok
                "label" => _g("検収リードタイム"),
            ),
            array(
                "column" => "currency_id",
                "convert" => array(
                    array(
                        "cat" => "blankToNull",
                    ),
                ),
                "validate" => array(
                    array(
                        "cat" => "blankOrNumeric",
                        "skipValidatePHP" => "$1===null",
                        "skipValidateJS" => "$1===null",
                        "msg" => _g('取引通貨が正しくありません。'),
                        "skipHasError" => true,
                    ),
                    array(
                        "cat" => "existRecord",
                        "msg" => _g('取引通貨が正しくありません。'),
                        "skipHasError" => true,
                        "skipValidatePHP" => "$1===null",
                        "skipValidateJS" => "$1===null",
                        "param" => "select currency_id from currency_master where currency_id = $1"
                    ),
                    array(
                        "cat" => "eval",
                        "msg" => _g('オーダーが登録されているため、取引通貨を変更することはできません。'),
                        "skipHasError" => true,
                        "skipValidateJS" => "true",
                        "evalPHP" => "\$res=([[currency_id_error1]]!=='t');",
                        "evalJS" => ";",
                    ),
                    array(
                        "cat" => "eval",
                        "msg" => _g('受注が登録されているため、取引通貨を変更することはできません。'),
                        "skipHasError" => true,
                        "skipValidateJS" => "true",
                        "evalPHP" => "\$res=([[currency_id_error2]]!=='t');",
                        "evalJS" => ";",
                    ),
                ),
            ),
            array(
                "column" => "report_language",
                "validate" => array(
                    array(
                        "cat" => "selectString",
                        "msg" => _g('帳票言語区分が正しくありません。'),
                        "param" => array(array('0', '1')),
                    ),
                ),
            ),
            array(
                "column" => "bill_pattern",
                "validate" => array(
                    array(
                        "cat" => "selectString",
                        "msg" => _g('請求パターンが正しくありません。'),
                        "param" => array(Gen_Option::getBillPattern('model')),
                    ),
                    array(
                        "cat" => "notExistRecord",
                        "msg" => _g('請求書が発行されているため請求パターンを変更できません。'),
                        "skipHasError" => true,
                        "skipValidatePHP" => "[[customer_id]]==''||$1==2",   // 新規登録はスキップ（締めが選択されている時のみチェック）
                        "param" => "select customer_id from customer_master
                            left join (select customer_id as cid, count(bill_header_id) as bill_count from bill_header group by customer_id) as t_bill
                            on coalesce(customer_master.bill_customer_id, customer_master.customer_id) = t_bill.cid
                            where customer_id = [[customer_id]] and bill_pattern = 2 and coalesce(t_bill.bill_count, 0) > 0
                        ",
                    ),
                    array(
                        "cat" => "notExistRecord",
                        "msg" => _g('請求書が発行されているため請求パターンを変更できません。'),
                        "skipHasError" => true,
                        "skipValidatePHP" => "[[customer_id]]==''||$1!=2",   // 新規登録はスキップ（都度が選択されている時のみチェック）
                        "param" => "select customer_id from customer_master
                            left join (select customer_id as cid, count(bill_header_id) as bill_count from bill_header group by customer_id) as t_bill
                            on coalesce(customer_master.bill_customer_id, customer_master.customer_id) = t_bill.cid
                            where customer_id = [[customer_id]] and bill_pattern <> 2 and coalesce(t_bill.bill_count, 0) > 0
                        ",
                    ),
                ),
            ),
            array(
                "column" => "bill_customer_id",
                "pattern" => "customer_id",
                "label" => _g("請求先"),
                "addwhere" => "classification=0",
            ),
            array(
                "column" => "bill_customer_id",
                "validate" => array(
                    array(
                        "cat" => "eval",
                        "msg" => _g('登録する取引先を請求先として指定できません。'),
                        "skipValidatePHP" => "$1==''||[[bill_pattern]]=='2'",
                        "skipValidateJS" => "$1==''||[[bill_pattern]]=='2'",
                        "evalPHP" => "\$res=([[customer_id]]!==$1);",
                        "evalJS" => "res=([[customer_id]]!==$1);",
                    ),
                    array(
                        "cat" => "notExistRecord",
                        "msg" => _g('請求先として登録されているため請求先を指定できません。'),
                        "skipHasError" => true,
                        "skipValidatePHP" => "[[customer_id]]==''||$1==null",   // 新規登録はスキップ
                        "param" => "select customer_id from customer_master where bill_customer_id = [[customer_id]]"
                    ),
                    array(
                        "cat" => "existRecord",
                        "msg" => _g('請求先が登録されている取引先を指定できません。'),
                        "skipHasError" => true,
                        "skipValidatePHP" => "$1==null",
                        "param" => "select customer_id from customer_master where coalesce(bill_customer_id,0) = 0 and customer_id = $1"
                    ),
                ),
            ),
            array(
                "column" => "monthly_limit_date",
                "convert" => array(
                    array(
                        // CSVで「都度請求」のときは入力を省略できるように
                        "cat" => "nullBlankToValue",
                        "param" => 31,
                    ),
                ),
                "validate" => array(
                    array(
                        "cat" => "selectString",
                        "msg" => _g('締日グループが正しくありません。'),
                        "param" => array(Gen_Option::getMonthlyLimit('model')),
                    ),
                ),
            ),
            array(
                "column" => "tax_category",
                "validate" => array(
                    array(
                        "cat" => "selectString",
                        "msg" => _g('税計算単位が正しくありません。'),
                        "param" => array(array(0, 1, 2)),
                    ),
                ),
            ),
            array(
                "column" => "price_percent",
                "pattern" => "numeric", // null ok
                "label" => _g("掛率"),
            ),
            array(
                "column" => "price_percent_group_id",
                "pattern" => "price_percent_group_id",
                "label" => _g("掛率グループ"),
            ),
            array(
                "column" => "opening_balance",
                "pattern" => "numeric", // null ok
                "label" => _g("売掛残高初期値"),
            ),
            array(
                "column" => "opening_balance",
                "skipValidatePHP" => "$1===null",
                "skipValidateJS" => "$1===null",
                "validate" => array(
                    array(
                        "cat" => "eval",
                        "msg" => _g('請求書が発行されているため、売掛初期値を変更することはできません。'),
                        "skipHasError" => true,
                        "skipValidateJS" => "true",
                        "evalPHP" => "\$res=([[opening_balance_error]]!=='t');",
                        "evalJS" => ";",
                    ),
                ),
            ),
            array(
                "column" => "opening_date",
                "convert" => array(
                    array(
                        "cat" => "blankToNull",
                    ),
                ),
                "skipValidatePHP" => "$1===null",
                "skipValidateJS" => "$1===null",
                "validate" => array(
                    array(
                        "cat" => "blankOrDateString",
                        "msg" => _g('売掛基準日が不正です。'),
                    ),
                    array(
                        "cat" => "eval",
                        "msg" => _g('請求書が発行されているため、売掛基準日を変更することはできません。'),
                        "skipHasError" => true,
                        "skipValidateJS" => "true",
                        "evalPHP" => "\$res=([[opening_date_error]]!=='t');",
                        "evalJS" => ";",
                    ),
                ),
            ),
            array(
                "column" => "credit_line",
                "pattern" => "integer", // null ok
                "label" => _g("与信限度額"),
            ),
            array(
                "column" => "receivable_cycle1",
                "pattern" => "integer", // null ok
                "label" => _g("回収サイクル1"),
            ),
            array(
                "column" => "receivable_cycle2_month",
                "pattern" => "integer", // null ok
                "label" => _g("回収サイクル2（xヶ月後）"),
            ),
            array(
                "column" => "receivable_cycle2_day",
                "pattern" => "integer", // null ok
                "label" => _g("回収サイクル2（x日）"),
            ),
            array(
                "column" => "receivable_cycle2_month",
                "dependentColumn" => "receivable_cycle2_day",
                "skipValidatePHP" => "[[receivable_cycle2_day]]==''",
                "skipValidateJS" => "[[receivable_cycle2_day]]==''",
                "validate" => array(
                    array(
                        "cat" => "eval",
                        "msg" => _g("回収サイクル2（x日）が指定されている場合、回収サイクル2（xヶ月後）も指定する必要があります。"),
                        "evalPHP" => "\$res=([[receivable_cycle2_month]]!='');",
                        "evalJS" => "res=([[receivable_cycle2_month]]!='');",
                    ),
                ),
            ),
            array(
                "column" => "receivable_cycle2_day",
                "dependentColumn" => "receivable_cycle2_month",
                "skipValidatePHP" => "[[receivable_cycle2_month]]==''",
                "skipValidateJS" => "[[receivable_cycle2_month]]==''",
                "validate" => array(
                    array(
                        "cat" => "eval",
                        "msg" => _g("回収サイクル2（xヶ月後）が指定されている場合、回収サイクル2（x日）も指定する必要があります。"),
                        "evalPHP" => "\$res=([[receivable_cycle2_day]]!='');",
                        "evalJS" => "res=([[receivable_cycle2_day]]!='');",
                    ),
                ),
            ),
            array(
                "column" => "default_lead_time",
                "pattern" => "integer", // null ok
                "label" => _g("標準リードタイム"),
            ),
            array(
                "column" => "default_lead_time",
                "convert" => array(
                    array(
                        "cat" => "strToNum",
                    ),
                    array(
                        "cat" => "blankToNull",
                    ),
                ),
                "validate" => array(
                    array(
                        "cat" => "range",
                        "skipValidatePHP" => "$1===null",
                        "skipValidateJS" => "$1===null",
                        "msg" => _g('標準リードタイムが正しくありません。0から365の整数を指定してください。'),
                        "param" => array(0, 365),
                    ),
                ),
            ),
            array(
                "column" => "payment_opening_balance",
                "pattern" => "numeric", // null ok
                "label" => _g("買掛残高初期値"),
            ),
            array(
                "column" => "payment_opening_date",
                "convert" => array(
                    array(
                        "cat" => "blankToNull",
                    ),
                ),
                "skipValidatePHP" => "$1===null",
                "skipValidateJS" => "$1===null",
                "validate" => array(
                    array(
                        "cat" => "blankOrDateString",
                        "msg" => _g('買掛基準日が不正です。'),
                    ),
                ),
            ),
            array(
                "column" => "payment_cycle1",
                "pattern" => "integer", // null ok
                "label" => _g("支払サイクル1"),
            ),
            array(
                "column" => "payment_cycle2_month",
                "pattern" => "integer", // null ok
                "label" => _g("支払サイクル2（xヶ月後）"),
            ),
            array(
                "column" => "payment_cycle2_day",
                "pattern" => "integer", // null ok
                "label" => _g("支払サイクル2（x日）"),
            ),
            array(
                "column" => "payment_cycle2_month",
                "dependentColumn" => "payment_cycle2_day",
                "skipValidatePHP" => "[[payment_cycle2_day]]==''",
                "skipValidateJS" => "[[payment_cycle2_day]]==''",
                "validate" => array(
                    array(
                        "cat" => "eval",
                        "msg" => _g("支払サイクル2（x日）が指定されている場合、支払サイクル2（xヶ月後）も指定する必要があります。"),
                        "evalPHP" => "\$res=([[payment_cycle2_month]]!='');",
                        "evalJS" => "res=([[payment_cycle2_month]]!='');",
                    ),
                ),
            ),
            array(
                "column" => "payment_cycle2_day",
                "dependentColumn" => "payment_cycle2_month",
                "skipValidatePHP" => "[[payment_cycle2_month]]==''",
                "skipValidateJS" => "[[payment_cycle2_month]]==''",
                "validate" => array(
                    array(
                        "cat" => "eval",
                        "msg" => _g("支払サイクル2（xヶ月後）が指定されている場合、支払サイクル2（x日）も指定する必要があります。"),
                        "evalPHP" => "\$res=([[payment_cycle2_day]]!='');",
                        "evalJS" => "res=([[payment_cycle2_day]]!='');",
                    ),
                ),
            ),
            array(
                "column" => "delivery_port",
                "convert" => array(
                    array(
                        "cat" => "nullBlankToValue",
                        "param" => "",
                    ),
                ),
            ),
            array(
                "column" => "dropdown_flag",
                "convert" => array(
                    array(
                        "cat" => "nullBlankToValue",
                        "param" => "false",
                    ),
                ),
            ),
            array(
                "column" => "template_delivery",
                "skipValidatePHP" => "$1==''",
                "skipValidateJS" => "$1==''",
                "validate" => array(
                    array(
                        "cat" => "selectString",
                        "msg" => _g('帳票（納品書）が正しくありません。'),
                        "param" => array($templates[0]),
                    ),
                ),
            ),
            array(
                "column" => "template_bill",
                "skipValidatePHP" => "$1==''",
                "skipValidateJS" => "$1==''",
                "validate" => array(
                    array(
                        "cat" => "selectString",
                        "msg" => _g('帳票（請求書）が正しくありません。'),
                        "param" => array($templates[1]),
                    ),
                ),
            ),
            array(
                "column" => "template_partner_order",
                "skipValidatePHP" => "$1==''",
                "skipValidateJS" => "$1==''",
                "validate" => array(
                    array(
                        "cat" => "selectString",
                        "msg" => _g('帳票（注文書）が正しくありません。'),
                        "param" => array($templates[2]),
                    ),
                ),
            ),
            array(
                "column" => "template_subcontract",
                "skipValidatePHP" => "$1==''",
                "skipValidateJS" => "$1==''",
                "validate" => array(
                    array(
                        "cat" => "selectString",
                        "msg" => _g('帳票（外製指示書）が正しくありません。'),
                        "param" => array($templates[3]),
                    ),
                ),
            ),
        );

        return $columns;
    }

    protected function _regist(&$param, $isFirstRegist)
    {
        global $gen_db;

        if (isset($param['customer_id']) && is_numeric($param['customer_id'])) {
            $key = array("customer_id" => $param['customer_id']);
        } else {
            $key = null;
        }
        $data = array(
            'customer_no' => $param['customer_no'],
            'customer_name' => $param['customer_name'],
            'classification' => $param['classification'],
            'end_customer' => @$param['end_customer'],
            'customer_group_id_1' => @$param['customer_group_id_1'],
            'customer_group_id_2' => @$param['customer_group_id_2'],
            'customer_group_id_3' => @$param['customer_group_id_3'],
            'zip' => $param['zip'],
            'address1' => $param['address1'],
            'address2' => $param['address2'],
            'tel' => $param['tel'],
            'fax' => $param['fax'],
            'e_mail' => $param['e_mail'],
            'person_in_charge' => $param['person_in_charge'],
            'remarks' => @$param['remarks'],
            'remarks_2' => @$param['remarks_2'],
            'remarks_3' => @$param['remarks_3'],
            'remarks_4' => @$param['remarks_4'],
            'remarks_5' => @$param['remarks_5'],
            'rounding' => @$param['rounding'],
            'precision' => $param['precision'],
            'inspection_lead_time' => @$param['inspection_lead_time'],
            'currency_id' => @$param['currency_id'],
            'report_language' => @$param['report_language'],
            'bill_pattern' => @$param['bill_pattern'],
            'bill_customer_id' => @$param['bill_customer_id'],
            'monthly_limit_date' => $param['monthly_limit_date'],
            'tax_category' => @$param['tax_category'],
            'price_percent' => @$param['price_percent'],
            'price_percent_group_id' => @$param['price_percent_group_id'],
            'opening_balance' => @$param['opening_balance'],
            'opening_date' => @$param['opening_date'],
            'credit_line' => @$param['credit_line'],
            'receivable_cycle1' => @$param['receivable_cycle1'],
            'receivable_cycle2_month' => @$param['receivable_cycle2_month'],
            'receivable_cycle2_day' => @$param['receivable_cycle2_day'],
            'default_lead_time' => @$param['default_lead_time'],
            'delivery_port' => $param['delivery_port'],
            'payment_opening_balance' => @$param['payment_opening_balance'],
            'payment_opening_date' => @$param['payment_opening_date'],
            'payment_cycle1' => @$param['payment_cycle1'],
            'payment_cycle2_month' => @$param['payment_cycle2_month'],
            'payment_cycle2_day' => @$param['payment_cycle2_day'],
            'dropdown_flag' => @$param['dropdown_flag'],
            'template_delivery' => @$param['template_delivery'],
            'template_bill' => @$param['template_bill'],
            'template_partner_order' => @$param['template_partner_order'],
            'template_subcontract' => @$param['template_subcontract'],
        );
        $gen_db->updateOrInsert('customer_master', $key, $data);

        // id(keyColumnの値)を戻す。keyColumnがないModelではfalseを戻す。
        if (isset($key)) {
            $key = $param['customer_id'];
        } else {
            $key = $gen_db->getSequence("customer_master_customer_id_seq");
        }
        return $key;
    }

}
