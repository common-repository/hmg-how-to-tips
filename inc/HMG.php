<?php 
/*
Description: HMG Base Class
Author: Richard Bush | Haley Marketing Group
Author URI: http://www.haleymarketing.com/
Version: 1.0
License: GPLv2 or later
Copyright (c) 2012 Haley Marketing Group (http://www.haleymarketing.com)

BY USING THIS SOFTWARE, YOU AGREE TO THE TERMS OF THE PLUGIN LICENSE AGREEMENT. 
IF YOU DO NOT AGREE TO THESE TERMS, DO NOT USE THE SOFTWARE.

*/
if (!class_exists("HMG")) {
    class HMG {

        public function __construct(){
            /* Custom Post Type Icon for Admin Menu & Post Screen */
            add_action( 'admin_head', array($this,'set_post_type_icon_cb'));
            add_action('admin_menu', array($this,'register_HMG_mainpage'));
        }
        
        public function register_HMG_mainpage() {
            $main_page = add_menu_page('Haley Marketing', 'Haley Marketing', 'administrator', 'HMG', array($this,'hmg_init'),'div'); //plugins_url('myplugin/images/icon.png')
            wp_register_style( 'HMGStylesheet', plugins_url('../css/style.css', __FILE__) );
            add_action( 'admin_print_styles-' . $main_page, array($this, 'add_HMG_stylesheet') );
        }
          
        public function hmg_init() {
            include_once('splash.php');
        }
        
        public function add_HMG_stylesheet() {
            wp_enqueue_style( 'HMGStylesheet' );
        }
        
        /* for inline messages */
        public function show_HMG_message($msg,$class = '') {
            $class = (empty($class)) ? 'updated' : $class;
            ?>
            <div class="<?php echo $class ?>">
                <p>
                <?php echo $msg ?>
                </p>
            </div>
            <?php 
        }
        
        public function cleanOptions($arr) {
            return array_filter($arr);
        }
        
        public function set_post_type_icon_cb() {
    
            HMG::set_post_type_icon($image_urls=array('plugin'=>'HMG',
                                                                'admin-image'=>'images/HMG_adminmenu16-sprite.png',
                                                                'posts-image'=>'images/HMG_adminpage32.png',
                                                                'admin-imageX2'=>'images/HMG_adminmenu16-sprite_2x.png',
                                                                'posts-imageX2'=>'images/HMG_adminpage32_2x.png',
                                                                'file'=>__FILE__
                                                                ));
            
        }
        
        public function set_post_type_icon($image_urls=array('plugin'=>'HMG',
                                                                'admin-image'=>'images/HMG_adminmenu16-sprite.png',
                                                                'posts-image'=>'images/HMG_adminpage32.png',
                                                                'admin-imageX2'=>'images/HMG_adminmenu16-sprite_2x.png',
                                                                'posts-imageX2'=>'images/HMG_adminpage32_2x.png',
                                                                'file'=>__FILE__
                                                                )) {
    ?>
            <style>
                /* Admin Menu - 16px */
                #toplevel_page_<?php echo $image_urls['plugin']?> .wp-menu-image, #menu-posts-<?php echo $image_urls['plugin']?> .wp-menu-image {
                    background: url(<?php echo plugins_url($image_urls['admin-image'], $image_urls['file']) ?>) no-repeat 6px 6px !important;
                }
                #toplevel_page_<?php echo $image_urls['plugin']?>:hover .wp-menu-image, #menu-posts-<?php echo $image_urls['plugin']?>.wp-has-current-submenu .wp-menu-image,  #menu-posts-<?php echo $image_urls['plugin']?>:hover .wp-menu-image {
                    background-position: 6px -26px !important;
                }
                
                /* Post Screen - 32px */
                .icon32-posts-<?php echo $image_urls['plugin']?> {
                    background: url(<?php echo plugins_url($image_urls['posts-image'], $image_urls['file']) ?>) no-repeat left top !important;
                }
                @media
                only screen and (-webkit-min-device-pixel-ratio: 1.5),
                only screen and (   min--moz-device-pixel-ratio: 1.5),
                only screen and (     -o-min-device-pixel-ratio: 3/2),
                only screen and (        min-device-pixel-ratio: 1.5),
                only screen and (                min-resolution: 1.5dppx) {
                     
                    /* Admin Menu - 16px @2x */
                    #toplevel_page_<?php echo $image_urls['plugin']?> .wp-menu-image {
                        background-image: url(<?php echo plugins_url($image_urls['admin-imageX2'], $image_urls['file']) ?>) !important;
                        -webkit-background-size: 16px 48px;
                        -moz-background-size: 16px 48px;
                        background-size: 16px 48px;
                    }
                    /* Post Screen - 32px @2x */
                    .icon32-posts-<?php echo $image_urls['plugin']?> {
                        background-image: url(<?php echo plugins_url($image_urls['posts-imageX2'], $image_urls['file']) ?>) !important;
                        -webkit-background-size: 32px 32px;
                        -moz-background-size: 32px 32px;
                        background-size: 32px 32px;
                    }        
                }
            </style>
    <?php 
    
        }

        public function license($key,$product) {
            
            $license = get_option("_hmg_license");
            $hash = md5($key . $product);
            $validity = 0;
            $owner = '';
            $url = "http://admin.haleymarketing.com/json/?k=$key&p=$product&pid=gwt&arg=validate_license";
            $whetherUpdateLicense = false;
            
            if (!($key && $product)) {
                return array("last_update"=>time(),"validity"=>false,"owner"=>$owner);
            }
            
            if (isset($license[$hash])){
               $timeOfLastFetch = intval(@$license[$hash]["last_update"]);
               if (time()-$timeOfLastFetch > 500) { //update the license every 500 minutes
                    $whetherUpdateLicense = true;
               } else {
                     if ($license[$hash]['validity'] == true) {
                        return $license[$hash];
                     } else {
                        $whetherUpdateLicense = true; 
                    }
               }
               
            } else {
               $whetherUpdateLicense = true;             
            }     
            
            if ($whetherUpdateLicense) {
                        
               if ($result = file_get_contents($url)) {
               
                   $result = json_decode($result);
                   $validity = intval($result->ResultSet->validity) ? true : false;
                   $owner = $result->ResultSet->owner;
                                      
               }
               $license = get_option("_hmg_license");
               $license[$hash] = array("last_update"=>time(),"validity"=>$validity,"owner"=>$owner);
               update_option("_hmg_license",$license);
            }
            
            return $license[$hash];
            
        }
              
    }
    
    $hmg = new HMG();
    
}

/* Helper Class for checking for plugin dependencies */
if (!class_exists('Theme_Plugin_Dependency')) {
    // we need this to enable plugin checks outside of admin
    require_once(ABSPATH . 'wp-admin/includes/plugin.php');
	class Theme_Plugin_Dependency {
		// input information from the theme
		var $slug;
		var $uri;

		// installed plugins and uris of them
		private $plugins; // holds the list of plugins and their info
		private $uris; // holds just the URIs for quick and easy searching

		// both slug and PluginURI are required for checking things
		function __construct( $slug, $uri ) {
			$this->slug = $slug;
			$this->uri = $uri;
			if ( empty( $this->plugins ) ) 
				$this->plugins = get_plugins();
			if ( empty( $this->uris ) ) 
				$this->uris = wp_list_pluck($this->plugins, 'PluginURI');
		}

		// return true if installed, false if not
		function check() {
			return in_array($this->uri, $this->uris);
		}

		// return true if installed and activated, false if not
		function check_active() {
			$plugin_file = $this->get_plugin_file();
			if ($plugin_file) return is_plugin_active($plugin_file);
			return false;
		}

		// gives a link to activate the plugin
		function activate_link() {
			$plugin_file = $this->get_plugin_file();
			if ($plugin_file) return wp_nonce_url(self_admin_url('plugins.php?action=activate&plugin='.$plugin_file), 'activate-plugin_'.$plugin_file);
			return false;
		}

		// return a nonced installation link for the plugin. checks wordpress.org to make sure it's there first.
		function install_link() {
			include_once ABSPATH . 'wp-admin/includes/plugin-install.php';

			$info = plugins_api('plugin_information', array('slug' => $this->slug ));

			if ( is_wp_error( $info ) ) 
				return false; // plugin not available from wordpress.org

			return wp_nonce_url(self_admin_url('update.php?action=install-plugin&plugin=' . $this->slug), 'install-plugin_' . $this->slug);
		}

		// return array key of plugin if installed, false if not, private because this isn't needed for themes, generally
		private function get_plugin_file() {
			return array_search($this->uri, $this->uris);
		}
	}
}

/* Helper Class for admin notices */
if (!class_exists('HMG_Admin_Notices')) {
    class HMG_Admin_Notices {
    
        // input information
		var $id;
		var $msg;
		var $class;
		var $hide_hide;
		
        public function __construct($id,$msg,$class,$hide_hide,$filter = ''){
            $this->id = $id;
            $this->msg = $msg;
			$this->class = $class;
			$this->hide_hide = $hide_hide;
            /* Display a notice that can be dismissed */
            add_action('admin_notices', array($this,'hmg_admin_notice'));
            add_action('admin_init', array($this,'hmg_nag_ignore'));
            if ($filter) {
                return add_filter($filter, array($this,'hmg_admin_notice') ); 
            }
        }
        
        public function hmg_admin_notice() {
            global $current_user ;
                $user_id = $current_user->ID;
                /* Check that the user hasn't already clicked to ignore the message */
            if ( ! get_user_meta($user_id, $this->id . '_ignore_notice') ) {
                echo "<div class='" . $this->class . "'><p>";
                if ($this->hide_hide) {
                    echo $this->msg;
                } else {
                    parse_str($_SERVER['QUERY_STRING'], $params);
                    printf(__($this->msg . ' | <a href="%1$s">Hide Notice</a>'), '?' . http_build_query(array_merge($params, array($this->id . '_ignore_notice'=>'0'))));
                }
                echo "</p></div>";
            }
        }
        
        public function hmg_nag_ignore() {
            global $current_user;
                $user_id = $current_user->ID;
                /* If user clicks to ignore the notice, add that to their user meta */
                if ( isset($_GET[$this->id . '_ignore_notice']) && '0' == $_GET[$this->id . '_ignore_notice'] ) {
                     add_user_meta($user_id, $this->id . '_ignore_notice', 'true', true);
            }
        }
    }
}

/* Helper Class for Taxonomy Widgets */
if (!class_exists('WP_Widget_Taxonomy_Terms')) {
    class WP_Widget_Taxonomy_Terms extends WP_Widget {
     
      function WP_Widget_Taxonomy_Terms() {
        $widget_ops = array( 'classname' => 'widget_taxonomy_terms' , 'description' => __( "A list, dropdown, or cloud of taxonomy terms" ) );
        $this->WP_Widget( 'taxonomy_terms' , __( 'Taxonomy Terms' ) , $widget_ops );
      }
     
      function widget( $args , $instance ) {
        extract( $args );
     
        $current_taxonomy = $this->_get_current_taxonomy( $instance );
        $tax = get_taxonomy( $current_taxonomy );
        if ( !empty( $instance['title'] ) ) {
          $title = $instance['title'];
        } else {
          $title = $tax->labels->name;
        }
     
        global $t;
        $t = $instance['taxonomy'];
        $f = $instance['format'];
        $c = $instance['count'] ? '1' : '0';
        $h = $instance['hierarchical'] ? '1' : '0';
     
        $w = $args['widget_id'];
        $w = 'ttw' . str_replace( 'taxonomy_terms-' , '' , $w );
     
        echo $before_widget;
        if ( $title )
          echo $before_title . $title . $after_title;
     
        $tax_args = array( 'orderby' => 'name' , 'show_count' => $c , 'hierarchical' => $h , 'taxonomy' => $t );
     
        if ( $f == 'dropdown' ) {
          $tax_args['show_option_none'] = __( 'Select ' . $tax->labels->singular_name );
          $tax_args['name'] = __( $w );
          $tax_args['echo'] = false;
          $my_dropdown_categories = wp_dropdown_categories( apply_filters( 'widget_categories_dropdown_args' , $tax_args ) );
     
          $my_get_term_link = create_function( '$matches' , 'global $t; return "value=\"" . get_term_link( (int) $matches[1] , $t ) . "\"";' );
          echo preg_replace_callback( '#value="(\\d+)"#' , $my_get_term_link , $my_dropdown_categories );
     
    ?>
    <script type='text/javascript'>
    /* <![CDATA[ */
      var dropdown<?php echo $w; ?> = document.getElementById("<?php echo $w; ?>");
      function on<?php echo $w; ?>change() {
        if ( dropdown<?php echo $w; ?>.options[dropdown<?php echo $w; ?>.selectedIndex].value != '-1' ) {
          location.href = dropdown<?php echo $w; ?>.options[dropdown<?php echo $w; ?>.selectedIndex].value;
        }
      }
      dropdown<?php echo $w; ?>.onchange = on<?php echo $w; ?>change;
    /* ]]> */
    </script>
    <?php
     
        } elseif ( $f == 'list' ) {
     
    ?>
        <ul>
    <?php
     
        $tax_args['title_li'] = '';
        wp_list_categories( apply_filters( 'widget_categories_args' , $tax_args ) );
     
    ?>
        </ul>
    <?php
     
        } else {
     
    ?>
        <div>
    <?php
     
          wp_tag_cloud( apply_filters( 'widget_tag_cloud_args' , array( 'taxonomy' => $t ) ) );
     
    ?>
        </div>
    <?php
     
        }
        echo $after_widget;
      }
     
      function update( $new_instance, $old_instance ) {
        $instance = $old_instance;
        $instance['title'] = strip_tags( $new_instance['title'] );
        $instance['taxonomy'] = stripslashes( $new_instance['taxonomy'] );
        $instance['format'] = stripslashes( $new_instance['format'] );
        $instance['count'] = !empty( $new_instance['count'] ) ? 1 : 0;
        $instance['hierarchical'] = !empty( $new_instance['hierarchical'] ) ? 1 : 0;
     
        return $instance;
      }
     
      function form( $instance ) {
        //Defaults
        $instance = wp_parse_args( (array) $instance , array( 'title' => '' ) );
        $current_taxonomy = $this->_get_current_taxonomy( $instance );
        $current_format = esc_attr( $instance['format'] );
        $title = esc_attr( $instance['title'] );
        $count = isset( $instance['count'] ) ? (bool) $instance['count'] : false;
        $hierarchical = isset( $instance['hierarchical'] ) ? (bool) $instance['hierarchical'] : false;
     
    ?>
        <p><label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label>
        <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo $title; ?>" /></p>
     
        <p><label for="<?php echo $this->get_field_id( 'taxonomy' ); ?>"><?php _e( 'Taxonomy:' ); ?></label>
        <select class="widefat" id="<?php echo $this->get_field_id( 'taxonomy' ); ?>" name="<?php echo $this->get_field_name( 'taxonomy' ); ?>">
    <?php
     
        $args = array(
          'public' => true ,
          '_builtin' => false
        );
        $output = 'names';
        $operator = 'and';
     
        $taxonomies = get_taxonomies( $args , $output , $operator );
        $taxonomies = array_merge( $taxonomies, array( 'category' , 'post_tag' ) );
        foreach ( $taxonomies as $taxonomy ) {
          $tax = get_taxonomy( $taxonomy );
          if ( empty( $tax->labels->name ) )
            continue;
    ?>
        <option value="<?php echo esc_attr( $taxonomy ); ?>" <?php selected( $taxonomy , $current_taxonomy ); ?>><?php echo $tax->labels->name; ?></option>
    <?php
     
        }
     
    ?>
        </select></p>
     
        <p><label for="<?php echo $this->get_field_id( 'format' ); ?>"><?php _e( 'Format:' ) ?></label>
        <select class="widefat" id="<?php echo $this->get_field_id( 'format' ); ?>" name="<?php echo $this->get_field_name( 'format' ); ?>">
    <?php
     
        $formats = array( 'list' , 'dropdown' , 'cloud' );
        foreach( $formats as $format ) {
     
    ?>
        <option value="<?php echo esc_attr( $format ); ?>" <?php selected( $format , $current_format ); ?>><?php echo ucfirst( $format ); ?></option>
    <?php
     
        }
     
    ?>
        </select></p>
        <input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id( 'count' ); ?>" name="<?php echo $this->get_field_name( 'count' ); ?>"<?php checked( $count ); ?> />
        <label for="<?php echo $this->get_field_id( 'count' ); ?>"><?php _e( 'Show post counts' ); ?></label><br />
     
        <input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id( 'hierarchical' ); ?>" name="<?php echo $this->get_field_name( 'hierarchical' ); ?>"<?php checked( $hierarchical ); ?> />
        <label for="<?php echo $this->get_field_id( 'hierarchical' ); ?>"><?php _e( 'Show hierarchy' ); ?></label></p>
    <?php
     
      }
     
      function _get_current_taxonomy( $instance ) {
        if ( !empty( $instance['taxonomy'] ) && taxonomy_exists( $instance['taxonomy'] ) )
          return $instance['taxonomy'];
        else
          return 'category';
      }
    }
}

?>