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

// Kategori düzenleme veya silme formu gönderilmiş mi kontrol et
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST["edit_category"])) {
        $error = "";

        // Kategori adını al ve boş olup olmadığını kontrol et
        $category_name = trim($_POST["category_name"]);
        $category_id = $_POST["category_id"];

        if (empty($category_name)) {
            $error = "Kategori adı boş bırakılamaz.";
        }

        // Eğer hata yoksa, kategoriyi güncelle
        if (empty($error)) {
            $sql_update = "UPDATE categories SET name = ? WHERE id = ?";
            $stmt_update = $conn->prepare($sql_update);
            $stmt_update->bind_param("si", $category_name, $category_id);

            if ($stmt_update->execute()) {
                // Başarıyla güncellendiğini belirt
                $success_message = "Kategori başarıyla güncellendi.";
            } else {
                // Güncelleme hatası durumunda hata mesajını belirt
                $error = "Kategori güncellenirken bir hata oluştu: " . $stmt_update->error;
            }

            $stmt_update->close();
        }
    } elseif (isset($_POST["delete_category"])) {
        $category_id_to_delete = $_POST["category_id"];

        // Kategoriyi sil
        $sql_delete = "DELETE FROM categories WHERE id = ?";
        $stmt_delete = $conn->prepare($sql_delete);
        $stmt_delete->bind_param("i", $category_id_to_delete);

        if ($stmt_delete->execute()) {
            // Başarıyla silindiğini belirt
            $success_message = "Kategori başarıyla silindi.";
        } else {
            // Silme hatası durumunda hata mesajını belirt
            $error = "Kategori silinirken bir hata oluştu: " . $stmt_delete->error;
        }

        $stmt_delete->close();
    }
}

// Kategorileri veritabanından al
$sql_select_categories = "SELECT * FROM categories";
$result = $conn->query($sql_select_categories);
$categories = $result->fetch_all(MYSQLI_ASSOC);

// Veritabanı bağlantısını kapat
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kategori Düzenleme</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 600px;
            margin: 50px auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.3);
        }

        h2 {
            text-align: center;
            margin-bottom: 20px;
            color: #333;
        }

        .error {
            color: red;
            text-align: center;
            margin-bottom: 10px;
        }

        .success {
            color: green;
            text-align: center;
            margin-bottom: 10px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            color: #555;
        }

        input[type="text"] {
            width: calc(100% - 20px);
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 3px;
            margin-bottom: 10px;
        }

        button[type="submit"] {
            width: 100%;
            padding: 10px;
            background-color: #333;
            color: #fff;
            border: none;
            border-radius: 3px;
            cursor: pointer;
        }

        button[type="submit"]:hover {
            background-color: #555;
        }
        
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
    <!-- Kategori Düzenleme Formu -->
    <div class="container">
        <h2>Kategori Düzenleme</h2>

        <?php if (!empty($error)) : ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if (isset($success_message)) : ?>
            <div class="success"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <ul>
            <?php foreach ($categories as $category) : ?>
                <li>
                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                        <label for="category_name">Kategori Adı:</label>
                        <input type="text" name="category_name" value="<?php echo $category['name']; ?>" required>
                        <input type="hidden" name="category_id" value="<?php echo $category['id']; ?>">
                        <button type="submit" name="edit_category">Kategori Güncelle</button>
                        <button type="submit" name="delete_category">Kategori Sil</button>
                    </form>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</body>
</html>
