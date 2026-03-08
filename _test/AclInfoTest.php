<?php

namespace dokuwiki\plugin\aclinfo\test;

use DokuWikiTest;

/**
 * Tests for the aclinfo plugin
 *
 * @group plugin_aclinfo
 * @group plugins
 */
class AclInfoTest extends DokuWikiTest
{

    /**
     * @return array
     * @see testProcessAclMatches
     */
    public function provideProcessAclMatches()
    {
        return [
            'empty array' => [
                [],
                []
            ],
            'single user permission' => [
                ['wiki:page  user  1'],
                ['user' => 1]
            ],
            'multiple user permissions' => [
                ['wiki:page  user1  1', 'wiki:page  user2  2'],
                ['user1' => 1, 'user2' => 2]
            ],
            'comment removal' => [
                ['wiki:page  user  1  # this is a comment'],
                ['user' => 1]
            ],
            'admin capped to delete' => [
                ['wiki:page  admin  255'],
                ['admin' => 16] // AUTH_DELETE = 16
            ],
            'first permission wins for duplicate user' => [
                ['wiki:page  user  1', 'wiki:page  user  8'],
                ['user' => 1]
            ],
        ];
    }

    /**
     * @dataProvider provideProcessAclMatches
     * @param array $matches
     * @param array $expected
     */
    public function testProcessAclMatches(array $matches, array $expected)
    {
        $plugin = new \syntax_plugin_aclinfo();
        $result = $this->callInaccessibleMethod($plugin, 'processAclMatches', [$matches]);
        $this->assertEquals($expected, $result);
    }

    /**
     * @return array
     * @see testAclCheck
     */
    public function provideAclCheck()
    {
        return [
            'exact page match' => [
                'wiki:page',
                [
                    'wiki:page  @ALL     1',
                    'wiki:page  user1    2',
                ],
                ['@ALL' => 1, 'user1' => 2]
            ],
            'namespace match' => [
                'namespace:page',
                [
                    'namespace:*  @ALL  1',
                ],
                ['@ALL' => 1]
            ],
            'exact match takes precedence over namespace' => [
                'namespace:page',
                [
                    'namespace:page  user1  8',
                    'namespace:*    @ALL  1',
                ],
                ['user1' => 8, '@ALL' => 1]
            ],
            'root page namespace' => [
                ':page',
                [
                    '*  @ALL  1',
                ],
                ['@ALL' => 1]
            ],
            'empty namespace climbs to root' => [
                'namespace:sub:page',
                [
                    '*           @ALL  1',
                ],
                ['@ALL' => 1]
            ],
            'multiple namespace levels' => [
                'a:b:c:page',
                [
                    'a:b:c:page  user1  2',
                    'a:b:*       user2  4',
                    'a:*         user3  8',
                ],
                ['user1' => 2, 'user2' => 4, 'user3' => 8]
            ],
        ];
    }

    /**
     * @dataProvider provideAclCheck
     * @param string $id
     * @param array $authAcl
     * @param array $expected
     */
    public function testAclCheck(string $id, array $authAcl, array $expected)
    {
        global $AUTH_ACL;
        $AUTH_ACL = $authAcl;

        $plugin = new \syntax_plugin_aclinfo();
        $result = $this->callInaccessibleMethod($plugin, 'aclCheck', [$id]);

        $this->assertEquals($expected, $result);
    }

    public function testAclCheckNoMatch()
    {
        global $AUTH_ACL;
        $AUTH_ACL = [];

        $plugin = new \syntax_plugin_aclinfo();
        $result = $this->callInaccessibleMethod($plugin, 'aclCheck', ['nonexistent:page']);

        $this->assertEquals([], $result);
    }
}
