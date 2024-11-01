<?php
/*
 * @desc: Add ticket menu to Network Admin
 * @vers: 1.0
 */
add_action( 'network_admin_menu', 'iew_ticket_plugin' );
function iew_ticket_plugin(){
	$adminmenu = add_menu_page( __( 'Tickets', 'iewticket') , __( 'Tickets', 'iewticket'), 'manage_options', 'iew-admin-tickets', 'iew_admin_tickets', plugins_url( 'img/iew-icon.gif', __FILE__ ) );
	add_action( 'admin_print_scripts-'. $adminmenu, 'iew_ticket_header' );
	//add_submenu_page( 'iew-admin-tickets', __( 'Ticket Settings', 'iewticket'), __( 'Settings', 'iewticket'), 'manage_options', 'iew-admin-ticket-settings', 'iew_admin_ticket_settings' );
}

function iew_ticket_header(){
	echo '<link rel="stylesheet" type="text/css" href="'.plugins_url( 'iew-admin-style.css', __FILE__ ).'" />';
}

/*
 * !_Main_Function_!
 *
 * @desc: list all tickets by status, reply any ticket, delete tickets, close tickets.
 * @vers: 1.0
 */
function iew_admin_tickets(){
	$do = ( isset($_GET['do']) ) ? $_GET['do'] : 'default';
	switch($do){
		case 'default':
		default:
			_iew_admin_list();
		break;
		
		case 'show':
			_iew_admin_show_ticket();
		break;
		
		case 'send':
			_iew_admin_send_reply();
		break;
		
		case 'mark':
			_iew_admin_mark_as_solved();
		break;
		
		case 'delete':
			_iew_admin_delete_ticket();
		break;
	}
}

function _iew_admin_list(){
	global $wpdb, $admurl;
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
					<th style="text-align:center"><?php _e( 'User', 'iewticket' ); ?></th>
					<th style="text-align:center"><?php _e( 'Last Update', 'iewticket' ); ?></th>
					<th style="text-align:center"><?php _e( 'Status', 'iewticket' ); ?></th>
					<th style="text-align:center"><?php _e( 'Actions', 'iewticket' ); ?></th>
				</tr>
			</thead>
				
			<tbody>
<?php
$total_records = $wpdb->get_var("SELECT COUNT(*) FROM {$tcktdb}");
$scroll_page = 3; // kaydýrýlacak sayfa sayýsý
$per_page = 15; // her sayafa gösterilecek sayfa sayýsý
$current_page = $_GET['s']; // bulunulan sayfa
$pager_url = 'admin.php?page=iew-admin-tickets&s='; // sayfalamanýn yapýldýðý adres
$inactive_page_tag = 'class="page-numbers"'; // aktif olmayan sayfa linki için biçim
$previous_page_text = '&lt;'; // önceki sayfa metni (resim de olabilir <img src="... gibi)
$next_page_text = '&gt;'; // sonraki sayfa metni (resim de olabilir <img src="... gibi)
$first_page_text = '&lt;&lt;'; // ilk sayfa metni (resim de olabilir <img src="... gibi)
$last_page_text = '&gt;&gt;'; // son sayfa metni (resim de olabilir <img src="... gibi)
$pager_url_last = ''; // sayfalama linkinde sayfa sayýsýndan sonra gelecek karakter (bol olabilir).

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

	$tickets = $wpdb->get_results("SELECT * FROM {$tcktdb} ORDER BY 'uptodate' DESC LIMIT ".$kgPagerOBJ -> start.", ".$kgPagerOBJ -> per_page);
	foreach($tickets as $tip => $ticket) {
		$class = ($tip % 2) ? 'background:#eee;' : 'background:#fff;';
		$status = $ticket->status;
		if($status == 0):
			$status = __( 'Waiting for reply', 'iewticket' );
			$class.=" font-weight:bold;";
		elseif($status == 1):
			$status = __( 'Response from site manager', 'iewticket' );
		elseif($status == 2):
			$status = __( 'Response from user', 'iewticket' );
			$class.=" font-weight:bold;";
		elseif($status == 3):
			$status = __( 'Marked as solved by manager', 'iewticket' );
		elseif($status == 4):
			$status = __( 'Marked as solved by user', 'iewticket' );
		else:
			$status = __( 'Permanently closed.', 'iewticket' );
		endif;
			
		$suser = get_userdata( $ticket->uid );
		echo '
		<tr class="active" style="'.$class.'">
			<td><a href="'.$admurl.'&do=show&id='.$ticket->id.'">'. $ticket->title .'</a></td>
			<td align="center">'.$suser->first_name.' '.$suser->last_name.'</td>
			<td align="center">'.date( 'd.m.Y - H:i:s', strtotime( $ticket->uptodate ) ).'</td>
			<td align="center">'. $status .'</td>
			<td align="center">
				<a href="'.$admurl.'&do=show&id='.$ticket->id.'" title="'.__( 'Reply the ticket', 'iewticket' ).'"><img src="'.plugins_url( 'img/help.png', __FILE__ ).'" alt="'.__( 'Reply the ticket', 'iewticket' ).'" /></a> &nbsp;&nbsp;
				<a href="'.$admurl.'&do=mark&id='.$ticket->id.'" title="'.__( 'Mark as solved', 'iewticket' ).'"><img src="'.plugins_url( 'img/flag.png', __FILE__ ).'" alt="'.__( 'Mark as solved', 'iewticket' ).'" /></a> &nbsp;&nbsp;
				<a href="'.$admurl.'&do=delete&id='.$ticket->id.'" title="'.__( 'Permanently delete this ticket', 'iewticket' ).'"><img src="'.plugins_url( 'img/delete.png', __FILE__ ).'" alt="'.__( 'Permanently delete this ticket', 'iewticket' ).'" /></a>
			</td>
		</tr>';
	}
?>
			</tbody>
		</table>
	</div>
<?php
}

function _iew_admin_show_ticket(){
	global $wpdb, $admurl;
	$msgsdb = get_site_option( 'iew_msg_table' );
	$tcktdb = get_site_option( 'iew_ticket_table' );
	echo '<div class="wrap">';
	$id = (int) $_GET['id'];
	if( $ticket = $wpdb->get_row("SELECT * FROM ".$tcktdb." WHERE id = ".$id) ){
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
		$form = "<form method='post' action='{$admurl}&do=send&id={$id}'>";
		$form .= "<textarea name='answer' class='replybox' onclick='if(this.value==\"".__( 'Write your reply here...', 'iewticket' )."\"){this.value=\"\"}' onblur='if(this.value==\"\"){this.value=\"".__( 'Write your reply here...', 'iewticket' )."\"}'>".__( 'Write your reply here...', 'iewticket' )."</textarea>";
		$form .= "<p><input type='submit' value='".__( 'Reply', 'iewticket' )."' class='button-primary' /></p>";
		$form .= "</form>";
		$result .= $form;
		echo '</div>';
	}
	echo $result;
	echo '</div>';
}

function _iew_admin_send_reply(){
	global $wpdb, $admurl;
	$msgsdb = get_site_option( 'iew_msg_table' );
	$tcktdb = get_site_option( 'iew_ticket_table' );
	$id = (int) $_GET['id'];
	if($id && isset($_POST['answer']) && $_POST['answer'] != ""){
		$uid = $wpdb->get_var("SELECT uid FROM {$tcktdb} WHERE id={$id}");
		$cruser = wp_get_current_user();
		if(is_super_admin($cruser->ID) || $cruser->ID == $uid){
			$answer = mysql_real_escape_string(trim($_POST['answer']));
			$crdate = date('Y-m-d H:i:s');
			$wpdb->insert( $msgsdb, array( 'tid' => $id, 'uid' => $cruser->ID, 'answer' => $answer, 'pubdate' => $crdate ), array( '%d', '%d', '%s', '%s' ) );
			if($wpdb->insert_id){
				$wpdb->update( $tcktdb, array( 'uptodate' => $crdate, 'status' => 1 ), array( 'id' => $id ), array( '%s', '%d' ), array( '%d' ) );
				header("Location:".$admurl."&do=show&id=".$id);
			}
		} else {
			_e( 'ERROR: Access denied.', 'iewticket' );
		}
	} else {
		_e( 'ERROR: Something missing. Please try again later.', 'iewticket' );
	}
}

function _iew_admin_mark_as_solved(){
	global $wpdb, $admurl;
	$msgsdb = get_site_option( 'iew_msg_table' );
	$tcktdb = get_site_option( 'iew_ticket_table' );
	$id = (int) $_GET['id'];
	if($uid = $wpdb->get_var("SELECT id FROM {$tcktdb} WHERE id={$id}")){
		$crdate = date('Y-m-d H:i:s');
		$wpdb->update($tcktdb, array( 'uptodate' => $crdate, 'status' => 3 ), array( 'id' => $id ), array( '%s', '%d' ), array( '%d' ) );
		header("Location: {$admurl}");
	}
}

function _iew_admin_delete_ticket(){
	global $wpdb, $admurl;
	$msgsdb = get_site_option( 'iew_msg_table' );
	$tcktdb = get_site_option( 'iew_ticket_table' );
	$id = (int) $_GET['id'];
	
	if($uid = $wpdb->get_var("SELECT id FROM {$tcktdb} WHERE id={$id}")){
		$delete = $wpdb->query("DELETE FROM {$tcktdb} WHERE id={$id}");
		if($delete){
			$wpdb->query("DELETE FROM {$msgsdb} WHERE tid={$id}");
		}
		header("Location: {$admurl}");
	}
}

function iew_admin_ticket_settings(){
}

function iew_admin_change_menu(){
	global $menu, $wpdb;

	$tcktdb = get_site_option( 'iew_ticket_table' );
	$uid = $current_user->ID;
	$kac = $wpdb->get_var("SELECT COUNT(*) FROM {$tcktdb} WHERE status=0 OR status=2");
	
	if($kac > 0){
		$find = array_searchRecursive( 'iew-admin-tickets', $menu );
		$morder = $find[0];
		$mymenu = $menu[$morder];
		$mtitle = sprintf( __('Tickets %s', 'iewticket'), "<span class='awaiting-mod count-$kac'><span class='pending-count'>" . number_format_i18n($kac) . "</span></span>" );
		$menu[$morder] = array( $mtitle, $mymenu[1], $mymenu[2], $mymenu[3], $mymenu[4], $mymenu[5], $mymenu[6] );
	}
	return $menu;
}
add_filter('network_admin_menu', 'iew_admin_change_menu');
?>