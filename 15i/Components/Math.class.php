<?php

/**
 * 数値演算系のユーティリティ関数を集めたクラス
 *
 * @copyright 2011 e-commode
 */
class Gen_Math
{

    /**
     * 加算 (bcmath)
     *
     * @access  public
     * @param   numeric   $leftOperand      数値
     * @param   numeric   $rightOperand     数値
     * @return  numeric                     加算値
     */
    static function add($leftOperand, $rightOperand)
    {
        $value = bcadd($leftOperand, $rightOperand);
        return Gen_String::naturalFormat($value);
    }

    /**
     * 減算 (bcmath)
     *
     * @access  public
     * @param   numeric   $leftOperand      数値
     * @param   numeric   $rightOperand     数値
     * @return  numeric                     減算値
     */
    static function sub($leftOperand, $rightOperand)
    {
        $value = bcsub($leftOperand, $rightOperand);
        return Gen_String::naturalFormat($value);
    }

    /**
     * 乗算 (bcmath)
     *
     * @access  public
     * @param   numeric   $leftOperand      数値
     * @param   numeric   $rightOperand     数値
     * @return  numeric                     乗算値
     */
    static function mul($leftOperand, $rightOperand)
    {
        $value = bcmul($leftOperand, $rightOperand);
        return Gen_String::naturalFormat($value);
    }

    /**
     * 除算 (bcmath)
     *
     * @access  public
     * @param   numeric   $leftOperand      数値
     * @param   numeric   $rightOperand     数値
     * @return  numeric                     除算値
     */
    static function div($leftOperand, $rightOperand)
    {
        $value = bcdiv($leftOperand, $rightOperand);
        return Gen_String::naturalFormat($value);
    }

    /**
     * 数値のまるめ処理
     *
     * 次の点がPHPの標準関数（floor/ceil/round）と異なる。
     * 1. (13i以前のみ)切り上げは、小数点以下1桁に注目して行う。
     *     たとえばPHP関数で ceil(1.01) は 2 だが、この関数では 1 となる。
     *     請求書発行等で使われているSQL の gen_round の動きにあわせている。
     * 　 ※15i以降はPHP関数と同じ動きに変更。SQLのgen_roundも同じ。
     *      ag.cgi?page=ProjectDocView&pid=1574&did=207515
     * 2. 切り上げ・切り捨てにおける負数の扱いが逆になる。
     *     PHP関数では負数の切り上げ・切り捨ては次のようになる。
     *          切り上げ(ceil)の場合は値が大きくなる（ceil(-100.1) = -100）
     *          切り捨て(floor)の場合は値が小さくなる（floor(-100.1) = -101）
     *     この関数では逆になる。
     *          切り上げ(ceil)の場合は値が小さくなる（ceil(-100.1) = -101）
     *          切り捨て(floor)の場合は値が大きくなる（floor(-100.1) = -100）
     *     これは、赤黒処理の場合の違和感をなくすため。
     *          たとえばPHP関数で floor(100.1) は 100 だが、floor(-100.1) は -101となり、赤黒の値がそろわない。
     *          この関数では -100 となる。
     *     ※負数の切り上げ・切り捨てに関してはいろいろな考え方があり、プログラム言語やRDBMSによって扱いが異なる。
     *      【切り上げ】
     *       PHP        ceil(-1.1)          -1
     *       PostgreSQL ceil(-1.1)          -1
     *       Gen_Math::round(-1.1,'ceil')   -2
     *      【切り捨て】    
     *       PHP        floor(-1.1)         -2
     *       PHP        (int)(-1.1)         -1    intへキャスト
     *       PostgreSQL floor(-1.1)         -2    マニュアルでは「引数より大きくない最大の整数」
     *       PostgreSQL trunc(-1.1)         -1    マニュアルでは「切り捨て」
     *       Gen_Math::round(-1.1,'floor')  -1
     * 3. 演算誤差への対処。
     *     PHP（に限らないが）では、10進->2進の丸め誤差の関係で、正確な小数演算ができない場合がある。
     *      例： floor((0.1+0.7)*10) が 0.7、floor(0.57*100) が 56 になるなど。
     *     ここでは、いったん文字化することである程度 正確な計算ができるようにしている。
     *      例： floor(0.57*100) は 56 になってしまうが、floor(sprintf('%.3f',0.57*100)) なら 56になる。
     *     ただし、この方法ですべてのパターンをカバーできるわけではない。
     *      例： sprintf('%.10f',50000*19.65) は 982500 のはずが 982499.9999999999 になる。
     *     正確に計算するには、小数を含む計算すべてにおいて BCMath関数を使用する必要がある。
     * 4. floor, ceil でも小数点以下の丸め桁数を指定できる。
     *
     * @param   numeric $val        処理する数値
     * @param   text    $method     丸め方法（floor/ceil/round）
     * @param   int     $precision  小数点以下の桁数（省略すると0）
     *
     * @return  numeric             丸め後の数値
     */
    static function round($val, $method, $precision = 0)
    {
        if (!is_numeric($val))
            return false;
        if (!is_numeric($precision) || $precision < 0)
            return false;

        // いったん値を文字化。
        // これは 10進->2進の丸め誤差による端数が、文字化することで補正されるため。
        // たとえば (0.1+0.7)*10 は内部的には 7.9999999.. なので floor() すると 7 となってしまうが、
        // いったん文字化すると"8"になるので floor() は 8 になる。
        // なお、単純なstringキャストではうまく補正されない場合があるため、spfintfで固定小数点値と
        // して解釈した上で文字化している。
        $val = sprintf('%.10f', $val);

        if ($precision == 0) {
            $mul = 1;
        } else {
            $mul = pow(10, $precision);
        }

        switch ($method) {
            case 'floor':   // 切り捨て
                if ($val >= 0) {
                    // 正数の場合
                    if ($precision == 0) {
                        $val = floor($val);
                    } else {
                        $val = bcdiv(sprintf('%.10f', floor(bcmul($val, $mul))), $mul);
                    }
                } else {
                    // 負数の場合
                    // 負数の丸めはPHPとは逆の動きになるため、ceilを使う。
                    $val = bcdiv(sprintf('%.10f', ceil(bcmul($val, $mul))), $mul);
                    // (13i以前)ceilを使用する場合、丸め桁の一つ下の桁に注目して処理を行う必要がある。
                    // $val = bcdiv(ceil(sprintf('%.10f', bcdiv(ceil(sprintf('%.10f', bcmul(bcmul($val, $mul), 10))), 10))), $mul);
                }
                break;

            case 'ceil':    // 切り上げ
                if ($val >= 0) {
                    // 正数の場合
                    $val = bcdiv(sprintf('%.10f', ceil(bcmul($val, $mul))), $mul);
                    // (13i以前)ceilを使用する場合、丸め桁の一つ下の桁に注目して処理を行う必要がある。
                    // $val = bcdiv(ceil(sprintf('%.10f', bcdiv(floor(sprintf('%.10f', bcmul(bcmul($val, $mul), 10))), 10))), $mul);
                } else {
                    // 負数の場合
                    // 負数の丸めはPHPとは逆の動きになるため、floorを使う。
                    if ($precision == 0) {
                        $val = sprintf('%.10f',floor($val));
                        // (13i以前)ceilを使用する場合、丸め桁の一つ下の桁に注目して処理を行う必要がある。
                        // $val = floor(sprintf('%.10f', bcdiv(ceil(sprintf('%.10f', bcmul($val, 10))), 10)));
                    } else {
                        $val = bcdiv(sprintf('%.10f', floor(bcmul($val, $mul))), $mul);
                        // (13i以前)ceilを使用する場合、丸め桁の一つ下の桁に注目して処理を行う必要がある。
                        // $val = bcdiv(floor(sprintf('%.10f', bcdiv(ceil(sprintf('%.10f', bcmul(bcmul($val, $mul), 10))), 10))), $mul);
                    }
                }
                break;

            default:        // 四捨五入
                $val = round($val, $precision);
                break;
        }
        return Gen_String::naturalFormat($val);
    }

}