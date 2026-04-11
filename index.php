<?php
session_start();
require_once 'config.php';
require_once 'functions.php';
require_once 'auth.php';

requireLogin();

$totalProductsResult = mysqli_query($conn, "SELECT COUNT(*) AS total FROM products");
$totalProductsRow    = $totalProductsResult ? mysqli_fetch_assoc($totalProductsResult) : ['total' => 0];
$totalProducts       = $totalProductsRow['total'];

$lowStockResult = mysqli_query($conn, "SELECT COUNT(*) AS total FROM products WHERE StockQuantity < 5");
$lowStockRow    = $lowStockResult ? mysqli_fetch_assoc($lowStockResult) : ['total' => 0];
$lowStockCount  = $lowStockRow['total'];

$today           = date('Y-m-d');
$todaySalesResult = mysqli_query($conn,
    "SELECT SUM(QuantitySold) AS totalSold FROM sales WHERE SaleDate = '$today'"
);
$todaySalesRow   = $todaySalesResult ? mysqli_fetch_assoc($todaySalesResult) : ['totalSold' => 0];
$todaySales      = $todaySalesRow['totalSold'] ? $todaySalesRow['totalSold'] : 0;

$allProductsResult = mysqli_query($conn, "SELECT * FROM products");
$allProducts       = [];

while ($row = mysqli_fetch_assoc($allProductsResult)) {
    $allProducts[] = $row;
}

$totalInventoryValue = calculateTotalValue($allProducts);

$recentSalesResult = mysqli_query($conn,
    "SELECT s.SaleID, p.ProductName, s.QuantitySold, s.SaleDate,
            (s.QuantitySold * p.UnitPrice) AS Revenue
     FROM sales s
     JOIN products p ON s.ProductID = p.ProductID
     ORDER BY s.SaleID DESC
     LIMIT 5"
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard — Inventory System</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<nav class="navbar">
    <a href="index.php" class="brand">Inventory</a>
    <a href="index.php" class="active">Dashboard</a>
    <?php if (isAdmin()): ?>
    <a href="users.php">Users</a>
    <?php endif; ?>
    <a href="products.php">Products</a>
    <a href="sales.php">Sales</a>
    <a href="reports.php">Reports</a>
    <span class="nav-user"><?= htmlspecialchars($_SESSION['full_name']) ?></span>
    <a href="profile.php">Profile</a>
    <a href="logout.php" class="btn-logout">Logout</a>
</nav>

<div class="container">
    <h1>Dashboard</h1>
    <p style="color:#6b7084; margin-bottom:24px; font-size:14px;">Overview for <?= date('F d, Y') ?></p>

    <div class="cards">
        <div class="card">
            <h3>Total Products</h3>
            <div class="value"><?= $totalProducts ?></div>
        </div>
        <div class="card green">
            <h3>Today's Units Sold</h3>
            <div class="value"><?= $todaySales ?></div>
        </div>
        <div class="card orange">
            <h3>Low Stock Items</h3>
            <div class="value"><?= $lowStockCount ?></div>
        </div>
        <div class="card">
            <h3>Total Inventory Value</h3>
            <div class="value">$<?= number_format($totalInventoryValue, 2) ?></div>
        </div>
    </div>

    <div class="table-wrap">
        <div class="table-header">
            <h2>Recent Sales</h2>
            <a href="sales.php" class="btn btn-primary">New Sale</a>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Sale ID</th>
                    <th>Product</th>
                    <th>Qty Sold</th>
                    <th>Revenue</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $rowCount = 0;
                while ($sale = mysqli_fetch_assoc($recentSalesResult)) {
                    $rowCount++;
                    echo "<tr>";
                    echo "<td>#" . $sale['SaleID'] . "</td>";
                    echo "<td>" . htmlspecialchars($sale['ProductName']) . "</td>";
                    echo "<td>" . $sale['QuantitySold'] . "</td>";
                    echo "<td>$" . number_format($sale['Revenue'], 2) . "</td>";
                    echo "<td>" . date('d/m/Y', strtotime($sale['SaleDate'])) . "</td>";
                    echo "</tr>";
                }

                if ($rowCount == 0) {
                    echo "<tr><td colspan='5' style='text-align:center; color:#aaa; padding:30px;'>
                          No sales recorded yet. <a href='sales.php'>Record first sale</a></td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

    <?php if ($lowStockCount > 0): ?>
        <div class="alert alert-warning">
            <strong><?= $lowStockCount ?> product(s)</strong> have low stock (less than 5 units).
            <a href="products.php" style="color:#fbbf24; font-weight:600;">View products</a>
        </div>
    <?php endif; ?>

</div>

<footer>Inventory Management System — Joe's Electronics &copy; <?= date('Y') ?></footer>
</body>
</html>
<?php mysqli_close($conn); ?>
