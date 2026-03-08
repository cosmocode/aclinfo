<?php

use dokuwiki\Extension\SyntaxPlugin;

/**
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Andreas Gohr <andi@splitbrain.org>
 */
class syntax_plugin_aclinfo extends SyntaxPlugin
{
    /** @inheritdoc */
    public function getType()
    {
        return 'substition';
    }

    /** @inheritdoc */
    public function getPType()
    {
        return 'block';
    }

    /** @inheritdoc */
    public function getSort()
    {
        return 155;
    }

    /** @inheritdoc */
    public function connectTo($mode)
    {
        $this->Lexer->addSpecialPattern('~~ACLINFO!?[^~]*?~~', $mode, 'plugin_aclinfo');
    }

    /** @inheritdoc */
    public function handle($match, $state, $pos, Doku_Handler $handler)
    {
        $match = substr($match, 10, -2);
        return [$match];
    }

    /** @inheritdoc */
    public function render($format, Doku_Renderer $R, $data)
    {
        global $INFO;
        if ($format != 'xhtml') return false;

        if (!$data[0]) {
            $page = $INFO['id'];
        } else {
            $page = $data[0];
        }

        $perms = $this->aclCheck($page);
        $R->listu_open();
        foreach ($perms as $who => $p) {
            $R->listitem_open(1);
            $R->listcontent_open();
            $R->cdata(sprintf($this->getLang('perm' . $p), urldecode($who)));
            $R->listcontent_close();
            $R->listitem_close();
        }
        $R->listu_close();
        return true;
    }

    /**
     * Parse the ACL setup and return the permissions for the given page ID.
     *
     * @param string $id The page ID to check
     * @return array
     */
    protected function aclCheck($id)
    {
        global $AUTH_ACL;

        $id    = cleanID($id);
        $ns    = getNS($id);
        $perms = [];

        //check exact match first
        $matches = preg_grep('/^' . preg_quote($id, '/') . '\s+/', $AUTH_ACL);
        $perms = array_merge($perms, $this->processAclMatches($matches));

        //still here? do the namespace checks
        if ($ns) {
            $path = $ns . ':\*';
        } else {
            $path = '\*'; //root document
        }

        do {
            $matches = preg_grep('/^' . $path . '\s+/', $AUTH_ACL);
            $perms = array_merge($perms, $this->processAclMatches($matches));

            //get next higher namespace
            $ns   = getNS($ns);

            if ($path != '\*') {
                $path = $ns . ':\*';
                if ($path == ':\*') $path = '\*';
            } else {
                //we did this already
                //break here
                break;
            }
        } while (1); //this should never loop endless

        return $perms;
    }

    /**
     * Process ACL matches and return parsed permissions.
     *
     * @param array $matches Array of ACL lines to process
     * @return array Parsed permissions array
     */
    protected function processAclMatches(array $matches)
    {
        $perms = [];
        foreach ($matches as $match) {
            $match = preg_replace('/#.*$/', '', $match); //ignore comments
            $acl   = preg_split('/\s+/', $match);
            if ($acl[2] > AUTH_DELETE) $acl[2] = AUTH_DELETE; //no admins in the ACL!
            if (!isset($perms[$acl[1]])) $perms[$acl[1]] = $acl[2];
        }
        return $perms;
    }

}
