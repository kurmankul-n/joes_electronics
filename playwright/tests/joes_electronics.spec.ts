import { test, expect, Page } from '@playwright/test';

// Order matters (doc 4.5): TC07 changes stock, TC08 needs untouched Wireless Mouse, TC10 uses TC07's sale date.
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

let saleDate = '';

test('TC01 - Valid administrator login', async ({ page }) => {
  await login(page, 'admin', 'password');
  await expect(page).toHaveURL(/index\.php/);
  await expect(page.getByRole('heading', { name: /Dashboard/ })).toBeVisible();
  await expect(page.getByRole('link', { name: 'Users', exact: true })).toBeVisible();
  await shot(page, 'TC01_admin_login');
});

test('TC02 - Invalid login credentials', async ({ page }) => {
  await login(page, 'admin', 'wrong-password-123');
  await expect(page).toHaveURL(/login\.php/);
  await expect(page.locator('body')).toContainText('Invalid username or password.');
  await shot(page, 'TC02_invalid_login');
});

test('TC03 - Registration boundary and password confirmation', async ({ page }) => {
  await page.goto('/register.php');
  await page.locator('input[name="fullName"]').fill('Boundary Test User');
  await page.locator('input[name="username"]').fill('ab');
  await page.locator('input[name="password"]').fill('pass1234');
  await page.locator('input[name="confirmPassword"]').fill('different123');
  await page.getByRole('button', { name: 'Create Account' }).click();
  await expect(page).toHaveURL(/register\.php/);
  await expect(page.locator('body')).toContainText('Username must be at least 3 characters.');
  await shot(page, 'TC03_registration_boundary');
});

test('TC04 - Administrator adds a product with normal data', async ({ page }) => {
  await login(page, 'admin', 'password');
  await nav(page, 'Products');
  await expect(page).toHaveURL(/products\.php/);
  await page.locator('input[name="productName"]').fill('Test Wireless Charger');
  await page.locator('input[name="category"]').fill('Accessories');
  await page.locator('input[name="unitPrice"]').fill('29.99');
  await page.locator('input[name="stockQuantity"]').fill('10');
  await page.getByRole('button', { name: 'Add Product' }).click();
  await expect(page.locator('body')).toContainText("Product 'Test Wireless Charger' added successfully!");
  const row = page.locator('tr').filter({ hasText: 'Test Wireless Charger' }).first();
  await expect(row).toContainText('Accessories');
  await expect(row).toContainText('29.99');
  await row.scrollIntoViewIfNeeded();
  await shot(page, 'TC04_add_product');
});

test('TC05 - Product ID search, including a boundary value', async ({ page }) => {
  await login(page, 'admin', 'password');
  await nav(page, 'Products');
  const samsungRow = page.locator('tr').filter({ hasText: 'Samsung TV 55"' }).first();
  const id = (await samsungRow.locator('td').first().textContent())?.trim() ?? '';
  expect(id).toMatch(/^\d+$/);
  await page.locator('input[name="searchID"]').fill(id);
  await page.getByRole('button', { name: 'Search' }).click();
  await expect(page.locator('body')).toContainText('Product found using Binary Search');
  await expect(page.getByRole('heading', { name: 'Search Result' })).toBeVisible();
  await shot(page, 'TC05_search_valid');
  await page.locator('input[name="searchID"]').fill('0');
  await page.getByRole('button', { name: 'Search' }).click();
  await expect(page.locator('body')).toContainText('Product ID must be a positive number.');
  await shot(page, 'TC05_search_zero');
});

test('TC06 - Regular-user access control', async ({ page }) => {
  await login(page, 'user', 'password');
  await expect(page).toHaveURL(/index\.php/);
  await expect(page.getByRole('link', { name: 'Users', exact: true })).toHaveCount(0);
  await shot(page, 'TC06_user_access');
  await page.goto('/users.php');
  await expect(page).toHaveURL(/index\.php/);
  await expect(page.getByRole('link', { name: 'Users', exact: true })).toHaveCount(0);
  await shot(page, 'TC06_users_blocked');
});

test('TC07 - Valid sale: preview, stock and revenue', async ({ page }) => {
  await login(page, 'admin', 'password');
  await nav(page, 'Sales');
  await expect(page).toHaveURL(/sales\.php/);
  await page.locator('#productSelect').selectOption({ label: 'Samsung TV 55" (Stock: 15)' });
  await page.locator('#quantityInput').fill('2');
  await page.locator('#quantityInput').blur(); // fires onchange -> calculatePreview()
  saleDate = await page.locator('input[name="saleDate"]').inputValue();
  await expect(page.locator('#previewBox')).toBeVisible();
  await expect(page.locator('#prevPrice')).toHaveText('$499.99');
  await expect(page.locator('#prevRevenue')).toHaveText('$999.98');
  await shot(page, 'TC07_sale_preview');
  await page.getByRole('button', { name: 'Record Sale' }).click();
  await expect(page.locator('body')).toContainText('Sale recorded! 2 unit(s)');
  await expect(page.locator('body')).toContainText('New stock: 13 units.');
  const hist = page.locator('tr').filter({ hasText: 'Samsung TV 55"' }).filter({ hasText: '$999.98' }).first();
  await expect(hist).toBeVisible();
  await shot(page, 'TC07_sale_recorded');
});

test('TC08 - Overselling is prevented', async ({ page }) => {
  await login(page, 'admin', 'password');
  await nav(page, 'Sales');
  await page.locator('#productSelect').selectOption({ label: 'Wireless Mouse (Stock: 2)' });
  await page.locator('#quantityInput').fill('3');
  await page.locator('#quantityInput').blur();
  // Layer 1: page JS sets max=stock, so the browser blocks the submit before the server is reached.
  await page.getByRole('button', { name: 'Record Sale' }).click();
  await page.waitForTimeout(400); // let the native validation tooltip finish fading in
  await shot(page, 'TC08_client_validation');
  await expect(page).toHaveURL(/sales\.php$/);
  const clientMsg = await page.locator('#quantityInput').evaluate((el: HTMLInputElement) => el.validationMessage);
  expect(clientMsg).toContain('less than or equal to 2');
  // Layer 2: bypass browser validation to prove the server-side stock rule (no source change).
  await page.locator('form[action="sales.php"]').evaluate((f: HTMLFormElement) => { f.noValidate = true; });
  await page.getByRole('button', { name: 'Record Sale' }).click();
  await expect(page.locator('body')).toContainText("Not enough stock! Available: 2 units of 'Wireless Mouse'.");
  // stock unchanged: mouse still offered with stock 2
  await expect(page.locator('#productSelect option', { hasText: 'Wireless Mouse (Stock: 2)' })).toHaveCount(1);
  await shot(page, 'TC08_overselling_blocked');
});

test('TC09 - Profile update and password validation', async ({ page }) => {
  await login(page, 'admin', 'password');
  await nav(page, 'Profile');
  await expect(page).toHaveURL(/profile\.php/);
  const original = await page.locator('input[name="fullName"]').inputValue();
  await page.locator('input[name="fullName"]').fill('Updated Test Name');
  await page.getByRole('button', { name: 'Update Profile' }).click();
  await expect(page.locator('body')).toContainText('Profile updated successfully!');
  await expect(page.locator('input[name="fullName"]')).toHaveValue('Updated Test Name');
  await shot(page, 'TC09_profile_update');
  await page.locator('input[name="currentPassword"]').fill('wrong-current');
  await page.locator('input[name="newPassword"]').fill('newpass123');
  await page.locator('form').filter({ hasText: 'Current Password' }).locator('input[name="confirmPassword"]').fill('newpass123');
  await page.getByRole('button', { name: 'Change Password' }).click();
  await expect(page.locator('body')).toContainText('Current password is incorrect.');
  await shot(page, 'TC09_password_rejected');
  // restore original name (doc 4.5 item 6)
  await page.locator('input[name="fullName"]').fill(original);
  await page.getByRole('button', { name: 'Update Profile' }).click();
  await expect(page.locator('body')).toContainText('Profile updated successfully!');
});

test('TC10 - Reports with and without sales', async ({ page }) => {
  expect(saleDate).toMatch(/^\d{4}-\d{2}-\d{2}$/);
  await login(page, 'admin', 'password');
  await nav(page, 'Reports');
  await expect(page).toHaveURL(/reports\.php/);
  await page.locator('input[name="date"]').fill(saleDate);
  await page.getByRole('button', { name: 'Generate Report' }).click();
  const row = page.locator('tr').filter({ hasText: 'Samsung TV 55"' }).first();
  await expect(row).toContainText('2');
  await expect(row).toContainText('$999.98');
  await expect(page.locator('body')).not.toContainText('No sales were recorded');
  await shot(page, 'TC10_report_with_sales');
  await page.locator('input[name="date"]').fill('2020-01-01');
  await page.getByRole('button', { name: 'Generate Report' }).click();
  await expect(page.locator('body')).toContainText('No sales were recorded on 01/01/2020.');
  await shot(page, 'TC10_report_no_sales');
});
