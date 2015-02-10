<?php

// テストクラス共通処理。先頭に書くこと
require_once dirname(__FILE__) . '/../TestCommon.class.php';

require_once dirname(__FILE__) . '/../../Logic/Customer.class.php';

class Logic_CustomerTest extends PHPUnit_Framework_TestCase {

    protected $object;

    protected function setUp() {
        $this->object = new Logic_Customer;

        global $gen_db;
        $gen_db->begin();         // コミットはしないこと
    }

    protected function tearDown() {
        global $gen_db;
        $gen_db->rollback();
    }

    public function testRound() {
        global $gen_db; // TestCommonで準備される

        // 取引先マスタ
        $custId_floor = TestCommon::makeCustomer(array('rounding'=>'floor', 'precision'=>4));
        $custId_round = TestCommon::makeCustomer(array('rounding'=>'round', 'precision'=>3));
        $custId_ceil = TestCommon::makeCustomer(array('rounding'=>'ceil', 'precision'=>2));

        // マイナス値や小数点誤差などのテストは Gen_Math::round() に対するテストケースでおこなわれている。
        // ここでは取引先マスタの設定が反映されていることだけをテストする。
        $this->assertEquals(1.2345, $this->object->round(1.23456, $custId_floor));   // 切捨てテスト
        $this->assertEquals(1.235, $this->object->round(1.23456, $custId_round));   // 四捨五入テスト
        $this->assertEquals(1.24, $this->object->round(1.23456, $custId_ceil));    // 切り上げテスト
    }

    public function testMakeCycleDateTable() {
        global $gen_db; // TestCommonで準備される

        // 得意先テスト（支払予定日）
        // サイクル1が優先される
        $custId = TestCommon::makeCustomer(array('receivable_cycle1'=>10, 'receivable_cycle2_month'=>2, 'receivable_cycle2_day'=>30));
        $this->object->makeCycleDateTable('2010-1-1', true, $custId);
        $date = $gen_db->queryOneValue("select to_char(cycle_date,'YYYY-MM-DD') from temp_cycle_date");
        $this->assertEquals('2010-01-11', $date);

        // サイクル2。2ヵ月後の月末
        $custId = TestCommon::makeCustomer(array('receivable_cycle2_month'=>2, 'receivable_cycle2_day'=>31));
        $this->object->makeCycleDateTable('2010-1-1', true, $custId);
        $date = $gen_db->queryOneValue("select to_char(cycle_date,'YYYY-MM-DD') from temp_cycle_date");
        $this->assertEquals('2010-03-31', $date);

        // サプライヤーテスト（回収予定日）。
        // サイクル1が優先される
        $partId = TestCommon::makeCustomer(array('classification'=>1, 'payment_cycle1'=>10, 'payment_cycle2_month'=>2, 'payment_cycle2_day'=>30));
        $this->object->makeCycleDateTable('2010-8-1', false, $partId);
        $date = $gen_db->queryOneValue("select to_char(cycle_date,'YYYY-MM-DD') from temp_cycle_date");
        $this->assertEquals('2010-08-11', $date);

        // サイクル2。2ヵ月後の月末
        $partId = TestCommon::makeCustomer(array('classification'=>1, 'payment_cycle2_month'=>2, 'payment_cycle2_day'=>31));
        $this->object->makeCycleDateTable('2010-8-1', false, $partId);
        $date = $gen_db->queryOneValue("select to_char(cycle_date,'YYYY-MM-DD') from temp_cycle_date");
        $this->assertEquals('2010-10-31', $date);
    }

}