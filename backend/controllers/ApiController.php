<?php
namespace app\controllers;

use yii\web\Controller;
use app\models\Users;
use app\models\Articles;
use app\models\Articlecomments;
use app\models\Admins;
use app\models\Personalinfo;
use app\models\Articlelikes;


class ApiController extends Controller
{   
    //用于登录的api
    public function actionLogin()
    {
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
    
        $username = \Yii::$app->request->get('username');
        $password = \Yii::$app->request->get('password');
    
        if ($username !== null && $password !== null) {
            // 查询数据库检查用户名和密码是否匹配
            $user = Users::find()
                ->where(['Username' => $username])
                ->one();
    
            if ($user !== null && ($password == $user->Password)) {
                // 用户名和密码匹配
                return ['status' => 1];
            } else {
                // 用户名和密码不匹配
                return ['status' => 0];
            }
        }
    }

    //用于注册的api
    public function actionSignup()
    {
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $username = \Yii::$app->request->get('username');
        $password = \Yii::$app->request->get('password');

        // 查询数据库中最大的 UserID
        $maxUserID = Users::find()
            ->select('MAX(UserID)')
            ->scalar(); // 获取最大的 UserID 值

        $newUserID = $maxUserID + 1;

        $existingUser = Users::find()
            ->where(['Username' => $username])
            ->one();

        if ($existingUser !== null) {
            return ['status' => 0, 'message' => '用户已存在'];
        } else {
            $user = new Users();
            $user->UserID = $newUserID; // 设置新用户的 UserID
            $user->Username = $username;
            // 在存储密码之前，应该对密码进行哈希处理,但是暂时忽略
            $user->Password = $password;

            if ($user->save()) {
                return ['status' => 1, 'message' => '注册成功'];
            } else {
                return ['status' => -1, 'message' => '保存失败'];
            }
        }
    }

    //用于判断管理员登录的api
    public function actionAdminlogin()
    {
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
    
        $username = \Yii::$app->request->get('username');
        $password = \Yii::$app->request->get('password');
    
        if ($username !== null && $password !== null) {
            // 查询数据库检查用户名和密码是否匹配
            $user = Users::find()
                ->where(['Username' => $username])
                ->one();
    
            if ($user !== null && ($password == $user->Password)) {
                // 用户名和密码匹配
                // 检查用户是否为管理员
                $admin = Admins::find()
                    ->where(['Username' => $user->Username])
                    ->one();
                if($admin !== null){
                    return ['status' => 1];
                }else{
                    return ['status' => 0];
                }
            } else {
                // 用户名和密码不匹配
                return ['status' => 0];
            }
        }
    }

    //用于文章页面获取文章的api
    public function actionGetarticle()
    {
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        // 获取页数
        $page = \Yii::$app->request->get('page');
        $intpage = (int)$page;
        $id = \Yii::$app->request->get('id');
        if ($id !== null) {
            $articles = Articles::find()->select(['ArticleID', 'Title', 'Content', 'PublicationDate','LikesCount'])->where(['ArticleID' => $id])->one();
        }
        else{
            // 查询数据库获取对应页数的文章信息
            $articles = Articles::find()->select(['ArticleID', 'Title', 'Content', 'PublicationDate','LikesCount'])->offset(15*($intpage-1))->limit(15)->all();
        }
        // 格式化为 JSON 并返回
        return $articles;
    }


    //用于文章详情页获取文章评论的api
    public function actionGetarticlecomment()
    {
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $vid = \Yii::$app->request->get('vid');
        $username = \Yii::$app->request->get('username');
        
        if ($username !== null) {
            // 如果有 username 参数，则查询指定 Username 的评论信息
            $comments = Articlecomments::find()
                ->select(['CommentID', 'ArticleID', 'Comment', 'CommentDate', 'Username'])
                ->where(['Username' => $username])
                ->all();
        } elseif ($vid !== null) {
            // 如果有 vid 参数，则查询指定 ArticleID 的评论信息
            $comments = Articlecomments::find()
                ->select(['CommentID', 'ArticleID', 'Comment', 'CommentDate', 'Username'])
                ->where(['ArticleID' => $vid])
                ->all();
        } else {
            // 否则按照原来的逻辑查询分页数据
            $comments = Articlecomments::find()
                ->select(['CommentID', 'ArticleID', 'Comment', 'CommentDate', 'Username'])
                ->all();
        }

        // 格式化为 JSON 并返回
        return $comments;
    }

    //在文章详情页添加评论的api
    public function actionAddarticlecomment()
    {
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $username = \Yii::$app->request->get('username');
        $comment = \Yii::$app->request->get('comment');
        $articleID = \Yii::$app->request->get('articleID');

        $comments = new Articlecomments();
        $comments->Username = $username;
        $comments->Comment = $comment;
        $comments->ArticleID = $articleID;

        if ($comments->save()) {
            return ['status' => 1, 'message' => '发布评论成功'];
        } else {
            return ['status' => -1, 'message' => '发布评论失败'];
        }
    }

    //关于页面获取小组成员信息的api
    public function actionGetpersonalinfo()
    {
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $name = \Yii::$app->request->get('name');
        if ($name !== null) {
        // 查询数据库获取个人信息信息
        $personalinfo = Personalinfo::find()->select(['Name', 'Info', 'AvatarURL', 'Email', 'GitHubAccount', 'WeChatID'])->where(['Name' => $name])->one();
        }
        else{
            $personalinfo = Personalinfo::find()->select(['Name', 'Info', 'AvatarURL', 'Email', 'GitHubAccount', 'WeChatID'])->all();
        }
        // 格式化为 JSON 并返回
        return $personalinfo;
    }

    //用于文章详情页获取文章点赞数的api
    public function actionGetarticlelikes()
    {
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $articleID = \Yii::$app->request->get('articleID');
        
        $likes = Articlelikes::find()
            ->where(['ArticleID' => $articleID])
            ->one();
        
        if($likes == null){
            $likes = new Articlelikes();
            $likes->ArticleID = $articleID;
            $likes->Likes = 0;
            $likes->save();
        }

        $likesnum = $likes->Likes;
        $likesnum=json_encode($likesnum);
        // 格式化为 JSON 并返回
        return $likesnum;
    }


    //在文章详情页增加点赞量的api
    public function actionAddarticlelikes()
    {
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $articleID = \Yii::$app->request->get('articleID');
        $num = \Yii::$app->request->get('num');
        
        $likes = Articlelikes::find()
            ->where(['ArticleID' => $articleID])
            ->one();
        
        if($likes == null){
            $likes = new Articlelikes();
            $likes->ArticleID = $articleID;
            $likes->Likes = 0;
            $likes->save();
        }

        $likes->Likes = $likes->Likes + $num;
        if ($likes->save()) {
            return ['status' => 1, 'message' => '点赞量增加成功'];
        } else {
            return ['status' => -1, 'message' => '点赞量增加失败'];
        }
    }
}
