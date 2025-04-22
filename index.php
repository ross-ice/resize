<?php

class ImageHelper
{
    public static function saveMainAndThumbnail($file_path, $directory, $id, $mainWidth = 1000, $thumbWidth = 200, $jpegQuality = 80, $thumbJpegQuality = 70)
    {
        // ตรวจสอบว่า GD Library ถูกติดตั้งและเปิดใช้งานหรือไม่
        if (!function_exists('imagecreatefromjpeg') && !function_exists('imagecreatefrompng') && !function_exists('imagecreatefromgif')) {
            die("GD Library is not installed or enabled.");
        }

        $file_info = pathinfo($file_path);
        $extension = strtolower($file_info['extension']);

        // สร้าง Image Resource จากไฟล์ต้นฉบับ
        switch ($extension) {
            case 'jpg':
            case 'jpeg':
                $source_image = imagecreatefromjpeg($file_path);
                break;
            case 'png':
                $source_image = imagecreatefrompng($file_path);
                break;
            case 'gif':
                $source_image = imagecreatefromgif($file_path);
                break;
            default:
                return false; // ไม่รองรับนามสกุลไฟล์นี้
        }

        if (!$source_image) {
            return false; // ไม่สามารถสร้าง Image Resource ได้
        }

        $original_width = imagesx($source_image);
        $original_height = imagesy($source_image);

        // === สร้างภาพหลัก ===
        $mainFilename = $id . '.jpg';
        $mainFilePath = $directory . '/' . $mainFilename;

        // คำนวณขนาดใหม่โดยรักษา Aspect Ratio
        $ratio_orig = $original_width / $original_height;
        if ($mainWidth / $original_width < 1) {
            $new_main_width = $mainWidth;
            $new_main_height = round($new_main_width / $ratio_orig);
        } else {
            $new_main_width = $original_width;
            $new_main_height = $original_height;
        }

        $main_image = imagecreatetruecolor($new_main_width, $new_main_height);
        imagecopyresampled($main_image, $source_image, 0, 0, 0, 0, $new_main_width, $new_main_height, $original_width, $original_height);

        // บันทึกภาพหลักเป็น JPEG
        imagejpeg($main_image, $mainFilePath, $jpegQuality);
        imagedestroy($main_image);

        // === สร้างภาพ Thumbnail ===
        $thumbFilename = $id . '_thumb.jpg';
        $thumbFilePath = $directory . '/' . $thumbFilename;

        // คำนวณขนาดใหม่สำหรับ Thumbnail โดยรักษา Aspect Ratio
        if ($thumbWidth / $original_width < 1) {
            $new_thumb_width = $thumbWidth;
            $new_thumb_height = round($new_thumb_width / $ratio_orig);
        } else {
            $new_thumb_width = $original_width;
            $new_thumb_height = $original_height;
        }

        $thumb_image = imagecreatetruecolor($new_thumb_width, $new_thumb_height);
        imagecopyresampled($thumb_image, $source_image, 0, 0, 0, 0, $new_thumb_width, $new_thumb_height, $original_width, $original_height);

        // บันทึกภาพ Thumbnail เป็น JPEG
        imagejpeg($thumb_image, $thumbFilePath, $thumbJpegQuality);
        imagedestroy($thumb_image);
        imagedestroy($source_image);

        return [
            'main' => $directory . '/' . $mainFilename,
            'thumb' => $directory . '/' . $thumbFilename,
        ];
    }

    public static function deleteImageFiles($directory, $id)
    {
        $mainImagePath = $directory . '/' . $id . '.jpg';
        $thumbImagePath = $directory . '/' . $id . '_thumb.jpg';

        $deletedMain = file_exists($mainImagePath) ? unlink($mainImagePath) : true;
        $deletedThumb = file_exists($thumbImagePath) ? unlink($thumbImagePath) : true;

        return $deletedMain && $deletedThumb;
    }
}

// โฟลเดอร์ที่เก็บรูปภาพต้นฉบับ (เปลี่ยนเป็น Path ที่ถูกต้อง)
$sourceImageDirectory = 'image';

// Directory ที่จะบันทึกภาพที่ Resize และ Thumbnails (ต้องมีอยู่จริง)
$targetDirectory = 'images';

// สร้าง Directory เป้าหมายถ้ายังไม่มี
if (!is_dir($targetDirectory)) {
    mkdir($targetDirectory, 0755, true);
}

// อ่านไฟล์ทั้งหมดในโฟลเดอร์ต้นฉบับ
$files = scandir($sourceImageDirectory);

// Loop ผ่านแต่ละไฟล์
foreach ($files as $file) {
    // สร้าง Path เต็มของไฟล์ต้นฉบับ
    $sourceFilePath = $sourceImageDirectory . '/' . $file;

    // ตรวจสอบว่าเป็นไฟล์หรือไม่ และเป็นไฟล์รูปภาพ (jpg, jpeg, png, gif)
    if (is_file($sourceFilePath) && in_array(strtolower(pathinfo($sourceFilePath, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif'])) {
        // แยกเอา ID ออกจากชื่อไฟล์ (สมมติว่าชื่อไฟล์คือ "123.jpg" ดังนั้น ID คือ "123")
        $id = pathinfo($file, PATHINFO_FILENAME);

        // เรียกใช้ Function saveMainAndThumbnail()
        $filePaths = ImageHelper::saveMainAndThumbnail(
            $sourceFilePath,
            $targetDirectory,
            $id,
            1000, // กำหนดความกว้างของภาพหลัก
            250  // กำหนดความกว้างของภาพ Thumbnail
        );

        if ($filePaths) {
            echo "Processed: " . $file . "<br>";
            echo "Main Image saved to: " . $filePaths['main'] . "<br>";
            echo "Thumbnail saved to: " . $filePaths['thumb'] . "<br><br>";
        } else {
            echo "Error processing image: " . $file . "<br><br>";
        }
    }
}

echo "Processing complete.<br>";


?>
