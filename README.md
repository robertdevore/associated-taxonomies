# Associated Taxonomies
Associated Taxonomies is a free WordPress® plugin designed to enhance your taxonomy management by allowing you to associate terms within the same taxonomy. 

It simplifies term relationships, offering an intuitive way to manage and display associated terms for categories, tags, and custom taxonomies.

## Features

- **Associate Terms:** Add, edit, and save associated terms for any public taxonomy.
- **Dynamic Hooks:** Automatically integrates with all public taxonomies.
- **Shortcode Support:** Display associated terms or posts based on term relationships using simple shortcodes.
- **Custom Admin UI:** Enriches taxonomy term admin pages with a Select2-powered multi-select dropdown.
- **Frontend Styling:** Ensures associated terms are displayed elegantly on the frontend.

## Installation

1. Download the plugin from the [GitHub repository](https://github.com/robertdevore/associated-taxonomies/).
2. Upload the plugin folder to the `/wp-content/plugins/` directory.
3. Activate the plugin through the **Plugins** menu in WordPress.
4. Enjoy enhanced taxonomy management!

## Usage

### Associating Terms in the Admin Panel

1. Navigate to any taxonomy (e.g., Categories or Tags) in the WordPress admin.
2. On the **Add New Term** page, use the "Associated Terms" dropdown to select terms to associate.
3. On the **Edit Term** page, manage associations with the Select2-powered dropdown.
4. Save the term to update its associated terms.

### Displaying Associated Terms

Use the `[related_terms]` shortcode to display associated terms for a specific taxonomy term.

#### Example:
```
[related_terms id="123" taxonomy="category"]
```

- `id`: The term ID.
- `taxonomy`: The taxonomy name (e.g., `category`, `post_tag`, etc.).

### Displaying Posts by Related Terms

Use the `[posts_by_related_terms]` shortcode to display posts that belong to a parent term and at least one associated child term.

#### Example:
```
[posts_by_related_terms parent="12" child="34,56" taxonomy="category"]
```

- `parent`: The parent term ID.
- `child`: Comma-separated IDs of child terms.
- `taxonomy`: The taxonomy name.

## Hooks and Filters

### Filters

- `associated_taxonomies_tax_list`: Modify the list of taxonomies where associations are enabled.

#### Example:
```
add_filter( 'associated_taxonomies_tax_list', function( $taxonomies ) {
    return array_merge( $taxonomies, [ 'custom_taxonomy' ] );
});
```

### Actions

- Hooks for dynamically adding fields to all public taxonomies:
    - `{$taxonomy}_add_form_fields`
    - `{$taxonomy}_edit_form_fields`
    - `created_{$taxonomy}`
    - `edited_{$taxonomy}`

## Enqueued Assets

- **Admin Scripts:** Select2.js for taxonomy term admin pages.
- **Frontend Styles:** Basic CSS for associated terms presentation.

## Development Notes

### Plugin Update Checker

This plugin uses the [YahnisElsts/PluginUpdateChecker](https://github.com/YahnisElsts/plugin-update-checker) library to check for updates hosted on GitHub. Updates are pulled from the `main` branch of the repository.

## Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher

## Contributing

Your contributions are welcomed and appreciated! Feel free to:

- Submit issues or feature requests on the [GitHub repository](https://github.com/robertdevore/associated-taxonomies/).
- Fork the repository, make your changes, and submit a pull request.

## License

This plugin is licensed under the [GNU General Public License v2.0](http://www.gnu.org/licenses/gpl-2.0.txt).