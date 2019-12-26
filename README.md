hakumamatata/yii2logger
=======================
LOG紀錄(Table) Yii2套件


版本更新
----
v1.0.5.1 :
更新 - 將recordAct()、recordErrorLog()兩個方法做try...catch 包裝(有開關可以設定)，增加系統穩定性，並且有錯誤紀錄。

v1.0.5 :
更新 - 抓取 message欄位的$_GET、$_POST資訊時，增加過濾長度的功能 (避免像base64...等太長的字串)。

安裝
----
這個套件請使用 [composer](http://getcomposer.org/download/) 安裝

執行

```
php composer.phar require hakumamatata/yii2logger "*"
```

或增加

```
"hakumamatata/yii2logger": "*"
```

到你的 `composer.json` 檔案中


環境確認
----
#### 注意運行主機是否已建置Log資料表。

DB: login

Table: login_normal_log


使用
----
eztechtw\yii2logger\NormalLog 為繼承 \yii\db\ActiveRecord 的物件，

建議使用Yii2配置組件的方式，於專案中的設定檔，
如下所示(參數說明如註解):
```
...
'components' => [   # Yii2組件項目內
    ...
    # DB 連線設定 到login
    'dblogin' => [ 
        'class' => 'yii\db\Connection',
        'dsn' => 'mysql:host='.getenv('DB_HOST').';dbname=login',
        'username' => getenv('DB_USERNAME'),
        'password' => getenv('DB_PASSWORD'),
        'charset' => 'utf8',
    ],
    # 自行設定想要的名稱
    'Logger' => [
        'class' => 'eztechtw\yii2logger\NormalLog',
        # 下列兩個一定要設定 
        # 系統名稱
        'applicationSystemName' => 'poc', 
        # yii2配置 DB login的名稱
        'dbAliasName' => 'dblogin',
        
        # 以下為選配
        # 如有使用yii i18n 翻譯的名稱，例如 Yii::t('POC', 'ID')
        'applicationI18nName' => 'POC',
        # 是否有要排除紀錄的路由 controller/action 
        'exceptActs' => ['site/message', 'site/notification'],
        # 操作紀錄 分類名稱，預設值為'act_log'，建議各系統統一
        'logCategoryAction' => 'act_log',
        # 錯誤紀錄 分類名稱，預設值為'error_log'，建議各系統統一
        'logCategoryError' => 'error_log',
        # 單一登入 yii2配置 單一登入的名稱
        'simplesamlphpAliasName' => 'simplesamlphp'
        # 登入系統紀錄 分類名稱，預設值為'login_log'，建議各系統統一
        'logCategoryLogin' => 'login_log',
        # 是否紀錄 操作紀錄 的開關，預設值為true
        'isLog' => true,
        # 是否紀錄 操作紀錄 的開關，預設值為true
        'isErrorLog' => true,
        // 是否使用session檢查 登入系統LOG 的開關，預設值為true
        'isSessionCheck' => true,
        // (過濾)可接收最長長度，預設值為1000
        'filterLimitLength' => 1000,
        // 最高過濾階層，預設值為3     
        'filterMaxResortLevel' => 3, 
        // 是否丟出異常或錯誤的開關，預設值為false
        'isThrowException' => false,
    ],
    ...
]
...
```

紀錄LOG(參數說明如以下註解，皆為選填):

#### 注意，recordAct()、recordErrorLog()兩個方法$NormalLog->save()失敗時皆會拋出ServerErrorHttpException!
```
# 關於 controller 和 action 參數，套件會自動抓取(透過Yii2)，不需額外設定。

# 操作紀錄：  recordAct($logParams = [])
try {
    Yii::$app->Logger->recordAct([
        # 公司ID
        'company_id' => $this->account->company_id,
        # 建案/社區 ID
        'building_id' => $project_id,
        # 使用者ID
        'user_id' => $this->account->user_id,
        # 使用者名稱
        'username' => $this->account->username,
        # 自訂拋出異常信息
        'exceptionErrorMsg' => 'LOG失敗',
    ]);
} catch (ServerErrorHttpException $e) {
    // error handler
}

# 異常紀錄： recordErrorLog($msg, $logParams = [], $exception = null)
# 第三個參數(非必填) $catchErrorException 為捕獲的異常對象，預設值為 null 
try {
    Yii::$app->Logger->recordErrorLog('異常自訂信息',[
        # 公司ID
        'company_id' => $this->account->company_id,
        # 建案/社區 ID
        'building_id' => $project_id,
        # 使用者ID
        'user_id' => $this->account->user_id,
        # 使用者名稱
        'username' => $this->account->username,
        # 自訂拋出異常信息
        'exceptionErrorMsg' => 'LOG失敗',
    ] , $catchErrorException);
} catch (ServerErrorHttpException $e) {
    // error handler
}
```

#### 操作紀錄 recordAct()，建議可建置於各系統相關的BaseController中， 以便紀錄每個請求的資訊。

其他
----
非使用yii2配置方法時，可直接單獨使用
```
use eztechtw\yii2logger\NormalLog;

$log = new NormalLog([
    'applicationSystemName' => 'poc',
    'applicationI18nName' => 'POC',
    'dbAliasName' => 'dblogin',
    'exceptActs' => ['site/message', 'site/notification'],
]);

try {
    $log->recordAct([
        'company_id' => $this->account->company_id,
        'user_id' => $this->account->user_id,
        'username' => $this->account->username,
    ]);
} catch (ServerErrorHttpException $e) {
    // error handler..
}
```

如有使用多語系(i18n)時，可以遵照以下的KEY值設定翻譯:
```
# Yii::t($this->applicationI18nName, 'ID')

'id' => 'ID'
'company_id' => 'Company ID'
'building_id' => 'Project ID'
'user_id' => 'User Id'
'username' => 'User Name'
'code' => 'Application System Name'
'controller' => 'Request Controller'
'action' => 'Request Action'
'created_at' => 'Created At'
'updated_at' => 'Updated At'
'category' => 'Log Category'
'level' => 'Log Level'
'prefix' => 'Log Prefix'
'message' => 'Log Message'
```

備註
----
呼叫recordAct()，組件會自動記錄 系統登入的LOG(會依賴單一登入資訊以及使用session檢查，同一登入只會記錄一次)。
