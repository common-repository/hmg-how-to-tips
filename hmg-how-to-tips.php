<?php
/*
Plugin Name: HMG How-To Tips
Plugin URI: http://www.haleymarketing.com/
Description: The Haley Marketing How-To Tips Plugin creates a custom post type and taxonomy for managing a knowledge base.
Author: Richard Bush | Haley Marketing Group
Author URI: http://www.haleymarketing.com/
Version: 1.1.1
License: GPLv2 or later

Copyright (c) 2012 Haley Marketing Group (http://www.haleymarketing.com)

BY USING THIS SOFTWARE, YOU AGREE TO THE TERMS OF THE PLUGIN LICENSE AGREEMENT. 
IF YOU DO NOT AGREE TO THESE TERMS, DO NOT USE THE SOFTWARE.

*/

/* set up our base HMG helper class */
include_once('inc/HMG.php');

/* load our helper libraries */
include_once('inc/custom-post-type-helper-class.php'); 

global $HOWTOTIPS_POSTTYPE_ID;
global $HOWTOTIPS_MENU_LABEL_PLURAL;
global $HOWTOTIPS_TAXONOMY_LABEL_PLURAL;

class _HOWTOTIPS {

    public $options;
    public $license;
    public $disabled_options;
    public $posttype_id;
    
    public function __construct(){
    
        global $HOWTOTIPS_POSTTYPE_ID;
        global $HOWTOTIPS_MENU_LABEL_PLURAL;
        global $HOWTOTIPS_TAXONOMY_LABEL_PLURAL;
        global $wpdb;
        global $_POST;
    
        /* load all our options and set defaults */
        
        $this->options = get_option('hmg_howtotips_options');
        
        $this->options = ($this->options) ? $this->options : array();
                
        /* retrieve license object */
        //$this->license = HMG::license($this->options['license_key'],'howtotips');                            
        $this->disabled_options = '';
        
        /* this assumes we do not want any empty options */
        $this->options = HMG::cleanOptions($this->options);
        
        $current_posttype = get_option('hmg_howtotips_type_id');
		$current_posttype = ($current_posttype) ? $current_posttype : 'how-to-tips';
        $this->posttype_id = $current_posttype;
                
        $this->options = array_merge(
				// Set Defaults
				array(
					'menu_label' 			=> _x( 'How-To Tip', 'post type label' ),
					'menu_label_plural' 	=> _x( 'How-To Tips', 'post type label plural' ),
					'taxonomy_label'        => _x( 'Type', 'taxonomy type label' ),
					'taxonomy_label_plural' => _x( 'Types', 'taxonomy type label plural' ),
				),
				// set options
				$this->options
			);
			
		$posttypeid_in_options = strtolower( str_replace( ' ', '-', $this->options['menu_label_plural'] ) );
		        
        if ($posttypeid_in_options != $current_posttype) {
		    // we have a new posttype for this 
		    // update the database with the new posttype
		    $wpdb->query( $wpdb->prepare( 
                "
                UPDATE $wpdb->posts 
                SET post_type = %s
                WHERE post_type = %s 
                ", array($posttypeid_in_options,$current_posttype)
            ) );
		    // save the new option
		    update_option('hmg_howtotips_type_id', $posttypeid_in_options);
		    $this->posttype_id = $posttypeid_in_options;
		}
		
		$HOWTOTIPS_POSTTYPE_ID = $this->posttype_id;
		$HOWTOTIPS_MENU_LABEL_PLURAL = $this->options['menu_label_plural'];
		$HOWTOTIPS_TAXONOMY_LABEL_PLURAL = $this->options['taxonomy_label_plural'];
		
        /* actions */
        add_action( 'admin_head', array($this,'custom_post_type_icon'));
        
        /* these need to execute later than 'normal', hence the 20 */
        add_action( 'init', array($this,'register_taxonomies'),5);
        
        /* create our admin options page */
        add_action('admin_menu', array($this,'register_HMG_page'));
        
        /* queue frontend scripts */
		add_action('wp_enqueue_scripts', array($this,'script_init'));
		
		/* queue admin scripts */
		add_action('admin_enqueue_scripts', array($this,'admin_script_init'));
        
        add_shortcode('starhowtotips', array($this, 'shortcodeHowToTips'));
        
        /* widget init */
        add_action( 'widgets_init', array($this, 'widget_init'));
		
        /* filters */
        /* add filter to ensure the text Talent is displayed when user updates a Talent entry */
        add_filter( 'post_updated_messages', array($this,'codex_how_to_tips_updated_messages') );
       
        /* modify the post content to add in howtotips features */
		add_filter('the_content', array($this,'howtotips_content_cb'),30);
                
        /* Add shortcode support for widgets */
		add_filter('widget_text', 'do_shortcode');
		
    }

    public function codex_how_to_tips_updated_messages( $messages ) {
        global $post, $post_ID;
                
        $messages[$this->posttype_id] = array(
            0 => '', // Unused. Messages start at index 1.
            1 => sprintf( __('%s updated. <a href="%s">View %s</a>'), $this->options['menu_label'], esc_url( get_permalink($post_ID) ), strtolower($this->options['menu_label']) ),
            2 => __('Custom field updated.'),
            3 => __('Custom field deleted.'),
            4 => sprintf(__('%s updated.'),$this->options['menu_label']),
            /* translators: %s: date and time of the revision */
            5 => isset($_GET['revision']) ? sprintf( __('%s restored to revision from %s'), $this->options['menu_label'], wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
            6 => sprintf( __('%s published. <a href="%s">View %s</a>'), $this->options['menu_label'], esc_url( get_permalink($post_ID) ),$this->options['menu_label'] ),
            7 => sprintf(__('%s saved.'), $this->options['menu_label']),
            8 => sprintf( __('%s submitted. <a target="_blank" href="%s">Preview %s</a>'), $this->options['menu_label'], esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ),$this->options['menu_label'] ),
            9 => sprintf( __('%s scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview %s</a>'),$this->options['menu_label'],
              // translators: Publish box date format, see http://php.net/date
              date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ), esc_url( get_permalink($post_ID) ),$this->options['menu_label'] ),
            10 => sprintf( __('%s draft updated. <a target="_blank" href="%s">%s How-To Tips</a>'), $this->options['menu_label'], esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ),$this->options['menu_label'] ),
        );
        
        return $messages;
    }
    
    public function register_HMG_page() {
        
        /* set up this HMG plugin admin page*/
        
        /* create nav and load page styles */
        $page = add_submenu_page('HMG', 'How-To Tips','How-To Tips','administrator',__FILE__, array($this,'howtotips_opts'));
        
        /* register options */
        register_setting('hmg_howtotips_options','hmg_howtotips_options'); // 3rd param = optional callback
                        
        add_settings_section('hmg_howtotips_advanced_section','Settings',array($this,'hmg_howtotips_advanced_section_cb'),__FILE__);
        
        add_settings_field('menu_label',"Menu Label (Singular): ",array($this,'menu_label_setting'),__FILE__,'hmg_howtotips_advanced_section');
        add_settings_field('menu_label_plural',"Menu Label (Plural): ",array($this,'menu_label_plural_setting'),__FILE__,'hmg_howtotips_advanced_section');
        add_settings_field('taxonomy_label',"Category Menu Label (Singular): ",array($this,'taxonomy_label_setting'),__FILE__,'hmg_howtotips_advanced_section');
        add_settings_field('taxonomy_label_plural',"Category Menu Label (Plural): ",array($this,'taxonomy_label_plural_setting'),__FILE__,'hmg_howtotips_advanced_section');
        
    }
    
    public function script_init() {
        /* Register our scripts. */
    }
    
    public function admin_script_init($hook) {
        /* Register our scripts. */
        
        wp_register_style( 'HMGStylesheet2', plugins_url('css/style.css', __FILE__) );
        wp_enqueue_style( 'HMGStylesheet2' );
        
    } 
    
    public function widget_init() {
            register_widget( 'How_To_Tips_Taxonomy_Terms' );
            register_widget('How_To_Tips_Widget');
    }
    
    public function howtotips_opts() {
        ?> 
        
        <div id="howtotipsOptions" class="wrap">
            <div id="<?php echo $this->posttype_id ?>" class="icon32 icon32-posts-<?php echo $this->posttype_id ?>"></div>
            <h2>How-To Tips</h2>
            <?php
                if (isset($_GET['settings-updated'])) { 
                    HMG::show_HMG_message('Options saved');
                } 
            ?>
            
            <form method="POST" action="options.php" enctype="multipart/form-data">
                
                <?php settings_fields('hmg_howtotips_options'); ?>
                <?php do_settings_sections(__FILE__); ?>
                
                <p class="submit">
                    <input type="submit" name="submit" class="button-primary" value="Save Changes" />
                </p>
            </form>

        </div>
        
        <?php
    }
    
    public function howtotips_content_cb( $content ) {
        // Do somethign to the content.   
        // Return the content.
        return $content;
    }
    
    public function custom_post_type_icon() {
        
        HMG::set_post_type_icon($image_urls=array('plugin'=>$this->posttype_id,
                                                            'admin-image'=>'images/how_to_tips_adminmenu16-sprite.png',
                                                            'admin-imageX2'=>'images/how_to_tips_adminmenu16-sprite_2x.png',
                                                            'posts-image'=>'images/adminpage32.png',
                                                            'posts-imageX2'=>'images/adminpage64.png',
                                                            'file' => __FILE__
                                                            ));
    }
    
    public function shortcodeHowToTips($atts) {
		/* hook to add in shortcode */		
	}
    
    public function hmg_howtotips_main_section_cb() {
        /* hook to add main section content */	
    }
    
    public function hmg_howtotips_advanced_section_cb() {
    
    }
    
    public function register_taxonomies() {
		
		/* create howtotips post type */
        $howtotips = new Custom_Post_Type_HMG( $this->posttype_id, $this->options['menu_label'], $this->options['menu_label_plural']); // id, menu label, menu label plural
        $this->howtotips = $howtotips;
        $this->howtotips->add_taxonomy('how-to-tips-type', $this->options['taxonomy_label'], $this->options['taxonomy_label_plural']); // id, menu label, menu label plural
    
    }
    
    public function menu_label_setting() {
        $val = $this->options['menu_label'];
        echo "<input type='text' name='hmg_howtotips_options[menu_label]' value='$val' $this->disabled_options/>";
    }
    
    public function menu_label_plural_setting() {
        $val = $this->options['menu_label_plural'];
        echo "<input type='text' name='hmg_howtotips_options[menu_label_plural]' value='$val' $this->disabled_options/>";
    }
    
    public function taxonomy_label_setting() {
        $val = $this->options['taxonomy_label'];
        echo "<input type='text' name='hmg_howtotips_options[taxonomy_label]' value='$val' $this->disabled_options/>";
    }
    
    public function taxonomy_label_plural_setting() {
        $val = $this->options['taxonomy_label_plural'];
        echo "<input type='text' name='hmg_howtotips_options[taxonomy_label_plural]' value='$val' $this->disabled_options/>";
    }
        
}

$starhowtotipsHMG = new _HOWTOTIPS();

class How_To_Tips_Widget extends WP_Widget {

	public function __construct() {
	    global $HOWTOTIPS_POSTTYPE_ID;
		global $HOWTOTIPS_MENU_LABEL_PLURAL;
        global $HOWTOTIPS_TAXONOMY_LABEL_PLURAL;
		parent::__construct(
	 		'how_to_tips_widget', // Base ID
			$HOWTOTIPS_MENU_LABEL_PLURAL, // Name
			array( 'description' => __( "Displays a list of the most recent $HOWTOTIPS_MENU_LABEL_PLURAL")) // Args
		);
	}
	
	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title'] = strip_tags( $new_instance['title'] );
		$instance['count'] = !empty( $new_instance['count'] ) ? $new_instance['count'] : 3;
		return $instance;
	}

	public function form( $instance ) {
		$title = (isset( $instance[ 'title' ])) ? $instance[ 'title' ] : 'Most Recent Tips';
        $current_count = esc_attr( $instance['count'] );
	?>
		<p>
		<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label> 
		<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		</p>
		
		<p><label for="<?php echo $this->get_field_id( 'count' ); ?>"><?php _e( 'How many entries should we display:' ) ?></label>
        <select class="widefat" id="<?php echo $this->get_field_id( 'count' ); ?>" name="<?php echo $this->get_field_name( 'count' ); ?>">
        
    <?php
        $counts = array( '1' , '2' , '3', '4', '5');
        foreach( $counts as $count ) {
    ?>
        <option value="<?php echo esc_attr( $count ); ?>" <?php selected( $count , $current_count ); ?>><?php echo ucfirst( $count ); ?></option>
    <?php
        }
    ?>
        </select>
		
	<?php 
	}

	public function widget( $args, $instance ) {
		extract( $args );
		global $post;
		global $HOWTOTIPS_POSTTYPE_ID;
		$title = apply_filters( 'widget_title', $instance['title'] );
		$count = $instance['count'];

		echo $before_widget;
		if ( ! empty( $title ) ) echo $before_title . $title . $after_title;

		$args= array(
			'post_type' => $HOWTOTIPS_POSTTYPE_ID,
			'posts_per_page' => 3,
			'posts_per_page' => $count,
			'post__not_in' => array($post->ID)
			);

	$featuredWidget= new WP_Query($args);
	
	while ( $featuredWidget->have_posts() ) : $featuredWidget->the_post(); ?>
	
	<div class="how_to_tips_widget st_owner_box clearfix">
		<div class="thumb"><?php print get_the_post_thumbnail($post->ID); ?></div>
		<h5><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h5>
		<?php the_excerpt(); ?>
	</div>
		
		<?php
	endwhile;
	
	wp_reset_postdata();

		echo $after_widget; 
	}

}

class How_To_Tips_Taxonomy_Terms extends WP_Widget {
    
    public function __construct() {
        global $HOWTOTIPS_MENU_LABEL_PLURAL;
        global $HOWTOTIPS_TAXONOMY_LABEL_PLURAL;
		parent::__construct(
	 		'how_to_tips_widget_taxonomy_terms', // Base ID
			"$HOWTOTIPS_MENU_LABEL_PLURAL $HOWTOTIPS_TAXONOMY_LABEL_PLURAL List", // Name
			array( 'description' => __( "A list, dropdown, or cloud of your $HOWTOTIPS_MENU_LABEL_PLURAL $HOWTOTIPS_TAXONOMY_LABEL_PLURAL")) // Args
		);
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
        $t = 'how-to-tips-type';
        //$t = $instance['taxonomy'];
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
        $hierarchical = isset( $instance['hierarchical'] ) ? (bool) $instance['hierarchical'] : true;
        
        ?>
        <p><label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label>
        <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo $title; ?>" /></p>
        
        <input type="hidden" id="<?php echo $this->get_field_id( 'taxonomy' ); ?>" name="<?php echo $this->get_field_name( 'taxonomy' ); ?>" value="type">
                
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


