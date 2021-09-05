<?php

ini_set("display_errors", 'On');
error_reporting(E_ALL);

require '/home/ec2-user/vendor/autoload.php';
use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;

$IMAGE_DIR = "pictures/";
$PAGE_TITLE = "静止画取得システム・閲覧画面";

$HTML_BASE =<<<EOT
<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<title><!--PAGE_TITLE--></title>
	<style>
		html,body,div,h1{
			margin:0;
			padding:0;
		}
		header{
			background:lightblue;
			color:white;
			padding:20px;
		}
		.pic_wrapper,.button_wrapper{
			text-align:center;
		}
		.pic_wrapper{
			padding-top:50px;
		}
		.pic_wrapper img{
			width:500px;
			height:400px;
		}
		.whole_wrapper{
			padding-bottom:50px ;
			width:50%;
			margin:0 auto;
		}
		.button_wrapper,.to_manage{
			margin-top:20px;
		}

		#btn{
			cursor:pointer;
			font-size:50px;
			background:lightblue;
			border-radius:20px;
			padding:10px 20px;
		}
		#btn:hover{
			opacity:0.9;
		}
	</style
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
			<div class="to_manage">
				<a href="manage/index_m.php">撮影画面へ</a>
			</div>
		</div>
</body>
</html>
EOT;

$s3 = new S3Client([
	'version' => 'latest',
	'region' => 'ap-northeast-1'
]);

$ini_file = parse_ini_file("app.ini",true);
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

		if(file_exists("pictures/" . $pic_name)==false){
			 array_map('unlink', glob("pictures/*.jpg"));
			 try {
				$result = $s3->getObject([
					'Bucket' => 'rasp-camera',
					'Key' => 'pictures/' . $pic_name,
					'SaveAs' => 'pictures/'.$pic_name,
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
