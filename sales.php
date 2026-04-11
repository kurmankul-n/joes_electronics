<?php
session_start();
require_once 'config.php';
require_once 'functions.php';
require_once 'auth.php';

requireLogin();

$message     = "";
$messageType = "";

if (isset($_POST['action']) && $_POST['action'] == 'recordSale') {
    $productID    = intval($_POST['productID']);
    $quantitySold = intval($_POST['quantitySold']);
    $saleDate     = mysqli_real_escape_string($conn, $_POST['saleDate']);

    if ($productID <= 0 || $quantitySold <= 0 || $saleDate == "") {
        $message     = "Please fill all fields correctly.";
        $messageType = "error";
    } else {
        $stockResult = mysqli_query($conn,
            "SELECT StockQuantity, ProductName FROM products WHERE ProductID = $productID"
        );
        $stockRow    = mysqli_fetch_assoc($stockResult);

        if (!$stockRow) {
            $message     = "Product not found.";
            $messageType = "error";
        } elseif ($stockRow['StockQuantity'] < $quantitySold) {
            $message     = "Not enough stock! Available: " . $stockRow['StockQuantity']
                           . " units of '" . htmlspecialchars($stockRow['ProductName']) . "'.";
            $messageType = "error";
        } else {
            $reportResult = mysqli_query($conn,
                "SELECT ReportID FROM reports WHERE ReportDate = '$saleDate'"
            );

            if (mysqli_num_rows($reportResult) == 0) {
                mysqli_query($conn, "INSERT INTO reports (ReportDate) VALUES ('$saleDate')");
                $reportID = mysqli_insert_id($conn);
            } else {
                $reportRow = mysqli_fetch_assoc($reportResult);
                $reportID  = $reportRow['ReportID'];
            }

            $insertSaleSQL = "INSERT INTO sales (SaleDate, ProductID, ReportID, QuantitySold)
                              VALUES ('$saleDate', $productID, $reportID, $quantitySold)";

            if (mysqli_query($conn, $insertSaleSQL)) {
                $newStock      = $stockRow['StockQuantity'] - $quantitySold;
                $updateStockSQL = "UPDATE products SET StockQuantity = $newStock
                                   WHERE ProductID = $productID";
                mysqli_query($conn, $updateStockSQL);

                $message     = "Sale recorded! " . $quantitySold . " unit(s) of '"
                               . htmlspecialchars($stockRow['ProductName']) . "' sold.
                               New stock: $newStock units.";
                $messageType = "success";
            } else {
                $message     = "Error recording sale: " . mysqli_error($conn);
                $messageType = "error";
            }
        }
    }
}

$productsResult = mysqli_query($conn,
    "SELECT ProductID, ProductName, StockQuantity, UnitPrice
     FROM products
     WHERE StockQuantity > 0
     ORDER BY ProductName ASC"
);

$productsList = [];
while ($row = mysqli_fetch_assoc($productsResult)) {
    $productsList[] = $row;
}

$salesHistoryResult = mysqli_query($conn,
    "SELECT s.SaleID, p.ProductName, p.UnitPrice, s.QuantitySold, s.SaleDate,
            (s.QuantitySold * p.UnitPrice) AS Revenue
     FROM sales s
     JOIN products p ON s.ProductID = p.ProductID
     ORDER BY s.SaleID DESC
     LIMIT 15"
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Record Sale — Inventory System</title>
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
    <a href="sales.php" class="active">Sales</a>
    <a href="reports.php">Reports</a>
    <span class="nav-user"><?= htmlspecialchars($_SESSION['full_name']) ?></span>
    <a href="profile.php">Profile</a>
    <a href="logout.php" class="btn-logout">Logout</a>
</nav>

<div class="container">
    <h1>Record a Sale</h1>

    <?php if ($message != ""): ?>
        <div class="alert alert-<?= $messageType ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <div style="display:flex; gap:30px; align-items:flex-start; flex-wrap:wrap;">

        <div style="flex:1; min-width:260px;">
            <div class="form-box">
                <h2>New Sale</h2>

                <?php if (count($productsList) == 0): ?>
                    <div class="alert alert-warning">
                        No products in stock. <a href="products.php">Add products first</a>
                    </div>
                <?php else: ?>
                <form method="POST" action="sales.php">
                    <input type="hidden" name="action" value="recordSale">

                    <div class="form-group">
                        <label>Select Product</label>
                        <select name="productID" id="productSelect" required onchange="updatePrice()">
                            <option value="">-- Choose a product --</option>
                            <?php
                            foreach ($productsList as $product) {
                                echo "<option value='" . $product['ProductID'] . "'
                                             data-price='" . $product['UnitPrice'] . "'
                                             data-stock='" . $product['StockQuantity'] . "'>";
                                echo htmlspecialchars($product['ProductName']);
                                echo " (Stock: " . $product['StockQuantity'] . ")";
                                echo "</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Quantity to Sell</label>
                        <input type="number" name="quantitySold" id="quantityInput"
                               min="1" required placeholder="Enter quantity"
                               onchange="calculatePreview()">
                    </div>

                    <div class="form-group">
                        <label>Sale Date</label>
                        <input type="date" name="saleDate" required
                               value="<?= date('Y-m-d') ?>">
                    </div>

                    <div id="previewBox" style="display:none; background:#e8f0fe; border-radius:6px;
                                                padding:12px; margin-bottom:18px; font-size:14px; color:#1a73e8; font-weight:bold;">
                        <strong>Preview:</strong><br>
                        Unit Price: <span id="prevPrice">$0.00</span><br>
                        Estimated Revenue: <span id="prevRevenue">$0.00</span>
                    </div>

                    <button type="submit" class="btn btn-success btn-block">
                        Record Sale
                    </button>
                </form>
                <?php endif; ?>
            </div>
        </div>

        <div style="flex:2; min-width:300px;">
            <div class="table-wrap">
                <div class="table-header">
                    <h2>Sales History</h2>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Product</th>
                            <th>Qty</th>
                            <th>Unit Price</th>
                            <th>Revenue</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $historyCount = 0;

                        while ($sale = mysqli_fetch_assoc($salesHistoryResult)) {
                            $historyCount++;
                            echo "<tr>";
                            echo "<td>#" . $sale['SaleID'] . "</td>";
                            echo "<td>" . htmlspecialchars($sale['ProductName']) . "</td>";
                            echo "<td>" . $sale['QuantitySold'] . "</td>";
                            echo "<td>$" . number_format($sale['UnitPrice'], 2) . "</td>";
                            echo "<td>$" . number_format($sale['Revenue'], 2) . "</td>";
                            echo "<td>" . date('d/m/Y', strtotime($sale['SaleDate'])) . "</td>";
                            echo "</tr>";
                        }

                        if ($historyCount == 0) {
                            echo "<tr><td colspan='6' style='text-align:center; color:#aaa; padding:30px;'>
                                  No sales recorded yet.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<footer>Inventory Management System — Joe's Electronics &copy; <?= date('Y') ?></footer>

<script>
    function updatePrice() {
        var select     = document.getElementById('productSelect');
        var selectedOption = select.options[select.selectedIndex];
        var price      = parseFloat(selectedOption.getAttribute('data-price')) || 0;
        var stock      = parseInt(selectedOption.getAttribute('data-stock'))  || 0;

        document.getElementById('quantityInput').max = stock;
        document.getElementById('prevPrice').textContent = "$" + price.toFixed(2);
        calculatePreview();
    }

    function calculatePreview() {
        var select   = document.getElementById('productSelect');
        var option   = select.options[select.selectedIndex];
        var price    = parseFloat(option.getAttribute('data-price')) || 0;
        var quantity = parseInt(document.getElementById('quantityInput').value) || 0;

        var revenue  = price * quantity;

        if (price > 0 && quantity > 0) {
            document.getElementById('previewBox').style.display = 'block';
            document.getElementById('prevRevenue').textContent  = "$" + revenue.toFixed(2);
        } else {
            document.getElementById('previewBox').style.display = 'none';
        }
    }
</script>
</body>
</html>
<?php mysqli_close($conn); ?>
