# Convert Flick photos and albums to WordPress content 

## Project Description 

The project converts the downloaded data from Flick to WordPress content, focusing on photos and albums. 

## Technical specs 

### Development environment 

- Docker with a support tool by WordPress core. TODO: search on wordpress.org to find a tool supporting a WordPress plugin like this.

### General requirements 

- Initial version supports only WP CLI.
- During the development, ensure there is the proper logging to the standard WordPress log file as well as to the screen for WP CLI.

### Main Feature 1 - Flick photos => WordPress media + post: 
- Each photo will be converted into the proper WordPress media. 
    - Exif data is added to the `description` field of metadata. 
    - Description should start with `flickr_exif_data:` then all bullets of exit data, each line of bullet includes exif name and its value such as: `ImageWidth: 320`
- This photo will be added into a normal post including this photo as the first image with the proper descripton.
    - Tag this post with a tag `flickr-to-wp`. 
    - TODO: figure out what WordPress post content and title, post_published time, from the Flickr data. 

### Main Feature 2 

- Each Flickr album will become a WordPress tag. 
    - TODO: figure out what should be tag name and tag description from Flickr data.
- Add relevant photos into this tag.

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

## Some other considerations, for later versions.

- Flickr json prop `.original` has already included the orignal photo public links so we can offer the feature to download these links direclty, and users do not need to point out the path on the host to the original-photos directory. 
