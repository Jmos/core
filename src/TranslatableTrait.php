<?php

declare(strict_types=1);

namespace atk4\core;

use atk4\core\Translator\Translator;

/**
 * If a class use this trait, string can be translated calling method translate.
 */
trait TranslatableTrait
{
    /**
     * Check this property to see if trait is present in the object.
     *
     * @var bool
     */
    public $_translatableTrait = true;

    /**
     * Translates the given message.
     *
     * @param string      $message    The message to be translated
     * @param array       $parameters Array of parameters used to translate message
     * @param string|null $domain     The domain for the message or null to use the default
     * @param string|null $locale     The locale or null to use the default
     *
     * @return string The translated string
     */
    public function _($message, array $parameters = [], string $domain = null, string $locale = null): string
    {
        if (isset($this->_appScopeTrait) && $this->issetApp() && method_exists($this->getApp(), '_')) {
            return $this->getApp()->_($message, $parameters, $domain, $locale);
        }

        return Translator::instance()->_($message, $parameters, $domain, $locale);
    }
}
