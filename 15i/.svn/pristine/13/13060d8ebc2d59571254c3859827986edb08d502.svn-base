<?php

// テストクラス共通処理。先頭に書くこと
require_once dirname(__FILE__) . '/../TestCommon.class.php';

require_once dirname(__FILE__) . '/../../Logic/Delivery.class.php';
require_once dirname(__FILE__) . '/../../Logic/Bill.class.php';
require_once dirname(__FILE__) . '/../../Components/Math.class.php';

class SalesAmountTest extends PHPUnit_Framework_TestCase {

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

    // ag.cgi?page=BulletinView&bid=30460&gid=0&cid=377 の「売掛買掛テスト結果.xlsx」をもとにした売掛総合テスト
    public function testSalesAmount() {
        global $gen_db; // TestCommonで準備される

        var_dump('--- 1月 ---');
        self::_salesAmountTestCore(1000.1, 1000, 1000.3, 1000, -1000);
        var_dump('--- 2月 ---');
        self::_salesAmountTestCore(1000.3, 1000.1, 1000.1, 1000.1, -1000.1);
        var_dump('--- 6月 ---');
        self::_salesAmountTestCore(1000.003, 1000.001, 1000.001, 1000.001, -1000.001);
        var_dump('--- 9月 ---');
        self::_salesAmountTestCore(1000.002, 1000.009, 1000.009, 1000.009, -1000.001);
    }

    // さまざまなパターンにおいて、納品書や請求書の数値が期待値（計算値）どおりになるかどうかをチェックする。
    private function _salesAmountTestCore($recPrice11, $recPrice12, $recPrice21, $recPrice31, $recPrice41) {
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
                for($taxCategoryMode=0; $taxCategoryMode<=2; $taxCategoryMode++) {
                    switch($taxCategoryMode) {
                        case 0: $taxCategory = 0; $taxStr = "per Bill"; break;     // 請求書単位
                        case 1: $taxCategory = 1; $taxStr = "per Delivery"; break;     // 納品書単位
                        case 2: $taxCategory = 2; $taxStr = "per DeliveryDetail"; break;     // 納品明細単位
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

                            // 期待値1：受注1件目の納品金額（税別）
                            //  ※金額は税計算単位（請求書単位・納品書単位など）にかかわらず、常に納品明細行ごとに丸められる
                            //  ※Gen_Math::round() 自体のテストは別のテストクラスで行なっているので、信頼してよい
                            $answer1 =
                                Gen_Math::round($recPrice11, $round, $precision)
                                + Gen_Math::round($recPrice12, $round, $precision);

                            // 期待値2：請求書の金額（税込）
                            //  ※金額は税計算単位（請求書単位・納品書単位など）にかかわらず、常に納品明細行ごとに丸められる
                            $am11 = Gen_Math::round($recPrice11, $round, $precision);
                            $am12 = Gen_Math::round($recPrice12, $round, $precision);
                            $am21 = Gen_Math::round($recPrice21, $round, $precision);
                            $am31 = Gen_Math::round($recPrice31, $round, $precision);
                            $am41 = Gen_Math::round($recPrice41, $round, $precision);
                            if ($taxClass==0 && $currency == null) {
                                switch($taxCategory) {
                                    case 0: // 請求書単位
                                        $tax = Gen_Math::round(($am11 + $am12 + $am21 + $am31 + $am41) * $taxRate, $round, $precision);
                                        break;
                                    case 1: // 納品書単位
                                        $tax = Gen_Math::round(($am11 + $am12) * $taxRate, $round, $precision)
                                            + Gen_Math::round($am21 * $taxRate, $round, $precision)
                                            + Gen_Math::round($am31 * $taxRate, $round, $precision)
                                            + Gen_Math::round($am41 * $taxRate, $round, $precision);
                                        break;
                                    case 2: // 納品明細単位
                                        $tax = Gen_Math::round($am11 * $taxRate, $round, $precision)
                                            + Gen_Math::round($am12 * $taxRate, $round, $precision)
                                            + Gen_Math::round($am21 * $taxRate, $round, $precision)
                                            + Gen_Math::round($am31 * $taxRate, $round, $precision)
                                            + Gen_Math::round($am41 * $taxRate, $round, $precision);
                                        break;
                                }
                            } else {
                                $tax = 0;
                            }
                            $answer2 = $am11 + $am12 + $am21 + $am31 + $am41 + $tax;
                            var_dump("No: {$testNo}, {$currencyStr}, {$precisionStr}, {$taxStr}, {$roundStr}, {$taxClassStr}");

                            // 引数の最後の2つが期待値。 _salesAmountTestCore2の引数リストを参照
                            self::_salesAmountTestCore2(
                                    $testNo++,
                                    $precision,
                                    $taxCategory,
                                    $round,
                                    $taxClass,
                                    $currency,
                                    $recPrice11,
                                    $recPrice12,
                                    $recPrice21,
                                    $recPrice31,
                                    $recPrice41,
                                    $answer1,
                                    $answer2
                             );
                        }
                    }
                }
            }
        }
    }

    // $testNo
    // $precision   取引先マスタ　小数点以下の桁数
    // $taxCategory 取引先マスタ　税計算単位 0: 請求書単位, 1: 納品書単位, 2: 納品明細単位
    // $round       取引先マスタ　端数処理  空欄: なし, round: 四捨五入, floor: 切捨, ceil: 切上
    // $taxClass    品目マスタ　　課税区分　0:課税、1:非課税
    // $rate        外貨のレート（基軸通貨の場合はnull）
    // $recPrice11  受注1件目1行目　単価
    // $recPrice12  受注1件目2行目　単価
    // $recPrice21  受注2件目1行目　単価
    // $recPrice31  受注3件目1行目　単価
    // $recPrice41  受注4件目1行目　単価
    // $deliveryAnswer  期待値：受注1件目の納品金額（税別）
    // $billAnswer      期待値：請求書の金額（税込）
    private function _salesAmountTestCore2($testNo, $precision, $taxCategory, $round, $taxClass, $rate,
            $recPrice11, $recPrice12, $recPrice21, $recPrice31, $recPrice41,
            $deliveryAnswer, $billAnswer
            ) {
        global $gen_db;

        $deliveryDate = date('Y-m-d');

        // 品目
        $itemId = TestCommon::makeItem(array('tax_class'=>$taxClass));

        // 外貨
        if ($rate == null) {
            $currencyId = null;
        } else {
            $currencyId = TestCommon::makeCurrencyAndRate(array(), '1970-01-01', 1);
        }

        // 得意先（締め請求）
        $custId = TestCommon::makeCustomer(array('tax_category'=>$taxCategory, 'rounding'=>$round, 'precision'=>$precision, 'bill_pattern'=>1, 'currency_id' => $currencyId));

        // 受注1件目。同時に納品を登録
        $recHeaderId_1 = TestCommon::makeReceived(array(
            // 明細1行目:
            array(
                'customer_id' => $custId
                ,'received_date' => $deliveryDate
                ,'dead_line' => $deliveryDate
                ,'item_id' => $itemId
                ,'received_quantity' => 1
                ,'product_price' => $recPrice11
                ,'delivery_regist' => 'true'
                ),
            // 明細2行目
            array(
                'customer_id' => $custId
                ,'received_date' => $deliveryDate
                ,'dead_line' => $deliveryDate
                ,'item_id' => $itemId
                ,'received_quantity' => 1
                ,'product_price' => $recPrice12
                ,'delivery_regist' => 'true'
                ),
            ));

        // 受注2件目。同時に納品を登録
        $recHeaderId_2 = TestCommon::makeReceived(array(
            'customer_id' => $custId
            ,'received_date' => $deliveryDate
            ,'dead_line' => $deliveryDate
            ,'item_id' => $itemId
            ,'received_quantity' => 1
            ,'product_price' => $recPrice21
            ,'delivery_regist' => 'true'
        ));

        // 受注3件目。同時に納品を登録
        $recHeaderId_3 = TestCommon::makeReceived(array(
            'customer_id' => $custId
            ,'received_date' => $deliveryDate
            ,'dead_line' => $deliveryDate
            ,'item_id' => $itemId
            ,'received_quantity' => 1
            ,'product_price' => $recPrice31
            ,'delivery_regist' => 'true'
        ));

        // 受注4件目。同時に納品を登録
        $recHeaderId_4 = TestCommon::makeReceived(array(
            'customer_id' => $custId
            ,'received_date' => $deliveryDate
            ,'dead_line' => $deliveryDate
            ,'item_id' => $itemId
            ,'received_quantity' => 1
            ,'product_price' => $recPrice41
            ,'delivery_regist' => 'true'
        ));
        // 請求
        Logic_Bill::makeBillData(0, $deliveryDate, 0, array($custId), null);

        // ***** 結果確認 *****

        // 受注1件目に対する納品金額（税別）
        $query = "select sum(delivery_amount) from delivery_detail
            inner join received_detail on delivery_detail.received_detail_id = received_detail.received_detail_id
            where received_detail.received_header_id = '$recHeaderId_1'";
        $res = $gen_db->queryOneValue($query);
        //var_dump ("testNo: $testNo -1  $deliveryAnswer : $res");
        $this->assertEquals((string)$deliveryAnswer, $res);

        // 請求金額（税込）
        $query = "select sales_amount + coalesce(tax_amount,0) from bill_header where customer_id = '$custId'";
        $res = $gen_db->queryOneValue($query);
        //var_dump($gen_db->getArray("select * from bill_header where customer_id = '$custId'"));        
        //var_dump ("testNo: $testNo -2 $billAnswer : $res");
        $this->assertEquals((string)$billAnswer, $res);
    }

}