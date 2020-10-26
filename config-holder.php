<?php
return array(
    /**
     * Provide your MYSQL database credentials
     */
    'db_host' => '{host}',
    'db_username' => '{username}',
    'db_name' => '{name}',
    'db_password' => '{password}',
    'c' => '{purchasecode}',
    'p' => '',

    'installed' => true,
    /**
     * Option to know if debug is enabled or not
     */
    'debug' => false,

    /**
     * Option to enable https
     */
    'https' => '{secure}',

    /**
     * cookie path
     */
    'cookie_path' => '/',

    'permalink' => true,

    'default_language' => 'en',

    'api-key' => 'normalKey',

    'crypto-key' => "def0000024ce4817f56bdbd92c5d0c1c8ba9261cad849536486e273f3992f8228439c74314c3f3772fc5f9b8eab50b900383a898ff4300d85d10b0d3b1dc2067c9923db6",

    'timezone' => 'Pacific/Niue',
    'rtl-langs' => array(
        'ar',
        'fa',
    ),

    'demo' => false,

    'site-title' => '{sitename}',
    'site-keywords' => 'Instagram, autpost, social marketing tool',
    'site-description' => 'Instagram social marketing tool',

    /** Default email templates **/

    'activation-subject' => 'Hello {full_name}! Activation your account',
    'activation-content' => "Welcome to {site-name}! 

Hello {full_name},  

Thank you for joining! We're glad to have you as community member, and we're stocked for you to start exploring our service.  
 All you need to do is activate your account: 
  {activation_link} ",
    'welcome-subject' => 'Hi {full_name}! Getting Started with Our Service',
    'welcome-content' => "Hello {full_name}! 

Congratulations! 
You have successfully signed up for our service. 
We hope you enjoy this package! We love to hear from you, 

Thanks and Best Regards!",

    /** Default email templates end */
    'max-image-size' => '10000000',
    'image-file-types' => 'jpg,png,gif,jpeg',

);