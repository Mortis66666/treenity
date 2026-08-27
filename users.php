<?php
include_once("database.php");
include_once("check_user.php");
include("pagination.php");

check_user_role(['ADMIN']); // Only allow users with the 'ADMIN' role to access this page

$csrf_token = $_SESSION['csrf_token'] ??= bin2hex(random_bytes(32));
$action_error = '';

$sortby = $_GET['sortby'] ?? 'user_id';
$sort_columns = [
    'user_id' => 'user_id',
    'username' => 'username',
    'tp_number' => 'tp_number',
    'role' => 'role',
    'created_at' => 'created_at'
];
$sortby = $sort_columns[$sortby] ?? 'user_id';
$order = strtolower($_GET['order'] ?? 'asc');
$order = $order === 'desc' ? 'DESC' : 'ASC';

$roles_result = $conn->execute_query("SELECT DISTINCT role FROM `users` WHERE role IS NOT NULL AND role <> '' ORDER BY role ASC");
$roles = [];
while ($role_row = $roles_result->fetch_assoc()) {
    $roles[] = $role_row['role'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = filter_var($_POST['user_id'] ?? null, FILTER_VALIDATE_INT);
    $posted_token = $_POST['csrf_token'] ?? '';

    if (!hash_equals($csrf_token, $posted_token)) {
        $action_error = 'The request could not be verified. Please try again.';
    } elseif (!$user_id || $user_id === (int) $_SESSION['user_id']) {
        $action_error = 'This user cannot be deleted or edited.';
    } elseif (($_POST['action'] ?? '') === 'delete') {
        $conn->execute_query("DELETE FROM `users` WHERE user_id = ?", [$user_id]);
    } elseif (($_POST['action'] ?? '') === 'update') {
        $username = trim($_POST['username'] ?? '');
        $tp_number = trim($_POST['tp_number'] ?? '');
        $role = trim($_POST['role'] ?? '');

        if ($username === '' || $tp_number === '' || !in_array($role, $roles, true)) {
            $action_error = 'Please provide valid values for every field.';
        } else {
            $conn->execute_query(
                "UPDATE `users` SET username = ?, tp_number = ?, role = ? WHERE user_id = ?",
                [$username, $tp_number, $role, $user_id]
            );
        }
    }

    if ($action_error === '') {
        header('Location: users.php');
        exit();
    }
}

$search = trim($_GET['search'] ?? '');
$selected_role = trim($_GET['role'] ?? '');
$sort_columns = [
    'user_id' => 'user_id',
    'username' => 'username',
    'tp_number' => 'tp_number',
    'role' => 'role',
    'created_at' => 'created_at'
];

$where = [];
$params = [];
if ($search !== '') {
    $where[] = "(username LIKE ? OR tp_number LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($selected_role !== '' && in_array($selected_role, $roles, true)) {
    $where[] = "role = ?";
    $params[] = $selected_role;
} else {
    $selected_role = '';
}
$where_sql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

$query_total = "SELECT COUNT(*) as total FROM `users`$where_sql";
$total_result = $conn->execute_query($query_total, $params);
$total_rows = $total_result->fetch_assoc()['total'];

$limit = 10;
$total_pages = max(1, (int) ceil($total_rows / $limit));
$page = max(1, min((int) ($_GET['page'] ?? 1), $total_pages));
$offset = ($page - 1) * $limit;

$query = "SELECT * FROM `users`$where_sql ORDER BY $sortby $order LIMIT ?, ?";
$result = $conn->execute_query($query, array_merge($params, [$offset, $limit]));

$query_state = ['search' => $search, 'role' => $selected_role, 'sortby' => $sortby, 'order' => strtolower($order)];
$query_state = array_filter($query_state, fn($value) => $value !== '');
$pagination_url = '?' . http_build_query($query_state) . ($query_state ? '&' : '') . 'page=';

function sortUrl(string $column, string $currentSort, string $currentOrder, string $search, string $role): string
{
    $next_order = ($column === $currentSort && $currentOrder === 'asc') ? 'desc' : 'asc';
    return '?' . http_build_query([
        'search' => $search,
        'role' => $role,
        'sortby' => $column,
        'order' => $next_order,
        'page' => 1
    ]);
}

function sortLabel(string $label, string $column, string $currentSort, string $currentOrder, string $search, string $role): string
{
    $indicator = $column === $currentSort ? ($currentOrder === 'asc' ? ' &uarr;' : ' &darr;') : '';
    return '<a href="' . htmlspecialchars(sortUrl($column, $currentSort, $currentOrder, $search, $role), ENT_QUOTES, 'UTF-8') . '">' .
        htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . $indicator . '</a>';
}

$table_headers = [
    'user_id' => '#',
    'username' => 'Username',
    'tp_number' => 'TP number',
    'role' => 'Role',
    'created_at' => 'Created At'
];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users</title>

    <link rel="stylesheet" href="styles/users.css">
    <?php include("global.php"); ?>


</head>

<body>
    <?php include("header.php"); ?>

    <main class="content">
        <div class="page-title-bar">
            <h1>Users</h1>
            <a class="create-user-button" href="create_user.php">Create new user</a>
        </div>

        <form class="users-filters" method="get">
            <div class="filter-field">
                <label for="user-search">Search users</label>
                <input id="user-search" type="search" name="search" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>" placeholder="Username or TP number">
            </div>
            <div class="filter-field">
                <label for="role-filter">Role</label>
                <select id="role-filter" name="role">
                    <option value="">All roles</option>
                    <?php foreach ($roles as $role): ?>
                        <option value="<?= htmlspecialchars($role, ENT_QUOTES, 'UTF-8') ?>" <?= $role === $selected_role ? 'selected' : '' ?>><?= htmlspecialchars($role) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit">Filter</button>
            <?php if ($search !== '' || $selected_role !== ''): ?>
                <a class="clear-filters" href="users.php">Clear</a>
            <?php endif; ?>
        </form>

        <div class="users-list">
            <table>
                <thead>
                    <tr>
                        <?php foreach ($table_headers as $column => $label): ?>
                            <th><?= sortLabel($label, $column, $sortby, strtolower($order), $search, $selected_role) ?></th>
                        <?php endforeach; ?>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>
                    <?php
                    $rank = $offset + 1;
                    while ($row = $result->fetch_assoc()) {
                        $profile_url = 'profile.php?' . http_build_query(['user' => (int) $row['user_id']]);
                        $profile_url_attribute = htmlspecialchars($profile_url, ENT_QUOTES, 'UTF-8');
                        echo '<tr class="clickable-row" tabindex="0" role="link" data-profile-url="' . $profile_url_attribute . '" onclick="if (!event.target.closest(\'input, select, button, form, a\')) window.location.href = this.dataset.profileUrl;" onkeydown="if ((event.key === \'Enter\' || event.key === \' \') && !event.target.closest(\'input, select, button, form, a\')) { event.preventDefault(); window.location.href = this.dataset.profileUrl; }">';
                        foreach ($table_headers as $column => $label) {
                            if ($column === 'username' || $column === 'tp_number') {
                                echo '<td>';
                                echo '<input form="edit-' . (int) $row['user_id'] . '" name="' . $column . '" value="' . htmlspecialchars($row[$column], ENT_QUOTES, 'UTF-8') . '">';
                                echo '</td>';
                            } elseif ($column === 'role') {
                                echo '<td><select form="edit-' . (int) $row['user_id'] . '" name="role">';
                                foreach ($roles as $role) {
                                    $selected = $role === $row['role'] ? ' selected' : '';
                                    echo '<option value="' . htmlspecialchars($role, ENT_QUOTES, 'UTF-8') . '"' . $selected . '>' . htmlspecialchars($role, ENT_QUOTES, 'UTF-8') . '</option>';
                                }
                                echo '</select></td>';
                            } else {
                                echo "<td>" . htmlspecialchars($row[$column], ENT_QUOTES, 'UTF-8') . "</td>";
                            }
                        }
                        echo '<td class="user-actions">';
                        echo '<form id="edit-' . (int) $row['user_id'] . '" method="post">';
                        echo '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') . '">';
                        echo '<input type="hidden" name="action" value="update">';
                        echo '<input type="hidden" name="user_id" value="' . (int) $row['user_id'] . '">';
                        echo '<button class="save-user" type="submit">Save</button>';
                        echo '</form>';
                        echo '<form method="post" onsubmit="return confirm(\'Delete this user?\');">';
                        echo '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') . '">';
                        echo '<input type="hidden" name="action" value="delete">';
                        echo '<input type="hidden" name="user_id" value="' . (int) $row['user_id'] . '">';
                        echo '<button class="delete-user" type="submit" aria-label="Delete user" title="Delete user"><span aria-hidden="true">&#128465;</span></button>';
                        echo '</form></td>';
                        echo "</tr>";
                        $rank++;
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <?php renderPagination($page, $total_pages, $pagination_url); ?>

    </main>

    <?php include("footer.php"); ?>

    <?php if ($action_error !== '') execute("alert(" . json_encode($action_error) . ");"); ?>
</body>

</html>