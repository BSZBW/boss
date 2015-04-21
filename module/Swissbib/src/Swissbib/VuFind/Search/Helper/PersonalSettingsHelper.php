<?php
namespace Swissbib\VuFind\Search\Helper;

use VuFind\Auth\Manager;
use Zend\Stdlib\Parameters;

/**
 * Helper to control the application behaviour related to some personal settings
 * up to now:
 * a) length of result list
 * b) sorting of result list
 *
 * @category swissbib / VuFind2
 * @package  VuFind/Search
 * @author   Demian Katz <guenter.hipler@unibas.ch>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:developer_manual Wiki
 *
 * @codeCoverageIgnore
 */

/*
 * GH: first time I used the new PHP trait functionality introduced with PHP 5.4
 * What I don't like about the current implementation is the dependency of the trait related to the clients using the trait type
 * this is the reason for the return value of handle sort and the longer parameter list
 * On the other hand I already use class members of the client class in the trait - which is handy. An argument for this might be these parameters are only
 * of the base class.
 * We have to get better experiences related to this mechanism
 */
trait PersonalSettingsHelper {


    public function handleLimit(Manager $manager, Parameters $request, $defaultLimit, $legalOptions, $view)
    {
        $user = $manager->isLoggedIn();

        $requestParams = $request->toArray();
        if ($user)
        {
            //in case user changed the the limit with a UI control on the result list or the advanced search page
            //we want to serialize the new value in database
            if (array_key_exists('limitControlElement',$requestParams) || array_key_exists('advancedSearchFormRequest',$requestParams))
            {
                if (array_key_exists('limit',$requestParams))
                {
                    $user->max_hits = (int) $requestParams['limit'];
                    $user->save();
                    $limit =  $requestParams['limit'];
                } else {
                    $limit = $tLimit = $request->get('limit') != $defaultLimit ? $request->get('limit') : $defaultLimit;
                }
            } else
            {
                //check if there is a stored value in database. If not use the request or default value
                if ($user->max_hits) {
                    $limit = $user->max_hits;
                } else {
                    $limit  =  $tLimit = $request->get('limit') != $defaultLimit ? $request->get('limit') : $defaultLimit;
                }
            }
        } else {
            $limit  =  $tLimit = $request->get('limit') != $defaultLimit ? $request->get('limit') : $defaultLimit;
        }

        if (in_array($limit, $legalOptions)
            || ($limit > 0 && $limit < max($legalOptions))
        ) {
            $this->limit = $limit;
            return;
        }


        if ($view == 'rss' && $defaultLimit < 50) {
            $defaultLimit = 50;
        }

        // If we got this far, setting was missing or invalid; load the default
        $this->limit = $defaultLimit;


    }

    public function handleSort (Manager $manager, Parameters $request, $defaultSort, $target)
    {
        $user = $manager->isLoggedIn();
        $requestParams = $request->toArray();
        if ($user)
        {
            //in case user changed the the sort settings on the result list with a specialized UI control
            //we want to serialize the new value in database
            if (array_key_exists('sortControlElement',$requestParams))
            {
                if (array_key_exists('sort',$requestParams))
                {
                    $sort =  $requestParams['sort'];
                    $dbSort = unserialize($user->default_sort);
                    $dbSort[$target] = $requestParams['sort'];
                    $user->default_sort = serialize($dbSort);
                    $user->save();
                } else {
                    $tSort = $request->get('sort');
                    $sort = !empty($tSort) ? $tSort : $defaultSort;
                }
            } else
            {
                //check if there is a value stored for sort in the database
                //if not use the request or default value
                if ($user->default_sort) {
                    $userDefaultSort = unserialize($user->default_sort);
                    $userDefaultSort = $userDefaultSort[$target];
                    $sort = $userDefaultSort;
                } else {
                    $tSort = $request->get('sort');
                    $sort = !empty($tSort) ? $tSort : $defaultSort;
                }
            }
        } else {
            $sort = $request->get('sort');
        }

        // Check for special parameter only relevant in RSS mode:
        if ($request->get('skip_rss_sort', 'unset') != 'unset') {
            $this->skipRssSort = true;
        }

        return $sort;

    }


}