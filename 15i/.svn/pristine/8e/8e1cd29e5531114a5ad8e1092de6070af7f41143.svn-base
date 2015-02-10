<?php

// テストクラス共通処理。先頭に書くこと
require_once dirname(__FILE__) . '/../TestCommon.class.php';

require_once dirname(__FILE__) . '/../../Logic/Delivery.class.php';
require_once dirname(__FILE__) . '/../../Logic/Accepted.class.php';
require_once dirname(__FILE__) . '/../../Logic/Payment.class.php';
require_once dirname(__FILE__) . '/../../Components/Math.class.php';

class BuyAmountTest extends PHPUnit_Framework_TestCase {

    protected $object;

    protected function setUp() {
        $this->object = new Logic_Delivery;

        global $gen_db;
        $gen_db->begin();         // コミットはしないこと
    }

    protected function tearDown() {
        global $gen_db;
        $gen_db->rollback();
    }

    // ag.cgi?page=BulletinView&bid=30460&gid=0&cid=377 の「売掛買掛テスト結果.xlsx」をもとにした買掛総合テスト
    public function testDeliveryBill() {
        global $gen_db; // TestCommonで準備される

        var_dump('--- 1月 ---');
        self::_buyAmountTestCore(1000.1, 1000, 1000.3, 1000, -1000);
        var_dump('--- 2月 ---');
        self::_buyAmountTestCore(1000.3, 1000.1, 1000.1, 1000.1, -1000.1);
        var_dump('--- 3月 ---');
        self::_buyAmountTestCore(1000.1, 1000.4, 1000.4, 1000.4, -1000.4);
        var_dump('--- 4月 ---');
        self::_buyAmountTestCore(1000.4, 1000.5, 1000.1, 1000.5, -1000.5);
        var_dump('--- 5月 ---');
        self::_buyAmountTestCore(1000.1, 1000.9, 1000.1, 1000.9, -1000.9);
        var_dump('--- 6月 ---');
        self::_buyAmountTestCore(1000.003, 1000.001, 1000.001, 1000.001, -1000.001);
        var_dump('--- 7月 ---');
        self::_buyAmountTestCore(1000.001, 1000.004, 1000.001, 1000.004, -1000.004);
        var_dump('--- 8月 ---');
        self::_buyAmountTestCore(1000.004, 1000.005, 1000.002, 1000.005, -1000.005);
        var_dump('--- 9月 ---');
        self::_buyAmountTestCore(1000.002, 1000.009, 1000.009, 1000.009, -1000.009);
    }

    // さまざまなパターンにおいて、納品書や請求書の数値が期待値（計算値）どおりになるかどうかをチェックする。
    private function _buyAmountTestCore($orderPrice11, $orderPrice12, $orderPrice21, $orderPrice31, $orderPrice41) {
        $testNo = 1;
        $taxRate = (Logic_Tax::getTaxRate(date('Y-m-d')) / 100);

        for($currencyMode=0; $currencyMode<=1; $currencyMode++) {
            switch($currencyMode) {
                case 0: $currency = null; $currencyStr = "JPY"; break;   // 基軸通貨
                case 1: $currency = 1; $currencyStr = "USD"; break;      // 外貨
            }
            for($precisionMode=0; $precisionMode<=1; $precisionMode++) {
                switch($precisionMode) {
                    case 0: $precision = 0; $precisionStr = "precision 0"; break;     // 小数点以下0桁
                    case 1: $precision = 2; $precisionStr = "precision 2"; break;     // 小数点以下2桁
                }
                for($roundMode=0; $roundMode<=2; $roundMode++) {
                    switch($roundMode) {
                        case 0: $round = 'round'; $roundStr = "round"; break;     // 四捨五入
                        case 1: $round = 'floor'; $roundStr = "floor"; break;     // 切り捨て
                        case 2: $round = 'ceil'; $roundStr = "ceil"; break;      // 切り上げ
                    }
                    for($taxClassMode=0; $taxClassMode<=1; $taxClassMode++) {
                        switch($taxClassMode) {
                            case 0: $taxClass = 0; $taxClassStr = "tax"; break;     // 課税
                            case 1: $taxClass = 1; $taxClassStr = "no tax"; break;     // 非課税
                        }

                        // 期待値を算出する

                        // 期待値1：月間合計受入金額（税別）
                        //  ※金額は常に受入明細行ごとに丸められる
                        //  ※Gen_Math::round() 自体のテストは別のテストクラスで行なっているので、信頼してよい
                        $answer1 =
                            Gen_Math::round($orderPrice11, $round, $precision)
                            + Gen_Math::round($orderPrice12, $round, $precision)
                            + Gen_Math::round($orderPrice21, $round, $precision)
                            + Gen_Math::round($orderPrice31, $round, $precision)
                            + Gen_Math::round($orderPrice41, $round, $precision);

                        // 期待値2：月間合計税額
                        $am11 = Gen_Math::round($orderPrice11, $round, $precision);
                        $am12 = Gen_Math::round($orderPrice12, $round, $precision);
                        $am21 = Gen_Math::round($orderPrice21, $round, $precision);
                        $am31 = Gen_Math::round($orderPrice31, $round, $precision);
                        $am41 = Gen_Math::round($orderPrice41, $round, $precision);
                        if ($taxClass==0 && $currency == null) {
                            $tax = Gen_Math::round($am11 * $taxRate, $round, $precision)
                                + Gen_Math::round($am12 * $taxRate, $round, $precision)
                                + Gen_Math::round($am21 * $taxRate, $round, $precision)
                                + Gen_Math::round($am31 * $taxRate, $round, $precision)
                                + Gen_Math::round($am41 * $taxRate, $round, $precision);
                        } else {
                            $tax = 0;
                        }
                        $answer2 = $tax;

                        var_dump("No: {$testNo}, {$currencyStr}, {$precisionStr}, {$roundStr}, {$taxClassStr}");
                        $testNo++;

                        // 引数の最後の2つが期待値。 _buyAmountTestCore2の引数リストを参照
                        self::_buyAmountTestCore2(
                                $testNo++,
                                $precision,
                                $round,
                                $taxClass,
                                $currency,
                                $orderPrice11,
                                $orderPrice12,
                                $orderPrice21,
                                $orderPrice31,
                                $orderPrice41,
                                $answer1,
                                $answer2
                         );
                    }
                }
            }
        }
    }

    // $testNo
    // $precision   取引先マスタ　小数点以下の桁数
    // $round       取引先マスタ　端数処理  空欄: なし, round: 四捨五入, floor: 切捨, ceil: 切上
    // $taxClass    品目マスタ　　課税区分　0:課税、1:非課税
    // $rate        外貨のレート（基軸通貨の場合はnull）
    // $orderPrice11  発注1件目1行目　単価
    // $orderPrice12  発注1件目2行目　単価
    // $orderPrice21  発注2件目1行目　単価
    // $orderPrice31  発注3件目1行目　単価
    // $orderPrice41  発注4件目1行目　単価
    // $acceptedAnswer  期待値：月間合計受入金額（税別）
    // $taxAnswer      期待値：月間合計税額
    private function _buyAmountTestCore2($testNo, $precision, $round, $taxClass, $rate,
            $orderPrice11, $orderPrice12, $orderPrice21, $orderPrice31, $orderPrice41,
            $acceptedAnswer, $taxAnswer
            ) {
        global $gen_db;

        $orderDate = date('Y-m-d');

        // 品目
        $itemId = TestCommon::makeItem(array('tax_class'=>$taxClass));

        // 外貨
        if ($rate == null) {
            $currencyId = null;
        } else {
            $currencyId = TestCommon::makeCurrencyAndRate(array(), '1970-01-01', 1);
        }

        // サプライヤー
        $custId = TestCommon::makeCustomer(array('classification'=>1, 'rounding'=>$round, 'precision'=>$precision, 'currency_id' => $currencyId));

        // 発注1件目。同時に受入を登録
        $headerId_1 = TestCommon::makeOrder(array(
            // 明細1行目:
            array(
                'partner_id' => $custId
                ,'order_date' => $orderDate
                ,'order_detail_dead_line' => $orderDate
                ,'item_id' => $itemId
                ,'order_detail_quantity' => 1
                ,'item_price' => $orderPrice11
                ,'accepted_regist' => 'true'
                ),
            // 明細2行目
            array(
                'partner_id' => $custId
                ,'order_date' => $orderDate
                ,'order_detail_dead_line' => $orderDate
                ,'item_id' => $itemId
                ,'order_detail_quantity' => 1
                ,'item_price' => $orderPrice12
                ,'accepted_regist' => 'true'
                ),
            ));

        // 発注2件目。同時に受入を登録
        $headerId_2 = TestCommon::makeOrder(array(
            'partner_id' => $custId
            ,'order_date' => $orderDate
            ,'order_detail_dead_line' => $orderDate
            ,'item_id' => $itemId
            ,'order_detail_quantity' => 1
            ,'item_price' => $orderPrice21
            ,'accepted_regist' => 'true'
        ));

        // 発注3件目。同時に受入を登録
        $headerId_3 = TestCommon::makeOrder(array(
            'partner_id' => $custId
            ,'order_date' => $orderDate
            ,'order_detail_dead_line' => $orderDate
            ,'item_id' => $itemId
            ,'order_detail_quantity' => 1
            ,'item_price' => $orderPrice31
            ,'accepted_regist' => 'true'
        ));

        // 発注4件目。同時に受入を登録
        $headerId_4 = TestCommon::makeOrder(array(
            'partner_id' => $custId
            ,'order_date' => $orderDate
            ,'order_detail_dead_line' => $orderDate
            ,'item_id' => $itemId
            ,'order_detail_quantity' => 1
            ,'item_price' => $orderPrice41
            ,'accepted_regist' => 'true'
        ));

        // ***** 結果確認 *****

        // 受入日基準にする
        $query = "update company_master set payment_report_timing ='0'";
        $gen_db->query($query);

        $fromDate = date('Y-m-01');
        $toDate = date('Y-m-t');
        $yenMode = false;
        Logic_Payment::createTempPaymentTable($fromDate, $toDate, $yenMode);

        $query = "
        select
            sum(accepted_amount) as accepted_amount
            ,sum(accepted_tax) as accepted_tax
        from
            temp_payment
            inner join customer_master on temp_payment.customer_no = customer_master.customer_no
        where
            customer_master.customer_id = '{$custId}'
        ";
        $res = $gen_db->queryOneRowObject($query);

        // 月間合計受入金額（税別）
        $this->assertEquals((string)$acceptedAnswer, $res->accepted_amount);

        // 月間合計税額
        $this->assertEquals((string)$taxAnswer, $res->accepted_tax);
    }

}