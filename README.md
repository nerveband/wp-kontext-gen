# WP Kontext Gen

A WordPress plugin for generating and editing images using Replicate's FLUX.1 Kontext [dev] model.

## Description

WP Kontext Gen brings the power of Black Forest Labs' FLUX.1 Kontext model directly to your WordPress admin. This state-of-the-art AI model allows you to:

- **Generate new images** from text prompts
- **Edit existing images** with natural language instructions
- **Transform styles** (watercolor, oil painting, sketches)
- **Modify objects** (change colors, add/remove elements)
- **Replace text** in signs and labels
- **Swap backgrounds** while preserving subjects

## Features

- 🎨 Easy-to-use admin interface
- 🔐 Secure API key storage
- 📊 Generation history tracking
- ⚙️ Customizable default parameters
- 📱 Responsive design
- 🚀 Fast generation with "Go Fast" mode
- 🖼️ Multiple output formats (WebP, JPEG, PNG)

## Installation

1. Download the plugin zip file
2. Navigate to WordPress Admin > Plugins > Add New
3. Click "Upload Plugin" and select the zip file
4. Activate the plugin
5. Go to Kontext Gen > Settings to configure your API key

## Configuration

### Getting Your API Key

1. Sign up for a [Replicate account](https://replicate.com)
2. Go to [API tokens page](https://replicate.com/account/api-tokens)
3. Create a new token
4. Copy the token and paste it in the plugin settings

### Default Parameters

You can set default values for:
- **Inference Steps** (1-50): Number of denoising steps
- **Guidance Scale** (0-10): How closely to follow the prompt
- **Output Format**: WebP, JPEG, or PNG
- **Output Quality** (0-100): Compression quality
- **Go Fast Mode**: Faster generation with slightly reduced quality

## Usage

### Generating Images

1. Go to **Kontext Gen** in your WordPress admin
2. Enter a descriptive prompt
3. (Optional) Upload an input image for editing
4. Adjust parameters as needed
5. Click "Generate Image"
6. Wait for the generation to complete
7. Download or view your generated image

### Prompting Tips

**Be Specific**
- Use clear, detailed language
- Specify exact colors: "bright red" not just "red"
- Name subjects directly: "the woman with short black hair"

**For Editing Images**
- Describe what to change clearly
- Specify what should stay the same
- Use quotation marks for text replacements

**Examples:**
- "Change the car color to metallic blue"
- "Replace the text 'SALE' with 'SOLD OUT' on the sign"
- "Transform this photo into a watercolor painting style"
- "Add a sunset background while keeping the person unchanged"

## Advanced Options

- **Aspect Ratio**: Match input image or choose from presets
- **Seed**: Use for reproducible results
- **Safety Checker**: Toggle NSFW content filtering
- **Go Fast Mode**: Quicker generation for most prompts

## History

View all your previous generations in **Kontext Gen > History**:
- See preview thumbnails
- View generation parameters
- Download successful generations
- Delete old generations

## System Requirements

- WordPress 5.0 or higher
- PHP 7.2 or higher
- Active Replicate API key

## Support

For issues or feature requests, please visit the [GitHub repository](https://github.com/nerveband/wp-kontext-gen).

## License

GPL v2 or later

## Credits

This plugin uses the [FLUX.1 Kontext [dev]](https://replicate.com/black-forest-labs/flux-kontext-dev) model by Black Forest Labs.