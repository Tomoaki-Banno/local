<?php

class Config_Download_Download
{

    // ログインユーザーのみダウンロード可能としたいファイルは、Downloadフォルダに置いた上で
    // index.php?action=Config_Download_Download&file=ファイル名　のダウンロードリンクを設置する。
    // ログインした全ユーザーがダウンロードできる（index.phpでアクセス権チェックをスキップしている）。
    // アクセス権コントロールが必要な場合はカスタマイズが必要。
    
    // 15i追記
    //  このクラスおよびDownloadディレクトリを使用するのは、ソース内に含まれるファイルのみ。
    //　ユーザーが登録するファイル（会社ロゴ・品目画像・プロフィール画像・帳票テンプレート・登録ファイルetc）は
    //　files_dir で指定されたディレクトリに保管し、index.php?action=download&cat=XXX&name=YYY で
    //　ダウンロードする。

    function execute(&$form)
    {
        $file = DOWNLOAD_DIR . Gen_File::path2FileName(@$form['file']);
        if (file_exists($file)) {
            Gen_Download::DownloadFile($file, $form['file'], false);
        } else {
            header("Location:index.php?action=Logout");
        }
        return;
    }

}