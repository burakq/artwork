import json
import pandas as pd
from datetime import datetime

# JSON dosyasını oku
with open('/Applications/MAMP/htdocs/Proje/ocr_output.json', 'r', encoding='utf-8') as f:
    data = json.load(f)

# Verileri düzenle
excel_data = []
for item in data:
    # Dosya adından eser adını çıkar
    file_name = item['file_name']
    
    # OCR metnini satırlara böl
    lines = item['ocr_text'].split('\n')
    
    # Verileri sözlük olarak topla
    row = {
        'Dosya Adı': file_name,
        'Sanatçı': '',
        'Eser Adı': '',
        'Baskı Tarihi': '',
        'Boyut': '',
        'Teknik': '',
        'Edisyon': '',
        'Menşei': '',
        'İlk Sahibi': '',
        'Doğrulama Kodu': ''
    }
    
    # Her satırı kontrol et
    for line in lines:
        line = line.strip()
        if not line:
            continue
            
        if 'Artist' in line:
            row['Sanatçı'] = line.split('|')[-1].strip()
        elif 'Artwork Name' in line:
            row['Eser Adı'] = line.split('Artwork Name')[-1].strip()
        elif 'Print Date' in line:
            row['Baskı Tarihi'] = line.split('Print Date')[-1].strip()
        elif 'Print Size' in line:
            row['Boyut'] = line.split('Print Size')[-1].strip()
        elif 'Print Method' in line:
            row['Teknik'] = line.split('Print Method')[-1].strip()
        elif 'Edition' in line:
            row['Edisyon'] = line.split('Edition')[-1].strip()
        elif 'Country of Origin' in line:
            row['Menşei'] = line.split('Country of Origin')[-1].strip()
        elif 'First Owner' in line:
            row['İlk Sahibi'] = line.split('First Owner')[-1].strip()
        elif 'Verification Code' in line:
            row['Doğrulama Kodu'] = line.split('Verification Code')[-1].strip()
    
    excel_data.append(row)

# DataFrame oluştur
df = pd.DataFrame(excel_data)

# Excel dosyasına kaydet
output_file = f'eserler_{datetime.now().strftime("%Y%m%d_%H%M%S")}.xlsx'
df.to_excel(output_file, index=False, engine='openpyxl')

print(f"Excel dosyası oluşturuldu: {output_file}") 