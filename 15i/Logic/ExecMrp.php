<?php

// Webではなく、単体で呼ばれるスクリプト
//
// ******** 計算対象開始日は「明日」に固定とする **********
//
// ●理由1: 理論在庫計算の問題
//
// 所要量計算を行うには､計算対象日時点の理論在庫を求める必要がある｡理論在庫は､基本的には棚卸
// 在庫にそれ以降の入出庫（受入や製造、従属需要消費を含む）を足し引きすれば求まるのだが、問題は、
// 所要量計算の対象日は将来であるという点だ｡通常､将来の入出庫は登録されていないため､登録済み
// の入出庫のほかに下記の要素を足し引きする必要がある｡
// (1) 現時点ではまだ受入登録されていないが、
//     計算対象日時点までには納品されているはずの発注数 (発注残)
// (2) 現時点ではまだ実績登録されていないが、
//     計算対象日時点までには製造されているはずの製造数 (製造残)
// (3) 現時点ではまだ登録されていないが、
//     計算対象日時点までには従属使用されているはずの数量 (従属需要残)
// 2004/2005では、上記(1)(3)を計算している（MRP計算するのは部品だけなので、(2)は関係ない。また
// 2004/2005では(2)ではなく(3)のことを「製造残」と呼んでいることに注意）。
//
// 2006では、上記(1)(2)は計算している（T_ORDER_DETAILベース）が、問題は(3)である。
// 部品表がストラクチャ型なので構成展開が簡単ではない｡結局､本日以降のすべての日について､構成展開
// による計算を行う必要がある｡
// それで、計算対象前日までの受入・実績はすべて登録ずみの状態で計算しなければならない。よって開始日
// を「明日」に固定している。
//
// ⇒・・のはずだったが、結局、初期在庫計算において use_planを考慮することにより(3)の問題もクリア
//   できたため、理由1は実質的に関係なくなった。はず。
//
// ●理由2: 再MRPの際の上書き問題
//
// 再MRPすると前回のMRP結果はすべて消去される｡
// その際、必ず全期間（今日から最後の計画日まで）を対象とすることが前提とな
// る｡一部期間だけのMRPを許すのは難しい｡なぜなら､MRP実行前に前回のMRP結果を消去することになる
// が､一部期間限定の再MRPだと､どの部分を消去したらよいかの判断が非常に難しいためだ｡ある子品目
// に従属需要が発生している際､その需要がすべて今回計算期間によるものなのか､一部は計算期間外の
// 需要によるものなのかが判断しづらい｡
//
// ●理由3: 引当の横取り問題（2008以降）
// 2008では、2006の頃から存在していた「引当の横取り問題」に対処するためMRPの変更を行った。
//
// 引当の横取り問題とは・・
//    2007までのMRPにおける引当の扱いは以下のようなものだった。
//    ・前在庫計算においては、引当を考慮しない
//        ※ただし計算対象開始日の時点ですでに引当日（引当分を使用する日）が過ぎている引当は在庫数
//        　から差し引く（すでに使用済みのはずなので）
//    ・日々の計算においては、引当日（引当分を使用する日）にだけ引当を考慮する。
//        引当日に、当日必要数に引当数を加算し、在庫と翌日の使用可能数を差し引く。
//
//    この形だと、せっかく引当していても、引当日より前に需要があるとそちらで使用されてしまい、引当日
//    にあらためてオーダーが出る。これでは引当の意味がない。
//
// そこで、2008のMRPでは、前在庫計算の際に在庫数から引当数を差し引き（つまり実在庫ではなく有効在庫を
// 計算のベースとする）、日々の計算においては引当数を考慮しないという形に変更した。
// これなら引き当てた数量が横取りされることはない。
//
// しかし新たな問題点として、計算開始日より後の入庫に対して引当が行われていた場合、計算開始日の時点
// で引当数分のオーダーが出てしまうようになった。
//    例：在庫0、4/2に100入庫、4/3の受注に100を引当　で 4/1にMRPをまわすと、4/1に100のオーダーが出て
//      しまう。
// これは要するに「所要量計算の計算対象期間開始日より後の入庫を前提とした引当が行われている」状態の
// ときに問題が発生するということ。
// そこで引当の際に現在庫（最終入出庫時点の在庫）ではなく、本日時点の在庫によって引当可能数を計算
// するようにした。
// これで問題は解決したが、この解決策は所要量計算の対象期間が明日に固定されていることが前提となる。
//

// これを設定しないとMRPが止まる
ini_set('date.timezone', 'Asia/Tokyo');

define('COMPONENTS_DIR', dirname(dirname(__FILE__)) . '/Components/');
define('LOGIC_DIR', dirname(dirname(__FILE__)) . '/Logic/');
define('APP_DIR', dirname(dirname(__FILE__)) . '/htdocs/');
define('ROOT_DIR', dirname(dirname(__FILE__)) . '/');

require_once COMPONENTS_DIR . '/Spyc.php';
// サーバー設定ファイル（gen_server_config.yml）
$serverConfig = Spyc::YAMLLoad(dirname(ROOT_DIR) . '/gen_server_config.yml');
// アプリケーション設定ファイル（gen_config.yml）
$appConfig = Spyc::YAMLLoad(ROOT_DIR . '/gen_config.yml');
// キー重複の場合、後の配列の内容が優先される
$config = array_merge($serverConfig, $appConfig);

define("GEN_POSTGRES_HOST", $config['postgresql']['host']);
define("GEN_POSTGRES_PORT", $config['postgresql']['port']);
define("GEN_POSTGRES_USER", $config['postgresql']['user']);
define("GEN_SERVER_INFO_CLASS", (isset($config['server_info_class']) ? $config['server_info_class'] : 90));

define("GEN_DATABASE_NAME", $config['database']);
if (isset($config['files_dir'])) {
    $filesDir = $config['files_dir'];
}
if (isset($config['temp_dir'])) {
    $tempDir = $config['temp_dir'];
}
if (isset($config['database'])) {
    $db = $config['database'];
}
define('GEN_FILES_DIR', $filesDir. "/");
define('GEN_TEMP_DIR', $tempDir . "/" . $db . "/Temp/");

require_once(COMPONENTS_DIR . "Db.class.php");
require_once(COMPONENTS_DIR . "String.class.php");
require_once(COMPONENTS_DIR . "Mail.class.php");
require_once(COMPONENTS_DIR . "Storage.class.php");
require_once(COMPONENTS_DIR . "File.class.php");
require_once(COMPONENTS_DIR . "Auth.class.php");
require_once(LOGIC_DIR . "Mrp.class.php");
define("ADMIN_LOGIN_ID", "admin");      
define("ADMIN_NAME", "e-commode");      

$start_date = $argv[1];
$to_date = $argv[2];
$isNaiji = ($argv[3] == 'true' ? true : false);
$isNonSafetyStock = ($argv[4] == 'true' ? true : false);
$userName = $argv[5];
$days = $argv[6];

if (Gen_String::isDateString($start_date) && Gen_String::isDateString($to_date)) {

    if (time() < strtotime($start_date) && time() < strtotime($to_date)) {      // 明日以降
        $gen_db = new Gen_Db();
        $gen_db->connect();
        Gen_String::initGetText($gen_db);
        $mrp = new Logic_MRP();

        // 通知メール（計算開始）
        $title = _g("所要量計算の開始");
        $body = _g("所要量計算が開始されました。") . "\n\n"
                . "[" . _g("開始日時") . "] " . date('Y-m-d H:i:s') . "\n"
                . "[" . _g("実行ユーザー") . "] {$userName}\n"
                . "";
        Gen_Mail::sendAlertMail('manufacturing_mrp_start', $title, $body, $userName);

        // 所要量計算実行
        $mrp->mrpMain($start_date, $to_date, $isNaiji, $isNonSafetyStock, $userName, $days);

        // 通知メール（計算終了）
        $title = _g("所要量計算の終了");
        $body = _g("所要量計算が終了しました。") . "\n\n"
                . "[" . _g("終了日時") . "] " . date('Y-m-d H:i:s') . "\n"
                . "[" . _g("実行ユーザー") . "] {$userName}\n"
                . "";
        Gen_Mail::sendAlertMail('manufacturing_mrp_end', $title, $body, $userName);
    } else {
        deleteProgress();
    }
} else {
    deleteProgress();
}

function deleteProgress()
{
    $storage = new Gen_Storage("MRPProgress");
    $storage->delete('mrp_progress.dat');
}

function _g($str)
{
    $str = _($str);
    if (isset($_SESSION['gen_setting_company']->wordconvert)) {
       foreach ($_SESSION['gen_setting_company']->wordconvert as $key => $val) {
           $str = str_replace($key, $val, $str);
       }
    }
    return $str;
}
function h($str)
{
    if (is_array($str)) {
        return array_map("h", $str);
    } else {
        return htmlspecialchars($str, ENT_QUOTES);
    }
}
