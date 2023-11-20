<?php

namespace Bsz\RecordDriver;

use VuFind\RecordDriver\Feature\MarcReaderTrait;

trait AdvancedMarcReaderTrait
{
    use MarcReaderTrait;

    public function getLeader(?int $pos = null): string
    {
        $leader = $this->getMarcReader()->getLeader() ?? '';
        if (null !== $pos) {
            if (($pos >= 0) && ($pos < strlen($leader))) {
                return $leader[$pos];
            }
            return '';
        }
        return $leader;
    }

    public function getField(string $fieldTag, ?array $subfieldCodes = null): array|string
    {
        return $this->getMarcReader()->getField($fieldTag, $subfieldCodes);
    }

    public function getFields(string $fieldTag, ?array $subfieldCodes= null): array
    {
        return $this->getMarcReader()->getFields($fieldTag, $subfieldCodes);
    }

    public function getAllSubfields(array $field): array
    {
        return $this->getMarcReader()->getSubfields($field);
    }

}