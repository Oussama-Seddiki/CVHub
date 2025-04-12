<?php
// معلومات الاتصال بقاعدة البيانات
$servername = "localhost";
$username = "root";
$password = "";

try {
    // إنشاء اتصال PDO
    $conn = new PDO("mysql:host=$servername", $username, $password);
    // تعيين وضع الخطأ PDO إلى الاستثناء
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // إنشاء قاعدة البيانات
    $sql = "CREATE DATABASE IF NOT EXISTS cvhubdb CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
    $conn->exec($sql);
    
    echo "تم إنشاء قاعدة البيانات بنجاح";
} catch(PDOException $e) {
    echo "فشل إنشاء قاعدة البيانات: " . $e->getMessage();
}

// إغلاق الاتصال
$conn = null;
?> 