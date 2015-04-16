=== Post-a-pic ===
Contributors: Starnuto di topo
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=8LFUW8AMENU4Y
Tags: auto post, post, image upload
Requires at least: 4.1.1
Tested up to: 4.1.1
Stable tag: 1.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Let you create single/bulk post after uploading any media from wordpress media gallery.

== Description ==

Provides the facility to automatically create a post when an image is uploaded to the Wordpress media gallery.
The uploaded image can become the post's featured image and some information about the image (such as EXIF data), or the image itself, can automatically be set in post's content.
If the image provides its GPS coordinates, a Google map displaying its location can be added as well.
The image's XMP keywords can be set as the post's tags and a set of categories can be associated to it too.
Useful for photo blog or where there is a large number of image posting in a wordpress driven site.
Originally inspired by Auto Post After Image Upload, https://wordpress.org/plugins/auto-post-after-image-upload/
Some code sketches to handle XMP data have been excerpted from Jean-Sebastien Morisset,  http://surniaulula.com/
More credits are reported inside the code.

Project BitBucket URL: [http://bitbucket.org/starnutoditopo/post-a-pic/](http://bitbucket.org/starnutoditopo/post-a-pic/)

Get the source code with Mercurial from: [ssh://hg@bitbucket.org/starnutoditopo/post-a-pic](ssh://hg@bitbucket.org/starnutoditopo/post-a-pic)


== Installation ==

To **Install** this plugin there is no any complexity. It's very simple like other plugin. Just follow the procedure described below.

1. Download the plugin from Wordpress Plugin repository. After downloading the zip file extract it.
2. Upload `Post-a-pic` plugin directory to the `/wp-content/plugins/` directory.
3. Activate the plugin through the 'Plugins' menu in WordPress
4. In Wordpress dashboards, select Settings, Post-a-pic to access the plugin options. Select the options you want to enable and click "Save changes".
5. Upload an image from 'Add Media' menu in Wordpress. Then go to 'All Post' menu and see after uploading that image there will be a new post related to the image you uploaded.

== Frequently Asked Questions ==

= This is useful for creating bulk post? =

Yes, it is. The main intention to create this plugin to use where user need bulk image posting and creating post.

== Screenshots ==

There is no Screenshot yet!

== Changelog ==

= 1.0 =
* Initial release

= 1.1 =
* Bug fixes in case of missing GPS coordinates
* Showing properties in a table
* Added capability to display Google map

= 1.2 =
* Bug fixes in case of missing image meta data
* Removed some irrelevant information
* Added capability to include XMP keywords as tags
* Added capability to set categories

== Upgrade Notice ==

= 1.1 =
* Bug fixes in case of missing GPS coordinates
* Enhancements

= 1.2 =
* Enhancements
* New functionalities (set XMP keywords as tags, set categories)