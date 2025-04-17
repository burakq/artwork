# PDF to JPEG Converter

Bu Python scripti, PDF dosyalarını JPEG formatına dönüştürmek için kullanılır. `pdf2image` ve `PIL` kütüphanelerini kullanarak yüksek kaliteli dönüşüm sağlar.

## Özellikler

- PDF dosyalarını JPEG formatına dönüştürme
- Sayfa başına ayrı JPEG dosyası oluşturma
- Otomatik boyut optimizasyonu (1024x1448 max)
- Türkçe karakter desteği
- Hata yönetimi ve loglama

## Gereksinimler

- Python 3.6 veya üzeri
- pdf2image kütüphanesi
- PIL (Python Imaging Library)
- poppler-utils

## Kurulum

1. Gerekli kütüphaneleri yükleyin:
   ```bash
   pip install pdf2image Pillow
   ```

2. poppler-utils'i yükleyin:
   ```bash
   # Ubuntu/Debian
   sudo apt-get install poppler-utils

   # macOS
   brew install poppler
   ```

3. Scripti indirin:
   ```bash
   git clone https://github.com/burakq/pdf-to-jpeg.git
   ```

## Kullanım

1. `INPUT_FOLDER` ve `OUTPUT_FOLDER` değişkenlerini kendi dizinlerinize göre ayarlayın
2. Scripti çalıştırın:
   ```bash
   python pdf-to-jpg.py
   ```

## Ayarlar

- `INPUT_FOLDER`: PDF dosyalarının bulunduğu dizin
- `OUTPUT_FOLDER`: JPEG dosyalarının kaydedileceği dizin
- `MAX_SIZE`: Maksimum çıktı boyutu (oran korunarak)

## Örnek Çıktı

```
📄 Dönüştürülüyor: example.pdf
✅ JPEG oluşturuldu: /path/to/output/example_page1.jpg
✅ JPEG oluşturuldu: /path/to/output/example_page2.jpg
✅ Tüm PDF dosyaları dönüştürüldü.
```

## Hata Yönetimi

Script, aşağıdaki durumlarda hata mesajı gösterir:
- PDF dosyası bulunamadığında
- Dönüşüm başarısız olduğunda
- Çıktı dizini oluşturulamadığında

## Lisans

MIT License

## Yazar

Burak Yıldırım - [GitHub](https://github.com/burakq) 