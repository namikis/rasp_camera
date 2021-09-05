<?php

    ini_set("display_errors", "On");
    error_reporting(E_ALL);

    require '/home/ec2-user/vendor/autoload.php';
    use Aws\S3\S3Client;
    use Aws\S3\Exception\S3Exception;  

    $s3 = new S3Client([
        'version' => 'latest',
        'region' => 'ap-northeast-1'
    ]);

    $ini_file = parse_ini_file("../app.ini",true);
    $DB = "MySQL";
    $user = $ini_file[$DB]['USER'];
    $password = $ini_file[$DB]['PASSWORD'];
    $host = $ini_file[$DB]['HOST'];
    $db_name = $ini_file[$DB]['DB_NAME'];

    $bucket = 'rasp-camera';

    try{
        $dsn = 'mysql:dbname=' . $db_name . ';host=' . $host . ';charset=utf8';
        $dbh = new PDO($dsn,$user,$password);
        $dbh->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
        $sql = "delete from pictures where pic_name=:pic_name";

        try {
            foreach($_POST['del_pic'] as $key){
                $s3->deleteObject([
                    'Bucket' => $bucket,
                    'Key' => 'pictures/' . $key . ".jpg"
                ]);
            $stmt = $dbh->prepare($sql);
            $stmt->execute(array(':pic_name' => $key));
            }
        } catch (S3Exception $e) {
            echo $e->getMessage() . PHP_EOL;
        }
        $dbh = null;

    }catch(PDOException $e){
        echo $e->getMessage() . PHP_EOL;
    }

    echo "削除しました。";

    header("Location: http://35.76.184.39/manage/list.php");
    exit();