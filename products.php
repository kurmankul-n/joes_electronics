<?php
session_start();
require_once 'config.php';
require_once 'functions.php';
require_once 'auth.php';

requireLogin();

$message     = "";
$messageType = "";
$editProduct = null;

$canEdit = isAdmin();

if ($canEdit && isset($_POST['action']) && $_POST['action'] == 'add') {
    $productName   = mysqli_real_escape_string($conn, trim($_POST['productName']));
    $category      = mysqli_real_escape_string($conn, trim($_POST['category']));
    $unitPrice     = floatval($_POST['unitPrice']);
    $stockQuantity = intval($_POST['stockQuantity']);

    if ($productName == "" || $category == "" || $unitPrice <= 0 || $stockQuantity < 0) {
        $message     = "Please fill all fields correctly. Price must be greater than 0.";
        $messageType = "error";
    } else {
        $insertSQL = "INSERT INTO products (ProductName, Category, UnitPrice, StockQuantity)
                      VALUES ('$productName', '$category', $unitPrice, $stockQuantity)";

        if (mysqli_query($conn, $insertSQL)) {
            $message     = "Product '$productName' added successfully!";
            $messageType = "success";
        } else {
            $message     = "Error adding product: " . mysqli_error($conn);
            $messageType = "error";
        }
    }
}

if ($canEdit && isset($_POST['action']) && $_POST['action'] == 'update') {
    $productID     = intval($_POST['productID']);
    $productName   = mysqli_real_escape_string($conn, trim($_POST['productName']));
    $category      = mysqli_real_escape_string($conn, trim($_POST['category']));
    $unitPrice     = floatval($_POST['unitPrice']);
    $stockQuantity = intval($_POST['stockQuantity']);

    $updateSQL = "UPDATE products
                  SET ProductName = '$productName',
                      Category    = '$category',
                      UnitPrice   = $unitPrice,
                      StockQuantity = $stockQuantity
                  WHERE ProductID = $productID";

    if (mysqli_query($conn, $updateSQL)) {
        $message     = "Product updated successfully!";
        $messageType = "success";
    } else {
        $message     = "Error updating product: " . mysqli_error($conn);
        $messageType = "error";
    }
}

if ($canEdit && isset($_POST['delete'])) {
    $deleteID  = intval($_POST['delete']);

    $checkSQL    = "SELECT COUNT(*) AS cnt FROM sales WHERE ProductID = $deleteID";
    $checkResult = mysqli_query($conn, $checkSQL);
    $checkRow    = mysqli_fetch_assoc($checkResult);

    if ($checkRow['cnt'] > 0) {
        $message     = "Cannot delete product - it has existing sales records.";
        $messageType = "error";
    } else {
        $deleteSQL = "DELETE FROM products WHERE ProductID = $deleteID";
        if (mysqli_query($conn, $deleteSQL)) {
            $message     = "Product deleted successfully.";
            $messageType = "success";
        } else {
            $message     = "Error deleting product: " . mysqli_error($conn);
            $messageType = "error";
        }
    }
}

if (isset($_GET['edit']) && $canEdit) {
    $editID          = intval($_GET['edit']);
    $editResult      = mysqli_query($conn, "SELECT * FROM products WHERE ProductID = $editID");
    $editProduct     = mysqli_fetch_assoc($editResult);
}

$searchResult  = null;
$searchMessage = "";
if (isset($_GET['searchID']) && $_GET['searchID'] !== "") {
    $searchTargetID = intval($_GET['searchID']);

    if ($searchTargetID <= 0) {
        $searchMessage = "Product ID must be a positive number.";
    } else {

    $sortedResult = mysqli_query($conn, "SELECT * FROM products ORDER BY ProductID ASC");
    $sortedArray  = [];
    while ($row = mysqli_fetch_assoc($sortedResult)) {
        $sortedArray[] = $row;
    }

    $foundIndex = binarySearch($sortedArray, $searchTargetID);

    if ($foundIndex != -1) {
        $searchResult  = $sortedArray[$foundIndex];
        $searchMessage = "Product found using Binary Search (index: $foundIndex)";
    } else {
        $searchMessage = "Product with ID '$searchTargetID' not found.";
    }
    }
}

$productsResult = mysqli_query($conn, "SELECT * FROM products");
$productsArray  = [];

while ($row = mysqli_fetch_assoc($productsResult)) {
    $productsArray[] = $row;
}

$sortedProducts = bubbleSort($productsArray);
$categories = getCategories($productsArray);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Products — Inventory System</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<nav class="navbar">
    <a href="index.php" class="brand">Inventory</a>
    <a href="index.php">Dashboard</a>
    <?php if (isAdmin()): ?>
    <a href="users.php">Users</a>
    <?php endif; ?>
    <a href="products.php" class="active">Products</a>
    <a href="sales.php">Sales</a>
    <a href="reports.php">Reports</a>
    <span class="nav-user"><?= htmlspecialchars($_SESSION['full_name']) ?></span>
    <a href="profile.php">Profile</a>
    <a href="logout.php" class="btn-logout">Logout</a>
</nav>

<div class="container">
    <h1>Products</h1>

    <?php if ($message != ""): ?>
        <div class="alert alert-<?= $messageType == 'success' ? 'success' : 'error' ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <div class="search-box">
        <form method="GET" action="products.php" style="display:flex; gap:10px; align-items:center;">
            <input type="number" name="searchID" min="0" placeholder="Search by Product ID..."
                   value="<?= isset($_GET['searchID']) ? htmlspecialchars($_GET['searchID']) : '' ?>">
            <button type="submit" class="btn btn-primary">Search</button>
            <?php if (isset($_GET['searchID'])): ?>
                <a href="products.php" class="btn btn-sm" style="background:#eee; color:#333;">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <?php if ($searchMessage != ""): ?>
        <div class="alert alert-<?= $searchResult ? 'success' : 'error' ?>"><?= $searchMessage ?></div>
        <?php if ($searchResult): ?>
            <div class="table-wrap" style="margin-bottom:20px;">
                <div class="table-header"><h2>Search Result</h2></div>
                <table>
                    <thead>
                        <tr><th>ID</th><th>Name</th><th>Category</th><th>Unit Price</th><th>Stock</th></tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><?= $searchResult['ProductID'] ?></td>
                            <td><?= htmlspecialchars($searchResult['ProductName']) ?></td>
                            <td><?= htmlspecialchars($searchResult['Category']) ?></td>
                            <td>$<?= number_format($searchResult['UnitPrice'], 2) ?></td>
                            <td><?= $searchResult['StockQuantity'] ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <div style="display:flex; gap:30px; align-items:flex-start; flex-wrap:wrap;">

        <div style="flex:2; min-width:300px;">
            <div class="table-wrap">
                <div class="table-header">
                    <h2>All Products <small style="font-size:13px; color:#888;">(sorted A-Z)</small></h2>
                    <span class="badge badge-blue"><?= count($sortedProducts) ?> total</span>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Product Name</th>
                            <th>Category</th>
                            <th>Unit Price</th>
                            <th>Stock</th>
                            <?php if ($canEdit): ?>
                            <th>Actions</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        foreach ($sortedProducts as $product) {
                            $stockBadge = "";

                            if ($product['StockQuantity'] == 0) {
                                $stockBadge = "<span class='badge badge-red'>Out of Stock</span>";
                            } elseif ($product['StockQuantity'] < 5) {
                                $stockBadge = "<span class='badge badge-orange'>" . $product['StockQuantity'] . " left</span>";
                            } else {
                                $stockBadge = "<span class='badge badge-green'>" . $product['StockQuantity'] . "</span>";
                            }

                            echo "<tr>";
                            echo "<td>" . $product['ProductID'] . "</td>";
                            echo "<td>" . htmlspecialchars($product['ProductName']) . "</td>";
                            echo "<td>" . htmlspecialchars($product['Category']) . "</td>";
                            echo "<td>$" . number_format($product['UnitPrice'], 2) . "</td>";
                            echo "<td>" . $stockBadge . "</td>";
                            if ($canEdit) {
                                echo "<td>
                                        <a href='products.php?edit=" . $product['ProductID'] . "' class='btn btn-edit'>Edit</a>
                                        <form method='POST' style='display:inline;'>
                                            <button type='submit' name='delete' value='" . $product['ProductID'] . "' class='btn btn-danger'
                                               onclick=\"return confirm('Delete this product?')\">Delete</button>
                                        </form>
                                      </td>";
                            }
                            echo "</tr>";
                        }

                        if (count($sortedProducts) == 0) {
                            echo "<tr><td colspan='" . ($canEdit ? '6' : '5') . "' style='text-align:center; color:#aaa; padding:30px;'>
                                  No products yet.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php if ($canEdit): ?>
        <div style="flex:1; min-width:260px;">
            <div class="form-box">
                <h2><?= $editProduct ? 'Edit Product' : 'Add New Product' ?></h2>

                <form method="POST" action="products.php">
                    <input type="hidden" name="action" value="<?= $editProduct ? 'update' : 'add' ?>">
                    <?php if ($editProduct): ?>
                        <input type="hidden" name="productID" value="<?= $editProduct['ProductID'] ?>">
                    <?php endif; ?>

                    <div class="form-group">
                        <label>Product Name</label>
                        <input type="text" name="productName" maxlength="100" required
                               value="<?= $editProduct ? htmlspecialchars($editProduct['ProductName']) : '' ?>"
                               placeholder="e.g. Samsung TV 55&quot;">
                    </div>

                    <div class="form-group">
                        <label>Category</label>
                        <input type="text" name="category" maxlength="50" required
                               value="<?= $editProduct ? htmlspecialchars($editProduct['Category']) : '' ?>"
                               placeholder="e.g. Television">
                    </div>

                    <div class="form-group">
                        <label>Unit Price ($)</label>
                        <input type="number" name="unitPrice" step="0.01" min="0.01" required
                               value="<?= $editProduct ? $editProduct['UnitPrice'] : '' ?>"
                               placeholder="0.00">
                    </div>

                    <div class="form-group">
                        <label>Stock Quantity</label>
                        <input type="number" name="stockQuantity" min="0" required
                               value="<?= $editProduct ? $editProduct['StockQuantity'] : '' ?>"
                               placeholder="0">
                    </div>

                    <button type="submit" class="btn btn-<?= $editProduct ? 'primary' : 'success' ?>" style="width:100%;">
                        <?= $editProduct ? 'Save Changes' : 'Add Product' ?>
                    </button>

                    <?php if ($editProduct): ?>
                        <a href="products.php" class="btn" style="width:100%; margin-top:10px; background:#eee; color:#333; text-align:center; display:block;">
                            Cancel
                        </a>
                    <?php endif; ?>
                </form>
            </div>

            <?php if (count($categories) > 0): ?>
            <div class="form-box" style="margin-top:20px;">
                <h2>Categories</h2>
                <?php
                foreach ($categories as $cat) {
                    echo "<span class='badge badge-blue' style='margin:3px;'>" . htmlspecialchars($cat) . "</span>";
                }
                ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

    </div>
</div>

<footer>Inventory Management System — Joe's Electronics &copy; <?= date('Y') ?></footer>
</body>
</html>
<?php mysqli_close($conn); ?>
