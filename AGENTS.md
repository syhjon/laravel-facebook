# 專案開發規範

所有後續功能新增、修正與重構都以職責分離、清楚的依賴方向與行為相容為目標，沿用目標專案既有目錄、命名空間與類別名稱。開始修改前先閱讀 `docs/architecture.md` 與 `docs/infrastructure-refactoring-guide.md`，並以目標功能目前的程式碼確認真實行為。通用指南提供設計參考；範例名稱、路徑及類別數量不構成實作要求，未經明確要求不搬目錄或改 namespace。

## 分層

- 現有主要鏈：Controller → `app/ServiceManagers` → `app/Services` → Repository interface／implementation → `app/Models`。新設計依實際複雜度選用編排、介面與組裝層，不為符合架構圖建立空包裝。
- HTTP 邊界保留 FormRequest、認證授權、CSRF、Session 與既有 ResponseMaker；不把 Request 傳入業務層。
- ServiceManage 負責流程協調與交易邊界；Service 透過 Repository 介面操作領域資料。
- Repository 介面沿用 `app/Contracts/Repositories/*RepositoryInterface.php`，實作沿用 `app/Repositories/*Repository.php`，並由已載入的 RepositoryServiceProvider 綁定；不為配合教學範例改動既有介面名稱。
- Checker、OrmCollection、Combination、CombinationManage 依實際驗證、集合映射及資料組裝需求加入，不建立空包裝。
- 只有確實存在多實作與選擇需求時，才評估策略、Factory、Resolver 或設定；依既有機制承擔責任，不要求全部新增。
- 保留既有 I/O 介面；不得為單一業務實作重新建立 Container 或 ServiceManager interface。
- 新的業務類別放在既有 ServiceManagers、Services、Repositories、Models、Checkers、CombinationManagers 等對應目錄；優先依職責調整相依關係，不把架構重構擴大成目錄或命名空間搬移。

## 相容性與相依

- 保留既有 API、認證授權、參數／變數／屬性名稱、預設值、錯誤、輸出欄位、排序、分頁、快取與資料庫行為。
- 逐方法確認回傳型別，不混用 Model、Collection、Builder、內部結果與 HTTP response。
- Checker 的失敗語意及訊息格式依原呼叫契約保留；fails 型 status=true 代表失敗。現有 Checker 只提供規則，不擅自改成結果包裝。
- 若功能有 skipPresenter(true)，保留原 Builder 行為與執行時機；combinationType 依原方法保留所有分支。現有功能沒有這兩個 API，不主動補造。
- 確認已安裝套件與基底類別後才使用，不猜測第三方套件方法，不複製 TODO、dd、空方法與明顯錯誤。
- Model namespace 變更時，同步確認 Auth Provider、Factory、關聯、Cache、Provider 與所有呼叫端。
- Repository 與 ServiceManage 保持一般解析，不改為 singleton；保留原交易連線、範圍、重試預設值與檔案補償順序。

## 檢查與交付

- 先列出目前呼叫鏈、輸入輸出契約與副作用，再進行修改。
- 執行受影響的測試；架構變更至少執行 `php artisan test`、`vendor/bin/pint --test app bootstrap/app.php bootstrap/providers.php config database routes tests` 與 `git diff --check`。
- 前端變更另執行 `node --test tests/Frontend/http.test.js` 與 `npm run build`；namespace 變更執行 `composer dump-autoload`。
- 回覆使用繁體中文，列出完成後呼叫鏈、檔案職責、驗證結果及未驗證項目，不宣稱未執行的檢查已通過。
