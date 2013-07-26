<?php
/**
 * WordPress API for creating bbcode like tags or what WordPress calls
 * "shortcodes." The tag and attribute parsing or regular expression code is
 * based on the Textpattern tag parser.
 *
 * A few examples are below:
 *
 * [shortcode /]
 * [shortcode foo="bar" baz="bing" /]
 * [shortcode foo="bar"]content[/shortcode]
 *
 * Shortcode tags support attributes and enclosed content, but does not entirely
 * support inline shortcodes in other shortcodes. You will have to call the
 * shortcode parser in your function to account for that.
 *
 * {@internal
 * Please be aware that the above note was made during the beta of WordPress 2.6
 * and in the future may not be accurate. Please update the note when it is no
 * longer the case.}}
 *
 * To apply shortcode tags to content:
 *
 * <code>
 * $out = do_shortcode($content);
 * </code>
 *
 * @link http://codex.wordpress.org/Shortcode_API
 *
 * @package WordPress
 * @subpackage Shortcodes
 * @since 2.5
 */

/**
 * Container for storing shortcode tags and their hook to call for the shortcode
 *
 * @since 2.5
 * @name $shortcode_tags
 * @var array
 * @global array $shortcode_tags
 */
$shortcode_tags = array();

/**
 * Add hook for shortcode tag.
 *
 * There can only be one hook for each shortcode. Which means that if another
 * plugin has a similar shortcode, it will override yours or yours will override
 * theirs depending on which order the plugins are included and/or ran.
 *
 * Simplest example of a shortcode tag using the API:
 *
 * <code>
 * // [footag foo="bar"]
 * function footag_func($atts) {
 * 	    return "foo = {$atts[foo]}";
 * }
 * add_shortcode('footag', 'footag_func');
 * </code>
 *
 * Example with nice attribute defaults:
 *
 * <code>
 * // [bartag foo="bar"]
 * function bartag_func($atts) {
 * 	    extract(shortcode_atts(array(
 *		'foo' => 'no foo',
 * 		      'baz' => 'default baz',
 * 		      ), $atts));
 *
 *	return "foo = {$foo}";
 * }
 * add_shortcode('bartag', 'bartag_func');
 * </code>
 *
 * Example with enclosed content:
 *
 * <code>
 * // [baztag]content[/baztag]
 * function baztag_func($atts, $content='') {
 * 	    return "content = $content";
 * }
 * add_shortcode('baztag', 'baztag_func');
 * </code>
 *
 * @since 2.5
 * @uses $shortcode_tags
 *
 * @param string $tag Shortcode tag to be searched in post content.
 * @param callable $func Hook to run when shortcode is found.
 */
function add_shortcode($tag, $func) {
	 global $shortcode_tags;

	 if ( is_callable($func) )
	    $shortcode_tags[$tag] = $func;
}

/**
 * Removes hook for shortcode.
 *
 * @since 2.5
 * @uses $shortcode_tags
 *
 * @param string $tag shortcode tag to remove hook for.
 */
function remove_shortcode($tag) {
	 global $shortcode_tags;

	 unset($shortcode_tags[$tag]);
}

/**
 * Clear all shortcodes.
 *
 * This function is simple, it clears all of the shortcode tags by replacing the
 * shortcodes global by a empty array. This is actually a very efficient method
 * for removing all shortcodes.
 *
 * @since 2.5
 * @uses $shortcode_tags
 */
function remove_all_shortcodes() {
	 global $shortcode_tags;

	 $shortcode_tags = array();
}

/**
 * Search content for shortcodes and filter shortcodes through their hooks.
 *
 * If there are no shortcode tags defined, then the content will be returned
 * without any filtering. This might cause issues when plugins are disabled but
 * the shortcode will still show up in the post or content.
 *
 * @since 2.5
 * @uses $shortcode_tags
 * @uses get_shortcode_regex() Gets the search pattern for searching shortcodes.
 *
 * @param string $content Content to search for shortcodes
 * @return string Content with shortcodes filtered out.
 */
function do_shortcode($content) {
	 global $shortcode_tags;

	 if (empty($shortcode_tags) || !is_array($shortcode_tags))
	    return $content;

	    $pattern = get_shortcode_regex();
	    return preg_replace_callback( "/$pattern/s", 'do_shortcode_tag', $content );
}

/**
 * Retrieve the shortcode regular expression for searching.
 *
 * The regular expression combines the shortcode tags in the regular expression
 * in a regex class.
 *
 * The regular expression contains 6 different sub matches to help with parsing.
 *
 * 1 - An extra [ to allow for escaping shortcodes with double [[]]
 * 2 - The shortcode name
 * 3 - The shortcode argument list
 * 4 - The self closing /
 * 5 - The content of a shortcode when it wraps some content.
 * 6 - An extra ] to allow for escaping shortcodes with double [[]]
 *
 * @since 2.5
 * @uses $shortcode_tags
 *
 * @return string The shortcode search regular expression
 */
function get_shortcode_regex() {
	 global $shortcode_tags;
	 $tagnames = array_keys($shortcode_tags);
	 $tagregexp = join( '|', array_map('preg_quote', $tagnames) );

	 // WARNING! Do not change this regex without changing do_shortcode_tag() and strip_shortcode_tag()
	 // Also, see shortcode_unautop() and shortcode.js.
	 return
		  '\\['                              // Opening bracket
		  				     . '(\\[?)'                           // 1: Optional second opening bracket for escaping shortcodes: [[tag]]
						       . "($tagregexp)"                     // 2: Shortcode name
						       	 . '(?![\\w-])'                       // Not followed by word character or hyphen
							   . '('                                // 3: Unroll the loop: Inside the opening shortcode tag
							     .     '[^\\]\\/]*'                   // Not a closing bracket or forward slash
							     	   .     '(?:'
								   	 .         '\\/(?!\\])'               // A forward slash not followed by a closing bracket
									 	   .         '[^\\]\\/]*'               // Not a closing bracket or forward slash
										   	     .     ')*?'
											     	   . ')'
												     . '(?:'
												       .     '(\\/)'                        // 4: Self closing tag ...
												       	     .     '\\]'                          // ... and closing bracket
													     	   . '|'
														     .     '\\]'                          // Closing bracket
														     	   .     '(?:'
															   	 .         '('                        // 5: Unroll the loop: Optionally, anything between the opening and closing shortcode tags
																 	   .             '[^\\[]*+'             // Not an opening bracket
																	   		 .             '(?:'
																			 	       .                 '\\[(?!\\/\\2\\])' // An opening bracket not followed by the closing shortcode tag
																				       			 .                 '[^\\[]*+'         // Not an opening bracket
																							 		   .             ')*+'
																									   		 .         ')'
																											 	   .         '\\[\\/\\2\\]'             // Closing shortcode tag
																												   	     .     ')?'
																													     	   . ')'
																														     . '(\\]?)';                          // 6: Optional second closing brocket for escaping shortcodes: [[tag]]
}

/**
 * Regular Expression callable for do_shortcode() for calling shortcode hook.
 * @see get_shortcode_regex for details of the match array contents.
 *
 * @since 2.5
 * @access private
 * @uses $shortcode_tags
 *
 * @param array $m Regular expression match array
 * @return mixed False on failure.
 */
function do_shortcode_tag( $m ) {
	 global $shortcode_tags;

	 // allow [[foo]] syntax for escaping a tag
	 if ( $m[1] == '[' && $m[6] == ']' ) {
	    return substr($m[0], 1, -1);
	    }

	    $tag = $m[2];
	    $attr = shortcode_parse_atts( $m[3] );

	    if ( isset( $m[5] ) ) {
	       // enclosing tag - extra parameter
	       	  return $m[1] . call_user_func( $shortcode_tags[$tag], $attr, $m[5], $tag ) . $m[6];
		  } else {
		    // self-closing tag
		       return $m[1] . call_user_func( $shortcode_tags[$tag], $attr, null,  $tag ) . $m[6];
		       }
}

/**
 * Retrieve all attributes from the shortcodes tag.
 *
 * The attributes list has the attribute name as the key and the value of the
 * attribute as the value in the key/value pair. This allows for easier
 * retrieval of the attributes, since all attributes have to be known.
 *
 * @since 2.5
 *
 * @param string $text
 * @return array List of attributes and their value.
 */
function shortcode_parse_atts($text) {
	 $atts = array();
	 $pattern = '/(\w+)\s*=\s*"([^"]*)"(?:\s|$)|(\w+)\s*=\s*\'([^\']*)\'(?:\s|$)|(\w+)\s*=\s*([^\s\'"]+)(?:\s|$)|"([^"]*)"(?:\s|$)|(\S+)(?:\s|$)/';
	 $text = preg_replace("/[\x{00a0}\x{200b}]+/u", " ", $text);
	 if ( preg_match_all($pattern, $text, $match, PREG_SET_ORDER) ) {
	    foreach ($match as $m) {
	    	    	    if (!empty($m[1]))
					$atts[strtolower($m[1])] = stripcslashes($m[2]);
								   elseif (!empty($m[3]))
											$atts[strtolower($m[3])] = stripcslashes($m[4]);
														   elseif (!empty($m[5]))
														   	    $atts[strtolower($m[5])] = stripcslashes($m[6]);
															    			       elseif (isset($m[7]) and strlen($m[7]))
																		       	      		    	$atts[] = stripcslashes($m[7]);
																							  elseif (isset($m[8]))
																							  	   $atts[] = stripcslashes($m[8]);
																								   	   }
																									   } else {
																									     $atts = ltrim($text);
																									     }
																									     return $atts;
}

/**
 * Combine user attributes with known attributes and fill in defaults when needed.
 *
 * The pairs should be considered to be all of the attributes which are
 * supported by the caller and given as a list. The returned attributes will
 * only contain the attributes in the $pairs list.
 *
 * If the $atts list has unsupported attributes, then they will be ignored and
 * removed from the final returned list.
 *
 * @since 2.5
 *
 * @param array $pairs Entire list of supported attributes and their defaults.
 * @param array $atts User defined attributes in shortcode tag.
 * @return array Combined and filtered attribute list.
 */
function shortcode_atts($pairs, $atts) {
	 $atts = (array)$atts;
	 $out = array();
	 foreach($pairs as $name => $default) {
	 		if ( array_key_exists($name, $atts) )
			     $out[$name] = $atts[$name];
			     		 else
							$out[$name] = $default;
							}
							return $out;
}

/**
 * Remove all shortcode tags from the given content.
 *
 * @since 2.5
 * @uses $shortcode_tags
 *
 * @param string $content Content to remove shortcode tags.
 * @return string Content without shortcode tags.
 */
function strip_shortcodes( $content ) {
	 global $shortcode_tags;

	 if (empty($shortcode_tags) || !is_array($shortcode_tags))
	    return $content;

	    $pattern = get_shortcode_regex();

	    return preg_replace_callback( "/$pattern/s", 'strip_shortcode_tag', $content );
}

function strip_shortcode_tag( $m ) {
	 // allow [[foo]] syntax for escaping a tag
	 if ( $m[1] == '[' && $m[6] == ']' ) {
	    return substr($m[0], 1, -1);
	    }

	    return $m[1] . $m[6];
}

add_filter('the_content', 'do_shortcode', 11); // AFTER wpautop()


function wptuts_first_shortcode($atts, $content=null){
  
    $post_url = get_permalink($post->ID);
    $post_title = get_the_title($post->ID);
    $tweet = '<a href="http://twitter.com/home/?status=Read ' . $post_title . ' at ' . $post_url . '">Share on Twitter</a>';
  
    return $tweet;
}
  
add_shortcode('twitter', 'wptuts_first_shortcode');
 

function wptuts_youtube($atts, $content=null){

	 extract(shortcode_atts( array('id' => ''), $atts));

	 $return = $content;
	 if($content)
		$return .= "<br /><br />";
		
		$return .= '<iframe width="560" height="349" src="http://www.youtube.com/embed/' . $id . '" frameborder="0" allowfullscreen></iframe>';
		
		return $return; 

}
add_shortcode('youtube', 'wptuts_youtube');

// README:http://www.gravityhelp.com/forums/topic/collecting-email-addresses
// shortcode usage: [submitted_emails form='FORMID']
add_shortcode('submitted_emails', 'retrieve_emails');
function retrieve_emails($emails) {
        $form_id = $emails['form'];
        // get all the field values for one form and one field ID
        $addresses = RGFormsModel::get_leads($form_id, '1', 'ASC', $search='', $offset=0, $page_size=150);
        // initialize the HTML we are going to return
        $html = "<ul class='email_addresses'>\n";
        // loop through all the leads
	foreach ($addresses as $email) {
		$one_email = $email['1'];
                $html .= "\t<li>$one_email</li>\n";
        }
        $html .= '</ul>';
        // return the list
        return $html;
}


// http://www.gravityhelp.com/forums/topic/query-submitted-forms-from-custom-page#post-39607
// create shortcode to return names of participants for a specific form
// usage: [participants form=37] where 37 is the form ID

add_shortcode('participants', 'phage_participants');
function phage_participants($atts) {
        $form_id = $atts['form'];
        // function to pull entries from one form
        $scouts = RGFormsModel::get_leads($form_id, '2.6', 'ASC', $search='', $offset=0, $page_size=150);
        $html = "<ul class='participants'>\n";
        // loop through all the returned results
        foreach ($scouts as $participant) {
                // field 2.3 is the first name. I upper cased the first letter for consistency
               // $fname    = ucfirst($participant['2.3']);
               
                $goingstatus = $participant['27'];
                $taxstatus = $participant['4'];
                $entryid = $participant['id'];
       
                if (!strcmp($taxstatus, "regular") || !strcmp($taxstatus, "Exemption Code")) {                    
                    $fname    = $participant['2.3'];
                    $lname = $participant['2.6'];
           	        $email = $participant['1'];
                    $linitial = strtoupper(substr($participant['2.6'],0,1));
                    $html .= "\t<li><a href=\"/wp-admin/admin.php?page=gf_entries&view=entry&id=5&lid=$entryid&filter=\">$fname $lname </a> (<a href=\"mailto:$email\">$email</a>)  </li> \n";
                }
                
        }
        $html .= '</ul>';
        // return the html output from the shorcode
        return $html;
}

add_shortcode('notgoing', 'nogo');
function nogo($atts) {
        $form_id = $atts['form'];
        // function to pull entries from one form
        $scouts = RGFormsModel::get_leads($form_id, '2.6', 'ASC', $search='', $offset=0, $page_size=150);
        $html = "<ul class='participants'>\n";
        // loop through all the returned results
        foreach ($scouts as $participant) {
                // field 2.3 is the first name. I upper cased the first letter for consistency
               // $fname    = ucfirst($participant['2.3']);
               
                $goingstatus = $participant['27'];
                $taxstatus = $participant['4'];
                $entryid = $participant['id'];
       
                if (!strcmp($taxstatus, "Not Going")) {                    
                    $fname    = $participant['2.3'];
                    $lname = $participant['2.6'];
           	        $email = $participant['1'];
                    $linitial = strtoupper(substr($participant['2.6'],0,1));
                    $html .= "\t<li><a href=\"/wp-admin/admin.php?page=gf_entries&id=5&lid=$entryid\"> $fname $lname </a> (<a href=\"mailto:$email\">$email</a>) </li> \n";
                }
                
        }
        $html .= '</ul>';
        // return the html output from the shorcode
        return $html;
}


add_shortcode('notwithphage', 'nonphage');
function nonphage($atts) {
        $form_id = $atts['form'];
        $scouts = RGFormsModel::get_leads($form_id, '2.6', 'ASC', $search='', $offset=0, $page_size=150);
        $html = "<ul class='participants'>\n";
        foreach ($scouts as $participant) {       

                $taxstatus = $participant['4'];
                $goingstatus = $participant['27'];

           if (!strcmp($taxstatus, "Going but not with Phage")) {                    
                $fname    = $participant['2.3'];
                $lname = $participant['2.6'];
           	$email = $participant['1'];
           	$entryid = $participant['id'];
                $linitial = strtoupper(substr($participant['2.6'],0,1));
                 $html .= "\t<li><a href=\"/wp-admin/admin.php?page=gf_entries&id=5&lid=$entryid\"> $fname $lname </a> (<a href=\"mailto:$email\">$email</a>) </li> \n";
           }
                
        }
        $html .= '</ul>';
        // return the html output from the shorcode
        return $html;
}




add_shortcode('old_participants', 'old_phage_participants');
function old_phage_participants($atts) {
        $form_id = $atts['form'];
        // function to pull entries from one form
        $scouts = RGFormsModel::get_leads($form_id, '2.6', 'ASC', $search='', $offset=0, $page_size=150);
        $html = "<ul class='participants'>\n";
        // loop through all the returned results
        foreach ($scouts as $participant) {
                // field 2.3 is the first name. I upper cased the first letter for consistency
               // $fname    = ucfirst($participant['2.3']);
                $fname    = $participant['2.3'];
                $lname = $participant['2.6'];
           	$email = $participant['27'];
                $html .= "\t<li>$fname $lname (<a href=\"mailto:$email\">$email</a>) </li>\n";
        }
        $html .= '</ul>';
        // return the html output from the shorcode
        return $html;
}


add_shortcode('totalsite', 'totalSiteEntries');
function totalSiteEntries($atts) { 
	 $html = "";
	 $total = 0;
	 $form_id = $atts['form'];
        // function to pull entries from one form
        $scouts = RGFormsModel::get_leads($form_id, '2.6', 'ASC', $search='', $offset=0, $page_size=150);
        foreach ($scouts as $participant) { 
         	$total++;
        }
        $html = $total.$html;
        return $html;
}


add_shortcode('totalattend', 'totalentries');
function totalentries($atts) { 
	 $html = "";
	 $total = 0;
	 $form_id = $atts['form'];
        // function to pull entries from one form
        $scouts = RGFormsModel::get_leads($form_id, '2.6', 'ASC', $search='', $offset=0, $page_size=150);
        foreach ($scouts as $participant) { 
          $taxstatus = $participant['4'];
          if (!strcmp($taxstatus, "regular") || !strcmp($taxstatus, "Exemption Code") ) {  
          $total++;
          }
        }
        $html = $total.$html;
        return $html;
}


add_shortcode('totalnotattend', 'totalNotGoingEntries');
function totalNotGoingEntries($atts) { 
	 $html = "";
	 $total = 0;
	 $form_id = $atts['form'];
        // function to pull entries from one form
        $scouts = RGFormsModel::get_leads($form_id, '2.6', 'ASC', $search='', $offset=0, $page_size=150);
        foreach ($scouts as $participant) { 
          $taxstatus = $participant['4'];
          if (!strcmp($taxstatus, "Going but not with Phage") ) {  
          $total++;
          }
        }
        $html = $total.$html;
        return $html;
}



add_shortcode('totalnotgoing', 'totalNotGoingAtAllEntries');
function totalNotGoingAtAllEntries($atts) { 
	 $html = "";
	 $total = 0;
	 $form_id = $atts['form'];
        // function to pull entries from one form
        $scouts = RGFormsModel::get_leads($form_id, '2.6', 'ASC', $search='', $offset=0, $page_size=150);
        foreach ($scouts as $participant) { 
          $taxstatus = $participant['4'];
          if (!strcmp($taxstatus, "Not Going") ) {  
          $total++;
          }
        }
        $html = $total.$html;
        return $html;
}


add_shortcode('phagedir', 'entries');
function entries($atts) {
        $form_id = $atts['form'];
        // function to pull entries from one form
        $scouts = RGFormsModel::get_leads($form_id, '2.6', 'ASC', $search='', $offset=0, $page_size=150);
        $html = "<table border=\"0\" cellspacing=\"0\" cellpadding=\"0\"
           class=\"light\">";
        // loop through all the returned results
        foreach ($scouts as $participant) {
                // field 2.3 is the first name. 
                $fname    = $participant['2.3'];
                $lname = $participant['2.6'];
 
	$photo = $participant['26'];
	       $email = $participant['1'];
	       	      $preplaya = $participant['18'];
		      		$newbie = $participant['14'];					
						$sponsor = strlen($participant['17']) > 0 ?  "<strong> Newbie Inducted by: </strong>".$participant['17'] : "";
							 $playaname = strlen($participant['30']) > 0 ? " <strong> Playaname: </strong>".$participant['30'] : "";
							 	    $phone = strlen($participant['3']) > 0 ?  " <strong> Phone: </strong>".$participant['3'] : "";
								    	   $hometown = strlen($participant['29']) > 0 ?  " <strong> Hometown: </strong>".$participant['29'] : "";

									   	     $taxstatus = strlen($participant['4']) > 0 ?  "<strong> Camp Status: </strong>".$participant['4'] : "";
										     		$entryid = $participant['id'];
												                
															$about = strlen($participant['25']) >= GFORMS_MAX_FIELD_LENGTH ? RGFormsModel::get_field_value_long($participant, 25, $form_id, false): $participant['25'];
                
			
				$html .= "<div> <h4>$fname $lname <a href=\"/wp-admin/admin.php?page=gf_entries&view=entry&id=5&lid=$entryid&filter=\"> [Edit] </a> </h4> ";
				      $html .= " <img src=\"$photo\"> </div> &nbsp;";
				      	    
						$html .= "<div> $playaname &nbsp; </div> ";
						      
							$html .= "<div> <strong> Email: </strong> <a href=\"mailto:$email\">$email</a> </div>";
							      $html .= "<div> $phone </div> ";
							      	    $html .= "<div> $sponsor </div> ";
								    	  $html .= "<div> $hometown </div> ";
									  	$html .= "<div> $taxstatus </div> ";

										      	 	
													$html .= "<div> <strong> Location Preplaya:</strong> $preplaya </div> ";
													      
														$html .= "&nbsp; </div>";
														      $html .= "<div> <p> $about </p> </div> ";
                $html .= "<div> &nbsp; </div> ";
		      $html .= "<div> <hr> </div> ";		
        }
        $html .= '</table>';
        return $html;
 }