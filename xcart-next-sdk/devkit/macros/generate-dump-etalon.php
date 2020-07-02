#!/usr/bin/env php
<?php
// vim: set ts=4 sw=4 sts=4 et:

/**
 * LiteCommerce
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to licensing@litecommerce.com so we can send you a copy immediately.
 *
 * PHP version 5.3.0
 *
 * @category  LiteCommerce
 * @author    Creative Development LLC <info@cdev.ru>
 * @copyright Copyright (c) 2011-2012 Creative Development LLC <info@cdev.ru>. All rights reserved
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      http://www.litecommerce.com/
 */

/**
 * Generate data dump
 */

require_once __DIR__ . '/core.php';


define('PRODUCT_IMAGE_PATH', LC_DIR_ROOT . 'public/error_image.png');

// {{{ Normalize input

$categories = 10;
$categoryImage = true;
$depth = 2;
$products = 20;
$featuredProducts = 20;
$wholesalePrices = 0;
$attributes = 0;
$options = 0;
$optionsValues = 0;
$productImages = 1;
$orders = 1000;
$orderItems = 0;

// }}}

$sums = array(
    'categories'       => 0,
    'categoryImages'   => 0,
    'featuredProducts' => 0,
    'products'         => 0,
    'attributes'       => 0,
    'options'          => 0,
    'optionValues'     => 0,
    'productImages'    => 0,
    'wholesalePrices'  => 0,
    'orders'           => 0,
    'orderItems'       => 0,
);
$counters = array(
    'attributes'      => 0,
    'options'         => 0,
    'wholesalePrices' => 0,
    'productImages'   => 0,
    'orders'          => 0,
);


$t = microtime(true);

// {{{ Categories

if ($GLOBALS['categories'] > 0) {
    print 'Clear categories ... ';
    $repo = \XLite\Core\Database::getRepo('XLite\Model\Category');
    foreach ($repo->findAll() as $category) {
        $repo->delete($category, false);
    }
    \XLite\Core\Database::getEM()->flush();
    print 'done' . PHP_EOL;

    print 'Generate categories ';
    generate_categories();
    print ' done' . PHP_EOL;

    \XLite\Core\Database::getEM()->clear();

    print 'Recalculate quick flags ... ';
    $repo->correctCategoriesStructure();
    print 'done' . PHP_EOL;

    \XLite\Core\Database::getEM()->clear();
}

// }}}

// {{{ Products

if ($GLOBALS['products'] > 0) {
    print 'Clear products ... ';
    \XLite\Core\Database::getRepo('XLite\Model\Product')->createPureQueryBuilder()
        ->delete('XLite\Model\Product', 'p')
        ->execute();
    print 'done' . PHP_EOL;

    print 'Generate products ';
    $categories = \XLite\Core\Database::getRepo('XLite\Model\Category')
        ->createQueryBuilder()
        ->andWhere('c.parent IS NOT NULL')
        ->getResult();
    foreach ($categories as $category) {
        $category = \XLite\Core\Database::getRepo('XLite\Model\Category')->find($category->getCategoryId());
        if (0 == count($category->getChildren())) {
            generate_products($category);
        }
    }
    print ' done' . PHP_EOL;

    // Featured products
    if (
        $GLOBALS['featuredProducts'] > 0
        && \XLite\Core\Operator::isClassExists('XLite\Module\CDev\FeaturedProducts\Model\FeaturedProduct')
    ) {
        $category = \XLite\Core\Database::getRepo('XLite\Model\Category')->getRootCategory(true);
        $list = \XLite\Core\Database::getRepo('XLite\Model\Product')->findFrame(
            0,
            $GLOBALS['featuredProducts']
        );
        $i = 0;
        foreach ($list as $product) {
            \XLite\Core\Database::getRepo('XLite\Module\CDev\FeaturedProducts\Model\FeaturedProduct')->insert(
                array(
                    'product'  => $product,
                    'category' => $category,
                    'orderBy'  => $i,
                ),
                false
            );
            $GLOBALS['sums']['featuredProducts']++;
            $i++;
        }
        \XLite\Core\Database::getEM()->flush();
    }

    \XLite\Core\Database::getEM()->clear();

    print 'Recalculate quick data ... ';
    \XLite\Core\QuickData::getInstance()->update();
    print 'done' . PHP_EOL;
}

// }}}

// {{{ Orders

if ($GLOBALS['orders'] > 0) {
    print 'Clear orders ... ';
    \XLite\Core\Database::getRepo('XLite\Model\Order')->createPureQueryBuilder()
        ->delete('XLite\Model\Order', 'o')
        ->execute();
    print 'done' . PHP_EOL;

    print 'Generate orders ';
    generate_orders();
    print ' done' . PHP_EOL;

    \XLite\Core\Database::getEM()->clear();
}

// }}}

print 'Statistics:' . PHP_EOL;
foreach ($sums as $k => $v) {
    print "\t" . $k . ': ' . $v . PHP_EOL;
}
print 'Duration: ' . gmdate('H:i:s', $t - microtime(true)) . PHP_EOL;

die(0);

// {{{ Functions

function generate_categories(\XLite\Model\Category $parent = null, $depth = 1)
{
    if (!$parent) {
        $parent = \XLite\Core\Database::getRepo('XLite\Model\Category')->getRootCategory(true);
    }

    $list = array();
    for ($i = 0; $i < $GLOBALS['categories']; $i++) {
        $category = \XLite\Core\Database::getRepo('XLite\Model\Category')->insert(
            array(
                'name'   => 'Test category #' . $depth . ' - ' . $i,
                'pos'    => $i,
                'parent' => $parent,
            ),
            false
        );
        $quickFlags = new \XLite\Model\Category\QuickFlags();
        $quickFlags->setCategory($category);
        $category->setQuickFlags($quickFlags);

        print '.';
        $GLOBALS['sums']['categories']++;

        $list[] = $category;
    }

    if ($list) {
        \XLite\Core\Database::getEM()->flush();

        // Images
        if ($GLOBALS['categoryImage']) {
            foreach ($list as $category) {
                $image = new \XLite\Model\Image\Category\Image;
                $image->setCategory($category);
                $category->setImage($image);
                \XLite\Core\Database::getEM()->persist($image);
                $image->loadFromLocalFile(PRODUCT_IMAGE_PATH);
                $GLOBALS['sums']['categoryImages']++;
            }
            \XLite\Core\Database::getEM()->flush();
        }

        if ($depth < $GLOBALS['depth']) {
            foreach ($list as $category) {
                generate_categories($category, $depth + 1);
            }
        }
    }
}

function generate_products(\XLite\Model\Category $category)
{
    if (!\XLite\Core\Database::getEM()->contains($category)) {
        \XLite\Core\Database::getEM()->merge($category);
    }

    $list = array();
    for ($i = 0; $i < $GLOBALS['products']; $i++) {
        $product = \XLite\Core\Database::getRepo('XLite\Model\Product')->insert(
            array(
                'sku'    => 'SKU' . $category->getCategoryId() . '_' . $i,
                'name'   => 'Test product #' . $category->getCategoryId() . ' - ' . $i,
                'price'  => rand(1, 100),
                'weight' => rand(1, 100),
            ),
            false
        );
        $link = new \XLite\Model\CategoryProducts;
        $link->setProduct($product);
        $link->setCategory($category);
        $product->addCategoryProducts($link);
        \XLite\Core\Database::getEM()->persist($link);

        $product->setInventory($product->getInventory());

        $list[] = $product;

        print '.';
        $GLOBALS['sums']['products']++;
    }

    \XLite\Core\Database::getEM()->flush();

    // Images
    foreach ($list as $product) {
        $GLOBALS['counters']['productImages']++;
        $limit = floor($GLOBALS['counters']['productImages'] / 100) + 1;
        for ($i = 0; $i < $limit; $i++) {
            $image = new \XLite\Model\Image\Product\Image;
            $image->setProduct($product);
            if ($image->loadFromLocalFile(PRODUCT_IMAGE_PATH)) {
                $product->addImages($image);
                $GLOBALS['sums']['productImages']++;
            }
       }
    }

    // Featured products
    if (
        $GLOBALS['featuredProducts'] > 0
        && \XLite\Core\Operator::isClassExists('XLite\Module\CDev\FeaturedProducts\Model\FeaturedProduct')
    ) {
        $i = 0;
        foreach ($list as $product) {
            if ($i < $GLOBALS['featuredProducts']) {
                \XLite\Core\Database::getRepo('XLite\Module\CDev\FeaturedProducts\Model\FeaturedProduct')->insert(
                    array(
                        'product'  => $product,
                        'category' => $category,
                        'orderBy'  => $i,
                    ),
                    false
                );
                $GLOBALS['sums']['featuredProducts']++;
                $i++;
            }
        }
    }

    // Wholesale prices
    if (\XLite\Core\Operator::isClassExists('XLite\Module\CDev\Wholesale\Model\WholesalePrice')) {
        foreach ($list as $product) {
            $q1 = 1;
            $q2 = 10;
            $GLOBALS['counters']['wholesalePrices']++;
            $limit = floor($GLOBALS['counters']['wholesalePrices'] / 100) + 2;
            for ($i = 0; $i < $limit; $i++) {
                $last = $i == $limit - 1;
                \XLite\Core\Database::getRepo('XLite\Module\CDev\Wholesale\Model\WholesalePrice')->insert(
                    array(
                        'quantityRangeBegin' => $q1,
                        'quantityRangeEnd'   => $last ? 0 : $q2,
                        'product'            => $product,
                        'price'              => $product->getPrice() * round(rand(0, 100) / 100, 2),
                    ),
                    false
                );
                $GLOBALS['sums']['wholesalePrices']++;
                $q1 = $q2 + 1;
                $q2 += 10;
            }

            \XLite\Core\Database::getRepo('XLite\Module\CDev\Wholesale\Model\MinQuantity')->insert(
                array(
                    'product'  => $product,
                    'quantity' => 1,
                ),
                false
            );
        }
    }

    // Attributes
    foreach ($list as $product) {
        $GLOBALS['counters']['attributes']++;
        $limit = floor($GLOBALS['counters']['attributes'] / 100);
        for ($i = 0; $i < $limit; $i++) {
            $attribute = \XLite\Core\Database::getRepo('XLite\Model\Attribute')->insert(
                array(
                    'product' => $product,
                    'name'    => 'Test attribute ' . $product->getProductId() . '-' . $i,
                ),
                false
            );
            \XLite\Core\Database::getEM()->persist($attribute);
            $option = \XLite\Core\Database::getRepo('XLite\Model\AttributeOption')->insert(
                array(
                    'attribute' => $attribute,
                    'name'      => 'value ' . $i,
                ),
                false
            );
            \XLite\Core\Database::getEM()->persist($option);
            $value = \XLite\Core\Database::getRepo('XLite\Model\AttributeValue\AttributeValueSelect')->insert(
                array(
                    'attribute'        => $attribute,
                    'product'          => $product,
                    'attribute_option' => $option,
                ),
                false
            );
            \XLite\Core\Database::getEM()->persist($value);

            $GLOBALS['sums']['attributes']++;
        }
    }

    // Options
    foreach ($list as $product) {
        $GLOBALS['counters']['options']++;
        $limit = floor($GLOBALS['counters']['options'] / 100);
        for ($i = 0; $i < $limit; $i++) {
            $attribute = \XLite\Core\Database::getRepo('XLite\Model\Attribute')->insert(
                array(
                    'product' => $product,
                    'name'    => 'Test option ' . $product->getProductId() . '-' . $i,
                ),
                false
            );
            \XLite\Core\Database::getEM()->persist($attribute);
            for ($n = 0; $n < $limit + 1; $n++) {
                $option = \XLite\Core\Database::getRepo('XLite\Model\AttributeOption')->insert(
                    array(
                        'attribute' => $attribute,
                        'name'      => 'value ' . $i . '-' . $n,
                    ),
                    false
                );
                \XLite\Core\Database::getEM()->persist($option);
                $value = \XLite\Core\Database::getRepo('XLite\Model\AttributeValue\AttributeValueSelect')->insert(
                    array(
                        'attribute'        => $attribute,
                        'product'          => $product,
                        'attribute_option' => $option,
                        'priceModifier'    => $n > 0 ? round(rand(0, 50) / 10, 1) : 0,
                    ),
                    false
                );
                \XLite\Core\Database::getEM()->persist($value);
                $GLOBALS['sums']['optionValues']++;
            }

            $GLOBALS['sums']['options']++;
        }
    }

    \XLite\Core\Database::getEM()->flush();
    \XLite\Core\Database::getEM()->clear();
}

function generate_orders()
{
    if ($GLOBALS['orders'] > 0) {
        $profile = \XLite\Core\Database::getRepo('XLite\Model\Profile')->createQueryBuilder()
            ->bindRegistered()
            ->getSingleResult();
        $productCount = \XLite\Core\Database::getRepo('XLite\Model\Product')->count();
        $currency = \XLite\Core\Database::getRepo('XLite\Model\Currency')->findOneByCode('USD');

        for ($i = 0; $i < $GLOBALS['orders']; $i++) {
            if (!\XLite\Core\Database::getEM()->contains($profile)) {
                $profile = \XLite\Core\Database::getRepo('XLite\Model\Profile')->find($profile->getProfileId());
                $currency =  \XLite\Core\Database::getRepo('XLite\Model\Currency')->find($currency->getCurrencyId());
            }

            $profileClone = $profile->cloneEntity();
            $order = \XLite\Core\Database::getRepo('XLite\Model\Order')->insert(
                array(
                    'profileOrig' => $profile,
                    'profile'     => $profileClone,
                    'currency'    => $currency,
                ),
                false
            );
            $profileClone->setOrder($order);
            $GLOBALS['counters']['orders']++;
            $limit = floor($GLOBALS['counters']['orders'] / 100) + 1;
            for ($n = 0; $n < $limit; $n++) {
                $product = \XLite\Core\Database::getRepo('XLite\Model\Product')->findFrame(
                    rand(0, $productCount - 1),
                    1
                );
                $product = reset($product);
                $item = \XLite\Core\Database::getRepo('XLite\Model\OrderItem')->insert(
                    array(
                        'order'   => $order,
                        'product' => $product,
                    ),
                    false
                );
                $order->addItems($item);
                $item->renew();
                $GLOBALS['sums']['orderItems']++;
            }
            \XLite\Core\Database::getEM()->flush();

            $order->renewPaymentMethod();
            $order->renewShippingMethod();
            $order->calculate();

            print '.';
            $GLOBALS['sums']['orders']++;

            if ($i % 10 == 0) {
                \XLite\Core\Database::getEM()->flush();
                \XLite\Core\Database::getEM()->clear();
            }
        }

        \XLite\Core\Database::getEM()->flush();
    }
}

function normalize_nested_set()
{
    $sql = <<<SQL
CREATE PROCEDURE tree_recover () MODIFIES SQL DATA
BEGIN

    DECLARE currentId, currentParentId  CHAR(36);
    DECLARE currentLeft                 INT;
    DECLARE startId                     INT DEFAULT 1;

    # Determines the max size for MEMORY tables.
    SET max_heap_table_size = 1024 * 1024 * 512;

    START TRANSACTION;

    # Temporary MEMORY table to do all the heavy lifting in,
    # otherwise performance is simply abysmal.
    CREATE TABLE `tmp_tree` (
        `id`        int(11) unsigned NOT NULL DEFAULT 0,
        `parent_id` int(11) unsigned DEFAULT NULL,
        `lpos`      int(11) unsigned DEFAULT NULL,
        `rpos`      int(11) unsigned DEFAULT NULL,
        PRIMARY KEY      (`id`),
        INDEX USING HASH (`parent_id`),
        INDEX USING HASH (`lpos`),
        INDEX USING HASH (`rpos`)
    ) ENGINE = MEMORY

    SELECT `category_id` as `id`, `parent_id`, `lpos`, `rpos` FROM `xlite_categories`;

    # Leveling the playing field.
    UPDATE `tmp_tree` SET `lpos` = NULL,`rpos` = NULL;

    # Establishing starting numbers for all root elements.
    WHILE EXISTS (SELECT * FROM `tmp_tree` WHERE `parent_id` IS NULL AND `lpos` IS NULL AND `rpos` IS NULL LIMIT 1) DO

        UPDATE `tmp_tree` SET `lpos` = startId, `rpos` = startId + 1 WHERE `parent_id` IS NULL AND `lpos` IS NULL AND `rpos` IS NULL LIMIT  1;
        SET startId = startId + 2;

    END WHILE;

    # Switching the indexes for the lpos/rpos columns to B-Trees to speed up the next section, which uses range queries.
    DROP INDEX `lpos` ON `tmp_tree`;
    DROP INDEX `rpos` ON `tmp_tree`;
    CREATE INDEX `lpos` USING BTREE ON `tmp_tree` (`lpos`);
    CREATE INDEX `rpos` USING BTREE ON `tmp_tree` (`rpos`);

    # Numbering all child elements
    WHILE EXISTS (SELECT * FROM `tmp_tree` WHERE `lpos` IS NULL LIMIT 1) DO

        # Picking an unprocessed element which has a processed parent.
        SELECT `tmp_tree`.`id` INTO currentId FROM `tmp_tree` INNER JOIN `tmp_tree` AS `parents` ON `tmp_tree`.`parent_id` = `parents`.`id` WHERE `tmp_tree`.`lpos` IS NULL AND `parents`.`lpos`  IS NOT NULL LIMIT 1;

        # Finding the element's parent.
        SELECT  `parent_id` INTO currentParentId FROM `tmp_tree` WHERE `id` = currentId;

        # Finding the parent's lpos value.
        SELECT `lpos` INTO currentLeft FROM `tmp_tree` WHERE `id` = currentParentId;

        # Shifting all elements to the right of the current element 2 to the right.
        UPDATE `tmp_tree` SET `rpos` = `rpos` + 2 WHERE `rpos` > currentLeft;

        UPDATE `tmp_tree` SET `lpos` = `lpos` + 2 WHERE `lpos` > currentLeft;

        # Setting lpos and rpos values for current element.
        UPDATE `tmp_tree` SET `lpos`  = currentLeft + 1, `rpos` = currentLeft + 2 WHERE `id` = currentId;

    END WHILE;

    # Writing calculated values back to physical table.
    UPDATE `xlite_categories`, `tmp_tree` SET `xlite_categories`.`lpos` = `tmp_tree`.`lpos`, `xlite_categories`.`rpos` = `tmp_tree`.`rpos` WHERE `xlite_categories`.`category_id` = `tmp_tree`.`id`;

    COMMIT;

    DROP TABLE `tmp_tree`;

END;
SQL;

    $sql = str_replace(
        'xlite_categories',
        \XLite\Core\Database::getInstance()->getTablePrefix() . 'categories',
        $sql
    );

    \XLite\Core\Database::getEM()->getConnection()->exec('DROP PROCEDURE IF EXISTS tree_recover');
    \XLite\Core\Database::getEM()->getConnection()->exec($sql);
    \XLite\Core\Database::getEM()->getConnection()->exec('CALL tree_recover()');
}


// }}}

// {{{ Help

function macro_help()
{
    $script = __FILE__;

    return <<<HELP
Usage: $script --categories=<categories count per level> --categoryImage --depth=<categories depth> --featuredProducts=<featured products per category> --products=<product per category> --attributes=<attributes per product> --options=<options per product> --optionsValues=<option values per option> --productImages=<images per product> --wholesalePrices=<wholesale prices per product> --orders=<order count> --orderItems=<order items per product>

    --categories=<categories count per level>
        Categories per ceategories tree level. Default - 0

    --categoryImage
        Flag - add for every category image or not. Default - no

    --depth=<categories depth>
        Categories tree depth. Default - 0        

    --featuredProducts=<featured products per category
        Number of featured products per categoey< include root category. Default - 0

    --products=<product per category>
        Products per category. Default - 0

    --attributes=<attributes per product>
        Attributes per product. Default - 0

    --options=<options per product>
        Options per product. Default - 0

    --optionsValues=<option values per option>
        Option values per option. Default - 2

    --productImages=<images per product>
        Product'images per product. Default - 0

    --wholesalePrices=<wholesale prices per product>
        Wholesale prices poer product. Default - 0

    --orders=<order count>
        Orders count. Default - 0

    --orderItems=<order items per product>
        Order items per order. Default - 1

Example: $script --categories=5 --categoryImage --depth=3 --featuredProducts=20 --products=100 --attributes=20 --options=3 --optionsValues=10 --productImages=3 --wholesalePrices=5 --orders=20000 --orderItems=5
HELP;
}

// }}}

