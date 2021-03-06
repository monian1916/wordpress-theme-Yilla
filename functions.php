<?php
/**
* 数字分页函数
* 因为wordpress默认仅仅提供简单分页
* 所以要实现数字分页，需要自定义函数
* @Param int $range            数字分页的宽度
* @Return string|empty        输出分页的HTML代码        
*/
function pagenav($type=null,$archive_pages=0) {
    global $paged,$wp_query;
    $range = 4;
    if ( !$max_page ) {
    		if($type){
    			$max_page=$archive_pages/30;
    		}else{
    			$max_page = $wp_query->max_num_pages;
    		}
    }
    if( $max_page >1 ) {
        echo '<nav id="page-nav">'; 
        if( !$paged ){
            $paged = 1;
        }
        if( $paged != 1 ) {
            echo '<a class="extend" href="'.get_pagenum_link(1).'">首页</a>';
            echo '<a class="extend" href="'.get_pagenum_link($paged-1).'">上一页</a>';
        }
        if ( $max_page >$range ) {
            if( $paged <$range ) {
                for( $i = 1; $i <= ($range +1); $i++ ) {
                    echo "<a href='".get_pagenum_link($i) ."' class='page-number ";
                if($i==$paged) echo "current";echo "'>$i</a>";
                }
            }elseif($paged >= ($max_page -ceil(($range/2)))){
                for($i = $max_page -$range;$i <= $max_page;$i++){
                    echo "<a href='".get_pagenum_link($i) ."' class='page-number ";
                    if($i==$paged)echo "current";echo "'>$i</a>";
                    }
                }elseif($paged >= $range &&$paged <($max_page -ceil(($range/2)))){
                    for($i = ($paged -ceil($range/2));$i <= ($paged +ceil(($range/2)));$i++){
                        echo "<a href='".get_pagenum_link($i) ."' class='page-number ";if($i==$paged) echo "current";echo "'>$i</a>";
                    }
                }
            }else{
                for($i = 1;$i <= $max_page;$i++){
                    echo "<a href='".get_pagenum_link($i) ."' class='page-number ";
                    if($i==$paged)echo "current";echo "'>$i</a>";
                }
            }
        if($paged != $max_page){
        		echo '<a class="extend" href="'.get_pagenum_link($paged+1).'">下一页</a>';
            echo "<a href='".get_pagenum_link($max_page) ."' class='extend' title='跳转到最后一页'>尾页</a>";
        }
        echo '</nav>';  
    }
}

function count_words ($text) {
global $post;
if ( '' != $text ) {
   $text = $post->post_content;
   if (mb_strlen($output, 'UTF-8') < mb_strlen($text, 'UTF-8')) $output .=mb_strlen(preg_replace('/\s/','',html_entity_decode(strip_tags($post->post_content))),'UTF-8');
   return $output;
}
}

function tag_cloud( $args = '' ) {
	$defaults = array(
		'smallest' => 18, 'largest' => 35, 'unit' => 'px', 'number' => 45,
		'format' => 'flat', 'separator' => "\n", 'orderby' => 'name', 'order' => 'ASC',
		'exclude' => '', 'include' => '', 'link' => 'view', 'taxonomy' => 'post_tag', 'post_type' => '', 'echo' => true,
		'show_count' => 0,'type' =>null,
	);
	$args = wp_parse_args( $args, $defaults );

	$tags = get_terms( $args['taxonomy'], array_merge( $args, array( 'orderby' => 'count', 'order' => 'DESC' ) ) ); // Always query top tags

	if ( empty( $tags ) || is_wp_error( $tags ) )
		return;

	foreach ( $tags as $key => $tag ) {
		if ( 'edit' == $args['link'] )
			$link = get_edit_term_link( $tag->term_id, $tag->taxonomy, $args['post_type'] );
		else
			$link = get_term_link( intval($tag->term_id), $tag->taxonomy );
		if ( is_wp_error( $link ) )
			return;

		$tags[ $key ]->link = $link;
		$tags[ $key ]->id = $tag->term_id;
	}

	$return = generate_tag_cloud( $tags, $args ); // Here's where those top tags get sorted according to $args

	/**
	 * Filters the tag cloud output.
	 *
	 * @since 2.3.0
	 *
	 * @param string $return HTML output of the tag cloud.
	 * @param array  $args   An array of tag cloud arguments.
	 */
	$return = apply_filters( 'wp_tag_cloud', $return, $args );

	if ( 'array' == $args['format'] || empty($args['echo']) )
		return $return;

	echo $return;
}

function generate_tag_cloud( $tags, $args = '' ) {
	$defaults = array(
		'smallest' => 12, 'largest' => 22, 'unit' => 'px', 'number' => 0,
		'format' => 'flat', 'separator' => "\n", 'orderby' => 'name', 'order' => 'ASC',
		'topic_count_text' => null, 'topic_count_text_callback' => null,
		'topic_count_scale_callback' => 'default_topic_count_scale', 'filter' => 1,
		'show_count' => 0,'type' =>null,
	);

	$args = wp_parse_args( $args, $defaults );

	$return = ( 'array' === $args['format'] ) ? array() : '';

	if ( empty( $tags ) ) {
		return $return;
	}

	// Juggle topic counts.
	if ( isset( $args['topic_count_text'] ) ) {
		// First look for nooped plural support via topic_count_text.
		$translate_nooped_plural = $args['topic_count_text'];
	} elseif ( ! empty( $args['topic_count_text_callback'] ) ) {
		// Look for the alternative callback style. Ignore the previous default.
		if ( $args['topic_count_text_callback'] === 'default_topic_count_text' ) {
			$translate_nooped_plural = _n_noop( '%s item', '%s items' );
		} else {
			$translate_nooped_plural = false;
		}
	} elseif ( isset( $args['single_text'] ) && isset( $args['multiple_text'] ) ) {
		// If no callback exists, look for the old-style single_text and multiple_text arguments.
		$translate_nooped_plural = _n_noop( $args['single_text'], $args['multiple_text'] );
	} else {
		// This is the default for when no callback, plural, or argument is passed in.
		$translate_nooped_plural = _n_noop( '%s item', '%s items' );
	}

	/**
	 * Filters how the items in a tag cloud are sorted.
	 *
	 * @since 2.8.0
	 *
	 * @param array $tags Ordered array of terms.
	 * @param array $args An array of tag cloud arguments.
	 */
	$tags_sorted = apply_filters( 'tag_cloud_sort', $tags, $args );
	if ( empty( $tags_sorted ) ) {
		return $return;
	}

	if ( $tags_sorted !== $tags ) {
		$tags = $tags_sorted;
		unset( $tags_sorted );
	} else {
		if ( 'RAND' === $args['order'] ) {
			shuffle( $tags );
		} else {
			// SQL cannot save you; this is a second (potentially different) sort on a subset of data.
			if ( 'name' === $args['orderby'] ) {
				uasort( $tags, '_wp_object_name_sort_cb' );
			} else {
				uasort( $tags, '_wp_object_count_sort_cb' );
			}

			if ( 'DESC' === $args['order'] ) {
				$tags = array_reverse( $tags, true );
			}
		}
	}

	if ( $args['number'] > 0 )
		$tags = array_slice( $tags, 0, $args['number'] );

	$counts = array();
	$real_counts = array(); // For the alt tag
	foreach ( (array) $tags as $key => $tag ) {
		$real_counts[ $key ] = $tag->count;
		$counts[ $key ] = call_user_func( $args['topic_count_scale_callback'], $tag->count );
	}

	$min_count = min( $counts );
	$spread = max( $counts ) - $min_count;
	if ( $spread <= 0 )
		$spread = 1;
	$font_spread = $args['largest'] - $args['smallest'];
	if ( $font_spread < 0 )
		$font_spread = 1;
	$font_step = $font_spread / $spread;

	$aria_label = false;
	/*
	 * Determine whether to output an 'aria-label' attribute with the tag name and count.
	 * When tags have a different font size, they visually convey an important information
	 * that should be available to assistive technologies too. On the other hand, sometimes
	 * themes set up the Tag Cloud to display all tags with the same font size (setting
	 * the 'smallest' and 'largest' arguments to the same value).
	 * In order to always serve the same content to all users, the 'aria-label' gets printed out:
	 * - when tags have a different size
	 * - when the tag count is displayed (for example when users check the checkbox in the
	 *   Tag Cloud widget), regardless of the tags font size
	 */
	if ( $args['show_count'] || 0 !== $font_spread ) {
		$aria_label = true;
	}

	// Assemble the data that will be used to generate the tag cloud markup.
	$tags_data = array();
	foreach ( $tags as $key => $tag ) {
		$tag_id = isset( $tag->id ) ? $tag->id : $key;

		$count = $counts[ $key ];
		$real_count = $real_counts[ $key ];

		if ( $translate_nooped_plural ) {
			$formatted_count = sprintf( translate_nooped_plural( $translate_nooped_plural, $real_count ), number_format_i18n( $real_count ) );
		} else {
			$formatted_count = call_user_func( $args['topic_count_text_callback'], $real_count, $tag, $args );
		}

		$tags_data[] = array(
			'id'              => $tag_id,
			'url'             => '#' != $tag->link ? $tag->link : '#',
			'role'            => '#' != $tag->link ? '' : ' role="button"',
			'name'            => $tag->name,
			'formatted_count' => $formatted_count,
			'slug'            => $tag->slug,
			'real_count'      => $real_count,
			'class'           => $args['type'] ?'color'.rand(1,6):'',
			'font_size'       => $args['type'] ?'':'font-size: '.($args['smallest'] + ( $count - $min_count ) * $font_step),
			'aria_label'      => $aria_label ? sprintf( ' aria-label="%1$s (%2$s)"', esc_attr( $tag->name ), esc_attr( $formatted_count ) ) : '',
			'show_count'      => $args['show_count'] ? '<span> (' . $real_count . ')</span>' : '',
		);
	}

	/**
	 * Filters the data used to generate the tag cloud.
	 *
	 * @since 4.3.0
	 *
	 * @param array $tags_data An array of term data for term used to generate the tag cloud.
	 */
	$tags_data = apply_filters( 'wp_generate_tag_cloud_data', $tags_data );

	$a = array();

	// Generate the output links array.
	foreach ( $tags_data as $key => $tag_data ) {
		$class = $tag_data['class'];
		$a[] = sprintf(
			'<a href="%1$s"%2$s class="%3$s" style="%4$s;"%5$s>%6$s%7$s</a>',
			esc_url( $tag_data['url'] ),
			$tag_data['role'],
			esc_attr( $class ),
			esc_attr( str_replace( ',', '.', $tag_data['font_size'] ) . $args['unit'] ),
			$tag_data['aria_label'],
			esc_html( $tag_data['name'] ),
			$tag_data['show_count']
		);
	}

	switch ( $args['format'] ) {
		case 'array' :
			$return =& $a;
			break;
		case 'list' :
			/*
			 * Force role="list", as some browsers (sic: Safari 10) don't expose to assistive
			 * technologies the default role when the list is styled with `list-style: none`.
			 * Note: this is redundant but doesn't harm.
			 */
			$return = "\n\t<li class='category-list-item'>";
			$return .= join( "</li>\n\t<li class='category-list-item'>", $a );
			$return .= "</li>\n";
			break;
		default :
			$return = join( $args['separator'], $a );
			break;
	}

	if ( $args['filter'] ) {
		/**
		 * Filters the generated output of a tag cloud.
		 *
		 * The filter is only evaluated if a true value is passed
		 * to the $filter argument in wp_generate_tag_cloud().
		 *
		 * @since 2.3.0
		 *
		 * @see wp_generate_tag_cloud()
		 *
		 * @param array|string $return String containing the generated HTML tag cloud output
		 *                             or an array of tag links if the 'format' argument
		 *                             equals 'array'.
		 * @param array        $tags   An array of terms used in the tag cloud.
		 * @param array        $args   An array of wp_generate_tag_cloud() arguments.
		 */
		return apply_filters( 'wp_generate_tag_cloud', $return, $tags, $args );
	}

	else
		return $return;
}

?>