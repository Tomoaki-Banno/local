<?php

// テストクラス共通処理。先頭に書くこと
require_once dirname(__FILE__) . '/../TestCommon.class.php';

require_once dirname(__FILE__) . '/../../Components/Math.class.php';

class Gen_MathTest extends PHPUnit_Framework_TestCase {

    /**
     * @var Gen_Math
     */
    protected $object;

    protected function setUp() {
        $this->object = new Gen_Math;
    }

    protected function tearDown() {
    }

    public function testRound() {
        // ---------- 整数丸め ----------
 
        // ●正数
        $this->assertEquals(1, $this->object->round(1.9, 'floor'));
        $this->assertEquals(2, $this->object->round(1.1, 'ceil'));
        $this->assertEquals(1, $this->object->round(1.4, 'round'));
        $this->assertEquals(2, $this->object->round(1.5, 'round'));
        
        // 15iで切り上げの動きが変わった。
        //  13i: round(1.01, 'ceil') => 1 （丸め位置の直下の桁だけを考慮）
        //  15i: round(1.01, 'ceil') => 2
        $this->assertEquals(2, $this->object->round(1.01, 'ceil'));
        $this->assertEquals(-2, $this->object->round(-1.01, 'ceil'));
        
        // ●負数
        // ceil/floorの負数の丸めはPHPとは逆になる。
        // 例えば PHPのfloor(-100.1)は -101 だが、この関数では -100 とする。
        $this->assertEquals(-100, $this->object->round(-100.1, 'floor'));
        $this->assertEquals(-101, $this->object->round(-100.1, 'ceil'));
        $this->assertEquals(-1, $this->object->round(-1.4, 'round'));
        $this->assertEquals(-2, $this->object->round(-1.5, 'round'));
        
        // ●小数点誤差問題
        $this->assertEquals(8, $this->object->round((0.1 + 0.7) * 10, 'floor'));
        $this->assertEquals(-8, $this->object->round((0.1 + 0.7) * -10, 'floor'));
        $this->assertEquals(57, $this->object->round(0.57 * 100, 'floor'));
        $this->assertEquals(29, $this->object->round(0.29 * 100, 'floor'));
        $this->assertEquals(7175, $this->object->round(3500 * 2.05, 'floor'));
        
        // ---------- 小数点以下の桁数を指定した丸め ----------

        // ●正数
        $this->assertEquals(1.0001, $this->object->round(1.00011, 'floor',4));
        $this->assertEquals(1.0002, $this->object->round(1.00011, 'ceil',4));
        $this->assertEquals(1.0000, $this->object->round(1.00004, 'round',4));
        $this->assertEquals(1.0001, $this->object->round(1.00005, 'round',4));
        
        // 15iで切り上げの動きが変わった。
        //  13i: round(1.01, 'ceil') => 1 （丸め位置の直下の桁だけを考慮）
        //  15i: round(1.01, 'ceil') => 2
        $this->assertEquals(1.0002, $this->object->round(1.000101, 'ceil',4));
        $this->assertEquals(-1.0002, $this->object->round(-1.000101, 'ceil',4));

        // ●負数
        // ceil/floorの負数の丸めはPHPとは逆になる。
        // 例えば PHPのfloor(-100.1)は -101 だが、この関数では -100 とする。
        $this->assertEquals(-1.0001, $this->object->round(-1.00011, 'floor',4));
        $this->assertEquals(-1.0002, $this->object->round(-1.00011, 'ceil',4));
        $this->assertEquals(-1.0000, $this->object->round(-1.00004, 'round',4));
        $this->assertEquals(-1.0001, $this->object->round(-1.00005, 'round',4));
        
        // ag.cgi?page=ProjectDocView&pid=1195&did=128048
        // 負数のfloorで、まるめ桁より下の桁が2桁以上あった場合の問題
        $this->assertEquals(-107485, $this->object->round(-107485.92, 'floor',0));
        $this->assertEquals(-107486, $this->object->round(-107485.02, 'ceil',0));
        
        // ag.cgi?page=ProjectDocView&pid=1389&did=157309
        $this->assertEquals(982500, $this->object->round(bcmul(50000, 19.65), 'floor',0));

        // ag.cgi?page=ProjectDocView&pid=1574&did=217365
        // 大きなの数値の丸め
        $this->assertEquals(99999999999, $this->object->round(99999999999, 'floor', 4));
        $this->assertEquals(-99999999999, $this->object->round(-99999999999, 'floor', 4));
        $this->assertEquals(99999999999, $this->object->round(99999999999, 'ceil', 4));
        $this->assertEquals(-99999999999, $this->object->round(-99999999999, 'ceil', 4));
        $this->assertEquals(99999999999, $this->object->round(99999999999, 'round', 4));
        $this->assertEquals(-99999999999, $this->object->round(-99999999999, 'round', 4));
    }

    /**
     * Generated from @assert (10.1, 'floor') == 10.
     */
    public function testRound2() {
        $this->assertEquals(
                10
                , $this->object->round(10.1, 'floor')
        );
    }

}

?>
