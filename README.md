# WordPress Custom Font Uploader

A WordPress plugin that allows users to upload custom fonts through the WordPress general settings page.

## Features

- Upload custom font files (.woff, .woff2, .ttf, .otf)
- Manage uploaded fonts through the WordPress admin interface
- Automatic @font-face declarations
- Secure file handling and validation
- Clean and user-friendly interface

## Installation

### Method 1: Using the ZIP file (Recommended)
1. Download the `custom-font-uploader.zip` file from the releases
2. Go to WordPress admin panel > Plugins > Add New
3. Click "Upload Plugin" and select the downloaded zip file
4. Click "Install Now" and then "Activate"

### Method 2: Manual Installation
1. Download the plugin files
2. Upload the `custom-font-uploader` folder to the `/wp-content/plugins/` directory
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Go to Settings > General to find the new "Custom Font Uploader" section

## Usage

1. Navigate to Settings > General in your WordPress admin panel
2. Scroll down to the "Custom Font Uploader" section
3. Click "Choose File" to select a font file
4. Click "Save Changes" to upload the font
5. The font will be automatically available for use in your theme

## Using Custom Fonts in Your Theme

To use the uploaded fonts in your theme, reference them using the font-family name that matches the uploaded file name (without the extension). For example:

```css
.your-element {
    font-family: 'YourFontName', sans-serif;
}
```

## Requirements

- WordPress 5.0 or higher
- PHP 7.0 or higher

## License

This plugin is licensed under the GPL v2 or later.

## Author

Austin Ross - [https://rossworks.net](https://rossworks.net) 