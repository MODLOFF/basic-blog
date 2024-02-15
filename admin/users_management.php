<?php
session_start();

// Kullanıcı oturum açmış mı kontrol et
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Veritabanı bağlantısı
require_once "../config.php";

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
if (!$isAdmin) {
    echo "
    <html>
    <!-- ... (your modal and styling code) ... -->
    </html>";
    header("Location: index.php");
    exit();
}

// Kullanıcı ekleme formu gönderilmiş mi kontrol et
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["add_user"])) {
    $error = "";

    // Kullanıcı adını, e-posta adresini ve şifresini al
    $new_username = trim($_POST["new_username"]);
    $new_email = trim($_POST["new_email"]);
    $new_password = $_POST["new_password"];
    $is_admin = isset($_POST["is_admin"]) ? 1 : 0;

    // Gerekli kontrolleri yap (örneğin, boş olup olmadığını kontrol et)
    if (empty($new_username) || empty($new_email) || empty($new_password)) {
        $error = "Kullanıcı adı, e-posta adresi ve şifre boş bırakılamaz.";
    }

    // Eğer hata yoksa, kullanıcıyı ekle
    if (empty($error)) {
        // Şifreyi güvenli bir şekilde hashle
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

        // Kullanıcıyı ekleyen SQL sorgusu
        $sql_add_user = "INSERT INTO users (username, email, password, isAdmin) VALUES (?, ?, ?, ?)";
        $stmt_add_user = $conn->prepare($sql_add_user);
        $stmt_add_user->bind_param("sssi", $new_username, $new_email, $hashed_password, $is_admin);

        if ($stmt_add_user->execute()) {
            // Başarıyla eklendiğini belirt
            $success_message = "Kullanıcı başarıyla eklendi.";
        } else {
            // Ekleme hatası durumunda hata mesajını belirt
            $error = "Kullanıcı eklenirken bir hata oluştu: " . $stmt_add_user->error;
        }

        $stmt_add_user->close();
    }
}

// Kullanıcıları veritabanından al
$sql_select_users = "SELECT id, username, isAdmin FROM users";
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
    <title>Kullanıcı Yönetimi</title>
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

        <!-- Kullanıcı Yönetimi Formu -->
        <div class="card">
            <h2 class="card-header bg-primary text-white">Kullanıcı Yönetimi</h2>
            <div class="card-body">

                <?php if (!empty($error)) : ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <?php if (isset($success_message)) : ?>
                    <div class="alert alert-success"><?php echo $success_message; ?></div>
                <?php endif; ?>

                <!-- Kullanıcı Ekleme Formu -->
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                    <div class="mb-3">
                        <label for="new_username" class="form-label">Yeni Kullanıcı Adı:</label>
                        <input type="text" name="new_username" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="new_email" class="form-label">E-posta Adresi:</label>
                        <input type="email" name="new_email" class="form-control" required>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" name="is_admin" class="form-check-input">
                        <label for="is_admin" class="form-check-label">Admin Olarak Ekle</label>
                    </div>
                    <div class="mb-3">
                        <label for="new_password" class="form-label">Yeni Şifre:</label>
                        <input type="password" name="new_password" class="form-control" required>
                    </div>
                    <button type="submit" name="add_user" class="btn btn-primary">Kullanıcı Ekle</button>
                </form>
                <ul class="list-group mt-3">
                    <?php foreach ($users as $user) : ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span><?php echo $user['username']; ?></span>
                            
                            <?php 
                            if ($isAdmin == 2) : 
                                // Super Admin can delete all users
                            ?>
                                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <button type="submit" name="delete_user" class="btn btn-danger btn-sm">Kullanıcı Sil</button>
                                </form>
                            <?php 
                            elseif ($isAdmin == 1 && $user['isAdmin'] == 0) : 
                                // Admin can delete non-admin users
                            ?>
                                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <button type="submit" name="delete_user" class="btn btn-danger btn-sm">Kullanıcı Sil</button>
                                </form>
                            <?php endif; ?>
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
