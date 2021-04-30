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
    /** @var helper_plugin_aclinfo */
    protected $helper;

    /**
     * syntax_plugin_aclinfo constructor.
     */
    public function __construct() {
        $this->helper = plugin_load('helper', 'aclinfo');
    }

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

        if($format != 'xhtml') return false;

        if(!$data[0]) {
            $page = $INFO['id'];
        } else {
            $page = $data[0];
        }

        $info = $this->helper->getACLInfo($page);

        $R->listu_open();

        /*
         * Go through each entry of the ACL rules.
         */
        foreach($info as $entry){
            $R->listitem_open(1);
            $R->listcontent_open();
            $R->cdata($this->helper->getACLInfoString($entry));
            $R->listcontent_close();
            $R->listitem_close();
        }
        $R->listu_close();
        return true;
    }
}

//Setup VIM: ex: et ts=4 enc=utf-8 :
