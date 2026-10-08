import { test, expect, Page } from '@playwright/test';

// Order matters: TC10 changes Samsung stock, TC11/TC12 need the seed Wireless Mouse stock (2), TC15 uses the sales from TC10/TC12.
test.describe.configure({ mode: 'serial' });

const shot = (page: Page, name: string) =>
  page.screenshot({ path: `evidence/${name}.png`, fullPage: true });

async function login(page: Page, username: string, password: string) {
  await page.goto('/login.php');
  await page.locator('input[name="username"]').fill(username);
  await page.locator('input[name="password"]').fill(password);
  await page.getByRole('button', { name: 'Sign In' }).click();
}

const nav = (page: Page, name: string) =>
  page.getByRole('link', { name, exact: true }).first().click();

async function register(page: Page, username: string, password: string) {
  await page.goto('/register.php');
  await page.locator('input[name="fullName"]').fill('Boundary Test User');
  await page.locator('input[name="username"]').fill(username);
  await page.locator('input[name="password"]').fill(password);
  await page.locator('input[name="confirmPassword"]').fill(password);
  await page.getByRole('button', { name: 'Create Account' }).click();
}

async function addProduct(page: Page, name: string, category: string, price: string, stock: string) {
  await page.locator('input[name="productName"]').fill(name);
  await page.locator('input[name="category"]').fill(category);
  await page.locator('input[name="unitPrice"]').fill(price);
  await page.locator('input[name="stockQuantity"]').fill(stock);
  await page.getByRole('button', { name: 'Add Product' }).click();
}

async function search(page: Page, id: string) {
  await page.locator('input[name="searchID"]').fill(id);
  await page.getByRole('button', { name: 'Search' }).click();
}

let saleDate = '';

test('TC01 - Normal: valid administrator login', async ({ page }) => {
  await login(page, 'admin', 'password');
  await expect(page).toHaveURL(/index\.php/);
  await expect(page.getByRole('heading', { name: /Dashboard/ })).toBeVisible();
  await expect(page.getByRole('link', { name: 'Users', exact: true })).toBeVisible();
  await shot(page, 'TC01_admin_login');
});

test('TC02 - Invalid: wrong password', async ({ page }) => {
  await login(page, 'admin', 'wrong-password-123');
  await expect(page).toHaveURL(/login\.php/);
  await expect(page.locator('body')).toContainText('Invalid username or password.');
  await shot(page, 'TC02_invalid_login');
});

test('TC03 - Borderline: shortest allowed username and password', async ({ page }) => {
  await register(page, 'abc', 'pass');
  await expect(page.locator('body')).toContainText('Account created.');
  await shot(page, 'TC03_register_borderline');
  await login(page, 'abc', 'pass');
  await expect(page).toHaveURL(/index\.php/);
});

test('TC04 - Invalid: username one character too short', async ({ page }) => {
  await register(page, 'ab', 'pass1234');
  await expect(page).toHaveURL(/register\.php/);
  await expect(page.locator('body')).toContainText('Username must be at least 3 characters.');
  await shot(page, 'TC04_register_invalid');
});

test('TC05 - Normal: add a product', async ({ page }) => {
  await login(page, 'admin', 'password');
  await nav(page, 'Products');
  await addProduct(page, 'Test Wireless Charger', 'Accessories', '29.99', '10');
  await expect(page.locator('body')).toContainText("Product 'Test Wireless Charger' added successfully!");
  const row = page.locator('tr').filter({ hasText: 'Test Wireless Charger' }).first();
  await expect(row).toContainText('29.99');
  await row.scrollIntoViewIfNeeded();
  await shot(page, 'TC05_add_product');
});

test('TC06 - Borderline: lowest price and zero stock', async ({ page }) => {
  await login(page, 'admin', 'password');
  await nav(page, 'Products');
  await addProduct(page, 'Test Cable', 'Accessories', '0.01', '0');
  await expect(page.locator('body')).toContainText("Product 'Test Cable' added successfully!");
  const row = page.locator('tr').filter({ hasText: 'Test Cable' }).first();
  await expect(row).toContainText('0.01');
  await row.scrollIntoViewIfNeeded();
  await shot(page, 'TC06_product_borderline');
});

test('TC07 - Borderline: lowest valid product ID', async ({ page }) => {
  await login(page, 'admin', 'password');
  await nav(page, 'Products');
  await search(page, '1');
  await expect(page.locator('body')).toContainText('Product found using Binary Search');
  await expect(page.getByRole('heading', { name: 'Search Result' })).toBeVisible();
  await shot(page, 'TC07_search_id1');
});

test('TC08 - Invalid: product ID 0', async ({ page }) => {
  await login(page, 'admin', 'password');
  await nav(page, 'Products');
  await search(page, '0');
  await expect(page.locator('body')).toContainText('Product ID must be a positive number.');
  await shot(page, 'TC08_search_id0');
});

test('TC09 - Invalid: regular user opens the Users page', async ({ page }) => {
  await login(page, 'user', 'password');
  await expect(page).toHaveURL(/index\.php/);
  await expect(page.getByRole('link', { name: 'Users', exact: true })).toHaveCount(0);
  await page.goto('/users.php');
  await expect(page).toHaveURL(/index\.php/);
  await shot(page, 'TC09_users_blocked');
});

test('TC10 - Normal: record a sale', async ({ page }) => {
  await login(page, 'admin', 'password');
  await nav(page, 'Sales');
  await page.locator('#productSelect').selectOption({ label: 'Samsung TV 55" (Stock: 15)' });
  await page.locator('#quantityInput').fill('2');
  await page.locator('#quantityInput').blur(); // fires onchange -> calculatePreview()
  saleDate = await page.locator('input[name="saleDate"]').inputValue();
  await expect(page.locator('#prevPrice')).toHaveText('$499.99');
  await expect(page.locator('#prevRevenue')).toHaveText('$999.98');
  await shot(page, 'TC10_sale_preview');
  await page.getByRole('button', { name: 'Record Sale' }).click();
  await expect(page.locator('body')).toContainText('Sale recorded! 2 unit(s)');
  await expect(page.locator('body')).toContainText('New stock: 13 units.');
  await shot(page, 'TC10_sale_recorded');
});

test('TC11 - Invalid: quantity above stock', async ({ page }) => {
  await login(page, 'admin', 'password');
  await nav(page, 'Sales');
  await page.locator('#productSelect').selectOption({ label: 'Wireless Mouse (Stock: 2)' });
  await page.locator('#quantityInput').fill('3');
  await page.locator('#quantityInput').blur();
  // Layer 1: page JS sets max=stock, so the browser blocks the submit.
  await page.getByRole('button', { name: 'Record Sale' }).click();
  await page.waitForTimeout(400); // let the validation tooltip fade in
  await shot(page, 'TC11_client_validation');
  const clientMsg = await page.locator('#quantityInput').evaluate((el: HTMLInputElement) => el.validationMessage);
  expect(clientMsg).toContain('less than or equal to 2');
  // Layer 2: bypass browser validation to reach the server check (no source change).
  await page.locator('form[action="sales.php"]').evaluate((f: HTMLFormElement) => { f.noValidate = true; });
  await page.getByRole('button', { name: 'Record Sale' }).click();
  await expect(page.locator('body')).toContainText("Not enough stock! Available: 2 units of 'Wireless Mouse'.");
  await expect(page.locator('#productSelect option', { hasText: 'Wireless Mouse (Stock: 2)' })).toHaveCount(1);
  await shot(page, 'TC11_overselling_blocked');
});

test('TC12 - Borderline: quantity equal to stock', async ({ page }) => {
  await login(page, 'admin', 'password');
  await nav(page, 'Sales');
  await page.locator('#productSelect').selectOption({ label: 'Wireless Mouse (Stock: 2)' });
  await page.locator('#quantityInput').fill('2');
  await page.locator('#quantityInput').blur();
  await page.getByRole('button', { name: 'Record Sale' }).click();
  await expect(page.locator('body')).toContainText('Sale recorded! 2 unit(s)');
  await expect(page.locator('body')).toContainText('New stock: 0 units.');
  // out-of-stock products leave the sale list
  await expect(page.locator('#productSelect option', { hasText: 'Wireless Mouse' })).toHaveCount(0);
  await shot(page, 'TC12_sell_all_stock');
});

test('TC13 - Normal: update profile name', async ({ page }) => {
  await login(page, 'admin', 'password');
  await nav(page, 'Profile');
  const original = await page.locator('input[name="fullName"]').inputValue();
  await page.locator('input[name="fullName"]').fill('Updated Test Name');
  await page.getByRole('button', { name: 'Update Profile' }).click();
  await expect(page.locator('body')).toContainText('Profile updated successfully!');
  await expect(page.locator('input[name="fullName"]')).toHaveValue('Updated Test Name');
  await shot(page, 'TC13_profile_update');
  await page.locator('input[name="fullName"]').fill(original);
  await page.getByRole('button', { name: 'Update Profile' }).click();
  await expect(page.locator('body')).toContainText('Profile updated successfully!');
});

test('TC14 - Invalid: wrong current password', async ({ page }) => {
  await login(page, 'admin', 'password');
  await nav(page, 'Profile');
  await page.locator('input[name="currentPassword"]').fill('wrong-current');
  await page.locator('input[name="newPassword"]').fill('newpass123');
  await page.locator('form').filter({ hasText: 'Current Password' }).locator('input[name="confirmPassword"]').fill('newpass123');
  await page.getByRole('button', { name: 'Change Password' }).click();
  await expect(page.locator('body')).toContainText('Current password is incorrect.');
  await shot(page, 'TC14_password_rejected');
});

test('TC15 - Normal: reports by date', async ({ page }) => {
  expect(saleDate).toMatch(/^\d{4}-\d{2}-\d{2}$/);
  await login(page, 'admin', 'password');
  await nav(page, 'Reports');
  await page.locator('input[name="date"]').fill(saleDate);
  await page.getByRole('button', { name: 'Generate Report' }).click();
  await expect(page.locator('tr').filter({ hasText: 'Samsung TV 55"' }).first()).toContainText('$999.98');
  await expect(page.locator('tr').filter({ hasText: 'Wireless Mouse' }).first()).toContainText('2');
  await expect(page.locator('body')).not.toContainText('No sales were recorded');
  await shot(page, 'TC15_report_with_sales');
  await page.locator('input[name="date"]').fill('2020-01-01');
  await page.getByRole('button', { name: 'Generate Report' }).click();
  await expect(page.locator('body')).toContainText('No sales were recorded on 01/01/2020.');
  await shot(page, 'TC15_report_no_sales');
});
