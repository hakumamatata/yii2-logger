<?php

namespace hakumamatata\yii2logger;

use Yii;
use yii\helpers\Json;
use yii\web\ServerErrorHttpException;
use yii\log\Logger;
use yii\web\Request;

/**
 * This is the model class for table "login_normal_log".
 *
 * @property string $id 流水號主鍵
 * @property int $company_id 公司ID
 * @property int $building_id 建案/社區 ID
 * @property int $user_id 使用者ID
 * @property string $username 使用者名稱
 * @property string $code 軟體代碼
 * @property string $controller 請求 - 控制器名稱
 * @property string $action 請求 - 控制器中動作名稱
 * @property string $created_at 紀錄時間
 * @property string $category 紀錄分類
 * @property int $level 紀錄階層
 * @property string $prefix 記錄前綴
 * @property string $message 紀錄信息
 */
class NormalLog extends \yii\db\ActiveRecord
{
    /**
     * @var bool 是否紀錄LOG 開關
     */
    public $isLog = true;
    /**
     * @var bool 是否記錄異常LOG 開關
     */
    public $isErrorLog = true;
    /**
     * @var array 排除的actions
     */
    public $exceptActs = [];
    /**
     * @var string 平台系統名稱
     */
    public $applicationSystemName = '';
    /**
     * @var string 多國語系 名稱
     */
    public $applicationI18nName = '';
    /**
     * @var string 操作紀錄 分類名稱
     */
    public $logCategoryAction = 'act_log';
    /**
     * @var string 異常紀錄 分類名稱
     */
    public $logCategoryError = 'error_log';
    /**
     * @var string 專案中 DB設置的名稱 (組件配置時用)
     */
    public $dbAliasName = '';
    /**
     * @var string 專案中 DB設置的名稱 (Yii執行時需要)
     */
    public static $dbName = '';
    /**
     * @var string
     */
    public $exceptionErrorMsg = '';
    /**
     * 紀錄LOG時 參數預設值範本 (主要紀錄Yii初始配置時，無法配置以及動態的值)
     * @var array
     */
    protected $logParamsTemplate = [
        'company_id' => 0,
        'building_id' => 0,
        'user_id' => 0,
        'username' => '',
        'exceptionErrorMsg' => '',
    ];

    /**
     * NormalLog constructor.
     * @param array $config
     */
    public function __construct($config = [])
    {
        parent::__construct($config);

        # 需要 $this->dbName 故要在parent::__construct後面
        self::$dbName = $this->dbAliasName;
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get(self::$dbName);
    }

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'login_normal_log';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['company_id', 'building_id', 'user_id', 'level'], 'integer'],
            [['created_at'], 'safe'],
            [['message'], 'string'],
            [['username'], 'string', 'max' => 255],
            [['code', 'controller', 'action'], 'string', 'max' => 50],
            [['category'], 'string', 'max' => 25],
            [['prefix'], 'string', 'max' => 100],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        if ($this->applicationI18nName) {
            return [
                'id' => Yii::t($this->applicationI18nName, 'ID'),
                'company_id' => Yii::t($this->applicationI18nName, 'Company ID'),
                'building_id' => Yii::t($this->applicationI18nName, 'Project ID'),
                'user_id' => Yii::t($this->applicationI18nName, 'User Id'),
                'username' => Yii::t($this->applicationI18nName, 'User Name'),
                'code' => Yii::t($this->applicationI18nName, 'Application System Name'),
                'controller' => Yii::t($this->applicationI18nName, 'Request Controller'),
                'action' => Yii::t($this->applicationI18nName, 'Request Action'),
                'created_at' => Yii::t($this->applicationI18nName, 'Created At'),
                'category' => Yii::t($this->applicationI18nName, 'Log Category'),
                'level' => Yii::t($this->applicationI18nName, 'Log Level'),
                'prefix' => Yii::t($this->applicationI18nName, 'Log Prefix'),
                'message' => Yii::t($this->applicationI18nName, 'Log Message'),
            ];
        } else {
            return [
                'id' => 'ID',
                'company_id' => 'Company ID',
                'building_id' => 'Project ID',
                'user_id' => 'User Id',
                'username' => 'User Name',
                'code' => 'Application System Name',
                'controller' => 'Request Controller',
                'action' => 'Request Action',
                'created_at' => 'Created At',
                'category' => 'Log Category',
                'level' => 'Log Level',
                'prefix' => 'Log Prefix',
                'message' => 'Log Message',
            ];
        }
    }

    /**
     * {@inheritdoc}
     * @return NormalLogQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new NormalLogQuery(get_called_class());
    }

    /**
     * 紀錄 controller和actions 的相關資訊
     * @param array $logParams
     * @throws ServerErrorHttpException
     */
    public function recordAct($logParams = [])
    {
        if ($this->isLog) {
            $apiRoute = Yii::$app->controller->action->uniqueId;
            if ($apiRoute && !in_array($apiRoute, $this->exceptActs)) {

                $logMsg = [
                    '$_GET' => Yii::$app->request->get(),
                    '$_POST' => Yii::$app->request->post(),
                    '$_FILES' => (isset($_FILES) && is_array($_FILES)) ? $_FILES : '',
                ];

                $this->saveLog($this->logCategoryAction, Json::encode($logMsg), $logParams);
            }
        }
    }

    /**
     * 通用版本 err log
     * @param $msg
     * @param array $logParams
     * @param mixed $exception
     * @throws ServerErrorHttpException
     */
    public function recordErrorLog($msg, $logParams = [], $exception = null)
    {
        if ($this->isErrorLog) {
            $errMsg = [
                '$_GET' => Yii::$app->request->get(),
                '$_POST' => Yii::$app->request->post(),
                '$_FILES' => (isset($_FILES) && is_array($_FILES)) ? $_FILES : '',
            ];

            if ($msg && is_string($msg)) {
                $errMsg['msg'] = $msg;
            }

            if ($exception && is_object($exception)) {
                $errMsg['exception'] = [
                    'message' => method_exists($exception, 'getMessage') ? $exception->getMessage() : '',
                    'file' => method_exists($exception, 'getFile') ? $exception->getFile() : '',
                    'line' => method_exists($exception, 'getLine') ? $exception->getLine() : '',
                ];
            }

            $this->saveLog($this->logCategoryError, Json::encode($errMsg), $logParams);
        }
    }

    /**
     * 儲存LOG 資料
     * @param string $category
     * @param string $msg
     * @param array $logParams
     * @throws ServerErrorHttpException
     */
    protected function saveLog($category, $msg, $logParams = [])
    {
        # 參數處理
        $params = $this->logParamsTemplate;
        if ($logParams && $params) {
            foreach ($params as $key => $param) {
                if (isset($logParams[$key])) {
                    $params[$key] = $logParams[$key];
                }
            }
        }

        /* @var $log \hakumamatata\yii2logger\NormalLog */
        $log = clone $this;

        # 可設定的參數
        $log->company_id = $params['company_id'];
        $log->building_id = $params['building_id'];
        $log->user_id = $params['user_id'];
        $log->username = $params['username'];
        $this->exceptionErrorMsg = $params['exceptionErrorMsg'];

        # 已設定 或是 YII自動可抓取參數
        $log->controller = Yii::$app->controller->id ?: '';
        $log->action = Yii::$app->controller->action->id ?: '';
        $log->code = $log->applicationSystemName;
        $log->created_at = (new \DateTime('now'))->format('Y-m-d H:i:s');
        $log->category = $category;
        switch ($category) {
            case $this->logCategoryError:
                $log->level = Logger::LEVEL_ERROR;
                break;
            case $this->logCategoryAction:
            default:
                $log->level = Logger::LEVEL_INFO;
                break;
        }
        $log->prefix = $this->getMessagePrefix();
        $log->message = $msg;

        if (!$log->save()) {
            throw new ServerErrorHttpException($this->exceptionErrorMsg ?: Json::encode($log->getErrors()));
        }
    }

    /**
     * 取得LOG Prefix數值
     * 從 \yii\log\Target 中改寫
     * @return string
     */
    protected function getMessagePrefix()
    {
        if (Yii::$app === null) {
            return '';
        }

        $request = Yii::$app->getRequest();
        $ip = $request instanceof Request ? $request->getUserIP() : '-';

        /* @var $user \yii\web\User */
        $user = Yii::$app->has('user', true) ? Yii::$app->get('user') : null;
        if ($user && ($identity = $user->getIdentity(false))) {
            $userID = $identity->getId();
        } else {
            $userID = '-';
        }

        /* @var $session \yii\web\Session */
        $session = Yii::$app->has('session', true) ? Yii::$app->get('session') : null;
        $sessionID = $session && $session->getIsActive() ? $session->getId() : '-';

        return "[$ip][$userID][$sessionID]";
    }

}
