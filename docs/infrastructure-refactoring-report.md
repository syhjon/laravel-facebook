# 分層重構交付紀錄

## 套用方式

會員認證、個人檔案與大頭貼、動態牆依分層職責重構，沿用目標專案既有目錄、命名空間、類別與介面名稱。通用架構指南的範例配置不構成搬移要求。

48 個曾搬移的檔案均已恢復原始路徑與 namespace，app/InfraStructure 已移除。Repository 介面恢復為 UserRepositoryInterface、PostRepositoryInterface，沒有保留 UserEloquent／PostEloquent 改名。Model 與 Factory 恢復原 App\\Models 及既有對應慣例。

分層及交易調整保留：Controller 直接注入 ServiceManager，原單一實作 Container 的流程與交易責任整合到 ServiceManager。登入轉址修正保留。

## 完成後呼叫鏈

```text
HTTP middleware → FormRequest → Checker → Validator
Controller → ServiceManager → Service → RepositoryInterface → Repository → Model
ServiceManager → Combination／CombinationManager（輸出與頁面組裝）
```

會員註冊：AuthController → AuthenticationServiceManager → UserService → UserRepositoryInterface → UserRepository → User，再由 AuthenticationService 登入。

帳密登入：AuthController → AuthenticationServiceManager → AuthenticationService → Laravel session guard／EloquentUserProvider → App\\Models\\User，保留 Auth::attempt、RateLimiter、remember token 與 Session 處理。

個人檔案：ProfileController → UserProfileServiceManager → UserService → UserRepositoryInterface → UserRepository → User；UserCombination 整理個人資料，LocalProfileImageStorage 處理照片檔案。

動態牆：FeedController → PostServiceManager → PostService → PostRepositoryInterface → PostRepository → Post／Comment／PostLike；PostCombination 整理輸出。

## 檔案責任與既有路徑

| 路徑 | 職責 |
| --- | --- |
| app/ServiceManagers | 認證、個人資料、貼文流程與交易邊界 |
| app/Services | 領域操作與 Repository 呼叫 |
| app/Contracts/Repositories | 既有 UserRepositoryInterface、PostRepositoryInterface |
| app/Repositories | 查詢與 CRUD |
| app/Models | 原 Model、資料表、casts、關聯與序列化 |
| app/Checkers、app/Validators | 原驗證規則來源 |
| app/Combinations、app/CombinationManagers | 單一領域輸出與頁面組裝 |
| app/Providers | Repository 及必要 I/O 介面綁定 |
| app/CacheManagers | 原 key、TTL、純量 payload 與局部快取清除 |
| app/Contracts/Responses、Storage、Transactions | 既有 I/O 介面；實作沿用 app/Responses、app/Storage、app/Transactions |
| AGENTS.md、docs/architecture.md | 後續開發沿用既有目錄與命名的規範 |
| docs/infrastructure-refactoring-guide.md | 通用設計與重構指南；依需求選用分層，沿用目標專案既有目錄與命名 |

目前沒有平台／身分實作切換或客製集合需求，未建立策略 Contract、SwitchServiceManage、Factory interface、Config 或空 OrmCollection。

## 保留的契約

- API 路徑、method、名稱、middleware、auth／guest、CSRF 與輸入白名單。
- 既有參數、變數、屬性名稱；Controller 的 authenticationContainer、feedContainer、profileContainer 名稱保留，型別為對應具體 ServiceManager。
- 登入 200、註冊 201、登出 302，以及既有 redirect、message、data、meta、duration、datetime、code、errors。
- 已登入後重送登入／註冊的 JSON redirect 回應。
- 原方法的 void、array、Model、nullable Model、bool、Comment、CursorPaginator 回傳格式。
- 貼文 ID 倒序、每批 10 筆 cursor pagination、留言 ID 正序，以及 next_cursor／has_more。
- 個人資料欄位、照片限制、相對公開 URL、替換與失敗清理順序、會員快取失效。
- 原資料庫結構、連線、交易範圍、attempts 預設值與例外行為。
- Checker 仍回傳原規則陣列；現有功能沒有 skipPresenter、combinationType 或 status 結果包裝，未新增假定分支。

## 本次恢復路徑後的驗證

| 檢查 | 結果 |
| --- | --- |
| 原始檔案路徑檢查 | 48 個原路徑全部存在 |
| namespace 掃描 | 業務程式、設定、路由、測試及 resources 沒有 App\\InfraStructure 引用 |
| 目錄檢查 | app/InfraStructure 不存在 |
| composer dump-autoload 與 package discovery | 成功 |
| php artisan test | 59 項通過、445 次斷言 |
| node --test tests/Frontend/http.test.js | 6 項全部通過 |
| PHP 語法檢查 | 99 個非快取 PHP 檔案全部通過 |
| 路由前後完整 JSON 比較 | 18 條完全一致 |
| Pint 與 git diff --check | 通過 |

另以獨立 cookie 在正在執行的 127.0.0.1:8000 實測登入、dashboard、profile、個人資料 API、feed、已登入重送登入及登出，全部通過。登入 JSON 的 redirect 為 /dashboard。測試未改寫既有會員、貼文或照片，登入憑證及 cookie 未寫入本報告。

交易測試涵蓋註冊後認證失敗回滾、貼文輸出組裝失敗回滾、個人資料組裝失敗回滾，以及照片路徑寫入失敗時的新檔清理及舊檔保留。分層檢查已改回既有目錄，包含 Checkers 與 Validators。

## 未驗證項目

- 未執行瀏覽器 UI 自動操作、MySQL 寫入併發、故障注入或正式部署驗證。
- 寫入與回滾自動測試使用 SQLite 記憶體資料庫與 fake storage。
- 本次前端程式未修改，未重跑 npm run build；前一輪建置已通過。
