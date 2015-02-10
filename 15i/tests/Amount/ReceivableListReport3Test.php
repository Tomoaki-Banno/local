<?php

// テストクラス共通処理。先頭に書くこと
require_once dirname(__FILE__) . '/../TestCommon.class.php';

require_once dirname(__FILE__) . '/../../Logic/Delivery.class.php';
require_once dirname(__FILE__) . '/../../Logic/Bill.class.php';
require_once dirname(__FILE__) . '/../../Components/Math.class.php';

class ReceivableListReport3Test extends PHPUnit_Framework_TestCase {

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

    public function testReceivablListReport3() {
        var_dump('--- 1月 ---');
        self::_receivableListReport3TestCore(1000.1, 1000, 1000.3, 1000, -1000, 1000);
        var_dump('--- 2月 ---');
        self::_receivableListReport3TestCore(1000.3, 1000.1, 1000.1, 1000.1, -1000.1, 1000.001);
        var_dump('--- 6月 ---');
        self::_receivableListReport3TestCore(1000.003, 1000.001, 1000.001, 1000.001, -1000.001, -1000);
        var_dump('--- 9月 ---');
        self::_receivableListReport3TestCore(1000.002, 1000.009, 1000.009, 1000.009, -1000.001, -1000.001);
    }

    // さまざまなパターンにおいて、納品書や請求書の数値が期待値（計算値）どおりになるかどうかをチェックする。
    private function _receivableListReport3TestCore($recPrice11, $recPrice12, $recPrice21, $recPrice31, $recPrice41, $payingIn) {
        $testNo = 1;
        $taxRate = (Logic_Tax::getTaxRate(date('Y-m-d')) / 100);
        
        $recPriceNonDelivery = 1000;    // 未納品の受注
        $recPriceNonBill = 2000;        // 納品済・未請求の受注
        $broughtForward = 10000;        // 期間前からの繰越額

        for($currencyMode=0; $currencyMode<=1; $currencyMode++) {
            switch($currencyMode) {
                case 0: $currency = null; $currencyStr = "JPY"; break;   // 基軸通貨
                case 1: $currency = 1; $currencyStr = "USD"; break;      // 外貨
            }
            for($yenMode=0; $yenMode<=1; $yenMode++) {
                switch($yenMode) {
                    case 0: $yenModeStr = "No conv"; break;   // 取引通貨別
                    case 1: $yenModeStr = "conv"; break;      // 基軸通貨換算
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
                            // 入金の外貨の円換算値は登録時に丸められている
                            if ($currency == 1 && $yenMode == 1) {
                                $payingInRounded = Gen_Math::round($payingIn, $round, $precision);
                            } else {
                                $payingInRounded = $payingIn;
                            }
                            for($taxClassMode=0; $taxClassMode<=1; $taxClassMode++) {
                                switch($taxClassMode) {
                                    case 0: $taxClass = 0; $taxClassStr = "tax"; break;     // 課税
                                    case 1: $taxClass = 1; $taxClassStr = "no tax"; break;     // 非課税
                                }
                                for($amountMode=0; $amountMode<=2; $amountMode++) {
                                    // 受注ベースの場合、常に納品明細単位として扱われる。
                                    // （Delivery_ReceivableList_Report3のSQLの、明細行のデータ行部分のコメントを参照）
                                    if ($amountMode == 1 && $taxCategory != 2) {
                                        continue;
                                    }

                                    switch($amountMode) {
                                        case 0: $amountModeStr = "delivery base"; break;     // 納品ベース
                                        case 1: $amountModeStr = "received base"; break;     // 受注ベース
                                        case 2: $amountModeStr = "bill base"; break;        // 請求ベース
                                    }

                                    // 期待値を算出する

                                    //  ※金額は税計算単位（請求書単位・納品書単位など）にかかわらず、常に納品明細行ごとに丸められる
                                    $am11 = Gen_Math::round($recPrice11, $round, $precision);
                                    $am12 = Gen_Math::round($recPrice12, $round, $precision);
                                    $am21 = Gen_Math::round($recPrice21, $round, $precision);
                                    $am31 = Gen_Math::round($recPrice31, $round, $precision);
                                    $am41 = Gen_Math::round($recPrice41, $round, $precision);
                                    $salesAmount = $am11 + $am12 + $am21 + $am31 + $am41;
                                    switch($amountMode) {
                                        case 0: $salesAmount += $recPriceNonBill; break;                        // 納品ベース
                                        case 1: $salesAmount += $recPriceNonBill + $recPriceNonDelivery; break; // 受注ベース
                                        case 2: break;                                                          // 請求ベース
                                    }
                                    if ($taxClass==0 && $currency == null) {
                                        switch($taxCategory) {
                                            case 0: // 請求書単位
                                                $tax = Gen_Math::round(($salesAmount) * $taxRate, $round, $precision);
                                                break;
                                            case 1: // 納品書単位
                                                $tax = Gen_Math::round(($am11 + $am12) * $taxRate, $round, $precision)
                                                    + Gen_Math::round($am21 * $taxRate, $round, $precision)
                                                    + Gen_Math::round($am31 * $taxRate, $round, $precision)
                                                    + Gen_Math::round($am41 * $taxRate, $round, $precision);
                                                switch($amountMode) {
                                                    case 0: $tax += Gen_Math::round($recPriceNonBill * $taxRate, $round, $precision); break;   // 納品ベース
                                                    case 1: $tax += Gen_Math::round($recPriceNonBill * $taxRate, $round, $precision) + Gen_Math::round($recPriceNonDelivery * $taxRate, $round, $precision); break; // 受注ベース
                                                    case 2: break;                                                                  // 請求ベース
                                                }
                                                break;
                                            case 2: // 納品明細単位
                                                $tax = Gen_Math::round($am11 * $taxRate, $round, $precision)
                                                    + Gen_Math::round($am12 * $taxRate, $round, $precision)
                                                    + Gen_Math::round($am21 * $taxRate, $round, $precision)
                                                    + Gen_Math::round($am31 * $taxRate, $round, $precision)
                                                    + Gen_Math::round($am41 * $taxRate, $round, $precision);
                                                switch($amountMode) {
                                                    case 0: $tax += Gen_Math::round($recPriceNonBill * $taxRate, $round, $precision); break;   // 納品ベース
                                                    case 1: $tax += Gen_Math::round($recPriceNonBill * $taxRate, $round, $precision) + Gen_Math::round($recPriceNonDelivery * $taxRate, $round, $precision); break; // 受注ベース
                                                    case 2: break;                                                                  // 請求ベース
                                                }
                                                break;
                                        }
                                    } else {
                                        $tax = 0;
                                    }

                                    var_dump("No: {$testNo}, {$currencyStr}, {$yenModeStr}, {$precisionStr}, {$taxStr}, {$roundStr}, {$taxClassStr}, {$amountModeStr}");

                                    self::_receivableListReport3TestCore2(
                                            $testNo++,
                                            $precision,
                                            $taxCategory,
                                            $round,
                                            $taxClass,
                                            $currency,
                                            $yenMode,
                                            $amountMode,

                                            $recPrice11,
                                            $recPrice12,
                                            $recPrice21,
                                            $recPrice31,
                                            $recPrice41,
                                            $recPriceNonDelivery,
                                            $recPriceNonBill,
                                            $payingIn,

                                            // 以下、期待値
                                            $broughtForward,    // 繰越額
                                            $salesAmount,       // 合計売上金額
                                            $tax,               // 合計消費税額
                                            $payingInRounded,          // 合計入金額
                                            $broughtForward + $salesAmount + $tax - $payingInRounded    // 売掛残高
                                     );
                                }
                            }
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
    // $rate        外貨レート（基軸通貨の場合はnull）
    // $yenMode     基軸通貨換算モード　0:取引通貨別、1:基軸通貨換算
    // $amountMode  金額モード　  0: 納品ベース, 1: 受注ベース, 2: 請求ベース
    // $recPrice11  受注1件目1行目　単価
    // $recPrice12  受注1件目2行目　単価
    // $recPrice21  受注2件目1行目　単価
    // $recPrice31  受注3件目1行目　単価
    // $recPrice41  受注4件目1行目　単価
    // $recPriceNonDelivery     未納品の受注
    // $recPriceNonBill         納品済・未請求の受注
    // $payingIn    入金
    // $answer1     期待値：繰越額
    // $answer2     期待値：合計売上金額
    // $answer3     期待値：合計消費税額
    // $answer4     期待値：合計入金額
    // $answer5     期待値：売掛残高
    private function _receivableListReport3TestCore2($testNo, $precision, $taxCategory, $round, $taxClass, $rate, $yenMode, $amountMode,
            $recPrice11, $recPrice12, $recPrice21, $recPrice31, $recPrice41, $recPriceNonDelivery, $recPriceNonBill, $payingIn,
            $answer1, $answer2, $answer3, $answer4, $answer5
            ) {
        global $gen_db;

        $deliveryDate = date('Y-m-d');

//if ($testNo != 21) return;

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
        $custNo = $gen_db->queryOneValue("select customer_no from customer_master where customer_id = '$custId'");
        
        // 繰越額（期間前の売掛残高初期値として登録）
        $beforeDate = date('Y-m-d', strtotime($deliveryDate . ' -1 month'));
        $query = "update customer_master set opening_balance = {$answer1}, opening_date = '{$beforeDate}' where customer_id = '{$custId}'";
        $gen_db->query($query);

        // ********** 受注 ⇒ 納品 ⇒ 請求 データ作成 **********

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

        // 入金
        $foreignCurrencyRate = null;
        $foreignCurrencyAmount = null;
        if ($currencyId != null) {
            $foreignCurrencyRate = $rate;
            $foreignCurrencyAmount = $payingIn;
            // 入力された値はまるめないが、円換算時にはまるめを行う
            $payingIn = Logic_Customer::round(Gen_Math::mul($payingIn, $foreignCurrencyRate), $custId);
        }
        $data = array(
            'paying_in_date' => $deliveryDate,
            'customer_id' => $custId,
            'foreign_currency_id' => $currencyId,
            'foreign_currency_rate' => $foreignCurrencyRate,
            'way_of_payment' => 0,
            'amount' => $payingIn,
            'foreign_currency_amount' => $foreignCurrencyAmount,
            'bill_header_id' => 0,
            'remarks' => "",
        );
        $gen_db->insert("paying_in", $data);
        
        // 請求
        Logic_Bill::makeBillData(0, $deliveryDate, 0, array($custId), null);

        // ********** 受注 （未納品・未請求）データ作成 **********

        $afterDate = date('Y-m-d', strtotime($deliveryDate . ' +1 day'));
        
        // 受注
        TestCommon::makeReceived(array(
            'customer_id' => $custId
            ,'received_date' => $afterDate
            ,'dead_line' => $afterDate
            ,'item_id' => $itemId
            ,'received_quantity' => 1
            ,'product_price' => $recPriceNonDelivery
        ));
        
        // ********** 受注 ⇒ 納品（未請求）データ作成 **********

        // 受注。同時に納品を登録
        TestCommon::makeReceived(array(
            'customer_id' => $custId
            ,'received_date' => $afterDate
            ,'dead_line' => $afterDate
            ,'item_id' => $itemId
            ,'received_quantity' => 1
            ,'product_price' => $recPriceNonBill
            ,'delivery_regist' => 'true'
        ));

        
        // ***** 得意先元帳 帳票発行（結果を temp_test テーブルに取得） *****
        
        $action = "Delivery_ReceivableList_List";        
        require_once(Gen_File::safetyPathForAction($action));
        $actionClass = new $action;
        $form['gen_iPad'] = false;
        $form['action'] = $action;
        $form['gen_reportAction'] = "Delivery_ReceivableList_Report3";
        $form['gen_unitTestMode'] = true;
        $form['gen_unitTestMode'] = true;
        
        $form['gen_searchConditionClear'] = true; // ピンを無視
        $form['gen_search_receivable_Year'] = date("Y", strtotime($deliveryDate));
        $form['gen_search_receivable_Month'] = date("m", strtotime($deliveryDate));
        $form['gen_search_temp_receivable___customer_no'] = $custNo;
        $form['gen_search_match_mode_gen_search_temp_receivable___customer_no'] = "3"; // 3:「と一致」
        $form['gen_search_data_mode'] = $amountMode;   // array('2' => _g("請求ベース"), '0' => _g("納品ベース"), '1' => _g("受注ベース")),
        $form['gen_search_foreign_currency_mode'] = $yenMode;   // array('0' => _g("取引通貨別"), '1' => sprintf(_g("%s換算"), $keyCurrency)),
        
        $query = $actionClass->execute($form);
        $gen_db->createTempTable("temp_test", $query, true);

        // ***** 結果確認 *****
        // ヘッダ
        $arr = $gen_db->getArray("select 得意先元帳_繰越額, 得意先元帳_期間中売上額, 得意先元帳_期間中消費税額, 得意先元帳_期間中入金額, 得意先元帳_売掛金残高 from temp_test limit 1");
        $this->assertEquals((string)$answer1, $arr[0]['得意先元帳_繰越額']);
        $this->assertEquals((string)$answer2, $arr[0]['得意先元帳_期間中売上額']);
        $this->assertEquals((string)$answer3, $arr[0]['得意先元帳_期間中消費税額']);
        $this->assertEquals((string)$answer4, $arr[0]['得意先元帳_期間中入金額']);
        $this->assertEquals((string)$answer5, $arr[0]['得意先元帳_売掛金残高']);
        
        // 明細（合計）
        $arr = $gen_db->getArray("select coalesce(sum(detail_得意先元帳_金額),0) as 金額, coalesce(sum(detail_得意先元帳_消費税額),0) as 消費税, coalesce(sum(detail_得意先元帳_入金額),0) as 入金 from temp_test");
        $this->assertEquals((string)$answer2, $arr[0]['金額']);
        $this->assertEquals((string)$answer3, $arr[0]['消費税']);
        $this->assertEquals((string)$answer4, $arr[0]['入金']);
        
        // 明細残高の最終
        $arr = $gen_db->getArray("select detail_得意先元帳_明細残高 as 残高 from temp_test");
        $this->assertEquals((string)$answer5, $arr[count($arr)-1]['残高']);
    }

}