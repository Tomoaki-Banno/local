<?php

require_once(ROOT_DIR . 'PHPExcel/PHPExcel.php');
require_once(ROOT_DIR . 'PHPExcel/PHPExcel/IOFactory.php');

require_once(ROOT_DIR . 'tcpdf/config/lang/eng.php');
require_once(ROOT_DIR . 'tcpdf/tcpdf.php');

// 用紙マージンの係数（エクセル(inch)->PDF(mm)）
//  エクセルの余白（$pageMargin->getXX()で取得できる数値）はインチ単位。
//      ちなみにエクセルページ設定ダイアログではセンチ単位で表示される（コンパネの地域と言語のオプションでメートル法を指定している場合）。
//  pdfの余白はmm単位。
//  したがってインチ->mm換算するために、 0.03937 で割る必要がある。
//  ・・はずなのだが、やってみたところ下の値でちょうどよかった。
define('MARGIN_MUL', 0.04252);

// 行の高さの係数（エクセル(pt)->PDF(mm)）
// エクセルの行の高さはpt単位。1ptは0.35277...mm なので本来、係数は 0.3528でいいはずなのだが、やってみると下の値でないとうまくいかない。
// もっともディスプレイドライバやプリンタによって1ptのサイズが若干変わってくることがあるようだ。
// エクセル上でさえ、画面では収まっているのに印刷したらはみ出すとか、別PCだとはみ出すといったことがしばしばある。厳密な調整は難しい。
define('ROW_HEIGHT_MUL', 0.325);

// 列の幅の係数（エクセル(px)->PDF(mm)）
// エクセルの幅の単位は文字数(chars)。
//    ※ デフォルトフォントの半角数字の幅を1charsとする。したがって同じ幅でもデフォルトフォントを変更するとピクセル数が変わり、印刷時の横幅も変わる。
// charsからpxに変換するには、chars × 8 + 5 とすればよい。
//    ※ 「8」はExcel2000の出荷時のデフォルトフォントであるMS Pゴシック11の1文字の横幅。
//        デフォルトフォントが上記以外の場合はこの値を変更する必要がある。
//    ※ 「5」は文字列両端に2pxずつ、セル枠に1px
// 問題は pxから実寸(mm)への変換。1pxの実寸はディスプレイドライバやプリンタによってことなる。
// 下の係数は実測によって求めたもの。
// エクセル上でさえ、画面では収まっているのに印刷したらはみ出すとか、別PCだとはみ出すといったことがしばしばある。厳密な調整は難しい。
define('COL_WIDTH_MUL', 0.25);

// エクセルのデフォルトの列幅（chars）
define('DEFAULT_COLUMN_WIDTH', 8.38);

// エクセルのデフォルトの行高（pt）
define('DEFAULT_ROW_HEIGHT', 13.5);

class Gen_PDF
{

    var $_pdf;
    var $_excel;
    var $_imageCache;
    var $_pdfArr;

    // ベンチマーク関連
    var $_bench = false;
    var $_benchDetail = false;
    var $_benchParse = false;

    static function getTemplateInfo($reportCategory)
    {
        // 10i: 選択テンプレートは全ユーザー共通（選択テンプレ情報を gen_templates.dat に保存）
        // 12i: 選択テンプレートはユーザーごとになった（選択テンプレ情報を DBに保存）
        // 13i: テンプレートの格納場所を変更（セキュリティ向上、ユーザーアップロードファイルの一括管理などの理由）
        //      システムテンプレート: htdocs/ReportTemplates ⇒ (ROOT)/ReportTemplates
        //      ユーザーテンプレート: htdocs/ReportTemplates ⇒ (files_dir)/ReportTemplates
        
        global $gen_db;

        // 選択テンプレートを user_template_info から取得する（12i以降）
        // そのテーブルにレコードがなかった場合や指定されたテンプレートが無効だったときの処理は、
        // このfunctionの最後のほうでおこなっている
        $userId = $_SESSION['user_id'];
        $query = "select template_name from user_template_info where user_id = '{$userId}' and category = '{$reportCategory}'";
        $selectedTemplate = $gen_db->queryOneValue($query);
        
        $dirArr = array();
        $fpArr = array();
        
        // システムテンプレート
        $dirArr[0] = SYSTEM_REPORT_TEMPLATES_DIR . $reportCategory;
        if (!file_exists($dirArr[0]))
            throw new Exception("システム ReportTemplates にディレクトリ{$reportCategory}が存在しません。");
        $datFile = $dirArr[0] . "/gen_templates.dat";
        if (!file_exists($datFile))
            throw new Exception("システム ReportTemplates のディレクトリ{$reportCategory} にgen_templates.datが存在しません。");
        $fpArr[0] = fopen($datFile, 'r');
        fgets($fpArr[0]);  // システム gen_templates.dat の1行目は、12i以降では無意味なので読み飛ばす
        
        // ユーザーテンプレート
        $dirArr[1] = $reportCategory;
        $storage = new Gen_Storage("ReportTemplates");
        if ($storage->exist($reportCategory . "/gen_templates.dat")) {
            $datFile = $storage->get($reportCategory . "/gen_templates.dat");
            $fpArr[1] = fopen($datFile, 'r');
        } else {
            // ユーザーテンプレートディレクトリが存在しない場合、ここで作成する
            $storage->makeDir($reportCategory);
            $datFile = GEN_TEMP_DIR . 'gen_templates.dat';
            if (file_exists($datFile)) {
                unlink($datFile);
            }
            touch($datFile);
            $storage->put($datFile, true, $reportCategory . '/gen_templates.dat');
            $fpArr[1] = fopen($datFile, 'r');
        }
            
        $selectedTemplateFile = $dirArr[0] . "/" . $selectedTemplate;
        if (!file_exists($selectedTemplateFile)) {
            if ($storage->exist($reportCategory . "/" . $selectedTemplate)) {
                $selectedTemplateFile = $storage->get($reportCategory . "/" . $selectedTemplate);
            } else {
                $selectedTemplateFile = "";
            }
        }

        // テンプレートリスト（システムテンプレート + ユーザーテンプレート）
        $infoArr = array();
        $selectedNo = 1;
        $no = 1;
        foreach ($dirArr as $key => $dir) {
            while ($str = fgets($fpArr[$key])) {
                // システムテンプレートとユーザーテンプレートのディレクトリが別になったので、gen_templates.dat が空もありえる
                $str = trim($str);
                
                $pArr = explode(",", $str);
                $exists = false;
                if ($key == 0) {
                    // システムテンプレート
                    $exists = file_exists($dir . "/" . $pArr[0]);
                } else {
                    // ユーザーテンプレート
                    $storage = new Gen_Storage("ReportTemplates");
                    $exists = $storage->exist($dir . "/" . $pArr[0]);
                }
                if (!$exists)
                    throw new Exception("gen_templates.dat の内容が正しくありません。（{$pArr[0]}）");
                $infoArr[] = array(
                    "file" => $pArr[0],
                    "comment" => $pArr[1],
                    "isDefault" => trim($pArr[2]),
                    "uploader" => (isset($pArr[3]) ? str_replace("\n", "", $pArr[3]) : ''),
                    "url" => "index.php?action=download&cat=reporttemplate&repcat={$reportCategory}&file=" . urlencode($pArr[0]),
                );
                if ($pArr[0] == $selectedTemplate)
                    $selectedNo = $no;
                $no++;
            }
            fclose($fpArr[$key]);
        }

        // 10iでは指定されたテンプレートが存在しなかったときはdieしていたが、12iでは
        // 選択テンプレートの記録方式がかわったため、選択テンプレートが存在しないという事態も
        // ありうるようになった。それでその場合は最初のテンプレートを使用するようにした。
        if ($selectedTemplate == "" || !file_exists($selectedTemplateFile)) {
            $selectedTemplate = $infoArr[0]['file'];
            $selectedTemplateFile = $dirArr[0] . "/" . $selectedTemplate;
            $selectedNo = 1;
        }

        return array($selectedTemplate, $selectedTemplateFile, $infoArr, $selectedNo, $dirArr[0]);
    }

    static function putTemplateInfo($reportCategory, $selectedTemplate, $infoArr)
    {
        // 1行目
        $data = "";

        // 全ユーザーの選択テンプレートを共通にする場合は、上の行を無効にして下記を有効にする
        // （10iの仕様。gen_templates.datの1行目に選択テンプレートを記録）、
        //$data = $selectedTemplate . "\n";

        foreach ($infoArr as $info) {
            if ($info['isDefault'] != 'true')
                $data .= $info['file'] . "," . $info['comment'] . "," . $info['isDefault'] . "," . $info['uploader'] . "\n";
        }
        $remFile = GEN_TEMP_DIR . "/gen_templates.dat";
        file_put_contents($remFile, $data);
        $storage = new Gen_Storage("ReportTemplates");
        $storage->put($remFile, true, $reportCategory . "/gen_templates.dat");

        // 選択テンプレート情報を保存する（12i以降）
        self::updateSelectedTemplateInfo($reportCategory, $selectedTemplate);
    }

    static function updateSelectedTemplateInfo($reportCategory, $selectedTemplate)
    {
        global $gen_db;

        // 選択テンプレート情報を保存する（12i以降）
        $userId = $_SESSION['user_id'];
        $key = array("user_id" => $userId, "category" => $reportCategory);
        $data = array(
            'template_name' => $selectedTemplate,
        );
        $gen_db->updateOrInsert('user_template_info', $key, $data);
    }

    function createPDFFromExcel($reportCategory, $outputFileName, $query, $pageKeyColumn, $pageCountKeyColumn = "", $template = "")
    {
        global $gen_db;
        
        // *************** Main ***************
        
        $this->_pdfArr = array();
        
        // SQL内に「gen_template」というカラムがある場合、そのカラムで指定されているテンプレートを使用する。
        if (is_array($query)) {
            foreach($query as $q1) {
                $existTemplateColumn = strpos($q1, "as gen_template");
                if ($existTemplateColumn) {
                    break;
                }
            }
        } else {
            $existTemplateColumn = strpos($query, "as gen_template");
        }
        if ($existTemplateColumn) {
            // 「gen_template」がある場合：
            //  　レコードごとに頻繁にテンプレートが切り替わるとメモリを大量に消費したり処理に時間がかかったりするため、
            //    テンプレートごとに発行処理を行うようにする。
            //  　そのため、まず使用するテンプレートのリストを作成する。
            $templateQuery = "";
            if (is_array($query)) {
                $templateQueryArr = array();
                foreach($query as $q1) {
                    if (strpos($q1, "as gen_template")) {
                        $templateQueryArr[] = "select coalesce(gen_template,'') as gen_template from ({$q1}) as gen_template_sub group by coalesce(gen_template,'')";
                    }
                }
                $templateQuery = join(" union ", $templateQueryArr) . " order by gen_template";
            } else {
                $templateQuery = "select coalesce(gen_template,'') as gen_template from ({$query}) as gen_template_sub group by gen_template order by coalesce(gen_template,'')";
            }
            $arr = $gen_db->getArray($templateQuery);
  
            if ($arr) {
                $templateArr = array();
                foreach($arr as $row) {
                    if (!in_array($row['gen_template'], $templateArr)) {
                        $templateArr[] = $row['gen_template'];
                    }
                }
                foreach($templateArr as $template) {
                    // 帳票テンプレートが2種類以上ある場合はテンプレートごとに別PDFが作成される（DL時にZip圧縮される）。
                    // 1種類のみの場合は単一PDFとなる（PDFのままDLされる）。
                    $res = self::_generatePDFFromExcelMain($reportCategory, $query, $pageKeyColumn, $pageCountKeyColumn, $template, count($templateArr) > 1);
                    if ($res != "0") {
                        return $res;
                    }
                }
            } else {
                return "1";   // データなし
            }
        } else {
            // 「gen_template」がない場合（通常）：
            //  　帳票設定画面で選択されているテンプレートを使用する。
            //  　（サンプル帳票モードでは引数 $template が指定されており、そのテンプレートを使用する）
            $res = self::_generatePDFFromExcelMain($reportCategory, $query, $pageKeyColumn, $pageCountKeyColumn, $template, false);
            if ($res != "0") {
                return $res;
            }
        }
        

        // *************** Output PDF ***************
        
        if (count($this->_pdfArr) > 0) {
            // ZIP出力
            $zip = new ZipArchive();
            $zipName = $outputFileName . ".zip";
            $zipFile = GEN_TEMP_DIR . $zipName;
            // OVERWRITEを指定しないと、$outputFileNameが既存だったときに 生成されるファイルがおかしくなる
            $res = $zip->open($zipFile, ZIPARCHIVE::CREATE | ZIPARCHIVE::OVERWRITE);
            if ($res !== true) {
                throw new Exception("ZIPファイルを作成できません。");
            }
            foreach($this->_pdfArr as $onePDFName) {
                $zip->addFile($onePDFName, basename($onePDFName));
            }
            $zip->close();
            Gen_Download::DownloadFile($zipFile, $zipName, false);
            
        } else {
            // PDF出力
            // 第2引数
            //    "D"
            //        ブラウザの設定やAdobe Readerの設定に関係なく、毎回「開くまたは保存」ダイアログが出る。
            //    "I"
            //        ブラウザ内で開く（確認ダイアログなし）。
            //
            //        ただし Adobe Readerの[編集]-[環境設定]-[インターネット] で「PDFをブラウザに表示」の
            //        チェックを外している場合、Adobe Reader が立ち上がって開く。
            //        ※ Firefox/Chromeの場合、上記チェックをはずすと、Adobe Reader表示時に元ページが空白になる。
            //        　（Gen以外のサイトでも同じなので、仕様っぽい）
            //        　 Firefoxの場合は次の項目の設定で「Adobe Readerで開く」を選ぶことにより回避できる。
            //        また Firefoxの場合、[ツール]-[オプション]-[プログラム]のAdobe Acrobat Documentで
            //        　「Firefox内で表示」以外を選択している場合、その設定が優先される。
            //        　（毎回確認/ファイルを保存/Adobe Readerで開く/Firefox内で表示）
            $this->_pdf->Output($outputFileName, "D");
        }

        return "0";   // success
    }
    
    private function _generatePDFHeader()
    {
        $this->_pdf = new TCPDF();
        $this->_pdf->setPrintHeader(false);
        $this->_pdf->setPrintFooter(false);

        // 画面表示時の倍率
        $this->_pdf->SetDisplayMode('default');

        // フォントサブセット（フォントのうち、実際にPDF内で使用されている文字のものだけを埋め込む処理）を無効にする。
        // フォントサブセットが有効だとPDFのサイズが小さくなるが、処理に時間がかかる上、MSゴシックの半角カナや
        // MS明朝のすべての文字の字間がおかしくなる。
        $this->_pdf->setFontSubsetting(false);
    }
    
    // PDFをサーバー上のファイルとして保存。複数PDF（pdfkey）モードで使用する
    private function _savePDF($fileName)
    {
	$fileName = str_replace( array('\\','/',':','*','?','"','<','>','|'), "_", $fileName );
        $pdfName = GEN_TEMP_DIR . mb_convert_encoding($fileName . ".pdf", "SJIS", "UTF-8");
        $this->_pdf->Output($pdfName, "F");
        $this->_pdf->_destroy();
        unset($this->_pdf);
        $this->_pdfArr[] = $pdfName;
    }
    
    private function _generatePDFFromExcelMain($reportCategory, $query, $pageKeyColumn, $pageCountKeyColumn, $template, $isMultiTemplateMode)
    {
        global $gen_db;

        if ($this->_bench) {
            require_once dirname(dirname(__FILE__)) . '/pear/Benchmark/Timer.php';
            $timer = new Benchmark_Timer(true);
        }
        
        // *************** template load ***************
        // セルフォーマット適用（PHPExcel_Style_NumberFormat::toFormattedString()）において、
        // 数値のカンマ区切りを有効にするための設定。
        // これを実行するとPHPのロケール情報として以下の値がセットされる。
        //  windows: コンパネの「地域と言語の設定」
        //  linux: 環境変数
        // くわしくは　http://www.php.net/manual/ja/function.setlocale.php
        setlocale(LC_ALL, '');

        // Excelテンプレートのパスを取得
        $tempInfo = $this->getTemplateInfo($reportCategory);
        if ($template == "") {
            // 通常は選択テンプレートを使用
            $excelPath = $tempInfo[1];
            $useTemplateName =  $tempInfo[0];
        } else {
            // テンプレートが指定されているとき（サンプル、もしくはレコードによる指定）
            $excelPath = $tempInfo[4] . "/" . $template;
            if (!file_exists($excelPath)) {
                $storage = new Gen_Storage("ReportTemplates");
                $excelPath = $storage->get($reportCategory . "/" . $template);
            }
            // 指定されたテンプレートが存在しないときはエラーとする
            //　 以前は設定画面で選択されたテンプレートを使用していたが、下記スレッドの指摘により変更
            //   ag.cgi?page=ProjectDocView&pid=1434&did=226308
            if (!file_exists($excelPath)) {
                return $template;  // 指定テンプレートが存在しない 
            }
            $useTemplateName =  $template;
        }

        if (!file_exists($excelPath)) {
            throw new Exception("テンプレート {$excelPath} は存在しません。");
        }

        // Excelテンプレートのロード
        if (substr($excelPath, -4) == "xlsx") {
            $excelType = "Excel2007";
        } else {
            $excelType = "Excel5";
        }
        $reader = PHPExcel_IOFactory::createReader($excelType);
        if ($this->_bench)
            $timer->setMarker('createReader');

        $xl = $reader->load($excelPath);
        if ($this->_bench)
            $timer->setMarker('load');

        // *************** template parse (first page) ***************

        $xl->setActiveSheetIndex(0);
        $sheet = $xl->getActiveSheet();

        // 関数の計算結果のキャッシュを無効にする。
        // PHPExcelでは、関数を含むセルに対して getCaluclatedValue() したときに、計算結果を
        // キャッシュするという機能がある。そのセルに対する2回目以降の getCaluclatedValue() では
        // キャッシュされた値を返す。
        // しかしいちどセルに getCaluclatedValue()すると、その後 setValue()で値を書き換えても
        // 計算結果キャッシュが書き換わらず、常に最初の計算結果が返されてしまうというバグ（たぶん）が
        // あるようだ。
        // そのためデフォルトのままだと、関数を含むセルについて、複数ページを出力する際に2ページ目以降
        // にも1ページ目の値が表示されてしまう。（ページごとに$sheetをcloneしてはいるが、セルオブジェクト
        // は参照になるようだ。）この現象にはずいぶん悩まされた。
        // 上記の現象を避けるため、キャッシュ機能を無効にしている。
        // 将来的にPHPExcelで上記バグが解消されたら、この1文は削除してよい。
        PHPExcel_Calculation::getInstance()->setCalculationCacheEnabled(false);

        if ($this->_bench)
            $timer->setMarker('getSheet');

        // テンプレートのパース（1シート目）
        $excel = self::_parseSheet($sheet);
        
        unset($pageFormat);
        unset($pageOrientation);

        // A1セルの読み取り
        $matches = "";
        $a1val = $excel['valueArr'][0][1];
        if (preg_match_all("(\[\[[^\]]*\]\])", $a1val, $matches) > 0) {
            foreach ($matches[0] as $match) {
                // orderby指定
                if (substr($match, 0, 10) == "[[orderby:") {
                    $orderbyCol = str_replace("[[orderby:", "", str_replace("]]", "", $match));
                    $orderbyColArr = explode(',', $orderbyCol);
                    $a1val = str_replace($match, '', $a1val);
                    
                // pdfkey指定
                } else if (substr($match, 0, 9) == "[[pdfkey:") {
                    $templatePdfKeyColumn = str_replace("[[pdfkey:", "", str_replace("]]", "", $match));
                    if (!isset($orderbyColArr)) {
                        $orderbyColArr = array($templatePdfKeyColumn);
                    } else {
                        array_unshift($orderbyColArr, $templatePdfKeyColumn);  // orderbyの先頭に追加
                    }
                    $a1val = str_replace($match, '', $a1val);
                    
                // pagekey指定
                } else if (substr($match, 0, 10) == "[[pagekey:") {
                    $templatePageKeyColumn = str_replace("[[pagekey:", "", str_replace("]]", "", $match));
                    $a1val = str_replace($match, '', $a1val);

                // groupby指定
                } else if (substr($match, 0, 10) == "[[groupby:") {
                    $groupbyCol = str_replace("[[groupby:", "", str_replace("]]", "", $match));
                    $groupbyColArr = explode(',', $groupbyCol);
                    $a1val = str_replace($match, '', $a1val);

                // querymode指定
                } else if (substr($match, 0, 12) == "[[querymode:") {
                    $querymode = str_replace("[[querymode:", "", str_replace("]]", "", $match));
                    $a1val = str_replace($match, '', $a1val);

                // pagecopy指定
                } else if (substr($match, 0, 11) == "[[pagecopy:") {
                    $pagecopy = str_replace("[[pagecopy:", "", str_replace("]]", "", $match));
                    $a1val = str_replace($match, '', $a1val);

                // 用紙サイズと向きの指定
                // 　[[papersize:縦サイズmm,横サイズmm,向き]]  向きは L or P
                // 　この指定がない場合、テンプレートの用紙サイズが使用される（A4/B5/Letter。それ以外ならA4）
                } else if (substr($match, 0, 12) == "[[papersize:") {
                    $papersize = str_replace("[[papersize:", "", str_replace("]]", "", $match));
                    $sizeArr = explode(',', $papersize);
                    if (count($sizeArr) >= 2) {
                        $sizeArr[0] = Gen_String::trimEx($sizeArr[0]);
                        $sizeArr[1] = Gen_String::trimEx($sizeArr[1]);
                        if (Gen_String::isNumeric($sizeArr[0]) && Gen_String::isNumeric($sizeArr[1])) {
                            // このために tcpdf.phpをハックしている（tcpdf.phpを「FREE_」で検索）
                            $excel['pageFormat'] = array($sizeArr[0], $sizeArr[1]);
                            if (count($sizeArr) >= 3) {
                                if ($sizeArr[2] == 'L') {
                                    $excel['pageOrientation'] = "L";
                                } else {
                                    $excel['pageOrientation'] = "P";    // デフォルト
                                }
                            }
                            $pageFormat = $excel['pageFormat'];
                            $pageOrientation = $excel['pageOrientation'];
                        }
                    }
                    $a1val = str_replace($match, '', $a1val);
                }
            }
            $excel['valueArr'][0][1] = $a1val;
            $sheet->setCellValueByColumnAndRow(0, 1, $a1val);
        }

        if ($this->_bench)
            $timer->setMarker('parseSheet');

        // *************** query ***************

        // queryが複数指定されていた場合の処理（querymode）
        //    $queryが複数指定されていた場合、A1セルのquerymode指定でどのqueryを指定するかを決める。
        //    querymodeが未指定の場合、最初のqueryを使用する。
        if (is_array($query)) {
            if (!isset($querymode) || !Gen_String::isNumeric($querymode) || !isset($query[$querymode])) {
                $querymode = 0;
            }
            $query = $query[$querymode];
        }

        // orderbyの処理
        if (isset($orderbyColArr) && is_array($orderbyColArr)) {
            $newOrderbyColArr = array();
            foreach ($orderbyColArr as $col) {
                $isDesc = strpos($col, ' desc') !== false;
                $col = str_replace(' desc', '', $col);
                if (strpos($query, 'as ' . $col) === false) {
                    // detailタグの処理
                    if (strpos($query, 'detail_' . $col) !== false) {
                        $newOrderbyColArr[] = 'detail_' . $col . ($isDesc ? ' desc' : '');
                    } else {
                        // 存在しないタグは無視（システムタグはアップロード時にチェックされていない）
                    }
                } else {
                    $newOrderbyColArr[] = $col . ($isDesc ? ' desc' : '');
                }
            }
            // orderby指定をするため、select * from ($query) as t_temp order by.. のようにサブクエリ化したほうが
            // 簡単に思えるが、それだと「'100' as 製番」のようなリテラルカラムをorder by指定したときにSQLエラーになる。
            $orderbyCol = join(',', $newOrderbyColArr);
            if ($orderbyCol != '') {
                $orderByPos = strrpos(strtolower($query), "order by");
                if ($orderByPos === false) {
                    $orderByPos = strlen($query) + 1;
                }
                $query = substr($query, 0, $orderByPos) . " order by $orderbyCol";
            }
        }

        // pagekeyの処理
        if (isset($templatePageKeyColumn)) {
            if ($templatePageKeyColumn == "") {
                // 空欄指定（改ページしない）
                $selectPos = strpos(strtolower($query), "select");
                if ($selectPos !== false) {
                    $query = substr($query, 0, $selectPos + 6) . " 1 as gen_dummy," . substr($query, $selectPos + 6);
                    $pageKeyColumn = "gen_dummy";
                }
            } else {
                if (strpos($query, 'as ' . $templatePageKeyColumn) === false) {
                    // detailタグの処理
                    if (strpos($query, 'detail_' . $templatePageKeyColumn) !== false) {
                        $pageKeyColumn = 'detail_' . $templatePageKeyColumn;
                    } else {
                        // 存在しないタグは無視（システムタグはアップロード時にチェックされていない）
                    }
                } else {
                    $pageKeyColumn = $templatePageKeyColumn;
                }
            }
        }

        // pdfkeyの処理
        if (isset($templatePdfKeyColumn)) {
            if ($templatePdfKeyColumn != "") {
                if (strpos($query, 'as ' . $templatePdfKeyColumn) === false) {
                    // detailタグの処理
                    if (strpos($query, 'detail_' . $templatePdfKeyColumn) !== false) {
                        $pdfKeyColumn = 'detail_' . $templatePdfKeyColumn;
                    } else {
                        // 存在しないタグは無視（システムタグはアップロード時にチェックされていない）
                    }
                } else {
                    $pdfKeyColumn = $templatePdfKeyColumn;
                }
            }
        }

        // groupbyの処理
        if (isset($groupbyColArr) && is_array($groupbyColArr) && $groupbyCol != "") {
            // groupbyKeyがdetailタグだったときの処理
            foreach ($groupbyColArr as $col) {
                if (strpos($query, 'as ' . $col) === false) {
                    // detailタグの処理
                    if (strpos($query, 'detail_' . $col) !== false) {
                        $newGroupbyColArr[] = 'detail_' . $col;
                    } else {
                        // 存在しないタグは無視（システムタグはアップロード時にチェックされていない）
                    }
                } else {
                    $newGroupbyColArr[] = $col;
                }
            }
            $groupbyCol = join(",", $newGroupbyColArr);
            $groupbyColArr = $newGroupbyColArr;
            
            // カラム名と型を取得する
            $tempTableName = "gen_report_groupby";
            $gen_db->createTempTable($tempTableName, $query . " limit 1", true);
            $colArr = $gen_db->getArray("
                select 
                    attname, typname 
                from 
                    pg_attribute 
                    inner join pg_type on pg_type.oid = atttypid
                where 
                    attrelid = (select relid from pg_stat_all_tables where relname = '{$tempTableName}') 
                    and attnum > 0");

            // 2段階のサブクエリとする。
            //  内側：
            //      groupby： pageKey, groupbyKey
            //      数値カラム： detailはsum、それ以外はmax
            //      非数値カラム： max
            //  外側：
            //      groupby： groupbyKey
            //      数値カラム： sum
            //      非数値カラム： max

            // 内側
            $wrapSelect = "";
            foreach($colArr as $key => $col) {
                // エイリアスのないリテラルカラム（「1」など）は処理できないのでスキップする
                if ($col['attname'] == '?column?') {
                    unset($colArr[$key]);
                    continue;
                }
                // リテラルカラム（'100' as xxxx など）を親クエリで集約するとSQLエラーになることへの対処。
                $cast = "";
                if ($col['typname'] == "unknown") {    // リテラルカラムはunknownになる
                    $cast = "::text";
                } 
                // 集約関数
                $wrapSelect .= ($wrapSelect == "" ? "select " : ",");
                if ($col['attname'] == $pageKeyColumn || in_array($col['attname'], $groupbyColArr)) {
                    $agg = "";
                    // リテラルカラム対策
                    if (in_array($col['attname'], $groupbyColArr) && $col['typname'] == "unknown") {
                        foreach($groupbyColArr as $key => $gCol) {
                            if ($col['attname'] == $gCol) {
                                $groupbyColArr[$key] = $gCol . "::text";
                                $groupbyCol = join(",",$groupbyColArr);
                                break;
                            }
                        }
                    } 
                } else if (($col['typname'] == "numeric" || $col['typname'] == "int4") && substr($col['attname'], 0, 7) == "detail_") {
                    $agg = "sum";
                } else {
                    $agg = "max";
                }
                $wrapSelect .= "{$agg}({$col['attname']}{$cast}) as {$col['attname']}";
            }
            $query = "{$wrapSelect} from ({$query}) as t_gen_wrap2 group by {$pageKeyColumn}, {$groupbyCol}";
            
            // 外側
            $wrapSelect2 = "";
            foreach($colArr as $col) {
                $wrapSelect2 .= ($wrapSelect2 == "" ? "select " : ",");
                if (in_array($col['attname'], $groupbyColArr)) {
                    $agg = "";
                } else if ($col['typname'] == "numeric" || $col['typname'] == "int4") {
                    $agg = "sum";
                } else {
                    $agg = "max";
                }
                $wrapSelect2 .= "{$agg}({$col['attname']}) as {$col['attname']}";
            }
            $query = "{$wrapSelect2} from ({$query}) as t_gen_wrap1 group by {$groupbyCol}";
            if (isset($orderbyCol)) {
                $query .= " order by {$orderbyCol}";
            }
        }
        
        // SQL内に「gen_template」というカラムがある場合、そのカラムで指定されているテンプレートを使用する。
        // その場合、テンプレートごとにこのfunctionが呼び出されている（$templateにテンプレート名がセットされているので
        // 該当するレコードのみ印刷対象とする）
        if (strpos($query, "as gen_template")) {
            $query = "select * from ({$query}) as t_gen_wrap_template where coalesce(gen_template,'') = '{$template}'";
        }

        // queryの実行
        $data = null;
        if ($query != "") {
            $data = $gen_db->getArray($query);

            if (!is_array($data))
                return "1";     // データなし
        }

        // 自社情報
        $company = $gen_db->queryOneRowObject("select * from company_master");

        // totalタグ用に、ページブレイクまでの各カラム値の合計を取得しておく
        $totalArr = self::_calcTotal($data, 0, $pageKeyColumn);
        if ($this->_bench)
            $timer->setMarker('query');

        // *************** page count ***************

        // 総ページ数（ページブレークまでのページ数）の計算。
        //  [[総ページ数]]タグと、発行ページ数制限（レコード数よりページ数がサーバー負荷に影響する）のために計算しておく。
        //  途中、テンプレート2シート目が必要になることがわかったら、そちらもパース処理しておく。
        //　なお、テンプレートの1シートのサイズが大きい場合、1シートがPDFの複数ページになることがあるが、その場合も
        //　1シートが1ページと計算されることに注意。そのため、総ページ数タグと実際のPDFのページ数は一致しないことがある。
        //  また総ページ数計算は、1行に1つの[[Repeat]]タグが存在していることを前提としている。
        //　1行に複数の[[Repeat]]タグが含まれているとページ数表示が不正になるので注意。（「3/2ページ」のようになることがある）
        $totalPageCount = 1;
        $thisExcel = $excel;
        if (is_array($data)) {  // いまのところ、データなしの場合はここへ来ないはずだが一応
            $pageStartY = 0;
            $pageCount = 1;
            $line = 1;
            $cache = $data[0][$pageKeyColumn];
            $pageMaxRecordCount = $excel['recordCount'];
            $cnt = count($data);
            if ($pageCountKeyColumn != "")
                $totalPageKeyCache = $data[0][$pageCountKeyColumn];

            for ($y = 0; $y < $cnt; $y++) {
                if ($cache != $data[$y][$pageKeyColumn]) {
                    // ページブレーク
                    $totalPageCount++;
                    $line = 1;
                    $cache = $data[$y][$pageKeyColumn];
                    $pageMaxRecordCount = $excel['recordCount'];
                    //  pageCountKeyカラムが指定されている場合は、総ページ数を pageKeyではなくそのカラムに
                    //　基づいてカウントする。
                    //　たとえば請求書では、改ページは受注得意先ごとに行うが、ページ数表示は請求先ごと。
                    if ($pageCountKeyColumn != "") {
                        if ($totalPageKeyCache != $data[$y][$pageCountKeyColumn]) {
                            $totalPageKeyCache = $data[$y][$pageCountKeyColumn];
                            for ($yy = $pageStartY; $yy < $y; $yy++) {
                                $data[$yy]['総ページ数'] = $pageCount;
                            }
                            $pageStartY = $y;
                            $pageCount = 1;
                        } else {
                            $pageCount++;
                        }
                    } else {
                        for ($yy = $pageStartY; $yy < $y; $yy++) {
                            $data[$yy]['総ページ数'] = $pageCount;
                        }
                        $pageStartY = $y;
                        $pageCount = 1;
                    }
                } else if ($line > $pageMaxRecordCount) {
                    // ページ明細行数超え
                    $totalPageCount++;
                    $pageCount++;
                    $line = 1;
                    // テンプレート2ページ目のパース（なければ1ページめ用をそのまま使う）
                    if ($xl->getSheetCount() > 1) {
                        if (!isset($sheet2)) {
                            $xl->setActiveSheetIndex(1);
                            $sheet2 = $xl->getActiveSheet();
                            $excel2 = self::_parseSheet($sheet2);
                            if (isset($pageFormat)) {
                                $excel2['pageFormat'] = $pageFormat = $excel['pageFormat'];
                                $excel2['pageOrientation'] = $pageOrientation;
                            }
                        }
                        $pageMaxRecordCount = $excel2['recordCount'];
                    }
                }
                $line++;
            }
            for ($yy = $pageStartY; $yy < $y; $yy++) {
                $data[$yy]['総ページ数'] = $pageCount;
            }
        }
        if ($this->_bench)
            $timer->setMarker('calcPageCount');

        // ページ数制限 (gen_config.ymlで設定)
        // 　$totalPageCount は、テンプレートの1シート分が1ページとしてカウントされている。
        // 　1シートのサイズが大きくPDFの複数ページ分になる場合、実際に出力されるPDFのページ数は 
        // 　$totalPageCount より多くなる。
        // 　その場合の実際のPDFページ数はこの段階ではわからない。
        // 　ページ数オーバーのエラーはなるべく早い段階で出せたほうがいいので、ここではとりあえず
        // 　テンプレートのシート数でチェックを行っておき、あとでPDFを作成するときに実際の出力
        // 　ページ数でチェックを行っている。
        if ($totalPageCount > GEN_REPORT_MAX_PAGES) {
            return "2";   // ページ数が多すぎる
        }

        // *************** generate pdf ***************

        self::_generatePDFHeader();

        // *************** main ***************

        $pageKeyColumnCache = $data[0][$pageKeyColumn];     // ページキー
        if (isset($pdfKeyColumn)) {
            $pdfKeyColumnCache = $data[0][$pdfKeyColumn];          // PDFキー
        }
        $pageNo = 1;    // ページ数（ページキーブレイクまでのページ数）
        $page = 1;      // ページ数（全体までのページ数）
        $rowIndex = 0;
        $dataCount = count($data);

        if ($pageCountKeyColumn != "")
            $totalPageKeyColumnCache = $data[0][$pageCountKeyColumn];

        // PDF1ページ分ごとにループ
        //  テンプレートが大きい場合は、複数のPDFページが作成されることもある
        $thisExcel = $excel;    // Excelデータを格納した配列
        $thisSheet = $sheet;    // PHPExcel Sheetオブジェクト

        if ($this->_bench)
            $timer->setMarker('pdfInit');
        
        // ベースページの作成
        //  罫線処理は時間がかかるので、ページごとに行うのではなく、最初に罫線・図・ベタのみのページを作成しておいて、
        //  各ページがそれをコピーして使用するようにする。        
        self::_generatePDFBasePage($excel, $sheet);
        $currentBasePage = 1;
        $basePageCount1 = $this->_pdf->getNumPages();
        if (isset($sheet2)) {
            self::_generatePDFBasePage($excel2, $sheet2);
        }
        $basePageCount2 = $this->_pdf->getNumPages() - $basePageCount1;

        if ($this->_bench)
            $timer->setMarker('generate base page');

        while (true) {
            // *************** tag to value ***************
            // テンプレートのタグをDB値に置換

            $xCount = $thisExcel['xCount'];
            $yCount = $thisExcel['yCount'];
            $styleArr = $thisExcel['styleArr'];

            // ページ複製値取得
            if (isset($pagecopy) && $pagecopy != "") {
                if (is_numeric($pagecopy)) {
                    $copyCount = $pagecopy;
                } else {
                    if (isset($data[$rowIndex][$pagecopy])) {
                        $copyCount = $data[$rowIndex][$pagecopy];
                    } else if (isset($data[$rowIndex]["detail_{$pagecopy}"])) {
                        $copyCount = $data[$rowIndex]["detail_{$pagecopy}"];
                    } else {
                        $copyCount = 1;
                    }
                }
                if (!is_numeric($copyCount) || $copyCount <= 0) {
                    $copyCount = 1;
                }
            } else {
                $copyCount = 1;
            }

            $existDetail = false;
            // 通常セル
            self::_tagToValue(false, $thisSheet, $xCount, $yCount, $styleArr, $data, $pageNo, $copyCount, $rowIndex, $pageKeyColumn, $company, $totalArr, $existDetail);
            // Repeatセル
            self::_tagToValue(true, $thisSheet, $xCount, $yCount, $styleArr, $data, $pageNo, $copyCount, $rowIndex, $pageKeyColumn, $company, $totalArr, $existDetail);
            if ($this->_bench)
                $timer->setMarker('tagToValue' . $page);

            // *************** generate PDF ***************
            // テンプレートを元にPDFを作成。
            // PDF1ページ分を処理。ただしテンプレートが大きい場合は、複数のPDFページが作成されることもある

            // ベースページ（罫線・図・ベタのみのページ）をコピーして新たなPDFページを作る
            $lastPage = $this->_pdf->getPage();    
            $basePageCount = ($currentBasePage == 1 ? $basePageCount1 : $basePageCount2);
            for ($i = 1; $i <= $basePageCount; $i++) {            
                $this->_pdf->copyPage($currentBasePage + $i - 1);
            }
            $this->_pdf->setPage($lastPage + 1);
            
            // PDFページにテンプレートのセルの内容を書き込む
            self::_writeValueToPDF($thisExcel, $thisSheet);

            // pagecopyタグの処理
            if ($copyCount > 1) {
                $copyPageFrom = $this->_pdf->getPage() - $basePageCount + 1;
                for ($i = 1; $i <= $basePageCount; $i++) { 
                    for ($count = 1; $count < $copyCount; $count++) {
                        $this->_pdf->copyPage($copyPageFrom + $i - 1);
                    }
                }
            }
            
            // ページ数制限チェック。
            //  PDF作成前にもチェックしているのに ここでもチェックしている理由は、上のほうの「ページ数制限」の
            //　コメントを参照。
            if ($this->_pdf->PageNo() > GEN_REPORT_MAX_PAGES) {
                return "2";   // ページ数が多すぎる
            }
            
            if ($this->_bench)
                $timer->setMarker('generatePDFPage' . $page);

            // *************** go to next record ***************
            // レコードがなくなったら終了
            if ($dataCount <= $rowIndex)
                break;

            // 次のページへ
            $page++;
            $pageNo++;

            if ($pageKeyColumnCache != $data[$rowIndex][$pageKeyColumn] || (isset($pdfKeyColumn) && $pdfKeyColumnCache != $data[$rowIndex][$pdfKeyColumn])) {
                // ページブレークのとき：　ページ数をリセット。テンプレートが2ページ目になっているときは1ページ目にもどす
                //  pageCountKeyカラムが存在するときはページ数のリセットを行わない
                if ($pageCountKeyColumn == "")
                    $pageNo = 1;
                $currentBasePage = 1;
                $thisExcel = $excel;
                $thisSheet = $sheet;
                $pageKeyColumnCache = $data[$rowIndex][$pageKeyColumn];

                // totalタグ用に、ページブレイクまでの各カラム値の合計を取得しておく
                $totalArr = self::_calcTotal($data, $rowIndex, $pageKeyColumn);
            } else {
                // 未ページブレークのとき：　2ページめ用のテンプレートシートを探す（なければ1ページめ用をそのまま使う）
                if (isset($sheet2)) {
                    $currentBasePage = $basePageCount1 + 1;
                    $thisExcel = $excel2;
                    $thisSheet = $sheet2;
                }
            }

            // pdfkey処理
            if (isset($pdfKeyColumn) && $pdfKeyColumnCache != $data[$rowIndex][$pdfKeyColumn]) {
                // ベースページの削除 
                for ($i = 1; $i <= ($basePageCount1 + $basePageCount2); $i++) {
                    $this->_pdf->deletePage(1);  
                }        
                
                // PDFをローカルファイルとして保存
                self::_savePDF($pdfKeyColumnCache);

                // 次のPDFを作成
                self::_generatePDFHeader();
                
                // 新しいベースページ
                self::_generatePDFBasePage($excel, $sheet);
                $currentBasePage = 1;
                $basePageCount1 = $this->_pdf->getNumPages();
                if (isset($sheet2)) {
                    self::_generatePDFBasePage($excel2, $sheet2);
                }
                $basePageCount2 = $this->_pdf->getNumPages() - $basePageCount1;
                
                $pdfKeyColumnCache = $data[$rowIndex][$pdfKeyColumn];
            }
            
            // pageCountKeyカラムが存在するとき用のページ数リセット
            if ($pageCountKeyColumn != "" && $totalPageKeyColumnCache != $data[$rowIndex][$pageCountKeyColumn]) {
                $pageNo = 1;
                $totalPageKeyColumnCache = $data[$rowIndex][$pageCountKeyColumn];
            }

            // rev.20110516で次の2行を追加。
            // これがなかったため、1ページ目用と2ページ目以降用のテンプレートが切り替わる際に、
            // 前のテンプレートのxyサイズに基づいてセルタグの書き戻しが行われてしまう不具合があった。
            // その結果として、前のテンプレートより今回のテンプレートのサイズが大きい場合に、
            // 一部のセルが書き戻されず、前のレコードでそのテンプレートを使用したときのセルの内容が
            // そのまま表示されてしまうということが起きていた。
            // 具体的には、次のすべての条件を満たすケースが問題となった。
            // 　・ヘッダ/明細形式の帳票（pageKeyが設定されている帳票）
            // 　・テンプレートの1ページ目と2ページ目以降のフォーマットが異なる
            // 　・2レコード以上を同時に印刷する。なおかつ、各レコードに2ページ分以上の明細行データが含まれている
            $xCount = $thisExcel['xCount'];
            $yCount = $thisExcel['yCount'];

            // テンプレートの値を元に戻す
            //  テンプレートのタグが値に書き換えられてしまうので、parseSheetの際に保存しておいた
            //  値を書き戻す。
            //  cloneで$sheetのコピーを作っておく方式だと処理がもっと簡単なのだが、PHPExcelがメモリを相当消費してしまい、
            //  多数のページがあるときにFatal Errorになるので、この方法に変えた。
            for ($y = 1; $y <= $yCount; $y++) {
                for ($x = 0; $x <= $xCount; $x++) {   // $xは0始まり
                    if (isset($thisExcel['valueArr'][$x][$y]))
                        $thisSheet->setCellValueByColumnAndRow($x, $y, $thisExcel['valueArr'][$x][$y]);
                }
            }
        }
        
        if (is_array($this->_imageCache)) {
            foreach($this->_imageCache as $img) {
                unlink($img);
            }
        }

        // ベースページの削除 
        for ($i = 1; $i <= ($basePageCount1 + $basePageCount2); $i++) {
            $this->_pdf->deletePage(1);  
        }        
        
        // Excel出力モード用の処理
        //$objWriter = new PHPExcel_Writer_Excel5($xl);
        //header("Pragma: public");
        //header("Expires: 0");
        //header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        //header("Content-Type: application/force-download");
        //header("Content-Type: application/octet-stream");
        //header("Content-Type: application/download");
        //header("Content-Disposition: attachment;filename=new.xls");
        //header("Content-Transfer-Encoding: binary ");
        //$objWriter->save('php://output');

        // PHPExcel の メモリ解放
        $xl->disconnectWorksheets();
        unset($xl);
        
        // 複数PDF(pdfkey)モード用
        if (isset($pdfKeyColumn)) {
            // 最後のPDFをローカルファイルとして保存
            self::_savePDF($pdfKeyColumnCache);
        } else if ($isMultiTemplateMode) {
            self::_savePDF($useTemplateName);
        }

        if ($this->_bench) {
            $timer->close();
            echo("memory peak usage: " . ceil(memory_get_peak_usage() / 1024 / 1024) . "MB");
        }

        return "0";   // success
    }

    //各値の合計値（ページブレイクまで）を計算。totalタグ用
    private function _calcTotal($data, $rowIndex, $pageKeyColumn)
    {
        if (!is_array($data))
            return;

        $resArr = array();
        $cache = $data[$rowIndex][$pageKeyColumn];
        foreach ($data[0] as $name => $val) {
            $resArr[$name] = 0;
        }
        $cnt = count($data);
        for ($y = $rowIndex; $y < $cnt; $y++) {
            if ($cache != $data[$y][$pageKeyColumn])
                break;
            foreach ($data[$y] as $name => $val) {
                if (Gen_String::isNumeric($val)) {
                    $resArr[$name] += $val;
                }
            }
        }
        return $resArr;
    }

    // Excelテンプレート（シート）の情報を読み取ってプロパティ配列として返す。
    //  読み取るだけでなく、テンプレートの一部のセルを書き換えていることに注意。
    private function _parseSheet($sheet)
    {
        if ($this->_benchParse) {
            require_once dirname(dirname(__FILE__)) . '/pear/Benchmark/Timer.php';
            $timerParse = new Benchmark_Timer(true);
        }

        $excel = array();

        // 用紙サイズ
        $pageSetup = $sheet->getPageSetup();
        $paperSize = $pageSetup->getPaperSize();
        // PHPExcel側の対応サイズ一覧は、 PHPExcel/Worksheet/PageSetup.class.php の冒頭を参照
        //  ※しかしエクセルで使用できる用紙サイズはそのPCにインストールされているプリンタドライバによって
        //    決まるので、一般的なサイズのみにしておくのが無難
        // tcpdf側の対応サイズ一覧は、 tcpdf.php の1907行目あたりを参照
        // テンプレートに[[papersize:]]タグがある場合、ここでの設定が上書きされることに注意
        switch ($paperSize) {
            case 1:
                $excel['pageFormat'] = "LETTER";
                break;
            case 9: default:
                $excel['pageFormat'] = "A4";
                break;
            case 13:
                $excel['pageFormat'] = "B5";
                break;
        }

        // 用紙の向き
        // テンプレートに[[papersize:]]タグがある場合、ここでの設定が上書きされることに注意
        if ($pageSetup->getOrientation() == "landscape") {   // 用紙方向  portrait:縦、landscape:横
            $excel['pageOrientation'] = "L";
        } else {
            $excel['pageOrientation'] = "P";
        }

        if ($this->_benchParse)
            $timerParse->setMarker("Parse: Read Paper Setting");

        // 余白（マージン）
        $pageMargin = $sheet->getPageMargins();
        $excel['topMargin'] = round($pageMargin->getTop() / MARGIN_MUL, 1);
        $excel['leftMargin'] = round($pageMargin->getLeft() / MARGIN_MUL, 1);
        $excel['rightMargin'] = round($pageMargin->getRight() / MARGIN_MUL, 1);
        $excel['bottomMargin'] = round($pageMargin->getBottom() / MARGIN_MUL, 1);
        $excel['headerMargin'] = round($pageMargin->getHeader() / MARGIN_MUL, 1);
        $excel['footerMargin'] = round($pageMargin->getFooter() / MARGIN_MUL, 1);

        if ($this->_benchParse)
            $timerParse->setMarker("Parse: Read Margin");

        // 拡大縮小（Excelのページ設定メニュー）
        $excel['pageScale'] = $pageSetup->getScale();

        if ($this->_benchParse)
            $timerParse->setMarker("Parse: Scale");

        // セル範囲
        $yMax = $sheet->getHighestRow();      // 行：　1はじまり
        $xMax = PHPExcel_Cell::columnIndexFromString($sheet->getHighestColumn());   // 列：　0はじまり
        $excel['yCount'] = $yMax;
        $excel['xCount'] = $xMax;

        if ($this->_benchParse)
            $timerParse->setMarker("Parse: Read Cell Range");

        // 行高の読み取り
        $heightArr = array();
        for ($y = 1; $y <= $yMax; $y++) {
            $h = $sheet->getRowDimension($y)->getRowHeight();
            if ($h == -1)
                $h = DEFAULT_ROW_HEIGHT;
            $heightArr[$y] = $h * ROW_HEIGHT_MUL;   // エクセル(pt)->PDF(mm)
        }
        $excel['heightArr'] = $heightArr;

        // 列幅の読み取り
        $widthArr = array();
        for ($x = 0; $x <= $xMax; $x++) {   // $xは0はじまり
            $getWidth = $sheet->getColumnDimensionByColumn($x)->getWidth();
            if ($getWidth == -1) {
                // シート作成後、列幅を変更していない場合は-1が取得される。
                $chars = DEFAULT_COLUMN_WIDTH;
            } else {
                // getWidth() ではエクセル上の列幅（chars）に0.625を足した値が取得される。
                $chars = $getWidth - 0.625;
            }

            // 列幅(chars)を実寸(mm)に変換する。
            // エクセルのデフォルトフォントがMS Pゴシック11であることが前提。
            // くわしくはこのクラス冒頭の COL_WIDTH_MUL の定義のところのコメントを参照。
            $px = $chars * 8 + 5;                    // エクセル(chars) -> px
            $widthArr[$x] = $px * COL_WIDTH_MUL;     // px -> PDF(mm)
        }
        $excel['widthArr'] = $widthArr;
        if ($this->_benchParse)
            $timerParse->setMarker("Parse: Read Height & Width");

        // 非表示行
        $hideArr = array();
        for ($y = 1; $y <= $yMax; $y++) {
            if (!$sheet->getRowDimension($y)->getVisible())
                $hideArr[] = $y;
        }
        $excel['hideRowArr'] = $hideArr;

        // 非表示列
        $hideArr = array();
        for ($x = 0; $x <= $xMax; $x++) {   // $xは0はじまり
            if (!$sheet->getColumnDimensionByColumn($x)->getVisible())
                $hideArr[] = $x;
        }
        $excel['hideColArr'] = $hideArr;

        if ($this->_benchParse)
            $timerParse->setMarker("Parse: Read Hide");

        // 改ページの読み取り
        // いまのところPDF出力（tcpdf）側を実装していないのでコメントアウト
//        $excel['breaks'] = array();
//        $breaks = $sheet->getBreaks();
//        foreach ($breaks as $break=>$val) {
//            $arr = self::_A1ToR1C0($mp[0]);
//            $excel['breaks'][] = $arr[1];   // 改ページのy位置を記録
//        }

        // 結合セルの読み取り
        $mergeCells = $sheet->getMergeCells();
        $mergeArr = array();
        foreach ($mergeCells as $merge) {
            $mp = explode(":", $merge);
            list($x0, $y0) = self::_A1ToR1C0($mp[0]);
            list($x1, $y1) = self::_A1ToR1C0($mp[1]);

            // 左上セルのパラメータ
            $mergeArr[$x0][$y0]['x1'] = $x1;
            $mergeArr[$x0][$y0]['y1'] = $y1;

            $height = 0;
            for ($y = $y0; $y <= $y1; $y++) {
                $height += $heightArr[$y];
            }
            $mergeArr[$x0][$y0]['height'] = $height;
            $width = 0;
            for ($x = $x0; $x <= $x1; $x++) {
                $width += $widthArr[$x];
            }
            $mergeArr[$x0][$y0]['width'] = $width;

            // 結合セル内の各セル
            for ($y = $y0; $y <= $y1; $y++) {
                for ($x = $x0; $x <= $x1; $x++) {
                    $mergeArr[$x][$y]['yInMerge'] = $y - $y0 + 1;   // 結合セル内の何行目か
                }
            }
        }

        if ($this->_benchParse)
            $timerParse->setMarker("Parse: Read Marge Postition");

        // セル（値・枠線・スタイル）の読み取り
        $valueArr = array();
        $borderArr = array();
        $styleArr = array();
        $excel['recordCount'] = 1;
        for ($y = 1; $y <= $yMax; $y++) {
            $rowNextTagGenerated = false;
            for ($x = 0; $x <= $xMax; $x++) {   // $xは0はじまり
                if ($this->_benchParse && $y == 7 && $x == 1) {
                    $timerParseDetail = new Benchmark_Timer(true);
                }

                // Repeatタグの処理
                //  明細行の２行め以降。
                //  上のセルセクションの内容をコピーし、NextタグとDetailセルタグを挿入する。
                $val = $sheet->getCellByColumnAndRow($x, $y)->getValue();

                if ((string) $val === "[[Repeat]]") {    // なぜかobject型で取得されることがあるのでcastしておく
                    $excel['recordCount']++;

                    // 処理範囲を決定
                    if (isset($mergeArr[$x][$y])) {
                        $xSize = $mergeArr[$x][$y]['x1'] - $x + 1;
                        $ySize = $mergeArr[$x][$y]['y1'] - $y + 1;
                    } else {
                        $xSize = 1;
                        $ySize = 1;
                    }
                    $copyXtop = $x;
                    $copyYtop = $y - $ySize;
                    if ($copyXtop < 0)
                        $copyXtop = 0;
                    if ($copyYtop < 1)
                        $copyYtop = 1;
                    $copyXend = $copyXtop + $xSize - 1;
                    $copyYend = $copyYtop + $ySize - 1;

                    $borders = $sheet->getStyleByColumnAndRow($x, $y)->getBorders();
                    self::_readBorder($borders->getTop(), $topBorder, $topColor);
                    self::_readBorder($borders->getLeft(), $leftBorder, $leftColor);
                    $borders = $sheet->getStyleByColumnAndRow($x + $xSize - 1, $y + $ySize - 1)->getBorders();
                    self::_readBorder($borders->getBottom(), $bottomBorder, $bottomColor);
                    self::_readBorder($borders->getRight(), $rightBorder, $rightColor);
                    unset($borders);

                    // Excel出力モード用の処理（PDF出力のときに実行しても問題はない）
                    // テンプレート内でrepeatセルの結合を解除する（Excel出力モードのみ）
                    if (isset($mergeArr[$x][$y])) {
                        $colStr1 = PHPExcel_Cell::stringFromColumnIndex($x);
                        $colStr2 = PHPExcel_Cell::stringFromColumnIndex($mergeArr[$x][$y]['x1']);
                        $sheet->unmergeCells($colStr1 . (string) ($y) . ':' . $colStr2 . (string) ($mergeArr[$x][$y]['y1']));
                    }

                    // 上のセルセクションの内容をコピー
                    for ($yy = $copyYtop; $yy <= $copyYend; $yy++) {
                        for ($xx = $copyXtop; $xx <= $copyXend; $xx++) {
                            // スタイルのコピー
                            if (isset($styleArr[$xx][$yy])) {
                                $styleArr[$xx][$yy + $ySize] = $styleArr[$xx][$yy];
                            }
                            // 枠線のコピー
                            if (isset($borderArr[$xx][$yy])) {
                                $borderArr[$xx][$yy + $ySize] = $borderArr[$xx][$yy];
                            }
                            // 結合セルのコピー
                            if (isset($mergeArr[$xx][$yy + $ySize])) {    // Repeatセルのセル結合を解除
                                unset($mergeArr[$xx][$yy + $ySize]);
                            }
                            if (isset($mergeArr[$xx][$yy])) {           // 上のセルセクションの結合をコピー
                                $mergeArr[$xx][$yy + $ySize] = $mergeArr[$xx][$yy];
                                if (isset($mergeArr[$xx][$yy + $ySize]['y1']))
                                    $mergeArr[$xx][$yy + $ySize]['y1']+=$ySize;

                                // Excel出力モード用の処理（PDF出力のときに実行しても問題はない）
                                // テンプレート内でセル結合状態をコピーする（Excel出力モードのみ）
                                if (isset($mergeArr[$xx][$yy]['x1'])) {
                                    $colStr1 = PHPExcel_Cell::stringFromColumnIndex($xx);
                                    $colStr2 = PHPExcel_Cell::stringFromColumnIndex($mergeArr[$xx][$yy]['x1']);
                                    $sheet->mergeCells($colStr1 . (string) ($yy + $ySize) . ':' . $colStr2 . (string) ($mergeArr[$xx][$yy]['y1'] + $ySize));
                                }
                            }
                            // Detailセルの外枠（外枠だけは上のセルセクションではなく、Detailセル自身のものを使用する必要がある）
                            if (!isset($mergeArr[$xx][$yy + $ySize]) || isset($mergeArr[$xx][$yy + $ySize]['x1'])) {
                                // 非結合セル or 結合セルのトップ（左上セル）
                                if ($yy == $copyYtop) {
                                    $borderArr[$xx][$yy + $ySize]['topBorder'] = $topBorder;
                                    $borderArr[$xx][$yy + $ySize]['topColorArr'] = $topColor;
                                }
                                if ($xx == $copyXtop) {
                                    $borderArr[$xx][$yy + $ySize]['leftBorder'] = $leftBorder;
                                    $borderArr[$xx][$yy + $ySize]['leftColorArr'] = $leftColor;
                                }
                                if ($yy == $copyYend || (isset($mergeArr[$xx][$yy + $ySize]) && $mergeArr[$xx][$yy + $ySize]['y1'] == $copyYend + $ySize)) {
                                    $borderArr[$xx][$yy + $ySize]['bottomBorder'] = $bottomBorder;
                                    $borderArr[$xx][$yy + $ySize]['bottomColorArr'] = $bottomColor;
                                }
                                if ($xx == $copyXend) {
                                    $borderArr[$xx][$yy + $ySize]['rightBorder'] = $rightBorder;
                                    $borderArr[$xx][$yy + $ySize]['rightColorArr'] = $rightColor;
                                }
                            }

                            // 上のセルセクションの内容をコピー
                            $writeTag = isset($valueArr[$xx][$yy]) ? $valueArr[$xx][$yy] : ""; // issetはセル結合内のセルのため
                            //$writeTag = $sheet->getCellByColumnAndRow($xx, $yy)->getValue();
                            // 上のセルセクションからコピーしたNextタグは消す
                            $writeTag = str_replace("[[__Next]]", "", $writeTag);
                            // 上のセルセクションからコピーしたRepeatセルタグは消す
                            $writeTag = str_replace("[[__Repeat]]", "", $writeTag);
                            // セルにRepeatタグをつける
                            $writeTag = "[[__Repeat]]" . $writeTag;
                            // 左上セルにNextタグをつける
                            //  1行に複数のRepeatタグが出てきたとき、Nextタグをつけるのは最初の1回のみ。
                            //  Repeatタグを横に並べたときに（非推奨な形だが）、タグごとにレコードが進んでしまうのは不自然。
                            if ($xx == $copyXtop && $yy == $copyYtop && !$rowNextTagGenerated) {
                                $writeTag = "[[__Next]]" . $writeTag;
                                $rowNextTagGenerated = true;
                            }

                            // 値を保存
                            $valueArr[$xx][$yy + $ySize] = $writeTag;
                            // テンプレートも書き換えておく（_tagToValue()の処理のため）
                            $sheet->setCellValueByColumnAndRow($xx, $yy + $ySize, $writeTag);

                            // Excel出力モード用の処理（PDF出力のときに実行しても問題はない）
                            // テンプレート内でStyleをコピーする（Excel出力モードのみ）
                            $colStr = PHPExcel_Cell::stringFromColumnIndex($xx);
                            $style = $sheet->getStyleByColumnAndRow($xx, $yy);
                            $sheet->duplicateStyle($style, $colStr . (string) ($yy + $ySize));
                        } // end of $xx loop
                    } // end of $yy loop (cellSectionCopy)
                    continue;
                } // end of if Repeat tag

                // Repeatセルの場合は処理しない（上のセルセクションからコピー済みなので）
                if (strpos($val, "[[__Repeat]]") !== FALSE)
                    continue;

                // 結合セルの処理
                $isMergeTop = false;
                $isInMerge = false;
                if (isset($mergeArr[$x][$y])) {
                    if (isset($mergeArr[$x][$y]['x1'])) {
                        // 結合セルのトップ（左上セル）
                        $isMergeTop = true;

                        // 右と下の枠線情報を取得しておく
                        $mergeStyle = $sheet->getStyleByColumnAndRow($mergeArr[$x][$y]['x1'], $mergeArr[$x][$y]['y1']);
                        $mergeRBBorders = $mergeStyle->getBorders();
                        self::_readBorder($mergeRBBorders->getRight(), $borderArr[$x][$y]['rightBorder'], $borderArr[$x][$y]['rightColorArr']);
                        self::_readBorder($mergeRBBorders->getBottom(), $borderArr[$x][$y]['bottomBorder'], $borderArr[$x][$y]['bottomColorArr']);
                    } else {
                        // トップ以外
                        $isInMerge = true;
                    }
                }

                if ($this->_benchParse && $y == 7 && $x == 1)
                    $timerParseDetail->setMarker("Parse Border & Style(y:{$y}, x:{$x}): Merge Operation");

                if (!$isInMerge) {
                    // 値の読み取り
                    $valueArr[$x][$y] = $val;

                    // 枠線の読み取り
                    $borders = $sheet->getStyleByColumnAndRow($x, $y)->getBorders();
                    self::_readBorder($borders->getTop(), $borderArr[$x][$y]['topBorder'], $borderArr[$x][$y]['topColorArr']);
                    self::_readBorder($borders->getLeft(), $borderArr[$x][$y]['leftBorder'], $borderArr[$x][$y]['leftColorArr']);
                    if (!$isMergeTop) {
                        self::_readBorder($borders->getRight(), $borderArr[$x][$y]['rightBorder'], $borderArr[$x][$y]['rightColorArr']);
                        self::_readBorder($borders->getBottom(), $borderArr[$x][$y]['bottomBorder'], $borderArr[$x][$y]['bottomColorArr']);
                    }
                    self::_readBorder($borders->getDiagonal(), $borderArr[$x][$y]['diagonalBorder'], $borderArr[$x][$y]['diagonalColorArr']);
                    if ($borderArr[$x][$y]['diagonalBorder'] != 'none')
                        $borderArr[$x][$y]['diagonalDirection'] = $borders->getDiagonalDirection();
                    unset($borders);

                    if ($this->_benchParse && $y == 7 && $x == 1)
                        $timerParseDetail->setMarker("Parse Border & Style(y:{$y}, x:{$x}): Read border");

                    // スタイルの読み取り
                    $style = $sheet->getStyleByColumnAndRow($x, $y);
                    if ($sheet->getCellByColumnAndRow($x, $y)->getValue() !== "") {    // != ではダメ。0!="" はfalse
                        $format = $style->getNumberFormat()->getFormatCode();
                        $styleArr[$x][$y]['format'] = str_replace("\"", "", $format);    // 「xxxx年xx月xx日」などで余分な「"」が入ることへの対処
                        $alignment = $style->getAlignment();
                        $styleArr[$x][$y]['align'] = $alignment->getHorizontal(); // general, left, center, right
                        $styleArr[$x][$y]['valign'] = $alignment->getVertical(); // general, top, center, bottom
                        $styleArr[$x][$y]['isShrinkToFit'] = $alignment->getShrinkToFit();
                        $styleArr[$x][$y]['isWrapText'] = $alignment->getWrapText();
                        unset($alignment);
                        $fontObj = $style->getFont();
                        $styleArr[$x][$y]['fontName'] = $fontObj->getName();
                        $styleArr[$x][$y]['fontSize'] = $fontObj->getSize();
                        $fontStyle = "";
                        if ($fontObj->getBold())
                            $fontStyle .= "B";     // フォントによっては無効
                        if ($fontObj->getItalic())
                            $fontStyle .= "I";   // フォントによっては無効
                        if ($fontObj->getUnderline() != "none")
                            $fontStyle .= "U";
                        $styleArr[$x][$y]['fontStyle'] = $fontStyle;
                        $styleArr[$x][$y]['fontColor'] = self::_colorObjToRGB($fontObj->getColor());
                        unset($fontObj);
                    }
                    // セルのパターン（網掛け）
                    $fill = $style->getFill();
                    $styleArr[$x][$y]['fillColor'] = self::_colorObjToRGB($fill->getStartColor());
                    $styleArr[$x][$y]['fillType'] = $fill->getFillType();
                    unset($style);

                    if ($this->_benchParse && $y == 7 && $x == 1)
                        $timerParseDetail->setMarker("Parse Border & Style(y:{$y}, x:{$x}): Read style");
                    if ($this->_benchParse && $y == 7 && $x == 1) {
                        $timerParseDetail->close();
                    }
                }   // end of セル（値・枠線・スタイル）
            }   // end of x
        }   // end of y

        $excel['valueArr'] = $valueArr;
        $excel['mergeArr'] = $mergeArr;
        $excel['borderArr'] = $borderArr;

        $excel['styleArr'] = $styleArr;

        if ($this->_benchParse)
            $timerParse->setMarker("Parse: Read Value & Border & Style");

        // 図の読み取り
        $excel['drawingCollection'] = $sheet->getDrawingCollection();
        if ($this->_benchParse)
            $timerParse->setMarker("Parse: Read Drawing");

        if ($this->_benchParse) {
            $timerParse->close();
        }

        return $excel;
    }

    // Excelテンプレート（シート）内のタグをDB値に置き換える（1ページ分）
    //  タグの置き換えはPDFへの出力時に処理するほうが簡単に思えるが、それだとセル参照やセル内計算を正しく反映できない。
    //  通常セル処理とRepeatセル処理の2回呼び出される。RepeatセルはNextタグ出現ごとにレコードを進めるので、分けて処理する必要がある。
    //  また、引数 $existDetail は、通常セル処理のときは戻し（テンプレートに明細タグがあるかどうかを調べて返す）、Repeatセル処理の時は
    //  受け取り（明細タグの有無によりレコードの進め方を変える）であることに注意。
    private function _tagToValue($isRepeatCell, $sheet, $xCount, $yCount, $styleArr, $data, $page, $copyCount, &$rowIndex, $pageKeyColumn, $company, $totalArr, &$existDetail)
    {
        global $gen_db;

        // ページキー
        $pageKeyColumnCache = $data[$rowIndex][$pageKeyColumn];

        $countData = count($data);

        // detailカラム（detail_ ではじまるカラム。テンプレートの明細行の有無の判断に使用）をリストアップ
        $detailColumnsArr = array();
        foreach ($data[0] as $name => $val) {
            if (substr($name, 0, 7) == 'detail_') {
                $detailColumnsArr[] = $name;
            }
        }

        $colArr = array();
        $isPageFirst = true;
        $isPageBreaked = false;

        // 置換処理
        for ($y = 1; $y <= $yCount; $y++) {
            for ($x = 0; $x <= $xCount; $x++) {   // $xは0始まり
                $value = $sheet->getCellByColumnAndRow($x, $y)->getValue();

                // タグがあるセルだけを処理
                if (strpos($value, "[[") === FALSE)
                    continue;
                // Repeatセルタグの有無
                $pos = strpos($value, "[[__Repeat]]");
                if (($isRepeatCell && $pos === FALSE) || (!$isRepeatCell && $pos !== FALSE))
                    continue;

                // タグを抽出
                $matches = "";
                if (preg_match_all("(\[\[[^\]]*\]\])", $value, $matches) == 0)
                    continue;

                // 置換処理
                $isBarcode = false;
                $isImage = false;
                foreach ($matches[0] as $match) {

                    // Nextタグ（レコードを進める）の処理
                    //  Nextタグは必ずRepeatタグと同じセルに存在するので、ここが処理されるのは $isRepeatCell == true
                    //  のときのみということになる
                    if ($match === "[[__Next]]") {
                        if (!$isPageBreaked) {
                            // レコードを進める処理。
                            // （左上から順番に処理される。したがって横方向に明細を置くのは難しい）
                            self::_nextRecord($rowIndex, $data, $pageKeyColumn, $existDetail);

                            // ページブレイク
                            if ($countData > $rowIndex && $pageKeyColumnCache != $data[$rowIndex][$pageKeyColumn]) {
                                $isPageBreaked = true;
                                $rowIndex--;
                            }
                        }

                        // Nextタグを消す。
                        // 1セルにNextタグが複数あっても、1レコードしか進まないことに注意
                        $value = str_replace('[[__Next]]', '', $value);
                        continue;
                    }

                    $colName = $match;
                    $colName = str_replace('[[', '', $colName);
                    $colName = str_replace(']]', '', $colName);

                    // バーコード指定
                    if (strtolower(substr($colName, 0, 8)) == "barcode:") {
                        $isBarcode = true;
                        $colName = substr($colName, 8);
                    }
                    // 画像
                    if (strtolower(substr($colName, 0, 6)) == "image:") {
                        $isImage = true;
                        $colName = substr($colName, 6);
                    }
                    // orderby指定
                    //  通常、orderbyタグはorderby処理の箇所で消されているはず。
                    //  しかし誤って1ページ目のA1セル以外の箇所に書かれていた場合に
                    //  エラーになるのを回避するためにここで処理している
                    if (strtolower(substr($colName, 0, 8)) == "orderby:") {
                        continue;
                    }

                    switch ($colName) {
                        // Repeatセルタグ
                        case "__Repeat" :
                            $value = str_replace($match, "", $value);
                            break;
                        // システム規定タグ
                        case "自社名" :
                            $value = str_replace($match, $company->company_name, $value);
                            break;
                        case "自社名（英語表記）" :
                            $value = str_replace($match, $company->company_name_en, $value);
                            break;
                        case "自社郵便番号" :
                            $value = str_replace($match, $company->zip, $value);
                            break;
                        case "自社住所1" :
                            $value = str_replace($match, $company->address1, $value);
                            break;
                        case "自社住所1（英語表記）" :
                            $value = str_replace($match, $company->address1_en, $value);
                            break;
                        case "自社住所2" :
                            $value = str_replace($match, $company->address2, $value);
                            break;
                        case "自社住所2（英語表記）" :
                            $value = str_replace($match, $company->address2_en, $value);
                            break;
                        case "自社電話番号" :
                            $value = str_replace($match, $company->tel, $value);
                            break;
                        case "自社ファックス番号" :
                            $value = str_replace($match, $company->fax, $value);
                            break;
                        case "自社取引銀行" :
                            $value = str_replace($match, $company->main_bank, $value);
                            break;
                        case "自社取引銀行口座" :
                            $value = str_replace($match, $company->bank_account, $value);
                            break;
                        case "ページ" :
                            $value = str_replace($match, $page, $value);
                            break;
                        case "複製ページ数" :
                            $value = str_replace($match, $copyCount, $value);
                            break;
                        case "複製ページ" :
                            // 13iまでは複製ページごとに _tagToValue() と _generatePDFPage() を行っていたためこのタグが使えたが、
                            // 15iからは速度向上のため $this->_pdf->copyPage() でコピーするようになり、このタグが使えなくなった。
                            // テンプレートアップロード時にチェックしているが、13iからの移行ユーザーのためにここでも警告を出す。
                            $value = _g("15iではタグ [[複製ページ]] は使用できません。");
                            break;
                        // DBタグ
                        default:
                            // このチェックを行うとnull値でもエラーになってしまう。
                            // カラムチェックはテンプレートアップロード時に行うようにする。
                            //if ($colName != "" && !isset($data[0][$colName])) {
                            //    throw new Exception("カラム名 " . htmlspecialchars($colName,ENT_QUOTES) . "は存在しません。");
                            //}

                            if (!isset($data[$rowIndex])) {
                                // データがない行
                                $value = str_replace($match, "", $value);
                            } else if ($isRepeatCell && $isPageBreaked) {
                                // ページブレイク後は明細を表示しない
                                $value = str_replace($match, "", $value);
                            } else {
                                if (substr($colName, 0, 5) == "text:") {
                                    // text: タグ
                                    $value = substr($colName, 5);
                                } else if (substr($colName, 0, 6) == "total:") {
                                    // total: タグ
                                    $colName = substr($colName, 6);
                                    if (in_array('detail_' . $colName, $detailColumnsArr)) {
                                        $colName = 'detail_' . $colName;
                                        $existDetail = true;
                                    }
                                    $value = str_replace($match, $totalArr[$colName], $value);
                                } else {
                                    // 通常（DBタグ）
                                    if (in_array('detail_' . $colName, $detailColumnsArr)) {
                                        $colName = 'detail_' . $colName;
                                        $existDetail = true;
                                    }
                                    // タグチェック。アップロード時にチェックしているので、ここでひっかかるのは
                                    // コード変更（ReportクラスのSQLを変更）でタグを減らして既存テンプレが使えなくなった
                                    // 場合ぐらい。
                                    // データがnullのばあいもあるので、issetでのチェックは難しい
                                    //if (!isset($data[$rowIndex][$colName])) {
                                    //    echo("<head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\"></head><body>" . sprintf(_g("タグ [[%s]] は使用できません。"),$colName) . "</body>");
                                    //    throw new Exception();
                                    //}
                                    $value = str_replace($match, $data[$rowIndex][$colName], $value);

                                    if ($isBarcode) {
                                        $value = "barcode:" . $value;
                                    } else if ($isImage) {
                                        $value = "image:" . $value;
                                    }
                                }
                            }
                    }   // end of switch
                } // end of tag match

                // ExcelシートにDB値を書き込み
                if ((string) $value === "") {    // なぜかobjectとして文字が取得される場合があるのでcast
                    // PHPExcelは、計算対象のセル値のひとつが空文字だと、計算結果が「#value」になってしまうことがある。
                    // （たとえば注文書の最下部の小計欄など）
                    // この現象を避けるため、以前（PHPExcel 1.7.4を使用していたとき）は、PHPExcel本体にハックを入れた上で
                    // （PHPExcel/Cell.php の 236行目あたりに「case 'null': $this->_value = null; break;」を挿入）、
                    // $sheet->getCellByColumnAndRow($x, $y)->setValueExplicit(null,'null'); のようにしてnull値を
                    // 書きこむようにしていた。
                    // しかしPHPExcel 1.7.6では、この方法だとうまくいかない場合があり、やり方を変えた。
                    // PHPExcel/Caluculation.php の 3506行目（_executeNumericBinaryOperation()内の「} else {」の後）に
                    // 以下の2行を追加。
                    //      if ($operand1==='') $operand1 = 0;
                    //      if ($operand2==='') $operand2 = 0;
                    // これにより、セル値が空欄の場合の不具合を避けられる。
                    $sheet->getCellByColumnAndRow($x, $y)->setValueExplicit("", PHPExcel_Cell_DataType::TYPE_STRING);
                } else if ($styleArr[$x][$y]['format'] == "@") {
                    // セルの書式が文字列である場合、強制的に文字列型として書き込む。
                    // 文字列の頭のゼロが消えてしまうのを回避するため。
                    // この処理を入れた結果、セル内で計算を行う際、計算対象セルの書式設定が「文字列」の場合に正しく計算されなくなったので注意
                    $sheet->getCellByColumnAndRow($x, $y)->setValueExplicit($value, PHPExcel_Cell_DataType::TYPE_STRING);
                } else {
                    // 日付解釈できる文字列は、日付シリアル値として書き込む。
                    // 日付形式の書式が適用されるようにするため。
                    if (Gen_String::isDateString($value) && $styleArr[$x][$y]['format'] != "General") {
                        $value = "=DATEVALUE(\"" . date("Y/m/d", strtotime($value)) . "\")";
                    }
                    $sheet->setCellValueByColumnAndRow($x, $y, $value);
                }
            }   // end of x loop
        }   // end of y loop

        unset($sheetData);

        // ページ（テンプレートのシート）チェンジの際には、レコードを進めておく必要がある。
        if ($isRepeatCell) {
            self::_nextRecord($rowIndex, $data, $pageKeyColumn, $existDetail);
        }

        return $existDetail;
    }

    // 次のレコードへすすむ
    private function _nextRecord(&$rowIndex, $data, $pageKeyColumn, $existDetail)
    {
        if ($existDetail) {
            // テンプレートに明細タグがあるとき
            $rowIndex++;
        } else {
            // テンプレートに明細タグがないとき：　ページブレイクまでレコードを進める。
            //  たとえば次のような2行のレコードがあるとき、明細タグがあるテンプレートでは2行とも表示する
            //  必要があるが、明細タグがない（ヘッダのみ）テンプレートでは、2行目を飛ばす必要がある。
            //      [ヘッダ項目A], [明細項目1]
            //      [ヘッダ項目A], [明細項目2]
            $dataCount = count($data);
            if ($dataCount > $rowIndex) {
                $pageKeyColumnCache = $data[$rowIndex][$pageKeyColumn];
                while (true) {
                    $rowIndex++;
                    if ($dataCount <= $rowIndex || $pageKeyColumnCache != $data[$rowIndex][$pageKeyColumn]) {
                        break;
                    }
                }
            }
        }
    }

    // PDFベースページ（罫線・図・ベタだけのページ）を作成。
    // 　罫線処理は時間がかかるため、ページごとに行うのではなく、このページをコピーする。
    // 　ベタについては価を書き込むときに同時に行なえばよいように思えるが、罫線のほうがベタより上に描画される
    // 　必要があるため、ここで行う。
    // 　ここで作成されるPDFは1ページとは限らない。テンプレートが大きければ、複数ページが生成されることもある。
    private function _generatePDFBasePage($excel, $sheet)
    {
        // PDFページの追加
        $this->_pdf->AddPage($excel['pageOrientation'], $excel['pageFormat']);
        $this->_pdf->Scale($excel['pageScale'], $excel['pageScale']);

        self::_setPDFPageHeader($excel);

        // PDF上の描画スタート位置（現在のカーソル位置）を保存しておく
        $pdfStartX = $this->_pdf->getX();
        $pdfStartY = $this->_pdf->getY();
        
        // ページの高さ
        $pageHeight = $this->_pdf->getPageHeight() - $excel['bottomMargin'];

        //  DBレコードではなく、Excelシートを基準にループする。
        //  Excelシートを左上から1セルずつループ。
        //  Iteratorを使う手もあるが、内容がない列や行がスキップされてしまう。
        //  setIterateOnlyExistingCells() を falseにすればいいが、そうすると255列65535行をすべて見に行く（たぶん）
        for ($y = 1; $y <= $excel['yCount']; $y++) {
            for ($x = 0; $x <= $excel['xCount']; $x++) {
                // 非表示行
                if (in_array($y, $excel['hideRowArr']))
                    break;

                // 非表示列
                if (in_array($x, $excel['hideColArr']))
                    continue;

                // PDF上の描画位置（現在のカーソル位置）を保存しておく
                $this->_pdfCellTopX = $this->_pdf->getX();
                $this->_pdfCellTopY = $this->_pdf->getY();

                // style
                if (isset($excel['styleArr'][$x][$y])) {
                    $styleArr = $excel['styleArr'][$x][$y];

                    // fill type/color
                    // fillType にはエクセルのセルの網掛けの「パターン」の名称が入っているが、
                    // いまのところベタ以外のパターンには対応していない。fillTypeは網掛けの有無の判断にのみ使用している
                    $isFill = ($styleArr['fillType'] != 'none');
                    if ($isFill) {
                        $RGB = $styleArr['fillColor'];
                        $this->_pdf->SetFillColor($RGB[0], $RGB[1], $RGB[2]);
                    }
                }   // style

                $width = $excel['widthArr'][$x];
                $height = $excel['heightArr'][$y];

                // 結合セル
                $writeMode = 0;         // 0:通常、1:スキップ、2:空白
                $isInMerge = false;
                if (is_array($excel['mergeArr']) && isset($excel['mergeArr'][$x][$y])) {
                    // 結合セル内
                    $isInMerge = true;
                    if (isset($excel['mergeArr'][$x][$y]['width'])) {
                        // 結合セルのトップ（左上セル）
                        // 結合セルの幅と高さ
                        $width = $excel['mergeArr'][$x][$y]['width'];
                        $height = $excel['mergeArr'][$x][$y]['height'];
                    } else {
                        // 結合セル内（トップ以外）
                        if ($excel['mergeArr'][$x][$y]['yInMerge'] == 1) {
                            $writeMode = 1;     // 結合セルの一番上の行はスキップ
                        } else {
                            $writeMode = 2;     // 結合セルの2行目以降は空白セル
                        }
                    }
                } else {
                    $isInMerge = false;
                }

                // セル描画
                switch ($writeMode) {
                    case 0: // 通常
                        // セルのベタ塗りを行う
                        if ($isFill) {
                            $this->_pdf->Cell($width, $height, "", 0, 0, "", 1, "", 0, true, false, "");
                        } else {                     
                            $this->_pdf->setX($this->_pdf->GetX() + $width);
                        }

                        // 枠線処理。
                        //  セルの背景より優先する（上に描く）必要があるため、ここでは描かず、情報を配列に蓄積しておいてあとでまとめて描く。
                        //  ちなみにCellメソッドの第３引数で枠線を描くこともできるが、上下左右で線の種類を変える、ということができないようだ。
                        $x1 = $this->_pdfCellTopX;
                        $y1 = $this->_pdfCellTopY;
                        $x2 = $x1 + $width;
                        $y2 = $y1 + $height;
                        $borderArr = $excel['borderArr'][$x][$y];

                        $lineArr[] = self::_getLine($excel['borderArr'], $borderArr, "top", $x, $y, $x1, $y1, $x2, $y1);
                        $lineArr[] = self::_getLine($excel['borderArr'], $borderArr, "left", $x, $y, $x1, $y1, $x1, $y2);
                        $lineArr[] = self::_getLine($excel['borderArr'], $borderArr, "right", $x, $y, $x2, $y1, $x2, $y2);
                        $lineArr[] = self::_getLine($excel['borderArr'], $borderArr, "bottom", $x, $y, $x1, $y2, $x2, $y2);
                        if (isset($borderArr['diagonalDirection'])) {
                            if ($borderArr['diagonalDirection'] == 1 || $borderArr['diagonalDirection'] == 3) {
                                $lineArr[] = self::_getLine($excel['borderArr'], $borderArr, "diagonal", $x, $y, $x2, $y1, $x1, $y2);
                            }
                            if ($borderArr['diagonalDirection'] == 2 || $borderArr['diagonalDirection'] == 3) {
                                $lineArr[] = self::_getLine($excel['borderArr'], $borderArr, "diagonal", $x, $y, $x1, $y1, $x2, $y2);
                            }
                        }
                        break;
                    case 1: // スキップ（改行のみ）
                        break;
                    case 2: // 空白セル
                        $this->_pdf->setX($this->_pdf->getX() + $excel['widthArr'][$x]);
                        break;
                }

                // 図（画像）の表示
                foreach ($excel['drawingCollection'] as $drawing) {
                    $arr = self::_A1ToR1C0($drawing->getCoordinates());  // 親セルの位置

                    if ($arr[0] == $x && $arr[1] == $y) {
                        $gd = $drawing->getImageResource();
                        $filename = tempnam(GEN_TEMP_DIR, "");

                        // 図を画像ファイル化してテンポラリディレクトリに保存
                        $mimetype = "";
                        switch ($drawing->getMimeType()) {
                            case "image/jpeg":
                                ImageJPEG($gd, $filename);
                                $mimetype = "JPEG";
                                break;
                            // いまのところ、GIFがPNGと判断されてしまう
                            case "image/gif":
                                ImageGIF($gd, $filename);
                                $mimetype = "GIF";
                                break;
                            case "image/png":
                                ImagePNG($gd, $filename);
                                $mimetype = "PNG";
                                break;
                            default:
                                echo (_g("JPEG/GIF/PNG以外の形式の画像を含んでいます。"));
                                die;
                        }

                        // 画像ファイルをPDFに表示
                        // 親セル（画像の左上点を含むセル）の位置
                        $pCellX = $this->_pdfCellTopX;
                        $pCellY = $this->_pdfCellTopY;
                        // 結合セル内では親セルの左上位置が不明（$this->_pdfCellTopXは結合セルの右下を指している）なので計算する
                        if ($isInMerge) {
                            $pCellX = $pdfStartX;
                            for ($xx = 0; $xx < $x; $xx++) {
                                if (!in_array($xx, $excel['hideColArr']))
                                    $pCellX += $excel['widthArr'][$xx];
                            }
                            $pCellY = $pdfStartY;
                            for ($yy = 1; $yy < $y; $yy++) {
                                if (!in_array($yy, $excel['hideRowArr']))
                                    $pCellY += $excel['heightArr'][$yy];
                            }
                        }
                        $imageX = $pCellX + $drawing->getOffsetX() * ROW_HEIGHT_MUL;    // XもROW_HEIGHTでいい。getOffsetX()はインチ単位なので
                        $imageY = $pCellY + $drawing->getOffsetY() * ROW_HEIGHT_MUL;
                        $imageW = $drawing->getWidth() * 0.28;    // 係数は実測により求めた
                        $imageH = $drawing->getHeight() * 0.24;   // 10iでは 0.28 だったが 12iで0.24 に変更。ag.cgi?page=ProjectDocView&pid=1195&did=122202
                        $this->_pdf->Image($filename, $imageX, $imageY, $imageW, $imageH, $mimetype, "", "", "", "", "", false);

                        unlink($filename);
                    }
                }
                reset($excel['drawingCollection']);

                // 改行処理
                if ($x == $excel['xCount']) {
                    // 結合セル内で改行したときのため、縦位置を調整
                    $nextY = $this->_pdf->getY() + $excel['heightArr'][$y];

                    if ($y < $excel['yCount'] && $nextY + $excel['heightArr'][$y + 1] > $pageHeight) {
                        // 改ページ処理
                        // 枠線を描く
                        //   枠線を最後にまとめて描画している理由は、$lineArrの作成箇所のコメントを参照
                        foreach ($lineArr as $line) {
                            $this->_pdf->Line($line[0], $line[1], $line[2], $line[3], array('width' => $line[4], 'cap' => 'butt', 'join' => 'miter', 'dash' => $line[5], 'color' => $line[6]));
                        }
                        unset($lineArr);

                        // ページの追加
                        $this->_pdf->AddPage();
                        $this->_pdf->Scale($excel['pageScale'], $excel['pageScale']);

                        $pdfStartX = $this->_pdf->getX();
                        $pdfStartY = $this->_pdf->getY();
                    } else {
                        $this->_pdf->setX(0);
                        $this->_pdf->setY($nextY);
                    }
                }
            }   // x
        }   // y
        
        // 枠線を描く
        //   枠線を最後にまとめて描画している理由は、$lineArrの作成箇所のコメントを参照
        $lineCount = 0;
        foreach ($lineArr as $line) {
            if ($line) {
                $this->_pdf->Line($line[0], $line[1], $line[2], $line[3], array('width' => $line[4], 'cap' => 'butt', 'join' => 'miter', 'dash' => $line[5], 'color' => $line[6]));
                $lineCount++;
            }
        }
    }
    
    // PDFベースページにテンプレートの各セルの価を書き込む
    private function _writeValueToPDF($excel, $sheet)
    {
        if ($this->_benchDetail) {
            $benchDetailArray = array();
            require_once dirname(dirname(__FILE__)) . '/pear/Benchmark/Timer.php';
            $timerDetail = new Benchmark_Timer(true);
        }
        
        self::_setPDFPageHeader($excel);

        // PDF上の描画スタート位置（現在のカーソル位置）を保存しておく
        $pdfStartX = $this->_pdf->getX();
        $pdfStartY = $this->_pdf->getY();
        
        // ページの高さ
        $pageHeight = $this->_pdf->getPageHeight() - $excel['bottomMargin'];

        // フォント情報
        $currentFontName = "";
        $currentFontSize = 1;
        $currentFontStyle = "";

        $alignMark = "";
        $valignMark = "";
        $isShrinkToFit = false;
        $isWrapText = false;
        
        // 15iで追加
        // テンプレートの同じシートから複数のページが生成されたとき、2ページ目以降のセルの数式の計算結果が
        // 1ページ目の同じ位置のセルのものとなってしまう問題を回避。
        // セル計算キャッシュを無効化する。
        PHPExcel_Calculation::getInstance($sheet->getParent())->setCalculationCacheEnabled(false);

        if ($this->_benchDetail) {
            $timerDetail->stop();
            $benchDetailArray['init'] = $timerDetail->timeElapsed();
            $timerDetail->start();
            $countPdfCell = 0;
            $countGetValue = 0;                 
        }

        //  DBレコードではなく、Excelシートを基準にループする。
        //  Excelシートを左上から1セルずつループ。
        //  Iteratorを使う手もあるが、内容がない列や行がスキップされてしまう。
        //  setIterateOnlyExistingCells() を falseにすればいいが、そうすると255列65535行をすべて見に行く（たぶん）
        for ($y = 1; $y <= $excel['yCount']; $y++) {
            for ($x = 0; $x <= $excel['xCount']; $x++) {
                // 非表示行
                if (in_array($y, $excel['hideRowArr']))
                    break;

                // 非表示列
                if (in_array($x, $excel['hideColArr']))
                    continue;

                if ($this->_benchDetail) {
                    $timerDetail->stop();
                    @$benchDetailArray['hide Row Col'] += $timerDetail->timeElapsed();
                    $timerDetail->start();
                }

                // PDF上の描画位置（現在のカーソル位置）を保存しておく
                $this->_pdfCellTopX = $this->_pdf->getX();
                $this->_pdfCellTopY = $this->_pdf->getY();

                // value
                //  解釈できない関数があったとき例外がスローされるため、キャッチしておく。
                //  ちなみに15i開発の際、高速化のため、式やタグ以外のセル値は $excel['valueArr']
                //  から読み出すということも試みたが、ほとんど処理時間が変わらなかったのでやめた
                try {
                    $value = $sheet->getCellByColumnAndRow($x, $y)->getCalculatedValue();
                } catch (Exception $ex) {
                    // 解釈できない関数があったときは、セルに書かれている内容をそのまま表示する
                    $value = $sheet->getCellByColumnAndRow($x, $y)->getValue();
                }

                if (is_float($value)) { // 指数（1.0E5 とか）対策
                    $value = sprintf("%.9f", $value);
                    $value = preg_replace("/0+$/", '', $value);   // 小数点以下の余分な0を削除
                    $value = preg_replace("/\.$/", '', $value);   // 余分な小数点を削除
                }

                // なぜかobject型で取得されることがある。=== や !== で問題が起きるのを避けるためcast
                $value = (string) $value;

                if ($this->_benchDetail) {
                    $timerDetail->stop();
                    @$benchDetailArray['get cell value'] += $timerDetail->timeElapsed();
                    $timerDetail->start();
                }

                // style
                if (isset($excel['styleArr'][$x][$y])) {
                    $styleArr = $excel['styleArr'][$x][$y];

                    if ($this->_benchDetail) {
                        $timerDetail->stop();
                        @$benchDetailArray['get style'] += $timerDetail->timeElapsed();
                        $timerDetail->start();
                    }

                    // 値なしのセルは処理しない
                    if ($value != "") {

                        // value format
                        $value = PHPExcel_Style_NumberFormat::toFormattedString(
                                        $value, // セルの値。計算式ではなく計算後の値を取得
                                        $styleArr['format']
                        );

                        if ($this->_benchDetail) {
                            $timerDetail->stop();
                            @$benchDetailArray['get format'] += $timerDetail->timeElapsed();
                            $timerDetail->start();
                        }

                        // align
                        switch ($styleArr['align']) {
                            case 'left' :
                                $alignMark = "L";
                                break;
                            case 'center' :
                                $alignMark = "C";
                                break;
                            case 'right' :
                                $alignMark = "R";
                                break;
                            default:    // general
                                if (Gen_String::isNumeric(str_replace(',', '', $value))) {
                                    $alignMark = "R";
                                } else {
                                    $alignMark = "L";
                                }
                        }

                        // valign
                        switch ($styleArr['valign']) {
                            case 'top' :
                                $valignMark = "T";
                                break;
                            case 'bottom' :
                                $valignMark = "B";
                                break;
                            default:    // center, general
                                $valignMark = "C";
                        }

                        if ($this->_benchDetail) {
                            $timerDetail->stop();
                            @$benchDetailArray['align'] += $timerDetail->timeElapsed();
                            $timerDetail->start();
                        }

                        // shinktofit
                        $isShrinkToFit = $styleArr['isShrinkToFit'];
                        $isWrapText = $styleArr['isWrapText'];

                        if ($this->_benchDetail) {
                            $timerDetail->stop();
                            @$benchDetailArray['shrinkToFit'] += $timerDetail->timeElapsed();
                            $timerDetail->start();
                        }

                        // font family
                        // フォントの処理は時間がかかるので、変更があったときのみSetFontするようにする。
                        $fontName = $styleArr['fontName'];
                        if ($fontName == "")
                            $fontName = $currentFontName;
                        if ($this->_benchDetail) {
                            $timerDetail->stop();
                            @$benchDetailArray['font read'] += $timerDetail->timeElapsed();
                            $timerDetail->start();
                        }

                        if ($currentFontName != $fontName) {
                            switch ($fontName) {
                                // フォント設定
                                // 組み込みの日本語フォントは以下
                                //      Arial Uni CID0      arialunicid0       ->「●」などの記号が半角幅になってしまう不具合あり
                                //      小塚ゴシックPro M    kozgopromedium     ->半角カナ文字のセンタリングで不具合あり
                                //      小塚明朝Pro M        kozminproregular
                                // 以下は手動でインストールしたフォント（非埋め込み。tcpdf/fonts/xxx.php）。インストール手順はTips参照
                                // プロポーショナルフォントはハイフンが次の文字と重なる問題があるので、非プロポーショナルに変換する。
                                case "ＭＳ Ｐゴシック" :
                                    // MSゴシックとして表示。本来はmspgothic
                                    $fontFamily = "msgothic";
                                    break;
                                // Adobe Reader XI 以降、Genのフォントファイルでは MS UI Gothicが正常に表示されなくなったため
                                // サポート外とする。
                                //case "MS UI Gothic" :
                                //    $fontFamily = "msuigothic";
                                //    break;
                                case "ＭＳ 明朝" :
                                    $fontFamily = "msmincho";
                                    break;
                                case "ＭＳ Ｐ明朝" :
                                    // MS明朝として表示。本来はmspmincho
                                    $fontFamily = "msmincho";
                                    break;
                                case "Batang" :
                                    // ハングル用
                                    $fontFamily = "batang";
                                    break;
                                case "MingLiU" :
                                    // 中国語繁体字用 (winのみ)
                                    $fontFamily = "mingliu";
                                    break;
                                case "SimHei" :
                                    // 中国語簡体字用 (winのみ)
                                    $fontFamily = "simhei";
                                    break;
                                case "Times New Roman" :
                                    // Times New Roman
                                    $fontFamily = "times";
                                    break;
                                default:
                                    // ＭＳ ゴシック
                                    $fontFamily = "msgothic";
                            }
                            $this->_pdf->SetFont($fontFamily);
                            $currentFontName = $fontName;

                            if ($this->_benchDetail) {
                                $timerDetail->stop();
                                @$benchDetailArray['font family(set:' . $fontName . ')'] += $timerDetail->timeElapsed();
                                $timerDetail->start();
                            }
                        } else {
                            if ($this->_benchDetail) {
                                $timerDetail->stop();
                                @$benchDetailArray['font family(not set)'] += $timerDetail->timeElapsed();
                                $timerDetail->start();
                            }
                        }

                        // font size
                        $fontSize = ($value === "" ? $currentFontSize : $styleArr['fontSize']);     // 数値
                        if ($currentFontSize != $fontSize) {
                            $this->_pdf->SetFontSize($fontSize);
                            $currentFontSize = $fontSize;

                            if ($this->_benchDetail) {
                                $timerDetail->stop();
                                @$benchDetailArray['font size(set:' . $fontSize . ')'] += $timerDetail->timeElapsed();
                                $timerDetail->start();
                            }
                        } else {
                            if ($this->_benchDetail) {
                                $timerDetail->stop();
                                @$benchDetailArray['font size(not set)'] += $timerDetail->timeElapsed();
                                $timerDetail->start();
                            }
                        }

                        // font style
                        $fontStyle = $styleArr['fontStyle'];
                        if ($currentFontStyle != $fontStyle) {
                            $this->_pdf->SetFont("", $fontStyle);
                            $currentFontStyle = $fontStyle;

                            if ($this->_benchDetail) {
                                $timerDetail->stop();
                                @$benchDetailArray['font style(set:' . $fontStyle . ')'] += $timerDetail->timeElapsed();
                                $timerDetail->start();
                            }
                        } else {
                            if ($this->_benchDetail) {
                                $timerDetail->stop();
                                @$benchDetailArray['font style(not set)'] += $timerDetail->timeElapsed();
                                $timerDetail->start();
                            }
                        }
                        // font color
                        $RGB = $styleArr['fontColor'];
                        $this->_pdf->SetTextColor($RGB[0], $RGB[1], $RGB[2]);

                        if ($this->_benchDetail) {
                            $timerDetail->stop();
                            @$benchDetailArray['font color'] += $timerDetail->timeElapsed();
                            $timerDetail->start();
                        }
                    }
                }   // style

                if ($this->_benchDetail) {
                    $timerDetail->stop();
                    @$benchDetailArray['back color'] += $timerDetail->timeElapsed();
                    $timerDetail->start();
                }

                $width = $excel['widthArr'][$x];
                $height = $excel['heightArr'][$y];

                // 結合セル
                $writeMode = 0;         // 0:通常、1:スキップ、2:空白
                $isInMerge = false;
                if (is_array($excel['mergeArr']) && isset($excel['mergeArr'][$x][$y])) {
                    // 結合セル内
                    $isInMerge = true;
                    if (isset($excel['mergeArr'][$x][$y]['width'])) {
                        // 結合セルのトップ（左上セル）
                        // 結合セルの幅と高さ
                        $width = $excel['mergeArr'][$x][$y]['width'];
                        $height = $excel['mergeArr'][$x][$y]['height'];
                    } else {
                        // 結合セル内（トップ以外）
                        if ($excel['mergeArr'][$x][$y]['yInMerge'] == 1) {
                            $writeMode = 1;     // 結合セルの一番上の行はスキップ
                        } else {
                            $writeMode = 2;     // 結合セルの2行目以降は空白セル
                        }
                    }
                } else {
                    $isInMerge = false;
                }

                if ($this->_benchDetail) {
                    $timerDetail->stop();
                    @$benchDetailArray['margeCell'] += $timerDetail->timeElapsed();
                    $timerDetail->start();
                }

                // セル描画
                switch ($writeMode) {
                    case 0: // 通常
                        // バーコード
                        if (substr($value, 0, 8) == "barcode:") {
                            $value = substr($value, 8);
                            // CODE39で使えない文字が含まれていたときは表示しない
                            if ($value !== "" && preg_match("/[^0-9a-zA-Z\-\. \$\/\+\%]/i", $value) == 0) {
                                $this->_pdf->write1DBarcode($value, 'C39', $this->_pdf->getX() + 4, $this->_pdf->getY() + 1, $width - 8, $height - 2, "", array('position' => 'S'));
                            }
                            $value = "";
                        }

                        // 画像
                        if (substr($value, 0, 6) == "image:") {
                            $value = substr($value, 6);
                            // $valueには画像のフルパスが指定されている。
                            // Gen_Storage に格納している画像の場合は、「カテゴリ（Gen_Storage 冒頭で指定。ItemImageなど）::ファイル名」という形になっている。
                            $imgFileArr = explode("::", $value);
                            $tempFlag = false;
                            if (count($imgFileArr) == 1) {
                                $file = $imgFileArr[0];     // フルパス指定
                            } else {
                                // S3からの読み出しは時間がかかるのでキャッシュを活用
                                if (isset($this->_imageCache[$imgFileArr[1]]) && file_exists($this->_imageCache[$imgFileArr[1]])) {
                                    $file = $this->_imageCache[$imgFileArr[1]];
                                } else {
                                    $extp = explode(".", $imgFileArr[1]); // 変数化しないとPHP5.3以上でエラー
                                    $ext = end($extp);
                                    $storage = new Gen_Storage($imgFileArr[0]);
                                    $file = $storage->get($imgFileArr[1]);
                                    $extp = explode(".", $file);
                                    if (end($extp) != $ext) {
                                        $newFile = $file . ".{$ext}";
                                        rename($file, $newFile);
                                        $file = $newFile;        
                                    }
                                    // S3の場合のみキャッシュする。
                                    //  $this->_imageCache のファイルは最後に削除されるため、files_dirの場合はキャッシュしてはいけない。
                                    if ($storage->isS3()) {
                                        $this->_imageCache[$imgFileArr[1]] = $file;
                                    }
                                }
                            }
                            if (file_exists($file)) {
                                // 画像の読み出し
                                $imagePathName = $file;

                                // 表示サイズを決める（セルのサイズにあわせて伸縮。ただし縦横比を維持する）
                                $showWidth = $width * 0.98;
                                $showHeight = $height * 0.98;
                                $size = Gen_Image::getSize($imagePathName);
                                if ($size) {
                                    $isHeightCrit = ($showWidth / $showHeight > $size[0] / $size[1]);    // セルの縦横比と画像の縦横比を比べて、縦横のどちらを固定するか判断
                                    if ($isHeightCrit) {
                                        $showWidth = bcdiv(bcmul($showHeight, $size[0]), $size[1]);
                                    } else {
                                        $showHeight = bcdiv(bcmul($showWidth, $size[1]), $size[0]);
                                    }

                                    // 表示位置を決める（セルの中央に）
                                    $imageXpos = $this->_pdf->getX() + ($width - $showWidth) / 2;
                                    $imageYpos = $this->_pdf->getY() + ($height - $showHeight) / 2;

                                    // 画像表示
                                    $this->_pdf->Image($imagePathName, $imageXpos, $imageYpos, $showWidth, $showHeight, "", "", "", "", "", "", false);
                                }
                            }
                            $value = "";
                        }

                        if ($this->_benchDetail) {
                            $timerDetail->stop();
                            @$benchDetailArray['barcode picture'] += $timerDetail->timeElapsed();
                            $timerDetail->start();
                        }

                        // セルの内容を書く
                        if ($value == "") {
                            $this->_pdf->setX($this->_pdf->GetX() + $width);
                        } else {
                            if ($isWrapText) {
                                // MultiCell はセル端での折り返しとセル内改行が有効になるかわり、「縮小して全体を表示」がうまく動かない。（折り返しが優先される）
                                // そのため「折り返して」のセルのみ MultiCellを使う。
                                // したがってセル内改行は「折り返して」のセルのみ有効。
                                // ※どのセルでもセル内改行を有効にするため、MultiCell を標準にして「縮小して」のセルのみCellを使う手もあるが、
                                // 　そうすると「縮小して」でも「折り返して」でもないセルで、折り返しが有効になってしまう。
                                // ※最後の引数をtrueにすることにより、行数が多くて縦方向がセル高に収まらない場合、フォントが自動縮小される。
                                // ※ちなみにshrinkToFit（第11引数）をtrueにすると、「折り返して」のときにセル内の最後の行の文字サイズが小さくなってしまう。(tcpdfのバグ？)
                                $this->_pdf->MultiCell($width, $height, $value, 0, $alignMark, 0, 0, '', '', true, false, false, false, $height, $valignMark, true);
                            } else {
                                $this->_pdf->Cell($width, $height, $value, 0, 0, $alignMark, 0, "", ($isShrinkToFit ? 1 : 0), true, false, $valignMark);
                                if ($this->_benchDetail) {
                                    $countPdfCell++;
                                }
                            }
                        }

                        if ($this->_benchDetail) {
                            $timerDetail->stop();
                            @$benchDetailArray['cell write'] += $timerDetail->timeElapsed();
                            $timerDetail->start();
                        }
                        break;
                    case 1: // スキップ（改行のみ）
                        break;
                    case 2: // 空白セル
                        $this->_pdf->setX($this->_pdf->getX() + $excel['widthArr'][$x]);
                        break;
                }

                // 次の行へ
                if ($x == $excel['xCount']) {
                    // 結合セル内で改行したときのため、縦位置を調整
                    $nextY = $this->_pdf->getY() + $excel['heightArr'][$y];

                    if ($y < $excel['yCount'] && $nextY + $excel['heightArr'][$y + 1] > $pageHeight) {
                        // テンプレートが大きく、PDF１ページ分をはみ出したとき

                        if ($this->_pdf->getPage() < $this->_pdf->getNumPages()) {
                            $this->_pdf->setPage($this->_pdf->getPage() + 1);
                            self::_setPDFPageHeader($excel);
                            
                            $currentFontName = "";
                            $currentFontSize = 1;
                            $currentFontStyle = "";

                            $alignMark = "";
                            $valignMark = "";
                            $isShrinkToFit = false;
                            $isWrapText = false;
                        }
                        $pdfStartX = $this->_pdf->getX();
                        $pdfStartY = $this->_pdf->getY();
                    } else {
                        $this->_pdf->setX(0);
                        $this->_pdf->setY($nextY);
                    }
                }

                if ($this->_benchDetail) {
                    $timerDetail->stop();
                    @$benchDetailArray['page break'] += $timerDetail->timeElapsed();
                    $timerDetail->start();
                }
            }   // x
        }   // y

        if ($this->_benchDetail) {
            $timerDetail->stop();
            @$benchDetailArray['loop end'] += $timerDetail->timeElapsed();
            $timerDetail->start();
        }
        
        if ($this->_benchDetail) {
            $timerDetail->stop();
            $benchDetailArray['end'] = $timerDetail->timeElapsed();
            asort($benchDetailArray);
            $html = '<table>';
            $total = 0;
            foreach($benchDetailArray as $key => $val) {
                $html .= '<tr><td>' . $key . '</td><td>' . $val . '</td></tr>';
                $total += $val;
            }
            $html .= '<tr><td><b>total</b></td><td><b>' . $total . '</b></td></tr>';
            $html .= '</table>';
            $html .= '_pdf->Cell() の回数 ：' . $countPdfCell . '<br><br>';
            $html .= 'getCalculatedValue() の回数 ：' . $countGetValue . '<br><br>';
            echo($html);
            $timerDetail = null;
        }
    }
    
    private function _setPDFPageHeader($excel)
    {
        // 余白（マージン）設定
        $this->_pdf->SetTopMargin($excel['topMargin']);
        $this->_pdf->SetLeftMargin($excel['leftMargin']);
        $this->_pdf->SetRightMargin($excel['rightMargin']);
        $this->_pdf->SetHeaderMargin($excel['headerMargin']);
        $this->_pdf->SetFooterMargin($excel['footerMargin']);
        $this->_pdf->SetAutoPageBreak(false, $excel['bottomMargin']); // 自動改ページは使わない。改ページ時の処理がいろいろあるので。

        // 描画開始位置
        $this->_pdf->setX($excel['leftMargin']);
        $this->_pdf->setY($excel['topMargin']);
    }

    // セル位置をA1形式からR1C0形式に変換（結果は配列。0がRow(x)、1がCol(y)）
    private function _A1ToR1C0($a1)
    {
        $res = array();
        for ($i = 0; $i < strlen($a1); $i++) {
            if (is_numeric(substr($a1, $i, 1))) {
                $res[0] = PHPExcel_Cell::columnIndexFromString(substr($a1, 0, $i)) - 1;
                break;
            }
        }
        $res[1] = (int) substr($a1, $i);
        return $res;
    }

    // 枠線情報の読み取り（第2・第3引数が戻り値）
    private function _readBorder($borderObj, &$border, &$colorArr)
    {
        $border = $borderObj->getBorderStyle();
        if ($border != "none") {
            $colorArr = self::_colorObjToRGB($borderObj->getColor());
        }
    }

    // 枠線の描画情報を取得
    private function _getLine($exBorderArr, $borderArr, $direction, $cellX, $cellY, $x1, $y1, $x2, $y2)
    {
        $lineName = $borderArr[$direction . 'Border'];
        if ($lineName == "none" || $lineName === null)
            return;
        $lineColorArr = $borderArr[$direction . 'ColorArr'];

        $lineWidth = 0;
        $lineDash = "";
        self::_lineNameToParam($lineName, $lineWidth, $lineDash);

        // カドの見栄えをよくするための調整。
        // 縦線と横線の端が重なるとき、少し縦線を延長する。
        if ($direction == "left" || $direction == "right") {
            // 縦枠線の上端の処理
            $adj = self::_getLineAdjust($exBorderArr, 'top', ($direction == 'left' ? $cellX - 1 : $cellX), $cellY);
            if ($adj > 0)
                $y1 -= ($adj / 5);    // 横線の線幅の1/5の長さだけ、縦線を延長する。太線の場合はもっと伸ばす必要があるかも。でも細線はこのくらいが限界

            // 縦枠線の下端の処理
            $adj = self::_getLineAdjust($exBorderArr, 'bottom', ($direction == 'left' ? $cellX - 1 : $cellX), $cellY);
            if ($adj > 0)
                $y2 += ($adj / 5);
        }

        // 情報を返す
        return array($x1, $y1, $x2, $y2, $lineWidth, $lineDash, $lineColorArr);
    }

    // 上のfunctionのヘルパー
    private function _getLineAdjust($exBorderArr, $direction, $cellX, $cellY)
    {
        $borderWidth1 = 0;
        $borderWidth2 = 0;
        if (isset($exBorderArr[$cellX][$cellY][$direction . 'Border'])) {
            self::_lineNameToParam($exBorderArr[$cellX][$cellY][$direction . 'Border'], $borderWidth1, $dummy);
        }
        if (isset($exBorderArr[$cellX + 1][$cellY]['topBorder'])) {
            self::_lineNameToParam($exBorderArr[$cellX + 1][$cellY][$direction . 'Border'], $borderWidth2, $dummy);
        }
        return ($borderWidth1 >= $borderWidth2 ? $borderWidth1 : $borderWidth2);
    }

    // 線種
    private function _lineNameToParam($lineName, &$lineWidth, &$lineDash)
    {
        $lineWidth = 0;
        $lineDash = "0";
        switch ($lineName) {
            case "hair":
                $lineWidth = 0.05;
                $lineDash = "0";
                break;
            case "dotted":
                $lineWidth = 0.2;
                $lineDash = "1";
                break;
            case "dashDotDot":
                $lineWidth = 0.3;
                $lineDash = "4,2,2,2,2,2";
                break;
            case "dashDot":
                $lineWidth = 0.3;
                $lineDash = "4,2,2,2";
                break;
            case "dashed":
                $lineWidth = 0.2;
                $lineDash = "2,1";
                break;
            case "thin":
                $lineWidth = 0.25;
                $lineDash = "0";
                break;
            case "mediumDashDotDot":
                $lineWidth = 0.4;
                $lineDash = "2,1,1,1,1,1";
                break;
            case "slantDashDot":
                $lineWidth = 0.4;
                $lineDash = "5,2,1,2";
                break;
            case "mediumDashDot":
                $lineWidth = 0.4;
                $lineDash = "5,2,1,2";
                break;
            case "mediumDashed":
                $lineWidth = 0.4;
                $lineDash = "5,2";
                break;
            case "medium":
                $lineWidth = 0.5;
                $lineDash = "0";
                break;
            case "thick":
                $lineWidth = 0.6;
                $lineDash = "0";
                break;
            // 二重囲み線。未実装
            case "double":
                $lineWidth = 0.4;
                $lineDash = "0";
                break;
            default:
                break;
        }
    }

    private function _colorObjToRGB($obj)
    {
        $color = $obj->getRGB();    // FF0000 など
        $res = array();
        $res[0] = hexdec(substr($color, 0, 2));
        $res[1] = hexdec(substr($color, 2, 2));
        $res[2] = hexdec(substr($color, -2));
        return $res;
    }

}
