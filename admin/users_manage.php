<?php
session_start();
// require admin before any output
require_once(__DIR__ . '/../includes/admin_auth.php');
include("../includes/config.php");
include("../includes/header.php");

$sql = "SELECT user_id, username, email, role, status, date_created FROM users ORDER BY date_created DESC";
$result = mysqli_query($conn, $sql);
$itemCount = mysqli_num_rows($result);

?>

<div class="admin-page">
    <div class="admin-card">
        <h2>Users (<?= $itemCount ?>)</h2>
        <?php include('../includes/alert.php'); ?>
        <table class="table table-striped admin-table">
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
                    <!-- Roles are retained and not editable here; display as text -->
                    <span><?= htmlspecialchars($row['role']) ?></span>
                </td>
                <td>
                    <!-- Allow admin to set status explicitly for other users (active/inactive) -->
                    <?php if ($row['user_id'] != $_SESSION['user_id']): ?>
                        <form method="post" action="user_status_update.php" class="d-flex">
                            <input type="hidden" name="user_id" value="<?= (int)$row['user_id'] ?>">
                            <select name="status" class="form-select form-select-sm me-2">
                                <?php
                                $states = ['active','inactive'];
                                foreach ($states as $s):
                                    $sel = ($s === $row['status']) ? 'selected' : '';
                                    echo "<option value=\"{$s}\" {$sel}>" . htmlspecialchars(ucfirst($s)) . "</option>";
                                endforeach;
                                ?>
                            </select>
                            <button class="btn btn-sm btn-primary" type="submit">Save</button>
                        </form>
                    <?php else: ?>
                        <span class="text-muted"><?= htmlspecialchars($row['status']) ?> (you)</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($row['user_id'] != $_SESSION['user_id']): ?>
                        <!-- Quick toggle button for convenience (calls user_toggle.php). Status form still available. -->
                        <a class="btn btn-sm btn-<?= $row['status'] === 'active' ? 'warning' : 'success' ?> me-1" href="user_toggle.php?id=<?= (int)$row['user_id'] ?>">
                            <?= $row['status'] === 'active' ? 'Deactivate' : 'Activate' ?>
                        </a>
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
</div>

<?php include('../includes/footer.php'); ?>
