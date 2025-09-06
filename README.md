# Flickr to WordPress XML Exporter

Convert your downloaded Flickr photos and albums into a WordPress-ready XML file that can be imported using WordPress's built-in import functionality.

## 🚀 Quick Start

```bash
# Generate XML from your Flickr data
php flickr-to-wordpress-xml.php \
    --json-dir=./flickr-data/json \
    --output=./flickr-import.xml \
    --site-url=https://yoursite.com

# Run tests to validate everything works
php tests/run-tests.php
```

## 📋 What This Does

- **Albums → WordPress Tags**: Your Flickr albums become searchable tags
- **Photos → WordPress Posts**: Each photo becomes a post with configurable status
- **EXIF Data**: Camera settings preserved in attachment metadata  
- **Gutenberg Blocks**: Modern WordPress block format for images and content
- **Original URLs**: WordPress downloads images during import (no local files needed)

## 🎯 Key Benefits

✅ **Simple**: Just PHP - no complex dependencies or WordPress installation needed  
✅ **Safe**: Generates standard WordPress XML that you can review before importing  
✅ **Reliable**: Uses WordPress's proven import system with error handling  
✅ **Portable**: Works with any WordPress site via Admin → Tools → Import  

## 📦 Requirements

- **PHP 8.3+** with XML extensions
- **Flickr JSON data** (from Flickr data export)

## 🛠️ Installation & Usage

### 1. Get Your Flickr Data
Download your Flickr data export and extract the JSON files to a directory like `flickr-data/json/`.

### 2. Generate WordPress XML
```bash
# Basic usage
php flickr-to-wordpress-xml.php --json-dir=path/to/json --output=export.xml

# With custom options
php flickr-to-wordpress-xml.php \
    --json-dir=./flickr-data/json \
    --output=./my-photos.xml \
    --site-url=https://myblog.com \
    --author=admin \
    --post-status=draft \
    --start-post-id=20000 \
    --verbose
```

**Options:**
- `--json-dir` (required): Path to Flickr JSON files
- `--output` (required): Output XML file path  
- `--site-url`: Your WordPress site URL (default: https://example.com)
- `--author`: WordPress author username (default: admin)
- `--post-status`: Post status: `publish`, `draft`, `pending`, `private` (default: private)
- `--start-post-id`: Starting ID for posts/attachments (default: 10000)
- `--start-term-id`: Starting ID for tags/categories (default: 10000)
- `--dry-run`: Preview without creating files
- `--verbose`: Show detailed progress

### 3. Import to WordPress

#### For Large Imports (100+ photos) - Recommended
Use WP-CLI for better reliability and performance with large datasets:

```bash
wp import flickr-import.xml --authors=skip
```

See [WP-CLI Import documentation](https://developer.wordpress.org/cli/commands/import/) for full options and setup.

#### For Small Imports (<100 photos) - WordPress Admin
1. Log into your WordPress Admin
2. Go to **Tools → Import**  
3. Install **WordPress Importer** if needed
4. Choose **WordPress** from the list
5. Upload your generated XML file
6. **Important**: Enable "Download and import file attachments"
7. Run the import

### 4. Review & Publish
- Posts are created with your specified status (default: **Private** for review)
- Check that images loaded correctly
- Verify album tags were created  
- Publish posts when ready

## 🧪 Testing

Run the comprehensive test suite to validate everything works correctly:

```bash
php tests/run-tests.php
```

The test suite validates:
- XML structure and WordPress compatibility
- Album → Tag mapping with proper slugs
- Photo → Post conversion with Gutenberg blocks
- EXIF data formatting
- Special character handling (Vietnamese, Unicode)
- Attachment relationships

## 📁 Data Structure

Your Flickr export should look like:
```
flickr-data/
└── json/
    ├── albums.json          # Album information
    ├── photo_12345.json     # Individual photo data
    ├── photo_67890.json
    └── ...
```

## 🔧 What Gets Created

### Albums → WordPress Tags
- **Tag Name**: Album title (e.g., "#livingDanang 2016")
- **Tag Slug**: URL-friendly (e.g., "livingdanang-2016") 
- **Description**: Album description

### Photos → WordPress Posts + Attachments
- **Post Title**: Photo name or "Photo taken at [date]"
- **Post Content**: Gutenberg image block + metadata paragraphs
- **Post Status**: Configurable (default: Private for review)
- **Category**: "From Flickr" 
- **Tags**: Assigned album tags
- **Attachment**: Original Flickr image URL for WordPress to download

### EXIF Data
Preserved in attachment metadata as:
```
flickr_exif_data:
• Make: Canon
• Model: Canon EOS 600D
• ISO: 400
• FNumber: f/2.4
```

## 📊 Performance

- **Processing Speed**: ~1000 photos per minute
- **Memory Usage**: Minimal (streams data, doesn't load images)
- **XML Size**: ~2-5MB for 1000 photos
- **Import Limits**: 
  - **WordPress Admin**: Up to ~100 photos (PHP limits)
  - **WP-CLI**: No practical limits (recommended for 100+ photos)

## 🔍 Validation

The generated XML includes:
- ✓ Proper WordPress WXR format and namespaces
- ✓ Valid Gutenberg block structure  
- ✓ Correct post-attachment relationships
- ✓ Special character encoding (Vietnamese, Unicode)
- ✓ WordPress-compatible slugs and IDs

## 🛡️ Error Handling

- **Missing files**: Skipped with warnings
- **Corrupted JSON**: Logged and skipped
- **Missing images**: Photo skipped, process continues
- **Import conflicts**: Use `--start-post-id` to avoid ID collisions

## 🤝 Contributing

The project includes comprehensive tests and follows WordPress standards. See `prd-export-xml-approach.md` for detailed specifications.

## 📄 License

This project converts personal Flickr data to WordPress format for individual use.

---

**Need help?** Check the verbose output with `--verbose` or run `php flickr-to-wordpress-xml.php --help` for all options.