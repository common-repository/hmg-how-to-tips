<?php
if (!class_exists("Custom_Post_Type_HMG")) {
class Custom_Post_Type_HMG {

		public $post_type_name;
		public $post_type_args;
		public $post_type_labels;
		public $validations;
		
		/* Class constructor */
		public function __construct( $id, $name, $plural, $args = array(), $labels = array() ) {
			// Set some important variables
			$this->post_type_id         = strtolower( str_replace( ' ', '_', $id ) );
			$this->post_type_name		= strtolower( str_replace( ' ', '_', $name ) );
			$this->post_type_plural		= $plural;
			$this->post_type_args 		= $args;
			$this->post_type_labels 	= $labels;

			// Add action to register the post type, if the post type doesnt exist
			if( ! post_type_exists( $this->post_type_id ) ) {
				add_action( 'init', array($this,'cpt_register_post_type') );
			}

			// Listen for the save post hook
			$this->save();
		}
		
		/* Method which registers the post type */
		public function cpt_register_post_type() {		
				    
			//Capitilize the words and make it plural
			$name 		= ucwords( str_replace( '_', ' ', $this->post_type_name ) );
			$plural 	= ($this->post_type_plural) ? $this->post_type_plural : $name . 's';

			// We set the default labels based on the post type name and plural. We overwrite them with the given labels.
			$labels = array_merge(

				// Default
				array(
					'name' 					=> _x( $name, 'post type general name' ),
					'singular_name' 		=> _x( $name, 'post type singular name' ),
					'add_new' 				=> _x( 'Add New', strtolower( $name ) ),
					'add_new_item' 			=> __( 'Add New ' . $name ),
					'edit_item' 			=> __( 'Edit ' . $name ),
					'new_item' 				=> __( 'New ' . $name ),
					'all_items' 			=> __( 'All ' . $plural ),
					'view_item' 			=> __( 'View ' . $name ),
					'search_items' 			=> __( 'Search ' . $plural ),
					'not_found' 			=> __( 'No ' . strtolower( $plural ) . ' found'),
					'not_found_in_trash' 	=> __( 'No ' . strtolower( $plural ) . ' found in Trash'), 
					'parent_item_colon' 	=> '',
					'menu_name' 			=> $plural
				),

				// Given labels
				$this->post_type_labels

			);

			// Same principle as the labels. We set some default and overwite them with the given arguments.
			$args = array_merge(

				// Default
				
			    array(
                    'labels' => $labels,
                    'public' => true,
                    'publicly_queryable' => true,
                    'show_ui' => true,
                    'query_var' => true,		
                    'rewrite' => true,
                    'capability_type' => 'post',
                    'hierarchical' => false,
                    'menu_position' => null,
                    'has_archive' => true,
                    'supports' => array('title', 'editor', 'thumbnail', 'comments', 'revisions', 'excerpt')
				),

				// Given args
				$this->post_type_args

			);

			// Register the post type
			register_post_type( $this->post_type_id, $args );
			flush_rewrite_rules();
		}
		
		/* Method to attach the taxonomy to the post type */
		public function add_taxonomy( $id, $name, $plural, $args = array(), $labels = array() ) {
			if( ! empty( $id ) && ! empty( $name) ) {			
				// We need to know the post type name, so the new taxonomy can be attached to it.
				$post_type_id = $this->post_type_id;
				
				// Taxonomy properties
				$taxonomy_name		= strtolower( str_replace( ' ', '_', $id ) );
				$taxonomy_labels	= $labels;
				$taxonomy_args		= $args;

				if( ! taxonomy_exists( $taxonomy_name ) ) {
                    //Capitilize the words and make it plural
                        $name 		= ucwords( str_replace( '_', ' ', $name ) );
                        $plural 	= ($plural) ? $plural : $name . 's';

                        // Default labels, overwrite them with the given labels.
                        $labels = array_merge(

                            // Default
                            array(
                                'name' 					=> _x( $plural, 'taxonomy general name' ),
                                'singular_name' 		=> _x( $name, 'taxonomy singular name' ),
                                'search_items' 			=> __( 'Search ' . $plural ),
                                'all_items' 			=> __( 'All ' . $plural ),
                                'parent_item' 			=> __( 'Parent ' . $name ),
                                'parent_item_colon' 	=> __( 'Parent ' . $name . ':' ),
                                'edit_item' 			=> __( 'Edit ' . $name ), 
                                'update_item' 			=> __( 'Update ' . $name ),
                                'add_new_item' 			=> __( 'Add New ' . $name ),
                                'new_item_name' 		=> __( 'New ' . $name . ' Name' ),
                                'menu_name' 			=> __( $plural ),
                            ),

                            // Given labels
                            $taxonomy_labels

                        );

                        // Default arguments, overwitten with the given arguments
                        $args = array_merge(

                            // Default
                            array(
                                'labels'				=> $labels,
                                'hierarchical'          => true
                            ),
                            // Given
                            $taxonomy_args

                        );
                        
                        register_taxonomy($taxonomy_name, $post_type_id, $args);
                        flush_rewrite_rules();
                        
                }
			}
		}
		
		/* Attaches meta boxes to the post type */
		public function add_meta_boxes( $id, $title, $fields = array(), $context = 'normal', $priority = 'default' ) {
			if( ! empty( $title ) ) {		
				// We need to know the Post Type name again
				$post_type_id = $this->post_type_id;

				// Meta variables	
				$box_id 		= strtolower( str_replace( ' ', '_', $id ) );
				$box_title		= ucwords( str_replace( '_', ' ', $title ) );
				$box_context	= $context;
				$box_priority	= $priority;

				// Make the fields global
				global $custom_fields;
				$custom_fields[$id] = $fields;
				
				add_meta_box(
								$box_id,
								$box_title,
								array($this,'add_meta_box_cb'),
								$post_type_id,
								$box_context,
								$box_priority,
								array( $fields )
							);
				
			}
		}
		
		public function add_meta_box_cb($post, $data) {
                global $post;

                // Nonce field for some validation
                wp_nonce_field( plugin_basename( __FILE__ ), 'custom_post_type' );

                // Get all inputs from $data
                $custom_fields = $data['args'][0];

                // Get the saved values
                $meta = get_post_custom( $post->ID );

                // Check the array and loop through it
                if( ! empty( $custom_fields ) ) {
                    /* Loop through $custom_fields */
                    
                    // need to save validation hooks
                    // $this->validations[fieldname] = array($this,'function')
                    
                    foreach( $custom_fields as $label => $atts ) {
                        $type = $atts[0];
                        // not functional yet
                        // $callback = $atts[1];
                        $class='';
                        $err_msg = '';
                        $field_id_name 	= strtolower( str_replace( ' ', '_', $data['id'] ) ) . '_' . strtolower( str_replace( ' ', '_', $label ) );
                        
                        if ($field_id_name == 'talent_details_owner') {
                            
                            // do a test to make sure the user exists in this blog
                            $args = array(
	                                        'blog_id' => $GLOBALS['blog_id'],
	                                        'search'  => $meta[$field_id_name][0]
	                                    );
                            $blogusers = get_users($args);
                            if ($blogusers) {
                                
                            } else {
                            $class='_error';
                            $err_msg = "<strong>This must be a valid wordpress user.</strong><script type='text/javascript'>
                                             var error = '<div class=\"error below-h2\"><p><strong>Invalid Data: </strong>The Owner be a valid wordpress user.</p></div>';
                                             // Append the error
                                             jQuery( '#post' ).prepend( error );
                                        </script>";
                            }           
                        
                        }
                        
                        echo '<label class="hmg_mb_label" for="' . $field_id_name . '">' . $label . '</label><input type="text" name="custom_meta[' . $field_id_name . ']" id="' . $field_id_name . '" value="' . $meta[$field_id_name][0] . '" class="hmg_mb_field' . $class . '"/><br/>' . $err_msg;
                        // this is where we need to do validation on the fields!
                        
                    }
                }

            }
		
		/* Listens for when the post type being saved */
		public function save() {
			add_action('save_post',array($this,'save_cb'),20);
		}
		
		public function save_cb() {
		    
		    $post_type_id = $this->post_type_id;
            // Deny the wordpress autosave function
            if( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return;
            
            if ( ! wp_verify_nonce( $_POST['custom_post_type'], plugin_basename(__FILE__) ) ) return;

            global $post;

            if( isset( $_POST ) && isset( $post->ID ) && get_post_type( $post->ID ) == $post_type_id ) {
                global $custom_fields;
                // Loop through each meta box
                foreach( $custom_fields as $title => $fields ) {
                    // Loop through all fields
                    foreach( $fields as $label => $type ) {
                        $field_id_name 	= strtolower( str_replace( ' ', '_', $title ) ) . '_' . strtolower( str_replace( ' ', '_', $label ) );
                        // need to hook into custom validation methods here
                        update_post_meta( $post->ID, $field_id_name, $_POST['custom_meta'][$field_id_name] );
                    }
                } 
            }
            
        }

}
}


?>