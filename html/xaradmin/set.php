<?php
/**
 * File: $Id$
 *
 * Xaraya HTML Module
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2002 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.org
 *
 * @subpackage HTML Module
 * @author John Cox
*/

/**
 * Set the allowed HTML 
 *
 * @public
 * @author John Cox 
 * @author Richard Cave 
 */
function html_admin_set()
{
    // Initialise the variable
    $data['items'] = array();

    // Specify some labels for display
    $data['submitlabel'] = xarML('Submit');
    $data['authid'] = xarSecGenAuthKey();

    // Security Check
    if(!xarSecurityCheck('AdminHTML')) return;

    // The user API function is called.
    $allowed = xarModAPIFunc('html',
                             'user',
                             'getalltags');

    // Check for exceptions
    if (!isset($allowed) && xarExceptionMajor() != XAR_NO_EXCEPTION) return; // throw back

    // Add the edit and delete urls
    for ($idx = 0; $idx < count($allowed); $idx++) {
        if (xarSecurityCheck('EditHTML')) { 
            $allowed[$idx]['editurl'] = xarModURL('html',
                                                  'admin',
                                                  'edit',
                                                  array('cid' => $allowed[$idx]['cid']));
        } else {
            $allowed[$idx]['editurl'] = '';
        }

        if (xarSecurityCheck('DeleteHTML')) { 
            $allowed[$idx]['deleteurl'] = xarModURL('html',
                                                    'admin',
                                                    'delete',
                                                    array('cid' => $allowed[$idx]['cid']));
        } else {
            $allowed[$idx]['deleteurl'] = '';
        }
    }

    // Add the array of items to the template variables
    $data['items'] = $allowed;

    // Return the template variables defined in this function
    return $data;
}

?>
