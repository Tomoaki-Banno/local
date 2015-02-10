<?php

class Master_Company_Model extends Base_ModelBase
{

    protected function _getKeyColumn()
    {
        // キー項目がない場合、常に新規として扱いたければtrue、修正として扱いたければfalseを返す。
        return false;
    }

    protected function _setDefault(&$param, $entryMode)
    {
    }

    protected function _getColumns()
    {
        $columns = array(
            array(
                "column" => "company_name",
                "validate" => array(
                    array(
                        "cat" => "required",
                        "msg" => _g('会社名は必ず入力してください。'),
                    ),
                ),
            ),
            array(
                "column" => "starting_month_of_accounting_period",
                "convert" => array(
                    array(
                        "cat" => "strToNum",
                    ),
                ),
                "validate" => array(
                    array(
                        "cat" => "selectString",
                        "msg" => _g('年度開始月が正しくありません。'),
                        "param" => array(array(1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12)),
                    ),
                ),
            ),
            array(
                "column" => "password_minimum_length",
                "convert" => array(
                    array(
                        "cat" => "strToNum",
                    ),
                ),
                "validate" => array(
                    array(
                        "cat" => "minNum",
                        "msg" => _g('ユーザーパスワードの最低桁数が正しくありません。'),
                        "param" => 0,
                    ),
                    array(
                        "cat" => "integer",
                        "msg" => _g('ユーザーパスワードの最低桁数には整数を指定してください。'),
                    ),
                ),
            ),
            array(
                "column" => "password_valid_until",
                "convert" => array(
                    array(
                        "cat" => "strToNum",
                    ),
                ),
                "validate" => array(
                    array(
                        "cat" => "minNum",
                        "msg" => _g('ユーザーパスワードの有効期間が正しくありません。'),
                        "param" => 0,
                    ),
                    array(
                        "cat" => "integer",
                        "msg" => _g('ユーザーパスワードの有効期間には整数を指定してください。'),
                    ),
                ),
            ),
            array(
                "column" => "account_lockout_threshold",
                "convert" => array(
                    array(
                        "cat" => "strToNum",
                    ),
                ),
                "validate" => array(
                    array(
                        "cat" => "minNum",
                        "msg" => _g('ログイン失敗回数の上限が正しくありません。'),
                        "param" => 0,
                    ),
                    array(
                        "cat" => "integer",
                        "msg" => _g('ログイン失敗回数の上限には整数を指定してください。'),
                    ),
                ),
            ),
            array(
                "column" => "account_lockout_reset_minute",
                "convert" => array(
                    array(
                        "cat" => "strToNum",
                    ),
                ),
                "validate" => array(
                    array(
                        "cat" => "minNum",
                        "msg" => _g('ﾛｸﾞｲﾝ失敗回数のﾘｾｯﾄが正しくありません。'),
                        "param" => 0,
                    ),
                    array(
                        "cat" => "integer",
                        "msg" => _g('ﾛｸﾞｲﾝ失敗回数のﾘｾｯﾄには整数を指定してください。'),
                    ),
                ),
            ),
            array(
                "column" => "stock_price_assessment",
                "validate" => array(
                    array(
                        "cat" => "selectString",
                        "msg" => _g('在庫評価法が正しくありません。'),
                        "param" => array(array(0, 1, 2)),
                    ),
                ),
            ),
            array(
                "column" => "assessment_rounding",
                "validate" => array(
                    array(
                        "cat" => "selectString",
                        "msg" => _g('在庫評価端数処理が正しくありません。'),
                        "param" => array(array('round', 'floor', 'ceil')),
                    ),
                ),
            ),
            array(
                "column" => "assessment_precision",
                "convert" => array(
                    array(
                        "cat" => "strToNum",
                    ),
                ),
                "validate" => array(
                    // PHPの小数点の有効桁数を考慮して、小数点以下は6桁までとする。
                    array(
                        "cat" => "selectString",
                        "msg" => _g('在庫評価の小数点以下桁数は0から6の数値で指定してください。'),
                        "param" => array(array(0, 1, 2, 3, 4, 5, 6)),
                    ),
                ),
            ),
            array(
                "column" => "payout_timing",
                "validate" => array(
                    array(
                        "cat" => "selectString",
                        "msg" => _g('外製支給のタイミングが正しくありません。'),
                        "param" => array(array(0, 1)),
                    ),
                ),
            ),
            array(
                "column" => "receivable_report_timing",
                "validate" => array(
                    array(
                        "cat" => "selectString",
                        "msg" => _g('売上計上基準が正しくありません。'),
                        "param" => array(array(0, 1)),
                    ),
                ),
            ),
            array(
                "column" => "payment_report_timing",
                "validate" => array(
                    array(
                        "cat" => "selectString",
                        "msg" => _g('仕入計上基準が正しくありません。'),
                        "param" => array(array(0, 1)),
                    ),
                ),
            ),
            array(
                "column" => "key_currency",
                "converter" => array(
                    // 円は「￥」に統一。
                    array(
                        "cat" => "selectStrToValue",
                        "param" => array(array('\\'), array('￥')),
                    ),
                    array(
                        "cat" => "selectStrToValue",
                        "param" => array(array('円'), array('￥')),
                    ),
                ),
                "validate" => array(
                    array(
                        "cat" => "required",
                        "msg" => _g('基軸通貨は必ず指定してください。'),
                    ),
                    // 通貨マスタの重複チェック
                    array(
                        "cat" => "notExistRecord",
                        "msg" => _g('基軸通貨は通貨マスタにすでに登録されています。別の取引通貨を指定してください。'),
                        "skipHasError" => true,
                        "param" => "select currency_id from currency_master where currency_name = $1"
                    ),
                ),
            ),
            array(
                "column" => "excel_cell_join",
                "pattern" => "bool",
            ),
            array(
                "column" => "excel_color",
                "pattern" => "bool",
            ),
            array(
                "column" => "excel_date_type",
                "validate" => array(
                    array(
                        "cat" => "selectString",
                        "msg" => _g('Excel日付出力形式が正しくありません。'),
                        "param" => array(array(0, 1)),
                    ),
                ),
            ),
        );

        return $columns;
    }

    protected function _regist(&$param, $isFirstRegist)
    {
        global $gen_db;

        $data = array(
            'company_name' => $param['company_name'],
            'company_name_en' => $param['company_name_en'],
            'zip' => $param['zip'],
            'address1' => $param['address1'],
            'address1_en' => $param['address1_en'],
            'address2' => $param['address2'],
            'address2_en' => $param['address2_en'],
            'tel' => $param['tel'],
            'fax' => $param['fax'],
            'main_bank' => $param['main_bank'],
            'bank_account' => $param['bank_account'],
            //  company_master.max_lc は未使用になった。とりあえず0を入れておく
            'remarks' => $param['remarks'],
            'password_minimum_length' => $param['password_minimum_length'],
            'password_valid_until' => $param['password_valid_until'],
            'account_lockout_threshold' => $param['account_lockout_threshold'],
            'account_lockout_reset_minute' => $param['account_lockout_reset_minute'],
            'stock_price_assessment' => $param['stock_price_assessment'],
            'assessment_rounding' => $param['assessment_rounding'],
            'assessment_precision' => $param['assessment_precision'],
            'payout_timing' => $param['payout_timing'],
            'receivable_report_timing' => $param['receivable_report_timing'],
            'payment_report_timing' => $param['payment_report_timing'],
            'key_currency' => $param['key_currency'],
            'excel_cell_join' => $param['excel_cell_join'],
            'excel_color' => $param['excel_color'],
            'excel_date_type' => $param['excel_date_type'],
        );

        $where = "";
        $gen_db->update("company_master", $data, $where);

        // 自社名の即時反映
        $_SESSION["company_name"] = $param['company_name'];
        
        // 年度開始月は多くの画面で使用するため（日付期間セレクタで「年度」が選択された場合）、
        // DBではなく setting に保存する。
        $_SESSION['gen_setting_company']->starting_month_of_accounting_period = (int)$param['starting_month_of_accounting_period'];
        Gen_Setting::saveSetting();

        // id(keyColumnの値)を戻す。keyColumnがないModelではfalseを戻す。
        return false;
    }

}
