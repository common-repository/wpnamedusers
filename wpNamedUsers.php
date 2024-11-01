<?php
/*
Plugin Name: wpNamedUsers
Plugin URI: http://wordpress.sundskard.dk/archives/category/wpnamedusers
Description: Intranet / Extranet plugin for Wordpress that allows users to specify which users can access specific posts or pages.
Version: 0.5
Author: Andrias Sundskarð
Author URI: http://wordpress.sundskard.dk/

Copyright 2008-2011  Andrias Sundskarð

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
?>
<?php
if (!class_exists("wpNamedUsers")) {
	class wpNamedUsers {
		function wpNamedUsers() {
		}
		
		function activate() {
			global $wpdb;
			$wpdb->show_errors();
			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		
			// create database table for users
			$sqlstring = "CREATE TABLE " . $wpdb->prefix . "named_users (
				id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				user_id BIGINT(20) UNSIGNED,
				group_id BIGINT(20) UNSIGNED,
				post_id BIGINT(20) UNSIGNED NOT NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY (user_id, group_id, post_id)
				)";
			dbDelta( $sqlstring );

			// create database table for groups
			$sqlstring = "CREATE TABLE " . $wpdb->prefix . "named_users_groups (
				id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				group_name VARCHAR(250) NOT NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY (group_name)
				)";
			dbDelta( $sqlstring );

			// create database table for relation between users and groups
			$sqlstring = "CREATE TABLE " . $wpdb->prefix . "named_users_groups_relations (
				group_id BIGINT(20) UNSIGNED NOT NULL,
				user_id BIGINT(20) UNSIGNED NOT NULL,
				UNIQUE KEY (group_id, user_id)
				)";
			dbDelta( $sqlstring );
		}
		
		function deactivate() {
			//global $wpdb;
			//$wpdb->show_errors();
		
			// drop database tablea
			//$sqlstring = "DROP TABLE IF EXISTS " . $wpdb->prefix . "named_users";
			//$wpdb->query( $sqlstring );
			//$sqlstring = "DROP TABLE IF EXISTS " . $wpdb->prefix . "named_users_groups";
			//$wpdb->query( $sqlstring );
			//$sqlstring = "DROP TABLE IF EXISTS " . $wpdb->prefix . "named_users_groups_relation";
			//$wpdb->query( $sqlstring );
		}
		
		function init() {
			load_plugin_textdomain('wpnamedusers', PLUGINDIR . '/' . dirname(plugin_basename(__FILE__)));
		}
		
		function admin_menu() {
			// add options link for users with user level 2 or higher
			add_options_page(__('wpNamedUsers permissions', 'wpnamedusers'), __('wpNamedUsers permissions', 'wpnamedusers'), 2, 'wpNamedUsers-Permissions', array(&$this, 'permissions_page'));
			add_options_page(__('wpNamedUsers groups', 'wpnamedusers'), __('wpNamedUsers groups', 'wpnamedusers'), 2, 'wpNamedUsers-Groups', array(&$this, 'groups_page'));
		
			// add meta boxes to posts and pages
			add_meta_box(__('wpNamedUsers - Users', 'wpnamedusers'), __('wpNamedUsers - Users', 'wpnamedusers'), array(&$this, 'add_meta_box_users'), 'post', 'normal');
			add_meta_box(__('wpNamedUsers - Users', 'wpnamedusers'), __('wpNamedUsers - Users', 'wpnamedusers'), array(&$this, 'add_meta_box_users'), 'page', 'normal');
			add_meta_box(__('wpNamedUsers - Groups', 'wpnamedusers'), __('wpNamedUsers - Groups', 'wpnamedusers'), array(&$this, 'add_meta_box_groups'), 'post', 'normal');
			add_meta_box(__('wpNamedUsers - Groups', 'wpnamedusers'), __('wpNamedUsers - Groups', 'wpnamedusers'), array(&$this, 'add_meta_box_groups'), 'page', 'normal');
		}

		function add_hyphens( $pages ) {
			$page_parent = 0;
			$prefix = '';
			$arr_parents = array();
			foreach( $pages as $page ) {
				if ( !empty( $page->post_parent ) ) {
					if ( $page_parent != $page->post_parent )
						if ( array_key_exists( 'id' . $page->post_parent,$arr_parents ) ) {
							$prefix = substr( $prefix,2 );
						}
						else {
							$prefix = '- ' . $prefix;
							$arr_parents['id' . $page->post_parent] = $page->post_parent;
						}
					$page->post_title = $prefix . $page->post_title;
					$page_parent = $page->post_parent;
				}
				else {
					$prefix = '';
				}
			}
		}

		function permissions_page() {
			global $wpdb;
			$wpdb->show_errors();
			global $current_user;
	
			if ( !empty( $_POST['set'] ) ) {
				$wpNamedUsers_posts = $_POST['wpNamedUsers_posts'];
				$wpNamedUsers_pages = $_POST['wpNamedUsers_pages'];
				$wpNamedUsers_users = $_POST['wpNamedUsers_users'];
				$wpNamedUsers_groups = $_POST['wpNamedUsers_groups'];
	
				if ( ( !empty( $wpNamedUsers_posts ) || !empty( $wpNamedUsers_pages ) ) && ( !empty( $wpNamedUsers_users ) || !empty( $wpNamedUsers_groups ) ) ) {
					// clear permissions for selected posts / pages for selected users
					$clear_posts = "0";
					$clear_users = "0";
					$clear_groups = "0";
					
					if ( !empty( $wpNamedUsers_posts ) ) {
						foreach ( $wpNamedUsers_posts as $wpNamedUsers_post ) {
							if ( current_user_can( 'edit_post', $wpNamedUsers_post ) )
								$clear_posts .= "," . $wpNamedUsers_post;
						}
					}
	
					if ( !empty( $wpNamedUsers_pages ) ) {
						foreach ( $wpNamedUsers_pages as $wpNamedUsers_page ) {
							if ( current_user_can( 'edit_page', $wpNamedUsers_page ) )
								$clear_posts .= "," . $wpNamedUsers_page;
						}
					}
	
					if ( !empty( $wpNamedUsers_users ) ) {
						foreach ( $wpNamedUsers_users as $wpNamedUsers_user ) {
							$clear_users .= "," . $wpNamedUsers_user;
						}
					}

					if ( !empty( $wpNamedUsers_groups ) ) {
						foreach ( $wpNamedUsers_groups as $wpNamedUsers_group ) {
							$clear_groups .= "," . $wpNamedUsers_group;
						}
					}

					$sqlstring = "DELETE FROM " . $wpdb->prefix . "named_users WHERE " . $wpdb->prefix . "named_users.post_id IN (" . $clear_posts . ")";
					
					$wpdb->query($sqlstring);
	
					if ( !empty( $wpNamedUsers_users ) ) {
						$sqlstring = "INSERT INTO " . $wpdb->prefix . "named_users(" . $wpdb->prefix . "named_users.post_id, " . $wpdb->prefix . "named_users.user_id) VALUES ";
						foreach ( $wpNamedUsers_users as $wpNamedUsers_user) {
							if ( !empty( $wpNamedUsers_posts ) ) {
								foreach ( $wpNamedUsers_posts as $wpNamedUsers_post ) {
									if ( current_user_can( 'edit_post', $wpNamedUsers_post ) )
										$sqlstring .= "(" . $wpNamedUsers_post . "," . $wpNamedUsers_user . "),";
								}
							}
			
							if ( !empty( $wpNamedUsers_pages ) ) {
								foreach ( $wpNamedUsers_pages as $wpNamedUsers_page ) {
									if ( current_user_can( 'edit_page', $wpNamedUsers_page ) )
										$sqlstring .= "(" . $wpNamedUsers_page . "," . $wpNamedUsers_user . "),";
								}
							}
						}
						$sqlstring = substr( $sqlstring, 0, -1 );
					}

					$wpdb->query($sqlstring);

					if ( !empty( $wpNamedUsers_groups ) ) {
						$sqlstring = "INSERT INTO " . $wpdb->prefix . "named_users(" . $wpdb->prefix . "named_users.post_id, " . $wpdb->prefix . "named_users.group_id) VALUES ";
						foreach ( $wpNamedUsers_groups as $wpNamedUsers_group) {
							if ( !empty( $wpNamedUsers_posts ) ) {
								foreach ( $wpNamedUsers_posts as $wpNamedUsers_post ) {
									if ( current_user_can( 'edit_post', $wpNamedUsers_post ) )
										$sqlstring .= "(" . $wpNamedUsers_post . "," . $wpNamedUsers_group . "),";
								}
							}
			
							if ( !empty( $wpNamedUsers_pages ) ) {
								foreach ( $wpNamedUsers_pages as $wpNamedUsers_page ) {
									if ( current_user_can( 'edit_page', $wpNamedUsers_page ) )
										$sqlstring .= "(" . $wpNamedUsers_page . "," . $wpNamedUsers_group . "),";
								}
							}
						}
						$sqlstring = substr( $sqlstring, 0, -1 );
					}

					$wpdb->query($sqlstring);
	
					echo '<div id="message" class="updated fade"><p><strong>' . __('Permissions set.', 'wpnamedusers') . '</strong></p></div>';
				}
			} else if ( !empty( $_POST['clear'] ) ) {
				$wpNamedUsers_posts = $_POST['wpNamedUsers_posts'];
				$wpNamedUsers_pages = $_POST['wpNamedUsers_pages'];
				$wpNamedUsers_users = $_POST['wpNamedUsers_users'];
				$wpNamedUsers_groups = $_POST['wpNamedUsers_groups'];
				
				if ( !empty( $wpNamedUsers_posts ) || !empty( $wpNamedUsers_pages ) || !empty( $wpNamedUsers_users ) || !empty( $wpNamedUsers_groups ) ) {
					if ( !empty( $wpNamedUsers_users ) || !empty( $wpNamedUsers_groups ) ) {
						if ( !empty( $wpNamedUsers_posts ) || !empty( $wpNamedUsers_pages ) ) {
							// clear permissions for selected posts / pages for selected users
							$clear_posts = "0";
							$clear_users = "0";
							$clear_groups = "0";
							
							if ( !empty( $wpNamedUsers_posts ) ) {
								foreach ( $wpNamedUsers_posts as $wpNamedUsers_post ) {
									if ( current_user_can( 'edit_post', $wpNamedUsers_post ) )
										$clear_posts .= "," . $wpNamedUsers_post;
								}
							}
			
							if ( !empty( $wpNamedUsers_pages ) ) {
								foreach ( $wpNamedUsers_pages as $wpNamedUsers_page ) {
									if ( current_user_can( 'edit_page', $wpNamedUsers_page ) )
										$clear_posts .= "," . $wpNamedUsers_page;
								}
							}
							
							if ( !empty( $wpNamedUsers_users ) ) {
								foreach ( $wpNamedUsers_users as $wpNamedUsers_user ) {
									$clear_users .= "," . $wpNamedUsers_user;
								}
							}
		
							if ( !empty( $wpNamedUsers_groups ) ) {
								foreach ( $wpNamedUsers_groups as $wpNamedUsers_group ) {
									$clear_groups .= "," . $wpNamedUsers_group;
								}
							}

							$sqlstring = "DELETE FROM " . $wpdb->prefix . "named_users WHERE " . $wpdb->prefix . "named_users.post_id IN (" . $clear_posts . ") AND (" . $wpdb->prefix . "named_users.user_id IN (" . $clear_users . ") OR " . $wpdb->prefix . "named_users.group_id IN (" . $clear_groups . "))";
							$wpdb->query($sqlstring);
						} else {
							// clear permissions for selected users
							$allowed_ids = "0";
							$clear_users = "0";
							$clear_groups = "0";
			
							$posts = get_posts();
							$pages = get_pages();
							
							foreach ( $posts as $post ) {
								if ( current_user_can( 'edit_post', $post->ID ) )
									$allowed_ids .= "," . $post->ID;
							}
							
							foreach ( $pages as $page ) {
								if ( current_user_can( 'edit_page', $page->ID ) )
									$allowed_ids .= "," . $page->ID;
							}
								
							if ( !empty( $wpNamedUsers_users ) ) {
								foreach ( $wpNamedUsers_users as $wpNamedUsers_user ) {
									$clear_users .= "," . $wpNamedUsers_user;
								}
							}
		
							if ( !empty( $wpNamedUsers_groups ) ) {
								foreach ( $wpNamedUsers_groups as $wpNamedUsers_group ) {
									$clear_groups .= "," . $wpNamedUsers_group;
								}
							}
		
							$sqlstring = "DELETE FROM " . $wpdb->prefix . "named_users WHERE " . $wpdb->prefix . "named_users.post_id IN (" . $allowed_ids . ") AND (" . $wpdb->prefix . "named_users.user_id IN (" . $clear_users . ") OR " . $wpdb->prefix . "named_users.group_id IN (" . $clear_groups . "))";
							$wpdb->query($sqlstring);
						}
					} else {
						// clear permissions for selected posts / pages
						$clear = "0";
						
						if ( !empty( $wpNamedUsers_posts ) ) {
							foreach ( $wpNamedUsers_posts as $wpNamedUsers_post ) {
								if ( current_user_can( 'edit_post', $wpNamedUsers_post ) )
									$clear .= "," . $wpNamedUsers_post;
							}
						}
		
						if ( !empty( $wpNamedUsers_pages ) ) {
							foreach ( $wpNamedUsers_pages as $wpNamedUsers_page ) {
								if ( current_user_can( 'edit_page', $wpNamedUsers_page ) )
									$clear .= "," . $wpNamedUsers_page;
							}
						}
		
						$sqlstring = "DELETE FROM " . $wpdb->prefix . "named_users WHERE " . $wpdb->prefix . "named_users.post_id IN (" . $clear . ")";
						$wpdb->query($sqlstring);
					}
					echo '<div id="message" class="updated fade"><p><strong>' . __('Permissions cleared.', 'wpnamedusers') . '</strong></p></div>';
				}
			} else if ( !empty( $_POST['copy'] ) ) {
				if ( $current_user->user_level > 7 ) {
					$source_user = $_POST['wpNamedUsers_source_user'];
					$target_user = $_POST['wpNamedUsers_target_user'];
					if ( !empty( $source_user ) && !empty( $target_user ) ) {
						if ( $source_user != $target_user ) {
							// clear current permissions
							$sqlstring = "DELETE FROM " . $wpdb->prefix . "named_users WHERE " . $wpdb->prefix . "named_users.user_id = " . $target_user;
							$wpdb->query( $sqlstring );
							// copy permissions
							$sqlstring = "INSERT INTO " . $wpdb->prefix . "named_users(" . $wpdb->prefix . "named_users.user_id, " . $wpdb->prefix . "named_users.post_id)
								SELECT '" . $target_user . "', " . $wpdb->prefix . "named_users.post_id
								FROM " . $wpdb->prefix . "named_users
								WHERE " . $wpdb->prefix . "named_users.user_id = " . $source_user;
							$wpdb->query( $sqlstring );
							echo '<div id="message" class="updated fade"><p><strong>' . __('Permissions copied.', 'wpnamedusers') . '</strong></p></div>';
						}
					}
				}
			}
	
			$posts = get_posts('numberposts=-1');
			$pages = get_pages();
			$this->add_hyphens($pages);
			$users = $wpdb->get_results("SELECT * FROM $wpdb->users ORDER BY user_nicename");
			$groups = $wpdb->get_results( "SELECT * FROM " . $wpdb->prefix . "named_users_groups ORDER BY group_name" );
?>
			<div class="wrap">
				<form method="post" action="">
					<input type="hidden" name="wpNamedUsers_noncename" id="wpNamedUsers_noncename" value="<?php echo wp_create_nonce( plugin_basename( __FILE__ ) ) ?>" />
					<h2><?php _e('wpNamedUsers permissions', 'wpnamedusers') ?></h2>
					<h3><?php _e('Set / Clear permissions', 'wpnamedusers') ?></h3>
					<table class="form-table" cellpadding="2" cellspacing="2" width="100%">
						<tr>
							<th width="35%"><label for="wpNamedUsers_posts"><?php _e('Posts', 'wpnamedusers') ?></label></th>
							<th width="35%"><label for="wpNamedUsers_pages"><?php _e('Pages', 'wpnamedusers') ?></label></th>
							<th width="30%"><label for="wpNamedUsers_users"><?php _e('Users', 'wpnamedusers') ?></label> / <label for="wpNamedUsers_groups"><?php _e('Groups', 'wpnamedusers') ?></label></th>
						</tr>
						<tr>
							<td>
								<select name="wpNamedUsers_posts[]" id="wpNamedUsers_posts" size="15" multiple="multiple" style="width: 100%; height: 300px;">
<?php
			foreach( $posts as $post ) {
				if ( current_user_can( 'edit_post', $post->ID ) ) {
?>
									<option value="<?php echo $post->ID ?>"><?php echo $post->post_title ?></option>
<?php
				}
			}
?>
								</select>
							</td>
							<td>
								<select name="wpNamedUsers_pages[]" id="wpNamedUsers_pages" size="15" multiple="multiple" style="width: 100%; height: 300px;">
<?php
			foreach( $pages as $page ) {
				if ( current_user_can( 'edit_page', $page->ID ) ) {
?>
									<option value="<?php echo $page->ID ?>"><?php echo $page->post_title ?></option>
<?php
				}
			}
?>
								</select>
							</td>
							<td>
								<select name="wpNamedUsers_users[]" id="wpNamedUsers_users" size="15" multiple="multiple" style="width: 100%; height: 150px;">
<?php
			foreach( $users as $user ) {
?>
									<option value="<?php echo $user->ID ?>"><?php echo $user->display_name ?> (<?php echo $user->user_login ?>)</option>
<?php
			}
?>
								</select>
								<select name="wpNamedUsers_groups[]" id="wpNamedUsers_groups" size="15" multiple="multiple" style="width: 100%; height: 150px;">
<?php
			foreach( $groups as $group ) {
?>
									<option value="<?php echo $group->id ?>"><?php echo $group->group_name ?></option>
<?php
			}
?>
								</select>
							</td>
						</tr>
					</table>
					<p><?php _e('You can only set / clear permissions on posts / pages that you have the right to edit. If you want to clear all permissions for a specific user, you can login as administrator, select the user and click "Clear permissions".', 'wpnamedusers') ?></p>
					<p class="submit">
						<input type="submit" name="set" class="button-primary save" value="<?php _e('Set permissions', 'wpnamedusers') ?>" />
						<input type="submit" name="clear" class="button-secondary save" value="<?php _e('Clear permissions', 'wpnamedusers') ?>" />
					</p>
<?php if ( $current_user->user_level > 7 ) { ?>
					<h3><?php _e('Copy permissions', 'wpnamedusers') ?></h3>
					<table class="form-table" cellpadding="2" cellspacing="2" width="100%">
						<tr>
							<th width="30%"><label for="wpNamedUsers_source_user"><?php _e('Source user', 'wpnamedusers') ?></label></th>
							<th width="30%"><label for="wpNamedUsers_target_user"><?php _e('Target user', 'wpnamedusers') ?></label></th>
							<th width="40%"></th>
						</tr>
						<tr>
							<td>
								<select name="wpNamedUsers_source_user" id="wpNamedUsers_source_user">
<?php
			foreach( $users as $user ) {
?>
									<option value="<?php echo $user->ID ?>"><?php echo $user->display_name ?> (<?php echo $user->user_login ?>)</option>
<?php
			}
?>
								</select>
							</td>
							<td>
								<select name="wpNamedUsers_target_user" id="wpNamedUsers_target_user">
<?php
			foreach( $users as $user ) {
?>
									<option value="<?php echo $user->ID ?>"><?php echo $user->display_name ?> (<?php echo $user->user_login ?>)</option>
<?php
			}
?>
								</select>
							</td>
							<td></td>
						</tr>
					</table>
					<p class="submit">
						<input type="submit" name="copy" class="button-primary save" value="<?php _e('Copy permissions', 'wpnamedusers') ?>" />
					</p>
				<?php } ?>
				</form>
			</div>
<?php
		}

		function groups_page() {
			global $wpdb;
			$wpdb->show_errors();
			global $current_user;

			if ( !empty( $_POST['create_group'] ) ) {
				$wpNamedUsers_group_name = $_POST['wpNamedUsers_group_name'];
	
				if ( !empty( $wpNamedUsers_group_name ) ) {
					$sqlstring = "INSERT INTO " . $wpdb->prefix . "named_users_groups(group_name) VALUES('" . $wpNamedUsers_group_name . "')";
					$wpdb->query($sqlstring);
					echo '<div id="message" class="updated fade"><p><strong>' . __('Group created.', 'wpnamedusers') . '</strong></p></div>';
				}
			} else if ( !empty( $_POST['show_group'] ) ) {
				$wpNamedUsers_groups = $_POST['wpNamedUsers_groups'];

				if ( !empty( $wpNamedUsers_groups ) ) {
					$selected_users = $wpdb->get_col( "SELECT user_id FROM " . $wpdb->prefix . "named_users_groups_relations WHERE group_id = " . $wpNamedUsers_groups . "" );
				}
			} else if ( !empty( $_POST['update_group'] ) ) {
				$wpNamedUsers_groups = $_POST['wpNamedUsers_groups'];
				$wpNamedUsers_users = $_POST['wpNamedUsers_users'];

				if ( !empty( $wpNamedUsers_groups ) && !empty( $wpNamedUsers_users ) ) {
					$sqlstring = "DELETE FROM " . $wpdb->prefix . "named_users_groups_relations WHERE group_id = " . $wpNamedUsers_groups . "";
					$wpdb->query($sqlstring);
					$sqlstring = "INSERT INTO " . $wpdb->prefix . "named_users_groups_relations(group_id, user_id) VALUES";
					foreach ( $wpNamedUsers_users as $wpNamedUsers_user ) {
						$sqlstring .= "(" . $wpNamedUsers_groups . "," . $wpNamedUsers_user . "),";
					}
					$sqlstring = substr($sqlstring, 0, -1);
					$wpdb->query($sqlstring);
					echo '<div id="message" class="updated fade"><p><strong>' . __('Group members updated.', 'wpnamedusers') . '</strong></p></div>';
				}
			} else if ( !empty( $_POST['delete_group'] ) ) {
				$wpNamedUsers_groups = $_POST['wpNamedUsers_groups'];
	
				if ( !empty( $wpNamedUsers_groups ) ) {
					$sqlstring = "DELETE FROM " . $wpdb->prefix . "named_users_groups_relations WHERE group_id = " . $wpNamedUsers_groups . "";
					$wpdb->query($sqlstring);
					$sqlstring = "DELETE FROM " . $wpdb->prefix . "named_users_groups WHERE id = " . $wpNamedUsers_groups . "";
					$wpdb->query($sqlstring);
					if (get_option('wpNamedUsers_groups_default') == $wpNamedUsers_groups) {
						update_option( 'wpNamedUsers_groups_default', '0' );
					}
					echo '<div id="message" class="updated fade"><p><strong>' . __('Group deleted.', 'wpnamedusers') . '</strong></p></div>';
				}
			} else if ( !empty( $_POST['default_group'] ) ) {
				$wpNamedUsers_groups_default = $_POST['wpNamedUsers_groups_default'];
	
				update_option( 'wpNamedUsers_groups_default', $wpNamedUsers_groups_default );
				echo '<div id="message" class="updated fade"><p><strong>' . __('Default group updated.', 'wpnamedusers') . '</strong></p></div>';
			}

			$users = $wpdb->get_results( "SELECT * FROM $wpdb->users ORDER BY user_nicename" );
			$groups = $wpdb->get_results( "SELECT * FROM " . $wpdb->prefix . "named_users_groups ORDER BY group_name" );
?>
			<div class="wrap">
				<form method="post" action="">
					<input type="hidden" name="wpNamedUsers_noncename" id="wpNamedUsers_noncename" value="<?php echo wp_create_nonce( plugin_basename( __FILE__ ) ) ?>" />
					<h2><?php _e('wpNamedUsers groups', 'wpnamedusers') ?></h2>
					<h3><?php _e('Create group', 'wpnamedusers') ?></h3>
					<table class="form-table" cellpadding="2" cellspacing="2" width="100%">
						<tr>
							<th width="20%"><label for="wpNamedUsers_group_name"><?php _e('Group name', 'wpnamedusers') ?></label></th>
							<td width="80%"><input name="wpNamedUsers_group_name" id="wpNamedUsers_group_name" size="250" style="width: 50%;"></td>
						</tr>
					</table>
					<p class="submit">
						<input type="submit" name="create_group" class="button-primary save" value="<?php _e('Create group', 'wpnamedusers') ?>" />
					</p>
					<h3><?php _e('Manage groups', 'wpnamedusers') ?></h3>
                    <p><?php _e('To assign users to a group, select the group in the list of groups and then select the users that should be assigned to that group. Press "Update group members" to save.', 'wpnamedusers') ?></p>
                    <p><?php _e('If you want to check which users are assigned to a group, select the group in the list and press "Show group members".', 'wpnamedusers') ?></p>
					<table class="form-table" cellpadding="2" cellspacing="2" width="100%">
						<tr>
							<th width="50%"><label for="wpNamedUsers_groups"><?php _e('Groups', 'wpnamedusers') ?></label></th>
							<th width="50%"><label for="wpNamedUsers_users"><?php _e('Users', 'wpnamedusers') ?></label></th>
						</tr>
						<tr>
							<td width="50%">
								<select name="wpNamedUsers_groups" id="wpNamedUsers_groups" size="15" style="width: 100%; height: 300px;">
<?php
			foreach( $groups as $group ) {
?>
									<option value="<?php echo $group->id ?>"<?php if ( !empty( $_POST['show_group']) && ( $group->id == $wpNamedUsers_groups ) ) { ?> selected="selected"<?php } ?>><?php echo $group->group_name ?></option>
<?php
			}
?>
								</select>
                            </td>
							<td width="50%">
								<select name="wpNamedUsers_users[]" id="wpNamedUsers_users" size="15" multiple="multiple" style="width: 100%; height: 300px;">
<?php
			foreach( $users as $user ) {
?>
									<option value="<?php echo $user->ID ?>"<?php if ( !empty( $selected_users ) ) if ( in_array( $user->ID, $selected_users) ) { ?> selected="selected"<?php } ?>><?php echo $user->display_name ?> (<?php echo $user->user_login ?>)</option>
<?php
			}
?>
								</select>
                            </td>
						</tr>
					</table>
					<p class="submit">
						<input type="submit" name="show_group" class="button-secondary save" value="<?php _e('Show group members', 'wpnamedusers') ?>" />
						<input type="submit" name="update_group" class="button-primary save" value="<?php _e('Update group members', 'wpnamedusers') ?>" />
						<input type="submit" name="delete_group" class="button-secondary save" value="<?php _e('Delete group', 'wpnamedusers') ?>" />
					</p>
					<h3><?php _e('Default group', 'wpnamedusers') ?></h3>
                    <p><?php _e('If you wish, you can choose a group, where every new user will be a member of.', 'wpnamedusers') ?></p>
					<table class="form-table" cellpadding="2" cellspacing="2" width="100%">
						<tr>
							<th width="20%"><label for="wpNamedUsers_group_default"><?php _e('Default group', 'wpnamedusers') ?></label></th>
							<td width="80%">
								<select name="wpNamedUsers_groups_default" id="wpNamedUsers_groups_default" style="width: 50%;">
									<option value="0"><?php _e('- - - NONE - - -', 'wpnamedusers') ?></option>
<?php
			foreach( $groups as $group ) {
?>
									<option value="<?php echo $group->id ?>"<?php if ( $group->id == get_option('wpNamedUsers_groups_default') ) { ?> selected="selected"<?php } ?>><?php echo $group->group_name ?></option>
<?php
			}
?>
								</select>
							</td>
						</tr>
					</table>
					<p class="submit">
						<input type="submit" name="default_group" class="button-primary save" value="<?php _e('Set default group', 'wpnamedusers') ?>" />
					</p>
				</form>
			</div>
<?php
		}

		function add_meta_box_users() {
			global $wpdb;
			$wpdb->show_errors();
			global $post;
		
			echo '<input type="hidden" name="wpNamedUsers_noncename" id="wpNamedUsers_noncename" value="' . wp_create_nonce( plugin_basename( __FILE__ ) ) . '" />';
			echo '<p>' . __('Select the users that can access the content. If no users or groups are selected, the post / page will be public. Users with permissions to edit a post / page will be able to access the content even if they are not selected in the list.', 'wpnamedusers') . '</p>';
			// show list of users
			$users = $wpdb->get_results("SELECT * FROM $wpdb->users ORDER BY user_nicename");
			$allowed_users = $wpdb->get_col("SELECT DISTINCT(user_id) FROM " . $wpdb->prefix . "named_users WHERE post_id = " . $post->ID);
	
			foreach( $users as $user ) {
				echo '<p><label class="selectit">';
				echo '<input type="checkbox" name="wpNamedUsers_users[]" id="wpNamedUsers_users" value="' . $user->ID . '"';
				// tick checkbox if user has access to this content
				foreach( $allowed_users as $allowed_user ) {
					if ( $user->ID == $allowed_user ) {
						echo ' checked="checked"';
						break;
					}
				}
				echo ' /> ';
				echo $user->display_name . ' (' . $user->user_login . ')</label></p>';
			}
		}

		function add_meta_box_groups() {
			global $wpdb;
			$wpdb->show_errors();
			global $post;
		
			echo '<p>' . __('Select the groups that can access the content. If no groups or users are selected, the post / page will be public. Users with permissions to edit a post / page will be able to access the content even if they are not selected in the list.', 'wpnamedusers') . '</p>';
			// show list of groups
			$groups = $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . "named_users_groups ORDER BY group_name");
			$allowed_groups = $wpdb->get_col("SELECT DISTINCT(group_id) FROM " . $wpdb->prefix . "named_users WHERE post_id = " . $post->ID);
	
			foreach( $groups as $group ) {
				echo '<p><label class="selectit">';
				echo '<input type="checkbox" name="wpNamedUsers_groups[]" id="wpNamedUsers_groups" value="' . $group->id . '"';
				// tick checkbox if user has access to this content
				foreach( $allowed_groups as $allowed_group ) {
					if ( $group->id == $allowed_group ) {
						echo ' checked="checked"';
						break;
					}
				}
				echo ' /> ';
				echo $group->group_name . '</label></p>';
			}
		}

		function save_post( $post_id, $post ) {
			global $wpdb;
			$wpdb->show_errors();
		
			if ( !wp_verify_nonce( $_POST['wpNamedUsers_noncename'], plugin_basename(__FILE__) ) ) {
				return array( $post_id, $post );
			}
		
			if ( 'revision' == $post->post_type ) {
				return array( $post_id, $post );
			} elseif ( 'page' == $post->post_type ) {
				if ( !current_user_can( 'edit_page', $post_id ) )
					return array( $post_id, $post );
			} else {
				if ( !current_user_can( 'edit_post', $post_id ) )
					return array( $post_id, $post );
			}
		
			$users = $_POST['wpNamedUsers_users'];
			$groups = $_POST['wpNamedUsers_groups'];
		
			// delete current permissions
			$sqlstring = "DELETE FROM " . $wpdb->prefix . "named_users WHERE " . $wpdb->prefix . "named_users.post_id = " . $post_id;
			$wpdb->query( $sqlstring );
		
			// if users are selected
			if ( !empty( $users ) ) {
				// set permissions
				$sqlstring = "INSERT INTO " . $wpdb->prefix . "named_users(" . $wpdb->prefix . "named_users.user_id, " . $wpdb->prefix . "named_users.post_id) VALUES";
				// loop through list of selected users
				foreach( $users as $user ) {
					$sqlstring = $sqlstring . "(" . $user . ", " . $post_id . "),";
				}
				// remove last comma from sqlstring
				$sqlstring = substr( $sqlstring, 0, -1 );
				$wpdb->query( $sqlstring );
			}

			if ( !empty( $groups ) ) {
				// set permissions
				$sqlstring = "INSERT INTO " . $wpdb->prefix . "named_users(" . $wpdb->prefix . "named_users.group_id, " . $wpdb->prefix . "named_users.post_id) VALUES";
				// loop through list of selected users
				foreach( $groups as $group ) {
					$sqlstring = $sqlstring . "(" . $group . ", " . $post_id . "),";
				}
				// remove last comma from sqlstring
				$sqlstring = substr( $sqlstring, 0, -1 );
				$wpdb->query( $sqlstring );
			}
		
			return array( $post_id, $post );
		}

		function delete_post( $post_id ) {
			global $wpdb;
			$wpdb->show_errors();
		
			// delete permissions for deleted post
			$sqlstring = "DELETE FROM " . $wpdb->prefix . "named_users WHERE " . $wpdb->prefix . "named_users.post_id = " . $post_id;
			$wpdb->query( $sqlstring );
		
			return $post_id;
		}

		function user_register( $user_id ) {
			global $wpdb;
			$wpdb->show_errors();
		
			// add user to default group
			if (get_option('wpNamedUsers_groups_default') != 0) {
					$sqlstring = "INSERT INTO " . $wpdb->prefix . "named_users_groups_relations(group_id, user_id) VALUES(" . get_option('wpNamedUsers_groups_default') . ", " . $user_id . ")";
					$wpdb->query($sqlstring);
			}

			return $user_id;
		}

		function delete_user( $user_id ) {
			global $wpdb;
			$wpdb->show_errors();
		
			// delete permissions for deleted user
			$sqlstring = "DELETE FROM " . $wpdb->prefix . "named_users WHERE " . $wpdb->prefix . "named_users.user_id = " . $user_id;
			$wpdb->query( $sqlstring );
			// delete group relations for deleted user
			$sqlstring = "DELETE FROM " . $wpdb->prefix . "named_users_groups_relations WHERE " . $wpdb->prefix . "named_users_groups_relations.user_id = " . $user_id;
			$wpdb->query( $sqlstring );
		
			return $user_id;
		}

		function wp_list_pages_excludes( $excludes ) {
			global $wpdb;
			$wpdb->show_errors();
			global $current_user;
			
			// return list of excluded pages
			$protected = $wpdb->get_col("SELECT DISTINCT(" . $wpdb->prefix . "named_users.post_id)
				FROM " . $wpdb->prefix . "named_users
				WHERE " . $wpdb->prefix . "named_users.post_id NOT IN (
				SELECT " . $wpdb->prefix . "named_users.post_id
				FROM " . $wpdb->prefix . "named_users
				WHERE " . $wpdb->prefix . "named_users.user_id = " . $current_user->ID . "
				OR " . $wpdb->prefix . "named_users.group_id IN (
					SELECT " . $wpdb->prefix . "named_users_groups_relations.group_id
					FROM " . $wpdb->prefix . "named_users_groups_relations
					WHERE " . $wpdb->prefix . "named_users_groups_relations.user_id = " . $current_user->ID . ")
				)");
			
			$exclude_list = array();
			$i = 0;
		
			if ( !empty( $excludes ) ) {
				foreach ( $excludes as $exclude ) {
					$exclude_list[$i++] = $exclude;
				}
			}
			

			// remove protected posts/pages that current user can edit
			foreach ( $protected as $protect ) {
				if ( ( !current_user_can( 'edit_page', $protect ) ) && ( !current_user_can( 'edit_post', $protect ) ) )
					$exclude_list[$i++] = $protect;
			}

			return $exclude_list;
		}

		function posts_where( $where ) {
			global $wpdb;
			$wpdb->show_errors();
			global $current_user;
		
			$allowed_ids = "0";
			
			$posts = get_posts();
			$pages = get_pages();
		
			foreach ( $posts as $post ) {
				if ( current_user_can( 'edit_post', $post->ID ) )
					$allowed_ids .= "," . $post->ID;
			}
		
			foreach ( $pages as $page ) {
				if ( current_user_can( 'edit_page', $page->ID ) )
					$allowed_ids .= "," . $page->ID;
			}
		
			// hide content from users
			$where = $where . " AND $wpdb->posts.ID NOT IN (
				SELECT nu1.post_id
					FROM " . $wpdb->prefix . "named_users AS nu1 LEFT JOIN (
						SELECT nu2.post_id
						FROM " . $wpdb->prefix . "named_users AS nu2, " . $wpdb->prefix . "named_users_groups_relations
						WHERE nu2.user_id = " . $current_user->ID . "
						OR (nu2.group_id = " . $wpdb->prefix . "named_users_groups_relations.group_id
						AND " . $wpdb->prefix . "named_users_groups_relations.user_id = " . $current_user->ID . ")
					) AS nu2 ON nu1.post_id = nu2.post_id
					WHERE nu2.post_id IS NULL 
					AND nu1.post_id NOT IN (" . $allowed_ids . ")
				)";

			return $where;
		}
	}
}

if (class_exists("wpNamedUsers")) {
	$wpNamedUsers = new wpNamedUsers();
}

if (isset($wpNamedUsers)) {
	register_activation_hook(__FILE__, array(&$wpNamedUsers, 'activate'));
	register_deactivation_hook(__FILE__, array(&$wpNamedUsers, 'deactivate'));
	
	add_action('init', array(&$wpNamedUsers, 'init'));

	add_action('admin_menu', array(&$wpNamedUsers, 'admin_menu'));
	add_action('save_post', array(&$wpNamedUsers, 'save_post'), 1, 2);
	add_action('delete_post', array(&$wpNamedUsers, 'delete_post'));
	add_action('user_register', array(&$wpNamedUsers, 'user_register'));
	add_action('delete_user', array(&$wpNamedUsers, 'delete_user'));
	
	add_filter('wp_list_pages_excludes', array(&$wpNamedUsers, 'wp_list_pages_excludes'));
	add_filter('posts_where', array(&$wpNamedUsers, 'posts_where'));
}
?>
