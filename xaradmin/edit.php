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
 * Edit an HTML tag
 *
 * @public
 * @author Richard Cave
 * @return array, or false on failure
 * @throws BAD_PARAM
 */
function html_admin_edit()
{
    // Security Check
    if (!xarSecurityCheck('EditHTML')) {
        return;
    }

    // Get parameters from input
    if (!xarVarFetch('id', 'int:0:', $id)) {
        return;
    }
    if (!xarVarFetch('tag', 'str:1:', $tag, '')) {
        return;
    }
    if (!xarVarFetch('confirm', 'int:0:1', $confirm, 0)) {
        return;
    }

    // Get the current html tag
    $html = xarModAPIFunc(
        'html',
        'user',
        'gettag',
        array('id' => $id)
    );

    // Check for exceptions
    if (!isset($html) && xarCurrentErrorType() != XAR_NO_EXCEPTION) {
        return;
    } // throw back

    // Check for confirmation.
    if (!$confirm) {

        // Specify for which html tag you want confirmation
        $data['id'] = $id;

        // Data to display in the template
        $data['tag'] = xarVarPrepForDisplay($html['tag']);
        $data['allowed'] = $html['allowed'];
        $data['editbutton'] = xarML('Submit');

        // Generate a one-time authorisation code for this operation
        $data['authid'] = xarSecGenAuthKey();

        // Return the template variables defined in this function
        return $data;
    }

    // If we get here it means that the user has confirmed the action

    // Confirm authorisation code
    if (!xarSecConfirmAuthKey()) {
        $msg = xarML(
            'Invalid authorization key for editing #(1) HTML tag #(2)',
            'HTML',
            xarVarPrepForDisplay($id)
        );
        return xarResponse::notFound();
    }

    // Modify the html tag
    if (!xarModAPIFunc(
        'html',
        'admin',
        'edit',
        array('id' => $id,
                             'tag' => $tag)
    )) {
        return; // throw back
    }

    xarSession::setVar('statusmsg', xarML('HTML Tag Updated'));

    // Redirect
    xarController::redirect(xarModURL('html', 'admin', 'set'));

    // Return
    return true;
}
