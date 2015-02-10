<?php

class Gen_Report
{

    //************************************************
    // httpヘッダを出力する(PXDOC)
    //************************************************

    function sendHttpHeader()
    {
        // ヘッダを出力
        header("Content-type: application/pxd; charset=UTF-8");
        header("Content-Disposition:inline;filename=\"test.pxd\"");

        // ブラウザのキャッシュを有効にする。
        //   common_header.tpl のMETAタグでブラウザキャッシュを無効にしているが、
        //     （smartyでもキャッシュ無効にしているため、METAタグがなくても無効かも）
        //   IEのバグで、ブラウザキャッシュ無効だとPXDocのオープンがエラーになる。
        //   　http://support.microsoft.com/default.aspx?scid=kb;ja;436605
        //   それでこのページではキャッシュを有効にしている。
        //   ここでの指定は、METAタグの指定より優先されるようだ。
        //   下記を見るとsession_startより先にsession_cache_limiterを行わなければ
        //   ならないようだが、ここで宣言するやり方でも動くようだ。
        //     http://www.php.net/manual/ja/function.session-cache-limiter.php
        header("Cache-Control: public");
        header("Expires: 0");
        header("Cache-Control: must-revalidate, post-check=0,pre-check=0");
        header("Pragma: public");
    }

    //************************************************
    // 縮小が必要かどうかを判断し、必要ならtextタグ用の属性を返す
    //************************************************
    // 最大バイト数 =「枠幅 ÷ フォントサイズ * 1.92」（全角2バイト、半角1バイト）
    // ちなみにSVGには「指定した幅に拡大縮小」の機能はあるが、「大きければ縮小」の機能は
    // ない。そのため判断が必要になる。
    // 2008i rev.20071124以降、このfunctionを使用するのは非推奨。
    //  textタグ自体を、この下の Gen_Report::text()を使用して書くこと

    function getScalingProperty($len, $maxWidth, $fontSize)
    {
        if ($maxWidth <= 0 || $fontSize <= 0)
            return "";

        $maxWidth -= 10;
        $maxLength = (int) (bcmul(bcdiv($maxWidth, $fontSize), 1.92));

        $spacingStr = "";
        if ($len > $maxLength) {
            $spacingStr = " textLength=\"{$maxWidth}\" lengthAdjust=\"spacingAndGlyphs\"";
        }

        return $spacingStr;
    }

    //************************************************
    // textタグを返す
    //************************************************
    // $x           x位置
    // $y           y位置
    // $text        表示するテキスト
    // $fontSize    フォントサイズ
    // $textAnchor  表示位置（空欄:左寄せ、middle:中央、end:右寄せ）
    // $property    追加プロパティ。そのままタグ内に出力される
    // $maxWidth    最大幅（これをはみ出ると自動縮小。空欄にすると縮小なし）
    //  スケーリング用の文字カウントは、htmlspecialchars() した後の文字列に対して行うようにした。
    //  またPXDocのバグ（「全角が続いたあと最後の数文字が半角」の文字列を縮小すると、末尾の表示が乱れる）
    //  に対処するため、縮小したときは半角文字をすべて全角に置き換えて表示するようにした。
    function text($x, $y, $fontSize, $text, $textAnchor, $property, $maxWidth, $isBarcode = "")
    {
        $text = htmlspecialchars($text, ENT_QUOTES);
        $scalingStr = "";
        if (is_numeric($maxWidth)) {
            $scalingStr = Gen_Report::getScalingProperty(Gen_String::strlenEx($text), $maxWidth, $fontSize);
            if (strlen($scalingStr) != 0) {
                // スケーリングするときは、半角文字を全角に置き換える
                $text = mb_convert_kana($text, "RNASK");
            }
        }

        if ($isBarcode == "true") {
            $barcode_height = 150 - $fontSize - 20; // 上下paddingは10で固定
            $barcode_width = $barcode_height * 3; // バーコードの縦横比(縦は固定で、横幅を縦幅に対する倍率指定する)
            $template = '<pxd:barcode x="%d" y="%d" width="%d" height="%d" type="CODE39" data="%s" />';
            $data = sprintf($template, $x - $barcode_width / 2, $y - 70, $barcode_width, $barcode_height, $text);
            $data2 = "<text x=\"{$x}\" y=\"" . ($y + 55) . "\" font-size=\"{$fontSize}\" " .
                    ($textAnchor == "" ? "" : "text-anchor=\"{$textAnchor}\"") .
                    " {$property} {$scalingStr}>{$text}</text>\n";

            return $data . $data2;
        } else {
            return
                    "<text x=\"{$x}\" y=\"{$y}\" font-size=\"{$fontSize}\" " .
                    ($textAnchor == "" ? "" : "text-anchor=\"{$textAnchor}\"") .
                    " {$property} {$scalingStr}>{$text}</text>\n";
        }
    }

    //************************************************
    // rectタグを返す
    //************************************************
    function rect($x, $y, $width, $height, $stroke, $strokeWidth, $fill)
    {
        return
                "<rect x=\"{$x}\" y=\"{$y}\" width=\"{$width}\" height=\"{$height}\" stroke=\"{$stroke}\" stroke-width=\"{$strokeWidth}\" fill=\"#{$fill}\" />\n";
    }

    //************************************************
    // textタグを返す
    // x座標を文字の上(左詰めなら左上)、ﾊﾞｰｺｰﾄﾞ付きはﾊﾞｰｺｰﾄﾞの上(左詰めなら左上)に指定できるように変更
    //************************************************
    function text2($x, $y, $fontSize, $text, $textAnchor, $property, $maxWidth, $isBarcode = "")
    {
        $text = htmlspecialchars($text, ENT_QUOTES);
        $scalingStr = "";
        if (is_numeric($maxWidth)) {
            $scalingStr = Gen_Report::getScalingProperty(Gen_String::strlenEx($text), $maxWidth, $fontSize);
            if (strlen($scalingStr) != 0) {
                // スケーリングするときは、半角文字を全角に置き換える
                $text = mb_convert_kana($text, "RNASK");
            }
        }

        if ($isBarcode == "true") {
            $barcode_height = 150 - $fontSize - 20; // 上下paddingは10で固定
            $barcode_width = $barcode_height * 3; // バーコードの縦横比(縦は固定で、横幅を縦幅に対する倍率指定する)
            $template = '<pxd:barcode x="%d" y="%d" width="%d" height="%d" type="CODE39" data="%s" />';
            $data = sprintf($template, $x - $barcode_width / 2, $y, $barcode_width, $barcode_height, $text);
            $data2 = "<text x=\"{$x}\" y=\"" . ($y + $barcode_height + 10 + $fontSize) . "\" font-size=\"{$fontSize}\" " .
                    ($textAnchor == "" ? "" : "text-anchor=\"{$textAnchor}\"") .
                    " {$property} {$scalingStr}>{$text}</text>\n";

            return $data . $data2;
        } else {
            return
                    "<text x=\"{$x}\" y=\"" . ($y + $fontSize) . "\" font-size=\"{$fontSize}\" " .
                    ($textAnchor == "" ? "" : "text-anchor=\"{$textAnchor}\"") .
                    " {$property} {$scalingStr}>{$text}</text>\n";
        }
    }

    //************************************************
    // rectタグを返す
    // fill指定に「none」や「red」等指定できるように変更
    // ※注意 16進数(FFFFFF)で指定する時は、引数で「"#FFFFFFFF"」と指定すること
    //************************************************
    function rect2($x, $y, $width, $height, $stroke, $strokeWidth, $fill)
    {
        return
                "<rect x=\"{$x}\" y=\"{$y}\" width=\"{$width}\" height=\"{$height}\" stroke=\"{$stroke}\" stroke-width=\"{$strokeWidth}\" fill=\"{$fill}\" />\n";
    }

    //************************************************
    // lineタグを返す
    // $direction ･･･ 方向（"tate","yoko"）
    // 点線を描くときは、$propertyに「stroke-dasharray=\"10,10\"」と指定する
    //************************************************
    function line($direction, $x, $y, $length, $stroke, $strokeWidth, $property)
    {
        if ($direction == "tate") {
            return
                    "<line x1=\"{$x}\" y1=\"{$y}\" x2=\"{$x}\" y2=\"" . ($y + $length) . "\" stroke=\"{$stroke}\" stroke-width=\"{$strokeWidth}\" {$property} />\n";
        } else {
            return
                    "<line x1=\"{$x}\" y1=\"{$y}\" x2=\"" . ($x + $length) . "\" y2=\"{$y}\" stroke=\"{$stroke}\" stroke-width=\"{$strokeWidth}\" {$property} />\n";
        }
    }

    //************************************************
    // 枠つき文字列のタグを返す
    // ﾊﾞｰｺｰﾄﾞは未対応
    //************************************************
    function textInRect($x, $y, $fontSize, $text, $textAnchor, $textproperty, $width, $height, $stroke, $strokeWidth, $fill)
    {
        $data = Gen_Report::rect2($x, $y, $width, $height, $stroke, $strokeWidth, $fill);

        // ﾌｫﾝﾄｻｲｽﾞが枠の高さより大きい時は、枠の高さに変換する
        if ($fontSize > $height) {
            $fontSize = $height;
        }

        if ($textAnchor == "middle") {
            $x = $x + $width / 2;
        } else if ($textAnchor == "end") {
            $x = $x + $width - 10;
        } else {
            $x = $x + 10;
        }

        // 文字は枠の上下中央に配置
        $y = ($y + ($height / 2) - ($fontSize / 2));

        $data .= Gen_Report::text2($x, $y, $fontSize, $text, $textAnchor, $textproperty, $width);

        return $data;
    }

}