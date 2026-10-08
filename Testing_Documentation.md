# Joe’s Electronics Inventory Management System — Testing Documentation

**Assignment:** Project – Testing (SAU2)  
**System:** Joe’s Electronics Inventory Management System  
**Application type:** PHP/MySQL web application running through XAMPP  
**Source repository:** [https://github.com/kurmankul-n/joes_electronics](https://github.com/kurmankul-n/joes_electronics)  
**Required evidence:** One clear screenshot for each completed test

## 1. Purpose of this document

This document is an execution guide for completing the SAU2 testing table. It contains **10 test cases** covering normal, boundary, invalid, functional, and access-control scenarios in the actual Joe’s Electronics system.

The coding agent should:

1. Set up the application and database.
2. Execute the ten tests in the order shown.
3. Capture at least one readable screenshot for each test.
4. Fill the **Actual Result** column with what really happened, rather than copying the expected result.
5. Mark each test as **PASS** or **FAIL** and briefly explain any difference.
6. Insert the screenshot filename or image into the evidence section of the final submission.

> Important: The expected results below describe the correct behaviour. The actual result must be based on the observed application output during execution.

---

## 2. Repository and implementation reference

The system under test is the public GitHub project [kurmankul-n/joes_electronics](https://github.com/kurmankul-n/joes_electronics). The test plan was derived from the repository files, including `login.php`, `register.php`, `products.php`, `sales.php`, `reports.php`, `profile.php`, `users.php`, `index.php`, `auth.php`, and `inventory_db.sql`.

When reporting a defect, include the affected page/file where it can be identified, for example: `sales.php` — overselling validation, or `users.php` — regular-user authorization.

## 3. Evidence capture with Playwright

Evidence may be captured manually in Chrome/Edge or automatically using the **Playwright skill** available to the coding agent. Playwright is recommended because it can repeat the same steps, preserve a browser session, verify visible text, and save consistently named screenshots.

### Recommended Playwright evidence workflow

1. Start Apache and MySQL and confirm the application URL, for example `http://localhost/inventory/`.
2. Open the application with a Playwright browser context. Use a fresh context for authentication tests unless the test specifically requires an existing session.
3. Navigate using visible links or direct URLs only where the test instructs it.
4. Locate controls by label, role, or name, such as `getByLabel('Username')`, `getByRole('button', { name: 'Sign In' })`, or `getByRole('link', { name: 'Products' })`.
5. Fill the exact test data from the relevant test case and submit the form.
6. Assert the expected URL, visible alert text, table row, calculated value, or navigation item.
7. Save a screenshot after the result is visible using the required filename, for example `TC07_sale_recorded.png`. Use a full-page screenshot when the relevant result is not visible in the viewport.
8. In the final table, report the observed result and link or insert the saved screenshot. Do not use a screenshot of an assertion failure as evidence of a passing test.

Example Playwright-style evidence snippet:

```javascript
await page.goto('http://localhost/inventory/login.php');
await page.getByLabel('Username').fill('admin');
await page.getByLabel('Password').fill('password');
await page.getByRole('button', { name: 'Sign In' }).click();
await expect(page).toHaveURL(/index\.php/);
await expect(page.getByRole('heading', { name: 'Dashboard' })).toBeVisible();
await page.screenshot({ path: 'evidence/TC01_admin_login.png', fullPage: true });
```

If the coding agent uses a different Playwright selector because the local browser exposes labels differently, it should preserve the same test data, expected assertion, and screenshot filename.

## 4. Pre-digested implementation briefing for the coding agent

This section is intentionally detailed so the coding agent does not need to rediscover the repository before running the tests. Use the source files as the authority if the live application differs from these notes.

### 4.1 Application routes and authentication behaviour

| Route | Authentication requirement | Main purpose | Important implementation facts |
|---|---|---|---|
| `login.php` | Public | Sign in | POST fields are `username` and `password`; empty fields produce `Please fill in all fields.`; successful login redirects to `index.php`; failed credentials produce `Invalid username or password.` |
| `register.php` | Public | Create account | POST fields are `fullName`, `username`, `password`, `confirmPassword`; new accounts default to role `user`; validation occurs before `auth.php::registerUser()`. |
| `index.php` | Logged-in user | Dashboard | Calls `requireLogin()`; displays total products, today’s units sold, low-stock count, inventory value, and up to five recent sales. |
| `products.php` | Logged-in user | Product list/search/CRUD | All users can view/search; only admins see the add/edit/delete controls because `$canEdit = isAdmin()`. |
| `sales.php` | Logged-in user | Record and review sales | POST action is `recordSale`; available product options only include rows where `StockQuantity > 0`; history shows the latest 15 entries. |
| `reports.php` | Logged-in user | Date-filtered report | GET parameter is `date`; invalid date format falls back to the current date; report includes products even when their sales for the selected date are zero. |
| `profile.php` | Logged-in user | Profile/password changes | POST submit controls are `updateProfile` and `changePassword`; username is displayed as disabled and cannot be edited through the form. |
| `users.php` | Admin only | User management | Calls `requireAdmin()` before rendering; a regular user is redirected to `index.php`; admins cannot delete or change their own account/role. |
| `logout.php` | Logged-in user | End session | Calls `logout()` from `auth.php`, destroys the session, and redirects to `login.php`. |

### 4.2 Exact form controls and Playwright-friendly selectors

Use accessible labels where possible. If the local browser does not expose a label correctly, use the listed `name` or `id` selector.

| Test/feature | Page | Preferred locator | HTML name/id or action value |
|---|---|---|---|
| Login username | `login.php` | `getByLabel('Username')` | `input[name="username"]` |
| Login password | `login.php` | `getByLabel('Password')` | `input[name="password"]` |
| Login submit | `login.php` | `getByRole('button', {name: 'Sign In'})` | POST to `login.php` |
| Registration full name | `register.php` | `getByLabel('Full Name')` | `input[name="fullName"]` |
| Registration username/password | `register.php` | `getByLabel(...)` | `username`, `password`, `confirmPassword` |
| Registration submit | `register.php` | `getByRole('button', {name: 'Create Account'})` | POST to `register.php` |
| Product search | `products.php` | `locator('input[name="searchID"]')` | GET `searchID`; Search button submits the GET form |
| Add-product fields | `products.php` | `getByLabel(...)` | `productName`, `category`, `unitPrice`, `stockQuantity` |
| Add-product request | `products.php` | `getByRole('button', {name: 'Add Product'})` | hidden `action=add` |
| Sale product | `sales.php` | `getByLabel('Select Product')` or `#productSelect` | `select[name="productID"]` |
| Sale quantity/date | `sales.php` | `getByLabel(...)` | `quantitySold` / `saleDate`; quantity ID is `quantityInput` |
| Sale submit | `sales.php` | `getByRole('button', {name: 'Record Sale'})` | hidden `action=recordSale` |
| Profile name | `profile.php` | `getByLabel('Full Name')` | `input[name="fullName"]` in the `updateProfile` form |
| Profile submit | `profile.php` | `getByRole('button', {name: 'Update Profile'})` | POST field `updateProfile` |
| Password fields | `profile.php` | `getByLabel(...)` | `currentPassword`, `newPassword`, `confirmPassword` |
| Password submit | `profile.php` | `getByRole('button', {name: 'Change Password'})` | POST field `changePassword` |
| Report date | `reports.php` | `getByLabel('Report Date:')` or `input[name="date"]` | GET `date` |
| Report submit | `reports.php` | `getByRole('button', {name: 'Generate Report'})` | GET to `reports.php` |

### 4.3 Source-level behaviour and state changes by test

| Test | Source-level sequence to verify | State change |
|---:|---|---|
| 1 | `login.php` receives POST → `loginUser()` in `auth.php` queries `users` → `password_verify()` succeeds → session values are set → redirect to `index.php`. | Session is created; database is unchanged. |
| 2 | `login.php` receives POST → `loginUser()` fails username/password lookup or verification → error string is rendered. | No authenticated session should be created; database unchanged. |
| 3 | `register.php` validates the input in order: empty fields, username length, password length, password match → only then calls `registerUser()`. | With username `ab`, no user row should be inserted. |
| 4 | `products.php` checks `$canEdit` and `action=add` → validates price/stock/name/category → executes `INSERT INTO products` → reloads the list and applies `bubbleSort()`. | One product row is inserted. |
| 5 | `products.php` reads GET `searchID` → rejects `<= 0`, otherwise loads products ordered by `ProductID` → calls `binarySearch()` → renders either Search Result or error alert. | No database change. |
| 6 | Page templates call `isAdmin()` to show the Users link; `users.php` calls `requireAdmin()` → `requireLogin()` → non-admin redirect to `index.php`. | No database change. |
| 7 | `sales.php` validates positive product/quantity/date → loads stock → creates or reuses one `reports` row for the date → inserts `sales` row → updates product stock. The JavaScript preview runs before the POST. | One sale row; stock decreases by the quantity; one report row may be created. |
| 8 | `sales.php` loads the selected product and checks `StockQuantity < quantitySold` before the report INSERT, sale INSERT, and stock UPDATE branches. | No sale/report/stock change should occur. |
| 9 | `profile.php` update branch writes `FullName` and session name; password branch checks length/matching fields, loads the stored hash, and calls `password_verify()`. | Test 9 changes only the full name; wrong current password must not change the password hash. |
| 10 | `reports.php` reads GET `date` → `buildReportMatrix()` queries each product’s selected-date sales → totals are accumulated → rows are sorted by revenue descending → the table and totals are rendered. | Report viewing should not change database state. |

### 4.4 Exact business rules and expected messages from the source

- Login with blank username or password: `Please fill in all fields.`
- Login with unknown username or wrong password: `Invalid username or password.`
- Registration username shorter than 3 characters: `Username must be at least 3 characters.`
- Registration password shorter than 4 characters: `Password must be at least 4 characters.`
- Registration confirmation mismatch: `Passwords do not match.`
- Product add with blank fields, non-positive price, or negative stock: `Please fill all fields correctly. Price must be greater than 0.`
- Product search with ID `0` or a negative value: `Product ID must be a positive number.`
- Sale with invalid product, quantity, or date: `Please fill all fields correctly.`
- Sale quantity greater than stock: `Not enough stock! Available: [stock] units of '[product]'.`
- Profile empty full name: `Full name cannot be empty.`
- Profile wrong current password: `Current password is incorrect.`
- Report date with no sales: `No sales were recorded on [date]. The table below shows current inventory levels only.`

### 4.5 Database reset and test-order instructions

For a deterministic run, import `inventory_db.sql` into a clean `inventory_db` database. It creates the tables `users`, `products`, `reports`, and `sales`, and seeds the two accounts and eight products listed earlier. The seeded password hash corresponds to `password`.

Run tests in this order when possible:

1. **Tests 1–3:** public authentication/registration checks; no intended database mutation.
2. **Tests 4–5:** admin product insertion and search. Test 4 intentionally adds `Test Wireless Charger`.
3. **Test 6:** regular-user authorization; no intended database mutation.
4. **Test 7:** valid sale; intentionally changes stock and creates a sales/report record.
5. **Test 8:** use a fresh database or a product whose known stock is still `2`; it must run before that product is sold out.
6. **Test 9:** profile name is intentionally changed; restore the name after evidence capture if the environment is shared.
7. **Test 10:** depends on the sale created by Test 7; use the exact sale date shown in the sale record.

Do not call a test **PASS** merely because the page loads. The evidence must show the specific message, row, value, redirect, or unchanged state described in the test.

## 5. Assessment-table column meanings

| Column | What to write | Quality requirement |
|---|---|---|
| **#** | Test number from 1 to 10 | Keep numbering consistent with the screenshots |
| **Purpose** | The function or characteristic being tested | State the feature clearly |
| **Description** | Exact actions, sequence, and conditions | Include page, account, input, and submit action |
| **Type of validation** | For example: functional, boundary, invalid-input, security/access-control, integration, calculation | Use a specific validation type |
| **Test data** | Account, product, dates, quantities, and values used | Record exact values, not only “valid data” |
| **Expected result** | Correct outcome before running the test | Include messages, redirects, displayed values, and database-visible effects |
| **Actual result** | What the application actually displayed or changed | End with `PASS` or `FAIL`; include the screenshot reference |
| **Evidence** | Screenshot of the executed test | Show the relevant page and result message/value clearly |

The supplied example has six visible columns. Because the marking criteria also require **Evidence**, add an evidence reference inside the Actual Result cell or add an **Evidence** column immediately after Actual Result.

The **Code/screen under test** field added to each test below is an execution aid for the coding agent. It does not have to become a separate column in the submitted assignment table, but it explains exactly where the behaviour is implemented and what screen must be visible in the evidence.

---

## 6. Test-environment preparation

### 6.1 Start the system

1. Install/start **XAMPP**.
2. Start **Apache** and **MySQL**.
3. Copy the project folder into the XAMPP web root, for example:
   - Windows: `C:\xampp\htdocs\inventory\`
   - Linux: `/opt/lampp/htdocs/inventory/`
4. Open phpMyAdmin and execute `inventory_db.sql` in a clean database.
5. Open the application at `http://localhost/inventory/`.
6. Use a current Chrome or Edge browser and browser zoom around 100% so screenshots are readable.

### 6.2 Seed accounts supplied by the project

| Account | Username | Password | Role | Use |
|---|---|---|---|---|
| Administrator | `admin` | `password` | admin | Product management, user management |
| Regular user | `user` | `password` | user | Normal employee workflow and access-control test |

### 6.3 Seed products supplied by the project

The clean database should contain these products. Record the Product ID displayed by the application because auto-increment IDs can differ if the database was previously used.

| Product | Category | Unit price | Initial stock | Useful tests |
|---|---:|---:|---:|---|
| Samsung TV 55\" | Television | 499.99 | 15 | Search, sale |
| Sony Headphones WH-1000 | Audio | 299.99 | 30 | Product/sale |
| iPhone 15 Case | Accessories | 19.99 | 50 | Product/sale |
| Logitech Keyboard K380 | Peripherals | 49.99 | 20 | Product/sale |
| USB-C Hub 7-in-1 | Accessories | 34.99 | 4 | Low-stock boundary |
| Coca-Cola 2L | Soda | 2.49 | 100 | Product/sale |
| HDMI Cable 2m | Accessories | 8.99 | 60 | Product/sale |
| Wireless Mouse | Peripherals | 25.99 | 2 | Low-stock boundary |

### 6.4 Test-data control rules

- Run tests 1–6 before tests that alter inventory or passwords where possible.
- Test 7 changes product stock and creates a sale. Test 8 must use a product with enough remaining stock and deliberately exceed it.
- If the tests are repeated, restore the database from `inventory_db.sql` or use a fresh test database so previous sales and registered usernames do not change the results.
- For test 3, use a unique username such as `testuser_20261008_01` so the test can be repeated.
- For test 9, use a temporary profile name and restore it afterward if the assignment environment is shared.

---

## 7. Ten tests to enter in the assignment table

### Test 1 — Valid administrator login

| Field | Content |
|---|---|
| **Purpose** | Verify that a registered administrator can sign in and access the protected system. |
| **Code/screen under test** | Screen: `login.php` before submission and `index.php` after submission. Code: `login.php` handles the POST and redirect; `auth.php` function `loginUser()` verifies the password, regenerates the session ID, and stores the user role; `index.php` is the protected destination. Evidence target: the Dashboard heading, summary cards, signed-in name, and administrator-only **Users** link. |
| **Description** | 1. Open the login page. 2. Confirm the Username and Password fields are visible. 3. Enter `admin` in Username. 4. Enter `password` in Password. 5. Click **Sign In**. 6. Wait for the dashboard to load. 7. Check the URL, dashboard heading, administrator name, and Users link. |
| **Type of validation** | Functional validation; positive/normal input; authentication and session validation. |
| **Test data** | Username: `admin`; password: `password`. |
| **Expected result** | Login succeeds and the user is redirected to `index.php` (Dashboard). The dashboard is displayed and the navigation includes the administrator-only **Users** link. |
| **Actual result** | Fill after execution: record redirect, dashboard visibility, displayed role/navigation, PASS or FAIL, and screenshot filename. |
| **Evidence screenshot** | Capture the dashboard after login with the navbar and administrator name/Users link visible. Suggested filename: `TC01_admin_login.png`. |

### Test 2 — Invalid login credentials

| Field | Content |
|---|---|
| **Purpose** | Verify that invalid credentials are rejected without creating an authenticated session. |
| **Code/screen under test** | Screen: `login.php`. Code: `login.php` validates that fields are non-empty and calls `auth.php` function `loginUser()`; the function returns **“Invalid username or password.”** when the account/password check fails. Evidence target: login form, entered username, and visible error alert, with no dashboard. |
| **Description** | 1. Log out if a session is active. 2. Open the login page. 3. Enter `admin` as the username. 4. Enter `wrong-password-123` as the password. 5. Click **Sign In**. 6. Wait for the response. 7. Confirm that the page remains on login and inspect the error alert. |
| **Type of validation** | Invalid-input validation; negative authentication/security test. |
| **Test data** | Existing username: `admin`; incorrect password: `wrong-password-123`. |
| **Expected result** | The user remains on the login page. The error message **“Invalid username or password.”** is displayed. The dashboard is not opened. |
| **Actual result** | Fill after execution with the exact displayed message and PASS or FAIL. |
| **Evidence screenshot** | Capture the login form and visible error alert. Suggested filename: `TC02_invalid_login.png`. |

### Test 3 — Registration boundary and password confirmation

| Field | Content |
|---|---|
| **Purpose** | Verify that registration validates minimum username length and matching passwords before creating an account. |
| **Code/screen under test** | Screen: `register.php`. Code: the registration POST block checks empty fields, `strlen($username) < 3`, `strlen($password) < 4`, and password confirmation before calling `auth.php` function `registerUser()`. Evidence target: registration form and the validation alert. |
| **Description** | 1. Open the login page. 2. Click **Create one**. 3. Enter `Boundary Test User` in Full Name. 4. Enter the two-character username `ab`. 5. Enter `pass1234` as the password. 6. Enter `different123` as the confirmation. 7. Click **Create Account**. 8. Check which validation message is displayed and confirm no success message appears. |
| **Type of validation** | Boundary validation and invalid-input validation. |
| **Test data** | Full name: `Boundary Test User`; username: `ab`; password: `pass1234`; confirmation: `different123`. |
| **Expected result** | Registration is rejected and no account is created. The application displays the first validation error, expected to be **“Username must be at least 3 characters.”** because the username is below the minimum boundary. |
| **Actual result** | Record the exact alert, whether the page stayed on registration, PASS or FAIL, and screenshot filename. |
| **Evidence screenshot** | Capture the filled registration form and validation message. Suggested filename: `TC03_registration_boundary.png`. |

### Test 4 — Administrator adds a product with normal data

| Field | Content |
|---|---|
| **Purpose** | Verify that an administrator can create a new product and that it appears in the product list with the correct values. |
| **Code/screen under test** | Screen: `products.php`, administrator **Add New Product** form and product table. Code: the `action=add` branch validates the submitted values and executes the `INSERT INTO products` query; `bubbleSort()` in `functions.php` controls the displayed alphabetical order. Evidence target: success alert and the newly inserted product row showing name, category, price, and stock. |
| **Description** | 1. Sign in as `admin`. 2. Open **Products** from the navbar. 3. Confirm the Add New Product form is visible. 4. Enter `Test Wireless Charger` as the product name. 5. Enter `Accessories` as the category. 6. Enter `29.99` as the unit price. 7. Enter `10` as the stock quantity. 8. Click **Add Product**. 9. Inspect the success alert and locate the new row in the product table. |
| **Type of validation** | Functional CRUD validation; positive/normal input; database-to-interface integration. |
| **Test data** | Name: `Test Wireless Charger`; category: `Accessories`; price: `29.99`; stock: `10`. |
| **Expected result** | A success alert such as **“Product 'Test Wireless Charger' added successfully!”** appears. The new product is visible in the product table with price `$29.99` and stock `10`, shown as in stock. |
| **Actual result** | Record the success message and the values visible in the product table, then mark PASS or FAIL. |
| **Evidence screenshot** | Capture the success alert and the new product row in the Products table. Suggested filename: `TC04_add_product.png`. |

### Test 5 — Product ID search, including a boundary value

| Field | Content |
|---|---|
| **Purpose** | Verify that product search returns an existing product and rejects the non-positive ID boundary. |
| **Code/screen under test** | Screen: `products.php`, Product ID search box and Search Result panel. Code: the `searchID` GET branch rejects IDs `<= 0`, loads products ordered by `ProductID`, and calls `functions.php` function `binarySearch()`. Evidence target: the valid Search Result and the `Product ID must be a positive number.` alert for `0`. |
| **Description** | 1. Open **Products** while logged in. 2. Find the displayed Product ID for `Samsung TV 55\"`. 3. Enter that ID into Search by Product ID. 4. Click **Search** and verify the matching result. 5. Enter `0` in the same field. 6. Click **Search** again. 7. Verify that the positive-number validation appears and no product result is shown. |
| **Type of validation** | Functional search validation plus boundary/invalid-input validation. |
| **Test data** | Valid Product ID: the displayed ID for `Samsung TV 55\"`; boundary ID: `0`. |
| **Expected result** | For the valid ID, a **Search Result** row shows the matching product and a message similar to **“Product found using Binary Search”**. For `0`, the application displays **“Product ID must be a positive number.”** and does not return a product. |
| **Actual result** | Record both observed outcomes and mark PASS only if both behave as expected. |
| **Evidence screenshot** | Use one screenshot showing the valid search result and one showing the boundary error, or combine both in a clearly labelled evidence image. Suggested filenames: `TC05_search_valid.png`, `TC05_search_zero.png`. |

### Test 6 — Regular-user access control

| Field | Content |
|---|---|
| **Purpose** | Verify that a regular user can use permitted pages but cannot access administrator-only user management. |
| **Code/screen under test** | Screens: regular-user `index.php` navbar and direct `users.php` request. Code: `auth.php` functions `isAdmin()` and `requireAdmin()` enforce the role; `index.php` and other navigation templates hide the Users link when the role is not admin; `users.php` calls `requireAdmin()` before rendering. Evidence target: dashboard without Users and the redirected dashboard after opening `users.php`. |
| **Description** | 1. Log out from the administrator account. 2. Sign in with username `user` and password `password`. 3. Inspect the navbar and confirm that **Users** is absent. 4. In the address bar, navigate directly to `users.php`. 5. Wait for the redirect. 6. Confirm the final page is the dashboard and that no user-management controls are available. |
| **Type of validation** | Security validation; role-based authorization and negative access test. |
| **Test data** | Regular account: `user` / `password`; protected URL: `users.php`. |
| **Expected result** | The regular user can reach the dashboard, but the **Users** link is hidden. Direct navigation to `users.php` is blocked and redirects to `index.php` (Dashboard). No user-management table or delete/role controls are exposed. |
| **Actual result** | Record the navbar and redirect observed, then mark PASS or FAIL. |
| **Evidence screenshot** | Capture the regular-user dashboard/navbar without Users, plus the final page after direct `users.php` access. Suggested filenames: `TC06_user_access.png`, `TC06_users_blocked.png`. |

### Test 7 — Record a normal sale and verify stock/revenue calculation

| Field | Content |
|---|---|
| **Purpose** | Verify that a valid sale calculates revenue, records the sale, reduces stock, and updates the sales history. |
| **Code/screen under test** | Screen: `sales.php`, New Sale form, JavaScript preview, success alert, and Sales History. Code: the `action=recordSale` branch validates product/quantity/date, checks stock, creates or reuses a daily report, inserts into `sales`, and updates `products.StockQuantity`; the page JavaScript functions `updatePrice()` and `calculatePreview()` calculate the visible preview. Evidence target: preview before submission and success/history/stock result after submission. |
| **Description** | 1. Sign in as `admin` or `user`. 2. Open **Sales**. 3. Select `Samsung TV 55\"` or another product with at least five units available. 4. Enter quantity `2`. 5. Keep the pre-filled current date. 6. Confirm the live preview shows the unit price and calculated revenue. 7. Capture the preview. 8. Click **Record Sale**. 9. Inspect the success alert, new stock value, and newest Sales History row. |
| **Type of validation** | Functional transaction/integration validation; normal input; calculation and state-change validation. |
| **Test data** | Product: `Samsung TV 55\"`; unit price: `$499.99`; quantity: `2`; date: current date. Expected revenue: `$999.98`. |
| **Expected result** | The preview shows unit price `$499.99` and estimated revenue `$999.98`. After submission, a success message reports 2 units sold and the new stock (initially 15, expected 13 if untouched). Sales History contains the new sale with revenue `$999.98`. |
| **Actual result** | Record preview, success message, new stock, sales-history row, PASS or FAIL, and screenshot filename. |
| **Evidence screenshot** | Capture the preview before submitting and the success message/history after submitting. Suggested filenames: `TC07_sale_preview.png`, `TC07_sale_recorded.png`. |

### Test 8 — Prevent overselling beyond available stock

| Field | Content |
|---|---|
| **Purpose** | Verify that the system prevents a sale quantity greater than the product’s available stock. |
| **Code/screen under test** | Screen: `sales.php`, New Sale form and error alert. Code: the `recordSale` branch queries the selected product stock and checks `StockQuantity < quantitySold` before creating a report, inserting a sale, or updating stock. Evidence target: selected product and attempted quantity plus the **Not enough stock!** alert; verify the history and stock state are unchanged. |
| **Description** | 1. Open **Sales** on a fresh or restored database. 2. Select `Wireless Mouse` and note that available stock is `2`. 3. Enter quantity `3`, which is one greater than available stock. 4. Keep the current date. 5. Click **Record Sale**. 6. Inspect the error alert. 7. Confirm that no new sale appears in Sales History and that the product stock has not decreased. |
| **Type of validation** | Boundary/invalid-input validation; business-rule and data-integrity test. |
| **Test data** | Product: `Wireless Mouse`; available stock: `2`; attempted quantity: `3`; date: current date. |
| **Expected result** | The sale is rejected. The application displays a message similar to **“Not enough stock! Available: 2 units of 'Wireless Mouse'.”** No new sales-history row is created and the product stock remains `2`. |
| **Actual result** | Record the exact message and verify no stock/sale change, then mark PASS or FAIL. |
| **Evidence screenshot** | Capture the selected product/quantity and the visible “Not enough stock” alert. Suggested filename: `TC08_overselling_blocked.png`. |

### Test 9 — Profile update and password validation

| Field | Content |
|---|---|
| **Purpose** | Verify that an authenticated user can update the profile name and that an incorrect current password is rejected when changing the password. |
| **Code/screen under test** | Screen: `profile.php`, Edit Profile form, Account Information panel, and Change Password form. Code: the `updateProfile` branch updates `users.FullName` and `$_SESSION['full_name']`; the `changePassword` branch validates fields and length, then uses `password_verify()` before updating the hash. Evidence target: successful profile message/name and the incorrect-current-password alert. |
| **Description** | 1. Open **Profile** while authenticated. 2. Replace Full Name with `Updated Test Name`. 3. Click **Update Profile**. 4. Confirm the success alert and updated name. 5. In Change Password, enter `wrong-current` as Current Password. 6. Enter `newpass123` in both new-password fields. 7. Click **Change Password**. 8. Confirm that the incorrect-current-password error appears and that the password is not changed. |
| **Type of validation** | Functional profile validation plus invalid security/password validation. |
| **Test data** | New full name: `Updated Test Name`; incorrect current password: `wrong-current`; new password: `newpass123`; confirmation: `newpass123`. |
| **Expected result** | The profile update succeeds and shows **“Profile updated successfully!”**; the name is updated in the profile/navigation. The password change is rejected with **“Current password is incorrect.”** and the old password remains valid. |
| **Actual result** | Record both messages and the displayed updated name, then mark PASS or FAIL. |
| **Evidence screenshot** | Capture the successful profile update and the incorrect-current-password error. Suggested filenames: `TC09_profile_update.png`, `TC09_password_rejected.png`. |

### Test 10 — Daily report totals and date filtering

| Field | Content |
|---|---|
| **Purpose** | Verify that the Reports page filters by date and displays consistent units sold, remaining stock, revenue, product summaries, and totals after a recorded sale. |
| **Code/screen under test** | Screen: `reports.php`, date form, summary cards, product summary table, and totals row. Code: `reports.php` reads the GET `date`, calls `functions.php` function `buildReportMatrix()`, aggregates totals, and sorts rows by revenue; `buildReportMatrix()` queries products and sales for the selected date. Evidence target: sale-date row/totals and the no-sales warning/zero totals for the second date. |
| **Description** | 1. Complete Test 7 or use an existing sale. 2. Open **Reports**. 3. Enter the exact date of the sale in Report Date. 4. Click **Generate Report**. 5. Locate the Samsung product row and totals row. 6. Compare units sold and revenue with Test 7. 7. Select a different date with no sales, such as `01/01/2020`. 8. Click **Generate Report** again. 9. Verify the no-sales warning and zero sales totals while inventory rows remain visible. |
| **Type of validation** | Functional report validation; date-filtering, aggregation/calculation, and integration validation. |
| **Test data** | Sale date from Test 7; Samsung sale quantity `2`; unit price `$499.99`; expected Samsung revenue `$999.98`; no-sales date: a different date such as `01/01/2020`. |
| **Expected result** | For the sale date, the Samsung row shows 2 units sold and `$999.98` revenue; the totals row includes those values. For the no-sales date, the warning **“No sales were recorded”** appears and total units/revenue are zero, while current inventory levels remain visible. |
| **Actual result** | Record both report views, observed totals, warning, and PASS or FAIL. |
| **Evidence screenshot** | Capture the report for the sale date with the Samsung row/totals and the no-sales-date warning. Suggested filenames: `TC10_report_with_sales.png`, `TC10_report_no_sales.png`. |

---

## 8. Test-to-code traceability matrix

Use this matrix when the coding agent needs to locate the implementation before running a test. The screen/page is the primary evidence location; the listed file/function is the source-code location to inspect if the result differs from the expected behaviour.

| Test | Primary screen/page | Main source file(s) | Key implementation to inspect |
|---:|---|---|---|
| 1 | `login.php` → `index.php` | `login.php`, `auth.php`, `index.php` | `loginUser()`, session creation, `requireLogin()`, admin navbar |
| 2 | `login.php` | `login.php`, `auth.php` | Empty-field validation and invalid-credential return |
| 3 | `register.php` | `register.php`, `auth.php` | Minimum lengths, confirmation check, `registerUser()` |
| 4 | `products.php` | `products.php`, `functions.php` | Add POST branch, products INSERT, `bubbleSort()` |
| 5 | `products.php` | `products.php`, `functions.php` | `searchID` branch and `binarySearch()` |
| 6 | `index.php`, direct `users.php` | `auth.php`, `index.php`, `users.php` | `isAdmin()`, `requireAdmin()`, hidden Users link |
| 7 | `sales.php` | `sales.php`, `functions.php`, database tables | Preview JavaScript, stock check, sales INSERT, stock UPDATE |
| 8 | `sales.php` | `sales.php` | Overselling condition before any transaction changes |
| 9 | `profile.php` | `profile.php`, `auth.php` | Profile update, `password_verify()`, password hash update |
| 10 | `reports.php` | `reports.php`, `functions.php` | Date filter, `buildReportMatrix()`, totals and revenue sorting |

## 9. Final results table template

Copy the following table into the assignment document. Keep the wording in the first six columns, then replace each Actual Result entry with observed evidence.

| # | Purpose | Description | Type of validation | Test data | Expected result | Actual result / Evidence |
|---:|---|---|---|---|---|---|
| 1 | Verify valid administrator login. | Sign in as `admin` with `password` and open the dashboard. | Functional; positive authentication. | `admin` / `password` | Dashboard opens and Users link is visible. | Login with `admin` / `password` redirected to `index.php`. Dashboard heading, summary cards (8 products, 0 units sold today, 2 low-stock items, $19,479.19 value) and the administrator-only **Users** link were visible; navbar showed “Administrator”. **PASS.** Evidence: `evidence/TC01_admin_login.png` |
| 2 | Verify invalid credentials are rejected. | Submit `admin` with `wrong-password-123`. | Invalid-input/security. | Existing username + wrong password | Login page remains and “Invalid username or password.” appears. | Submitting `admin` / `wrong-password-123` kept the browser on `login.php`; red alert “Invalid username or password.” was shown, username field retained `admin`, password cleared, no dashboard. **PASS.** Evidence: `evidence/TC02_invalid_login.png` |
| 3 | Verify registration boundary validation. | Submit 2-character username and mismatched passwords. | Boundary; invalid input. | `ab`, `pass1234`, `different123` | Registration is rejected with minimum-username validation. | Submitting `ab` with mismatched passwords stayed on `register.php` with alert “Username must be at least 3 characters.” (username check fires before the mismatch check); no account created. **PASS.** Evidence: `evidence/TC03_registration_boundary.png` |
| 4 | Verify admin product creation. | Add Test Wireless Charger at `$29.99`, stock `10`. | Functional CRUD; normal input. | Product name/category/price/stock above | Success alert and correct row appear. | Success alert “Product 'Test Wireless Charger' added successfully!” appeared. The Products table (now 9 total) lists ID 9, Test Wireless Charger, Accessories, $29.99, stock 10, in A–Z order. **PASS.** Evidence: `evidence/TC04_add_product.png` |
| 5 | Verify valid and boundary Product ID search. | Search an existing ID, then search ID `0`. | Functional search; boundary. | Existing ID and `0` | Existing item found; `0` produces positive-number error. | Search for ID `1` (Samsung TV 55") showed “Product found using Binary Search (index: 0)” and a Search Result row (Samsung TV 55", Television, $499.99, stock 15). Search for `0` showed “Product ID must be a positive number.” and no result panel. **PASS.** Evidence: `evidence/TC05_search_valid.png`, `evidence/TC05_search_zero.png` |
| 6 | Verify regular-user authorization. | Sign in as `user`; open `users.php` directly. | Security/access-control. | `user` / `password`; `users.php` | Users link hidden; direct page redirects to dashboard. | Signed in as `user` / `password` (“Regular User”): navbar shows Dashboard, Products, Sales, Reports, Profile with **no Users link**. Opening `users.php` directly redirected to `index.php` (dashboard), no user list shown. **PASS.** Evidence: `evidence/TC06_user_access.png`, `evidence/TC06_users_blocked.png` |
| 7 | Verify valid sale processing. | Sell 2 Samsung TVs and inspect preview/history. | Functional integration/calculation. | Price `$499.99`, quantity `2`, revenue `$999.98` | Preview/revenue correct; sale stored; stock decreases by 2. | Samsung TV 55", qty 2, date 08/10/2026: preview showed Unit Price $499.99 and Estimated Revenue $999.98. After **Record Sale**: alert “Sale recorded! 2 unit(s) … New stock: 13 units.”, Sales History row #1 (qty 2, $499.99, $999.98, 08/10/2026); stock fell 15 → 13 (also confirmed in the database). **PASS.** Defect note: the alert prints the product name as `'Samsung TV 55&quot;'` (HTML entity shown literally – the message is escaped twice); cosmetic, does not affect the sale. Evidence: `evidence/TC07_sale_preview.png`, `evidence/TC07_sale_recorded.png` |
| 8 | Verify overselling is prevented. | Sell 3 Wireless Mice when stock is 2. | Boundary/business-rule/data integrity. | Stock `2`; attempted quantity `3` | “Not enough stock” error; no sale; stock unchanged. | Wireless Mouse (stock 2), qty 3: the browser first blocked submission with the native tooltip “Value must be less than or equal to 2.” (the page sets `max` = stock; `evidence/TC08_client_validation.png`). With browser validation bypassed (`form.noValidate`, no source change) the server returned “Not enough stock! Available: 2 units of 'Wireless Mouse'.”; no sale row was added (history still only #1) and mouse stock stayed 2. **PASS** (two-layer protection). Evidence: `evidence/TC08_overselling_blocked.png`, `evidence/TC08_client_validation.png` |
| 9 | Verify profile update and password protection. | Update name; attempt password change with wrong current password. | Functional; invalid security validation. | `Updated Test Name`; wrong current password | Name updates; password change is rejected. | Full name changed to `Updated Test Name`: alert “Profile updated successfully!”, Account Information and navbar both show the new name. Password change with wrong current password (`wrong-current`) was rejected with “Current password is incorrect.”; stored password unchanged. Name restored to “Administrator” after capture. **PASS.** Evidence: `evidence/TC09_profile_update.png`, `evidence/TC09_password_rejected.png` |
| 10 | Verify date-filtered report totals. | Generate report for sale date and a no-sales date. | Functional aggregation/date filtering. | Sale date, 2 units, `$999.98`; `01/01/2020` | Correct row/totals for sale date; no-sales warning and zero totals for other date. | Report for 08/10/2026 (Test 7 sale date): Samsung TV 55" row shows 2 units sold, 13 remaining, $999.98; totals 2 units / $999.98 / 289 in stock / 9 products. Report for 01/01/2020 showed warning “No sales were recorded on 01/01/2020. The table below shows current inventory levels only.” with 0 units and $0.00 totals. **PASS.** Evidence: `evidence/TC10_report_with_sales.png`, `evidence/TC10_report_no_sales.png` |

---

## 10. Screenshot/evidence standard

Every screenshot should:

- show the application page, not only a cropped alert;
- include the relevant input or selected record where useful;
- show the success/error message, result row, redirect destination, or displayed total;
- be readable at normal document zoom;
- have a filename that matches the test number;
- avoid exposing unrelated personal information;
- be inserted next to or directly below the corresponding test row.

For tests with a before-and-after state, use two screenshots or one composite image with labels **Before** and **After**. Do not claim PASS without visible evidence.

## 11. Completion checklist

- [x] XAMPP Apache and MySQL were running. *(Fedora: PHP 8.4 built-in web server on `localhost:8080` + MariaDB 10.11 used instead of XAMPP; same PHP/MySQL stack.)*
- [x] The database was reset or the test-data state was recorded. *(Dropped and re-imported `inventory_db.sql` immediately before the run; tests ran in document order.)*
- [x] All 10 tests were executed.
- [x] Normal, boundary, and invalid test data were included.
- [x] Every Actual Result is based on observation.
- [x] Every test has PASS or FAIL.
- [x] Every test has at least one clear screenshot.
- [x] The final table includes Purpose, Description, Type of validation, Test data, Expected result, Actual result, and Evidence.
- [x] Any failed test includes a short explanation and, if appropriate, a defect note. *(No test failed; one cosmetic defect noted under Test 7.)*
