<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\Sales\InvoiceBundle\Tests\Functional\Controller;

use DateTime;
use Doctrine\ORM\Tools\SchemaTool;

use Sulu\Bundle\ContactBundle\Entity\Account;
use Sulu\Bundle\ContactBundle\Entity\Address;
use Sulu\Bundle\ContactBundle\Entity\AddressType;
use Sulu\Bundle\ContactBundle\Entity\Contact;
use Sulu\Bundle\ContactBundle\Entity\ContactTitle;
use Sulu\Bundle\ContactBundle\Entity\Country;
use Sulu\Bundle\ContactBundle\Entity\Phone;
use Sulu\Bundle\ContactBundle\Entity\PhoneType;
use Sulu\Bundle\ContactBundle\Entity\TermsOfDelivery;
use Sulu\Bundle\ContactBundle\Entity\TermsOfPayment;
use Sulu\Bundle\ProductBundle\Entity\Product;
use Sulu\Bundle\ProductBundle\Entity\ProductTranslation;
use Sulu\Bundle\ProductBundle\Entity\Status;
use Sulu\Bundle\ProductBundle\Entity\StatusTranslation;
use Sulu\Bundle\ProductBundle\Entity\Type;
use Sulu\Bundle\ProductBundle\Entity\TypeTranslation;

use Sulu\Bundle\Sales\CoreBundle\Entity\Item;
use Sulu\Bundle\Sales\CoreBundle\Entity\ItemStatus;
use Sulu\Bundle\Sales\CoreBundle\Entity\ItemStatusTranslation;
use Sulu\Bundle\Sales\OrderBundle\Entity\Order;
use Sulu\Bundle\Sales\OrderBundle\Entity\OrderAddress;
use Sulu\Bundle\Sales\OrderBundle\Entity\OrderStatus;
use Sulu\Bundle\Sales\OrderBundle\Entity\OrderStatusTranslation;
use Sulu\Bundle\Sales\ShippingBundle\Entity\Shipping;
use Sulu\Bundle\Sales\ShippingBundle\Entity\ShippingItem;
use Sulu\Bundle\Sales\ShippingBundle\Entity\ShippingStatus;
use Sulu\Bundle\Sales\ShippingBundle\Entity\ShippingStatusTranslation;
use Sulu\Bundle\TestBundle\Entity\TestUser;
use Sulu\Bundle\TestBundle\Testing\DatabaseTestCase;
use Symfony\Component\HttpKernel\Client;

class InvoiceControllerTest extends DatabaseTestCase
{
    /**
     * @var array
     */
    protected static $entities;

    /**
     * @var Client
     */
    private $client;

    /**
     * @var TestUser
     */
    private $testUser;

    private $locale = 'en';

    /**
     * @var Account
     */
    private $account;
    /**
     * @var Address
     */
    private $address;
    /**
     * @var Address
     */
    private $address2;
    /**
     * @var Contact
     */
    private $contact;
    /**
     * @var Contact
     */
    private $contact2;
    /**
     * @var Order
     */
    private $order;
    /**
     * @var OrderAddress
     */
    private $orderAddressDelivery;
    /**
     * @var OrderAddress
     */
    private $orderAddressInvoice;
    /**
     * @var OrderStatus
     */
    private $orderStatus;
    /**
     * @var TermsOfDelivery
     */
    private $termsOfDelivery;
    /**
     * @var TermsOfPayment
     */
    private $termsOfPayment;
    /**
     * @var OrderStatusTranslation
     */
    private $orderStatusTranslation;
    /**
     * @var Item
     */
    private $item;
    /**
     * @var ItemStatus
     */
    private $itemStatus;
    /**
     * @var ItemStatusTranslation
     */
    private $itemStatusTranslation;
    /**
     * @var Product
     */
    private $product;
    /**
     * @var ProductType
     */
    private $productType;
    /**
     * @var ProductTypeTranslation
     */
    private $productTypeTranslation;
    /**
     * @var ProductTranslation
     */
    private $productTranslation;
    /**
     * @var Phone
     */
    private $phone;
    /**
     * @var Shipping
     */
    private $shipping;
    /**
     * @var Shipping
     */
    private $shipping2;
    /**
     * @var ShippingStatus
     */
    private $shippingStatus;
    /**
     * @var ShippingItem
     */
    private $shippingItem;
    /**
     * @var OrderAddress
     */
    private $shippingAddress;
    /**
     * @var OrderAddress
     */
    private $shippingAddress2;
    /**
     * @var ShippingStatusTranslation
     */
    private $shippingStatusTranslation;

    public function setUp()
    {
        $this->setUpTestUser();
        $this->setUpClient();
        $this->setUpSchema();
        $this->setUpTestData();
    }

    private function setUpTestUser()
    {
        $this->testUser = new TestUser();
        $this->testUser->setUsername('test');
        $this->testUser->setPassword('test');
        $this->testUser->setLocale($this->locale);
    }

    private function setUpClient()
    {
        $this->client = static::createClient(
            array(),
            array(
                'PHP_AUTH_USER' => $this->testUser->getUsername(),
                'PHP_AUTH_PW' => $this->testUser->getPassword()
            )
        );
    }

    private function setUpSchema()
    {
        self::$tool = new SchemaTool(self::$em);

        self::$entities = array(
            self::$em->getClassMetadata('Sulu\Bundle\TestBundle\Entity\TestUser'),
            // SalesOrderBundle
            self::$em->getClassMetadata('Sulu\Bundle\Sales\OrderBundle\Entity\Order'),
            self::$em->getClassMetadata('Sulu\Bundle\Sales\OrderBundle\Entity\OrderAddress'),
            self::$em->getClassMetadata('Sulu\Bundle\Sales\OrderBundle\Entity\OrderStatus'),
            self::$em->getClassMetadata('Sulu\Bundle\Sales\OrderBundle\Entity\OrderStatusTranslation'),
            // SalesCoreBundle
            self::$em->getClassMetadata('Sulu\Bundle\Sales\CoreBundle\Entity\Item'),
            self::$em->getClassMetadata('Sulu\Bundle\Sales\CoreBundle\Entity\ItemAttribute'),
            self::$em->getClassMetadata('Sulu\Bundle\Sales\CoreBundle\Entity\ItemStatus'),
            self::$em->getClassMetadata('Sulu\Bundle\Sales\CoreBundle\Entity\ItemStatusTranslation'),
            // SalesShippingBundle
            self::$em->getClassMetadata('Sulu\Bundle\Sales\ShippingBundle\Entity\Shipping'),
            self::$em->getClassMetadata('Sulu\Bundle\Sales\ShippingBundle\Entity\ShippingItem'),
            self::$em->getClassMetadata('Sulu\Bundle\Sales\ShippingBundle\Entity\ShippingStatus'),
            self::$em->getClassMetadata('Sulu\Bundle\Sales\ShippingBundle\Entity\ShippingStatusTranslation'),
            // ProductBundle
            self::$em->getClassMetadata('Sulu\Bundle\ProductBundle\Entity\Product'),
            self::$em->getClassMetadata('Sulu\Bundle\ProductBundle\Entity\DeliveryStatus'),
            self::$em->getClassMetadata('Sulu\Bundle\ProductBundle\Entity\ProductPrice'),
            self::$em->getClassMetadata('Sulu\Bundle\ProductBundle\Entity\Type'),
            self::$em->getClassMetadata('Sulu\Bundle\ProductBundle\Entity\TypeTranslation'),
            self::$em->getClassMetadata('Sulu\Bundle\ProductBundle\Entity\Status'),
            self::$em->getClassMetadata('Sulu\Bundle\ProductBundle\Entity\StatusTranslation'),
            self::$em->getClassMetadata('Sulu\Bundle\ProductBundle\Entity\AttributeSet'),
            self::$em->getClassMetadata('Sulu\Bundle\ProductBundle\Entity\AttributeSetTranslation'),
            self::$em->getClassMetadata('Sulu\Bundle\ProductBundle\Entity\Attribute'),
            self::$em->getClassMetadata('Sulu\Bundle\ProductBundle\Entity\AttributeTranslation'),
            self::$em->getClassMetadata('Sulu\Bundle\ProductBundle\Entity\ProductTranslation'),
            self::$em->getClassMetadata('Sulu\Bundle\ProductBundle\Entity\ProductAttribute'),
            self::$em->getClassMetadata('Sulu\Bundle\ProductBundle\Entity\Addon'),
            // ContactBundle
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\Account'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\AccountContact'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\AccountAddress'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\AccountCategory'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\Activity'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\ActivityStatus'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\ActivityPriority'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\ActivityType'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\Address'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\AddressType'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\BankAccount'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\Contact'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\ContactTitle'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\Position'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\ContactLocale'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\Country'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\ContactAddress'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\Email'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\EmailType'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\Note'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\Fax'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\FaxType'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\Phone'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\PhoneType'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\Url'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\UrlType'),
            self::$em->getClassMetadata('Sulu\Bundle\TagBundle\Entity\Tag'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\TermsOfPayment'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\TermsOfDelivery'),
            self::$em->getClassMetadata('Sulu\Bundle\MediaBundle\Entity\Collection'),
            self::$em->getClassMetadata('Sulu\Bundle\MediaBundle\Entity\CollectionType'),
            self::$em->getClassMetadata('Sulu\Bundle\MediaBundle\Entity\CollectionMeta'),
            self::$em->getClassMetadata('Sulu\Bundle\MediaBundle\Entity\Media'),
            self::$em->getClassMetadata('Sulu\Bundle\MediaBundle\Entity\MediaType'),
            self::$em->getClassMetadata('Sulu\Bundle\MediaBundle\Entity\File'),
            self::$em->getClassMetadata('Sulu\Bundle\MediaBundle\Entity\FileVersion'),
            self::$em->getClassMetadata('Sulu\Bundle\MediaBundle\Entity\FileVersionMeta'),
            self::$em->getClassMetadata('Sulu\Bundle\MediaBundle\Entity\FileVersionContentLanguage'),
            self::$em->getClassMetadata('Sulu\Bundle\MediaBundle\Entity\FileVersionPublishLanguage'),
            self::$em->getClassMetadata('Sulu\Bundle\CategoryBundle\Entity\Category'),
            self::$em->getClassMetadata('Sulu\Bundle\CategoryBundle\Entity\CategoryMeta'),
            self::$em->getClassMetadata('Sulu\Bundle\CategoryBundle\Entity\CategoryTranslation')
        );

        self::$tool->dropSchema(self::$entities);
        self::$tool->createSchema(self::$entities);
    }

    private function setUpTestData()
    {
        // account
        $this->account = new Account();
        $this->account->setName('Company');
        $this->account->setCreated(new DateTime());
        $this->account->setChanged(new DateTime());
        $this->account->setType(Account::TYPE_BASIC);
        $this->account->setUid('uid-123');
        $this->account->setDisabled(0);

        // country
        $country = new Country();
        $country->setName('Country');
        $country->setCode('co');
        // address type
        $addressType = new AddressType();
        $addressType->setName('Business');
        // address
        $this->address = new Address();
        $this->address->setStreet('Sample-Street');
        $this->address->setNumber('12');
        $this->address->setAddition('Entrance 2');
        $this->address->setCity('Sample-City');
        $this->address->setState('State');
        $this->address->setZip('12345');
        $this->address->setCountry($country);
        $this->address->setPostboxNumber('postboxNumber');
        $this->address->setPostboxPostcode('postboxPostcode');
        $this->address->setPostboxCity('postboxCity');
        $this->address->setAddressType($addressType);
        // address
        $this->address2 = new Address();
        $this->address2->setStreet('Street');
        $this->address2->setNumber('2');
        $this->address2->setCity('Utopia');
        $this->address2->setZip('1');
        $this->address2->setCountry($country);
        $this->address2->setAddressType($addressType);

        // phone
        $phoneType = new PhoneType();
        $phoneType->setName('Business');
        $this->phone = new Phone();
        $this->phone->setPhone('+43 123 / 456 789');
        $this->phone->setPhoneType($phoneType);

        // title
        $title = new ContactTitle();
        $title->setTitle('Dr');
        // contact
        $this->contact = new Contact();
        $this->contact->setFirstName('John');
        $this->contact->setLastName('Doe');
        $this->contact->setTitle($title);
        $this->contact->setCreated(new DateTime());
        $this->contact->setChanged(new DateTime());
        // contact
        $this->contact2 = new Contact();
        $this->contact2->setFirstName('Johanna');
        $this->contact2->setLastName('Dole');
        $this->contact2->setCreated(new DateTime());
        $this->contact2->setChanged(new DateTime());

        // order status
        $this->orderStatus = new OrderStatus();
        $this->orderStatusTranslation = new OrderStatusTranslation();
        $this->orderStatusTranslation->setName('English-Order-Status-1');
        $this->orderStatusTranslation->setLocale($this->locale);
        $this->orderStatusTranslation->setStatus($this->orderStatus);

        // order address
        $this->orderAddressDelivery = new OrderAddress();
        $this->orderAddressDelivery->setFirstName($this->contact->getFirstName());
        $this->orderAddressDelivery->setLastName($this->contact->getLastName());
        $this->orderAddressDelivery->setTitle($this->contact->getTitle()->getTitle());
        $this->orderAddressDelivery->setStreet($this->address->getStreet());
        $this->orderAddressDelivery->setNumber($this->address->getNumber());
        $this->orderAddressDelivery->setAddition($this->address->getAddition());
        $this->orderAddressDelivery->setCity($this->address->getCity());
        $this->orderAddressDelivery->setZip($this->address->getZip());
        $this->orderAddressDelivery->setState($this->address->getState());
        $this->orderAddressDelivery->setCountry($this->address->getCountry()->getName());
        $this->orderAddressDelivery->setPostboxNumber($this->address->getPostboxNumber());
        $this->orderAddressDelivery->setPostboxPostcode($this->address->getPostboxPostcode());
        $this->orderAddressDelivery->setPostboxCity($this->address->getPostboxCity());
        $this->orderAddressDelivery->setAccountName($this->account->getName());
        $this->orderAddressDelivery->setUid($this->account->getUid());
        $this->orderAddressDelivery->setPhone($this->phone->getPhone());
        $this->orderAddressDelivery->setPhoneMobile('+43 123 / 456');

        // clone address for invoice
        $this->orderAddressInvoice = clone $this->orderAddressDelivery;
        $this->orderAddressInvoice = clone $this->orderAddressDelivery;

        $this->termsOfDelivery = new TermsOfDelivery();
        $this->termsOfDelivery->setTerms('10kg minimum');
        $this->termsOfPayment = new TermsOfPayment();
        $this->termsOfPayment->setTerms('10% off');

        // order
        $this->order = new Order();
        $this->order->setNumber('1234');
        $this->order->setCommission('commission');
        $this->order->setCostCentre('cost-centre');
        $this->order->setCustomerName($this->contact->getFullName());
        $this->order->setCurrency('EUR');
        $this->order->setTermsOfDelivery($this->termsOfDelivery);
        $this->order->setTermsOfDeliveryContent($this->termsOfDelivery->getTerms());
        $this->order->setTermsOfPayment($this->termsOfPayment);
        $this->order->setTermsOfPaymentContent($this->termsOfPayment->getTerms());
        $this->order->setCreated(new DateTime());
        $this->order->setChanged(new DateTime());
        $this->order->setDesiredDeliveryDate(new DateTime('2015-01-01'));
        $this->order->setSessionId('abcd1234');
        $this->order->setTaxfree(true);
        $this->order->setContact($this->contact);
        $this->order->setAccount($this->account);
        $this->order->setStatus($this->orderStatus);
        $this->order->setDeliveryAddress($this->orderAddressDelivery);
        $this->order->setInvoiceAddress($this->orderAddressInvoice);

        $order2 = clone $this->order;
        $order2->setNumber('12345');
        $order2->setDeliveryAddress(null);
        $order2->setInvoiceAddress(null);

        // product type
        $productType = new Type();
        $productTypeTranslation = new TypeTranslation();
        $productTypeTranslation->setLocale($this->locale);
        $productTypeTranslation->setName('EnglishProductType-1');
        $productTypeTranslation->setType($productType);
        // product status
        $productStatus = new Status();
        $productStatusTranslation = new StatusTranslation();
        $productStatusTranslation->setLocale($this->locale);
        $productStatusTranslation->setName('EnglishProductStatus-1');
        $productStatusTranslation->setStatus($productStatus);
        // product
        $this->product = new Product();
        $this->product->setCode('EnglishProductCode-1');
        $this->product->setNumber('ProductNumber-1');
        $this->product->setManufacturer('EnglishManufacturer-1');
        $this->product->setType($productType);
        $this->product->setStatus($productStatus);
        $this->product->setCreated(new DateTime());
        $this->product->setChanged(new DateTime());
        // product translation
        $this->productTranslation = new ProductTranslation();
        $this->productTranslation->setProduct($this->product);
        $this->productTranslation->setLocale($this->locale);
        $this->productTranslation->setName('EnglishProductTranslationName-1');
        $this->productTranslation->setShortDescription('EnglishProductShortDescription-1');
        $this->productTranslation->setLongDescription('EnglishProductLongDescription-1');
        $this->product->addTranslation($this->productTranslation);

        // Item Status
        $this->itemStatus = new ItemStatus();
        $this->itemStatusTranslation = new ItemStatusTranslation();
        $this->itemStatusTranslation->setName('English-Item-Status-1');
        $this->itemStatusTranslation->setLocale($this->locale);
        $this->itemStatusTranslation->setStatus($this->itemStatus);
        // Item
        $this->item = new Item();
        $this->item->setName('Product1');
        $this->item->setNumber('123');
        $this->item->setQuantity(2);
        $this->item->setQuantityUnit('Pcs');
        $this->item->setUseProductsPrice(true);
        $this->item->setTax(20);
        $this->item->setPrice(125.99);
        $this->item->setDiscount(10);
        $this->item->setDescription('This is a description');
        $this->item->setWeight(15.8);
        $this->item->setWidth(5);
        $this->item->setHeight(6);
        $this->item->setLength(7);
        $this->item->setSupplierName('Supplier');
        $this->item->setCreated(new DateTime());
        $this->item->setChanged(new DateTime());
        $this->item->setProduct($this->product);
        $this->item->setStatus($this->itemStatus);

        $this->order->addItem($this->item);

        // shipping
        $this->shippingStatus = new ShippingStatus();
        $this->shippingStatusTranslation = new ShippingStatusTranslation();
        $this->shippingStatusTranslation->setName('English-Shipping-Status-1');
        $this->shippingStatusTranslation->setLocale($this->locale);
        $this->shippingStatusTranslation->setStatus($this->shippingStatus);

        $this->shippingAddress = clone $this->orderAddressDelivery;
        $this->shipping = new Shipping();
        $this->shipping->setNumber('00001');
        $this->shipping->setShippingNumber('432');
        $this->shipping->setStatus($this->shippingStatus);
        $this->shipping->setOrder($this->order);
        $this->shipping->setChanged(new DateTime());
        $this->shipping->setCreated(new DateTime());
        $this->shipping->setCommission('shipping-commission');
        $this->shipping->setDeliveryAddress($this->shippingAddress);
        $this->shipping->setExpectedDeliveryDate(new DateTime('2015-01-01'));
        $this->shipping->setHeight(101);
        $this->shipping->setWidth(102);
        $this->shipping->setLength(103);
        $this->shipping->setWeight(10);
        $this->shipping->setNote('simple shipping note');
        $this->shipping->setTermsOfDelivery($this->termsOfDelivery);
        $this->shipping->setTermsOfDeliveryContent($this->termsOfDelivery->getTerms());
        $this->shipping->setTrackingId('abcd1234');
        $this->shipping->setTrackingUrl('http://www.tracking.url?token=abcd1234');

        $this->shipping2 = clone $this->shipping;
        $this->shipping2->setNumber('00002');
        $this->shippingAddress2 = clone $this->shippingAddress;
        $this->shipping2->setDeliveryAddress($this->shippingAddress2);

        $this->shippingItem = new ShippingItem();
        $this->shippingItem->setShipping($this->shipping);
        $this->shippingItem->setItem($this->item);
        $this->shippingItem->setQuantity(1);
        $this->shippingItem->setNote('shipping-item-note');
        $this->shipping->addShippingItem($this->shippingItem);

        // persist
        self::$em->persist($this->account);
        self::$em->persist($title);
        self::$em->persist($country);
        self::$em->persist($this->termsOfPayment);
        self::$em->persist($this->termsOfDelivery);
        self::$em->persist($country);
        self::$em->persist($addressType);
        self::$em->persist($this->address);
        self::$em->persist($this->address2);
        self::$em->persist($phoneType);
        self::$em->persist($this->phone);
        self::$em->persist($this->contact);
        self::$em->persist($this->contact2);
        self::$em->persist($this->order);
        self::$em->persist($order2);
        self::$em->persist($this->orderStatus);
        self::$em->persist($this->orderAddressDelivery);
        self::$em->persist($this->orderAddressInvoice);
        self::$em->persist($this->orderStatusTranslation);
        self::$em->persist($this->item);
        self::$em->persist($this->itemStatus);
        self::$em->persist($this->itemStatusTranslation);
        self::$em->persist($this->product);
        self::$em->persist($this->productTranslation);
        self::$em->persist($productType);
        self::$em->persist($productTypeTranslation);
        self::$em->persist($productStatus);
        self::$em->persist($productStatusTranslation);
        self::$em->persist($this->shipping);
        self::$em->persist($this->shipping2);
        self::$em->persist($this->shippingStatus);
        self::$em->persist($this->shippingStatusTranslation);
        self::$em->persist($this->shippingItem);
        self::$em->persist($this->shippingAddress);
        self::$em->persist($this->shippingAddress2);
        self::$em->flush();
    }

    public function tearDown()
    {
        parent::tearDown();
        self::$tool->dropSchema(self::$entities);
    }

    public function testGetById()
    {

    }
}
