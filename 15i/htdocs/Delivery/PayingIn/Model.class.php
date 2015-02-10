<?php

@define('ROW_NUM', 10);

class Delivery_PayingIn_Model extends Base_ModelBase
{

    protected function _getKeyColumn()
    {
        return 'paying_in_id';
    }

    protected function _setDefault(&$param, $entryMode)
    {
        switch ($entryMode) {
            case "csv":
                // コード => ID
                self::_codeToId($param, "customer_no", "customer_id", "", "", "customer_master");
                self::_codeToId($param, "bill_number", "bill_header_id", "", "", "bill_header");
                break;
        }
    }

    protected function _getColumns()
    {
        $columns = array(
            array(
                "column" => "paying_in_id",
                "pattern" => "id",
            ),
            array(
                "column" => "paying_in_date",
                "validate" => array(
                    array(
                        "cat" => "salesLockDateOrLater",
                        "msg" => _g('日付')
                    ),
                ),
            ),
            array(
                "column" => "customer_id",
                "pattern" => "customer_id_required",
                "label" => _g("得意先"),
                "addwhere" => "classification=0",
            ),
            array(
                "column" => "customer_id",
                "validate" => array(
                    array(
                        "cat" => "notExistRecord",
                        "msg" => _g('取引先マスタで この得意先に対する「請求先」が設定されているため、登録を行えません。入金処理は請求先に対して行ってください。'),
                        "skipHasError" => true,
                        "param" => "select bill_customer_id from customer_master where customer_id = $1 and bill_customer_id is not null and bill_customer_id <> $1"
                    ),
                    array(
                        "cat" => "notExistRecord",
                        "msg" => _('この得意先に対して「締め請求」の請求書を発行済みです。入金日を変えるか請求書を削除してから登録してください。'),
                        "skipHasError" => true,
                        "param" => "select max(close_date) from bill_header
                            where bill_header.customer_id = $1 and bill_pattern = 1 having max(close_date) >= [[paying_in_date]] "
                    ),
                ),
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
                "column" => "bill_header_id",
                "pattern" => "id",
                "skipValidatePHP" => "[[paying_in_id]]==''", // 新規モードではスキップ
                "skipValidateJS" => "$1==undefined", // 新規モードではスキップ（項目が存在しない）
            ),
            array(
                "column" => "amount",
                "convert" => array(
                    array(
                        "cat" => "strToNum",
                    ),
                ),
                "skipValidatePHP" => "[[paying_in_id]]==''", // 新規モードではスキップ
                "skipValidateJS" => "$1==undefined", // 新規モードではスキップ（項目が存在しない）
                "validate" => array(
                    array(
                        "cat" => "numeric",
                        "msg" => _g('金額が正しくありません。')
                    ),
                ),
            ),
            array(
                "column" => "way_of_payment",
                "skipValidatePHP" => "[[paying_in_id]]==''", // 新規モードではスキップ
                "skipValidateJS" => "$1==undefined", // 新規モードではスキップ（項目が存在しない）
                "validate" => array(
                    array(
                        "cat" => "selectString",
                        "msg" => _g("入金種別が正しくありません。"),
                        "param" => array(Gen_Option::getWayOfPayment('model'))
                    ),
                ),
            ),
        );

        for ($i = 1; $i <= ROW_NUM; $i++) {
            // 新規モードのみ
            $columns[] = array(
                "column" => "bill_header_id_{$i}",
                "pattern" => "id",
                "skipValidatePHP" => "[[paying_in_id]]!=''", // 更新モードではスキップ
                "skipValidateJS" => "$1==undefined", // 更新モードではスキップ（項目が存在しない）
            );
            $columns[] = array(
                "column" => "bill_header_id_{$i}",
                "skipValidatePHP" => "[[paying_in_id]]!=''||$1==''", // 更新モードではスキップ
                "skipValidateJS" => "$1==undefined", // 更新モードではスキップ（項目が存在しない）
                "validate" => array(
                    array(
                        "cat" => "existRecord",
                        "msg" => sprintf(_g("請求書番号%sが、この得意先に対する請求書ではありません。"), $i),
                        "skipHasError" => true,
                        "param" => "select bill_header_id from bill_header where bill_header_id = $1 and customer_id = [[customer_id]]"
                    ),
                ),
            );
            $columns[] = array(
                "column" => "amount_{$i}",
                "convert" => array(
                    array(
                        "cat" => "strToNum",
                    ),
                ),
                "skipValidatePHP" => "[[paying_in_id]]!=''", // 更新モードではスキップ
                "skipValidateJS" => "$1==undefined", // 更新モードではスキップ（項目が存在しない）
                "validate" => array(
                    array(
                        "cat" => "blankOrNumeric",
                        "msg" => _g("金額{$i}が正しくありません。")
                    ),
                ),
            );
            $columns[] = array(
                "column" => "way_of_payment_{$i}",
                "skipValidatePHP" => "[[paying_in_id]]!=''||[[amount_{$i}]]==''", // 更新モードではスキップ
                "skipValidateJS" => "$1==undefined||[[amount_{$i}]]==''", // 更新モードではスキップ（項目が存在しない）
                "validate" => array(
                    array(
                        "cat" => "selectString",
                        "msg" => _g("入金種別{$i}が正しくありません。"),
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

        if (!isset($param['paying_in_id'])) {
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
                $data = array(
                    'paying_in_date' => $param['paying_in_date'],
                    'customer_id' => $param['customer_id'],
                    'foreign_currency_id' => $currencyId,
                    'foreign_currency_rate' => $foreignCurrencyRate,
                    'way_of_payment' => $param["way_of_payment"],
                    'amount' => $amount,
                    'foreign_currency_amount' => $foreignCurrencyAmount,
                    'bill_header_id' => (isset($param['bill_header_id']) && is_numeric($param['bill_header_id']) ? $param['bill_header_id'] : 0),
                    'remarks' => $param["remarks"],
                );
                $gen_db->insert("paying_in", $data);
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
                    $data = array(
                        'paying_in_date' => $param['paying_in_date'],
                        'customer_id' => $param['customer_id'],
                        'foreign_currency_id' => $currencyId,
                        'foreign_currency_rate' => $foreignCurrencyRate,
                        'way_of_payment' => $param["way_of_payment_{$i}"],
                        'amount' => $amount,
                        'foreign_currency_amount' => $foreignCurrencyAmount,
                        'bill_header_id' => (isset($param["bill_header_id_{$i}"]) && is_numeric($param["bill_header_id_{$i}"]) ? $param["bill_header_id_{$i}"] : 0),
                        'remarks' => $param["remarks_{$i}"],
                    );
                    $gen_db->insert("paying_in", $data);
                }
            }
        } else {
            // 更新モード（1行・更新）
            
            // この入金を含む締め請求の請求書が発行済の場合、更新できるのは備考のみ。
            // （画面上では備考以外を変更できないように制御しているが、念のためここでもチェックする）
            if (Logic_Bill::hasBillByPayingInId($param['paying_in_id'])) {
                $data = array(
                    'remarks' => $param['remarks'],
                );
            } else {
                $amount = $param["amount"];
                $foreignCurrencyRate = null;
                $foreignCurrencyAmount = null;
                if ($currencyId != null) {
                    $foreignCurrencyRate = $param['foreign_currency_rate'];
                    $foreignCurrencyAmount = $amount;
                    // 入力された値はまるめないが、円換算時にはまるめを行う
                    $amount = Logic_Customer::round(Gen_Math::mul($amount, $foreignCurrencyRate), $param['customer_id']);
                }
                $data = array(
                    'paying_in_date' => $param['paying_in_date'],
                    'customer_id' => $param['customer_id'],
                    'foreign_currency_id' => $currencyId,
                    'foreign_currency_rate' => $foreignCurrencyRate,
                    'way_of_payment' => $param['way_of_payment'],
                    'amount' => $amount,
                    'foreign_currency_amount' => $foreignCurrencyAmount,
                    'bill_header_id' => (isset($param['bill_header_id']) && is_numeric($param['bill_header_id']) ? $param['bill_header_id'] : 0),
                    'remarks' => $param['remarks'],
                );
            }
            $where = "paying_in_id={$param['paying_in_id']}";
            $gen_db->update("paying_in", $data, $where);
        }
        
        // id(keyColumnの値)を戻す。keyColumnがないModelではfalseを戻す。
        if (isset($param['paying_in_id'])) {
            $keyValue = $param['paying_in_id'];
        } else {
            $keyValue = $gen_db->getSequence("paying_in_paying_in_id_seq");
        }
        return $keyValue;
    }

}
