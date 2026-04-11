<?php
function bubbleSort($productArray) {
    $n = count($productArray);

    for ($i = 0; $i < $n - 1; $i++) {
        for ($j = 0; $j < $n - $i - 1; $j++) {
            if ($productArray[$j]['ProductName'] > $productArray[$j + 1]['ProductName']) {
                $temp                  = $productArray[$j];
                $productArray[$j]      = $productArray[$j + 1];
                $productArray[$j + 1]  = $temp;
            }
        }
    }
    return $productArray;
}

function insertionSort($productArray) {
    $n = count($productArray);

    for ($i = 1; $i < $n; $i++) {
        $current = $productArray[$i];
        $j = $i - 1;

        while ($j >= 0 && $productArray[$j]['UnitPrice'] > $current['UnitPrice']) {
            $productArray[$j + 1] = $productArray[$j];
            $j--;
        }
        $productArray[$j + 1] = $current;
    }
    return $productArray;
}

function binarySearch($sortedArray, $targetID) {
    $low  = 0;
    $high = count($sortedArray) - 1;

    while ($low <= $high) {
        $mid = (int)(($low + $high) / 2);

        if ($sortedArray[$mid]['ProductID'] == $targetID) {
            return $mid;
        } elseif ($sortedArray[$mid]['ProductID'] < $targetID) {
            $low = $mid + 1;
        } else {
            $high = $mid - 1;
        }
    }
    return -1;
}

function calculateTotalValue($productArray) {
    $totalValue = 0;

    foreach ($productArray as $product) {
        $totalValue += $product['UnitPrice'] * $product['StockQuantity'];
    }
    return round($totalValue, 2);
}

function getCategories($productArray) {
    $categories = [];

    foreach ($productArray as $product) {
        if (!in_array($product['Category'], $categories)) {
            $categories[] = $product['Category'];
        }
    }
    return $categories;
}

function buildReportMatrix($conn, $reportDate) {
    $reportMatrix = [];
    $reportDateEscaped = mysqli_real_escape_string($conn, $reportDate);

    $productQuery  = "SELECT ProductID, ProductName, StockQuantity, UnitPrice FROM products";
    $productResult = mysqli_query($conn, $productQuery);

    if (!$productResult) {
        return [];
    }

    $row = 0;

    while ($product = mysqli_fetch_assoc($productResult)) {
        $productID = $product['ProductID'];

        $salesQuery  = "SELECT SUM(QuantitySold) AS totalSold FROM sales
                        WHERE ProductID = $productID AND SaleDate = '$reportDateEscaped'";
        $salesResult = mysqli_query($conn, $salesQuery);

        if (!$salesResult) {
            continue;
        }

        $salesData   = mysqli_fetch_assoc($salesResult);
        $totalSold   = $salesData['totalSold'] ? $salesData['totalSold'] : 0;

        $revenue = $totalSold * $product['UnitPrice'];

        $reportMatrix[$row][0] = $product['ProductName'];
        $reportMatrix[$row][1] = $totalSold;
        $reportMatrix[$row][2] = $product['StockQuantity'];
        $reportMatrix[$row][3] = round($revenue, 2);

        $row++;
    }
    return $reportMatrix;
}
?>