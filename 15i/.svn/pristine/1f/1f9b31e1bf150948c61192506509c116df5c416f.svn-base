<?php

class Gen_Download
{

    //************************************************
    // ファイルのダウンロード処理
    //************************************************
    // 引数：
    //    $sourcePathName     ダウンロードするファイルのパスと名前
    //    $defaultName        ダウンロードファイルにつけるデフォルトの名前
    //    $sourceFileDelete
    //    $lastModified       (15i)最終更新日時。ブラウザキャッシュを有効にしたいファイル（主に画像）の場合に指定する。
    //                          これを指定するとヘッダに Last-Modified が付加される。
    //                          任意のタイミングでブラウザから更新確認が来るが、それへの応対はファイルDL部（index.php）で行う。

    static function DownloadFile($sourcePathName, $defaultName, $sourceFileDelete = true, $lastModified = null)
    {
        if (!file_exists($sourcePathName)) {
            throw new Exception();
        }
        // ファイルの拡張子から Mime Type を判定。同時に画像かどうかを判定。
        // ブラウザによっては Mime Type が正確でないと正しく動作しない（ファイル名の拡張子よりMime Typeを優先）ものもある。
        // また Mac OS X では Content-Disposition の filename が日本語のとき、Mime Type が正しく設定されていないと文字化けするようだ。
        $mime = "";
        $isImage = false;
        $pinfo = pathinfo($defaultName);
        switch(strtolower($pinfo['extension'])) {
            case "jpg":
            case "jpeg":
                $mime = "image/jpeg";
                $isImage = true;
                break;
            case "gif":
                $mime = "image/gif";
                $isImage = true;
                break;
            case "png":
                $mime = "image/png";
                $isImage = true;
                break;
            case "bmp":
                $mime = "image/bmp";
                $isImage = true;
                break;
            case "txt":
                $mime = "text/plain";
                break;
            case "csv":
                $mime = "text/csv";
                break;
            case "doc":
                $mime = "application/msword";
                break;
            case "docx":
                $mime = "application/vnd.openxmlformats-officedocument.wordprocessingml.document";
                break;
            case "xls":
                $mime = "application/vnd.ms-excel";
                break;
            case "xlsx":
                $mime = "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet";
                break;
            case "ppt":
                $mime = "application/vnd.ms-powerpoint";
                break;
            case "pptx":
                $mime = "application/vnd.openxmlformats-officedocument.presentationml.presentation";
                break;
            case "mp3":
                $mime = "audio/mpeg";
                break;
            case "mp4":
                $mime = "audio/mp4";
                break;
            case "wav":
                $mime = "audio/x-wav";
                break;
            case "mpg":
            case "mpeg":
                $mime = "video/mpeg";
                break;
            case "wmv":
                $mime = "video/x-ms-wmv";
                break;
            case "zip":
                $mime = "application/zip";
                break;
            case "lha":
            case "lzh":
                $mime = "application/x-lzh";
                break;
            case "tar":
            case "tgz":
                $mime = "application/x-tar";
                break;
            case "json":
                $mime = "application/json";
                break;
            default:
                $mime = "application/octet-stream";
                break;
        }

        // ファイル出力
        if ($lastModified) {
            // ブラウザキャッシュする。（頻繁に表示される画像。自社ロゴ・プロフィール画像など）
            // 下記のように設定するとファイルはブラウザキャッシュされる。
            // 任意のタイミングでブラウザから更新確認が来るが、それへの応対はファイルDL部（index.php）で行う。
            //  ・Last-Modified : あり（Apacheのデフォルトでは、静的ファイルの場合は自動付加されるが、PHPの場合はつかない。明示的に指定する必要がある）
            //  ・Expires : 過去日付
            //  ・Pragma : なし or private
            //  ・Cache-Control : private, max-age
            header("Last-Modified: " . date('r', $lastModified));
            header("Expires: " . date('r', strtotime('1980-1-1'))); // Expiresに過去日付
            header("Pragma: private");  
            header("Cache-Control: private, max-age=" . (60*60*24));    // 24H有効  
        } else {
            // ブラウザキャッシュしない。（上記以外）
            $agent = getenv("HTTP_USER_AGENT");
            if (mb_ereg("MSIE 6.0", $agent) || mb_ereg("MSIE 7.0", $agent) || mb_ereg("MSIE 8.0", $agent)) {
                // IE8以下は、SSL使用時に no-cache を指定すると「このインターネットのサイトを開くことができません」
                // と表示されてファイルをダウンロードできない問題がある。やむをえずキャッシュ有効とする。
                //  http://support.microsoft.com/kb/323308/ja
                header("Cache-Control: must-revalidate, max-age=0, post-check=0, pre-check=0");
                header("Pragma: public");
            } else {          
                // 下記のように設定するとブラウザキャッシュが無効になる。
                //  ・Last-Modified : なし（Apacheのデフォルトでは、静的ファイルの場合は自動付加されるが、PHPの場合はつかない）
                //  ・Expires : なし
                //  ・Pragma : なし or private
                //  ・Cache-Control : no-cache
                header("Cache-Control: no-cache");  
            }
        }
        header("Content-type: {$mime}");
        // ファイル名文字化け回避。
        $defaultName = str_replace("?","",$defaultName);
        $defaultName = str_replace("/","",$defaultName);
        $defaultName = str_replace(";","",$defaultName);
        $ua = $_SERVER['HTTP_USER_AGENT'];
        if (stripos($ua, 'MSIE') !== false || stripos($ua, 'Trident') !== false) {
            // IEの場合、filename は SJIS-winに変換しておく必要がある（Shift-JISだと環境依存文字で問題あり）
            // http://support.microsoft.com/kb/436616/
            // urlencode() する方法もあるが、それだとファイル名が189文字を超える場合に名前の一部が欠損するし、
            // firefoxで問題がある。
            $defaultName = mb_convert_encoding($defaultName, "SJIS-win","UTF-8");
            $defaultName = str_replace('#', '%23', $defaultName);
        }
        $fileSize = filesize($sourcePathName);
        header("Content-Disposition:attachment;filename=\"{$defaultName}\"");
        header("Content-Length: " . $fileSize);

        //  時々メモリ不足エラーが発生（エラーメッセージが画面に表示されたりCSVファイルに書き出されたり）していたため、
        //  readfileで一気に出力するのをやめ（readfileではファイル全体がいったんメモリ上にバッファされる）、
        //  少しずつ出力するようにした。
        //  15i以降では、ファイルサイズが1MB以下の場合は以前のように readfile を使用するようにした。
//        if ($fileSize > 1024 * 1024) {
            $fh = fopen($sourcePathName, "r");
            while (!feof($fh)) {
                echo fread($fh, 4096);
                @ob_flush();    // バッファの内容を出力（ob_startしていないので無意味か？）
            }
            fclose($fh);
//        } else {
//            readfile($sourcePathName);     // ファイルを読み込んでHTTP出力
//        }

        // テンポラリファイルの削除
        if ($sourceFileDelete)
            unlink($sourcePathName);

        // スクリプトを終了。
        // 09iまではここでいったんバッファクリアし、処理を継続していた。
        // （ダウンロードに続いて、index.phpに戻って tplの出力が行われていた。
        // 　そのため、ダウンロードと同時にactionリダイレクトやtplの表示を行うことが可能だった。）
        // しかしそれだと、ダウンロードのあとすぐに別の画面に遷移したり、ajax通信を行うと、
        // httpヘッダが出力されず、表示が乱れることがあった。
        // そのため、10iからはここで強制的にスクリプトを終了する（actionリダイレクトやtpl出力を行わない）ことにした。
        exit();

        // 以下は09iまで使用していた処理。
        // 出力と、バッファのクリア
        //    送信済みのヘッダをクリアすることで、actionリダイレクト先でのエラーを回避する（？）
        //flush();                    // echoした内容をブラウザへ送信
        //while(@ob_end_flush());      // 出力バッファの内容をブラウザへ送信し、出力バッファを終了（ob_startしていないので無意味か？）
        //// 出力バッファの開始
        //@ob_start();                 // 出力バッファを開始。以前は while(ob_start());　していたが、メモリ不足エラーに対処するため変更
        //
        //$form['gen_restore_search_condition'] = 'true';
    }

}