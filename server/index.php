<?php 

$raw_post_data = file_get_contents('php://input');

if (!empty($raw_post_data)) {
    // 保存数据库
    $config = require __DIR__ . '/local_db.php';

    $dsn = "mysql:host={$config['host']};dbname={$config['database']};";
    $_db = new PDO(
        $dsn,
        $config['username'],
        $config['pwd']
    );
    $_db->setAttribute(PDO::ATTR_EMULATE_PREPARES, FALSE);
    $_db->setAttribute(
        PDO::ATTR_ERRMODE,
        PDO::ERRMODE_EXCEPTION
    );
    $_db->exec('SET NAMES utf8');
    $sql = "
    INSERT INTO `FlagHead` ( `wx` ) VALUES ('{$raw_post_data}');";
    $_db->exec($sql);

    // 解析头像
    $info_arr = json_decode($raw_post_data,1);
    $pic = str_replace("/132","/0",$info_arr['avatarUrl']);

    $moban = './moban.png';

    // 头像图片信息
    $pic_img=imagecreatefromjpeg($pic);
    $pic_width=imagesx($pic_img);
    $pic_height=imagesy($pic_img);

    // 缩放为水印信息
    $tmp_moban = "./tmp";
    if(!file_exists($tmp_moban)) {
        mkdir($tmp_moban,0777);
    }

    $moban_file = $moban;
    $imgarr = getimagesize($moban_file);
    $max_img_x = $imgarr[0];
    $max_img_y = $imgarr[1];
    $max_type_num = $imgarr[2];
    $max_type = $imgarr['mime'];

    //大图的资源
    $max_img = imagecreatefrompng($moban_file);
    //等比例缩放算法
    $min_img_x=$pic_width;
    $min_img_y=$pic_height;
    if(($min_img_x / $max_img_x) > ($min_img_y / $max_img_y)){
        $bili=$min_img_y/$max_img_y;
    }else{
        $bili=$min_img_x/$max_img_x;
    }
    $min_img_x=floor($max_img_x*$bili);
    $min_img_y=floor($max_img_y*$bili);
    //小图的资源
    $min_img = imagecreatetruecolor($min_img_x, $min_img_y);
    $zhibg = imagecolorallocatealpha($min_img, 200, 200, 200, 0);
    imagefill($min_img,0,0,$zhibg);
    imagecolortransparent($min_img,$zhibg);

    //把大图缩放成小图
    imagecopyresampled($min_img, $max_img, 0, 0, 0, 0, $min_img_x, $min_img_y, $max_img_x, $max_img_y);
    //判断类型
    switch ($max_type_num) {
        case 1:
            $imgout="imagegif";
            break;
        
        case 2:
            $imgout="imagejpeg";
            break;

        case 3:
            $imgout="imagepng";
            break;  
    }
    $minfilename = "{$tmp_moban}/{$moban_file}";
    $imgout($min_img,$minfilename);
    //释放图片资源
    imagedestroy($max_img);
    imagedestroy($min_img);

    // 模板图像信息
    $moban_img=imagecreatefrompng($minfilename);
    $moban_width=imagesx($moban_img);
    $moban_height=imagesy($moban_img);

    imagecopy($pic_img,$moban_img,$pic_width-$moban_width,$pic_height-$moban_height,0,0,$moban_width,$moban_height);

    imagejpeg($pic_img,'./tmp/pic.jpeg');

    $filename = '/tmp/pic.jpeg';

    $ret = [
        'pic' => "{$_SERVER['REQUEST_SCHEME']}://{$_SERVER['SERVER_NAME']}{$filename}",
    ];

    echo json_encode($ret);

} else if (!empty($_FILES['pic']['tmp_name'])) {
        
    $pic = $_FILES['pic']['tmp_name'];
    $moban = './moban.png';

    // 头像图片信息
    $pic_img=imagecreatefromjpeg($pic);
    $pic_width=imagesx($pic_img);
    $pic_height=imagesy($pic_img);

    // 缩放为水印信息
    $tmp_moban = "./tmp";
    if(!file_exists($tmp_moban)) {
        mkdir($tmp_moban,0777);
    }

    $moban_file = $moban;
    $imgarr = getimagesize($moban_file);
    $max_img_x = $imgarr[0];
    $max_img_y = $imgarr[1];
    $max_type_num = $imgarr[2];
    $max_type = $imgarr['mime'];

    //大图的资源
    $max_img = imagecreatefrompng($moban_file);
    //等比例缩放算法
    $min_img_x=$pic_width;
    $min_img_y=$pic_height;
    if(($min_img_x / $max_img_x) > ($min_img_y / $max_img_y)){
        $bili=$min_img_y/$max_img_y;
    }else{
        $bili=$min_img_x/$max_img_x;
    }
    $min_img_x=floor($max_img_x*$bili);
    $min_img_y=floor($max_img_y*$bili);
    //小图的资源
    $min_img = imagecreatetruecolor($min_img_x, $min_img_y);
    $zhibg = imagecolorallocatealpha($min_img, 200, 200, 200, 0);
    imagefill($min_img,0,0,$zhibg);
    imagecolortransparent($min_img,$zhibg);

    //把大图缩放成小图
    imagecopyresampled($min_img, $max_img, 0, 0, 0, 0, $min_img_x, $min_img_y, $max_img_x, $max_img_y);
    //判断类型
    switch ($max_type_num) {
        case 1:
            $imgout="imagegif";
            break;
        
        case 2:
            $imgout="imagejpeg";
            break;

        case 3:
            $imgout="imagepng";
            break;  
    }
    $minfilename = "{$tmp_moban}/{$moban_file}";
    $imgout($min_img,$minfilename);
    //释放图片资源
    imagedestroy($max_img);
    imagedestroy($min_img);

    // 模板图像信息
    $moban_img=imagecreatefrompng($minfilename);
    $moban_width=imagesx($moban_img);
    $moban_height=imagesy($moban_img);

    imagecopy($pic_img,$moban_img,$pic_width-$moban_width,$pic_height-$moban_height,0,0,$moban_width,$moban_height);

    // header("content-type:image/jpeg");
    // imagejpeg($pic_img);

    imagejpeg($pic_img,'./tmp/pic.png');
    $filename = './tmp/pic.png';
    echo '<img src="./tmp/pic.png" alt="pic" title="pic">';

    // header("content-disposition:attachment;filename=".basename($filename));
    // header("content-length:".filesize($filename));
    // readfile($filename);

} else {
    $html = <<<ESO
<!DOCTYPE html>
<html>
<head>
    <title>头像国旗</title>
    <meta charset="utf-8">
</head>
<body>
    <div>
        <form action="" method="post" enctype="multipart/form-data">
            <input type="file" name="pic" id="pic" required placeholder="头像">
            <input type="submit" value="提交">
        </form>
    </div>
</body>
</html>
ESO;

echo $html;
}