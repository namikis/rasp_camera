<?php

ini_set("display_errors", 'On');
error_reporting(E_ALL);

require '/home/ec2-user/vendor/autoload.php';
use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;

$IMAGE_DIR = "../pictures/";
$PAGE_TITLE = "静止画取得システム";

$HTML_BASE =<<<EOT
<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<title><!--PAGE_TITLE--></title>
	<link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.6.3/css/all.css">
	<link rel="stylesheet" href="../main.css">
	<link rel="icon" href="../pictures/cam_icon.png">
	<script src="http://code.jquery.com/jquery-latest.min.js"></script>
  <script>
		function Reload(){
    	            window.location.reload();
    	        }
	        $(function () {
	            var ws = new WebSocket("ws://192.168.1.40:5555/");
		    ws.onopen = onOpen;
		    ws.onerror = onError;

	            $('#btn').on('click',function(){
	              ws.send($('#btn').text());
		      window.setTimeout(Reload,1500);
		      console.log("reloaded.");
	            });

		    function onOpen(){
		      console.log("connected.");
		      $("#session_judge").text("connected socket_server.").css('color','green');
		    }

		    function onError(){
		      console.log("connection failed.");
		      $("#session_judge").text("connection failed, please starting socket_server.").css('color','red');
		    }
        	})
  </script>
<head>
<body>
		<header>
			<h1><!--PAGE_TITLE--></h1>
		</header>
		<div class="whole_wrapper">
			<div class="pic_wrapper">
				<p id="session_judge"></p>
				<p>撮影時刻：<!--TAKEN_TIME--></p>
				<img src="<!--RECENT_PICTURE_PATH-->"
			</div>
			<div class="button_wrapper">
				<i id="btn" class="fas fa-camera-retro"></i>
			</div>
			<a href="/manage/list.php">一覧・管理画面へ</a>
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

	$sql = "select pic_name,time_stamp from pictures where id = (select max(id) from pictures)";
	$stmt = $dbh->prepare($sql);
	$stmt->execute();
	// $pic_name .= ".jpg";
	$rec = $stmt->fetch(PDO::FETCH_ASSOC);

	if($rec == null){
		$taken_time = "デフォルト";
		$pic_name = 'default.png';
	}else{
		$taken_time = $rec['time_stamp'];
		$pic_name = $rec['pic_name'] . ".jpg";

		if(file_exists("../pictures/" . $pic_name)==false){
			 array_map('unlink', glob("../pictures/*.jpg"));
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

	}
} catch (PDOException $e) {
	echo "データベースとの接続でエラーが発生：" . $e->getMessage() . "<br/>";
}

$file_path = $IMAGE_DIR . $pic_name;
//$file_path = "../pictures/default.png";

$output_html = str_replace( "<!--PAGE_TITLE-->", $PAGE_TITLE, $HTML_BASE);
$output_html = str_replace("<!--TAKEN_TIME-->", $taken_time,$output_html);
$output_html = str_replace( "<!--RECENT_PICTURE_PATH-->", $file_path, $output_html);

print $output_html;
