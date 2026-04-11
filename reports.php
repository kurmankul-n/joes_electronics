<?php
session_start();
require_once 'config.php';
require_once 'functions.php';
require_once 'auth.php';

requireLogin();

$reportDate = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $reportDate)) {
    $reportDate = date('Y-m-d');
}

$reportMatrix = buildReportMatrix($conn, $reportDate);

$totalUnitsSold    = 0;
$totalRevenue      = 0;
$totalItemsInStock = 0;

for ($i = 0; $i < count($reportMatrix); $i++) {
    $totalUnitsSold    += $reportMatrix[$i][1];
    $totalRevenue      += $reportMatrix[$i][3];
    $totalItemsInStock += $reportMatrix[$i][2];
}

$n = count($reportMatrix);
for ($i = 1; $i < $n; $i++) {
    $current = $reportMatrix[$i];
    $j = $i - 1;

    while ($j >= 0 && $reportMatrix[$j][3] < $current[3]) {
        $reportMatrix[$j + 1] = $reportMatrix[$j];
        $j--;
    }
    $reportMatrix[$j + 1] = $current;
}

$datesResult = mysqli_query($conn, "SELECT ReportDate FROM reports ORDER BY ReportDate DESC");
$reportDates = [];
while ($dateRow = mysqli_fetch_assoc($datesResult)) {
    $reportDates[] = $dateRow['ReportDate'];
}

$hasSalesData = ($totalUnitsSold > 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reports — Inventory System</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<nav class="navbar">
    <a href="index.php" class="brand">Inventory</a>
    <a href="index.php">Dashboard</a>
    <?php if (isAdmin()): ?>
    <a href="users.php">Users</a>
    <?php endif; ?>
    <a href="products.php">Products</a>
    <a href="sales.php">Sales</a>
    <a href="reports.php" class="active">Reports</a>
    <span class="nav-user"><?= htmlspecialchars($_SESSION['full_name']) ?></span>
    <a href="profile.php">Profile</a>
    <a href="logout.php" class="btn-logout">Logout</a>
</nav>

<div class="container">
    <h1>Daily Report</h1>

    <div style="display:flex; gap:15px; align-items:center; margin-bottom:20px; flex-wrap:wrap;">
        <form method="GET" action="reports.php" style="display:flex; gap:10px; align-items:center;">
            <label style="font-weight:bold; color:#555;">Report Date:</label>
            <input type="date" name="date" value="<?= htmlspecialchars($reportDate) ?>"
                   style="padding:8px 12px; border:1px solid #ddd; border-radius:6px; font-size:14px;">
            <button type="submit" class="btn btn-primary">Generate Report</button>
        </form>

        <?php if (count($reportDates) > 0): ?>
        <div>
            <span style="font-size:13px; color:#888;">Quick access: </span>
            <?php
            $linkCount = 0;
            foreach ($reportDates as $date) {
                if ($linkCount >= 5) break;
                $active = ($date == $reportDate) ? "background:#1a73e8; color:#fff;" : "background:#eee; color:#333;";
                echo "<a href='reports.php?date=$date'
                         style='display:inline-block; padding:4px 10px; border-radius:4px;
                                font-size:13px; text-decoration:none; margin:2px; $active'>"
                   . date('d/m/Y', strtotime($date)) . "</a>";
                $linkCount++;
            }
            ?>
        </div>
        <?php endif; ?>
    </div>

    <div class="report-summary">
        <strong>Report Date: <?= date('d/m/Y', strtotime($reportDate)) ?></strong>
        &nbsp;|&nbsp;
        Generated on: <?= date('d/m/Y H:i') ?>
    </div>

    <div class="cards">
        <div class="card green">
            <h3>Total Units Sold</h3>
            <div class="value"><?= $totalUnitsSold ?></div>
        </div>
        <div class="card">
            <h3>Total Revenue</h3>
            <div class="value">$<?= number_format($totalRevenue, 2) ?></div>
        </div>
        <div class="card orange">
            <h3>Total Items in Stock</h3>
            <div class="value"><?= $totalItemsInStock ?></div>
        </div>
        <div class="card">
            <h3>Products Tracked</h3>
            <div class="value"><?= count($reportMatrix) ?></div>
        </div>
    </div>

    <?php if (!$hasSalesData): ?>
        <div class="alert alert-warning">
            No sales were recorded on <?= date('d/m/Y', strtotime($reportDate)) ?>.
            The table below shows current inventory levels only.
        </div>
    <?php endif; ?>

    <div class="table-wrap">
        <div class="table-header">
            <h2>Product Summary - Sorted by Revenue</h2>
            <button onclick="window.print()" class="btn btn-primary">Print Report</button>
        </div>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Product Name</th>
                    <th>Units Sold Today</th>
                    <th>Remaining Stock</th>
                    <th>Revenue Today</th>
                    <th>Stock Status</th>
                </tr>
            </thead>
            <tbody>
                <?php
                for ($row = 0; $row < count($reportMatrix); $row++) {
                    $productName    = $reportMatrix[$row][0];
                    $unitsSold      = $reportMatrix[$row][1];
                    $remainingStock = $reportMatrix[$row][2];
                    $revenue        = $reportMatrix[$row][3];

                    if ($remainingStock == 0) {
                        $statusBadge = "<span class='badge badge-red'>Out of Stock</span>";
                    } elseif ($remainingStock < 5) {
                        $statusBadge = "<span class='badge badge-orange'>Low Stock</span>";
                    } else {
                        $statusBadge = "<span class='badge badge-green'>In Stock</span>";
                    }

                    $rowStyle = ($unitsSold > 0) ? "background:#1f2633;" : "";

                    echo "<tr style='$rowStyle'>";
                    echo "<td>" . ($row + 1) . "</td>";
                    echo "<td><strong>" . htmlspecialchars($productName) . "</strong></td>";
                    echo "<td>" . ($unitsSold > 0 ? "<strong>$unitsSold</strong>" : "<span style='color:#ccc'>0</span>") . "</td>";
                    echo "<td>" . $remainingStock . "</td>";
                    echo "<td>" . ($revenue > 0 ? "<strong>\$" . number_format($revenue, 2) . "</strong>" : "<span style='color:#ccc'>\$0.00</span>") . "</td>";
                    echo "<td>" . $statusBadge . "</td>";
                    echo "</tr>";
                }

                if (count($reportMatrix) == 0) {
                    echo "<tr><td colspan='6' style='text-align:center; color:#aaa; padding:30px;'>
                          No products found. <a href='products.php'>Add products first</a></td></tr>";
                }
                ?>
            </tbody>
            <tfoot>
                <tr style="background:#16181d; font-weight:bold; border-top:2px solid #2a2d36; color:#e8eaed;">
                    <td colspan="2" style="text-align:right; padding:12px 16px;">TOTALS:</td>
                    <td style="padding:12px 16px;"><?= $totalUnitsSold ?></td>
                    <td style="padding:12px 16px;"><?= $totalItemsInStock ?></td>
                    <td style="padding:12px 16px;">$<?= number_format($totalRevenue, 2) ?></td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <?php if (count($reportDates) > 0): ?>
    <div class="table-wrap">
        <div class="table-header"><h2>All Report Dates</h2></div>
        <table>
            <thead>
                <tr><th>#</th><th>Report Date</th><th>Action</th></tr>
            </thead>
            <tbody>
                <?php
                $num = 1;
                foreach ($reportDates as $date) {
                    echo "<tr>";
                    echo "<td>$num</td>";
                    echo "<td>" . date('d/m/Y', strtotime($date)) . "</td>";
                    echo "<td><a href='reports.php?date=$date' class='btn btn-sm btn-primary'>View Report</a></td>";
                    echo "</tr>";
                    $num++;
                }
                ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

</div>

<footer>Inventory Management System — Joe's Electronics &copy; <?= date('Y') ?></footer>
</body>
</html>
<?php mysqli_close($conn); ?>
