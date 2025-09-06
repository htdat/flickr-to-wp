#!/usr/bin/env php
<?php
/**
 * Flickr to WordPress XML Exporter - Test Suite
 * 
 * Simple test script that validates the complete XML generation workflow
 * using existing sample data and the main exporter script.
 * 
 * Usage: php tests/run-tests.php
 */

declare(strict_types=1);

class FlickrXMLTest
{
    private string $testOutputFile = 'tests/test-export.xml';
    private array $results = [];
    private int $passCount = 0;
    private int $failCount = 0;
    
    public function run(): void
    {
        echo "🧪 Flickr to WordPress XML Exporter - Test Suite\n";
        echo str_repeat("=", 60) . "\n\n";
        
        // Step 1: Generate XML
        $this->test('XML Generation', [$this, 'testXMLGeneration']);
        
        if (!file_exists($this->testOutputFile)) {
            echo "\n❌ Cannot continue - XML file not generated\n";
            exit(1);
        }
        
        // Load XML for validation
        $xml = $this->loadXML();
        if (!$xml) {
            echo "\n❌ Cannot continue - XML file is invalid\n";
            exit(1);
        }
        
        // Step 2: XML Structure Tests
        $this->test('XML Structure Validation', [$this, 'testXMLStructure'], $xml);
        
        // Step 3: Album → Tag Mapping Tests
        $this->test('Album Tag Creation', [$this, 'testAlbumTags'], $xml);
        $this->test('Tag Name Handling', [$this, 'testTagNames'], $xml);
        $this->test('Tag Slug Generation', [$this, 'testTagSlugs'], $xml);
        
        // Step 4: Photo → Post + Attachment Tests
        $this->test('Post Creation', [$this, 'testPostCreation'], $xml);
        $this->test('Attachment Creation', [$this, 'testAttachmentCreation'], $xml);
        $this->test('Post Title Generation', [$this, 'testPostTitles'], $xml);
        $this->test('Post Slug Generation', [$this, 'testPostSlugs'], $xml);
        
        // Step 5: Content Structure Tests
        $this->test('WordPress Gutenberg Blocks', [$this, 'testGutenbergBlocks'], $xml);
        $this->test('EXIF Data Formatting', [$this, 'testEXIFFormatting'], $xml);
        
        // Step 6: Relationship Tests
        $this->test('Album-Photo Relationships', [$this, 'testAlbumRelationships'], $xml);
        $this->test('Attachment-Post Relationships', [$this, 'testAttachmentRelationships'], $xml);
        $this->test('Category Assignment', [$this, 'testCategoryAssignment'], $xml);
        
        // Step 7: Data Integrity Tests
        $this->test('Original URL Preservation', [$this, 'testOriginalURLs'], $xml);
        $this->test('Date Handling Priority', [$this, 'testDateHandling'], $xml);
        $this->test('Special Character Handling', [$this, 'testSpecialCharacters'], $xml);
        
        // Summary
        $this->printSummary();
        
        // Cleanup
        if (file_exists($this->testOutputFile)) {
            unlink($this->testOutputFile);
        }
        
        exit($this->failCount > 0 ? 1 : 0);
    }
    
    private function test(string $name, callable $testFunction, $xml = null): void
    {
        try {
            $result = $xml ? $testFunction($xml) : $testFunction();
            if ($result === true) {
                echo "✓ {$name}\n";
                $this->passCount++;
            } else {
                echo "❌ {$name}: " . ($result ?: 'Failed') . "\n";
                $this->failCount++;
            }
        } catch (Exception $e) {
            echo "❌ {$name}: Exception - " . $e->getMessage() . "\n";
            $this->failCount++;
        }
    }
    
    private function testXMLGeneration(): bool|string
    {
        // Generate XML using the main script
        $command = 'php flickr-to-wordpress-xml.php --json-dir=tests/sample --output=' . $this->testOutputFile . ' 2>&1';
        exec($command, $output, $returnCode);
        
        if ($returnCode !== 0) {
            return 'Command failed: ' . implode(' ', $output);
        }
        
        if (!file_exists($this->testOutputFile)) {
            return 'Output file not created';
        }
        
        return true;
    }
    
    private function loadXML(): ?SimpleXMLElement
    {
        try {
            $xml = simplexml_load_file($this->testOutputFile);
            return $xml ?: null;
        } catch (Exception $e) {
            echo "XML parsing error: " . $e->getMessage() . "\n";
            return null;
        }
    }
    
    private function testXMLStructure(SimpleXMLElement $xml): bool|string
    {
        // Check required WordPress WXR namespaces
        $namespaces = $xml->getNamespaces(true);
        $requiredNamespaces = ['wp', 'content', 'dc', 'excerpt'];
        
        foreach ($requiredNamespaces as $ns) {
            if (!isset($namespaces[$ns])) {
                return "Missing namespace: {$ns}";
            }
        }
        
        // Check required channel elements
        if (!isset($xml->channel)) {
            return 'Missing channel element';
        }
        
        $channel = $xml->channel;
        if (!$channel->title || !$channel->link || !$channel->description) {
            return 'Missing required channel elements';
        }
        
        // Check WXR version
        $wpElements = $channel->children('wp', true);
        if (!$wpElements->wxr_version || (string)$wpElements->wxr_version !== '1.2') {
            return 'Missing or incorrect WXR version';
        }
        
        return true;
    }
    
    private function testAlbumTags(SimpleXMLElement $xml): bool|string
    {
        $tags = $xml->xpath('//wp:tag');
        
        if (count($tags) !== 3) {
            return 'Expected 3 album tags, found ' . count($tags);
        }
        
        return true;
    }
    
    private function testTagNames(SimpleXMLElement $xml): bool|string
    {
        $expectedTags = [
            '#aBitHiddenIsland',
            '#livingDanang 2016',
            'Central Highlands (Vietnam) 2015'
        ];
        
        $tags = $xml->xpath('//wp:tag/wp:tag_name');
        $actualTags = array_map(fn($tag) => (string)$tag, $tags);
        
        foreach ($expectedTags as $expectedTag) {
            if (!in_array($expectedTag, $actualTags)) {
                return "Missing expected tag: {$expectedTag}";
            }
        }
        
        return true;
    }
    
    private function testTagSlugs(SimpleXMLElement $xml): bool|string
    {
        $expectedSlugs = [
            'abithiddenisland',
            'livingdanang-2016',
            'central-highlands-vietnam-2015'
        ];
        
        $slugs = $xml->xpath('//wp:tag/wp:tag_slug');
        $actualSlugs = array_map(fn($slug) => (string)$slug, $slugs);
        
        foreach ($expectedSlugs as $expectedSlug) {
            if (!in_array($expectedSlug, $actualSlugs)) {
                return "Missing expected slug: {$expectedSlug}";
            }
        }
        
        return true;
    }
    
    private function testPostCreation(SimpleXMLElement $xml): bool|string
    {
        $posts = $xml->xpath('//item[wp:post_type="post"]');
        
        if (count($posts) !== 8) {
            return 'Expected 8 posts, found ' . count($posts);
        }
        
        // Check all posts have required elements
        foreach ($posts as $post) {
            $wp = $post->children('wp', true);
            if (!$post->title || !$wp->post_id || !$wp->status) {
                return 'Post missing required elements';
            }
            
            if ((string)$wp->status !== 'private') {
                return 'Posts should have private status';
            }
        }
        
        return true;
    }
    
    private function testAttachmentCreation(SimpleXMLElement $xml): bool|string
    {
        $attachments = $xml->xpath('//item[wp:post_type="attachment"]');
        
        if (count($attachments) !== 8) {
            return 'Expected 8 attachments, found ' . count($attachments);
        }
        
        // Check all attachments have required elements
        foreach ($attachments as $attachment) {
            $wp = $attachment->children('wp', true);
            if (!$wp->attachment_url || !$wp->post_parent) {
                return 'Attachment missing required elements';
            }
            
            if ((string)$wp->status !== 'inherit') {
                return 'Attachments should have inherit status';
            }
        }
        
        return true;
    }
    
    private function testPostTitles(SimpleXMLElement $xml): bool|string
    {
        $posts = $xml->xpath('//item[wp:post_type="post"]');
        $expectedTitlePatterns = [
            'Job is both fun and colorful!',
            'Xanh xanh, mát mát.',
            'Lặng lẽ hoàng hôn.',
            'Humble speakers',
            'Candlelit Paper Flowers or No Rubbish?',
            'Hue it is.',
            'Childhood Moment', // May have invisible Unicode characters
            'Hue - So art so deep!'
        ];
        
        $foundNamedTitles = 0;
        $foundFallbackTitles = 0;
        
        foreach ($posts as $post) {
            $title = (string)$post->title;
            
            // Check for exact match or pattern match (for Unicode issues)
            $matched = false;
            foreach ($expectedTitlePatterns as $pattern) {
                if ($title === $pattern || str_contains($title, $pattern) || str_contains($pattern, trim($title))) {
                    $matched = true;
                    break;
                }
            }
            
            if ($matched) {
                $foundNamedTitles++;
            } elseif (str_starts_with($title, 'Photo taken at ')) {
                $foundFallbackTitles++;
            } else {
                return "Unexpected title format: '{$title}'";
            }
        }
        
        // Should have 8 named posts (all sample photos have names) and 0 fallback titles
        if ($foundNamedTitles !== 8) {
            return "Expected 8 named titles, found {$foundNamedTitles}";
        }
        
        if ($foundFallbackTitles !== 0) {
            return "Expected 0 fallback titles, found {$foundFallbackTitles}";
        }
        
        return true;
    }
    
    private function testPostSlugs(SimpleXMLElement $xml): bool|string
    {
        $posts = $xml->xpath('//item[wp:post_type="post"]');
        $expectedSlugPatterns = [
            'job-is-both-fun-and-colorful',
            'xanh-xanh-mat-mat',
            'lang-le-hoang-hon',
            'humble-speakers',
            'candlelit-paper-flowers-or-no-rubbish',
            'hue-it-is',
            'childhood-moment',
            'hue-so-art-so-deep'
        ];
        
        $foundValidSlugs = 0;
        
        foreach ($posts as $post) {
            $wp = $post->children('wp', true);
            $slug = (string)$wp->post_name;
            
            // Check if slug matches expected patterns or fallback format
            $matched = false;
            
            // Check against expected slug patterns
            foreach ($expectedSlugPatterns as $pattern) {
                if ($slug === $pattern) {
                    $matched = true;
                    break;
                }
            }
            
            // Check fallback format: photo-taken-at-YYYY-MM-DD-HH-MM-SS
            if (!$matched && preg_match('/^photo-taken-at-\d{4}-\d{2}-\d{2}-\d{2}-\d{2}-\d{2}$/', $slug)) {
                $matched = true;
            }
            
            if ($matched) {
                $foundValidSlugs++;
            } else {
                return "Invalid slug format: '{$slug}'";
            }
            
            // Verify slug follows WordPress conventions
            if (preg_match('/[^a-z0-9\-]/', $slug)) {
                return "Slug contains invalid characters: '{$slug}'";
            }
            
            if (str_starts_with($slug, '-') || str_ends_with($slug, '-')) {
                return "Slug has leading/trailing hyphens: '{$slug}'";
            }
            
            if (str_contains($slug, '--')) {
                return "Slug has double hyphens: '{$slug}'";
            }
        }
        
        if ($foundValidSlugs !== 8) {
            return "Expected 8 valid slugs, found {$foundValidSlugs}";
        }
        
        return true;
    }
    
    private function testGutenbergBlocks(SimpleXMLElement $xml): bool|string
    {
        $posts = $xml->xpath('//item[wp:post_type="post"]');
        
        foreach ($posts as $post) {
            $content = $post->children('content', true);
            $encoded = (string)$content->encoded;
            
            // Check for WordPress image block
            if (!str_contains($encoded, '<!-- wp:image')) {
                return 'Missing WordPress image block';
            }
            
            // Check for paragraph blocks
            if (!str_contains($encoded, '<!-- wp:paragraph -->')) {
                return 'Missing paragraph blocks';
            }
            
            // Check for "Taken on" text
            if (!str_contains($encoded, 'Taken on ')) {
                return 'Missing "Taken on" paragraph';
            }
            
            // Check for "Originally from" text
            if (!str_contains($encoded, 'Originally from:')) {
                return 'Missing "Originally from" paragraph';
            }
        }
        
        return true;
    }
    
    private function testEXIFFormatting(SimpleXMLElement $xml): bool|string
    {
        $attachments = $xml->xpath('//item[wp:post_type="attachment"]');
        $foundEXIFData = false;
        
        foreach ($attachments as $attachment) {
            $description = (string)$attachment->description;
            
            if (!empty($description)) {
                if (!str_starts_with($description, 'flickr_exif_data:')) {
                    return 'EXIF data should start with "flickr_exif_data:"';
                }
                
                if (!str_contains($description, '•')) {
                    return 'EXIF data should contain bullet points';
                }
                
                $foundEXIFData = true;
            }
        }
        
        if (!$foundEXIFData) {
            return 'No EXIF data found in any attachment';
        }
        
        return true;
    }
    
    private function testAlbumRelationships(SimpleXMLElement $xml): bool|string
    {
        $posts = $xml->xpath('//item[wp:post_type="post"]');
        $foundTaggedPosts = 0;
        
        foreach ($posts as $post) {
            $categories = $post->xpath('category[@domain="post_tag"]');
            if (count($categories) > 0) {
                $foundTaggedPosts++;
            }
        }
        
        // Should have some posts with album tags (not all photos are in albums)
        if ($foundTaggedPosts < 5) {
            return "Expected more posts with album tags, found {$foundTaggedPosts}";
        }
        
        return true;
    }
    
    private function testAttachmentRelationships(SimpleXMLElement $xml): bool|string
    {
        $attachments = $xml->xpath('//item[wp:post_type="attachment"]');
        $posts = $xml->xpath('//item[wp:post_type="post"]');
        
        $postIds = [];
        foreach ($posts as $post) {
            $wp = $post->children('wp', true);
            $postIds[] = (string)$wp->post_id;
        }
        
        foreach ($attachments as $attachment) {
            $wp = $attachment->children('wp', true);
            $parentId = (string)$wp->post_parent;
            
            if (!in_array($parentId, $postIds)) {
                return "Attachment parent ID {$parentId} not found in posts";
            }
        }
        
        return true;
    }
    
    private function testCategoryAssignment(SimpleXMLElement $xml): bool|string
    {
        $posts = $xml->xpath('//item[wp:post_type="post"]');
        
        foreach ($posts as $post) {
            $categories = $post->xpath('category[@domain="category" and @nicename="from-flickr"]');
            if (count($categories) !== 1) {
                return 'Not all posts have "From Flickr" category';
            }
        }
        
        return true;
    }
    
    private function testOriginalURLs(SimpleXMLElement $xml): bool|string
    {
        $attachments = $xml->xpath('//item[wp:post_type="attachment"]');
        
        foreach ($attachments as $attachment) {
            $wp = $attachment->children('wp', true);
            $url = (string)$wp->attachment_url;
            
            if (!str_starts_with($url, 'https://live.staticflickr.com/')) {
                return "Invalid Flickr URL format: {$url}";
            }
        }
        
        return true;
    }
    
    private function testDateHandling(SimpleXMLElement $xml): bool|string
    {
        $posts = $xml->xpath('//item[wp:post_type="post"]');
        
        foreach ($posts as $post) {
            $wp = $post->children('wp', true);
            $postDate = (string)$wp->post_date;
            $postDateGmt = (string)$wp->post_date_gmt;
            
            if (empty($postDate) || empty($postDateGmt)) {
                return 'Missing post dates';
            }
            
            // Basic date format validation
            if (!preg_match('/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/', $postDate)) {
                return "Invalid date format: {$postDate}";
            }
        }
        
        return true;
    }
    
    private function testSpecialCharacters(SimpleXMLElement $xml): bool|string
    {
        // Check that special characters in album titles are preserved
        $tags = $xml->xpath('//wp:tag/wp:tag_name');
        $foundHashTag = false;
        
        foreach ($tags as $tag) {
            $tagName = (string)$tag;
            if (str_contains($tagName, '#')) {
                $foundHashTag = true;
                break;
            }
        }
        
        if (!$foundHashTag) {
            return 'Hash characters not preserved in tag names';
        }
        
        return true;
    }
    
    private function printSummary(): void
    {
        echo "\n" . str_repeat("=", 60) . "\n";
        echo "📊 Test Summary\n";
        echo str_repeat("=", 60) . "\n";
        echo "✓ Passed: {$this->passCount}\n";
        echo "❌ Failed: {$this->failCount}\n";
        echo "Total: " . ($this->passCount + $this->failCount) . "\n\n";
        
        if ($this->failCount === 0) {
            echo "🎉 All tests passed! The XML export is working correctly.\n";
        } else {
            echo "⚠️  Some tests failed. Please review the issues above.\n";
        }
    }
}

// Run the tests
$test = new FlickrXMLTest();
$test->run();