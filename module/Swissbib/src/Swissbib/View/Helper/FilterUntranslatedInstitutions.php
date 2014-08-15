<?php
namespace Swissbib\View\Helper;

use Zend\I18n\View\Helper\AbstractTranslatorHelper;

/**
 * Filter out untranslated institutions from list
 *
 */
class FilterUntranslatedInstitutions extends AbstractTranslatorHelper
{

    /**
     * Filter institutions
     *
     * @param    String[]    $institutionCodes
     * @return    String[]
     */
    public function __invoke($institutionCodes, $extended = false)
    {
        $filtered = array();

            // Filter not translated institutions
        foreach ($institutionCodes as $institutionCode) {
            $institutionLabel = $extended ? $institutionCode['institution'] : $institutionCode;
            if ($institutionLabel !== $this->translator->translate($institutionLabel, 'institution')) {
                $filtered[] = $institutionCode;
            }
        }

        return $filtered;
    }
}
