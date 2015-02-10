<?php
die('developer only');

// Smarty Template(.tpl) および Javascript(.js) から getText対象文字列（_g(...)）を
// 抽出して messages/tpl_js_words.php を作成する。
//  POEditでは Smarty Template および Javascript に対応したパーサが用意されておらず、それらの
//  ファイルからgetText対象文字列を正しく抽出できない。
//  このスクリプトを実行してからPOEditでの抽出を実行すれば正しく処理できる。
//  
//  _g(...) の間に改行や文字連結を含むパターンには対応していないことに注意。
//      ☓ _g("あいう" + "えお")

$rootDir = dirname(dirname(__FILE__));
$resultFile = $rootDir . '/messages/tpl_js_words.php';

$wordArr = array();
getWords($rootDir, $wordArr);
$wordList = "<?php\n";
foreach($wordArr as $word) {
    $wordList .= "_g(\"{$word}\");\n";
}
file_put_contents($resultFile, $wordList);
echo ("<br><br><br>" . $resultFile . "が作成されました。");

function getWords($dir, &$wordArr) {
    $target = '{*.js,*.tpl}'; // ここには複数定義可能。(e.g. '{*.jpg,*.gif}')

    foreach(glob($dir . '/*', GLOB_ONLYDIR) as $sub) {
        //echo "Directory: " . $sub . "<br />";
        foreach(glob($sub . '/' . $target, GLOB_BRACE) as $file) {
            echo "parse {$file}...<br>";
            $code = file_get_contents($file);
            for ($i=0;$i<=1;$i++) {
                $quote = ($i==0 ? "\"" : "'");
                $pos = 0;
                while($pos = strpos($code, "_g(" . $quote, $pos)) {
                    $pos2 = strpos($code, $quote, $pos+4);
                    $word = substr($code, $pos+4, $pos2-$pos-4);
                    if (!in_array($word, $wordArr)) {
                        $wordArr[] = $word;
                    }
                    $pos = $pos2+1;
                }
            }
        }
        getWords($sub, $wordArr);
    }
}
