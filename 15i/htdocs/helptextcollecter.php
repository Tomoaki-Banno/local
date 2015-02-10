<?php
// チップヘルプのリスト（CSV形式）を作成する。
// ag.cgi?page=ProjectDocView&pid=1574&did=194403

die('developer only');

define('SEPARATOR', '/');
define('ROOT_DIR', dirname(dirname(__FILE__)) . SEPARATOR);    // このファイルの1階層上。
define('APP_DIR', dirname(__FILE__) . SEPARATOR);              // このファイルと同じ階層。
define('BASE_DIR', ROOT_DIR . 'Base' . SEPARATOR);
define('COMPONENTS_DIR', ROOT_DIR . 'Components' . SEPARATOR);
define('LOGIC_DIR', ROOT_DIR . 'Logic' . SEPARATOR);
define('ADMIN_NAME', 'admin');

spl_autoload_register('gen_autoload');
ini_set('date.timezone', 'Asia/Tokyo');
$gen_db = new Gen_Db_Dummy();

// むりやりevalしているので、notice や warning がいっぱい出る。非表示にする
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

// メイン
$root = dirname(__FILE__) . "/";
$res = "";
doCollect($root, '');
file_put_contents($root . "helptext.csv", $res);
echo("正常終了しました。{$root}helptext.csv が作成されました。");
   

function doCollect($target_dir, $parent) {
    global $res, $gen_db;
    
    chdir($target_dir);
    $files = glob('*');
    if (!is_array($files)||count($files)==0) return;
    foreach ($files as $file) {
        $path = $target_dir.$file;
        if (is_dir($path)) {
            doCollect($path.'/', $parent . ($parent != "" ? "_" : "") . $file);
        } else if (substr($file, -10) == ".class.php" && $file != "Model.class.php" && $file != "Report.class.php" && $file != "Login.class.php" && $file != "Logout.class.php") {
            $str = file_get_contents($path);
            $str = str_replace("\n", "", $str);
            
            // ページタイトル
            if (preg_match_all("/form\\[(\'|\")gen_pageTitle(\'|\")\\].*?=.*?;/m", $str, $matches) > 0) {
                $match = str_replace('$form[\'Logic_Inout_title\']', "\"入出庫\"", $matches[0][0]);
                $match = str_replace('Logic_Inout::classificationToTitle($form[\'gen_search_classification\'], false)', "\"入出庫\"", $match);
                eval("$" . $match);
                $title = str_replace(",", "、",$form['gen_pageTitle']);
            } else {
                $title = $path;
            }
            
            // セミコロンをarrayの区切りとみなすが、JSやCSS内のセミコロンが邪魔になるので排除する
            $str = str_replace("array();", "array()；", $str);
            $str = str_replace("();", "", $str);
            $str = str_replace("array()；", "array();", $str);
            $str = str_replace("\";\"", "\"\"", $str);
            $str = str_replace("]);", "", $str);
            $str = preg_replace("/[a-z0-9\]];/", "", $str);
            
            // チップヘルプ
            $arr = array(
                array("gen_searchControlArray", "表示条件"),
                array("gen_fixColumnArray", "リスト"),
                array("gen_columnArray", "リスト"),
                array("gen_editControlArray", "編集画面"),
            );
            foreach($arr as $one) {
                if (preg_match_all("/form\\[(\'|\")" . $one[0] . "(\'|\")\\][^\$]*?array.*?;/m", $str, $matches) > 0) {
                    foreach ($matches[0] as $match) {
                        //if (strpos($match, "helpText") !== false) {
                            $match = str_replace('$this', '$dummy', $match);
                            $match = str_replace('$helpText_noEscape', '"' . _g("以下の各画面に適用されます。") . "<br><br>・" . _g("入庫登録") . "<br>・" . _g("出庫登録") . "<br>・" . _g("使用数リスト") . "<br>・" . _g("支給登録") . '"', $match);    // Master_User_Edit
                            unset($form[$one[0]]);
                            eval("$" . $match);
                            foreach($form[$one[0]] as $col) {
                                if ((isset($col['label']) && $col['label'] != "") || (isset($col['label_noEscape']) && $col['label_noEscape'] != "")) {
                                    $res .= mb_convert_encoding($title . ",{$one[1]}," 
                                        . str_replace(",", "、", isset($col['label']) ? $col['label'] : $col['label_noEscape']) 
                                        . "," . str_replace(",", "、", isset($col['helpText']) ? $col['helpText'] : isset($col['helpText_noEscape']) ?  $col['helpText_noEscape'] : "")
                                        . "\n"
                                      ,"SJIS", "UTF-8");
                                }
                                if (isset($col['controls'])) {
                                    foreach($col['controls'] as $col2) {
                                        if ((isset($col2['label']) && $col2['label'] != "") || (isset($col2['label_noEscape']) && $col2['label_noEscape'] != "")) {
                                            $res .= mb_convert_encoding($title 
                                                . ",{$one[1]}（明細リスト）," 
                                                . str_replace(",", "、", isset($col2['label']) ? $col2['label'] : $col2['label_noEscape']) 
                                                . "," . str_replace(",", "、", isset($col2['helpText']) ? $col2['helpText'] : isset($col2['helpText_noEscape']) ?  $col2['helpText_noEscape'] : "")
                                                . "\n"
                                              ,"SJIS", "UTF-8");
                                        }
                                    }
                                }
                            }
                        //}
                    }
                }
            }
        }
    }
}

function _g($str)
{
    return $str;
}

function h($str)
{
    return $str;
}

function gen_autoload($className)
{
     $nodes = explode('_', $className);

     switch ($nodes[0]) {
         // rtrimは*_DIRの末尾についている/を削除するためにある。
         // array_sliceはクラスファイル内に、そのクラスを補助・あるいは関連する
         // 小目的クラスを定義できるようにするためにある。
         // (Logic_Receivedと同ファイルにLogic_Received_Subなど))
         case 'Base':
             $nodes[0] = rtrim(BASE_DIR, SEPARATOR);
             $nodes = array_slice($nodes, 0, 2);
             break;
         case 'Gen':
             $nodes[0] = rtrim(COMPONENTS_DIR, SEPARATOR);
             $nodes = array_slice($nodes, 0, 2);
             break;
         case 'Logic':
             $nodes[0] = rtrim(LOGIC_DIR, SEPARATOR);
             $nodes = array_slice($nodes, 0, 2);
             break;
         default :
             // 上記以外のクラス
             return;
     }
     require_once(COMPONENTS_DIR. "File.class.php");
     $fileName = Gen_File::safetyPath(join(SEPARATOR, array_slice($nodes, 0, count($nodes)-1)), end($nodes) . ".class.php");
     require_once($fileName);
 }
 
 class Gen_Db_Dummy
 {
     function query($sql) 
     {
     }
     function queryOneValue($sql) 
     {
         return false;
     }
     function queryOneRowObject($sql) 
     {
         return false;
     }
     function getArray($sql) 
     {
         return false;
     }
     function getHtmlOptionArray($sql)
     {
         return false;
     }
     function quoteParam($p)
     {
         return $p;
     }
     function existRecord($sql)
     {
         return false;
     }
 }
