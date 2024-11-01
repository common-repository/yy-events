<?php
/*
Plugin Name: YY EVENTS
Plugin URI: http://yyengine.jp/yyevents/
Description: Enables you to show a list of Events, Concerts, Sports and Theater Listings.
Version: 1.4
Author: Yuji Yamabata
Author URI: http://yyengine.jp/
License: GPL2
*/
?>
<?php
/***********************************************
	include
*******/
load_plugin_textdomain( 'yyevents', false, basename( dirname( __FILE__ ) ) . '/languages' );
include_once ABSPATH.'wp-content/plugins/yy-events/metaboxes/setup.php';

/***********************************************
	create custom post tyle
*******/
function yye_create_post_type($type , $name , $menu) {

	register_post_type( $type, 
		array(
				'labels' => array(
				'name' => $name,
				'singular_name' => $name,
			),
			'public' => true,
			'supports' => $menu,
			'menu_position' => 5,
			'rewrite' => true,
			'has_archive' => false
		)
	);
}

function yye_create_post_type_lists() {
	yye_create_post_type('yyevents', __('YYEvents', 'yyevents'), array('title' , 'editor')); 
}
add_action( 'init', 'yye_create_post_type_lists' );


/***********************************************
	custom fields
*******/
$yye_custom_metabox = new WPAlchemy_MetaBox(array(
	'id' => 'ctm_yyevents',
	'title' => 'YY EVENTS',
	'template' => ABSPATH . 'wp-content/plugins/yy-events/custom/meta.php',
	'mode' => WPALCHEMY_MODE_EXTRACT,
	'types' => array('yyevents')
));

/***********************************************
	shortcode
	
	現日付より先のイベント一覧を表示する
	[yyevents]

	過去分のイベント一覧を表示する(1ページ当たり5イベント表示)
	[yyevents pagenum=5 show="old"]

	パラメーター
	type		: 表示タイプ					 		list:リスト表示(default) calendar:カレンダー表示
	pagenum		: 1ページに表示するイベント件数 		数値(default 10)
	singlelink	: イベント詳細ページへのリンク表示		on:表示する(default) off:表示しない
	image		: サムネイル画像						on:表示する(default) off:表示しない
	show		: 表示するイベント						now:現在日から先のイベント(default) old:現在日より前のイベント all:すべてのイベント
*******/
function shortcode_yyevents($atts) {
	extract(shortcode_atts(array(
		'type' => 'list',
		'pagenum' => 10,
		'singlelink' => 'on',
		'image' => 'on',
		'show' => 'now'
	), $atts));

	global $yye_custom_metabox;
	global $paged;
	$ret = '';
	$args = array();
	
	//カレンダーの表示
	if($type == 'calendar'){
		$get_year = date('Y');
		$get_month = date('m');
		if($_GET["e"]){
			$get_year = substr($_GET["e"], 0, 4);
			$get_month = substr($_GET["e"], 4, 2);
		}
		$get_end_date = date('j', mktime(0, 0, 0, $get_month + 1, 0, $get_year));
		//指定月のイベントデータ取得
		$args = array(	
						'post_type' => 'yyevents'
						,'meta_key' => 'yye_date'
						,'orderby' => 'meta_value'
						,'order' => 'ASC'
						,'meta_query' => array(
							array(
								'key'=> 'yye_date',
								'value'=> array($get_year.'/'.$get_month.'/01', $get_year.'/'.$get_month.'/'.$get_end_date),
								'compare'=> 'BETWEEN'
								)
							)
					);
		query_posts( $args );
		//print_r($args);
		$event = array();
		if (have_posts()):while(have_posts()): the_post();
			$custom_fields = get_post_custom(get_the_ID());
			$event[] = array('title' => get_the_title(), 'link' => get_permalink(), 'date' => $custom_fields['yye_date'][0]);
		endwhile; endif;
		wp_reset_postdata();
		wp_reset_query();

		//カレンダー表示
		$ret .= '<ul id="yye_date_control">';
		$ret .= '<li class="yye_today"><a href=".">今日</a></li>';
		$ret .= '<li class="yye_before"><a href="?e='.date('Ym', mktime(0, 0, 0, $get_month - 1, 1, $get_year)).'">&lt;&lt;</a></li>';
		$ret .= '<li class="yye_next"><a href="?e='.date('Ym', mktime(0, 0, 0, $get_month + 1, 1, $get_year)).'">&gt;&gt;</a></li>';
		$ret .= '<li class="yye_now">'.$get_year.'年'.$get_month.'月</li>';
		$ret .= '</ul>';

		//カレンダー表示
		$cal = yye_get_calendar($get_year, $get_month);
		$weekday = array('日', '月', '火', '水', '木', '金', '土');
		$ret .= '<table id="yye_calendar">';
		$ret .= '<tr>';
		foreach($weekday as $value) $ret .= '<th>' . $value . '</th>';
		$ret .= '<tr>';
		$ret .= '<tr>';
		$cnt = 0;
		foreach($cal as $key => $value){
			$ret .= '<td>';
			$ret .= '<span class="d">' . $value['day'] .'</span>';
			//イベントデータ表示ここから
			foreach($event as $val){
				if(($get_year.'/'.$get_month.'/' . sprintf("%02d", $value['day'])) == $val['date']){
					$ret .= '<br />' . '<a href="' . $val['link'] . '">' . $val['title'] . '</a>';
				}
			}
			//イベントデータ表示ここまで
			$ret .= '</td>';
			if(++$cnt == 7){
				$ret .= '</tr>';
				$ret .= '<tr>';
				$cnt = 0;
			}
		}
		$ret .= '</tr>';
		$ret .= '</table>';

		return $ret;
	}
	
	//リスト表示
	if($show == 'now'){
		$args = array(	
						'post_type' => 'yyevents'
						,'posts_per_page' => $pagenum
						,'paged' => $paged
						,'meta_key' => 'yye_date'
						,'orderby' => 'meta_value'
						,'order' => 'ASC'
						,'meta_query' => array(
							'relation' => 'OR',
							array(
								'key'=> 'yye_date',
								'value'=> date("Y/m/d"),
								'compare'=> '>='
								) ,
							array(
								'key'=> 'yye_date_end',
								'value'=> date("Y/m/d"),
								'compare'=> '>=',
								'type'=>'DATE'
								)
							)
					);
	}else if($show == 'old'){
		$args = array(	
						'post_type' => 'yyevents'
						,'posts_per_page' => $pagenum
						,'paged' => $paged
						,'meta_key' => 'yye_date'
						,'orderby' => 'meta_value'
						,'order' => 'DESC'
						,'meta_query' => array(
							array(
								'key'=> 'yye_date',
								'value'=> date("Y/m/d"),
								'compare'=> '<'
								)
							)
					);
	}else{
		$args = array(	
						'post_type' => 'yyevents'
						,'posts_per_page' => $pagenum
						,'paged' => $paged
						,'meta_key' => 'yye_date'
						,'orderby' => 'meta_value'
						,'order' => 'DESC'
					);
	}
	query_posts( $args );
	if (have_posts()):while(have_posts()):the_post();
		$custom_fields = get_post_custom(get_the_ID());
		$ret .= yye_set_event(get_the_title(), $custom_fields, $image, $singlelink);
	endwhile; endif;

	//paging
	$ret .= '<div class="yyeNav">';
	global $wp_rewrite;
	global $wp_query;
	$paginate_base = get_pagenum_link(1);
	if (strpos($paginate_base, '?') || ! $wp_rewrite->using_permalinks()) {
		$paginate_format = '';
		$paginate_base = add_query_arg('paged', '%#%');
	} else {
		$paginate_format = (substr($paginate_base, -1 ,1) == '/' ? '' : '/') . 
		user_trailingslashit('page/%#%/', 'paged');;
		$paginate_base .= '%_%';
	}
	$ret .= paginate_links( array(
				'base' => $paginate_base,
				'format' => $paginate_format,
				'total' => $wp_query->max_num_pages,
				'mid_size' => 5,
				'current' => ($paged ? $paged : 1),
			));
	$ret .= '</div>';

	wp_reset_postdata();
	wp_reset_query();
	
	return $ret;
}
add_shortcode('yyevents', 'shortcode_yyevents');

/***********************************************
	event list html
*******/
function yye_set_event($event_title, $custom_fields, $image, $singlelink){
	$ckeys = array( "yye_title",
					"yye_description",
					"yye_place",
					"yye_date",
					"yye_date_end",
					"yye_start",
					"yye_price",
					"yye_actors",
					"yye_contact",
					"yye_etc",
					"imgurl"
					);

	$cfields = array();
	foreach($custom_fields as $key => $value){
		if(!in_array($key, $ckeys)) continue;
		if(!is_array($value[0])) $str = nl2br($value[0]);	//改行コードをbrタグに変換
		//urlにリンクを付ける
		$str = preg_replace('/(https?|ftp)(:\/\/[-_.!~*\'()a-zA-Z0-9;\/?:\@&=+\$,%#]+)/', '<A href="\\1\\2" target="_blank">\\1\\2</A>', $str);
		$cfields[$key] = $str;
	}

	$ret = '';
	$ret .= '<div class="yyeBox">';
		$ret .= '<div class="yyeHead">';
			if($image == 'on') $ret .= '<div class="yyeInnerL">';
				$ret .= '<p class="yyeDate">';
				$ret .= $cfields['yye_date'];
				if($cfields['yye_date_end']) $ret .= '〜' . $cfields['yye_date_end'];
				$ret .= '</p>';
				if($cfields['yye_description']){
					$ret .= '<p class="yyeCatch">';
					$ret .= $cfields['yye_title'];
					$ret .= '</p>';
				}
				$ret .= '<h3 class="yyeTitle">';
				if($singlelink == 'on') $ret .= '<a href=' . get_permalink() . '>';
				$ret .= $event_title;
				if($singlelink == 'on') $ret .= '</a>';
				$ret .= '</h3>';
				if($cfields['yye_description']){
					$ret .= '<p class="yyeDescription">';
					$ret .= $cfields['yye_description'];
					$ret .= '</p>';
				}
			if($image == 'on') $ret .= '</div>';
			if($image == 'on'){
					$ret .= '<div class="yyeInnerR">';
					if($cfields['imgurl']){
						$ret .= wp_get_attachment_image($cfields['imgurl'], medium);
					}
				$ret .= '</div>';
			}
		$ret .= '</div>';
		
		$ret .= '<div class="yyeBody">';
			$ret .= '<table>';
			$ret .= '<tbody>';
			if($cfields['yye_place']){		$ret .= '<tr><th>'.__('Place', 'yyevents').'</th><td>' . $cfields['yye_place'] .'</td></tr>'; }
			if($cfields['yye_start']){		$ret .= '<tr><th>'.__('Open/Start', 'yyevents').'</th><td>' . $cfields['yye_start'] .'</td></tr>'; }
			if($cfields['yye_price']){		$ret .= '<tr><th>'.__('Price', 'yyevents').'</th><td>' . $cfields['yye_price'] .'</td></tr>'; }
			if($cfields['yye_actors']){		$ret .= '<tr><th>'.__('Actors', 'yyevents').'</th><td>' . $cfields['yye_actors'] .'</td></tr>'; }
			if($cfields['yye_contact']){	$ret .= '<tr><th>'.__('Contact', 'yyevents').'</th><td>' . $cfields['yye_contact'] .'</td></tr>'; }
			if($cfields['yye_etc']){		$ret .= '<tr><th>'.__('Infomation', 'yyevents').'</th><td>' . $cfields['yye_etc'] .'</td></tr>'; }
			$ret .= '</tbody>';
			$ret .= '</table>';
		$ret .= '</div>';
	$ret .= '</div>';
	return $ret;
}

/***********************************************
	event calendar get
*******/
function yye_get_calendar($year, $month){
	$last_day = date('j', mktime(0, 0, 0, $month + 1, 0, $year));
	$ret = array();
	$j = 0;
	$week = 0;
	for($i = 1; $i <= $last_day; $i++, $j++){
		$week = date('w', mktime(0, 0, 0, $month, $i, $year));
		if($i == 1){
			for($s = 1; $s <= $week; $s++, $j++) $ret[$j]['day'] = '';
		}
		$ret[$j]['day'] = $i;
	}
	for($i = 1; $i <= 6 - $week; $i++, $j++) $ret[$j]['day'] = '';
	return $ret;
}

/***********************************************
	the_content fook
*******/
function yye_event_single_page($the_content) {
	$ret  ='';
	if( 'yyevents' == get_post_type() ){
		$custom_fields = get_post_custom(get_the_ID());
		$ret .= yye_set_event(get_the_title(), $custom_fields, 'off', 'off');
		$ret .= $the_content;
	} else {
		$ret = $the_content;
	}
	return $ret;
}

add_filter('the_content', 'yye_event_single_page');

/***********************************************
	css
*******/
function yye_register_style() {
	wp_register_style('yye_style', '/wp-content/plugins/yy-events/css/yy-events.css');
}
function yye_add_stylesheet() {
	yye_register_style();
	wp_enqueue_style('yye_style');
}
add_action('wp_print_styles', 'yye_add_stylesheet');


/***********************************************
	widget
*******/
add_action( 'widgets_init', create_function('', 'return register_widget("yye_event_widget");') );
class yye_event_widget extends WP_Widget {
	function yye_event_widget() {
		$widget_ops = array('classname' => 'yye_event_widget', 'description' => __('Event List', 'yyevents'));
    	$this->WP_Widget('yye_event_widget', 'YY EVENTS', $widget_ops);
	}
	
	function form( $instance ) {
		$instance = wp_parse_args( (array) $instance ); ?>
		<p><label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e('Title:', 'yyevents') ?></label><br /><input type=text class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php if( isset($instance['title']) ) echo $instance['title']; ?>" /></p>
        <p><label for="<?php echo $this->get_field_id( 'postcount' ); ?>"><?php _e('num:', 'yyevents') ?></label><br /><input type=text class="widefat" id="<?php echo $this->get_field_id( 'postcount' ); ?>" name="<?php echo $this->get_field_name( 'postcount' ); ?>" value="<?php if( isset($instance['postcount']) ) echo $instance['postcount']; ?>" /></p>
<?php }

	function widget( $args, $instance ) {
		extract( $args );

		$title = apply_filters('widget_title', $instance['title'] );
		if ( isset($instance['id']) ) $id = $instance['id'];
		if ( isset($instance['postcount']) ) $pcount = $instance['postcount']; else $pcount = 1;
			
		echo $before_widget;
		if ( $title ) echo $before_title . $title . $after_title;
		
		$unique = uniqid();

		$weekdays = array();
		$weekdays[] = 'Sun';
		$weekdays[] = 'Mon';
		$weekdays[] = 'Tue';
		$weekdays[] = 'Wed';
		$weekdays[] = 'Thu';
		$weekdays[] = 'Fri';
		$weekdays[] = 'Sat';

		$args = array(
						'post_type' => 'yyevents'
						,'posts_per_page' => $pcount
						,'orderby' => 'meta_value'
						,'meta_key' => 'yye_date'
						,'order' => 'ASC'
						,'meta_query' => array(
							array(
								'key'=> 'yye_date',
								'value'=> date('Y/m/d'),
								'compare'=> '>'
								)
							)
					);
		query_posts($args);
		echo '<ul class="post-list">';
		if (have_posts()) : while (have_posts()) : the_post();
			$da = get_post_meta(get_the_ID(), 'yye_date', true);
			$da = explode('/', $da);
			$mt = mktime(0, 0, 0, $da[1], $da[2], $da[0]);
			$ld = date('Y.m.d ', $mt);
			$ld .= '('.$weekdays[date('w', $mt)].')';

			$event_title = get_post_meta(get_the_ID(), 'yye_title', true) . get_the_title();
			$event_kaijo = get_post_meta(get_the_ID(), 'yye_place', true);

			echo '<li>';
			echo '<div>'.$ld.'</div>';
			echo '<div>'.' @ '.$event_kaijo.'</div>';
			echo '<div>'.$event_title.'</div>';
			echo '</li>';
		endwhile;endif;
		wp_reset_query();
		echo '</ul>';

		echo $after_widget;
	}


	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = $new_instance['title'];
		$instance['postcount'] = $new_instance['postcount'];
		return $instance;
	}
}
