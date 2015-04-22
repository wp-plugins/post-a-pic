<?php

/*
    Plugin Name: Post-a-pic
    Plugin URI:
    Description: Provides the facility to automatically create a post when an image is uploaded to the Wordpress media gallery.
    Author: Starnuto di topo
    Version: 1.3
    Author URI: 
*/

class PostAPic
{
    private $tableLinesCount;
    function __construct (){        
        $this->tableLinesCount = 0;
    }

    function fractionToNumber($stringValue){
         $values = explode('/', $stringValue);
         if(count($values) == 1){
             return $values[0];
         } else if(count($values) == 2){
            return $values[0] / $values[1];
         }
         return NULL;
    }

    function write_Table_Header(){
        return '<table style="border:1px solid #cccccc;"><caption>Image details</caption><tbody>';
    }

    function write_Table_Line($key, $value){
        $this->tableLinesCount ++;

        //return $key . ' ' . $value . "<br />";
        return '<tr>'
            . '<td>' . $key . '</td>'
            . '<td>' . $value . '</td>'
            . '</tr>';
    }

    function write_Table_Footer(){
        return '</tbody></table>';
    }

    /**
     * Returns an array of latitude and longitude from the Image file
     * @param image $file
     * @return multitype:number |boolean
     */
     //See: http://stackoverflow.com/a/19420991/1288109
    function read_gps_location($file){
        if (is_file($file)) {
            $info = @read_exif_data($file);

            if (
                (@array_key_exists('GPSLatitude', $info))
                && (@array_key_exists('GPSLongitude', $info))
                && (@array_key_exists('GPSLatitudeRef', $info))
                && (@array_key_exists('GPSLongitudeRef', $info))
                && in_array($info['GPSLatitudeRef'], array('E','W','N','S'))
                && in_array($info['GPSLongitudeRef'], array('E','W','N','S'))
                ) {

                $GPSLatitudeRef  = strtolower(trim($info['GPSLatitudeRef']));
                $GPSLongitudeRef = strtolower(trim($info['GPSLongitudeRef']));

                $lat_degrees_a = explode('/',$info['GPSLatitude'][0]);
                $lat_minutes_a = explode('/',$info['GPSLatitude'][1]);
                $lat_seconds_a = explode('/',$info['GPSLatitude'][2]);
                $lng_degrees_a = explode('/',$info['GPSLongitude'][0]);
                $lng_minutes_a = explode('/',$info['GPSLongitude'][1]);
                $lng_seconds_a = explode('/',$info['GPSLongitude'][2]);

                $lat_degrees = $lat_degrees_a[0] / $lat_degrees_a[1];
                $lat_minutes = $lat_minutes_a[0] / $lat_minutes_a[1];
                $lat_seconds = $lat_seconds_a[0] / $lat_seconds_a[1];
                $lng_degrees = $lng_degrees_a[0] / $lng_degrees_a[1];
                $lng_minutes = $lng_minutes_a[0] / $lng_minutes_a[1];
                $lng_seconds = $lng_seconds_a[0] / $lng_seconds_a[1];

                $lat = (float) $lat_degrees+((($lat_minutes*60)+($lat_seconds))/3600);
                $lng = (float) $lng_degrees+((($lng_minutes*60)+($lng_seconds))/3600);

                //If the latitude is South, make it negative. 
                //If the longitude is west, make it negative
                $GPSLatitudeRef  == 's' ? $lat *= -1 : '';
                $GPSLongitudeRef == 'w' ? $lng *= -1 : '';

                return array(
                    'lat' => $lat,
                    'lng' => $lng
                );
            }           
        }
        return false;
    }

    function getImageDate($attachId){
        $imagePath = get_attached_file($attachId, true);
        $exif_general = @read_exif_data($imagePath);
        if (@array_key_exists('DateTime', $exif_general)){
            $result = $exif_general['DateTime'];
        }
        else{
            $result = date("Y-m-d H:i:s");
        }
        return $result;
    }

    // Method inspired from Jean-Sebastien Morisset,  http://surniaulula.com/
	function get_xmp_raw( $filepath ) {
		//$max_size = 512000;	// maximum size read
		$chunk_size = 65536;	// read 64k at a time
		$start_tag = '<x:xmpmeta';
		$end_tag = '</x:xmpmeta>';
		$xmp_raw = null; 
		if ( $file_fh = fopen( $filepath, 'rb' ) ) {
			$chunk = '';
			$file_size = filesize( $filepath );
			//while ( ( $file_pos = ftell( $file_fh ) ) < $file_size  && $file_pos < $max_size ) {
            while ( ( $file_pos = ftell( $file_fh ) ) < $file_size ) {
				$chunk .= fread( $file_fh, $chunk_size );
				if ( ( $end_pos = strpos( $chunk, $end_tag ) ) !== false ) {
					if ( ( $start_pos = strpos( $chunk, $start_tag ) ) !== false ) {
						$xmp_raw = substr( $chunk, $start_pos, $end_pos - $start_pos + strlen( $end_tag ) );							
					}
					break;	// stop reading after finding the xmp data
				}
			}
			fclose( $file_fh );
		}
		return $xmp_raw;
	}

    // Method inspired from https://github.com/wp-plugins/adobe-xmp-for-wp/blob/master/adobe-xmp-for-wp.php
    function get_xmp_array( &$xmp_raw ) {
		$xmp_arr = array();
		foreach ( array(
			'Creator Email'	=> '<Iptc4xmpCore:CreatorContactInfo[^>]+?CiEmailWork="([^"]*)"',
			'Owner Name'	=> '<rdf:Description[^>]+?aux:OwnerName="([^"]*)"',
			'Creation Date'	=> '<rdf:Description[^>]+?xmp:CreateDate="([^"]*)"',
			'Modification Date'	=> '<rdf:Description[^>]+?xmp:ModifyDate="([^"]*)"',
			'Label'		=> '<rdf:Description[^>]+?xmp:Label="([^"]*)"',
			'Credit'	=> '<rdf:Description[^>]+?photoshop:Credit="([^"]*)"',
			'Source'	=> '<rdf:Description[^>]+?photoshop:Source="([^"]*)"',
			'Headline'	=> '<rdf:Description[^>]+?photoshop:Headline="([^"]*)"',
			'City'		=> '<rdf:Description[^>]+?photoshop:City="([^"]*)"',
			'State'		=> '<rdf:Description[^>]+?photoshop:State="([^"]*)"',
			'Country'	=> '<rdf:Description[^>]+?photoshop:Country="([^"]*)"',
			'Country Code'	=> '<rdf:Description[^>]+?Iptc4xmpCore:CountryCode="([^"]*)"',
			'Location'	=> '<rdf:Description[^>]+?Iptc4xmpCore:Location="([^"]*)"',
			'Title'		=> '<dc:title>\s*<rdf:Alt>\s*(.*?)\s*<\/rdf:Alt>\s*<\/dc:title>',
			'Description'	=> '<dc:description>\s*<rdf:Alt>\s*(.*?)\s*<\/rdf:Alt>\s*<\/dc:description>',
			'Creator'	=> '<dc:creator>\s*<rdf:Seq>\s*(.*?)\s*<\/rdf:Seq>\s*<\/dc:creator>',
			'Keywords'	=> '<dc:subject>\s*<rdf:Bag>\s*(.*?)\s*<\/rdf:Bag>\s*<\/dc:subject>',
			'Hierarchical Keywords'	=> '<lr:hierarchicalSubject>\s*<rdf:Bag>\s*(.*?)\s*<\/rdf:Bag>\s*<\/lr:hierarchicalSubject>'
		) as $key => $regex ) {
			// get a single text string
			$xmp_arr[$key] = preg_match( "/$regex/is", $xmp_raw, $match ) ? $match[1] : '';
			// if string contains a list, then re-assign the variable as an array with the list elements
			$xmp_arr[$key] = preg_match_all( "/<rdf:li[^>]*>([^>]*)<\/rdf:li>/is", $xmp_arr[$key], $match ) ? $match[1] : $xmp_arr[$key];
			// hierarchical keywords need to be split into a third dimension
			if ( ! empty( $xmp_arr[$key] ) && $key == 'Hierarchical Keywords' ) {
				foreach ( $xmp_arr[$key] as $li => $val ) $xmp_arr[$key][$li] = explode( '|', $val );
				unset ( $li, $val );
			}
		}
		return $xmp_arr;
	}

    function getXmpKeywords($imagePath){
        $keywords = array();

        $xmp_raw = $this-> get_xmp_raw( $imagePath );
        if ( ! empty( $xmp_raw ) ){
            $xmp = $this->get_xmp_array( $xmp_raw );
            if (@array_key_exists('Keywords', $xmp)){
                if(is_array($xmp['Keywords']) )
                {
                    $keywordsArray = $xmp['Keywords'];
                    $arrlength = count($keywordsArray);
                    for($x = 0; $x < $arrlength; $x++) {
                        $keyword = $keywordsArray[$x];
                        array_push($keywords, $keyword);
                    }
                }
            }            
        }
        return $keywords;
    }

    function makePostContent($attachId, $options){
        $result = "";

        $attachment = get_post($attachId);

        if($options['includeImageInPost']){
            $image = wp_get_attachment_image_src( $attachId, 'large');
            $imageUrl = $image[0];
            $image_tag = '<p><img src="'.$imageUrl.'" /></p>';

            $result = $result . $image_tag;
        }

        $tableContent = "";

        $imagePath = get_attached_file($attachId, true);

        $exif_general = @read_exif_data($imagePath);
        //$result = $result . "<hr />" . print_r($exif_general, 1) . "<hr />";

        if($options['addImageInfo']){
            // Image Description
            if (@array_key_exists('ImageDescription', $exif_general))
            {
                $description = trim($exif_general['ImageDescription']);
                if(!empty ($description)){
                    $tableContent = $tableContent . $this->write_Table_Line("Image description:", $description );
                }
            }

            // Date
            if (@array_key_exists('DateTime', $exif_general))
            {
                $tableContent = $tableContent . $this->write_Table_Line("Date taken:",  $exif_general['DateTime']);
            }
        }       

        if($options['addCameraInfo']){
            $exif_ifd0 = @read_exif_data($imagePath ,'IFD0' ,0);
            if ($exif_ifd0 !== false) {
                // maker and model unified
                //// Make
                //if (@array_key_exists('Make', $exif_ifd0))
                //{
                //    $tableContent = $tableContent . $this->write_Table_Line("Camera maker:",  $exif_ifd0['Make']);
                //}

                //// Model
                //if (@array_key_exists('Model', $exif_ifd0))
                //{
                //    $tableContent = $tableContent . $this->write_Table_Line("Camera model:",  $exif_ifd0['Model']);
                //}

                // Camera info
                {
                    $cameraInfo = array();

                    // Make
                    if (@array_key_exists('Make', $exif_ifd0))
                    {
                        array_push($cameraInfo,  $exif_ifd0['Make']);
                    }

                    // Model
                    if (@array_key_exists('Model', $exif_ifd0))
                    {                        
                        array_push($cameraInfo,  $exif_ifd0['Model']);
                    }
                    if(!empty($cameraInfo)){
                        $tableContent = $tableContent . $this->write_Table_Line("Camera:",  implode(' ', $cameraInfo));
                    }
                }
                
                //// Exposure time (already read somewhere else)
                //if (@array_key_exists('ExposureTime', $exif_ifd0))
                //{
                //    $tableContent = $tableContent . $this->write_Table_Line("Exposure time:",  $exif_ifd0['ExposureTime']);
                //}

                // Irrelevant
                //if (@array_key_exists('COMPUTED', $exif_ifd0))
                //{
                //    $computed = $exif_ifd0['COMPUTED'];
                //    // Aperture
                //    if (@array_key_exists('ApertureFNumber', $computed))
                //    {
                //        $tableContent = $tableContent . $this->write_Table_Line("Aperture:",  $computed['ApertureFNumber']);
                //    }

                //    // CCD width
                //    if (@array_key_exists('CCDWidth', $exif_ifd0))
                //    {
                //        $tableContent = $tableContent . $this->write_Table_Line("CCD width:",  $exif_ifd0['CCDWidth']);
                //    }
                //}
            }


            if (@array_key_exists('ExposureTime', $exif_general))
            {
                $tableContent = $tableContent . $this->write_Table_Line("Exposure time:", $this->fractionToNumber($exif_general['ExposureTime']) . ' s');
            }
            if (@array_key_exists('FNumber', $exif_general))
            {
                $tableContent = $tableContent . $this->write_Table_Line("Focal stop:", 'f/' . $this->fractionToNumber($exif_general['FNumber']));
            }

            // irrelevant
            //if (@array_key_exists('ExifVersion', $exif_general))
            //{
            //    $tableContent = $tableContent . $this->write_Table_Line("Exif version:",  $exif_general['ExifVersion']);
            //}
            // irrelevant
            //if (@array_key_exists('ShutterSpeedValue', $exif_general))
            //{
            //    $tableContent = $tableContent . $this->write_Table_Line("Shutter speed value:",  $this->fractionToNumber($exif_general['ShutterSpeedValue']) . ' s');
            //}
            // irrelevant
            // if (@array_key_exists('ApertureValue', $exif_general))
            // {
            //    $tableContent = $tableContent . $this->write_Table_Line("Aperture value:",  $exif_general['ApertureValue']);
            // }
            if (@array_key_exists('ExposureBiasValue', $exif_general))
            {
                $tableContent = $tableContent . $this->write_Table_Line("Exposure bias:", $this->fractionToNumber($exif_general['ExposureBiasValue']) . ' EV' );
            }

            // irrelevant
            //if (@array_key_exists('MaxApertureValue', $exif_general))
            //{
            //    $tableContent = $tableContent . $this->write_Table_Line("Max aperture value:",  $this->fractionToNumber($exif_general['MaxApertureValue']));
            //}

            // irrelevant
            //if (@array_key_exists('MeteringMode', $exif_general))
            //{
            //    $tableContent = $tableContent . $this->write_Table_Line("Metering mode:",  $exif_general['MeteringMode']);
            //}

            if (@array_key_exists('Flash', $exif_general))
            {
                $flashFired = 'no';
                if( ($exif_general['Flash'] & 1) != 0)
                {
                    $flashFired = 'yes';
                }
                $tableContent = $tableContent . $this->write_Table_Line("Flash:",  $flashFired);
            }
            if (@array_key_exists('FocalLength', $exif_general))
            {
                $tableContent = $tableContent . $this->write_Table_Line("Focal length:", $this->fractionToNumber($exif_general['FocalLength']) . ' mm' );
            }
            // irrelevant
            // if (@array_key_exists('DigitalZoomRatio', $exif_general))
            // {
            //    $tableContent = $tableContent . $this->write_Table_Line("Digital zoom ratio:",  $exif_general['DigitalZoomRatio']);
            // }

            // irrelevant
            //if (@array_key_exists('SceneCaptureType', $exif_general))
            //{
            //    // See: http://www.awaresystems.be/imaging/tiff/tifftags/privateifd/exif/scenecapturetype.html
            //    $SceneCaptureType = "";
            //    $type = $exif_general['SceneCaptureType'];
            //    if($type == 0){
            //        $SceneCaptureType = "Standard";
            //    } else if($type == 1){
            //        $SceneCaptureType =  "Landscape";
            //    } else if($type == 1){
            //        $SceneCaptureType =  "Portrait";
            //    } else if($type == 1){
            //        $SceneCaptureType =  "Night scene";
            //    }            
            //    $tableContent = $tableContent . $this->write_Table_Line("Scene capture type:",  $SceneCaptureType);
            //}        

            $exif_exif = @read_exif_data($imagePath ,'EXIF' ,0);
            if ($exif_exif !== false)
            {
                // ISO
                if (@array_key_exists('ISOSpeedRatings',$exif_exif))
                {
                    $tableContent = $tableContent . $this->write_Table_Line("ISO speed:",  $exif_exif['ISOSpeedRatings']);
                }
            }
        }

        $gpsLocation = $this->read_gps_location($imagePath);            
        if($options['addGpsLocation']){
            if($gpsLocation !== false){
                $tableContent = $tableContent . $this->write_Table_Line("GPS lat:",  $gpsLocation['lat']);
                $tableContent = $tableContent . $this->write_Table_Line("GPS lon:",  $gpsLocation['lng']);
            }            
        }

        if($this->tableLinesCount > 0)
        {
            $result = $result . $this->write_Table_Header();
            $result = $result . $tableContent;
            $result = $result . $this->write_Table_Footer();
        }

        if($options['includeGoogleMap']){
            if($gpsLocation !== false){
                // See: http://www.w3schools.com/googleapi/google_maps_basic.asp
                $result = $result .'<script>
                 function postAPic_InitializeGoogleMaps() {
                     var mapProp = {
                         center: new google.maps.LatLng('.$gpsLocation['lat'] . ',' . $gpsLocation['lng'] . '),
                         zoom: 12,
                         mapTypeId: google.maps.MapTypeId.ROADMAP
                     };
                     var map = new google.maps.Map(document.getElementById("postAPic_googleMapDiv"),mapProp);
                     var marker = new google.maps.Marker({
                         position: new google.maps.LatLng('.$gpsLocation['lat'] . ',' . $gpsLocation['lng'] . '),
                     });
                     marker.setMap(map);
                 }
                 function postAPic_LoadGoogleMapsScript() {
                    var alreadyRegistered = false;
                    if (typeof google === "object"){
                        if (typeof google.maps === "object"){
                            alreadyRegistered = true;
                        }
                    }
                    if (alreadyRegistered){
                        postAPic_InitializeGoogleMaps();
                    } else {
                        var script = document.createElement("script");
                        script.src = "http://maps.googleapis.com/maps/api/js?callback=postAPic_InitializeGoogleMaps";
                        document.body.appendChild(script);
                    }
                 }
                 window.onload = postAPic_LoadGoogleMapsScript;
                 </script>
                 <div id="postAPic_googleMapDiv" style="width:500px;height:380px;"></div>';
            }
        }
        return $result;    
    }

    function getCurrentOptions(){
        $defaultOptions = array(
            'featuredImage'         => true,
            'addGpsLocation'        => true,
            'addCameraInfo'         => true,
            'includeImageInPost'    => true,
            'addImageInfo'          => true,
            'setPostDate'           => true,
            'includeGoogleMap'      => true,
            'setXmpKeywordsAsTags'  => true,
            'setCategory'           => "",
            'setPostFormat'         => ""
        );
        $options = get_option('post_a_pic_plugin_options_general', $defaultOptions);
        $options = array_merge($defaultOptions, $options);
        return $options;
    }

    function post_a_pic($attachId){
        
        $options = $this->getCurrentOptions();
        
        $attachment = get_post($attachId);
        $postContent = $this->makePostContent($attachId, $options);

        $date = $attachment->post_date; //date( "Y-m-d H:i:s");        
        if($options['setPostDate']){
            $date = $this->getImageDate($attachId);
        }
        
        $postData = array(
            'post_title' => $attachment->post_title,
            'post_type' => 'post',
            'post_content' => $postContent,
            'post_category' => array('0'),
            'post_status' => 'publish',
            'post_date' => $date
        );

        $post_id = wp_insert_post($postData);
    
        if($options['featuredImage']){
            // attach media to post
            wp_update_post(array(
                'ID' => $attachId,
                'post_parent' => $post_id,
            ));

            set_post_thumbnail($post_id, $attachId);
        }

        if($options['setXmpKeywordsAsTags']){
            $imagePath = get_attached_file($attachId, true);
            $xmpKeywords = $this->getXmpKeywords($imagePath);
            wp_set_post_tags( $post_id, $xmpKeywords, true );
        }

        $categoryOptions = trim($options['setCategory']);
        if(! empty($categoryOptions)){
            $categories = explode(',', $categoryOptions);
            $trimmedCategories = array_map('trim', $categories);
            wp_set_object_terms( $post_id, $trimmedCategories, 'category', false);
        }

        $postFormat = trim($options['setPostFormat']);
        //if(! empty($postFormat)){
            set_post_format($post_id, $postFormat );
        //}        
        
        return $attachId;
    }




    // See: http://ottopress.com/2009/wordpress-settings-api-tutorial/


    function post_a_pic_plugin_options_page() {
        echo "<div>";
        echo "<h2>Post-a-pic settings</h2>";
        echo "Options related to the 'Post-a-pic' Plugin.";
        echo '<form action="options.php" method="post">';
        echo settings_fields('post_a_pic_plugin_options_group'); 
        echo do_settings_sections('pluginPage'); 
        echo '<input name="Submit" type="submit" value="Save Changes" />';
        echo "</form></div>";

        //Add a "Donate" Paypal button
        echo "<hr />";
        echo "<div>";
        echo "<p>Did you find this plugin useful? Please, consider donating!</p>";
        echo '  <form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_top">';
        echo '      <input type="hidden" name="cmd" value="_s-xclick">';
        echo '    <input type="hidden" name="hosted_button_id" value="8LFUW8AMENU4Y">';
        echo '      <input type="image" src="https://www.paypalobjects.com/it_IT/IT/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="PayPal - Il metodo rapido, affidabile e innovativo per pagare e farsi pagare.">';
        echo '    <img alt="" border="0" src="https://www.paypalobjects.com/it_IT/i/scr/pixel.gif" width="1" height="1">';
        echo '  </form>';
        echo "</div>";
    }

    function admin_add_page() {
        add_options_page('Post-a-pic', 'Post-a-pic', 'manage_options', 'post_a_pic_plugin_settings', array( $this,'post_a_pic_plugin_options_page'));
    }

    function admin_init(){
        register_setting( 'post_a_pic_plugin_options_group', 'post_a_pic_plugin_options_general', array( $this, 'post_a_pic_plugin_options_general_validate') );
        add_settings_section('generalSectionId', 'General', array( $this,'general_section_text'), 'pluginPage');
        add_settings_field('setFeaturedImage', 'Set featured image', array( $this,'printSetFeaturedImageString'), 'pluginPage', 'generalSectionId');
        add_settings_field('addGpsLocation', 'Add GPS location', array( $this,'printAddGpsLocationString'), 'pluginPage', 'generalSectionId');
        add_settings_field('addCameraInfo', 'Add camera information', array( $this,'printAddCameraInfoString'), 'pluginPage', 'generalSectionId');
        add_settings_field('includeImageInPost', 'Include image in post', array( $this,'printIncludeImageInPostString'), 'pluginPage', 'generalSectionId');
        add_settings_field('addImageInfo', 'Add image information', array( $this,'printAddImageInfoString'), 'pluginPage', 'generalSectionId');
        add_settings_field('setPostDate', 'Set post time according to image time', array( $this,'printSetPostDateString'), 'pluginPage', 'generalSectionId');
        add_settings_field('includeGoogleMap', 'Include Google map', array( $this,'printIncludeGoogleMapString'), 'pluginPage', 'generalSectionId');
        add_settings_field('setXmpKeywordsAsTags', 'Set XMP keywords as tags', array( $this,'printSetXmpKeywordsAsTagsString'), 'pluginPage', 'generalSectionId');
        add_settings_field('setCategory', 'Categories', array( $this,'printSetCategoryString'), 'pluginPage', 'generalSectionId');
        add_settings_field('setPostFormat', 'Post format', array( $this,'printSetPostFormatString'), 'pluginPage', 'generalSectionId');        
    }

    function general_section_text() {
        echo "<p>Select the image's features you want to include in the post.</p>";
    }

    function printSetFeaturedImageString() {
        $options = $this->getCurrentOptions();

        echo "<input id='setFeaturedImage' name='post_a_pic_plugin_options_general[featuredImage]' type='checkbox' value='1' ";
        checked( $options['featuredImage'] );
        echo " />";
    }

    function printAddGpsLocationString() {
        $options = $this->getCurrentOptions();

        echo "<input id='addGpsLocation' name='post_a_pic_plugin_options_general[addGpsLocation]' type='checkbox' value='1' ";
        checked( $options['addGpsLocation'] );
        echo " />";
    }

    function printAddCameraInfoString() {
        $options = $this->getCurrentOptions();

        echo "<input id='addCameraInfo' name='post_a_pic_plugin_options_general[addCameraInfo]' type='checkbox' value='1' ";
        checked( $options['addCameraInfo'] );
        echo " />";
    }

    function printIncludeImageInPostString() {
        $options = $this->getCurrentOptions();

        echo "<input id='includeImageInPost' name='post_a_pic_plugin_options_general[includeImageInPost]' type='checkbox' value='1' ";
        checked( $options['includeImageInPost'] );
        echo " />";
    }

    function printAddImageInfoString() {
        $options = $this->getCurrentOptions();

        echo "<input id='addImageInfo' name='post_a_pic_plugin_options_general[addImageInfo]' type='checkbox' value='1' ";
        checked( $options['addImageInfo'] );
        echo " />";
    }

    function printSetPostDateString() {
        $options = $this->getCurrentOptions();

        echo "<input id='setPostDate' name='post_a_pic_plugin_options_general[setPostDate]' type='checkbox' value='1' ";
        checked( $options['setPostDate'] );
        echo " />";
    }

    function printIncludeGoogleMapString() {
        $options = $this->getCurrentOptions();

        echo "<input id='includeGoogleMap' name='post_a_pic_plugin_options_general[includeGoogleMap]' type='checkbox' value='1' ";
        checked( $options['includeGoogleMap'] );
        echo " />";
    }

    function printSetXmpKeywordsAsTagsString() {
        $options = $this->getCurrentOptions();

        echo "<input id='setXmpKeywordsAsTags' name='post_a_pic_plugin_options_general[setXmpKeywordsAsTags]' type='checkbox' value='1' ";
        checked( $options['setXmpKeywordsAsTags'] );
        echo " />";
    }

    function printSetCategoryString() {
        $options = $this->getCurrentOptions();

        echo "<input id='setCategory' name='post_a_pic_plugin_options_general[setCategory]' type='text' value='";
        echo $options['setCategory'];
        echo "' placeholder='Comma-separated categories' />";
    }

    function printSetPostFormatString() {
        $options = $this->getCurrentOptions();

        if (! current_theme_supports( 'post-formats' ) ) {
            echo "<p>Current theme does not support post formats.</p>";
        } else {
            $post_formats = get_theme_support( 'post-formats' );

            $supportedFormats = $post_formats[0];
            if ( is_array( $supportedFormats ) ) {
                // Array( supported_format_1, supported_format_2 ... )
                echo '<select name="post_a_pic_plugin_options_general[setPostFormat]">';
                echo '<option value="" ' . selected($options['setPostFormat'], '') . ' >' . get_post_format_string('') . '</option>';
                foreach ( $supportedFormats as $id => $value ) {
                    echo '<option value="' . esc_attr($value) . '" ' . selected($options['setPostFormat'], $value) . '>' . get_post_format_string($value) . '</option>';
                }
                echo '</select>';
            }
        }
    }    

    function post_a_pic_plugin_options_general_validate($input) {
        $options = $this->getCurrentOptions();
        $options['featuredImage'] = $input['featuredImage'];
        $options['addGpsLocation'] = $input['addGpsLocation'];
        $options['addCameraInfo'] = $input['addCameraInfo'];
        $options['includeImageInPost'] = $input['includeImageInPost'];
        $options['addImageInfo'] = $input['addImageInfo'];
        $options['setPostDate'] = $input['setPostDate'];
        $options['includeGoogleMap'] = $input['includeGoogleMap'];
        $options['setXmpKeywordsAsTags'] = $input['setXmpKeywordsAsTags'];
        $options['setCategory'] = $input['setCategory'];
        $options['setPostFormat'] = $input['setPostFormat'];        
        return $options;
    }
}

function post_a_pic_add_attachment($attachId){
	//try{
		$pap = new PostAPic();
		return $pap->post_a_pic($attachId);
	//}
	//catch(Exception $e)
	//{
	//	// Define the settings error to display
    //  add_settings_error(
    //    'Error',
    //    '',
    //    $e->getMessage(),
    //    'error'
    //  );
	//}
	//return NULL;
}
add_action('add_attachment', 'post_a_pic_add_attachment');

function post_a_pic_admin_init(){
    $pap = new PostAPic();
    $pap->admin_init();
}
add_action('admin_init', 'post_a_pic_admin_init');

function post_a_pic_admin_add_page(){
    $pap = new PostAPic();
    $pap->admin_add_page();
}
add_action('admin_menu', 'post_a_pic_admin_add_page');
