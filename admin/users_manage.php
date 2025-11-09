<?php
session_start();
include("../includes/config.php");
include("../includes/header.php");

// only admin
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    $_SESSION['message'] = 'Access denied.';
    header('Location: ../users/login.php');
    exit();
}

$sql = "SELECT user_id, username, email, role, status, date_created FROM users ORDER BY date_created DESC";
$result = mysqli_query($conn, $sql);
$itemCount = mysqli_num_rows($result);

?>

<div class="container mt-4">
    <h2>Users (<?= $itemCount ?>)</h2>
    <?php include('../includes/alert.php'); ?>
    <table class="table table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Username</th>
                <th>Email</th>
                <th>Role</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php while ($row = mysqli_fetch_assoc($result)): ?>
            <tr>
                <td><?= (int)$row['user_id'] ?></td>
                <td><?= htmlspecialchars($row['username']) ?></td>
                <td><?= htmlspecialchars($row['email']) ?></td>
                <td>
                    <?php if ($row['status'] === 'active'): ?>
                        <form method="post" action="user_role_update.php" class="d-flex">
                            <input type="hidden" name="user_id" value="<?= (int)$row['user_id'] ?>">
                            <select name="role" class="form-select form-select-sm me-2">
                                <?php
                                $roles = ['admin','customer','inventory_manager','finance_manager'];
                                foreach ($roles as $r):
                                    $sel = ($r === $row['role']) ? 'selected' : '';
                                    echo "<option value=\"{$r}\" {$sel}>" . htmlspecialchars($r) . "</option>";
                                endforeach;
                                ?>
                            </select>
                            <button class="btn btn-sm btn-primary" type="submit">Save</button>
                        </form>
                    <?php else: ?>
                        <span class="text-muted"><?= htmlspecialchars($row['role']) ?></span>
                    <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($row['status']) ?></td>
                <td>
                    <?php if ($row['user_id'] != $_SESSION['user_id']): ?>
                        <a class="btn btn-sm btn-<?= $row['status'] === 'active' ? 'warning' : 'success' ?>" href="user_toggle.php?id=<?= (int)$row['user_id'] ?>">
                            <?= $row['status'] === 'active' ? 'Deactivate' : 'Activate' ?>
                        </a>
                        <?php if ($row['status'] === 'active'): ?>
                            <a class="btn btn-sm btn-secondary" href="impersonate.php?id=<?= (int)$row['user_id'] ?>">Impersonate</a>
                        <?php endif; ?>
                        <a class="btn btn-sm btn-danger" href="user_delete.php?id=<?= (int)$row['user_id'] ?>" onclick="return confirm('Delete this user?');">Delete</a>
                    <?php else: ?>
                        <span class="text-muted">(you)</span>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>

<?php include('../includes/footer.php'); ?>
