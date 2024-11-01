<?php
/*
 * @desc: Add ticket menu to end-user Dashboard
 * @vers: 1.0
 * @updated 1.5
 */
add_action( 'admin_menu', 'iew_ticket_menu' );
function iew_ticket_menu(){
	$iewusermenu = add_menu_page( __( 'Online Support', 'iewticket' ), __( 'Support', 'iewticket') , 'manage_options', 'iew-help-desk', 'iew_help_menu_page', plugins_url( 'img/iew-icon.gif', __FILE__ ) );
	add_action( 'admin_print_scripts-'. $iewusermenu, 'iew_ticket_header' );
	add_submenu_page( 'iew-help-desk', __( 'New Ticket', 'iewticket' ), __( 'New Ticket', 'iewticket') , 'manage_options', 'iew-new-ticket', 'iew_new_ticket_page', plugins_url( 'img/iew-icon.gif', __FILE__ ) );
	
	global $menu, $wpdb, $current_user;
	get_currentuserinfo();

	$tcktdb = get_site_option( 'iew_ticket_table' );
	$uid = $current_user->ID;
	$kac = $wpdb->get_var("SELECT COUNT(*) FROM {$tcktdb} WHERE uid={$uid} AND status=1");
	if($kac > 0){
		$find = array_searchRecursive( 'iew-help-desk', $menu );
		$morder = $find[0];
		$mymenu = $menu[$morder];
		$mtitle = sprintf( __('Support %s', 'iewticket'), "<span class='awaiting-mod count-$kac'><span class='pending-count'>" . number_format_i18n($kac) . "</span></span>" );
		$menu[$morder] = array( $mtitle, $mymenu[1], $mymenu[2], $mymenu[3], $mymenu[4], $mymenu[5], $mymenu[6] );
	}
	return $menu;
}

/*
 * !_default_page_for_user_! *
 */
function iew_help_menu_page(){
	$do = ( isset($_GET['do']) ) ? $_GET['do'] : 'default';
	switch($do){
		case 'default':
		default:
			_iew_user_ticket_list();
		break;
		
		case 'show':
			_iew_user_show_ticket();
		break;
		
		case 'send':
			_iew_user_send_reply();
		break;
		
		case 'mark':
			_iew_user_mark_as_solved();
		break;
	}
}

function _iew_user_ticket_list(){
	global $wpdb, $usrurl;
	$cruser = wp_get_current_user();
	$msgsdb = get_site_option( 'iew_msg_table' );
	$tcktdb = get_site_option( 'iew_ticket_table' );
?>
	<div class="wrap">
		<h2><?php _e( 'All Tickets', 'iewticket' ); ?></h2>
		
		<table class="widefat" id="referrerDetectorEntries">
			<thead>
				<tr align="center">
					<th><?php _e( 'Subject', 'iewticket' ); ?></th>
					<th style="text-align:center"><?php _e( 'Opening Date', 'iewticket' ); ?></th>
					<th style="text-align:center"><?php _e( 'Last Update', 'iewticket' ); ?></th>
					<th style="text-align:center"><?php _e( 'Status', 'iewticket' ); ?></th>
					<th style="text-align:center"><?php _e( 'Actions', 'iewticket' ); ?></th>
				</tr>
			</thead>
				
			<tbody>
<?php
$total_records = $wpdb->get_var("SELECT COUNT(*) FROM {$tcktdb} WHERE uid=".$cruser->ID);
$scroll_page = 3; // kaydırılacak sayfa sayısı
$per_page = 15; // her sayafa gösterilecek sayfa sayısı
$current_page = $_GET['s']; // bulunulan sayfa
$pager_url = 'admin.php?page=iew-help-desk&s='; // sayfalamanın yapıldığı adres
$inactive_page_tag = 'class="page-numbers"'; // aktif olmayan sayfa linki için biçim
$previous_page_text = '&lt;'; // önceki sayfa metni (resim de olabilir <img src="... gibi)
$next_page_text = '&gt;'; // sonraki sayfa metni (resim de olabilir <img src="... gibi)
$first_page_text = '&lt;&lt;'; // ilk sayfa metni (resim de olabilir <img src="... gibi)
$last_page_text = '&gt;&gt;'; // son sayfa metni (resim de olabilir <img src="... gibi)
$pager_url_last = ''; // sayfalama linkinde sayfa sayısından sonra gelecek karakter (bol olabilir).

$kgPagerOBJ = & new iewPager();
$kgPagerOBJ -> pager_set($pager_url, $total_records, $scroll_page, $per_page, $current_page, $inactive_page_tag, $previous_page_text, $next_page_text, $first_page_text, $last_page_text, $pager_url_last);

if($current_page == "") { $current_page = "1"; }
$ksay = (($current_page - 1) * $per_page)+1;
$bsay = ($current_page*$per_page);

echo '<div class="tablenav"><div class="alignleft actions"><div class="tablenav-pages"><span class="displaying-num">'.sprintf(__('%1$d records showing in %2$d of %3$d', 'iewticket'), $total_records, $ksay, $bsay).'</span>';
echo $kgPagerOBJ -> first_page;
echo $kgPagerOBJ -> previous_page;
echo $kgPagerOBJ -> page_links;
echo $kgPagerOBJ -> next_page;
echo $kgPagerOBJ -> last_page;
echo '</div></div></div>';

	$tickets = $wpdb->get_results("SELECT * FROM {$tcktdb} WHERE uid = {$cruser->ID} ORDER BY pubdate DESC LIMIT ".$kgPagerOBJ -> start.", ".$kgPagerOBJ -> per_page);
	foreach($tickets as $tip => $ticket) {
		$class = ($tip % 2) ? 'background:#eee;' : 'background:#fff;';
		$status = $ticket->status;
		if($status == 0):
			$status = __( 'Waiting for reply', 'iewticket' );
		elseif($status == 1):
			$status = __( 'Response from site manager', 'iewticket' );
			$class.=" font-weight:bold;";
		elseif($status == 2):
			$status = __( 'Response from user', 'iewticket' );
		elseif($status == 3):
			$status = __( 'Marked as solved by manager', 'iewticket' );
		elseif($status == 4):
			$status = __( 'Marked as solved by user', 'iewticket' );
		else:
			$status = __( 'Permanently closed.', 'iewticket' );
		endif;
			
		echo '
		<tr class="active" style="'.$class.'">
			<td><a href="'.$usrurl.'&do=show&id='.$ticket->id.'" title="'.__( 'Reply the ticket', 'iewticket' ).'">'. $ticket->title .'</a></td>
			<td align="center">'.date( 'd.m.Y - H:i:s', strtotime( $ticket->pubdate ) ).'</td>
			<td align="center">'.date( 'd.m.Y - H:i:s', strtotime( $ticket->uptodate ) ).'</td>
			<td align="center">'. $status .'</td>
			<td align="center">
				<a href="'.$usrurl.'&do=show&id='.$ticket->id.'" title="'.__( 'Reply the ticket', 'iewticket' ).'"><img src="'.plugins_url( 'img/help.png', __FILE__ ).'" alt="'.__( 'Reply the ticket', 'iewticket' ).'" /></a> &nbsp;&nbsp;
				<a href="'.$usrurl.'&do=mark&id='.$ticket->id.'" title="'.__( 'Mark as solved', 'iewticket' ).'"><img src="'.plugins_url( 'img/flag.png', __FILE__ ).'" alt="'.__( 'Mark as solved', 'iewticket' ).'" /></a>
			</td>
		</tr>';
	}
?>
			</tbody>
		</table>
	</div>
<?php
}

function _iew_user_show_ticket(){
	global $wpdb, $usrurl;
	$cruser = wp_get_current_user();
	$msgsdb = get_site_option( 'iew_msg_table' );
	$tcktdb = get_site_option( 'iew_ticket_table' );
	echo '<div class="wrap">';
	$id = (int) $_GET['id'];
	if( $ticket = $wpdb->get_row("SELECT * FROM ".$tcktdb." WHERE id = ".$id." AND uid = ".$cruser->ID) ){
		$result = '<h2>'.$ticket->title.'</h2>';
		
		echo '<div class="messagebox">';
		if( $msgs = $wpdb->get_results("SELECT * FROM {$msgsdb} WHERE tid = {$ticket->id} ORDER BY pubdate") ){
			foreach($msgs as $msg){
				$class = ($msg->uid == $ticket->uid) ? 'user-answer' : 'master-answer';
				$suser = get_userdata( $msg->uid );
				$cruser = ($suser->first_name != "" || $suser->lastname != "") ? $suser->first_name." ".$suser->last_name : $suser->display_name;
				$mdate = date( 'd.m.Y H:i', strtotime( $msg->pubdate ) );
				$result .= "<div class='iew {$class}'>";
				$result .= "<div class='answer'>".reverse_escape($msg->answer)."</div>";
				$result .= "<div class='meta'><p class='author'>{$cruser}</p> <p class='datetime'>{$mdate}</p></div>";
				$result .= "</div><div class='cl'></div>";
			}
		}
		$form = "<form method='post' action='{$usrurl}&do=send&id={$id}'>";
		$form .= "<textarea name='answer' class='replybox' onclick='if(this.value==\"".__( 'Write your reply here...', 'iewticket' )."\"){this.value=\"\"}' onblur='if(this.value==\"\"){this.value=\"".__( 'Write your reply here...', 'iewticket' )."\"}'>".__( 'Write your reply here...', 'iewticket' )."</textarea>";
		$form .= "<p><input type='submit' value='".__( 'Reply', 'iewticket' )."' class='button-primary' /></p>";
		$form .= "</form>";
		$result .= $form;
	}
	echo $result;
	echo '</div>';
	echo '</div>';
}

function _iew_user_send_reply(){
	global $wpdb, $usrurl;
	$msgsdb = get_site_option( 'iew_msg_table' );
	$tcktdb = get_site_option( 'iew_ticket_table' );
	$id = (int) $_GET['id'];
	if($id && isset($_POST['answer']) && $_POST['answer'] != ""){
		$uid = $wpdb->get_var("SELECT uid FROM {$tcktdb} WHERE id={$id}");
		$cruser = wp_get_current_user();
		if($cruser->ID == $uid){
			$answer = mysql_real_escape_string(trim($_POST['answer']));
			$crdate = date('Y-m-d H:i:s');
			$wpdb->insert( $msgsdb, array( 'tid' => $id, 'uid' => $cruser->ID, 'answer' => $answer, 'pubdate' => $crdate ), array( '%d', '%d', '%s', '%s' ) );
			if($wpdb->insert_id){
				$wpdb->update( $tcktdb, array( 'uptodate' => $crdate, 'status' => 2 ), array( 'id' => $id ), array( '%s', '%d' ), array( '%d' ) );
				header("Location:".$usrurl."&do=show&id=".$id);
			}
		} else {
			_e( 'ERROR: Access denied.', 'iewticket' );
		}
	} else {
		_e( 'ERROR: Something missing. Please try again later.', 'iewticket' );
	}
}


function _iew_user_mark_as_solved(){
	global $wpdb, $usrurl;
	$cruser = wp_get_current_user();
	$msgsdb = get_site_option( 'iew_msg_table' );
	$tcktdb = get_site_option( 'iew_ticket_table' );
	$id = (int) $_GET['id'];
	if($uid = $wpdb->get_var("SELECT id FROM {$tcktdb} WHERE id={$id} AND uid={$cruser->ID}")){
		$crdate = date('Y-m-d H:i:s');
		$wpdb->update($tcktdb, array( 'uptodate' => $crdate, 'status' => 4 ), array( 'id' => $id ), array( '%s', '%d' ), array( '%d' ) );
		header("Location: {$usrurl}");
	}
}


/*
 * !_user_can_create_new_ticket_! *
 */
function iew_new_ticket_page(){
	global $wpdb;
	$cruser = wp_get_current_user();
	$msgsdb = get_site_option( 'iew_msg_table' );
	$tcktdb = get_site_option( 'iew_ticket_table' );
?>
	<div class="wrap">
		<h2><?php _e( 'New Ticket', 'iewticket' ); ?></h2>
		
<?php
if( !isset( $_POST['newticket'] ) ){ ?>
	<form action="" method="POST" name="newticket">
	<table class="form-table">
		<tr valign="top">
			<th scope="row"><label for="pname"><?php _e( 'Subject:', 'iewticket' ); ?></label></th>
			<td><input type="text" name="tname" class="regular-text" /> (*)</td>
		</tr>
		<tr valign="top">
			<th scope="row"><label for="purl"><?php _e( 'Question:', 'iewticket' ); ?></label></th>
			<td>
				<p><?php _e( 'Please describe your problem clearly.', 'iewticket' ); ?> (*)</p>
				<p><textarea name="tdesc" rows="7" class="large-text"></textarea></p>
			</td>
		</tr>
		<tr valign="top">
			<td colspan="2"><?php _e( '(*) Required field - You have to fill it.', 'iewticket' ); ?></td>
		</tr>
		<tr valign="top">
			<td colspan="2" align="center"><input type="submit" class="button-primary" value="<?php _e( 'Create Ticket', 'iewticket' ); ?>" /></td>
		</tr>
		<input type="hidden" name="newticket" value="ok" />
	</table>
	</form>
	</div>
<?php
	} else {
		if( isset($_POST['tname']) && $_POST['tname'] != "" && isset( $_POST['tdesc'] ) && $_POST['tdesc'] != "" ){
			$title = trim(wptexturize(strip_tags($_POST['tname'])));
			$question = mysql_real_escape_string(trim($_POST['tdesc']));
			$crdate = date('Y-m-d H:i:s');
			$wpdb->insert( $tcktdb, array( 'uid' => $cruser->ID, 'title' => $title, 'pubdate' => $crdate, 'uptodate' => $crdate, 'status' => 0 ), array( '%d', '%s', '%s', '%s', '%d' ) );
			if( $wpdb->insert_id ){
				$wpdb->insert( $msgsdb, array( 'tid' => $wpdb->insert_id, 'uid' => $cruser->ID, 'answer' => $question, 'pubdate' => $crdate ), array( '%d', '%d', '%s', '%s' ) );
				if($wpdb->insert_id){
					echo '<p>'.__( 'Your ticket has been created.', 'iewticket' ).'</p>';
				}
			}
		} else {
			echo '<p>'.__( 'Ooops, It seems that you didnt fill all form fields.', 'iewticket' ).'</p>';
		}
	}
?>
	</div>
<?php
}
?>