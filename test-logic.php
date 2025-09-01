#!/usr/bin/env php
<?php
/**
 * Test script for Flickr to WordPress XML logic validation
 * 
 * This script tests core functionality without generating full XML
 */

declare(strict_types=1);

class FlickrLogicTester
{
    private string $jsonDir;
    
    public function __construct(string $jsonDir)
    {
        $this->jsonDir = $jsonDir;
        
        if (!is_dir($jsonDir)) {
            throw new InvalidArgumentException("JSON directory does not exist: {$jsonDir}");
        }
    }
    
    public function runTests(): void
    {
        echo "=== Flickr Logic Test Suite ===\n\n";
        
        $this->testAlbumsLoading();
        $this->testPhotoLoading();
        $this->testSlugGeneration();
        $this->testDateFormatting();
        $this->testEXIFFormatting();
        $this->testContentGeneration();
        
        echo "=== All Tests Completed ===\n";
    }
    
    private function testAlbumsLoading(): void
    {
        echo "1. Testing albums loading...\n";
        
        $albumsFile = $this->jsonDir . '/albums.json';
        if (!file_exists($albumsFile)) {
            echo "   ❌ albums.json not found\n";
            return;
        }
        
        $data = json_decode(file_get_contents($albumsFile), true);
        if (!$data || !isset($data['albums'])) {
            echo "   ❌ Invalid albums.json format\n";
            return;
        }
        
        echo "   ✅ Found " . count($data['albums']) . " albums\n";
        
        // Show first few albums
        foreach (array_slice($data['albums'], 0, 3) as $album) {
            echo "      - \"{$album['title']}\" ({$album['photo_count']} photos)\n";
        }
        
        echo "\n";
    }
    
    private function testPhotoLoading(): void
    {
        echo "2. Testing photo files loading...\n";
        
        $photoFiles = glob($this->jsonDir . '/photo_*.json') ?: [];
        echo "   ✅ Found " . count($photoFiles) . " photo files\n";
        
        if (empty($photoFiles)) {
            echo "   ❌ No photo files found\n";
            return;
        }
        
        // Test loading a few photos
        $testFiles = array_slice($photoFiles, 0, 3);
        $validPhotos = 0;
        $missingOriginal = 0;
        
        foreach ($testFiles as $file) {
            $data = json_decode(file_get_contents($file), true);
            if (!$data) {
                echo "   ❌ Corrupted: " . basename($file) . "\n";
                continue;
            }
            
            if (!isset($data['original'])) {
                echo "   ⚠️  Missing 'original' URL: " . basename($file) . "\n";
                $missingOriginal++;
                continue;
            }
            
            echo "   ✅ Valid: " . basename($file) . " -> " . $data['name'] . "\n";
            echo "      Original: " . $data['original'] . "\n";
            $validPhotos++;
        }
        
        echo "   Summary: {$validPhotos} valid, {$missingOriginal} missing original URLs\n\n";
    }
    
    private function testSlugGeneration(): void
    {
        echo "3. Testing slug generation...\n";
        
        $testCases = [
            "Happy Baby in Danang 2017" => "happy-baby-in-danang-2017",
            "#livingDanang 2016" => "livingdanang-2016",
            "Job is both fun and colorful!" => "job-is-both-fun-and-colorful",
            "Sài Thành - Quận nhất" => "si-thnh-qun-nht",
            "Photo taken at 2015-06-08 05:46:43" => "photo-taken-at-2015-06-08-05-46-43"
        ];
        
        foreach ($testCases as $input => $expected) {
            $actual = $this->generateSlug($input);
            if ($actual === $expected) {
                echo "   ✅ \"{$input}\" -> \"{$actual}\"\n";
            } else {
                echo "   ❌ \"{$input}\" -> \"{$actual}\" (expected: \"{$expected}\")\n";
            }
        }
        
        echo "\n";
    }
    
    private function testDateFormatting(): void
    {
        echo "4. Testing date formatting...\n";
        
        $testDates = [
            "2015-06-08 05:46:43",
            "2013-09-20 09:52:51",
            "2014-05-05 17:36:13"
        ];
        
        foreach ($testDates as $date) {
            $formatted = $this->formatDate($date);
            $formattedGMT = $this->formatDateGMT($date);
            $rfc2822 = $this->formatRFC2822($date);
            
            echo "   ✅ {$date}\n";
            echo "      Local: {$formatted}\n";
            echo "      GMT:   {$formattedGMT}\n";
            echo "      RFC:   {$rfc2822}\n";
        }
        
        echo "\n";
    }
    
    private function testEXIFFormatting(): void
    {
        echo "5. Testing EXIF data formatting...\n";
        
        // Load a sample photo to test EXIF
        $photoFiles = glob($this->jsonDir . '/photo_*.json') ?: [];
        if (empty($photoFiles)) {
            echo "   ❌ No photo files to test EXIF\n";
            return;
        }
        
        $photoData = json_decode(file_get_contents($photoFiles[0]), true);
        if (!isset($photoData['exif']) || empty($photoData['exif'])) {
            echo "   ⚠️  No EXIF data in sample photo\n";
            return;
        }
        
        $formatted = $this->formatEXIFData($photoData['exif']);
        $lines = explode("\n", $formatted);
        
        echo "   ✅ EXIF formatted with " . (count($lines) - 1) . " fields:\n";
        foreach (array_slice($lines, 0, 6) as $line) {
            echo "      {$line}\n";
        }
        
        if (count($lines) > 6) {
            echo "      ... and " . (count($lines) - 6) . " more fields\n";
        }
        
        echo "\n";
    }
    
    private function testContentGeneration(): void
    {
        echo "6. Testing post content generation...\n";
        
        $photoFiles = glob($this->jsonDir . '/photo_*.json') ?: [];
        if (empty($photoFiles)) {
            echo "   ❌ No photo files to test content generation\n";
            return;
        }
        
        $photoData = json_decode(file_get_contents($photoFiles[0]), true);
        $content = $this->generatePostContent($photoData);
        
        echo "   ✅ Generated content for: " . ($photoData['name'] ?: 'Untitled') . "\n";
        echo "   Content preview:\n";
        
        $lines = explode("\n", $content);
        foreach (array_slice($lines, 0, 5) as $line) {
            echo "      {$line}\n";
        }
        
        echo "\n";
    }
    
    // Helper methods (copied from main implementation)
    private function generateSlug(string $text): string
    {
        $slug = strtolower($text);
        $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
        $slug = preg_replace('/[\s-]+/', '-', $slug);
        return trim($slug, '-');
    }
    
    private function formatDate(string $date): string
    {
        return date('Y-m-d H:i:s', strtotime($date));
    }
    
    private function formatDateGMT(string $date): string
    {
        return gmdate('Y-m-d H:i:s', strtotime($date));
    }
    
    private function formatRFC2822(string $date): string
    {
        return date('r', strtotime($date));
    }
    
    private function formatEXIFData(array $exif): string
    {
        if (empty($exif)) {
            return '';
        }
        
        $lines = ['flickr_exif_data:'];
        foreach ($exif as $key => $value) {
            $lines[] = "• {$key}: {$value}";
        }
        
        return implode("\n", $lines);
    }
    
    private function generatePostContent(array $photo): string
    {
        $content = [];
        
        if (!empty($photo['description'])) {
            $content[] = $photo['description'];
        }
        
        $content[] = "Taken on " . $photo['date_taken'];
        $content[] = "Originally from: " . $photo['photopage'];
        
        return implode("\n\n", $content);
    }
}

// Command line interface for test
if ($argc < 2) {
    echo "Usage: php test-logic.php <json-directory>\n";
    echo "Example: php test-logic.php ./flickr-data/json\n";
    exit(1);
}

try {
    $tester = new FlickrLogicTester($argv[1]);
    $tester->runTests();
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}