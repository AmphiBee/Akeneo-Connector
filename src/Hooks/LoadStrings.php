<?php

namespace AmphiBee\AkeneoConnector\Hooks;

use OP\Framework\Wordpress\Hook;
use AmphiBee\AkeneoConnector\Facade\LocaleStrings;

/**
 * This file is part of the Amphibee package.
 *
 * @package    AmphiBee/AkeneoConnector
 * @author     Amphibee & tgeorgel
 * @license    MIT
 * @copyright  (c) Amphibee <hello@amphibee.fr>
 * @since      1.1
 * @access     public
 */
class LoadStrings extends Hook
{
    /**
     * Event name to hook on.
     */
    public $hook = 'after_setup_theme';

    /**
     * The hook priority
     */
    public $priority = 80;


    /**
     * What to do.
     */
    public function execute()
    {
        # We need to register our string in db each time
        LocaleStrings::commit();
    }
}
