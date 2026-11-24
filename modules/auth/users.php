<?php
session_start();
$page_title = "Users";

require_once '../../includes/db.php';
require_once '../../includes/header.php';

$current_id   = $_SESSION['user_id'] ?? 0;
$current_role = strtolower($_SESSION['user_role'] ?? '');
$main_principal_email = 'arslan.raiwind@gmail.com';

$success = "";
$error = "";

if (isset($_GET['approve'])) {
    $approve_id = intval($_GET['approve']);
    mysqli_query($conn, "UPDATE users SET status = 1 WHERE id = $approve_id");
    $success = "User approved successfully!";
}

if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    $check = mysqli_query($conn, "SELECT id, role, email FROM users WHERE id = $delete_id LIMIT 1");

    if ($check && mysqli_num_rows($check) > 0) {
        $target = mysqli_fetch_assoc($check);

        if ($target['email'] === $main_principal_email) {
            $error = "Main Principal cannot be deleted!";
        } elseif ($delete_id == $current_id && $current_role === 'principal') {
            $error = "Principal cannot delete their own account!";
        } elseif ($current_role === 'principal' && $delete_id != $current_id) {
            mysqli_query($conn, "DELETE FROM users WHERE id = $delete_id");
            $success = "User deleted successfully!";
        } elseif ($delete_id == $current_id && $current_role !== 'principal') {
            mysqli_query($conn, "DELETE FROM users WHERE id = $delete_id");
            session_destroy();
            header("Location: login.php");
            exit;
        } else {
            $error = "You are not allowed to delete this user!";
        }
    }
}

$users = [];
$result = mysqli_query($conn, "SELECT * FROM users ORDER BY id ASC");
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $users[] = $row;
    }
}
?>

<?php if ($success): ?>
    <div style="background:#E6F7F5; color:#2A9D8F; padding:14px 18px; border-radius:10px; margin-bottom:20px;">
        <?php echo $success; ?>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div style="background:#FEF2F2; color:#DC2626; padding:14px 18px; border-radius:10px; margin-bottom:20px;">
        <?php echo $error; ?>
    </div>
<?php endif; ?>

<div class="filter-bar">
    <a href="profile.php" class="btn btn-outline">
        <i class="bi bi-person-gear"></i> My Profile
    </a>
    <a href="user_form.php" class="btn btn-primary" style="margin-left:auto;">
        <i class="bi bi-plus-lg"></i> Add New User
    </a>
</div>

<div class="card">
    <div class="card-body" style="padding:0;">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): 
                        $is_main = ($user['email'] === $main_principal_email);
                        $is_me = ($user['id'] == $current_id);
                    ?>
                    <tr>
                        <td><?php echo $user['id']; ?></td>
                        <td>
                            <strong><?php echo htmlspecialchars($user['name']); ?></strong>
                            <?php if ($is_me): ?> <span class="text-muted">(You)</span><?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td><span class="badge badge-success"><?php echo ucfirst($user['role']); ?></span></td>
                        <td>
                            <?php if ((int)$user['status'] === 1): ?>
                                <span class="badge badge-success">Active</span>
                            <?php elseif ((int)$user['status'] === 0): ?>
                                <span class="badge badge-warning">Pending</span>
                            <?php else: ?>
                                <span class="badge badge-secondary">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo date('d-M-Y', strtotime($user['created_at'])); ?></td>
                        <td>
                            <div class="d-flex gap-2">
                                <?php if ((int)$user['status'] === 0): ?>
                                    <a href="?approve=<?php echo $user['id']; ?>" class="btn btn-sm btn-primary"
                                       onclick="return confirm('Approve this user?')">Approve</a>
                                <?php endif; ?>

                                <?php if ($is_main): ?>
                                    <span class="badge badge-secondary">Main Principal</span>
                                <?php elseif ($current_role === 'principal' && !$is_me): ?>
                                    <a href="?delete=<?php echo $user['id']; ?>" class="btn btn-sm btn-outline" style="color:var(--danger);"
                                       onclick="return confirm('Delete this user?')">Delete</a>
                                <?php elseif ($is_me && $current_role !== 'principal'): ?>
                                    <a href="?delete=<?php echo $user['id']; ?>" class="btn btn-sm btn-outline" style="color:var(--danger);"
                                       onclick="return confirm('Delete your account?')">Delete Me</a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>