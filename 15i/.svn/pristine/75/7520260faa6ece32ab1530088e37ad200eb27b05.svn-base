<?php

class Gen_Storage
{
    private $type;
    private $dir;
    
    function __construct($category)
    {
        switch($category) {
            // AWS S3 に保存するカテゴリ。
            //  多数・大容量のファイルが保存される可能性があるものはこちらにする。
            //　容量の心配をする必要がないが、速度は速くない（files_dirの数十から数百倍）し、料金がかかる。
            case "Files": 
            case "ItemImage": 
            case "ChatFiles": 
                if (GEN_USE_S3) {
                    $this->type = "S3";
                } else {
                    $this->type = "files_dir";
                }
                break;

            // files_dir に保存するカテゴリ。
            //  多数・大容量のファイルが保存される心配がないものはこちらにする。
            //　S3より速度が速く、利用料金がかからない。
            case "BackupData":
            case "CompanyLogo":
            case "ProfileImage":
            case "ReportTemplates":
            case "MRPProgress":
            case "JSGetText":
                $this->type = "files_dir";
                break;
            
            default:
                throw new Exception("Gen_Storage の カテゴリ ". h($category) . "が正しくありません。");
        }
        
        // カテゴリ名をそのままディレクトリ名とする
        if ($this->type == "S3") {
            // S3
            $this->dir = GEN_DATABASE_NAME . "/{$category}/";
        } else {
            // files_dir
            $this->dir = GEN_FILES_DIR . GEN_DATABASE_NAME . "/{$category}/";
            if (!is_dir($this->dir)) {
                mkdir($this->dir, 0770, true);
            }
        }
    }
    
    function isS3()
    {
        return ($this->type == "S3");
    }

    // $filePathName    保存するファイル（パスと名前）。このファイルが、S3もしくはfiles_dir内の該当カテゴリのディレクトリに配置される。
    // $overWrite       同名ファイルが既存の場合、上書きするかどうか。trueなら上書き、falseなら別名で保存
    // $saveName        保存時のファイル名。省略すると元ファイルと同名になる（$overWriteがfalseで同名既存なら別名）。
    //                  ディレクトリを含むことも可能。つまり、S3もしくはfiles_dirの該当カテゴリのディレクトリ内にさらに子ディレクトリを作って配置できる。
    function put($filePathName, $overWrite = false, $saveName = "")
    {
        if ($saveName == "") {
            $saveName = Gen_File::path2FileName($filePathName);
        }
        if ($this->type == "S3") {
            // S3
            $s3 = new Gen_S3();
            $saveName = $s3->put($filePathName, $this->dir . $saveName, $overWrite);
            return end(explode("/", $saveName));   // ディレクトリ名は除き、ファイル名だけを返す
        } else {
            // files_dir
            if (!self::_isFilesDirPath($this->dir . $saveName)) {
                return false;
            }
            if (!$overWrite) {
                $no = 0;
                $orgFileName = $saveName;
                while (file_exists($this->dir . $saveName)) {
                    $saveName =  $orgFileName . (++$no); 
                }
            }
            copy($filePathName, $this->dir . $saveName);
            return $saveName;
        }
    }
    
    // ファイルのパスを返す。
    // S3の場合はgetしてテンポラリディレクトリに配置した上でそのパスを返す。
    function get($fileName)
    {
        if ($this->type == "S3") {
            // S3
            $savePathName = tempnam(GEN_TEMP_DIR, "");
            $s3 = new Gen_S3();
            $res = $s3->get($this->dir . $fileName, $savePathName);
            if ($res) {
                return $savePathName;
            } else {
                return false;   // 取得失敗
            }
        } else {
            // files_dir
            if (!self::_isFilesDirPath($this->dir . $fileName)) {
                return false;
            }
            return $this->dir . $fileName;
        }
    }
    
    function makeDir($dirName)
    {
        if ($this->type == "S3") {
            // S3はputの時点で自動的にディレクトリが作成されるので、なにもする必要がない
        } else {
            // files_dir
            if (!self::_isFilesDirPath($this->dir . $dirName)) {
                return false;
            }
            if (!is_dir($this->dir . $dirName)) {
                mkdir($this->dir . $dirName, 0770, true);
            }
        }
    }
    
    function delete($fileName)
    {
        if ($this->type == "S3") {
            // S3
            $s3 = new Gen_S3();
            return $s3->delete($this->dir . $fileName);  // true or false
        } else {
            // files_dir
            if (!self::_isFilesDirPath($this->dir . $fileName)) {
                return false;
            }
            if (file_exists($this->dir . $fileName)) {
                unlink($this->dir . $fileName);
            }
            return true;
        }
    }
    
    function rename($oldName, $newName)
    {
        if ($this->type == "S3") {
            // S3
            $s3 = new Gen_S3();
            if ($s3->copy($this->dir . $oldName, $this->dir . $newName)) {
                if ($s3->delete($this->dir . $oldName)) {
                    return true;
                }
            }
            return false;
        } else {
            // files_dir
            if (!self::_isFilesDirPath($this->dir . $oldName)) {
                return false;
            }
            if (!self::_isFilesDirPath($this->dir . $newName)) {
                return false;
            }
            rename($this->dir . $oldName, $this->dir . $newName);
        }
    }
    
    function exist($fileName)
    {
        if ($this->type == "S3") {
            // S3
            $s3 = new Gen_S3();
            return $s3->exist($this->dir . $fileName);
        } else {
            // files_dir
            return file_exists($this->dir . $fileName);
        }
    }
    
    function getFileInfo($fileName)
    {
        if ($this->type == "S3") {
            // S3
            $s3 = new Gen_S3();
            return $s3->getFileInfo($this->dir . $fileName);
        } else {
            // files_dir
            return 
                array(
                    "LastModified" => filemtime($this->dir . $fileName),
                    "Size" => filesize($this->dir . $fileName),
                );
        }
    }
    
    function getDirSize()
    {
        if ($this->type == "S3") {
            // S3
            $s3 = new Gen_S3();
            return $s3->getDirSize($this->dir);
        } else {
            // files_dir
            return Gen_File::getDirSize($this->dir);
        }
    }
    
    // ディレクトリ内のファイル名を配列で返す
    function listFiles()
    {
        if ($this->type == "S3") {
            // S3
            $s3 = new Gen_S3();
            return $s3->listFiles($this->dir);
        } else {
            // files_dir
            $arr = array();
            $handle = opendir($this->dir);
            if ($handle) {
                while (false !== ($file = readdir($handle))) {
                    if ($file != "." && $file != "..")
                        $arr[] = $file;
                }
                closedir($handle);
            }
            return $arr;
        }
    }
    
    // 与えられたパスが files_dir（$this->dir）の配下であることを確認する。ag.cgi?page=ProjectDocView&pid=1574&did=196015
    // ディレクトリトラバーサル対策。
    // 同時にヌルバイト攻撃対策も行う。
    // パス（ディレクトリ名）に使用できる文字は半角英数字と「:」「_」「-」のみ。 ag.cgi?page=ProjectDocView&pid=1574&did=203959
    // 　ファイル名はそれ以外でもOK。
    //   未存在のファイル・パスもチェックできる。
    // ※ちなみに Gen_File::safetyPath() では realpath() を使用してチェックしているため、
    //　　パスに半角英数字以外も使用できる。そのかわり未存在のパス・ファイルをチェックできない
    private function _isFilesDirPath($path)
    {
        // ヌルバイト攻撃対策
        // 通常ヌル文字が入力に混入することは有り得ない
        // 検知したら即攻撃とみなし、処理を中断する
        if (strpos($path, "\0") !== false) {
            throw new Exception();
        }
        
        // ディレクトリトラバーサル対策
        if (substr($path, 0, strlen($this->dir)) !== $this->dir) {
            return false;
        }
        $_path = str_replace('\\', '/', $path);
        $_path = dirname($_path);
        $_path = trim($_path, '/');
        foreach(explode('/', $_path) as $node) {
            if (!preg_match('/\A[a-z0-9:_-]+\z/i', $node)) {
                return false;
            }
        }
        return true;
        // realpathを使用している理由については Gen_File::safetyPath() のコメントを参照。
//        $realFilesDir = realpath($this->dir);
//        return substr(realpath($path), 0, strlen($realFilesDir)) == $realFilesDir;
    }
}
