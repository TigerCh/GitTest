<?php

namespace Supporter\Controller;

use Think\Controller;

class ArticleController extends Controller {
    protected $num=5;
    //添加文章操作方法
    public function add() {
        //show_bug($arr);
        $a = cookie("spower");
        if (isset($_COOKIE['author']) && $a['spower']) {
            $user = $_COOKIE['author'];
            $c = D('Article');
            //提前占个位置
            //将用户表的字段name转换为author
            $temp = cookie('author');
            $arr['author'] = $temp;
            $arr['content'] = "temp";
            echo 'arr' . show_bug($arr);
            //将作者名以及一个空内容添加进数据库，并获取当前将要添加文章的文章id
            $c->add($arr);
            $temp = "temp";
            $res = $c->where("content='temp'AND author='$user'")->field('AID')->find();
            show_bug($aid);
            $arr['aid'] = $res['aid'];
            show_bug($arr);
            //获取文章分类列表
            $class = M('class');
            $a = $class->select();
            $this->assign('a', $a);
            $this->assign('arr', $arr)->display();
        } else {
            //没有登陆就进行操作则无法运行
            echo '需登陆后进行次操作，如果您已登陆那么说明你没有进行文章添加的权限哈哈哈';
            exit();
        }
    }

    //上传文章附件  
    public function upload() {
        $bucketName = 'demobucket';
        $operatorName = 'operator';
        $operatorPwd = 'operatorpassword';
        //被上传的文件路径
        $filePath = $_POST['filePath'];
        $fileSize = filesize($filePath);
        //文件上传到服务器的服务端路径
        $serverPath = 'foo.txt';
        $uri = "/$bucketName/$serverPath";
        //生成签名时间。得到的日期格式如：Thu, 11 Jul 2014 05:34:12 GMT
        $date = gmdate('D, d M Y H:i:s \G\M\T');
        $sign = md5("PUT&{$uri}&{$date}&{$fileSize}&" . md5($operatorPwd));

        $ch = curl_init('http://v0.api.upyun.com' . $uri);

        $headers = array(
            "Expect:",
            "Date: " . $date, // header 中需要使用生成签名的时间
            "Authorization: UpYun $operatorName:" . $sign
        );
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_PUT, true);

        $fh = fopen($filePath, 'rb');
        curl_setopt($ch, CURLOPT_INFILE, $fh);
        curl_setopt($ch, CURLOPT_INFILESIZE, $fileSize);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $result = curl_exec($ch);
        if (curl_getinfo($ch, CURLINFO_HTTP_CODE) === 200) {
            //"上传成功"
        } else {
            $errorMessage = sprintf("UPYUN API ERROR:%s", $result);
            echo $errorMessage;
        }
        curl_close($ch);
    }

//放弃添加文章
    public function quit() {
        $quit = D('Article');
        $quit->where("content='temp'")->delete();
        $this->display();
    }

    public function test() {
        $c = D('Article');
        $res = $c->where("author='tiger'")->limit('5,10')->select();
        $this->assign("res", $res)->display();
    }

    //修改文章内容
    public function update() {
        $type = $_POST['type'];
        if ($type == '修改' || $type == '添加') {
            $update = D('Article');
            $aid = $_POST['aid'];
            $arr['title'] = $_POST['title'];
            $arr['author'] = $_POST['author'];
            $arr['class'] = $_POST['class'];
            $arr['content'] = $_POST['content'];
            $arr['utime'] = date('Y-m-d');
            show_bug($arr);
            //修改符合AID的那一条数据
            $a = $update->where("AID='$aid'")->save($arr);
            //修改成功则返回文章管理首页面，修改失败则显示失败提示并跳转到文章首页面
            if ($a) {
                $this->success("修改成功", __CONTROLLER__);
            } else {
                $this->error("修改失败", __CONTROLLER__);
            }
        } elseif ($type == '保存') {
            $update = D('Article');
            $aid = $_POST['aid'];
            $arr['title'] = $_POST['title'];
            $arr['author'] = $_POST['author'];
            $arr['class'] = $_POST['class'];
            $arr['content'] = $_POST['content'];
            $arr['utime'] = date('Y-m-d');
            $a = $update->where("AID='$aid'")->save($arr);
            if ($a) {
                $msg = "保存成功";
                $res = $update->where("AID='$aid'")->find();
                $this->assign("msg", $msg);
                $this->assign("res", $res)->display('read');
            } else {
                $msg = "保存失败";
                $res = $update->where("AID='$aid'")->find();
                $this->assign("msg", $msg);
                $this->assign("res", $res)->display('read');
            }
        }
    }

    public function delete($aid) {
        $delete = D('Article');
        $f = $delete->where("AID='$aid'")->delete();
        if ($f) {
            $this->success("删除成功", __CONTROLLER__);
        } else {
            $this->error("删除失败", __CONTROLLER__);
        }
    }

//后台显示文章列表
    public function read($aid) {
        $show = D('article');
        $res = $show->where("AID='$aid'")->find();
        $class = M('class');
        $a = $class->select();
        //传送分类数据
        $this->assign('a', $a);
        $this->assign('res', $res)->display();
    }

    //修改头部html
    public function hupd() {
        $content = $_POST['content'];
        echo "内容" . $content;
        //删除未修改的文件
        unlink(H_DIR);
        //重新创建文件并写入修改后的内容
        $f = fopen(H_DIR, 'w');
        $b = fwrite($f, $content);
        if ($b) {
            $this->success("修改成功", __CONTROLLER__);
        } else {
            $this->error("修改错误", __CONTROLLER__);
        }
    }

//展示文章列表
    public function index() {
        $a = cookie("spower");
        if (isset($_COOKIE['author']) && $a) {
            //获取文章头部html样式文件并输入到文章首页模板      
            $fp = fopen(H_DIR, r);
            $content = fread($fp, filesize(H_DIR));
            fclose($handle);
            //从数据库中获取文章信息，并输入到文章首页模板
            $index = D('article');
            //获取最大页数
            $m = $index->query("select count(*) from rz_article");
            $max = $m[0]['count(*)'];
            $max = ceil($max / $this->num);
            echo $max;
            $res = $index->page($page, $this->num)->select();
            $page = 1;
            $this->assign("page", $page);
            $this->assign('max', $max);
            //html文件内容
            $this->assign("content", $content);
            $this->assign('res', $res)->display();
        } else {
            echo '请登陆后进行次操作';
        }
    }

    //翻页
    public function turn($page) {
        $turn=D('article');
        $m = $turn->query("select count(*) from rz_article");
        $max = $m[0]['count(*)'];
        $max = ceil($max / $this->num);
        $res = $turn->page($page, $this->num)->select();
        $this->assign("max",$max);
        $this->assign("page", $page);
        $this->assign("res", $res)->display("index");
    }

//查询文章
    public function query() {
        $i = 0;
        //如果有表单数据传输过来则进行查询操作，否则显示查询页面
        if (!empty($_POST)) {
            $title = $_POST['title'];
            //获取random数据库的链接
            $conn = D("article");
            if ($conn->connect_error) {
                die("数据库连接错误" . $conn->connect_error);
            }
            //在user数据库中查询用户名含有该字段的用户的信息
            $res = $conn->query("select * from rz_article where title like '%$title%'");
            $fp = fopen(H_DIR, r);
            $content = fread($fp, filesize(H_DIR));
            fclose($handle);
            //html文件内容
            $this->assign("content", $content);
            $this->assign("res", $res)->display('index');
        } else {
            $this->error("错误，即将跳转到文章首页面", __CONTROLLER__);
        }
    }

}
?>

