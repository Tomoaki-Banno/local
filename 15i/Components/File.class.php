<?php

class Gen_File
{

    // パスからファイル名を取得。
    //   basename関数を使えばよいように思えるが、PHP5のbasenameにはバグがあって日本語を正しく処理
    //   できない。　http://bugs.php.net/bug.php?id=37738
    static function path2FileName($path)
    {
        $dir = dirname($path);
        if ($dir == ".")
            return $path;
        return substr($path, strlen($dir) + 1);
    }

    // ディレクトリのサイズを取得(Byte)。
    static function getDirSize($dir)
    {
        $handle = opendir($dir);
        $size = 0;
        while ($file = readdir($handle)) {
            if ($file != '..' && $file != '.' && !is_dir($dir.'/'.$file)) {
                $size += filesize($dir . '/' . $file);
            } else if (is_dir($dir . '/' . $file) && $file != '..' && $file != '.') {
                $size += self::getDirSize($dir . '/' . $file);
            }
        }
        return $size;
    }

    // ファイルストレージ（レコード・チャットの添付ファイル）容量オーバーチェック
    static function checkFileStorageSize($addSize = 0)
    {
        $storage1 = new Gen_Storage("Files");
        $storage2 = new Gen_Storage("ChatFiles");
        $storage3 = new Gen_Storage("ItemImage");
        $usage = $storage1->getDirSize() + $storage2->getDirSize() + $storage3->getDirSize() + $addSize;
        return ($usage > GEN_FILE_STORAGE_SIZE * 1024 * 1024);
    }

    // アップロードされたファイルの保存処理
    //  画像の場合、必要に応じてリサイズや画像回転も行う
    //  引数
    //      $file                   $_FILES['uploadFile']
    //      $storageCat             Gen_Storageのカテゴリ
    //      $isCheckStorageSize     ファイルストレージの容量オーバーチェックを行うか
    //      $maxWidth, $maxHeight   (jpg/gif/png画像のみ)指定された場合、収まるようにリサイズを行う。
    //                                  どちらか一方だけが指定された場合、縦横比を維持してリサイズする。
    //                                  両方無指定の場合はリサイズしない。
    static function saveUploadFile($file, $storageCat, $isCheckStorageSize, $maxWidth, $maxHeight)
    {
        // ストレージ容量のチェック
        if ($isCheckStorageSize && self::checkFileStorageSize($file['size'])) {
            return "storageError";
        }

        // クライアントマシン上のファイル名。
        // パスからクライアントファイル名を取得するにはbasename関数を使えばよいように思えるが、
        // PHP5のbasenameにはバグがあって日本語を正しく処理できない。　http://bugs.php.net/bug.php?id=37738
        $clientFileName = Gen_File::path2FileName($file['name']);

        // アップロードテンポラリファイル名
        $uploadPathName = $file['tmp_name'];

        // 拡張子
        $pinfo = pathinfo($clientFileName);
        $extension = "." . $pinfo['extension'];

        // 画像関連の処理
        $imageType = Gen_Image::getImageType($clientFileName);
        if ($imageType != "") {
            // この後の処理のため、アップロードファイル名に拡張子をつける
            $uploadPathNameExt = $uploadPathName . $extension;
            rename($uploadPathName, $uploadPathNameExt);
            $uploadPathName = $uploadPathNameExt;

            // iPhone等で撮影した写真の向きの補正。詳細は下記functionのコメントを参照。
            if ($imageType == "jpg") {
                Gen_Image::rotateJpegImageByExif($uploadPathName);
            }

            // 画像のリサイズ（縮小のみ）
            if ($maxWidth != "" || $maxHeight != "") {
                sleep(0.5);   // なぜかこれがないとリサイズに失敗することがある
                if (!Gen_Image::resize($uploadPathName, $maxWidth, $maxHeight, true)) {
                    unlink($uploadPathName);
                    return "imageTypeError";
                }
            }
            // 0.5MBを超える場合、圧縮処理を行う
            $limitSize = 0.5 * 1024 * 1024;
            if ($file['size'] > $limitSize) {
                Gen_Image::compress($uploadPathName, $limitSize);
                $file['size'] = filesize($uploadPathName);
            }
        }
        $storage = new Gen_Storage($storageCat);
        $newName = $storage->put($uploadPathName);

        // 元ファイル名、保存ファイル名、ファイルサイズを返す
        return array($clientFileName, $newName, $file['size']);
    }

    // 安全なファイルパスを生成する　ag.cgi?page=ProjectDocView&pid=1574&did=196015
    //  ファイルアクセスする際、ディレクトリ名やファイル名にユーザー入力値を使用すると、
    //  ディレクトリトラバーサルおよびヌルバイト攻撃に対する脆弱性が発生する恐れがある。
    //  この関数を使用してパスを組み立てるようにすれば安全。
    //      $dirName: ディレクトリ名
    //      $unsafeFileName: ファイル名（ディレクトリ名を含んではいけない）
    //  ただしディレクトリ名($dirName)については、アクセスを許可するディレクトリを固定値
    //  として指定し、ユーザー入力値はその下に指定すること。
    //      例： $dirName = "/path/to/" . $form['user_dir_name'];
    //          上記では「/path/to/」以下のすべてのディレクトリ・ファイルにアクセス可能
    //　また、realpath()を使用しているため以下の制限があることにも注意。
    //      ag.cgi?page=ProjectDocView&pid=1574&did=203959
    //      ・存在しないファイルには使用できない
    //      ・サーバー側がシンボリックリンクを使用した構成になっている場合は使用できない
    //  　※ちなみに Gen_Storage::_isFilesDirPath() では、realpath() を使用しないことで
    //  　　（そのかわりディレクトリ名に使用できる文字を半角英数字に限定）、未存在のパス・
    //  　　ファイルでもチェックできるようにしている。

    static function safetyPath($dirName, $unsafeFileName)
    {
        // ヌルバイト攻撃対策
        // 通常ヌル文字が入力に混入することは有り得ない
        // 検知したら即攻撃とみなし、処理を中断する
        if (strpos($unsafeFileName, "\0") !== false) {
            throw new Exception();
        }

        // ディレクトリ名は末尾セパレータの有無にかかわらず
        // 処理できるように
        $dirName = rtrim($dirName, SEPARATOR) . SEPARATOR;

        // ディレクトリトラバーサル対策
        // $dirName が realpath関数を通したものと一致することを確認することで、../ などの不正な文字
        // が含まれていないことを確認する。
        // ちなみに単純に「../」が含まれていないかどうかチェックするだけだと、エンコードにより回避される可能性がある
        if (str_replace("\\", "/", rtrim($dirName, SEPARATOR)) != str_replace("\\", "/", realpath($dirName))) {
            throw new Exception();
        }
        // ファイル名から、ファイル名以外の不正な文字（../など）を取り除く。
        $fileName = self::path2FileName($unsafeFileName);

        return $dirName . $fileName;
    }

    // 安全なファイルパスを作成する(Action名用)
    // この関数を通してActionクラスへのパスを作成することで
    // リモートファイルインクルード攻撃に対して安全になる
    static function safetyPathForAction($actionName)
    {
        $nodes = explode('_', $actionName);

        $fileName = array_pop($nodes) . '.class.php';

        $dirName = APP_DIR . join(SEPARATOR, $nodes);

        $path = self::safetyPath($dirName, $fileName);

        if (!file_exists($path)) {
            throw new Exception('Action does not exists.');
        }

        return $path;
    }
}
