<?php
/**
 * DokuWiki Plugin aclinfo (Helper Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Frieder Schrempf <dev@fris.de>
 */

// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

/**
 * Class helper_plugin_aclinfo
 */
class helper_plugin_aclinfo extends DokuWiki_Plugin {

    /**
     * Create a list of file permissions for a page
     *
     * @param string $pageid
     */
    public function getACLInfo($page) {
        global $AUTH_ACL;

        $info = array();
        $subjects = array();

        /*
         * Get the permissions for @ALL in the beginning, we will use it
         * to compare and filter other permissions that are lower.
         */
        $allperm = auth_aclcheck($page, '', array('ALL'));

        /*
         * Go through each entry of the ACL rules.
         */
        foreach($AUTH_ACL as $rule){
            $rule = preg_replace('/#.*$/', '', $rule); // Ignore comments
            $subject = preg_split('/[ \t]+/', $rule)[1];
            $subject = urldecode($subject);
            $groups = array();
            $user = '';

            // Skip if we already checked this user/group
            if(in_array($subject, $subjects))
                continue;

            $subjects[] = $subject;

            // Check if this entry is about a user or a group (starting with '@')
            if(substr($subject, 0, 1) === '@')
                    $groups[] = substr($subject, 1);
            else
                    $user = $subject;

            $perm = auth_aclcheck($page, $user, $groups);

            // Skip permissions of 0 or if lower than @ALL
            if($perm == AUTH_NONE || ($subject != '@ALL' && $perm <= $allperm))
                continue;

            $info[] = array('subject' => $subject, 'perm' => $perm);
        }

        return $info;
    }

    /**
     * Get a string representation for the permission info
     *
     * @param array $info
     */
    public function getACLInfoString($info) {
        return sprintf($this->getLang('perm'.$info['perm']), $info['subject']);
    }
}
