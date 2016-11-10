<?php

/**
 * TechDivision\Import\Product\Subjects\BunchHandler
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * PHP version 5
 *
 * @author    Tim Wagner <tw@appserver.io>
 * @copyright 2015 TechDivision GmbH <info@appserver.io>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://github.com/wagnert/csv-import
 * @link      http://www.appserver.io
 */

namespace TechDivision\Import\Product\Subjects;

use Goodby\CSV\Export\Standard\Exporter;
use Goodby\CSV\Export\Standard\ExporterConfig;
use TechDivision\Import\Subjects\AbstractSubject;
use TechDivision\Import\Utils\RegistryKeys;
use TechDivision\Import\Services\RegistryProcessor;
use TechDivision\Import\Product\Utils\MemberNames;
use TechDivision\Import\Product\Utils\VisibilityKeys;
use TechDivision\Import\Product\Services\ProductProcessorInterface;

/**
 * A SLSB that handles the process to import product bunches.
 *
 * @author    Tim Wagner <tw@appserver.io>
 * @copyright 2015 TechDivision GmbH <info@appserver.io>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://github.com/wagnert/csv-import
 * @link      http://www.appserver.io
 */
class BunchSubject extends AbstractSubject
{

    /**
     * The processor to read/write the necessary product data.
     *
     * @var \TechDivision\Import\Product\Services\ProductProcessorInterface
     */
    protected $productProcessor;

    /**
     * The mapping for the supported backend types (for the product entity) => persist methods.
     *
     * @var array
     */
    protected $backendTypes = array(
        'datetime' => 'persistProductDatetimeAttribute',
        'decimal'  => 'persistProductDecimalAttribute',
        'int'      => 'persistProductIntAttribute',
        'text'     => 'persistProductTextAttribute',
        'varchar'  => 'persistProductVarcharAttribute'
    );

    /**
     * Mappings for attribute code => CSV column header.
     *
     * @var array
     */
    protected $headerMappings = array(
        'status' => 'product_online',
        'tax_class_id' => 'tax_class_name',
        'price_type'  => 'bundle_price_type',
        'sku_type' => 'bundle_sku_type',
        'price_view' => 'bundle_price_view',
        'weight_type' => 'bundle_weight_type',
        'image' => 'base_image',
        'image_label' => 'base_image_label',
        'thumbnail' => 'thumbnail_image',
        'thumbnail_label' => 'thumbnail_image_label'
    );

    /**
     * Mappings for the table column => CSV column header.
     *
     * @var array
     */
    protected $headerStockMappings = array(
        'qty'                         => array('qty', 'float'),
        'min_qty'                     => array('out_of_stock_qty', 'float'),
        'use_config_min_qty'          => array('use_config_min_qty', 'int'),
        'is_qty_decimal'              => array('is_qty_decimal', 'int'),
        'backorders'                  => array('allow_backorders', 'int'),
        'use_config_backorders'       => array('use_config_backorders', 'int'),
        'min_sale_qty'                => array('min_cart_qty', 'float'),
        'use_config_min_sale_qty'     => array('use_config_min_sale_qty', 'int'),
        'max_sale_qty'                => array('max_cart_qty', 'float'),
        'use_config_max_sale_qty'     => array('use_config_max_sale_qty', 'int'),
        'is_in_stock'                 => array('is_in_stock', 'int'),
        'notify_stock_qty'            => array('notify_on_stock_below', 'float'),
        'use_config_notify_stock_qty' => array('use_config_notify_stock_qty', 'int'),
        'manage_stock'                => array('manage_stock', 'int'),
        'use_config_manage_stock'     => array('use_config_manage_stock', 'int'),
        'use_config_qty_increments'   => array('use_config_qty_increments', 'int'),
        'qty_increments'              => array('qty_increments', 'float'),
        'use_config_enable_qty_inc'   => array('use_config_enable_qty_inc', 'int'),
        'enable_qty_increments'       => array('enable_qty_increments', 'int'),
        'is_decimal_divided'          => array('is_decimal_divided', 'int'),
    );

    /**
     * The array with the available visibility keys.
     *
     * @var array
     */
    protected $availableVisibilities = array(
        'Not Visible Individually' => VisibilityKeys::VISIBILITY_NOT_VISIBLE,
        'Catalog'                  => VisibilityKeys::VISIBILITY_IN_CATALOG,
        'Search'                   => VisibilityKeys::VISIBILITY_IN_SEARCH,
        'Catalog, Search'          => VisibilityKeys::VISIBILITY_BOTH
    );

    /**
     * The array containing the data for product type configuration (configurables, bundles, etc).
     *
     * @var array
     */
    protected $artefacs = array();

    /**
     * The mapping for the SKUs to the created entity IDs.
     *
     * @var array
     */
    protected $skuEntityIdMapping = array();

    /**
     * The UID of the file to be imported.
     *
     * @var string
     */
    protected $uid;

    /**
     * The available EAV attribute sets.
     *
     * @var array
     */
    protected $attributeSets = array();

    /**
     * The available stores.
     *
     * @var array
     */
    protected $stores = array();

    /**
     * The available store websites.
     *
     * @var array
     */
    protected $storeWebsites = array();

    /**
     * The available EAV attributes, grouped by their attribute set and the attribute set name as keys.
     *
     * @var array
     */
    protected $attributes = array();

    /**
     * The available tax classes.
     *
     * @var array
     */
    protected $taxClasses = array();

    /**
     * The available categories.
     *
     * @var array
     */
    protected $categories = array();

    /**
     * The attribute set of the product that has to be created.
     *
     * @var array
     */
    protected $attributeSet = array();

    /**
     * The ID of the product that has been created recently.
     *
     * @var integer
     */
    protected $lastEntityId;

    /**
     * The SKU of the product that has been created recently.
     *
     * @var string
     */
    protected $lastSku;

    /**
     * The store view code the create the product/attributes for.
     *
     * @var string
     */
    protected $storeViewCode;

    /**
     * Set's the product processor instance.
     *
     * @param \TechDivision\Import\Product\Services\ProductProcessorInterface $productProcessor The product processor instance
     *
     * @return void
     */
    public function setProductProcessor(ProductProcessorInterface $productProcessor)
    {
        $this->productProcessor = $productProcessor;
    }

    /**
     * Return's the product processor instance.
     *
     * @return \TechDivision\Import\Services\ProductProcessorInterface The product processor instance
     */
    public function getProductProcessor()
    {
        return $this->productProcessor;
    }

    /**
     * Set's the SKU of the last imported product.
     *
     * @param string $lastSku The SKU
     *
     * @return void
     */
    public function setLastSku($lastSku)
    {
        $this->lastSku = $lastSku;
    }

    /**
     * Return's the SKU of the last imported product.
     *
     * @return string|null The SKU
     */
    public function getLastSku()
    {
        return $this->lastSku;
    }

    /**
     * Set's the ID of the product that has been created recently.
     *
     * @param string $lastEntityId The entity ID
     *
     * @return void
     */
    public function setLastEntityId($lastEntityId)
    {
        $this->lastEntityId = $lastEntityId;
    }

    /**
     * Return's the ID of the product that has been created recently.
     *
     * @return string The entity Id
     */
    public function getLastEntityId()
    {
        return $this->lastEntityId;
    }

    /**
     * Set's the attribute set of the product that has to be created.
     *
     * @param array $attributeSet The attribute set
     *
     * @return void
     */
    public function setAttributeSet(array $attributeSet)
    {
        $this->attributeSet = $attributeSet;
    }

    /**
     * Return's the attribute set of the product that has to be created.
     *
     * @return array The attribute set
     */
    public function getAttributeSet()
    {
        return $this->attributeSet;
    }

    /**
     * Set's the store view code the create the product/attributes for.
     *
     * @param string $storeViewCode The store view code
     *
     * @return void
     */
    public function setStoreViewCode($storeViewCode)
    {
        $this->storeViewCode = $storeViewCode;
    }

    /**
     * Return's the store view code the create the product/attributes for.
     *
     * @return string The store view code
     */
    public function getStoreViewCode()
    {
        return $this->storeViewCode;
    }

    /**
     * Intializes the previously loaded global data for exactly one bunch.
     *
     * @return void
     * @see \Importer\Csv\Actions\ProductImportAction::prepare()
     */
    public function setUp()
    {

        // load the status of the actual import
        $status = $this->getRegistryProcessor()->getAttribute($this->serial);

        // load the attribute set we've prepared intially
        $this->attributeSets = $status['globalData'][RegistryKeys::ATTRIBUTE_SETS];

        // load the store websites we've prepare initially
        $this->storeWebsites =  $status['globalData'][RegistryKeys::STORE_WEBSITES];

        // load the EAV attributes we've prepared initially
        $this->attributes = $status['globalData'][RegistryKeys::EAV_ATTRIBUTES];

        // load the stores we've initialized before
        $this->stores = $status['globalData'][RegistryKeys::STORES];

        // load the stores we've initialized before
        $this->taxClasses = $status['globalData'][RegistryKeys::TAX_CLASSES];

        // load the categories we've initialized before
        $this->categories = $status['globalData'][RegistryKeys::CATEGORIES];

        // prepare the callbacks
        parent::setUp();
    }

    /**
     * Clean up the global data after importing the bunch.
     *
     * @return void
     */
    public function tearDown()
    {

        // export the artefacts
        $this->exportArtefacts();

        // load the registry processor
        $registryProcessor = $this->getRegistryProcessor();

        // update the status up the actual import with the found variations, bundles, SKU => entity ID mapping and the imported files
        /*
        $registryProcessor->mergeAttributesRecursive($this->serial, array('variations'         => $this->variations));
        $registryProcessor->mergeAttributesRecursive($this->serial, array('bundles'            => $this->bundles));
        */
        $registryProcessor->mergeAttributesRecursive($this->serial, array('skuEntityIdMapping' => $this->skuEntityIdMapping));
        $registryProcessor->mergeAttributesRecursive($this->serial, array('files'              => array($this->uid => array('status' => 1))));
    }

    /**
     * Export's the artefacts
     */
    public function exportArtefacts()
    {

        // load the target directory and the actual timestamp
        $targetDir = $this->getConfiguration()->getTargetDir();
        $timestamp = date('Ymd-His');

        // initialize the counter
        $counter = 0;

        // iterate over the artefacts and export them
        foreach ($this->getArtefacts() as $artefactType => $artefacts) {
            foreach ($artefacts as $entityArtefacts) {
                // initialize the the exporter
                $exporter = new Exporter(new ExporterConfig());

                // initialize the bunch
                $bunch = array();

                // set the bunch header and append the artefact data
                $bunch[] = array_keys(reset(reset($entityArtefacts)));

                // export the artefacts
                foreach ($entityArtefacts as $entityArtefact) {
                    $bunch = array_merge($bunch, $entityArtefact);
                }

                // export the artefact (bunch)
                $exporter->export($filename = sprintf('%s/%s-%s_%d.csv', $targetDir, $artefactType, $timestamp, $counter++), $bunch);
            }
        }
    }

    /**
     * Cast's the passed value based on the backend type information.
     *
     * @param string $backendType   The backend type to cast to
     * @param mixed  $value         The value to be casted
     *
     * @return mixed The casted value
     */
    public function castValueByBackendType($backendType, $value)
    {

        // cast the value to a valid timestamp
        if ($backendType === 'datetime') {
            return \DateTime::createFromFormat($this->getSourceDateFormat(), $value)->format('Y-m-d H:i:s');
        }

        // cast the value to a float value
        if ($backendType === 'float') {
            return (float) $value;
        }

        // cast the value to an integer
        if ($backendType === 'int') {
            return (int) $value;
        }

        // we don't need to cast strings
        return $value;
    }

    /**
     * Return's the mappings for the table column => CSV column header.
     *
     * @return array The header stock mappings
     */
    public function getHeaderStockMappings()
    {
        return $this->headerStockMappings;
    }

    /**
     * Return's mapping for the supported backend types (for the product entity) => persist methods.
     *
     * @return array The mapping for the supported backend types
     */
    public function getBackendTypes()
    {
        return $this->backendTypes;
    }

    /**
     * Return's the attributes for the attribute set of the product that has to be created.
     *
     * @return array The attributes
     * @throws \Exception Is thrown if the attributes for the actual attribute set are not available
     */
    public function getAttributes()
    {

        // load the attribute set of the product that has to be created.
        $attributeSet = $this->getAttributeSet();

        // query whether or not, the requested EAV attributes are available
        if (isset($this->attributes[$attributeSetName = $attributeSet[MemberNames::ATTRIBUTE_SET_NAME]])) {
            return $this->attributes[$attributeSetName];
        }

        // throw an exception, if not
        throw new \Exception(sprintf('Found invalid attribute set name %s', $attributeSetName));
    }

    /**
     * Return's the artefacts for post-processing.
     *
     * @return array The artefacts
     */
    public function getArtefacts()
    {
        return $this->artefacs;
    }

    /**
     * Return's the store ID of the actual row.
     *
     * @return integer The ID of the actual store
     * @throws \Exception Is thrown, if the store with the actual code is not available
     */
    public function getRowStoreId()
    {

        // load the store view code the create the product/attributes for
        $storeViewCode = $this->getStoreViewCode();

        // query whether or not, the requested store is available
        if (isset($this->stores[$storeViewCode])) {
            return (integer) $this->stores[$storeViewCode][MemberNames::STORE_ID];
        }

        // throw an exception, if not
        throw new \Exception(sprintf('Found invalid store view code %s', $storeViewCode));
    }

    /**
     * Return's the tax class ID for the passed tax class name.
     *
     * @param string $taxClassName The tax class name to return the ID for
     *
     * @return integer The tax class ID
     * @throws \Exception Is thrown, if the tax class with the requested name is not available
     */
    public function getTaxClassIdByTaxClassName($taxClassName)
    {

        // query whether or not, the requested tax class is available
        if (isset($this->taxClasses[$taxClassName])) {
            return (integer) $this->taxClasses[$taxClassName][MemberNames::CLASS_ID];
        }

        // throw an exception, if not
        throw new \Exception(sprintf('Found invalid tax class name %s', $taxClassName));
    }

    /**
     * Return's the store website for the passed code.
     *
     * @param string $code The code of the store website to return the ID for
     *
     * @return integer The store website ID
     * @throws \Exception Is thrown, if the store website with the requested code is not available
     */
    public function getStoreWebsiteIdByCode($code)
    {

        // query whether or not, the requested store website is available
        if (isset($this->storeWebsites[$code])) {
            return (integer) $this->storeWebsites[$code][MemberNames::WEBSITE_ID];
        }

        // throw an exception, if not
        throw new \Exception(sprintf('Found invalid website code %s', $code));
    }

    /**
     * Return's the visibility key for the passed visibility string.
     *
     * @param string $visibility The visibility string the return the key for
     *
     * @return integer The requested visibility key
     * @throws \Exception Is thrown, if the requested visibility is not available
     */
    public function getVisibilityIdByValue($visibility)
    {

        // query whether or not, the requested visibility is available
        if (isset($this->availableVisibilities[$visibility])) {
            return $this->availableVisibilities[$visibility];
        }

        // throw an exception, if not
        throw new \Exception(sprintf('Found invalid visibility %s', $visibility));
    }

    /**
     * Return's the attribute set with the passed attribute set name.
     *
     * @param string $attributeSetName The name of the requested attribute set
     *
     * @return array The attribute set data
     * @throws \Exception Is thrown, if the attribute set with the passed name is not available
     */
    public function getAttributeSetByAttributeSetName($attributeSetName)
    {
        // query whether or not, the requested attribute set is available
        if (isset($this->attributeSets[$attributeSetName])) {
            return $this->attributeSets[$attributeSetName];
        }

        // throw an exception, if not
        throw new \Exception(sprintf('Found invalid attribute set name %s', $attributeSetName));
    }

    /**
     * Return's the category with the passed path.
     *
     * @param string The path of the category to return
     *
     * @return array The category
     * @throws \Exception Is thrown, if the requested category is not available
     */
    public function getCategoryByPath($path)
    {

        // query whether or not the category with the passed path exists
        if (isset($this->categories[$path])) {
            return $this->categories[$path];
        }

        // throw an exception, if not
        throw new \Exception(sprintf('Found invalid category path %s', $path));
    }

    /**
     * Map the passed attribute code, if a header mapping exists and return the
     * mapped mapping.
     *
     * @param string $attributeCode The attribute code to map
     *
     * @return string The mapped attribute code, or the original one
     */
    public function mapAttributeCodeByHeaderMapping($attributeCode)
    {

        // query weather or not we've a mapping, if yes, map the attribute code
        if (isset($this->headerMappings[$attributeCode])) {
            $attributeCode = $this->headerMappings[$attributeCode];
        }

        // return the (mapped) attribute code
        return $attributeCode;
    }

    /**
     * Add the passed product type artefacts to the product with the
     * last entity ID.
     *
     * @param array $artefacts The product type artefacts
     *
     * @return void
     * @uses \TechDivision\Import\Product\Subjects\BunchSubject::getLastEntityId()
     */
    public function addArtefacts($type, array $artefacts)
    {
        $this->artefacs[$type][$this->getLastEntityId()][] = $artefacts;
    }

    /**
     * Add the passed SKU => entity ID mapping.
     *
     * @param string $sku The SKU
     *
     * @return void
     * @uses \Import\Csv\Actions\ProductImportBunchAction::getLastEntityId()
     */
    public function addSkuEntityIdMapping($sku)
    {
        $this->skuEntityIdMapping[$sku] = $this->getLastEntityId();
    }

    /**
     * Persist's the passed product data and return's the ID.
     *
     * @param array $product The product data to persist
     *
     * @return string The ID of the persisted entity
     */
    public function persistProduct($product)
    {
        return $this->getProductProcessor()->persistProduct($product);
    }

    /**
     * Persist's the passed product varchar attribute.
     *
     * @param array $attribute The attribute to persist
     *
     * @return void
     */
    public function persistProductVarcharAttribute($attribute)
    {
        $this->getProductProcessor()->persistProductVarcharAttribute($attribute);
    }

    /**
     * Persist's the passed product integer attribute.
     *
     * @param array $attribute The attribute to persist
     *
     * @return void
     */
    public function persistProductIntAttribute($attribute)
    {
        $this->getProductProcessor()->persistProductIntAttribute($attribute);
    }

    /**
     * Persist's the passed product decimal attribute.
     *
     * @param array $attribute The attribute to persist
     *
     * @return void
     */
    public function persistProductDecimalAttribute($attribute)
    {
        $this->getProductProcessor()->persistProductDecimalAttribute($attribute);
    }

    /**
     * Persist's the passed product datetime attribute.
     *
     * @param array $attribute The attribute to persist
     *
     * @return void
     */
    public function persistProductDatetimeAttribute($attribute)
    {
        $this->getProductProcessor()->persistProductDatetimeAttribute($attribute);
    }

    /**
     * Persist's the passed product text attribute.
     *
     * @param array $attribute The attribute to persist
     *
     * @return void
     */
    public function persistProductTextAttribute($attribute)
    {
        $this->getProductProcessor()->persistProductTextAttribute($attribute);
    }

    /**
     * Persist's the passed product website data and return's the ID.
     *
     * @param array $productWebsite The product website data to persist
     *
     * @return void
     */
    public function persistProductWebsite($productWebsite)
    {
        $this->getProductProcessor()->persistProductWebsite($productWebsite);
    }

    /**
     * Persist's the passed product category data and return's the ID.
     *
     * @param array $productWebsite The product category data to persist
     *
     * @return void
     */
    public function persistProductCategory($productCategory)
    {
        $this->getProductProcessor()->persistProductCategory($productCategory);
    }

    /**
     * Persist's the passed stock item data and return's the ID.
     *
     * @param array $stockItem The stock item data to persist
     *
     * @return void
     */
    public function persistStockItem($stockItem)
    {
        $this->getProductProcessor()->persistStockItem($stockItem);
    }

    /**
     * Persist's the passed stock status data and return's the ID.
     *
     * @param array $stockItem The stock status data to persist
     *
     * @return void
     */
    public function persistStockStatus($stockStatus)
    {
        $this->getProductProcessor()->persistStockStatus($stockStatus);
    }

    /**
     * Return's the attribute option value with the passed value and store ID.
     *
     * @param mixed   $value   The option value
     * @param integer $storeId The ID of the store
     *
     * @return array|boolean The attribute option value instance
     */
    public function getEavAttributeOptionValueByOptionValueAndStoreId($value, $storeId)
    {
        return $this->getProductProcessor()->getEavAttributeOptionValueByOptionValueAndStoreId($value, $storeId);
    }
}
