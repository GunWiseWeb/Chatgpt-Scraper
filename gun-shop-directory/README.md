# Gun Shop Directory WordPress Plugin

A professional WordPress plugin for creating a business directory for gun shops (both online e-commerce and brick-and-mortar stores) with customer ratings and reviews, similar to Yelp.

## Features

### Business Listings
- Custom post type for gun shop listings
- Support for both brick-and-mortar and e-commerce stores
- **Frontend listing submission** - logged-in users can submit listings
- **Pending approval system** - all user submissions require admin approval
- Detailed business information (address, contact details, hours)
- Featured images and photo galleries
- Categories and location taxonomies
- Google Maps integration

### Rating & Review System
- 5-star rating system
- Written customer reviews
- Review moderation and approval workflow
- Review statistics and distribution charts
- User-friendly review submission forms
- Anonymous reviews (optional)

### Search & Filter
- Advanced search functionality
- Filter by location, category, and business type
- Sort by rating, date, or name
- Professional search form with shortcode support

### Admin Features
- Comprehensive settings page
- Review management interface
- Business listing meta boxes
- Rating statistics dashboard
- Bulk review actions
- Customizable options

### Frontend Display
- **Unified directory shortcode** with 5 different layout options
- **Multiple layout styles**:
  - Grid layouts: Large, Compact, Minimal
  - List layouts: Simple, Detailed
- **Premium, modern design** with gradients and animations
- **Fancy styling** - professional color schemes and visual effects
- **Mobile-responsive** - all layouts optimized for mobile devices
- Custom templates (archive and single views)
- Interactive hover effects and smooth transitions
- Star rating visualizations
- Business hours display
- Social media integration
- Google Maps location display

## Installation

1. Upload the `gun-shop-directory` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Gun Shops → Settings to configure the plugin
4. (Optional) Add your Google Maps API key for map functionality

## Usage

### Adding a New Listing

1. Navigate to **Gun Shops → Add New** in the WordPress admin
2. Enter the business name and description
3. Fill in the business information:
   - Business Type (Brick & Mortar, E-commerce, or Both)
   - Address and location details
   - Contact information
   - Business hours
   - Social media links
4. Assign categories and locations
5. Add a featured image
6. Publish the listing

### Managing Reviews

1. Go to **Gun Shops → Reviews** in the admin menu
2. View pending, approved, or all reviews
3. Approve or delete reviews as needed
4. Reviews appear automatically on listing pages

### Settings

Navigate to **Gun Shops → Settings** to configure:
- **Require Review Approval**: Reviews must be approved before appearing
- **Allow Anonymous Reviews**: Let non-logged-in users submit reviews
- **Allow User Listing Submissions**: Let logged-in users submit listings (pending approval)
- **Listings Per Page**: Number of listings to display
- **Enable Map Display**: Show/hide Google Maps
- **Google Maps API Key**: Required for map functionality

### Shortcodes

#### Unified Directory (Recommended)
```
[gsd_directory layout="grid-large" show_search="true" show_submit="true" limit="12"]
```

**The all-in-one shortcode** that combines search, listings, and submission functionality with multiple layout options.

**Parameters:**
- `layout`: Choose from 5 layout options (default: grid-large)
  - `grid-large`: Large cards with featured images (default)
  - `grid-compact`: Smaller cards, more per row
  - `grid-minimal`: Very compact grid layout
  - `list-simple`: Horizontal minimal layout
  - `list-detailed`: Horizontal with thumbnails and details
- `show_search`: Show search form (default: true)
- `show_submit`: Show "Add Your Listing" button (default: true)
- `limit`: Number of listings to show (default: 12)
- `category`: Filter by category slug
- `location`: Filter by location slug
- `business_type`: Filter by type (brick_mortar, ecommerce, both)
- `orderby`: Sort by date, title, or rating
- `order`: ASC or DESC

**Examples:**
```
# Compact grid with search
[gsd_directory layout="grid-compact" limit="20"]

# Detailed list view without search form
[gsd_directory layout="list-detailed" show_search="false"]

# Minimal grid for mobile-friendly display
[gsd_directory layout="grid-minimal" category="firearms"]

# Simple list for sidebar widget
[gsd_directory layout="list-simple" limit="5" show_submit="false"]
```

#### Display Listings
```
[gsd_listings limit="12" category="firearms" location="texas" orderby="date" order="DESC"]
```

**Parameters:**
- `limit`: Number of listings to show (default: 12)
- `category`: Filter by category slug
- `location`: Filter by location slug
- `orderby`: Sort by date, title, or rating
- `order`: ASC or DESC

#### Search Form
```
[gsd_search]
```

Displays a comprehensive search form with filters for location, category, and business type.

#### Submit Listing Form
```
[gsd_submit_listing]
```

Displays a frontend form that allows logged-in users to submit their gun shop listings. Submissions are set to "pending" status and require admin approval before appearing on the site.

## File Structure

```
gun-shop-directory/
├── gun-shop-directory.php          # Main plugin file
├── README.md                         # This file
├── includes/                         # Core functionality
│   ├── class-gsd-core.php           # Main plugin class
│   ├── class-gsd-activator.php      # Activation handler
│   ├── class-gsd-deactivator.php    # Deactivation handler
│   ├── class-gsd-post-types.php     # Custom post types
│   ├── class-gsd-reviews.php        # Review functionality
│   ├── class-gsd-admin.php          # Admin interface
│   └── class-gsd-public.php         # Frontend display
├── admin/                            # Admin assets
│   ├── css/
│   │   └── gsd-admin.css
│   ├── js/
│   │   └── gsd-admin.js
│   └── partials/
├── public/                           # Public assets
│   ├── css/
│   │   └── gsd-public.css
│   ├── js/
│   │   └── gsd-public.js
│   └── templates/
│       ├── archive-gsd_listing.php  # Archive template
│       └── single-gsd_listing.php   # Single listing template
└── assets/
```

## Database Tables

The plugin creates two custom tables:

### wp_gsd_reviews
Stores review data including ratings, titles, content, and status.

### wp_gsd_review_meta
Stores additional review metadata.

## Custom Post Types

### gsd_listing
The main post type for gun shop listings.

**Taxonomies:**
- `gsd_category`: Categories (firearms, ammunition, accessories, etc.)
- `gsd_location`: Locations (states, cities, regions)

## Hooks & Filters

The plugin is extensible and provides various hooks for developers:

### Actions
- `gsd_before_listing_content`
- `gsd_after_listing_content`
- `gsd_before_review_form`
- `gsd_after_review_form`

### Filters
- `gsd_listing_query_args`
- `gsd_review_form_fields`
- `gsd_rating_html`

## Customization

### Template Override

You can override plugin templates by copying them to your theme:

1. Create a `gun-shop-directory` folder in your theme
2. Copy templates from `public/templates/` to your theme folder
3. Modify as needed

### Custom Styling

Add your custom CSS to override default styles:

```css
/* Override primary color */
:root {
    --gsd-primary: #your-color;
    --gsd-secondary: #your-color;
}
```

## Requirements

- WordPress 5.0 or higher
- PHP 7.2 or higher
- MySQL 5.6 or higher

## Optional Requirements

- Google Maps API key (for map functionality)

## Support

For bug reports and feature requests, please visit:
https://github.com/GunWiseWeb/gun-shop-directory/issues

## Changelog

### Version 1.1.0
- **Unified directory shortcode** `[gsd_directory]` - combines search, listings, and submission in one
- **5 layout options** - grid-large, grid-compact, grid-minimal, list-simple, list-detailed
- **Mobile-responsive layouts** - all layouts optimized for mobile devices
- **Premium styling** with gradients, animations, and modern design
- **Frontend listing submission** - users can submit listings from the frontend
- **Pending approval system** - all user submissions require admin approval
- New `[gsd_submit_listing]` shortcode for submission forms
- Enhanced CSS with interactive hover effects and transitions
- Improved button designs with gradient backgrounds
- Better form styling with modern inputs
- New admin setting to enable/disable user submissions
- Updated color scheme with professional blues and purples
- JavaScript toggle for submission form display

### Version 1.0.0
- Initial release
- Custom post type for gun shop listings
- Rating and review system
- Admin interface for managing listings and reviews
- Search and filter functionality
- Professional frontend design
- Google Maps integration
- Shortcode support

## License

GPL-2.0+

## Credits

Developed by GunWise
