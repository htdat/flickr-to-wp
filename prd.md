# Convert Flick photos and albums to WordPress content 

## Project Description 

The project converts the downloaded data from Flick to WordPress content, focusing on photos and albums. 

## Technical specs 

### Development environment 

- Docker with **wp-env** (@wordpress/env) - Official WordPress.org Docker development environment
  - Install: `npm install -g @wordpress/env`
  - Start: `wp-env start` (Access at http://localhost:8888, admin/password)
  - Built on Docker Compose, zero-configuration tool
  - Includes WP CLI by default for plugin development

### General requirements 

- Initial version supports only WP CLI with custom command: `wp flickr_import`
- During the development, ensure there is the proper logging to the standard WordPress log file as well as to the screen for WP CLI.

### Main Feature 1 - Flickr photos => WordPress media + post: 
- Each photo will be converted into the proper WordPress media. 
    - EXIF data is added to the `description` field of media metadata only (not post content)
    - Description should start with `flickr_exif_data:` then all bullets of EXIF data, each line of bullet includes EXIF name and its value such as: `ImageWidth: 320`
    - Include all available EXIF fields (no prioritization needed)
- This photo will be added into a normal post including this photo as the featured image with the proper description.
    - Tag this post with a tag `flickr-to-wp`

#### Data Mapping Rules:
**Post Title:**
- Use photo `name` field (e.g., "Job is both fun and colorful!")
- Fallback: "Photo taken at [date_taken]" (e.g., "Photo taken at 2015-06-08 05:46:43")

**Post Content:**
- Start with photo `description` if available
- Include photo details: "Taken on [date_taken]"
- Add photo page link: "Originally from: [photopage]"

**Post Published Time:**
- Use `date_imported` (the actual Flickr publication date)
- Fallback to `date_taken` if date_imported missing 

### Main Feature 2 - Flickr albums => WordPress tags

- Each Flickr album will become a WordPress tag.
- Add relevant photos into this tag.

#### Album-to-Tag Mapping Rules:
**Tag Name:**
- Use album `title` directly (e.g., "Happy Baby in Danang 2017", "#livingDanang 2016")

**Tag Description:**
- Use album `description` only (some may be empty)

## Provided data as sample 

```
this-repo/
└── flickr-data/
    ├── json/                          # JSON metadata files
    │   ├── albums.json               # Album information
    │   ├── photo_20617248745.json    # Individual photo metadata
    │   ├── photo_28198606453.json
    │   ├── photo_24242676554.json
    │   └── ... (additional photo_*.json files)
    └── original-photos/              # Original photo files directory
        └── (original photo files corresponding to each photo_*.json)
```

## Technical Implementation

### WP CLI Command Structure

**Main Command:**
```bash
wp flickr_import --json-dir=/path/to/flickr-data/json --photos-dir=/path/to/flickr-data/original-photos
```

**Command Options:**
```bash
wp flickr_import \
  --json-dir=/path/to/json \
  --photos-dir=/path/to/photos \
  [--dry-run] \
  [--verbose] \
  [--skip-existing] \
  [--albums-only] \
  [--photos-only]
```

**Implementation:**
- Plugin file: `flickr-to-wp.php`
- WP CLI class: `Flickr_Import_CLI_Command`
- Command registration: `WP_CLI::add_command('flickr_import', 'Flickr_Import_CLI_Command')`

### File Matching Logic

**Process:**
1. Extract filename from `original` URL in JSON
2. Check if exact filename exists in `original-photos/`
3. If not found, search for any file containing photo `id` in filename
4. If still not found, log as missing file and **do not add it to WordPress**

**Examples:**
- Type 1: `photo_40516244825.json` → `40516244825_acc0f2b365_o.jpg` (exact match)
- Type 2: `photo_11649221333.json` → `si-thnh---qun-nht_11649221333_o.jpg` (search by ID)

### Error Handling & Logging

**Behavior:**
- Skip corrupted JSON files and log them
- Skip WordPress upload failures and log them
- No retry mechanism

**Logging:**
- **Location:** `wp-content/uploads/`
- **Filename:** `flickr_import_[date_time_in_utc].txt`
- **Format:** `id_[id] WARNING/INFO/ERROR : message`

**Examples:**
```
id_11649221333 INFO : Successfully imported photo
id_20429280958 ERROR : Image file not found
id_14139582351 WARNING : JSON file corrupted, skipping
id_10682465326 ERROR : WordPress upload failed
```

### Duplicate Handling

**Detection Method:**
- Check for existing files by filename using WordPress `WP_Query`
- Query: `post_type => 'attachment'`, `post_status => 'inherit'`, search by `_wp_attached_file` meta

**Behavior:**
- If duplicate found: Skip import and log warning
- Log format: `id_[id] WARNING : Duplicate file found: [filename], skipping import`

## Some other considerations, for later versions.

- Flickr json prop `.original` has already included the orignal photo public links so we can offer the feature to download these links direclty, and users do not need to point out the path on the host to the original-photos directory.

## Still To Be Defined

The following items need further specification:

1. **Command Options & Behavior**
   - Default behavior for `--dry-run`, `--verbose`, `--skip-existing`
   - Validation rules for directory paths

2. **Performance Considerations**
   - Batch processing limits (how many photos at once?)
   - Memory usage limits for large datasets
   - Progress reporting for long-running imports 
