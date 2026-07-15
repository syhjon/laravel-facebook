# 專案架構準則

本專案採用 Combination & Cache 分層架構，並配合 Laravel 13 的 Service Container、Eloquent、Validation 與 Cache 實作。

## 資料流程

```text
HTTP Request
  └─ Controller（流程與 Transaction）
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
| `app/Http/Controllers` | 控制 HTTP 流程與資料交易，不放商業邏輯 |
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
2. Controller 不得直接存取 Repository、Validator 或 Model。
3. Service 不得直接查詢 Model，資料存取必須經過 Repository。
4. 低階層不得反向存取高階層。
5. 同類型類別不得互相呼叫，避免循環依賴。
6. CacheManager、Constant、Support、ExceptionCode 為獨立結構，可被各層使用。
7. 寫入資料的 transaction 由 Controller 統一控制。
8. Blade 與 Vue 只負責呈現；衍生欄位應由 Presenter 或 Combination 先行準備。

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
8. Controller 僅串接上述流程並控制 transaction。
9. 增加行為測試與分層限制測試。
