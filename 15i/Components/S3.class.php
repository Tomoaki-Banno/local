<?php
// AWS SDK for PHP 1 (PHP5.2)
// こちらを使用する場合、AWS の Key と Secret Key は、/aws/config.inc.php の中に記入する
require_once("../aws/sdk.class.php");

// AWS SDK for PHP 2 (PHP5.3以上)
// こちらを使用する場合、AWS の Key と Secret Key は、gen_server_config.yml の中に記入する
//require_once("aws.phar");
//use Aws\S3\S3Client;
//use Aws\S3\Enum\CannedAcl;
//use Aws\Common\Enum\Region;
//use Aws\S3\Exception\S3Exception;
//use Guzzle\Http\EntityBody;

class Gen_S3
{

    // $filePathName    保存対象ファイル（パス付きのファイル名）
    // $fileName        保存する際のファイル名（S3ディレクトリ付き）
    // $overWrite       同名ファイルが既存の場合、上書きするかどうか。trueなら上書き、falseなら別名で保存
    function put($filePathName, $fileName, $overWrite = false)
    {
        $client = self::_getClient();
        
        // ファイル名が既存だったときの処理
        if (!$overWrite) {
            $no = 0;
            $orgFileName = $fileName;
            while(true) {
                if ($no > 0) {
                    $fileName = $orgFileName . "_" . $no;
                }
                if (!$client->if_object_exists(GEN_S3_BUCKET, $fileName)) {
                //if (!$client->doesObjectExist(GEN_S3_BUCKET, $fileName)) {
                    break;
                }
                ++$no;
            }
        }
        
        // 保存
        try {
            $result = $client->create_object(GEN_S3_BUCKET, $fileName, array(
                'fileUpload' => $filePathName,
            ));
            // AWS SDK for PHP 2 (PHP5.3)
//            $result = $client->putObject(array(
//                'Bucket' => GEN_S3_BUCKET,
//                'Key' => $fileName,
//                'Body' => EntityBody::factory(fopen($filePathName, 'r')),
//                'ACL' => CannedAcl::PRIVATE_ACCESS,
//            ));
        } catch (S3Exception $exc) {
            echo $exc->getMessage();
            return false;
        }
        
        // 保存ファイル名を返す
        return $fileName;
    }
    
    function get($fileName, $savePathName)
    {
        $client = self::_getClient();
        try {
            $result = $client->get_object(GEN_S3_BUCKET, $fileName,
                array(
                    'fileDownload' => $savePathName,
                )
            );
            // AWS SDK for PHP 2 (PHP5.3)
//            $result = $client->getObject(
//                array(
//                    'Bucket' => GEN_S3_BUCKET,
//                    'Key' => $fileName,
//                    'SaveAs' => $savePathName,
//                )
//            );
        } catch (S3Exception $exc) {
            echo $exc->getMessage();
            return false;
        }
        return true;
    }
    
    function delete($fileName)
    {
        $client = self::_getClient();
        try {
            $client->delete_object(GEN_S3_BUCKET, $fileName);
            // AWS SDK for PHP 2 (PHP5.3)
//            $client->deleteObject(
//                array(
//                    'Bucket' => GEN_S3_BUCKET,
//                    'Key' => $fileName,
//                )
//            );
        } catch (S3Exception $exc) {
            echo $exc->getMessage();
            return false;
        }
        return true;
    }
    
    function copy($sourceName, $destName)
    {
        $client = self::_getClient();
        try {
            $client->copy_object(
                array( // Source
                    'bucket'   => GEN_S3_BUCKET,
                    'filename' => $sourceName
                ),
                array( // Destination
                    'bucket'   => GEN_S3_BUCKET,
                    'filename' => $destName
                )
            );
            // AWS SDK for PHP 2 (PHP5.3)
//            $client->copyObject(
//                array(
//                    'Bucket' => GEN_S3_BUCKET,
//                    'CopySource' => $sourceName,
//                    'Key' => $destName,
//                )
//            );
        } catch (S3Exception $exc) {
            echo $exc->getMessage();
            return false;
        }
        return true;
    }
    
    function exist($fileName)
    {
        $client = self::_getClient();
        return $client->if_object_exists(GEN_S3_BUCKET, $fileName);
            // AWS SDK for PHP 2 (PHP5.3)
//        return $client->doesObjectExist(GEN_S3_BUCKET, $fileName);
    }
    
    function getFileInfo($fileName)
    {
        $client = self::_getClient();
        
        $res = $client->list_objects(GEN_S3_BUCKET, array(
            'prefix' => $fileName,
        ));     
        return 
            array(
                "LastModified" =>$res->body->Contents->LastModified,
                "Size" => $res->body->Contents->Size,
            );
            // AWS SDK for PHP 2 (PHP5.3)
//        $res = $client->listObjects(array(
//            'Bucket' => GEN_S3_BUCKET,
//            'Prefix' => $fileName,
//        ));        
//        return 
//            array(
//                "LastModified" =>$res['Contents'][0]['LastModified'],
//                "Size" => $res['Contents'][0]['Size'],
//            );
    }
    
    function getDirSize($dir)
    {
        $client = self::_getClient();
        
        $res = $client->list_objects(GEN_S3_BUCKET, array(
            'prefix' => $dir,
        ));
        $total = 0;
        foreach ($res->body->Contents as $object) {
            $total += $object->Size;
        }        
            // AWS SDK for PHP 2 (PHP5.3)
//        $iterator = $client->getIterator('ListObjects', array(
//            'Bucket' => GEN_S3_BUCKET,
//            "Prefix" => $dir,
//        ));
//        $total = 0;
//        foreach ($iterator as $object) {
//            $total += $object['Size'];
//        }        
        return $total;
    }
    
    function listFiles($dir)
    {
        $client = self::_getClient();
   
        $res = $client->list_objects(GEN_S3_BUCKET, array(
            'prefix' => $dir,
        ));
        $arr = array();
        foreach ($res->body->Contents as $object) {
            $arr[] = end(explode("/", $object->Key));
        }        
            // AWS SDK for PHP 2 (PHP5.3)
//        $iterator = $client->getIterator('ListObjects', array(
//            'Bucket' => GEN_S3_BUCKET,
//            "Prefix" => $dir,
//        ));
//        $arr = array();
//        foreach ($iterator as $object) {
//            $arr[] = end(explode("/", $object['Key']));
//        }        
        return $arr;
    }
    
    private function _getClient() 
    {
        $s3 = new AmazonS3();
        $s3->disable_ssl_verification();
        return $s3;
            // AWS SDK for PHP 2 (PHP5.3)
//        return S3Client::factory(
//            array(
//                'key' => GEN_S3_KEY,
//                'secret' => GEN_S3_SECRET,
//                'region' => GEN_S3_REGION,
//            )
//        );
        
    }        
}
