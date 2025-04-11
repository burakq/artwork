<?php
// Veritabanı bağlantısı için gerekli dosyaları dahil et
require_once 'config/db.php';
require_once 'includes/functions.php';

// Veritabanına bağlan
$conn = connectDB();
echo "Veritabanına bağlandı.<br>";

// Tabloyu boşalt ve yeniden oluştur
$conn->query("TRUNCATE TABLE old_verification_codes");
echo "Tablo temizlendi, tüm kodlar yeniden eklenecek.<br>";

// Eski doğrulama kodları tablosu yoksa oluştur
$tableCheck = $conn->query("SHOW TABLES LIKE 'old_verification_codes'");
if ($tableCheck->num_rows == 0) {
    $sql = "CREATE TABLE old_verification_codes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        code VARCHAR(255) NOT NULL,
        image_url VARCHAR(512) NOT NULL,
        title VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($sql) === TRUE) {
        echo "Eski Doğrulama Kodları tablosu oluşturuldu.<br>";
    } else {
        echo "Eski Doğrulama Kodları tablosu oluşturulamadı: " . $conn->error . "<br>";
        exit;
    }
}

// Temel değişkenler
$success = 0;
$errors = 0;
$startTime = microtime(true);

// Temel kodlar (bu kodlar kesin eklenir)
$base_codes = [
    // Devrim Erbil Eserleri
    [
        'code' => 'GXIDONSZLYJLN-10072024',
        'image_url' => 'https://www.devrimerbil.com/wp-content/uploads/2024/11/Istanbul-ve-Kuslar-Silver-39-1.jpg',
        'title' => 'İstanbul ve Kuşlar Silver'
    ],
    [
        'code' => 'DPQ6UUILNCKSS240120256',
        'image_url' => 'https://www.devrimerbil.com/wp-content/uploads/2025/01/6-Kadikoyun-Kalbinden-24-Ocak-2025-6-130x130-cm-T.U.Y.B.-Eser-Kodu-DPQ6UUILNCKSS240120256.jpg',
        'title' => 'Kadıköyün Kalbinden'
    ],
    [
        'code' => 'Kod-11-Temmuz-2013-75',
        'image_url' => 'http://devrimerbil.com/wp-content/uploads/2020/01/İstanbul-ÇM10-2013Tuv.Üz.Kar_.Tek_.145x150-cm.Kod-11-Temmuz-2013-75-1.jpg',
        'title' => 'İstanbul (ÇM10)'
    ],
    [
        'code' => 'kod-11-Temmuz-2013-73-2',
        'image_url' => 'http://devrimerbil.com/wp-content/uploads/2020/01/İstanbula-Bakış-K-Y.11-2013Tuv.Üz.Kar_.Tek_.180x130-cm.kod-11-Temmuz-2013-73-2-1.jpg',
        'title' => 'İstanbula Bakış'
    ],
    [
        'code' => 'Kod-18-Mart-2013-23',
        'image_url' => 'http://devrimerbil.com/wp-content/uploads/2020/01/İstanbul-Kırmızı-Gri-Kuşlar2013-Tuv.Üz.Kar_.Tek_.125X-200cm.Kod-18-Mart-2013-23-2.jpg',
        'title' => 'İstanbul Kırmızı Gri Kuşlar'
    ],
    [
        'code' => '3EPLS93Z2W4WQ030120251',
        'image_url' => 'https://www.devrimerbil.com/wp-content/uploads/2023/02/devrim-erbil-eylul-mavi-istanbul.jpg',
        'title' => 'Mavi İstanbul Eylül'
    ],
    [
        'code' => 'O2MM0AWTUJZOK240120255',
        'image_url' => 'https://www.devrimerbil.com/wp-content/uploads/2023/06/devrim-erbil-kirmizi-istanbul.jpg',
        'title' => 'Kırmızı İstanbul'
    ],
    [
        'code' => 'TUI9KNCUAYEXR160120253',
        'image_url' => 'https://www.devrimerbil.com/wp-content/uploads/2022/11/devrim-erbil-yesil-manzara.jpg',
        'title' => 'Yeşil Manzara'
    ],
    [
        'code' => 'X8A1FWSGB1XOO-06122024107',
        'image_url' => 'https://www.devrimerbil.com/wp-content/uploads/2021/09/devrim-erbil-istanbul-siluet.jpg',
        'title' => 'İstanbul Siluet'
    ],
    [
        'code' => '6E7EYXHRNZEXX-170120254',
        'image_url' => 'https://www.devrimerbil.com/wp-content/uploads/2022/04/devrim-erbil-mavi-kuslar.jpg',
        'title' => 'Mavi Kuşlar'
    ]
];

// Yıl değerleri
$years = range(2000, 2025);

// Renk isimleri
$colors = [
    'Mavi', 'Kırmızı', 'Yeşil', 'Sarı', 'Turuncu', 'Mor', 'Pembe', 'Kahverengi', 
    'Siyah', 'Beyaz', 'Gri', 'Turkuaz', 'Lacivert', 'Bordo', 'Altın', 'Gümüş', 
    'Bronz', 'Bakır', 'Fuşya', 'Lila', 'Bej', 'Haki', 'Zeytin', 'Mürdüm',
    'Indigo', 'Cyan', 'Magenta', 'Lime', 'Mint', 'Aqua', 'Violet'
];

// Şehir isimleri
$cities = [
    'İstanbul', 'Ankara', 'İzmir', 'Bursa', 'Adana', 'Konya', 'Antalya', 'Samsun',
    'Kayseri', 'Eskişehir', 'Diyarbakır', 'Şanlıurfa', 'Gaziantep', 'Kocaeli',
    'Mersin', 'Trabzon', 'Erzurum', 'Malatya', 'Hatay', 'Balıkesir', 'Manisa',
    'Aydın', 'Kahramanmaraş', 'Van', 'Denizli', 'Sakarya', 'Tekirdağ', 'Elazığ',
    'Batman', 'Sivas', 'Muğla', 'Mardin', 'Tokat', 'Afyon', 'Ordu', 'Kütahya'
];

// Semt isimleri (İstanbul'un semtleri)
$districts = [
    'Kadıköy', 'Üsküdar', 'Beşiktaş', 'Beyoğlu', 'Fatih', 'Şişli', 'Bakırköy', 'Sarıyer',
    'Beykoz', 'Eyüp', 'Adalar', 'Kartal', 'Pendik', 'Maltepe', 'Ataşehir', 'Beylikdüzü',
    'Büyükçekmece', 'Çekmeköy', 'Esenyurt', 'Sultanbeyli', 'Ümraniye', 'Zeytinburnu',
    'Bahçelievler', 'Bağcılar', 'Güngören', 'Gaziosmanpaşa', 'Kağıthane', 'Başakşehir',
    'Arnavutköy', 'Avcılar', 'Beyazıt', 'Taksim', 'Levent', 'Nişantaşı', 'Moda', 'Etiler'
];

// Tür isimleri
$styles = [
    'Kuşlar', 'Kubbeler', 'Siluet', 'Minareler', 'Deniz', 'Boğaz', 'Galata', 'Soyut',
    'Manzara', 'Panorama', 'Sokaklar', 'Köprüler', 'Çiçekler', 'Ağaçlar', 'Camiler',
    'Vapurlar', 'Kayıklar', 'Yelkenliler', 'Saraylar', 'Meydanlar', 'Çarşılar', 'Hanlar',
    'Kervansaraylar', 'Kaleler', 'Surlar', 'Kuleler', 'Limanlar', 'İskeleler', 'Adalar', 
    'Nehirler', 'Göller', 'Dağlar', 'Tepeler', 'Ovalar', 'Vadiler', 'Şelaleler'
];

// Sanatçı isimleri (Yeni eklenen)
$artists = [
    'Devrim Erbil', 'İbrahim Çallı', 'Bedri Rahmi Eyüboğlu', 'Neşet Günal', 'Fikret Mualla',
    'Abidin Dino', 'Nuri İyem', 'Avni Arbaş', 'Eren Eyüboğlu', 'Burhan Doğançay',
    'Fahrelnissa Zeid', 'Erol Akyavaş', 'Adnan Çoker', 'Selim Turan', 'İlhan Koman',
    'Mehmet Güleryüz', 'Ömer Uluç', 'Mübin Orhon', 'Nejad Devrim', 'Yüksel Arslan',
    'Sabri Berkel', 'Şükriye Dikmen', 'Cihat Burak', 'Altan Gürman', 'Füreya Koral',
    'Sarkis Zabunyan', 'Nil Yalter', 'Seyhun Topuz', 'Gülsün Karamustafa', 'Ergin İnan',
    'Balkan Naci İslimyeli', 'Mehmet Aksoy', 'İpek Duben', 'Mehmet Gün', 'Kemal Önsoy'
];

// Kompozisyon çeşitleri
$compositions = [
    'Soyut', 'Simetrik', 'Asimetrik', 'Dairesel', 'Düzlemsel', 'Yapısal', 'Çizgisel',
    'Geometrik', 'Organik', 'Modernist', 'Post-modern', 'Klasik', 'Neo-klasik', 'Romantik',
    'Minimalist', 'Maksimalist', 'Ekspresif', 'İzlenimci', 'Kübist', 'Fütürist',
    'Konstrüktivist', 'Süprematist', 'Pop-Art', 'Op-Art', 'Dijital', 'Kavramsal',
    'Gerçeküstü', 'Dışavurumcu', 'İlkel', 'Çağdaş', 'Deneysel', 'Yenilikçi', 'Geleneksel'
];

// Boyutlar ve malzemeler (Yeni eklenen)
$dimensions = [
    '100x70 cm', '120x90 cm', '150x120 cm', '180x150 cm', '200x180 cm',
    '50x50 cm', '60x60 cm', '80x80 cm', '100x100 cm', '120x120 cm',
    '30x40 cm', '40x50 cm', '50x70 cm', '70x100 cm', '90x120 cm',
    '210x150 cm', '240x180 cm', '300x200 cm', '350x250 cm', '400x300 cm'
];

$materials = [
    'Tuval Üzerine Yağlıboya', 'Tuval Üzerine Akrilik', 'Kağıt Üzerine Suluboya', 
    'Kağıt Üzerine Mürekkep', 'Ahşap Üzerine Karışık Teknik', 'Tuval Üzerine Karışık Teknik',
    'Metal Üzerine Akrilik', 'Kağıt Üzerine Pastel', 'Tuval Üzerine Kolaj', 
    'Kağıt Üzerine Karışık Teknik', 'Tuval Üzerine Guaj', 'Cam Üzerine Akrilik',
    'Metal Üzerine Karışık Teknik', 'İpek Üzerine Baskı', 'Keten Üzerine Yağlıboya'
];

// Çeşitli kod formatları
$code_formats = [
    'standart', 'numerik', 'alfanumerik', 'tarihli', 'sanatçı_kod', 'seri_no'
];

function generateCode($length = 15) {
    $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $code = '';
    for ($i = 0; $i < $length; $i++) {
        $code .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $code;
}

// Temel kodları ekle
foreach ($base_codes as $code_data) {
    // Kod ekle
    $insertQuery = "INSERT INTO old_verification_codes (code, image_url, title) VALUES (?, ?, ?)";
    $insertStmt = $conn->prepare($insertQuery);
    $insertStmt->bind_param("sss", $code_data['code'], $code_data['image_url'], $code_data['title']);
    
    if ($insertStmt->execute()) {
        $success++;
    } else {
        $errors++;
    }
}

// Toplam hedef satır sayısı: 20000 (daha fazla kod)
$target_count = 20000;

// Her bir döngü için maks çalışma süresi: 60 saniye (daha uzun süre)
$maxExecutionTime = 60;

// Düzenli DE kodları
for ($year = 2000; $year <= 2025; $year++) {
    for ($i = 1; $i <= 100; $i++) {
        // Maksimum çalışma süresini kontrol et
        if ((microtime(true) - $startTime) > $maxExecutionTime) {
            break 2; // İki döngüden çık
        }
        
        // İki haneli sayı formatı
        $number = str_pad($i, 3, '0', STR_PAD_LEFT);
        $code = "DE-$year-$number";
        $color = $colors[array_rand($colors)];
        $style = $styles[array_rand($styles)];
        $title = "$color $style $year";
        $image_url = "https://www.devrimerbil.com/wp-content/uploads/$year/01/$color-$style-$year.jpg";
        
        $insertQuery = "INSERT INTO old_verification_codes (code, image_url, title) VALUES (?, ?, ?)";
        $insertStmt = $conn->prepare($insertQuery);
        $insertStmt->bind_param("sss", $code, $image_url, $title);
        
        if ($insertStmt->execute()) {
            $success++;
        } else {
            $errors++;
        }
        
        // Hedef sayıya ulaştık mı?
        if ($success >= $target_count) {
            break 2; // İki döngüden çık
        }
    }
}

// Özel format kodlar
if ($success < $target_count) {
    for ($month = 1; $month <= 12; $month++) {
        for ($day = 1; $day <= 28; $day++) {
            for ($i = 1; $i <= 10; $i++) {
                // Maksimum çalışma süresini kontrol et
                if ((microtime(true) - $startTime) > $maxExecutionTime) {
                    break 3; // Üç döngüden çık
                }
                
                $year = rand(2000, 2025);
                $random_code = generateCode(10);
                $formattedMonth = str_pad($month, 2, '0', STR_PAD_LEFT);
                $formattedDay = str_pad($day, 2, '0', STR_PAD_LEFT);
                $code = "$random_code$formattedDay$formattedMonth$year$i";
                
                $color = $colors[array_rand($colors)];
                $city = $cities[array_rand($cities)];
                $district = $districts[array_rand($districts)];
                $title = "$color $city $district";
                $image_url = "https://www.devrimerbil.com/wp-content/uploads/$year/$month/$color-$city-$day$month$year.jpg";
                
                $insertQuery = "INSERT INTO old_verification_codes (code, image_url, title) VALUES (?, ?, ?)";
                $insertStmt = $conn->prepare($insertQuery);
                $insertStmt->bind_param("sss", $code, $image_url, $title);
                
                if ($insertStmt->execute()) {
                    $success++;
                } else {
                    $errors++;
                }
                
                // Hedef sayıya ulaştık mı?
                if ($success >= $target_count) {
                    break 3; // Üç döngüden çık
                }
            }
        }
    }
}

// Tarih kodlar
if ($success < $target_count) {
    for ($year = 2000; $year <= 2025; $year++) {
        foreach (['Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran', 'Temmuz', 'Ağustos', 'Eylül', 'Ekim', 'Kasım', 'Aralık'] as $month_name) {
            for ($day = 1; $day <= 25; $day++) {
                // Maksimum çalışma süresini kontrol et
                if ((microtime(true) - $startTime) > $maxExecutionTime) {
                    break 3; // Üç döngüden çık
                }
                
                // Kod formatı: Kod-GünAy-Yıl-NumaraJ
                $code = "Kod-$day.$month_name-$year-" . rand(1, 999);
                
                $composition = $compositions[array_rand($compositions)];
                $style = $styles[array_rand($styles)];
                $title = "$composition $style ($year)";
                $image_url = "https://www.devrimerbil.com/wp-content/uploads/$year/" . strtolower($month_name) . "/$day-$composition-$style.jpg";
                
                $insertQuery = "INSERT INTO old_verification_codes (code, image_url, title) VALUES (?, ?, ?)";
                $insertStmt = $conn->prepare($insertQuery);
                $insertStmt->bind_param("sss", $code, $image_url, $title);
                
                if ($insertStmt->execute()) {
                    $success++;
                } else {
                    $errors++;
                }
                
                // Hedef sayıya ulaştık mı?
                if ($success >= $target_count) {
                    break 3; // Üç döngüden çık
                }
            }
        }
    }
}

// Sanatçı özel kodları (Yeni eklenen format)
if ($success < $target_count) {
    foreach ($artists as $artist) {
        $artist_shortcode = strtoupper(substr(str_replace(' ', '', $artist), 0, 3));
        
        for ($i = 1; $i <= 50; $i++) {
            // Maksimum çalışma süresini kontrol et
            if ((microtime(true) - $startTime) > $maxExecutionTime) {
                break 2; // İki döngüden çık
            }
            
            $year = rand(2000, 2025);
            $serial = str_pad($i, 4, '0', STR_PAD_LEFT);
            $code = "$artist_shortcode-$year-$serial";
            
            $dimension = $dimensions[array_rand($dimensions)];
            $material = $materials[array_rand($materials)];
            $style = $styles[array_rand($styles)];
            $color = $colors[array_rand($colors)];
            
            $title = "$artist - $color $style ($dimension)";
            $artist_name_url = strtolower(str_replace(' ', '-', $artist));
            $image_url = "https://www.sanatgalerisi.com/wp-content/uploads/$year/" . rand(1, 12) . "/$artist_name_url-$style-$year-$i.jpg";
            
            $insertQuery = "INSERT INTO old_verification_codes (code, image_url, title) VALUES (?, ?, ?)";
            $insertStmt = $conn->prepare($insertQuery);
            $insertStmt->bind_param("sss", $code, $image_url, $title);
            
            if ($insertStmt->execute()) {
                $success++;
            } else {
                $errors++;
            }
            
            // Hedef sayıya ulaştık mı?
            if ($success >= $target_count) {
                break 2; // İki döngüden çık
            }
        }
    }
}

// Seri numaralı kodlar (Yeni eklenen format)
if ($success < $target_count) {
    for ($series = 'A'; $series <= 'Z'; $series++) {
        for ($i = 1; $i <= 100; $i++) {
            // Maksimum çalışma süresini kontrol et
            if ((microtime(true) - $startTime) > $maxExecutionTime) {
                break 2; // İki döngüden çık
            }
            
            $serial = str_pad($i, 5, '0', STR_PAD_LEFT);
            $code = "SN$series-$serial";
            
            $year = rand(2000, 2025);
            $artist = $artists[array_rand($artists)];
            $color = $colors[array_rand($colors)];
            $style = $styles[array_rand($styles)];
            $dimension = $dimensions[array_rand($dimensions)];
            
            $title = "$artist - $color $style, Seri $series, No $i";
            $artist_name_url = strtolower(str_replace(' ', '-', $artist));
            $image_url = "https://www.sanatgalerisi.com/wp-content/uploads/$year/" . rand(1, 12) . "/$artist_name_url-$color-$style-seri-$series-$i.jpg";
            
            $insertQuery = "INSERT INTO old_verification_codes (code, image_url, title) VALUES (?, ?, ?)";
            $insertStmt = $conn->prepare($insertQuery);
            $insertStmt->bind_param("sss", $code, $image_url, $title);
            
            if ($insertStmt->execute()) {
                $success++;
            } else {
                $errors++;
            }
            
            // Hedef sayıya ulaştık mı?
            if ($success >= $target_count) {
                break 2; // İki döngüden çık
            }
        }
    }
}

// Alfanumerik kodlar (Yeni eklenen format)
if ($success < $target_count) {
    for ($i = 1; $i <= 5000; $i++) {
        // Maksimum çalışma süresini kontrol et
        if ((microtime(true) - $startTime) > $maxExecutionTime) {
            break; // Döngüden çık
        }
        
        $code = generateCode(rand(10, 20));
        
        $year = rand(2000, 2025);
        $month = rand(1, 12);
        $day = rand(1, 28);
        
        $artist = $artists[array_rand($artists)];
        $color = $colors[array_rand($colors)];
        $style = $styles[array_rand($styles)];
        $city = $cities[array_rand($cities)];
        
        $title = "$artist - $color $style $city";
        $artist_name_url = strtolower(str_replace(' ', '-', $artist));
        $style_url = strtolower(str_replace(' ', '-', $style));
        $image_url = "https://www.sanatgalerisi.com/wp-content/uploads/$year/$month/$artist_name_url-$style_url-$day$month$year.jpg";
        
        $insertQuery = "INSERT INTO old_verification_codes (code, image_url, title) VALUES (?, ?, ?)";
        $insertStmt = $conn->prepare($insertQuery);
        $insertStmt->bind_param("sss", $code, $image_url, $title);
        
        if ($insertStmt->execute()) {
            $success++;
        } else {
            $errors++;
        }
        
        // Hedef sayıya ulaştık mı?
        if ($success >= $target_count) {
            break; // Döngüden çık
        }
    }
}

$endTime = microtime(true);
$executionTime = round($endTime - $startTime, 2);

echo "<hr>";
echo "<strong>Sonuç:</strong><br>";
echo "$success kod başarıyla eklendi.<br>";
if ($errors > 0) echo "$errors kod eklenirken hata oluştu.<br>";
echo "İşlem süresi: $executionTime saniye<br>";

// Toplam kod sayısını göster
$countResult = $conn->query("SELECT COUNT(*) as count FROM old_verification_codes");
$countRow = $countResult->fetch_assoc();
echo "Toplam eski doğrulama kodu sayısı: <b>" . $countRow['count'] . "</b><br>";

// Database bağlantısını kapat
$conn->close();
echo "<br>Veritabanı bağlantısı kapatıldı.";
?> 
 
 
 
 