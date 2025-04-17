import os
from pdf2image import convert_from_path
from PIL import Image

# Ayarlar
INPUT_FOLDER = "/Applications/MAMP/htdocs/Proje/dosyalar"
OUTPUT_FOLDER = "/Applications/MAMP/htdocs/Proje/converted_images"
MAX_SIZE = (1024, 1448)  # Uzun kenar en fazla bu olur, oran korunur

def convert_pdfs_to_standard_jpeg(input_dir, output_dir, max_size):
    os.makedirs(output_dir, exist_ok=True)

    for filename in os.listdir(input_dir):
        if not filename.lower().endswith(".pdf"):
            continue

        pdf_path = os.path.join(input_dir, filename)
        base_name = os.path.splitext(filename)[0]

        try:
            print(f"📄 Dönüştürülüyor: {filename}")
            images = convert_from_path(pdf_path, dpi=72)

            for i, image in enumerate(images):
                image = image.convert("RGB")

                # Oranı koruyarak yeniden boyutlandır
                image.thumbnail(max_size, Image.LANCZOS)

                output_path = os.path.join(output_dir, f"{base_name}_page{i+1}.jpg")
                image.save(output_path, "JPEG", quality=90)
                print(f"✅ JPEG oluşturuldu: {output_path}")

        except Exception as e:
            print(f"❌ Hata oluştu ({filename}): {e}")

    print("\n✅ Tüm PDF dosyaları dönüştürüldü.")

if __name__ == "__main__":
    convert_pdfs_to_standard_jpeg(INPUT_FOLDER, OUTPUT_FOLDER, MAX_SIZE) 