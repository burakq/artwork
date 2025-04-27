import os
from pdf2image import convert_from_path
from PIL import Image

# Giriş ve çıkış klasörleri
INPUT_FOLDER = "/Applications/MAMP/htdocs/Proje/dosyalar"
OUTPUT_FOLDER = "/Applications/MAMP/htdocs/Proje/converted_images"

# Maksimum resim boyutu
MAX_SIZE = (1024, 1448)

def convert_pdfs_to_standard_jpeg():
    try:
        # Çıkış klasörünü oluştur
        if not os.path.exists(OUTPUT_FOLDER):
            os.makedirs(OUTPUT_FOLDER)
            
        # PDF dosyalarını bul
        pdf_files = [f for f in os.listdir(INPUT_FOLDER) if f.lower().endswith('.pdf')]
        
        if not pdf_files:
            print("PDF dosyası bulunamadı!")
            return
            
        for pdf_file in pdf_files:
            try:
                print(f"İşleniyor: {pdf_file}")
                
                # PDF'i sayfalara ayır
                pages = convert_from_path(os.path.join(INPUT_FOLDER, pdf_file))
                
                for i, page in enumerate(pages):
                    # Resmi yeniden boyutlandır
                    page.thumbnail(MAX_SIZE, Image.LANCZOS)
                    
                    # JPEG olarak kaydet
                    output_file = os.path.join(OUTPUT_FOLDER, f"{os.path.splitext(pdf_file)[0]}_page_{i+1}.jpg")
                    page.save(output_file, 'JPEG', quality=95)
                    
                    print(f"Sayfa {i+1} kaydedildi: {output_file}")
                    
            except Exception as e:
                print(f"Hata: {pdf_file} dosyası işlenirken bir sorun oluştu: {str(e)}")
                continue
                
        print("Dönüştürme işlemi tamamlandı!")
        
    except Exception as e:
        print(f"Genel hata: {str(e)}")

if __name__ == "__main__":
    convert_pdfs_to_standard_jpeg() 