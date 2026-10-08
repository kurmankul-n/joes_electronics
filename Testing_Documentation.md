# Joe’s Electronics Inventory Management System — Testing Documentation

**Assignment:** Project – Testing (SAU2)  
**System:** Joe’s Electronics Inventory Management System  
**Application type:** PHP/MySQL web application running through XAMPP  
**Source repository:** [https://github.com/kurmankul-n/joes_electronics](https://github.com/kurmankul-n/joes_electronics)  
**Required evidence:** One clear screenshot for each completed test

## 1. Purpose of this document

This document is an execution guide for completing the SAU2 testing table. It contains **15 test cases** for the Joe’s Electronics system. Each test uses one type of test data from the course notes: normal, borderline (extreme) or invalid. All tests are black box tests: the tester enters inputs and compares the outputs with the expected result.

The coding agent should:

1. Set up the application and database.
2. Execute the fifteen tests in the order shown.
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
7. Save a screenshot after the result is visible using the required filename, for example `TC10_sale_recorded.png`. Use a full-page screenshot when the relevant result is not visible in the viewport.
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
| 1 | `login.php` POST → `loginUser()` in `auth.php` → `password_verify()` succeeds → session set → redirect to `index.php`. | Session created; database unchanged. |
| 2 | `login.php` POST → `loginUser()` fails the password check → error string rendered. | No session; database unchanged. |
| 3 | `register.php` checks empty fields, username length (≥3), password length (≥4), password match → `registerUser()`. | One user row (`abc`) inserted. |
| 4 | `register.php` stops at `strlen($username) < 3`. | No user row inserted. |
| 5 | `products.php` `action=add` → validates name/category/price/stock → `INSERT INTO products` → `bubbleSort()`. | One product row inserted. |
| 6 | Same branch as Test 5; `$unitPrice <= 0 \|\| $stockQuantity < 0` is false for `0.01` and `0`. | One product row inserted. |
| 7 | `products.php` GET `searchID` → `<= 0` check passes for `1` → `binarySearch()`. | No database change. |
| 8 | `products.php` GET `searchID` → `<= 0` check rejects `0`. | No database change. |
| 9 | `users.php` calls `requireAdmin()` → non-admin redirect to `index.php`; navbar hides Users when `isAdmin()` is false. | No database change. |
| 10 | `sales.php` validates input → checks stock → creates/reuses `reports` row → inserts `sales` row → updates stock. | One sale; Samsung stock 15 → 13. |
| 11 | Browser `max` = stock blocks the form; server check `StockQuantity < quantitySold` rejects it. | No sale; stock unchanged. |
| 12 | `StockQuantity < quantitySold` is false for 2 of 2 → sale inserted → stock 0 → product leaves the sale list (`StockQuantity > 0`). | One sale; Mouse stock 2 → 0. |
| 13 | `profile.php` `updateProfile` writes `FullName` and the session name. | Full name changed, then restored. |
| 14 | `profile.php` `changePassword` → `password_verify()` fails. | Password hash unchanged. |
| 15 | `reports.php` GET `date` → `buildReportMatrix()` → totals → sort by revenue. | No database change. |

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

Run tests in this order:

1. **Tests 1–4:** login and registration. Test 3 creates the user `abc`.
2. **Tests 5–8:** products and search. Tests 5 and 6 add `Test Wireless Charger` and `Test Cable`.
3. **Test 9:** regular-user access; no database change.
4. **Test 10:** sells 2 Samsung TV 55\" (stock 15 → 13).
5. **Tests 11–12:** need Wireless Mouse stock `2`. Test 11 must run before Test 12, which sells the last 2 units.
6. **Tests 13–14:** profile. Test 13 restores the original name.
7. **Test 15:** needs the sales from Tests 10 and 12; use the sale date shown in Sales History.

Do not call a test **PASS** merely because the page loads. The evidence must show the specific message, row, value, redirect, or unchanged state described in the test.

## 5. Assessment-table column meanings

| Column | What to write | Quality requirement |
|---|---|---|
| **#** | Test number from 1 to 15 | Keep numbering consistent with the screenshots |
| **Purpose** | The function or characteristic being tested | State the feature clearly |
| **Description** | Exact actions, sequence, and conditions | Include page, account, input, and submit action |
| **Type of validation** | The type of test data from the course notes: **normal** (expected input, accepted), **borderline/extreme** (the last value still accepted at a limit, for example 1 and 10 in a 1–10 range) or **invalid** (wrong type, characters not allowed, or outside the limits; rejected with an error message). Add the strategy: black box. | One data type per test |
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

- Reset the database from `inventory_db.sql` before each full run, so earlier sales and the user `abc` do not change the results.
- Test 10 changes Samsung stock. Tests 11 and 12 need the seed Wireless Mouse stock of `2`.
- Test 13 changes the admin full name and restores it afterward.

---

## 7. Fifteen tests and their data types

The course notes name three types of test data:

- **Normal:** data the program expects. It runs without errors.
- **Borderline (extreme):** data at the edge of what the program accepts. It still runs without errors.
- **Invalid:** data the program must reject with an error message, because it has the wrong type, uses characters that are not allowed, or falls outside the limits.

The limits in the source code are: username at least 3 characters and password at least 4 (`register.php`), price above 0 and stock 0 or more (`products.php`), Product ID 1 or more (`products.php`), and sale quantity from 1 up to the stock (`sales.php`).

| # | Data type | Test | Why this data type |
|---:|---|---|---|
| 1 | Normal | Admin login with `admin` / `password` | Correct username and password |
| 2 | Invalid | Login with `admin` / `wrong-password-123` | Password does not match the account |
| 3 | Borderline | Register `abc` / `pass` | 3 and 4 characters, the shortest the app accepts |
| 4 | Invalid | Register `ab` / `pass1234` | 2 characters, one below the minimum |
| 5 | Normal | Add Test Wireless Charger, `29.99`, stock `10` | Typical product values |
| 6 | Borderline | Add Test Cable, `0.01`, stock `0` | Lowest accepted price and stock |
| 7 | Borderline | Search Product ID `1` | Lowest accepted ID |
| 8 | Invalid | Search Product ID `0` | One below the lowest ID |
| 9 | Invalid | `user` opens `/users.php` | Account without admin rights |
| 10 | Normal | Sell 2 Samsung TV 55\" (stock 15) | Quantity well inside the stock |
| 11 | Invalid | Sell 3 Wireless Mouse (stock 2) | One above the stock |
| 12 | Borderline | Sell 2 Wireless Mouse (stock 2) | Quantity equal to the stock, the highest accepted |
| 13 | Normal | Change full name to `Updated Test Name` | Typical name |
| 14 | Invalid | Change password with current password `wrong-current` | Current password is wrong |
| 15 | Normal | Reports for `08/10/2026` and `01/01/2020` | Valid dates, one with sales and one without |

Mix: 5 normal, 4 borderline, 6 invalid. Section 9 holds the full description, test data, expected and actual result for each test.

---

## 8. Test-to-code traceability matrix

| Test | Primary screen/page | Main source file(s) | Key implementation to inspect |
|---:|---|---|---|
| 1–2 | `login.php` → `index.php` | `login.php`, `auth.php` | `loginUser()`, `password_verify()`, session |
| 3–4 | `register.php` | `register.php`, `auth.php` | Length checks, `registerUser()` |
| 5–6 | `products.php` | `products.php`, `functions.php` | Add branch, `INSERT`, `bubbleSort()` |
| 7–8 | `products.php` | `products.php`, `functions.php` | `searchID` check, `binarySearch()` |
| 9 | `index.php`, `users.php` | `auth.php`, `users.php` | `isAdmin()`, `requireAdmin()` |
| 10–12 | `sales.php` | `sales.php` | Preview script, `max` attribute, stock check, sale `INSERT`, stock `UPDATE` |
| 13–14 | `profile.php` | `profile.php` | `updateProfile`, `changePassword`, `password_verify()` |
| 15 | `reports.php` | `reports.php`, `functions.php` | `buildReportMatrix()`, totals, revenue sort |

## 9. Final results table

Results of the 15 test cases, run on 08/10/2026. Each test uses one type of test data: normal, borderline or invalid.

| # | Purpose | Description | Type of validation | Test data | Expected result | Actual result / Evidence |
|---:|---|---|---|---|---|---|
| 1 | Check that the administrator can log in. | Pre: fresh database import.<br>1. Open `/login.php`.<br>2. Enter username `admin` and password `password`.<br>3. Click **Sign In**.<br>4. Look at the dashboard and the navbar. | Normal data; black box | Username `admin`; password `password` (the correct pair) | The dashboard opens and the navbar shows the Users link. | The browser went to `index.php`. The dashboard showed the summary cards and the **Users** link, and the navbar showed “Administrator”. **PASS.** Evidence: `evidence/TC01_admin_login.png` |
| 2 | Check that a wrong password is rejected. | 1. Open `/login.php`.<br>2. Enter username `admin` and password `wrong-password-123`.<br>3. Click **Sign In**.<br>4. Read the page and the alert. | Invalid data; black box | Username `admin` (exists); password `wrong-password-123` (does not match the account) | The login page stays open and shows “Invalid username or password.” | The browser stayed on `login.php` and showed a red alert, “Invalid username or password.” The username field kept `admin`, the password field was empty, and no dashboard opened. **PASS.** Evidence: `evidence/TC02_invalid_login.png` |
| 3 | Check that registration accepts the shortest allowed username and password. | 1. Open `/register.php`.<br>2. Enter full name `Boundary Test User`, username `abc`, and `pass` in both password fields.<br>3. Click **Create Account**.<br>4. Sign in with `abc` / `pass`. | Borderline data; black box | Username `abc` (3 characters, the minimum); password and confirm password `pass` (4 characters, the minimum) | The app accepts both values, shows “Account created. You can now sign in.”, and the new account can log in. | The page showed “Account created. You can now sign in.” Login with `abc` / `pass` opened the dashboard. **PASS.** Evidence: `evidence/TC03_register_borderline.png` |
| 4 | Check that registration rejects a username below the minimum length. | 1. Open `/register.php`.<br>2. Enter full name `Boundary Test User`, username `ab`, and `pass1234` in both password fields.<br>3. Click **Create Account**. | Invalid data; black box | Username `ab` (2 characters, one below the minimum of 3); password and confirm password `pass1234` | The app rejects the form, stays on `register.php` and shows “Username must be at least 3 characters.” No account is created. | The page stayed on `register.php` and showed “Username must be at least 3 characters.” The form kept `ab` and cleared both password fields. No account was created. **PASS.** Evidence: `evidence/TC04_register_invalid.png` |
| 5 | Check that an administrator can add a product. | Pre: logged in as `admin`.<br>1. Open **Products**.<br>2. Fill in the Add Product form with the test data.<br>3. Click **Add Product**.<br>4. Find the new row in the product table. | Normal data; black box | Name `Test Wireless Charger`; category `Accessories`; price `29.99`; stock `10` | The alert “Product 'Test Wireless Charger' added successfully!” appears, and the table shows a row with Accessories, $29.99 and stock 10. | The alert “Product 'Test Wireless Charger' added successfully!” appeared. The table lists ID 9, Test Wireless Charger, Accessories, $29.99, stock 10. **PASS.** Evidence: `evidence/TC05_add_product.png` |
| 6 | Check that the product form accepts the lowest allowed price and stock. | Pre: logged in as `admin`.<br>1. Open **Products**.<br>2. Enter name `Test Cable`, category `Accessories`, price `0.01` and stock `0`.<br>3. Click **Add Product**.<br>4. Find the new row in the product table. | Borderline data; black box | Name `Test Cable`; category `Accessories`; price `0.01` (lowest price above 0); stock `0` (lowest allowed stock) | The app accepts the product, shows “Product 'Test Cable' added successfully!” and lists it at $0.01 with stock 0. | The alert “Product 'Test Cable' added successfully!” appeared. The table (10 products) lists ID 10, Test Cable, Accessories, $0.01, with an **Out of Stock** badge for stock 0. **PASS.** Evidence: `evidence/TC06_product_borderline.png` |
| 7 | Check that Product ID search accepts the lowest valid ID. | Pre: logged in as `admin`, on **Products**.<br>1. Enter `1` in Search by Product ID.<br>2. Click **Search**.<br>3. Read the message and the Search Result panel. | Borderline data; black box | Product ID `1` (the lowest valid ID; the app rejects anything below 1) | The app finds Samsung TV 55" and shows “Product found using Binary Search”. | The page showed “Product found using Binary Search (index: 0)” and a Search Result row: ID 1, Samsung TV 55", Television, $499.99, stock 15. **PASS.** Evidence: `evidence/TC07_search_id1.png` |
| 8 | Check that Product ID search rejects an ID below 1. | Pre: logged in as `admin`, on **Products**.<br>1. Enter `0` in Search by Product ID.<br>2. Click **Search**. | Invalid data; black box | Product ID `0` (one below the lowest valid ID) | The app shows “Product ID must be a positive number.” and no Search Result. | The page showed “Product ID must be a positive number.” and no Search Result panel. **PASS.** Evidence: `evidence/TC08_search_id0.png` |
| 9 | Check that a regular user cannot open the Users page. | 1. Open `/login.php` and sign in as `user` / `password`.<br>2. Look for a Users link in the navbar.<br>3. Type `/users.php` in the address bar. | Invalid data (account without admin rights); black box | Username `user`; password `password` (role: user); URL `/users.php` | The navbar has no Users link, and `users.php` sends the user back to the dashboard. | The navbar showed “Regular User” with Dashboard, Products, Sales and Reports, and **no Users link**. Opening `users.php` redirected to `index.php`, and no user list appeared. **PASS.** Evidence: `evidence/TC09_users_blocked.png` |
| 10 | Check that a sale updates the preview, the history and the stock. | Pre: logged in as `admin`; Samsung TV 55" stock is 15.<br>1. Open **Sales**.<br>2. Select Samsung TV 55" (Stock: 15), enter quantity `2`, and keep today's date.<br>3. Read the preview box.<br>4. Click **Record Sale**.<br>5. Read the alert, the Sales History and the stock. | Normal data; black box | Product Samsung TV 55" (price `$499.99`, stock `15`); quantity `2`; date = today (08/10/2026); expected revenue `$999.98` | The preview shows $499.99 and $999.98, the sale appears in the history, and stock drops from 15 to 13. | The preview showed Unit Price $499.99 and Estimated Revenue $999.98. After **Record Sale** the alert said “Sale recorded! 2 unit(s) … New stock: 13 units.” Sales History gained row #1 (quantity 2, $499.99, $999.98, 08/10/2026). **PASS.** Defect: the alert prints the product name as `'Samsung TV 55&quot;'`. The app escapes the text twice, so the HTML entity appears as typed. The sale itself is correct. Evidence: `evidence/TC10_sale_preview.png`, `evidence/TC10_sale_recorded.png` |
| 11 | Check that the app refuses to sell more than the stock. | Pre: logged in as `admin`; Wireless Mouse stock is 2.<br>1. Open **Sales**.<br>2. Select Wireless Mouse (Stock: 2), enter quantity `3` and click **Record Sale**. The browser `max` check blocks the form first.<br>3. Turn off browser validation (`form.noValidate`, no source change) and submit again.<br>4. Read the message, the Sales History and the stock. | Invalid data; black box | Product Wireless Mouse (stock `2`); quantity `3` (one above the stock) | A “Not enough stock” error appears, no sale is recorded and the stock stays at 2. | The browser blocked the form first with the tooltip “Value must be less than or equal to 2.” because the page sets `max` to the stock value (`evidence/TC11_client_validation.png`). With browser validation off, the server answered “Not enough stock! Available: 2 units of 'Wireless Mouse'.” The history still held only sale #1, and the mouse stock stayed at 2. **PASS.** The browser and the server each stop overselling. Evidence: `evidence/TC11_overselling_blocked.png`, `evidence/TC11_client_validation.png` |
| 12 | Check that the app accepts a sale of the full remaining stock. | Pre: logged in as `admin`; Wireless Mouse stock is 2 (Test 11 did not change it).<br>1. Open **Sales**.<br>2. Select Wireless Mouse (Stock: 2) and enter quantity `2`.<br>3. Click **Record Sale**.<br>4. Read the alert, the Sales History and the product list. | Borderline data; black box | Product Wireless Mouse (stock `2`, price `$25.99`); quantity `2` (equal to the stock, the highest accepted) | The app records the sale, the stock drops to 0, and the mouse leaves the sale product list. | The alert said “Sale recorded! 2 unit(s) of 'Wireless Mouse' sold. New stock: 0 units.” Sales History gained row #2 (quantity 2, $25.99, $51.98, 08/10/2026). Wireless Mouse no longer appears in Select Product. **PASS.** Evidence: `evidence/TC12_sell_all_stock.png` |
| 13 | Check that a user can change the profile name. | Pre: logged in as `admin`.<br>1. Open **Profile**.<br>2. Change Full Name to `Updated Test Name`.<br>3. Click **Update Profile**.<br>4. Restore the original name. | Normal data; black box | Full name `Updated Test Name` | The app shows “Profile updated successfully!” and displays the new name. | The alert said “Profile updated successfully!”, and both Account Information and the navbar showed `Updated Test Name`. The test restored the name “Administrator” afterward. **PASS.** Evidence: `evidence/TC13_profile_update.png` |
| 14 | Check that a password change needs the correct current password. | Pre: logged in as `admin`.<br>1. Open **Profile**.<br>2. Under Change Password enter current password `wrong-current`, new password `newpass123` and confirmation `newpass123`.<br>3. Click **Change Password**. | Invalid data; black box | Current password `wrong-current` (wrong; the real one is `password`); new and confirm password `newpass123` | The app rejects the change with “Current password is incorrect.” and keeps the old password. | The page showed “Current password is incorrect.” The password did not change: Test 15 still logged in with `admin` / `password`. **PASS.** Evidence: `evidence/TC14_password_rejected.png` |
| 15 | Check that the daily report filters sales by date. | Pre: the sales from Tests 10 and 12 exist (run the tests in order).<br>1. Open **Reports**.<br>2. Set the date to `2026-10-08` (the sale date) and click **Generate Report**.<br>3. Set the date to `2020-01-01` and click **Generate Report**. | Normal data; black box | Sale date `08/10/2026`; date with no sales `01/01/2020` | 08/10/2026 lists Samsung TV 55" (2 units, $999.98) and Wireless Mouse (2 units, $51.98) with matching totals. 01/01/2020 shows a no-sales warning and zero totals. | The 08/10/2026 report showed Samsung TV 55" (2 sold, 13 left, $999.98) and Wireless Mouse (2 sold, 0 left, $51.98). Totals: 4 units, $1,051.96, 287 in stock, 10 products. The 01/01/2020 report showed “No sales were recorded on 01/01/2020. The table below shows current inventory levels only.” with 0 units and $0.00. **PASS.** Evidence: `evidence/TC15_report_with_sales.png`, `evidence/TC15_report_no_sales.png` |

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

- [x] XAMPP Apache and MySQL were running. *(Fedora: PHP 8.4 built-in web server on `localhost:8080` and MySQL used instead of XAMPP.)*
- [x] The database was reset or the test-data state was recorded. *(Dropped and re-imported `inventory_db.sql` immediately before the run; tests ran in document order.)*
- [x] All 15 tests were executed.
- [x] Normal, borderline and invalid test data were included (5 normal, 4 borderline, 6 invalid).
- [x] Every Actual Result is based on observation.
- [x] Every test has PASS or FAIL.
- [x] Every test has at least one clear screenshot.
- [x] The final table includes Purpose, Description, Type of validation, Test data, Expected result, Actual result, and Evidence.
- [x] Any failed test includes a short explanation and, if appropriate, a defect note. *(No test failed; one cosmetic defect noted under Test 10.)*
