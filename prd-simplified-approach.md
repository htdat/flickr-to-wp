# Flickr to WordPress XML Exporter

## Project Description 

This project converts downloaded Flickr data (photos and albums) into a WordPress WXR (WordPress eXtended RSS) XML export file. The generated XML can then be imported into any WordPress site using WordPress's built-in import functionality.

**Key Advantage:** Instead of directly manipulating WordPress via WP CLI, we generate a standard WordPress export XML file that leverages WordPress's proven import system for reliability, error handling, and user control.

## Technical Architecture

### Approach Overview
**Input:** Flickr JSON metadata only  
**Processing:** Standalone PHP script  
**Output:** WordPress WXR XML file with Flickr image URLs  
**Import:** Standard WordPress Admin → Tools → Import → WordPress

### Benefits Over Direct Database Approach
- **Simplicity:** ~200 lines of PHP vs complex WP CLI plugin
- **Reliability:** Uses WordPress's battle-tested import system
- **Portability:** Works with any WordPress installation
- **User Control:** XML can be reviewed/edited before import
- **Error Handling:** WordPress import handles duplicates, failures, media downloads
- **Progress Tracking:** Built-in WordPress import progress indicators

## Data Mapping Specifications

### Flickr Albums → WordPress Tags

**XML Structure:**
```xml
<wp:tag>
    <wp:term_id>[auto-increment]</wp:term_id>
    <wp:tag_slug>[slug-from-title]</wp:tag_slug>
    <wp:tag_name><![CDATA[[album.title]]]></wp:tag_name>
    <wp:term_description><![CDATA[[album.description]]]></wp:term_description>
</wp:tag>
```

**Mapping Rules:**
- **Tag Name:** Use album `title` directly (e.g., "Happy Baby in Danang 2017", "#livingDanang 2016")
- **Tag Description:** Use album `description` only (some may be empty)
- **Tag Slug:** Generate from title (lowercase, spaces to hyphens, special chars removed)

### Flickr Photos → WordPress Posts + Attachments

**Post XML Structure:**
```xml
<item>
    <title><![CDATA[[post_title]]]></title>
    <link>[permalink]</link>
    <pubDate>[rfc_date]</pubDate>
    <dc:creator><![CDATA[admin]]></dc:creator>
    <guid isPermaLink="false">[unique_guid]</guid>
    <description></description>
    <content:encoded><![CDATA[[post_content]]]></content:encoded>
    <wp:post_id>[auto-increment]</wp:post_id>
    <wp:post_date><![CDATA[[formatted_date]]]></wp:post_date>
    <wp:post_date_gmt><![CDATA[[formatted_date_gmt]]]></wp:post_date_gmt>
    <wp:comment_status><![CDATA[open]]></wp:comment_status>
    <wp:ping_status><![CDATA[open]]></wp:ping_status>
    <wp:post_name><![CDATA[[post-slug]]]></wp:post_name>
    <wp:status><![CDATA[publish]]></wp:status>
    <wp:post_parent>0</wp:post_parent>
    <wp:menu_order>0</wp:menu_order>
    <wp:post_type><![CDATA[post]]></wp:post_type>
    <wp:post_password><![CDATA[]]></wp:post_password>
    <wp:is_sticky>0</wp:is_sticky>
    
    <!-- Tag assignments -->
    <category domain="post_tag" nicename="[tag-slug]"><![CDATA[[tag-name]]]></category>
    <category domain="post_tag" nicename="flickr-to-wp"><![CDATA[flickr-to-wp]]></category>
    
    <!-- Featured image reference -->
    <wp:postmeta>
        <wp:meta_key><![CDATA[_thumbnail_id]]></wp:meta_key>
        <wp:meta_value><![CDATA[[attachment_id]]]></wp:meta_value>
    </wp:postmeta>
</item>
```

**Post Data Mapping:**
- **Post Title:** Use photo `name` field, fallback to "Photo taken at [date_taken]"
- **Post Content:** 
  - Start with photo `description` if available
  - Add "Taken on [date_taken]"
  - Add "Originally from: [photopage]"
- **Post Date:** Use `date_imported` (actual Flickr publication date), fallback to `date_taken`
- **Post Status:** `publish`
- **Post Slug:** Generate from title or photo ID

### Photo Attachments → WordPress Media

**Attachment XML Structure:**
```xml
<item>
    <title><![CDATA[[photo_name]]]></title>
    <link>[attachment_url]</link>
    <pubDate>[rfc_date]</pubDate>
    <dc:creator><![CDATA[admin]]></dc:creator>
    <guid isPermaLink="false">[attachment_url]</guid>
    <description><![CDATA[[exif_data]]]></description>
    <content:encoded><![CDATA[[exif_data]]]></content:encoded>
    <wp:post_id>[auto-increment]</wp:post_id>
    <wp:post_date><![CDATA[[formatted_date]]]></wp:post_date>
    <wp:post_date_gmt><![CDATA[[formatted_date_gmt]]]></wp:post_date_gmt>
    <wp:comment_status><![CDATA[open]]></wp:comment_status>
    <wp:ping_status><![CDATA[closed]]></wp:ping_status>
    <wp:post_name><![CDATA[[attachment-slug]]]></wp:post_name>
    <wp:status><![CDATA[inherit]]></wp:status>
    <wp:post_parent>[parent_post_id]</wp:post_parent>
    <wp:menu_order>0</wp:menu_order>
    <wp:post_type><![CDATA[attachment]]></wp:post_type>
    <wp:post_password><![CDATA[]]></wp:post_password>
    <wp:is_sticky>0</wp:is_sticky>
    
    <wp:attachment_url><![CDATA[[flickr_original_url]]]></wp:attachment_url>
</item>
```

**Attachment Data Mapping:**
- **Title:** Photo `name` field
- **Description:** EXIF data formatted as `flickr_exif_data:` followed by bullet points
- **Date:** Same as parent post
- **Status:** `inherit` (attached to post)
- **Attachment URL:** Original Flickr URL for WordPress to download during import

## Data Processing Logic

### Image URL Processing
- **Source:** Use `original` field from each `photo_[id].json` file
- **Format:** Direct Flickr URLs (e.g., `https://live.staticflickr.com/5545/10682465326_ba1cee9e29_o.jpg`)
- **WordPress handling:** WordPress import will automatically download images during import process
- **No local files needed:** Eliminates file matching complexity entirely

### EXIF Data Processing
- **Source:** Use only EXIF data from `photo_[id].json` file's `exif` field
- **Format:** `flickr_exif_data:` followed by bullet points
- **Example:**
  ```
  flickr_exif_data:
  • Make: Canon
  • Model: Canon EOS 600D
  • ISO: 250
  • FNumber: f/5.6
  • ExposureTime: 0.013 sec (1/80)
  • FocalLength: 55 mm
  ```
- **If no EXIF:** Skip EXIF section entirely

## Implementation Specifications

### Command Structure
```bash
php flickr-to-wordpress-xml.php \
    --json-dir=/path/to/flickr-data/json \
    --output=/path/to/flickr-export.xml \
    [--site-url=https://example.com] \
    [--author=admin] \
    [--dry-run] \
    [--verbose]
```

### Command Options
- **`--json-dir`** (required): Path to Flickr JSON files directory
- **`--output`** (required): Output XML file path
- **`--site-url`** (optional): Base URL for generating permalinks (default: https://example.com)
- **`--author`** (optional): WordPress author username (default: admin)
- **`--dry-run`** (optional): Generate XML without writing file, show statistics
- **`--verbose`** (optional): Show detailed processing information

### Error Handling & Logging
- **Missing JSON files:** Skip and log warning
- **Corrupted JSON:** Skip and log error
- **Missing `original` URL:** Skip photo and log warning
- **Log format:** Simple text output to console
- **Exit codes:** 0 for success, 1 for errors

### WordPress XML Format Requirements

**XML Declaration:**
```xml
<?xml version="1.0" encoding="UTF-8" ?>
<rss version="2.0"
    xmlns:excerpt="http://wordpress.org/export/1.2/excerpt/"
    xmlns:content="http://purl.org/rss/1.0/modules/content/"
    xmlns:wfw="http://wellformedweb.org/CommentAPI/"
    xmlns:dc="http://purl.org/dc/elements/1.1/"
    xmlns:wp="http://wordpress.org/export/1.2/"
>
```

**Required Channel Elements:**
- `<title>`, `<link>`, `<description>`, `<pubDate>`, `<language>`
- `<wp:wxr_version>1.2</wp:wxr_version>`
- `<wp:base_site_url>` and `<wp:base_blog_url>`

**ID Management:**
- Auto-increment post IDs starting from 1
- Auto-increment term IDs starting from 1
- Maintain ID relationships between posts and attachments

## User Import Workflow

### Step 1: Generate XML
```bash
php flickr-to-wordpress-xml.php \
    --json-dir=./flickr-data/json \
    --output=./flickr-import.xml \
    --site-url=https://mysite.com
```

### Step 2: Review XML (Optional)
- Users can inspect the generated XML file
- Edit content, remove unwanted photos, modify tags
- Validate XML structure if needed

### Step 3: WordPress Import
1. **Log into WordPress Admin** as administrator
2. **Navigate to Tools → Import**
3. **Install WordPress Importer** plugin if not present
4. **Choose "WordPress" from importer list**
5. **Upload the generated XML file**
6. **Map authors** (or create new ones)
7. **Choose import options:**
   - Download and import file attachments: **Yes** (important for photos)
   - Import author information: **Optional**
8. **Run the import**

### Step 4: Verification
- Check that posts were created with correct dates
- Verify featured images are attached
- Confirm tags were created and assigned
- Review any import warnings/errors

## Performance Considerations

### Memory Management
- **Stream processing:** Process one JSON file at a time
- **XML writing:** Write directly to file, don't build entire XML in memory
- **Image handling:** Reference files only, don't load image data

### Batch Processing
- **No batching needed:** XML generation is fast
- **Large datasets:** Progress indicators for user feedback
- **Estimated time:** ~1000 photos processed per minute

### File Size Limits
- **XML file size:** Typical export with 1000 photos ≈ 2-5MB
- **WordPress import limits:** Most servers handle up to 50MB imports
- **Large datasets:** Option to split into multiple XML files

## Development Environment

### Requirements
- **PHP 8.3+** with XML extensions
- **Internet connection** for validating Flickr URLs (optional)
- **No WordPress installation needed** for XML generation
- **File system access** to Flickr JSON directory only

### Development Setup
```bash
# No complex setup required
git clone [repository]
cd flickr-to-wp
php flickr-to-wordpress-xml.php --help
```

### Testing Strategy
- **Unit tests:** JSON parsing, XML generation, file matching
- **Integration tests:** Complete workflow with sample Flickr data
- **Validation:** Generated XML against WordPress WXR schema
- **Import testing:** Verify XML imports correctly into WordPress

## Sample Data Structure

```
project/
├── flickr-data/
│   └── json/
│       ├── albums.json
│       ├── photo_10682465326.json
│       └── ... (more photo JSONs)
├── flickr-to-wordpress-xml.php
└── output/
    └── flickr-import.xml
```

## Future Enhancements

### Version 1.1 Considerations
- **Content customization:** Template system for post content generation
- **Taxonomy options:** Import albums as categories instead of tags
- **Image validation:** Check if Flickr URLs are still accessible before generating XML
- **Batch splitting:** Automatically split large datasets into multiple XML files
- **Progress resumption:** Resume interrupted XML generation
- **URL fallback:** Handle cases where original Flickr URLs are no longer accessible

### Advanced Features
- **Custom fields:** Map additional Flickr metadata to WordPress custom fields
- **Geolocation:** Convert GPS data to WordPress location plugins
- **Multi-author support:** Map Flickr photos to different WordPress authors
- **Content filtering:** Rules to skip certain photos or albums during generation