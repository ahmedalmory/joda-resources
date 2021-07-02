<?php

namespace Ahmedjoda\JodaResources;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Ahmedjoda\JodaResources\Skeleton\SkeletonClass
 */
class JodaResourcesFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'joda-resources';
    }
}
