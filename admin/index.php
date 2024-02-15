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

// Eğer admin değilse, ana sayfaya yönlendir
if (!$isAdmin) {
    header("Location: index.php");
    exit();
}

// Kullanıcı sayısını al
$sql = "SELECT COUNT(*) FROM users";
$result = $conn->query($sql);
$userCount = $result->fetch_assoc()['COUNT(*)'];

// Post sayısını al
$sql = "SELECT COUNT(*) FROM posts";
$result = $conn->query($sql);
$postCount = $result->fetch_assoc()['COUNT(*)'];

// Kategori sayısını al
$sql = "SELECT COUNT(*) FROM categories";
$result = $conn->query($sql);
$categoryCount = $result->fetch_assoc()['COUNT(*)'];

// Veritabanı bağlantısını kapat
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            padding-top: 56px;
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

    <div class="content">
        <h1>İstatistikler</h1>

        <!-- Kullanıcı, Post ve Kategori sayıları için grafik -->
        <div style="width: 50%; margin: auto;">
            <canvas id="myChart"></canvas>
        </div>

        <script>
            var ctx = document.getElementById('myChart').getContext('2d');
            var myChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['Kullanıcı Sayısı', 'Post Sayısı', 'Kategori Sayısı'],
                    datasets: [{
                        label: 'Sayı',
                        data: [<?php echo $userCount; ?>, <?php echo $postCount; ?>, <?php echo $categoryCount; ?>],
                        backgroundColor: [
                            'rgba(255, 99, 132, 0.2)',
                            'rgba(54, 162, 235, 0.2)',
                            'rgba(255, 206, 86, 0.2)'
                        ],
                        borderColor: [
                            'rgba(255, 99, 132, 1)',
                            'rgba(54, 162, 235, 1)',
                            'rgba(255, 206, 86, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        </script>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.10.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
