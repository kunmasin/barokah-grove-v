<?php

require_once '../config.php';

require_login();

if (!is_admin()) {
    redirect(BASE_URL . '/index.php');
}


/*
|--------------------------------------------------------------------------
| GENERATE INVOICE NUMBER
|--------------------------------------------------------------------------
*/

function generateInvoiceNumber($db)
{
    $prefix = 'BGV-' . date('Ym') . '-';

    $stmt = $db->prepare("
        SELECT invoice_no
        FROM invoices
        WHERE invoice_no LIKE CONCAT(?, '%')
        ORDER BY id DESC
        LIMIT 1
    ");

    $stmt->bind_param("s", $prefix);

    $stmt->execute();

    $result = $stmt->get_result();

    $last = $result->fetch_assoc();

    $stmt->close();


    $nextNumber = 1;


    if ($last && !empty($last['invoice_no'])) {

        $parts = explode('-', $last['invoice_no']);

        $lastNumber = end($parts);

        if (is_numeric($lastNumber)) {
            $nextNumber = ((int)$lastNumber) + 1;
        }
    }


    return $prefix . str_pad(
        $nextNumber,
        4,
        '0',
        STR_PAD_LEFT
    );
}


/*
|--------------------------------------------------------------------------
| GET INVOICE ID
|--------------------------------------------------------------------------
*/

$invoiceId = (int)($_GET['id'] ?? 0);


/*
|--------------------------------------------------------------------------
| CREATE NEW INVOICE
|--------------------------------------------------------------------------
*/

if ($invoiceId <= 0) {

    $invoiceNo = generateInvoiceNumber($db);

    $customerName = '';
    $customerEmail = '';
    $customerPhone = '';

    $subtotal = 0;
    $total = 0;

    $items = [];

    $isNewInvoice = true;

} else {

    /*
    |--------------------------------------------------------------------------
    | FETCH EXISTING INVOICE
    |--------------------------------------------------------------------------
    */

    $stmt = $db->prepare("
        SELECT *
        FROM invoices
        WHERE id = ?
        LIMIT 1
    ");

    $stmt->bind_param("i", $invoiceId);

    $stmt->execute();

    $invoice = $stmt->get_result()->fetch_assoc();

    $stmt->close();


    if (!$invoice) {

        redirect(BASE_URL . '/admin/invoice.php');

    }


    $invoiceNo = $invoice['invoice_no'];

    $customerName = $invoice['customer_name'];

    $customerEmail = $invoice['customer_email'];

    $customerPhone = $invoice['customer_phone'];

    $subtotal = (float)$invoice['subtotal'];

    $total = (float)$invoice['total'];

    $isNewInvoice = false;


    /*
    |--------------------------------------------------------------------------
    | FETCH INVOICE ITEMS
    |--------------------------------------------------------------------------
    */

    $stmt = $db->prepare("
        SELECT *
        FROM invoice_items
        WHERE invoice_id = ?
        ORDER BY id ASC
    ");

    $stmt->bind_param("i", $invoiceId);

    $stmt->execute();

    $items = $stmt
        ->get_result()
        ->fetch_all(MYSQLI_ASSOC);

    $stmt->close();
}


/*
|--------------------------------------------------------------------------
| FETCH PRODUCTS
|--------------------------------------------------------------------------
*/

$products = [];

$stmt = $db->prepare("
    SELECT
        id,
        name,
        price
    FROM products
    ORDER BY name ASC
");

$stmt->execute();

$products = $stmt
    ->get_result()
    ->fetch_all(MYSQLI_ASSOC);

$stmt->close();


/*
|--------------------------------------------------------------------------
| SAVE INVOICE
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $invoiceNo = trim($_POST['invoice_no'] ?? '');

    $customerName = trim($_POST['customer_name'] ?? '');

    $customerEmail = trim($_POST['customer_email'] ?? '');

    $customerPhone = trim($_POST['customer_phone'] ?? '');


    $itemIds = $_POST['item_id'] ?? [];

    $productIds = $_POST['product_id'] ?? [];

    $itemNames = $_POST['item_name'] ?? [];

    $itemPrices = $_POST['item_price'] ?? [];

    $itemQuantities = $_POST['item_quantity'] ?? [];


    /*
    |--------------------------------------------------------------------------
    | VALIDATION
    |--------------------------------------------------------------------------
    */

    if ($invoiceNo === '') {
        $invoiceNo = generateInvoiceNumber($db);
    }

    if ($customerName === '') {
        $customerName = 'Walk-in Customer';
    }


    /*
    |--------------------------------------------------------------------------
    | TRANSACTION
    |--------------------------------------------------------------------------
    */

    $db->begin_transaction();


    try {

        /*
        |--------------------------------------------------------------------------
        | CREATE / UPDATE INVOICE
        |--------------------------------------------------------------------------
        */

        if ($invoiceId > 0) {

            $stmt = $db->prepare("
                UPDATE invoices
                SET
                    invoice_no = ?,
                    customer_name = ?,
                    customer_email = ?,
                    customer_phone = ?
                WHERE id = ?
            ");

            $stmt->bind_param(
                "ssssi",
                $invoiceNo,
                $customerName,
                $customerEmail,
                $customerPhone,
                $invoiceId
            );

            $stmt->execute();

            $stmt->close();

        } else {

            $stmt = $db->prepare("
                INSERT INTO invoices
                (
                    invoice_no,
                    customer_name,
                    customer_email,
                    customer_phone,
                    subtotal,
                    total
                )
                VALUES (?, ?, ?, ?, 0, 0)
            ");

            $stmt->bind_param(
                "ssss",
                $invoiceNo,
                $customerName,
                $customerEmail,
                $customerPhone
            );

            $stmt->execute();

            $invoiceId = $stmt->insert_id;

            $stmt->close();
        }


        /*
        |--------------------------------------------------------------------------
        | DELETE CURRENT ITEMS
        |--------------------------------------------------------------------------
        |
        | Rebuilding the invoice items makes adding/removing products much
        | more reliable than trying to synchronize old/new item IDs.
        |
        */

        $stmt = $db->prepare("
            DELETE FROM invoice_items
            WHERE invoice_id = ?
        ");

        $stmt->bind_param("i", $invoiceId);

        $stmt->execute();

        $stmt->close();


        /*
        |--------------------------------------------------------------------------
        | INSERT ITEMS
        |--------------------------------------------------------------------------
        */

        $subtotal = 0;

        $count = count($itemNames);


        for ($i = 0; $i < $count; $i++) {

            $name = trim($itemNames[$i] ?? '');

            $price = (float)($itemPrices[$i] ?? 0);

            $quantity = (int)($itemQuantities[$i] ?? 1);

            $productId = (int)($productIds[$i] ?? 0);


            /*
            |--------------------------------------------------------------------------
            | SKIP EMPTY ITEM
            |--------------------------------------------------------------------------
            */

            if ($name === '') {
                continue;
            }


            if ($price < 0) {
                $price = 0;
            }


            if ($quantity < 1) {
                $quantity = 1;
            }


            $itemTotal = $price * $quantity;

            $subtotal += $itemTotal;


            /*
            |--------------------------------------------------------------------------
            | PRODUCT ID
            |--------------------------------------------------------------------------
            */

            $productIdValue = $productId > 0
                ? $productId
                : null;


            /*
            |--------------------------------------------------------------------------
            | INSERT ITEM
            |--------------------------------------------------------------------------
            */

            $stmt = $db->prepare("
                INSERT INTO invoice_items
                (
                    invoice_id,
                    product_id,
                    product_name,
                    price,
                    quantity,
                    total
                )
                VALUES (?, ?, ?, ?, ?, ?)
            ");


            $stmt->bind_param(
                "iisdid",
                $invoiceId,
                $productIdValue,
                $name,
                $price,
                $quantity,
                $itemTotal
            );


            $stmt->execute();

            $stmt->close();
        }


        /*
        |--------------------------------------------------------------------------
        | UPDATE TOTALS
        |--------------------------------------------------------------------------
        */

        $total = $subtotal;


        $stmt = $db->prepare("
            UPDATE invoices
            SET
                subtotal = ?,
                total = ?
            WHERE id = ?
        ");


        $stmt->bind_param(
            "ddi",
            $subtotal,
            $total,
            $invoiceId
        );


        $stmt->execute();

        $stmt->close();


        /*
        |--------------------------------------------------------------------------
        | COMMIT
        |--------------------------------------------------------------------------
        */

        $db->commit();


        /*
        |--------------------------------------------------------------------------
        | REDIRECT
        |--------------------------------------------------------------------------
        */

        header(
            "Location: " .
            BASE_URL .
            "/admin/invoice.php?id=" .
            $invoiceId .
            "&saved=1"
        );

        exit;


    } catch (Throwable $e) {

        $db->rollback();

        die(
            "Unable to save invoice: " .
            htmlspecialchars($e->getMessage())
        );
    }
}


/*
|--------------------------------------------------------------------------
| RELOAD INVOICE AFTER SAVE
|--------------------------------------------------------------------------
*/

if ($invoiceId > 0) {

    $stmt = $db->prepare("
        SELECT *
        FROM invoices
        WHERE id = ?
    ");

    $stmt->bind_param("i", $invoiceId);

    $stmt->execute();

    $invoice = $stmt
        ->get_result()
        ->fetch_assoc();

    $stmt->close();


    if ($invoice) {

        $invoiceNo = $invoice['invoice_no'];

        $customerName = $invoice['customer_name'];

        $customerEmail = $invoice['customer_email'];

        $customerPhone = $invoice['customer_phone'];

        $subtotal = (float)$invoice['subtotal'];

        $total = (float)$invoice['total'];
    }


    /*
    |--------------------------------------------------------------------------
    | RELOAD ITEMS
    |--------------------------------------------------------------------------
    */

    $stmt = $db->prepare("
        SELECT *
        FROM invoice_items
        WHERE invoice_id = ?
        ORDER BY id ASC
    ");

    $stmt->bind_param("i", $invoiceId);

    $stmt->execute();

    $items = $stmt
        ->get_result()
        ->fetch_all(MYSQLI_ASSOC);

    $stmt->close();
}

?>

<!doctype html>

<html lang="en">

<head>

<meta charset="utf-8">

<meta
    name="viewport"
    content="width=device-width,initial-scale=1"
>

<title>
    <?= e($invoiceNo) ?> |
    <?= e(SITE_NAME) ?>
</title>

<link
    rel="stylesheet"
    href="../assets/css/style.css"
>


<style>

/* =========================================================
   INVOICE PAGE
========================================================= */

.invoice-page {
    max-width: 1100px;
    margin: 30px auto;
    padding: 0 20px;
}


/* =========================================================
   ACTION BAR
========================================================= */

.invoice-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin-bottom: 20px;
}


/* =========================================================
   PRODUCT SELECTOR
========================================================= */

.invoice-product-selector {
    display: flex;
    gap: 10px;
    align-items: end;
    margin: 20px 0;
    padding: 18px;
    background: #f7f7f7;
    border: 1px solid #ddd;
    border-radius: 10px;
}

.invoice-product-selector-group {
    flex: 1;
}

.invoice-product-selector label {
    display: block;
    margin-bottom: 6px;
    font-weight: 600;
}

.invoice-product-select {
    width: 100%;
    padding: 11px;
    border: 1px solid #ccc;
    border-radius: 7px;
    background: white;
}


/* =========================================================
   ITEM ACTIONS
========================================================= */

.invoice-item-actions {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}


/* =========================================================
   CUSTOMER
========================================================= */

.invoice-customer-fields {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 15px;
    margin-top: 15px;
}

.invoice-field label {
    display: block;
    font-weight: 600;
    margin-bottom: 5px;
}


/* =========================================================
   EDIT INPUTS
========================================================= */

.invoice-edit-input,
.invoice-item-input {
    width: 100%;
    box-sizing: border-box;
    padding: 9px;
    border: 1px solid #ccc;
    border-radius: 6px;
}


/* =========================================================
   ALERT
========================================================= */

.invoice-alert {
    max-width: 1100px;
    margin: 15px auto;
    padding: 12px 18px;
    background: #e8f7ed;
    color: #176b35;
    border: 1px solid #b9e6c7;
    border-radius: 8px;
}


/* =========================================================
   MOBILE
========================================================= */

@media(max-width:700px) {

    .invoice-customer-fields {
        grid-template-columns: 1fr;
    }

    .invoice-product-selector {
        flex-direction: column;
        align-items: stretch;
    }

}


/* =========================================================
   PRINT
========================================================= */

@media print {

    .site-header,
    .invoice-actions,
    .invoice-product-selector,
    .invoice-items-header button,
    .invoice-remove-btn,
    .invoice-item-actions,
    .btn {
        display: none !important;
    }

    body {
        background: white !important;
    }

    .invoice-page {
        max-width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
    }

    .invoice {
        width: 100% !important;
        box-shadow: none !important;
        border: none !important;
    }

    input {
        border: none !important;
        background: transparent !important;
    }

}

</style>

</head>


<body>

<?php include '../includes/header.php'; ?>


<?php if (isset($_GET['saved'])): ?>

<div class="invoice-alert">

    ✓ Invoice saved successfully.

</div>

<?php endif; ?>


<div class="invoice-page">


<!-- =========================================================
     ACTIONS
========================================================= -->

<div class="invoice-actions">

    <button
        type="submit"
        form="invoiceForm"
        class="btn"
    >
        Save Invoice
    </button>


    <button
        type="button"
        class="btn btn-outline"
        onclick="window.print()"
    >
        Print / Save as PDF
    </button>


    <a
        href="<?= BASE_URL ?>/admin/transaction_history.php"
        class="btn btn-outline"
    >
        Invoice History
    </a>


    <!--<a
        href="<?= BASE_URL ?>/admin/index.php"
        class="btn btn-outline"
    >
        Dashboard
    </a>-->

</div>


<!-- =========================================================
     FORM
========================================================= -->

<form
    method="POST"
    id="invoiceForm"
>


<section class="invoice">


<!-- =========================================================
     HEADER
========================================================= -->

<div class="invoice-head">

    <div>

        <h1>
            BARAKAH GROVE VENTURES
        </h1>

        <p>
            Home of Quality Kitchen and Household Items
        </p>

    </div>


    <div class="invoice-meta">

        <!--<strong>
            INVOICE
        </strong> -->


        <label class="invoice-label">
            Invoice Number
        </label>


        <input
            type="text"
            name="invoice_no"
            value="<?= e($invoiceNo) ?>"
            class="invoice-edit-input"
            required
        >


        <p>

            <?= date('M d, Y') ?>

        </p>

    </div>

</div>


<!-- =========================================================
     CUSTOMER
========================================================= -->

<div class="invoice-customer">

    <strong class="invoice-section-title">
        Billed To
    </strong>


    <div class="invoice-customer-fields">


        <div class="invoice-field">

            <label>
                Full Name
            </label>

            <input
                type="text"
                name="customer_name"
                value="<?= e($customerName) ?>"
                class="invoice-edit-input"
                placeholder="Customer name"
                required
            >

        </div>


        <div class="invoice-field">

            <label>
                Email Address
            </label>

            <input
                type="email"
                name="customer_email"
                value="<?= e($customerEmail) ?>"
                class="invoice-edit-input"
                placeholder="customer@example.com"
            >

        </div>


        <div class="invoice-field">

            <label>
                Phone Number
            </label>

            <input
                type="text"
                name="customer_phone"
                value="<?= e($customerPhone) ?>"
                class="invoice-edit-input"
                placeholder="080XXXXXXXX"
            >

        </div>


    </div>

</div>


<!-- =========================================================
     ITEMS HEADER
========================================================= -->

<div class="invoice-items-header">

    <div>

        <h3>
            Invoice Items
        </h3>

        <p style="margin:4px 0;color:#777;font-size:13px;">

            Select a product from your product list or add
            a custom item.

        </p>

    </div>


    <div class="invoice-item-actions">

        <button
            type="button"
            class="btn btn-small"
            onclick="addSelectedProduct()"
        >
            + Add Product
        </button>


        <button
            type="button"
            class="btn btn-small btn-outline"
            onclick="addCustomItem()"
        >
            + Custom Item
        </button>

    </div>

</div>


<!-- =========================================================
     PRODUCT SELECTOR
========================================================= -->

<div class="invoice-product-selector">

    <div class="invoice-product-selector-group">

        <label for="productSelector">
            Select Product
        </label>


        <select
            id="productSelector"
            class="invoice-product-select"
        >

            <option value="">
                -- Select a product --
            </option>


            <?php foreach ($products as $product): ?>

                <option
                    value="<?= (int)$product['id'] ?>"
                    data-name="<?= e($product['name']) ?>"
                    data-price="<?= e($product['price']) ?>"
                >

                    <?= e($product['name']) ?>
                    —
                    <?= money($product['price']) ?>

                </option>

            <?php endforeach; ?>


        </select>

    </div>


    <button
        type="button"
        class="btn"
        onclick="addSelectedProduct()"
    >
        Add Selected
    </button>

</div>


<!-- =========================================================
     ITEMS TABLE
========================================================= -->

<div class="invoice-table-wrap">

<table class="invoice-table">

<thead>

<tr>

<th>
    Item
</th>

<th>
    Price
</th>

<th>
    Qty
</th>

<th>
    Total
</th>

<th>
    Action
</th>

</tr>

</thead>


<tbody id="invoiceItems">


<?php foreach ($items as $item): ?>

<tr>

<td>

<input
    type="hidden"
    name="item_id[]"
    value="<?= (int)$item['id'] ?>"
>


<input
    type="hidden"
    name="product_id[]"
    value="<?= (int)($item['product_id'] ?? 0) ?>"
>


<input
    type="text"
    name="item_name[]"
    value="<?= e($item['product_name']) ?>"
    class="invoice-item-input item-name"
    required
>

</td>


<td>

<input
    type="number"
    name="item_price[]"
    value="<?= e($item['price']) ?>"
    class="invoice-item-input item-price"
    min="0"
    step="0.01"
    required
>

</td>


<td>

<input
    type="number"
    name="item_quantity[]"
    value="<?= (int)$item['quantity'] ?>"
    class="invoice-item-input item-quantity"
    min="1"
    required
>

</td>


<td>

<span class="item-total">

    <?= money($item['total']) ?>

</span>

</td>


<td>

<button
    type="button"
    class="invoice-remove-btn"
    onclick="removeInvoiceItem(this)"
>
    Remove
</button>

</td>

</tr>

<?php endforeach; ?>


<?php if (empty($items)): ?>

<tr id="noItemsRow">

<td colspan="5">

<div class="invoice-no-items">

    No products added yet.

</div>

</td>

</tr>

<?php endif; ?>


</tbody>

</table>

</div>


<!-- =========================================================
     TOTAL
========================================================= -->

<div class="invoice-total">

<div class="summary-row">

<span>
    Subtotal
</span>

<strong id="invoiceSubtotal">

    <?= money($subtotal) ?>

</strong>

</div>


<div class="summary-row summary-total">

<span>
    Total
</span>

<strong id="invoiceTotal">

    <?= money($total) ?>

</strong>

</div>

</div>


<!-- =========================================================
     FOOTER
========================================================= -->

<div class="invoice-thank-you">

    Thank you for shopping with
    <strong>
        Barakah Grove Ventures.
    </strong>

</div>


</section>

</form>

</div>


<?php include '../includes/footer.php'; ?>


<script>

function formatMoney(amount)
{
    return '₦' + Number(amount).toLocaleString(
        'en-NG',
        {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }
    );
}


/*
|--------------------------------------------------------------------------
| CALCULATE TOTAL
|--------------------------------------------------------------------------
*/

function calculateInvoice()
{

    let subtotal = 0;


    document
        .querySelectorAll('#invoiceItems tr')
        .forEach(function(row)
        {

            const price =
                parseFloat(
                    row.querySelector('.item-price')?.value
                ) || 0;


            const quantity =
                parseInt(
                    row.querySelector('.item-quantity')?.value
                ) || 0;


            const total =
                price * quantity;


            const totalElement =
                row.querySelector('.item-total');


            if (totalElement)
            {
                totalElement.textContent =
                    formatMoney(total);
            }


            subtotal += total;

        });


    document.getElementById(
        'invoiceSubtotal'
    ).textContent =
        formatMoney(subtotal);


    document.getElementById(
        'invoiceTotal'
    ).textContent =
        formatMoney(subtotal);

}


/*
|--------------------------------------------------------------------------
| REMOVE EMPTY MESSAGE
|--------------------------------------------------------------------------
*/

function removeNoItems()
{

    const row =
        document.getElementById('noItemsRow');


    if (row)
    {
        row.remove();
    }

}


/*
|--------------------------------------------------------------------------
| ADD PRODUCT
|--------------------------------------------------------------------------
*/

function addSelectedProduct()
{

    const selector =
        document.getElementById(
            'productSelector'
        );


    if (!selector)
    {
        return;
    }


    const option =
        selector.options[
            selector.selectedIndex
        ];


    if (!option || !option.value)
    {

        alert(
            'Please select a product first.'
        );

        return;

    }


    const productId =
        option.value;


    const productName =
        option.dataset.name;


    const productPrice =
        parseFloat(
            option.dataset.price
        ) || 0;


    addProductRow(
        productId,
        productName,
        productPrice
    );


    selector.value = '';

}


/*
|--------------------------------------------------------------------------
| ADD PRODUCT ROW
|--------------------------------------------------------------------------
*/

function addProductRow(
    productId,
    productName,
    productPrice
)
{

    removeNoItems();


    const tbody =
        document.getElementById(
            'invoiceItems'
        );


    const row =
        document.createElement('tr');


    row.innerHTML = `

        <td>

            <input
                type="hidden"
                name="item_id[]"
                value="0"
            >

            <input
                type="hidden"
                name="product_id[]"
                value="${escapeHtml(productId)}"
            >

            <input
                type="text"
                name="item_name[]"
                value="${escapeHtml(productName)}"
                class="invoice-item-input item-name"
                required
            >

        </td>


        <td>

            <input
                type="number"
                name="item_price[]"
                value="${Number(productPrice).toFixed(2)}"
                class="invoice-item-input item-price"
                min="0"
                step="0.01"
                required
            >

        </td>


        <td>

            <input
                type="number"
                name="item_quantity[]"
                value="1"
                class="invoice-item-input item-quantity"
                min="1"
                required
            >

        </td>


        <td>

            <span class="item-total">
                ${formatMoney(productPrice)}
            </span>

        </td>


        <td>

            <button
                type="button"
                class="invoice-remove-btn"
                onclick="removeInvoiceItem(this)"
            >
                Remove
            </button>

        </td>

    `;


    tbody.appendChild(row);


    attachEvents(row);


    calculateInvoice();

}


/*
|--------------------------------------------------------------------------
| ADD CUSTOM ITEM
|--------------------------------------------------------------------------
*/

function addCustomItem()
{

    removeNoItems();


    const tbody =
        document.getElementById(
            'invoiceItems'
        );


    const row =
        document.createElement('tr');


    row.innerHTML = `

        <td>

            <input
                type="hidden"
                name="item_id[]"
                value="0"
            >

            <input
                type="hidden"
                name="product_id[]"
                value="0"
            >

            <input
                type="text"
                name="item_name[]"
                class="invoice-item-input item-name"
                placeholder="Item name"
                required
            >

        </td>


        <td>

            <input
                type="number"
                name="item_price[]"
                class="invoice-item-input item-price"
                value="0"
                min="0"
                step="0.01"
                required
            >

        </td>


        <td>

            <input
                type="number"
                name="item_quantity[]"
                class="invoice-item-input item-quantity"
                value="1"
                min="1"
                required
            >

        </td>


        <td>

            <span class="item-total">
                ₦0.00
            </span>

        </td>


        <td>

            <button
                type="button"
                class="invoice-remove-btn"
                onclick="removeInvoiceItem(this)"
            >
                Remove
            </button>

        </td>

    `;


    tbody.appendChild(row);


    attachEvents(row);


    calculateInvoice();


    row.querySelector(
        '.item-name'
    ).focus();

}


/*
|--------------------------------------------------------------------------
| REMOVE ITEM
|--------------------------------------------------------------------------
*/

function removeInvoiceItem(button)
{

    const row =
        button.closest('tr');


    if (row)
    {
        row.remove();
    }


    const tbody =
        document.getElementById(
            'invoiceItems'
        );


    if (
        tbody &&
        tbody.querySelectorAll('tr').length === 0
    )
    {

        tbody.innerHTML = `

            <tr id="noItemsRow">

                <td colspan="5">

                    <div class="invoice-no-items">

                        No products added yet.

                    </div>

                </td>

            </tr>

        `;

    }


    calculateInvoice();

}


/*
|--------------------------------------------------------------------------
| EVENTS
|--------------------------------------------------------------------------
*/

function attachEvents(row)
{

    row
        .querySelectorAll(
            '.item-price, .item-quantity'
        )
        .forEach(function(input)
        {

            input.addEventListener(
                'input',
                calculateInvoice
            );

        });

}


/*
|--------------------------------------------------------------------------
| ESCAPE HTML
|--------------------------------------------------------------------------
*/

function escapeHtml(value)
{

    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');

}


/*
|--------------------------------------------------------------------------
| INITIALISE
|--------------------------------------------------------------------------
*/

document
    .querySelectorAll(
        '#invoiceItems tr'
    )
    .forEach(function(row)
    {

        attachEvents(row);

    });


calculateInvoice();

</script>


</body>

</html>