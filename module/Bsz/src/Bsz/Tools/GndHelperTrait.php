<?php

namespace Bsz\Tools;

trait GndHelperTrait
{
    public function gndLinkFromId(string $id): string
    {
        return 'https://d-nb.info/gnd/' . urlencode($id);
    }

    public function gndIdFromLink(string $link): string
    {
        $matches = [];
        if (preg_match('#(?:https?://)?d-nb\.info/gnd/(.+)#', $link, $matches)) {
            return $matches[1];
        }
        return false;
    }

    public function exploreLinkFromId(string $id): string
    {
        return 'https://explore.gnd.network/gnd/' . urlencode($id);
    }
}
