<?php
/**
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Andreas Gohr <andi@splitbrain.org>
 * @author     Frieder Schrempf <dev@fris.de>
 */
// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'syntax.php');

/**
 * All DokuWiki plugins to extend the parser/rendering mechanism
 * need to inherit from this class
 */
class syntax_plugin_aclinfo extends DokuWiki_Syntax_Plugin {

    /**
     * What kind of syntax are we?
     */
    function getType(){
        return 'substition';
    }

    /**
     * What about paragraphs?
     */
    function getPType(){
        return 'block';
    }

    /**
     * Where to sort in?
     */
    function getSort(){
        return 155;
    }


    /**
     * Connect pattern to lexer
     */
    function connectTo($mode) {
        $this->Lexer->addSpecialPattern('~~ACLINFO!?[^~]*?~~',$mode,'plugin_aclinfo');
    }


    /**
     * Handle the match
     */
    function handle($match, $state, $pos, Doku_Handler $handler){
        $match = substr($match,10,-2);
        return array($match);
    }

    /**
     * Create output
     */
    function render($format, Doku_Renderer $R, $data) {
        global $INFO;
        global $AUTH_ACL;

        if($format != 'xhtml') return false;

        if(!$data[0]) {
            $page = $INFO['id'];
        } else {
            $page = $data[0];
        }

        $subjects = array();

        /*
         * Get the permissions for @ALL in the beginning, we will use it
         * to compare and filter other permissions that are lower.
         */
        $allperm = auth_aclcheck($page, '', array('ALL'));

        $R->listu_open();

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

            $R->listitem_open(1);
            $R->listcontent_open();
            $R->cdata(sprintf($this->getLang('perm'.$perm), $subject));
            $R->listcontent_close();
            $R->listitem_close();
        }
        $R->listu_close();
        return true;
    }
}

//Setup VIM: ex: et ts=4 enc=utf-8 :
