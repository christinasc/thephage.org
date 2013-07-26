thephage.org
============

The respository contains custom shortcodes for the <a href="http://thephage.org/camp-directory-2/"> directory </a>
and <a href="http://thephage.org/some-interesting-lists/"> interesting lists </a> on  thephage.org wordpress site
The shortcodes are a draft and quickly thrown together. Feel free to edit and refine.

<b>Shortcode Tutorials: </b>
<ul>
<li>http://wp.smashingmagazine.com/2012/05/01/wordpress-shortcodes-complete-guide/
<li>http://codex.wordpress.org/Shortcode_API
</ul>


<b>How this code is used: </b>

Example below for the wordpress page called <a href="http://thephage.org/some-interesting-lists/"> interesting lists </a>.
5 is the Id# of the Gravity Forms used for Camp Registration 2013.


======================================================================

Total Site Entries: <code> [totalsite form =5] </code>

Phagelings Going (<code> [totalattend form=5] </code>):

<code>
[participants form=5]
</code>

Going, but not with The Phage (<code>[totalnotattend form =5]</code>):

<code>[notwithphage form=5]</code>

Not Going:(<code>[totalnotgoing form=5]</code>):

(If not going, change 'Phage Camp Taxes?' to 'Not Going' in your Entry)
<code>[notgoing form=5]</code>

(Existing Users):
<code>[old_participants form=9]</code>
