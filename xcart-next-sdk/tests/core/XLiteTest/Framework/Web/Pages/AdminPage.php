<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace XLiteTest\Framework\Web\Pages;


/**
 * Description of Page
 *
 * @author givi
 */
class AdminPage extends \XLiteTest\Framework\Web\Pages\Page{
    
    /**
     * @findBy 'cssSelector'
     * @var \WebDriverBy
     */
    protected $logOffLink = ".link-item.logoff>a>span";
    /**
     * @findBy 'cssSelector'
     * @var \WebDriverBy
     */
    protected $saveChangesButton = ".action.submit";
    
    public function load($autologin = false) {
        if ($autologin === true && !$this->isLogedIn()) {
            return $this->LogIn();
        }
        return true;
    }
    
    public function isLogedIn() {
        return $this->isElementPresent($this->logOffLink);
    }
    
    public function LogIn($user='', $password='') {
        
        if (empty($user) && empty($password)) {
            $user = $this->getConfig('admin_user', 'username');
            $password = $this->getConfig('admin_user', 'password');
        }
        
        $login = new \XLiteTest\Framework\Web\Pages\Admin\Login($this->driver, $this->storeUrl);
        if (!$login->validate()) {
            return false;
        }
        
        $login->inputEmail($user);
        $login->inputPassword($password);
        
        $login->submit();
        
        if ($login->isErrorOnPage()) {
            return false;
        }
        return true;
    }
    
    public function SaveChanges() {
        return $this->driver->findElement($this->saveChangesButton)->click();
    }

     public function __get($name) {
        if (strpos($name, 'component') === 0) { 
            $path = '\\Admin\\Components\\' . substr($name, 9);
    
            return $this->createComponent($path);
        }
        return parent::__get($name);
    }   
}
