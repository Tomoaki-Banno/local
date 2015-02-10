<?php

// テストクラス共通処理。先頭に書くこと
require_once dirname(__FILE__) . '/../TestCommon.class.php';

require_once dirname(__FILE__) . '/../../Logic/Delivery.class.php';
require_once dirname(__FILE__) . '/../../Logic/Bill.class.php';
require_once dirname(__FILE__) . '/../../Logic/Receivable.class.php';
require_once dirname(__FILE__) . '/../../Components/Math.class.php';

class SalesForeignAmountTest extends PHPUnit_Framework_TestCase {

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

    // ag.cgi?page=BulletinView&bid=30460&gid=0&cid=377 の「12i外貨・消費税テスト結果.xlsx」の外貨売掛シートをもとにしたテスト
    public function testSalesForeignAmount() {
        global $gen_db; // TestCommonで準備される

        var_dump('--- 1月 ---');
        self::_salesForeignAmountTestCore(100.01, 100.04, 100.05, 100.09, 10, 10, 10, 10, -10, 10, 10, 10, 10, -10);
        var_dump('--- 2月 ---');
        self::_salesForeignAmountTestCore(100.05, 100.09, 100.0001, 100.0004, 10, 10, 10, 10, -10, 10, 10, 10, 10, -10);
        var_dump('--- 3月 ---');
        self::_salesForeignAmountTestCore(100.0001, 100.0004, 100.0005, 100.0009, 10, 10, 10, 10, -10, 10, 10, 10, 10, -10);
        var_dump('--- 4月 ---');
        self::_salesForeignAmountTestCore(100.0005, 100.0009, 100, 100, 10, 10, 10, 10, -10, 10, 10, 10, 10, 0);
    }

    // $rate1       当月1日時点の外貨レート
    // $rate2       当月15日時点の外貨レート
    // $rate3       翌月1日時点の外貨レート
    // $rate4       翌月15日時点の外貨レート
    // $recPrice11  当月受注（1日受注・1日納品）1件目1行目　単価
    // $recPrice12  当月受注（1日受注・1日納品）1件目2行目　単価
    // $recPrice21  当月受注（1日受注・15日納品）2件目1行目　単価
    // $recPrice31  当月受注（1日受注・15日納品）3件目1行目　単価
    // $deliveryRedPrice  当月納品赤伝（15日付）
    // $recPrice11_2  翌月受注（1日受注・1日納品）1件目1行目　単価
    // $recPrice12_2  翌月受注（1日受注・1日納品）1件目2行目　単価
    // $recPrice21_2  翌月受注（1日受注・15日納品）2件目1行目　単価
    // $recPrice31_2  翌月受注（1日受注・15日納品）3件目1行目　単価
    // $deliveryRedPrice_2  翌月納品赤伝（15日付）
    private function _salesForeignAmountTestCore($rate1, $rate2, $rate3, $rate4,
            $recPrice11, $recPrice12, $recPrice21, $recPrice31, $deliveryRedPrice,
            $recPrice11_2, $recPrice12_2, $recPrice21_2, $recPrice31_2, $deliveryRedPrice_2
            ) {
        $testNo = 1;
        $taxRate = (Logic_Tax::getTaxRate(date('Y-m-d')) / 100);

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

                    // 期待値を算出する

                    // 期待値1：当月受注合計金額（円換算）
                    //  ※金額は税計算単位（請求書単位・納品書単位など）にかかわらず、常に納品明細行ごとに丸められる
                    //  ※Gen_Math::round() 自体のテストは別のテストクラスで行なっているので、信頼してよい
                    $recAnswer =
                        Gen_Math::round($recPrice11 * $rate1, $round, $precision)       // 1日受注
                        + Gen_Math::round($recPrice12 * $rate1, $round, $precision)     // 1日受注
                        + Gen_Math::round($recPrice21 * $rate1, $round, $precision)     // 15日受注
                        + Gen_Math::round($recPrice31 * $rate1, $round, $precision);    // 15日受注

                    // 期待値2：当月納品合計金額（円換算）
                    $delAnswer =
                        Gen_Math::round($recPrice11 * $rate1, $round, $precision)       // 1日納品
                        + Gen_Math::round($recPrice12 * $rate1, $round, $precision)     // 1日納品
                        + Gen_Math::round($recPrice21 * $rate2, $round, $precision)     // 15日納品
                        + Gen_Math::round($recPrice31 * $rate2, $round, $precision)     // 15日納品
                        + Gen_Math::round($deliveryRedPrice * $rate2, $round, $precision);    // 15日

                    // 期待値3：売掛残高表（当月1日-翌月10日）・納品ベース（円換算）
                    $am11_2 = Gen_Math::round($recPrice11_2, $round, $precision);
                    $am12_2 = Gen_Math::round($recPrice12_2, $round, $precision);
                    $am21_2 = Gen_Math::round($recPrice21_2, $round, $precision);
                    $am31_2 = Gen_Math::round($recPrice31_2, $round, $precision);
                    $amDR_2 = Gen_Math::round($deliveryRedPrice_2, $round, $precision);
                    // 外貨なので $taxCategory は関係ないはず
                    $recListAnswer1 =
                        $delAnswer      // 当月納品分
                        + Gen_Math::round($am11_2 * $rate3, $round, $precision)     // 翌月1日納品分
                        + Gen_Math::round($am12_2 * $rate3, $round, $precision);    // 翌月1日納品分

                    // 期待値4：売掛残高表（当月1日-翌月10日）・受注ベース（円換算）
                    $recListAnswer2 =
                        $recAnswer      // 当月受注分
                        + Gen_Math::round($am11_2 * $rate3, $round, $precision)     // 翌月1日受注分
                        + Gen_Math::round($am12_2 * $rate3, $round, $precision);    // 翌月1日受注分
                        + Gen_Math::round($am21_2 * $rate3, $round, $precision)     // 翌月1日受注分
                        + Gen_Math::round($am31_2 * $rate3, $round, $precision);    // 翌月1日受注分

                    var_dump("No: {$testNo}, {$precisionStr}, {$taxStr}, {$roundStr}");
                    $testNo++;

                    // 引数の最後の2つが期待値。 _salesAmountTestCore2の引数リストを参照
                    self::_salesAmountTestCore2(
                            $testNo++,
                            $precision,
                            $taxCategory,
                            $round,
                            $rate1,
                            $rate2,
                            $rate3,
                            $rate4,
                            $recPrice11,
                            $recPrice12,
                            $recPrice21,
                            $recPrice31,
                            $deliveryRedPrice,
                            $recPrice11_2,
                            $recPrice12_2,
                            $recPrice21_2,
                            $recPrice31_2,
                            $deliveryRedPrice_2,
                            $recAnswer,
                            $delAnswer,
                            $recListAnswer1,
                            $recListAnswer2
                     );
                }
            }
        }
    }

    // $testNo
    // $precision   取引先マスタ　小数点以下の桁数
    // $taxCategory 取引先マスタ　税計算単位 0: 請求書単位, 1: 納品書単位, 2: 納品明細単位
    // $round       取引先マスタ　端数処理  空欄: なし, round: 四捨五入, floor: 切捨, ceil: 切上
    //
    // $rate1       当月1日時点の外貨レート
    // $rate2       当月15日時点の外貨レート
    // $rate3       翌月1日時点の外貨レート
    // $rate4       翌月15日時点の外貨レート
    // $recPrice11  当月受注（1日受注・1日納品）1件目1行目　単価
    // $recPrice12  当月受注（1日受注・1日納品）1件目2行目　単価
    // $recPrice21  当月受注（1日受注・15日納品）2件目1行目　単価
    // $recPrice31  当月受注（1日受注・15日納品）3件目1行目　単価
    // $deliveryRedPrice  当月納品赤伝（15日付）
    // $recPrice11_2  翌月受注（1日受注・1日納品）1件目1行目　単価
    // $recPrice12_2  翌月受注（1日受注・1日納品）1件目2行目　単価
    // $recPrice21_2  翌月受注（1日受注・15日納品）2件目1行目　単価
    // $recPrice31_2  翌月受注（1日受注・15日納品）3件目1行目　単価
    // $deliveryRedPrice_2  翌月納品赤伝（15日付）
    //
    // $recAnswer   期待値：当月受注合計額（円換算）
    // $delAnswer   期待値：当月納品合計額（円換算） = 請求合計額 = 売掛残高表請求ベース
    // $recListAnswer1  期待値：売掛残高表（当月1日-翌月10日）・納品ベース（円換算）
    // $recListAnswer2  期待値：売掛残高表（当月1日-翌月10日）・受注ベース（円換算）
    private function _salesAmountTestCore2($testNo, $precision, $taxCategory, $round,
            $rate1, $rate2, $rate3, $rate4,
            $recPrice11, $recPrice12, $recPrice21, $recPrice31, $deliveryRedPrice,
            $recPrice11_2, $recPrice12_2, $recPrice21_2, $recPrice31_2, $deliveryRedPrice_2,
            $recAnswer, $delAnswer, $recListAnswer1, $recListAnswer2
            ) {
        global $gen_db;

        // 日付
        $this1date = date('Y-m-01');
        $this15date = date('Y-m-15');
        $thisLastdate = date('Y-m-t');
        $next1date = date('Y-m-01', strtotime(date('Y-m-01') . ' +1 month'));
        $next10date = date('Y-m-01', strtotime(date('Y-m-01') . ' +1 month'));
        $next15date = date('Y-m-15', strtotime(date('Y-m-01') . ' +1 month'));

        // 品目
        $itemId = TestCommon::makeItem();

        // 外貨
        $currencyId = TestCommon::makeCurrencyAndRate(array(), '1970-01-01', $rate1);
        // 当月15日付レートの登録
        $data = array(
            'currency_id' => $currencyId,
            'rate_date' => $this15date,
            'rate' => $rate2,
        );
        $gen_db->insert('rate_master', $data);
        // 翌月1日付レートの登録
        $data = array(
            'currency_id' => $currencyId,
            'rate_date' => $next1date,
            'rate' => $rate3,
        );
        $gen_db->insert('rate_master', $data);
        // 翌月15日付レートの登録
        $data = array(
            'currency_id' => $currencyId,
            'rate_date' => $next15date,
            'rate' => $rate4,
        );
        $gen_db->insert('rate_master', $data);

        // 得意先（都度請求）
        $custId = TestCommon::makeCustomer(array('tax_category'=>$taxCategory, 'rounding'=>$round, 'precision'=>$precision, 'bill_pattern'=>0, 'currency_id' => $currencyId));

        // 当月受注1件目（1日受注、1日納品）。同時に納品を登録
        $recHeaderId_1 = TestCommon::makeReceived(array(
            // 明細1行目:
            array(
                'customer_id' => $custId
                ,'received_date' => $this1date
                ,'dead_line' => $this1date
                ,'item_id' => $itemId
                ,'received_quantity' => 1
                ,'product_price' => $recPrice11
                ,'delivery_regist' => 'true'
                ),
            // 明細2行目
            array(
                'customer_id' => $custId
                ,'received_date' => $this1date
                ,'dead_line' => $this1date
                ,'item_id' => $itemId
                ,'received_quantity' => 1
                ,'product_price' => $recPrice12
                ,'delivery_regist' => 'true'
                ),
            ));

        // 当月受注2件目（1日受注、15日納品）
        // 同時納品は使わない（受注日 = 納品日になってしまうので）
        $recHeaderId_2 = TestCommon::makeReceived(array(
            'customer_id' => $custId
            ,'received_date' => $this1date
            ,'dead_line' => $this15date
            ,'item_id' => $itemId
            ,'received_quantity' => 1
            ,'product_price' => $recPrice21
        ));
        $recDetailId_2 = $gen_db->queryOneValue("select received_detail_id from received_detail where received_header_id='{$recHeaderId_2}'");
        TestCommon::makeDelivery(array(
            'received_detail_id' => $recDetailId_2
            ,'delivery_date' => $this15date
            ,'inspection_date' => $this15date
            ,'delivery_price' => $recPrice21
        ));

        // 当月受注3件目（1日受注、15日納品）。同時に納品を登録
        // 同時納品は使わない（受注日 = 納品日になってしまうので）
        $recHeaderId_3 = TestCommon::makeReceived(array(
            'customer_id' => $custId
            ,'received_date' => $this1date
            ,'dead_line' => $this15date
            ,'item_id' => $itemId
            ,'received_quantity' => 1
            ,'product_price' => $recPrice31
        ));
        $recDetailId_3 = $gen_db->queryOneValue("select received_detail_id from received_detail where received_header_id='{$recHeaderId_3}'");
        TestCommon::makeDelivery(array(
            'received_detail_id' => $recDetailId_3
            ,'delivery_date' => $this15date
            ,'inspection_date' => $this15date
            ,'delivery_price' => $recPrice31
        ));

        // 当月納品赤伝（15日付）
        $recHeaderId_4 = TestCommon::makeReceived(array(
            'customer_id' => $custId
            ,'received_date' => $this15date
            ,'dead_line' => $this15date
            ,'item_id' => $itemId
            ,'received_quantity' => 1
            ,'product_price' => 0   // 0円受注
        ));
        $recDetailId_4 = $gen_db->queryOneValue("select received_detail_id from received_detail where received_header_id='{$recHeaderId_4}'");
        TestCommon::makeDelivery(array(
            'received_detail_id' => $recDetailId_4
            ,'delivery_date' => $this15date
            ,'inspection_date' => $this15date
            ,'delivery_price' => $deliveryRedPrice
        ));

        // 翌月受注1件目（1日受注、1日納品）。同時に納品を登録
        $recHeaderId_2_1 = TestCommon::makeReceived(array(
            // 明細1行目:
            array(
                'customer_id' => $custId
                ,'received_date' => $next1date
                ,'dead_line' => $next1date
                ,'item_id' => $itemId
                ,'received_quantity' => 1
                ,'product_price' => $recPrice11_2
                ,'delivery_regist' => 'true'
                ),
            // 明細2行目
            array(
                'customer_id' => $custId
                ,'received_date' => $next1date
                ,'dead_line' => $next1date
                ,'item_id' => $itemId
                ,'received_quantity' => 1
                ,'product_price' => $recPrice12_2
                ,'delivery_regist' => 'true'
                ),
            ));

        // 翌月受注2件目（1日受注、15日納品）
        $recHeaderId_2_2 = TestCommon::makeReceived(array(
            'customer_id' => $custId
            ,'received_date' => $next1date
            ,'dead_line' => $next15date
            ,'item_id' => $itemId
            ,'received_quantity' => 1
            ,'product_price' => $recPrice21_2
        ));
        $recDetailId_2_2 = $gen_db->queryOneValue("select received_detail_id from received_detail where received_header_id='{$recHeaderId_2_2}'");
        TestCommon::makeDelivery(array(
            'received_detail_id' => $recDetailId_2_2
            ,'delivery_date' => $next15date
            ,'inspection_date' => $next15date
            ,'delivery_price' => $recPrice21_2
        ));

        // 翌月受注3件目（1日受注、15日納品）。同時に納品を登録
        $recHeaderId_2_3 = TestCommon::makeReceived(array(
            'customer_id' => $custId
            ,'received_date' => $next1date
            ,'dead_line' => $next15date
            ,'item_id' => $itemId
            ,'received_quantity' => 1
            ,'product_price' => $recPrice31_2
        ));
        $recDetailId_2_3 = $gen_db->queryOneValue("select received_detail_id from received_detail where received_header_id='{$recHeaderId_2_3}'");
        TestCommon::makeDelivery(array(
            'received_detail_id' => $recDetailId_2_3
            ,'delivery_date' => $next15date
            ,'inspection_date' => $next15date
            ,'delivery_price' => $recPrice31_2
        ));

        // 翌月納品赤伝（15日付）
        $recHeaderId_2_4 = TestCommon::makeReceived(array(
            'customer_id' => $custId
            ,'received_date' => $next15date
            ,'dead_line' => $next15date
            ,'item_id' => $itemId
            ,'received_quantity' => 1
            ,'product_price' => 0   // 0円受注
        ));
        $recDetailId_2_4 = $gen_db->queryOneValue("select received_detail_id from received_detail where received_header_id='{$recHeaderId_2_4}'");
        $deliveryHeaderId = TestCommon::makeDelivery(array(
            'received_detail_id' => $recDetailId_2_4
            ,'delivery_date' => $next15date
            ,'inspection_date' => $next15date
            ,'delivery_price' => $deliveryRedPrice_2
        ));

        // 請求（当月末締め）
        Logic_Bill::makeBillData(0, $thisLastdate, 0, array($custId), null);

        // ***** 結果確認 *****
        // 当月受注合計額（円換算）
        $query = "select sum(gen_round_precision(received_quantity, rounding, $precision) * gen_round_precision(product_price, rounding, $precision)) from received_detail
            inner join received_header on received_detail.received_header_id = received_header.received_header_id
            inner join customer_master on received_header.customer_id= customer_master.customer_id
            where received_header.received_date between '{$this1date}' and '{$thisLastdate}' and received_header.customer_id= '{$custId}'";
        $res = $gen_db->queryOneValue($query);
        $this->assertEquals((string)$recAnswer, $res);

        // 当月納品合計額（円換算）
        $query = "select sum(delivery_amount) from delivery_detail
            inner join delivery_header on delivery_detail.delivery_header_id = delivery_header.delivery_header_id
            inner join received_detail on delivery_detail.received_detail_id = received_detail.received_detail_id
            inner join received_header on received_detail.received_header_id = received_header.received_header_id
            where received_header.received_date between '{$this1date}' and '{$thisLastdate}' and delivery_header.customer_id= '{$custId}'";
        $res = $gen_db->queryOneValue($query);
        $this->assertEquals((string)$delAnswer, $res);

        // 請求金額（円換算）
        $query = "select sales_amount from bill_header where customer_id = '{$custId}' and close_date = '{$thisLastdate}'";
        $res = $gen_db->queryOneValue($query);
        $this->assertEquals((string)$delAnswer, $res);

        // 売掛残高表（当月1日-翌月10日）・納品ベース（円換算）
        Logic_Receivable::createTempReceivableTable($this1date, $next10date, 0, true);
        $query = "select sum(sales) from temp_receivable where customer_id = '{$custId}'";
        $res = $gen_db->queryOneValue($query);
        $this->assertEquals((string)$recListAnswer1, $res);

        // 売掛残高表（当月1日-翌月10日）・受注ベース（円換算）
        Logic_Receivable::createTempReceivableTable($this1date, $next10date, 1, true);
        $query = "select sum(sales) from temp_receivable where customer_id = '{$custId}'";
        $res = $gen_db->queryOneValue($query);
        $this->assertEquals((string)$recListAnswer2, $res);
    }

}