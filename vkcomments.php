<?php
/*
	Plugin Name: VKontakte Comments
	Plugin URI: http://plugins.yourwordpresscoder.com/vkcomments/
	Description: This plugin connects to your site the opportunity to comment VKontakte users
	Version: 1.0.6
	Author: Your Wordpress Coder
	Author URI: http://plugins.yourwordpresscoder.com/vkcomments/
*/
if(preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) { die('You are not allowed to call this page directly.'); }

if (!class_exists('vkcomments')) {
	
    class vkcomments {
        
        function __construct() {
            add_action('init', array(&$this, 'vkcoments_init') );  
            add_action('wp_dashboard_setup', array(&$this, 'dashboard_widget') );
            add_action('wp_ajax_vkcomments_comment_activity', array(&$this, 'vkcomments_activity'));
            add_action('wp_ajax_nopriv_vkcomments_comment_activity', array(&$this, 'vkcomments_activity'));
        }
        
    	function dashboard_widget() {
			global $wp_meta_boxes;
			wp_add_dashboard_widget('custom_help_widget', __('Latest VKComments', 'vkcomments'), array( &$this, 'get_last_comments') );
		}
		
		function vkcomments_activity() {
			
			$options = get_option( 'vkcomments_options' );
			$all_comments = unserialize( $options['comments_info_data']);
			
			$vk_hash = md5( $_SERVER['HTTP_REFERER']);
			$new_comment = array( $vk_hash => array( date('d.m.Y H:i:s'), $_SERVER['HTTP_REFERER'], (int)$_POST['num'], mysql_real_escape_string( $_POST['last_comment']) ));

			
			if( is_array( $all_comments) ) {

				for( $i=0; $i<count( $all_comments); $i++) {
					if( isset( $all_comments[ $vk_hash])) unset( $all_comments[ $vk_hash ]);
				}
				
				if( is_array( $all_comments) && count( $all_comments) >= 1) {

					$all_comments = array_merge( $new_comment, $all_comments);
					
				} else {
					$all_comments = $new_comment;
				}
				
				if( count($all_comments) > 15) {
					$all_comments = @array_slice($all_comments, 0, 15);					
				}
				
				
			} else {
				$all_comments = $new_comment;
			}
			
			$options['comments_info_data'] = serialize( $all_comments);
			
			update_option( 'vkcomments_options', $options);

        	echo json_encode( array("result" => true));
        
        	die();
			
		}
		
		function get_last_comments() {
			$options = get_option( 'vkcomments_options' );
			$all_comments = unserialize( $options['comments_info_data']);
			
			if( $all_comments) {
				
				echo '<table class="widefat" style="width: 100%;" id="sellercms-dashdoard-orders">';
				echo '<thead>';
					echo '<tr>';
						echo '<th>'.__('Date', 'vkcomments').'</th>';
						echo '<th>'.__('Page Link', 'vkcomments').'</th>';
						echo '<th>'.__('Comments count', 'vkcomments').'</th>';
						echo '<th>'.__('Text', 'vkcomments').'</th>';
					echo '</tr>';
				echo '</thead>';
				echo '<tbody>';

					$i=0;
					foreach($all_comments as $ac) {
				
						$i++;
				
						$class =  ($i & 0x01)==0x01 ?  " class=\"alternate\"" : '';
						
						echo '<tr'.$class.'>';
						echo '<td style="white-space: nowrap; font-weight: bold;">'.$ac[0].'</td>';
						echo '<td><a href="'.$ac[1].'">'.__('go', 'vkcomments').'</a></td>';
						echo '<td>'.$ac[2].'</td>';
						echo '<td>'.$ac[3].'</td>';
						echo '</tr>';

					}
				
				echo '</tbody>';
				echo '</table><style type="text/css">#sellercms-dashdoard-orders td, #sellercms-dashdoard-orders th { font-size: 12px; padding: 7px; text-align: center; }</style>';
				
			} else {
				echo '<p>' . __('No comment activity at this time', 'vkcomments') . '</p>';
			}

		}
        
        function vkcoments_init() {
            global $wpdb;
            $wpdb->query("SET NAMES 'utf8'");
            $wpdb->query("SET CHARACTER_SET_CLIENT='utf8'");
            
            register_deactivation_hook( __FILE__, array( &$this, 'uninstall_plugin'));

            $options = get_option( 'vkcomments_options' );
            
            add_shortcode( 'vkcomments', array( &$this, 'return_comments') );
            
            if( $options['plugin_show'] == 1) {
                add_filter('comments_template', array(&$this, 'show_comments'));                
            }
        
            add_action('admin_menu', array( &$this, 'plugin_menu'));
        
            load_plugin_textdomain ( 'vkcomments' , false, dirname( plugin_basename( __FILE__ ) ) . '/languages/'  );
            
            add_action('wp_head', array( &$this,'vkcomments_js_header') );

        }
        
        function install_plugin() {
            add_option( 'vkcomments_options', array( 
            
                'api_id' => '',
                'autopublish' => 0,
                'width' => 500,
                'comments_count' => 20,
                'advanced_comments' => 1,
                'graffity' => 1,
                'photos' => 1,
                'audio' => 1,
                'video' => 1,
                'links' => 1,
                'plugin_show' => 1,
            	'comments_info_data' => ''
            
            ));

        }
        
        function uninstall_plugin() {
            delete_option( 'vkcomments_options');
        }
        
        function plugin_menu() {
            
            //$page = add_menu_page('vkcomments', __( "VK Comments", "vkcomments"), 1,  basename(__FILE__), array( &$this, 'show_options'), '../wp-content/plugins/vkcomments/images/vkontakte.png');
            $page = add_submenu_page('edit-comments.php', 'Options', 'VK Comments', 'manage_options', 'vkcomments-options', array(&$this, 'show_options'), '../wp-content/plugins/vkcomments/images/vkontakte.png' ); 
            add_contextual_help( $page ,'<p>' . __( 'You like this plugin? You can donate, or give me a job :-) Please, visit <a href="http://plugins.yourwordpresscoder.com/vkcomments/">plugin homepage </a>','vkcomments') . '</p>');
            
        }
        
        function vkcomments_js_header() {
            
            $options = get_option( 'vkcomments_options' );
            
?>
<script type="text/javascript" src="http://userapi.com/js/api/openapi.js?22"></script>
<script type="text/javascript">
  VK.init({
    apiId: <?php echo $options['api_id']; ?>,
    onlyWidgets: true
  });
</script>

<?php
        }
        
        public function show_comments() {
        	$options = get_option( 'vkcomments_options' );
?>

<div id="vk_comments"></div>
<script type="text/javascript">
VK.Widgets.Comments("vk_comments", {
	limit: <?php echo $options['comments_count']; ?>, 
	width: <?php echo $width = trim( $options['width']) == '' ? '' : $options['width']; ?>, 
	attach: "<?php 
				        
				if( $options['advanced_comments'] == 1) {
					$opts = array();

					if( $options['video'] == 1) $opts[] = 'video';
					if( $options['photos'] == 1) $opts[] = 'photo';
					if( $options['audio'] == 1) $opts[] = 'audio';
					if( $options['links'] == 1) $opts[] = 'link';
					if( $options['graffity'] == 1) $opts[] = 'graffiti';
				        
					echo join(",", $opts);            
				} else {
					echo 'false';
				}
				?>",
    autoPublish: <?php echo (int)$options['autopublish']; ?>,
	onChange: vkontakte_comments_save_data
});


function vkontakte_comments_save_data( num, last_comment, date ) {

	var params = {
		action: 'vkcomments_comment_activity',
		num: num,
		last_comment: last_comment,
		date: date
	};

    jQuery.ajax({
        url: '<?php bloginfo( 'wpurl' ); ?>/wp-admin/admin-ajax.php',
        dataType : "json",
        type: "POST",
        data: params
    });

	
}

</script>
   
<?php    
        }
        
        function return_comments() {
            ob_start();
            $this->show_comments();
            $content = ob_get_contents(); 
            ob_end_clean();
            return $content;
        }
        
        function show_options() {
            
        $comments_options = array(
	       '0' => array(
		      'value' => 5,
		      'label' => 5
	       ),
	       '1' => array(
		      'value' => 10,
		      'label' => 10
	       ),
            '2' => array(
		      'value' => 15,
		      'label' => 15
	       ),
	       '3' => array(
		      'value' => 20,
		      'label' => 20
	       )
        );
        
        $plugin_show = array(
	       '0' => array(
		      'value' => 0,
		      'label' => 'Manual'
	       ),
	       '1' => array(
		      'value' => 1,
		      'label' => 'Auto'
	       )
        );
            
    	if ( $_SERVER['REQUEST_METHOD'] == 'POST') {
    		
    		
            $_POST['vkcomments_options']['video'] = isset($_POST['vkcomments_options']['video']) ? 1 : 0;
            $_POST['vkcomments_options']['audio'] = isset($_POST['vkcomments_options']['audio']) ? 1 : 0;
            $_POST['vkcomments_options']['graffity'] = isset($_POST['vkcomments_options']['graffity']) ? 1 : 0;
            $_POST['vkcomments_options']['links'] = isset($_POST['vkcomments_options']['links']) ? 1 : 0;
            $_POST['vkcomments_options']['photos'] = isset($_POST['vkcomments_options']['photos']) ? 1 : 0;
            $_POST['vkcomments_options']['advanced_comments'] = isset($_POST['vkcomments_options']['advanced_comments']) ? 1 : 0; 
            $_POST['vkcomments_options']['autopublish'] = isset($_POST['vkcomments_options']['autopublish']) ? 1 : 0;
            
            
    		update_option( 'vkcomments_options', $_POST['vkcomments_options']);


    	}

            
?>

<div class="wrap">
    
    <div style="background: url(../wp-content/plugins/vkcomments/images/vk.png) 190px 62px no-repeat; min-height: 200px; height: auto !important; height: 200px;">
	<div class="icon32"  id="icon-link-manager"></div>
	<h2>VK Comments &raquo; <?php _e("Options", "vkcomments");?></h2>
    
    	<?php if ( $_SERVER["REQUEST_METHOD"] == 'POST'): ?>
			<div class="updated fade"><p><strong><?php _e( 'Options saved', 'vkcomments' ); ?></strong></p></div>
		<?php endif; ?>
        
		<form method="post" action="">
			<?php $options = get_option( 'vkcomments_options' ); ?>
            
			<table class="form-table">
				<tr valign="top"><th scope="row"><?php _e( 'VKontakte API ID', 'vkcomments' ); ?></th>
					<td>
						<input id="vkcomments_options[api_id]" class="regular-text" type="text" name="vkcomments_options[api_id]" value="<?php esc_attr_e( $options['api_id'] ); ?>" />
						<label class="description" for="vkcomments_options[api_id]"></label>
					</td>
				</tr>
				<tr valign="top"><th scope="row"><?php _e( 'Container width', 'vkcomments' ); ?></th>
					<td>
						<input id="vkcomments_options[width]" class="regular-text" type="text" name="vkcomments_options[width]" value="<?php esc_attr_e( $options['width'] ); ?>" />
						<label class="description" for="vkcomments_options[width]"></label>
					</td>
				</tr>
				<tr valign="top"><th scope="row"><?php _e( 'Show comments form', 'vkcomments' ); ?></th>
					<td>
						<select name="vkcomments_options[plugin_show]">
<?php
								$selected = $options['plugin_show'];
								$p = '';
								$r = '';

								foreach ( $plugin_show as $option ) {
									$label = $option['label'];
									if ( $selected == $option['value'] ) // Make default first in list
										$p = "\n\t<option style=\"padding-right: 10px;\" selected='selected' value='" . esc_attr( $option['value'] ) . "'>" . __($label, 'vkcomments') . "</option>";
									else
										$r .= "\n\t<option style=\"padding-right: 10px;\" value='" . esc_attr( $option['value'] ) . "'>" . __($label, 'vkcomments') . "</option>";
								}
                                echo $p . $r;
?>
						</select>
					</td>
				</tr>
                <tr>
                    <td></td>
                    <td><?php _e('If you selected the manual mode, you need to insert in your template the following code', 'vkcomments'); ?><br /><code>&lt;?php if( class_exists('vkcomments') ) $vkcomments-&gt;show_comments(); ?&gt;</code><br />
                    <?php _e('You can insert comments into the context of each post, using the code', 'vkcomments');?> <code>[vkcomments]</code></td>
                </tr>
				<tr valign="top"><th scope="row"><?php _e( 'Comments count', 'vkcomments' ); ?></th>
					<td>
						<select name="vkcomments_options[comments_count]">
<?php
								$selected = $options['comments_count'];
								$p = '';
								$r = '';

								foreach ( $comments_options as $option ) {
									$label = $option['label'];
									if ( $selected == $option['value'] ) // Make default first in list
										$p = "\n\t<option style=\"padding-right: 10px;\" selected='selected' value='" . esc_attr( $option['value'] ) . "'>" . __($label, 'vkcomments') . "</option>";
									else
										$r .= "\n\t<option style=\"padding-right: 10px;\" value='" . esc_attr( $option['value'] ) . "'>" . __($label, 'vkcomments') . "</option>";
								}
                                echo $p . $r;
?>
						</select>
					</td>
				</tr>
                <tr valign="top"><th scope="row"><?php _e( 'Automatically publish my comments in my VKontakte page', 'vkcomments' ); ?></th>
                    <td>
                        <input id="vkcomments_options[autopublish]" name="vkcomments_options[autopublish]" type="checkbox" value="1" <?php checked( '1', $options['autopublish'] ); ?> />
                    </td>
                </tr>
				<tr valign="top"><th scope="row"><?php _e( 'Allow advanced comments', 'vkcomments' ); ?></th>
					<td>
						<input id="vkcomments_options[advanced_comments]" name="vkcomments_options[advanced_comments]" type="checkbox" value="1" <?php checked( '1', $options['advanced_comments'] ); ?> />
					</td>
				</tr>
				<tr valign="top"><th scope="row"><?php _e( 'Allow graffity', 'vkcomments' ); ?></th>
					<td>
						<input id="vkcomments_options[graffity]" name="vkcomments_options[graffity]" type="checkbox" value="1" <?php checked( '1', $options['graffity'] ); ?> />
					</td>
				</tr>
				<tr valign="top"><th scope="row"><?php _e( 'Allow photos', 'vkcomments' ); ?></th>
					<td>
						<input id="vkcomments_options[photos]" name="vkcomments_options[photos]" type="checkbox" value="1" <?php checked( '1', $options['photos'] ); ?> />
					</td>
				</tr>
				<tr valign="top"><th scope="row"><?php _e( 'Allow video', 'vkcomments' ); ?></th>
					<td>
						<input id="vkcomments_options[video]" name="vkcomments_options[video]" type="checkbox" value="1" <?php checked( '1', $options['video'] ); ?> />
					</td>
				</tr>
				<tr valign="top"><th scope="row"><?php _e( 'Allow audio', 'vkcomments' ); ?></th>
					<td>
						<input id="vkcomments_options[audio]" name="vkcomments_options[audio]" type="checkbox" value="1" <?php checked( '1', $options['audio'] ); ?> />
					</td>
				</tr>
				<tr valign="top"><th scope="row"><?php _e( 'Allow links', 'vkcomments' ); ?></th>
					<td>
						<input id="vkcomments_options[links]" name="vkcomments_options[links]" type="checkbox" value="1" <?php checked( '1', $options['links'] ); ?> />
					</td>
				</tr>
            </table>
            
			<p class="submit">
				<input type="submit" class="button-primary" value="<?php _e( 'Save Options', 'vkcomments' ); ?>" />
			</p>
        </form>
    
    </div>
</div>

<?php
            
        }
       
    }

    global $vkcomments;
    $vkcomments = new vkcomments();
    
    register_activation_hook( __FILE__, $vkcomments->install_plugin());

}