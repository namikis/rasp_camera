<?php
date_default_timezone_set('Asia/Tokyo');
require 'vendor/autoload.php';

$s3Client = new Aws\S3\S3Client([
        'version' => 'latest',
        'region'  => 'ap-northeast-1',
        'credentials' => [
            'key'    => 'minio',
            'secret' => 'minio123',
        ],
        'endpoint' => 'http://localhost:9000',        // ← 追加 (docker内からアクセス)
        // 'endpoint' => 'http://127.0.0.1:9090', // ← 追加 (ホストからアクセス)
        'use_path_style_endpoint' => true,        // ← 追加
]);

$image = fopen('pictures/cam_icon.png', 'rb');

$result = $s3Client->putObject([
    'ACL' => 'public-read',
    'Bucket' => 'static',
    'Key' => 'cam_icon.png',
    'Body' => $image
]);
