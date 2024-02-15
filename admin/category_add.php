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
$sql = "SELECT isAdmin FROM users WHERE username = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->bind_result($isAdmin);
$stmt->fetch();
$stmt->close();

// Eğer admin değilse, erişimi engelle ve popup göster
if (!$isAdmin) {
    echo "
    <html>
    <!-- ... (your modal and styling code) ... -->
    </html>";
    header("Location: index.php");
    exit();
}

// Kategori ekleme formu gönderilmiş mi kontrol et
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $error = "";

    // Kategori adını al ve boş olup olmadığını kontrol et
    $category_name = trim($_POST["category_name"]);
    if (empty($category_name)) {
        $error = "Kategori adı boş bırakılamaz.";
    }

    // Eğer hata yoksa, kategoriyi veritabanına ekle
    if (empty($error)) {
        $sql_insert = "INSERT INTO categories (name) VALUES (?)";
        $stmt_insert = $conn->prepare($sql_insert);
        $stmt_insert->bind_param("s", $category_name);
        
        if ($stmt_insert->execute()) {
            // Başarıyla eklendiğini belirt
            $success_message = "Kategori başarıyla eklendi.";
        } else {
            // Ekleme hatası durumunda hata mesajını belirt
            $error = "Kategori eklenirken bir hata oluştu: " . $stmt_insert->error;
        }
        
        $stmt_insert->close();
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
<html>
<head>
    <title>Blog Yazısı Ekle</title>
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
            color: #333; /* Başlık rengi */
        }

        .error {
            color: red;
            text-align: center;
            margin-bottom: 10px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            color: #555; /* Etiket rengi */
        }

        input[type="text"],
        textarea,
        select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 3px;
            margin-bottom: 10px;
        }

        button[type="submit"] {
            padding: 10px 20px;
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
    <!-- Kategori Ekleme Formu -->
    <div class="container">
        <h2>Kategori Ekle</h2>

        <?php if (!empty($error)) : ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <div>
                <label for="category_name">Kategori Adı:</label>
                <input type="text" name="category_name" id="category_name" required>
            </div>

            <div>
                <button type="submit">Kategori Ekle</button>
            </div>
        </form>
    </div>

</body>
</html>
