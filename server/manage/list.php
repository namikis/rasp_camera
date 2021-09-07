<?php

 ini_set("display_errors", "On");
 error_reporting(E_ALL);

 require '/home/ec2-user/vendor/autoload.php';
 use Aws\S3\S3Client;
 use Aws\S3\Exception\S3Exception;
 
 $IMAGE_DIR = "../pictures/";
 $PAGE_TITLE = "静止画取得システム・一覧画面";
 
 $HTML_BASE =<<<EOT
 <!DOCTYPE html>
 <html>
 <head>
     <meta charset="UTF-8">
     <title><!--PAGE_TITLE--></title>
     <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.6.3/css/all.css">
     <script src="http://code.jquery.com/jquery-latest.min.js"></script>
     <link rel="stylesheet" href="../main.css">
 <head>
 <body>
         <header>
             <h1><!--PAGE_TITLE--></h1>
         </header>
         <div class="list_wrapper">
         <div class="back_link"><a href="/manage/index_m.php">戻る</a></div>
         <form method="post" action="delete.php">
            <div class="pictures">
                <!--PICTURE_DIV-->
            </div>
            <div class="del_button">
                 <input type="submit" value="選択したファイルを削除">
            </div>
        </form>
         </div>
 </body>
 </html>
 EOT;
 
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
 
 try {
     $dsn = 'mysql:dbname=' . $db_name . ';host=' . $host . ';charset=utf8';
     $dbh = new PDO($dsn,$user,$password);
     $dbh->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
 
     $sql = "select id,pic_name,time_stamp from pictures order by id desc";
     $stmt = $dbh->prepare($sql);
     $stmt->execute();
 
     $pic_div="";
     while($rec = $stmt->fetch(PDO::FETCH_ASSOC)){
        $id = $rec['id'];
        $taken_time = $rec['time_stamp'];
        $pic_name = $rec['pic_name'] . ".jpg";
        $pic_path = $IMAGE_DIR . $pic_name;
        if(file_exists($pic_path) == false){
            // ローカルに無ければDL
                try {
                    $result = $s3->getObject([
                        'Bucket' => 'rasp-camera',
                        'Key' => 'pictures/' . $pic_name,
                        'SaveAs' => '../pictures/'.$pic_name,
                    ]);
    
                } catch (S3Exception $e) {
                    echo $e->getMessage() . PHP_EOL;
                }    
        }
        $pic_div .= '<div class="picture"><p class="taken_time"><input type="checkbox" name="del_pic[]" value=' . $rec['pic_name'] .'
                    >' . $taken_time . '</p><div class="pic_image"><img src=' . $pic_path .'></div></div>';
     }
 } catch (PDOException $e) {
     echo "データベースとの接続でエラーが発生：" . $e->getMessage() . "<br/>";
 }

 if($pic_div == ""){
     $pic_div = "<p class='no_picture' style='font-size:20px; padding:20px;'>まだ写真が撮影されていません。</p>";
 }
  
 $output_html = str_replace( "<!--PAGE_TITLE-->", $PAGE_TITLE, $HTML_BASE);
 $output_html = str_replace( "<!--PICTURE_DIV-->", $pic_div, $output_html);
 
 print $output_html;