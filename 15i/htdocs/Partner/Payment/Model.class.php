<?php

@define('ROW_NUM', 10);

class Partner_Payment_Model extends Base_ModelBase
{

    protected function _getKeyColumn()
    {
        return 'payment_id';
    }

    protected function _setDefault(&$param, $entryMode)
    {
        switch ($entryMode) {
            case "csv":
                // コード => ID
                self::_codeToId($param, "customer_no", "customer_id", "", "", "customer_master");
                break;
        }
    }

    protected function _getColumns()
    {
        $columns = array(
            array(
                "column" => "payment_id",
                "pattern" => "id",
            ),
            array(
                "column" => "payment_date",
                "validate" => array(
                    array(
                        "cat" => "buyLockDateOrLater",
                        "msg" => _g('日付')
                    ),
                ),
            ),
            array(
                "column" => "customer_id",
                "pattern" => "customer_id_required",
                "label" => _g("発注先"),
                "addwhere" => "classification=1",
            ),
            array(
                "column" => "foreign_currency_rate",
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
                        "msg" => _g('レートが正しくありません。')
                    ),
                ),
            ),
            // 更新モードのみ
            array(
                "column" => "amount",
                "convert" => array(
                    array(
                        "cat" => "strToNum",
                    ),
                ),
                "skipValidatePHP" => "[[payment_id]]==''", // 新規モードではスキップ
                "skipValidateJS" => "$1==undefined", // 新規モードではスキップ（項目が存在しない）
                "validate" => array(
                    array(
                        "cat" => "numeric",
                        "msg" => _g('金額が正しくありません。')
                    ),
                ),
            ),
            array(
                "column" => "adjust_amount",
                "convert" => array(
                    array(
                        "cat" => "strToNum",
                    ),
                    array(
                        "cat" => "nullBlankToValue",
                        "param" => '0'
                    ),
                ),
                "skipValidatePHP" => "[[payment_id]]==''", // 新規モードではスキップ
                "skipValidateJS" => "$1==undefined", // 新規モードではスキップ（項目が存在しない）
                "validate" => array(
                    array(
                        "cat" => "numeric",
                        "msg" => _g('調整金額が正しくありません。')
                    ),
                ),
            ),
            array(
                "column" => "way_of_payment",
                "skipValidatePHP" => "[[payment_id]]==''", // 新規モードではスキップ
                "skipValidateJS" => "$1==undefined", // 新規モードではスキップ（項目が存在しない）
                "validate" => array(
                    array(
                        "cat" => "selectString",
                        "msg" => _g('支払種別が正しくありません。'),
                        "param" => array(Gen_Option::getWayOfPayment('model'))
                    ),
                ),
            ),
        );

        for ($i = 1; $i <= ROW_NUM; $i++) {
            // 新規モードのみ
            $columns[] = array(
                "column" => "amount_{$i}",
                "convert" => array(
                    array(
                        "cat" => "strToNum",
                    ),
                ),
                "skipValidatePHP" => "[[payment_id]]!=''", // 更新モードではスキップ
                "skipValidateJS" => "$1==undefined", // 更新モードではスキップ（項目が存在しない）
                "validate" => array(
                    array(
                        "cat" => "blankOrNumeric",
                        "msg" => _g("金額{$i}が正しくありません。")
                    ),
                ),
            );
            $columns[] = array(
                "column" => "adjust_amount_{$i}",
                "convert" => array(
                    array(
                        "cat" => "strToNum",
                    ),
                    array(
                        "cat" => "nullBlankToValue",
                        "param" => '0'
                    ),
                ),
                "skipValidatePHP" => "[[payment_id]]!=''", // 更新モードではスキップ
                "skipValidateJS" => "$1==undefined", // 更新モードではスキップ（項目が存在しない）
                "validate" => array(
                    array(
                        "cat" => "numeric",
                        "msg" => _g("調整金額{$i}が正しくありません。")
                    ),
                ),
            );
            $columns[] = array(
                "column" => "way_of_payment_{$i}",
                "skipValidatePHP" => "[[payment_id]]!=''||[[amount_{$i}]]==''", // 更新モードではスキップ
                "skipValidateJS" => "$1==undefined||[[amount_{$i}]]==''", // 更新モードではスキップ（項目が存在しない）
                "validate" => array(
                    array(
                        "cat" => "selectString",
                        "msg" => _g("支払種別{$i}が正しくありません。"),
                        "param" => array(Gen_Option::getWayOfPayment('model'))
                    ),
                ),
            );
        }

        return $columns;
    }

    protected function _regist(&$param, $isFirstRegist)
    {
        global $gen_db;

        // 外貨処理
        $query = "select currency_id from customer_master where customer_id = '{$param['customer_id']}'";
        $currencyId = $gen_db->queryOneValue($query);

        if (!isset($param['payment_id'])) {
            if (isset($param['amount'])) {
                // CSVモード（1行・新規）
                $amount = $param["amount"];
                $foreignCurrencyRate = null;
                $foreignCurrencyAmount = null;
                if ($currencyId != null) {
                    $foreignCurrencyRate = $param['foreign_currency_rate'];
                    $foreignCurrencyAmount = $amount;
                    // 入力された値はまるめないが、円換算時にはまるめを行う
                    $amount = Logic_Customer::round(Gen_Math::mul($amount, $foreignCurrencyRate), $param['customer_id']);
                }
                $adjustAmount = $param["adjust_amount"];
                $foreignCurrencyAdjustAmount = null;
                if ($currencyId != null) {
                    $foreignCurrencyAdjustAmount = $adjustAmount;
                    $adjustAmount = Logic_Customer::round(Gen_Math::mul($adjustAmount, $foreignCurrencyRate), $param['customer_id']);
                }
                $data = array(
                    'payment_date' => $param['payment_date'],
                    'customer_id' => $param['customer_id'],
                    'foreign_currency_id' => $currencyId,
                    'foreign_currency_rate' => $foreignCurrencyRate,
                    'way_of_payment' => $param["way_of_payment"],
                    'amount' => $amount,
                    'foreign_currency_amount' => $foreignCurrencyAmount,
                    'adjust_amount' => $adjustAmount,
                    'foreign_currency_adjust_amount' => $foreignCurrencyAdjustAmount,
                    'remarks' => $param["remarks"],
                );
                $gen_db->insert("payment", $data);
            } else {
                // 新規モード（複数行・新規）
                for ($i = 1; $i <= ROW_NUM; $i++) {
                    if (!is_numeric($param["amount_{$i}"])) {
                        continue;
                    }
                    if (!Gen_String::isNumericEx($param["way_of_payment_{$i}"], 1, ROW_NUM)) {
                        continue;
                    }
                    $amount = $param["amount_{$i}"];
                    $foreignCurrencyRate = null;
                    $foreignCurrencyAmount = null;
                    if ($currencyId != null) {
                        $foreignCurrencyRate = $param['foreign_currency_rate'];
                        $foreignCurrencyAmount = $amount;
                        // 入力された値はまるめないが、円換算時にはまるめを行う
                        $amount = Logic_Customer::round(Gen_Math::mul($amount, $foreignCurrencyRate), $param['customer_id']);
                    }
                    $adjustAmount = $param["adjust_amount_{$i}"];
                    $foreignCurrencyAdjustAmount = null;
                    if ($currencyId != null) {
                        $foreignCurrencyAdjustAmount = $adjustAmount;
                        $adjustAmount = Logic_Customer::round(Gen_Math::mul($adjustAmount, $foreignCurrencyRate), $param['customer_id']);
                    }
                    $data = array(
                        'payment_date' => $param['payment_date'],
                        'customer_id' => $param['customer_id'],
                        'foreign_currency_id' => $currencyId,
                        'foreign_currency_rate' => $foreignCurrencyRate,
                        'way_of_payment' => $param["way_of_payment_{$i}"],
                        'amount' => $amount,
                        'foreign_currency_amount' => $foreignCurrencyAmount,
                        'adjust_amount' => $adjustAmount,
                        'foreign_currency_adjust_amount' => $foreignCurrencyAdjustAmount,
                        'remarks' => $param["remarks_{$i}"],
                    );
                    $gen_db->insert("payment", $data);
                }
            }
        } else {
            // 更新モード（1行・更新）
            $amount = $param["amount"];
            $foreignCurrencyRate = null;
            $foreignCurrencyAmount = null;
            if ($currencyId != null) {
                $foreignCurrencyRate = $param['foreign_currency_rate'];
                $foreignCurrencyAmount = $amount;
                // 入力された値はまるめないが、円換算時にはまるめを行う
                $amount = Logic_Customer::round(Gen_Math::mul($amount, $foreignCurrencyRate), $param['customer_id']);
            }
            $adjustAmount = $param["adjust_amount"];
            $foreignCurrencyAdjustAmount = null;
            if ($currencyId != null) {
                $foreignCurrencyAdjustAmount = $adjustAmount;
                $adjustAmount = Logic_Customer::round(Gen_Math::mul($adjustAmount, $foreignCurrencyRate), $param['customer_id']);
            }
            $data = array(
                'payment_date' => $param['payment_date'],
                'customer_id' => $param['customer_id'],
                'foreign_currency_id' => $currencyId,
                'foreign_currency_rate' => $foreignCurrencyRate,
                'way_of_payment' => $param['way_of_payment'],
                'amount' => $amount,
                'foreign_currency_amount' => $foreignCurrencyAmount,
                'adjust_amount' => $adjustAmount,
                'foreign_currency_adjust_amount' => $foreignCurrencyAdjustAmount,
                'remarks' => $param['remarks'],
            );
            $where = "payment_id={$param['payment_id']}";
            $gen_db->update("payment", $data, $where);
        }
        
        // id(keyColumnの値)を戻す。keyColumnがないModelではfalseを戻す。
        if (isset($param['payment_id'])) {
            $keyValue = $param['payment_id'];
        } else {
            $keyValue = $gen_db->getSequence("payment_payment_id_seq");
        }
        return $keyValue;
    }

}
