<?php
/**
 * Created by PhpStorm.
 * User: benakamoorthi
 * Date: 7/10/18
 * Time: 4:47 PM
 */

namespace Piwik\Plugins\UsersManager\Sql;


use Piwik\Common;

class SiteAccessFilter
{
    /**
     * @var string
     */
    private $filterByRole;

    /**
     * @var int
     */
    private $userLogin;

    /**
     * @var string
     */
    private $filterSearch;

    public function __construct($userLogin, $filterSearch, $filterByRole)
    {
        if (empty($userLogin)) {
            throw new \InvalidArgumentException("filtering by role is only supported for a single site");
        }

        $this->userLogin = $userLogin;
        $this->filterSearch = $filterSearch;
        $this->filterByRole = $filterByRole;
    }

    public function getJoins($accessTable)
    {
        $result = 'INNER JOIN ' . Common::prefixTable('user') . " u ON u.login = $accessTable.login
                   INNER JOIN ". Common::prefixTable('site') . " s ON s.idsite = $accessTable.idsite";
        $bind = [];

        return [$result, $bind];
    }

    public function getWhere()
    {
        $bind = [$this->userLogin];
        $result = 'WHERE u.login = ?';

        if ($this->filterSearch) {
            $bind = array_merge($bind, \Piwik\Plugins\SitesManager\Model::getPatternMatchSqlBind($this->filterSearch));
            $result .= ' AND ' . \Piwik\Plugins\SitesManager\Model::getPatternMatchSqlQuery('s');
        }

        if ($this->filterByRole && $this->filterByRole != 'some') { // TODO: automated test w/ 'some' in methods that use it
            $result .= ' AND a.access = ?';
            $bind[] = $this->filterByRole;
        }

        return [$result, $bind];
    }
}