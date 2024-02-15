<?php
session_start();

// Veritabanı bağlantısı
require_once "../config.php";

// Kullanıcı oturum açmış mı kontrol et
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Kullanıcının admin olup olmadığını kontrol et
$username = $_SESSION['username'];
$sql_admin_check = "SELECT isAdmin FROM users WHERE username = ?";
$stmt_admin_check = $conn->prepare($sql_admin_check);
$stmt_admin_check->bind_param("s", $username);
$stmt_admin_check->execute();
$stmt_admin_check->bind_result($isAdmin);
$stmt_admin_check->fetch();
$stmt_admin_check->close();

// Eğer admin değilse, erişimi engelle ve popup göster
if ($isAdmin == 0) {
    echo "
    <html>
    <!-- ... (your modal and styling code) ... -->
    </html>";
    header("Location: index.php");
    exit();
}

// Kullanıcı düzenleme formu gönderilmiş mi kontrol et
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["edit_user"])) {
    $error = "";

    // Kullanıcı adını ve e-posta adresini al
    $edited_username = trim($_POST["edited_username"]);
    $edited_email = trim($_POST["edited_email"]);
    $edited_user_id = $_POST["edited_user_id"];
    $new_password = isset($_POST["new_password"]) ? trim($_POST["new_password"]) : "";

    // Gerekli kontrolleri yap (örneğin, boş olup olmadığını kontrol et)
    if (empty($edited_username) || empty($edited_email)) {
        $error = "Kullanıcı adı ve e-posta adresi boş bırakılamaz.";
    }

    // Eğer hata yoksa, kullanıcıyı güncelle
    if (empty($error)) {
        // Kullanıcıyı güncelleyen SQL sorgusu
        $sql_update_user = "UPDATE users SET username = ?, email = ?";
        
        // Check if a new password is provided
        if (!empty($new_password)) {
            $sql_update_user .= ", password = ?";
            $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
        }

        $sql_update_user .= " WHERE id = ?";
        $stmt_update_user = $conn->prepare($sql_update_user);

        if (!empty($new_password)) {
            $stmt_update_user->bind_param("sssi", $edited_username, $edited_email, $new_password_hash, $edited_user_id);
        } else {
            $stmt_update_user->bind_param("ssi", $edited_username, $edited_email, $edited_user_id);
        }

        if ($stmt_update_user->execute()) {
            // Başarıyla güncellendiğini belirt
            $success_message = "Kullanıcı başarıyla güncellendi.";
        } else {
            // Güncelleme hatası durumunda hata mesajını belirt
            $error = "Kullanıcı güncellenirken bir hata oluştu: " . $stmt_update_user->error;
        }

        $stmt_update_user->close();
    }
}

// Kullanıcıları veritabanından al
$sql_select_users = "SELECT id, username, email, isAdmin FROM users";
$result = $conn->query($sql_select_users);
$users = $result->fetch_all(MYSQLI_ASSOC);

// Veritabanı bağlantısını kapat
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kullanıcı Düzenleme</title>
    <style>
                .sidebar {
            height: 100%;
            width: 200px;
            position: fixed;
            top: 0;
            left: 0;
            background-color: #f8f9fa;
            padding-top: 15px;
        }

        .sidebar a {
            padding: 15px;
            text-decoration: none;
            font-size: 18px;
            color: #495057;
            display: block;
        }

        .sidebar a:hover {
            background-color: #e9ecef;
        }

        .content {
            margin-left: 250px;
            padding: 20px;
        }
        </style>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons (optional) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.18.0/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <div class="sidebar">
        <a href="index.php">Ana Sayfa</a>
        <a href="category_add.php">Kategori Ekle</a>
        <a href="category_edit.php">Kategori Düzenle </a>
        <a href="users_edit.php">Kullanıcı Düzenle </a>
        <a href="users_management.php">Kullanıcı Ekle </a>

        <!-- Diğer bağlantılar buraya eklenebilir -->
    </div>
    <div class="container mt-5">

        <!-- Kullanıcı Düzenleme Formu -->
        <div class="card">
            <h2 class="card-header bg-primary text-white">Kullanıcı Düzenleme</h2>
            <div class="card-body">

                <?php if (!empty($error)) : ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <?php if (isset($success_message)) : ?>
                    <div class="alert alert-success"><?php echo $success_message; ?></div>
                <?php endif; ?>

                <!-- Kullanıcı Arama Formu -->
                <form method="GET" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="mb-3">
                    <div class="input-group">
                        <input type="text" name="search_username" class="form-control" placeholder="Kullanıcı Adı Ara" value="<?php echo isset($_GET['search_username']) ? $_GET['search_username'] : ''; ?>">
                        <button type="submit" class="btn btn-outline-secondary" type="button">Ara</button>
                    </div>
                </form>

                <ul class="list-group mt-3">
                    <?php foreach ($users as $user) : ?>
                        <?php
                        // Filter users based on the entered username
                        $searchUsername = isset($_GET['search_username']) ? $_GET['search_username'] : '';
                        if ($searchUsername && stripos($user['username'], $searchUsername) === false) {
                            continue; // Skip users that do not match the search criteria
                        }
                        ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span><?php echo $user['username']; ?></span>
                            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $user['id']; ?>">
                                <i class="bi bi-gear"></i> Düzenle
                            </button>

                            <!-- Edit User Modal -->
                            <div class="modal fade" id="editModal<?php echo $user['id']; ?>" tabindex="-1" aria-labelledby="editModalLabel<?php echo $user['id']; ?>" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="editModalLabel<?php echo $user['id']; ?>">Kullanıcı Düzenleme</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                                                <div class="mb-3">
                                                    <label for="edited_username" class="form-label">Kullanıcı Adı:</label>
                                                    <input type="text" name="edited_username" class="form-control" value="<?php echo $user['username']; ?>" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label for="edited_email" class="form-label">E-posta Adresi:</label>
                                                    <input type="email" name="edited_email" class="form-control" value="<?php echo $user['email']; ?>" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label for="new_password" class="form-label">Yeni Şifre:</label>
                                                    <input type="password" name="new_password" class="form-control">
                                                </div>
                                                <input type="hidden" name="edited_user_id" value="<?php echo $user['id']; ?>">
                                                <button type="submit" name="edit_user" class="btn btn-primary">Kullanıcıyı Düzenle</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>

    </div>

    <!-- Bootstrap JS (optional, if you need it) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>