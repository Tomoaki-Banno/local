<?php

class Gen_Image
{

    // ファイル名の拡張子から画像形式を判断し、画像形式をあらわす文字列を返す。
    //  ・jpeg, gif, png の場合はそれぞれ「jpg」「gif」「png」の文字を返す。
    //  ・それ以外はすべて空文字。
    static function getImageType($path)
    {
        // 画像の種類を判断
        $pathinfo = pathinfo($path);
        $ext = strtolower($pathinfo['extension']);

        if ($ext == "jpeg")
            $ext = "jpg";
        if ($ext == "jpg" || $ext == "gif" || $ext == "png") {
            return $ext;
        } else {
            return "";
        }
    }

    // 画像形式がこのサーバー上のGDでサポートされているかどうかを返す。
    // 引数として受け付ける文字列は、上のgetImageType()で得られる「jpg」「gif」「png」のいずれか。
    // 画像形式がこのサーバーでサポートされている場合、trueを返す。
    // 未サポートの場合や、与えられた文字列が上記のいずれでもない場合はfalseを返す。
    static function isSupportedImageType($type)
    {
        switch ($type) {
            case "jpg": return (imagetypes() & IMG_JPG);
            case "gif": return (imagetypes() & IMG_GIF);
            case "png": return (imagetypes() & IMG_PNG);
        }
        return false;
    }

    // 画像ファイルの縦横サイズを変更する。
    //  ・対応形式はjpg/gif/png。種類はファイルの拡張子から判断している。
    //  ・$width と $height で指定されたサイズに拡大縮小する。
    //      ・どちらか一方だけが指定されている場合、指定されているほうを基準に、縦横比が維持されたサイズになる。
    //      ・両方が指定されている場合、縦横比を維持した状態で、縦横の両方が収まるサイズになる。
    //       ※いずれにせよ縦横比は維持される。09iまでは、縦横の両方を指定すると縦横比を無視して指定サイズに合わせていた。
    //          しかし縦横比を崩してリサイズしたいケースは少ないので、仕様を変えた。
    //      ・両方が未指定の場合はなにもしない。
    //  ・$isShrinkOnlyをtrueにすると、縮小のみ行われる。指定サイズより元画像が小さい場合はそのまま。
    // PHPを--with-gd でコンパイルしてある必要がある。
    // さらにjpgを使用するには、サーバーにjpeglibをインストールした上で、PHPを --with-jpeg-dir 付でコンパイル
    //  してある必要がある。

    static function resize($path, $width, $height, $isShrinkOnly)
    {
        // 画像形式の判断
        $type = Gen_Image::getImageType($path);

        // このサーバー上のGDでサポートされているか？
        if (!Gen_Image::isSupportedImageType($type)) {
            return false;
        }

        // 画像を読み込む
        switch ($type) {
            case "jpg":
                $im_inp = @imagecreatefromjpeg($path);
                break;
            case "gif":
                $im_inp = @ImageCreateFromGif($path);
                break;
            case "png":
                $im_inp = @ImageCreateFromPng($path);
                break;
        }
        if (!$im_inp)
            return false;

        $ix = ImageSX($im_inp);    // 読み込んだ画像の横サイズを取得
        $iy = ImageSY($im_inp);    // 読み込んだ画像の縦サイズを取得
        if (!is_numeric($width) && !is_numeric($height)) {
            return false;
        }

        if (!is_numeric($width)) {
            // 横幅未指定のとき
            if ($isShrinkOnly && $iy <= $height)
                return true;
            $width = bcdiv(bcmul($height, $ix), $iy);
        } else if (!is_numeric($height)) {
            // 縦幅未指定のとき
            if ($isShrinkOnly && $ix <= $width)
                return true;
            $height = bcdiv(bcmul($width, $iy), $ix);
        } else {
            // 縦横指定のとき
            //  縦横比を維持した状態で両方が収まるサイズにする
            //  （09iまでは縦横比を崩して指定サイズにあわせていた）
            if ($isShrinkOnly && $iy <= $height && $ix <= $width)
                return true;
            $tempWidth = bcdiv(bcmul($height, $ix), $iy);   // まず縦幅にあわせてみる
            if ($tempWidth > $width) {                      // 横幅がはみだしてしまうなら
                $height = bcdiv(bcmul($width, $iy), $ix);   // 横幅にあわせる
            } else {                                        // 横幅がはみださないなら
                $width = $tempWidth;                        // 縦幅にあわせる
            }
        }

        // サイズ変更後の画像データを生成し、ファイルとして保存
        // 2009から透過画像を扱えるようになった
        switch ($type) {
            case "jpg":
                $im_out = ImageCreate($width, $height);
                Gen_Image::transparentOperation($im_inp, $im_out, $type);
                imagecopyresampled($im_out, $im_inp, 0, 0, 0, 0, $width, $height, $ix, $iy);
                ImageJPEG($im_out, $path);
                break;
            case "gif":
                $im_out = ImageCreateTrueColor($width, $height);
                Gen_Image::transparentOperation($im_inp, $im_out, $type);
                imagecopyresampled($im_out, $im_inp, 0, 0, 0, 0, $width, $height, $ix, $iy);
                ImageGIF($im_out, $path);
                break;
            case "png":
                $im_out = ImageCreate($width, $height);
                Gen_Image::transparentOperation($im_inp, $im_out, $type);
                imagecopyresampled($im_out, $im_inp, 0, 0, 0, 0, $width, $height, $ix, $iy);
                ImagePNG($im_out, $path);
                break;
        }

        // メモリーの解放
        ImageDestroy($im_inp);
        ImageDestroy($im_out);

        return true;
    }

    // 画像のサイズを取得。
    // 結果は width, height の配列となる
    static function getSize($path)
    {
        // 画像形式の判断
        $type = Gen_Image::getImageType($path);

        // このサーバー上のGDでサポートされているか？
        if (!Gen_Image::isSupportedImageType($type)) {
            return false;
        }

        // 画像を読み込む
        switch ($type) {
            case "jpg":
                $im_inp = imagecreatefromjpeg($path);
                break;
            case "gif":
                $im_inp = ImageCreateFromGif($path);
                break;
            case "png":
                $im_inp = ImageCreateFromPng($path);
                break;
        }

        $ix = ImageSX($im_inp);    // 読み込んだ画像の横サイズを取得
        $iy = ImageSY($im_inp);    // 読み込んだ画像の縦サイズを取得
        // メモリーの解放
        ImageDestroy($im_inp);

        return array($ix, $iy);
    }

    // 画像ファイルを圧縮する。
    //  ・$afterSizeで指定されたサイズまで圧縮しようとする。できない場合もある。
    //  ・対応形式はjpg/png。種類はファイルの拡張子から判断している。
    //  ・gifには未対応。必要ない場合が多いと判断したので。やろうと思えばできると思う
    //  ・透過画像には未対応。resize() と同じ方法を使えばできないことはないと思う
    // PHP関連の条件は resize() と同じ。
    static function compress($path, $afterSize)
    {
        // 画像形式の判断
        $type = Gen_Image::getImageType($path);

        // このサーバー上のGDでサポートされているか？
        if (!Gen_Image::isSupportedImageType($type)) {
            return false;
        }

        // 圧縮する必要があるか
        if (filesize($path) <= $afterSize) {
            return false;
        }

        // 画像を読み込む
        switch ($type) {
            case "jpg":
                $readFunc = "imagecreatefromjpeg";
                $writeFunc = "imagejpeg";
                $qStart = 100;
                $qEnd = 1;
                $qStep = -10;
                break;
            case "gif":
                return false;   // Not Support
                break;
            case "png":
                $readFunc = "imagecreatefrompng";
                $writeFunc = "imagepng";
                $qStart = 1;
                $qEnd = 9;
                $qStep = 1;
                break;
        }
        if (!($im_inp = @$readFunc($path))) {
            return false;
        }
        $quality = $qStart;
        $tmpPath = $path . ".tmp";
        while(true) {
            if (!$writeFunc($im_inp, $tmpPath, $quality)) {
                break;
            }
            if ($type == "jpg") {
                // imagejpeg で生成されたファイルは、filesize() による直接計測がうまくいかない
                copy($tmpPath, $tmpPath."2");
                $size = filesize($tmpPath."2");
            } else {
                $size = filesize($tmpPath);
            }
            if ($size <= $afterSize) {
                break;
            }
            $quality += $qStep;
            if (($qStep > 0 && $quality >= $qEnd) || ($qStep < 0 && $quality <= $qEnd)) {
                break;
            }
        }
        rename($tmpPath, $path);

        // メモリーの解放
        ImageDestroy($im_inp);

        return true;
    }

    //  透過関連の処理
    static function transparentOperation(&$im_inp, &$im_out, $type)
    {
        $transColor = imagecolortransparent($im_inp);
        if ($transColor >= 0) { // 透過色が設定されている
            // 入力画像から透明色に指定してある色（RGBの配列）を取得する
            $transColorArr = imagecolorsforindex($im_inp, $transColor);

            // 色の設定
            $transColor = imagecolorallocate($im_out, $transColorArr['red'], $transColorArr['green'], $transColorArr['blue']);

            // 透明色（にする色）で塗りつぶす
            imagefill($im_out, 0, 0, $transColor);

            // 透明色設定
            imagecolortransparent($im_out, $transColor);
        } elseif ($type == "png") {
            // アルファチャンネル情報を保存するには、アルファブレンディングを解除する必要がある
            imagealphablending($im_out, false);
            imagesavealpha($im_out, true);

            // 透過色設定
            $color = imagecolorallocatealpha($im_out, 0, 0, 0, 127);
            imagefill($im_out, 0, 0, $color);
        }
    }

    // Jpeg画像のExif情報に画像の方向（ORIENTATION）が記録されている場合、それにしたがって
    // 画像を回転させて本来の向きに修正する。同時にExif情報を削除する。
    // 画像の保存時に、それがiPhone等で撮影された写真である可能性があるときにこの処理を行う。
    //
    // iPhone/iPadで撮影した写真は、横転したり天地逆の状態で保存されていることがよくある。
    // iPhoneはホームボタンを右にして横向き（シャッターボタンは下）にするのがカメラとしての
    // 正しい向き。それ以外の方向で撮影すると、保存される写真は横転または天地逆の状態となる。
    // しかしiPhoneは撮影時に上下を判断し、写真としての本来の向きをExif情報に保存する。
    // iPhone/iPad/Mac等ではこのExif情報を参照して、向きを補正した上で写真を表示するので、
    // それらのデバイスでは常に正しい向きに表示される。
    // しかし Windows上のブラウザやエクスプローラはExif情報を参照せず、保存されているままの
    // 方向で表示するので、iPhone等で撮影した写真の向きがおかしくなることがある。
    // そのため、ここではExif情報にしたがって画像を回転させ、かつExif情報を削除することで
    // どのデバイスでも正しい向きで保存されるようにする。
    static function rotateJpegImageByExif($jpegFile) {
        // 処理対象はJpegのみ
        if (self::getImageType($jpegFile) != "jpg") {
            return;
        }

        // Exif情報を取得
        require_once (ROOT_DIR . "Pel/PelJpeg.php");
        // 画像によってはPelのイニシャライズに失敗する。ag.cgi?page=ProjectDocView&pid=1574&did=198103
        try {
            $jpeg = new PelJpeg($jpegFile);
        } catch (Exception $e) {
            return;
        }
        $exif = $jpeg->getExif();
        if ($exif) {
            $tiff = $exif->getTiff();
            if ($tiff) {
                $ifd = $tiff->getIfd();
                if ($ifd && $ifd->getEntry(PelTag::ORIENTATION)) {
                    $orientation = $ifd->getEntry(PelTag::ORIENTATION)->getValue();
                    if ($orientation != 1) {    // 1: 無回転
                        $degree = 0;
                        switch ($orientation) {
                            case 3: $degree = 180; break;
                            case 6: $degree = 270; break;
                            case 8: $degree = 90; break;
                            default: throw new Exception('invalid orientation');

                            // ちなみに以下は反転系なので、写真の補正という意味ではほぼありえない
                            //  2: 左右反転
                            //  4: 上下反転
                            //  5: 時計回り90度 + 左右反転
                            //  7: 反時計回り90度 + 左右反転
                        }

                        // 画像を回転
                        $srcImage = ImageCreateFromJPEG($jpegFile);
                        $rotateImage = ImageRotate($srcImage, $degree, 0 /* 0は黒 */);
                        Gen_Image::transparentOperation($srcImage, $rotateImage, "jpg");

                        // Jpeg保存。この時点でexifが消える
                        ImageJPEG($rotateImage, $jpegFile);
                        ImageDestroy($srcImage);
                        ImageDestroy($rotateImage);

                        // exif情報を保存する例
                        //$entry->setValue(1);    // 1:保存されている向きと同じ
                        //file_put_contents($testFile, $jpeg->getBytes());
                    }
                }
            }
        }
    }
}
