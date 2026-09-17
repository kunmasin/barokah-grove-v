<?php
require_once 'config.php';
require_login();

$id = (int)($_GET['id'] ?? 0);

/*
|--------------------------------------------------------------------------
| FETCH ORDER
|--------------------------------------------------------------------------
*/

$stmt = $db->prepare("
    SELECT 
        o.*,
        u.full_name,
        u.email,
        u.phone
    FROM orders o
    JOIN users u ON u.id = o.user_id
    WHERE o.id = ?
");

$stmt->bind_param("i", $id);
$stmt->execute();

$order = $stmt->get_result()->fetch_assoc();

if (
    !$order ||
    (
        $order['user_id'] != $_SESSION['user_id']
        && !is_admin()
    )
) {
    redirect('index.php');
}


/*
|--------------------------------------------------------------------------
| UPDATE INVOICE - ADMIN ONLY
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST' && is_admin()) {

    $invoice_no = trim($_POST['invoice_no'] ?? '');
    $invoice_date = trim($_POST['invoice_date'] ?? '');

    $customer_name = trim($_POST['customer_name'] ?? '');
    $customer_email = trim($_POST['customer_email'] ?? '');
    $customer_phone = trim($_POST['customer_phone'] ?? '');

    $item_names = $_POST['item_name'] ?? [];
    $item_prices = $_POST['item_price'] ?? [];
    $item_quantities = $_POST['item_quantity'] ?? [];

    if ($invoice_no === '') {
        $invoice_no = $order['invoice_no'];
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE CUSTOMER INFORMATION
    |--------------------------------------------------------------------------
    |
    | Customer details are stored in users table.
    |
    */

    $stmt = $db->prepare("
        UPDATE users
        SET full_name = ?, email = ?, phone = ?
        WHERE id = ?
    ");

    $stmt->bind_param(
        "sssi",
        $customer_name,
        $customer_email,
        $customer_phone,
        $order['user_id']
    );

    $stmt->execute();


    /*
    |--------------------------------------------------------------------------
    | UPDATE INVOICE INFORMATION
    |--------------------------------------------------------------------------
    */

    $stmt = $db->prepare("
        UPDATE orders
        SET invoice_no = ?
        WHERE id = ?
    ");

    $stmt->bind_param(
        "si",
        $invoice_no,
        $id
    );

    $stmt->execute();


    /*
    |--------------------------------------------------------------------------
    | UPDATE ITEMS
    |--------------------------------------------------------------------------
    */

    $subtotal = 0;

    $stmt = $db->prepare("
        SELECT id
        FROM order_items
        WHERE order_id = ?
        ORDER BY id ASC
    ");

    $stmt->bind_param("i", $id);
    $stmt->execute();

    $existingItems = $stmt
        ->get_result()
        ->fetch_all(MYSQLI_ASSOC);


    foreach ($existingItems as $index => $existingItem) {

        if (
            !isset($item_names[$index]) ||
            !isset($item_prices[$index]) ||
            !isset($item_quantities[$index])
        ) {
            continue;
        }

        $name = trim($item_names[$index]);

        $price = (float)$item_prices[$index];

        $quantity = max(
            1,
            (int)$item_quantities[$index]
        );

        $total = $price * $quantity;

        $subtotal += $total;


        $stmt = $db->prepare("
            UPDATE order_items
            SET product_name = ?,
                price = ?,
                quantity = ?,
                total = ?
            WHERE id = ?
            AND order_id = ?
        ");

        $stmt->bind_param(
            "sdidii",
            $name,
            $price,
            $quantity,
            $total,
            $existingItem['id'],
            $id
        );

        $stmt->execute();
    }


    /*
    |--------------------------------------------------------------------------
    | UPDATE ORDER TOTALS
    |--------------------------------------------------------------------------
    */

    $stmt = $db->prepare("
        UPDATE orders
        SET subtotal = ?,
            total = ?
        WHERE id = ?
    ");

    $stmt->bind_param(
        "ddi",
        $subtotal,
        $subtotal,
        $id
    );

    $stmt->execute();


    /*
    |--------------------------------------------------------------------------
    | SUCCESS
    |--------------------------------------------------------------------------
    */

    header("Location: invoice.php?id=" . $id . "&updated=1");
    exit;
}


/*
|--------------------------------------------------------------------------
| FETCH UPDATED ORDER
|--------------------------------------------------------------------------
*/

$stmt = $db->prepare("
    SELECT 
        o.*,
        u.full_name,
        u.email,
        u.phone
    FROM orders o
    JOIN users u ON u.id = o.user_id
    WHERE o.id = ?
");

$stmt->bind_param("i", $id);
$stmt->execute();

$order = $stmt->get_result()->fetch_assoc();


/*
|--------------------------------------------------------------------------
| FETCH ITEMS
|--------------------------------------------------------------------------
*/

$stmt = $db->prepare("
    SELECT *
    FROM order_items
    WHERE order_id = ?
    ORDER BY id ASC
");

$stmt->bind_param("i", $id);
$stmt->execute();

$items = $stmt
    ->get_result()
    ->fetch_all(MYSQLI_ASSOC);

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
    <?= e($order['invoice_no']) ?>
    |
    <?= e(SITE_NAME) ?>
</title>

<link
    rel="stylesheet"
    href="assets/css/style.css"
>

</head>


<body>

<?php include 'includes/header.php'; ?>


<!-- =========================================================
     ACTIONS
========================================================= -->

<div class="print-actions">

    <?php if (is_admin()): ?>

        <button
            type="submit"
            form="invoiceForm"
            class="btn"
        >
            Save Invoice
        </button>

    <?php endif; ?>


    <button
        class="btn btn-outline"
        onclick="window.print()"
    >
        Print / Save as PDF
    </button>


    <a
        class="btn btn-outline"
        href="<?= is_admin()
            ? BASE_URL . '/admin/orders.php'
            : BASE_URL . '/my_orders.php'
        ?>"
    >
        Back
    </a>

</div>


<?php if (isset($_GET['updated'])): ?>

    <div class="invoice-alert">
        Invoice updated successfully.
    </div>

<?php endif; ?>


<!-- =========================================================
     INVOICE
========================================================= -->

<form
    method="POST"
    id="invoiceForm"
>

<section class="invoice">


    <!-- =====================================================
         INVOICE HEADER
    ====================================================== -->

    <div class="invoice-head">

        <div>

            <h1 style="color:blue">
                BARAKAH GROVE VENTURES
            </h1>

            <p>Home of Quality Kitchen and Houshold Items</p>

        </div>


        <div class="invoice-meta">

            <strong>
                INVOICE
            </strong>


            <?php if (is_admin()): ?>

                <input
                    type="text"
                    name="invoice_no"
                    value="<?= e($order['invoice_no']) ?>"
                    class="invoice-edit-input"
                >

            <?php else: ?>

                <p>
                    <?= e($order['invoice_no']) ?>
                </p>

            <?php endif; ?>


            <p>
                <?= e($order['created_at']) ?>
            </p>

        </div>

    </div>



    <!-- =====================================================
         CUSTOMER
    ====================================================== -->

    <div class="invoice-customer">

        <strong style="color:blue">
            Billed to:
        </strong>


        <?php if (is_admin()): ?>

            <div class="invoice-customer-fields" style="color:blue">

                <div>

                    <label>
                        Full Name
                    </label> <input type="text" name="customer_name" value="<?= e($order['full_name']) ?>" class="invoice-edit-input"
required>

                </div>

<br>
                <div>

                    <label>
                        Email
                    </label>

                    <input
                        type="email"
                        name="customer_email"
                        value="<?= e($order['email']) ?>"
                        class="invoice-edit-input"
                        required
                    >

                </div>


                <div>

                    <label>
                        Phone
                    </label>

                    <input
                        type="text"
                        name="customer_phone"
                        value="<?= e($order['phone']) ?>"
                        class="invoice-edit-input"
                    >

                </div>

            </div>

        <?php else: ?>

            <div class="invoice-customer-display">

                <?= e($order['full_name']) ?><br>

                <?= e($order['email']) ?><br>

                <?= e($order['phone']) ?>

            </div>

        <?php endif; ?>

    </div>



    <!-- =====================================================
         ITEMS
    ====================================================== -->

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

            </tr>

        </thead>


        <tbody>


        <?php foreach ($items as $index => $item): ?>

            <tr>


                <!-- ITEM NAME -->

                <td>

                    <?php if (is_admin()): ?>

                        <input
                            type="text"
                            name="item_name[]"
                            value="<?= e($item['product_name']) ?>"
                            class="invoice-item-input"
                            required
                        >

                    <?php else: ?>

                        <?= e($item['product_name']) ?>

                    <?php endif; ?>

                </td>



                <!-- PRICE -->

                <td>

                    <?php if (is_admin()): ?>

                        <input
                            type="number"
                            name="item_price[]"
                            value="<?= e($item['price']) ?>"
                            class="invoice-item-input item-price"
                            min="0"
                            step="0.01"
                            required
                        >

                    <?php else: ?>

                        <?= money($item['price']) ?>

                    <?php endif; ?>

                </td>



                <!-- QUANTITY -->

                <td>

                    <?php if (is_admin()): ?>

                        <input
                            type="number"
                            name="item_quantity[]"
                            value="<?= (int)$item['quantity'] ?>"
                            class="invoice-item-input item-quantity"
                            min="1"
                            required
                        >

                    <?php else: ?>

                        <?= (int)$item['quantity'] ?>

                    <?php endif; ?>

                </td>



                <!-- TOTAL -->

                <td>

                    <span class="item-total">

                        <?= money($item['total']) ?>

                    </span>

                </td>

            </tr>

        <?php endforeach; ?>


        </tbody>

    </table>



    <!-- =====================================================
         TOTAL
    ====================================================== -->

    <div class="invoice-total">

        <div class="summary-row">

            <span>
                Subtotal
            </span>

            <strong id="invoiceSubtotal">
                <?= money($order['subtotal']) ?>
            </strong>

        </div>


        <div class="summary-row summary-total">

            <span>
                Total
            </span>

            <strong id="invoiceTotal">
                <?= money($order['total']) ?>
            </strong>

        </div>

    </div>


    <p
        style="
            margin-top:45px;
            color: black;
            background: gold;
            padding: 40px;

        "
    >
        Thank you for shopping with
        Barakah Grove Ventures.
    </p>


</section>

</form>


<?php include 'includes/footer.php'; ?>


<!-- =========================================================
     ADMIN CALCULATION
========================================================= -->

<?php if (is_admin()): ?>

<script>

function formatMoney(amount) {

    return '₦' + Number(amount).toLocaleString(
        'en-NG',
        {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }
    );

}


function calculateInvoice() {

    let subtotal = 0;

    const rows = document.querySelectorAll(
        '.invoice-table tbody tr'
    );


    rows.forEach(function(row) {

        const priceInput =
            row.querySelector('.item-price');

        const quantityInput =
            row.querySelector('.item-quantity');

        const totalElement =
            row.querySelector('.item-total');


        if (!priceInput || !quantityInput) {
            return;
        }


        const price =
            parseFloat(priceInput.value) || 0;

        const quantity =
            parseInt(quantityInput.value) || 0;


        const total =
            price * quantity;


        subtotal += total;


        totalElement.textContent =
            formatMoney(total);

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


document.querySelectorAll(
    '.item-price, .item-quantity'
).forEach(function(input) {

    input.addEventListener(
        'input',
        calculateInvoice
    );

});


</script>

<?php endif; ?>


</body>

</html>