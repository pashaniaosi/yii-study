<?php

namespace app\controllers;

use app\controllers\base\BaseController;
use app\models\FeedForm;
use Yii;
use yii\filters\AccessControl;
use yii\web\Response;
use yii\filters\VerbFilter;
use app\models\LoginForm;
use app\models\ContactForm;
use app\models\EntryForm;
use yii\web\UploadedFile;
use app\models\UploadForm;
use app\models\SignupForm;

class SiteController extends BaseController
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'only' => ['logout', 'signup'],
                'rules' => [
                    [
                        'actions' => ['logout',],
                        'allow' => true,
//                        表示登入了才能访问
                        'roles' => ['@'],
                    ],
                    [
                        'actions' => ['signup'],
                        'allow' => true,
//                        表示没登入才能访问
                        'roles' => ['?'],
                    ]
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
        ];
    }

    /**
     * Displays homepage.
     *
     * @return string
     */
    public function actionIndex()
    {
        return $this->render('index');
    }

    /**
     * @return string|Response
     * @throws \Exception
     */
    public function actionSignup()
    {
        $model = new SignupForm();
        if ($model->load(Yii::$app->request->post())) {
            if ($user = $model->signup()) {
//                如果用户创建成功，就登入该用户
                if (Yii::$app->getUser()->login($user)) {
//                    登入成功，就返回首页
                    return $this->goHome();
                } else {
                    throw new \Exception('用户登入失败, 请重试!');
                }
            }
        }

        return $this->render('signup', [
            'model' => $model,
        ]);
    }

    /**
     * Login action.
     *
     * @return Response|string
     */
    public function actionLogin()
    {
        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            return $this->goBack();
        }
        return $this->render('login', [
            'model' => $model,
        ]);
    }

    /**
     * Logout action.
     *
     * @return Response
     */
    public function actionLogout()
    {
        Yii::$app->user->logout();

        return $this->goHome();
    }

    /**
     * Displays contact page.
     *
     * @return Response|string
     */
    public function actionContact()
    {
        $model = new ContactForm();
        if ($model->load(Yii::$app->request->post()) && $model->contact(Yii::$app->params['adminEmail'])) {
            Yii::$app->session->setFlash('contactFormSubmitted');

            return $this->refresh();
        }
        return $this->render('contact', [
            'model' => $model,
        ]);
    }

    /**
     * Displays about page.
     *
     * @return string
     */
    public function actionAbout()
    {
        return $this->render('about');
    }

    public function actionSay($message = 'Hello')
    {
        return $this->render('say', ['message' => $message]);
    }

    public function actionEntry()
    {
        $model = new EntryForm;//用户输入的数据

        if ($model->load(Yii::$app->request->post())&&$model->validate()){
            //验证成功了，就创建一个 entry-confirm 的动作
            return $this->render('entry-confirm', ['model' => $model]);
        } else {
            //验证失败了，就创建一个 entry 的动作
            //$model->getErrors()获取错误详细信息
            return $this->render('entry', ['model' => $model]);
        }
    }

    public function actionUpload()
    {
        $model = new UploadForm();

        if (Yii::$app->request->isPost) {
            $model->imageFiles = UploadedFile::getInstances($model, 'imageFile');
            if ($model->upload()) {
                // file is uploaded successfully
                return;
            }
        }

        return $this->render('upload', ['model' => $model]);
    }

    /**
     * 发布留言
     */
    public function actionAddFeed()
    {
        $model = new FeedForm();
        $model->content = Yii::$app->request->post('content');
        if($model->validate())
        {
            if($model->create()){
                return json_encode(['status' => true]);
            }
        }
//        此处有问题
        return json_decode(['status' => false, 'msg' => '发布失败']);
    }
}
