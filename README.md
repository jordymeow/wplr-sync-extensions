# WP/LR Sync Extensions
Extensions are the glue between WP/LR Sync and gallery plugins or the themes. 

## Internal Extensions
They are already included with WP/LR Sync. You will also find them here, for debugging and fixing purposes.

* Post Types (developed by Jordy Meow)
* Real Media Library (developed by Matthias Gunter)

## External Extensions
Each of those are basically a WordPress plugin. The folder can be added into the /plugins folder in WordPress. They are here so that you can get them and contribute as I am not actively working on them.

* Photo Gallery
* NextGen
* Media Folder
* CosmoThemes

## Settings Table for the Post Types Extension
This extension should work with most themes, but as it is difficult to find what settings are exactly used by a theme, let's keep them here.

| Theme                                                                                                                                      | Post Type        | Mode                                | Post Meta                                | Taxonomy             |
|--------------------------------------------------------------------------------------------------------------------------------------------|------------------|-------------------------------------|------------------------------------------|----------------------|
| [Photography](https://themeforest.net/item/photography-responsive-photography-theme/13304399?ref=TigrouMeow) by Theme Goods                | gallery          | Array in Post Meta                  | wpsimplegallery_gallery                  | gallerycat           |
| [BlueBird](https://themeforest.net/item/bluebird-design-for-professional-photographers/13090733?ref=TigrouMeow) by Colormelon              | portfolio        | Array (ID -> FullSize) in Post Meta | phort_gallery                            | phort_post_category  |
| [Tripod](https://themeforest.net/item/tripod-professional-wordpress-photography-theme/4438731?ref=TigrouMeow) by Cosmothemes               | gallery          | Array in Post Meta                  | _post_image_gallery                      | gallery-category     |
| [Kinetika](https://themeforest.net/item/kinetika-fullscreen-photography-theme/12162415?ref=TigrouMeow) by Imaginem                         | mtheme_portfolio | Array in Post Meta (Imploded)       | _mtheme_image_ids                        | types                |
| [Jupiter](https://themeforest.net/item/jupiter-multipurpose-responsive-theme/5177775?ref=TigrouMeow) by Artbees                            | photo_album      | Array in Post Meta (Imploded)       | _gallery_images                          | photo_album_category |
| [TheGem](https://themeforest.net/item/thegem-creative-multipurpose-highperformance-wordpress-theme/16061685?ref=TigrouMeow) by CodexThemes | thegem_gallery   | Array in Post Meta                  | thegem_gallery_images                    |                      |
| [Oshine](https://themeforest.net/item/oshine-creative-multipurpose-wordpress-theme/9545812?ref=TigrouMeow) by Brand Exponents              | portfolio        | Array in Post Meta                  | be_themes_single_portfolio_slider_images | portfolio_categories |
| [Uncode](https://themeforest.net/item/uncode-creative-multiuse-wordpress-theme/13373220?ref=TigrouMeow) by Undsgn                          | uncode_gallery   | Array in Post Meta (Imploded)       | _uncode_featured_media                   |                      |
