# BuddyBoss Location Finder - User Guide

## Overview

BuddyBoss Location Finder is a powerful plugin that allows your community members to set their locations and search for other members based on geographic proximity. This guide will help you set up and use the plugin effectively.

## Installation

1. Upload the `bb-location-finder` folder to your `/wp-content/plugins/` directory
2. Network Activate the plugin through the 'Plugins' menu in WordPress Network Admin
3. Enter your Google Maps API key in the plugin settings

## Setting Up Your Google Maps API Key

1. Go to the [Google Cloud Console](https://console.cloud.google.com/)
2. Create a new project (or use an existing one)
3. Navigate to "APIs & Services" > "Library"
4. Enable the following APIs:
   - Maps JavaScript API
   - Places API
   - Geocoding API
5. Go to "APIs & Services" > "Credentials"
6. Click "Create Credentials" > "API Key"
7. Set up a billing account (required by Google Maps Platform)
8. Add your API key to the plugin settings:
   - Network Admin > Settings > Location Finder (for multisite)
   - Settings > Location Finder (for single site)

## Using Shortcodes

The plugin provides two main shortcodes:

### 1. Location Setter Shortcode

```
[bb_location_setter]
```

This shortcode displays a form for users to set their location. Place it on a page where users can update their profile information.

**Attributes:**
- `button_text`: Customize the button text (default: "Update Location")
- `redirect`: URL to redirect to after updating (default: stays on same page)

**Example:**
```
[bb_location_setter button_text="Save My Location" redirect="https://yoursite.com/members"]
```

### 2. Location Search Shortcode

```
[bb_location_search]
```

This shortcode displays a search form that allows users to find members near a specific location. Place it on a page where users can search for other members.

**Attributes:**
- `radius_options`: Comma-separated list of radius options (default: "5,10,25,50,100")
- `unit`: Distance unit, either "km" or "mi" (default: "km")
- `show_map`: Whether to show a map with search results, "yes" or "no" (default: "yes")
- `map_height`: Height of the map (default: "400px")

**Example:**
```
[bb_location_search radius_options="10,25,50,100,200" unit="mi" show_map="yes" map_height="500px"]
```

## Implementation Examples

### Create a "Find Members Near Me" Page

1. Create a new page in WordPress
2. Add a title like "Find Members Near Me"
3. Add the search shortcode to the content:
   ```
   [bb_location_search radius_options="5,10,25,50,100,250" unit="mi" show_map="yes"]
   ```
4. Publish the page and add it to your main navigation

### Add Location Settings to User Profiles

1. Create a page called "Update Location" or add to an existing profile editing page
2. Add the setter shortcode:
   ```
   [bb_location_setter button_text="Save My Location" redirect=""]
   ```
3. Publish or update the page

## User Privacy

The plugin includes a privacy option that lets users decide whether they want to be discoverable in location searches. When users set their location, they have the option to:

- Allow others to find them in location searches (checked by default)
- Opt-out by unchecking the privacy box

## Troubleshooting

### Permalink Issues

If you encounter errors accessing the plugin settings or if shortcodes don't work properly, try these solutions:

1. **Permalink Structure Issues:**
   - Go to Settings > Permalinks
   - Select any option other than "Plain" (Post name is recommended)
   - Save changes
   - Flush rewrite rules by saving the permalinks again

2. **Plugin Access Denied Error:**
   - If you get "Sorry, you are not allowed to access this page" when clicking Settings:
   - Try logging out and logging back in as a Super Admin
   - Ensure the user has the correct capabilities (`manage_network_options` for network admin, `manage_options` for site admin)
   - Check for any security plugins that might be blocking access

3. **Shortcodes Not Working:**
   - Make sure the Google Maps API key is correctly entered in settings
   - Check for JavaScript errors in the browser console (F12)
   - Verify that Google Maps JavaScript, Places, and Geocoding APIs are enabled in your Google Cloud Console
   - Try adding the shortcode to a basic page without other complex elements

### Google Maps Not Loading

1. **API Key Issues:**
   - Verify your API key is correct
   - Check if billing is properly set up in Google Cloud Console
   - Ensure all required APIs are enabled

2. **Console Errors:**
   - Check browser console (F12) for specific error messages
   - Common errors include:
     - "Google Maps API error: MissingKeyMapError" - API key is missing or invalid
     - "Google Maps API error: RefererNotAllowedMapError" - Domain restrictions on API key
     - "Google Maps API error: BillingNotEnabledMapError" - Billing not set up

3. **API Restrictions:**
   - If you've restricted your API key to specific domains, ensure your site's domain is correctly listed
   - Try temporarily removing restrictions to test if that's the issue

### Geocoding Issues

If user locations aren't being converted to coordinates properly:

1. Check if the Geocoding API is enabled in your Google Cloud Console
2. Test with well-known addresses to see if geocoding works
3. Verify your API key has the necessary permissions
4. Check your Google Cloud billing status

## Advanced Configuration

### Customizing CSS

You can customize the appearance of the location forms and search results by adding custom CSS to your theme. The plugin includes classes you can target:

- `.bb-location-setter-form` - The location setter form container
- `.bb-location-search-container` - The search form container
- `.location-results` - The results container
- `.user-item` - Individual user results
- `.user-avatar` - User avatar in results
- `.user-info` - User information in results
- `#bb-location-map` - The Google Map

Example of custom CSS you might add to your theme:

```css
/* Make the map larger on desktop */
@media (min-width: 768px) {
    #bb-location-map {
        height: 600px !important;
    }
}

/* Style the user result cards */
.user-item {
    transition: all 0.3s ease;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.user-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
}
```

### For Developers

If you want to extend the plugin:

1. All functionality is organized in classes inside the `includes/` directory
2. Use WordPress actions and filters to modify plugin behavior
3. Main classes to know about:
   - `BB_Location_Profile_Fields` - Handles profile fields integration
   - `BB_Location_Search` - Contains the search functionality
   - `BB_Location_Geocoding` - Manages the geocoding process
   - `BB_Location_Map` - Handles map display and functionality

## Multisite Considerations

For WordPress multisite installations:

1. **Network Activation:**
   - The plugin should be network activated
   - The Google Maps API key is set at the network level
   - Settings are accessed via Network Admin > Settings > Location Finder

2. **Site-Specific Settings:**
   - Individual sites can also have their own API key
   - The plugin will first check for a network API key, then fall back to the site key

3. **Permissions:**
   - Super Admins can access network settings
   - Site Admins can access site-specific settings

## Common Use Cases

### Community Directory

Create a member directory page with the search shortcode to allow members to find others based on location.

```
<h1>Find Members Near You</h1>
<p>Enter your location and radius to find community members in your area.</p>
[bb_location_search radius_options="5,10,25,50,100,250" unit="mi" show_map="yes" map_height="500px"]
```

### Profile Update Flow

Add the location setter shortcode to your registration or profile completion flow:

```
<h2>Where are you located?</h2>
<p>Let other members know where you're based (you can control your privacy in the options below).</p>
[bb_location_setter button_text="Continue" redirect="https://yoursite.com/welcome"]
```

### Event Planning

Use the location search on event pages to help users find potential attendees near the event location:

```
<h2>Find Members Near This Event</h2>
<p>Connect with other members who might be interested in attending.</p>
[bb_location_search radius_options="5,10,25" unit="km" show_map="yes"]
```

## Support

If you encounter issues not covered in this guide:

1. Check your server error logs for PHP errors
2. Verify plugin compatibility with your version of WordPress and BuddyBoss
3. Ensure all JavaScript dependencies are loading correctly
4. Contact the plugin developer for additional support

## License

This plugin is licensed under GPL-2.0+. You are free to modify and extend it as needed for your site.

---

This guide should help you set up and use the BuddyBoss Location Finder plugin effectively. If you have additional questions or need further clarification, please reach out to the plugin developer.