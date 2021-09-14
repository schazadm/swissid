<?php

use PrestaShop\PrestaShop\Core\Exception\CoreException;

class SwissidAuthenticationModuleFrontController extends ModuleFrontController
{
    public function display()
    {
        $mail = 'osr.dev-@outlook.com';

        if (!$this->loginCustomer($mail)) {
            $this->context->cookie->__set('redirect_error', $this->translator->trans('Authentication failed.', [], 'Shop.Notifications.Error'));
        }

        Tools::redirect((new Link())->getPageLink('authentication', true));
    }

    private function loginCustomer($email)
    {
        try {
            $customer = new Customer();
            $authentication = $customer->getByEmail(trim($email));

            print_r($authentication);
            exit();

            if ($authentication) {
                $this->context->updateCustomer($authentication);
                // $authentication->logged = 1;
                // $this->context->customer = $authentication;
                // $this->context->cookie->id_customer = (int)$authentication->id;
                // $this->context->cookie->customer_lastname = $authentication->lastname;
                // $this->context->cookie->customer_firstname = $authentication->firstname;
                // $this->context->cookie->logged = 1;
                // $this->context->cookie->check_cgv = 1;
                // $this->context->cookie->is_guest = false;
                // $this->context->cookie->passwd = $authentication->passwd;
                // $this->context->cookie->email = $authentication->email;

                // try {
                //     $this->context->cookie->registerSession(new CustomerSession());
                // } catch (PrestaShopDatabaseException $e) {
                //     $this->errors[] = $e->getMessage();
                // } catch (PrestaShopException $e) {
                //     $this->errors[] = $e->getMessage();
                // } catch (CoreException $e) {
                //     $this->errors[] = $e->getMessage();
                // }
            }
        } catch (PrestaShopException $e) {
            return false;
        }

        return true;
    }

    private function cookieMessages()
    {
        // $this->context->cookie->__set('redirect_error', $this->translator->trans('Authentication failed.', [], 'Shop.Notifications.Error'));
        // $this->context->cookie->__set('redirect_warning', $this->translator->trans('Authentication failed.', [], 'Shop.Notifications.Error'));
        // $this->context->cookie->__set('redirect_info', $this->translator->trans('Authentication failed.', [], 'Shop.Notifications.Error'));
    }
}