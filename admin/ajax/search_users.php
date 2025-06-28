<?php
session_start();
require_once '../../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

$term = isset($_GET['term']) ? $_GET['term'] : '';
$response = ['success' => false, 'html' => ''];

if (strlen($term) >= 2) {
    $sql = "SELECT * FROM users WHERE username LIKE ? OR email LIKE ? ORDER BY id DESC";
    $stmt = $conn->prepare($sql);
    $searchTerm = "%$term%";
    $stmt->bind_param("ss", $searchTerm, $searchTerm);
    $stmt->execute();
    $result = $stmt->get_result();

    ob_start();
    while ($user = $result->fetch_assoc()) {
        ?>
        <tr>
            <td><?= $user['id'] ?></td>
            <td contenteditable="true" 
                onBlur="updateUser(<?= $user['id'] ?>, 'username', this.innerText)">
                <?= htmlspecialchars($user['username']) ?>
            </td>
            <td contenteditable="true" 
                onBlur="updateUser(<?= $user['id'] ?>, 'email', this.innerText)">
                <?= htmlspecialchars($user['email']) ?>
            </td>
            <td>
                <span class="badge <?= strtolower($user['role']) ?>">
                    <?= str_replace('_', ' ', ucfirst($user['role'])) ?>
                </span>
            </td>
            <td>
                <span class="status <?= $user['status'] ?>">
                    <?= ucfirst($user['status']) ?>
                </span>
            </td>
            <td>
                <div class="action-buttons">
                    <button onclick="editUser(<?= $user['id'] ?>)" class="btn-icon edit">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button onclick="deleteUser(<?= $user['id'] ?>)" class="btn-icon delete">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </td>
        </tr>
        <?php
    }
    $response['html'] = ob_get_clean();
    $response['success'] = true;
    $stmt->close();
}

header('Content-Type: application/json');
echo json_encode($response);