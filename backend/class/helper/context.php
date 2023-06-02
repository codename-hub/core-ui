<?php

namespace codename\core\ui\helper;

use codename\core\app;
use codename\core\exception;
use codename\core\model;

/**
 * helper for context
 */
class context
{
    /**
     * @var int
     */
    protected static int $paginationDefaultLimit = 10;
    /**
     * @var array|int[]
     */
    protected static array $paginationPageSizes = [ 3, 5, 10, 20, 50, 100, 200 ];


    public function __construct()
    {
    }

    /**
     * @param int $defaultLimit
     * @return void
     */
    public static function setPaginationDefaultLimit(int $defaultLimit): void
    {
        self::$paginationDefaultLimit = $defaultLimit;
    }

    /**
     * @param array $pageSizes
     * @return void
     */
    public static function setPaginationPageSizes(array $pageSizes): void
    {
        self::$paginationPageSizes = $pageSizes;
    }

    /**
     * @param int|array|model $modelCount
     * @param array|model $modelPagination
     * @return void
     * @throws exception
     */
    public static function addPagination(int|array|model $modelCount, array|model &$modelPagination): void
    {
        //
        // handle pagination
        //
        if (is_int($modelCount)) {
            $count = $modelCount;
        } elseif (is_array($modelCount)) {
            $count = count($modelCount);
        } else {
            $count = $modelCount->getCount();
        }

        // default value, if none of the below works:
        $page = 1;
        if (app::getRequest()->isDefined('pagination_page')) {
            // explicit page request
            $page = (int)app::getRequest()->getData('pagination_page');
        }

        if (app::getRequest()->isDefined('pagination_limit')) {
            $limit = (int)app::getRequest()->getData('pagination_limit');
        } else {
            $limit = self::$paginationDefaultLimit;
        }

        if (!in_array($limit, self::$paginationPageSizes)) {
            // default fallback
            $limit = self::$paginationDefaultLimit;
        }

        $pages = ($limit == 0 || $count == 0) ? 1 : ceil($count / $limit);

        // pagination limit change with present page param, that is out of range:
        if ($page > $pages) {
            $page = $pages;
        }

        if ($pages > 1) {
            if (is_array($modelPagination)) {
                $modelPagination = array_slice($modelPagination, ($page - 1) * $limit, $limit);
            } else {
                $modelPagination->setLimit($limit)->setOffset(($page - 1) * $limit);
            }
        }

        app::getResponse()->setData('pagination', [
          'pagination_count' => $count,
          'pagination_page' => $page,
          'pagination_page_count' => $pages,
          'pagination_sizes' => self::$paginationPageSizes,
          'pagination_limit' => $limit,
        ]);
    }

}
