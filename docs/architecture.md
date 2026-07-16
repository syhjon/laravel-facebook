# 專案架構準則

本專案採用 Combination & Cache 分層架構，並配合 Laravel 13 的 Service Container、Eloquent、Validation 與 Cache 實作。

## 資料流程

```text
HTTP Request
  └─ FormRequest（正規化、驗證、只輸出允許欄位）
      ├─ Checker
      │   └─ Validator（提供規則）
      └─ Controller（HTTP／Session 與 ResponseMaker Contract）
          └─ Container Contract
              └─ Container（由 Contextual Binding 決定實作）
                  ├─ ServiceManager Contract
                  │   └─ ServiceManager
                  │       └─ Service
                  │           ├─ Repository
                  │           │   ├─ CacheManager
                  │           │   └─ Model
                  │           │       └─ Presenter
                  │           └─ Combination
                  ├─ TransactionManager Contract
                  │   └─ DatabaseTransactionManager
                  └─ CombinationManager
                      ├─ Service
                      └─ Combination
```

目前會員系統的實際流程：

```text
AuthController
  ├─ RegisterRequest／LoginRequest
  │   └─ AuthenticationChecker → UserValidator
  └─ AuthenticationContainerInterface
      └─ WebAuthenticationContainer（EntryContextServiceProvider 決定）
          ├─ AuthenticationServiceManagerInterface → AuthenticationServiceManager
          │   ├─ AuthenticationService
          │   └─ UserService → UserRepository → User Model → UserPresenter
          │                         └─ UserCacheManager
          └─ AuthenticationPageCombinationManager
              ├─ UserService
              └─ AuthenticationPageCombination
```

貼文牆流程：

```text
FeedController
  ├─ FeedIndexRequest／StorePostRequest／StoreCommentRequest
  │   └─ PostChecker → PostValidator
  └─ FeedContainerInterface
      └─ WebFeedContainer（EntryContextServiceProvider 決定）
          └─ PostServiceManagerInterface → PostServiceManager
              ├─ PostService → PostRepository → Post／Comment／PostLike Model
              └─ PostCombination
```

貼文列表使用 `PostConstant::FEED_PER_PAGE` 控制每批筆數，並採用依 `posts.id` 倒序的 cursor pagination，供 Vue `IntersectionObserver` 進行無限滾動續載。

## 目錄職責

| 目錄 | 職責 |
| --- | --- |
| `app/Http/Controllers` | 控制 HTTP/session 與回應，只依賴 Container／Response Contract |
| `app/Http/Requests` | 正規化與驗證 HTTP input，提供明確型別的 payload、cursor 與登入者 ID |
| `app/Contracts/Containers` | 定義特定入口可執行的應用操作 |
| `app/Contracts/Responses` | 定義 HTTP 回應製造介面，隔離 Controller 與 JSON 實作 |
| `app/Contracts/ServiceManagers` | 定義入口 Container 可使用的商業流程介面 |
| `app/Contracts/Transactions` | 定義 transaction 執行邊界，隔離應用流程與資料庫實作 |
| `app/Responses` | 實作統一的 message、data、meta、duration 與 datetime 回應格式 |
| `app/Containers` | 作為 Controller 與應用層的邊界，決定入口流程組合並控制寫入 transaction |
| `app/Transactions` | 使用 Laravel database connection 實作 transaction、rollback 與重試 |
| `app/Checkers` | 依使用案例組合一或多個 Validator 規則，透過 FormRequest method injection 解析 |
| `app/Validators` | 提供單一領域資料的 Laravel validation rules |
| `app/ServiceManagers` | 組合多個 Service 的商業流程 |
| `app/Services` | 商業邏輯，只透過 Repository 存取資料 |
| `app/Contracts/Repositories` | Repository 抽象介面 |
| `app/Repositories` | Eloquent 查詢與資料持久化 |
| `app/Models` | 資料表、關聯、型別轉換設定 |
| `app/Presenters` | Model 欄位的輸出格式轉換 |
| `app/Combinations` | 將 Repository／Model 資料整理成可用資訊 |
| `app/CombinationManagers` | 結合多個 Service 與 Combination 的輸出 |
| `app/CacheManagers` | 集中管理 cache key、存取期限與清除策略 |
| `app/Constants` | 集中管理具語意的固定狀態與數值 |
| `app/Supports` | 無領域狀態、可獨立完成的共用轉換邏輯 |
| `app/ExceptionCodes` | 集中管理領域錯誤代碼 |
| `app/Exceptions` | 領域例外與一致的 HTTP 回應格式 |
| `app/Providers` | 依 Repository、Application infrastructure、Entry Context 分開註冊 container bindings |

## 存取限制

1. 不得跨越兩個以上的控制層直接存取。
2. Controller 只能依賴 FormRequest、Container Contract 與 Response Contract，不得直接存取 Checker、CombinationManager、ServiceManager、Service、Repository、Validator 或 Model。
3. Service 不得直接查詢 Model，資料存取必須經過 Repository。
4. 低階層不得反向存取高階層。
5. 同類型類別不得互相呼叫，避免循環依賴。
6. CacheManager、Constant、Support、ExceptionCode 為獨立結構，可被各層使用。
7. 寫入資料的 transaction 由入口 Container 透過 `TransactionManagerInterface` 控制；Controller 與 Container 都不得直接依賴資料庫 facade。
8. FormRequest 透過 Checker 取得 Validator 規則；Container 只接收已驗證資料，並只依賴 ServiceManager contract，不得跳層直接存取具體 ServiceManager、Service、Repository、Validator 或 Model。
9. Blade 與 Vue 只負責呈現；衍生欄位應由 Presenter 或 Combination 先行準備。
10. Controller 禁止使用 `$request->all()` 或 `$request[...]`；輸入資料必須來自 FormRequest 的明確方法。

## 常數與環境設定規範

- 產品名稱、品牌色、route/page 識別、驗證限制、cache key／TTL／payload version 等會隨產品版本調整的參數，統一定義於 `app/Constants`。
- PHP 參數由對應 Constant 直接引用；Vue 與 CSS 所需參數由 Combination 傳入 `applicationData`，不得在前端重複寫死。
- 資料庫連線、外部服務憑證、主機 URL 等會隨部署環境變動或涉及機密的設定，維持於 `.env` 與 `config`，不得放入 Constants。
- 可翻譯的使用者文案應放入語系檔；Constants 不負責取代 Laravel localization。
- `ProjectConstant` 管理專案名稱與品牌視覺參數，`AuthenticationConstant` 管理登入／註冊流程參數，`UserConstant` 管理會員與快取參數。
- `PostConstant` 管理貼文長度、回覆長度、每批載入筆數、route 與 URI。

## Container 選擇規範

- Controller 建構子只注入 `app/Contracts/Containers` 與 `app/Contracts/Responses` 下的介面。
- Controller action 只接受對應的 FormRequest，不得將 Laravel Request 物件傳入 Container、ServiceManager 或 Service。
- Controller 不得直接注入具體 Service 或 ServiceManager；若入口需要新的商業流程，應先擴充 Container Contract，再由 Provider 決定實作。
- `EntryContextServiceProvider` 使用 Laravel contextual binding，依 Controller 決定 Container 實作；因此在任何 Service 被調用前，入口容器已經確定。
- Container 負責使用案例與 transaction 的編排，不處理 HTTP response 或 session。
- `EntryContextServiceProvider` 只負責 Controller → Container 的 contextual binding。
- `ApplicationServiceProvider` 負責 ServiceManager、ResponseMaker 與 TransactionManager contracts。
- `RepositoryServiceProvider` 只負責 Repository contract → Eloquent Repository bindings。
- `ResponseMakerInterface` 由 `JsonResponseMaker` 實作，Controller 不直接呼叫 `response()->json()`；未來替換回應格式時不必修改 Controller。
- 例如未來新增 OAuth 入口時，可建立 `OauthAuthenticationContainer`，並將對應 Controller 綁定到該實作，不必修改既有 Service。

上述主要限制由 `tests/Unit/LayerDependencyTest.php` 自動檢查。

## 命名規範

- Controller action 的 FormRequest 參數名稱必須由具體型別完整衍生，例如 `FeedIndexRequest $feedIndexRequest`、`StorePostRequest $storePostRequest`；函式內必須持續使用相同名稱。
- 基礎的 Laravel `Request` 使用 `$httpRequest`，專案自訂的 `ApplicationRequest` 使用 `$applicationRequest`，不得退回無法辨識具體用途的 `$request`。
- 函式、參數、屬性與區域變數必須描述領域與用途，例如 `$authenticationThrottleKey`、`$cachedUserPayload`、`$feedResponsePayload`；不得使用 `$tmp`、`$obj`、`$val`、`$result` 等需要讀者猜測的名稱。
- 布林值使用可直接判讀的狀態名稱，例如 `$authenticationSucceeded`、`$feedRequestInProgress`、`$isLoginPage`。
- 標準技術縮寫 `HTTP`、`URL`、`API`、`ID`、`IP`、`CSRF` 可保留，但必須附帶領域上下文，例如 `$postId`、`$feedEndpointUrl`、`$ipAddress`。
- JSON 欄位、資料庫欄位與既有 HTTP 契約不因內部重新命名而任意變更；例如對外的 `data`、`meta`、`user_id` 保持相容，內部變數則使用 `$responseData`、`$metadata`、`$userId`。
- `tests/Unit/LayerDependencyTest.php` 會檢查 Request 型別與參數名稱，防止具體 FormRequest 再次被命名為 `$request`。

## Taichi API 參考架構採用原則

本專案採用參考專案的 Repository Provider、ServiceManage contract、Combination／CombinationManage 與交易封裝概念，但依 Laravel 13 與目前規模作以下調整：

- 保留：各層專屬 Service Provider、Repository interface、ServiceManager 流程編排、Combination 輸出整理、情境式 Container 選擇。
- 改良：以 constructor injection 與固定 contract binding 取代執行途中呼叫全域 `App::bind()`，避免長駐程序發生跨請求狀態污染。
- 改良：以無狀態 `TransactionManagerInterface` 取代 DB transaction trait，便於測試 rollback 與替換資料庫實作。
- 不採用：具可變回傳狀態的 AbstractService／AbstractCombination、Controller 直接 new Repository、Request 傳入 Service，以及 trait 型 response maker。
- 安全：參考壓縮檔內的 `.env`、OAuth key、`.git`、`vendor` 與 runtime storage 不屬於架構來源，禁止複製進本專案。

## Cache 規範

- Cache key 只能由對應的 CacheManager 產生。
- 會員快取格式：`[UserById][user_id:{id}]`。
- TTL 集中定義於 `UserConstant::CACHE_TTL_SECONDS`。
- Repository 寫入資料後必須清除受影響的 cache key。
- 不得使用 `Cache::flush()`，避免清除其他模組或共用應用程式的資料。

## 例外碼範圍

| 範圍 | 領域 |
| --- | --- |
| `1101xxx` | 註冊、登入與驗證流程 |
| `1201xxx` | 會員資料流程 |
| `1301xxx` | 貼文、按讚與回覆流程 |

新增錯誤時，應先在對應的 `ExceptionCodes` 類別定義，再由領域例外輸出 `message`、`code` 與欄位 `errors`。

## 新功能擴充步驟

以新增「貼文」為例：

1. 建立 `Post` Model 與 migration。
2. 建立 `PostRepositoryInterface`、`PostRepository` 與 Service Container binding。
3. 建立 `PostValidator`、`PostChecker` 與對應 FormRequest，透過 `rules()` method injection 組合規則。
4. FormRequest 以明確的 `payload()`／存取方法只輸出已驗證欄位。
5. 建立 `PostService`；需要跨 Service 流程時再建立 `PostServiceManager`。
6. 衍生欄位放入 `PostPresenter` 或 `PostCombination`。
7. 跨來源頁面資料由 `PostCombinationManager` 組合。
8. 複雜查詢需要快取時建立 `PostCacheManager`。
9. 建立 `PostContainerInterface` 與入口所需的 Container 實作，並於 `EntryContextServiceProvider` 設定 contextual binding。
10. Controller 只把 FormRequest 的已驗證資料交給 `PostContainerInterface`；transaction 由 Container 控制。
11. 增加行為測試、Request 邊界測試與分層限制測試。
