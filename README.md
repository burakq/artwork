# Artwork Auth Yönetim Sistemi

Bu proje, sanat eserlerinin kimlik doğrulama ve yönetimini sağlayan bir PHP uygulamasıdır.

## Özellikler

- Kullanıcı ve yönetici girişi
- Sanat eserleri yönetimi
- Müşteri yönetimi
- Sipariş takibi
- Doğrulama kayıtları
- PDF şablon oluşturma

## Kurulum

1. Dosyaları web sunucunuza yükleyin
2. MySQL veritabanını oluşturun
3. Veritabanı ayarlarını `config/db.php` dosyasında yapılandırın
4. Tarayıcınızda uygulamayı açın

## Gereksinimler

- PHP 7.4 veya üzeri
- MySQL 8.0 veya üzeri
- MAMP, XAMPP veya benzeri bir local web sunucusu

## Veritabanı Tabloları

- `users`: Sistem kullanıcıları
- `artworks`: Sanat eserleri
- `customers`: Müşteriler
- `orders`: Siparişler
- `order_artwork`: Sipariş-eser ilişkisi
- `verification_logs`: Doğrulama kayıtları
- `tags`: Etiketler
- `artwork_tag`: Eser-etiket ilişkisi
- `pdf_templates`: PDF şablonları

## Admin LTE 4 Entegrasyonu

Bu projede Admin LTE 4 teması kullanılmıştır. Admin paneli için modern ve duyarlı bir arayüz sağlar.

## Lisans

Bu proje [MIT lisansı](LICENSE) altında lisanslanmıştır.

## İletişim

Sorularınız veya önerileriniz için lütfen iletişime geçin. 