# 專案架構準則

本專案採用 Combination & Cache 分層架構，並配合 Laravel 13 的 Service Container、Eloquent、Validation 與 Cache 實作。

## 資料流程

```text
HTTP Request
  └─ Controller（流程與 Transaction）
      └─ Container Contract
          └─ Container（由 Contextual Binding 決定實作）
              ├─ Checker
              │   └─ Validator
              ├─ ServiceManager
              │   └─ Service
              │       ├─ Repository
              │       │   ├─ CacheManager
              │       │   └─ Model
              │       │       └─ Presenter
              │       └─ Combination
              └─ CombinationManager
                  ├─ Service
                  └─ Combination
```

目前會員系統的實際流程：

```text
AuthController
  └─ AuthenticationContainerInterface
      └─ WebAuthenticationContainer（AppServiceProvider 決定）
          ├─ AuthenticationChecker → UserValidator
          ├─ AuthenticationServiceManager
          │   ├─ AuthenticationService
          │   └─ UserService → UserRepository → User Model → UserPresenter
          │                         └─ UserCacheManager
          └─ AuthenticationPageCombinationManager
              ├─ UserService
              └─ AuthenticationPageCombination
```

## 目錄職責

| 目錄 | 職責 |
| --- | --- |
| `app/Http/Controllers` | 控制 HTTP/session 與資料交易，只依賴 Container Contract |
| `app/Contracts/Containers` | 定義特定入口可執行的應用操作 |
| `app/Containers` | 作為 Controller 與應用層的邊界，調用 Service 前先由容器決定該入口的流程組合 |
| `app/Checkers` | 依使用案例組合一或多個 Validator |
| `app/Validators` | 驗證單一 Model 的欄位資料 |
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

## 存取限制

1. 不得跨越兩個以上的控制層直接存取。
2. Controller 只能依賴 Container Contract，不得直接存取 Checker、CombinationManager、ServiceManager、Service、Repository、Validator 或 Model。
3. Service 不得直接查詢 Model，資料存取必須經過 Repository。
4. 低階層不得反向存取高階層。
5. 同類型類別不得互相呼叫，避免循環依賴。
6. CacheManager、Constant、Support、ExceptionCode 為獨立結構，可被各層使用。
7. 寫入資料的 transaction 由 Controller 統一控制。
8. Container 可組合 Checker、ServiceManager 與 CombinationManager，但不得跳層直接存取 Service、Repository、Validator 或 Model。
9. Blade 與 Vue 只負責呈現；衍生欄位應由 Presenter 或 Combination 先行準備。

## 常數與環境設定規範

- 產品名稱、品牌色、route/page 識別、驗證限制、cache key／TTL／payload version 等會隨產品版本調整的參數，統一定義於 `app/Constants`。
- PHP 參數由對應 Constant 直接引用；Vue 與 CSS 所需參數由 Combination 傳入 `appData`，不得在前端重複寫死。
- 資料庫連線、外部服務憑證、主機 URL 等會隨部署環境變動或涉及機密的設定，維持於 `.env` 與 `config`，不得放入 Constants。
- 可翻譯的使用者文案應放入語系檔；Constants 不負責取代 Laravel localization。
- `ProjectConstant` 管理專案名稱與品牌視覺參數，`AuthenticationConstant` 管理登入／註冊流程參數，`UserConstant` 管理會員與快取參數。

## Container 選擇規範

- Controller 建構子只注入 `app/Contracts/Containers` 下的介面。
- `AppServiceProvider` 使用 Laravel contextual binding，依 Controller 決定 Container 實作；因此在任何 Service 被調用前，入口容器已經確定。
- Container 負責使用案例的編排，不處理 HTTP response、session 或 transaction。
- 例如未來新增 OAuth 入口時，可建立 `OauthAuthenticationContainer`，並將對應 Controller 綁定到該實作，不必修改既有 Service。

上述主要限制由 `tests/Unit/LayerDependencyTest.php` 自動檢查。

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

新增錯誤時，應先在對應的 `ExceptionCodes` 類別定義，再由領域例外輸出 `message`、`code` 與欄位 `errors`。

## 新功能擴充步驟

以新增「貼文」為例：

1. 建立 `Post` Model 與 migration。
2. 建立 `PostRepositoryInterface`、`PostRepository` 與 Service Container binding。
3. 建立 `PostValidator` 與 `PostChecker`。
4. 建立 `PostService`；需要跨 Service 流程時再建立 `PostServiceManager`。
5. 衍生欄位放入 `PostPresenter` 或 `PostCombination`。
6. 跨來源頁面資料由 `PostCombinationManager` 組合。
7. 複雜查詢需要快取時建立 `PostCacheManager`。
8. 建立 `PostContainerInterface` 與入口所需的 Container 實作，並於 `AppServiceProvider` 設定 contextual binding。
9. Controller 僅依賴 `PostContainerInterface`，並控制 HTTP/session 與 transaction。
10. 增加行為測試與分層限制測試。
