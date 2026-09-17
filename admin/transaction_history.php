<?php
require_once '../config.php';
require_login();

if (!is_admin()) {
    redirect('../index.php');
}


/*
|--------------------------------------------------------------------------
| SEARCH
|--------------------------------------------------------------------------
*/

$search = trim($_GET['search'] ?? '');

$status = trim($_GET['status'] ?? '');


/*
|--------------------------------------------------------------------------
| FETCH INVOICE HISTORY
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        o.id,
        o.invoice_no,
        o.user_id,
        o.subtotal,
        o.total,
        o.status,
        o.created_at,
        u.full_name,
        u.email,
        u.phone
    FROM orders o
    LEFT JOIN users u
        ON u.id = o.user_id
    WHERE 1=1
";

$params = [];
$types = "";


/*
|--------------------------------------------------------------------------
| SEARCH FILTER
|--------------------------------------------------------------------------
*/

if ($search !== '') {

    $sql .= "
        AND (
            o.invoice_no LIKE ?
            OR u.full_name LIKE ?
            OR u.email LIKE ?
            OR u.phone LIKE ?
        )
    ";

    $searchValue = "%" . $search . "%";

    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;

    $types .= "ssss";
}


/*
|--------------------------------------------------------------------------
| STATUS FILTER
|--------------------------------------------------------------------------
*/

if ($status !== '') {

    $sql .= " AND o.status = ? ";

    $params[] = $status;

    $types .= "s";
}


/*
|--------------------------------------------------------------------------
| ORDER
|--------------------------------------------------------------------------
*/

$sql .= "
    ORDER BY o.id DESC
";


$stmt = $db->prepare($sql);


if (!empty($params)) {

    $stmt->bind_param(
        $types,
        ...$params
    );
}


$stmt->execute();

$transactions = $stmt
    ->get_result()
    ->fetch_all(MYSQLI_ASSOC);

$stmt->close();


/*
|--------------------------------------------------------------------------
| SUMMARY
|--------------------------------------------------------------------------
*/

$totalInvoices = count($transactions);

$totalAmount = 0;

foreach ($transactions as $transaction) {

    $totalAmount += (float)$transaction['total'];

}

?>

<!doctype html>

<html lang="en">

<head>

<meta charset="utf-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1"
>

<title>
    Transaction History |
    <?= e(SITE_NAME) ?>
</title>

<link
    rel="stylesheet"
    href="../assets/css/style.css"
>


<style>

.transaction-page {
    padding: 30px 0 60px;
}

.transaction-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 20px;
    margin-bottom: 25px;
}

.transaction-header h1 {
    margin: 0;
}

.transaction-header p {
    margin: 5px 0 0;
    color: #777;
}


.transaction-summary {
    display: grid;
    grid-template-columns:
        repeat(2, minmax(0, 1fr));

    gap: 20px;

    margin-bottom: 25px;
}


.transaction-card {
    background: #fff;
    padding: 22px;
    border-radius: 12px;
    border: 1px solid #eee;
    box-shadow: 0 3px 12px rgba(0,0,0,.05);
}

.transaction-card span {
    display: block;
    color: #777;
    font-size: 14px;
}

.transaction-card strong {
    display: block;
    margin-top: 7px;
    font-size: 24px;
}


.transaction-filters {
    background: #fff;

    padding: 20px;

    border-radius: 12px;

    border: 1px solid #eee;

    margin-bottom: 25px;
}


.transaction-filter-form {
    display: flex;

    gap: 12px;

    flex-wrap: wrap;
}


.transaction-filter-form input,
.transaction-filter-form select {

    padding: 11px 14px;

    border: 1px solid #ddd;

    border-radius: 7px;

    min-width: 200px;

}


.transaction-table-wrap {

    background: #fff;

    border-radius: 12px;

    border: 1px solid #eee;

    overflow-x: auto;

}


.transaction-table {

    width: 100%;

    border-collapse: collapse;

}


.transaction-table th {

    background: #f7f7f7;

    padding: 15px;

    text-align: left;

    font-size: 13px;

    white-space: nowrap;

}


.transaction-table td {

    padding: 15px;

    border-top: 1px solid #eee;

    vertical-align: middle;

}


.transaction-table tr:hover {

    background: #fafafa;

}


.invoice-number {

    font-weight: 700;

}


.customer-name {

    font-weight: 600;

}


.customer-contact {

    color: #777;

    font-size: 12px;

    margin-top: 3px;

}


.amount {

    font-weight: 700;

    white-space: nowrap;

}


.transaction-actions {

    display: flex;

    gap: 7px;

    flex-wrap: wrap;

}


.transaction-actions a {

    text-decoration: none;

}


.status {

    display: inline-block;

    padding: 5px 10px;

    border-radius: 20px;

    font-size: 12px;

    font-weight: 600;

}


.status-paid {

    background: #dff7e8;

    color: #18753a;

}


.status-pending {

    background: #fff3cd;

    color: #856404;

}


.status-cancelled {

    background: #f8d7da;

    color: #842029;

}


.empty-transactions {

    padding: 60px 20px;

    text-align: center;

    color: #777;

}


@media (max-width: 700px) {

    .transaction-summary {

        grid-template-columns: 1fr;

    }

    .transaction-header {

        flex-direction: column;

        align-items: flex-start;

    }

}

</style>

</head>


<body>

<?php include '../includes/header.php'; ?>


<main class="transaction-page">

<div class="container">


<!-- =====================================================
     HEADER
====================================================== -->

<div class="transaction-header">

    <div>

        <h1>
            Transaction History
        </h1>

        <p>
            Complete history of invoices and customer transactions.
        </p>

    </div>


    <a
        href="<?= BASE_URL ?>/admin/invoice.php"
        class="btn"
    >
        + Create Invoice
    </a>

</div>



<!-- =====================================================
     SUMMARY
====================================================== -->

<div class="transaction-summary">


    <div class="transaction-card">

        <span>
            Total Invoices
        </span>

        <strong>
            <?= number_format($totalInvoices) ?>
        </strong>

    </div>


    <div class="transaction-card">

        <span>
            Total Transaction Value
        </span>

        <strong>
            <?= money($totalAmount) ?>
        </strong>

    </div>


</div>



<!-- =====================================================
     FILTERS
====================================================== -->

<div class="transaction-filters">

<form
    method="GET"
    class="transaction-filter-form"
>

    <input
        type="search"
        name="search"
        value="<?= e($search) ?>"
        placeholder="Search invoice, customer, email or phone..."
    >


    <select name="status">

        <option value="">
            All Statuses
        </option>

        <option
            value="paid"
            <?= $status === 'paid' ? 'selected' : '' ?>
        >
            Paid
        </option>

        <option
            value="pending"
            <?= $status === 'pending' ? 'selected' : '' ?>
        >
            Pending
        </option>

        <option
            value="cancelled"
            <?= $status === 'cancelled' ? 'selected' : '' ?>
        >
            Cancelled
        </option>

    </select>


    <button
        type="submit"
        class="btn"
    >
        Search
    </button>


    <?php if ($search !== '' || $status !== ''): ?>

        <a
            href="<?= BASE_URL ?>/admin/transaction_history.php"
            class="btn btn-outline"
        >
            Clear
        </a>

    <?php endif; ?>

</form>

</div>



<!-- =====================================================
     TRANSACTIONS TABLE
====================================================== -->

<div class="transaction-table-wrap">

<table class="transaction-table">

<thead>

<tr>

    <th>
        #
    </th>

    <th>
        Invoice
    </th>

    <th>
        Customer
    </th>

    <th>
        Amount
    </th>

    <th>
        Status
    </th>

    <th>
        Date
    </th>

    <th>
        Actions
    </th>

</tr>

</thead>


<tbody>


<?php if (empty($transactions)): ?>

<tr>

    <td
        colspan="7"
        class="empty-transactions"
    >

        No transactions found.

    </td>

</tr>

<?php endif; ?>


<?php foreach ($transactions as $transaction): ?>


<tr>


    <!-- ID -->

    <td>

        <?= (int)$transaction['id'] ?>

    </td>


    <!-- INVOICE -->

    <td>

        <div class="invoice-number">

            <?= e($transaction['invoice_no']) ?>

        </div>

    </td>


    <!-- CUSTOMER -->

    <td>

        <div class="customer-name">

            <?= e(
                $transaction['full_name']
                ?: 'Walk-in Customer'
            ) ?>

        </div>


        <?php if (!empty($transaction['email'])): ?>

            <div class="customer-contact">

                <?= e($transaction['email']) ?>

            </div>

        <?php endif; ?>


        <?php if (!empty($transaction['phone'])): ?>

            <div class="customer-contact">

                <?= e($transaction['phone']) ?>

            </div>

        <?php endif; ?>

    </td>


    <!-- AMOUNT -->

    <td>

        <span class="amount">

            <?= money($transaction['total']) ?>

        </span>

    </td>


    <!-- STATUS -->

    <td>

        <?php

        $transactionStatus =
            strtolower(
                trim(
                    $transaction['status']
                    ?? 'pending'
                )
            );

        ?>


        <span
            class="status status-<?= e($transactionStatus) ?>"
        >

            <?= e(
                ucfirst($transactionStatus)
            ) ?>

        </span>

    </td>


    <!-- DATE -->

    <td>

        <?= date(
            'M d, Y',
            strtotime(
                $transaction['created_at']
            )
        ) ?>

        <br>

        <small style="color:#777">

            <?= date(
                'h:i A',
                strtotime(
                    $transaction['created_at']
                )
            ) ?>

        </small>

    </td>


    <!-- ACTIONS -->

    <td>

        <div class="transaction-actions">

            <a
                href="<?= BASE_URL ?>/admin/invoice.php?id=<?= (int)$transaction['id'] ?>"
                class="btn btn-small"
            >
                View
            </a>


            <a
                href="<?= BASE_URL ?>/admin/invoice.php?id=<?= (int)$transaction['id'] ?>"
                class="btn btn-small btn-outline"
            >
                Edit
            </a>


            <a
                href="<?= BASE_URL ?>/admin/invoice.php?id=<?= (int)$transaction['id'] ?>"
                target="_blank"
                class="btn btn-small btn-outline"
            >
                Print
            </a>

        </div>

    </td>


</tr>


<?php endforeach; ?>


</tbody>

</table>

</div>


</div>

</main>


<?php include '../includes/footer.php'; ?>


</body>

</html>