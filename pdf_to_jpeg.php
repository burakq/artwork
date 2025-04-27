<?php

class PDFToJPEG {
    private $dpi = 150;
    private $quality = 90;
    private $tempDir = '/tmp';
    private $ghostscriptPath = 'gs';

    /**
     * Ghostscript yolunu ayarlar
     */
    public function setGhostscriptPath($path) {
        $this->ghostscriptPath = $path;
        return $this;
    }

    /**
     * DPI değerini ayarlar
     */
    public function setDPI($dpi) {
        $this->dpi = (int)$dpi;
        return $this;
    }

    /**
     * JPEG kalitesini ayarlar
     */
    public function setQuality($quality) {
        $this->quality = (int)$quality;
        return $this;
    }

    /**
     * Geçici dizini ayarlar
     */
    public function setTempDir($dir) {
        $this->tempDir = $dir;
        return $this;
    }

    /**
     * Tek sayfalı PDF'i JPEG'e dönüştürür
     */
    public function convert($pdfPath, $outputPath) {
        $this->validateInput($pdfPath, $outputPath);
        
        $tempFile = $this->tempDir . '/temp_' . uniqid() . '.jpg';
        
        $command = sprintf(
            '%s -dSAFER -dBATCH -dNOPAUSE -sDEVICE=jpeg -dJPEGQ=%d -r%d -sOutputFile=%s %s',
            $this->ghostscriptPath,
            $this->quality,
            $this->dpi,
            escapeshellarg($tempFile),
            escapeshellarg($pdfPath)
        );

        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            throw new Exception('PDF dönüşümü başarısız oldu');
        }

        if (!rename($tempFile, $outputPath)) {
            unlink($tempFile);
            throw new Exception('Çıktı dosyası oluşturulamadı');
        }

        return true;
    }

    /**
     * Çok sayfalı PDF'i JPEG'lere dönüştürür
     */
    public function convertMultiPage($pdfPath, $outputDir) {
        $this->validateInput($pdfPath, $outputDir);
        
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        $tempPattern = $this->tempDir . '/temp_' . uniqid() . '_%d.jpg';
        
        $command = sprintf(
            '%s -dSAFER -dBATCH -dNOPAUSE -sDEVICE=jpeg -dJPEGQ=%d -r%d -sOutputFile=%s %s',
            $this->ghostscriptPath,
            $this->quality,
            $this->dpi,
            escapeshellarg($tempPattern),
            escapeshellarg($pdfPath)
        );

        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            throw new Exception('PDF dönüşümü başarısız oldu');
        }

        $files = glob($this->tempDir . '/temp_*_*.jpg');
        $result = [];

        foreach ($files as $file) {
            $pageNumber = preg_replace('/.*_(\d+)\.jpg$/', '$1', $file);
            $newPath = $outputDir . '/page_' . $pageNumber . '.jpg';
            
            if (rename($file, $newPath)) {
                $result[] = $newPath;
            } else {
                unlink($file);
            }
        }

        return $result;
    }

    /**
     * Girdi doğrulaması yapar
     */
    private function validateInput($pdfPath, $outputPath) {
        if (!file_exists($pdfPath)) {
            throw new Exception('PDF dosyası bulunamadı');
        }

        if (!is_readable($pdfPath)) {
            throw new Exception('PDF dosyası okunamıyor');
        }

        if (!is_writable(dirname($outputPath))) {
            throw new Exception('Çıktı dizini yazılabilir değil');
        }

        // Ghostscript kontrolü
        exec($this->ghostscriptPath . ' --version', $output, $returnCode);
        if ($returnCode !== 0) {
            throw new Exception('Ghostscript bulunamadı veya çalıştırılamıyor');
        }
    }
} 