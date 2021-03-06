<?php
/**
 * Unit-Testing einer Zend Framework 2 Anwendung
 *
 * Zend Framework Session auf der International PHP Conference 2013 in München
 *
 * @package    Customer
 * @author     Ralf Eggert <r.eggert@travello.de>
 * @copyright  Ralf Eggert <r.eggert@travello.de>
 * @link       http://www.ralfeggert.de/
 */

/**
 * namespace definition and usage
 */
namespace Customer\Controller;

use Customer\Form\CustomerForm;
use Customer\Hydrator\CustomerHydrator;
use Customer\Service\CustomerService;
use InvalidArgumentException;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

/**
 * Index controller
 *
 * Handles the customer pages
 *
 * @package    Customer
 */
class IndexController extends AbstractActionController
{
    /**
     * @var CustomerService
     */
    protected $customerService;

    /**
     * @var CustomerForm
     */
    protected $customerForm;

    /**
     * set the customer service
     *
     * @param CustomerService
     */
    public function setCustomerService(CustomerService $customerService)
    {
        $this->customerService = $customerService;

        return $this;
    }

    /**
     * Get the customer service
     *
     * @return CustomerService
     */
    public function getCustomerService()
    {
        if (!isset($this->customerService)) {
            throw new InvalidArgumentException('CustomerService was not set');
        }
        return $this->customerService;
    }

    /**
     * set the customer form
     *
     * @param CustomerForm
     */
    public function setCustomerForm(CustomerForm $customerForm)
    {
        $this->customerForm = $customerForm;

        return $this;
    }

    /**
     * Get the customer form
     *
     * @return CustomerForm
     */
    public function getCustomerForm()
    {
        if (!isset($this->customerForm)) {
            throw new InvalidArgumentException('CustomerForm was not set');
        }
        return $this->customerForm;
    }

    /**
     * Handle customer list
     */
    public function indexAction()
    {
        return new ViewModel(
            array(
                'customerList' => $this->getCustomerService()->fetchList(),
            )
        );
    }

    /**
     * Handle customer entry
     */
    public function showAction()
    {
        $id = $this->params()->fromRoute('id');

        if (!$id) {
            return $this->redirect()->toRoute('customer');
        }

        $customerEntity = $this->getCustomerService()->fetchSingleById($id);

        if (!$customerEntity) {
            return $this->redirect()->toRoute('customer');
        }

        return new ViewModel(array(
            'customerEntity' => $customerEntity,
        ));
    }

    /**
     * Handle customer create
     */
    public function createAction()
    {
        $customerForm = $this->getCustomerForm();

        $request = $this->getRequest();

        if ($request->isPost()) {
            $customerEntity = $this->getCustomerService()->save($request->getPost()->toArray());

            if ($customerEntity) {
                return $this->redirect()->toRoute('customer/action', array('action' => 'update', 'id' => $customerEntity->getId()));
            }

            $customerForm->setMessages($this->getCustomerService()->getCustomerFilter()->getMessages());
            $customerForm->setData($this->getCustomerService()->getCustomerFilter()->getValues());
        }

        return new ViewModel(
            array(
                'customerForm' => $customerForm,
            )
        );
    }

    /**
     * Handle customer update
     */
    public function updateAction()
    {
        $id = $this->params()->fromRoute('id');

        $customerForm = $this->getCustomerForm();

        $request = $this->getRequest();

        if ($request->isPost()) {
            $customerEntity = $this->getCustomerService()->save($request->getPost()->toArray(), $id);

            if ($customerEntity) {
                return $this->redirect()->toRoute('customer/action', array('action' => 'update', 'id' => $customerEntity->getId()));
            }

            $customerForm->setMessages($this->getCustomerService()->getCustomerFilter()->getMessages());
            $customerForm->setData($this->getCustomerService()->getCustomerFilter()->getValues());
        } else {
            $customerEntity = $this->getCustomerService()->fetchSingleById($id);

            $customerHydrator = new CustomerHydrator();

            $customerForm->setData($customerHydrator->extract($customerEntity));
        }

        return new ViewModel(
            array(
                'customerForm' => $customerForm,
            )
        );
    }

    /**
     * Handle customer delete page
     */
    public function deleteAction()
    {
        // read id from route
        $id = (int) $this->params()->fromRoute('id');

        // check for no id
        if (!$id) {
            // Redirect to customer page
            return $this->redirect()->toRoute('customer');
        }

        // read customer entity
        $customer = $this->getCustomerService()->fetchSingleById($id);

        // check for customer entity
        if (!$customer) {
            // Redirect to customer page
            return $this->redirect()->toRoute('customer');
        }

        // delete with redirected data
        $customer = $this->getCustomerService()->delete($id);

        // Redirect to customer page
        return $this->redirect()->toRoute('customer');
    }
}
