<?php
/**
 * Unit-Testing einer Zend Framework 2 Anwendung
 *
 * Zend Framework Session auf der International PHP Conference 2013 in München
 *
 * @package    CustomerTest
 * @author     Ralf Eggert <r.eggert@travello.de>
 * @copyright  Ralf Eggert <r.eggert@travello.de>
 * @link       http://www.ralfeggert.de/
 */

/**
 * namespace definition and usage
 */
namespace CustomerTest\Service;

use Customer\Entity\CustomerEntity;
use Customer\Hydrator\CustomerHydrator;
use Customer\InputFilter\CustomerInputFilter;
use Customer\Service\CustomerService;
use Customer\Table\CustomerTable;
use PHPUnit_Extensions_Database_DataSet_QueryDataSet;
use PHPUnit_Extensions_Database_DB_DefaultDatabaseConnection;
use PHPUnit_Extensions_Database_TestCase;
use Zend\Db\Adapter\Adapter;

/**
 * CustomerServiceDatabaseTest
 *
 * Tests the CustomerService class in connection with a database
 *
 * @package    CustomerTest
 */
class CustomerServiceDatabaseTest extends PHPUnit_Extensions_Database_TestCase
{
    /**
     * @var Adapter
     */
    private $adapter = null;

    /**
     * @var PHPUnit_Extensions_Database_DB_DefaultDatabaseConnection
     */
    private $connection = null;

    /**
     * Get Database Connection
     *
     * @return PHPUnit_Extensions_Database_DB_IDatabaseConnection
     */
    public function getConnection()
    {
        if (!$this->connection) {
            $dbConfig = array(
                'driver' => 'pdo',
                'dsn'    => 'mysql:dbname=ipc2013.testing.test;host=localhost;charset=utf8',
                'user'   => 'ipc2013',
                'pass'   => 'ipc2013',
            );

            $this->adapter = new Adapter($dbConfig);
            $this->connection = $this->createDefaultDBConnection(
                $this->adapter->getDriver()->getConnection()->getResource(),
                'ipc2013.testing.test'
            );
        }

        return $this->connection;
    }

    /**
     * Get DataSet
     *
     * @return PHPUnit_Extensions_Database_DataSet_IDataSet
     */
    public function getDataSet()
    {
        return $this->createXmlDataSet(__DIR__ . '/customer-test-data.xml');
    }

    public function testInsertCustomerValidData()
    {
        $data = array(
            'id'        => 99,
            'firstname' => 'Horst',
            'lastname'  => 'Hrubesch',
            'street'    => 'Am Köpfen 124',
            'postcode'  => '21451',
            'city'      => 'Hamburg',
            'country'   => 'de',
        );

        $customerFilter = new CustomerInputFilter();
        $customerTable  = new CustomerTable($this->adapter);

        $customerService = new CustomerService();
        $customerService->setCustomerFilter($customerFilter);
        $customerService->setCustomerTable($customerTable);

        $customerEntity = $customerService->save($data);

        $queryTable = $this->getConnection()->createQueryTable(
            'loadCustomersOrderedByLastname', 'SELECT * FROM customers WHERE id = "' . $data['id'] . '";'
        );

        $expectedRow = $queryTable->getRow(0);

        $hydrator = new CustomerHydrator();
        $customerRow = $hydrator->extract($customerEntity);

        $this->assertEquals($expectedRow, $customerRow);
    }

    public function testUpdateExistingCustomer()
    {
        $customerFilter = new CustomerInputFilter();
        $customerTable = new CustomerTable($this->adapter);
        $customerHydrator = new CustomerHydrator();

        $customerService = new CustomerService();
        $customerService->setCustomerFilter($customerFilter);
        $customerService->setCustomerTable($customerTable);

        $customerEntity = $customerService->fetchSingleById(42);
        $customerEntity->setFirstname('Monika');
        $customerEntity->setLastname('Musterfrau');

        $data = $customerHydrator->extract($customerEntity);

        $customerEntity = $customerService->save($data, $customerEntity->getId());

        $queryTable = $this->getConnection()->createQueryTable(
            'loadCustomersOrderedByLastname', 'SELECT * FROM customers WHERE id = "42";'
        );

        $expectedRow = $queryTable->getRow(0);

        $customerRow = $customerHydrator->extract($customerEntity);

        $this->assertEquals($expectedRow, $customerRow);
    }

    public function testDeleteExistingCustomer()
    {
        $customerFilter = new CustomerInputFilter();
        $customerTable = new CustomerTable($this->adapter);

        $customerService = new CustomerService();
        $customerService->setCustomerFilter($customerFilter);
        $customerService->setCustomerTable($customerTable);

        $customerService->delete('42');

        $queryTable = $this->getConnection()->createQueryTable(
            'loadCustomersOrderedByLastname', 'SELECT * FROM customers WHERE id = "42";'
        );

        $this->assertEquals(0, $queryTable->getRowCount());
    }

    public function testDeleteNotExistingCustomer()
    {
        $customerFilter = new CustomerInputFilter();
        $customerTable = new CustomerTable($this->adapter);

        $customerService = new CustomerService();
        $customerService->setCustomerFilter($customerFilter);
        $customerService->setCustomerTable($customerTable);

        $result = $customerService->delete('99');

        $this->assertFalse($result);
    }
}