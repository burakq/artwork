import pandas as pd
import mysql.connector
from datetime import datetime
import os

# Excel dosyasını oku
df = pd.read_excel('songuncel.xlsx')

# MySQL bağlantısı
conn = mysql.connector.connect(
    host="localhost",
    port=8889,
    user="root",
    password="root",
    database="artwork_auth"
)

cursor = conn.cursor()

# Her satır için
for index, row in df.iterrows():
    try:
        # Print Size'ı dimensions olarak al
        dimensions = None
        if pd.notna(row['Print Size']):
            dimensions = str(row['Print Size']).strip()

        # Verification code kontrolü
        if not pd.notna(row['Verification Code']):
            continue

        now = datetime.now().strftime('%Y-%m-%d %H:%M:%S')
        
        # Önce verification_code ile kayıt var mı kontrol et
        check_sql = "SELECT id FROM artworks WHERE verification_code = %s"
        cursor.execute(check_sql, (row['Verification Code'],))
        result = cursor.fetchone()

        if result:
            # Kayıt varsa güncelle
            sql = """
            UPDATE artworks 
            SET 
                title = %s,
                artist_name = %s,
                description = %s,
                image_path = %s,
                print_date = %s,
                technique = %s,
                status = %s,
                edition_number = %s,
                edition_type = %s,
                country = %s,
                dimensions = %s,
                verification_code = %s,
                is_verified = %s,
                updated_at = %s
            WHERE verification_code = %s
            """

            values = (
                row['Artwork Name'] if pd.notna(row['Artwork Name']) else None,
                'Devrim Erbil',
                None,  # description
                f"/upload/artworks/eski/{row['Filename']}" if pd.notna(row['Filename']) else None,
                row['Print Date'] if pd.notna(row['Print Date']) else None,
                row['Print Method'] if pd.notna(row['Print Method']) else None,
                'original',  # status
                row['Edition'] if pd.notna(row['Edition']) else None,
                None,  # edition_type
                'Turkey',  # country
                dimensions,
                row['Verification Code'],
                0,  # is_verified
                now,
                row['Verification Code']
            )
        else:
            # Kayıt yoksa yeni ekle
            sql = """
            INSERT INTO artworks (
                title, artist_name, description, image_path, print_date, 
                technique, status, edition_number, edition_type, country,
                dimensions, verification_code, is_verified, created_at, updated_at
            ) VALUES (
                %s, %s, %s, %s, %s, 
                %s, %s, %s, %s, %s,
                %s, %s, %s, %s, %s
            )
            """

            values = (
                row['Artwork Name'] if pd.notna(row['Artwork Name']) else None,
                'Devrim Erbil',
                None,  # description
                f"/upload/artworks/eski/{row['Filename']}" if pd.notna(row['Filename']) else None,
                row['Print Date'] if pd.notna(row['Print Date']) else None,
                row['Print Method'] if pd.notna(row['Print Method']) else None,
                'original',  # status
                row['Edition'] if pd.notna(row['Edition']) else None,
                None,  # edition_type
                'Turkey',  # country
                dimensions,
                row['Verification Code'],
                0,  # is_verified
                now,
                now
            )

        # SQL komutunu çalıştır
        cursor.execute(sql, values)
        print(f"Eser {'güncellendi' if result else 'eklendi'}: {row['Verification Code']}")

    except Exception as e:
        print(f"Hata: {str(e)}")
        print(f"Satır: {index + 1}")
        print(f"Veri: {row}")
        continue

# Değişiklikleri kaydet ve bağlantıyı kapat
conn.commit()
conn.close()

print("Veri aktarımı tamamlandı!") 