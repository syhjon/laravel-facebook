# CrowdTalk 專案架構

本文件說明本專案目前的分層職責與既有目錄配置。新增與修改功能應沿用現有目錄、命名空間及類別名稱，並參考[通用分層架構設計與重構指南](infrastructure-refactoring-guide.md)。通用指南的範例依實際需求選用，不構成固定路徑、類別數量或套件要求。

## 主要呼叫鏈

```text
HTTP middleware（web、auth／guest、CSRF）
  → FormRequest（正規化、驗證、白名單資料）
      → Checker → Validators（現有規則來源）
  → Controller（HTTP、Session、既有 ResponseMaker）
  → ServiceManagers（流程、交易邊界）
  → Services（領域操作）
  → Contracts/Repositories/*RepositoryInterface（interface）
      → Repositories/*Repository（implementation）
      → Models（Eloquent）
```

查詢結果沿原方法的契約回傳。單一領域的輸出由 Combination 整理；頁面資料由 CombinationManage 組合。只有實際依賴客製 Collection 型別或映射方法時才建立 OrmCollection。

目前沒有平台或身分實作切換，因此 Controller 直接注入具體 ServiceManage，沒有 Container、ServiceManager interface、SwitchServiceManage、策略 Contract、Factory interface 或策略 Config。既有類別仍命名為 AuthenticationServiceManager、PostServiceManager、UserProfileServiceManager，保留原類名；目錄沿用 app/ServiceManagers。

## 現有功能

### 會員認證

```text
AuthController
  → AuthenticationServiceManager
      ├─ register → transaction → UserService::create
      │                            → UserRepositoryInterface → UserRepository → User
      │                          → AuthenticationService::login
      ├─ authenticate → AuthenticationService::authenticate
      ├─ logout → AuthenticationService::logout
      └─ page → ApplicationPageCombinationManager
                 → UserService → UserRepository → User
                 → ApplicationPageCombination
```

AuthenticationService 保留 Laravel 的 Auth::attempt、Auth::login、Auth::logout 與 RateLimiter；帳密驗證仍由原 session guard 及 EloquentUserProvider 執行。Auth Provider 沿用既有 App\Models\User。

Controller 保留 Session regenerate、invalidate、regenerateToken 的原執行位置。RedirectAuthenticatedRequests 仍將已登入使用者的 JSON 登入／註冊請求回應為含 redirect 的 JSON；一般頁面請求維持 HTTP 302。

### 動態牆

```text
FeedController
  → PostServiceManager（寫入流程包含 transaction 與輸出組裝）
      → PostService → PostRepositoryInterface → PostRepository
                                      → Post／Comment／PostLike
      → PostCombination
```

PostService::feed 回傳 CursorPaginator；PostService 的 create、toggleLike、comment 回傳 Post；Repository 的 toggleLike 回傳 bool，createComment 回傳 Comment。ServiceManage 才回傳原有 array。貼文依 id 倒序、每批 10 筆游標分頁；留言依 id 正序。HTTP feed metadata 保留 next_cursor、has_more。

### 個人檔案與大頭貼

```text
ProfileController
  → UserProfileServiceManager
      ├─ profile／updateProfile／updateProfileImage
      │   → UserService → UserRepositoryInterface → UserRepository → User
      │       ├─ UserCombination
      │       └─ ProfileImageStorageInterface → LocalProfileImageStorage
      └─ page → ApplicationPageCombinationManager
```

更新姓名、Email 與照片的交易由 ServiceManage 控制，涵蓋原本 Container 的完整範圍。UserRepository 寫入後仍只清除受影響的會員快取。照片上傳、替換、舊檔刪除與寫入失敗時的新檔清除順序保持不變。

## 目錄與職責

各層沿用下列既有路徑。ServiceManage、CombinationManage 等名稱表示職責，不要求變更目錄拼字、深度或 namespace。

| 目錄 | 職責 |
| --- | --- |
| app/ServiceManagers | 應用流程、跨 Service 協調、既有交易邊界與輸出組裝 |
| app/Services | 領域操作、例外與 Repository 呼叫 |
| app/Contracts/Repositories、app/Repositories | 既有 XxxRepositoryInterface 與 XxxRepository，集中查詢與 CRUD |
| app/Models | 資料表、欄位、casts、關聯、序列化與 Factory 指定 |
| app/Checkers、app/Validators | 現有驗證規則入口；Validators 保存單一領域規則 |
| app/Combinations | 單一領域輸出欄位及游標分頁格式 |
| app/CombinationManagers | 透過 Service 取得資料並組合完整頁面 |
| app/Providers | Repository 綁定及必要的 I/O 介面綁定 |
| app/CacheManagers | 快取 key、TTL、純量 payload 與局部清除 |
| app/Presenters | 保留 User::present 的既有欄位轉換 |
| app/Storage | 照片儲存、刪除、同網域公開 URL 與既有介面 |
| app/Transactions | Laravel 原連線的 transaction、rollback 與 attempts 預設值 |
| app/Responses | 既有 JSON 回應工具與介面 |
| app/Constants、app/Supports | 領域常數、品牌設定與無狀態共用轉換 |
| app/ExceptionCodes、app/Exceptions | 既有錯誤代碼、訊息及 HTTP 轉換 |
| app/Http | Controller、FormRequest、Middleware；HTTP 物件不傳入業務層 |
| database/factories | 沿用既有 Factory 與 App\Models 的預設對應慣例 |
| bootstrap/providers.php | 載入 app/Providers 的 RepositoryServiceProvider、ApplicationServiceProvider |

ResponseMakerInterface、TransactionManagerInterface、ProfileImageStorageInterface 是既有 I/O 抽象，與參考文件中用於平台切換的具體 Contract 入口不同。介面沿用 app/Contracts 的既有子目錄，實作沿用 app/Responses、app/Transactions、app/Storage，不新增業務入口包裝。

## 相容性規則

1. 保留 API 路徑、method、路由名稱、middleware、認證授權、CSRF、輸入白名單及預設值。
2. 保留變數、屬性、參數名稱、公開方法的既有參數順序與回傳格式。Controller 的 authenticationContainer、feedContainer、profileContainer 屬性名稱因相容要求保留，其型別已改為具體 ServiceManage。
3. 保留 HTTP 狀態、message、data、redirect、code、errors、meta、duration、datetime；不可統一改套參考專案的其他回應版本。
4. 現有 Checker 只回傳規則陣列，失敗仍由 FormRequest 轉換為 DomainValidationException。不可擅自加上 status 包裝或把驗證移到寫入之後。
5. 未來沿用 fails 型 Checker 時，status=true 代表失敗；必須依該功能原有的 messages／json_messages／message 格式處理。
6. 本專案目前沒有 skipPresenter 或 combinationType，不新增假定的通用 API。若未來引入既有模組，須逐方法保留 Builder 的執行時機及所有分支。
7. 保留原 Model namespace、table、connection、fillable、hidden、casts、關聯與日期格式。僅在使用者明確要求搬移時，同步更新 Auth Provider、Factory、Cache 與全部呼叫端。
8. 不以直接 Model 查詢繞過 Repository。保留 Laravel guard 本身的認證與資料讀取流程。
9. Repository、ServiceManage 不設為 singleton；RepositoryServiceProvider 使用一般 bind，避免可變 Model 或查詢狀態跨使用案例共用。
10. 交易由 ServiceManage 透過 TransactionManagerInterface 控制。只在原本有交易的操作開始交易；try/catch 僅在原有補償或例外轉換位置保留。
11. 保留照片補償順序；不把檔案操作宣稱為資料庫交易的一部分。
12. 只使用已確認安裝的框架與套件基底，不得依名稱猜測第三方套件 API。
13. 沿用既有目錄、namespace、類別與介面名稱；通用指南的範例不構成搬移或改名要求。只有存在多實作與選擇需求時，才評估策略、Factory、Resolver 或設定，並測試實際存在的分支。
14. 新功能不得建立空包裝、空方法、dd 或複製參考 TODO。具實際集合映射需求才建立 OrmCollection 並調整 Model::newCollection。

## 常數、快取與錯誤

- 產品名稱、品牌色、route/page 識別、限制、TTL、key pattern 仍由 Constants 管理；Vue、CSS 透過既有 applicationData 取得。
- DB、憑證與環境 URL 維持於環境設定，不搬入 Constants。
- 會員快取 key 維持 [UserById][user_id:{id}]；payload 保留 version 與純量 attributes，不存 Model 序列化物件。不得 Cache::flush。
- 錯誤碼範圍維持：1101xxx 認證、1201xxx 會員資料、1301xxx 貼文互動。

## 驗證

```bash
composer dump-autoload
php artisan test
node --test tests/Frontend/http.test.js
npm run build
vendor/bin/pint --test app bootstrap/app.php bootstrap/providers.php config database routes tests
git diff --check
```

LayerDependencyTest 防止 HTTP 邊界跳過 ServiceManage、低階層反向依賴與分層檢查因空目錄失效。ServiceManageTransactionTest 驗證註冊、發文、個人資料的交易回滾與照片寫入失敗補償。既有 Feature 測試負責 API、認證、錯誤、分頁與快取契約。

每次交付須列出完成後呼叫鏈、檔案職責、實際執行的驗證與未驗證項目。
