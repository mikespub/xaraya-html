<?php
/**
 * HTML Module
 *
 * @package modules
 * @subpackage html module
 * @category Third Party Xaraya Module
 * @version 1.5.0
 * @copyright see the html/credits.html file in this release
 * @link http://www.xaraya.com/index.php/release/779.html
 * @author John Cox
 */

/**
 * Transform text
 *
 * @public
 * @author John Cox
 * @param $args['extrainfo'] string or array of text items
 * @return mixed string or array of transformed text items
 * @throws BAD_PARAM
 */
function html_userapi_transformoutput($args)
{
    // Get arguments from argument array
    extract($args);

    // Argument check
    if (!isset($extrainfo)) {
        $msg = xarML(
            'Invalid Parameter #(1) for #(2) function #(3)() in module #(4)',
            'extrainfo',
            'userapi',
            'transformoutput',
            'html'
        );
        throw new BadParameterException(null, $msg);
    }

    if (is_array($extrainfo)) {
        if (isset($extrainfo['transform']) && is_array($extrainfo['transform'])) {
            foreach ($extrainfo['transform'] as $key) {
                if (isset($extrainfo[$key])) {
                    $extrainfo[$key] = html_userapitransformoutput($extrainfo[$key]);
                }
            }
            return $extrainfo;
        }
        $transformed = array();
        foreach ($extrainfo as $text) {
            $transformed[] = html_userapitransformoutput($text);
        }
    } else {
        $transformed = html_userapitransformoutput($extrainfo);
    }

    return $transformed;
}

/**
 * Transform text api
 *
 * @private
 * @author John Cox
 * @author Matthew Mullenweg - credit for smart linebreak transforms
 */

function html_userapitransformoutput($text)
{
    /* include_once 'modules/bbcode/xarclass/stringparser_bbcode.class.php';
     $bbcode = new StringParser_BBCode();
     $dotransform = xarModVars::get('html', 'dolinebreak');
     if ($dotransform == 1){
         $bbcode->addParser(array ('block', 'inline', 'link', 'listitem'), 'nl2br');
         $bbcode->setRootParagraphHandling(true);
     }
     $text = $bbcode->parse($text);
     */

    if (strlen(trim($text)) == 0) {
        return '';
    }
    $dobreak = xarModVars::get('html', 'dobreak');
    $dotransform = xarModVars::get('html', 'dolinebreak');

    if ($dotransform == 1) {
        // just to make things a little easier, pad the end
        $text = $text . "\n";

        // Create a few entities where required.
        // TODO: transform < and > where they do not form part of a tag
        // Convert a free-standing '&' into '&amp;'
        $text = preg_replace('/&(?!#{0,1}[a-z0-9]+;)/i', "&amp;", $text);

        // Normalise existing breaks into newlines.
        $text = preg_replace('|<br />\s*<br />|', "\n\n", $text);

        $text = preg_replace('!(<(?:table|thead|tfoot|caption|colgroup|tbody|tr|td|th|div|dl|dd|dt|ul|ol|li|pre|select|form|blockquote|address|math|p|h[1-6])[^>]*>)!', "\n$1", $text);

        $text = preg_replace('!(</(?:table|thead|tfoot|caption|colgroup|tbody|tr|td|th|div|dl|dd|dt|ul|ol|li|pre|select|form|blockquote|address|math|p|h[1-6])>)!', "$1\n\n", $text);

        // cross-platform newlines
        $text = str_replace(array("\r\n", "\r"), "\n", $text);

        // take care of duplicaten newlines - turns runs of two or more into just two (treated as paragraphs)
        $text = preg_replace("/\n\n+/", "\n\n", $text);

        // make paragraphs, including one at the end
        $text = preg_replace('/\n?(.+?)(?:\n\s*\n|\z)/s', "<p>$1</p>\n", $text);

        // under certain strange conditions it could create a P of entirely whitespace
        $text = preg_replace('|<p>\s*?</p>|', '', $text);

        $text = preg_replace('!<p>\s*(</?(?:table|thead|tfoot|caption|colgroup|tbody|tr|td|th|div|dl|dd|dt|ul|ol|li|hr|pre|select|form|blockquote|address|math|p|h[1-6])[^>]*>)\s*</p>!', "$1", $text);

        // problem with nested lists
        $text = preg_replace("|<p>(<li.+?)</p>|", "$1", $text);

        $text = preg_replace('|<p><blockquote([^>]*)>|i', "<blockquote$1><p>", $text);

        $text = str_replace('</blockquote></p>', '</p></blockquote>', $text);

        $text = preg_replace('!<p>\s*(</?(?:table|thead|tfoot|caption|colgroup|tbody|tr|td|th|div|dl|dd|dt|ul|ol|li|hr|pre|select|form|blockquote|address|math|p|h[1-6])[^>]*>)!', "$1", $text);

        $text = preg_replace('!(</?(?:table|thead|tfoot|caption|colgroup|tbody|tr|td|th|div|dl|dd|dt|ul|ol|li|pre|select|form|blockquote|address|math|p|h[1-6])[^>]*>)\s*</p>!', "$1", $text);

        // Remove all <br>s after a block tag
        $text = preg_replace('!(</?(?:table|thead|tfoot|caption|tbody|tr|td|th|div|dl|dd|dt|ul|ol|li|pre|select|form|blockquote|address|math|p|h[1-6])[^>]*>)\s*<br />!', "$1", $text);

        //Remove the plain linebreak transform and add this one as optional
        //Add in another below now to compensate, but only add linebreaks not br tags in case html is not used (ieg bbcode)
        if ($dobreak == 1) {
            // A <br/> for a single newline, on its own, with no tags immediately surrounding it.
            // This allows breaks within a paragraph (where double-newlines define the paragraphs)
            // Preserve any additional white space
            $text =  preg_replace('/([^>]\s*)[\n](\s*[^<])/', '$1<br />'."\n".'$2', $text);
        }

        // Remove a <br> before a block tag
        // TODO: this does not include all block tags, h1-6, tables etc?
        $text = preg_replace('!<br />(\s*</?(?:p|li|div|dl|dd|dt|th|pre|td|ul|ol)>)!', '$1', $text);

        // Remove paragraphs and breaks from within any <pre> tags.
        $text = preg_replace('!(<pre.*?>)(.*?)</pre>!ise', " stripslashes('$1') .  stripslashes(html_userapitransformoutput_clean_pre('$2'))  . '</pre>' ", $text);

        // Since this is HTML now, it can be safely trimmed.
        $text = trim($text);
    } elseif ($dobreak == 1) { // just do line breaks
        $text =  preg_replace('/([^>]\s*)[\n](\s*[^<])/', '$1<br />'."\n".'$2', $text);
    }

    return $text;
}

// Remove paragraphs and breaks from within any <pre> tags.
function html_userapitransformoutput_clean_pre($text)
{
    $text = str_replace('<br />', '', $text);
    $text = str_replace('<p>', "\n", $text);
    $text = str_replace('</p>', '', $text);
    return $text;
}
