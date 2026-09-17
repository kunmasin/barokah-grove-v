<?php
require_once '../config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/*
|--------------------------------------------------------------------------
| AUTHENTICATION
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| ADMIN ACCESS
|--------------------------------------------------------------------------
*/

if (isset($_SESSION['role']) && $_SESSION['role'] !== 'admin') {
    header("Location: dashboard.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| SEARCH & FILTER
|--------------------------------------------------------------------------
*/

$search = trim($_GET['search'] ?? '');
$role   = $_GET['role'] ?? '';

$page = max(1, (int)($_GET['page'] ?? 1));

$limit = 10;

$offset = ($page - 1) * $limit;


/*
|--------------------------------------------------------------------------
| BUILD QUERY
|--------------------------------------------------------------------------
*/

$where = "WHERE 1=1";

$params = [];

$types = "";


/*
|--------------------------------------------------------------------------
| SEARCH BY NAME, EMAIL OR PHONE
|--------------------------------------------------------------------------
*/

if ($search !== '') {

    $where .= " AND (
        full_name LIKE ?
        OR email LIKE ?
        OR phone LIKE ?
    )";

    $searchTerm = "%{$search}%";

    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;

    $types .= "sss";
}


/*
|--------------------------------------------------------------------------
| ROLE FILTER
|--------------------------------------------------------------------------
*/

$allowedRoles = [
    'admin',
    'customer'
];

if (in_array($role, $allowedRoles, true)) {

    $where .= " AND role = ?";

    $params[] = $role;

    $types .= "s";
}


/*
|--------------------------------------------------------------------------
| COUNT USERS
|--------------------------------------------------------------------------
*/

$countSql = "
    SELECT COUNT(*) AS total
    FROM users
    $where
";

$stmt = $db->prepare($countSql);

if (!$stmt) {
    die("Database error: " . $db->error);
}

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();

$countResult = $stmt
    ->get_result()
    ->fetch_assoc();

$total = (int) $countResult['total'];

$totalPages = max(
    1,
    (int) ceil($total / $limit)
);

$stmt->close();


/*
|--------------------------------------------------------------------------
| FETCH USERS
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        id,
        full_name,
        email,
        phone,
        role,
        created_at
    FROM users

    $where

    ORDER BY created_at DESC

    LIMIT ? OFFSET ?
";


$queryParams = $params;

$queryTypes = $types;

$queryParams[] = $limit;
$queryParams[] = $offset;

$queryTypes .= "ii";


$stmt = $db->prepare($sql);

if (!$stmt) {
    die("Database error: " . $db->error);
}

$stmt->bind_param(
    $queryTypes,
    ...$queryParams
);

$stmt->execute();

$users = $stmt
    ->get_result()
    ->fetch_all(MYSQLI_ASSOC);

$stmt->close();

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
        Users | <?= e(SITE_NAME) ?>
    </title>

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >

</head>


<body>

<?php include '../includes/header.php'; ?>


<section class="users-page">

    <div class="container">


        <!-- =====================================================
             PAGE HEADER
        ====================================================== -->

        <div class="users-header">

            <div class="users-header-content">

                <h1>
                    User Management
                </h1>

                <p>
                    Manage registered users and account information.
                </p>

            </div>
        </div>



        <!-- =====================================================
             FILTER
        ====================================================== -->

        <div class="users-filter">

            <form
                method="GET"
                class="users-filter-form"
            >


                <!-- SEARCH -->

                <div class="users-field">

                    <label for="user-search">
                        Search
                    </label>

                    <input
                        type="text"
                        id="user-search"
                        name="search"
                        value="<?= htmlspecialchars($search) ?>"
                        placeholder="Name, email or phone..."
                        class="users-input"
                    >

                </div>



                <!-- ROLE -->

                <div class="users-field">

                    <label for="role">
                        Role
                    </label>

                    <select
                        id="role"
                        name="role"
                        class="users-select"
                    >

                        <option value="">
                            All Roles
                        </option>

                        <option
                            value="admin"
                            <?= $role === 'admin' ? 'selected' : '' ?>
                        >
                            Admin
                        </option>

                        <option
                            value="customer"
                            <?= $role === 'customer' ? 'selected' : '' ?>
                        >
                            Customer
                        </option>

                    </select>

                </div>



                <!-- FILTER BUTTON -->

                <div class="users-field">

                    <button
                        type="submit"
                        class="users-filter-btn"
                    >
                        Search Users
                    </button>

                </div>


            </form>

        </div>



        <!-- =====================================================
             USERS CARD
        ====================================================== -->

        <div class="users-card">


            <!-- CARD HEADER -->

            <div class="users-card-header">

                <div>

                    <h2>
                        Registered Users
                    </h2>

                    <p class="users-count">

                        <?= number_format($total) ?>

                        user(s) found

                    </p>

                </div>


                <a
                    href="user-create.php"
                    class="users-add-btn"
                >
                    + Add User
                </a>

            </div>



            <!-- =================================================
                 EMPTY STATE
            ================================================== -->

            <?php if (empty($users)): ?>

                <div class="users-empty">

                    <div class="users-empty-icon">
                        👤
                    </div>

                    <h3>
                        No users found
                    </h3>

                    <p>
                        No users match your search.
                    </p>

                </div>


            <?php else: ?>


                <!-- =================================================
                     TABLE
                ================================================== -->

                <div class="users-table-wrap">

                    <table class="users-table">

                        <thead>

                            <tr>

                                <th>
                                    User
                                </th>

                                <th>
                                    Email
                                </th>

                                <th>
                                    Phone
                                </th>

                                <th>
                                    Role
                                </th>

                                <th>
                                    Joined
                                </th>

                                <th>
                                    Actions
                                </th>

                            </tr>

                        </thead>


                        <tbody>


                        <?php foreach ($users as $user): ?>


                            <tr>


                                <!-- USER -->

                                <td>

                                    <div class="user-profile">


                                        <!--<div class="user-avatar">

                                            <?= strtoupper(
                                                substr(
                                                    $user['full_name'],
                                                    0,
                                                    1
                                                )
                                            ) ?>

                                        </div>--> 


                                        <div>

                                            <div class="user-name">

                                                <?= htmlspecialchars(
                                                    $user['full_name']
                                                ) ?>

                                            </div>


                                            <!--<div class="user-id">

                                                ID #<?= (int) $user['id'] ?>

                                            </div>-->

                                        </div>


                                    </div>

                                </td>



                                <!-- EMAIL -->

                                <td class="user-email">

                                    <?= htmlspecialchars(
                                        $user['email']
                                    ) ?>

                                </td>



                                <!-- PHONE -->

                                <td class="user-phone">

                                    <?= htmlspecialchars(
                                        $user['phone'] ?: 'Not provided'
                                    ) ?>

                                </td>



                                <!-- ROLE -->

                                <td>

                                    <span
                                        class="user-role user-role-<?= htmlspecialchars(
                                            $user['role']
                                        ) ?>"
                                    >

                                        <?= htmlspecialchars(
                                            ucfirst($user['role'])
                                        ) ?>

                                    </span>

                                </td>



                                <!-- CREATED -->

                                <td class="user-date">

                                    <?= date(
                                        'M d, Y',
                                        strtotime(
                                            $user['created_at']
                                        )
                                    ) ?>

                                </td>



                                <!-- ACTIONS -->

                                <td>

                                    <div class="user-actions">

                                        <a
                                            href="user-view.php?id=<?= (int) $user['id'] ?>"
                                            class="user-action-view"
                                        >
                                            View
                                        </a>


                                        <a
                                            href="user-edit.php?id=<?= (int) $user['id'] ?>"
                                            class="user-action-edit"
                                        >
                                            Edit
                                        </a>

                                    </div>

                                </td>


                            </tr>


                        <?php endforeach; ?>


                        </tbody>

                    </table>

                </div>



                <!-- =================================================
                     PAGINATION
                ================================================== -->

                <?php if ($totalPages > 1): ?>

                    <div class="users-pagination">


                        <p class="users-page-info">

                            Page <?= $page ?>
                            of <?= $totalPages ?>

                        </p>



                        <div class="users-pagination-links">


                            <?php if ($page > 1): ?>

                                <a
                                    href="?<?= http_build_query(
                                        array_merge(
                                            $_GET,
                                            [
                                                'page' => $page - 1
                                            ]
                                        )
                                    ) ?>"
                                    class="users-page-btn"
                                >
                                    ← Previous
                                </a>

                            <?php endif; ?>



                            <?php if ($page < $totalPages): ?>

                                <a
                                    href="?<?= http_build_query(
                                        array_merge(
                                            $_GET,
                                            [
                                                'page' => $page + 1
                                            ]
                                        )
                                    ) ?>"
                                    class="users-page-btn next"
                                >
                                    Next →
                                </a>

                            <?php endif; ?>


                        </div>

                    </div>

                <?php endif; ?>


            <?php endif; ?>


        </div>

    </div>

</section>


<?php include '../includes/footer.php'; ?>


</body>

</html>