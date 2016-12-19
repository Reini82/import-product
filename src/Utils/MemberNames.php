<?php

/**
 * TechDivision\Import\Product\Utils\MemberNames
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * PHP version 5
 *
 * @author    Tim Wagner <t.wagner@techdivision.com>
 * @copyright 2016 TechDivision GmbH <info@techdivision.com>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://github.com/techdivision/import-product
 * @link      http://www.techdivision.com
 */

namespace TechDivision\Import\Product\Utils;

/**
 * Utility class containing the entities member names.
 *
 * @author    Tim Wagner <t.wagner@techdivision.com>
 * @copyright 2016 TechDivision GmbH <info@techdivision.com>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://github.com/techdivision/import-product
 * @link      http://www.techdivision.com
 */
class MemberNames extends \TechDivision\Import\Utils\MemberNames
{

    /**
     * This is a utility class, so protect it against direct
     * instantiation.
     */
    private function __construct()
    {
    }

    /**
     * This is a utility class, so protect it against cloning.
     *
     * @return void
     */
    private function __clone()
    {
    }

    /**
     * Name for the member 'code'.
     *
     * @var string
     */
    const CODE = 'code';

    /**
     * Name for the member 'attribute_code'.
     *
     * @var string
     */
    const ATTRIBUTE_CODE = 'attribute_code';

    /**
     * Name for the member 'attribute_set_id'.
     *
     * @var string
     */
    const ATTRIBUTE_SET_ID = 'attribute_set_id';

    /**
     * Name for the member 'attribute_set_name'.
     *
     * @var string
     */
    const ATTRIBUTE_SET_NAME = 'attribute_set_name';

    /**
     * Name for the member 'attribute_id'.
     *
     * @var string
     */
    const ATTRIBUTE_ID = 'attribute_id';

    /**
     * Name for the member 'entity_id'.
     *
     * @var string
     */
    const ENTITY_ID = 'entity_id';

    /**
     * Name for the member 'website_id'.
     *
     * @var string
     */
    const WEBSITE_ID = 'website_id';

    /**
     * Name for the member 'store_id'.
     *
     * @var string
     */
    const STORE_ID = 'store_id';

    /**
     * Name for the member 'backend_type'.
     *
     * @var string
     */
    const BACKEND_TYPE = 'backend_type';

    /**
     * Name for the member 'class_name'.
     *
     * @var string
     */
    const CLASS_NAME = 'class_name';

    /**
     * Name for the member 'class_id'.
     *
     * @var string
     */
    const CLASS_ID = 'class_id';

    /**
     * Name for the member 'value_id'.
     *
     * @var string
     */
    const VALUE_ID = 'value_id';

    /**
     * Name for the member 'frontend_input'.
     *
     * @var string
     */
    const FRONTEND_INPUT = 'frontend_input';

    /**
     * Name for the member 'option_id'.
     *
     * @var string
     */
    const OPTION_ID = 'option_id';

    /**
     * Name for the member 'path'.
     *
     * @var string
     */
    const PATH = 'path';

    /**
     * Name for the member 'value'.
     *
     * @var string
     */
    const VALUE = 'value';

    /**
     * Name for the member 'frontend_label'.
     *
     * @var string
     */
    const FRONTENT_LABEL = 'frontend_label';

    /**
     * Name for the member 'category_id'.
     *
     * @var string
     */
    const CATEGORY_ID = 'category_id';

    /**
     * Name for the member 'request_path'.
     *
     * @var string
     */
    const REQUEST_PATH = 'request_path';

    /**
     * Name for the member 'url_rewrite_id'.
     *
     * @var string
     */
    const URL_REWRITE_ID = 'url_rewrite_id';
}
