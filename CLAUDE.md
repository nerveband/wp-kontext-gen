# WP Kontext Gen - Development Guide

This document provides context and guidance for AI assistants working on the WP Kontext Gen WordPress plugin.

## Project Overview

WP Kontext Gen is a WordPress plugin that integrates Replicate's FLUX.1 Kontext [dev] model for AI-powered image generation and editing. The plugin provides a user-friendly admin interface for WordPress users to generate and edit images using natural language prompts.

## Architecture

### File Structure
```
wp-kontext-gen/
├── wp-kontext-gen.php          # Main plugin file
├── includes/
│   ├── class-wp-kontext-gen.php        # Core plugin class
│   ├── class-wp-kontext-gen-api.php    # Replicate API integration
│   └── class-wp-kontext-gen-admin.php  # Admin functionality
├── admin/
│   ├── css/
│   │   └── wp-kontext-gen-admin.css    # Admin styles
│   ├── js/
│   │   └── wp-kontext-gen-admin.js     # Admin JavaScript
│   └── partials/
│       ├── wp-kontext-gen-admin-display.php    # Main generation page
│       ├── wp-kontext-gen-settings-display.php # Settings page
│       └── wp-kontext-gen-history-display.php  # History page
├── README.md
└── CLAUDE.md
```

### Key Components

1. **API Integration** (`class-wp-kontext-gen-api.php`)
   - Handles all Replicate API communications
   - Manages API key validation
   - Creates predictions and checks status
   - Implements proper error handling

2. **Admin Interface** (`class-wp-kontext-gen-admin.php`)
   - Registers admin menus and pages
   - Handles AJAX requests
   - Manages settings and history
   - Enqueues scripts and styles

3. **Database**
   - Custom table: `{prefix}_kontext_gen_history`
   - Stores generation history with parameters and results

## Security Considerations

- API keys are stored encrypted in WordPress options
- All user inputs are sanitized using WordPress functions
- AJAX requests use nonces for security
- User capabilities are checked before operations
- File uploads use WordPress media library

## Testing Instructions

When testing the plugin:

1. **API Key Validation**
   - Test with invalid API key
   - Test with valid API key
   - Ensure error messages are clear

2. **Image Generation**
   - Test prompt-only generation
   - Test image editing with input image
   - Test all parameter combinations
   - Verify status checking works

3. **Error Handling**
   - Test network failures
   - Test API rate limits
   - Test invalid parameters
   - Ensure graceful degradation

## Development Guidelines

### Code Standards
- Follow WordPress Coding Standards
- Use proper internationalization (`__()`, `_e()`)
- Sanitize all inputs
- Escape all outputs
- Use WordPress APIs where possible

### Adding Features
- Maintain backward compatibility
- Update version numbers appropriately
- Add new database fields carefully
- Document new settings

### Common Tasks

**Adding a new parameter:**
1. Add to the form in `wp-kontext-gen-admin-display.php`
2. Add to AJAX handler in `class-wp-kontext-gen-admin.php`
3. Add to API call in `class-wp-kontext-gen-api.php`
4. Update JavaScript if needed

**Modifying the UI:**
1. Update partials in `admin/partials/`
2. Update CSS in `admin/css/`
3. Update JavaScript handlers

## API Reference

### Replicate API Endpoints
- Model: `black-forest-labs/flux-kontext-dev`
- Create prediction: `POST https://api.replicate.com/v1/predictions`
- Check status: `GET https://api.replicate.com/v1/predictions/{id}`

### Model Parameters
- `prompt` (required): Text description
- `input_image` (required for editing): Base64 or URL
- `aspect_ratio`: Image dimensions
- `num_inference_steps`: 1-50
- `guidance`: 0-10
- `seed`: For reproducibility
- `output_format`: webp, jpg, png
- `output_quality`: 0-100
- `disable_safety_checker`: boolean
- `go_fast`: boolean

## Troubleshooting

Common issues and solutions:

1. **API Key Issues**
   - Check key format (starts with `r8_`)
   - Verify account has credits
   - Check rate limits

2. **Generation Failures**
   - Check error logs
   - Verify input image format
   - Check parameter ranges

3. **JavaScript Errors**
   - Check browser console
   - Verify jQuery is loaded
   - Check AJAX URL is correct

## Completion Notification Format

When using the ntfy.sh notification system, use this format for clickable links:

```bash
curl -H "Title: Project Name - Phase Complete ✅" \
     -H "Tags: relevant,tags,based,on,work" \
     -H "Click: https://github.com/nerveband/wp-kontext-gen" \
     -d "📋 Completed:
- Brief description of what was done
- Key files/outputs created
- Important results

⏱️ $(date '+%H:%M')" \
     https://ntfy.sh/cc-bfl
```

The `Click` header creates a clickable action button in supported clients.

## Future Enhancements

Potential improvements:
- Batch processing
- Preset prompt templates
- Shortcode support
- Gutenberg block
- Multi-language support
- Advanced queue management