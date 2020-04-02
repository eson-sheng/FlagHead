<?php
/**
 * Created by PhpStorm.
 * User: eson
 * Date: 2020-03-24
 * Time: 11:17
 */

/**
 * @desc 微信小程序 - 国旗头像 - 应用类
 * Class FlagHead
 */
class FlagHead
{
    /**
     * @desc 入口方法
     */
    public function index ()
    {
        $raw_post_data = file_get_contents('php://input');

        if (!empty($raw_post_data)) {
            return $this->wx($raw_post_data);
        }

        if (!empty($_FILES['pic']['tmp_name'])) {
            return $this->web($_FILES['pic']['tmp_name']);
        }

        return $this->view();
    }

    /**
     * @desc 微信小程序直接发送的用户信息处理
     * @param $data
     * @return bool
     */
    public function wx ($data)
    {
        // 解析头像
        $info_arr = json_decode($data, 1);

        // 验证合法性
        if (!is_array($info_arr)) {
            echo json_encode([
                'message' => '数据不合法！'
            ]);
            return false;
        }

        if (empty($info_arr['avatarUrl'])) {
            echo json_encode([
                'message' => '数据不合法！'
            ]);
            return false;
        }

        // 获取头像地址
        $pic = str_replace("/132", "/0", $info_arr['avatarUrl']);

        // 存储数据库
        $this->save($data);

        // 处理图片返回结果集
        $filename = '/' . $this->deal_with_pic($pic);
        $ret = [
            'pic' => "{$_SERVER['REQUEST_SCHEME']}://{$_SERVER['SERVER_NAME']}{$filename}",
        ];
        echo json_encode($ret);

        return true;
    }

    /**
     * @desc 网页发送头像直接处理
     * @param $data
     * @return bool
     */
    public function web ($data)
    {
        if (empty($_FILES['pic'])) {
            echo json_encode([
                'message' => '数据为空！'
            ]);
            return false;
        }

        if (!in_array($_FILES['pic']['type'], [
            'image/jpeg',
        ])) {
            echo json_encode([
                'message' => '文件不合法！'
            ]);
            return false;
        }

        $filename = $this->deal_with_pic($data);
        echo "<img src='{$filename}' alt='pic' title='pic'>";
        return true;
    }

    /**
     * @desc 表单提交页面
     * @return bool
     */
    public function view ()
    {
        $html = <<<ESO
<!DOCTYPE html>
<html lang="zh">
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
        return true;
    }

    /**
     * @desc 对图片文件处理，加水印国旗
     * @param $pic
     * @return bool|string
     */
    public function deal_with_pic ($pic)
    {
        try {
            $moban = './moban.png';

            // 头像图片信息
            $pic_img = imagecreatefromjpeg($pic);
            $pic_width = imagesx($pic_img);
            $pic_height = imagesy($pic_img);

            // 缩放为水印信息
            $tmp_moban = "./tmp";
            if (!file_exists($tmp_moban)) {
                mkdir($tmp_moban, 0777);
            }

            $moban_file = $moban;
            $imgarr = getimagesize($moban_file);
            $max_img_x = $imgarr[0];
            $max_img_y = $imgarr[1];
            $max_type_num = $imgarr[2];
//        $max_type = $imgarr['mime'];

            //大图的资源
            $max_img = imagecreatefrompng($moban_file);
            //等比例缩放算法
            $min_img_x = $pic_width;
            $min_img_y = $pic_height;
            if (($min_img_x / $max_img_x) > ($min_img_y / $max_img_y)) {
                $bili = $min_img_y / $max_img_y;
            } else {
                $bili = $min_img_x / $max_img_x;
            }
            $min_img_x = floor($max_img_x * $bili);
            $min_img_y = floor($max_img_y * $bili);
            //小图的资源
            $min_img = imagecreatetruecolor($min_img_x, $min_img_y);
            $zhibg = imagecolorallocatealpha($min_img, 200, 200, 200, 0);
            imagefill($min_img, 0, 0, $zhibg);
            imagecolortransparent($min_img, $zhibg);

            //把大图缩放成小图
            imagecopyresampled($min_img, $max_img, 0, 0, 0, 0, $min_img_x, $min_img_y, $max_img_x, $max_img_y);
            //判断类型
            switch ($max_type_num) {
                case 1:
                    $imgout = "imagegif";
                    break;

                case 2:
                    $imgout = "imagejpeg";
                    break;

                case 3:
                    $imgout = "imagepng";
                    break;
            }
            $minfilename = "{$tmp_moban}/{$moban_file}";
            $imgout($min_img, $minfilename);
            //释放图片资源
            imagedestroy($max_img);
            imagedestroy($min_img);

            // 模板图像信息
            $moban_img = imagecreatefrompng($minfilename);
            $moban_width = imagesx($moban_img);
            $moban_height = imagesy($moban_img);

            imagecopy($pic_img, $moban_img, $pic_width - $moban_width, $pic_height - $moban_height, 0, 0, $moban_width, $moban_height);

            imagejpeg($pic_img, './tmp/pic.png');
        } catch (Exception $e) {
            var_dump($e);
            return false;
        }

        return './tmp/pic.png';
    }

    /**
     * @desc 保存微信的信息
     * @param $json
     * @return bool
     */
    public function save ($json)
    {
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
        $sql = "INSERT INTO `FlagHead` ( `wx` ) VALUES ( :wx );";
        $stmt = $_db->prepare($sql);
        try {
            $_db->beginTransaction();
            $stmt->execute(['wx' => $json]);
            $_db->commit();
            return true;
        } catch (\Exception $e) {
            $_db->rollback();
            var_dump($e->getMessage());
            return false;
        }
    }
}

$obj = new FlagHead();
$obj->index();
